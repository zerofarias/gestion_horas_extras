<?php

function system_settings_ready() {
    return class_exists('SystemSetting') && SystemSetting::isReady();
}

function setting($key, $default = null) {
    if (!class_exists('SystemSettingsService')) {
        return $default;
    }
    return SystemSettingsService::instance()->get($key, $default);
}

function setting_int($key, $default = 0) {
    return SystemSettingsService::instance()->getInt($key, $default);
}

function setting_bool($key, $default = false) {
    return SystemSettingsService::instance()->getBool($key, $default);
}

function setting_string($key, $default = '') {
    return SystemSettingsService::instance()->getString($key, $default);
}

function app_name() {
    $name = setting_string('sitename', defined('SITENAME') ? SITENAME : 'Paviotti RRHH');
    return $name !== '' ? $name : 'Paviotti RRHH';
}

function system_config_unlocked() {
    if (empty($_SESSION['system_config_unlocked_until'])) {
        return false;
    }
    return time() < (int)$_SESSION['system_config_unlocked_until'];
}

function system_config_unlock($minutes = 30) {
    $_SESSION['system_config_unlocked_until'] = time() + max(5, (int)$minutes) * 60;
}

function system_config_lock() {
    unset($_SESSION['system_config_unlocked_until']);
}

function require_system_config_unlock() {
    if (!isAdmin()) {
        redirect('login');
    }
    if (!system_settings_ready()) {
        $_SESSION['flash_error'] = 'Ejecutá migration_system_settings.sql en MySQL (ver MIGRATIONS.md #32).';
        redirect('admin/dashboard');
    }
    if (!system_config_unlocked()) {
        redirect('systemConfig/unlock');
    }
}

function clock_api_base_url() {
    $base = rtrim(setting_string('clock_api_base_url', defined('CLOCK_API_BASE_URL') ? CLOCK_API_BASE_URL : ''), '/');
    return $base;
}

function clock_api_login_url() {
    $base = clock_api_base_url();
    return $base !== '' ? $base . '/login' : (defined('CLOCK_API_LOGIN_URL') ? CLOCK_API_LOGIN_URL : '');
}

function extintos_db_config() {
    return [
        'host' => setting_string('extintos_db_host', defined('EXTINTOS_DB_HOST') ? EXTINTOS_DB_HOST : ''),
        'name' => setting_string('extintos_db_name', defined('EXTINTOS_DB_NAME') ? EXTINTOS_DB_NAME : ''),
        'user' => setting_string('extintos_db_user', defined('EXTINTOS_DB_USER') ? EXTINTOS_DB_USER : ''),
        'pass' => setting_string('extintos_db_pass', defined('EXTINTOS_DB_PASS') ? EXTINTOS_DB_PASS : ''),
    ];
}
