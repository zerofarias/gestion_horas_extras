<?php require APPROOT . '/views/inc/header.php'; $c = $data['closure']; ?>

<h1 class="page-title mb-1">Lote #<?php echo (int)$c->lot_number; ?></h1>
<p class="page-subtitle mb-4">
    Cerrado el <?php echo htmlspecialchars($c->closed_at); ?>
    por <?php echo htmlspecialchars($c->closed_by_name ?? '—'); ?>
    · <a href="<?php echo URLROOT; ?>/cpTaskAdmin/exportClosure/<?php echo (int)$c->id; ?>">Descargar CSV</a>
</p>

<?php $markupLabel = $data['markup_label'] ?? cp_closure_markup_pct_label(); ?>
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Importe cargado (neto)</div><div class="h5 mb-0"><?php echo cp_format_money($c->total_amount); ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Recargo <?php echo htmlspecialchars($markupLabel); ?></div><div class="h5 mb-0"><?php echo cp_format_money($c->iva_amount); ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body"><div class="small text-muted">Total final</div><div class="h5 mb-0"><?php echo cp_format_money($c->final_amount); ?></div></div></div></div>
</div>

<?php if (!empty($data['employee_totals'])): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Totales por empleado (para liquidación)</strong></div>
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
            <?php foreach ($data['employee_totals'] as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row->full_name); ?></td>
                    <td class="text-end"><?php echo cp_format_money($row->net); ?></td>
                    <td class="text-end"><?php echo cp_format_money($row->markup); ?></td>
                    <td class="text-end fw-semibold"><?php echo cp_format_money($row->final); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-4">
    <div class="card-header"><strong>Tareas</strong> (<?php echo count($data['entries']); ?>)</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Empleado</th><th>Fecha</th><th>Tarea</th><th>Detalle</th><th class="text-end">Importe</th></tr></thead>
            <tbody>
            <?php foreach ($data['entries'] as $e): ?>
            <tr>
                <td><?php echo htmlspecialchars($e->employee_name); ?></td>
                <td><?php echo htmlspecialchars($e->activity_date); ?></td>
                <td><?php echo htmlspecialchars($e->task_name); ?></td>
                <td><?php echo htmlspecialchars($e->deceased_name ?: $e->deceased_code ?: '—'); ?></td>
                <td class="text-end"><?php echo cp_format_money($e->amount); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($data['external'])): ?>
<div class="card shadow-sm">
    <div class="card-header"><strong>Tareas externas</strong></div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Empleado</th><th>Fecha</th><th>Empresa</th><th>Tarea</th><th class="text-end">Importe</th></tr></thead>
            <tbody>
            <?php foreach ($data['external'] as $x): ?>
            <tr>
                <td><?php echo htmlspecialchars($x->employee_name); ?></td>
                <td><?php echo htmlspecialchars($x->activity_date); ?></td>
                <td><?php echo htmlspecialchars($x->external_company_name); ?></td>
                <td><?php echo htmlspecialchars($x->task_label); ?></td>
                <td class="text-end"><?php echo cp_format_money($x->amount); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<p class="mt-3"><a href="<?php echo URLROOT; ?>/cpTaskAdmin/history">← Historial</a></p>

<?php require APPROOT . '/views/inc/footer.php'; ?>
