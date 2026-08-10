<?php
$course = $data['course'];
$lesson = $data['lesson'];
$lessons = $data['lessons'];
$completedIds = $data['completedIds'];
$pos = (int)$data['position'];
$enrollment = $data['enrollment'];
$total = max(1, count($lessons));
$progressMeta = $data['progress_meta'] ?? learning_enrollment_progress_meta($lessons, $completedIds, $enrollment);
$lessonBase = URLROOT . '/training/lesson/' . (int)$course->id . '/' . $pos;
$panel = $data['panel'] ?? preg_replace('/[^a-z]/', '', $_GET['panel'] ?? 'content');
$resourcesReady = !empty($data['resources_ready']);
$notesReady = !empty($data['notes_ready']);
$communityReady = !empty($data['discussions_ready']);
$reviewsReady = !empty($data['reviews_ready']);
$lessonMetaReady = learning_lesson_meta_is_ready();
$hasTabs = ($lesson && ($resourcesReady || $notesReady || $communityReady)) || $reviewsReady;
require APPROOT . '/views/inc/header.php';
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?php echo URLROOT; ?>/training/index" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Mis cursos</a>
    <span class="lrn-hero-stars ms-auto"><i class="fas fa-star"></i> <?php echo (int)$data['stars']; ?></span>
</div>

