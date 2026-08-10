<?php
require APPROOT . '/views/inc/header.php';
$stub = $data['stub'];
$signed = $stub->status === 'signed';
$fileUrl = $data['file_url'];
$downloadUrl = $data['download_url'];
$isPdf = !empty($data['is_pdf']);
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/notifications.css">
<h1 class="h4 mb-3"><?php echo pay_stub_period_display($stub->period); ?></h1>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm pay-stub-doc-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
                <span class="small fw-semibold text-muted">Documento del recibo</span>
                <div class="btn-group btn-group-sm pay-stub-doc-actions">
                    <button type="button" class="btn btn-outline-primary" id="btnPayStubExpand" data-file-url="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" data-is-pdf="<?php echo $isPdf ? '1' : '0'; ?>">
                        <i class="fas fa-expand me-1"></i>Ver en grande
                    </button>
                    <a href="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-download me-1"></i>Descargar
                    </a>
                    <?php if ($isPdf): ?>
                    <a href="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                        <i class="fas fa-external-link-alt me-1"></i>Abrir pestaña
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-2">
                <?php if ($isPdf): ?>
                <iframe src="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" class="pay-stub-preview-iframe" title="Recibo PDF"></iframe>
                <?php else: ?>
                <button type="button" class="btn btn-link p-0 border-0 w-100 pay-stub-img-trigger" id="btnPayStubExpandImg" data-file-url="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>">
                    <img src="<?php echo htmlspecialchars($fileUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Recibo" class="pay-stub-preview-img">
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <?php if (!empty($stub->admin_note)): ?>
        <div class="alert alert-info small mb-3 pay-stub-admin-note">
            <strong><i class="fas fa-comment-dots me-1"></i>Observación del período</strong>
            <p class="mb-0 mt-1"><?php echo nl2br(htmlspecialchars($stub->admin_note)); ?></p>
        </div>
        <?php endif; ?>
        <?php if ($signed): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>Firmado el <?php echo date('d/m/Y H:i', strtotime($stub->signed_at)); ?>
        </div>
        <?php if (!empty($stub->signature_path) && !empty($data['signature_url'])): ?>
        <p class="small text-muted">Tu firma:</p>
        <img src="<?php echo htmlspecialchars($data['signature_url'], ENT_QUOTES); ?>" alt="Firma" class="pay-stub-signature-img">
        <?php endif; ?>
        <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Firma digital</div>
            <div class="card-body">
                <p class="small text-muted">Firmá en el recuadro con el mouse o el dedo.</p>
                <canvas id="signatureCanvas" class="pay-stub-signature-canvas"></canvas>
                <form method="post" action="<?php echo URLROOT; ?>/employee/signPayStub/<?php echo (int)$stub->id; ?>" id="signPayStubForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="signature_data" id="signatureData">
                    <div class="d-flex gap-2 mt-3">
                        <button type="button" class="btn btn-outline-secondary" id="clearSignature">Limpiar</button>
                        <button type="submit" class="btn btn-primary flex-grow-1" id="confirmSignature">Confirmar firma</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        <a href="<?php echo URLROOT; ?>/employee/payStubs" class="btn btn-link mt-3">← Volver a mis recibos</a>
    </div>
</div>

<div class="modal fade" id="payStubFullscreenModal" tabindex="-1" aria-labelledby="payStubFullscreenLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content pay-stub-fullscreen-modal">
            <div class="modal-header py-2">
                <h2 class="modal-title h6" id="payStubFullscreenLabel"><?php echo pay_stub_period_display($stub->period); ?></h2>
                <div class="d-flex gap-2 ms-auto me-2">
                    <a href="<?php echo htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download me-1"></i>Descargar
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center bg-dark bg-opacity-10" id="payStubFullscreenBody"></div>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script src="<?php echo URLROOT; ?>/js/pay-stub-sign.js"></script>
<script src="<?php echo URLROOT; ?>/js/pay-stub-view.js"></script>
