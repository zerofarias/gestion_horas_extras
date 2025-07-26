<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/controllers/EmployeeController.php (VERSIÓN CORREGIDA Y FINAL)
// ----------------------------------------------------------------------

class EmployeeController {
    private $overtimeModel;
    // Se eliminan las dependencias a los modelos de horarios que no se usan aquí.
    // private $scheduleModel; 
    // private $workScheduleModel;

    /**
     * El constructor se ejecuta al crear la instancia del controlador.
     * - Verifica que el usuario tenga el rol de 'empleado'.
     * - Carga los modelos necesarios.
     */
    public function __construct(){
        if(!hasRole('empleado')){
            redirect('login');
        }
        $this->overtimeModel = new Overtime();
        // Las siguientes líneas se eliminan para evitar el error, ya que no se usan en este controlador.
        // $this->scheduleModel = new Schedule();
        // $this->workScheduleModel = new WorkSchedule();
    }
    public function index(){
        $this->view('employee/index');
    }

    /**
     * Muestra el panel para cargar horas extras.
     * (Anteriormente era la página principal del empleado).
     */
    public function dashboard(){
        $overtimeEntries = $this->overtimeModel->getPendingEntriesByUserId($_SESSION['user_id']);
        $data = [ 'entries' => $overtimeEntries ];
        $this->view('employee/dashboard', $data);
    }   
    
    
    /**
     * Procesa la solicitud para añadir una nueva entrada de horas extras.
     * Incluye la validación para prevenir duplicados.
     */
    public function add(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

            $data = [
                'user_id' => $_SESSION['user_id'],
                'date' => trim($_POST['date']),
                'start_time' => trim($_POST['start_time']),
                'end_time' => trim($_POST['end_time']),
                'is_holiday' => (int)$_POST['is_holiday'],
                'reason' => trim($_POST['reason'])
            ];

            // Validación de campos y duplicados
            if(empty($data['date']) || empty($data['start_time']) || empty($data['end_time']) || empty($data['reason'])){
                $_SESSION['flash_error'] = 'Por favor, completa todos los campos.';
                redirect('employee/dashboard');
            } elseif ($this->overtimeModel->checkForDuplicateEntry($data)) {
                // Si el modelo encuentra un duplicado, se establece un mensaje de error.
                $_SESSION['flash_error'] = 'Error: Ya existe una carga de horas idéntica para esta fecha y horario.';
                redirect('employee/dashboard');
            } else {
                // Si no hay duplicados, se procede a guardar.
                if($this->overtimeModel->addEntry($data)){
                    $_SESSION['flash_success'] = '¡Horas extras guardadas correctamente!';
                    redirect('employee/dashboard');
                } else {
                    die('Algo salió mal al intentar guardar las horas.');
                }
            }
        } else {
            redirect('employee/dashboard');
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
