<?php

function marcClockBadge($name, $map = null) {
    $map = $map ?? ($GLOBALS['CLOCK_DEVICE_MAP'] ?? []);
    if (!$name) {
        return '<span class="badge bg-secondary">—</span>';
    }
    $cfg = $map[$name] ?? null;
    $label = $cfg ? $cfg['label'] : htmlspecialchars($name);
    $color = $cfg ? $cfg['badge'] : 'secondary';
    return "<span class=\"badge bg-{$color}\">{$label}</span>";
}

function marcDirectionBadge($direction, $directionLabel = null) {
    if ($direction === 'P10') {
        return '<span class="badge" style="background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7;"><i class="fas fa-sign-in-alt me-1"></i>Entrada</span>';
    }
    if ($direction === 'P20') {
        return '<span class="badge" style="background:#fce4ec;color:var(--clr-primary);border:1px solid #f48fb1;"><i class="fas fa-sign-out-alt me-1"></i>Salida</span>';
    }
    if (!empty($directionLabel)) {
        return '<span class="badge bg-light text-muted border">' . htmlspecialchars($directionLabel) . '</span>';
    }
    return '<span class="badge bg-light text-muted border">—</span>';
}

function overtimeStatusLabel($status) {
    return function_exists('overtime_status_label')
        ? overtime_status_label($status)
        : ucfirst((string)$status);
}

function marcPersonLabel($personName, $employeeId, $fullName = null) {
    if ($fullName) {
        return $fullName;
    }
    $name = trim((string)$personName);
    if ($name !== '') {
        return $name;
    }
    $id = trim((string)$employeeId);
    return $id !== '' ? "Legajo {$id}" : '—';
}

function marcMappingBadge($userId, $fullName = null) {
    if ($userId) {
        return '<span class="badge" style="background:#e8f5e9;color:#2e7d32;"><i class="fas fa-user-check me-1"></i>En sistema</span>';
    }
    return '<span class="badge bg-warning text-dark"><i class="fas fa-user-slash me-1"></i>Sin mapear</span>';
}

/**
 * Agrupa marcaciones por persona + fecha + reloj, con entradas y salidas separadas.
 *
 * @param array $marcaciones Filas de marcaciones_cache (+ full_name)
 * @return array
 */
function marcBuildPersonDayGroups(array $marcaciones) {
    $groups = [];

    foreach ($marcaciones as $m) {
        $date = date('Y-m-d', strtotime($m->event_time));
        $personKey = !empty($m->user_id) ? 'u' . $m->user_id : 'e' . ($m->employee_id ?? '');
        $device = $m->device_name ?? '';
        $key = $personKey . '|' . $date . '|' . $device;

        if (!isset($groups[$key])) {
            $groups[$key] = [
                'user_id'       => $m->user_id ?? null,
                'employee_id'   => $m->employee_id ?? '',
                'full_name'     => $m->full_name ?? null,
                'person_name'   => $m->person_name ?? null,
                'date'          => $date,
                'device_name'   => $device,
                'events'        => [],
                'entradas'      => [],
                'salidas'       => [],
                'other'         => [],
            ];
        }

        $groups[$key]['events'][] = $m;
    }

    $out = array_values($groups);
    $schedule = new Schedule();

    foreach ($out as &$g) {
        usort($g['events'], function ($a, $b) {
            return strcmp($a->event_time, $b->event_time);
        });
        $g['entradas'] = [];
        $g['salidas']  = [];
        $g['other']    = [];
        foreach ($g['events'] as $m) {
            $timeLabel = date('H:i', strtotime($m->event_time));
            $dir = $m->direction ?? '';
            if ($dir === 'P10') {
                $g['entradas'][] = $timeLabel;
            } elseif ($dir === 'P20') {
                $g['salidas'][] = $timeLabel;
            } else {
                $g['other'][] = $timeLabel;
            }
        }
        $g['summary'] = $schedule->computeDaySummaryFromClockings($g['events']);
        list($g['entradas'], $g['salidas']) = marcDisplayEntryExitTimes($g);
    }
    unset($g);

    usort($out, function ($a, $b) {
        $dc = strcmp($b['date'], $a['date']);
        if ($dc !== 0) {
            return $dc;
        }
        $na = marcPersonLabel($a['person_name'], $a['employee_id'], $a['full_name']);
        $nb = marcPersonLabel($b['person_name'], $b['employee_id'], $b['full_name']);
        return strcasecmp($na, $nb);
    });

    return $out;
}

/**
 * Columnas Entrada/Salida para la UI: respeta P10/P20 y, si hace falta, 1ª/última del resumen.
 */
function marcDisplayEntryExitTimes(array $group) {
    $entradas = [];
    $salidas  = [];
    $summary  = $group['summary'] ?? [];
    $primaryIn  = marcGroupPrimaryTime($summary['entry_time'] ?? null);
    $primaryOut = marcGroupPrimaryTime($summary['exit_time'] ?? null);

    foreach ($group['events'] as $m) {
        $t   = date('H:i', strtotime($m->event_time));
        $dir = $m->direction ?? '';

        if ($primaryIn && $t === $primaryIn) {
            $entradas[] = $t;
            continue;
        }
        if ($primaryOut && $t === $primaryOut) {
            $salidas[] = $t;
            continue;
        }
        if ($dir === 'P10') {
            $entradas[] = $t;
        } elseif ($dir === 'P20' && (!$primaryIn || $t !== $primaryIn)) {
            $salidas[] = $t;
        }
    }

    $entradas = array_values(array_unique($entradas));
    $salidas  = array_values(array_unique($salidas));
    sort($entradas);
    sort($salidas);

    return [$entradas, $salidas];
}

function marcFormatTimesList(array $times) {
    if (empty($times)) {
        return '—';
    }
    return implode(', ', $times);
}

/**
 * Horas del grupo usando la misma lógica que el perfil del empleado (Schedule).
 */
function marcGroupHoursLabel(array $group) {
    $s = $group['summary'] ?? null;
    if (!$s || $s['total_hours'] === null) {
        return null;
    }
    return number_format((float)$s['total_hours'], 2, ',', '.') . ' h';
}

function marcGroupUsesFirstLast(array $group) {
    return ($group['summary']['calc_method'] ?? null) === 'first_last';
}

/** Hora HH:MM usada para el cálculo (primera entrada o última salida). */
function marcGroupPrimaryTime($summaryTime) {
    if (empty($summaryTime)) {
        return null;
    }
    return substr($summaryTime, 0, 5);
}

function marcTimeChipIsPrimary($chipTime, $primaryTime) {
    return $primaryTime !== null && $chipTime === $primaryTime;
}
