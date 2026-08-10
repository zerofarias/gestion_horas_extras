<?php

function cp_tasks_is_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW TABLES LIKE 'cp_task_entries'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function cp_tasks_extras_mode_column_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW COLUMNS FROM `companies` LIKE 'extras_mode'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function cp_casapav_company_id() {
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    $companyModel = new Company();
    $id = (int)($companyModel->getIdByName('Casa Paviotti') ?? 0);
    return $id;
}

function company_uses_casapav_tasks($companyId) {
    $companyId = (int)$companyId;
    if ($companyId <= 0 || !cp_tasks_is_ready()) {
        return false;
    }
    if (cp_tasks_extras_mode_column_ready()) {
        $db = new Database();
        $db->query('SELECT extras_mode FROM companies WHERE id = ? LIMIT 1');
        $row = $db->single([$companyId]);
        if ($row && ($row->extras_mode ?? '') === 'casapav_tasks') {
            return true;
        }
    }
    return $companyId === cp_casapav_company_id();
}

function current_user_uses_casapav_tasks() {
    if (!isLoggedIn()) {
        return false;
    }
    $companyId = (int)($_SESSION['user_company_id'] ?? 0);
    if ($companyId <= 0 && hasRole('empleado')) {
        $user = (new User())->getUserById((int)$_SESSION['user_id']);
        $companyId = (int)($user->company_id ?? 0);
    }
    return company_uses_casapav_tasks($companyId);
}

function require_cp_tasks_ready($redirectTo = 'employee/index') {
    if (!cp_tasks_is_ready()) {
        $_SESSION['flash_error'] = 'El módulo de extras Casa Paviotti no está instalado. Ejecutá migration_casapav_tasks.sql (ver MIGRATIONS.md).';
        redirect($redirectTo);
    }
}

function require_cp_employee() {
    requireEmployeeRole();
    require_cp_tasks_ready();
    if (function_exists('cp_empleado_can_view') && !cp_empleado_can_view()) {
        $_SESSION['flash_error'] = 'Las extras por tarea no están disponibles para tu perfil o área.';
        redirect('employee/index');
    }
    if (!function_exists('cp_empleado_can_view') && !current_user_uses_casapav_tasks()) {
        $_SESSION['flash_error'] = 'Las extras por tarea son solo para empleados de Casa Paviotti.';
        redirect('employee/index');
    }
}

function require_cp_staff() {
    if (!isStaffAdmin()) {
        redirect('login');
    }
    require_cp_tasks_ready();
    $companyId = requireAdminCompany('admin/dashboard');
    if (function_exists('cp_staff_can_view')) {
        if (!cp_staff_can_view($companyId)) {
            $_SESSION['flash_error'] = 'El módulo de extras Casa Paviotti no está habilitado para esta empresa o tu perfil.';
            redirect('admin/dashboard');
        }
    } elseif (!company_uses_casapav_tasks($companyId)) {
        $_SESSION['flash_error'] = 'Seleccioná la empresa Casa Paviotti en el selector de empresa.';
        redirect('admin/dashboard');
    }
    return $companyId;
}

function cp_format_money($amount) {
    return '$ ' . number_format((float)$amount, 2, ',', '.');
}

function cp_format_date_es($date) {
    if (!$date || $date === '0000-00-00') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : htmlspecialchars((string)$date);
}

/** Etiquetas, iconos y agrupación para empleados (mobile-first). */
function cp_task_type_catalog() {
    return [
        'armar' => [
            'label' => 'Armar sepelio',
            'short' => 'Armar',
            'icon' => 'fa-box-open',
            'color' => '#b45309',
            'hint' => 'Preparación del servicio',
            'group' => 'sepelio',
            'group_label' => 'Sepelio',
        ],
        'realizar' => [
            'label' => 'Realizar sepelio',
            'short' => 'Realizar',
            'icon' => 'fa-route',
            'color' => '#c2410c',
            'hint' => 'Servicio en sala o domicilio',
            'group' => 'sepelio',
            'group_label' => 'Sepelio',
        ],
        'ambulancia' => [
            'label' => 'Traslado en ambulancia',
            'short' => 'Ambulancia',
            'icon' => 'fa-truck-medical',
            'color' => '#0284c7',
            'hint' => 'Traslado sanitario',
            'group' => 'traslados',
            'group_label' => 'Traslados',
        ],
        'viajes' => [
            'label' => 'Viajes sanitarios',
            'short' => 'Viajes',
            'icon' => 'fa-road',
            'color' => '#0369a1',
            'hint' => 'Km × tarifa activa/pasiva',
            'group' => 'traslados',
            'group_label' => 'Traslados',
        ],
        'metalica' => [
            'label' => 'Cambio de metálica',
            'short' => 'Metálica',
            'icon' => 'fa-cross',
            'color' => '#6d28d9',
            'hint' => 'Cambio de cruz o placa',
            'group' => 'servicios',
            'group_label' => 'Servicios',
        ],
        'cremacion' => [
            'label' => 'Cremación',
            'short' => 'Cremación',
            'icon' => 'fa-fire',
            'color' => '#dc2626',
            'hint' => 'Viajes a Cintra',
            'group' => 'servicios',
            'group_label' => 'Servicios',
        ],
        'tanato' => [
            'label' => 'Tanatopraxia',
            'short' => 'Tanato',
            'icon' => 'fa-user-md',
            'color' => '#7c3aed',
            'hint' => 'Preparación del cuerpo',
            'group' => 'servicios',
            'group_label' => 'Servicios',
        ],
        'gestion' => [
            'label' => 'Gestión y trámites',
            'short' => 'Gestión',
            'icon' => 'fa-file-signature',
            'color' => '#4f46e5',
            'hint' => 'Trámites administrativos',
            'group' => 'servicios',
            'group_label' => 'Servicios',
        ],
        'mantenimiento' => [
            'label' => 'Mantenimiento y tareas',
            'short' => 'Mantenimiento',
            'icon' => 'fa-broom',
            'color' => '#059669',
            'hint' => 'Importe manual',
            'group' => 'otros',
            'group_label' => 'Otros',
        ],
        'parcelas' => [
            'label' => 'Comisión en parcela',
            'short' => 'Parcela',
            'icon' => 'fa-tree',
            'color' => '#15803d',
            'hint' => 'Importe manual',
            'group' => 'otros',
            'group_label' => 'Otros',
        ],
        'externas' => [
            'label' => 'Otra empresa del grupo',
            'short' => 'Externa',
            'icon' => 'fa-building',
            'color' => '#475569',
            'hint' => 'Tarea para Ecofarma u otra',
            'group' => 'otros',
            'group_label' => 'Otros',
        ],
    ];
}

