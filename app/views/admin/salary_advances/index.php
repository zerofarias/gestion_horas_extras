<?php
require APPROOT . '/views/inc/header.php';
$entries = $entries ?? [];
$filters = $filters ?? [];
$openEntry = $open_entry ?? null;
$openId = (int)($open_id ?? 0);
$maxHr = (int)($max_installments_hr ?? 6);
$maxEmp = (int)($max_installments_employee ?? 2);
$finalizadoReady = !empty($finalizado_ready);
$jsEntries = $entries;
if (!empty($openEntry)) {
    $listedIds = array_map(function ($r) { return (int)$r->id; }, $entries);
    if (!in_array((int)$openEntry->id, $listedIds, true)) {
        array_unshift($jsEntries, $openEntry);
    }
}
$GLOBALS['salary_advance_admin_page'] = true;
?>

<?php if (!empty($installments_ready) && !$finalizadoReady): ?>
<div class="alert alert-warning small mb-3">
    <i class="fas fa-exclamation-triangle me-1"></i>
    Para pasar adelantos a <strong>Finalizado</strong> al cobrar todas las cuotas, importá
    <code>migration_salary_advance_finalizado.sql</code> en phpMyAdmin (MIGRATIONS.md #40).
</div>
<?php endif; ?>

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1">Adelantos de sueldo</h1>
        <p class="text-muted small mb-0">Revisión y autorización de solicitudes de adelanto.</p>
    </div>
    <a href="<?php echo URLROOT; ?>/systemConfig?tab=salary_advance" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-cog me-1"></i> Configuración
    </a>
</div>

<form method="get" action="<?php echo URLROOT; ?>/salaryAdvanceAdmin/index" class="row g-2 mb-4">
    <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
            <option value="">Todos los estados</option>
            <?php foreach (['Pendiente', 'Aprobado', 'Finalizado', 'Rechazado'] as $st): ?>
            <option value="<?php echo $st; ?>" <?php echo ($filters['status'] ?? '') === $st ? 'selected' : ''; ?>><?php echo $st; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-5">
        <input type="search" name="search" class="form-control form-control-sm" placeholder="Buscar por nombre de empleado"
               value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
    </div>
    <div class="col-md-2">
        <a href="<?php echo URLROOT; ?>/salaryAdvanceAdmin/index" class="btn btn-outline-secondary btn-sm w-100">Limpiar</a>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Empleado</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Cuotas</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($entries)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Sin solicitudes para los filtros seleccionados.</td></tr>
            <?php else: ?>
                <?php foreach ($entries as $row): ?>
                <tr class="sa-row-detail <?php echo $row->status === 'Pendiente' ? 'table-warning' : ''; ?>" style="cursor:pointer" role="button" tabindex="0"
                    data-id="<?php echo (int)$row->id; ?>">
                    <td>
                        <a href="<?php echo URLROOT; ?>/admin/employeeProfile/<?php echo (int)$row->user_id; ?>#tab-req" class="text-decoration-none">
                            <?php echo htmlspecialchars($row->employee_name); ?>
                        </a>
                    </td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row->created_at)); ?></td>
                    <td><?php echo salary_advance_format_money($row->amount); ?></td>
                    <td><?php echo (int)$row->installments_requested; ?><?php if (in_array($row->status, ['Aprobado', 'Finalizado'], true) && $row->installments_approved): ?> <span class="text-muted small">/ <?php echo (int)$row->installments_approved; ?></span><?php endif; ?></td>
                    <td><span class="badge <?php echo salary_advance_status_badge_class($row->status); ?>"><?php echo htmlspecialchars($row->status); ?></span></td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary sa-open-detail"
                                data-id="<?php echo (int)$row->id; ?>"
                                data-user-id="<?php echo (int)$row->user_id; ?>"
                                data-name="<?php echo htmlspecialchars($row->employee_name, ENT_QUOTES); ?>">
                            Ver / resolver
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="offcanvas offcanvas-end req-review-panel sa-advance-panel" tabindex="-1" id="saReviewCanvas" aria-labelledby="saReviewTitle">
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title h5 mb-0" id="saReviewTitle">Solicitud</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body" id="saReviewBody">
        <p class="text-muted">Cargando…</p>
    </div>
</div>

<script>
window.SA_URLROOT = <?php echo json_encode(URLROOT); ?>;
window.SA_OPEN_ID = <?php echo (int)$openId; ?>;
window.SA_MAX_HR = <?php echo (int)$maxHr; ?>;
window.SA_MAX_EMP = <?php echo (int)$maxEmp; ?>;
window.SA_CSRF = <?php echo json_encode(csrf_token()); ?>;
window.SA_INSTALLMENTS_READY = <?php echo !empty($installments_ready) ? 'true' : 'false'; ?>;
window.SA_ENTRIES = <?php echo json_encode(array_map(function ($r) {
    return [
        'id' => (int)$r->id,
        'user_id' => (int)$r->user_id,
        'employee_name' => $r->employee_name,
        'amount' => (float)$r->amount,
        'amount_fmt' => salary_advance_format_money($r->amount),
        'installments_requested' => (int)$r->installments_requested,
        'installments_approved' => $r->installments_approved !== null ? (int)$r->installments_approved : null,
        'status' => $r->status,
        'reason' => $r->reason ?? '',
        'admin_notes' => $r->admin_notes ?? '',
        'created_at_fmt' => date('d/m/Y H:i', strtotime($r->created_at)),
    ];
}, $jsEntries), JSON_UNESCAPED_UNICODE); ?>;
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
