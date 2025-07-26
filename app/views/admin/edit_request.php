<?php
// ----------------------------------------------------------------------
// ARCHIVO 4: app/views/admin/edit_request.php (NUEVO ARCHIVO)
// Este es el formulario para editar una solicitud existente.
// Debes CREAR este archivo.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; 
$request = $data['request'];
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <a href="<?php echo URLROOT; ?>/admin/requests" class="btn btn-light me-2" title="Volver"><i class="fas fa-arrow-left"></i></a>
                    Editar Solicitud #<?php echo $request->id; ?>
                </h4>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/editRequest/<?php echo $request->id; ?>" method="post">
                    <div class="mb-3">
                        <label>Empleado:</label>
                        <p class="form-control-plaintext"><strong><?php /* Necesitaríamos pasar el nombre del empleado aquí */ ?></strong></p>
                    </div>
                    <div class="mb-3">
                        <label for="request_type_id" class="form-label">Tipo de Solicitud</label>
                        <select name="request_type_id" class="form-select" required>
                            <?php foreach($data['requestTypes'] as $type): ?>
                                <option value="<?php echo $type->id; ?>" <?php echo ($type->id == $request->request_type_id) ? 'selected' : ''; ?>>
                                    <?php echo $type->name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Fecha Desde</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $request->start_date; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">Fecha Hasta (Opcional)</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $request->end_date; ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo / Comentario</label>
                        <textarea name="reason" class="form-control" rows="3" required><?php echo htmlspecialchars($request->reason); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select name="status" class="form-select">
                            <option value="Pendiente" <?php echo ($request->status == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Aprobado" <?php echo ($request->status == 'Aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="Rechazado" <?php echo ($request->status == 'Rechazado') ? 'selected' : ''; ?>>Rechazado</option>
                        </select>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-success w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>