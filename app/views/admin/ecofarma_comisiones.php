<?php require APPROOT . '/views/inc/header.php';

$filters = $filters ?? [];
$resumen = $resumen ?? null;
$detalle = $detalle ?? null;
$sucursales = $sucursales ?? [];
$sucursalesError = $sucursales_error ?? null;
$defaultObra = $default_obra_social ?? '999900';
$defaultPct = $default_comision_pct ?? 7;

$fechaDesde = $filters['fecha_desde'] ?? date('Y-m-01');
$fechaHasta = $filters['fecha_hasta'] ?? date('Y-m-d');
$sucursalId = (int)($filters['sucursal_id'] ?? 0);
$idObra = $filters['id_obra_social'] ?? $defaultObra;
$pctComision = $filters['porcentaje_comision'] ?? (string)$defaultPct;
$vista = $filters['vista'] ?? 'resumen';
$hasQuery = $sucursalId > 0 && $fechaDesde !== '' && $fechaHasta !== '';

$baseQs = http_build_query([
    'fecha_desde' => $fechaDesde,
    'fecha_hasta' => $fechaHasta,
    'sucursal_id' => $sucursalId,
    'id_obra_social' => $idObra,
    'porcentaje_comision' => $pctComision,
]);
$qsResumen = $baseQs . '&vista=resumen';
$qsDetalle = $baseQs . '&vista=detalle';

$fmtMoney = function ($n) {
    return '$ ' . number_format((float)$n, 2, ',', '.');
};
?>

<div class="admin-page-head mb-4">
    <div class="admin-page-brand">
        <div class="admin-page-icon" style="background:#e8f5e9;color:#2e7d32;">
            <i class="fas fa-pills"></i>
        </div>
        <div class="admin-page-meta">
            <h2 class="page-title mb-0">Comisiones Ecofarma</h2>
            <p class="page-subtitle mb-0">Resumen por operador · facturación ACOS</p>
        </div>
    </div>
</div>

<?php if ($sucursalesError): ?>
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle me-1"></i>
    <?php echo htmlspecialchars($sucursalesError); ?>
    Verificá conexión a la API y credenciales en <code>config.php</code>.
</div>
<?php endif; ?>

