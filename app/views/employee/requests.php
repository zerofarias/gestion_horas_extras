<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/views/employee/requests.php (ACTUALIZADO)
// Se añade el script al final para mostrar la alerta de SweetAlert
// si existe un mensaje de éxito en la sesión.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Nueva Solicitud</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/request/create" method="post">
                    <div class="mb-3">
                        <label for="request_type_id" class="form-label">Tipo de Solicitud</label>
                        <select name="request_type_id" class="form-select" required>
                            <?php foreach($data['requestTypes'] as $type): ?>
                                <option value="<?php echo $type->id; ?>"><?php echo $type->name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Fecha Desde</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">Fecha Hasta (Opcional)</label>
                            <input type="date" name="end_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo / Comentario</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enviar Solicitud</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7 mb-4">
        <div class="card shadow h-100">
            <div class="card-header"><h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>Historial de Mis Solicitudes</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employee-requests-table" class="table table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Desde</th>
                                <th>Hasta</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['requests'] as $request): ?>
                                <tr>
                                    <td>
                                        <span class="badge" style="background-color: <?php echo $request->color; ?>; color: white; font-size: 0.9em;">
                                            <?php echo htmlspecialchars($request->type_name); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($request->start_date)); ?></td>
                                    <td><?php echo $request->end_date ? date('d/m/Y', strtotime($request->end_date)) : '-'; ?></td>
                                    <td>
                                        <?php 
                                            $statusClass = 'bg-secondary'; // Pendiente
                                            if ($request->status == 'Aprobado') $statusClass = 'bg-success';
                                            if ($request->status == 'Rechazado') $statusClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $request->status; ?></span>
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

<?php require APPROOT . '/views/inc/footer.php'; ?>

<!-- INICIO DE CAMBIO: Script para mostrar SweetAlert -->
<?php if(isset($_SESSION['flash_success'])): ?>
<script>
    // Espera a que todo el documento (incluyendo librerías) esté cargado
    $(document).ready(function() {
        Swal.fire({
            title: '¡Solicitud Enviada!',
            text: '<?php echo $_SESSION['flash_success']; ?>',
            icon: 'success',
            timer: 2500,
            showConfirmButton: false
        });
    });
</script>
<?php 
    // Se limpia la variable de sesión para que no se muestre de nuevo.
    unset($_SESSION['flash_success']); 
?>
<?php endif; ?>