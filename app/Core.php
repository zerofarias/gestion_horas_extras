<?php
// ----------------------------------------------------------------------
// ARCHIVO: app/Core.php (VERSIÓN COMPLETA Y FINAL)
// ----------------------------------------------------------------------

/*
 * Clase Principal de la Aplicación (Router)
 * Crea la URL y carga el controlador correspondiente.
 * Formato de la URL: /controlador/metodo/parametros
 */
class Core {
    // Propiedades con valores por defecto para la página de inicio.
    protected $currentController = 'LoginController';
    protected $currentMethod = 'index';
    protected $params = [];

    public function __construct(){
        // Obtiene la URL parseada.
        $url = $this->getUrl();

        // 1. Busca el controlador en la carpeta app/controllers.
        // El primer elemento de la URL ($url[0]) es el nombre del controlador.
        if(isset($url[0]) && file_exists('../app/controllers/' . ucwords($url[0]) . 'Controller.php')){
            // Si el archivo del controlador existe, se establece como el controlador actual.
            $this->currentController = ucwords($url[0]) . 'Controller';
            // Se elimina el controlador del array $url para dejar solo los parámetros.
            unset($url[0]);
        }
        
        // Carga el archivo del controlador (ya sea el por defecto o el de la URL).
        require_once '../app/controllers/'. $this->currentController . '.php';
        
        // Crea una instancia del controlador. E.g., $LoginController = new LoginController;
        $this->currentController = new $this->currentController;

        // 2. Busca el método en el controlador.
        // El segundo elemento de la URL ($url[1]) es el nombre del método.
        if(isset($url[1])){
            // Comprueba si el método existe dentro del controlador instanciado.
            if(method_exists($this->currentController, $url[1])){
                $this->currentMethod = $url[1];
                // Se elimina el método del array $url.
                unset($url[1]);
            }
        }
        
        // 3. Obtiene los parámetros de la URL.
        // Lo que queda en el array $url son los parámetros.
        $this->params = $url ? array_values($url) : [];
        
        // 4. Llama al método del controlador con los parámetros.
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }
    
    /**
     * Obtiene y parsea la URL desde el parámetro 'url' definido en public/.htaccess
     * @return array La URL descompuesta en un array.
     */
    public function getUrl(){
        if(isset($_GET['url'])){
            $url = rtrim($_GET['url'], '/'); // Quita la barra final (/)
            $url = filter_var($url, FILTER_SANITIZE_URL); // Sanea la URL
            $url = explode('/', $url); // Divide la URL en un array
            return $url;
        }
        return []; // Devuelve un array vacío si no hay URL
    }
}
?>
