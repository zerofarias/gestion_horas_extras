<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/views/admin/sync.php (VERSIÓN VERIFICADA)
// Nos aseguramos de que el formulario apunte a /admin/runSync
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0"><i class="fas fa-sync-alt me-2"></i>Sincronización con Reloj Biométrico</h4>
    </div>
    <div class="card-body">
        <p class="card-text">
            Selecciona un rango de fechas para descargar y verificar las marcaciones desde el reloj HIK-VISION.
        </p>
        
        <!-- Mensajes de éxito o error -->
        <?php if(isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>

        <form action="<?php echo URLROOT; ?>/admin/runSync" method="post" class="mt-4">
            <div class="row align-items-end p-3 bg-light rounded border">
                <div class="col-md-4">
                    <label for="start_date" class="form-label"><strong>Fecha Desde:</strong></label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('-1 week')); ?>" required>
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label"><strong>Fecha Hasta:</strong></label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-2"></i>Buscar Marcaciones
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>