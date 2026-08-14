<?php require APPROOT . '/views/inc/header.php';

$company = $data['company'];
$usesCp = !empty($data['uses_cp_tasks']);
$showCol = !empty($data['show_overtime_column']);
$showOt = (int)($company->show_overtime ?? 1) === 1;
$showCpCol = !empty($data['show_cp_extras_column']);
$showCp = (int)($company->show_cp_extras ?? 1) === 1;
$location = $data['location'] ?? null;
$locationReady = !empty($data['location_ready']);
$branches = $data['branches'] ?? [];
$branchesReady = !empty($data['branches_ready']);
$savedLocalities = array_map(function ($branch) { return $branch->locality ?? ''; }, $branches);
$localityOptions = array_values(array_filter(array_unique(array_merge(['Villa Maria', 'San Francisco'], $savedLocalities, [$location->locality ?? '']))));
$provinceOptions = ['C??rdoba'];

function company_location_options($options, $selected, $placeholder) {
    echo '<option value="">' . htmlspecialchars($placeholder) . '</option>';
    foreach ($options as $option) {
        echo '<option value="' . htmlspecialchars($option) . '"' . ((string)$selected === (string)$option ? ' selected' : '') . '>'
            . htmlspecialchars($option) . '</option>';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div class="card shadow">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Editar empresa</h5>
                <span class="badge bg-light text-dark border">#<?php echo (int)$company->id; ?></span>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/admin/editCompany/<?php echo (int)$company->id; ?>">
                    <?php echo csrf_field(); ?>

                    <div class="mb-4">
                        <label class="form-label" for="company_name">Nombre</label>
                        <input type="text" name="company_name" id="company_name" class="form-control" required value="<?php echo htmlspecialchars($company->name); ?>">
                    </div>

                    <?php if ($locationReady): ?>
                    <section class="border rounded-3 p-3 mb-4">
                        <h6 class="mb-1">Ubicación principal</h6>
                        <p class="text-muted small mb-3">Se usa como ubicación general y para reglas geográficas cuando no corresponda una sucursal específica.</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="locality">Localidad / ciudad</label>
                                <select name="locality" id="locality" class="form-select">
                                    <?php company_location_options($localityOptions, $location->locality ?? '', 'Seleccioná una localidad'); ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="province">Provincia</label>
                                <select name="province" id="province" class="form-select">
                                    <?php company_location_options($provinceOptions, $location->province ?? '', 'Seleccioná una provincia'); ?>
                                </select>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>

                    <?php if ($branchesReady): ?>
                    <section class="border rounded-3 p-3 mb-4">
                        <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                            <div><h6 class="mb-1">Sucursales y localidades</h6><p class="text-muted small mb-0">Agregá las sedes operativas. Al quitar una fila se desactiva, para conservar el historial.</p></div>
                            <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" id="addBranch">Agregar sucursal</button>
                        </div>
                        <div id="branchesList" class="vstack gap-2">
                            <?php foreach ($branches as $index => $branch): ?>
                            <div class="border rounded-3 p-3 bg-light branch-row">
                                <input type="hidden" name="branches[<?php echo (int)$index; ?>][id]" value="<?php echo (int)$branch->id; ?>">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4"><label class="form-label small">Sucursal</label><input type="text" class="form-control" name="branches[<?php echo (int)$index; ?>][name]" value="<?php echo htmlspecialchars($branch->name); ?>" placeholder="Ej. Ecofarma Central"></div>
                                    <div class="col-md-3"><label class="form-label small">Localidad</label><select class="form-select" name="branches[<?php echo (int)$index; ?>][locality]"><?php company_location_options($localityOptions, $branch->locality, 'Seleccioná'); ?></select></div>
                                    <div class="col-md-3"><label class="form-label small">Provincia</label><select class="form-select" name="branches[<?php echo (int)$index; ?>][province]"><?php company_location_options($provinceOptions, $branch->province, 'Seleccioná'); ?></select></div>
                                    <div class="col-md-2 d-flex gap-2 align-items-center pb-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="branches[<?php echo (int)$index; ?>][is_active]" value="1" <?php echo !empty($branch->is_active) ? 'checked' : ''; ?>><label class="form-check-label small">Activa</label></div><button type="button" class="btn btn-sm btn-outline-danger remove-branch" aria-label="Quitar sucursal">×</button></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <template id="branchTemplate"><div class="border rounded-3 p-3 bg-light branch-row"><input type="hidden" name="branches[__INDEX__][id]" value="0"><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label small">Sucursal</label><input type="text" class="form-control" name="branches[__INDEX__][name]" placeholder="Ej. Ecofarma Central"></div><div class="col-md-3"><label class="form-label small">Localidad</label><select class="form-select" name="branches[__INDEX__][locality]"><?php company_location_options($localityOptions, '', 'Seleccioná'); ?></select></div><div class="col-md-3"><label class="form-label small">Provincia</label><select class="form-select" name="branches[__INDEX__][province]"><?php company_location_options($provinceOptions, 'C??rdoba', 'Seleccioná'); ?></select></div><div class="col-md-2 d-flex gap-2 align-items-center pb-1"><div class="form-check"><input class="form-check-input" type="checkbox" name="branches[__INDEX__][is_active]" value="1" checked><label class="form-check-label small">Activa</label></div><button type="button" class="btn btn-sm btn-outline-danger remove-branch" aria-label="Quitar sucursal">×</button></div></div></div></template>
                    </section>
                    <?php endif; ?>

                    <?php if ($showCol): ?><div class="mb-4"><?php if ($usesCp): ?><p class="small text-muted mb-2">Esta empresa usa <strong>extras por tarea</strong> (Casa Paviotti). El módulo de horas extras 50%/100% no aplica.</p><?php if ($showCpCol): ?><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_cp_extras" id="show_cp_extras" value="1" <?php echo $showCp ? 'checked' : ''; ?>><label class="form-check-label" for="show_cp_extras">Mostrar extras Casa Paviotti (portal y admin)</label></div><?php endif; ?><?php else: ?><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_overtime" id="show_overtime" value="1" <?php echo $showOt ? 'checked' : ''; ?>><label class="form-check-label" for="show_overtime">Mostrar horas extras (50%/100%)</label></div><p class="small text-muted mb-0">Si lo desactivás, empleados, supervisores y el dashboard de esta empresa no verán el módulo de horas extras clásicas.</p><?php endif; ?></div><?php endif; ?>
                    <button type="submit" class="btn btn-primary">Guardar</button><a href="<?php echo URLROOT; ?>/admin/companies" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php if ($branchesReady): ?><script>(() => { const list = document.getElementById('branchesList'); const template = document.getElementById('branchTemplate'); let nextIndex = <?php echo count($branches); ?>; document.getElementById('addBranch').addEventListener('click', () => list.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++))); list.addEventListener('click', (event) => { const button = event.target.closest('.remove-branch'); if (button) button.closest('.branch-row').remove(); }); })();</script><?php endif; ?>
<?php require APPROOT . '/views/inc/footer.php'; ?>
