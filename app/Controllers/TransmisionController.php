<?php
/**
 * Controlador Transmision - Gestión de transmisiones de YouTube
 */

class TransmisionController extends BaseController {
    private $transmision;

    private function tienePermiso($accion = 'ver') {
        return AuthController::esAdministrador() || AuthController::tienePermiso('transmisiones', $accion);
    }

    public function __construct() {
        require_once APP . '/Models/Transmision.php';
        $this->transmision = new Transmision();
    }

    /**
     * Listar todas las transmisiones (PRIVADA - Admin)
     */
    public function listar() {
        if (!$this->tienePermiso('ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $transmisiones = $this->transmision->obtenerTodas();
        
        $estadisticas = [
            'en_vivo' => $this->transmision->contarPorEstado('en_vivo'),
            'proximamente' => $this->transmision->contarPorEstado('proximamente'),
            'finalizada' => $this->transmision->contarPorEstado('finalizada')
        ];

        $this->view('transmisiones/listar', [
            'title' => 'Gestión de Transmisiones',
            'transmisiones' => $transmisiones,
            'estadisticas' => $estadisticas
        ]);
    }

    public function exportarExcel() {
        if (!$this->tienePermiso('ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $transmisiones = $this->transmision->obtenerTodas();

        $rows = [];
        foreach ($transmisiones as $transmision) {
            $rows[] = [
                (string)($transmision['Nombre'] ?? ''),
                (string)($transmision['URL_YouTube'] ?? ''),
                (string)($transmision['Fecha_Transmision'] ?? ''),
                (string)($transmision['Hora_Transmision'] ?? ''),
                (string)($transmision['Estado'] ?? ''),
                (string)($transmision['Descripcion'] ?? '')
            ];
        }

        $this->exportCsv(
            'transmisiones_' . date('Ymd_His'),
            ['Nombre', 'URL YouTube', 'Fecha', 'Hora', 'Estado', 'Descripcion'],
            $rows
        );
    }

    /**
     * Vista para crear nueva transmisión (PRIVADA - Admin)
     */
    public function crear() {
        if (!$this->tienePermiso('crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $this->view('transmisiones/crear', [
            'title' => 'Nueva Transmisión',
            'fechaHoy' => date('Y-m-d'),
            'horaActual' => date('H:i')
        ]);
    }

    /**
     * Guardar nueva transmisión (PRIVADA - Admin)
     */
    public function guardar() {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if (!$this->tienePermiso('crear')) {
            if ($isAjax) {
                $this->json(['error' => 'Acceso denegado'], 403);
            }
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($isAjax) {
                $this->json(['error' => 'Método no permitido'], 405);
            }
            header('Location: ' . BASE_URL . '/public/?url=transmisiones/crear');
            exit;
        }

        try {
            $nombre = $_POST['nombre'] ?? '';
            $url = $_POST['url'] ?? '';
            $fecha = $_POST['fecha'] ?? '';
            $hora = $_POST['hora'] ?? '00:00';
            $estado = $_POST['estado'] ?? 'proximamente';
            $descripcion = $_POST['descripcion'] ?? '';

            // Validaciones
            if (empty($nombre) || empty($url) || empty($fecha)) {
                if ($isAjax) {
                    $this->json([
                        'error' => 'Nombre, URL y fecha son obligatorios'
                    ], 400);
                }
                header('Location: ' . BASE_URL . '/public/?url=transmisiones/crear&error=' . urlencode('Nombre, URL y fecha son obligatorios'));
                exit;
            }

            // Validar URL de YouTube
            if (!$this->validarURLYouTube($url)) {
                if ($isAjax) {
                    $this->json([
                        'error' => 'URL de YouTube inválida'
                    ], 400);
                }
                header('Location: ' . BASE_URL . '/public/?url=transmisiones/crear&error=' . urlencode('URL de YouTube inválida'));
                exit;
            }

            // Obtener ID del usuario actual
            $idUsuario = $_SESSION['id_persona'] ?? null;

            $id = $this->transmision->crear(
                $nombre,
                $url,
                $fecha,
                $hora,
                $estado,
                $descripcion,
                $idUsuario
            );

            if ($isAjax) {
                $this->json([
                    'success' => true,
                    'message' => 'Transmisión creada exitosamente',
                    'id' => $id
                ]);
            }

            header('Location: ' . BASE_URL . '/public/?url=transmisiones&success=' . urlencode('Transmisión creada exitosamente'));
            exit;

        } catch (Exception $e) {
            error_log("Error al guardar transmisión: " . $e->getMessage());
            if ($isAjax) {
                $this->json([
                    'error' => 'Error al guardar la transmisión'
                ], 500);
            }

            header('Location: ' . BASE_URL . '/public/?url=transmisiones/crear&error=' . urlencode('Error al guardar la transmisión'));
            exit;
        }
    }

    /**
     * Vista para editar transmisión (PRIVADA - Admin)
     */
    public function editar() {
        if (!$this->tienePermiso('editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;

        if (!$id) {
            $this->redirect('transmisiones');
        }

        $transmision = $this->transmision->obtenerPorId($id);

        if (!$transmision) {
            $this->redirect('transmisiones');
        }

        $this->view('transmisiones/editar', [
            'title' => 'Editar Transmisión',
            'transmision' => $transmision
        ]);
    }

    /**
     * Actualizar transmisión (PRIVADA - Admin)
     */
    public function actualizar() {
        if (!$this->tienePermiso('editar')) {
            $this->json(['error' => 'Acceso denegado'], 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        try {
            $id = $_POST['id'] ?? '';
            $nombre = $_POST['nombre'] ?? '';
            $url = $_POST['url'] ?? '';
            $fecha = $_POST['fecha'] ?? '';
            $hora = $_POST['hora'] ?? '00:00';
            $estado = $_POST['estado'] ?? 'proximamente';
            $descripcion = $_POST['descripcion'] ?? '';

            if (empty($id) || empty($nombre) || empty($url) || empty($fecha)) {
                $this->json([
                    'error' => 'ID, nombre, URL y fecha son obligatorios'
                ], 400);
            }

            if (!$this->validarURLYouTube($url)) {
                $this->json([
                    'error' => 'URL de YouTube inválida'
                ], 400);
            }

            $this->transmision->actualizar($id, $nombre, $url, $fecha, $hora, $estado, $descripcion);

            $this->json([
                'success' => true,
                'message' => 'Transmisión actualizada exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error al actualizar transmisión: " . $e->getMessage());
            $this->json([
                'error' => 'Error al actualizar la transmisión'
            ], 500);
        }
    }

    /**
     * Cambiar estado de transmisión (PRIVADA - Admin)
     */
    public function cambiarEstado() {
        if (!$this->tienePermiso('editar')) {
            $this->json(['error' => 'Acceso denegado'], 403);
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        try {
            $id = $_POST['id'] ?? '';
            $estado = $_POST['estado'] ?? '';

            if (empty($id) || empty($estado)) {
                $this->json([
                    'error' => 'ID y estado son obligatorios'
                ], 400);
            }

            $this->transmision->cambiarEstado($id, $estado);

            $this->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente'
            ]);

        } catch (Exception $e) {
            $this->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Eliminar transmisión (PRIVADA - Admin)
     */
    public function eliminar() {
        if (!$this->tienePermiso('eliminar')) {
            $this->json(['error' => 'Acceso denegado'], 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        try {
            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                $this->json([
                    'error' => 'ID es obligatorio'
                ], 400);
            }

            $this->transmision->eliminar($id);

            $this->json([
                'success' => true,
                'message' => 'Transmisión eliminada exitosamente'
            ]);

        } catch (Exception $e) {
            error_log("Error al eliminar transmisión: " . $e->getMessage());
            $this->json([
                'error' => 'Error al eliminar la transmisión'
            ], 500);
        }
    }

    /**
     * Ver transmisiones públicamente (PÚBLICA - Sin login)
     */
    public function verPublico() {
        $transmisionEnVivo = $this->transmision->obtenerEnVivo();
        $transmisionesProximas = $this->transmision->obtenerProximas(5);
        $transmisionesFinalizadas = $this->transmision->obtenerFinalizadas(10);

        $this->view('transmisiones/publico', [
            'title' => 'Transmisiones en Vivo',
            'transmisionEnVivo' => $transmisionEnVivo,
            'transmisionesProximas' => $transmisionesProximas,
            'transmisionesFinalizadas' => $transmisionesFinalizadas
        ]);
    }

    /**
     * Buscar transmisiones
     */
    public function buscar() {
        if (!$this->tienePermiso('ver')) {
            $this->json(['error' => 'Acceso denegado'], 403);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Método no permitido'], 405);
        }

        try {
            $termino = $_POST['termino'] ?? '';

            if (empty($termino)) {
                $this->json([
                    'error' => 'Término de búsqueda requerido'
                ], 400);
            }

            $resultados = $this->transmision->buscar($termino);

            $this->json([
                'success' => true,
                'resultados' => $resultados,
                'total' => count($resultados)
            ]);

        } catch (Exception $e) {
            error_log("Error en búsqueda: " . $e->getMessage());
            $this->json([
                'error' => 'Error en la búsqueda'
            ], 500);
        }
    }

    /**
     * Obtener transmisión en vivo actual (API JSON)
     */
    public function obtenerEnVivo() {
        try {
            $transmision = $this->transmision->obtenerEnVivo();
            $this->json([
                'success' => true,
                'transmision' => $transmision
            ]);
        } catch (Exception $e) {
            $this->json([
                'error' => 'Error al obtener transmisión'
            ], 500);
        }
    }

    /**
     * Validar URL de YouTube
     */
    private function validarURLYouTube($url) {
        // Patrones válidos de YouTube
        $patrones = [
            '/youtube\.com\/watch\?v=/',
            '/youtu\.be\//',
            '/youtube\.com\/embed\//',
            '/youtube\.com\/v\/'
        ];

        foreach ($patrones as $patron) {
            if (preg_match($patron, $url)) {
                return true;
            }
        }

        return false;
    }
}
