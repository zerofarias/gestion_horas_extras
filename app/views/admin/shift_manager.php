<?php
require APPROOT . '/views/inc/header.php';

$viewData = $data ?? [];
$shifts = $viewData['shifts'] ?? [];
$totalShifts = count($shifts);
$totalRanges = array_sum(array_map(fn($shift) => count($shift->ranges ?? []), $shifts));
$avgHours = $totalShifts > 0 ? array_sum(array_map(fn($shift) => (float)$shift->total_hours, $shifts)) / $totalShifts : 0;
?>

<?php if(!empty($viewData['success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i><?php echo $viewData['success']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if(!empty($viewData['error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $viewData['error']; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="admin-page-head">
    <div class="admin-page-brand">
        <div class="admin-page-icon"><i class="fas fa-business-time"></i></div>
        <div class="admin-page-meta">
            <h2 class="page-title">Turnos</h2>
            <p class="page-subtitle mb-0">Definí bloques reutilizables para el planificador semanal.</p>
        </div>
    </div>
</div>

<div class="admin-kpi-grid">
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon" style="background:var(--clr-primary-l);color:var(--clr-primary)"><i class="fas fa-layer-group"></i></div>
        <div><div class="admin-kpi-value"><?php echo $totalShifts; ?></div><div class="admin-kpi-label">Turnos activos</div></div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon" style="background:#dbeafe;color:#1d4ed8"><i class="fas fa-clock"></i></div>
        <div><div class="admin-kpi-value"><?php echo $totalRanges; ?></div><div class="admin-kpi-label">Rangos totales</div></div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon" style="background:#d1fae5;color:#065f46"><i class="fas fa-stopwatch"></i></div>
        <div><div class="admin-kpi-value"><?php echo number_format($avgHours, 1); ?></div><div class="admin-kpi-label">Horas promedio</div></div>
    </div>
    <div class="admin-kpi-card">
        <div class="admin-kpi-icon" style="background:#fff3cd;color:#8a5600"><i class="fas fa-lightbulb"></i></div>
        <div><div class="admin-kpi-value"><?php echo $totalShifts > 0 ? 'OK' : '0'; ?></div><div class="admin-kpi-label">Base reusable</div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <section class="admin-surface h-100">
            <div class="admin-surface-head">
                <div>
                    <h3 class="admin-surface-title"><i class="fas fa-clock"></i>Turnos predefinidos</h3>
                    <p class="admin-surface-subtitle">Colores, rangos y duración total por plantilla.</p>
                </div>
            </div>
            <div class="admin-surface-body is-tight">
                <div class="table-responsive">
                <table class="table table-striped admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Nombre del Turno</th>
                            <th>Rangos Horarios</th>
                            <th>Total Horas</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($shifts)): ?>
                            <?php foreach($shifts as $shift):
                                $rangesJson = htmlspecialchars(json_encode(array_map(function($r){
                                    return ['inicio' => substr($r->start_time,0,5), 'fin' => substr($r->end_time,0,5)];
                                }, $shift->ranges ?? [])), ENT_QUOTES);
                            ?>
                                <tr>
                                    <td>
                                        <span class="color-indicator" style="background-color:<?php echo htmlspecialchars($shift->color); ?>;"></span>
                                        <?php echo htmlspecialchars($shift->shift_name); ?>
                                    </td>
                                    <td>
                                        <?php foreach($shift->ranges ?? [] as $range): ?>
                                            <span class="badge bg-primary">
                                                <?php echo date('H:i', strtotime($range->start_time)); ?> - <?php echo date('H:i', strtotime($range->end_time)); ?>
                                            </span><br>
                                        <?php endforeach; ?>
                                    </td>
                                    <td><strong><?php echo number_format($shift->total_hours, 2); ?> hs</strong></td>
                                    <td class="text-center" style="white-space:nowrap;">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary me-1"
                                                onclick="openEditModal(<?php echo $shift->id; ?>, '<?php echo htmlspecialchars(addslashes($shift->shift_name)); ?>', '<?php echo htmlspecialchars($shift->color); ?>', '<?php echo htmlspecialchars(addslashes($shift->notes ?? '')); ?>', <?php echo $rangesJson; ?>)">
                                            <i class="fas fa-pencil-alt me-1"></i>Editar
                                        </button>
                                        <form method="post" action="<?php echo URLROOT; ?>/admin/deleteShift/<?php echo (int)$shift->id; ?>" class="d-inline"
                                              onsubmit="return confirm('¿Eliminar este turno predefinido?');">
                                            <?php echo csrf_field(); ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash me-1"></i>Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No hay turnos predefinidos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="admin-surface">
            <div class="admin-surface-head">
                <div>
                    <h3 class="admin-surface-title"><i class="fas fa-plus-circle"></i>Crear nuevo turno</h3>
                    <p class="admin-surface-subtitle">Definí un nombre, color y todos los bloques horarios.</p>
                </div>
            </div>
            <div class="admin-surface-body">
                <form id="shift-form" action="<?php echo URLROOT; ?>/admin/createSplitShift" method="post">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Nombre del Turno</label>
                        <input type="text" name="shift_name" class="form-control" placeholder="Ej: Turno Ma&ntilde;ana" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color del Turno</label>
                        <input type="color" name="color" class="form-control form-control-color" value="#3788d8">
                    </div>
                    <h6>Rangos Horarios</h6>
                    <div id="time-ranges-container">
                        <div class="time-range">
                            <input type="time" name="ranges[0][inicio]" class="form-control" required>
                            <span>-</span>
                            <input type="time" name="ranges[0][fin]" class="form-control" required>
                        </div>
                    </div>
                    <button type="button" id="add-range-btn" class="btn btn-sm btn-secondary mt-2">+ Agregar Rango</button>
                    <div class="mt-3">
                        <label class="form-label">Notas (Opcional)</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3"><i class="fas fa-save me-1"></i>Guardar Turno</button>
                </form>
                <div class="admin-note-panel mt-3">
                    <div class="admin-note-title">Recomendación operativa</div>
                    <p class="admin-note-text">Usá nombres claros por franja o sector, por ejemplo “Mañana Caja” o “Noche Depósito”, para facilitar el arrastre en el planificador.</p>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modal editar turno -->
<div class="modal fade" id="editShiftModal" tabindex="-1" aria-labelledby="editShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editShiftModalLabel"><i class="fas fa-pencil-alt me-2"></i>Editar Turno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="edit-shift-form" method="post">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre del Turno</label>
                            <input type="text" id="edit-shift-name" name="shift_name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="color" id="edit-shift-color" name="color" class="form-control form-control-color w-100">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Notas (Opcional)</label>
                        <textarea id="edit-shift-notes" name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <hr>
                    <h6>Rangos Horarios</h6>
                    <div id="edit-time-ranges-container"></div>
                    <button type="button" id="edit-add-range-btn" class="btn btn-sm btn-secondary mt-2">+ Agregar Rango</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    var createRangeHtml = function(idx, inicio, fin) {
        return '<div class="time-range">' +
            '<input type="time" name="ranges[' + idx + '][inicio]" class="form-control" value="' + (inicio || '') + '" required>' +
            '<span>-</span>' +
            '<input type="time" name="ranges[' + idx + '][fin]" class="form-control" value="' + (fin || '') + '" required>' +
            '<button type="button" class="remove-range-btn">&times;</button>' +
            '</div>';
    };

    /* ---- Crear turno: agregar rango ---- */
    var rangeIndex = 1;
    document.getElementById('add-range-btn').addEventListener('click', function() {
        var container = document.getElementById('time-ranges-container');
        var div = document.createElement('div');
        div.className = 'time-range';
        div.innerHTML =
            '<input type="time" name="ranges[' + rangeIndex + '][inicio]" class="form-control" required>' +
            '<span>-</span>' +
            '<input type="time" name="ranges[' + rangeIndex + '][fin]" class="form-control" required>' +
            '<button type="button" class="remove-range-btn">&times;</button>';
        container.appendChild(div);
        rangeIndex++;
    });
    document.getElementById('time-ranges-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-range-btn')) e.target.parentElement.remove();
    });

    /* ---- Modal editar: agregar rango ---- */
    var editRangeIndex = 0;
    document.getElementById('edit-add-range-btn').addEventListener('click', function() {
        var container = document.getElementById('edit-time-ranges-container');
        container.insertAdjacentHTML('beforeend', createRangeHtml(editRangeIndex++, '', ''));
    });
    document.getElementById('edit-time-ranges-container').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-range-btn')) {
            var ranges = document.querySelectorAll('#edit-time-ranges-container .time-range');
            if (ranges.length <= 1) { alert('Debe quedar al menos un rango horario.'); return; }
            e.target.parentElement.remove();
        }
    });
})();

