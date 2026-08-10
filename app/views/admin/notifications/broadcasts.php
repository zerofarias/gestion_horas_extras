<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/notifications.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo URLROOT; ?>/notificationsAdmin" class="text-muted small"><i class="fas fa-arrow-left me-1"></i>Notificaciones</a>
        <h1 class="h4 mb-0 mt-1">Notificaciones (campana)</h1>
    </div>
    <a href="<?php echo URLROOT; ?>/notificationsAdmin/broadcastForm" class="btn btn-primary btn-sm">Nueva notificación</a>
</div>

<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Título</th><th>Destinatarios</th><th>Tipo</th><th>Creado</th><th>Por</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($data['items'] as $b): ?>
            <tr>
                <td><?php echo htmlspecialchars($b->title); ?></td>
                <td class="small"><?php echo (int)($b->recipient_count ?? 0); ?></td>
                <td><span class="badge bg-info text-dark"><?php echo htmlspecialchars($b->type); ?></span></td>
                <td class="small"><?php echo date('d/m/Y H:i', strtotime($b->created_at)); ?></td>
                <td class="small"><?php echo htmlspecialchars($b->creator_name ?? '—'); ?></td>
                <td class="text-end">
                    <?php if ($b->type === 'manual'): ?>
                    <form method="post" action="<?php echo URLROOT; ?>/notificationsAdmin/deleteBroadcast/<?php echo (int)$b->id; ?>" class="d-inline" onsubmit="return confirm('¿Eliminar este envío? Se quitará de la campana de todos los empleados.');">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar envío"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted small">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data['items'])): ?>
            <tr><td colspan="6" class="text-muted text-center py-4">Sin notificaciones enviadas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
