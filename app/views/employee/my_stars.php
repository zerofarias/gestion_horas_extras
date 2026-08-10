<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="d-flex align-items-center gap-2 mb-4">
    <h2 class="page-title mb-0">Mis estrellas y premios</h2>
    <span class="lrn-hero-stars ms-auto"><i class="fas fa-star"></i> <?php echo (int)$data['balance']; ?></span>
</div>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header">Catálogo de premios</div>
            <div class="card-body">
                <?php if (empty($data['rewards'])): ?>
                <p class="text-muted mb-0">No hay premios disponibles.</p>
                <?php else: foreach ($data['rewards'] as $rw): ?>
                <div class="border rounded p-3 mb-2">
                    <strong><?php echo htmlspecialchars($rw->title); ?></strong>
                    <p class="small text-muted mb-2"><?php echo htmlspecialchars($rw->description ?? ''); ?></p>
                    <span class="badge bg-warning text-dark"><?php echo (int)$rw->stars_required; ?> estrellas</span>
                    <?php if ((int)$data['balance'] >= (int)$rw->stars_required): ?>
                    <form method="post" action="<?php echo URLROOT; ?>/training/redeem/<?php echo (int)$rw->id; ?>" class="d-inline float-end" onsubmit="return confirm('¿Canjear este premio?')">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-sm btn-primary">Canjear</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow">
            <div>Historial</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Fecha</th><th>Movimiento</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($data['transactions'] as $tx): ?>
                    <tr>
                        <td class="small"><?php echo htmlspecialchars($tx->created_at ?? ''); ?></td>
                        <td class="small"><?php echo htmlspecialchars($tx->note ?? $tx->source_type); ?></td>
                        <td class="<?php echo (int)$tx->delta > 0 ? 'text-success' : 'text-danger'; ?>"><?php echo ((int)$tx->delta > 0 ? '+' : '') . (int)$tx->delta; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<a href="<?php echo URLROOT; ?>/training/index" class="btn btn-outline-secondary mt-3">&larr; Mis cursos</a>
<?php require APPROOT . '/views/inc/footer.php'; ?>
