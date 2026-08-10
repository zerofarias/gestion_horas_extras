<?php /** @var $c,$cid,$data */ ?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header">Preguntas del cuestionario final</div>
            <div class="card-body">
                <?php foreach ($data['questions'] as $q): ?>
                <div class="border rounded p-2 mb-2 small">
                    <strong><?php echo (int)$q->position; ?>.</strong> <?php echo htmlspecialchars($q->question_text); ?>
                    <?php if ($q->explanation): ?><p class="text-muted mb-0 mt-1"><em><?php echo htmlspecialchars($q->explanation); ?></em></p><?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (empty($data['questions'])): ?><p class="text-muted">Agregá al menos 5 preguntas si el cuestionario es obligatorio.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header">Nueva pregunta</div>
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/saveQuiz/<?php echo $cid; ?>">
                    <?php echo csrf_field(); ?>
                    <input type="number" name="position" class="form-control mb-2" value="<?php echo count($data['questions']) + 1; ?>" min="1">
                    <textarea name="question_text" class="form-control mb-2" rows="2" required placeholder="Enunciado de la pregunta"></textarea>
                    <input type="text" name="explanation" class="form-control mb-2" placeholder="Explicación al responder (opcional)">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                    <div class="input-group mb-1">
                        <span class="input-group-text"><input type="radio" name="correct_option" value="<?php echo $i; ?>" <?php echo $i === 0 ? 'checked' : ''; ?>></span>
                        <input type="text" name="option_text[]" class="form-control" placeholder="Opción <?php echo $i + 1; ?>">
                    </div>
                    <?php endfor; ?>
                    <button type="submit" class="btn btn-primary mt-2">Agregar pregunta</button>
                </form>
            </div>
        </div>
    </div>
</div>
