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

    public function index() {
        $filtroMinisterio = $_GET['ministerio'] ?? null;
        $filtroLider = $_GET['lider'] ?? null;
        
        // Aplicar filtro de aislamiento según el rol del usuario
        $filtroRol = DataIsolation::generarFiltroPersonas();
        
        // Si no es administrador, forzar filtro por su ministerio
        if (!AuthController::esAdministrador()) {
            $filtroMinisterio = $_SESSION['usuario_ministerio'] ?? null;
        }
        
        // Obtener personas con filtros
        if (($filtroMinisterio !== null && $filtroMinisterio !== '') || ($filtroLider !== null && $filtroLider !== '')) {
            $personas = $this->personaModel->getWithFiltersAndRole($filtroRol, $filtroMinisterio, $filtroLider);
        } else {
            $personas = $this->personaModel->getAllWithRole($filtroRol);
        }
        
        // Obtener datos para los filtros
        $ministerios = $this->ministerioModel->getAll();
        $lideres = $this->personaModel->getLideresYPastores();
        
        $this->view('personas/lista', [
            'personas' => $personas,
            'ministerios' => $ministerios,
            'lideres' => $lideres
        ]);
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('personas', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                'Hora_Llamada' => $_POST['hora_llamada'] ?: null,
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
            if (AuthController::esAdministrador()) {
                if (!empty($_POST['usuario'])) {
                    $data['Usuario'] = $_POST['usuario'];
                }
                
                // Si se proporciona contraseña, hashearla
                if (!empty($_POST['contrasena'])) {
                    $data['Contrasena'] = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
                    // Activar cuenta por defecto si se crea con contraseña
                    $data['Estado_Cuenta'] = 'Activo';
                }
            }
            
            try {
                $this->personaModel->create($data);
                $this->redirect('personas');
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
                    'post_data' => $_POST
                ];
                $this->view('personas/formulario', $viewData);
            }
        } else {
            $data = [
                'celulas' => $this->celulaModel->getAll(),
                'ministerios' => $this->ministerioModel->getAll(),
                'roles' => $this->rolModel->getAll(),
                'personas_invitadores' => $this->personaModel->getAll(),
                'personas_lideres' => $this->personaModel->getLideresYPastores()
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
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('personas');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                'Hora_Llamada' => $_POST['hora_llamada'] ?: null,
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
            if (AuthController::esAdministrador()) {
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
            }
            
            try {
                $this->personaModel->update($id, $data);
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
                    'post_data' => $_POST
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
                'personas_lideres' => $this->personaModel->getLideresYPastores()
            ];
            $this->view('personas/formulario', $data);
        }
    }

    public function detalle() {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('personas');
        }

        $persona = $this->personaModel->getById($id);
        $this->view('personas/detalle', ['persona' => $persona]);
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