<div class="lrn-player-shell lrn-player-premium">
    <div class="lrn-player-top">
        <h2><?php echo htmlspecialchars($course->title); ?></h2>
        <span class="lrn-player-progress-label"><?php echo (int)$progressMeta['required_done']; ?>/<?php echo (int)$progressMeta['required_total']; ?> obligatorias · <?php echo htmlspecialchars($progressMeta['label']); ?></span>
    </div>
    <div class="lrn-progress mb-3"><div class="lrn-progress-bar" style="width:<?php echo (int)$progressMeta['bar_percent']; ?>%"></div></div>

    <?php if ($hasTabs): ?>
    <ul class="nav nav-pills lrn-lesson-pills mb-3 flex-nowrap overflow-auto">
        <?php if ($lesson): ?>
        <li class="nav-item"><a class="nav-link <?php echo $panel === 'content' ? 'active' : ''; ?>" href="<?php echo URLROOT; ?>/training/lesson/<?php echo (int)$course->id; ?>/<?php echo $pos; ?>?panel=content"><i class="fas fa-play me-1"></i> Contenido</a></li>
        <?php endif; ?>
        <?php if ($resourcesReady && $lesson): ?>
        <li class="nav-item"><a class="nav-link <?php echo $panel === 'materials' ? 'active' : ''; ?>" href="<?php echo $lessonBase; ?>?panel=materials"><i class="fas fa-folder-open me-1"></i> Materiales <?php $matCount = count($data['resources']) + count($data['course_resources']); if ($matCount): ?><span class="badge bg-secondary"><?php echo $matCount; ?></span><?php endif; ?></a></li>
        <?php endif; ?>
        <?php if ($notesReady && $lesson): ?>
        <li class="nav-item"><a class="nav-link <?php echo $panel === 'notes' ? 'active' : ''; ?>" href="<?php echo $lessonBase; ?>?panel=notes"><i class="fas fa-sticky-note me-1"></i> Notas</a></li>
        <?php endif; ?>
        <?php if ($communityReady && $lesson): ?>
        <li class="nav-item"><a class="nav-link <?php echo $panel === 'community' ? 'active' : ''; ?>" href="<?php echo $lessonBase; ?>?panel=community"><i class="fas fa-comments me-1"></i> Preguntas</a></li>
        <?php endif; ?>
        <?php if ($reviewsReady): ?>
        <li class="nav-item"><a class="nav-link <?php echo $panel === 'reviews' ? 'active' : ''; ?>" href="<?php echo $lesson ? $lessonBase . '?panel=reviews' : URLROOT . '/training/lesson/' . (int)$course->id . '/1?panel=reviews'; ?>"><i class="fas fa-star-half-alt me-1"></i> Reseñas</a></li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>

    <div class="row g-4 lrn-player-layout">
        <div class="col-lg-8 order-1 order-lg-1 lrn-player-main">
            <?php if ($panel === 'materials' && $resourcesReady): ?>
            <div class="lrn-lesson-main">
                <h3 class="h5 mb-3"><i class="fas fa-folder-open me-1"></i> Materiales de apoyo</h3>
                <?php if (empty($data['resources']) && empty($data['course_resources'])): ?>
                <p class="text-muted">No hay materiales para esta lección.</p>
                <?php else: ?>
                <?php if (!empty($data['resources'])): ?>
                <h6 class="text-muted small text-uppercase">De esta lección</h6>
                <?php foreach ($data['resources'] as $r): $url = learning_resource_url($r); ?>
                <div class="lrn-resource-item">
                    <i class="fas <?php echo learning_resource_icon($r->resource_type); ?> fa-lg"></i>
                    <div class="flex-grow-1">
                        <strong><?php echo htmlspecialchars($r->title); ?></strong>
                        <?php if ($r->description): ?><p class="small text-muted mb-0"><?php echo htmlspecialchars($r->description); ?></p><?php endif; ?>
                    </div>
                    <?php if ($url): ?><a href="<?php echo htmlspecialchars($url); ?>" class="btn btn-sm btn-primary" target="_blank" rel="noopener"><?php echo $r->file_path ? 'Descargar' : 'Abrir'; ?></a><?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
                <?php if (!empty($data['course_resources'])): ?>
                <h6 class="text-muted small text-uppercase mt-3">Del curso (generales)</h6>
                <?php foreach ($data['course_resources'] as $r): $url = learning_resource_url($r); ?>
                <div class="lrn-resource-item">
                    <i class="fas <?php echo learning_resource_icon($r->resource_type); ?> fa-lg"></i>
                    <div class="flex-grow-1"><strong><?php echo htmlspecialchars($r->title); ?></strong></div>
                    <?php if ($url): ?><a href="<?php echo htmlspecialchars($url); ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Abrir</a><?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
                <?php endif; ?>
            </div>

            <?php elseif ($panel === 'notes' && $notesReady && $lesson): ?>
            <div class="lrn-lesson-main">
                <h3 class="h5 mb-3"><i class="fas fa-sticky-note me-1"></i> Mis notas privadas</h3>
                <p class="small text-muted">Solo vos podés ver estas notas. Se guardan por lección.</p>
                <form method="post" action="<?php echo URLROOT; ?>/training/saveNote/<?php echo (int)$lesson->id; ?>">
                    <?php echo csrf_field(); ?>
                    <textarea name="body" class="form-control" rows="10" placeholder="Escribí tus apuntes, dudas personales, recordatorios…"><?php echo htmlspecialchars($data['user_note']->body ?? ''); ?></textarea>
                    <button type="submit" class="btn btn-primary mt-2"><i class="fas fa-save me-1"></i> Guardar notas</button>
                </form>
            </div>

            <?php elseif ($panel === 'community' && $communityReady): ?>
            <div class="lrn-lesson-main">
                <h3 class="h5 mb-3"><i class="fas fa-comments me-1"></i> Preguntas y sugerencias</h3>
                <form method="post" action="<?php echo URLROOT; ?>/training/postDiscussion/<?php echo (int)$course->id; ?>" class="card mb-4">
                    <div class="card-body">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="lesson_id" value="<?php echo $lesson ? (int)$lesson->id : ''; ?>">
                        <input type="hidden" name="lesson_position" value="<?php echo $pos; ?>">
                        <div class="mb-2">
                            <select name="post_type" class="form-select form-select-sm">
                                <option value="question">Tengo una pregunta</option>
                                <option value="suggestion">Sugerencia de mejora</option>
                                <option value="comment">Comentario</option>
                            </select>
                        </div>
                        <textarea name="body" class="form-control" rows="3" required placeholder="Escribí tu mensaje…"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm mt-2">Enviar</button>
                    </div>
                </form>
                <?php if (empty($data['discussions'])): ?>
                <p class="text-muted small">Sé el primero en preguntar sobre esta lección.</p>
                <?php else: foreach ($data['discussions'] as $d): ?>
                <div class="border rounded p-3 mb-2 <?php echo $d->admin_reply ? 'border-success' : ''; ?>">
                    <span class="badge bg-light text-dark"><?php echo learning_discussion_type_label($d->post_type); ?></span>
                    <span class="small text-muted float-end"><?php echo htmlspecialchars($d->created_at); ?></span>
                    <p class="mb-1 mt-2"><?php echo nl2br(htmlspecialchars($d->body)); ?></p>
                    <?php if ($d->admin_reply): ?>
                    <div class="alert alert-success small mb-0 py-2"><strong>Respuesta:</strong><br><?php echo nl2br(htmlspecialchars($d->admin_reply)); ?></div>
                    <?php else: ?>
                    <p class="small text-muted mb-0"><i class="fas fa-clock"></i> Pendiente de respuesta</p>
                    <?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <?php elseif ($panel === 'reviews' && $reviewsReady): ?>
            <div class="lrn-lesson-main">
                <?php require APPROOT . '/views/employee/partials/course_review_panel.php'; ?>
            </div>

            <?php elseif ($lesson && ($panel === 'content' || !$hasTabs)): ?>
            <div class="lrn-lesson-main">
                <div class="lrn-lesson-meta">
                    <i class="fas <?php echo learning_lesson_type_icon($lesson->content_type); ?>"></i>
                    Lección <?php echo (int)$lesson->position; ?> de <?php echo $total; ?>
                    · ~<?php echo (int)$lesson->duration_minutes; ?> min
                </div>
                <h1 class="lrn-lesson-title"><?php echo htmlspecialchars($lesson->title); ?></h1>

                <?php if ($lessonMetaReady && !empty($lesson->objectives)): ?>
                <div class="lrn-objectives mb-3">
                    <strong><i class="fas fa-bullseye me-1"></i> Objetivos</strong>
                    <p class="mb-0 small"><?php echo nl2br(htmlspecialchars($lesson->objectives)); ?></p>
                </div>
                <?php endif; ?>

                <?php if ($lessonMetaReady && !empty($lesson->instructor_notes)): ?>
                <div class="lrn-instructor-tip mb-3">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <div><strong>Nota del instructor</strong><br><?php echo nl2br(htmlspecialchars($lesson->instructor_notes)); ?></div>
                </div>
                <?php endif; ?>

                <?php require APPROOT . '/views/employee/partials/lesson_content.php'; ?>

                <?php if ($lessonMetaReady && !empty($lesson->key_points)): ?>
                <div class="card bg-light border-0 mt-3">
                    <div class="card-body small"><strong><i class="fas fa-key me-1"></i> Puntos clave</strong><br><?php echo nl2br(htmlspecialchars($lesson->key_points)); ?></div>
                </div>
                <?php endif; ?>

                <?php $done = in_array((int)$lesson->id, $completedIds, true); ?>
                <div class="lrn-action-bar">
                    <?php if ($pos > 1): ?>
                    <a href="<?php echo URLROOT; ?>/training/lesson/<?php echo (int)$course->id; ?>/<?php echo $pos - 1; ?>" class="btn btn-outline-secondary"><i class="fas fa-chevron-left"></i> Anterior</a>
                    <?php endif; ?>
                    <?php if (!$done): ?>
                    <form method="post" action="<?php echo URLROOT; ?>/training/completeLesson/<?php echo (int)$course->id; ?>/<?php echo (int)$lesson->id; ?>" class="lrn-action-primary">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-success lrn-btn-complete w-100"><i class="fas fa-check me-1"></i> Completé esta lección</button>
                    </form>
                    <?php else: ?>
                    <span class="text-success ms-auto"><i class="fas fa-check-circle"></i> Completada</span>
                    <?php if ($data['allLessonsDone'] && $course->require_quiz): ?>
                    <a href="<?php echo URLROOT; ?>/training/quiz/<?php echo (int)$course->id; ?>" class="btn btn-primary">Cuestionario <i class="fas fa-chevron-right"></i></a>
                    <?php elseif ($pos < $total): ?>
                    <a href="<?php echo URLROOT; ?>/training/lesson/<?php echo (int)$course->id; ?>/<?php echo $pos + 1; ?>" class="btn btn-primary">Siguiente <i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <p class="text-muted">Sin lecciones en este curso.</p>
            <?php endif; ?>
        </div>

        <div class="col-lg-4 order-2 order-lg-2 lrn-lesson-sidebar">
            <div class="lrn-sidebar-card">
                <div class="card-header py-2">Programa</div>
                <div class="list-group list-group-flush">
                    <?php foreach ($lessons as $l):
                        $isDone = in_array((int)$l->id, $completedIds, true);
                        $active = (int)$l->position === $pos;
                    ?>
                    <a href="<?php echo URLROOT; ?>/training/lesson/<?php echo (int)$course->id; ?>/<?php echo (int)$l->position; ?>"
                       class="list-group-item list-group-item-action <?php echo $active ? 'active' : ''; ?> <?php echo $isDone ? 'done' : ''; ?>">
                        <i class="fas <?php echo learning_lesson_type_icon($l->content_type); ?> lesson-type-icon"></i>
                        <?php if ($isDone): ?><i class="fas fa-check text-success me-1" style="font-size:.7rem"></i><?php endif; ?>
                        <?php echo (int)$l->position; ?>. <?php echo htmlspecialchars($l->title); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if ($data['allLessonsDone'] && $course->require_quiz): ?>
            <a href="<?php echo URLROOT; ?>/training/quiz/<?php echo (int)$course->id; ?>" class="btn btn-warning w-100 mt-3"><i class="fas fa-clipboard-check me-1"></i> Cuestionario final</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php if ($reviewsReady): ?>
<script src="<?php echo URLROOT; ?>/js/learning-player.js" defer></script>
<?php endif; ?>
<?php require APPROOT . '/views/inc/footer.php'; ?>
