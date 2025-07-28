<?php require APPROOT . '/views/inc/header.php'; ?>

<!-- Incluimos las librerías de FullCalendar desde un CDN -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/es.js'></script> <!-- Importante: Locale en español -->

<div class="row">
    <!-- Calendario de Solicitudes -->
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header"><h5 class="mb-0">Calendario de Solicitudes</h5></div>
            <div class="card-body">
                <div id="calendar"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Detalle de Todas las Solicitudes -->
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Detalle de Todas las Solicitudes</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Tipo</th>
                                <th>Fechas</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['requests'] as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request->full_name); ?></td>
                                    <td><?php echo htmlspecialchars($request->type_name); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($request->start_date)); ?> - <?php echo date('d/m/Y', strtotime($request->end_date)); ?></td>
                                    <td><?php echo htmlspecialchars($request->reason); ?></td>
                                    <td>
                                        <?php 
                                            $status_class = 'secondary';
                                            if($request->status == 'Aprobado') $status_class = 'success';
                                            if($request->status == 'Rechazado') $status_class = 'danger';
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>"><?php echo $request->status; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <?php if($request->status == 'Pendiente'): ?>
                                            <a href="<?php echo URLROOT; ?>/admin/approveRequest/<?php echo $request->id; ?>" class="btn btn-sm btn-success" title="Aprobar"><i class="fas fa-check"></i></a>
                                            <a href="<?php echo URLROOT; ?>/admin/rejectRequest/<?php echo $request->id; ?>" class="btn btn-sm btn-danger" title="Rechazar"><i class="fas fa-times"></i></a>
                                        <?php endif; ?>
                                        <a href="<?php echo URLROOT; ?>/admin/editRequest/<?php echo $request->id; ?>" class="btn btn-sm btn-info" title="Editar"><i class="fas fa-pencil-alt"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es', // Usar el idioma español
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: <?php echo $data['calendarEvents']; ?>, // Cargar los eventos desde el controlador
        eventTimeFormat: { // Opcional: formato de hora
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false
        }
    });
    calendar.render();
});
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
