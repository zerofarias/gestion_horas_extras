<?php

/**
 * Entorno de ejecución según el host HTTP (local vs producción).
 * @return 'local'|'production'
 */
function app_runtime_environment() {
    if (PHP_SAPI === 'cli') {
        return app_cli_environment();
    }
    return app_is_local_host($_SERVER['HTTP_HOST'] ?? '') ? 'local' : 'production';
}

function app_is_local_host($host) {
    $host = strtolower(trim((string)$host));
    if ($host === '') {
        return true;
    }
    if (strpos($host, ':') !== false) {
        $host = explode(':', $host, 2)[0];
    }
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }
    if (substr($host, -6) === '.local' || substr($host, -5) === '.test') {
        return true;
    }
    return false;
}

function app_cli_environment() {
    $root = str_replace('\\', '/', dirname(__DIR__, 2));
    if (stripos($root, 'xampp') !== false || stripos($root, 'xamppcubo') !== false) {
        return 'local';
    }
    return 'production';
}

function app_request_is_https() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
}

/**
 * Ruta web de la app (ej. /gestion_horas_extras), sin barra final.
 */
function app_detect_base_path() {
    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script !== '' && $script !== '/index.php') {
        $base = rtrim(dirname($script), '/\\');
        if ($base !== '' && substr($base, -7) === '/public') {
            $base = substr($base, 0, -7);
        }
        if ($base !== '' && $base !== '/') {
            return $base;
        }
    }

    $docRoot = str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $projectRoot = str_replace('\\', '/', dirname(__DIR__, 2));
    if ($docRoot !== '' && strpos($projectRoot, $docRoot) === 0) {
        $rel = substr($projectRoot, strlen($docRoot));
        $rel = trim(str_replace('\\', '/', $rel), '/');
        if ($rel !== '') {
            return '/' . $rel;
        }
    }

    return '/gestion_horas_extras';
}

/**
 * URL base pública (sin barra final). Local → localhost; hosting → dominio real.
 */
function app_detect_urlroot() {
    if (defined('URLROOT') && URLROOT !== '') {
        return rtrim((string)URLROOT, '/');
    }

    if (PHP_SAPI === 'cli') {
        return app_runtime_environment() === 'local'
            ? 'http://localhost/gestion_horas_extras'
            : 'https://paviotti.com.ar/gestion_horas_extras';
    }

    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = app_request_is_https() ? 'https' : 'http';
    $base = app_detect_base_path();

    return rtrim($scheme . '://' . $host . $base, '/');
}
