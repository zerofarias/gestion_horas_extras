<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/admin/dashboard.php (VERSIÓN COMPLETA Y CORREGIDA)
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; 
?>

<!-- Banner de Bienvenida -->
<div class="welcome-banner shadow-sm">
    <h2 class="mb-0">¡Bienvenido, <?php echo htmlspecialchars(explode(' ', $_SESSION['user_full_name'])[0]); ?>!</h2>
    <p class="lead mb-0">Resumen general del estado de las horas extras.</p>
</div>

<!-- Tarjetas de Estadísticas Principales -->
<div class="row">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card bg-gradient-primary shadow-lg h-100">
            <div class="card-body">
                <div class="icon"><i class="fas fa-business-time"></i></div>
                <h6 class="card-title text-uppercase">Horas al 50%</h6>
                <p class="display-5 fw-bold"><?php echo number_format($data['stats']['totals_by_type']['total_50'], 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card bg-gradient-warning shadow-lg h-100">
            <div class="card-body">
                <div class="icon"><i class="fas fa-moon"></i></div>
                <h6 class="card-title text-uppercase">Horas al 100%</h6>
                <p class="display-5 fw-bold"><?php echo number_format($data['stats']['totals_by_type']['total_100'], 2); ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card bg-gradient-success shadow-lg h-100">
            <div class="card-body">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h6 class="card-title text-uppercase">Empleados con Horas</h6>
                <p class="display-5 fw-bold"><?php echo $data['stats']['employee_count']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card stat-card bg-gradient-danger shadow-lg h-100 action-card">
             <div class="card-body d-flex flex-column justify-content-center">
                <h5 class="card-title">Acción Rápida</h5>
                <form action="<?php echo URLROOT; ?>/admin/createClosure" method="post" id="closureForm">
                    <button type="submit" class="btn btn-light w-100 fw-bold mt-2 btn-pulse"><i class="fas fa-lock me-2"></i>Realizar Cierre</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Mensaje de éxito -->
<?php if(!empty($data['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $data['success_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Fila de Estado del Personal -->
<div class="row">
    <!-- Tarjeta de Cumpleaños -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-birthday-cake me-2"></i>Cumpleaños de Hoy</h5>
            </div>
            <div class="card-body">
                <?php if(empty($data['todaysBirthdays'])): ?>
                    <p class="text-muted">No hay cumpleaños hoy.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach($data['todaysBirthdays'] as $user): ?>
                            <li class="d-flex align-items-center mb-2 employee-list-item">
                                <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo $user->profile_picture; ?>" class="rounded-circle me-3" width="40" height="40" onerror="this.onerror=null; this.src='<?php echo URLROOT; ?>/uploads/avatars/default.png';">
                                <strong><?php echo htmlspecialchars($user->full_name); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Vacaciones -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-plane-departure me-2"></i>Personal de Vacaciones</h5>
            </div>
            <div class="card-body">
                <?php if(empty($data['onVacation'])): ?>
                    <p class="text-muted">Nadie está de vacaciones hoy.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach($data['onVacation'] as $request): ?>
                            <li class="d-flex align-items-center mb-2 employee-list-item">
                                <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo $request->profile_picture; ?>" class="rounded-circle me-3" width="40" height="40" onerror="this.onerror=null; this.src='<?php echo URLROOT; ?>/uploads/avatars/default.png';">
                                <strong><?php echo htmlspecialchars($request->full_name); ?></strong>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tarjeta de Licencias -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Personal con Licencia</h5>
            </div>
            <div class="card-body">
                <?php if(empty($data['onLeave'])): ?>
                    <p class="text-muted">Nadie tiene licencia hoy.</p>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach($data['onLeave'] as $request): ?>
                            <li class="d-flex align-items-center mb-2 employee-list-item">
                                <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo $request->profile_picture; ?>" class="rounded-circle me-3" width="40" height="40" onerror="this.onerror=null; this.src='<?php echo URLROOT; ?>/uploads/avatars/default.png';">
                                <div>
                                    <strong><?php echo htmlspecialchars($request->full_name); ?></strong>
                                    <br>
                                    <small class="text-muted">(<?php echo $request->type_name; ?>)</small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Resumen por Empleado -->
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card shadow">
            <div class="card-header"><i class="fas fa-user-clock me-1"></i>Resumen de Horas Pendientes por Empleado</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="summary-employee-table" class="table table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Empleado</th>
                                <th class="text-center">Total Hs. 50%</th>
                                <th class="text-center">Total Hs. 100%</th>
                                <th class="text-center">Total General</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['employee_summary'] as $userId => $summary): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($summary['full_name']); ?></td>
                                    <td class="text-center text-success fw-bold"><?php echo number_format($summary['hours_50'], 2); ?></td>
                                    <td class="text-center text-warning fw-bold"><?php echo number_format($summary['hours_100'], 2); ?></td>
                                    <td class="text-center fw-bold"><?php echo number_format($summary['hours_50'] + $summary['hours_100'], 2); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo URLROOT; ?>/admin/employeeDetails/<?php echo $userId; ?>" class="btn btn-info btn-sm" title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </a>
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

<!-- Fila de Gráficos Principales -->
<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card shadow h-100">
            <div class="card-header"><i class="fas fa-chart-line me-1"></i>Tendencia de Horas Extras (Últimos 7 días)</div>
            <div class="card-body" style="min-height: 300px;"><canvas id="overtimeTrendChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card shadow h-100">
            <div class="card-header"><i class="fas fa-chart-pie me-1"></i>Distribución de Horas Pendientes</div>
            <div class="card-body" style="min-height: 300px;"><canvas id="overtimeSplitChart"></canvas></div>
        </div>
    </div>
</div>

<!-- Fila de Gráficos Adicionales -->
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header"><i class="fas fa-trophy me-1"></i>Top 5 Empleados con Más Horas Pendientes</div>
            <div class="card-body" style="min-height: 300px;"><canvas id="topEmployeesChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow h-100">
            <div class="card-header"><i class="fas fa-calendar-week me-1"></i>Distribución de Horas por Día de la Semana</div>
            <div class="card-body" style="min-height: 300px;"><canvas id="hoursByDayChart"></canvas></div>
        </div>
    </div>
</div>

<<!-- INICIO DE CORRECCIÓN: Divs ocultos con fallbacks más seguros -->
<div id="closureSummaryData" data-summary='<?php echo isset($data['employee_summary']) ? json_encode(array_values($data['employee_summary'])) : '[]'; ?>' style="display: none;"></div>
<div id="pieChartData" data-chart='<?php echo isset($data['pie_chart_data']) ? $data['pie_chart_data'] : '[]'; ?>'></div>
<div id="lineChartData" data-chart='<?php echo isset($data['line_chart_data']) ? $data['line_chart_data'] : '{}'; ?>'></div>
<div id="topEmployeesChartData" data-chart='<?php echo isset($data['top_employees_chart_data']) ? $data['top_employees_chart_data'] : '{}'; ?>'></div>
<div id="hoursByDayChartData" data-chart='<?php echo isset($data['hours_by_day_chart_data']) ? $data['hours_by_day_chart_data'] : '{}'; ?>'></div>
<!-- FIN DE CORRECCIÓN -->
<?php require APPROOT . '/views/inc/footer.php'; ?>
