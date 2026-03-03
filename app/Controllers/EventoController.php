<?php
/**
 * Controlador Evento
 */

require_once APP . '/Models/Evento.php';
require_once APP . '/Helpers/DataIsolation.php';

class EventoController extends BaseController {
    private $eventoModel;
    private $uploadDir;
    private $uploadUrlBase;

    public function __construct() {
        $this->eventoModel = new Evento();
        $this->uploadDir = ROOT . '/public/uploads/eventos';
        $this->uploadUrlBase = rtrim(PUBLIC_URL, '/') . '/uploads/eventos';
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
