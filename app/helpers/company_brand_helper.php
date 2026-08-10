<?php

/**
 * Marca por empresa: logo, nombre y clase de portal.
 * Logos en public/img/companies/{slug}.png o companies.logo_path en BD.
 */

function company_brand_slug_map() {
    return [
        'Casa Paviotti' => 'casa-paviotti',
        'Ecofarma' => 'ecofarma',
        'Servicios Sociales' => 'servicios-sociales',
        'A.M.S.S.I' => 'amssi',
        'AMSSI' => 'amssi',
    ];
}

function company_brand_resolve_company_id($companyId = null) {
    $id = (int)($companyId ?? 0);
    if ($id > 0) {
        return $id;
    }
    if (!empty($_SESSION['user_company_id'])) {
        return (int)$_SESSION['user_company_id'];
    }
    if (isLoggedIn() && hasRole('empleado') && !empty($_SESSION['user_id'])) {
        $u = (new User())->getUserById((int)$_SESSION['user_id']);
        return $u ? (int)($u->company_id ?? 0) : 0;
    }
    return 0;
}

function company_brand_display_name($companyId = null) {
    if (!empty($_SESSION['user_company_name']) && $companyId === null) {
        return (string)$_SESSION['user_company_name'];
    }
    $id = company_brand_resolve_company_id($companyId);
    if ($id <= 0) {
        return defined('SITENAME') ? SITENAME : 'RRHH';
    }
    $name = (new Company())->getNameById($id);
    return $name ?: (defined('SITENAME') ? SITENAME : 'RRHH');
}

function company_brand_slug($companyId = null) {
    $name = company_brand_display_name($companyId);
    $map = company_brand_slug_map();
    if (isset($map[$name])) {
        return $map[$name];
    }
    $slug = mb_strtolower($name);
    $slug = preg_replace('/[^a-z0-9]+/u', '-', $slug);
    return trim($slug, '-');
}

function company_brand_logo_path_on_disk($companyId = null) {
    $id = company_brand_resolve_company_id($companyId);
    if ($id > 0 && company_brand_logo_column_ready()) {
        $db = new Database();
        $db->query('SELECT logo_path FROM companies WHERE id = ? LIMIT 1');
        $row = $db->single([$id]);
        if ($row && !empty($row->logo_path)) {
            $rel = ltrim(str_replace('\\', '/', (string)$row->logo_path), '/');
            if ($rel === '' || strpos($rel, '..') !== false || !preg_match('#^(img/|uploads/)#', $rel)) {
                $rel = '';
            }
            if ($rel === '') {
                return company_brand_slug($companyId) !== ''
                    ? (is_file(dirname(APPROOT) . '/public/img/companies/' . company_brand_slug($companyId) . '.png')
                        ? 'img/companies/' . company_brand_slug($companyId) . '.png'
                        : 'img/logo-paviotti.png')
                    : 'img/logo-paviotti.png';
            }
            $full = dirname(APPROOT) . '/public/' . $rel;
            if (is_file($full)) {
                return $rel;
            }
        }
    }
    $slug = company_brand_slug($companyId);
    if ($slug !== '') {
        $rel = 'img/companies/' . $slug . '.png';
        if (is_file(dirname(APPROOT) . '/public/' . $rel)) {
            return $rel;
        }
    }
    return 'img/logo-paviotti.png';
}

function company_brand_logo_url($companyId = null) {
    return URLROOT . '/' . company_brand_logo_path_on_disk($companyId);
}

function company_brand_logo_column_ready() {
    static $ready = null;
    if ($ready !== null) {
        return $ready;
    }
    try {
        $db = new Database();
        $db->query("SHOW COLUMNS FROM `companies` LIKE 'logo_path'");
        $ready = (bool)$db->single();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/** Clase en <body> para estilos por empresa (ej. portal Casa Paviotti). */
function company_brand_body_class() {
    if (!isLoggedIn()) {
        return '';
    }
    if (function_exists('current_user_uses_casapav_tasks') && current_user_uses_casapav_tasks()) {
        return 'portal-casapav';
    }
    $slug = company_brand_slug();
    return $slug !== '' ? 'portal-brand-' . preg_replace('/[^a-z0-9-]/', '', $slug) : '';
}

function company_brand_subtitle($companyId = null) {
    if (function_exists('current_user_uses_casapav_tasks') && current_user_uses_casapav_tasks()) {
        return 'Extras por tarea';
    }
    return 'Gestión de RRHH';
}
