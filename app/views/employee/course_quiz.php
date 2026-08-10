<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="mb-3">
    <a href="<?php echo URLROOT; ?>/training/lesson/<?php echo (int)$data['course']->id; ?>/<?php echo max(1, (int)($data['enrollment']->current_lesson_position ?? 1)); ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Volver al curso</a>
</div>
<div class="lrn-player-shell lrn-player-premium">
<h2 class="h4 mb-3">Cuestionario: <?php echo htmlspecialchars($data['course']->title); ?></h2>
<?php if (!empty($data['quiz_shuffled'])): ?>
<p class="small text-muted mb-3"><i class="fas fa-random me-1"></i> Las preguntas y opciones están en orden aleatorio (único para vos).</p>
<?php endif; ?>
<p class="small mb-3">Aprobación: <?php echo (int)$data['course']->passing_score; ?>% · Estrellas al aprobar: <i class="fas fa-star text-warning"></i> <?php echo (int)$data['course']->stars_on_complete; ?>
<?php if ((int)($data['course']->first_finisher_bonus ?? 0) > 0): ?>
 · <strong>+<?php echo (int)$data['course']->first_finisher_bonus; ?></strong> si sos el primero en completar
<?php endif; ?>
</p>
<form method="post" action="<?php echo URLROOT; ?>/training/submitQuiz/<?php echo (int)$data['course']->id; ?>">
    <?php echo csrf_field(); ?>
    <?php foreach ($data['questions'] as $q): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <p class="fw-semibold mb-2"><?php echo (int)($q->display_position ?? $q->position); ?>. <?php echo htmlspecialchars($q->question_text); ?></p>
            <?php foreach ($q->options as $opt): ?>
            <label class="lrn-quiz-option d-block">
                <input type="radio" name="answers[<?php echo (int)$q->id; ?>]" value="<?php echo (int)$opt->id; ?>" required class="me-2">
                <?php echo htmlspecialchars($opt->option_text); ?>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary btn-lg w-100">Enviar respuestas</button>
</form>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
