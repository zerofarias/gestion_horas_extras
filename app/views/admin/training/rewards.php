<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="d-flex align-items-center gap-2 mb-4">
    <h2 class="page-title mb-0">Premios canjeables</h2>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/courses" class="btn btn-outline-secondary btn-sm ms-auto">Cursos</a>
</div>

<?php if (!empty($data['pending'])): ?>
<div class="card shadow mb-4 border-warning">
    <div class="card-header">Canjes pendientes</div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead><tr><th>Empleado</th><th>Premio</th><th>Estrellas</th><th>Fecha</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($data['pending'] as $p): ?>
            <tr>
                <td><?php echo htmlspecialchars($p->full_name); ?></td>
                <td><?php echo htmlspecialchars($p->reward_title); ?></td>
                <td><?php echo (int)$p->stars_spent; ?></td>
                <td><?php echo htmlspecialchars($p->redeemed_at ?? ''); ?></td>
                <td class="text-nowrap">
                    <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/reviewRedemption/<?php echo (int)$p->id; ?>/approved" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-sm btn-success">Aprobar</button>
                    </form>
                    <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/reviewRedemption/<?php echo (int)$p->id; ?>/rejected" class="d-inline">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-sm btn-outline-danger">Rechazar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header">Nuevo premio</div>
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveReward">
                    <?php echo csrf_field(); ?>
                    <div class="mb-2">
                        <label class="form-label">Título</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Estrellas requeridas</label>
                        <input type="number" name="stars_required" class="form-control" value="100" min="1">
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ra" checked>
                        <label for="ra" class="form-check-label">Activo</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow">
            <div>
                <table class="table mb-0">
                    <thead><tr><th>Título</th><th>Estrellas</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['rewards'] as $rw): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rw->title); ?></td>
                        <td><?php echo (int)$rw->stars_required; ?></td>
                        <td><?php echo $rw->is_active ? 'Activo' : 'Inactivo'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
