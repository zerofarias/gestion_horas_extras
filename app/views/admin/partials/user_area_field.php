<?php
$areaReady = (new User())->isAreaReady();
if (!$areaReady) {
    return;
}

$selectedArea = 0;
if (isset($data['area_id']) && (int)$data['area_id'] > 0) {
    $selectedArea = (int)$data['area_id'];
} elseif (isset($data['user']->area_id) && (int)$data['user']->area_id > 0) {
    $selectedArea = (int)$data['user']->area_id;
}

$companyId = 0;
if (!empty($data['company_id']) && (int)$data['company_id'] > 0) {
    $companyId = (int)$data['company_id'];
} elseif (isset($data['user']->company_id) && (int)$data['user']->company_id > 0) {
    $companyId = (int)$data['user']->company_id;
} elseif (!empty($data['default_company_id'])) {
    $companyId = (int)$data['default_company_id'];
} else {
    $companyId = (int)($_SESSION['user_company_id'] ?? 0);
}

$areaModel = new Area();
$globalAreas = $areaModel->isGlobalAreasReady();
$areasByCompany = [];

$mapArea = function ($a) {
    $global = $a->company_id === null || (int)$a->company_id === 0;
    return [
        'id' => (int)$a->id,
        'name' => $a->name,
        'scope' => $global ? 'global' : 'company',
    ];
};

if (!empty($data['companies'])) {
    foreach ($data['companies'] as $co) {
        $cid = (int)$co->id;
        $areasByCompany[$cid] = array_map($mapArea, $areaModel->getAvailableForCompany($cid, true));
    }
} elseif ($companyId > 0) {
    $areasByCompany[$companyId] = array_map($mapArea, $areaModel->getAvailableForCompany($companyId, true));
}

$areas = $companyId > 0 && isset($areasByCompany[$companyId]) ? $areasByCompany[$companyId] : [];
$hasCompanySelect = true;
?>
<div class="mb-3" id="user-area-field-wrap" data-selected-area="<?php echo $selectedArea; ?>">
    <label for="area_id" class="form-label">Área / departamento</label>
    <select name="area_id" id="area_id" class="form-select <?php echo isset($data['errors']['area_id']) ? 'is-invalid' : ''; ?>">
        <option value="">— Sin área —</option>
        <?php foreach ($areas as $ar): ?>
        <option value="<?php echo (int)$ar['id']; ?>" <?php echo $selectedArea === (int)$ar['id'] ? 'selected' : ''; ?>>
            <?php
            echo htmlspecialchars($ar['name']);
            if ($globalAreas && ($ar['scope'] ?? '') === 'global') {
                echo ' (todas las empresas)';
            }
            ?>
        </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($data['errors']['area_id'])): ?>
    <div class="invalid-feedback d-block"><?php echo htmlspecialchars($data['errors']['area_id']); ?></div>
    <?php endif; ?>
    <p id="user-area-empty-hint" class="small text-warning mb-1 <?php echo ($companyId > 0 && empty($areas)) ? '' : 'd-none'; ?>">
        No hay áreas para esta empresa.
        <a href="<?php echo URLROOT; ?>/trainingAdmin/areas">Gestionar áreas</a>
    </p>
    <small class="text-muted">
        <?php if ($globalAreas): ?>
        Se muestran áreas globales y las propias de la empresa del empleado.
        <?php endif; ?>
        <a href="<?php echo URLROOT; ?>/trainingAdmin/areas">Gestionar áreas</a>
    </small>
</div>
<?php if (!empty($areasByCompany)): ?>
<script>window.AREAS_BY_COMPANY = <?php echo json_encode($areasByCompany, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;</script>
<script src="<?php echo URLROOT; ?>/js/user-company-areas.js" defer></script>
<?php endif; ?>
