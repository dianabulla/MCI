<?php
/**
 * Controlador Evento
 */

require_once APP . '/Models/Evento.php';
require_once APP . '/Helpers/DataIsolation.php';

class EventoController extends BaseController {
    private $eventoModel;

    public function __construct() {
        $this->eventoModel = new Evento();
    }

    public function index() {
        // Generar filtro según el rol del usuario
        $filtroEventos = DataIsolation::generarFiltroEventos();
        
        // Obtener eventos con aislamiento de rol
        $eventos = $this->eventoModel->getAllWithRole($filtroEventos);
        $this->view('eventos/lista', ['eventos' => $eventos]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('eventos', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Evento' => $_POST['nombre_evento'],
                'Descripcion_Evento' => $_POST['descripcion_evento'],
                'Fecha_Evento' => $_POST['fecha_evento'],
                'Hora_Evento' => $_POST['hora_evento'],
                'Lugar_Evento' => $_POST['lugar_evento']
            ];
            
            $this->eventoModel->create($data);
            $this->redirect('eventos');
        } else {
            $this->view('eventos/formulario');
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
            $data = [
                'Nombre_Evento' => $_POST['nombre_evento'],
                'Descripcion_Evento' => $_POST['descripcion_evento'],
                'Fecha_Evento' => $_POST['fecha_evento'],
                'Hora_Evento' => $_POST['hora_evento'],
                'Lugar_Evento' => $_POST['lugar_evento']
            ];
            
            $this->eventoModel->update($id, $data);
            $this->redirect('eventos');
        } else {
            $data = [
                'evento' => $this->eventoModel->getById($id)
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
            $this->eventoModel->delete($id);
        }
        
        $this->redirect('eventos');
    }
}
