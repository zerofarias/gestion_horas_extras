<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/views/admin/employee_details.php (ACTUALIZADO)
// Se añade una columna de "Acciones" con los botones de Editar y Borrar.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0">
            <a href="<?php echo URLROOT; ?>/admin/dashboard" class="btn btn-light me-2" title="Volver al Dashboard"><i class="fas fa-arrow-left"></i></a>
            Detalle de Horas Pendientes de: <strong><?php echo htmlspecialchars($data['user']->full_name); ?></strong>
        </h4>
    </div>
    <div class="card-body">
        <?php if(isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="table-responsive">
            <table id="employee-details-table" class="table table-striped table-bordered" style="width:100%">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Horario</th>
                        <th class="text-center">Feriado</th>
                        <th class="text-center">Hs. 50%</th>
                        <th class="text-center">Hs. 100%</th>
                        <th>Motivo</th>
                        <th class="text-center">Acciones</th> <!-- NUEVA COLUMNA -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['entries'] as $entry): ?>
                    <tr>
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
                        <!-- INICIO DE CAMBIO: Botones de Acción -->
                        <td class="text-center">
                            <a href="<?php echo URLROOT; ?>/admin/editEntry/<?php echo $entry->id; ?>" class="btn btn-warning btn-sm" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-sm delete-btn" data-id="<?php echo $entry->id; ?>" data-user-id="<?php echo $entry->user_id; ?>" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                        <!-- FIN DE CAMBIO -->
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>

<script>
$(document).ready(function() {
    // ... (configuración de DataTable existente) ...
    const employeeName = $('h4 strong').text();
    const exportTitle = 'Horas Pendientes - ' + employeeName;
    $('#employee-details-table').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        order: [[ 0, "desc" ]],
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', title: exportTitle, className: 'btn-success' },
            { extend: 'pdfHtml5', title: exportTitle, className: 'btn-danger' },
            { extend: 'print', title: exportTitle, className: 'btn-info' }
        ]
    });

    // --- INICIO DE CAMBIO: Lógica para el botón de eliminar con SweetAlert ---
    $('#employee-details-table').on('click', '.delete-btn', function(e) {
        e.preventDefault();
        const entryId = $(this).data('id');
        const userId = $(this).data('user-id');
        
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¡No podrás revertir esta acción!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, ¡bórralo!',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Si el usuario confirma, redirige a la URL de eliminación
                window.location.href = `<?php echo URLROOT; ?>/admin/deleteEntry/${entryId}/${userId}`;
            }
        });
    });
    // --- FIN DE CAMBIO ---
});
</script>