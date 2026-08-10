<?php
/**
 * Copiar a config.local.php en este mismo directorio (no versionar).
 * Las constantes definidas aquí reemplazan los valores por defecto de config.php.
 *
 * Ejemplo mínimo en producción:
 *   define('URLROOT', 'https://paviotti.com.ar/gestion_horas_extras');
 *   define('DB_PASS', 'contraseña_segura');
 *   define('CLOCK_API_PASSWORD', '...');
 *   define('EXTINTOS_DB_PASS', '...');
 *
 * URLROOT y APP_ENV se detectan solos (local vs hosting). Ver config.local.example.php.
 * Forzar solo si hace falta: define('URLROOT', 'https://paviotti.com.ar/gestion_horas_extras');
 */
define('DB_PASS', 'cambiar');
define('CLOCK_API_PASSWORD', 'cambiar');
define('EXTINTOS_DB_PASS', 'cambiar');
