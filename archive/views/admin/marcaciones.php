<?php require APPROOT . '/views/inc/header.php'; ?>

<?php $clockMap = $GLOBALS['CLOCK_DEVICE_MAP'] ?? []; ?>

<div class="d-flex align-items-center flex-wrap gap-3 mb-4">
    <div style="background:var(--clr-primary-l);border-radius:1rem;padding:.75rem 1rem;">
        <i class="fas fa-fingerprint fa-lg" style="color:var(--clr-primary);"></i>
    </div>
    <div>
        <h2 class="page-title mb-0">Marcaciones</h2>
        <p class="page-subtitle mb-0">Registros importados en la base de datos local</p>
    </div>
    <a href="<?php echo URLROOT; ?>/admin/sync" class="btn btn-outline-primary ms-auto">
        <i class="fas fa-sync-alt me-1"></i> Sincronizar
    </a>
</div>

<!-- ── FILTROS ── -->
<div class="card shadow mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?php echo URLROOT; ?>/admin/marcaciones"
              class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small fw-semibold">Desde</label>
                <input type="date" name="start_date" class="form-control form-control-sm"
                       value="<?php echo htmlspecialchars($data['filters']['start_date']); ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label mb-1 small fw-semibold">Hasta</label>
                <input type="date" name="end_date" class="form-control form-control-sm"
                       value="<?php echo htmlspecialchars($data['filters']['end_date']); ?>">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label mb-1 small fw-semibold">Empleado</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Todos</option>
                    <?php foreach ($data['users'] as $u): ?>
                        <option value="<?php echo $u->id; ?>"
                            <?php echo ($data['filters']['user_id'] == $u->id) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($u->full_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label mb-1 small fw-semibold">Reloj</label>
                <select name="device_name" class="form-select form-select-sm">
                    <option value="">Todos los relojes</option>
                    <?php foreach ($data['devices'] as $dev): ?>
                        <?php if (!$dev->device_name) continue; ?>
                        <?php $cfg = $clockMap[$dev->device_name] ?? null; ?>
                        <option value="<?php echo htmlspecialchars($dev->device_name); ?>"
                            <?php echo ($data['filters']['device_name'] === $dev->device_name) ? 'selected' : ''; ?>>
                            <?php echo $cfg ? $cfg['label'] : htmlspecialchars($dev->device_name); ?>
                        </option>
                    <?php endforeach; ?>
                    <!-- Opciones fijas para los 3 relojes conocidos aunque aún no tengan datos -->
                    <?php
                    $knownDevices = array_column($data['devices'], 'device_name');
                    foreach ($clockMap as $devKey => $cfg):
                        if (in_array($devKey, $knownDevices)) continue;
                    ?>
                        <option value="<?php echo htmlspecialchars($devKey); ?>"
                            <?php echo ($data['filters']['device_name'] === $devKey) ? 'selected' : ''; ?>>
                            <?php echo $cfg['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                    <i class="fas fa-filter me-1"></i>Filtrar
                </button>
                <a href="<?php echo URLROOT; ?>/admin/marcaciones" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ── TABLA ── -->
<div class="card shadow">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="fas fa-table" style="color:var(--clr-primary);"></i>
        <h5 class="mb-0">Resultados</h5>
        <span class="badge ms-auto" style="background:var(--clr-primary);">
            <?php echo count($data['marcaciones']); ?> registros
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tbl-marcaciones" class="table table-hover mb-0" style="width:100%">
                <thead>
                    <tr>
                        <th>Empleado</th>
                        <th>Reloj</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Tipo</th>
                        <th>ID en Reloj</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['marcaciones'])): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block opacity-25"></i>
                            Sin marcaciones para los filtros seleccionados.<br>
                            <a href="<?php echo URLROOT; ?>/admin/sync" style="color:var(--clr-primary);">
                                Sincronizar desde API →
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($data['marcaciones'] as $m): ?>
                    <tr>
                        <td>
                            <a href="<?php echo URLROOT; ?>/admin/employeeProfile/<?php echo $m->user_id; ?>"
                               style="color:var(--clr-primary);font-weight:600;">
                                <?php echo htmlspecialchars($m->full_name); ?>
                            </a>
                        </td>
                        <td><?php echo marcClockBadge($m->device_name, $clockMap); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($m->event_time)); ?></td>
                        <td><strong><?php echo date('H:i:s', strtotime($m->event_time)); ?></strong></td>
                        <td><?php echo marcDirectionBadge($m->direction ?? null, $m->direction_label ?? null); ?></td>
                        <td><code class="small"><?php echo htmlspecialchars($m->clock_id ?? '—'); ?></code></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    $('#tbl-marcaciones').DataTable({
        pageLength: 50,
        order: [[2,'desc'],[3,'desc']],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        columnDefs: [{ targets: [1,4,5], orderable: false }]
    });
});
</script>

<?php require APPROOT . '/views/inc/footer.php'; ?>
