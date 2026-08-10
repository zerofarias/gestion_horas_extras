<?php

/** Columnas hire_date en users (fechas de empleo en alta/edición). */
function employment_fields_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    $ready = (new User())->isVacationProfileReady();
    return $ready;
}

function vacation_module_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE 'vacation_balance_periods'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function vacation_day_count_modes() {
    return [
        'weekdays' => 'Días hábiles (lun–vie)',
        'calendar' => 'Días corridos',
    ];
}

function vacation_count_days_in_range($startDate, $endDate, $mode) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate ?: $startDate);
    if ($end < $start) {
        return 0;
    }
    $count = 0;
    while ($start <= $end) {
        if ($mode === 'calendar' || (int)$start->format('N') < 6) {
            $count++;
        }
        $start->modify('+1 day');
    }
    return $count;
}

function vacation_schedule_entry($notes = '') {
    return [
        'type' => 'vacation',
        'shift_id' => null,
        'start_time' => null,
        'end_time' => null,
        'notes' => $notes !== '' ? $notes : 'Vacaciones',
    ];
}

function vacation_leave_schedule_entry($notes = '') {
    return [
        'type' => 'leave',
        'shift_id' => null,
        'start_time' => null,
        'end_time' => null,
        'notes' => $notes !== '' ? $notes : 'Licencia',
    ];
}

function vacation_planner_valid_types() {
    return ['shift', 'custom', 'overtime', 'vacation', 'leave'];
}

/** Etiqueta período Oct–Sep: 2024-2025 */
function vacation_period_label_from_start($periodStartDate) {
    $y = (int)date('Y', strtotime($periodStartDate));
    return $y . '-' . ($y + 1);
}

function vacation_period_bounds($year, $startMonth = 10, $startDay = 1) {
    $startMonth = max(1, min(12, (int)$startMonth));
    $startDay = max(1, min(28, (int)$startDay));
    $periodStart = sprintf('%04d-%02d-%02d', (int)$year, $startMonth, $startDay);
    $endTs = strtotime($periodStart . ' +1 year -1 day');
    return [
        'period_start' => $periodStart,
        'period_end' => date('Y-m-d', $endTs),
        'period_label' => vacation_period_label_from_start($periodStart),
    ];
}

/** Período que contiene una fecha de referencia. */
function vacation_period_for_date($referenceDate, $startMonth = 10, $startDay = 1) {
    $ref = new DateTime($referenceDate);
    $y = (int)$ref->format('Y');
    $bounds = vacation_period_bounds($y, $startMonth, $startDay);
    $ps = new DateTime($bounds['period_start']);
    if ($ref < $ps) {
        $bounds = vacation_period_bounds($y - 1, $startMonth, $startDay);
    }
    return $bounds;
}

function vacation_count_vacation_days_in_entries(array $entries) {
    $n = 0;
    foreach ($entries as $e) {
        $type = is_array($e) ? ($e['type'] ?? '') : ($e->type ?? '');
        if ($type === 'vacation') {
            $n++;
        }
    }
    return $n;
}

function vacation_format_days($days) {
    $d = (float)$days;
    return (fmod($d, 1.0) === 0.0) ? (string)(int)$d : number_format($d, 1, ',', '.');
}

/** Días pendientes de un período (BD o calculado desde correspondían − tomados). */
function vacation_period_pending($period) {
    if (is_array($period)) {
        $period = (object)$period;
    }
    if (isset($period->days_pending) && $period->days_pending !== null && $period->days_pending !== '') {
        return (float)$period->days_pending;
    }
    return max(0, (float)($period->days_entitled ?? 0) - (float)($period->days_taken ?? 0));
}
