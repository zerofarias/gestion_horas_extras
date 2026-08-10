<?php require APPROOT . '/views/inc/header.php'; ?>

<h1 class="h4 mb-4"><i class="fas fa-file-invoice-dollar me-2"></i>Mis recibos de sueldo</h1>

<div class="list-group shadow-sm">
    <?php foreach ($data['stubs'] as $ps):
        $canSign = (int)$ps->id === (int)$data['oldest_pending_id'];
        $locked = $ps->status === 'pending_signature' && !$canSign;
    ?>
    <div class="list-group-item d-flex justify-content-between align-items-center">
        <div>
            <strong><?php echo pay_stub_period_label($ps->period); ?></strong>
            <?php if ($ps->status === 'signed'): ?>
            <span class="badge bg-success ms-2">Firmado</span>
            <?php elseif ($locked): ?>
            <span class="badge bg-secondary ms-2"><i class="fas fa-lock me-1"></i>Firmá el anterior primero</span>
            <?php else: ?>
            <span class="badge bg-warning text-dark ms-2">Pendiente de firma</span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($ps->status === 'signed'): ?>
            <a href="<?php echo URLROOT; ?>/employee/payStubSign/<?php echo (int)$ps->id; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
            <?php elseif ($canSign): ?>
            <a href="<?php echo URLROOT; ?>/employee/payStubSign/<?php echo (int)$ps->id; ?>" class="btn btn-sm btn-primary">Firmar</a>
            <?php else: ?>
            <button type="button" class="btn btn-sm btn-outline-secondary" disabled>Firmar</button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($data['stubs'])): ?>
    <div class="list-group-item text-muted text-center py-4">No hay recibos cargados.</div>
    <?php endif; ?>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
