<?php
$c = $data['course'];
$cid = $c ? (int)$c->id : 0;
$activeTab = preg_replace('/[^a-z]/', '', $data['active_tab'] ?? 'overview');
if (isset($_GET['edit_lesson'])) {
    $activeTab = 'lesson';
}
$editLessonId = isset($_GET['edit_lesson']) && $_GET['edit_lesson'] !== 'new' ? (int)$_GET['edit_lesson'] : 0;
$editLesson = null;
foreach ($data['lessons'] as $l) {
    if ((int)$l->id === $editLessonId) {
        $editLesson = $l;
        break;
    }
}
$enrich = !empty($data['enrich_ready']);
require APPROOT . '/views/inc/header.php';
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<script src="<?php echo URLROOT; ?>/js/learning-admin.js" defer></script>

<?php if (!$enrich && $cid): ?>
<div class="alert alert-info mb-3">
    <i class="fas fa-database me-1"></i>
    Para materiales, anotaciones y comunidad ejecutá <code>migration_learning_enrich.sql</code> en MySQL (ver MIGRATIONS.md paso 14).
</div>
<?php endif; ?>

<?php if ($cid && $c): ?>
<div class="lrn-workspace-header mb-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div class="flex-grow-1 min-w-0">
            <a href="<?php echo URLROOT; ?>/trainingAdmin/courses" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left"></i> Cursos</a>
            <h1 class="h3 mb-1 mt-1"><?php echo htmlspecialchars($c->title); ?></h1>
            <p class="text-muted small mb-0"><?php echo (int)count($data['lessons']); ?> lecciones · <?php echo (int)count($data['questions']); ?> preguntas
                <?php if ($enrich): ?> · <?php echo count($data['resources']); ?> materiales<?php endif; ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge <?php echo $c->is_published ? 'bg-success' : 'bg-warning text-dark'; ?> fs-6">
                <?php echo $c->is_published ? 'Publicado' : 'Borrador'; ?>
            </span>
            <?php if ($enrich && $data['pending_questions'] > 0): ?>
            <span class="badge bg-danger"><?php echo (int)$data['pending_questions']; ?> preguntas sin responder</span>
            <?php endif; ?>
            <a href="<?php echo URLROOT; ?>/training/course/<?php echo $cid; ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                <i class="fas fa-eye me-1"></i> Vista empleado
            </a>
        </div>
    </div>
</div>

<ul class="nav nav-tabs lrn-workspace-tabs mb-3 flex-nowrap overflow-auto" role="tablist">
    <li class="nav-item"><a class="nav-link <?php echo $activeTab === 'overview' ? 'active' : ''; ?>" href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo $cid; ?>?tab=overview">General</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $activeTab === 'lessons' ? 'active' : ''; ?>" href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo $cid; ?>?tab=lessons">Lecciones</a></li>
    <?php if ($activeTab === 'lesson'): ?>
    <li class="nav-item"><a class="nav-link active" href="#"><?php echo $editLesson ? 'Editar: ' . htmlspecialchars(mb_substr($editLesson->title, 0, 18)) : 'Nueva lección'; ?></a></li>
    <?php endif; ?>
    <?php if ($enrich): ?>
    <li class="nav-item"><a class="nav-link <?php echo $activeTab === 'materials' ? 'active' : ''; ?>" href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo $cid; ?>?tab=materials">Materiales</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $activeTab === 'community' ? 'active' : ''; ?>" href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo $cid; ?>?tab=community">Comunidad <?php if ($data['pending_questions']): ?><span class="badge bg-danger"><?php echo (int)$data['pending_questions']; ?></span><?php endif; ?></a></li>
    <?php endif; ?>
    <li class="nav-item"><a class="nav-link <?php echo $activeTab === 'quiz' ? 'active' : ''; ?>" href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo $cid; ?>?tab=quiz">Cuestionario</a></li>
    <li class="nav-item"><a class="nav-link <?php echo $activeTab === 'assign' ? 'active' : ''; ?>" href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo $cid; ?>?tab=assign">Asignación</a></li>
</ul>

<div class="tab-content">
<?php
$tabFile = APPROOT . '/views/admin/training/partials/tab_' . preg_replace('/[^a-z]/', '', $activeTab) . '.php';
if (!file_exists($tabFile)) {
    $tabFile = APPROOT . '/views/admin/training/partials/tab_overview.php';
}
require $tabFile;
?>
</div>

<?php else: ?>
<h2 class="page-title">Nuevo curso</h2>
<?php require APPROOT . '/views/admin/training/partials/tab_overview.php'; ?>
<?php endif; ?>

<?php require APPROOT . '/views/inc/footer.php'; ?>
