<?php require APPROOT . '/views/inc/header.php'; ?>

<form action="<?php echo URLROOT; ?>/admin/editUser/<?php echo $data['id']; ?>" method="post" enctype="multipart/form-data">
    <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <a href="<?php echo URLROOT; ?>/admin/users" class="btn btn-light me-2" title="Volver"><i class="fas fa-arrow-left"></i></a>
                Ficha del Empleado: <?php echo htmlspecialchars($data['user']->full_name); ?>
            </h4>
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-2"></i>Guardar Cambios</button>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Columna de la foto -->
                <div class="col-lg-3 text-center pt-3">
                    <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo isset($data['user']->profile_picture) ? $data['user']->profile_picture : 'default.png'; ?>" 
                         alt="Avatar" class="rounded-circle img-fluid mb-3" style="width: 150px; height: 150px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='<?php echo URLROOT; ?>/uploads/avatars/default.png';">
                    <label for="profile_picture" class="form-label">Cambiar Foto de Perfil</label>
                    <input type="file" name="profile_picture" id="profile_picture" class="form-control form-control-sm">
                </div>

                <!-- Columna de los datos con pestañas -->
                <div class="col-lg-9">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab">Personales</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="laboral-tab" data-bs-toggle="tab" data-bs-target="#laboral" type="button" role="tab">Laborales</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="acceso-tab" data-bs-toggle="tab" data-bs-target="#acceso" type="button" role="tab">Acceso</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab">Documentos</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 border border-top-0" id="myTabContent">
                        <!-- Pestaña Datos Personales -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre Completo</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo isset($data['user']->full_name) ? htmlspecialchars($data['user']->full_name) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" name="birth_date" class="form-control" value="<?php echo isset($data['user']->birth_date) ? $data['user']->birth_date : ''; ?>">
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Dirección</label>
                                    <input type="text" name="address" class="form-control" value="<?php echo isset($data['user']->address) ? htmlspecialchars($data['user']->address) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="text" name="phone" class="form-control" value="<?php echo isset($data['user']->phone) ? htmlspecialchars($data['user']->phone) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nº de Hijos</label>
                                    <input type="number" name="children_count" class="form-control" value="<?php echo isset($data['user']->children_count) ? $data['user']->children_count : '0'; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre Contacto de Emergencia</label>
                                    <input type="text" name="emergency_contact_name" class="form-control" value="<?php echo isset($data['user']->emergency_contact_name) ? htmlspecialchars($data['user']->emergency_contact_name) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Teléfono Contacto de Emergencia</label>
                                    <input type="text" name="emergency_contact_phone" class="form-control" value="<?php echo isset($data['user']->emergency_contact_phone) ? htmlspecialchars($data['user']->emergency_contact_phone) : ''; ?>">
                                </div>
                            </div>
                        </div>
                        <!-- Pestaña Datos Laborales -->
                        <div class="tab-pane fade" id="laboral" role="tabpanel">
                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?php echo isset($data['user']->email) ? htmlspecialchars($data['user']->email) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fecha de Ingreso</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo isset($data['user']->start_date) ? $data['user']->start_date : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Empresa</label>
                                    <select name="company_id" class="form-select">
                                        <?php foreach($data['companies'] as $company): ?>
                                            <option value="<?php echo $company->id; ?>" <?php echo (isset($data['user']->company_id) && $company->id == $data['user']->company_id) ? 'selected' : ''; ?>>
                                                <?php echo $company->name; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Obra Social</label>
                                    <input type="text" name="health_insurance" class="form-control" value="<?php echo isset($data['user']->health_insurance) ? htmlspecialchars($data['user']->health_insurance) : ''; ?>">
                                </div>
                             </div>
                        </div>
                        <!-- Pestaña Acceso -->
                        <div class="tab-pane fade" id="acceso" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nombre de Usuario</label>
                                    <input type="text" name="username" class="form-control" value="<?php echo isset($data['user']->username) ? htmlspecialchars($data['user']->username) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rol</label>
                                    <select name="role" class="form-select">
                                        <option value="empleado" <?php echo (isset($data['user']->role) && $data['user']->role == 'empleado') ? 'selected' : ''; ?>>Empleado</option>
                                        <option value="admin" <?php echo (isset($data['user']->role) && $data['user']->role == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Nueva Contraseña</label>
                                    <input type="password" name="password" class="form-control" placeholder="Dejar en blanco para no cambiar">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirmar Nueva Contraseña</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ID de Reloj</label>
                                    <input type="text" name="clock_id" class="form-control" value="<?php echo isset($data['user']->clock_id) ? htmlspecialchars($data['user']->clock_id) : ''; ?>">
                                </div>
                                <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Rol</label>
                                    <select name="role" id="role" class="form-select">
                                        <option value="empleado" <?php if($data['user']->role == 'empleado') echo 'selected'; ?>>Empleado</option>
                                        <option value="admin" <?php if($data['user']->role == 'admin') echo 'selected'; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="weekly_hour_limit" class="form-label">Límite de Horas Semanales</label>
                                    <input type="number" step="0.25" name="weekly_hour_limit" id="weekly_hour_limit" class="form-control" value="<?php echo htmlspecialchars($data['user']->weekly_hour_limit); ?>">
                                    <small class="form-text text-muted">Horas a partir de las cuales se consideran extras (ej. 44).</small>
                                </div>
                            </div>
                        </div>
                        <!-- Pestaña Documentos -->
                        <div class="tab-pane fade" id="documentos" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Foto DNI (Frente)</label>
                                    <input type="file" name="dni_photo_front" class="form-control">
                                    <?php if(isset($data['user']->dni_photo_front) && !empty($data['user']->dni_photo_front)): ?>
                                        <a href="<?php echo URLROOT . '/uploads/documents/' . $data['user']->dni_photo_front; ?>" target="_blank">Ver DNI Frente actual</a>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Foto DNI (Dorso)</label>
                                    <input type="file" name="dni_photo_back" class="form-control">
                                     <?php if(isset($data['user']->dni_photo_back) && !empty($data['user']->dni_photo_back)): ?>
                                        <a href="<?php echo URLROOT . '/uploads/documents/' . $data['user']->dni_photo_back; ?>" target="_blank">Ver DNI Dorso actual</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>