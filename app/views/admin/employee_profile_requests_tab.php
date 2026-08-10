<?php
$requests = $data['requests'] ?? [];
$salaryAdvances = $data['salary_advances'] ?? [];
$salaryAdvancesReady = !empty($data['salary_advances_ready']);
$userId = (int)($data['user']->id ?? 0);
?>
<div class="tab-pane fade" id="tab-req">
    <h6 class="fw-semibold mb-2">Licencias / ausencias</h6>
    <div class="table-responsive border rounded mb-4">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light"><tr><th>Tipo</th><th>Desde</th><th>Hasta</th><th>Estado</th></tr></thead>
            <tbody>
            <?php if (empty($requests)): ?>
                <tr><td colspan="4" class="text-muted text-center py-3">Sin solicitudes de licencia.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request->type_name); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($request->start_date)); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($request->end_date)); ?></td>
                    <td><?php echo htmlspecialchars($request->status); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h6 class="fw-semibold mb-0">Historial de adelantos de sueldo</h6>
        <?php if ($salaryAdvancesReady && function_exists('salary_advance_module_enabled') && salary_advance_module_enabled() && hasRole('admin')): ?>
        <a href="<?php echo URLROOT; ?>/salaryAdvanceAdmin/index?user_id=<?php echo $userId; ?>" class="btn btn-outline-primary btn-sm">
            Ver en adelantos
        </a>
        <?php endif; ?>
    </div>

    <?php if (!$salaryAdvancesReady): ?>
    <div class="alert alert-warning small mb-0">
        Ejecutá <code>migration_salary_advances.sql</code> (ver MIGRATIONS.md #38).
    </div>
    <?php elseif (empty($salaryAdvances)): ?>
    <div class="text-center py-4 text-muted border rounded">Sin adelantos registrados.</div>
    <?php else: ?>
    <div class="table-responsive border rounded">
        <table class="table table-sm mb-0">
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
            <?php foreach ($salaryAdvances as $adv): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($adv->created_at)); ?></td>
                    <td><?php echo salary_advance_format_money($adv->amount); ?></td>
                    <td>
                        <?php echo (int)$adv->installments_requested; ?>
                        <?php if (in_array($adv->status, ['Aprobado', 'Finalizado'], true) && $adv->installments_approved !== null): ?>
                            <span class="text-muted small">(aprob.: <?php echo (int)$adv->installments_approved; ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo salary_advance_status_badge_class($adv->status); ?>"><?php echo htmlspecialchars($adv->status); ?></span></td>
                    <td class="small text-muted"><?php echo htmlspecialchars($adv->admin_notes ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
