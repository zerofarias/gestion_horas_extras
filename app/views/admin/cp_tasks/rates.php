<?php
require APPROOT . '/views/inc/header.php';
$labels = [
    'armar_s' => 'Armar sepelio',
    'realizar_s' => 'Realizar sepelio',
    'cremacion' => 'Cremación',
    'cremacion_adicional' => 'Cremación adic.',
    'localidades' => 'Adic. localidades',
    'covid' => 'COVID',
    'cambio_metalica' => 'Cambio metálica',
    'ambu_localidades' => 'Ambulancia local',
    'ambu_vm' => 'Ambulancia VM',
    'viajes_activa' => 'Viajes activa/km',
    'viajes_pasiva' => 'Viajes pasiva/km',
    'tanato' => 'Tanatopraxia',
    'gestion_tramites' => 'Gestión / trámite',
];
$cols = $data['rate_columns'] ?? CpTask::rateColumnNames();
?>

<h1 class="page-title mb-2">Tarifas extras — Casa Paviotti</h1>
<p class="page-subtitle mb-4">
    <a href="<?php echo URLROOT; ?>/cpTaskAdmin/catalogs">Catálogos (localidades, retiros, empresas externas)</a>
    · <a href="<?php echo URLROOT; ?>/cpTaskAdmin/pending">Pendientes</a>
</p>

<?php foreach ($data['employees'] as $emp): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5 class="card-title mb-3"><?php echo htmlspecialchars($emp->full_name); ?></h5>
        <form method="post" action="<?php echo URLROOT; ?>/cpTaskAdmin/rates">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="user_id" value="<?php echo (int)$emp->id; ?>">
            <div class="row g-2">
                <?php foreach ($cols as $c): ?>
                <div class="col-md-4 col-lg-3">
                    <label class="form-label small"><?php echo htmlspecialchars($labels[$c] ?? $c); ?></label>
                    <input type="number" step="0.01" name="<?php echo htmlspecialchars($c); ?>" class="form-control form-control-sm"
                           value="<?php echo htmlspecialchars($emp->$c ?? 0); ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="btn btn-sm btn-primary mt-2">Guardar</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php require APPROOT . '/views/inc/footer.php'; ?>
