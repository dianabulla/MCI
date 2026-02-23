<?php
/**
 * Controlador Base - Clase padre para todos los controladores
 */

class BaseController {
    /**
     * Cargar una vista
     */
    protected function view($viewName, $data = []) {
        // Extraer datos para que estén disponibles en la vista
        extract($data);
        
        // Construir ruta a la vista
        $viewPath = VIEWS . '/' . $viewName . '.php';
        
        if (file_exists($viewPath)) {
            require_once $viewPath;
        } else {
            die("Vista no encontrada: $viewPath");
        }
    }

    /**
     * Redirigir a otra URL
     */
    protected function redirect($url) {
        header("Location: " . PUBLIC_URL . "index.php?url=$url");
        exit;
    }

    /**
     * Devolver JSON
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
