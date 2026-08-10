<?php /** @var $c,$cid,$data */ ?>
<div class="card shadow">
    <div class="card-header">¿Quién debe realizar este curso?</div>
    <div class="card-body">
        <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveAssignments/<?php echo $cid; ?>">
            <?php echo csrf_field(); ?>
            <div class="form-check mb-3">
                <input type="checkbox" name="assign_company" value="1" class="form-check-input" id="ac" checked>
                <label for="ac" class="fw-semibold">Toda la empresa activa</label>
            </div>
            <p class="small text-muted">Y/o restringir por área:</p>
            <?php foreach ($data['areas'] as $ar): ?>
            <div class="form-check">
                <input type="checkbox" name="area_ids[]" value="<?php echo (int)$ar->id; ?>" id="a<?php echo $ar->id; ?>">
                <label for="a<?php echo $ar->id; ?>"><?php echo htmlspecialchars($ar->name); ?></label>
            </div>
            <?php endforeach; ?>
            <label class="form-label mt-3">Usuarios específicos (Ctrl+clic)</label>
            <select name="user_ids[]" class="form-select" multiple size="8">
                <?php foreach ($data['users'] as $u): ?>
                <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($u->full_name); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary mt-4">Guardar asignaciones</button>
        </form>
    </div>
</div>
