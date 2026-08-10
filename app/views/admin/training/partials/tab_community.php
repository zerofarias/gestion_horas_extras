<?php /** @var $c,$cid,$data,$enrich */ ?>
<?php if (!$enrich): ?>
<div class="alert alert-warning">Ejecutá <code>migration_learning_enrich.sql</code> para preguntas y sugerencias.</div>
<?php return; endif; ?>
<div class="card shadow">
    <div class="card-header">Preguntas y sugerencias de empleados</div>
    <div class="card-body">
        <?php if (empty($data['discussions'])): ?>
        <p class="text-muted mb-0">Aún no hay mensajes. Los empleados pueden preguntar desde cada lección.</p>
        <?php else: foreach ($data['discussions'] as $d): ?>
        <div class="border rounded p-3 mb-3 <?php echo $d->is_resolved ? 'bg-light' : ''; ?>">
            <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="badge bg-secondary"><?php echo learning_discussion_type_label($d->post_type); ?></span>
                <?php if ($d->lesson_title): ?><span class="badge bg-info text-dark">Lección: <?php echo htmlspecialchars($d->lesson_title); ?></span><?php endif; ?>
                <?php if ($d->is_resolved): ?><span class="badge bg-success">Resuelto</span><?php endif; ?>
                <span class="text-muted small ms-auto"><?php echo htmlspecialchars($d->created_at); ?></span>
            </div>
            <p class="mb-1"><strong><?php echo htmlspecialchars($d->author_name); ?></strong></p>
            <p class="mb-2"><?php echo nl2br(htmlspecialchars($d->body)); ?></p>
            <?php if ($d->admin_reply): ?>
            <div class="alert alert-success small mb-2">
                <strong>Respuesta<?php echo $d->replier_name ? ' (' . htmlspecialchars($d->replier_name) . ')' : ''; ?>:</strong><br>
                <?php echo nl2br(htmlspecialchars($d->admin_reply)); ?>
            </div>
            <?php endif; ?>
            <form method="post" action="<?php echo URLROOT; ?>/trainingAdmin/replyDiscussion/<?php echo (int)$d->id; ?>" class="mt-2">
                <?php echo csrf_field(); ?>
                <textarea name="admin_reply" class="form-control form-control-sm mb-1" rows="2" placeholder="Escribí tu respuesta…"><?php echo htmlspecialchars($d->admin_reply ?? ''); ?></textarea>
                <div class="d-flex gap-2 align-items-center">
                    <div class="form-check">
                        <input type="checkbox" name="is_resolved" class="form-check-input" id="res<?php echo $d->id; ?>" <?php echo $d->is_resolved ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="res<?php echo $d->id; ?>">Marcar resuelto</label>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary ms-auto">Responder</button>
                </div>
            </form>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>
