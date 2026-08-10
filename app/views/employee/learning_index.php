<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">

<div class="lrn-catalog-hero d-flex flex-wrap align-items-center gap-3">
    <div class="flex-grow-1">
        <h2 class="h4 mb-1">Mi capacitación</h2>
        <p class="mb-0 opacity-75 small">Cursos asignados · completá lecciones y ganá estrellas</p>
    </div>
    <span class="lrn-hero-stars"><i class="fas fa-star"></i> <?php echo (int)$data['stars']; ?> estrellas</span>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?php echo URLROOT; ?>/training/stars" class="btn btn-light btn-sm">Premios</a>
        <a href="<?php echo URLROOT; ?>/training/tasks" class="btn btn-outline-light btn-sm">Mis tareas</a>
    </div>
</div>

<?php if (empty($data['courses'])): ?>
<div class="alert alert-info border-0 shadow-sm">
    <i class="fas fa-graduation-cap me-2"></i>
    No tenés cursos asignados por el momento. Cuando RRHH te asigne uno, aparecerá acá.
</div>
<?php else: ?>
<div class="row g-4">
<?php foreach ($data['courses'] as $c):
    $e = $c->enrollment;
    $pct = (int)($e->progress_percent ?? 0);
    $total = max(1, (int)$c->lesson_count);
    $status = $e->status ?? 'not_started';
    $barPct = $status === 'failed_quiz' ? min(99, $pct) : ($status === 'completed' ? 100 : $pct);
    $progressText = learning_catalog_progress_text($e, $total);
    $starsText = learning_stars_promise_text($c);
    $btnLabel = $status === 'completed' ? 'Repasar' : ($status === 'failed_quiz' ? 'Reintentar quiz' : ($status === 'not_started' ? 'Comenzar' : 'Continuar'));
?>
    <div class="col-12 col-sm-6 col-lg-4">
        <article class="lrn-course-card lrn-course-card-premium">
            <div class="lrn-course-card-thumb">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="lrn-course-card-body">
                <span class="lrn-status-chip <?php echo learning_status_badge_class($status); ?> mb-2">
                    <?php echo learning_status_label($status); ?>
                </span>
                <h5><?php echo htmlspecialchars($c->title); ?></h5>
                <?php if (!empty($c->description)): ?>
                <p class="text-muted small mb-2"><?php echo htmlspecialchars(mb_substr($c->description, 0, 90)); ?><?php echo mb_strlen($c->description) > 90 ? '…' : ''; ?></p>
                <?php endif; ?>
                <div class="lrn-progress mb-2">
                    <div class="lrn-progress-bar" style="width:<?php echo min(100, $barPct); ?>%"></div>
                </div>
                <?php if (!empty($data['reviews_ready']) && !empty($c->review_stats) && $c->review_stats->total > 0):
                    $rp = learning_review_positive_pct($c->review_stats);
                ?>
                <p class="small mb-2 lrn-card-reviews">
                    <i class="fas fa-thumbs-up text-success"></i> <?php echo (int)$c->review_stats->likes; ?>
                    <i class="fas fa-thumbs-down text-muted ms-2"></i> <?php echo (int)$c->review_stats->dislikes; ?>
                    <?php if ($rp !== null): ?><span class="text-muted ms-1">· <?php echo $rp; ?>% recomiendan</span><?php endif; ?>
                </p>
                <?php endif; ?>
                <p class="small text-muted mb-2"><?php echo htmlspecialchars($progressText); ?></p>
                <?php if ($starsText): ?>
                <p class="small text-warning mb-3"><i class="fas fa-star"></i> <?php echo htmlspecialchars($starsText); ?></p>
                <?php endif; ?>
                <a href="<?php echo URLROOT; ?>/training/course/<?php echo (int)$c->id; ?>" class="btn btn-primary btn-sm mt-auto align-self-start">
                    <?php echo $btnLabel; ?> <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </article>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require APPROOT . '/views/inc/footer.php'; ?>
