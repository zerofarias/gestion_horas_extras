<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="fas fa-lock fa-2x text-primary mb-2"></i>
                    <h1 class="h5 mb-1">Configuración del sistema</h1>
                    <p class="text-muted small mb-0">Ingresá la clave de configuración para continuar.</p>
                </div>
                <form method="post" action="<?php echo URLROOT; ?>/systemConfig/unlock">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label" for="config_pin">Clave</label>
                        <input type="password" name="config_pin" id="config_pin" class="form-control" required autofocus autocomplete="off">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Desbloquear</button>
                </form>
                <p class="text-muted small mt-3 mb-0 text-center">
                    Solo administradores. La sesión dura 30 minutos.
                </p>
                <div class="text-center mt-3">
                    <a href="<?php echo URLROOT; ?>/admin/dashboard" class="small text-muted">Volver al panel</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
