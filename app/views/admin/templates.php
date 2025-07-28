<?php require APPROOT . '/views/inc/header.php'; ?>
<div class="row">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Plantillas Semanales Guardadas</h5></div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Nombre de la Plantilla</th><th>Fecha de Creación</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($data['templates'] as $template): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($template->template_name); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($template->created_at)); ?></td>
                            <td><a href="<?php echo URLROOT; ?>/admin/deleteTemplate/<?php echo $template->id; ?>" class="btn btn-sm btn-danger">Eliminar</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Crear Plantilla desde Semana</h5></div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/templates" method="post">
                    <div class="mb-3">
                        <label class="form-label">Nombre de la Nueva Plantilla</label>
                        <input type="text" name="template_name" class="form-control" placeholder="Ej: Rotativo Mañana" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Copiar Horarios de la Semana</label>
                        <input type="week" name="source_week" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Plantilla</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
