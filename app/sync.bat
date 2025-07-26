@echo OFF
REM --- Script para ejecutar la sincronizacion de PHP ---

REM 1. Ruta al ejecutable de PHP en tu instalacion de XAMPP
SET PHP_EXE="C:\xampp54\php\php.exe"

REM 2. Ruta a tu script de sincronizacion
SET SCRIPT_PATH="C:\xampp54\htdocs\gestion_horas_extras\cron_sync.php"

ECHO Ejecutando la sincronizacion...
%PHP_EXE% -f %SCRIPT_PATH%

ECHO Proceso finalizado.
