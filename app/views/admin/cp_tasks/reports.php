<?php require APPROOT . '/views/inc/header.php'; ?>

<h1 class="page-title mb-4">Reportes — Extras Casa Paviotti</h1>

<?php
$markupLabel = $data['markup_label'] ?? (function_exists('cp_closure_markup_pct_label') ? cp_closure_markup_pct_label() : '19,5%');
$sumNet = $sumMarkup = $sumFinal = 0;
?>
<form method="get" class="row g-2 align-items-end mb-4">
    <div class="col-auto">
        <label class="form-label">Mes</label>
        <input type="month" name="mes" class="form-control" value="<?php echo htmlspecialchars($data['month']); ?>">
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary">Filtrar</button></div>
    <div class="col-auto">
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/exportReport?mes=<?php echo urlencode($data['month']); ?>" class="btn btn-success">
            <i class="fas fa-file-csv me-1"></i>Exportar CSV
        </a>
    </div>
</form>

<p class="small text-muted mb-3">Al cierre se aplica un recargo de <strong><?php echo htmlspecialchars($markupLabel); ?></strong> sobre lo cargado por cada empleado (ej. $100.000 + recargo = total final).</p>

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Totales por empleado — <?php echo htmlspecialchars($data['month']); ?></strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th class="text-end">Importe cargado</th>
                    <th class="text-end">Recargo (<?php echo htmlspecialchars($markupLabel); ?>)</th>
                    <th class="text-end">Total final</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($data['rows'])): ?>
                <tr><td colspan="4" class="text-muted text-center py-3">Sin movimientos en el período.</td></tr>
            <?php else: foreach ($data['rows'] as $r):
                $sumNet += (float)$r->total_amount;
                $sumMarkup += (float)($r->markup ?? 0);
                $sumFinal += (float)($r->final ?? 0);
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($r->full_name); ?></td>
                    <td class="text-end"><?php echo cp_format_money($r->total_amount); ?></td>
                    <td class="text-end"><?php echo cp_format_money($r->markup ?? 0); ?></td>
                    <td class="text-end fw-semibold"><?php echo cp_format_money($r->final ?? 0); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if (!empty($data['rows'])): ?>
            <tfoot class="table-light">
                <tr>
                    <th>TOTAL</th>
                    <th class="text-end"><?php echo cp_format_money($sumNet); ?></th>
                    <th class="text-end"><?php echo cp_format_money($sumMarkup); ?></th>
                    <th class="text-end"><?php echo cp_format_money($sumFinal); ?></th>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><strong>Últimos cierres</strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Lote</th><th>Fecha</th><th>Neto</th><th>Recargo</th><th>Total final</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($data['closures'] as $c): ?>
            <tr>
                <td>#<?php echo (int)$c->lot_number; ?></td>
                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($c->closed_at))); ?></td>
                <td><?php echo cp_format_money($c->total_amount); ?></td>
                <td><?php echo cp_format_money($c->iva_amount); ?></td>
                <td><?php echo cp_format_money($c->final_amount); ?></td>
                <td class="text-nowrap">
                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/closureDetail/<?php echo (int)$c->id; ?>" class="btn btn-sm btn-outline-primary">Detalle</a>
                        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/exportClosure/<?php echo (int)$c->id; ?>" class="btn btn-sm btn-outline-success">CSV</a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="mt-3"><a href="<?php echo URLROOT; ?>/cpTaskAdmin/pending">← Pendientes</a></p>

<?php require APPROOT . '/views/inc/footer.php'; ?>
