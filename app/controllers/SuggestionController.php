<?php

class SuggestionController {
    private $suggestionModel;
    private $userModel;

    public function __construct(){
        requireEmployeeRole();
        require_employee_portal_feature('suggestions');
        $this->suggestionModel = new Suggestion();
        $this->userModel = new User();
    }

    public function index(){
        $this->view('employee/suggestions');
    }

    public function submit(){
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            csrf_verify();

            $user = $this->userModel->getUserById($_SESSION['user_id']);
            if (!$user || empty($user->company_id)) {
                $_SESSION['flash_error'] = 'Tu usuario no tiene empresa asignada. No se puede enviar la sugerencia.';
                redirect('suggestion/index');
            }

            $data = [
                'company_id' => $user->company_id,
                'suggestion_text' => postString('suggestion_text'),
            ];

            if($data['suggestion_text'] !== ''){
                if($this->suggestionModel->addSuggestion($data)){
                    $_SESSION['flash_success'] = '¡Gracias! Tu sugerencia ha sido enviada.';
                    redirect('suggestion/index');
                }
                $_SESSION['flash_error'] = 'No se pudo guardar la sugerencia.';
                redirect('suggestion/index');
            }
            $_SESSION['flash_error'] = 'Por favor, escribe una sugerencia.';
            redirect('suggestion/index');
        }
        redirect('suggestion/index');
    }

    private function view($view, $data = []){
        if(file_exists('../app/views/' . $view . '.php')){
            require_once '../app/views/' . $view . '.php';
        } else {
            die('La vista no existe');
        }
    }
}
