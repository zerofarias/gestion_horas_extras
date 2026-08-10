<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/notifications.css">

<div class="page-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1"><i class="fas fa-poll me-2 text-primary"></i>Encuestas</h1>
        <p class="text-muted mb-0">Formularios para empleados — anónimos o identificados</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo URLROOT; ?>/notificationsAdmin/index" class="btn btn-outline-secondary btn-sm">Hub notificaciones</a>
        <a href="<?php echo URLROOT; ?>/surveyAdmin/edit/0" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i> Nueva encuesta</a>
    </div>
</div>

<div class="card border shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Título</th>
                    <th>Estado</th>
                    <th>Anónima</th>
                    <th>Respuestas</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['surveys'])): ?>
            <tr><td colspan="5" class="text-muted p-4">Sin encuestas. Creá la primera.</td></tr>
            <?php else: foreach ($data['surveys'] as $s): ?>
            <tr>
                <td><?php echo htmlspecialchars($s->title); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars(survey_status_label($s->status)); ?></span></td>
                <td><?php echo (int)$s->is_anonymous ? 'Sí' : 'No'; ?></td>
                <td><?php echo (new Survey())->countResponses((int)$s->id); ?></td>
                <td class="text-end text-nowrap">
                    <a href="<?php echo URLROOT; ?>/surveyAdmin/edit/<?php echo (int)$s->id; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                    <a href="<?php echo URLROOT; ?>/surveyAdmin/results/<?php echo (int)$s->id; ?>" class="btn btn-sm btn-outline-secondary">Resultados</a>
                    <?php if ($s->status === 'draft'): ?>
                    <form method="post" action="<?php echo URLROOT; ?>/surveyAdmin/publish/<?php echo (int)$s->id; ?>" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-sm btn-success">Publicar</button>
                    </form>
                    <?php elseif ($s->status === 'published'): ?>
                    <form method="post" action="<?php echo URLROOT; ?>/surveyAdmin/close/<?php echo (int)$s->id; ?>" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-sm btn-warning">Cerrar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
