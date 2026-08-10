<?php require APPROOT . '/views/inc/header.php'; ?>

<?php
// --- CÁLCULO DE TOTALES PARA EL RESUMEN ---
$total_hours = 0;
$total_hours_50 = 0;
$total_hours_100 = 0;
if (isset($data['entries']) && !empty($data['entries'])) {
    foreach($data['entries'] as $entry) {
        $total_hours += $entry->total_hours;
        $total_hours_50 += $entry->hours_50;
        $total_hours_100 += $entry->hours_100;
    }
}
?>

<div class="card shadow">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <?php if (isset($data['closure']) && is_object($data['closure'])): ?>
                <h5 class="mb-0">Detalle del Cierre #<?php echo htmlspecialchars($data['closure']->id); ?></h5>
                <small class="text-muted">
                    Realizado el <?php echo date('d/m/Y H:i', strtotime($data['closure']->closure_date)); ?> 
                    por <?php echo htmlspecialchars($data['closure']->admin_username); ?>
                </small>
            <?php else: ?>
                <h5 class="mb-0">Detalle del Cierre</h5>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center flex-shrink-0 gap-2">
            <a href="<?php echo URLROOT; ?>/admin/history" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Volver al Historial</a>
            <?php if (isset($data['closure']) && is_object($data['closure'])): ?>
            <a href="<?php echo URLROOT; ?>/admin/exportClosure/<?php echo (int)$data['closure']->id; ?>" class="btn btn-success" download="cierre_horas_<?php echo (int)$data['closure']->id; ?>.csv">
                <i class="fas fa-file-excel me-2"></i>Descargar Excel
            </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="closure-details-table" class="table table-striped table-hover" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th class="text-end">Hs. 50%</th>
                        <th class="text-end">Hs. 100%</th>
                        <th class="text-end">Total Horas</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($data['entries']) && !empty($data['entries'])): ?>
                        <?php foreach($data['entries'] as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry->full_name); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($entry->entry_date)); ?></td>
                                <td><?php echo date('H:i', strtotime($entry->start_time)); ?> - <?php echo date('H:i', strtotime($entry->end_time)); ?></td>
                                <td class="text-end"><?php echo number_format($entry->hours_50, 2); ?></td>
                                <td class="text-end"><?php echo number_format($entry->hours_100, 2); ?></td>
                                <td class="text-end fw-bold"><?php echo number_format($entry->total_hours, 2); ?></td>
                                <td><?php echo htmlspecialchars($entry->reason); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">No se encontraron registros de horas para este cierre.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-light">
        <div class="row text-center">
            <div class="col-4">
                <h6 class="text-muted mb-0">Total Hs. 50%</h6>
                <p class="fs-5 fw-bold mb-0"><?php echo number_format($total_hours_50, 2); ?></p>
            </div>
            <div class="col-4">
                <h6 class="text-muted mb-0">Total Hs. 100%</h6>
                <p class="fs-5 fw-bold mb-0"><?php echo number_format($total_hours_100, 2); ?></p>
            </div>
            <div class="col-4">
                <h6 class="text-muted mb-0">Total General</h6>
                <p class="fs-5 fw-bold text-primary mb-0"><?php echo number_format($total_hours, 2); ?></p>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#closure-details-table').DataTable({
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', title: 'Cierre horas extras #<?php echo isset($data['closure']->id) ? (int)$data['closure']->id : ''; ?>', className: 'btn btn-success btn-sm' },
            { extend: 'print', className: 'btn btn-outline-secondary btn-sm' }
        ],
        language: {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sSearch":         "Buscar:",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            }
        },
        responsive: true,
        "order": [[ 1, "desc" ]]
    });
});
</script>
