<?php

function overtime_status_label($status) {
    switch ($status) {
        case 'pending':
            return 'Pendiente de cierre';
        case 'archived':
            return 'Cerrada';
        default:
            return ucfirst((string)$status);
    }
}

function overtime_status_badge_class($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning text-dark';
        case 'archived':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

function overtime_month_label_es($year, $month) {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];
    $m = (int)$month;
    return ($meses[$m] ?? $m) . ' ' . (int)$year;
}

function overtime_entry_total_hours($entry) {
    return round((float)$entry->hours_50 + (float)$entry->hours_100, 1);
}

function overtime_format_entry_for_js($entry) {
    return [
        'id' => (int)$entry->id,
        'date' => date('d/m/Y', strtotime($entry->entry_date)),
        'time' => date('H:i', strtotime($entry->start_time)) . ' – ' . date('H:i', strtotime($entry->end_time)),
        'hours' => overtime_entry_total_hours($entry),
        'hours_50' => round((float)$entry->hours_50, 1),
        'hours_100' => round((float)$entry->hours_100, 1),
        'is_holiday' => (int)$entry->is_holiday === 1,
        'reason' => $entry->reason,
        'status' => $entry->status,
        'status_label' => overtime_status_label($entry->status),
    ];
}
