<?php require APPROOT . '/views/inc/header.php'; $task = $data['task_type']; ?>
<div class="emp-page-header">
    <a href="<?php echo URLROOT; ?>/cpTask/index" class="emp-back-btn"><i class="fas fa-arrow-left"></i></a>
    <div><h1 class="emp-page-title"><?php echo htmlspecialchars($task->display_name ?? cp_task_display_name($task->form_key, $task->name)); ?></h1></div>
</div>
<div class="emp-card emp-form-card">
    <form action="<?php echo URLROOT; ?>/cpTask/store" method="post">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="form_key" value="ambulancia">
        <div class="emp-form-group"><label class="emp-label">Fecha</label><input type="date" name="activity_date" class="emp-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required></div>
        <div class="emp-form-group"><label class="emp-label">Paciente</label><input type="text" name="paciente" class="emp-input" required></div>
        <div class="emp-form-group"><label class="emp-label">Hora</label><input type="time" name="start_time" class="emp-input"></div>
        <div class="emp-form-group"><label class="emp-label">Realizado en</label>
            <input type="text" name="locality_name" class="emp-input" list="loc-list" required>
            <datalist id="loc-list"><?php foreach ($data['localities'] as $loc): ?><option value="<?php echo htmlspecialchars($loc->name); ?>"><?php endforeach; ?></datalist>
        </div>
        <div class="emp-form-group"><label class="emp-label">Comentario</label><textarea name="comment" class="emp-input" rows="2"></textarea></div>
        <button type="submit" class="emp-btn-primary w-100">Guardar</button>
    </form>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
