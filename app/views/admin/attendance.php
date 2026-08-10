<?php require APPROOT . '/views/inc/header.php'; ?>

<?php
$workDate = $data['work_date'];
$statusFilter = $data['status_filter'];
$rows = $data['rows'];
$stats = $data['stats'];
$statsOk = $stats ? (int)$stats->count_ok : 0;
$statsAlert = $stats ? (int)$stats->count_alerts : 0;
$statsLeave = $stats ? (int)$stats->count_leave : 0;
?>

<div class="admin-page-head">
    <div class="admin-page-brand">
        <div class="admin-page-icon"><i class="fas fa-user-clock"></i></div>
        <div class="admin-page-meta">
            <h2 class="page-title">Asistencia</h2>
            <p class="page-subtitle mb-0">Planificado vs fichado · <?php echo date('d/m/Y', strtotime($workDate)); ?></p>
        </div>
    </div>
    <div class="admin-page-actions d-flex gap-2">
        <?php if (isAdmin()): ?>
        <a href="<?php echo URLROOT; ?>/admin/exportAttendanceMonthCsv?month=<?php echo urlencode(date('Y-m', strtotime($workDate))); ?>"
           class="btn btn-success btn-sm">
            <i class="fas fa-file-csv me-1"></i> Exportar mes CSV
        </a>
        <?php endif; ?>
        <a href="<?php echo URLROOT; ?>/admin/dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Dashboard
        </a>
    </div>
</div>

<div class="att-dashboard mb-4">
    <div class="row g-2 align-items-end mb-3">
        <form method="get" action="<?php echo URLROOT; ?>/admin/attendance" class="col-sm-8 col-md-6 d-flex flex-wrap gap-2 align-items-end">
            <div class="flex-grow-1" style="min-width:9rem">
                <label class="form-label small fw-semibold mb-1">Fecha</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($workDate); ?>">
            </div>
            <div class="flex-grow-1" style="min-width:9rem">
                <label class="form-label small fw-semibold mb-1">Mostrar</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="alerts" <?php echo $statusFilter === 'alerts' ? 'selected' : ''; ?>>Solo alertas</option>
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Todos</option>
                    <option value="ok" <?php echo $statusFilter === 'ok' ? 'selected' : ''; ?>>En horario</option>
                    <option value="no_show" <?php echo $statusFilter === 'no_show' ? 'selected' : ''; ?>>Ausentes</option>
                    <option value="late" <?php echo $statusFilter === 'late' ? 'selected' : ''; ?>>Tarde</option>
                    <option value="on_leave" <?php echo $statusFilter === 'on_leave' ? 'selected' : ''; ?>>Licencia</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter me-1"></i>Filtrar</button>
        </form>
        <div class="col-sm-4 col-md-3">
            <form method="post" action="<?php echo URLROOT; ?>/admin/recomputeAttendance" class="d-inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($workDate); ?>">
                <input type="hidden" name="redirect_to" value="admin/attendance">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                <button type="submit" class="btn btn-outline-primary btn-sm" title="Recalcular asistencia"><i class="fas fa-sync-alt me-1"></i>Recalcular</button>
            </form>
        </div>
    </div>
    <div class="row g-2 mb-0">
        <div class="col-4"><div class="att-stat-card att-stat-ok py-2"><span class="att-stat-num" style="font-size:1.4rem"><?php echo $statsOk; ?></span><span class="att-stat-lbl">OK</span></div></div>
        <div class="col-4"><div class="att-stat-card att-stat-alert py-2"><span class="att-stat-num" style="font-size:1.4rem"><?php echo $statsAlert; ?></span><span class="att-stat-lbl">Alertas</span></div></div>
        <div class="col-4"><div class="att-stat-card att-stat-leave py-2"><span class="att-stat-num" style="font-size:1.4rem"><?php echo $statsLeave; ?></span><span class="att-stat-lbl">Licencia</span></div></div>
    </div>
</div>

<div class="db-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 admin-table">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Planificado</th>
                    <th>Fichado</th>
                    <th class="text-end">Δ</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Sin registros para esta fecha y filtro.</td></tr>
                <?php else: foreach ($rows as $row): ?>
                <?php $meta = attendanceStatusMeta($row->status); ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?php echo htmlspecialchars($row->full_name); ?></div>
                        <?php if ((int)$row->planned_blocks > 1): ?>
                        <span class="badge bg-light text-muted border"><?php echo (int)$row->planned_blocks; ?> bloques</span>
                        <?php endif; ?>
                    </td>
                    <td class="small">
                        <?php if ($row->planned_start): ?>
                        <?php echo attendanceFormatTime($row->planned_start); ?>–<?php echo attendanceFormatTime($row->planned_end); ?>
                        <span class="text-muted">(<?php echo $row->planned_minutes ? round($row->planned_minutes / 60, 1) . ' h' : '—'; ?>)</span>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="small">
                        <?php echo attendanceFormatTime($row->actual_entry); ?>
                        <?php if ($row->actual_exit): ?>–<?php echo attendanceFormatTime($row->actual_exit); ?><?php endif; ?>
                        <?php if ($row->actual_minutes !== null): ?>
                        <span class="text-muted">(<?php echo round($row->actual_minutes / 60, 1); ?> h)</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end small fw-semibold <?php echo ($row->delta_minutes ?? 0) >= 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo attendanceFormatMinutes($row->delta_minutes); ?>
                    </td>
                    <td><?php echo attendanceStatusBadge($row->status); ?></td>
                    <td class="text-end text-nowrap">
                        <a href="<?php echo URLROOT; ?>/admin/marcacionesTodas?person_q=<?php echo urlencode($row->full_name); ?>&start_date=<?php echo urlencode($workDate); ?>&end_date=<?php echo urlencode($workDate); ?>"
                           class="btn btn-sm btn-outline-secondary">Marcas</a>
                        <?php if ($row->status !== 'ok' && $row->status !== 'on_leave'): ?>
                        <a href="<?php echo attendanceJustifyCalendarUrl($row->user_id, $workDate, $row->status); ?>"
                           class="btn btn-sm btn-outline-primary" title="Registrar permiso o motivo">Permiso</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
