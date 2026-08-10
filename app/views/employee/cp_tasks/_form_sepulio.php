<?php require APPROOT . '/views/inc/header.php';
$task = $data['task_type'];
$localityLabel = $data['locality_label'] ?? 'Lugar instalado';
$localityField = $data['locality_field'] ?? 'installed_place';
?>

<div class="emp-page-header">
    <a href="<?php echo URLROOT; ?>/cpTask/index" class="emp-back-btn"><i class="fas fa-arrow-left"></i></a>
    <div>
        <h1 class="emp-page-title"><?php echo htmlspecialchars($task->display_name ?? cp_task_display_name($task->form_key, $task->name)); ?></h1>
    </div>
</div>

<div class="emp-card emp-form-card">
    <form action="<?php echo URLROOT; ?>/cpTask/store" method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form_key" value="<?php echo htmlspecialchars($task->form_key); ?>">

        <div class="emp-form-group">
            <label class="emp-label">Fecha de la tarea</label>
            <input type="date" name="activity_date" class="emp-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <?php include APPROOT . '/views/employee/cp_tasks/_deceased_fields.php'; ?>
        <div class="emp-form-row">
            <div class="emp-form-group">
                <label class="emp-label">Hora inicio</label>
                <input type="time" name="start_time" class="emp-input">
            </div>
            <div class="emp-form-group">
                <label class="emp-label">Hora fin</label>
                <input type="time" name="end_time" class="emp-input">
            </div>
        </div>
        <div class="emp-form-group">
            <label class="emp-label">Retirado en</label>
            <input type="text" name="pickup_place" class="emp-input" list="pickup-list">
            <datalist id="pickup-list">
                <?php foreach ($data['pickup_places'] as $p): ?>
                <option value="<?php echo htmlspecialchars($p->name); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="emp-form-group">
            <label class="emp-label"><?php echo htmlspecialchars($localityLabel); ?></label>
            <input type="text" name="<?php echo htmlspecialchars($localityField); ?>" class="emp-input" list="loc-list" required>
            <datalist id="loc-list">
                <?php foreach ($data['localities'] as $loc): ?>
                <option value="<?php echo htmlspecialchars($loc->name); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <div class="emp-form-group">
            <label class="emp-label">Acompañante</label>
            <select name="companion_user_id" class="emp-input">
                <option value="">— Sin acompañante —</option>
                <?php foreach ($data['colleagues'] as $c): ?>
                <option value="<?php echo (int)$c->id; ?>"><?php echo htmlspecialchars($c->full_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="emp-form-group">
            <label class="emp-label">COVID</label>
            <select name="covid" class="emp-input">
                <option value="No">No</option>
                <option value="Si">Sí</option>
            </select>
        </div>
        <div class="emp-form-group">
            <label class="emp-label">Comentario</label>
            <textarea name="comment" class="emp-input" rows="2"></textarea>
        </div>
        <p class="small text-muted">Si la fecha es feriado de Casa Paviotti, el importe se duplica automáticamente.</p>
        <button type="submit" class="emp-btn-primary w-100">Guardar tarea</button>
    </form>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
