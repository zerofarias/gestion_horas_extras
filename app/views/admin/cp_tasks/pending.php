<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="page-title mb-1">Extras Casa Paviotti — Pendientes</h1>
        <p class="page-subtitle mb-0">Total: <strong><?php echo cp_format_money($data['total']); ?></strong></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <?php if (!$data['is_supervisor']): ?>
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/exportPending" class="btn btn-outline-success btn-sm">CSV pendientes</a>
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/closeMonth" class="btn btn-warning btn-sm">Cierre de mes</a>
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/rateIncrease" class="btn btn-outline-warning btn-sm">Aumento %</a>
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/reports" class="btn btn-outline-info btn-sm">Reportes</a>
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/history" class="btn btn-outline-secondary btn-sm">Historial</a>
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/rates" class="btn btn-outline-primary btn-sm">Tarifas</a>
        <a href="<?php echo URLROOT; ?>/cpTaskAdmin/catalogs" class="btn btn-outline-secondary btn-sm">Catálogos</a>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Tareas Casa Paviotti</strong>
        <?php if (!$data['is_supervisor']): ?><span class="small text-muted">Clic en importe para corregir antes del cierre</span><?php endif; ?>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 table-sm">
            <thead><tr><th>Fecha</th><th>Empleado</th><th>Tarea</th><th>Detalle</th><th class="text-end">Importe</th></tr></thead>
            <tbody>
            <?php if (empty($data['entries'])): ?>
                <tr><td colspan="5" class="text-muted text-center py-3">Sin registros.</td></tr>
            <?php else: foreach ($data['entries'] as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e->activity_date); ?></td>
                    <td><?php echo htmlspecialchars($e->employee_name); ?></td>
                    <td><?php echo htmlspecialchars($e->task_name); ?></td>
                    <td><?php echo htmlspecialchars($e->deceased_name ?: $e->deceased_code ?: '—'); ?></td>
                    <td class="text-end">
                        <?php if (!$data['is_supervisor']): ?>
                        <form method="post" action="<?php echo URLROOT; ?>/cpTaskAdmin/editAmount" class="d-inline-flex gap-1 align-items-center justify-content-end">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="kind" value="entry">
                            <input type="hidden" name="id" value="<?php echo (int)$e->id; ?>">
                            <input type="number" name="amount" step="0.01" class="form-control form-control-sm" style="width:100px" value="<?php echo htmlspecialchars($e->amount); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">OK</button>
                        </form>
                        <?php else: ?>
                        <?php echo cp_format_money($e->amount); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($data['external_entries'])): ?>
<div class="card shadow-sm">
    <div class="card-header"><strong>Tareas otras empresas</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 table-sm">
            <thead><tr><th>Fecha</th><th>Empleado</th><th>Empresa</th><th>Tarea</th><th class="text-end">Importe</th></tr></thead>
            <tbody>
            <?php foreach ($data['external_entries'] as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e->activity_date); ?></td>
                    <td><?php echo htmlspecialchars($e->employee_name); ?></td>
                    <td><?php echo htmlspecialchars($e->external_company_name); ?></td>
                    <td><?php echo htmlspecialchars($e->task_label); ?></td>
                    <td class="text-end">
                        <?php if (!$data['is_supervisor']): ?>
                        <form method="post" action="<?php echo URLROOT; ?>/cpTaskAdmin/editAmount" class="d-inline-flex gap-1 align-items-center justify-content-end">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="kind" value="external">
                            <input type="hidden" name="id" value="<?php echo (int)$e->id; ?>">
                            <input type="number" name="amount" step="0.01" class="form-control form-control-sm" style="width:100px" value="<?php echo htmlspecialchars($e->amount); ?>">
                            <button type="submit" class="btn btn-sm btn-outline-primary">OK</button>
                        </form>
                        <?php else: ?>
                        <?php echo cp_format_money($e->amount); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require APPROOT . '/views/inc/footer.php'; ?>
