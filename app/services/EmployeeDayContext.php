<?php

/**
 * Contexto unificado por empleado y día: planificación, asistencia, solicitudes, vacaciones, cambios de turno.
 */
class EmployeeDayContext {
    public static function enrich(array $day) {
        if (empty($day['in_month'])) {
            return $day;
        }

        if (function_exists('calendar_day_apply_visibility')) {
            $day = calendar_day_apply_visibility(
                $day,
                (int)($day['_scope_company_id'] ?? 0),
                (int)($day['_scope_user_id'] ?? 0)
            );
        } elseif (function_exists('employee_portal_apply_day_visibility')) {
            $day = employee_portal_apply_day_visibility($day);
        }

        $planned = $day['planned'] ?? [];
        $att = $day['attendance'] ?? null;
        $just = $day['justification'] ?? null;
        $requests = $day['requests'] ?? [];
        $swaps = $day['swaps'] ?? [];
        $incidents = $day['incidents'] ?? [];
        $cpTasks = $day['cp_tasks'] ?? [];
        $holiday = $day['holiday'] ?? null;

        $hasVacation = false;
        $hasLeave = false;
        $shiftBlocks = [];

        foreach ($planned as $block) {
            $type = $block->type ?? 'shift';
            if ($type === 'vacation') {
                $hasVacation = true;
            } elseif ($type === 'leave') {
                $hasLeave = true;
            } elseif (in_array($type, ['shift', 'custom', 'overtime'], true)
                && !empty($block->start_time) && !empty($block->end_time)) {
                $shiftBlocks[] = $block;
            }
        }

        $chips = [];
        if ($holiday) {
            $chips[] = ['kind' => 'holiday', 'label' => 'Feriado', 'detail' => $holiday];
        }
        if ($hasVacation) {
            $chips[] = ['kind' => 'vacation', 'label' => 'Vacaciones', 'detail' => ''];
        }
        if ($hasLeave) {
            $chips[] = ['kind' => 'leave', 'label' => 'Licencia', 'detail' => ''];
        }
        foreach ($shiftBlocks as $block) {
            $chips[] = [
                'kind' => 'shift',
                'label' => attendanceFormatTime($block->start_time) . '–' . attendanceFormatTime($block->end_time),
                'detail' => $block->shift_name ?? '',
                'color' => $block->color ?? '#1d4ed8',
            ];
        }
        foreach ($requests as $req) {
            if ($hasVacation || $hasLeave) {
                continue;
            }
            $chips[] = [
                'kind' => 'request',
                'label' => $req->type_name ?? 'Solicitud',
                'detail' => '',
            ];
        }
        foreach ($swaps as $swap) {
            $chips[] = [
                'kind' => 'swap',
                'label' => 'Cambio de turno',
                'detail' => $swap->status ?? '',
            ];
        }
        foreach ($incidents as $inc) {
            $chips[] = [
                'kind' => 'incident',
                'label' => employee_incident_type_label($inc->incident_type),
                'detail' => $inc->title ?? '',
            ];
        }
        if (!empty($cpTasks) && function_exists('cp_calendar_chip_from_entries')) {
            $chips[] = cp_calendar_chip_from_entries($cpTasks);
        }

        $cellState = self::resolveCellState($day, $hasVacation, $hasLeave, $just, $att);

        $summaryParts = [];
        if ($holiday) {
            $summaryParts[] = 'Feriado: ' . $holiday;
        }
        if ($hasVacation) {
            $summaryParts[] = 'Vacaciones';
        } elseif ($hasLeave) {
            $summaryParts[] = 'Licencia';
        } elseif (!empty($shiftBlocks)) {
            $times = [];
            foreach ($shiftBlocks as $b) {
                $times[] = attendanceFormatTime($b->start_time) . '–' . attendanceFormatTime($b->end_time);
            }
            $summaryParts[] = 'Turno ' . implode(', ', $times);
        } elseif (!empty($planned)) {
            $summaryParts[] = 'Planificado';
        } else {
            $summaryParts[] = 'Sin turno';
        }

        if ($att) {
            $meta = attendanceStatusMeta($att->status);
            $summaryParts[] = $meta['label'];
            if ($att->actual_entry) {
                $summaryParts[] = 'E ' . attendanceFormatTime($att->actual_entry)
                    . ' / S ' . attendanceFormatTime($att->actual_exit);
            }
        } elseif ($just) {
            $summaryParts[] = 'Justificado';
        }

        if (!empty($requests) && !$hasVacation && !$hasLeave) {
            $summaryParts[] = $requests[0]->type_name ?? 'Licencia aprobada';
        }
        if (!empty($cpTasks) && function_exists('cp_format_money')) {
            $totalCp = 0;
            foreach ($cpTasks as $t) {
                $totalCp += (float)($t->amount ?? 0);
            }
            $summaryParts[] = count($cpTasks) . ' extra(s) CP · ' . cp_format_money($totalCp);
        }

        $day['context'] = [
            'has_vacation'   => $hasVacation,
            'has_leave'      => $hasLeave,
            'has_shift'      => !empty($shiftBlocks),
            'shift_blocks'   => $shiftBlocks,
            'chips'          => $chips,
            'cell_state'     => $cellState,
            'summary'        => implode(' · ', array_filter($summaryParts)),
            'show_attendance_dot' => $att && !$hasVacation && !$hasLeave && empty($just),
            'has_cp_tasks'      => !empty($cpTasks),
            'cp_tasks'          => $cpTasks,
        ];

        return $day;
    }

    private static function resolveCellState(array $day, $hasVacation, $hasLeave, $just, $att) {
        if (!empty($day['holiday']) && empty($day['planned']) && !$att) {
            return 'holiday';
        }
        if ($hasVacation) {
            return 'vacation';
        }
        if ($hasLeave || ($att && $att->status === 'on_leave')) {
            return 'on_leave';
        }
        if ($just) {
            return 'justified';
        }
        if ($att) {
            return preg_replace('/[^a-z_]/', '', $att->status);
        }
        if (!empty($day['planned'])) {
            return 'planned-only';
        }
        if (!empty($day['holiday'])) {
            return 'holiday';
        }
        return 'neutral';
    }
}
