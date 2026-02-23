<?php
/**
 * Controlador Celula
 */

require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Helpers/DataIsolation.php';

class CelulaController extends BaseController {
    private $celulaModel;
    private $personaModel;
    private $ministerioModel;

    public function __construct() {
        $this->celulaModel = new Celula();
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
    }

    public function index() {
        // Generar filtro según el rol del usuario
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';

        // Base para opciones de filtros según visibilidad por rol
        $celulasBase = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);

        $ministeriosDisponibles = [];
        $ministerioIdsPermitidos = [];
        $lideresDisponibles = [];
        $liderIdsPermitidos = [];

        foreach ($celulasBase as $celulaBase) {
            $idMinisterioLider = (int)($celulaBase['Id_Ministerio_Lider'] ?? 0);
            $nombreMinisterioLider = trim((string)($celulaBase['Nombre_Ministerio_Lider'] ?? ''));
            if ($idMinisterioLider > 0 && $nombreMinisterioLider !== '') {
                $ministeriosDisponibles[$idMinisterioLider] = [
                    'Id_Ministerio' => $idMinisterioLider,
                    'Nombre_Ministerio' => $nombreMinisterioLider
                ];
                $ministerioIdsPermitidos[$idMinisterioLider] = true;
            }

            $idLider = (int)($celulaBase['Id_Lider'] ?? 0);
            $nombreLider = trim((string)($celulaBase['Nombre_Lider'] ?? ''));
            $idMinisterioLider = (int)($celulaBase['Id_Ministerio_Lider'] ?? 0);
            if ($idLider > 0 && $nombreLider !== '') {
                $lideresDisponibles[$idLider] = [
                    'Id_Persona' => $idLider,
                    'Nombre_Completo' => $nombreLider,
                    'Id_Ministerio' => $idMinisterioLider
                ];
                $liderIdsPermitidos[$idLider] = true;
            }
        }

