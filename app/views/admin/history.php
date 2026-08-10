<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Cierres de Horas Extras</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID de Cierre</th>
                        <th>Fecha de Cierre</th>
                        <th>Admin</th>
                        <th class="text-end">Total Horas</th>
                        <th class="text-end">Hs. 50%</th>
                        <th class="text-end">Hs. 100%</th>
                        <th class="text-center">Nº Empleados</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // CORRECCIÓN: Verificamos si la variable 'closures' existe y no está vacía
                    if (isset($data['closures']) && !empty($data['closures'])): 
                        foreach($data['closures'] as $closure): 
                    ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($closure->id); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($closure->closure_date)); ?></td>
                            <td><?php echo htmlspecialchars($closure->admin_username); ?></td>
                            <td class="text-end fw-bold"><?php echo number_format($closure->total_hours, 2); ?></td>
                            <td class="text-end"><?php echo number_format($closure->total_hours_50, 2); ?></td>
                            <td class="text-end"><?php echo number_format($closure->total_hours_100, 2); ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($closure->employee_count); ?></td>
                            <td class="text-center">
                                <a href="<?php echo URLROOT; ?>/admin/closureDetails/<?php echo $closure->id; ?>" class="btn btn-sm btn-info" title="Ver Detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo URLROOT; ?>/admin/exportClosure/<?php echo $closure->id; ?>" class="btn btn-sm btn-success" title="Descargar Excel">
                                    <i class="fas fa-file-excel"></i>
                                </a>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    else: // Si no hay cierres, mostramos un mensaje
                    ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">No hay cierres archivados en el historial.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
