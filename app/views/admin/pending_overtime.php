<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="mb-0">
            <a href="<?php echo URLROOT; ?>/admin/dashboard" class="btn btn-light me-2" title="Volver al Dashboard"><i class="fas fa-arrow-left"></i></a>
            Horas extras pendientes de cierre
        </h4>
        <a href="<?php echo URLROOT; ?>/admin/exportPendingClosure" class="btn btn-success btn-sm" download="resumen_cierre_pendiente.csv">
            <i class="fas fa-file-excel me-1"></i> Descargar Excel
        </a>
    </div>
    <div class="card-body">
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($data['entries'])): ?>
            <p class="text-muted text-center mb-0 py-4">No hay horas extras pendientes de cierre.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table id="pending-overtime-table" class="table table-striped table-bordered" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Empleado</th>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th class="text-center">Feriado</th>
                        <th class="text-center">Hs. 50%</th>
                        <th class="text-center">Hs. 100%</th>
                        <th>Motivo</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['entries'] as $entry): ?>
                    <tr>
                        <td>
                            <a href="<?php echo URLROOT; ?>/admin/employeeDetails/<?php echo (int)$entry->user_id; ?>" class="text-decoration-none fw-semibold">
                                <?php echo htmlspecialchars($entry->full_name); ?>
                            </a>
                        </td>
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
                        <td class="text-center text-nowrap">
                            <a href="<?php echo URLROOT; ?>/admin/editEntry/<?php echo (int)$entry->id; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-sm delete-entry-btn" data-id="<?php echo (int)$entry->id; ?>" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<form method="post" action="<?php echo URLROOT; ?>/admin/deleteEntry/0" id="delete-entry-form" class="d-none">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="entry_id" value="">
    <input type="hidden" name="redirect_to" value="pendingOvertime">
</form>

<?php require APPROOT . '/views/inc/footer.php'; ?>

<?php if (!empty($data['entries'])): ?>
<script>
$(document).ready(function() {
    $('#pending-overtime-table').DataTable({
        language: window.DATATABLES_LANG_ES || { url: '<?php echo URLROOT; ?>/js/datatables-es-ES.json' },
        order: [[1, 'desc']],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', title: 'Horas extras pendientes', className: 'btn-success' },
            { extend: 'pdfHtml5', title: 'Horas extras pendientes', className: 'btn-danger' },
            { extend: 'print', title: 'Horas extras pendientes', className: 'btn-info' }
        ]
    });

    const deleteEntryForm = document.getElementById('delete-entry-form');
    $('#pending-overtime-table').on('click', '.delete-entry-btn', function(e) {
        e.preventDefault();
        const entryId = $(this).data('id');
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¡No podrás revertir esta acción!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, ¡bórralo!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed && deleteEntryForm) {
                deleteEntryForm.action = '<?php echo URLROOT; ?>/admin/deleteEntry/' + entryId;
                deleteEntryForm.querySelector('[name="entry_id"]').value = entryId;
                deleteEntryForm.submit();
            }
        });
    });
});
</script>
<?php endif; ?>
