<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-10 col-lg-9">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Editar Usuario: <?php echo htmlspecialchars($data['user']->full_name); ?></h5>
            </div>
            <div class="card-body p-4">
                <form action="<?php echo URLROOT; ?>/admin/editUser/<?php echo $data['user']->id; ?>" method="post" enctype="multipart/form-data" autocomplete="off">
                    <?php echo csrf_field(); ?>
                    <h6 class="mb-3 text-muted">Foto de Perfil</h6>
                    <div class="mb-4 d-flex align-items-center gap-4">
                        <img id="avatarPreview"
                             src="<?php echo htmlspecialchars(avatar_url($data['user']->profile_picture ?? '')); ?>"
                             alt="Foto de perfil"
                             class="rounded-circle border bg-light"
                             style="width:90px;height:90px;object-fit:cover;"
                             onerror="this.onerror=null;this.src='<?php echo htmlspecialchars(avatar_default_url(), ENT_QUOTES); ?>';">
                        <div class="flex-grow-1">
                            <label for="profile_picture" class="form-label mb-1">Cambiar foto <small class="text-muted">(JPG, PNG, WEBP · máx. 2 MB)</small></label>
                            <input type="file" name="profile_picture" id="profile_picture" accept="image/*"
                                   class="form-control <?php echo isset($data['errors']['picture']) ? 'is-invalid' : ''; ?>"
                                   onchange="if(this.files[0]){document.getElementById('avatarPreview').src=URL.createObjectURL(this.files[0])}">
                            <?php if(isset($data['errors']['picture'])): ?>
                                <div class="invalid-feedback"><?php echo htmlspecialchars($data['errors']['picture']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h6 class="mb-3 text-muted">Información Principal</h6>
                    <?php
                    $nameSource = isset($data['user']) ? $data['user'] : null;
                    if (!empty($data['first_name']) || !empty($data['last_name'])) {
                        $nameSource = $data;
                    }
                    require APPROOT . '/views/admin/partials/user_name_fields.php';
                    ?>

                    <div class="mb-3">
                        <label class="form-label">Usuario de acceso</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($data['user']->username); ?>" disabled>
                        <small class="text-muted">El nombre de usuario no se modifica desde aquí.</small>
                    </div>

                    <?php
                    $profileExtendedReady = (new User())->isProfileExtendedReady();
                    $profileSource = $data['user'];
                    require APPROOT . '/views/admin/partials/user_profile_fields.php';
                    ?>

                    <hr class="my-3">
                    <h6 class="mb-2 text-muted"><i class="fas fa-building me-1"></i> Empresa</h6>
                    <?php
                    $selectedCompanyId = (int)($data['user']->company_id ?? 0);
                    $currentCompanyLabel = !empty($data['current_company_name'])
                        ? $data['current_company_name']
                        : 'Sin empresa asignada';
                    ?>
                    <p class="small text-muted mb-2">
                        Actual: <strong><?php echo htmlspecialchars($currentCompanyLabel); ?></strong>.
                        Los empleados solo intercambian turnos con compañeros de la misma empresa.
                    </p>
                    <?php if (empty($data['companies'])): ?>
                    <div class="alert alert-warning small mb-3">
                        No hay empresas en el sistema. Ejecutá <code>migration_companies_grupo.sql</code> en MySQL o
                        <a href="<?php echo URLROOT; ?>/admin/companies">creá empresas aquí</a>.
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label for="company_id" class="form-label">Asignar / cambiar empresa <span class="text-danger">*</span></label>
                        <select name="company_id" id="company_id" class="form-select <?php echo isset($data['errors']['company_id']) ? 'is-invalid' : ''; ?>" required>
                            <option value="">— Seleccioná empresa —</option>
                            <?php foreach ($data['companies'] as $co): ?>
                            <option value="<?php echo (int)$co->id; ?>" <?php echo $selectedCompanyId === (int)$co->id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($co->name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($data['errors']['company_id'])): ?>
                        <div class="invalid-feedback d-block"><?php echo htmlspecialchars($data['errors']['company_id']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <?php require APPROOT . '/views/admin/partials/user_area_field.php'; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Rol</label>
                            <select name="role" id="role" class="form-select">
                                <option value="empleado" <?php if($data['user']->role == 'empleado') echo 'selected'; ?>>Empleado</option>
                                <option value="supervisor" <?php if($data['user']->role == 'supervisor') echo 'selected'; ?>>Supervisor (jefe de área)</option>
                                <option value="admin" <?php if($data['user']->role == 'admin') echo 'selected'; ?>>Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Dejar en blanco para no cambiar" autocomplete="new-password">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3 text-muted">Configuración de Horarios</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="hourly_rate" class="form-label">Tarifa por Hora</label>
                            <input type="number" step="0.01" name="hourly_rate" id="hourly_rate" class="form-control" value="<?php echo htmlspecialchars($data['user']->hourly_rate); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="weekly_hour_limit" class="form-label">Límite de Horas Semanales</label>
                            <input type="number" step="0.25" name="weekly_hour_limit" id="weekly_hour_limit" class="form-control" value="<?php echo htmlspecialchars($data['user']->weekly_hour_limit); ?>">
                            <small class="form-text text-muted">Para cálculo de H. Extras (ej. 44).</small>
                        </div>
                    </div>

                    <?php
                    $source = isset($data['user']) ? $data['user'] : $data;
                    if (!empty($data['probation_start_date']) || !empty($data['hire_date'])) {
                        $source = (object)array_merge((array)$source, [
                            'probation_start_date' => $data['probation_start_date'] ?? '',
                            'hire_date' => $data['hire_date'] ?? '',
                            'agreement_id' => $data['agreement_id'] ?? 0,
                        ]);
                    }
                    require APPROOT . '/views/admin/partials/user_employment_fields.php';
                    ?>

                    <hr class="my-4">
                    <h6 class="mb-1 text-muted">IDs de Empleado en Relojes</h6>
                    <p class="text-muted small mb-3">
                        Los IDs se gestionan desde la pantalla de mapeo al consultar la API.
                    </p>
                    <?php if (!empty($data['clock_mappings'])): ?>
                    <ul class="list-group list-group-flush mb-2">
                        <?php foreach ($data['clock_mappings'] as $clockName => $clockId): ?>
                        <li class="list-group-item d-flex align-items-center gap-2 px-0 py-1 border-0">
                            <i class="fas fa-circle-check" style="color:var(--clr-primary);"></i>
                            <strong><?php echo htmlspecialchars($clockName); ?></strong>:
                            <code><?php echo htmlspecialchars($clockId); ?></code>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php else: ?>
                    <p class="text-warning small"><i class="fas fa-exclamation-triangle me-1"></i>Sin IDs asignados.</p>
                    <?php endif; ?>
                    <a href="<?php echo URLROOT; ?>/admin/mapeoApi" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-link me-1"></i> Gestionar mapeos en la página de mapeo
                    </a>

                    <?php if (!empty($data['user']) && (new User())->isPlexOperatorReady()): ?>
                    <hr class="my-4">
                    <h6 class="mb-3 text-muted">Ecofarma — operador API</h6>
                    <div class="mb-3">
                        <label for="plex_operator_name" class="form-label">Nombre operador (resumen comisiones)</label>
                        <input type="text" name="plex_operator_name" id="plex_operator_name" class="form-control"
                               value="<?php echo htmlspecialchars($data['user']->plex_operator_name ?? ''); ?>"
                               placeholder="Como figura en la API de Ecofarma">
                        <div class="form-text">Debe coincidir con el nombre del operador en facturación ACOS.</div>
                    </div>
                    <?php endif; ?>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
