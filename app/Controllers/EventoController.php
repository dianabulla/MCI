<?php
/**
 * Controlador Evento
 */

require_once APP . '/Models/Evento.php';
require_once APP . '/Models/EventoModulo.php';
require_once APP . '/Helpers/DataIsolation.php';

class EventoController extends BaseController {
    private $eventoModel;
    private $eventoModuloModel;
    private $uploadDir;
    private $uploadUrlBase;

    public function __construct() {
        $this->eventoModel = new Evento();
        $this->eventoModuloModel = new EventoModulo();
        $this->uploadDir = ROOT . '/public/uploads/eventos';
        $this->uploadUrlBase = rtrim(PUBLIC_URL, '/') . '/uploads/eventos';
    }

    private function getModuloConfig($tipo) {
        $tipo = strtolower(trim((string)$tipo));
        $map = [
            'universidad_vida' => [
                'tipo' => 'universidad_vida',
                'titulo' => 'Universidad de la vida',
                'route_privada' => 'eventos/universidad-vida',
                'route_publica' => 'eventos/universidad-vida/publico'
            ],
            'capacitacion_destino' => [
                'tipo' => 'capacitacion_destino',
                'titulo' => 'Capacitación destino',
                'route_privada' => 'eventos/capacitacion-destino',
                'route_publica' => 'eventos/capacitacion-destino/publico'
            ]
        ];

        return $map[$tipo] ?? null;
    }

    private function redirigirModulo($tipo) {
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $this->redirect($config['route_privada']);
    }

