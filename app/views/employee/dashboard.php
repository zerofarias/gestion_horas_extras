<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/employee/dashboard.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; 
?>

<div class="row">
    <!-- Columna del Formulario de Carga -->
    <div class="col-lg-5 mb-4">
        <div class="card shadow h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Cargar Horas Extras</h5>
            </div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/employee/add" method="post">
                    <div class="mb-3">
                        <label for="date" class="form-label">Fecha:</label>
                        <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_time" class="form-label">Hora Inicio:</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_time" class="form-label">Hora Fin:</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="is_holiday" class="form-label">¿Es Feriado?</label>
                        <select name="is_holiday" class="form-select">
                            <option value="0" selected>No</option>
                            <option value="1">Sí</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Motivo:</label>
                        <textarea name="reason" class="form-control" placeholder="Ej: Reemplazo de compañero" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Horas</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Columna de la Tabla de Horas Pendientes -->
    <div class="col-lg-7 mb-4">
        <div class="card shadow h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Mis Horas Cargadas (Pendientes)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="employee-table" class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Horario</th>
                                <th>H. 50%</th>
                                <th>H. 100%</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($data['entries'] as $entry): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($entry->entry_date)); ?></td>
                                <td><?php echo date('H:i', strtotime($entry->start_time)) . ' - ' . date('H:i', strtotime($entry->end_time)); ?></td>
                                <td><?php echo number_format($entry->hours_50, 2); ?></td>
                                <td><?php echo number_format($entry->hours_100, 2); ?></td>
                                <td><?php echo htmlspecialchars($entry->reason); ?></td>
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

<!-- Script para mostrar la alerta de éxito o error -->
<?php if(isset($_SESSION['flash_success'])): ?>
<script>
    // Espera a que todo el documento (incluyendo librerías) esté cargado.
    $(document).ready(function() {
        // Muestra la alerta nativa del navegador.
        Swal.fire({
  title: "CARGA CON EXITO!",
  text: "",
  icon: "success"
});
    });
</script>
<?php 
    // Se limpia la variable de sesión para que no se muestre de nuevo.
    unset($_SESSION['flash_success']); 
?>
<?php endif; ?>

<?php if(isset($_SESSION['flash_error'])): ?>
<script>
    $(document).ready(function() {
        // Muestra una alerta moderna para los errores.
        Swal.fire({
            title: 'Error',
            text: '<?php echo $_SESSION['flash_error']; ?>',
            icon: 'error',
            confirmButtonText: 'Entendido'
        });
    });
</script>
<?php 
    unset($_SESSION['flash_error']); 
?>
<?php endif; ?>
