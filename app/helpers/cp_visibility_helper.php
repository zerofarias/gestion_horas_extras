<?php

/** Visibilidad del módulo Extras Casa Paviotti (empresa, área, portal, roles). */

function cp_visibility_columns_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW COLUMNS FROM `companies` LIKE 'show_cp_extras'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

function company_cp_extras_enabled($companyId) {
    $companyId = (int)$companyId;
    if ($companyId <= 0 || !function_exists('company_uses_casapav_tasks') || !company_uses_casapav_tasks($companyId)) {
        return false;
    }
    if (!cp_visibility_columns_ready()) {
        return true;
    }
    try {
        $db = new Database();
        $db->query('SELECT show_cp_extras FROM companies WHERE id = ? LIMIT 1');
        $row = $db->single([$companyId]);
        return $row && (int)($row->show_cp_extras ?? 1) === 1;
    } catch (Throwable $e) {
        return false;
    }
}

function cp_scope_enabled_for_user($user) {
    if (!$user || !company_cp_extras_enabled((int)($user->company_id ?? 0))) {
        return false;
    }
    $areaId = (int)($user->area_id ?? 0);
    if ($areaId <= 0 || !cp_visibility_columns_ready()) {
        return true;
    }
    try {
        $db = new Database();
        $db->query('SELECT show_cp_extras FROM areas WHERE id = ? LIMIT 1');
        $row = $db->single([$areaId]);
        if (!$row || $row->show_cp_extras === null || $row->show_cp_extras === '') {
            return true;
        }
        return (int)$row->show_cp_extras === 1;
    } catch (Throwable $e) {
        return false;
    }
}

function cp_staff_role_allowed() {
    if (!function_exists('isStaffAdmin') || !isStaffAdmin()) {
        return false;
    }
    if (!function_exists('setting_bool')) {
        return true;
    }
    if (isSupervisor()) {
        return setting_bool('cp_extras_visible_supervisor', true);
    }
    return setting_bool('cp_extras_visible_admin', true);
}

function cp_staff_can_view($companyId = null, $contextUser = null) {
    if (!cp_staff_role_allowed()) {
        return false;
    }
    $companyId = (int)($companyId ?: (function_exists('adminCompanyId') ? adminCompanyId() : 0));
    if (!company_cp_extras_enabled($companyId)) {
        return false;
    }
    if ($contextUser !== null && !cp_scope_enabled_for_user($contextUser)) {
        return false;
    }
    return true;
}

function cp_empleado_can_view($user = null) {
    if (!function_exists('hasRole') || !hasRole('empleado')) {
        return false;
    }
    if (!function_exists('employee_portal_can') || !employee_portal_can('cp_extras')) {
        return false;
    }
    if ($user === null && function_exists('isLoggedIn') && isLoggedIn()) {
        $user = (new User())->getUserById((int)$_SESSION['user_id']);
    }
    return $user ? cp_scope_enabled_for_user($user) : false;
}

function require_cp_staff_access($redirectTo = 'admin/dashboard') {
    if (!cp_staff_can_view()) {
        $_SESSION['flash_error'] = 'El módulo de extras Casa Paviotti no está habilitado para esta empresa o tu perfil.';
        redirect($redirectTo);
    }
}

/** Porcentaje de recargo al cierre (ej. 19.5 → cargos sociales / IVA interno). */
function cp_closure_markup_rate() {
    $pct = 19.5;
    if (function_exists('setting')) {
        $raw = setting('cp_closure_markup_pct');
        if ($raw !== null && $raw !== '') {
            $pct = (float)str_replace(',', '.', (string)$raw);
        }
    }
    if ($pct < 0) {
        $pct = 0;
    }
    return round($pct / 100, 6);
}

function cp_closure_markup_pct_label() {
    $pct = cp_closure_markup_rate() * 100;
    $s = number_format($pct, 2, ',', '.');
    return rtrim(rtrim($s, '0'), ',') . '%';
}

/** @return array{net:float,rate:float,markup:float,final:float} */
function cp_compute_closure_amounts($netTotal) {
    $net = round((float)$netTotal, 2);
    $rate = cp_closure_markup_rate();
    $markup = round($net * $rate, 2);
    $final = round($net + $markup, 2);
    return ['net' => $net, 'rate' => $rate, 'markup' => $markup, 'final' => $final];
}

function cp_area_show_cp_label($area) {
    if (!$area || !cp_visibility_columns_ready()) {
        return 'Hereda empresa';
    }
    if ($area->show_cp_extras === null || $area->show_cp_extras === '') {
        return 'Hereda empresa';
    }
    return (int)$area->show_cp_extras === 1 ? 'Visible' : 'Oculto';
}

function company_cp_extras_label($company) {
    if (!$company) {
        return '—';
    }
    if (!function_exists('company_uses_casapav_tasks') || !company_uses_casapav_tasks((int)$company->id)) {
        return 'N/A';
    }
    if (!cp_visibility_columns_ready()) {
        return 'Sí';
    }
    return (int)($company->show_cp_extras ?? 1) === 1 ? 'Sí' : 'No';
}
