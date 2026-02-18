<?php
/**
 * Controlador de Autenticación
 */

require_once APP . '/Models/Persona.php';

class AuthController extends BaseController {
    private $personaModel;

    public function __construct() {
        $this->personaModel = new Persona();
    }

    /**
     * Mostrar formulario de login
     */
    public function login() {
        // Si ya está logueado, redirigir al home
        if (isset($_SESSION['usuario_id'])) {
            $this->redirect('home');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = $_POST['usuario'] ?? '';
            $contrasena = $_POST['contrasena'] ?? '';

            // Debug temporal
            $debugInfo = [];
            
            // Validar credenciales
            $user = $this->personaModel->autenticar($usuario, $contrasena);
            
            // Debug: verificar si el usuario existe en BD
            $sql = "SELECT Usuario, Contrasena, Estado_Cuenta FROM persona WHERE Usuario = ?";
            $testUser = $this->personaModel->query($sql, [$usuario]);
            $debugInfo['usuario_existe'] = !empty($testUser);
            $debugInfo['hash_bd'] = !empty($testUser) ? substr($testUser[0]['Contrasena'], 0, 20) . '...' : 'N/A';
            $debugInfo['estado_cuenta'] = !empty($testUser) ? $testUser[0]['Estado_Cuenta'] : 'N/A';

            if ($user) {
                // Verificar estado de la cuenta
                if ($user['Estado_Cuenta'] !== 'Activo') {
                    $error = 'Cuenta inactiva o bloqueada. Contacte al administrador.';
                    $this->view('auth/login', ['error' => $error]);
                    return;
                }

                // Crear sesión
                $_SESSION['usuario_id'] = $user['Id_Persona'];
                $_SESSION['usuario_nombre'] = $user['Nombre'] . ' ' . $user['Apellido'];
                $_SESSION['usuario_rol'] = $user['Id_Rol'];
                $_SESSION['usuario_rol_nombre'] = $user['Nombre_Rol'];

                // Actualizar último acceso
                $this->personaModel->actualizarUltimoAcceso($user['Id_Persona']);

                // Cargar permisos
                $this->cargarPermisos($user['Id_Rol']);

                $this->redirect('home');
            } else {
                $error = 'Usuario o contraseña incorrectos';
                $this->view('auth/login', ['error' => $error, 'debug' => $debugInfo]);
            }
        } else {
            $this->view('auth/login');
        }
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        $this->redirect('auth/login');
    }

    /**
     * Cargar permisos del usuario en la sesión
     */
    private function cargarPermisos($idRol) {
        $permisos = $this->personaModel->getPermisosPorRol($idRol);
        
        $_SESSION['permisos'] = [];
        foreach ($permisos as $permiso) {
            $_SESSION['permisos'][$permiso['Modulo']] = [
                'ver' => $permiso['Puede_Ver'],
                'crear' => $permiso['Puede_Crear'],
                'editar' => $permiso['Puede_Editar'],
                'eliminar' => $permiso['Puede_Eliminar']
            ];
        }
    }

    /**
     * Verificar si tiene permiso
     */
    public static function tienePermiso($modulo, $accion = 'ver') {
        if (!isset($_SESSION['permisos'][$modulo])) {
            return false;
        }
        return $_SESSION['permisos'][$modulo][$accion] ?? false;
    }

    /**
     * Verificar si está autenticado
     */
    public static function estaAutenticado() {
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Verificar si es administrador
     */
    public static function esAdministrador() {
        return isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] == 6;
    }

    /**
     * Página de acceso denegado
     */
    public function accesoDenegado() {
        $this->view('auth/acceso_denegado');
    }
}
