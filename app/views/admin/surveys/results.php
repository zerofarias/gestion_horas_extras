<?php require APPROOT . '/views/inc/header.php';
$s = $data['survey'];
?>

<div class="page-header mb-4">
    <h1 class="h3 mb-1">Resultados: <?php echo htmlspecialchars($s->title); ?></h1>
    <p class="text-muted mb-0">
        <?php echo (int)$data['response_count']; ?> respuesta(s)
        <?php if ((int)$s->is_anonymous): ?> — <strong>encuesta anónima</strong> (sin listado por persona)<?php endif; ?>
    </p>
    <a href="<?php echo URLROOT; ?>/surveyAdmin/index" class="btn btn-outline-secondary btn-sm">Volver</a>
</div>

<?php foreach ($data['questions'] as $q): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header fw-semibold"><?php echo htmlspecialchars($q->label); ?></div>
    <div class="card-body">
        <?php if (!empty($data['aggregates'][$q->id])): ?>
        <ul class="mb-0">
        <?php foreach ($data['aggregates'][$q->id] as $opt => $cnt): ?>
        <li><?php echo htmlspecialchars($opt); ?>: <strong><?php echo (int)$cnt; ?></strong></li>
        <?php endforeach; ?>
        </ul>
        <?php else: ?>
        <p class="text-muted small mb-0">Ver respuestas individuales abajo (texto libre) o sin datos aún.</p>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (!(int)$s->is_anonymous && !empty($data['responses'])): ?>
<div class="card shadow-sm">
    <div class="card-header fw-semibold">Respuestas por empleado</div>
    <div class="list-group list-group-flush">
    <?php foreach ($data['responses'] as $resp): ?>
    <div class="list-group-item">
        <strong><?php echo htmlspecialchars($resp->full_name ?? 'Usuario #' . $resp->user_id); ?></strong>
        <span class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($resp->submitted_at)); ?></span>
        <ul class="small mb-0 mt-2">
        <?php foreach ((new Survey())->getAnswersForResponse((int)$resp->id) as $ans): ?>
        <li><em><?php echo htmlspecialchars($ans->label); ?>:</em> <?php echo htmlspecialchars($ans->answer_text); ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php require APPROOT . '/views/inc/footer.php'; ?>
