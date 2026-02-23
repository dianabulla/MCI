<?php
/**
 * Clase Base Controller
 * Proporciona funcionalidad común para todos los controladores
 */

namespace App\Controllers;

class BaseController {
    protected array $data = [];

    /**
     * Constructor del controlador
     */
    public function __construct() {
        // Inicializar si es necesario
    }

    /**
     * Asigna datos a la vista
     * 
     * @param string $key
     * @param mixed $value
     */
    protected function assign(string $key, mixed $value): void {
        $this->data[$key] = $value;
    }

    /**
     * Asigna múltiples datos a la vez
     * 
     * @param array $data
     */
    protected function assignArray(array $data): void {
        $this->data = array_merge($this->data, $data);
    }

    /**
     * Renderiza una vista
     * 
     * @param string $view Ruta de la vista sin extensión
     * @param array $data Datos adicionales
     */
    protected function render(string $view, array $data = []): void {
        $this->data = array_merge($this->data, $data);
        $this->includeLayout($view);
    }

    /**
     * Renderiza una vista sin layout
     * 
     * @param string $view
     * @param array $data
     */
    protected function renderJson(mixed $data): void {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Incluye el layout principal
     * 
     * @param string $view
     */
    private function includeLayout(string $view): void {
        extract($this->data);
        
        ob_start();
        include VIEWS_PATH . '/' . $view . '.php';
        $content = ob_get_clean();
        
        include VIEWS_PATH . '/layout/base.php';
    }

    /**
     * Obtiene datos POST
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function post(string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $_POST;
        }
        
        return $_POST[$key] ?? $default;
    }

    /**
     * Obtiene datos GET
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get(string $key = null, mixed $default = null): mixed {
        if ($key === null) {
            return $_GET;
        }
        
        return $_GET[$key] ?? $default;
    }

    /**
     * Valida que existan ciertos campos
     * 
     * @param array $required
     * @param array $data
     * @return array Errores de validación
     */
    protected function validate(array $required, array $data = null): array {
        $data = $data ?? $_REQUEST;
        $errors = [];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[$field] = "El campo {$field} es requerido";
            }
        }

        return $errors;
    }

    /**
     * Envía una notificación al usuario
     * 
     * @param string $message
     * @param string $type success|error|warning|info
     */
    protected function notify(string $message, string $type = 'info'): void {
        $_SESSION['notification'] = [
            'message' => $message,
            'type' => $type
        ];
    }

    /**
     * Redirige a una URL
     * 
     * @param string $url
     */
    protected function redirect(string $url): void {
        header('Location: ' . APP_URL . '/' . $url);
        exit;
    }

    /**
     * Retorna una respuesta JSON
     * 
     * @param bool $success
     * @param string $message
     * @param array $data
     */
    protected function json(bool $success, string $message, array $data = []): void {
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message,
            'data' => $data
        ];

        echo json_encode($response);
        exit;
    }

    /**
     * Log de acciones
     * 
     * @param string $accion
     */
    protected function log(string $accion): void {
        $fecha = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
        $mensaje = "[{$fecha}] {$accion} - IP: {$ip}" . PHP_EOL;
        
        file_put_contents(BASE_PATH . '/logs/acciones.log', $mensaje, FILE_APPEND);
    }
}
