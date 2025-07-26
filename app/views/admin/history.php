<?php
// ----------------------------------------------------------------------
// ARCHIVO 3: app/views/admin/history.php (VERSIÓN CORREGIDA)
// Se ha ELIMINADO por completo el bloque <script> del final para evitar la
// doble inicialización de la tabla.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fas fa-archive me-2"></i>Historial de Cierres</h4>
    </div>
    <div class="card-body">
        <p class="card-text text-muted">A continuación se listan todos los lotes de cierre que se han realizado en el sistema.</p>
        <div class="table-responsive">
            <table id="history-table" class="table table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar-alt me-1"></i> Fecha de Cierre</th>
                        <th><i class="fas fa-user-shield me-1"></i> Cerrado Por</th>
                        <th class="text-center text-success"><i class="fas fa-sun me-1"></i> Hs. 50%</th>
                        <th class="text-center text-warning"><i class="fas fa-moon me-1"></i> Hs. 100%</th>
                        <th class="text-center"><i class="fas fa-stopwatch me-1"></i> Total Horas</th>
                        <th class="text-center"><i class="fas fa-cogs me-1"></i> Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['history'] as $closure): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($closure->closure_date)); ?></td>
                        <td><?php echo htmlspecialchars($closure->admin_username); ?></td>
                        <td class="text-center fw-bold text-success"><?php echo number_format($closure->total_hours_50, 2); ?></td>
                        <td class="text-center fw-bold text-warning"><?php echo number_format($closure->total_hours_100, 2); ?></td>
                        <td class="text-center fw-bold"><?php echo number_format($closure->total_hours, 2); ?></td>
                        <td class="text-center">
                            <a href="<?php echo URLROOT; ?>/admin/closureDetails/<?php echo $closure->id; ?>" class="btn btn-primary btn-sm" title="Ver Detalle">
                                <i class="fas fa-eye"></i> Ver Detalle
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>