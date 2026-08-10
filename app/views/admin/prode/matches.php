<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="alert alert-light border small mb-3">
    <strong>Resultado correcto:</strong> RRHH lo carga acá abajo (goles reales). El sistema compara cada pronóstico y asigna 3 / 1 / 0 puntos.
    <br>
    <strong>Cierre de pronósticos:</strong> cada partido se bloquea automáticamente a la hora de inicio (<em>kickoff</em>) en <strong>hora Argentina (Córdoba)</strong>, por ejemplo México vs Sudáfrica a las 16:00 hs ARG — después nadie puede cargar ni cambiar ese partido.
</div>

<div class="admin-page-head">
    <div class="admin-page-brand">
        <div class="admin-page-icon">⚽</div>
        <div class="admin-page-meta">
            <h2 class="page-title">Copa del mundo 2026 — Cargar resultados</h2>
            <p class="page-subtitle mb-0">Grupo <?php echo htmlspecialchars($active_group); ?> · Estado: <?php echo prode_edition_status_label($edition->status ?? ''); ?></p>
        </div>
    </div>
    <a href="<?php echo URLROOT; ?>/prodeAdmin/ranking" class="btn btn-outline-secondary btn-sm">Ver ranking</a>
</div>

<form method="post" class="card border shadow-sm mb-3">
    <div class="card-body py-2 row g-2 align-items-end">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="edition_status">
        <div class="col-md-4">
            <label class="form-label small mb-0">Estado del PRODE</label>
            <select name="edition_status" class="form-select form-select-sm">
                <option value="upcoming" <?php echo ($edition->status ?? '') === 'upcoming' ? 'selected' : ''; ?>>Próximamente</option>
                <option value="open" <?php echo ($edition->status ?? '') === 'open' ? 'selected' : ''; ?>>Abierto</option>
                <option value="closed" <?php echo ($edition->status ?? '') === 'closed' ? 'selected' : ''; ?>>Cerrado</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Actualizar</button>
        </div>
    </div>
</form>

<ul class="nav nav-pills mb-3 flex-nowrap overflow-auto">
    <?php foreach ($groups as $g): ?>
    <li class="nav-item">
        <a class="nav-link <?php echo $g->code === $active_group ? 'active' : ''; ?>"
           href="<?php echo URLROOT; ?>/prodeAdmin/matches?group=<?php echo htmlspecialchars($g->code); ?>">
            Grupo <?php echo htmlspecialchars($g->code); ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="card border shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Partido</th>
                    <th>Kickoff</th>
                    <th>Resultado real</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($matches as $m): ?>
            <tr>
                <td><?php echo (int)$m->match_number; ?></td>
                <td>
                    <img src="<?php echo htmlspecialchars(prode_flag_url($m->home_flag)); ?>" width="20" height="20" class="rounded-circle me-1" alt="">
                    <?php echo htmlspecialchars($m->home_name); ?>
                    <span class="text-muted mx-1">vs</span>
                    <?php echo htmlspecialchars($m->away_name); ?>
                    <img src="<?php echo htmlspecialchars(prode_flag_url($m->away_flag)); ?>" width="20" height="20" class="rounded-circle ms-1" alt="">
                </td>
                <td>
                    <form method="post" class="d-flex align-items-center gap-1">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="save_kickoff">
                        <input type="hidden" name="match_id" value="<?php echo (int)$m->id; ?>">
                        <input type="hidden" name="group" value="<?php echo htmlspecialchars($active_group); ?>">
                        <input type="datetime-local" name="kickoff_at" class="form-control form-control-sm"
                               value="<?php echo htmlspecialchars(prode_kickoff_input_value($m->kickoff_at ?? '')); ?>" required>
                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Guardar horario ARG">⏱</button>
                    </form>
                    <div class="small text-muted"><?php echo prode_format_kickoff($m->kickoff_at ?? ''); ?></div>
                </td>
                <td>
                    <form method="post" class="d-flex align-items-center gap-1">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="save_result">
                        <input type="hidden" name="match_id" value="<?php echo (int)$m->id; ?>">
                        <input type="hidden" name="group" value="<?php echo htmlspecialchars($active_group); ?>">
                        <input type="number" name="home_score_actual" class="form-control form-control-sm" style="width:3rem"
                               min="0" max="20" value="<?php echo $m->home_score_actual !== null ? (int)$m->home_score_actual : ''; ?>" required>
                        <span>:</span>
                        <input type="number" name="away_score_actual" class="form-control form-control-sm" style="width:3rem"
                               min="0" max="20" value="<?php echo $m->away_score_actual !== null ? (int)$m->away_score_actual : ''; ?>" required>
                        <button type="submit" class="btn btn-sm btn-success">OK</button>
                    </form>
                </td>
                <td>
                    <?php if ($m->status === 'finished'): ?>
                    <span class="badge bg-success">Final</span>
                    <?php elseif (prode_is_match_locked($m)): ?>
                    <span class="badge bg-warning text-dark">En juego</span>
                    <?php else: ?>
                    <span class="badge bg-secondary">Programado</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
