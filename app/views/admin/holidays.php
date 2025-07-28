<?php require APPROOT . '/views/inc/header.php'; ?>
<div class="row">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Feriados Cargados</h5></div>
            <div class="card-body">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Nombre</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach($data['holidays'] as $holiday): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($holiday->holiday_date)); ?></td>
                            <td><?php echo htmlspecialchars($holiday->name); ?></td>
                            <td><a href="<?php echo URLROOT; ?>/admin/deleteHoliday/<?php echo $holiday->id; ?>" class="btn btn-sm btn-danger">Eliminar</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow">
            <div class="card-header"><h5 class="mb-0">Añadir Feriado</h5></div>
            <div class="card-body">
                <form action="<?php echo URLROOT; ?>/admin/holidays" method="post">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Feriado</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="holiday_date" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar Feriado</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require APPROOT . '/views/inc/footer.php'; ?>
