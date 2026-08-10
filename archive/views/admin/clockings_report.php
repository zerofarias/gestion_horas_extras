<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/admin/clockings_report.php (VERSIÓN CORREGIDA Y FINAL)
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Reporte de Fichajes</h4>
    </div>
    <div class="card-body">
        <form method="get" action="<?php echo URLROOT; ?>/admin/clockingsReport" class="mb-4 p-3 bg-light rounded border">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Desde:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($data['filters']['start_date']); ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Hasta:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($data['filters']['end_date']); ?>">
                </div>
                <div class="col-md-4">
                    <label for="user_id" class="form-label">Empleado:</label>
                    <select name="user_id" id="user_id" class="form-select">
                        <option value="">Todos los empleados</option>
                        <?php foreach($data['users'] as $user): ?>
                            <option value="<?php echo $user->id; ?>" <?php echo ($data['filters']['user_id'] == $user->id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user->full_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table id="clockings-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Total Horas</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['clockings'])): ?>
                        <tr>
                            <td colspan="6" class="text-center">
                                <div class="alert alert-info mb-0">No se encontraron marcaciones para los filtros seleccionados.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($data['clockings'] as $clocking): ?>
                        <tr class="<?php echo ($clocking['status'] != 'Completo') ? 'table-warning' : ''; ?>">
                            <td><?php echo htmlspecialchars($clocking['full_name']); ?></td>
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

<?php require APPROOT . '/views/inc/footer.php'; ?>

<script>
$(document).ready(function() {
    if ($('#clockings-table').length && !$.fn.dataTable.isDataTable('#clockings-table')) {
        $('#clockings-table').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
            responsive: true,
            // Se simplifica el ordenamiento para mayor estabilidad
            order: [[ 1, "desc" ], [0, "asc"]],
            dom: '<"row mb-3"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row mt-3"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            buttons: [
                { 
                    extend: 'excelHtml5', 
                    text: '<i class="fas fa-file-excel"></i> Excel', 
                    title: 'Reporte de Fichajes'
                },
                { 
                    extend: 'pdfHtml5', 
                    text: '<i class="fas fa-file-pdf"></i> PDF', 
                    title: 'Reporte de Fichajes'
                },
                { 
                    extend: 'print', 
                    text: '<i class="fas fa-print"></i> Imprimir',
                    title: 'Reporte de Fichajes'
                }
            ]
        });
    }
});
</script>