function cp_task_display_meta($formKey) {
    $cat = cp_task_type_catalog();
    $key = trim((string)$formKey);
    return $cat[$key] ?? [
        'label' => ucfirst($key),
        'short' => ucfirst($key),
        'icon' => 'fa-tasks',
        'color' => '#64748b',
        'hint' => '',
        'group' => 'otros',
        'group_label' => 'Otros',
    ];
}

function cp_task_display_name($formKey, $fallbackName = '') {
    $meta = cp_task_display_meta($formKey);
    return $meta['label'] ?: ($fallbackName ?: $formKey);
}

/** Agrupa tipos de tarea para la pantalla índice. */
function cp_task_types_grouped(array $taskTypes) {
    $groups = [
        'sepelio' => ['label' => 'Sepelio', 'items' => []],
        'traslados' => ['label' => 'Traslados', 'items' => []],
        'servicios' => ['label' => 'Servicios', 'items' => []],
        'otros' => ['label' => 'Otros', 'items' => []],
    ];
    foreach ($taskTypes as $t) {
        $meta = cp_task_display_meta($t->form_key ?? '');
        $item = (object)[
            'id' => $t->id,
            'form_key' => $t->form_key,
            'name' => $t->name,
            'display_name' => cp_task_display_name($t->form_key, $t->name),
            'is_manual' => (int)($t->is_manual_amount ?? 0) === 1,
            'icon' => $meta['icon'],
            'color' => $meta['color'],
            'hint' => $meta['hint'],
            'short' => $meta['short'],
        ];
        $gk = $meta['group'] ?? 'otros';
        if (!isset($groups[$gk])) {
            $groups[$gk] = ['label' => $meta['group_label'] ?? 'Otros', 'items' => []];
        }
        $groups[$gk]['items'][] = $item;
    }
    return array_values(array_filter($groups, function ($g) {
        return !empty($g['items']);
    }));
}

function cp_user_has_rates($userId) {
    $cp = new CpTask();
    $rates = $cp->getRatesForUser((int)$userId);
    if (!$rates) {
        return false;
    }
    foreach (CpTask::rateColumnNames() as $col) {
        if ((float)($rates->$col ?? 0) > 0) {
            return true;
        }
    }
    return false;
}

/** Formularios que usan select de extintos (como legacy t1/t2). */
function cp_task_form_uses_deceased_select($formKey) {
    return in_array(trim((string)$formKey), [
        'armar', 'realizar', 'tanato', 'metalica', 'viajes', 'cremacion', 'gestion',
    ], true);
}

function cp_task_duplicate_message($formKey, $deceasedName = '') {
    $task = cp_task_display_name($formKey);
    $who = $deceasedName !== '' ? (' para «' . $deceasedName . '»') : '';
    return 'Ya registraste «' . $task . '»' . $who . '. Podés cargar otra tarea distinta para la misma persona (ej. Realizar sepelio).';
}

function cp_validate_activity_date($date) {
    $date = trim((string)$date);
    if ($date === '') {
        return 'Indicá la fecha de la tarea.';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return 'Fecha inválida.';
    }
    if ($date > date('Y-m-d')) {
        return 'La fecha no puede ser futura.';
    }
    return null;
}

/** Chip compacto para calendario unificado (una celda puede tener varias tareas). */
function cp_calendar_chip_from_entries(array $entries) {
    $count = count($entries);
    $total = 0;
    $labels = [];
    foreach ($entries as $e) {
        $total += (float)($e->amount ?? 0);
        $name = trim((string)($e->task_name ?? 'Extra'));
        if (mb_strlen($name) > 14) {
            $name = mb_substr($name, 0, 12) . '…';
        }
        $labels[] = $name;
    }
    $detail = implode(' · ', $labels) . ' — ' . cp_format_money($total);
    if ($count === 1) {
        $label = $labels[0] . ' ' . cp_format_money($total);
        if (mb_strlen($label) > 22) {
            $label = cp_format_money($total);
        }
    } else {
        $label = 'Extras ×' . $count;
    }
    return [
        'kind' => 'cp_task',
        'label' => $label,
        'detail' => $detail,
        'count' => $count,
        'total' => $total,
    ];
}
