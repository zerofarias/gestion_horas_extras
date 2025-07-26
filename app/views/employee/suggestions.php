<?php
// ----------------------------------------------------------------------
// ARCHIVO 6: app/views/employee/suggestions.php (NUEVO ARCHIVO)
// Esta es la nueva página para que los empleados envíen sugerencias.
// Debes CREAR este archivo en la ruta app/views/employee/.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Buzón de Sugerencias Anónimas</h5>
            </div>
            <div class="card-body">
                <p class="card-text text-muted">
                    Este es un espacio seguro para que compartas tus ideas, sugerencias o inquietudes de forma <strong>100% anónima</strong>.
                    Tu nombre no será registrado.
                </p>
                <form action="<?php echo URLROOT; ?>/suggestion/submit" method="post">
                    <div class="mb-3">
                        <label for="suggestion_text" class="form-label">Tu Sugerencia:</label>
                        <textarea name="suggestion_text" class="form-control" rows="5" placeholder="Escribe aquí tu idea..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enviar Sugerencia Anónima</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>

<!-- Script para mostrar la alerta de éxito con SweetAlert -->
<?php if(isset($_SESSION['flash_success'])): ?>
<script>
    $(document).ready(function() {
        Swal.fire({
            title: '¡Enviado!',
            text: '<?php echo $_SESSION['flash_success']; ?>',
            icon: 'success',
            timer: 3000,
            showConfirmButton: false
        });
    });
</script>
<?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>