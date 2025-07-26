<?php
// ----------------------------------------------------------------------
// ARCHIVO 5: app/views/admin/edit_entry.php (NUEVO ARCHIVO)
// Este es el formulario para editar una entrada de horas existente.
// Debes CREAR este archivo en la ruta indicada.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; 
$entry = $data['entry'];
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <a href="<?php echo URLROOT; ?>/admin/employeeDetails/<?php echo $entry->user_id; ?>" class="btn btn-light me-2" title="Volver"><i class="fas fa-arrow-left"></i></a>
                    Editar Entrada de Horas
                </h4>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/editEntry/<?php echo $entry->id; ?>" method="post">
                    <input type="hidden" name="user_id" value="<?php echo $entry->user_id; ?>">
                    <div class="mb-3">
                        <label for="date" class="form-label">Fecha:</label>
                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($entry->entry_date); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Hora Inicio:</label>
                            <input type="time" name="start_time" class="form-control" value="<?php echo htmlspecialchars($entry->start_time); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">Hora Fin:</label>
                            <input type="time" name="end_time" class="form-control" value="<?php echo htmlspecialchars($entry->end_time); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="is_holiday" class="form-label">¿Es Feriado?</label>
                        <select name="is_holiday" class="form-select">
                            <option value="0" <?php echo ($entry->is_holiday == 0) ? 'selected' : ''; ?>>No</option>
                            <option value="1" <?php echo ($entry->is_holiday == 1) ? 'selected' : ''; ?>>Sí</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo:</label>
                        <textarea name="reason" class="form-control" required><?php echo htmlspecialchars($entry->reason); ?></textarea>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-success w-100">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>