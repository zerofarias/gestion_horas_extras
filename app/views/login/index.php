<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/login/index.php (VERSIÓN CORREGIDA)
// Este archivo solo debe contener el HTML para el formulario de login.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; 
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="card shadow-lg p-4" style="width: 100%; max-width: 420px;">
        <div class="card-body text-center">
            
            <i class="fas fa-user-clock fa-3x text-primary mb-3"></i>
            <h2 class="card-title mb-3">Control de Horas</h2>
            <p class="text-muted mb-4">Ingresa con el formato: <strong>usuario+contraseña</strong></p>
            
            <!-- El formulario apunta al método 'process' del LoginController -->
            <form action="<?php echo URLROOT; ?>/login/process" method="post">
                <div class="form-floating mb-3">
                    <input type="password" name="login_data" class="form-control" id="login_data" placeholder=" " required>
                    <label for="login_data">usuario+contraseña</label>
                </div>

                <!-- Muestra un mensaje de error si las credenciales son incorrectas -->
                <?php if(isset($data['error']) && !empty($data['error'])): ?>
                    <div class="alert alert-danger py-2">
                        <?php echo $data['error']; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary w-100 btn-lg">Ingresar</button>
            </form>

        </div>
    </div>
</div>

<?php 
// Incluimos el pie de página común.
require APPROOT . '/views/inc/footer.php'; 
?>
