<?php
require APPROOT . '/views/inc/header.php';
$course = $data['course'];
$lesson = $data['lesson'];
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="container py-4">
    <div class="alert alert-secondary d-flex justify-content-between align-items-center">
        <span><i class="fas fa-eye me-1"></i> Vista previa — como la verá el empleado</span>
        <a href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo (int)$course->id; ?>?tab=lesson&edit_lesson=<?php echo (int)$lesson->id; ?>" class="btn btn-sm btn-primary">Volver a editar</a>
    </div>
    <h1 class="h4"><?php echo htmlspecialchars($course->title); ?></h1>
    <h2 class="h5 text-muted"><?php echo htmlspecialchars($lesson->title); ?></h2>
    <?php if (!empty($lesson->objectives)): ?>
    <div class="alert alert-light border"><strong>Objetivos:</strong> <?php echo nl2br(htmlspecialchars($lesson->objectives)); ?></div>
    <?php endif; ?>
    <?php if (!empty($lesson->instructor_notes)): ?>
    <div class="lrn-instructor-tip mb-3"><i class="fas fa-chalkboard-teacher me-2"></i><strong>Nota del instructor</strong><br><?php echo nl2br(htmlspecialchars($lesson->instructor_notes)); ?></div>
    <?php endif; ?>
    <?php if (!empty($lesson->key_points)): ?>
    <div class="card mb-3"><div class="card-body small"><strong>Puntos clave</strong><br><?php echo nl2br(htmlspecialchars($lesson->key_points)); ?></div></div>
    <?php endif; ?>
    <?php $learningStreamAdmin = true; require APPROOT . '/views/employee/partials/lesson_content.php'; ?>
    <?php if (!empty($data['resources'])): ?>
    <h3 class="h6 mt-4">Materiales de apoyo</h3>
    <ul class="list-group">
        <?php foreach ($data['resources'] as $r): $url = learning_resource_url($r, true); ?>
        <li class="list-group-item d-flex align-items-center gap-2">
            <i class="fas <?php echo learning_resource_icon($r->resource_type); ?>"></i>
            <?php echo htmlspecialchars($r->title); ?>
            <?php if ($url): ?><a href="<?php echo htmlspecialchars($url); ?>" class="btn btn-sm btn-outline-primary ms-auto" target="_blank">Abrir</a><?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
