<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="d-flex align-items-center gap-2 mb-4">
    <h2 class="page-title mb-0">Tareas operativas</h2>
    <a href="<?php echo URLROOT; ?>/trainingAdmin/courses" class="btn btn-outline-secondary btn-sm ms-auto">Cursos</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow">
            <div class="card-header">Nueva tarea</div>
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveTask">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="form-label">Título</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Fecha límite</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Estrellas al completar</label>
                        <input type="number" name="stars_on_complete" class="form-control" value="0" min="0" max="5">
                    </div>
                    <div class="form-check mb-2">
                        <input type="checkbox" name="is_active" class="form-check-input" id="ta" checked>
                        <label for="ta" class="form-check-label">Activa</label>
                    </div>
                    <hr>
                    <p class="small text-muted mb-1">Asignar a:</p>
                    <div class="form-check">
                        <input type="checkbox" name="assign_company" value="1" class="form-check-input" id="tac" checked>
                        <label for="tac">Toda la empresa</label>
                    </div>
                    <?php foreach ($data['areas'] as $ar): ?>
                    <div class="form-check">
                        <input type="checkbox" name="area_ids[]" value="<?php echo (int)$ar->id; ?>" id="ta<?php echo $ar->id; ?>">
                        <label for="ta<?php echo $ar->id; ?>"><?php echo htmlspecialchars($ar->name); ?></label>
                    </div>
                    <?php endforeach; ?>
                    <select name="user_ids[]" class="form-select form-select-sm mt-2" multiple size="4">
                        <?php foreach ($data['users'] as $u): ?>
                        <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($u->full_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary w-100 mt-3">Guardar tarea</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card shadow">
            <div class="card-header">Tareas creadas</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Título</th><th>Vence</th><th>Estrellas</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['tasks'] as $t): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($t->title); ?></td>
                        <td><?php echo $t->due_date ? htmlspecialchars($t->due_date) : '—'; ?></td>
                        <td><?php echo (int)$t->stars_on_complete; ?></td>
                        <td><?php echo $t->is_active ? 'Activa' : 'Inactiva'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
