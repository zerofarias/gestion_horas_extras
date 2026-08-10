<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/prode.css">

<?php
$edition = $edition ?? null;
$entry = $entry ?? null;
$groups = $groups ?? [];
$matches = $matches ?? [];
$predMap = $pred_map ?? [];
$totalMatches = (int)($total_matches ?? 0);
$filledCount = (int)($filled_count ?? 0);
$activeGroup = $active_group ?? 'A';
$groupIndex = (int)($group_index ?? 0);
$groupTotal = (int)($group_total ?? 12);
$prevGroup = $prev_group ?? null;
$nextGroup = $next_group ?? null;
$groupFilled = (int)($group_filled ?? 0);
$groupMatchCount = (int)($group_match_count ?? 6);
$isSubmitted = ($entry->status ?? '') === 'submitted';
$pct = $totalMatches > 0 ? (int)round(($filledCount / $totalMatches) * 100) : 0;
?>

<div class="prode-page">
    <div class="prode-hero">
        <div class="prode-hero-icon">⚽</div>
        <div>
            <h1 class="h4 mb-1"><?php echo htmlspecialchars($edition->title ?? 'Copa del mundo 2026'); ?></h1>
            <p class="small text-muted mb-0">Fase de grupos · Puntos: 3 exacto · 1 resultado · 0 fallo</p>
        </div>
        <div class="prode-hero-score text-end">
            <div class="prode-hero-score-val"><?php echo (int)($entry->total_points ?? 0); ?></div>
            <div class="small text-muted">tus puntos</div>
        </div>
    </div>

    <div class="prode-progress card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex justify-content-between align-items-center small mb-1">
                <span><strong id="prode-filled-label"><?php echo $filledCount; ?>/<?php echo $totalMatches; ?></strong> partidos cargados</span>
                <span id="prode-progress-pct"><?php echo $pct; ?>%</span>
            </div>
            <div class="progress" style="height:8px">
                <div class="progress-bar bg-success" id="prode-progress-bar" style="width:<?php echo $pct; ?>%"></div>
            </div>
            <?php if ($isSubmitted): ?>
            <div class="alert alert-success py-1 px-2 small mt-2 mb-0">
                <i class="fas fa-check-circle me-1"></i>
                Pronósticos confirmados<?php echo !empty($entry->submitted_at) ? ' el ' . date('d/m/Y H:i', strtotime($entry->submitted_at)) : ''; ?>.
            </div>
            <?php else: ?>
            <p class="small text-muted mb-0 mt-2">
                Completá local y visitante: se guarda solo y <strong>no podés cambiarlo</strong> después.
            </p>
            <?php endif; ?>
        </div>
    </div>

    <div class="prode-group-nav card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <div class="d-flex align-items-center justify-content-between gap-2">
                <?php if ($prevGroup): ?>
                <a href="<?php echo URLROOT; ?>/prode/index?group=<?php echo htmlspecialchars($prevGroup); ?>"
                   class="btn btn-outline-secondary btn-sm prode-group-nav-btn prode-group-link">
                    <i class="fas fa-chevron-left"></i><span class="d-none d-sm-inline"> Anterior</span>
                </a>
                <?php else: ?>
                <span class="btn btn-outline-secondary btn-sm disabled prode-group-nav-btn" aria-hidden="true">
                    <i class="fas fa-chevron-left"></i>
                </span>
                <?php endif; ?>

                <div class="text-center flex-grow-1">
                    <div class="fw-bold">Grupo <?php echo htmlspecialchars($activeGroup); ?></div>
                    <div class="small text-muted">
                        <?php echo ($groupIndex + 1); ?> de <?php echo $groupTotal; ?>
                        · <?php echo $groupFilled; ?>/<?php echo $groupMatchCount; ?> guardados
                    </div>
                </div>

                <?php if ($nextGroup): ?>
                <a href="<?php echo URLROOT; ?>/prode/index?group=<?php echo htmlspecialchars($nextGroup); ?>"
                   class="btn btn-primary btn-sm prode-group-nav-btn prode-group-link" id="prode-next-group">
                    <span class="d-none d-sm-inline">Siguiente </span><i class="fas fa-chevron-right"></i>
                </a>
                <?php else: ?>
                <span class="btn btn-outline-secondary btn-sm disabled prode-group-nav-btn" aria-hidden="true">
                    <i class="fas fa-chevron-right"></i>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <ul class="nav nav-pills prode-group-tabs flex-nowrap overflow-auto mb-3 d-none d-md-flex" role="tablist">
        <?php foreach ($groups as $g): ?>
        <li class="nav-item">
            <a class="nav-link prode-group-link <?php echo $g->code === $activeGroup ? 'active' : ''; ?>"
               href="<?php echo URLROOT; ?>/prode/index?group=<?php echo htmlspecialchars($g->code); ?>">
                Grupo <?php echo htmlspecialchars($g->code); ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="prode-matches-list">
        <?php foreach ($matches as $m):
            $pred = $predMap[(int)$m->id] ?? null;
            $saved = prode_prediction_is_saved($pred);
            $kickoffLocked = prode_is_match_locked($m);
            $locked = $kickoffLocked || $saved;
            $ph = $pred && $pred->home_score_pred !== null ? (int)$pred->home_score_pred : '';
            $pa = $pred && $pred->away_score_pred !== null ? (int)$pred->away_score_pred : '';
            $kick = prode_format_kickoff($m->kickoff_at ?? '', false);
            $pts = $pred ? (int)$pred->points_earned : null;
        ?>
        <div class="prode-match card border-0 shadow-sm mb-2 <?php echo $kickoffLocked ? 'is-locked' : ($saved ? 'is-saved' : ''); ?>"
             data-match-id="<?php echo (int)$m->id; ?>"
             data-locked="<?php echo $kickoffLocked ? '1' : '0'; ?>"
             data-saved="<?php echo $saved ? '1' : '0'; ?>">
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                    <span>Partido <?php echo (int)$m->match_number; ?> · <?php echo htmlspecialchars($kick); ?></span>
                    <span class="prode-save-status text-muted" data-status-id="<?php echo (int)$m->id; ?>">
                        <?php if ($locked && $m->status === 'finished'): ?>
                            <?php if ($pts !== null): ?><span class="badge bg-<?php echo $pts >= 3 ? 'success' : ($pts >= 1 ? 'warning text-dark' : 'secondary'); ?>"><?php echo $pts; ?> pt</span><?php endif; ?>
                        <?php elseif ($saved): ?>
                            <i class="fas fa-lock text-success"></i> Guardado
                        <?php endif; ?>
                    </span>
                </div>
                <div class="prode-match-row">
                    <div class="prode-team prode-team--home">
                        <img src="<?php echo htmlspecialchars(prode_flag_url($m->home_flag)); ?>" alt="" class="prode-flag" width="32" height="32">
                        <span class="prode-team-name"><?php echo htmlspecialchars($m->home_name); ?></span>
                    </div>
                    <div class="prode-scores">
                        <input type="number" min="0" max="20" class="form-control form-control-sm prode-score-input"
                               data-side="home" value="<?php echo $ph !== '' ? $ph : ''; ?>"
                               <?php echo $locked ? 'disabled' : ''; ?> aria-label="Goles local">
                        <span class="prode-score-sep">:</span>
                        <input type="number" min="0" max="20" class="form-control form-control-sm prode-score-input"
                               data-side="away" value="<?php echo $pa !== '' ? $pa : ''; ?>"
                               <?php echo $locked ? 'disabled' : ''; ?> aria-label="Goles visitante">
                    </div>
                    <div class="prode-team prode-team--away">
                        <img src="<?php echo htmlspecialchars(prode_flag_url($m->away_flag)); ?>" alt="" class="prode-flag" width="32" height="32">
                        <span class="prode-team-name"><?php echo htmlspecialchars($m->away_name); ?></span>
                    </div>
                </div>
                <?php if ($kickoffLocked && $m->status === 'finished'): ?>
                <div class="small text-center text-muted mt-1">
                    Resultado oficial: <strong><?php echo (int)$m->home_score_actual; ?> - <?php echo (int)$m->away_score_actual; ?></strong>
                </div>
                <?php elseif ($kickoffLocked): ?>
                <div class="small text-center text-warning mt-1">
                    <i class="fas fa-clock me-1"></i>Ya comenzó (<?php echo htmlspecialchars($kick); ?>). No se pueden cargar pronósticos.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($nextGroup): ?>
    <div class="prode-group-nav-bottom mb-3">
        <a href="<?php echo URLROOT; ?>/prode/index?group=<?php echo htmlspecialchars($nextGroup); ?>"
           class="btn btn-outline-primary w-100 prode-next-group-bottom prode-group-link">
            Siguiente grupo (<?php echo htmlspecialchars($nextGroup); ?>) <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
    <?php endif; ?>

    <?php if (($edition->status ?? '') === 'open' && !$isSubmitted): ?>
    <div class="prode-submit-bar">
        <form method="post" action="<?php echo URLROOT; ?>/prode/submit" id="prode-submit-form">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-primary w-100 btn-lg"
                    id="prode-submit-btn"
                    <?php echo $filledCount < $totalMatches ? 'disabled' : ''; ?>>
                <i class="fas fa-paper-plane me-1"></i>
                Confirmar mis pronósticos (<?php echo $filledCount; ?>/<?php echo $totalMatches; ?>)
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
window.PRODE_CONFIG = {
    saveUrl: <?php echo json_encode(URLROOT . '/prode/savePrediction'); ?>,
    csrfToken: <?php echo json_encode($csrf_token ?? csrf_token()); ?>,
    totalMatches: <?php echo (int)$totalMatches; ?>,
    nextGroupUrl: <?php echo json_encode($nextGroup ? URLROOT . '/prode/index?group=' . $nextGroup : ''); ?>
};
</script>
<script src="<?php echo URLROOT; ?>/js/prode-autosave.js"></script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
