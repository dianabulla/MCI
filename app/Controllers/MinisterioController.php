<?php
/**
 * Controlador Ministerio
 */

require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Helpers/DataIsolation.php';

class MinisterioController extends BaseController {
    private $ministerioModel;
    private $personaModel;

    public function __construct() {
        $this->ministerioModel = new Ministerio();
        $this->personaModel = new Persona();
    }

    public function index() {
        // Generar filtro según el rol del usuario
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        
        // Obtener ministerios con aislamiento de rol
        $ministerios = $this->ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios);

        $ministerioIds = array_map(static function ($ministerio) {
            return (int)($ministerio['Id_Ministerio'] ?? 0);
        }, $ministerios);

        $miembros = $this->personaModel->getActivosByMinisterioIds($ministerioIds, 3);
        $miembrosPorMinisterio = [];
        foreach ($miembros as $miembro) {
            $idMinisterio = (int)($miembro['Id_Ministerio'] ?? 0);
            if ($idMinisterio <= 0) {
                continue;
            }

            if (!isset($miembrosPorMinisterio[$idMinisterio])) {
                $miembrosPorMinisterio[$idMinisterio] = [];
            }
            $miembrosPorMinisterio[$idMinisterio][] = $miembro;
        }

        $sections = [];
        foreach ($ministerios as $ministerio) {
            $idMinisterio = (int)($ministerio['Id_Ministerio'] ?? 0);
            $miembrosMinisterio = $miembrosPorMinisterio[$idMinisterio] ?? [];

            $rows = [];
            $nro = 1;
            foreach ($miembrosMinisterio as $miembro) {
                $nombreCompleto = trim(((string)($miembro['Nombre'] ?? '')) . ' ' . ((string)($miembro['Apellido'] ?? '')));
                $rows[] = [
                    'nro' => $nro++,
                    'id_persona' => (int)$miembro['Id_Persona'],
                    'nombre' => $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre',
                    'telefono' => (string)($miembro['Telefono'] ?? ''),
                    'documento' => (string)($miembro['Numero_Documento'] ?? ''),
                    'celula' => (string)($miembro['Nombre_Celula'] ?? '')
                ];
            }

            $sections[] = [
                'id_ministerio' => $idMinisterio,
                'label' => (string)($ministerio['Nombre_Ministerio'] ?? 'Ministerio sin nombre'),
                'descripcion' => (string)($ministerio['Descripcion'] ?? ''),
                'rows' => $rows,
                'total_personas' => count($rows)
            ];
        }

        $this->view('ministerios/lista', [
            'ministerios' => $ministerios,
            'sections' => $sections
        ]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('ministerios', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->ministerioModel->create($data);
            $this->redirect('ministerios');
        } else {
            $this->view('ministerios/formulario');
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('ministerios', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('ministerios');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Ministerio' => $_POST['nombre_ministerio'],
                'Descripcion' => $_POST['descripcion']
            ];
            
            $this->ministerioModel->update($id, $data);
            $this->redirect('ministerios');
        } else {
            $data = [
                'ministerio' => $this->ministerioModel->getById($id)
            ];
            $this->view('ministerios/formulario', $data);
        }
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('ministerios', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->ministerioModel->delete($id);
        }
        
        $this->redirect('ministerios');
    }
}
