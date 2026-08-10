<?php require APPROOT . '/views/inc/header.php';

$company = $data['company'];

$usesCp = !empty($data['uses_cp_tasks']);

$showCol = !empty($data['show_overtime_column']);

$showOt = (int)($company->show_overtime ?? 1) === 1;
$showCpCol = !empty($data['show_cp_extras_column']);
$showCp = (int)($company->show_cp_extras ?? 1) === 1;

?>

<div class="row justify-content-center">

    <div class="col-md-7">

        <div class="card shadow">

            <div class="card-header"><h5 class="mb-0">Editar empresa</h5></div>

            <div class="card-body">

                <form method="post" action="<?php echo URLROOT; ?>/admin/editCompany/<?php echo (int)$company->id; ?>">

                    <?php echo csrf_field(); ?>

                    <div class="mb-3">

                        <label class="form-label">Nombre</label>

                        <input type="text" name="company_name" class="form-control" required

                               value="<?php echo htmlspecialchars($company->name); ?>">

                    </div>

                    <?php if ($showCol): ?>

                    <div class="mb-3">

                        <?php if ($usesCp): ?>

                        <p class="small text-muted mb-2">

                            Esta empresa usa <strong>extras por tarea</strong> (Casa Paviotti). El módulo de horas extras 50%/100% no aplica.

                        </p>
                        <?php if ($showCpCol): ?>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="show_cp_extras" id="show_cp_extras" value="1"
                                <?php echo $showCp ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="show_cp_extras">Mostrar extras Casa Paviotti (portal y admin)</label>
                        </div>
                        <?php endif; ?>

                        <?php else: ?>

                        <div class="form-check form-switch">

                            <input class="form-check-input" type="checkbox" name="show_overtime" id="show_overtime" value="1"

                                <?php echo $showOt ? 'checked' : ''; ?>>

                            <label class="form-check-label" for="show_overtime">Mostrar horas extras (50%/100%)</label>

                        </div>

                        <p class="small text-muted mb-0">

                            Si lo desactivás, empleados, supervisores y el dashboard de esta empresa no verán el módulo de horas extras clásicas.

                        </p>

                        <?php endif; ?>

                    </div>

                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Guardar</button>

                    <a href="<?php echo URLROOT; ?>/admin/companies" class="btn btn-secondary">Cancelar</a>

                </form>

            </div>

        </div>

    </div>

</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>

