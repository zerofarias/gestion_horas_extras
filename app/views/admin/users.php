<?php
// ----------------------------------------------------------------------
// ARCHIVO 2: app/views/admin/users.php (VERSIÓN FINAL Y CORREGIDA)
// Se ha ELIMINADO por completo el bloque <script> del final para evitar la
// doble inicialización de la tabla. La lógica ahora está en main.js
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Gestión de Usuarios</h4>
    <a href="<?php echo URLROOT; ?>/admin/createUser" class="btn btn-primary">
        <i class="fas fa-plus"></i> Crear Nuevo Usuario
    </a>
</div>

<!-- Mensajes de éxito o error -->
<?php if(isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow">
    <div class="card-body">
        <div class="table-responsive">
            <table id="users-table" class="table table-striped table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre Completo</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($data['users'] as $user): ?>
                    <tr class="<?php echo ($user->is_active == 0) ? 'text-muted' : ''; ?>">
                        <td>
                            <img src="<?php echo URLROOT; ?>/uploads/avatars/<?php echo $user->profile_picture; ?>" 
                                 alt="Avatar" class="rounded-circle <?php echo ($user->is_active == 0) ? 'opacity-50' : ''; ?>" width="40" height="40"
                                 onerror="this.onerror=null; this.src='<?php echo URLROOT; ?>/uploads/avatars/default.png';">
                        </td>
                        <td><?php echo htmlspecialchars($user->full_name); ?></td>
                        <td><?php echo htmlspecialchars($user->username); ?></td>
                        <td><span class="badge bg-secondary text-capitalize"><?php echo $user->role; ?></span></td>
                        <td>
                            <?php if($user->is_active == 1): ?>
                                <span class="badge bg-success">Activo</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?php echo URLROOT; ?>/admin/employeeSummary/<?php echo $user->id; ?>" class="btn btn-info btn-sm" title="Ver Ficha Completa"><i class="fas fa-address-card"></i></a>
                            <a href="<?php echo URLROOT; ?>/admin/editUser/<?php echo $user->id; ?>" class="btn btn-warning btn-sm" title="Editar"><i class="fas fa-edit"></i></a>
                            <?php if($user->is_active == 1): ?>
                                <a href="<?php echo URLROOT; ?>/admin/toggleUserStatus/<?php echo $user->id; ?>" class="btn btn-danger btn-sm toggle-status-btn" data-action="desactivar" title="Desactivar">
                                    <i class="fas fa-user-slash"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo URLROOT; ?>/admin/toggleUserStatus/<?php echo $user->id; ?>" class="btn btn-success btn-sm toggle-status-btn" data-action="activar" title="Activar">
                                    <i class="fas fa-user-check"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                        
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>