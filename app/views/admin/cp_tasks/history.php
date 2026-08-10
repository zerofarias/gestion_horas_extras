<?php require APPROOT . '/views/inc/header.php'; ?>

<h1 class="page-title mb-4">Historial de cierres — Casa Paviotti</h1>

<div class="mb-3">
    <a href="<?php echo URLROOT; ?>/cpTaskAdmin/closeMonth" class="btn btn-warning btn-sm">Nuevo cierre</a>
    <a href="<?php echo URLROOT; ?>/cpTaskAdmin/pending" class="btn btn-outline-secondary btn-sm">Pendientes</a>
</div>

<div class="table-responsive card shadow-sm">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Lote</th>
                <th>Fecha cierre</th>
                <th>Subtotal</th>
                <th>Recargo</th>
                <th>Total final</th>
                <th>Cerrado por</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($data['closures'])): ?>
            <tr><td colspan="7" class="text-muted text-center py-4">Aún no hay cierres registrados.</td></tr>
        <?php else: foreach ($data['closures'] as $c): ?>
            <tr>
                <td><strong>#<?php echo (int)$c->lot_number; ?></strong></td>
                <td><?php echo htmlspecialchars($c->closed_at); ?></td>
                <td><?php echo cp_format_money($c->total_amount); ?></td>
                <td><?php echo cp_format_money($c->iva_amount); ?></td>
                <td><?php echo cp_format_money($c->final_amount); ?></td>
                <td><?php echo htmlspecialchars($c->closed_by_name ?? '—'); ?></td>
                <td class="text-nowrap">
                    <a href="<?php echo URLROOT; ?>/cpTaskAdmin/closureDetail/<?php echo (int)$c->id; ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                    <a href="<?php echo URLROOT; ?>/cpTaskAdmin/exportClosure/<?php echo (int)$c->id; ?>" class="btn btn-sm btn-outline-success">CSV</a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
