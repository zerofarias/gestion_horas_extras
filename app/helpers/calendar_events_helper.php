<?php

/**
 * Eventos FullCalendar para ficha empleado: turno planificado, marcaciones, ausencias, licencias.
 * Cada tipo usa classNames distintas (ver CSS .fc-ev-*).
 */
function buildEmployeeProfileCalendarEvents($userId, $companyId, $startDate, $endDate) {
    $userId = (int)$userId;
    $companyId = (int)$companyId;
    $events = [];

    $workSchedule = new WorkSchedule();
    $scheduleModel = new Schedule();
    $requestModel = new Request();
    $justModel = new AttendanceJustification();
    $attSummaryModel = new AttendanceDaySummary();

    $justMap = $justModel->getForUserPeriod($userId, $startDate, $endDate);

    $attendanceMap = [];
    if ($attSummaryModel->isSchemaReady()) {
        try {
            $db = new Database();
            $db->query('SELECT * FROM attendance_day_summary
                WHERE user_id = :user_id AND work_date BETWEEN :start_date AND :end_date');
            $db->bind(':user_id', $userId);
            $db->bind(':start_date', $startDate);
            $db->bind(':end_date', $endDate);
            foreach ($db->resultSet() as $row) {
                $attendanceMap[$row->work_date] = $row;
            }
        } catch (Throwable $e) {
            $attendanceMap = [];
        }
    }

    // —— Licencias / permisos aprobados (solo color de fondo del día, sin texto) ——
    foreach ($requestModel->getRequestsByUserId($userId) as $req) {
        if ($req->status !== 'Aprobado') {
            continue;
        }
        $reqStart = $req->start_date;
        $reqEnd = $req->end_date ?: $req->start_date;
        if ($reqEnd < $startDate || $reqStart > $endDate) {
            continue;
        }
        $events[] = [
            'title' => '',
            'start' => max($reqStart, $startDate),
            'end'   => date('Y-m-d', strtotime(min($reqEnd, $endDate) . ' +1 day')),
            'display' => 'background',
            'backgroundColor' => !empty($req->color) ? $req->color : '#a78bfa',
            'classNames' => ['fc-ev-license'],
            'extendedProps' => ['hint' => $req->type_name],
            'order' => 1,
        ];
    }

    // —— Cambios de turno aprobados (fondo del día, sin texto) ——
    $swapModel = new ShiftSwap();
    if ($swapModel->isSchemaReady()) {
        $swapDaysAdded = [];
        foreach ($swapModel->getSwapsByUserId($userId) as $sw) {
            if (($sw->status ?? '') !== 'Aprobado') {
                continue;
            }
            $dates = array_filter([
                $sw->proposer_date ?? null,
                $sw->accepter_date ?? null,
            ]);
            foreach ($dates as $d) {
                if ($d < $startDate || $d > $endDate || isset($swapDaysAdded[$d])) {
                    continue;
                }
                $swapDaysAdded[$d] = true;
                $events[] = [
                    'title' => '',
                    'start' => $d,
                    'end'   => date('Y-m-d', strtotime($d . ' +1 day')),
                    'display' => 'background',
                    'backgroundColor' => '#fbbf24',
                    'classNames' => ['fc-ev-swap'],
                    'extendedProps' => ['hint' => 'Cambio de turno aprobado'],
                    'order' => 1,
                ];
            }
        }
    }

    // —— Turnos planificados (todo el día en grilla mensual, título corto sin hora duplicada) ——
    foreach ($workSchedule->getUpcomingSchedulesForUser($userId, $companyId, $startDate, $endDate) as $entry) {
        if (($entry->type ?? '') === 'overtime'
            && function_exists('calendar_should_hide_overtime')
            && calendar_should_hide_overtime($companyId, $userId)) {
            continue;
        }
        $date = $entry->schedule_date;
        if ($entry->type === 'shift' && !empty($entry->shift_name)) {
            $name = $entry->shift_name;
        } elseif ($entry->type === 'overtime') {
            $name = 'Horas extra';
        } else {
            $name = 'Turno';
        }
        $st = $entry->start_time ?? null;
        $et = $entry->end_time ?? null;
        $label = $name;
        if ($st && $et) {
            $label = attendanceFormatTime($st) . '–' . attendanceFormatTime($et) . ' · ' . $name;
        }
        $events[] = [
            'title' => $label,
            'start' => $date,
            'allDay' => true,
            'classNames' => ['fc-ev-planned'],
            'extendedProps' => ['kind' => 'planned'],
            'order' => 2,
        ];
    }

    // —— Justificaciones (día completo, distinto de ausencia sin justificar) ——
    foreach ($justMap as $date => $just) {
        $events[] = [
            'title' => 'Justif.: ' . attendanceJustificationTypeLabel($just->justification_type),
            'start' => $date,
            'allDay' => true,
            'classNames' => ['fc-ev-justified'],
            'order' => 3,
        ];
    }

    // —— Resumen asistencia: ausente / alertas (si no hay justificación ese día) ——
    foreach ($attendanceMap as $date => $att) {
        if (isset($justMap[$date])) {
            continue;
        }
        $meta = attendanceStatusMeta($att->status);
        if ($att->status === 'no_show') {
            $events[] = [
                'title' => 'Ausente',
                'start' => $date,
                'allDay' => true,
                'classNames' => ['fc-ev-absence'],
                'order' => 4,
            ];
        } elseif (in_array($att->status, ['late', 'early_leave', 'missing_out', 'incomplete'], true)) {
            $events[] = [
                'title' => $meta['label'],
                'start' => $date,
                'allDay' => true,
                'classNames' => ['fc-ev-alert'],
                'order' => 4,
            ];
        } elseif ($att->status === 'unplanned_clocking') {
            $events[] = [
                'title' => 'Marca sin turno planificado',
                'start' => $date,
                'allDay' => true,
                'classNames' => ['fc-ev-unplanned'],
                'order' => 4,
            ];
        }
    }

    // —— Marcaciones (entrada / salida) ——
    $clockings = $scheduleModel->getMarcacionesByUser($userId, 5000, [
        'start_date' => $startDate,
        'end_date'   => $endDate,
    ]);
    foreach ($clockings as $c) {
        if (empty($c->event_time)) {
            continue;
        }
        $dt = date('Y-m-d\TH:i:s', strtotime($c->event_time));
        $hora = date('H:i', strtotime($c->event_time));
        $dir = $c->direction ?? '';
        if ($dir === 'P10') {
            $events[] = [
                'title' => 'Entrada ' . $hora,
                'start' => $dt,
                'classNames' => ['fc-ev-clock-in'],
                'order' => 5,
            ];
        } elseif ($dir === 'P20') {
            $events[] = [
                'title' => 'Salida ' . $hora,
                'start' => $dt,
                'classNames' => ['fc-ev-clock-out'],
                'order' => 5,
            ];
        } else {
            $events[] = [
                'title' => 'Marca ' . $hora,
                'start' => $dt,
                'classNames' => ['fc-ev-clock-other'],
                'order' => 5,
            ];
        }
    }

    return $events;
}
