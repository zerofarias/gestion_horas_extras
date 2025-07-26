<?php
// ----------------------------------------------------------------------
// ARCHIVO 4: app/views/admin/shift_manager.php (NUEVO ARCHIVO)
// Esta es la nueva página para que el admin cree y gestione los turnos.
// Debes CREAR este archivo en la ruta app/views/admin/.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-clock me-2"></i>Turnos Predefinidos</h5></div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Nombre del Turno</th><th>Horario</th><th>Total Horas</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach($data['shifts'] as $shift): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($shift->shift_name); ?></td>
                                <td><?php echo date('H:i', strtotime($shift->start_time)); ?> - <?php echo date('H:i', strtotime($shift->end_time)); ?></td>
                                <td><?php echo number_format($shift->total_hours, 2); ?> hs</td>
                                <td><a href="<?php echo URLROOT; ?>/admin/deleteShift/<?php echo $shift->id; ?>" class="btn btn-sm btn-danger">Eliminar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Crear Nuevo Turno</h5></div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/shiftManager" method="post">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Turno</label>
                        <input type="text" name="shift_name" class="form-control" placeholder="Ej: Turno Mañana" required>
                    </div>
                    <div class="row">
                        <div class="col-6"><label class="form-label">Hora Inicio</label><input type="time" name="start_time" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Hora Fin</label><input type="time" name="end_time" class="form-control" required></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-3">Crear Turno</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>