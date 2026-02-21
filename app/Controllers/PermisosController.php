<?php
/**
 * Controlador de Permisos
 */

require_once APP . '/Models/Rol.php';
require_once APP . '/Controllers/AuthController.php';

class PermisosController extends BaseController {
    private $rolModel;
    private $db;

    public function __construct() {
        // Verificar que sea administrador
        if (!AuthController::esAdministrador()) {
            header('Location: ' . BASE_URL . '/auth/acceso-denegado');
            exit;
        }
        
        $this->rolModel = new Rol();
        $this->db = Database::getInstance()->getConnection();
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

    /**
     * Actualizar permisos
     */
    public function actualizar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRol = $_POST['id_rol'];
            $modulo = $_POST['modulo'];
            $campo = $_POST['campo']; // puede_ver, puede_crear, puede_editar, puede_eliminar
            $valor = $_POST['valor']; // 0 o 1

            try {
                // Verificar si existe el permiso
                $sql = "SELECT Id_Permiso FROM permisos WHERE Id_Rol = ? AND Modulo = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$idRol, $modulo]);
                $permiso = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($permiso) {
                    // Actualizar
                    $campoDb = $this->getCampoDb($campo);
                    $sql = "UPDATE permisos SET $campoDb = ? WHERE Id_Permiso = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$valor, $permiso['Id_Permiso']]);
                } else {
                    // Crear nuevo permiso
                    $sql = "INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) 
                            VALUES (?, ?, 0, 0, 0, 0)";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute([$idRol, $modulo]);
                    
                    // Actualizar el campo específico
                    $campoDb = $this->getCampoDb($campo);
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
            'celulas' => 'Células',
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
        return $map[$campo] ?? 'Puede_Ver';
    }
}
