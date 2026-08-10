<?php require APPROOT . '/views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/notifications.css">
<script src="<?php echo URLROOT; ?>/js/notifications-admin.js" defer></script>

<div class="mb-4">
    <a href="<?php echo URLROOT; ?>/notificationsAdmin/broadcasts" class="text-muted small"><i class="fas fa-arrow-left me-1"></i>Notificaciones</a>
    <h1 class="h4 mb-0 mt-1">Nueva notificación</h1>
</div>

<form method="post" action="<?php echo URLROOT; ?>/notificationsAdmin/broadcastForm">
    <?php echo csrf_field(); ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Título</label>
                        <input type="text" name="title" id="notifTitle" class="form-control" required placeholder="Hola &lt;nombre&gt;">
                    </div>
                    <?php require APPROOT . '/views/admin/notifications/partials/placeholders_help.php'; ?>
                    <div class="mb-3">
                        <label class="form-label">Mensaje</label>
                        <textarea name="body" id="notifBody" class="form-control" rows="5" placeholder="Hola &lt;nombre&gt;, nos alegra verte."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Enlace (opcional)</label>
                        <input type="url" name="link_url" class="form-control" placeholder="https://">
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="send_email" value="1" class="form-check-input" id="send_email">
                        <label for="send_email" class="form-check-label">Enviar también por correo</label>
                    </div>
                </div>
            </div>
            <?php
            $targeting = notification_admin_targeting_data();
            $companies = $targeting['companies'];
            $areas = $targeting['areas'];
            $users = $targeting['users'];
            $picker_users = $targeting['picker_users'];
            $selected = ['target_all' => false, 'company_ids' => [], 'area_ids' => [], 'user_ids' => []];
            require APPROOT . '/views/admin/notifications/partials/target_form.php';
            ?>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold">Vigencia (opcional)</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Desde</label>
                        <input type="datetime-local" name="starts_at" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasta</label>
                        <input type="datetime-local" name="ends_at" class="form-control">
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Enviar notificación</button>
        </div>
    </div>
</form>

<?php require APPROOT . '/views/inc/footer.php'; ?>
