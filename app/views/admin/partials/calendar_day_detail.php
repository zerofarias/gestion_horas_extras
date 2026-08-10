<?php
/** @var array $day */
/** @var object $selectedUser */
/** @var array $data */
$att = $day['attendance'] ?? null;
$just = $day['justification'] ?? null;
$ctx = $day['context'] ?? [];
$suggestType = $data['suggest_type'] ?? '';
$typeLabelsGrouped = $data['justification_types_grouped'] ?? AttendanceJustification::typeLabelsGrouped();
$weekdays = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
$dow = $weekdays[(int)date('w', strtotime($day['date']))] ?? '';
?>
<div class="d-flex justify-content-between align-items-start gap-2 mb-3">
    <p class="text-muted small mb-0"><?php echo $dow . ' ' . date('d/m/Y', strtotime($day['date'])); ?></p>
    <a href="<?php echo URLROOT; ?>/admin/employeeProfile/<?php echo (int)$data['user_id']; ?>" class="btn btn-sm btn-outline-secondary py-0">Ficha</a>
</div>
<?php if (!empty($ctx['summary'])): ?>
<p class="small fw-semibold mb-2"><?php echo htmlspecialchars($ctx['summary']); ?></p>
<?php endif; ?>

<?php if (!empty($day['holiday'])): ?>
<div class="alert alert-secondary py-2 small mb-2"><i class="fas fa-star me-1"></i> Feriado: <strong><?php echo htmlspecialchars($day['holiday']); ?></strong></div>
<?php endif; ?>

<h6 class="fw-bold small text-uppercase text-muted">Planificado</h6>
<?php if (empty($day['planned'])): ?>
<p class="small">Sin turno planificado.</p>
<?php else: foreach ($day['planned'] as $block):
    $type = $block->type ?? 'shift';
?>
<div class="cal-panel-block">
    <?php if ($type === 'vacation'): ?>
    <i class="fas fa-umbrella-beach me-1 text-info"></i><strong>Vacaciones</strong>
    <?php elseif ($type === 'leave'): ?>
    <i class="fas fa-notes-medical me-1 text-primary"></i><strong>Licencia</strong>
    <?php else: ?>
    <i class="fas fa-clock me-1"></i>
    <?php echo attendanceFormatTime($block->start_time); ?> – <?php echo attendanceFormatTime($block->end_time); ?>
    <?php if (!empty($block->shift_name)): ?> · <?php echo htmlspecialchars($block->shift_name); ?><?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($block->notes)): ?>
    <span class="text-muted"> · <?php echo htmlspecialchars($block->notes); ?></span>
    <?php endif; ?>
</div>
<?php endforeach; endif; ?>

<?php if (!empty($ctx['has_vacation']) || !empty($ctx['has_leave'])): ?>
<p class="small text-muted mb-0">
    <a href="<?php echo URLROOT; ?>/admin/employeeProfile/<?php echo (int)$data['user_id']; ?>#tab-vacation">Ver saldo de vacaciones</a>
</p>
<?php endif; ?>

<?php if (!empty($day['swaps'])): ?>
<h6 class="fw-bold small text-uppercase text-muted mt-3">Cambio de turno</h6>
<?php foreach ($day['swaps'] as $swap): ?>
<p class="small mb-1">
    <i class="fas fa-exchange-alt me-1 text-purple" style="color:#7c3aed"></i>
    <span class="badge bg-<?php echo $swap->status === 'Aprobado' ? 'success' : 'warning'; ?>"><?php echo htmlspecialchars($swap->status); ?></span>
    <?php if ((int)$swap->proposer_user_id === (int)$data['user_id']): ?>
    Turno <?php echo attendanceFormatTime($swap->proposer_start); ?>–<?php echo attendanceFormatTime($swap->proposer_end); ?>
    <?php if (!empty($swap->accepter_name)): ?> con <?php echo htmlspecialchars($swap->accepter_name); ?><?php endif; ?>
    <?php else: ?>
    Con <?php echo htmlspecialchars($swap->proposer_name); ?>
    <?php endif; ?>
</p>
<?php endforeach; ?>
<?php endif; ?>

