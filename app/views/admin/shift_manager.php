<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/admin/shift_manager.php (VERSIÓN CORRECTA CON COLOR)
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<!-- Estilos para el formulario dinámico y el indicador de color -->
<style>
    .time-range { display: flex; align-items: center; margin-bottom: 10px; gap: 10px; }
    .remove-range-btn {
        background-color: #dc3545; color: white; border: none; border-radius: 50%;
        width: 25px; height: 25px; line-height: 25px; text-align: center; cursor: pointer;
    }
    .color-indicator {
        display: inline-block;
        width: 15px;
        height: 15px;
        border-radius: 3px;
        margin-right: 8px;
        border: 1px solid #ccc;
        vertical-align: middle;
    }
</style>

<div class="row">
    <!-- Columna para mostrar los turnos existentes -->
    <div class="col-md-7">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-clock me-2"></i>Turnos Predefinidos</h5></div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nombre del Turno</th>
                            <th>Rangos Horarios</th>
                            <th>Total Horas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($data['shifts'])): ?>
                            <?php foreach($data['shifts'] as $shift): ?>
                                <tr>
                                    <td>
                                        <span class="color-indicator" style="background-color: <?php echo htmlspecialchars($shift->color); ?>;"></span>
                                        <?php echo htmlspecialchars($shift->shift_name); ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($shift->ranges)): ?>
                                            <?php foreach($shift->ranges as $range): ?>
                                                <span class="badge bg-primary"><?php echo date('H:i', strtotime($range->start_time)); ?> - <?php echo date('H:i', strtotime($range->end_time)); ?></span><br>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo number_format($shift->total_hours, 2); ?> hs</strong></td>
                                    <td><a href="<?php echo URLROOT; ?>/admin/deleteShift/<?php echo $shift->id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center">No hay turnos predefinidos.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Columna para crear un nuevo turno -->
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Turno Partido</h5></div>
            <div class="card-body">
                <form id="shift-form" action="<?php echo URLROOT; ?>/admin/createSplitShift" method="post">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Turno</label>
                        <input type="text" name="shift_name" class="form-control" placeholder="Ej: Turno Cortado" required>
                    </div>
                    
                    <?php // ▼▼▼ ¡AQUÍ ESTÁ EL CAMPO DE COLOR QUE FALTABA! ▼▼▼ ?>
                    <div class="mb-3">
                        <label class="form-label">Color del Turno</label>
                        <input type="color" name="color" class="form-control form-control-color" value="#3788d8">
                    </div>

                    <h6>Rangos Horarios</h6>
                    <div id="time-ranges-container">
                        <div class="time-range">
                            <input type="time" name="ranges[0][inicio]" class="form-control" required><span>-</span>
                            <input type="time" name="ranges[0][fin]" class="form-control" required>
                        </div>
                    </div>
                    <button type="button" id="add-range-btn" class="btn btn-sm btn-secondary mt-2">+ Agregar Rango</button>
                    
                    <div class="mt-3">
                        <label class="form-label">Notas (Opcional)</label>
                        <textarea name="notes" class="form-control"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mt-3">Guardar Turno</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript sin cambios -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var addRangeBtn = document.getElementById('add-range-btn');
    var container = document.getElementById('time-ranges-container');
    var rangeIndex = 1;
    addRangeBtn.addEventListener('click', function() {
        var newRange = document.createElement('div');
        newRange.classList.add('time-range');
        newRange.innerHTML =
            '<input type="time" name="ranges[' + rangeIndex + '][inicio]" class="form-control" required>' +
            '<span>-</span>' +
            '<input type="time" name="ranges[' + rangeIndex + '][fin]" class="form-control" required>' +
            '<button type="button" class="remove-range-btn">&times;</button>';
        container.appendChild(newRange);
        rangeIndex++;
    });
    container.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-range-btn')) {
            e.target.parentElement.remove();
        }
    });
});
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
