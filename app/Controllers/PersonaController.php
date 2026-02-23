<?php
/**
 * Controlador Persona
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Rol.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';

class PersonaController extends BaseController {
    private $personaModel;
    private $celulaModel;
    private $ministerioModel;
    private $rolModel;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->celulaModel = new Celula();
        $this->ministerioModel = new Ministerio();
        $this->rolModel = new Rol();
    }

    private function getContextoFiltrosVisibles() {
        if (AuthController::esAdministrador() || DataIsolation::tieneAccesoTotal()) {
            return [
                'restringido' => false,
                'ministerios' => $this->ministerioModel->getAll(),
                'lideres' => $this->personaModel->getLideresYPastores(),
                'ministerioIdsPermitidos' => null,
                'liderIdsPermitidos' => null
            ];
        }

        $ministerios = [];
        $lideres = [];
        $ministerioIdsPermitidos = [];
        $liderIdsPermitidos = [];

        $idMinisterio = DataIsolation::getUsuarioMinisterioId();
        if ($idMinisterio) {
            $ministerio = $this->ministerioModel->getById($idMinisterio);
            if (!empty($ministerio)) {
                $ministerios[] = $ministerio;
                $ministerioIdsPermitidos[] = (int)$ministerio['Id_Ministerio'];
            }

            $lideres = $this->personaModel->getLideresByMinisterio($idMinisterio);
            foreach ($lideres as $lider) {
                $liderIdsPermitidos[] = (int)$lider['Id_Persona'];
            }
        }

        if (DataIsolation::esLiderCelula()) {
            $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
            if ($usuarioId > 0) {
                $yaExiste = false;
                foreach ($lideres as $liderExistente) {
                    if ((int)$liderExistente['Id_Persona'] === $usuarioId) {
                        $yaExiste = true;
                        break;
                    }
                }

                if (!$yaExiste) {
                    $personaUsuario = $this->personaModel->getById($usuarioId);
                    if (!empty($personaUsuario)) {
                        $lideres[] = $personaUsuario;
                    }
                }

                if (!in_array($usuarioId, $liderIdsPermitidos, true)) {
                    $liderIdsPermitidos[] = $usuarioId;
                }
            }
        }

        return [
            'restringido' => true,
            'ministerios' => $ministerios,
            'lideres' => $lideres,
            'ministerioIdsPermitidos' => $ministerioIdsPermitidos,
            'liderIdsPermitidos' => $liderIdsPermitidos
        ];
    }

    private function limpiarFiltrosNoPermitidos($filtroMinisterio, $filtroLider, array $contexto) {
        $filtroMinisterio = $filtroMinisterio !== null ? (string)$filtroMinisterio : '';
        $filtroLider = $filtroLider !== null ? (string)$filtroLider : '';

        if (!$contexto['restringido']) {
            return [$filtroMinisterio, $filtroLider];
        }

        if ($filtroMinisterio === '0') {
            $filtroMinisterio = '';
        }
        if ($filtroLider === '0') {
            $filtroLider = '';
        }

        $ministerioIdsPermitidos = $contexto['ministerioIdsPermitidos'] ?? [];
        $liderIdsPermitidos = $contexto['liderIdsPermitidos'] ?? [];

        if ($filtroMinisterio !== '' && !in_array((int)$filtroMinisterio, $ministerioIdsPermitidos, true)) {
            $filtroMinisterio = '';
        }

        if ($filtroLider !== '' && !in_array((int)$filtroLider, $liderIdsPermitidos, true)) {
            $filtroLider = '';
        }

        return [$filtroMinisterio, $filtroLider];
    }

    private function esRolAsistente($idRol) {
        $idRol = (int)$idRol;
        if ($idRol <= 0) {
            return false;
        }

        $rol = $this->rolModel->getById($idRol);
        if (empty($rol['Nombre_Rol'])) {
            return false;
        }

        $nombreRol = strtolower(trim((string)$rol['Nombre_Rol']));
        $nombreRol = strtr($nombreRol, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        return strpos($nombreRol, 'asistente') !== false;
    }

    public function index() {
        $filtroMinisterio = $_GET['ministerio'] ?? null;
        $filtroLider = $_GET['lider'] ?? null;

        $contextoFiltros = $this->getContextoFiltrosVisibles();
        [$filtroMinisterio, $filtroLider] = $this->limpiarFiltrosNoPermitidos($filtroMinisterio, $filtroLider, $contextoFiltros);
        
        // Aplicar filtro de aislamiento según el rol del usuario
        $filtroRol = DataIsolation::generarFiltroPersonas();
        
        // Obtener personas con filtros
        if (($filtroMinisterio !== null && $filtroMinisterio !== '') || ($filtroLider !== null && $filtroLider !== '')) {
            $personas = $this->personaModel->getWithFiltersAndRole($filtroRol, $filtroMinisterio, $filtroLider);
        } else {
            $personas = $this->personaModel->getAllWithRole($filtroRol);
        }
        
        // Obtener datos para los filtros
        $ministerios = $contextoFiltros['ministerios'];
        $lideres = $contextoFiltros['lideres'];
        
        $this->view('personas/lista', [
            'personas' => $personas,
            'ministerios' => $ministerios,
            'lideres' => $lideres,
            'filtroRestringido' => $contextoFiltros['restringido'],
            'filtroMinisterioActual' => (string)$filtroMinisterio,
            'filtroLiderActual' => (string)$filtroLider
        ]);
    }

    public function ganar() {
        $filtroMinisterio = $_GET['ministerio'] ?? null;
        $filtroLider = $_GET['lider'] ?? null;

        $contextoFiltros = $this->getContextoFiltrosVisibles();
        [$filtroMinisterio, $filtroLider] = $this->limpiarFiltrosNoPermitidos($filtroMinisterio, $filtroLider, $contextoFiltros);

        $filtroRol = DataIsolation::generarFiltroPersonas();

        if (($filtroMinisterio !== null && $filtroMinisterio !== '') || ($filtroLider !== null && $filtroLider !== '')) {
            $personas = $this->personaModel->getWithFiltersAndRole($filtroRol, $filtroMinisterio, $filtroLider, true);
        } else {
            $personas = $this->personaModel->getAllWithRole($filtroRol, true);
        }

        $ministerios = $contextoFiltros['ministerios'];
        $lideres = $contextoFiltros['lideres'];

        $this->view('personas/ganar', [
            'personas' => $personas,
            'ministerios' => $ministerios,
            'lideres' => $lideres,
            'filtroRestringido' => $contextoFiltros['restringido'],
            'filtroMinisterioActual' => (string)$filtroMinisterio,
            'filtroLiderActual' => (string)$filtroLider
        ]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('personas', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $returnTo = $_POST['return_to'] ?? ($_GET['return_to'] ?? null);
        $celulaRetorno = $_POST['celula_retorno'] ?? ($_GET['celula'] ?? null);
        $celulaRetorno = ($celulaRetorno !== null && $celulaRetorno !== '') ? (int) $celulaRetorno : null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRolSeleccionado = $_POST['id_rol'] ?: null;
            $rolEsAsistente = $this->esRolAsistente($idRolSeleccionado);

            $data = [
                'Nombre' => $_POST['nombre'],
                'Apellido' => $_POST['apellido'],
                'Tipo_Documento' => $_POST['tipo_documento'] ?: null,
                'Numero_Documento' => $_POST['numero_documento'] ?: null,
                'Fecha_Nacimiento' => $_POST['fecha_nacimiento'] ?: null,
                'Edad' => $_POST['edad'] ?: null,
                'Genero' => $_POST['genero'] ?: null,
                'Telefono' => $_POST['telefono'] ?: null,
                'Email' => $_POST['email'] ?: null,
                'Direccion' => $_POST['direccion'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Peticion' => $_POST['peticion'] ?: null,
                'Invitado_Por' => $_POST['invitado_por'] ?: null,
                'Tipo_Reunion' => $_POST['tipo_reunion'] ?: null,
                'Id_Lider' => $_POST['id_lider'] ?: null,
                'Id_Celula' => $_POST['id_celula'] ?: null,
                'Id_Rol' => $_POST['id_rol'] ?: null,
                'Id_Ministerio' => $_POST['id_ministerio'] ?: null,
                'Fecha_Registro' => date('Y-m-d H:i:s'),
                'Fecha_Registro_Unix' => time()
            ];
            
            // Agregar campos de acceso al sistema si se proporcionan (solo admin)
            if (AuthController::esAdministrador() && !$rolEsAsistente) {
                if (!empty($_POST['usuario'])) {
                    $data['Usuario'] = $_POST['usuario'];
                }
                
                // Si se proporciona contraseña, hashearla
                if (!empty($_POST['contrasena'])) {
                    $data['Contrasena'] = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
                    // Activar cuenta por defecto si se crea con contraseña
                    $data['Estado_Cuenta'] = 'Activo';
                }
            } elseif ($rolEsAsistente) {
                // Regla de negocio: asistentes no deben tener acceso activo al sistema
                $data['Estado_Cuenta'] = 'Inactivo';
            }
            
            try {
                $this->personaModel->create($data);
                if ($returnTo === 'asistencia') {
                    $urlRetorno = 'asistencias/registrar';
                    if ($celulaRetorno) {
                        $urlRetorno .= '&celula=' . $celulaRetorno;
                    }
                    $this->redirect($urlRetorno);
                }

                $this->redirect('personas/ganar');
            } catch (PDOException $e) {
                // Detectar error de duplicado
                if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
                    $error = 'La cédula ' . htmlspecialchars($data['Numero_Documento']) . ' ya está registrada en el sistema.';
                } else {
                    $error = 'Error al guardar la persona: ' . $e->getMessage();
                }
                
                $viewData = [
                    'celulas' => $this->celulaModel->getAll(),
                    'ministerios' => $this->ministerioModel->getAll(),
                    'roles' => $this->rolModel->getAll(),
                    'personas_invitadores' => $this->personaModel->getAll(),
                    'personas_lideres' => $this->personaModel->getLideresYPastores(),
                    'error' => $error,
                    'post_data' => $_POST,
                    'return_to' => $returnTo,
                    'celula_retorno' => $celulaRetorno
                ];
                $this->view('personas/formulario', $viewData);
            }
        } else {
            $data = [
                'celulas' => $this->celulaModel->getAll(),
                'ministerios' => $this->ministerioModel->getAll(),
                'roles' => $this->rolModel->getAll(),
                'personas_invitadores' => $this->personaModel->getAll(),
                'personas_lideres' => $this->personaModel->getLideresYPastores(),
                'return_to' => $returnTo,
                'celula_retorno' => $celulaRetorno
            ];
            $this->view('personas/formulario', $data);
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('personas', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $returnTo = $_POST['return_to'] ?? ($_GET['return_to'] ?? null);
        $celulaRetorno = $_POST['celula_retorno'] ?? ($_GET['celula'] ?? null);
        $celulaRetorno = ($celulaRetorno !== null && $celulaRetorno !== '') ? (int) $celulaRetorno : null;
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('personas');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRolSeleccionado = $_POST['id_rol'] ?: null;
            $rolEsAsistente = $this->esRolAsistente($idRolSeleccionado);

            $data = [
                'Nombre' => $_POST['nombre'],
                'Apellido' => $_POST['apellido'],
                'Tipo_Documento' => $_POST['tipo_documento'] ?: null,
                'Numero_Documento' => $_POST['numero_documento'] ?: null,
                'Fecha_Nacimiento' => $_POST['fecha_nacimiento'] ?: null,
                'Edad' => $_POST['edad'] ?: null,
                'Genero' => $_POST['genero'] ?: null,
                'Telefono' => $_POST['telefono'] ?: null,
                'Email' => $_POST['email'] ?: null,
                'Direccion' => $_POST['direccion'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Peticion' => $_POST['peticion'] ?: null,
                'Invitado_Por' => $_POST['invitado_por'] ?: null,
                'Tipo_Reunion' => $_POST['tipo_reunion'] ?: null,
                'Id_Lider' => $_POST['id_lider'] ?: null,
                'Id_Celula' => $_POST['id_celula'] ?: null,
                'Id_Rol' => $_POST['id_rol'] ?: null,
                'Id_Ministerio' => $_POST['id_ministerio'] ?: null
            ];
            
            // Agregar campos de acceso al sistema si se proporcionan (solo admin)
            if (AuthController::esAdministrador() && !$rolEsAsistente) {
                if (!empty($_POST['usuario'])) {
                    $data['Usuario'] = $_POST['usuario'];
                }
                
                // Si se proporciona contraseña, hashearla
                if (!empty($_POST['contrasena'])) {
                    $data['Contrasena'] = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
                }
                
                // Estado de cuenta (solo al editar)
                if (isset($_POST['estado_cuenta'])) {
                    $data['Estado_Cuenta'] = $_POST['estado_cuenta'];
                }
            } elseif ($rolEsAsistente) {
                // Regla de negocio: asistentes no deben tener acceso activo al sistema
                $data['Estado_Cuenta'] = 'Inactivo';
            }
            
            try {
                $this->personaModel->update($id, $data);

                if ($returnTo === 'asistencia') {
                    $urlRetorno = 'asistencias/registrar';
                    if ($celulaRetorno) {
                        $urlRetorno .= '&celula=' . $celulaRetorno;
                    }
                    $this->redirect($urlRetorno);
                }

                if ($returnTo === 'celulas') {
                    $this->redirect('celulas');
                }

                $this->redirect('personas');
            } catch (PDOException $e) {
                // Detectar error de duplicado
                if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
                    $error = 'La cédula ' . htmlspecialchars($data['Numero_Documento']) . ' ya está registrada en el sistema.';
                } else {
                    $error = 'Error al actualizar la persona: ' . $e->getMessage();
                }
                
                $persona = $this->personaModel->getById($id);
                $viewData = [
                    'persona' => $persona,
                    'celulas' => $this->celulaModel->getAll(),
                    'ministerios' => $this->ministerioModel->getAll(),
                    'roles' => $this->rolModel->getAll(),
                    'personas_invitadores' => $this->personaModel->getAll(),
                    'personas_lideres' => $this->personaModel->getLideresYPastores(),
                    'error' => $error,
                    'post_data' => $_POST,
                    'return_to' => $returnTo,
                    'celula_retorno' => $celulaRetorno
                ];
                $this->view('personas/formulario', $viewData);
            }
        } else {
            $persona = $this->personaModel->getById($id);
            $data = [
                'persona' => $persona,
                'celulas' => $this->celulaModel->getAll(),
                'ministerios' => $this->ministerioModel->getAll(),
                'roles' => $this->rolModel->getAll(),
                'personas_invitadores' => $this->personaModel->getAll(),
                'personas_lideres' => $this->personaModel->getLideresYPastores(),
                'return_to' => $returnTo,
                'celula_retorno' => $celulaRetorno
            ];
            $this->view('personas/formulario', $data);
        }
    }

    public function detalle() {
        $id = $_GET['id'] ?? null;
        $returnTo = $_GET['return_to'] ?? null;
        
        if (!$id) {
            $this->redirect('personas');
        }

        $persona = $this->personaModel->getById($id);
        $this->view('personas/detalle', [
            'persona' => $persona,
            'return_to' => $returnTo
        ]);
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('personas', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->personaModel->delete($id);
        }
        
        $this->redirect('personas');
    }
}
