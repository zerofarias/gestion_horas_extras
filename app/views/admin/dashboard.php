<?php require APPROOT . '/views/inc/header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body { background-color: #f0f2f5; }
    .card { border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.05); }
    .gradient-card { color: white; position: relative; overflow: hidden; border-radius: .5rem; }
    .gradient-card .card-body { position: relative; z-index: 2; padding: 1.5rem; }
    .gradient-card .stat-number-lg { font-size: 2.5rem; font-weight: 700; }
    .gradient-card .card-icon { position: absolute; top: 50%; right: -20px; transform: translateY(-50%) rotate(-15deg); font-size: 6rem; opacity: 0.2; z-index: 1; }
    .bg-gradient-blue { background: linear-gradient(45deg, #4e73df, #224abe); }
    .bg-gradient-green { background: linear-gradient(45deg, #1cc88a, #13855c); }
    .bg-gradient-cyan { background: linear-gradient(45deg, #36b9cc, #2a96a5); }
    .bg-gradient-red { background: linear-gradient(45deg, #e74a3b, #b92e21); }
    .bg-gradient-orange { background: linear-gradient(45deg, #f6c23e, #dda20a); }
    .chart-container { position: relative; height: 320px; width: 100%; }
    .employee-avatar-sm { width: 45px; height: 45px; object-fit: cover; }
    .nav-tabs .nav-link.active { color: #4e73df; font-weight: bold; }
    .top-employee-list .progress { height: 8px; }
    .birthday-card .birthday-avatar { width: 80px; height: 80px; border: 3px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .suggestion-box blockquote { font-style: italic; background: #f1f3f5; border-left: 5px solid #4e73df; padding: 1rem; }
</style>
<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/admin/dashboard.php (VERSIÓN FINAL CORREGIDA)
// INSTRUCCIONES: Este archivo está limpio de JavaScript y es más robusto.
// La lógica de los gráficos y modales debe estar en footer.php.
// ----------------------------------------------------------------------
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Dashboard del Administrador</h1>
</div>

<!-- Fila 1: KPIs de Estado General -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card gradient-card bg-gradient-blue h-100"><div class="card-body"><div><div class="text-uppercase mb-1">Empleados Activos</div><div class="stat-number-lg"><?php echo isset($data['stats']['active_users']) ? htmlspecialchars($data['stats']['active_users']) : '0'; ?></div></div><i class="fas fa-users card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card gradient-card bg-gradient-green h-100"><div class="card-body"><div><div class="text-uppercase mb-1">Trabajando Ahora</div><div class="stat-number-lg"><?php echo isset($data['stats']['working_now']) ? htmlspecialchars($data['stats']['working_now']) : '0'; ?></div></div><i class="fas fa-walking card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card gradient-card bg-gradient-cyan h-100"><div class="card-body"><div><div class="text-uppercase mb-1">De Licencia Hoy</div><div class="stat-number-lg"><?php echo isset($data['stats']['on_leave_today']) ? htmlspecialchars($data['stats']['on_leave_today']) : '0'; ?></div></div><i class="fas fa-calendar-times card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card gradient-card bg-gradient-red h-100"><div class="card-body"><div><div class="text-uppercase mb-1">Solicitudes Pendientes</div><div class="stat-number-lg"><?php echo isset($data['stats']['pending_requests_count']) ? htmlspecialchars($data['stats']['pending_requests_count']) : '0'; ?></div></div><i class="fas fa-inbox card-icon"></i></div></div></div>
</div>

<!-- Fila 2: KPIs de Horas Extras y Cumpleaños -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4"><div class="card gradient-card bg-gradient-blue h-100"><div class="card-body"><div><div class="text-uppercase mb-1">Horas al 50%</div><div class="stat-number-lg"><?php echo isset($data['stats']['overtime_50']) ? number_format($data['stats']['overtime_50'], 2) : '0.00'; ?></div></div><i class="fas fa-briefcase card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card gradient-card bg-gradient-orange h-100"><div class="card-body"><div><div class="text-uppercase mb-1">Horas al 100%</div><div class="stat-number-lg"><?php echo isset($data['stats']['overtime_100']) ? number_format($data['stats']['overtime_100'], 2) : '0.00'; ?></div></div><i class="fas fa-moon card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4"><div class="card gradient-card bg-gradient-green h-100"><div class="card-body"><div><div class="text-uppercase mb-1">Empleados con Horas</div><div class="stat-number-lg"><?php echo isset($data['stats']['employees_with_pending']) ? htmlspecialchars($data['stats']['employees_with_pending']) : '0'; ?></div></div><i class="fas fa-user-clock card-icon"></i></div></div></div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 birthday-card">
            <div class="card-body text-center d-flex flex-column justify-content-center">
                <?php 
                $birthdaysToday = isset($data['birthday_info']['today']) ? $data['birthday_info']['today'] : [];
                $upcomingBirthdays = isset($data['birthday_info']['upcoming']) ? $data['birthday_info']['upcoming'] : [];
                ?>
                <?php if (!empty($birthdaysToday)): ?>
                    <?php $firstBirthday = $birthdaysToday[0]; ?>
                    <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo htmlspecialchars($firstBirthday->profile_picture); ?>" class="rounded-circle mb-2 mx-auto birthday-avatar" alt="Avatar" onerror="this.onerror=null; this.src='<?php echo URLROOT; ?>/uploads/avatars/default.png';">
                    <h6 class="mb-0 fw-bold text-success">¡Feliz Cumpleaños! 🎂</h6>
                    <?php foreach($birthdaysToday as $bdayUser): ?>
                        <p class="mb-0 text-muted"><?php echo htmlspecialchars($bdayUser->full_name); ?></p>
                    <?php endforeach; ?>
                <?php elseif (!empty($upcomingBirthdays)): ?>
                    <?php $firstUpcoming = $upcomingBirthdays[0]; ?>
                    <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo htmlspecialchars($firstUpcoming->profile_picture); ?>" class="rounded-circle mb-2 mx-auto birthday-avatar" alt="Avatar" onerror="this.onerror=null; this.src='<?php echo URLROOT; ?>/uploads/avatars/default.png';">
                    <h6 class="mb-0 fw-bold">Próximo Cumpleaños</h6>
                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($firstUpcoming->full_name); ?></p>
                    <small class="text-muted"><?php echo date('d/m', strtotime($firstUpcoming->birth_date)); ?></small>
                <?php else: ?>
                    <i class="fas fa-birthday-cake fa-3x text-muted"></i>
                    <p class="mt-2 text-muted">No hay cumpleaños próximos.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Fila 3: Análisis Visual Avanzado con Pestañas -->
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="analyticsTabs" role="tablist">
                    <li class="nav-item"><a class="nav-link active" id="distribucion_he-tab" data-bs-toggle="tab" data-bs-target="#distribucion_he" href="#distribucion_he" role="tab">Distribución H.E.</a></li>
                    <li class="nav-item"><a class="nav-link" id="top_empleados-tab" data-bs-toggle="tab" data-bs-target="#top_empleados" href="#top_empleados" role="tab">Top 5 Empleados</a></li>
                    <li class="nav-item"><a class="nav-link" id="distribucion_dia-tab" data-bs-toggle="tab" data-bs-target="#distribucion_dia" href="#distribucion_dia" role="tab">H.E. por Día</a></li>
                    <li class="nav-item"><a class="nav-link" id="ausencias-tab" data-bs-toggle="tab" data-bs-target="#ausencias" href="#ausencias" role="tab">Resumen de Ausencias</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content" id="analyticsTabsContent">
                    <div class="tab-pane fade show active" id="distribucion_he" role="tabpanel">
                        <div class="chart-container d-flex align-items-center justify-content-center" style="height: 280px;"><div style="width:300px; height:300px;"><canvas id="overtimeDistributionChart"></canvas></div></div>
                    </div>
                    <div class="tab-pane fade p-3" id="top_empleados" role="tabpanel">
                        <h5 class="mb-4">Top 5 Empleados con Más Horas Extras Pendientes</h5>
                        <ul class="list-group list-group-flush top-employee-list">
                            <?php if(!empty($data['top_employees'])): ?>
                                <?php $maxHours = (!empty($data['top_employees']) && $data['top_employees'][0]->total_hours > 0) ? $data['top_employees'][0]->total_hours : 1; ?>
                                <?php foreach($data['top_employees'] as $employee): ?>
                                <li class="list-group-item d-flex align-items-center px-0"><img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo htmlspecialchars($employee->profile_picture); ?>" class="rounded-circle me-3 employee-avatar-sm"><div class="w-100"><div class="d-flex justify-content-between"><strong><?php echo htmlspecialchars($employee->full_name); ?></strong><span><?php echo number_format($employee->total_hours, 2); ?> hs</span></div><div class="progress"><div class="progress-bar bg-danger" style="width: <?php echo ($employee->total_hours / $maxHours) * 100; ?>%"></div></div></div></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">No hay datos de empleados para mostrar.</p>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="tab-pane fade" id="distribucion_dia" role="tabpanel"><div class="chart-container"><canvas id="overtimeByDayChart"></canvas></div></div>
                    <div class="tab-pane fade" id="ausencias" role="tabpanel"><div class="chart-container"><canvas id="requestsChart"></canvas></div></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fila 4: Paneles de Acción y Feedback -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Solicitudes Pendientes</h6></div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if(empty($data['pending_requests'])): ?>
                        <p class="text-center text-muted mt-3">No hay solicitudes pendientes.</p>
                    <?php else: ?>
                        <?php foreach($data['pending_requests'] as $request): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0"><div class="d-flex align-items-center"><img src="<?php echo URLROOT; ?>/uploads/avatars/default.png" class="rounded-circle me-3 employee-avatar-sm" alt="Avatar"><div><strong><?php echo htmlspecialchars($request->full_name); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($request->type_name); ?></small></div></div><div><a href="<?php echo URLROOT; ?>/admin/approveRequest/<?php echo $request->id; ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a> <a href="<?php echo URLROOT; ?>/admin/rejectRequest/<?php echo $request->id; ?>" class="btn btn-sm btn-danger"><i class="fas fa-times"></i></a></div></li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header"><h6 class="m-0 fw-bold text-primary">Acciones y Feedback</h6></div>
            <div class="card-body">
                <?php if(isset($data['latest_suggestion']) && $data['latest_suggestion']): ?>
                <h6 class="small fw-bold">Última Sugerencia</h6>
                <div class="suggestion-box mb-3"><blockquote class="blockquote mb-0"><p class="small">"<?php echo htmlspecialchars($data['latest_suggestion']->suggestion_text); ?>"</p><footer class="blockquote-footer small">Anónimo</footer></blockquote></div>
                <hr>
                <?php endif; ?>
                <div class="d-grid gap-2">
                    <form id="closureForm" action="<?php echo URLROOT; ?>/admin/createClosure" method="post"></form>
                    <button id="closureModalButton" type="button" class="btn btn-danger w-100"><i class="fas fa-file-invoice-dollar me-2"></i>Realizar Cierre de Horas</button>
                    <a href="<?php echo URLROOT; ?>/admin/weeklyPlanner" class="btn btn-primary"><i class="fas fa-calendar-alt me-2"></i>Ir al Planificador</a>
                    <a href="<?php echo URLROOT; ?>/admin/createUser" class="btn btn-info text-white"><i class="fas fa-user-plus me-2"></i>Crear Empleado</a>
                </div>
            </div>
        </div>
    </div>
</div>


    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var charts = {};

        function createChart(canvasId, config) {
            var ctx = document.getElementById(canvasId);
            if (ctx) {
                if (charts[canvasId]) { charts[canvasId].destroy(); }
                charts[canvasId] = new Chart(ctx, config);
            }
        }

        function initOvertimeDistributionChart() {
            var data = <?php echo isset($data['charts']['overtime_distribution']) ? $data['charts']['overtime_distribution'] : '[]'; ?>;
            if(data.reduce((a, b) => a + b, 0) > 0) {
                createChart('overtimeDistributionChart', { type: 'doughnut', data: { labels: ['Horas al 50%', 'Horas al 100%'], datasets: [{ data: data, backgroundColor: ['#4e73df', '#f6c23e'], borderWidth: 2, borderColor: '#fff' }] }, options: { maintainAspectRatio: false, cutout: '75%', plugins: { legend: { display: true, position: 'bottom' } } } });
            } else {
                var container = document.getElementById('overtimeDistributionChart').parentElement;
                container.innerHTML = '<p class="text-center text-muted mt-5">No hay horas pendientes para mostrar.</p>';
            }
        }

        function initOvertimeByDayChart() {
            createChart('overtimeByDayChart', { type: 'bar', data: { labels: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'], datasets: [{ label: 'Horas Extras', data: <?php echo isset($data['charts']['overtime_by_day']) ? $data['charts']['overtime_by_day'] : '[]'; ?>, backgroundColor: 'rgba(231, 74, 59, 0.8)' }] }, options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } } });
        }

        function initRequestsChart() {
            var requestLabels = <?php echo isset($data['charts']['requests']['labels']) ? $data['charts']['requests']['labels'] : '[]'; ?>;
            var requestData = <?php echo isset($data['charts']['requests']['data']) ? $data['charts']['requests']['data'] : '[]'; ?>;
            if (requestData.length > 0) {
                createChart('requestsChart', { type: 'doughnut', data: { labels: requestLabels, datasets: [{ data: requestData, backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'], }] }, options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } } });
            } else {
                var container = document.getElementById('requestsChart').parentElement;
                container.innerHTML = '<p class="text-center text-muted mt-5">No hay datos de ausencias para mostrar este mes.</p>';
            }
        }

        initOvertimeDistributionChart();

        var analyticsTabs = document.querySelectorAll('#analyticsTabs a[data-bs-toggle="tab"]');
        analyticsTabs.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(event) {
                var targetId = event.target.getAttribute('data-bs-target');
                if (targetId === '#distribucion_dia') {
                    initOvertimeByDayChart();
                } else if (targetId === '#ausencias') {
                    initRequestsChart();
                }
            });
        });
    });
    </script>

    <?php // --- LÍNEA CORREGIDA Y AÑADIDA --- ?>
    <?php require APPROOT . '/views/inc/footer.php'; ?>
</body>
</html>
