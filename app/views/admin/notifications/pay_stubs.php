<?php
require APPROOT . '/views/inc/header.php';
$targeting = notification_admin_targeting_data();
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/notifications.css">

<div class="mb-4">
    <a href="<?php echo URLROOT; ?>/notificationsAdmin" class="text-muted small"><i class="fas fa-arrow-left me-1"></i>Notificaciones</a>
    <h1 class="h4 mb-0 mt-1">Recibos de sueldo</h1>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Cargar recibo</div>
            <div class="card-body">
                <form method="post" action="<?php echo URLROOT; ?>/notificationsAdmin/uploadPayStub" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <div class="mb-3">
                        <label class="form-label">Empleado</label>
                        <select name="user_id" class="form-select" required>
                            <option value="">Elegir…</option>
                            <?php foreach ($targeting['users'] as $u): ?>
                            <option value="<?php echo (int)$u->id; ?>"><?php echo htmlspecialchars($u->full_name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Período (MM-AAAA)</label>
                        <input type="text" name="period" class="form-control" placeholder="<?php echo date('m-Y'); ?>" pattern="\d{2}-\d{4}" required>
                        <div class="form-text">Mes calendario del recibo (ej. <?php echo date('m-Y'); ?>).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nota o devolución <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea name="admin_note" class="form-control" rows="3" maxlength="2000" placeholder="Observación para el empleado sobre este mes: horas, novedades, devolución, etc."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Archivo (PDF o imagen)</label>
                        <input type="file" name="pay_stub_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="ps_email">
                        <label for="ps_email" class="form-check-label">Notificar por correo</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Subir recibo</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Listado</span>
                <form method="get" class="d-flex gap-2">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Buscar empleado" value="<?php echo htmlspecialchars($data['search']); ?>">
                    <button class="btn btn-sm btn-outline-secondary">Buscar</button>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Empleado</th><th>Empresa</th><th>Período</th><th>Nota</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php foreach ($data['stubs'] as $ps): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ps->full_name); ?></td>
                        <td class="small"><?php echo htmlspecialchars($ps->company_name); ?></td>
                        <td><?php echo pay_stub_period_label($ps->period); ?></td>
                        <td class="small">
                            <?php if (!empty($ps->admin_note)): ?>
                            <span class="pay-stub-note-preview d-inline-block" title="<?php echo htmlspecialchars($ps->admin_note); ?>">
                                <?php echo htmlspecialchars(mb_substr($ps->admin_note, 0, 48)); ?><?php echo mb_strlen($ps->admin_note) > 48 ? '…' : ''; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ps->status === 'signed'): ?>
                            <span class="badge bg-success">Firmado</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">Pendiente firma</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($data['stubs'])): ?>
                    <tr><td colspan="5" class="text-muted text-center py-3">Sin recibos cargados.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
