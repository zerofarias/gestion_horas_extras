<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<?php
$globalAreas = !empty($data['global_areas']);
$companies = $data['companies'] ?? [];
$areaShowOtCol = !empty($data['area_show_overtime_column']);
$areaShowCpCol = !empty($data['area_show_cp_extras_column']);
?>
<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <h2 class="page-title mb-0">Áreas / departamentos</h2>
    <span class="text-muted small">
        <?php if ($globalAreas): ?>
        Definí si cada área aplica a <strong>todas las empresas</strong> o solo a una.
        <?php else: ?>
        Por empresa — ejecutá <code>migration_areas_global.sql</code> para elegir alcance.
        <?php endif; ?>
    </span>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/courses" class="btn btn-outline-secondary btn-sm ms-auto">Cursos</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow">
            <div class="card-header">Nueva área</div>
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveArea" data-area-form>
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ej. Administración, Sistemas">
                    </div>
                    <?php if ($globalAreas): ?>
                    <div class="mb-3">
                        <label class="form-label d-block">Alcance</label>
                        <div class="form-check">
                            <input class="form-check-input area-scope-all" type="radio" name="area_scope" id="create_scope_all" value="all" checked>
                            <label class="form-check-label" for="create_scope_all">Todas las empresas del grupo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input area-scope-company" type="radio" name="area_scope" id="create_scope_company" value="company">
                            <label class="form-check-label" for="create_scope_company">Solo una empresa</label>
                        </div>
                    </div>
                    <div class="mb-3 area-company-wrap d-none">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" class="form-select area-company-select">
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($companies as $co): ?>
                            <option value="<?php echo (int)$co->id; ?>"><?php echo htmlspecialchars($co->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="area_scope" value="company">
                    <input type="hidden" name="company_id" value="<?php echo (int)($_SESSION['user_company_id'] ?? 0); ?>">
                    <p class="small text-muted mb-0">Se creará para la empresa activa en la barra superior.</p>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary w-100">Crear área</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Áreas registradas</span>
                <span class="badge bg-secondary"><?php echo count($data['areas'] ?? []); ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($data['areas'])): ?>
                <p class="p-4 text-muted mb-0">No hay áreas. Creá la primera con el formulario de la izquierda.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Alcance</th>
                                <?php if ($areaShowOtCol): ?>
                                <th>Horas extras</th>
                                <?php endif; ?>
                                <?php if ($areaShowCpCol): ?>
                                <th>Extras CP</th>
                                <?php endif; ?>
                                <th>Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($data['areas'] as $a): ?>
                        <?php
                        $isGlobal = $a->company_id === null || (int)$a->company_id === 0;
                        $scopeKey = $isGlobal ? 'all' : 'company';
                        ?>
                        <tr class="<?php echo $a->is_active ? '' : 'table-secondary'; ?>">
                            <td class="fw-medium"><?php echo htmlspecialchars($a->name); ?></td>
                            <td>
                                <?php if ($isGlobal): ?>
                                <span class="badge bg-primary">Todas las empresas</span>
                                <?php else: ?>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars(Area::scopeLabel($a)); ?></span>
                                <?php endif; ?>
                            </td>
                            <?php if ($areaShowOtCol): ?>
                            <td class="small text-muted"><?php echo htmlspecialchars(overtime_area_show_overtime_label($a)); ?></td>
                            <?php endif; ?>
                            <?php if ($areaShowCpCol): ?>
                            <td class="small text-muted"><?php echo htmlspecialchars(cp_area_show_cp_label($a)); ?></td>
                            <?php endif; ?>
                            <td>
                                <?php echo $a->is_active
                                    ? '<span class="badge bg-success">Activa</span>'
                                    : '<span class="badge bg-secondary">Inactiva</span>'; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="modal" data-bs-target="#editAreaModal"
                                    data-edit-area
                                    data-id="<?php echo (int)$a->id; ?>"
                                    data-name="<?php echo htmlspecialchars($a->name, ENT_QUOTES); ?>"
                                    data-scope="<?php echo $scopeKey; ?>"
                                    data-company-id="<?php echo $isGlobal ? '' : (int)$a->company_id; ?>"
                                    data-active="<?php echo $a->is_active ? '1' : '0'; ?>"
                                    data-show-overtime="<?php echo ($a->show_overtime === null || $a->show_overtime === '') ? 'inherit' : ((int)$a->show_overtime === 1 ? '1' : '0'); ?>"
                                    data-show-cp-extras="<?php echo ($a->show_cp_extras === null || $a->show_cp_extras === '') ? 'inherit' : ((int)$a->show_cp_extras === 1 ? '1' : '0'); ?>">
                                    Editar
                                </button>
                                <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveArea" class="d-inline">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="toggle_only" value="1">
                                    <input type="hidden" name="id" value="<?php echo (int)$a->id; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $a->is_active ? '0' : '1'; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">
                                        <?php echo $a->is_active ? 'Desactivar' : 'Activar'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editAreaModal" tabindex="-1" aria-labelledby="editAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveArea" data-area-form>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editAreaModalLabel">Editar área</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <?php if ($globalAreas): ?>
                    <div class="mb-3">
                        <label class="form-label d-block">Alcance</label>
                        <div class="form-check">
                            <input class="form-check-input area-scope-all" type="radio" name="area_scope" id="edit_scope_all" value="all">
                            <label class="form-check-label" for="edit_scope_all">Todas las empresas</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input area-scope-company" type="radio" name="area_scope" id="edit_scope_company" value="company">
                            <label class="form-check-label" for="edit_scope_company">Solo una empresa</label>
                        </div>
                    </div>
                    <div class="mb-3 area-company-wrap d-none">
                        <label class="form-label">Empresa</label>
                        <select name="company_id" class="form-select area-company-select">
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($companies as $co): ?>
                            <option value="<?php echo (int)$co->id; ?>"><?php echo htmlspecialchars($co->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($areaShowOtCol): ?>
                    <div class="mb-3">
                        <label class="form-label">Horas extras en el portal</label>
                        <select name="show_overtime" class="form-select" id="edit_show_overtime">
                            <option value="inherit">Heredar empresa</option>
                            <option value="1">Visible para el área</option>
                            <option value="0">Oculto para el área</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php if ($areaShowCpCol): ?>
                    <div class="mb-3">
                        <label class="form-label">Extras Casa Paviotti</label>
                        <select name="show_cp_extras" class="form-select" id="edit_show_cp_extras">
                            <option value="inherit">Heredar empresa</option>
                            <option value="1">Visible para el área</option>
                            <option value="0">Oculto para el área</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="edit_is_active">
                        <label class="form-check-label" for="edit_is_active">Área activa</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?php echo URLROOT; ?>/js/areas-admin.js"></script>
<?php require APPROOT . '/views/inc/footer.php'; ?>
