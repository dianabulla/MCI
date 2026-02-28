<?php
/**
 * Controlador Peticion
 */

require_once APP . '/Models/Peticion.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Helpers/DataIsolation.php';

class PeticionController extends BaseController {
    private $peticionModel;
    private $personaModel;
    private $celulaModel;

    public function __construct() {
        $this->peticionModel = new Peticion();
        $this->personaModel = new Persona();
        $this->celulaModel = new Celula();
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
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroCelula = $_GET['celula'] ?? '';

        $celulasBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $celulasDisponibles = array_map(static function($celula) {
            return [
                'Id_Celula' => (int)($celula['Id_Celula'] ?? 0),
                'Nombre_Celula' => (string)($celula['Nombre_Celula'] ?? '')
            ];
        }, $celulasBase);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasDisponibles);

        $filtroCelula = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');
        
        // Obtener peticiones con aislamiento de rol
        $peticiones = $this->peticionModel->getAllWithPersonAndRole($filtroPeticiones, $filtroCelula);
        $this->view('peticiones/lista', [
            'peticiones' => $peticiones,
            'celulas_disponibles' => $celulasDisponibles,
            'filtro_celula_actual' => (string)$filtroCelula
        ]);
    }

    public function exportarExcel() {
        if (!AuthController::tienePermiso('peticiones', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $filtroPeticiones = DataIsolation::generarFiltroPeticiones();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroCelula = $_GET['celula'] ?? '';

        $celulasBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasBase);
        $filtroCelula = ($filtroCelula !== '' && in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) ? (int)$filtroCelula : (($filtroCelula === '0') ? '0' : '');

        $peticiones = $this->peticionModel->getAllWithPersonAndRole($filtroPeticiones, $filtroCelula);

        $rows = [];
        foreach ($peticiones as $peticion) {
            $rows[] = [
                (string)($peticion['Nombre_Completo'] ?? ''),
                (string)($peticion['Nombre_Celula'] ?? ''),
                (string)($peticion['Descripcion_Peticion'] ?? ''),
                (string)($peticion['Fecha_Peticion'] ?? ''),
                (string)($peticion['Estado_Peticion'] ?? '')
            ];
        }

        $this->exportCsv(
            'peticiones_' . date('Ymd_His'),
            ['Persona', 'Celula', 'Peticion', 'Fecha', 'Estado'],
            $rows
        );
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