<div class="card border shadow-sm mb-4">
    <div class="card-header"><strong><i class="fas fa-filter me-1"></i>Consulta</strong></div>
    <div class="card-body">
        <form method="post" action="<?php echo URLROOT; ?>/ecofarma/index" id="ecofarmaConsultForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="vista" value="<?php echo htmlspecialchars($vista); ?>">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Farmacia / sucursal</label>
                    <select name="sucursal_id" class="form-select" required>
                        <option value="">Seleccione farmacia…</option>
                        <?php foreach ($sucursales as $s):
                            $sid = (int)($s['id'] ?? 0);
                            $nombre = $s['nombre_fantasia'] ?? '';
                        ?>
                        <option value="<?php echo $sid; ?>" <?php echo $sucursalId === $sid ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nombre); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Obra social</label>
                    <input type="text" name="id_obra_social" class="form-control" value="<?php echo htmlspecialchars($idObra); ?>">
                    <small class="text-muted">ACOS: <?php echo htmlspecialchars($defaultObra); ?></small>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Comisión %</label>
                    <input type="number" name="porcentaje_comision" class="form-control" min="0" max="100" step="0.01"
                           value="<?php echo htmlspecialchars($pctComision); ?>">
                    <small class="text-muted">Sobre total ventas</small>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Desde</label>
                    <input type="date" name="fecha_desde" class="form-control" required value="<?php echo htmlspecialchars($fechaDesde); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Hasta</label>
                    <input type="date" name="fecha_hasta" class="form-control" required value="<?php echo htmlspecialchars($fechaHasta); ?>">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary w-100" id="btnEcofarmaConsult">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($resumen !== null): ?>
    <?php if (!$resumen['ok']): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($resumen['error'] ?? 'Error en la consulta.'); ?></div>
    <?php else:
        $meta = $resumen['meta'] ?? [];
        $operadores = $resumen['operadores'] ?? [];
        $totales = $resumen['totales'] ?? [];
        $pctLabel = (float)($meta['porcentaje_comision'] ?? $pctComision);
    ?>

    <?php if ($hasQuery): ?>
    <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
        <div class="btn-group btn-group-sm" role="group">
            <a href="<?php echo URLROOT; ?>/ecofarma/index?<?php echo htmlspecialchars($qsResumen); ?>"
               class="btn <?php echo $vista === 'resumen' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-users me-1"></i>Resumen por operador
            </a>
            <a href="<?php echo URLROOT; ?>/ecofarma/index?<?php echo htmlspecialchars($qsDetalle); ?>"
               class="btn <?php echo $vista === 'detalle' ? 'btn-primary' : 'btn-outline-primary'; ?>"
               id="btnVistaDetalle">
                <i class="fas fa-list me-1"></i>Detalle por líneas
            </a>
        </div>
        <span class="text-muted small ms-auto">
            <?php echo htmlspecialchars($meta['sucursal_filtro'] ?? ''); ?>
            · <?php echo htmlspecialchars($fechaDesde); ?> → <?php echo htmlspecialchars($fechaHasta); ?>
        </span>
    </div>
    <?php endif; ?>

    <?php if ($vista === 'resumen'): ?>
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="admin-kpi-card">
                <div class="admin-kpi-value"><?php echo (int)($meta['total_operadores'] ?? count($operadores)); ?></div>
                <div class="admin-kpi-label">Operadores</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="admin-kpi-card">
                <div class="admin-kpi-value"><?php echo (int)($totales['cantidad_lineas'] ?? 0); ?></div>
                <div class="admin-kpi-label">Líneas ACOS</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="admin-kpi-card">
                <div class="admin-kpi-value text-primary"><?php echo $fmtMoney($totales['total_ventas'] ?? 0); ?></div>
                <div class="admin-kpi-label">Total ventas</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="admin-kpi-card border-success">
                <div class="admin-kpi-value text-success"><?php echo $fmtMoney($totales['comision'] ?? 0); ?></div>
                <div class="admin-kpi-label">Comisión total (<?php echo $pctLabel; ?> %)</div>
            </div>
        </div>
    </div>

    <div class="card border shadow-sm">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong><i class="fas fa-hand-holding-usd me-1"></i>Comisiones por empleado</strong>
            <a href="<?php echo URLROOT; ?>/ecofarma/exportResumen?<?php echo htmlspecialchars($baseQs); ?>"
               class="btn btn-success btn-sm">
                <i class="fas fa-file-excel me-1"></i>Descargar Excel
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($operadores)): ?>
            <p class="p-4 text-muted mb-0">Sin ventas ACOS para el período seleccionado.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="ecofarma-resumen-table" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th>Operador</th>
                            <th class="text-end">Líneas</th>
                            <th class="text-end">Total ventas</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">Descuento</th>
                            <th class="text-end">% com.</th>
                            <th class="text-end">Comisión</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($operadores as $op): ?>
                    <tr>
                        <td class="fw-semibold"><?php echo htmlspecialchars($op['operador'] ?? ''); ?></td>
                        <td class="text-end"><?php echo (int)($op['cantidad_lineas'] ?? 0); ?></td>
                        <td class="text-end"><?php echo $fmtMoney($op['total_ventas'] ?? 0); ?></td>
                        <td class="text-end text-muted"><?php echo $fmtMoney($op['subtotal_ventas'] ?? 0); ?></td>
                        <td class="text-end text-muted"><?php echo $fmtMoney($op['total_descuento'] ?? 0); ?></td>
                        <td class="text-end"><?php echo number_format((float)($op['porcentaje_comision'] ?? $pctLabel), 1, ',', '.'); ?> %</td>
                        <td class="text-end fw-bold text-success"><?php echo $fmtMoney($op['comision'] ?? 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>TOTAL</td>
                            <td class="text-end"><?php echo (int)($totales['cantidad_lineas'] ?? 0); ?></td>
                            <td class="text-end"><?php echo $fmtMoney($totales['total_ventas'] ?? 0); ?></td>
                            <td colspan="2"></td>
                            <td class="text-end"><?php echo $pctLabel; ?> %</td>
                            <td class="text-end text-success"><?php echo $fmtMoney($totales['comision'] ?? 0); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php else: /* vista detalle */ ?>
        <?php if ($detalle === null || !($detalle['ok'] ?? false)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($detalle['error'] ?? 'Error al cargar el detalle.'); ?></div>
        <?php else:
            $dMeta = $detalle['meta'] ?? [];
            $items = $detalle['items'] ?? [];
            $totalLineas = (int)($dMeta['total'] ?? count($items));
        ?>
        <div class="card border shadow-sm">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <strong><i class="fas fa-list me-1"></i>Detalle por líneas</strong>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary"><?php echo $totalLineas; ?> líneas</span>
                    <a href="<?php echo URLROOT; ?>/ecofarma/exportExcel?<?php echo htmlspecialchars($baseQs); ?>"
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i>Descargar Excel
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($items)): ?>
                <p class="p-4 text-muted mb-0">Sin líneas para el período.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" id="ecofarma-facturacion-table" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>Emisión</th>
                                <th>Tipo</th>
                                <th>Comprobante</th>
                                <th>Operador</th>
                                <th>Producto</th>
                                <th class="text-end">Cant.</th>
                                <th class="text-end">Desc. %</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Importe ACOS</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $row): ?>
                        <tr>
                            <td class="text-nowrap"><?php echo htmlspecialchars($row['Emision'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['Tipo'] ?? ''); ?></td>
                            <td class="text-nowrap small"><?php echo htmlspecialchars($row['comprobante'] ?? ''); ?></td>
                            <td class="small"><?php echo htmlspecialchars($row['Operador'] ?? ''); ?></td>
                            <td class="small" style="max-width:240px;" title="<?php echo htmlspecialchars($row['Producto'] ?? ''); ?>">
                                <?php echo htmlspecialchars($row['Producto'] ?? ''); ?>
                            </td>
                            <td class="text-end"><?php echo isset($row['CantDecimal']) ? number_format((float)$row['CantDecimal'], 2, ',', '.') : ''; ?></td>
                            <td class="text-end"><?php echo isset($row['DesPorcentaje']) ? number_format((float)$row['DesPorcentaje'], 0, ',', '.') . ' %' : ''; ?></td>
                            <td class="text-end fw-semibold"><?php echo isset($row['Total']) ? $fmtMoney($row['Total']) : ''; ?></td>
                            <td class="text-end"><?php echo isset($row['ImporteACOS']) ? $fmtMoney($row['ImporteACOS']) : ''; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<div class="alert alert-light border small mt-3 mb-0">
    <strong>Notas</strong>
    <ul class="mb-0 mt-1">
        <li>Vista principal: <strong>resumen por operador</strong> con comisión sobre <code>total_ventas</code> (NC restan).</li>
        <li><strong>Detalle por líneas</strong> consulta el endpoint completo (más lento en rangos grandes).</li>
        <li>Obra social por defecto ACOS <code><?php echo htmlspecialchars($defaultObra); ?></code>; comisión default <?php echo (float)$defaultPct; ?> %.</li>
    </ul>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>

<script>
$(function () {
    $('#ecofarmaConsultForm').on('submit', function () {
        $('#btnEcofarmaConsult').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    });

    var dtOpts = {
        language: window.DATATABLES_LANG_ES || { url: '<?php echo URLROOT; ?>/js/datatables-es-ES.json' },
        pageLength: 25,
        dom: '<"row mb-2"<"col-sm-12 col-md-6"B><"col-sm-12 col-md-6"f>>rtip',
        buttons: [
            { extend: 'excelHtml5', className: 'btn btn-success btn-sm', text: '<i class="fas fa-file-excel"></i> Excel' },
            { extend: 'csvHtml5', className: 'btn btn-primary btn-sm', text: '<i class="fas fa-file-csv"></i> CSV' },
            { extend: 'print', className: 'btn btn-info btn-sm', text: '<i class="fas fa-print"></i> Imprimir' }
        ]
    };

    if ($('#ecofarma-resumen-table').length && !$.fn.dataTable.isDataTable('#ecofarma-resumen-table')) {
        $('#ecofarma-resumen-table').DataTable($.extend({}, dtOpts, {
            order: [[6, 'desc']],
            columnDefs: [{ targets: [1,2,3,4,5,6], className: 'text-end' }]
        }));
    }
    if ($('#ecofarma-facturacion-table').length && !$.fn.dataTable.isDataTable('#ecofarma-facturacion-table')) {
        $('#ecofarma-facturacion-table').DataTable($.extend({}, dtOpts, {
            pageLength: 50,
            order: [[0, 'desc']]
        }));
    }
});
</script>
