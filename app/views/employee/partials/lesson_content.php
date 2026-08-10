<?php
/** @var object|null $lesson */
$player = learning_lesson_player($lesson ?? null, !empty($learningStreamAdmin));
?>
<?php if ($player['mode'] === 'iframe'): ?>
<div class="lrn-video-theater mb-3">
    <div class="lrn-video-theater-label">
        <i class="fas fa-play-circle"></i> Video en el curso — no necesitás salir a YouTube
    </div>
    <div class="lrn-video-wrap">
        <iframe src="<?php echo htmlspecialchars($player['src']); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen title="Video de la lección"></iframe>
    </div>
</div>
<?php elseif ($player['mode'] === 'video'): ?>
<div class="lrn-video-native mb-3">
    <video controls controlsList="nodownload" preload="metadata" class="w-100">
        <source src="<?php echo htmlspecialchars($player['src']); ?>" type="video/mp4">
        Tu navegador no reproduce video HTML5. <a href="<?php echo htmlspecialchars($player['src']); ?>" target="_blank" rel="noopener">Descargar video</a>
    </video>
</div>
<?php elseif ($player['mode'] === 'pdf'): ?>
<div class="lrn-pdf-wrap mb-3">
    <iframe src="<?php echo htmlspecialchars($player['src']); ?>#toolbar=1" title="Documento PDF"></iframe>
    <a href="<?php echo htmlspecialchars($player['src']); ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary mt-2">
        <i class="fas fa-external-link-alt me-1"></i> Abrir PDF en pestaña nueva
    </a>
</div>
<?php elseif ($player['mode'] === 'download'): ?>
<div class="lrn-download-card mb-3 p-4 text-center border rounded bg-light">
    <i class="fas fa-file-download fa-3x text-primary mb-3"></i>
    <p class="mb-2"><?php echo htmlspecialchars($player['filename'] ?? 'Archivo'); ?></p>
    <a href="<?php echo htmlspecialchars($player['src']); ?>" class="btn btn-primary" download>
        <i class="fas fa-download me-1"></i> Descargar material
    </a>
</div>
<?php elseif (!empty($player['body'])): ?>
<div class="lrn-text-content card mb-3">
    <div class="card-body lrn-prose">
        <?php echo nl2br(htmlspecialchars($player['body'])); ?>
    </div>
</div>
<?php else: ?>
<div class="alert alert-light border mb-3">
    <i class="fas fa-info-circle me-1"></i> Esta lección no tiene contenido cargado todavía.
</div>
<?php endif; ?>