        ksort($ministeriosDisponibles);
        ksort($lideresDisponibles);

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($ministerioIdsPermitidos[(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($liderIdsPermitidos[(int)$filtroLider])) ? (int)$filtroLider : '';

        // Obtener células con aislamiento y filtros
        $celulas = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas, $filtroMinisterio, $filtroLider);

        $celulaIds = array_map(static function ($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulas);

        $miembros = $this->personaModel->getActivosByCelulaIds($celulaIds);
        $miembrosPorCelula = [];
        foreach ($miembros as $miembro) {
            $idCelula = (int)($miembro['Id_Celula'] ?? 0);
            if ($idCelula <= 0) {
                continue;
            }

            if (!isset($miembrosPorCelula[$idCelula])) {
                $miembrosPorCelula[$idCelula] = [];
            }
            $miembrosPorCelula[$idCelula][] = $miembro;
        }

        $sections = [];
        foreach ($celulas as $celula) {
            $idCelula = (int)($celula['Id_Celula'] ?? 0);
            $miembrosCelula = $miembrosPorCelula[$idCelula] ?? [];

            $rows = [];
            $nro = 1;
            foreach ($miembrosCelula as $miembro) {
                $nombreCompleto = trim(((string)($miembro['Nombre'] ?? '')) . ' ' . ((string)($miembro['Apellido'] ?? '')));
                $rows[] = [
                    'nro' => $nro++,
                    'id_persona' => (int)$miembro['Id_Persona'],
                    'nombre' => $nombreCompleto !== '' ? $nombreCompleto : 'Sin nombre',
                    'telefono' => (string)($miembro['Telefono'] ?? ''),
                    'documento' => (string)($miembro['Numero_Documento'] ?? '')
                ];
            }

            $sections[] = [
                'id_celula' => $idCelula,
                'label' => (string)($celula['Nombre_Celula'] ?? 'Célula sin nombre'),
                'lider' => (string)($celula['Nombre_Lider'] ?? 'Sin líder'),
                'anfitrion' => (string)($celula['Nombre_Anfitrion'] ?? 'Sin anfitrión'),
                'direccion' => (string)($celula['Direccion_Celula'] ?? ''),
                'dia' => (string)($celula['Dia_Reunion'] ?? ''),
                'hora' => (string)($celula['Hora_Reunion'] ?? ''),
                'rows' => $rows,
                'total_personas' => count($rows)
            ];
        }

        $this->view('celulas/lista', [
            'celulas' => $celulas,
            'sections' => $sections,
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'lideres_disponibles' => array_values($lideresDisponibles),
            'filtro_ministerio_actual' => (string)$filtroMinisterio,
            'filtro_lider_actual' => (string)$filtroLider
        ]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('celulas', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idLider = $_POST['id_lider'] ?: null;

            // Para líder de célula, forzar que la célula quede anclada al usuario logueado
            if (DataIsolation::esLiderCelula()) {
                $idLider = $_SESSION['usuario_id'] ?? $idLider;
            }

            $data = [
                'Nombre_Celula' => $_POST['nombre_celula'],
                'Direccion_Celula' => $_POST['direccion_celula'],
                'Dia_Reunion' => $_POST['dia_reunion'],
                'Hora_Reunion' => $_POST['hora_reunion'],
                'Id_Lider' => $idLider,
                'Pastor_Principal' => $_POST['pastor_principal'] ?: null,
                'Id_Lider_Inmediato' => $_POST['id_lider_inmediato'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Red' => $_POST['red'] ?: null,
                'Id_Anfitrion' => $_POST['id_anfitrion'] ?: null,
                'Telefono_Anfitrion' => $_POST['telefono_anfitrion'] ?: null
            ];
            
            $this->celulaModel->create($data);
            $this->redirect('celulas');
        } else {
            $this->view('celulas/formulario');
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('celulas', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'Nombre_Celula' => $_POST['nombre_celula'],
                'Direccion_Celula' => $_POST['direccion_celula'],
                'Dia_Reunion' => $_POST['dia_reunion'],
                'Hora_Reunion' => $_POST['hora_reunion'],
                'Id_Lider' => $_POST['id_lider'] ?: null,
                'Pastor_Principal' => $_POST['pastor_principal'] ?: null,
                'Id_Lider_Inmediato' => $_POST['id_lider_inmediato'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Red' => $_POST['red'] ?: null,
                'Id_Anfitrion' => $_POST['id_anfitrion'] ?: null,
                'Telefono_Anfitrion' => $_POST['telefono_anfitrion'] ?: null
            ];
            
            $this->celulaModel->update($id, $data);
            $this->redirect('celulas');
        } else {
            $data = [
                'celula' => $this->celulaModel->getById($id)
            ];
            $this->view('celulas/formulario', $data);
        }
    }

    public function detalle() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('celulas');
        }

        $celula = $this->celulaModel->getWithMembers($id);
        $this->view('celulas/detalle', ['celula' => $celula]);
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('celulas', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->celulaModel->delete($id);
        }
        
        $this->redirect('celulas');
    }

    /**
     * Buscar líderes para autocompletar (AJAX)
     */
    public function buscarLideres() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            // Buscar líderes específicos: Líder de célula (3), Pastores (6), Líder de 12 (8)
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono, r.Nombre_Rol as Rol
                    FROM persona p
                    LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                    WHERE p.Id_Rol IN (3, 6, 8)
                    AND (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 20";
            
            $searchTerm = "%$term%";
            $lideres = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $lideres
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar líderes'
            ]);
        }
        exit;
    }

    /**
     * Buscar líderes de 12 para autocompletar (AJAX)
     */
    public function buscarLideres12() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono
                    FROM persona p
                    WHERE p.Id_Rol = 8
                    AND (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 20";
            
            $searchTerm = "%$term%";
            $lideres = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $lideres
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar líderes de 12'
            ]);
        }
        exit;
    }

    /**
     * Buscar pastores para autocompletar (AJAX)
     */
    public function buscarPastores() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono, r.Nombre_Rol as Rol
                    FROM persona p
                    LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
                    WHERE p.Id_Rol = 6
                    AND (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 20";
            
            $searchTerm = "%$term%";
            $pastores = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $pastores
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar pastores'
            ]);
        }
        exit;
    }

    /**
     * Buscar anfitriones (todas las personas) para autocompletar (AJAX)
     */
    public function buscarAnfitriones() {
        header('Content-Type: application/json');
        
        $term = $_GET['term'] ?? '';
        
        if (strlen($term) < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }
        
        try {
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono
                    FROM persona p
                    WHERE (p.Nombre LIKE ? OR p.Apellido LIKE ?)
                    AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 20";
            
            $searchTerm = "%$term%";
            $personas = $this->personaModel->query($sql, [$searchTerm, $searchTerm]);
            
            echo json_encode([
                'success' => true,
                'data' => $personas
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al buscar anfitriones'
            ]);
        }
        exit;
    }
}
