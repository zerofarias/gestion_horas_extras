<?php /** @var $c,$cid,$data */ ?>
<div class="card shadow">
    <div class="card-header">Datos del curso</div>
    <div class="card-body">
        <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/courseEdit/<?php echo (int)$cid; ?>">
            <?php echo csrf_field(); ?>
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Título</label>
                    <input type="text" name="title" class="form-control" required value="<?php echo $c ? htmlspecialchars($c->title) : ''; ?>">
                </div>
                <div>
                    <label class="form-label">Área</label>
                    <select name="area_id" class="form-select">
                        <option value="">Toda la empresa</option>
                        <?php foreach ($data['areas'] as $ar): ?>
                        <option value="<?php echo (int)$ar->id; ?>" <?php echo $c && (int)$c->area_id === (int)$ar->id ? 'selected' : ''; ?>><?php echo htmlspecialchars($ar->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="De qué trata el curso, a quién está dirigido…"><?php echo $c ? htmlspecialchars($c->description) : ''; ?></textarea>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estrellas al aprobar</label>
                    <input type="number" name="stars_on_complete" class="form-control" min="1" max="50" value="<?php echo $c ? (int)$c->stars_on_complete : 5; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Bonus 1° en terminar</label>
                    <input type="number" name="first_finisher_bonus" class="form-control" min="0" max="50" value="<?php echo $c ? (int)($c->first_finisher_bonus ?? 2) : 2; ?>">
                    <span class="small text-muted d-block">Estrellas extra al primer empleado que aprueba</span>
                </div>
                <div class="col-md-3">
                    <label class="form-label">% aprobación quiz</label>
                    <input type="number" name="passing_score" class="form-control" min="50" max="100" value="<?php echo $c ? (int)$c->passing_score : 70; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Intentos quiz</label>
                    <input type="number" name="max_quiz_attempts" class="form-control" min="1" max="10" value="<?php echo $c ? (int)$c->max_quiz_attempts : 3; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Minutos estimados</label>
                    <input type="number" name="estimated_minutes" class="form-control" value="<?php echo $c ? (int)$c->estimated_minutes : 60; ?>">
                </div>
                <div class="col-12">
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="require_quiz" class="form-check-input" id="rq" <?php echo !$c || $c->require_quiz ? 'checked' : ''; ?>>
                        <label for="rq">Cuestionario obligatorio</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input type="checkbox" name="is_published" class="form-check-input" id="pub" <?php echo $c && $c->is_published ? 'checked' : ''; ?>>
                        <label for="pub">Publicado</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Guardar curso</button>
        </form>
    </div>
</div>
