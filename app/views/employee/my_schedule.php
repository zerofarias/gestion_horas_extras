<?php require APPROOT . '/views/inc/header.php'; ?>

<style>
    .calendar-container { max-width: 1200px; margin: auto; }
    .calendar-header { display: flex; justify-content: space-between; align-items: center; padding: 1rem; }
    .calendar-grid { display: grid; grid-template-columns: repeat(7, 1fr); border-top: 1px solid #dee2e6; border-left: 1px solid #dee2e6; }
    .calendar-day-name { font-weight: bold; text-align: center; padding: 0.5rem; background-color: #f8f9fa; border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6; }
    .calendar-day { min-height: 120px; padding: 0.5rem; border-right: 1px solid #dee2e6; border-bottom: 1px solid #dee2e6; background-color: #fff; }
    .calendar-day.other-month { background-color: #f8f9fa; }
    .day-number { font-weight: bold; }
    .schedule-block { padding: 4px 8px; margin-top: 5px; border-radius: 5px; text-align: left; color: #fff; font-weight: 500; font-size: 0.8em; }
    .schedule-block .block-notes { font-size: 0.9em; opacity: 0.8; display: block; }
    .request-block { border-left: 5px solid; padding: 4px 8px; margin-top: 5px; border-radius: 5px; text-align: left; font-weight: 500; font-size: 0.8em; }
</style>

<div class="calendar-container">
    <div class="card shadow">
        <div class="calendar-header">
            <a href="<?php echo URLROOT; ?>/employee/mySchedule?month=<?php echo $data['prev_month']; ?>" class="btn btn-outline-primary">&laquo; Mes Anterior</a>
            <h3 class="mb-0"><?php echo htmlspecialchars($data['month_name']) . ' ' . htmlspecialchars($data['year']); ?></h3>
            <a href="<?php echo URLROOT; ?>/employee/mySchedule?month=<?php echo $data['next_month']; ?>" class="btn btn-outline-primary">Mes Siguiente &raquo;</a>
        </div>
        <div class="calendar-grid">
            <?php $days_of_week = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo']; ?>
            <?php foreach($days_of_week as $day_name): ?>
                <div class="calendar-day-name"><?php echo $day_name; ?></div>
            <?php endforeach; ?>

            <?php
                $first_day_timestamp = strtotime($data['target_month'] . '-01');
                $start_day_of_week = date('N', $first_day_timestamp); // 1 (para Lunes) a 7 (para Domingo)
                $days_in_month = date('t', $first_day_timestamp);
                
                // Celdas vacías para los días antes del inicio del mes
                for ($i = 1; $i < $start_day_of_week; $i++) {
                    echo '<div class="calendar-day other-month"></div>';
                }

                // Celdas para cada día del mes
                for ($day = 1; $day <= $days_in_month; $day++) {
                    $current_date_str = $data['target_month'] . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $entries = isset($data['schedules'][$current_date_str]) ? $data['schedules'][$current_date_str] : [];
                    $request = isset($data['requests'][$current_date_str]) ? $data['requests'][$current_date_str] : null;

                    echo '<div class="calendar-day">';
                    echo '<div class="day-number">' . $day . '</div>';

                    if ($request) {
                        echo '<div class="request-block" style="border-left-color: ' . htmlspecialchars($request->color) . '; background-color: ' . htmlspecialchars($request->color) . '20; color: #333;"><strong>' . htmlspecialchars($request->type_name) . '</strong></div>';
                    }

                    if (empty($entries) && !$request) {
                        echo '<span class="text-muted fst-italic">Libre</span>';
                    } else {
                        foreach($entries as $entry) {
                            $color = '#6c757d'; $name_display = 'Personalizado';
                            if ($entry->type == 'shift' && !empty($entry->shift_name)) { $color = $entry->color; $name_display = $entry->shift_name; } 
                            elseif ($entry->type == 'overtime') { $color = '#ffc107'; $name_display = 'Horas Extras'; }
                            
                            echo '<div class="schedule-block" style="background-color: ' . htmlspecialchars($color) . ';">';
                            echo '<span>' . htmlspecialchars($name_display) . '</span>';
                            if(!empty($entry->notes)) {
                                echo '<small class="block-notes d-block">' . htmlspecialchars($entry->notes) . '</small>';
                            }
                            echo '</div>';
                        }
                    }
                    echo '</div>';
                }

                // Celdas vacías para los días después del fin del mes
                $total_cells = ($start_day_of_week - 1) + $days_in_month;
                $remaining_cells = (7 - ($total_cells % 7)) % 7;
                for ($i = 0; $i < $remaining_cells; $i++) {
                    echo '<div class="calendar-day other-month"></div>';
                }
            ?>
        </div>
    </div>
</div>

<?php require APPROOT . '/views/inc/footer.php'; ?>
