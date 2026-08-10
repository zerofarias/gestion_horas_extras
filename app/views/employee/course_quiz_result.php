<?php
$r = $data['result'];
require APPROOT . '/views/inc/header.php';
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="text-center py-5">
    <?php if ($r['passed']): ?>
    <div class="lrn-stars-burst text-warning mb-3"><i class="fas fa-star"></i></div>
    <h2 class="text-success">¡Aprobaste!</h2>
    <?php if (!empty($r['stars'])): ?>
    <p class="lead">Ganaste <strong><?php echo (int)$r['stars']; ?></strong> estrellas en total</p>
    <?php if (!empty($r['base_stars'])): ?>
    <p class="small text-muted mb-0"><?php echo (int)$r['base_stars']; ?> por aprobar el curso
    <?php if (!empty($r['bonus_stars'])): ?> + <?php echo (int)$r['bonus_stars']; ?> bonus<?php endif; ?>
    </p>
    <?php endif; ?>
    <?php if (!empty($r['is_first_finisher'])): ?>
    <p class="alert alert-warning d-inline-block mt-2 py-2 px-3"><i class="fas fa-trophy me-1"></i> ¡Fuiste el primero en completar este curso!</p>
    <?php endif; ?>
    <p class="text-muted mt-2">Total en tu cuenta: <?php echo (int)$data['stars']; ?> estrellas</p>
    <?php endif; ?>
    <?php else: ?>
    <h2 class="text-danger">No aprobaste</h2>
    <p>Intentos restantes: <?php echo (int)$r['attempts_left']; ?></p>
    <?php endif; ?>
    <p class="mb-4">Puntaje: <strong><?php echo (int)$r['score']; ?>%</strong> (<?php echo (int)$r['correct']; ?>/<?php echo (int)$r['total']; ?>)</p>
    <a href="<?php echo URLROOT; ?>/training/index" class="btn btn-primary">Volver a mis cursos</a>
    <?php if (!$r['passed'] && $r['attempts_left'] > 0): ?>
    <a href="<?php echo URLROOT; ?>/training/quiz/<?php echo (int)$data['course']->id; ?>" class="btn btn-outline-secondary ms-2">Reintentar</a>
    <?php endif; ?>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
