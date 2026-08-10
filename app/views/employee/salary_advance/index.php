<?php
require APPROOT . '/views/inc/header.php';
$history = $history ?? [];
$usedAnnual = (int)($used_annual ?? 0);
$maxAnnual = (int)($max_annual ?? 2);
$maxInstallments = (int)($max_installments ?? 2);
$minAmount = (float)($min_amount ?? 1);
$canSubmit = !empty($can_submit);
$hasPending = !empty($has_pending);
$year = (int)($year ?? date('Y'));
?>

<div class="emp-page-header">
    <a href="<?php echo URLROOT; ?>/employee/index" class="emp-back-btn"><i class="fas fa-arrow-left"></i></a>
    <div>
        <h1 class="emp-page-title">Adelanto de sueldo</h1>
        <p class="emp-page-subtitle">Solicitá un adelanto y seguí el estado</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="emp-card p-3 h-100">
            <div class="small text-muted">Solicitudes en <?php echo $year; ?></div>
            <div class="h4 mb-0"><?php echo $usedAnnual; ?> <span class="text-muted fs-6">de <?php echo $maxAnnual; ?></span></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="emp-card p-3 h-100">
            <div class="small text-muted">Estado actual</div>
            <div class="h6 mb-0">
                <?php if ($hasPending): ?>
                    <span class="badge bg-warning text-dark">Tenés una solicitud pendiente</span>
                <?php elseif (!$canSubmit): ?>
                    <span class="badge bg-secondary">Sin cupo disponible</span>
                <?php else: ?>
                    <span class="badge bg-success">Podés solicitar</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($canSubmit): ?>
<section class="emp-card emp-form-card mb-4">
    <h2 class="emp-section-title mb-3"><i class="fas fa-hand-holding-usd me-2" style="color:var(--clr-primary)"></i>Nueva solicitud</h2>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        RRHH revisará el monto solicitado y definirá el plan de devolución al aprobar.
    </div>

    <form action="<?php echo URLROOT; ?>/salaryAdvance/store" method="post">
        <?php echo csrf_field(); ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Monto solicitado ($)</label>
                <input type="number" name="amount" class="form-control" step="0.01"
                       min="<?php echo htmlspecialchars((string)$minAmount); ?>" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Cuotas de devolución sugeridas</label>
                <select name="installments_requested" class="form-select" required>
                    <?php for ($i = 1; $i <= $maxInstallments; $i++): ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?> cuota<?php echo $i > 1 ? 's' : ''; ?></option>
                    <?php endfor; ?>
                </select>
                <div class="form-text">RRHH puede ajustar las cuotas al aprobar.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Motivo (opcional)</label>
                <textarea name="reason" class="form-control" rows="2" maxlength="1000" placeholder="Ej. gastos familiares"></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">
            <i class="fas fa-paper-plane me-1"></i> Enviar solicitud
        </button>
    </form>
</section>
<?php elseif ($hasPending): ?>
<div class="alert alert-warning">Ya tenés una solicitud en revisión. Cuando RRHH la resuelva podrás enviar otra (si tenés cupo anual).</div>
<?php else: ?>
<div class="alert alert-secondary">Alcanzaste el límite de <?php echo $maxAnnual; ?> solicitudes en <?php echo $year; ?>.</div>
<?php endif; ?>

<section class="emp-card">
    <h2 class="emp-section-title mb-3"><i class="fas fa-history me-2"></i>Mis solicitudes</h2>
    <?php if (empty($history)): ?>
    <div class="emp-empty py-4">
        <i class="fas fa-inbox"></i>
        <p class="mb-0">Todavía no enviaste solicitudes de adelanto.</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Cuotas</th>
                    <th>Estado</th>
                    <th>Notas RRHH</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($row->created_at)); ?></td>
                    <td><?php echo salary_advance_format_money($row->amount); ?></td>
                    <td>
                        <?php echo (int)$row->installments_requested; ?>
                        <?php if (in_array($row->status, ['Aprobado', 'Finalizado'], true) && $row->installments_approved !== null): ?>
                            <span class="text-muted small">(aprob.: <?php echo (int)$row->installments_approved; ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo salary_advance_status_badge_class($row->status); ?>"><?php echo htmlspecialchars($row->status); ?></span></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($row->admin_notes ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</section>

<?php require APPROOT . '/views/inc/footer.php'; ?>