function openEditModal(id, name, color, notes, ranges) {
    document.getElementById('edit-shift-form').action = '<?php echo URLROOT; ?>/admin/updateShift/' + id;
    document.getElementById('edit-shift-name').value  = name;
    document.getElementById('edit-shift-color').value = color;
    document.getElementById('edit-shift-notes').value = notes;

    var container = document.getElementById('edit-time-ranges-container');
    container.innerHTML = '';
    var idx = 0;
    if (ranges && ranges.length > 0) {
        ranges.forEach(function(r) {
            container.insertAdjacentHTML('beforeend',
                '<div class="time-range">' +
                '<input type="time" name="ranges[' + idx + '][inicio]" class="form-control" value="' + r.inicio + '" required>' +
                '<span>-</span>' +
                '<input type="time" name="ranges[' + idx + '][fin]" class="form-control" value="' + r.fin + '" required>' +
                '<button type="button" class="remove-range-btn">&times;</button>' +
                '</div>');
            idx++;
        });
    } else {
        container.insertAdjacentHTML('beforeend',
            '<div class="time-range">' +
            '<input type="time" name="ranges[0][inicio]" class="form-control" required>' +
            '<span>-</span>' +
            '<input type="time" name="ranges[0][fin]" class="form-control" required>' +
            '<button type="button" class="remove-range-btn">&times;</button>' +
            '</div>');
    }

    var modal = new bootstrap.Modal(document.getElementById('editShiftModal'));
    modal.show();
}
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>

