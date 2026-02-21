<?php
/**
 * Controlador Peticion
 */

require_once APP . '/Models/Peticion.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Helpers/DataIsolation.php';

class PeticionController extends BaseController {
    private $peticionModel;
    private $personaModel;

    public function __construct() {
        $this->peticionModel = new Peticion();
        $this->personaModel = new Persona();
    }

    private function getPersonasPermitidas() {
        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        return $this->personaModel->getAllWithRole($filtroPersonas);
    }

    private function personaEstaPermitida($idPersona) {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $sql = "SELECT p.Id_Persona FROM persona p WHERE p.Id_Persona = ? AND $filtroPersonas LIMIT 1";
        $rows = $this->personaModel->query($sql, [$idPersona]);
        return !empty($rows);
    }

    public function index() {
        // Generar filtro según el rol del usuario
        $filtroPeticiones = DataIsolation::generarFiltroPeticiones();
        
        // Obtener peticiones con aislamiento de rol
        $peticiones = $this->peticionModel->getAllWithPersonAndRole($filtroPeticiones);
        $this->view('peticiones/lista', ['peticiones' => $peticiones]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('peticiones', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idPersona = (int)($_POST['id_persona'] ?? 0);
            if (!$this->personaEstaPermitida($idPersona)) {
                header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
                exit;
            }

            $data = [
                'Id_Persona' => $idPersona,
                'Descripcion_Peticion' => $_POST['descripcion_peticion'],
                'Fecha_Peticion' => date('Y-m-d'),
                'Estado_Peticion' => 'Pendiente'
            ];
            
            $this->peticionModel->create($data);
            $this->redirect('peticiones');
        } else {
            $data = [
                'personas' => $this->getPersonasPermitidas()
            ];
            $this->view('peticiones/formulario', $data);
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('peticiones', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('peticiones');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idPersona = (int)($_POST['id_persona'] ?? 0);
            if (!$this->personaEstaPermitida($idPersona)) {
                header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
                exit;
            }

            $data = [
                'Id_Persona' => $idPersona,
                'Descripcion_Peticion' => $_POST['descripcion_peticion'],
                'Estado_Peticion' => $_POST['estado_peticion']
            ];
            
            $this->peticionModel->update($id, $data);
            $this->redirect('peticiones');
        } else {
            $data = [
                'peticion' => $this->peticionModel->getById($id),
                'personas' => $this->getPersonasPermitidas()
            ];
            $this->view('peticiones/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('peticiones', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->peticionModel->delete($id);
        }
        
        $this->redirect('peticiones');
    }
}
