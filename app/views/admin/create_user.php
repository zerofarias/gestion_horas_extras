<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/views/admin/create_user.php (CORREGIDO PARA PHP 5.4)
// Se ha reemplazado el operador '??' por 'isset()' para compatibilidad.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">
                    <a href="<?php echo URLROOT; ?>/admin/users" class="btn btn-light me-2" title="Volver a la lista"><i class="fas fa-arrow-left"></i></a>
                    Crear Nuevo Usuario
                </h4>
            </div>
            <div class="card-body">
                <!-- El formulario necesita 'enctype="multipart/form-data"' para poder subir archivos -->
                <form action="<?php echo URLROOT; ?>/admin/createUser" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo isset($data['full_name']) ? htmlspecialchars($data['full_name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nombre de Usuario <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control <?php echo (isset($data['errors']['username'])) ? 'is-invalid' : ''; ?>" value="<?php echo isset($data['username']) ? htmlspecialchars($data['username']) : ''; ?>" required>
                            <div class="invalid-feedback"><?php echo isset($data['errors']['username']) ? $data['errors']['username'] : ''; ?></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control <?php echo (isset($data['errors']['password'])) ? 'is-invalid' : ''; ?>" required>
                            <div class="invalid-feedback"><?php echo isset($data['errors']['password']) ? $data['errors']['password'] : ''; ?></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control <?php echo (isset($data['errors']['confirm_password'])) ? 'is-invalid' : ''; ?>" required>
                            <div class="invalid-feedback"><?php echo isset($data['errors']['confirm_password']) ? $data['errors']['confirm_password'] : ''; ?></div>
                        </div>
                    </div>
                    <div class="row">
                         <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <select name="role" class="form-select">
                                <option value="empleado" selected>Empleado</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="profile_picture" class="form-label">Foto de Perfil (Opcional)</label>
                            <input type="file" name="profile_picture" class="form-control <?php echo (isset($data['errors']['picture'])) ? 'is-invalid' : ''; ?>">
                            <div class="invalid-feedback"><?php echo isset($data['errors']['picture']) ? $data['errors']['picture'] : ''; ?></div>
                        </div>
                    </div>
                    
                    <?php if (isset($data['errors']['general'])): ?>
                        <div class="alert alert-danger"><?php echo $data['errors']['general']; ?></div>
                    <?php endif; ?>

                    <hr>
                    <button type="submit" class="btn btn-success w-100">Guardar Usuario</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
