<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0">Generador de Reportes de Horas</h5>
    </div>
    <div class="card-body">
        <form action="<?php echo URLROOT; ?>/admin/reports" method="get">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Fecha de Inicio</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($data['filters']['start_date']); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Fecha de Fin</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($data['filters']['end_date']); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="user_id" class="form-label">Empleado</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="all">Todos los Empleados</option>
                        <?php foreach($data['users'] as $user): ?>
                            <option value="<?php echo $user->id; ?>" <?php echo ($data['filters']['user_id'] == $user->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user->full_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Generar</button>
                </div>
            </div>
        </form>
    </div>

    <?php if(!empty($data['report_data'])): ?>
    <div class="card-footer d-flex justify-content-end">
        <a href="<?php echo URLROOT; ?>/admin/exportReportCsv?<?php echo http_build_query($data['filters']); ?>" class="btn btn-success">
            <i class="fas fa-file-csv me-2"></i>Exportar a CSV
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped mb-0">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Horas Regulares</th>
                    <th>Horas Extras Planificadas</th>
                    <th>Total de Horas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data['report_data'] as $userId => $report): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($report['full_name']); ?></td>
                        <td><?php echo number_format($report['regular_hours'], 2); ?> hs</td>
                        <td><?php echo number_format($report['overtime_hours'], 2); ?> hs</td>
                        <td><strong><?php echo number_format($report['total_hours'], 2); ?> hs</strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
