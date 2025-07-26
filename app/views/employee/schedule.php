<?php
// ----------------------------------------------------------------------
// ARCHIVO 7: app/views/employee/schedule.php (ACTUALIZADO)
// Se añade una nueva sección para mostrar el horario planificado de la semana.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<!-- INICIO DE CAMBIO: Nueva sección para el horario planificado -->
<?php if($data['plannedSchedule']): ?>
<div class="card shadow mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Mi Horario Planificado para esta Semana</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-borderless mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">Lunes</th>
                        <th class="text-center">Martes</th>
                        <th class="text-center">Miércoles</th>
                        <th class="text-center">Jueves</th>
                        <th class="text-center">Viernes</th>
                        <th class="text-center">Sábado</th>
                        <th class="text-center">Domingo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-center"><?php echo $data['plannedSchedule']->monday ?: '-'; ?></td>
                        <td class="text-center"><?php echo $data['plannedSchedule']->tuesday ?: '-'; ?></td>
                        <td class="text-center"><?php echo $data['plannedSchedule']->wednesday ?: '-'; ?></td>
                        <td class="text-center"><?php echo $data['plannedSchedule']->thursday ?: '-'; ?></td>
                        <td class="text-center"><?php echo $data['plannedSchedule']->friday ?: '-'; ?></td>
                        <td class="text-center"><?php echo $data['plannedSchedule']->saturday ?: '-'; ?></td>
                        <td class="text-center"><?php echo $data['plannedSchedule']->sunday ?: '-'; ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>
<!-- FIN DE CAMBIO -->

<div class="row">
    <!-- Columna de Fichaje y Resumen Semanal -->
    <div class="col-lg-4 mb-4">
        <!-- ... (código existente de la tarjeta de fichaje) ... -->
    </div>
    <div class="col-lg-8 mb-4">
        <!-- ... (código existente de la tarjeta de resumen semanal) ... -->
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>