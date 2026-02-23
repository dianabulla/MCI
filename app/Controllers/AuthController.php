<?php
/**
 * Controlador de Autenticación
 */

require_once APP . '/Models/Persona.php';

class AuthController extends BaseController {
    private $personaModel;

    private const ROL_ADMINISTRADOR_ID = 6;

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
            
            // Validar credenciales
            $user = $this->personaModel->autenticar($usuario, $contrasena);

            if ($user) {
                // Verificar estado de la cuenta
                $estadoCuenta = strtolower(trim((string)($user['Estado_Cuenta'] ?? 'Activo')));
                if ($estadoCuenta === 'inactivo' || $estadoCuenta === 'bloqueado') {
                    $error = 'Cuenta inactiva o bloqueada. Contacte al administrador.';
                    $this->view('auth/login', ['error' => $error]);
                    return;
                }

                // Crear sesión
                $_SESSION['usuario_id'] = $user['Id_Persona'];
                $_SESSION['usuario_nombre'] = $user['Nombre'] . ' ' . $user['Apellido'];
                $_SESSION['usuario_rol'] = $user['Id_Rol'];
                $_SESSION['usuario_rol_nombre'] = $user['Nombre_Rol'];
                $_SESSION['usuario_ministerio'] = $user['Id_Ministerio'] ?? null;

                // Actualizar último acceso
                $this->personaModel->actualizarUltimoAcceso($user['Id_Persona']);

                // Cargar permisos
                $this->cargarPermisos($user['Id_Rol']);

                $this->redirect('home');
            } else {
                $error = 'Usuario o contraseña incorrectos';
                $this->view('auth/login', ['error' => $error]);
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
        if (self::esAdministrador()) {
            return true;
        }

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
        $rolId = isset($_SESSION['usuario_rol']) ? (int) $_SESSION['usuario_rol'] : 0;
        if ($rolId === self::ROL_ADMINISTRADOR_ID) {
            return true;
        }

        $rolNombre = self::normalizarTexto((string) ($_SESSION['usuario_rol_nombre'] ?? ''));
        return strpos($rolNombre, 'admin') !== false;
    }

    private static function normalizarTexto($texto) {
        $texto = strtolower(trim((string) $texto));
        return strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);
    }

    /**
     * Página de acceso denegado
     */
    public function accesoDenegado() {
        $this->view('auth/acceso_denegado');
    }
}
