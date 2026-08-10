<?php require APPROOT . '/views/inc/header.php'; ?>

<h1 class="page-title mb-4">Cierre de mes — Extras Casa Paviotti</h1>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Pendientes a cerrar</h5>
                <p class="mb-1"><?php echo (int)$data['count']; ?> registros</p>
                <p class="h4 mb-3"><?php echo cp_format_money($data['total']); ?></p>
                <p class="small text-muted">Recargo <?php echo htmlspecialchars(function_exists('cp_closure_markup_pct_label') ? cp_closure_markup_pct_label() : '19,5%'); ?> sobre el subtotal cargado. Sin modificar importes.</p>
                <?php if ($data['count'] > 0): ?>
                <form method="post" action="<?php echo URLROOT; ?>/cpTaskAdmin/closeMonth" class="mb-3" onsubmit="return confirm('¿Cerrar todos los pendientes sin aumento?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="close_action" value="simple">
                    <button type="submit" class="btn btn-warning w-100">Cierre simple</button>
                </form>
                <p class="small text-muted mb-2">O cierre con aumento % (tarifas + pendientes, excepto parcelas/mantenimiento):</p>
                <a href="<?php echo URLROOT; ?>/cpTaskAdmin/rateIncrease" class="btn btn-outline-warning w-100">Ir a aumento % y cierre</a>
                <?php else: ?>
                <p class="text-muted">No hay nada para cerrar.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<h5>Últimos cierres</h5>
<div class="table-responsive card shadow-sm">
    <table class="table mb-0">
        <thead><tr><th>Lote</th><th>Fecha</th><th>Neto</th><th>Recargo</th><th>Total final</th><th>Por</th></tr></thead>
        <tbody>
        <?php foreach ($data['closures'] as $c): ?>
        <tr>
            <td>#<?php echo (int)$c->lot_number; ?></td>
            <td><?php echo htmlspecialchars($c->closed_at); ?></td>
            <td><?php echo cp_format_money($c->total_amount); ?></td>
            <td><?php echo cp_format_money($c->iva_amount); ?></td>
            <td><?php echo cp_format_money($c->final_amount); ?></td>
            <td><?php echo htmlspecialchars($c->closed_by_name ?? '—'); ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<p class="mt-3">
    <a href="<?php echo URLROOT; ?>/cpTaskAdmin/pending">← Pendientes</a>
    · <a href="<?php echo URLROOT; ?>/cpTaskAdmin/history">Historial completo</a>
</p>

<?php require APPROOT . '/views/inc/footer.php'; ?>
