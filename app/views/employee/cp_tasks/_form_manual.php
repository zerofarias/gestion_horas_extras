<?php
require APPROOT . '/views/inc/header.php';
$task = $data['task_type'];
$isExternas = ($task->form_key ?? '') === 'externas';
$action = $isExternas ? URLROOT . '/cpTask/store' : URLROOT . '/cpTask/store';
?>
<div class="emp-page-header">
    <a href="<?php echo URLROOT; ?>/cpTask/index" class="emp-back-btn"><i class="fas fa-arrow-left"></i></a>
    <div>
        <h1 class="emp-page-title"><?php echo htmlspecialchars($task->display_name ?? cp_task_display_name($task->form_key, $task->name)); ?></h1>
        <p class="emp-page-subtitle">Importe manual<?php echo $isExternas ? ' — otra empresa del grupo' : ''; ?></p>
    </div>
</div>
<div class="emp-card emp-form-card">
    <form action="<?php echo $action; ?>" method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form_key" value="<?php echo htmlspecialchars($task->form_key); ?>">
        <div class="emp-form-group">
            <label class="emp-label">Fecha</label>
            <input type="date" name="activity_date" class="emp-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <?php if ($isExternas): ?>
        <div class="emp-form-group">
            <label class="emp-label">Empresa</label>
            <select name="external_company_id" class="emp-input" required>
                <option value="">— Elegir —</option>
                <?php foreach ($data['external_companies'] as $ec): ?>
                <option value="<?php echo (int)$ec->id; ?>"><?php echo htmlspecialchars($ec->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="emp-form-group">
            <label class="emp-label">Descripción de la tarea</label>
            <input type="text" name="task_label" class="emp-input" required>
        </div>
        <?php else: ?>
        <div class="emp-form-group">
            <label class="emp-label">Detalle / mantenimiento</label>
            <input type="text" name="mantenimiento" class="emp-input">
        </div>
        <div class="emp-form-group">
            <label class="emp-label">Lugar</label>
            <input type="text" name="lugar" class="emp-input" list="loc-list">
            <datalist id="loc-list">
                <?php foreach ($data['localities'] as $loc): ?>
                <option value="<?php echo htmlspecialchars($loc->name); ?>">
                <?php endforeach; ?>
            </datalist>
        </div>
        <?php endif; ?>
        <div class="emp-form-group">
            <label class="emp-label">Importe ($)</label>
            <input type="number" name="manual_amount" class="emp-input" step="0.01" min="0.01" required>
        </div>
        <div class="emp-form-group">
            <label class="emp-label">Comentario</label>
            <textarea name="comment" class="emp-input" rows="2"></textarea>
        </div>
        <p class="small text-muted">En feriado no se duplica automáticamente el importe manual: verificá el monto.</p>
        <button type="submit" class="emp-btn-primary w-100">Guardar</button>
    </form>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
