<?php
// ----------------------------------------------------------------------
// ARCHIVO 1: app/views/admin/closure_details.php (VERSIÓN CORREGIDA)
// Se ha ELIMINADO por completo el bloque <script> del final para evitar
// la doble inicialización de la tabla.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0">
            <a href="<?php echo URLROOT; ?>/admin/history" class="btn btn-light me-2" title="Volver al Historial"><i class="fas fa-arrow-left"></i></a>
            Detalle del Cierre #<?php echo $data['closure_id']; ?>
        </h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="details-table" class="table table-striped table-bordered" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th class="text-center">Feriado</th>
                        <th class="text-center">Hs. 50%</th>
                        <th class="text-center">Hs. 100%</th>
                        <th>Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['entries'] as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry->full_name); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($entry->entry_date)); ?></td>
                        <td><?php echo date('H:i', strtotime($entry->start_time)) . ' - ' . date('H:i', strtotime($entry->end_time)); ?></td>
                        <td class="text-center">
                            <?php if ($entry->is_holiday): ?>
                                <span class="badge bg-danger">Sí</span>
                            <?php else: ?>
                                <span class="text-muted">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center <?php echo ($entry->hours_50 > 0) ? 'bg-success-subtle' : ''; ?>"><?php echo number_format($entry->hours_50, 2); ?></td>
                        <td class="text-center <?php echo ($entry->hours_100 > 0) ? 'bg-warning-subtle' : ''; ?>"><?php echo number_format($entry->hours_100, 2); ?></td>
                        <td><?php echo htmlspecialchars($entry->reason); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>