<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/views/admin/employee_summary.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="row">
    <!-- Columna Izquierda: Perfil y Notas -->
    <div class="col-lg-4 mb-4">
        <!-- Tarjeta de Perfil -->
        <div class="card shadow mb-4">
            <div class="card-body text-center">
                <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo isset($data['user']->profile_picture) ? $data['user']->profile_picture : 'default.png'; ?>" 
                     alt="Avatar" class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                <h4 class="card-title"><?php echo htmlspecialchars($data['user']->full_name); ?></h4>
                <p class="text-muted mb-1"><?php echo htmlspecialchars(ucfirst($data['user']->role)); ?></p>
                <p class="small text-muted"><?php echo isset($data['user']->email) ? htmlspecialchars($data['user']->email) : 'Email no especificado'; ?></p>
                <a href="<?php echo URLROOT; ?>/admin/editUser/<?php echo $data['user']->id; ?>" class="btn btn-outline-primary btn-sm">Editar Ficha Completa</a>
            </div>
        </div>
        
        <!-- Tarjeta de Notas / Incidencias -->
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notas / Incidencias</h5></div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/addNote/<?php echo $data['user']->id; ?>" method="post">
                    <textarea class="form-control mb-2" name="note" rows="2" placeholder="Añadir una nueva nota..." required></textarea>
                    <button type="submit" class="btn btn-secondary btn-sm w-100">Guardar Nota</button>
                </form>
                <hr>
                <div class="list-group list-group-flush" style="max-height: 200px; overflow-y: auto;">
                    <?php if(empty($data['userNotes'])): ?>
                        <p class="small text-muted">No hay notas registradas.</p>
                    <?php else: ?>
                        <?php foreach($data['userNotes'] as $note): ?>
                            <div class="list-group-item">
                                <p class="mb-1"><?php echo htmlspecialchars($note->note); ?></p>
                                <small class="text-muted">Por <?php echo $note->admin_name; ?> el <?php echo date('d/m/Y', strtotime($note->created_at)); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna Derecha: Actividad y Resumen -->
    <div class="col-lg-8">
        <!-- Calendario de Actividad -->
        <div class="card shadow mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Calendario de Actividad</h5></div>
            <div class="card-body">
                <div id="employee-activity-calendar" style="height: 400px;"></div>
            </div>
        </div>

        <!-- Gráfico de Horas Extras -->
        <div class="card shadow mb-4">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Resumen de Horas Extras (Últimos 6 Meses)</h5></div>
            <div class="card-body">
                <canvas id="overtimeSummaryChart" style="height: 250px;"></canvas>
            </div>
        </div>

        <!-- Tabla de Últimas Marcaciones -->
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-history me-2"></i>Últimas Marcaciones</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="latest-clockings-table" class="table table-sm table-hover table-striped" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha</th>
                                <th>Entrada</th>
                                <th>Salida</th>
                                <th>Total Horas</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($data['latestClockings'])): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No hay marcaciones registradas.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach($data['latestClockings'] as $clocking): ?>
                                    <tr class="<?php echo ($clocking['status'] != 'Completo') ? 'table-warning' : ''; ?>">
                                        <td><?php echo date('d/m/Y', strtotime($clocking['work_date'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($clocking['entry_time'])); ?></td>
                                        <td><?php echo $clocking['exit_time'] ? date('H:i', strtotime($clocking['exit_time'])) : '-'; ?></td>
                                        <td class="fw-bold"><?php echo $clocking['total_hours'] !== null ? number_format($clocking['total_hours'], 2) : '-'; ?></td>
                                        <td>
                                            <?php if($clocking['status'] == 'Completo'): ?>
                                                <span class="badge bg-success">Completo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Incompleto</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Divs ocultos para pasar datos a JS -->
<div id="calendar-events-data" data-events='<?php echo $data['calendarEvents']; ?>'></div>
<div id="overtime-chart-data" data-chart='<?php echo $data['overtimeChartData']; ?>'></div>

<?php require APPROOT . '/views/inc/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicialización del Calendario
    var calendarEl = document.getElementById('employee-activity-calendar');
    var eventsData = JSON.parse(document.getElementById('calendar-events-data').getAttribute('data-events'));
    var calendar = new FullCalendar.Calendar(calendarEl, {
        themeSystem: 'bootstrap5',
        headerToolbar: { left: 'prev,next', center: 'title', right: 'dayGridMonth,listWeek' },
        locale: 'es',
        events: eventsData,
        initialView: 'dayGridMonth'
    });
    calendar.render();

    // Inicialización del Gráfico de Horas Extras
    var chartEl = document.getElementById('overtimeSummaryChart');
    var chartData = JSON.parse(document.getElementById('overtime-chart-data').getAttribute('data-chart'));
    new Chart(chartEl, {
        type: 'bar',
        data: {
            labels: chartData.labels,
            datasets: [
                { label: 'Horas al 50%', data: chartData.data50, backgroundColor: 'rgba(54, 162, 235, 0.6)' },
                { label: 'Horas al 100%', data: chartData.data100, backgroundColor: 'rgba(255, 159, 64, 0.6)' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } } }
    });

    // Inicialización de la tabla de marcaciones
    if ($('#latest-clockings-table').length && !$.fn.dataTable.isDataTable('#latest-clockings-table')) {
        $('#latest-clockings-table').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            responsive: true,
            order: [[ 0, "desc" ], [1, "asc"]],
            pageLength: 10,
            dom: 'frtip'
        });
    }
});
</script>
