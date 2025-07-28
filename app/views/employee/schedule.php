<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
    .schedule-container { max-width: 1200px; margin: auto; }
    .schedule-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem; }
    .week-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
    .day-card { border: 1px solid #dee2e6; border-radius: 0.5rem; background-color: #fff; }
    .day-card-header { padding: 0.75rem; background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; font-weight: bold; }
    .day-card-body { padding: 0.75rem; min-height: 100px; }
    .schedule-block { padding: 6px 10px; margin-bottom: 5px; border-radius: 5px; text-align: left; color: #fff; font-weight: 500; font-size: 0.9em; }
    .schedule-block .block-notes { font-size: 0.9em; opacity: 0.8; display: block; }
    .request-block { border-left: 5px solid; padding: 6px 10px; margin-bottom: 8px; border-radius: 5px; text-align: left; font-weight: 500; }
</style>

<div class="schedule-container">
    <div class="card shadow-sm">
        <div class="schedule-header">
            <?php // --- VERIFICACIÓN AÑADIDA ---
                  // Si 'week_dates' existe, muestra el enlace. Si no, no hace nada.
                  if (isset($data['week_dates']) && !empty($data['week_dates'])): ?>
                <a href="<?php echo URLROOT; ?>/employee/mySchedule?month=<?php echo date('Y-m', strtotime($data['week_dates'][0])); ?>" class="btn btn-outline-secondary"><i class="fas fa-calendar-alt me-2"></i>Ver Calendario Mensual</a>
            <?php endif; ?>
            <h3 class="mb-0">Mi Horario Semanal</h3>
            <div></div>
        </div>
        <div class="card-body">
            <?php 
            // --- VERIFICACIÓN PRINCIPAL ---
            // Comprobamos si los datos necesarios existen antes de intentar usarlos.
            if (!isset($data['week_dates']) || !is_array($data['week_dates'])): 
            ?>
                <div class="alert alert-warning text-center">No se pudo cargar la información de la semana.</div>
            <?php else: ?>
                <div class="week-grid">
                    <?php
                    $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                    $scheduleByDate = array();
                    if (!empty($data['schedule'])) {
                        foreach ($data['schedule'] as $entry) {
                            if (is_object($entry)) {
                                $scheduleByDate[$entry->schedule_date][] = $entry;
                            }
                        }
                    }

                    foreach ($data['week_dates'] as $index => $date_str) :
                        $entries = isset($scheduleByDate[$date_str]) ? $scheduleByDate[$date_str] : [];
                        $request = isset($data['requests'][$date_str]) ? $data['requests'][$date_str] : null;
                    ?>
                        <div class="day-card">
                            <div class="day-card-header">
                                <?php echo $dias_semana[$index]; ?>
                                <span class="float-end text-muted"><?php echo date('d/m', strtotime($date_str)); ?></span>
                            </div>
                            <div class="day-card-body">
                                <?php if ($request && $request->status == 'Aprobado'): ?>
                                    <div class="request-block" style="border-left-color: <?php echo htmlspecialchars($request->color); ?>; background-color: <?php echo htmlspecialchars($request->color) . '20'; ?>; color: #333;">
                                        <strong><?php echo htmlspecialchars($request->type_name); ?></strong>
                                    </div>
                                <?php endif; ?>

                                <?php if (empty($entries)): ?>
                                    <?php if (!$request): ?>
                                        <span class="text-muted fst-italic">Libre</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php foreach ($entries as $entry):
                                        $color = '#6c757d'; $name_display = 'Personalizado';
                                        if ($entry->type == 'shift' && !empty($entry->shift_name)) { $color = $entry->color; $name_display = $entry->shift_name; } 
                                        elseif ($entry->type == 'overtime') { $color = '#ffc107'; $name_display = 'Horas Extras'; }
                                    ?>
                                        <div class="schedule-block" style="background-color: <?php echo htmlspecialchars($color); ?>;">
                                            <span><?php echo htmlspecialchars($name_display); ?></span>
                                            <?php if(!empty($entry->notes)): ?><small class="block-notes d-block"><?php echo htmlspecialchars($entry->notes); ?></small><?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; // Fin de la verificación principal ?>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
