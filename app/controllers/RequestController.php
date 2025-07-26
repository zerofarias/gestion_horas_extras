<?php
// ----------------------------------------------------------------------
// ARCHIVO 1: app/controllers/RequestController.php (ACTUALIZADO)
// Se verifica que el método `create()` establezca correctamente el
// mensaje de éxito en la sesión.
// ----------------------------------------------------------------------

class RequestController {
    private $requestModel;

    public function __construct(){
        if(!isLoggedIn()){ redirect('login'); }
        $this->requestModel = new Request();
    }

    public function index(){
        $requests = $this->requestModel->getRequestsByUserId($_SESSION['user_id']);
        $requestTypes = $this->requestModel->getRequestTypes();
        
        $data = [
            'requests' => $requests,
            'requestTypes' => $requestTypes
        ];
        
        $this->view('employee/requests', $data);
    }

    public function create(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $data = [
                'user_id' => $_SESSION['user_id'],
                'request_type_id' => trim($_POST['request_type_id']),
                'start_date' => trim($_POST['start_date']),
                'end_date' => !empty($_POST['end_date']) ? trim($_POST['end_date']) : NULL,
                'reason' => trim($_POST['reason'])
            ];

            if($this->requestModel->createRequest($data)){
                // Esta línea prepara el mensaje para la alerta.
                $_SESSION['flash_success'] = '¡Tu solicitud ha sido enviada con éxito!';
                redirect('request/index');
            } else {
                die('Algo salió mal.');
            }
        } else {
            redirect('request/index');
        }
    }

    public function view($view, $data = []){
        if(file_exists('../app/views/' . $view . '.php')){
            require_once '../app/views/' . $view . '.php';
        } else {
            die('La vista no existe');
        }
    }
}
?>