<?php
// ----------------------------------------------------------------------
// ARCHIVO 3: app/views/admin/sync_results.php (ACTUALIZADO)
// Se mejora la vista de resultados para que sea más clara y muestre
// un resumen de lo que se va a importar.
// ----------------------------------------------------------------------

require APPROOT . '/views/inc/header.php'; 

$response_data = json_decode($data['rawResponse'], true);
$events = isset($response_data['AcsEvent']['InfoList']) ? $response_data['AcsEvent']['InfoList'] : [];

// Procesar los datos para el resumen
$summary = [];
if (is_array($events)) {
    foreach ($events as $event) {
        if (isset($event['employeeNoString'])) {
            $clockId = $event['employeeNoString'];
            $dateTime = new DateTime($event['time']);
            $date = $dateTime->format('Y-m-d');
            if (!isset($summary[$date])) { $summary[$date] = []; }
            if (!isset($summary[$date][$clockId])) { $summary[$date][$clockId] = 0; }
            $summary[$date][$clockId]++;
        }
    }
}
?>

<div class="card shadow">
    <div class="card-header">
        <h4 class="mb-0">
            <a href="<?php echo URLROOT; ?>/admin/sync" class="btn btn-light me-2" title="Volver"><i class="fas fa-arrow-left"></i></a>
            Resultados de Sincronización
        </h4>
    </div>
    <div class="card-body">
        <?php if ($data['httpCode'] == 200 && $data['rawResponse']): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>Conexión exitosa. Se encontraron <strong><?php echo count($events); ?></strong> eventos de fichaje.
            </div>

            <h5>Resumen de Marcaciones por Día y Empleado:</h5>
            <p>Se importarán todos los registros. Aquellos con un número impar de marcaciones quedarán marcados como incompletos para su posterior revisión.</p>
            
            <div class="table-responsive mb-4" style="max-height: 250px;">
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>Fecha</th><th>ID Reloj</th><th>Nº de Marcaciones</th><th>Estado</th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($summary)): ?>
                            <tr><td colspan="4" class="text-center">No se encontraron marcaciones en el rango seleccionado.</td></tr>
                        <?php else: ?>
                            <?php foreach($summary as $date => $employees): ?>
                                <?php foreach($employees as $clockId => $count): ?>
                                    <?php $isPair = ($count % 2 == 0 && $count > 0); ?>
                                    <tr class="<?php echo $isPair ? 'table-success' : 'table-warning'; ?>">
                                        <td><?php echo date('d/m/Y', strtotime($date)); ?></td>
                                        <td><?php echo $clockId; ?></td>
                                        <td><?php echo $count; ?></td>
                                        <td><?php echo $isPair ? 'Completo (se importará)' : 'Incompleto (se importará para revisión)'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <form action="<?php echo URLROOT; ?>/admin/processSync" method="post" class="mt-4">
                <input type="hidden" name="raw_response" value='<?php echo htmlspecialchars($data['rawResponse'], ENT_QUOTES, 'UTF-8'); ?>'>
                <!-- INICIO DE CAMBIO: Se añaden las fechas al formulario -->
                <input type="hidden" name="start_date" value="<?php echo $data['startDate']; ?>">
                <input type="hidden" name="end_date" value="<?php echo $data['endDate']; ?>">
                <!-- FIN DE CAMBIO -->
                <button type="submit" class="btn btn-success btn-lg w-100" <?php echo empty($events) ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-double me-2"></i>Confirmar e Importar Horarios Válidos
                </button>
            </form>

        <?php else: ?>
            <div class="alert alert-danger">
                <!-- ... (código de error sin cambios) ... -->
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>