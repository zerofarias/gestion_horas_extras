<?php require APPROOT . '/views/inc/header.php';
$showOtCol = !empty($data['show_overtime_column']);
$showCpCol = !empty($data['show_cp_extras_column']);
?>

<div class="row">
    <div class="col-md-7">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Empresas registradas</h5></div>
            <div class="card-body">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <?php if ($showOtCol): ?><th>Horas extras</th><?php endif; ?>
                            <?php if ($showCpCol): ?><th>Extras CP</th><?php endif; ?>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['companies'] as $company): ?>
                        <tr>
                            <td class="fw-medium"><?php echo htmlspecialchars($company->name); ?></td>
                            <?php if ($showOtCol): ?>
                            <td>
                                <?php if (function_exists('company_overtime_label')): ?>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(company_overtime_label($company)); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <?php if ($showCpCol): ?>
                            <td>
                                <?php if (function_exists('company_cp_extras_label')): ?>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(company_cp_extras_label($company)); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="text-end">
                                <a href="<?php echo URLROOT; ?>/admin/editCompany/<?php echo (int)$company->id; ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Añadir nueva empresa</h5></div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/createCompany" method="post">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label for="company_name" class="form-label">Nombre de la empresa</label>
                        <input type="text" name="company_name" id="company_name" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Crear empresa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
