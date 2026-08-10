<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
    <h2 class="page-title mb-0">Cursos de capacitación</h2>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/0" class="btn btn-primary ms-auto"><i class="fas fa-plus me-1"></i> Nuevo curso</a>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/areas" class="btn btn-outline-secondary btn-sm">Áreas</a>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/reports" class="btn btn-outline-secondary btn-sm">Reportes</a>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/rewards" class="btn btn-outline-secondary btn-sm">Premios</a>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/tasks" class="btn btn-outline-secondary btn-sm">Tareas</a>
</div>
<div class="row g-3">
<?php foreach ($data['courses'] as $c): ?>
    <div class="col-md-6 col-lg-4">
        <div class="lrn-course-card bg-white p-3">
            <h5 class="mb-1"><?php echo htmlspecialchars($c->title); ?></h5>
            <p class="small text-muted mb-2"><?php echo (int)$c->lesson_count; ?> lecciones · <?php echo (int)$c->question_count; ?> preguntas</p>
            <span class="badge <?php echo $c->is_published ? 'bg-success' : 'bg-warning text-dark'; ?>">
                <?php echo $c->is_published ? 'Publicado' : 'Borrador'; ?>
            </span>
            <span class="lrn-hero-stars ms-1"><i class="fas fa-star"></i> <?php echo (int)$c->stars_on_complete; ?></span>
            <div class="mt-3">
                <a href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo (int)$c->id; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>
<?php if (empty($data['courses'])): ?>
<p class="text-muted">No hay cursos. Creá uno o ejecutá <code>seed_course_excel_basico.sql</code>.</p>
<?php endif; ?>
<?php require APPROOT . '/views/inc/footer.php'; ?>
