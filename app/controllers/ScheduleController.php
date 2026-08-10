<?php

/**
 * Legacy: fichaje web. Redirige a Mis horarios (planificación + cambio de turno).
 */
class ScheduleController {
    public function __construct() {
        requireEmployeeRole();
    }

    public function index() {
        redirect('employee/misHorarios');
    }

    public function clockIn() {
        $_SESSION['flash_error'] = 'El fichaje web ya no está disponible. Consultá tus horarios planificados.';
        redirect('employee/misHorarios');
    }

    public function clockOut() {
        $_SESSION['flash_error'] = 'El fichaje web ya no está disponible. Consultá tus horarios planificados.';
        redirect('employee/misHorarios');
    }
}
