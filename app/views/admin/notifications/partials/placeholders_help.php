<div class="alert alert-light border small mb-3 notif-placeholders-help">
    <strong><i class="fas fa-code me-1"></i>Variables personalizadas</strong>
    <p class="mb-2 text-muted">Clic para insertar donde está el cursor, o arrastrá una etiqueta al título o al contenido.</p>
    <div class="d-flex flex-wrap gap-2 notif-placeholder-tags">
        <?php foreach (notification_placeholders_catalog() as $tag => $label): ?>
        <button type="button"
                class="btn btn-sm btn-outline-secondary notif-insert-tag notif-draggable-tag"
                draggable="true"
                data-tag="<?php echo htmlspecialchars($tag); ?>"
                title="<?php echo htmlspecialchars($label); ?>">
            <i class="fas fa-grip-vertical me-1 text-muted" aria-hidden="true"></i>
            <code><?php echo htmlspecialchars($tag); ?></code>
        </button>
        <?php endforeach; ?>
    </div>
    <p class="mb-0 mt-2 text-muted">Ejemplo: <em>Hola &lt;nombre&gt;, nos alegra verte en &lt;empresa&gt;.</em></p>
</div>
