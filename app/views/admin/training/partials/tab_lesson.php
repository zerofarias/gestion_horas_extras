<?php
/** @var $c,$cid,$data,$editLesson,$editLessonId,$enrich */
$isNew = isset($_GET['edit_lesson']) && $_GET['edit_lesson'] === 'new';
$editLesson = $isNew ? null : ($editLesson ?? null);
$lessonResources = [];
if ($enrich && $editLesson) {
    foreach ($data['resources'] as $r) {
        if ((int)$r->lesson_id === (int)$editLesson->id) {
            $lessonResources[] = $r;
        }
    }
}
?>
<div class="row g-4">
    <div class="col-lg-7">
        <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveLesson/<?php echo $cid; ?>" enctype="multipart/form-data" id="lessonEditorForm">
            <?php echo csrf_field(); ?>
            <?php if ($editLesson): ?><input type="hidden" name="lesson_id" value="<?php echo (int)$editLesson->id; ?>"><?php endif; ?>

            <div class="card shadow mb-3">
                <div class="card-header">Información de la lección</div>
                <div class="card-body">
                    <div class="row g-2 mb-3">
                        <div class="col-2">
                            <label class="form-label small">Nº</label>
                            <input type="number" name="position" class="form-control" min="1" value="<?php echo $editLesson ? (int)$editLesson->position : count($data['lessons']) + 1; ?>">
                        </div>
                        <div class="col-10">
                            <label class="form-label small">Título</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo $editLesson ? htmlspecialchars($editLesson->title) : ''; ?>">
                        </div>
                    </div>
                    <?php if ($enrich): ?>
                    <div class="mb-3">
                        <label class="form-label small">Objetivos de aprendizaje</label>
                        <textarea name="objectives" class="form-control" rows="2" placeholder="Qué aprenderá el alumno en esta lección"><?php echo $editLesson ? htmlspecialchars($editLesson->objectives ?? '') : ''; ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Puntos clave</label>
                        <textarea name="key_points" class="form-control" rows="2" placeholder="Resumen en viñetas o frases cortas"><?php echo $editLesson ? htmlspecialchars($editLesson->key_points ?? '') : ''; ?></textarea>
                    </div>
                    <?php endif; ?>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Tipo</label>
                            <select name="content_type" id="lessonContentType" class="form-select">
                                <?php foreach (['text'=>'Texto','video'=>'Video','file'=>'Archivo (PDF, Excel…)'] as $v=>$lbl): ?>
                                <option value="<?php echo $v; ?>" <?php echo ($editLesson ? $editLesson->content_type : ($isNew ? 'video' : 'text')) === $v ? 'selected' : ''; ?>><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Duración (min)</label>
                            <input type="number" name="duration_minutes" class="form-control" min="1" value="<?php echo $editLesson ? (int)$editLesson->duration_minutes : 5; ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_required" class="form-check-input" id="reqL" <?php echo !$editLesson || $editLesson->is_required ? 'checked' : ''; ?>>
                                <label for="reqL">Obligatoria</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-3">
                <div class="card-header">Contenido principal</div>
                <div class="card-body">
                    <div id="field-text" class="lrn-content-fields mb-0">
                        <label class="form-label">Cuerpo de la lección</label>
                        <textarea name="content_body" class="form-control" rows="8" placeholder="Explicación, pasos, ejemplos…"><?php echo $editLesson ? htmlspecialchars($editLesson->content_body ?? '') : ''; ?></textarea>
                    </div>
                    <div id="field-video" class="lrn-content-fields">
                        <div class="alert alert-info border-0 lrn-video-guide mb-3">
                            <h6 class="alert-heading mb-2"><i class="fab fa-youtube text-danger me-1"></i> Cómo agregar un video</h6>
                            <ol class="small mb-0 ps-3">
                                <li>Elegí tipo <strong>Video</strong> arriba.</li>
                                <li>Pegá el enlace de YouTube o Vimeo — se reproduce <strong>dentro del curso</strong>.</li>
                                <li>O subí un archivo MP4/WebM si no usás enlace.</li>
                            </ol>
                        </div>
                        <label class="form-label fw-semibold" for="lessonYoutubeUrl">Enlace YouTube / Vimeo</label>
                        <input type="url" id="lessonYoutubeUrl" name="content_url" class="form-control form-control-lg mb-1"
                            placeholder="https://www.youtube.com/watch?v=…"
                            value="<?php echo $editLesson && ($editLesson->content_type ?? '') === 'video' ? htmlspecialchars($editLesson->content_url ?? '') : ''; ?>">
                        <p class="small text-muted mb-3">También Shorts, youtu.be y Vimeo. El alumno ve el video embebido sin salir del sitio.</p>
                        <div id="youtubePreviewWrap" class="lrn-video-wrap mb-3" hidden>
                            <iframe id="youtubePreviewFrame" title="Vista previa" allowfullscreen></iframe>
                        </div>
                        <hr class="my-3">
                        <label class="form-label">O subir video (MP4, WebM — máx. 150 MB)</label>
                        <input type="file" name="content_file" class="form-control" accept="video/mp4,video/webm,video/ogg" data-lesson-file>
                    </div>
                    <div id="field-file" class="lrn-content-fields">
                        <label class="form-label">Subir archivo</label>
                        <p class="small text-muted">PDF, Excel (.xlsx), Word, PowerPoint o ZIP — máx. 40 MB</p>
                        <input type="file" name="content_file" class="form-control" accept=".pdf,.xls,.xlsx,.doc,.docx,.pptx,.zip,.csv" disabled data-lesson-file>
                    </div>
                </div>
            </div>

            <?php if ($enrich): ?>
            <div class="card shadow mb-3">
                <div class="card-header"><i class="fas fa-chalkboard-teacher me-1"></i> Anotaciones para el alumno</div>
                <div class="card-body">
                    <textarea name="instructor_notes" class="form-control" rows="4" placeholder="Tips, advertencias, ejemplos del día a día, enlaces útiles… El alumno las verá destacadas en la lección."><?php echo $editLesson ? htmlspecialchars($editLesson->instructor_notes ?? '') : ''; ?></textarea>
                </div>
            </div>
            <?php endif; ?>

            <div class="d-flex gap-2 flex-wrap">
                <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Guardar lección</button>
                <?php if ($editLesson): ?>
                <a href="<?php echo URLROOT; ?>/trainingAdmin/previewLesson/<?php echo $cid; ?>/<?php echo (int)$editLesson->id; ?>" class="btn btn-outline-primary" target="_blank"><i class="fas fa-eye me-1"></i> Vista previa</a>
                <?php endif; ?>
                <a href="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo $cid; ?>?tab=lessons" class="btn btn-outline-secondary">Volver al listado</a>
            </div>
        </form>

        <?php if ($enrich && $editLesson): ?>
        <div class="card shadow mt-4">
            <div class="card-header">Materiales de esta lección</div>
            <div class="card-body">
                <?php if ($lessonResources): foreach ($lessonResources as $r): ?>
                <div class="d-flex align-items-center gap-2 border-bottom py-2 small">
                    <i class="fas <?php echo learning_resource_icon($r->resource_type); ?>"></i>
                    <span class="flex-grow-1"><?php echo htmlspecialchars($r->title); ?></span>
                    <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/deleteResource/<?php echo $cid; ?>/<?php echo (int)$r->id; ?>" class="d-inline" onsubmit="return confirm('Eliminar?')">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-link btn-sm text-danger p-0">Eliminar</button>
                    </form>
                </div>
                <?php endforeach; else: ?>
                <p class="text-muted small">Sin materiales adjuntos a esta lección.</p>
                <?php endif; ?>
                <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveResource/<?php echo $cid; ?>" enctype="multipart/form-data" class="mt-3 border-top pt-3">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="lesson_id" value="<?php echo (int)$editLesson->id; ?>">
                    <div class="row g-2">
                        <div class="col-md-6"><input type="text" name="title" class="form-control form-control-sm" placeholder="Nombre del material" required></div>
                        <div class="col-md-6"><input type="file" name="resource_file" class="form-control form-control-sm"></div>
                        <div class="col-md-6"><input type="url" name="external_url" class="form-control form-control-sm" placeholder="O pegar enlace"></div>
                        <div class="col-12"><button type="submit" class="btn btn-sm btn-outline-primary">Agregar material</button></div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <div class="card shadow sticky-top" style="top:1rem">
            <div class="card-header">Vista previa en vivo</div>
            <div class="card-body lrn-preview-pane" id="lessonPreviewPane">
                <p class="text-muted small">Guardá la lección y usá <strong>Vista previa</strong> para ver el reproductor completo.</p>
                <?php if ($editLesson): ?>
                <hr>
                <p class="small fw-bold mb-1"><?php echo htmlspecialchars($editLesson->title); ?></p>
                <?php if (!empty($editLesson->objectives)): ?><p class="small"><em>Objetivos:</em> <?php echo nl2br(htmlspecialchars($editLesson->objectives)); ?></p><?php endif; ?>
                <?php if (!empty($editLesson->instructor_notes)): ?>
                <div class="alert alert-info small py-2"><strong>Nota del instructor:</strong><br><?php echo nl2br(htmlspecialchars($editLesson->instructor_notes)); ?></div>
                <?php endif; ?>
                <?php
                $lesson = $editLesson;
                require APPROOT . '/views/employee/partials/lesson_content.php';
                ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
