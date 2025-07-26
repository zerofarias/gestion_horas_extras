<?php
// ----------------------------------------------------------------------
// ARCHIVO 3: app/views/admin/requests.php (VERSIÓN COMPLETA Y CORRECTA)
// Este archivo contiene el HTML del calendario y la tabla, y el script
// para inicializarlos.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="row">
    <!-- Columna del Calendario -->
    <div class="col-12 mb-4">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Calendario de Solicitudes</h5></div>
            <div class="card-body">
                <div id='calendar'></div>
            </div>
        </div>
    </div>

    <!-- Columna de la Tabla de Datos -->
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-list-ul me-2"></i>Detalle de Todas las Solicitudes</h5></div>
            <div class="card-body">
                <?php if(isset($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table id="requests-table" class="table table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th>Tipo</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['requests'] as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request->full_name); ?></td>
                                    <td><span class="badge" style="background-color: <?php echo $request->color; ?>; color: white;"><?php echo htmlspecialchars($request->type_name); ?></span></td>
                                    <td><?php echo date('d/m/Y', strtotime($request->start_date)); ?></td>
                                    <td><?php echo $request->end_date ? date('d/m/Y', strtotime($request->end_date)) : '-'; ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'bg-secondary';
                                            if ($request->status == 'Aprobado') $statusClass = 'bg-success';
                                            if ($request->status == 'Rechazado') $statusClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $request->status; ?></span>
                                    </td>
                                    <td>
                                        <a href="<?php echo URLROOT; ?>/admin/editRequest/<?php echo $request->id; ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                                        <?php if($request->status == 'Pendiente'): ?>
                                            <a href="#" class="btn btn-success btn-sm action-btn" data-action="approve" data-id="<?php echo $request->id; ?>" title="Aprobar"><i class="fas fa-check"></i></a>
                                            <a href="#" class="btn btn-danger btn-sm action-btn" data-action="reject" data-id="<?php echo $request->id; ?>" title="Rechazar"><i class="fas fa-times"></i></a>
                                        <?php endif; ?>
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

<!-- Div oculto para pasar los eventos al script -->
<div id="calendar-events" data-events='<?php echo $data['calendarEvents']; ?>' style="display:none;"></div>

<?php require APPROOT . '/views/inc/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización de la tabla
    if ($('#requests-table').length && !$.fn.dataTable.isDataTable('#requests-table')) {
        $('#requests-table').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            responsive: true,
            order: [[ 2, "desc" ]],
            dom: 'Bfrtip',
            buttons: ['excel', 'pdf', 'print']
        });
    }

    // Inicialización del Calendario
    var calendarEl = document.getElementById('calendar');
    var eventsData = JSON.parse(document.getElementById('calendar-events').getAttribute('data-events'));

    var calendar = new FullCalendar.Calendar(calendarEl, {
        themeSystem: 'bootstrap5',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        locale: 'es',
        events: eventsData,
        eventClick: function(info) {
            let event = info.event;
            Swal.fire({
                title: event.title,
                html: `
                    <strong>Motivo:</strong><p>${event.extendedProps.reason}</p>
                    <strong>Estado:</strong><p>${event.extendedProps.status}</p>
                `,
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-check"></i> Aprobar',
                denyButtonText: '<i class="fas fa-times"></i> Rechazar',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#198754',
                denyButtonColor: '#dc3545',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?php echo URLROOT; ?>/admin/approveRequest/${event.extendedProps.id}`;
                } else if (result.isDenied) {
                    window.location.href = `<?php echo URLROOT; ?>/admin/rejectRequest/${event.extendedProps.id}`;
                }
            })
        }
    });
    calendar.render();
    
    // Lógica para los botones de acción en la tabla
    $('#requests-table').on('click', '.action-btn', function(e) {
        e.preventDefault();
        const requestId = $(this).data('id');
        const action = $(this).data('action');
        
        const config = {
            approve: { title: '¿Aprobar Solicitud?', icon: 'success', confirmButtonText: 'Sí, ¡aprobar!' },
            reject: { title: '¿Rechazar Solicitud?', icon: 'warning', confirmButtonText: 'Sí, ¡rechazar!' }
        };
        const currentConfig = config[action];

        Swal.fire({
            title: currentConfig.title,
            icon: currentConfig.icon,
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: currentConfig.confirmButtonText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                let urlAction = (action === 'approve') ? 'approveRequest' : 'rejectRequest';
                window.location.href = `<?php echo URLROOT; ?>/admin/${urlAction}/${requestId}`;
            }
        });
    });
});
</script>