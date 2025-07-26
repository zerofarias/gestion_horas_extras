<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/bootstrap.php (VERSIÓN COMPLETA Y VERIFICADA)
// Este archivo carga la configuración y el autoloader de clases.
// ----------------------------------------------------------------------

// 1. Cargar el archivo de configuración principal
// Define constantes para la base de datos, rutas (URLROOT), etc.
require_once 'config/config.php';

// 2. Cargar Helpers
// Contiene funciones de ayuda globales, como redirect() y manejo de sesión.
require_once 'helpers/session_helper.php';

// 3. Autoload de Clases (Core, Controladores y Modelos)
// Esta función se ejecuta automáticamente cuando se intenta usar una clase
// que no ha sido incluida previamente.
spl_autoload_register(function($className){
    
    // Define las rutas donde pueden estar los archivos de las clases.
    // La ruta '../app/' es relativa a public/index.php, por lo que es correcta.
    $paths = [
        '../app/controllers/' . $className . '.php',
        '../app/models/' . $className . '.php',
        '../app/' . $className . '.php' // Para clases en la raíz de 'app', como Core.php
    ];

    // Recorre las rutas y, si encuentra el archivo, lo incluye
    // para que la clase esté disponible.
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return; // Detiene la búsqueda una vez que encuentra y carga la clase.
        }
    }
});
