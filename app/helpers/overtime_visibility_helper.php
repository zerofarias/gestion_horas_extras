<?php

/**
 * Visibilidad del módulo de horas extras clásicas (50%/100%).
 * Capas: rol RRHH (admin/supervisor), portal empleado (system_settings),
 * empresa (companies.show_overtime) y área (areas.show_overtime).
 * Casa Paviotti con extras_mode=casapav_tasks no usa overtime_entries.
 */

function overtime_visibility_columns_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW COLUMNS FROM `companies` LIKE 'show_overtime'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/** Empresa con módulo horas extras clásicas (no CP por tareas). */
function company_uses_classic_overtime($companyId) {
    $companyId = (int)$companyId;
    if ($companyId <= 0) {
        return false;
    }
    if (function_exists('company_uses_casapav_tasks') && company_uses_casapav_tasks($companyId)) {
        return false;
    }
    if (!overtime_visibility_columns_ready()) {
        return true;
    }
    try {
        $db = new Database();
        $db->query('SELECT show_overtime FROM companies WHERE id = ? LIMIT 1');
        $db->bind(1, $companyId);
        $row = $db->single();
        return $row && (int)($row->show_overtime ?? 1) === 1;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Área: NULL hereda empresa; 0 oculta; 1 visible.
 * Sin área asignada solo cuenta la empresa.
 */
function overtime_scope_enabled_for_user($user) {
    if (!$user) {
        return false;
    }
    $companyId = (int)($user->company_id ?? 0);
    if (!company_uses_classic_overtime($companyId)) {
        return false;
    }
    $areaId = (int)($user->area_id ?? 0);
    if ($areaId <= 0 || !overtime_visibility_columns_ready()) {
        return true;
    }
    try {
        $db = new Database();
        $db->query('SELECT show_overtime FROM areas WHERE id = ? LIMIT 1');
        $db->bind(1, $areaId);
        $row = $db->single();
        if (!$row || $row->show_overtime === null || $row->show_overtime === '') {
            return true;
        }
        return (int)$row->show_overtime === 1;
    } catch (Throwable $e) {
        return false;
    }
}

function overtime_staff_role_allowed() {
    if (!function_exists('isStaffAdmin') || !isStaffAdmin()) {
        return false;
    }
    if (!function_exists('setting_bool')) {
        return true;
    }
    if (isSupervisor()) {
        return setting_bool('overtime_visible_supervisor', true);
    }
    return setting_bool('overtime_visible_admin', true);
}

/** Empleado: portal global + empresa/área del usuario. */
function overtime_empleado_scope_allowed($user = null) {
    if (!function_exists('hasRole') || !hasRole('empleado')) {
        return false;
    }
    if ($user === null && function_exists('isLoggedIn') && isLoggedIn()) {
        $user = (new User())->getUserById((int)$_SESSION['user_id']);
    }
    if (!$user) {
        return false;
    }
    return overtime_scope_enabled_for_user($user);
}

/**
 * Panel admin/supervisor: rol + empresa activa (+ opcional empleado consultado).
 */
function overtime_staff_can_view($companyId = null, $contextUser = null) {
    if (!overtime_staff_role_allowed()) {
        return false;
    }
    $companyId = (int)($companyId ?: (function_exists('adminCompanyId') ? adminCompanyId() : 0));
    if (!company_uses_classic_overtime($companyId)) {
        return false;
    }
    if ($contextUser !== null && !overtime_scope_enabled_for_user($contextUser)) {
        return false;
    }
    return true;
}

function require_staff_overtime_access($companyId = null, $redirectTo = 'admin/dashboard') {
    if (!overtime_staff_can_view($companyId)) {
        $_SESSION['flash_error'] = 'El módulo de horas extras no está habilitado para esta empresa o tu perfil.';
        redirect($redirectTo);
    }
}

function overtime_area_show_overtime_label($area) {
    if (!$area || !overtime_visibility_columns_ready()) {
        return 'Hereda empresa';
    }
    if ($area->show_overtime === null || $area->show_overtime === '') {
        return 'Hereda empresa';
    }
    return (int)$area->show_overtime === 1 ? 'Visible' : 'Oculto';
}

function company_overtime_label($company) {
    if (!$company) {
        return '—';
    }
    if (function_exists('company_uses_casapav_tasks') && company_uses_casapav_tasks((int)$company->id)) {
        return 'Extras CP';
    }
    if (!overtime_visibility_columns_ready()) {
        return 'Sí';
    }
    return (int)($company->show_overtime ?? 1) === 1 ? 'Sí' : 'No';
}

/** ¿Ocultar bloques planificados tipo overtime en calendarios / listados? */
function calendar_should_hide_overtime($companyId = 0, $userId = 0) {
    if (function_exists('employee_portal_should_filter_for_session')
        && employee_portal_should_filter_for_session()) {
        return !employee_portal_can('overtime');
    }
    $companyId = (int)$companyId;
    if ($companyId > 0 && !company_uses_classic_overtime($companyId)) {
        return true;
    }
    $userId = (int)$userId;
    if ($userId > 0) {
        $user = (new User())->getUserById($userId);
        if ($user && !overtime_scope_enabled_for_user($user)) {
            return true;
        }
    }
    return false;
}

function calendar_filter_planned_blocks(array $planned, $companyId = 0, $userId = 0) {
    if (!calendar_should_hide_overtime($companyId, $userId)) {
        return $planned;
    }
    return array_values(array_filter($planned, function ($block) {
        return ($block->type ?? '') !== 'overtime';
    }));
}

/** Filtros de día para calendario unificado (empleado + admin). */
function calendar_day_apply_visibility(array $day, $companyId = 0, $userId = 0) {
    if (empty($day['in_month'])) {
        return $day;
    }
    if (function_exists('employee_portal_apply_day_visibility')
        && function_exists('employee_portal_should_filter_for_session')
        && employee_portal_should_filter_for_session()) {
        return employee_portal_apply_day_visibility($day);
    }
    $companyId = (int)($day['_scope_company_id'] ?? $companyId);
    $userId = (int)($day['_scope_user_id'] ?? $userId);
    $planned = $day['planned'] ?? [];
    $planned = calendar_filter_planned_blocks($planned, $companyId, $userId);
    $day['planned'] = $planned;
    $day['has_planned'] = !empty($planned);
    if ($companyId > 0 && function_exists('company_cp_extras_enabled') && !company_cp_extras_enabled($companyId)) {
        $day['cp_tasks'] = [];
    } elseif ($userId > 0 && function_exists('cp_scope_enabled_for_user')) {
        $user = (new User())->getUserById($userId);
        if ($user && !cp_scope_enabled_for_user($user)) {
            $day['cp_tasks'] = [];
        }
    }
    return $day;
}

/** Tipos permitidos al guardar el planificador semanal. */
function planner_valid_schedule_types($companyId = null) {
    $types = function_exists('vacation_module_ready') && vacation_module_ready()
        ? vacation_planner_valid_types()
        : ['shift', 'custom', 'overtime'];
    $companyId = (int)($companyId ?: (function_exists('adminCompanyId') ? adminCompanyId() : 0));
    if ($companyId > 0 && !company_uses_classic_overtime($companyId)) {
        $types = array_values(array_filter($types, function ($t) {
            return $t !== 'overtime';
        }));
    }
    return $types;
}

/** Reincorpora horas extra ya planificadas si el módulo está deshabilitado (evita borrado al guardar). */
function planner_preserve_locked_overtime_entries(array $entries, array $oldEntries) {
    $haveOt = false;
    foreach ($entries as $e) {
        if (($e['type'] ?? '') === 'overtime') {
            $haveOt = true;
            break;
        }
    }
    if ($haveOt) {
        return $entries;
    }
    foreach ($oldEntries as $old) {
        if (($old->type ?? '') !== 'overtime') {
            continue;
        }
        $entries[] = [
            'type' => 'overtime',
            'shift_id' => null,
            'start_time' => !empty($old->start_time) ? $old->start_time : null,
            'end_time' => !empty($old->end_time) ? $old->end_time : null,
            'notes' => $old->notes ?? null,
        ];
    }
    return $entries;
}

function schedule_filter_entries_for_scope(array $entries, $companyId = 0, $userId = 0) {
    if (function_exists('employee_portal_filter_schedule_entries')
        && function_exists('employee_portal_should_filter_for_session')
        && employee_portal_should_filter_for_session()) {
        return employee_portal_filter_schedule_entries($entries);
    }
    if (!calendar_should_hide_overtime($companyId, $userId)) {
        return $entries;
    }
    return array_values(array_filter($entries, function ($entry) {
        return ($entry->type ?? '') !== 'overtime';
    }));
}
