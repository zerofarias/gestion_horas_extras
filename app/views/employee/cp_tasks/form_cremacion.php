<?php require APPROOT . '/views/inc/header.php';
$task = $data['task_type'];
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
        <input type="hidden" name="form_key" value="cremacion">

        <div class="emp-form-group">
            <label class="emp-label">Fecha de la tarea</label>
            <input type="date" name="activity_date" class="emp-input" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <?php include APPROOT . '/views/employee/cp_tasks/_deceased_fields.php'; ?>
        <div class="emp-form-group">
            <label class="emp-label">Cantidad de féretros</label>
            <input type="number" name="coffin_count" class="emp-input" min="1" max="10" value="1" required>
        </div>
        <div class="emp-form-group">
            <label class="emp-label">Comentario</label>
            <textarea name="comment" class="emp-input" rows="2"></textarea>
        </div>
        <button type="submit" class="emp-btn-primary w-100">Guardar tarea</button>
    </form>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
