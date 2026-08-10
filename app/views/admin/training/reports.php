<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="d-flex align-items-center gap-2 mb-4">
    <h2 class="page-title mb-0">Reportes de capacitación</h2>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/courses" class="btn btn-outline-secondary btn-sm ms-auto">Cursos</a>
</div>
<div class="card shadow">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Área</th>
                        <th>Curso</th>
                        <th>Estado</th>
                        <th>Lección</th>
                        <th>%</th>
                        <th>Quiz</th>
                        <th>Estrellas</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($data['rows'])): ?>
                    <tr><td colspan="8" class="text-muted text-center py-4">Sin inscripciones aún.</td></tr>
                <?php else: foreach ($data['rows'] as $r): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r->full_name ?: $r->username); ?></td>
                        <td><?php echo htmlspecialchars($r->area_name ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($r->course_title); ?></td>
                        <td><span class="badge bg-secondary"><?php echo learning_status_label($r->status); ?></span></td>
                        <td><?php echo (int)$r->current_lesson_position; ?></td>
                        <td><?php echo (int)$r->progress_percent; ?>%</td>
                        <td><?php echo $r->quiz_score !== null ? (int)$r->quiz_score . '%' : '—'; ?></td>
                        <td><?php echo (int)$r->stars_awarded; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
