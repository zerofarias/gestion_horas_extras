<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">
<div class="d-flex align-items-center gap-2 mb-4">
    <h2 class="page-title mb-0">Mis tareas</h2>
    <span class="lrn-hero-stars ms-auto"><i class="fas fa-star"></i> <?php echo (int)$data['stars']; ?></span>
    <a href="<?php echo URLROOT; ?>/training/index" class="btn btn-outline-secondary btn-sm">Cursos</a>
</div>
<?php if (empty($data['tasks'])): ?>
<div class="alert alert-info">No tenés tareas asignadas.</div>
<?php else: ?>
<?php foreach ($data['tasks'] as $t): ?>
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div>
            <div>
                <h5 class="mb-1"><?php echo htmlspecialchars($t->title); ?></h5>
                <p class="text-muted small mb-0"><?php echo nl2br(htmlspecialchars($t->description ?? '')); ?></p>
                <?php if ($t->due_date): ?><p class="small mt-1 mb-0">Vence: <?php echo htmlspecialchars($t->due_date); ?></p><?php endif; ?>
                <?php if ((int)$t->stars_on_complete > 0): ?><p class="small text-warning mb-0">+<?php echo (int)$t->stars_on_complete; ?> estrella(s)</p><?php endif; ?>
            </div>
            <?php if (!empty($t->completed)): ?>
            <span class="badge bg-success">Hecha</span>
            <?php else: ?>
            <form method="post" action="<?php echo URLROOT; ?>/training/completeTask/<?php echo (int)$t->id; ?>">
                <?php echo csrf_field(); ?>
                <input type="text" name="note" class="form-control form-control-sm mb-1" placeholder="Nota opcional">
                <button type="submit" class="btn btn-sm btn-success">Marcar hecha</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>
<?php require APPROOT . '/views/inc/footer.php'; ?>
