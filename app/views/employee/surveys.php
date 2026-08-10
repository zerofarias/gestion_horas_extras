<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="container py-3">
    <h2 class="h4 mb-3">Encuestas</h2>
    <?php if (empty($data['pending'])): ?>
    <div class="alert alert-success">No tenés encuestas pendientes.</div>
    <?php else: ?>
    <div class="list-group">
    <?php foreach ($data['pending'] as $s): ?>
    <a href="<?php echo URLROOT; ?>/survey/fill/<?php echo (int)$s->id; ?>" class="list-group-item list-group-item-action">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <strong><?php echo htmlspecialchars($s->title); ?></strong>
                <?php if ((int)$s->is_anonymous): ?>
                <span class="badge bg-info ms-1">Anónima</span>
                <?php else: ?>
                <span class="badge bg-secondary ms-1">Identificada</span>
                <?php endif; ?>
                <p class="small text-muted mb-0 mt-1"><?php echo htmlspecialchars($s->description ?? ''); ?></p>
            </div>
            <i class="fas fa-chevron-right text-muted"></i>
        </div>
    </a>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