<?php if (!empty($day['cp_tasks'])): ?>
<h6 class="fw-bold small text-uppercase text-muted mt-3">Extras Casa Paviotti</h6>
<?php foreach ($day['cp_tasks'] as $cp): ?>
<div class="cal-panel-block border-success">
    <strong><?php echo htmlspecialchars($cp->task_name ?? 'Tarea'); ?></strong>
    <span class="badge bg-<?php echo ($cp->status ?? '') === 'pending' ? 'warning text-dark' : 'secondary'; ?> ms-1">
        <?php echo ($cp->status ?? '') === 'pending' ? 'Pendiente' : 'Cerrado'; ?>
    </span>
    <span class="float-end fw-semibold"><?php echo cp_format_money($cp->amount ?? 0); ?></span>
    <?php
    $sub = trim((string)($cp->deceased_name ?? ''));
    if ($sub === '' && !empty($cp->external_company_name)) {
        $sub = $cp->external_company_name;
    }
    if ($sub !== ''): ?>
    <div class="small text-muted mt-1"><?php echo htmlspecialchars($sub); ?></div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php if (function_exists('company_uses_casapav_tasks') && company_uses_casapav_tasks((int)($selectedUser->company_id ?? 0))): ?>
<a href="<?php echo URLROOT; ?>/cpTaskAdmin/pending" class="btn btn-sm btn-outline-success mt-1">Admin → Pendientes CP</a>
<?php endif; ?>
<?php endif; ?>

<?php if (!empty($day['incidents'])): ?>
<h6 class="fw-bold small text-uppercase text-muted mt-3">Incidencia RRHH</h6>
<?php foreach ($day['incidents'] as $inc): ?>
<div class="cal-panel-block border-warning">
    <strong><?php echo htmlspecialchars(employee_incident_type_label($inc->incident_type)); ?></strong>
    <?php if (!empty($inc->title)): ?> — <?php echo htmlspecialchars($inc->title); ?><?php endif; ?>
    <?php if (!empty($inc->description)): ?>
    <div class="small text-muted mt-1"><?php echo nl2br(htmlspecialchars(mb_substr($inc->description, 0, 200))); ?></div>
    <?php endif; ?>
    <?php if (!empty($inc->attachment_path)): ?>
    <a href="<?php echo htmlspecialchars(admin_employee_incident_stream_url((int)$data['user_id'], (int)$inc->id)); ?>"
       target="_blank" rel="noopener" class="small d-inline-block mt-1"><i class="fas fa-paperclip"></i> Adjunto</a>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<a href="<?php echo URLROOT; ?>/admin/employeeProfile/<?php echo (int)$data['user_id']; ?>#tab-incidents" class="btn btn-sm btn-outline-secondary mt-1">Ficha → Incidencias</a>
<?php endif; ?>

<h6 class="fw-bold small text-uppercase text-muted mt-3">Fichado</h6>
<?php if ($ctx['has_vacation'] ?? false): ?>
<p class="small text-muted">Día de vacaciones — no se exige fichada.</p>
<?php elseif ($ctx['has_leave'] ?? false): ?>
<p class="small text-muted">Día de licencia — no se exige fichada.</p>
<?php elseif ($att): ?>
<div class="mb-2"><?php echo attendanceStatusBadge($att->status); ?></div>
<p class="small mb-1">Entrada: <strong><?php echo attendanceFormatTime($att->actual_entry); ?></strong>
    · Salida: <strong><?php echo attendanceFormatTime($att->actual_exit); ?></strong></p>
<?php if ($att->delta_minutes !== null): ?>
<p class="small">Diferencia: <strong class="<?php echo $att->delta_minutes >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo attendanceFormatMinutes($att->delta_minutes); ?></strong></p>
<?php endif; ?>
<a href="<?php echo URLROOT; ?>/admin/marcacionesTodas?person_q=<?php echo urlencode($selectedUser->full_name); ?>&start_date=<?php echo urlencode($day['date']); ?>&end_date=<?php echo urlencode($day['date']); ?>" class="btn btn-sm btn-outline-secondary">Ver marcaciones</a>
<?php else: ?>
<p class="small text-muted">Sin fichadas registradas.</p>
<?php endif; ?>

<?php if (!empty($day['requests'])): ?>
<h6 class="fw-bold small text-uppercase text-muted mt-3">Solicitud aprobada</h6>
<?php foreach ($day['requests'] as $req): ?>
<p class="small mb-0"><i class="fas fa-umbrella-beach me-1"></i><?php echo htmlspecialchars($req->type_name); ?>
    <span class="text-muted">(<?php echo date('d/m', strtotime($req->start_date)); ?>–<?php echo date('d/m', strtotime($req->end_date)); ?>)</span></p>
