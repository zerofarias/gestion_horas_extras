<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/learning.css">

<div class="d-flex align-items-center gap-2 mb-4">
    <h2 class="page-title mb-0">Reconocer compañeros</h2>
    <span class="lrn-hero-stars ms-auto"><i class="fas fa-star text-warning"></i> <?php echo (int)$data['balance']; ?> pts</span>
</div>

<div class="alert alert-light border small mb-4">
    <i class="fas fa-user-secret me-1"></i>
    Tus acciones son <strong>anónimas</strong> para el resto: nadie ve quién dio o quitó puntos.
    Máximo <strong><?php echo (int)$data['max_net']; ?></strong> estrellas netas por persona por mes (por acción, hasta <?php echo (int)$data['max_single']; ?>).
    Este saldo es distinto de las estrellas de cursos y premios.
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header fw-semibold">Dar o quitar reconocimiento</div>
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/peerStar/give">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Compañero</label>
                        <select name="receiver_id" class="form-select" required>
                            <option value="">— Elegir —</option>
                            <?php foreach ($data['colleagues'] as $c): ?>
                            <option value="<?php echo (int)$c->id; ?>"><?php echo htmlspecialchars($c->full_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Cantidad (1–<?php echo (int)$data['max_single']; ?>)</label>
                            <select name="amount" class="form-select" required>
                                <?php for ($i = 1; $i <= (int)$data['max_single']; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Acción</label>
                            <select name="direction" class="form-select">
                                <option value="give">Dar (+)</option>
                                <option value="remove">Quitar (−)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <select name="reason_category" class="form-select" required>
                            <?php foreach ($data['categories'] as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comentario interno (opcional)</label>
                        <input type="text" name="comment" class="form-control" maxlength="255" placeholder="Solo visible para RRHH en auditoría">
                        <div class="form-text">No se muestra al compañero; evitá nombres para mantener el anonimato.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Registrar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow">
            <div class="card-header fw-semibold">Lo que recibiste</div>
            <div class="card-body p-0">
                <?php if (empty($data['history'])): ?>
                <p class="text-muted p-3 mb-0">Sin movimientos aún.</p>
                <?php else: ?>
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Fecha</th><th>De</th><th>Motivo</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($data['history'] as $h): ?>
                    <tr>
                        <td class="small text-nowrap"><?php echo date('d/m/Y', strtotime($h->created_at)); ?></td>
                        <td class="small"><?php echo htmlspecialchars(peer_star_anonymous_giver_label()); ?></td>
                        <td class="small"><?php echo htmlspecialchars(peer_star_category_label($h->reason_category)); ?></td>
                        <td class="text-end fw-semibold <?php echo (int)$h->delta > 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo ((int)$h->delta > 0 ? '+' : '') . (int)$h->delta; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        <p class="small text-muted mt-2 mb-0">
            <a href="<?php echo URLROOT; ?>/training/stars">Estrellas de cursos y premios</a> (saldo aparte)
        </p>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
