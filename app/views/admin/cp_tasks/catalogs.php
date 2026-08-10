<?php require APPROOT . '/views/inc/header.php'; ?>

<h1 class="page-title mb-4">Catálogos — Casa Paviotti</h1>
<p class="mb-4"><a href="<?php echo URLROOT; ?>/cpTaskAdmin/pending">← Pendientes</a></p>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Localidades</strong></div>
            <div class="card-body">
                <p class="small text-muted">Adicional: 0=normal, 1=+localidades en sepelio, 2=ambulancia VM / metálica</p>
                <ul class="list-group list-group-flush mb-3 small">
                    <?php foreach ($data['localities'] as $loc): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <?php echo htmlspecialchars($loc->name); ?>
                        <span class="badge bg-light text-dark"><?php echo (int)$loc->has_additional; ?></span>
                        <form method="post" class="ms-2" onsubmit="return confirm('¿Eliminar?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="catalog_action" value="del_locality">
                            <input type="hidden" name="id" value="<?php echo (int)$loc->id; ?>">
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">×</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" class="row g-2">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="catalog_action" value="add_locality">
                    <div class="col-7"><input type="text" name="name" class="form-control form-control-sm" placeholder="Nombre" required></div>
                    <div class="col-3"><input type="number" name="has_additional" class="form-control form-control-sm" min="0" max="2" value="0"></div>
                    <div class="col-2"><button type="submit" class="btn btn-sm btn-primary w-100">+</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Retirado en</strong></div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3 small">
                    <?php foreach ($data['pickup_places'] as $p): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <?php echo htmlspecialchars($p->name); ?>
                        <form method="post" onsubmit="return confirm('¿Eliminar?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="catalog_action" value="del_pickup">
                            <input type="hidden" name="id" value="<?php echo (int)$p->id; ?>">
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">×</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" class="input-group input-group-sm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="catalog_action" value="add_pickup">
                    <input type="text" name="name" class="form-control" placeholder="Nuevo lugar" required>
                    <button type="submit" class="btn btn-primary">+</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm h-100">
            <div class="card-header"><strong>Empresas (tareas externas)</strong></div>
            <div class="card-body">
                <ul class="list-group list-group-flush mb-3 small">
                    <?php foreach ($data['external_companies'] as $ec): ?>
                    <li class="list-group-item d-flex justify-content-between px-0">
                        <?php echo htmlspecialchars($ec->name); ?>
                        <form method="post" onsubmit="return confirm('¿Desactivar?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="catalog_action" value="del_external_co">
                            <input type="hidden" name="id" value="<?php echo (int)$ec->id; ?>">
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0">×</button>
                        </form>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <form method="post" class="input-group input-group-sm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="catalog_action" value="add_external_co">
                    <input type="text" name="name" class="form-control" placeholder="Empresa" required>
                    <button type="submit" class="btn btn-primary">+</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
