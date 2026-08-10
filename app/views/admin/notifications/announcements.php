<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/notifications.css">

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?php echo URLROOT; ?>/notificationsAdmin" class="text-muted small"><i class="fas fa-arrow-left me-1"></i>Notificaciones</a>
        <h1 class="h4 mb-0 mt-1">Avisos modales</h1>
    </div>
    <a href="<?php echo URLROOT; ?>/notificationsAdmin/announcementForm" class="btn btn-primary btn-sm">Nuevo aviso</a>
</div>

<div class="card shadow">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Título</th><th>Vigencia</th><th>Modo</th><th>Activo</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($data['items'] as $a): ?>
            <tr>
                <td><?php echo htmlspecialchars($a->title); ?></td>
                <td class="small"><?php echo date('d/m/Y', strtotime($a->starts_at)); ?> – <?php echo date('d/m/Y', strtotime($a->ends_at)); ?></td>
                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($a->display_mode); ?></span></td>
                <td><?php echo (int)$a->is_active ? '<span class="text-success">Sí</span>' : 'No'; ?></td>
                <td class="text-end">
                    <a href="<?php echo URLROOT; ?>/notificationsAdmin/announcementForm/<?php echo (int)$a->id; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                    <form method="post" action="<?php echo URLROOT; ?>/notificationsAdmin/deleteAnnouncement/<?php echo (int)$a->id; ?>" class="d-inline" onsubmit="return confirm('¿Eliminar aviso?');">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($data['items'])): ?>
            <tr><td colspan="5" class="text-muted text-center py-4">No hay avisos creados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
