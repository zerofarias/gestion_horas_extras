<?php
// ----------------------------------------------------------------------
// ARCHIVO 6: app/views/admin/schedules.php (NUEVO ARCHIVO)
// Esta es la nueva página para que el admin planifique los horarios.
// Debes CREAR este archivo en la ruta app/views/admin/.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Planificación de Horarios Semanales</h4>
    </div>
    <div class="card-body">
        <form action="<?php echo URLROOT; ?>/admin/schedules" method="get" class="row g-3 align-items-center mb-4">
            <div class="col-auto">
                <label for="week" class="form-label">Seleccionar Semana:</label>
            </div>
            <div class="col-auto">
                <input type="week" id="week" name="week" class="form-control" value="<?php echo $data['current_week_input']; ?>">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Cargar Horarios</button>
            </div>
        </form>

        <?php if(isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>

        <form action="<?php echo URLROOT; ?>/admin/schedules" method="post">
            <input type="hidden" name="year" value="<?php echo $data['year']; ?>">
            <input type="hidden" name="week_number" value="<?php echo $data['week_number']; ?>">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Empleado</th>
                            <th>Lunes</th>
                            <th>Martes</th>
                            <th>Miércoles</th>
                            <th>Jueves</th>
                            <th>Viernes</th>
                            <th>Sábado</th>
                            <th>Domingo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['users'] as $user): if($user->is_active): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user->full_name); ?></td>
                                <?php $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']; ?>
                                <?php foreach($days as $day): ?>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="schedules[<?php echo $user->id; ?>][<?php echo $day; ?>]" 
                                               value="<?php echo isset($data['schedules'][$user->id]) ? htmlspecialchars($data['schedules'][$user->id]->$day) : ''; ?>">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endif; endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="text-end mt-3">
                <button type="submit" class="btn btn-success btn-lg">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>