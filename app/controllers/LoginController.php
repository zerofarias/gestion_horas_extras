<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/controllers/LoginController.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

class LoginController {
    private $userModel;

    /**
     * El constructor se ejecuta al crear la instancia del controlador.
     * Carga el modelo 'User' para interactuar con la base de datos.
     */
    public function __construct(){
        $this->userModel = new User();
    }

    /**
     * Muestra la página de inicio de sesión (método por defecto).
     * @param array $data - Datos opcionales para pasar a la vista (ej. errores).
     */
    public function index($data = []){
        // Si el usuario ya está logueado, lo redirige a su panel correspondiente.
        if (isLoggedIn()) {
            redirect(hasRole('admin') ? 'admin/dashboard' : 'employee/dashboard');
        }
        
        // Carga la vista del formulario de login.
        $this->view('login/index', $data);
    }

    /**
     * Procesa los datos enviados desde el formulario de login.
     */
    public function process(){
        // Se asegura de que la solicitud sea de tipo POST.
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            
            $loginData = trim($_POST['login_data']);
            $data = [
                'login_data' => $loginData,
                'error' => ''
            ];

            // Valida el formato 'usuario+contraseña'.
            if(empty($loginData) || !strpos($loginData, '+')){
                 $data['error'] = 'Formato inválido. Use: usuario+contraseña';
                 $this->index($data);
                 return;
            }
            
            // Separa el string en usuario y contraseña.
            list($username, $password) = explode('+', $loginData, 2);

            // Intenta loguear al usuario a través del modelo.
            $loggedInUser = $this->userModel->login($username, $password);

            if($loggedInUser){
                // Comprueba si el usuario está inactivo.
                if($loggedInUser === 'inactive'){
                    $data['error'] = 'Este usuario ha sido desactivado.';
                    $this->index($data);
                } else {
                    // Si el login es exitoso, crea la sesión.
                    $this->createUserSession($loggedInUser);
                }
            } else {
                // Si las credenciales son incorrectas.
                $data['error'] = 'Usuario o Contraseña incorrectos.';
                $this->index($data);
            }
        } else {
            // Si se accede directamente a la URL, redirige al login.
            redirect('login');
        }
    }
    
    /**
     * Crea las variables de sesión para el usuario que ha iniciado sesión.
     * @param object $user - El objeto de usuario con los datos de la base de datos.
     */
    public function createUserSession($user){
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_username'] = $user->username;
        $_SESSION['user_full_name'] = $user->full_name;
        $_SESSION['user_role'] = $user->role;
        
        // --- INICIO DE CAMBIO ---
        if($user->role == 'admin'){
            redirect('admin/dashboard');
        } else {
            // Los empleados ahora son redirigidos a su nuevo menú principal.
            redirect('employee/index');
        }
        // --- FIN DE CAMBIO ---
    }

    /**
     * Destruye la sesión del usuario para cerrar la sesión.
     */
    public function logout(){
        unset($_SESSION['user_id']);
        unset($_SESSION['user_username']);
        unset($_SESSION['user_full_name']); // Limpia el nombre completo de la sesión
        unset($_SESSION['user_role']);
        session_destroy();
        redirect('login');
    }

    /**
     * Carga un archivo de vista y le pasa datos.
     * @param string $view - El nombre de la vista (ej. 'login/index').
     * @param array $data - Los datos a usar en la vista.
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
