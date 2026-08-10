<?php require APPROOT . '/views/inc/header.php';
$s = $data['survey'];
?>

<div class="container py-3" style="max-width:720px">
    <h2 class="h4 mb-2"><?php echo htmlspecialchars($s->title); ?></h2>
    <?php if ((int)$s->is_anonymous): ?>
    <div class="alert alert-info py-2 small">
        <i class="fas fa-user-secret me-1"></i>
        Esta encuesta es <strong>anónima</strong>. Tu nombre no se guardará con las respuestas.
    </div>
    <?php else: ?>
    <div class="alert alert-warning py-2 small">
        <i class="fas fa-id-card me-1"></i>
        Esta encuesta <strong>no es anónima</strong>. RRHH podrá ver que completaste el formulario y tus respuestas.
    </div>
    <?php endif; ?>
    <?php if (!empty($s->description)): ?>
    <p class="text-muted"><?php echo nl2br(htmlspecialchars($s->description)); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo URLROOT; ?>/survey/fill/<?php echo (int)$s->id; ?>">
        <?php echo csrf_field(); ?>
        <?php foreach ($data['questions'] as $q):
            $cfg = $q->config_json ? json_decode($q->config_json, true) : [];
            $req = (int)$q->is_required ? 'required' : '';
        ?>
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <label class="form-label fw-semibold">
                    <?php echo htmlspecialchars($q->label); ?>
                    <?php if ($req): ?><span class="text-danger">*</span><?php endif; ?>
                </label>
                <?php if ($q->question_type === 'short_text'): ?>
                <input type="text" name="q_<?php echo (int)$q->id; ?>" class="form-control" <?php echo $req; ?>>
                <?php elseif ($q->question_type === 'long_text'): ?>
                <textarea name="q_<?php echo (int)$q->id; ?>" class="form-control" rows="3" <?php echo $req; ?>></textarea>
                <?php elseif ($q->question_type === 'date'): ?>
                <input type="date" name="q_<?php echo (int)$q->id; ?>" class="form-control" <?php echo $req; ?>>
                <?php elseif ($q->question_type === 'scale'):
                    $min = (int)($cfg['min'] ?? 1);
                    $max = (int)($cfg['max'] ?? 5);
                ?>
                <select name="q_<?php echo (int)$q->id; ?>" class="form-select" <?php echo $req; ?>>
                    <option value="">—</option>
                    <?php for ($i = $min; $i <= $max; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
                <?php elseif ($q->question_type === 'single_choice'): ?>
                <?php foreach (($cfg['options'] ?? []) as $opt): ?>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="q_<?php echo (int)$q->id; ?>"
                           value="<?php echo htmlspecialchars($opt); ?>" id="q<?php echo (int)$q->id; ?>_<?php echo md5($opt); ?>" <?php echo $req; ?>>
                    <label class="form-check-label" for="q<?php echo (int)$q->id; ?>_<?php echo md5($opt); ?>"><?php echo htmlspecialchars($opt); ?></label>
                </div>
                <?php endforeach; ?>
                <?php elseif ($q->question_type === 'multiple_choice'): ?>
                <?php foreach (($cfg['options'] ?? []) as $opt): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="q_<?php echo (int)$q->id; ?>[]"
                           value="<?php echo htmlspecialchars($opt); ?>" id="q<?php echo (int)$q->id; ?>_<?php echo md5($opt); ?>">
                    <label class="form-check-label" for="q<?php echo (int)$q->id; ?>_<?php echo md5($opt); ?>"><?php echo htmlspecialchars($opt); ?></label>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary w-100">Enviar respuestas</button>
        <a href="<?php echo URLROOT; ?>/survey/index" class="btn btn-link w-100 mt-2">Cancelar</a>
    </form>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
