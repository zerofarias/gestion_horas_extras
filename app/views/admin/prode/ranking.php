<?php require APPROOT . '/views/inc/header.php'; ?>

<div class="admin-page-head">
    <div class="admin-page-brand">
        <div class="admin-page-icon">⚽</div>
        <div class="admin-page-meta">
            <h2 class="page-title">Copa del mundo 2026 — Ranking</h2>
            <p class="page-subtitle mb-0"><?php echo htmlspecialchars($edition->title ?? ''); ?> · <?php echo (int)$total_matches; ?> partidos</p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo URLROOT; ?>/prodeAdmin/matches" class="btn btn-outline-primary btn-sm">Resultados</a>
        <a href="<?php echo URLROOT; ?>/prodeAdmin/exportCsv?company_id=<?php echo (int)$company_id; ?>&area_id=<?php echo (int)$area_id; ?>"
           class="btn btn-success btn-sm"><i class="fas fa-file-csv me-1"></i> CSV</a>
    </div>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-md-4">
        <label class="form-label small mb-0">Empresa</label>
        <select name="company_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="0">Todas (holding)</option>
            <?php foreach ($companies as $co): ?>
            <option value="<?php echo (int)$co->id; ?>" <?php echo (int)$company_id === (int)$co->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($co->name); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!empty($areas)): ?>
    <div class="col-md-4">
        <label class="form-label small mb-0">Área</label>
        <select name="area_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="0">Todas</option>
            <?php foreach ($areas as $a): ?>
            <option value="<?php echo (int)$a->id; ?>" <?php echo (int)$area_id === (int)$a->id ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($a->name); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</form>

<div class="card border shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Empleado</th>
                    <th>Empresa</th>
                    <th>Área</th>
                    <th class="text-end">Puntos</th>
                    <th class="text-end">Exactos</th>
                    <th class="text-end">Resultado</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php $rank = 1; foreach ($rows as $row):
                $submitted = ($row->entry_status ?? '') === 'submitted';
            ?>
            <tr class="<?php echo !$submitted ? 'table-light' : ''; ?>">
                <td><?php echo $rank++; ?></td>
                <td><?php echo htmlspecialchars($row->full_name); ?></td>
                <td class="small text-muted"><?php echo htmlspecialchars($row->company_name ?? '—'); ?></td>
                <td class="small text-muted"><?php echo htmlspecialchars($row->area_name ?? '—'); ?></td>
                <td class="text-end fw-bold"><?php echo (int)$row->total_points; ?></td>
                <td class="text-end"><?php echo (int)$row->exact_hits; ?></td>
                <td class="text-end"><?php echo (int)$row->result_hits; ?></td>
                <td>
                    <?php if ($submitted): ?>
                    <span class="badge bg-success">Confirmado</span>
                    <?php else: ?>
                    <span class="badge bg-secondary"><?php echo (int)$row->predictions_count; ?>/<?php echo (int)$total_matches; ?></span>
                    <?php endif; ?>
                </td>
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
