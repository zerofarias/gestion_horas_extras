<?php require APPROOT . '/views/inc/header.php'; $task = $data['task_type']; ?>
<div class="emp-page-header">
    <a href="<?php echo URLROOT; ?>/cpTask/index" class="emp-back-btn"><i class="fas fa-arrow-left"></i></a>
    <div><h1 class="emp-page-title"><?php echo htmlspecialchars($task->name); ?></h1></div>
</div>
<div class="emp-card emp-form-card">
    <form action="<?php echo URLROOT; ?>/cpTask/store" method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form_key" value="<?php echo htmlspecialchars($task->form_key); ?>">
        <div class="emp-form-group"><label class="emp-label">Fecha</label><input type="date" name="activity_date" class="emp-input" value="<?php echo date('Y-m-d'); ?>" required></div>
        <?php $thisBlock = true; include APPROOT . '/views/employee/cp_tasks/_deceased_fields.php'; ?>
        <div class="emp-form-group"><label class="emp-label">Lugar</label>
            <input type="text" name="installed_place" class="emp-input" list="loc-list">
            <datalist id="loc-list"><?php foreach ($data['localities'] as $loc): ?><option value="<?php echo htmlspecialchars($loc->name); ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="emp-form-group"><label class="emp-label">Acompañante</label>
            <select name="companion_user_id" class="emp-input"><option value="">—</option>
            <?php foreach ($data['colleagues'] as $c): ?><option value="<?php echo (int)$c->id; ?>"><?php echo htmlspecialchars($c->full_name); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="emp-form-group"><label class="emp-label">Comentario</label><textarea name="comment" class="emp-input" rows="2"></textarea></div>
        <button type="submit" class="emp-btn-primary w-100">Guardar</button>
    </form>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
