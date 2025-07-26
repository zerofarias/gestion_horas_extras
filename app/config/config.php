<?php
// app/config/config.php

// --- Configuración de la Base de Datos ---
// ¡IMPORTANTE! Modifica estos valores cuando subas el proyecto a un servidor real.
define('DB_HOST', 'localhost');                  // Host de la base de datos (generalmente 'localhost' en desarrollo)
define('DB_USER', 'paviotti_lautaro');                      // Usuario de la base de datos (reemplaza con tu usuario de producción)
define('DB_PASS', '1fkyEb9Ix');                          // Contraseña de la base de datos (reemplaza con tu contraseña de producción)
define('DB_NAME', 'paviotti_lanaturaleza');       // Nombre de la base de datos

// --- Rutas de la Aplicación ---

// App Root: Ruta absoluta al directorio 'app'. No es necesario modificar esto.
// Se usa para incluir archivos de forma segura con 'require_once'.
define('APPROOT', dirname(dirname(__FILE__)));

// URL Root: URL base de tu proyecto.
// ¡IMPORTANTE! Modifica esto para que coincida con la URL de tu sitio en producción.
// Ejemplo: https://www.tusitioweb.com
define('URLROOT', 'http://localhost/gestion_horas_extras');

// Site Name: El nombre de tu sitio. Se usa en el título y otras partes de la web.
define('SITENAME', 'Control de Horas Extras');

// --- Configuración del Reloj HIK-VISION ---
define('HIKVISION_IP', '10.10.4.44'); // La dirección IP de tu reloj en la red local
define('HIKVISION_USER', 'admin');         // El usuario para acceder al reloj
define('HIKVISION_PASS', 'Gelamon80');   // La contraseña del usuario del reloj