    private function renderModuloContenido($tipo) {
        if (!AuthController::tienePermiso('eventos', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $items = $this->eventoModuloModel->getByModulo($config['tipo']);
        $idEditar = (int)($_GET['editar'] ?? 0);
        $itemEditar = null;
        if ($idEditar > 0) {
            $item = $this->eventoModuloModel->getById($idEditar);
            if (!empty($item) && (string)($item['Tipo_Modulo'] ?? '') === $config['tipo']) {
                $itemEditar = $item;
            }
        }

        $urlPublica = $this->buildAbsolutePublicUrl($config['route_publica']);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($urlPublica);

        $error = $_SESSION['evento_modulo_error'] ?? null;
        unset($_SESSION['evento_modulo_error']);

        $this->view('eventos/modulo_contenido', [
            'modulo' => $config,
            'items' => $items,
            'itemEditar' => $itemEditar,
            'urlPublica' => $urlPublica,
            'qrUrl' => $qrUrl,
            'error' => $error
        ]);
    }

    public function index() {
        // Generar filtro según el rol del usuario
        $filtroEventos = DataIsolation::generarFiltroEventos();
        
        // Obtener eventos con aislamiento de rol
        $eventos = $this->eventoModel->getAllWithRole($filtroEventos);
        $urlEventosPublicos = $this->buildAbsolutePublicUrl('eventos/proximos');
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($urlEventosPublicos);

        $this->view('eventos/lista', [
            'eventos' => $eventos,
            'urlEventosPublicos' => $urlEventosPublicos,
            'qrUrl' => $qrUrl
        ]);
    }

    public function exportarExcel() {
        if (!AuthController::tienePermiso('eventos', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $filtroEventos = DataIsolation::generarFiltroEventos();
        $eventos = $this->eventoModel->getAllWithRole($filtroEventos);

        $rows = [];
        foreach ($eventos as $evento) {
            $rows[] = [
                (string)($evento['Nombre_Evento'] ?? ''),
                (string)($evento['Fecha_Evento'] ?? ''),
                (string)($evento['Hora_Evento'] ?? ''),
                (string)($evento['Lugar_Evento'] ?? ''),
                (string)($evento['Descripcion_Evento'] ?? '')
            ];
        }

        $this->exportCsv(
            'eventos_' . date('Ymd_His'),
            ['Evento', 'Fecha', 'Hora', 'Lugar', 'Descripcion'],
            $rows
        );
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('eventos', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $rutaImagen = $this->procesarArchivo('imagen_evento', ['jpg', 'jpeg', 'png', 'webp', 'gif'], 5 * 1024 * 1024);
                $rutaVideo = $this->procesarArchivo('video_evento', ['mp4', 'webm', 'mov', 'm4v'], 25 * 1024 * 1024);

                $data = [
                    'Nombre_Evento' => trim($_POST['nombre_evento'] ?? ''),
                    'Descripcion_Evento' => trim($_POST['descripcion_evento'] ?? ''),
                    'Fecha_Evento' => $_POST['fecha_evento'] ?? '',
                    'Hora_Evento' => $_POST['hora_evento'] ?? '',
                    'Lugar_Evento' => trim($_POST['lugar_evento'] ?? ''),
                    'Imagen_Evento' => $rutaImagen,
                    'Video_Evento' => $rutaVideo
                ];

                $this->eventoModel->create($data);
                $this->redirect('eventos');
            } catch (Exception $e) {
                $_SESSION['evento_error'] = $e->getMessage();
                $this->redirect('eventos/crear');
            }
        } else {
            $error = $_SESSION['evento_error'] ?? null;
            unset($_SESSION['evento_error']);
            $this->view('eventos/formulario', ['error' => $error]);
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('eventos', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('eventos');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $eventoActual = $this->eventoModel->getById($id);

                $data = [
                    'Nombre_Evento' => trim($_POST['nombre_evento'] ?? ''),
                    'Descripcion_Evento' => trim($_POST['descripcion_evento'] ?? ''),
                    'Fecha_Evento' => $_POST['fecha_evento'] ?? '',
                    'Hora_Evento' => $_POST['hora_evento'] ?? '',
                    'Lugar_Evento' => trim($_POST['lugar_evento'] ?? '')
                ];

                $eliminarImagen = !empty($_POST['eliminar_imagen']);
                $eliminarVideo = !empty($_POST['eliminar_video']);

                $nuevaImagen = $this->procesarArchivo('imagen_evento', ['jpg', 'jpeg', 'png', 'webp', 'gif'], 5 * 1024 * 1024);
                $nuevoVideo = $this->procesarArchivo('video_evento', ['mp4', 'webm', 'mov', 'm4v'], 25 * 1024 * 1024);

                $data['Imagen_Evento'] = $eventoActual['Imagen_Evento'] ?? null;
                $data['Video_Evento'] = $eventoActual['Video_Evento'] ?? null;

                if ($eliminarImagen) {
                    $this->eliminarArchivoFisico($eventoActual['Imagen_Evento'] ?? null);
                    $data['Imagen_Evento'] = null;
                }

                if ($eliminarVideo) {
                    $this->eliminarArchivoFisico($eventoActual['Video_Evento'] ?? null);
                    $data['Video_Evento'] = null;
                }

                if ($nuevaImagen !== null) {
                    $this->eliminarArchivoFisico($eventoActual['Imagen_Evento'] ?? null);
                    $data['Imagen_Evento'] = $nuevaImagen;
                }

                if ($nuevoVideo !== null) {
                    $this->eliminarArchivoFisico($eventoActual['Video_Evento'] ?? null);
                    $data['Video_Evento'] = $nuevoVideo;
                }

                $this->eventoModel->update($id, $data);
                $this->redirect('eventos');
            } catch (Exception $e) {
                $_SESSION['evento_error'] = $e->getMessage();
                $this->redirect('eventos/editar&id=' . $id);
            }
        } else {
            $error = $_SESSION['evento_error'] ?? null;
            unset($_SESSION['evento_error']);

            $data = [
                'evento' => $this->eventoModel->getById($id),
                'error' => $error
            ];
            $this->view('eventos/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('eventos', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $evento = $this->eventoModel->getById($id);
            $this->eliminarArchivoFisico($evento['Imagen_Evento'] ?? null);
            $this->eliminarArchivoFisico($evento['Video_Evento'] ?? null);
            $this->eventoModel->delete($id);
        }
        
        $this->redirect('eventos');
    }

    public function proximosPublico() {
        $eventos = $this->eventoModel->getUpcoming();
        $this->view('eventos/proximos_publico', ['eventos' => $eventos]);
    }

    public function universidadVida() {
        $this->renderModuloContenido('universidad_vida');
    }

    public function capacitacionDestino() {
        $this->renderModuloContenido('capacitacion_destino');
    }

    public function guardarModuloContenido() {
        if (!AuthController::tienePermiso('eventos', 'crear') && !AuthController::tienePermiso('eventos', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('eventos');
            return;
        }

        $tipo = (string)($_POST['tipo_modulo'] ?? '');
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $idContenido = (int)($_POST['id_contenido'] ?? 0);

        try {
            $nuevaImagen = $this->procesarArchivo('imagen_modulo', ['jpg', 'jpeg', 'png', 'webp', 'gif'], 5 * 1024 * 1024);
            $nuevoVideo = $this->procesarArchivo('video_modulo', ['mp4', 'webm', 'mov', 'm4v'], 25 * 1024 * 1024);

            $data = [
                'Tipo_Modulo' => $config['tipo'],
                'Titulo' => trim((string)($_POST['titulo'] ?? '')),
                'Parrafo' => trim((string)($_POST['parrafo'] ?? '')),
                'Orden' => max(0, (int)($_POST['orden'] ?? 0)),
                'Estado_Activo' => !empty($_POST['estado_activo']) ? 1 : 0,
                'Fecha_Publicacion_Desde' => trim((string)($_POST['fecha_publicacion_desde'] ?? '')),
                'Fecha_Publicacion_Hasta' => trim((string)($_POST['fecha_publicacion_hasta'] ?? ''))
            ];

            $data['Fecha_Publicacion_Desde'] = $data['Fecha_Publicacion_Desde'] !== '' ? $data['Fecha_Publicacion_Desde'] : null;
            $data['Fecha_Publicacion_Hasta'] = $data['Fecha_Publicacion_Hasta'] !== '' ? $data['Fecha_Publicacion_Hasta'] : null;

            if ($data['Fecha_Publicacion_Desde'] && $data['Fecha_Publicacion_Hasta']
                && $data['Fecha_Publicacion_Desde'] > $data['Fecha_Publicacion_Hasta']) {
                throw new Exception('La fecha de publicación desde no puede ser mayor que hasta.');
            }

            if ($data['Titulo'] === '' || $data['Parrafo'] === '') {
                throw new Exception('Título y párrafo son obligatorios.');
            }

            if ($idContenido > 0) {
                $actual = $this->eventoModuloModel->getById($idContenido);
                if (empty($actual) || (string)($actual['Tipo_Modulo'] ?? '') !== $config['tipo']) {
                    throw new Exception('Contenido no válido para este módulo.');
                }

                $data['Imagen'] = $actual['Imagen'] ?? null;
                $data['Video'] = $actual['Video'] ?? null;

                if (!empty($_POST['eliminar_imagen'])) {
                    $this->eliminarArchivoFisico($actual['Imagen'] ?? null);
                    $data['Imagen'] = null;
                }

                if (!empty($_POST['eliminar_video'])) {
                    $this->eliminarArchivoFisico($actual['Video'] ?? null);
                    $data['Video'] = null;
                }

                if ($nuevaImagen !== null) {
                    $this->eliminarArchivoFisico($actual['Imagen'] ?? null);
                    $data['Imagen'] = $nuevaImagen;
                }

                if ($nuevoVideo !== null) {
                    $this->eliminarArchivoFisico($actual['Video'] ?? null);
                    $data['Video'] = $nuevoVideo;
                }

                $this->eventoModuloModel->update($idContenido, $data);
            } else {
                $data['Imagen'] = $nuevaImagen;
                $data['Video'] = $nuevoVideo;
                $this->eventoModuloModel->create($data);
            }
        } catch (Exception $e) {
            $_SESSION['evento_modulo_error'] = $e->getMessage();
        }

        $this->redirigirModulo($config['tipo']);
    }

    public function duplicarModuloContenido() {
        if (!AuthController::tienePermiso('eventos', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $tipo = (string)($_GET['tipo'] ?? '');
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $idContenido = (int)($_GET['id'] ?? 0);
        if ($idContenido > 0) {
            $item = $this->eventoModuloModel->getById($idContenido);
            if (!empty($item) && (string)($item['Tipo_Modulo'] ?? '') === $config['tipo']) {
                $this->eventoModuloModel->duplicar($idContenido);
            }
        }

        $this->redirigirModulo($config['tipo']);
    }

    public function eliminarModuloContenido() {
        if (!AuthController::tienePermiso('eventos', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $tipo = (string)($_GET['tipo'] ?? '');
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $idContenido = (int)($_GET['id'] ?? 0);
        if ($idContenido > 0) {
            $actual = $this->eventoModuloModel->getById($idContenido);
            if (!empty($actual) && (string)($actual['Tipo_Modulo'] ?? '') === $config['tipo']) {
                $this->eliminarArchivoFisico($actual['Imagen'] ?? null);
                $this->eliminarArchivoFisico($actual['Video'] ?? null);
                $this->eventoModuloModel->delete($idContenido);
            }
        }

        $this->redirigirModulo($config['tipo']);
    }

    private function renderModuloPublico($tipo) {
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            http_response_code(404);
            echo 'Módulo no encontrado';
            return;
        }

        $items = $this->eventoModuloModel->getByModuloPublico($config['tipo']);
        $this->view('eventos/modulo_publico', [
            'modulo' => $config,
            'items' => $items
        ]);
    }

    public function universidadVidaPublico() {
        $this->renderModuloPublico('universidad_vida');
    }

    public function capacitacionDestinoPublico() {
        $this->renderModuloPublico('capacitacion_destino');
    }

    private function procesarArchivo($campo, $extensionesPermitidas, $maxBytes) {
        if (!isset($_FILES[$campo]) || !is_array($_FILES[$campo])) {
            return null;
        }

        $archivo = $_FILES[$campo];

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new Exception('No se pudo subir el archivo de ' . str_replace('_', ' ', $campo) . '.');
        }

        $tamano = (int)($archivo['size'] ?? 0);
        if ($tamano <= 0 || $tamano > $maxBytes) {
            throw new Exception('El archivo de ' . str_replace('_', ' ', $campo) . ' supera el tamaño permitido.');
        }

        $extension = strtolower(pathinfo((string)($archivo['name'] ?? ''), PATHINFO_EXTENSION));
        if (!in_array($extension, $extensionesPermitidas, true)) {
            throw new Exception('Formato no permitido para ' . str_replace('_', ' ', $campo) . '.');
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
            throw new Exception('No se pudo crear la carpeta de uploads de eventos.');
        }

        $nombre = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destino = $this->uploadDir . '/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new Exception('No se pudo guardar el archivo subido.');
        }

        return $nombre;
    }

    private function eliminarArchivoFisico($archivo) {
        if (empty($archivo)) {
            return;
        }

        $ruta = $this->uploadDir . '/' . basename($archivo);
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    private function buildAbsolutePublicUrl($route) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(PUBLIC_URL, '/');
        return $scheme . '://' . $host . $base . '/index.php?url=' . urlencode($route);
    }
}
