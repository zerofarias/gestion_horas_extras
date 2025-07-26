<?php
// ----------------------------------------------------------------------
// ARCHIVO 6: app/views/admin/companies.php (NUEVO ARCHIVO)
// Esta es la nueva página para gestionar las empresas.
// Debes CREAR este archivo en la ruta app/views/admin/.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; ?>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Empresas Registradas</h5></div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr><th>Nombre de la Empresa</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($data['companies'] as $company): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($company->name); ?></td>
                                <td><a href="#" class="btn btn-sm btn-warning">Editar</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Añadir Nueva Empresa</h5></div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/createCompany" method="post">
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Nombre de la Empresa</label>
                        <input type="text" name="company_name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Crear Empresa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>