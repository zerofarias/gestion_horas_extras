<?php require APPROOT . '/views/inc/header.php'; ?>

<h1 class="page-title mb-2">Aumento porcentual — Tarifas y pendientes</h1>
<p class="page-subtitle mb-4">
    Como el sistema legacy: actualiza tarifas en <code>cp_employee_rates</code> y opcionalmente pendientes al cerrar.
    Parcelas y mantenimiento (importe manual) no se multiplican en pendientes.
</p>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/cpTaskAdmin/rateIncrease">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Porcentaje de aumento</label>
                        <div class="input-group">
                            <input type="number" name="percent" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($data['percent']); ?>" required>
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text">Ej: 10 = aumento del 10% (multiplicador 1,10)</div>
                    </div>
                    <button type="submit" name="rate_action" value="preview" class="btn btn-outline-secondary w-100 mb-2">Vista previa</button>
                    <button type="submit" name="rate_action" value="apply_rates" class="btn btn-primary w-100 mb-2" onclick="return confirm('¿Actualizar TODAS las tarifas de empleados CP?');">Solo actualizar tarifas</button>
                    <button type="submit" name="rate_action" value="apply_and_close" class="btn btn-warning w-100" onclick="return confirm('¿Aumentar tarifas, ajustar pendientes (excepto manuales) y CERRAR el lote?');">Aumentar + cierre de mes</button>
                </form>
            </div>
        </div>
    </div>
    <?php if (!empty($data['preview'])): $p = $data['preview']; ?>
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header">Vista previa (×<?php echo number_format($p['multiplier'], 4, ',', '.'); ?>)</div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td>Pendientes tareas (antes)</td><td class="text-end"><?php echo cp_format_money($p['entries_before']); ?></td></tr>
                    <tr><td>Pendientes tareas (después)</td><td class="text-end"><strong><?php echo cp_format_money($p['entries_after']); ?></strong></td></tr>
                    <tr><td>Pendientes externas (antes)</td><td class="text-end"><?php echo cp_format_money($p['external_before']); ?></td></tr>
                    <tr><td>Pendientes externas (después)</td><td class="text-end"><strong><?php echo cp_format_money($p['external_after']); ?></strong></td></tr>
                    <tr class="table-warning"><td><strong>Total pendiente (después)</strong></td><td class="text-end"><strong><?php echo cp_format_money($p['total_after']); ?></strong></td></tr>
                    <tr><td>IVA 19% estimado</td><td class="text-end"><?php echo cp_format_money($p['total_after'] * 0.19); ?></td></tr>
                    <tr><td>Total con IVA</td><td class="text-end"><?php echo cp_format_money($p['total_after'] * 1.19); ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<p class="mt-3"><a href="<?php echo URLROOT; ?>/cpTaskAdmin/pending">← Pendientes</a></p>

<?php require APPROOT . '/views/inc/footer.php'; ?>
