<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="emp-page-header cp-index-header">
    <a href="<?php echo URLROOT; ?>/employee/index" class="emp-back-btn" aria-label="Volver"><i class="fas fa-arrow-left"></i></a>
    <div class="cp-index-header-text">
        <h1 class="emp-page-title">Extras por tarea</h1>
        <p class="emp-page-subtitle">Casa Paviotti</p>
    </div>
</div>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if (empty($data['has_rates'])): ?>
<div class="alert alert-warning py-2 small mb-3">
    <i class="fas fa-exclamation-triangle me-1"></i>
    RRHH aún no cargó tus tarifas. Podés usar tareas con badge <strong>Manual</strong>; el resto necesita que RRHH configure tus importes.
</div>
<?php endif; ?>

<!-- Resumen pendientes (mobile first) -->
<div class="cp-summary-card" aria-live="polite">
    <div class="cp-summary-main">
        <span class="cp-summary-label">Pendiente de cierre</span>
        <span class="cp-summary-amount"><?php echo cp_format_money($data['pending_total']); ?></span>
    </div>
    <div class="cp-summary-meta">
        <span class="cp-summary-count">
            <i class="fas fa-list-check"></i>
            <?php echo (int)$data['pending_count']; ?> registro<?php echo $data['pending_count'] === 1 ? '' : 's'; ?>
        </span>
    </div>
</div>

<!-- Nueva tarea: cards por grupo -->
<div class="emp-section-title">
    <i class="fas fa-plus-circle me-2" style="color:var(--clr-primary)"></i>Nueva tarea
</div>

<?php foreach ($data['task_groups'] as $group): ?>
<section class="cp-task-section" aria-labelledby="cp-grp-<?php echo htmlspecialchars($group['label']); ?>">
    <h2 class="cp-task-section-title" id="cp-grp-<?php echo htmlspecialchars($group['label']); ?>">
        <?php echo htmlspecialchars($group['label']); ?>
    </h2>
    <div class="cp-task-grid">
        <?php foreach ($group['items'] as $t): ?>
        <a href="<?php echo URLROOT; ?>/cpTask/create/<?php echo htmlspecialchars($t->form_key); ?>"
           class="cp-task-card"
           style="--cp-card-color:<?php echo htmlspecialchars($t->color); ?>">
            <span class="cp-task-card-icon" aria-hidden="true">
                <i class="fas <?php echo htmlspecialchars($t->icon); ?>"></i>
            </span>
            <span class="cp-task-card-body">
                <span class="cp-task-card-title"><?php echo htmlspecialchars($t->display_name); ?></span>
                <?php if ($t->hint): ?>
                <span class="cp-task-card-hint"><?php echo htmlspecialchars($t->hint); ?></span>
                <?php endif; ?>
            </span>
            <?php if ($t->is_manual): ?>
            <span class="cp-task-card-badge">Manual</span>
            <?php endif; ?>
            <span class="cp-task-card-chevron" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endforeach; ?>

<!-- Pendientes -->
<div class="emp-section-title d-flex justify-content-between align-items-center flex-wrap gap-2 mt-2">
    <span><i class="fas fa-clock me-2" style="color:var(--clr-primary)"></i>Mis pendientes</span>
</div>

<?php if (empty($data['pending']) && empty($data['pending_external'])): ?>
<div class="emp-card cp-empty-card">
    <div class="emp-empty">
        <i class="fas fa-check-circle text-success"></i>
        <p>No tenés tareas pendientes de cierre.</p>
    </div>
</div>
<?php else: ?>
<div class="cp-pending-list">
    <?php foreach ($data['pending'] as $e):
        $meta = cp_task_display_meta($e->form_key ?? '');
        $detail = trim((string)($e->deceased_name ?: $e->deceased_code ?: ''));
    ?>
    <article class="cp-pending-card">
        <div class="cp-pending-top">
            <span class="cp-pending-icon" style="--cp-card-color:<?php echo htmlspecialchars($meta['color']); ?>">
                <i class="fas <?php echo htmlspecialchars($meta['icon']); ?>"></i>
            </span>
            <div class="cp-pending-info">
                <h3 class="cp-pending-title"><?php echo htmlspecialchars(cp_task_display_name($e->form_key ?? '', $e->task_name)); ?></h3>
                <p class="cp-pending-date"><i class="far fa-calendar me-1"></i><?php echo cp_format_date_es($e->activity_date); ?></p>
                <?php if ($detail !== ''): ?>
                <p class="cp-pending-detail"><?php echo htmlspecialchars($detail); ?></p>
                <?php endif; ?>
            </div>
            <div class="cp-pending-amount">
                <?php echo cp_format_money($e->amount); ?>
                <?php if ($e->is_holiday): ?>
                <span class="badge bg-info text-dark mt-1">Feriado ×<?php echo rtrim(rtrim(number_format((float)$e->holiday_multiplier, 2, ',', ''), '0'), ','); ?></span>
                <?php endif; ?>
            </div>
        </div>
        <form method="post" action="<?php echo URLROOT; ?>/cpTask/delete/<?php echo (int)$e->id; ?>"
              class="cp-pending-actions" onsubmit="return confirm('¿Eliminar este registro?');">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                <i class="fas fa-trash-alt me-1"></i>Eliminar
            </button>
        </form>
    </article>
    <?php endforeach; ?>

    <?php foreach ($data['pending_external'] ?? [] as $e):
        $meta = cp_task_display_meta('externas');
    ?>
    <article class="cp-pending-card cp-pending-card--external">
        <div class="cp-pending-top">
            <span class="cp-pending-icon" style="--cp-card-color:<?php echo htmlspecialchars($meta['color']); ?>">
                <i class="fas <?php echo htmlspecialchars($meta['icon']); ?>"></i>
            </span>
            <div class="cp-pending-info">
                <h3 class="cp-pending-title">
                    <?php echo htmlspecialchars($e->task_label); ?>
                    <span class="badge bg-secondary ms-1">Externa</span>
                </h3>
                <p class="cp-pending-date"><i class="far fa-calendar me-1"></i><?php echo cp_format_date_es($e->activity_date); ?></p>
                <p class="cp-pending-detail"><?php echo htmlspecialchars($e->external_company_name); ?></p>
            </div>
            <div class="cp-pending-amount"><?php echo cp_format_money($e->amount); ?></div>
        </div>
        <form method="post" action="<?php echo URLROOT; ?>/cpTask/deleteExternal/<?php echo (int)$e->id; ?>"
              class="cp-pending-actions" onsubmit="return confirm('¿Eliminar este registro?');">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                <i class="fas fa-trash-alt me-1"></i>Eliminar
            </button>
        </form>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<p class="cp-index-footnote small text-muted text-center mt-3 mb-4">
    Los importes se liquidan cuando RRHH cierra el mes. En feriado, las tareas automáticas se duplican; las manuales no.
</p>

<?php require APPROOT . '/views/inc/footer.php'; ?>
