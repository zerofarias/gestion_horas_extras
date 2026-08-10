<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="admin-page-head">
    <div class="admin-page-brand">
        <div class="admin-page-icon"><i class="fas fa-chart-pie"></i></div>
        <div class="admin-page-meta">
            <h2 class="page-title">Reportes de vacaciones</h2>
            <p class="page-subtitle mb-0">Saldos, convenios y alertas por empresa</p>
        </div>
    </div>
    <a href="<?php echo URLROOT; ?>/vacationAdmin/exportVacationBalancesCsv" class="btn btn-success btn-sm">
        <i class="fas fa-file-csv me-1"></i> Exportar saldos CSV
    </a>
</div>

<?php if (!empty($data['october_reminder'])): ?>
<div class="alert alert-warning">
    <i class="fas fa-calendar me-1"></i> Estamos en <strong>octubre</strong>: es el momento de liquidar el período Oct–Sep anterior (proceso manual).
    <a href="<?php echo URLROOT; ?>/vacationAdmin/liquidateCompanyBatch" class="alert-link">Ir a liquidación masiva</a>
</div>
<?php endif; ?>

<?php $alerts = $data['alerts']; ?>
<?php if (!empty($alerts['ready'])): ?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body">
                <h6 class="text-warning">Sin fecha de ingreso</h6>
                <p class="display-6 mb-0"><?php echo count($alerts['no_hire_date']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger">
            <div class="card-body">
                <h6 class="text-danger">Sin convenio efectivo</h6>
                <p class="display-6 mb-0"><?php echo count($alerts['no_agreement']); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-info">
            <div class="card-body">
                <h6 class="text-info">Saldo bajo (&lt; 3 días)</h6>
                <p class="display-6 mb-0"><?php echo count($alerts['low_balance']); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="card border shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Empleado</th>
                    <th>Ingreso</th>
                    <th>Convenio</th>
                    <th class="text-end">Días pendientes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data['balances'] as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row->user->full_name); ?></td>
                <td><?php echo $row->user->hire_date ? date('d/m/Y', strtotime($row->user->hire_date)) : '—'; ?></td>
                <td><?php echo $row->agreement ? htmlspecialchars($row->agreement->name) : '<span class="text-danger">Sin convenio</span>'; ?></td>
                <td class="text-end fw-semibold"><?php echo vacation_format_days($row->pending); ?></td>
                <td class="text-end">
                    <a href="<?php echo URLROOT; ?>/admin/employeeProfile/<?php echo (int)$row->user->id; ?>#tab-vacation" class="btn btn-sm btn-outline-primary">Ficha</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
