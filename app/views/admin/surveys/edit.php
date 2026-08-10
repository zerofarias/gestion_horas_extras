<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/notifications.css">
<?php
$s = $data['survey'] ?? null;
$isNew = !empty($data['is_new']);
$isPublished = $s && $s->status === 'published';
?>

<div class="page-header mb-4">
    <h1 class="h3 mb-1"><?php echo $isNew ? 'Nueva encuesta' : 'Editar encuesta'; ?></h1>
    <a href="<?php echo URLROOT; ?>/surveyAdmin/index" class="btn btn-outline-secondary btn-sm">Volver al listado</a>
</div>

<form method="post" action="<?php echo URLROOT; ?>/surveyAdmin/edit/<?php echo $isNew ? '0' : (int)$s->id; ?>">
    <?php echo csrf_field(); ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">Datos generales</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Título</label>
                <input type="text" name="title" class="form-control" required maxlength="200"
                       value="<?php echo htmlspecialchars($s->title ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($s->description ?? ''); ?></textarea>
            </div>
            <div class="form-check mb-3">
                <input type="checkbox" name="is_anonymous" value="1" class="form-check-input" id="isAnonymous"
                    <?php echo ($s ? (int)$s->is_anonymous : true) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="isAnonymous">
                    <strong>Encuesta anónima</strong> — no se guarda quién respondió (el empleado verá el aviso)
                </label>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Abre (opcional)</label>
                    <input type="datetime-local" name="open_at" class="form-control"
                           value="<?php echo $s && $s->open_at ? date('Y-m-d\TH:i', strtotime($s->open_at)) : ''; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Cierra (opcional)</label>
                    <input type="datetime-local" name="close_at" class="form-control"
                           value="<?php echo $s && $s->close_at ? date('Y-m-d\TH:i', strtotime($s->close_at)) : ''; ?>">
                </div>
            </div>
        </div>
    </div>

    <?php if ($isPublished): ?>
    <div class="alert alert-warning">Encuesta publicada: solo podés editar título, descripción y fechas. Para cambiar preguntas o destinatarios, cerrala y creá una nueva.</div>
    <?php endif; ?>

    <fieldset <?php echo $isPublished ? 'disabled' : ''; ?>>
    <?php
    $companies = $data['companies'];
    $areas = $data['areas'];
    $users = $data['users'];
    $selected = $data['selected'];
    $picker_users = $users;
    require APPROOT . '/views/admin/notifications/partials/target_form.php';
    ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Preguntas</span>
            <?php if (!$isPublished): ?>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddQuestion"><i class="fas fa-plus"></i> Agregar</button>
            <?php endif; ?>
        </div>
        <div class="card-body" id="questionsBuilder">
            <?php
            $qIndex = 0;
            foreach ($data['questions'] as $q):
                $cfg = $q->config_json ? json_decode($q->config_json, true) : [];
                $optsText = isset($cfg['options']) ? implode("\n", $cfg['options']) : '';
            ?>
            <div class="border rounded p-3 mb-3 question-row">
                <div class="row g-2">
                    <div class="col-md-8">
                        <label class="form-label small">Enunciado</label>
                        <input type="text" name="questions[<?php echo $qIndex; ?>][label]" class="form-control" required
                               value="<?php echo htmlspecialchars($q->label); ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Tipo</label>
                        <select name="questions[<?php echo $qIndex; ?>][question_type]" class="form-select q-type-select">
                            <?php foreach ($data['question_types'] as $tk => $tl): ?>
                            <option value="<?php echo $tk; ?>" <?php echo $q->question_type === $tk ? 'selected' : ''; ?>><?php echo htmlspecialchars($tl); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 q-options-wrap" style="<?php echo in_array($q->question_type, ['single_choice','multiple_choice'], true) ? '' : 'display:none'; ?>">
                        <label class="form-label small">Opciones (una por línea)</label>
                        <textarea name="questions[<?php echo $qIndex; ?>][options]" class="form-control" rows="3"><?php echo htmlspecialchars($optsText); ?></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="questions[<?php echo $qIndex; ?>][is_required]" value="1" class="form-check-input" <?php echo (int)$q->is_required ? 'checked' : ''; ?>>
                            <label class="form-check-label small">Obligatoria</label>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger mt-2 btn-remove-q">Quitar</button>
            </div>
            <?php $qIndex++; endforeach; ?>
        </div>
    </div>
    </fieldset>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary"><?php echo $isPublished ? 'Guardar cambios' : 'Guardar borrador'; ?></button>
        <?php if (!$isNew && $s && $s->status === 'draft'): ?>
        <button type="submit" formaction="<?php echo URLROOT; ?>/surveyAdmin/publish/<?php echo (int)$s->id; ?>" class="btn btn-success"
                onclick="return confirm('¿Publicar y notificar empleados?');">Publicar</button>
        <?php endif; ?>
    </div>
</form>

<template id="questionRowTpl">
    <div class="border rounded p-3 mb-3 question-row">
        <div class="row g-2">
            <div class="col-md-8">
                <label class="form-label small">Enunciado</label>
                <input type="text" name="questions[__IDX__][label]" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Tipo</label>
                <select name="questions[__IDX__][question_type]" class="form-select q-type-select">
                    <?php foreach ($data['question_types'] as $tk => $tl): ?>
                    <option value="<?php echo $tk; ?>"><?php echo htmlspecialchars($tl); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 q-options-wrap" style="display:none">
                <label class="form-label small">Opciones (una por línea)</label>
                <textarea name="questions[__IDX__][options]" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input type="checkbox" name="questions[__IDX__][is_required]" value="1" class="form-check-input" checked>
                    <label class="form-check-label small">Obligatoria</label>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger mt-2 btn-remove-q">Quitar</button>
    </div>
</template>

<script src="<?php echo URLROOT; ?>/js/notifications-admin.js"></script>
<script>
(function() {
    var idx = <?php echo (int)$qIndex; ?>;
    var builder = document.getElementById('questionsBuilder');
    var tpl = document.getElementById('questionRowTpl');

    function bindRow(row) {
        var sel = row.querySelector('.q-type-select');
        var wrap = row.querySelector('.q-options-wrap');
        function toggle() {
            var t = sel.value;
            wrap.style.display = (t === 'single_choice' || t === 'multiple_choice') ? '' : 'none';
        }
        sel.addEventListener('change', toggle);
        toggle();
        row.querySelector('.btn-remove-q').addEventListener('click', function() { row.remove(); });
    }

    builder.querySelectorAll('.question-row').forEach(bindRow);

    document.getElementById('btnAddQuestion').addEventListener('click', function() {
        var html = tpl.innerHTML.replace(/__IDX__/g, String(idx++));
        var div = document.createElement('div');
        div.innerHTML = html;
        var row = div.firstElementChild;
        builder.appendChild(row);
        bindRow(row);
    });
})();
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
