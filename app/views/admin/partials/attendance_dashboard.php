<?php
$attStats = $data['attendance_stats'] ?? null;
$attAlerts = $data['attendance_alerts'] ?? [];
$attDate = $data['attendance_date'] ?? date('Y-m-d');
$attOk = $attStats ? (int)$attStats->count_ok : 0;
$attAlertCount = $attStats ? (int)$attStats->count_alerts : 0;
$attLeave = $attStats ? (int)$attStats->count_leave : 0;
?>
<?php if ($attStats !== null): ?>
<div class="att-dashboard mb-4">
    <div class="att-dashboard-head">
        <div class="att-dashboard-brand">
            <div class="att-dashboard-icon"><i class="fas fa-user-clock"></i></div>
            <div>
                <h3 class="att-dashboard-title">Asistencia hoy</h3>
                <p class="att-dashboard-sub mb-0">Planificado vs fichado · tolerancia <?php echo function_exists('setting_int') ? setting_int('attendance_late_tolerance_min', 5) : (int)(defined('ATTENDANCE_LATE_TOLERANCE_MIN') ? ATTENDANCE_LATE_TOLERANCE_MIN : 5); ?> min</p>
            </div>
        </div>
        <div class="att-dashboard-actions d-flex flex-wrap gap-2">
            <a href="<?php echo URLROOT; ?>/admin/attendance?date=<?php echo urlencode($attDate); ?>&status=alerts" class="btn btn-sm btn-outline-primary">Ver detalle</a>
            <a href="<?php echo URLROOT; ?>/admin/calendar?month=<?php echo date('Y-m', strtotime($attDate)); ?>" class="btn btn-sm btn-outline-secondary">Calendario</a>
            <form method="post" action="<?php echo URLROOT; ?>/admin/recomputeAttendance" class="d-inline">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($attDate); ?>">
                <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($attDate); ?>">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-sync-alt me-1"></i>Actualizar</button>
            </form>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-4"><div class="att-stat-card att-stat-ok"><span class="att-stat-num"><?php echo $attOk; ?></span><span class="att-stat-lbl">En horario</span></div></div>
        <div class="col-md-4"><div class="att-stat-card att-stat-alert"><span class="att-stat-num"><?php echo $attAlertCount; ?></span><span class="att-stat-lbl">Con alerta</span></div></div>
        <div class="col-md-4"><div class="att-stat-card att-stat-leave"><span class="att-stat-num"><?php echo $attLeave; ?></span><span class="att-stat-lbl">En licencia</span></div></div>
    </div>
    <?php if (!empty($attAlerts)): ?>
    <div class="att-alert-list mt-3">
        <?php foreach ($attAlerts as $row): $meta = attendanceStatusMeta($row->status); ?>
        <a href="<?php echo attendanceJustifyCalendarUrl($row->user_id, $attDate, $row->status); ?>" class="att-alert-item">
            <span class="att-alert-avatar"><?php echo strtoupper(substr($row->full_name, 0, 1)); ?></span>
            <span class="att-alert-body">
                <strong><?php echo htmlspecialchars($row->full_name); ?></strong>
                <span class="text-muted small d-block">Plan <?php echo attendanceFormatTime($row->planned_start); ?>–<?php echo attendanceFormatTime($row->planned_end); ?> · Fichó <?php echo attendanceFormatTime($row->actual_entry); ?><?php if ($row->actual_exit): ?>–<?php echo attendanceFormatTime($row->actual_exit); ?><?php endif; ?></span>
            </span>
            <span class="badge bg-<?php echo $meta['badge']; ?>"><i class="fas <?php echo $meta['icon']; ?> me-1"></i><?php echo htmlspecialchars($meta['label']); ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php elseif ($attAlertCount === 0): ?>
    <div class="att-empty-ok mt-3"><i class="fas fa-check-circle me-2"></i>Sin alertas de asistencia para hoy.</div>
    <?php else: ?>
    <div class="att-empty-ok mt-3 text-muted small">Presioná <strong>Actualizar</strong> tras sincronizar o planificar turnos.</div>
    <?php endif; ?>
</div>
<?php endif; ?>
