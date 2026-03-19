<?php
/**
 * Controlador de Permisos
 */

require_once APP . '/Models/Rol.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Config/Database.php';

class PermisosController extends BaseController {
    private $rolModel;
    private $db;

    public function __construct() {
        // Verificar que sea administrador
        if (!AuthController::esAdministrador()) {
            header('Location: ' . PUBLIC_URL . '?url=auth/acceso-denegado');
            exit;
        }
        
        $this->rolModel = new Rol();
        $this->db = $this->obtenerConexionDb();
    }

    private function obtenerConexionDb() {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            return $pdo;
        }

        if (class_exists('Database')) {
            return Database::getInstance()->getConnection();
        }

        if (class_exists('App\\Config\\Database')) {
            return \App\Config\Database::getInstance()->getConnection();
        }

        throw new Exception('No se encontró la clase de base de datos (Database).');
    }

    /**
     * Mostrar gestión de permisos
     */
    public function index() {
        $roles = $this->rolModel->getAll();
        $modulos = $this->getModulos();
        
        // Obtener permisos de todos los roles
        $permisos = [];
        foreach ($roles as $rol) {
            $permisos[$rol['Id_Rol']] = $this->getPermisosPorRol($rol['Id_Rol']);
        }
        
        $data = [
            'pageTitle' => 'Administración de Permisos',
            'roles' => $roles,
            'modulos' => $modulos,
            'permisos' => $permisos
        ];
        
        $this->view('permisos/index', $data);
    }

    public function exportarExcel() {
        $roles = $this->rolModel->getAll();
        $modulos = $this->getModulos();

        $permisosPorRol = [];
        foreach ($roles as $rol) {
            $permisosPorRol[(int)$rol['Id_Rol']] = $this->getPermisosPorRol($rol['Id_Rol']);
        }

        $rows = [];
        foreach ($modulos as $moduloKey => $moduloNombre) {
            foreach ($roles as $rol) {
                $idRol = (int)$rol['Id_Rol'];
                $permiso = $permisosPorRol[$idRol][$moduloKey] ?? null;

                $rows[] = [
                    (string)$moduloNombre,
                    (string)($rol['Nombre_Rol'] ?? ''),
                    !empty($permiso['Puede_Ver']) ? 'Si' : 'No',
                    !empty($permiso['Puede_Crear']) ? 'Si' : 'No',
                    !empty($permiso['Puede_Editar']) ? 'Si' : 'No',
                    !empty($permiso['Puede_Eliminar']) ? 'Si' : 'No'
                ];
            }
        }

        $this->exportCsv(
            'permisos_' . date('Ymd_His'),
            ['Modulo', 'Rol', 'Puede Ver', 'Puede Crear', 'Puede Editar', 'Puede Eliminar'],
            $rows
        );
    }

    /**
     * Actualizar permisos
     */
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRol = (int)$_POST['id_rol'];
            $modulo = $_POST['modulo'] ?? '';
            $campo  = $_POST['campo'] ?? '';
            $valor  = (int)$_POST['valor'];

            // Validar campo para evitar inyección SQL (whitelist)
            $campoDb = $this->getCampoDb($campo);
            if ($campoDb === null) {
                echo json_encode(['success' => false, 'error' => 'Campo no válido']);
                return;
            }

            try {
                // Verificar si existe el permiso
                $sql = "SELECT Id_Permiso FROM permisos WHERE Id_Rol = ? AND Modulo = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idRol, $modulo]);
                $permiso = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($permiso) {
                    // Actualizar
                    $sql = "UPDATE permisos SET $campoDb = ? WHERE Id_Permiso = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$valor, $permiso['Id_Permiso']]);
                } else {
                    // Crear nuevo permiso con todo en 0
                    $sql = "INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) 
                            VALUES (?, ?, 0, 0, 0, 0)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$idRol, $modulo]);

                    // Actualizar el campo específico
                    $sql = "UPDATE permisos SET $campoDb = ? WHERE Id_Rol = ? AND Modulo = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$valor, $idRol, $modulo]);
                }

                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Obtener módulos disponibles
     */
    private function getModulos() {
        return [
            'personas' => 'Personas',
            'personas_formulario_publico' => 'Personas: Ver formulario publico',
            'personas_plantillas_whatsapp' => 'Personas: Ver plantillas WhatsApp',
            'personas_ganar_asignados' => 'Personas: Ver atajo Asignados (Pendiente)',
            'personas_ganar_reasignados' => 'Personas: Ver atajo Reasignados (Pendiente)',
            'celulas' => 'Células',
            'materiales_celulas' => 'Materiales Células (PDF)',
            'ministerios' => 'Ministerios',
            'roles' => 'Roles',
            'eventos' => 'Eventos',
            'peticiones' => 'Peticiones',
            'asistencias' => 'Asistencias',
            'reportes' => 'Reportes',
            'transmisiones' => 'Transmisiones',
            'entrega_obsequio' => 'Entrega de Obsequios',
            'registro_obsequio' => 'Registro de Obsequios',
            'nehemias' => 'Nehemias',
            'nehemias_cols_bogota_subio' => 'Nehemias: Ver En Bogotá se le subió',
            'nehemias_cols_puesto' => 'Nehemias: Ver Puesto',
            'nehemias_cols_mesa' => 'Nehemias: Ver Mesa',
            'nehemias_cols_acepta' => 'Nehemias: Ver Acepta',
            'nehemias_acciones_editar' => 'Nehemias: Botón editar',
            'nehemias_acciones_eliminar' => 'Nehemias: Botón eliminar',
            'permisos' => 'Permisos'
        ];
    }

    /**
     * Obtener permisos de un rol
     */
    private function getPermisosPorRol($idRol) {
        $sql = "SELECT * FROM permisos WHERE Id_Rol = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idRol]);
        $permisos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($permisos as $permiso) {
            $result[$permiso['Modulo']] = $permiso;
        }
        return $result;
    }

    /**
     * Convertir nombre de campo a nombre de base de datos
     */
    private function getCampoDb($campo) {
        $map = [
            'puede_ver' => 'Puede_Ver',
            'puede_crear' => 'Puede_Crear',
            'puede_editar' => 'Puede_Editar',
            'puede_eliminar' => 'Puede_Eliminar'
        ];
        return $map[$campo] ?? null;
    }
}
