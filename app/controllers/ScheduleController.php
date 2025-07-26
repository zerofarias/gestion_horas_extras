<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/controllers/ScheduleController.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

class ScheduleController {
    private $scheduleModel;
    private $overtimeModel;
    private $workScheduleModel;

    /**
     * El constructor se ejecuta al crear la instancia del controlador.
     * - Verifica que el usuario haya iniciado sesión.
     * - Carga todos los modelos necesarios.
     */
    public function __construct(){
        if(!isLoggedIn()){ 
            redirect('login'); 
        }
        $this->scheduleModel = new Schedule();
        $this->overtimeModel = new Overtime();
        $this->workScheduleModel = new WorkSchedule();
    }

    /**
     * Muestra la página principal de "Mis Horarios" para el empleado.
     * Carga el estado del fichaje del día, el resumen semanal y el horario planificado.
     */
    public function index(){
        $userId = $_SESSION['user_id'];
        $todaysEntry = $this->scheduleModel->getTodaysEntry($userId);
        $weeklyHours = $this->scheduleModel->getWeeklyHours($userId);
        $weekEntries = $this->scheduleModel->getWeekEntries($userId);
        $plannedSchedule = $this->workScheduleModel->getUserScheduleForCurrentWeek($userId);
        
        $data = [
            'todaysEntry' => $todaysEntry,
            'weeklyHours' => $weeklyHours,
            'weekEntries' => $weekEntries,
            'plannedSchedule' => $plannedSchedule
        ];
        
        $this->view('employee/schedule', $data);
    }

    /**
     * Registra la hora de entrada del empleado para el día actual.
     */
    public function clockIn(){
        if($this->scheduleModel->clockIn($_SESSION['user_id'])){
            $_SESSION['flash_success'] = '¡Entrada registrada! ¡Que tengas un buen día!';
        } else {
            $_SESSION['flash_error'] = 'Ya has registrado una entrada hoy.';
        }
        redirect('schedule/index');
    }

    /**
     * Registra la hora de salida del empleado y comprueba si se generaron horas extras.
     */
    public function clockOut(){
        $todaysEntry = $this->scheduleModel->getTodaysEntry($_SESSION['user_id']);
        if($todaysEntry && is_null($todaysEntry->exit_time)){
            if($this->scheduleModel->clockOut($todaysEntry->id)){
                $this->checkAndCreateOvertime($_SESSION['user_id']);
                $_SESSION['flash_success'] = '¡Salida registrada! ¡Hasta mañana!';
            } else {
                $_SESSION['flash_error'] = 'No se pudo registrar la salida.';
            }
        }
        redirect('schedule/index');
    }
    
    /**
     * Comprueba si el total de horas semanales supera las 45 y, si es así,
     * crea una nueva entrada de horas extras.
     * @param int $userId El ID del usuario a comprobar.
     */
    private function checkAndCreateOvertime($userId){
        $weeklyHours = $this->scheduleModel->getWeeklyHours($userId);
        
        if($weeklyHours > 45){
            $overtimeHours = $weeklyHours - 45;
            
            // Lógica simple para registrar las horas excedentes.
            // Se asume que son al 50% por defecto.
            $data = [
                'user_id' => $userId,
                'date' => date('Y-m-d'),
                'start_time' => '00:00', // Placeholder, ya que es un cálculo semanal
                'end_time' => '00:00',   // Placeholder
                'is_holiday' => 0,
                'reason' => 'Exceso de 45hs semanales',
                'hours_50' => $overtimeHours,
                'hours_100' => 0
            ];
            
            $this->overtimeModel->addCalculatedOvertime($data);
        }
    }

    /**
     * Carga un archivo de vista y le pasa datos.
     */
    public function view($view, $data = []){
        if(file_exists('../app/views/' . $view . '.php')){
            require_once '../app/views/' . $view . '.php';
        } else {
            die('Error: La vista no existe: ' . $view);
        }
    }
}
?>