<?php endforeach; ?>
<?php endif; ?>

<hr class="my-3">
<h6 class="fw-bold"><i class="fas fa-file-signature me-1"></i> Permiso / justificación</h6>
<?php
$defaultType = $just ? $just->justification_type : (
    $att ? AttendanceJustification::suggestTypeForStatus($att->status) : ($suggestType ?: 'other')
);
$showEarlyHint = $att && in_array($att->status, ['early_leave', 'missing_out', 'late'], true) && !$just && empty($ctx['has_vacation']) && empty($ctx['has_leave']);
?>
<?php if ($showEarlyHint): ?>
<div class="alert alert-warning py-2 small mb-2">
    <i class="fas fa-info-circle me-1"></i>
    Alerta: <strong><?php echo htmlspecialchars(attendanceStatusMeta($att->status)['label']); ?></strong>.
    Registrá permiso con aviso previo.
</div>
<?php endif; ?>
<?php if ($just): ?>
<div class="alert alert-success py-2 small">
    <?php echo attendanceJustificationDetailHtml($just); ?>
    <?php if ($just->file_path): ?>
    <br><a href="<?php echo htmlspecialchars(admin_justification_stream_url($data['user_id'], $day['date'])); ?>" target="_blank" rel="noopener" class="alert-link">Ver adjunto</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<form method="post" action="<?php echo URLROOT; ?>/admin/saveAttendanceJustification" enctype="multipart/form-data" class="cal-justify-form" data-default-type="<?php echo htmlspecialchars($defaultType); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="user_id" value="<?php echo (int)$data['user_id']; ?>">
    <input type="hidden" name="work_date" value="<?php echo htmlspecialchars($day['date']); ?>">
    <input type="hidden" name="month" value="<?php echo htmlspecialchars($data['month']); ?>">
    <div class="mb-2">
        <label class="form-label small fw-semibold">Motivo</label>
        <select name="justification_type" class="form-select form-select-sm cal-justify-type" required>
            <?php foreach ($typeLabelsGrouped as $groupLabel => $types): ?>
            <optgroup label="<?php echo htmlspecialchars($groupLabel); ?>">
                <?php foreach ($types as $k => $label): ?>
                <option value="<?php echo htmlspecialchars($k); ?>" <?php echo ($defaultType === $k) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
                <?php endforeach; ?>
            </optgroup>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="cal-leave-fields mb-2" style="display:none">
        <label class="form-label small fw-semibold">Hora de salida</label>
        <input type="time" name="leave_time" class="form-control form-control-sm cal-leave-time"
               value="<?php echo $just && !empty($just->leave_time) ? htmlspecialchars(substr($just->leave_time, 0, 5)) : ($att && $att->actual_exit ? attendanceFormatTime($att->actual_exit) : ''); ?>">
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="prior_notice" value="1" id="prior_<?php echo str_replace('-', '', $day['date']); ?>"
                <?php echo (!$just || !empty($just->prior_notice)) ? 'checked' : ''; ?>>
            <label class="form-check-label small" for="prior_<?php echo str_replace('-', '', $day['date']); ?>">
                Hubo aviso previo (supervisor / RRHH)
            </label>
        </div>
    </div>
    <div class="mb-2">
        <label class="form-label small fw-semibold">Detalle / observaciones</label>
        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Ej. turno médico 10:30"><?php echo $just ? htmlspecialchars($just->notes) : ''; ?></textarea>
    </div>
    <div class="mb-3">
        <label class="form-label small fw-semibold">Adjunto</label>
        <input type="file" name="certificate" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp">
    </div>
    <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">Guardar permiso / motivo</button>
</form>
<?php if ($just): ?>
<form method="post" action="<?php echo URLROOT; ?>/admin/saveAttendanceJustification" onsubmit="return confirm('¿Eliminar la justificación de este día?');">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="user_id" value="<?php echo (int)$data['user_id']; ?>">
    <input type="hidden" name="work_date" value="<?php echo htmlspecialchars($day['date']); ?>">
    <input type="hidden" name="month" value="<?php echo htmlspecialchars($data['month']); ?>">
    <input type="hidden" name="delete_justification" value="1">
    <button type="submit" class="btn btn-outline-danger btn-sm w-100">Eliminar justificación</button>
</form>
<?php endif; ?>
