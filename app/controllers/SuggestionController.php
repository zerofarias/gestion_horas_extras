<?php
// ----------------------------------------------------------------------
// ARCHIVO 4: app/controllers/SuggestionController.php (NUEVO ARCHIVO)
// Este nuevo controlador manejará las sugerencias del lado del empleado.
// Debes CREAR este archivo en la ruta app/controllers/.
// ----------------------------------------------------------------------

class SuggestionController {
    private $suggestionModel;
    private $userModel;

    public function __construct(){
        if(!isLoggedIn()){ redirect('login'); }
        $this->suggestionModel = new Suggestion();
        $this->userModel = new User();
    }

    public function index(){
        $this->view('employee/suggestions');
    }

    public function submit(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            // Obtenemos la empresa del usuario actual para asociar la sugerencia
            $user = $this->userModel->getUserById($_SESSION['user_id']);

            $data = [
                'company_id' => $user->company_id,
                'suggestion_text' => trim($_POST['suggestion_text'])
            ];

            if(!empty($data['suggestion_text'])){
                if($this->suggestionModel->createSuggestion($data)){
                    $_SESSION['flash_success'] = '¡Gracias! Tu sugerencia ha sido enviada de forma anónima.';
                    redirect('suggestion/index');
                } else {
                    die('Algo salió mal.');
                }
            } else {
                $_SESSION['flash_error'] = 'Por favor, escribe una sugerencia.';
                redirect('suggestion/index');
            }
        } else {
            redirect('suggestion/index');
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