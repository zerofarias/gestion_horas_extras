<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="admin-page-head">
    <div class="admin-page-brand">
        <div class="admin-page-icon"><i class="fas fa-star text-warning"></i></div>
        <div class="admin-page-meta">
            <h2 class="page-title">Ranking reconocimiento entre pares</h2>
            <p class="page-subtitle mb-0">Puntos anónimos entre compañeros (no incluye estrellas de cursos)</p>
        </div>
    </div>
    <a href="<?php echo URLROOT; ?>/peerStarAdmin/exportCsv?area_id=<?php echo (int)$data['area_id']; ?>"
       class="btn btn-success btn-sm"><i class="fas fa-file-csv me-1"></i> Exportar CSV</a>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <label class="form-label small mb-0">Área</label>
        <select name="area_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="0">Todas las áreas</option>
            <?php foreach ($data['areas'] as $a): ?>
            <option value="<?php echo (int)$a->id; ?>" <?php echo (int)$data['area_id'] === (int)$a->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($a->name); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="card border shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Empleado</th>
                    <th>Área</th>
                    <th class="text-end">Puntos</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php $rank = 1; foreach ($data['rows'] as $row): ?>
            <tr>
                <td><?php echo $rank++; ?></td>
                <td><?php echo htmlspecialchars($row->full_name); ?></td>
                <td class="text-muted small"><?php echo htmlspecialchars($row->area_name ?? '—'); ?></td>
                <td class="text-end fw-semibold"><?php echo (int)$row->total_score; ?></td>
                <td class="text-end">
                    <a href="<?php echo URLROOT; ?>/admin/employeeProfile/<?php echo (int)$row->id; ?>" class="btn btn-sm btn-outline-primary">Ficha</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
