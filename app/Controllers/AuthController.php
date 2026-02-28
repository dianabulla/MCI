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
        $modoAgregarCuenta = (($_GET['modo'] ?? ($_POST['modo'] ?? '')) === 'agregar');

        // Si ya está logueado, redirigir al home
        if (isset($_SESSION['usuario_id']) && !$modoAgregarCuenta) {
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
                    $this->view('auth/login', [
                        'error' => $error,
                        'usuario' => $usuario,
                        'modo_agregar_cuenta' => $modoAgregarCuenta
                    ]);
                    return;
                }

                $preservarPool = $modoAgregarCuenta && isset($_SESSION['usuario_id']);
                $this->iniciarSesionUsuario($user, $preservarPool);

                if ($modoAgregarCuenta) {
                    $_SESSION['flash_info'] = 'Cuenta agregada correctamente. Ya puedes cambiar entre cuentas sin volver a iniciar sesión.';
                }

                $this->redirect('home');
            } else {
                $error = 'Usuario o contraseña incorrectos';
                $this->view('auth/login', [
                    'error' => $error,
                    'usuario' => $usuario,
                    'modo_agregar_cuenta' => $modoAgregarCuenta
                ]);
            }
        } else {
            $usuarioPrefill = trim((string)($_GET['cuenta'] ?? ''));
            $this->view('auth/login', [
                'usuario' => $usuarioPrefill,
                'modo_agregar_cuenta' => $modoAgregarCuenta
            ]);
        }
    }

    /**
     * Cambiar entre cuentas vinculadas sin volver a login
     */
    public function cambiarUsuario() {
        if (!self::estaAutenticado()) {
            $this->redirect('auth/login');
        }

        $idUsuarioObjetivo = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($idUsuarioObjetivo <= 0) {
            $_SESSION['flash_info'] = 'Cuenta no válida para cambiar.';
            $this->redirect('home');
        }

        $pool = $_SESSION['account_pool'] ?? [];
        if (!isset($pool[$idUsuarioObjetivo])) {
            $_SESSION['flash_info'] = 'Esa cuenta no está vinculada en esta sesión.';
            $this->redirect('home');
        }

        $cuentaDb = $this->personaModel->getById($idUsuarioObjetivo);
        if (empty($cuentaDb)) {
            unset($_SESSION['account_pool'][$idUsuarioObjetivo]);
            $_SESSION['flash_info'] = 'La cuenta seleccionada ya no existe.';
            $this->redirect('home');
        }

        $estadoCuenta = strtolower(trim((string)($cuentaDb['Estado_Cuenta'] ?? 'Activo')));
        if ($estadoCuenta === 'inactivo' || $estadoCuenta === 'bloqueado') {
            $_SESSION['flash_info'] = 'La cuenta seleccionada está inactiva o bloqueada.';
            $this->redirect('home');
        }

        $this->iniciarSesionUsuario($cuentaDb, true);
        $_SESSION['flash_info'] = 'Cambiaste a la cuenta de ' . (($cuentaDb['Nombre'] ?? '') . ' ' . ($cuentaDb['Apellido'] ?? '')) . '.';
        $this->redirect('home');
    }

    /**
     * Cambiar a la siguiente cuenta vinculada (rápido con flecha)
     */
    public function siguienteCuenta() {
        if (!self::estaAutenticado()) {
            $this->redirect('auth/login');
        }

        $pool = $_SESSION['account_pool'] ?? [];
        if (!is_array($pool) || count($pool) < 2) {
            $_SESSION['flash_info'] = 'No hay otra cuenta vinculada para cambiar.';
            $this->redirect('home');
        }

        $ids = array_values(array_map('intval', array_keys($pool)));
        sort($ids);

        $actual = (int)($_SESSION['usuario_id'] ?? 0);
        $indiceActual = array_search($actual, $ids, true);
        if ($indiceActual === false) {
            $indiceActual = 0;
        }

        $siguienteIndice = ($indiceActual + 1) % count($ids);
        $idUsuarioObjetivo = (int)$ids[$siguienteIndice];

        $cuentaDb = $this->personaModel->getById($idUsuarioObjetivo);
        if (empty($cuentaDb)) {
            unset($_SESSION['account_pool'][$idUsuarioObjetivo]);
            $_SESSION['flash_info'] = 'La siguiente cuenta ya no existe.';
            $this->redirect('home');
        }

        $estadoCuenta = strtolower(trim((string)($cuentaDb['Estado_Cuenta'] ?? 'Activo')));
        if ($estadoCuenta === 'inactivo' || $estadoCuenta === 'bloqueado') {
            $_SESSION['flash_info'] = 'La siguiente cuenta está inactiva o bloqueada.';
            $this->redirect('home');
        }

        $this->iniciarSesionUsuario($cuentaDb, true);
        $_SESSION['flash_info'] = 'Cuenta activa: ' . (($cuentaDb['Nombre'] ?? '') . ' ' . ($cuentaDb['Apellido'] ?? '')) . '.';
        $this->redirect('home');
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        session_destroy();
        $this->redirect('auth/login');
    }

    /**
     * Cambiar de cuenta rápidamente
     */
    public function cambiarCuenta() {
        $cuentasVinculadas = $_SESSION['account_pool'] ?? [];
        if (is_array($cuentasVinculadas) && !empty($cuentasVinculadas)) {
            $_SESSION = [
                'account_pool' => $cuentasVinculadas
            ];
        } else {
            $_SESSION = [];
        }
        session_regenerate_id(true);
        $_SESSION['flash_info'] = 'Selecciona la cuenta con la que deseas ingresar.';
        $this->redirect('auth/login');
    }

    /**
     * Cargar permisos del usuario en la sesión
     */
    private function cargarPermisos($idRol) {
        $_SESSION['permisos'] = $this->obtenerPermisosNormalizados($idRol);
    }

    private function obtenerPermisosNormalizados($idRol) {
        $permisos = $this->personaModel->getPermisosPorRol($idRol);
        $resultado = [];

        foreach ($permisos as $permiso) {
            $resultado[$permiso['Modulo']] = [
                'ver' => $permiso['Puede_Ver'],
                'crear' => $permiso['Puede_Crear'],
                'editar' => $permiso['Puede_Editar'],
                'eliminar' => $permiso['Puede_Eliminar']
            ];
        }

        return $resultado;
    }

    private function iniciarSesionUsuario(array $user, $preservarPool = false) {
        if (!$preservarPool || !isset($_SESSION['account_pool']) || !is_array($_SESSION['account_pool'])) {
            $_SESSION['account_pool'] = [];
        }

        $idUsuario = (int)($user['Id_Persona'] ?? 0);
        $idRol = (int)($user['Id_Rol'] ?? 0);
        $permisos = $this->obtenerPermisosNormalizados($idRol);

        $_SESSION['usuario_id'] = $idUsuario;
        $_SESSION['usuario_nombre'] = trim((string)($user['Nombre'] ?? '') . ' ' . (string)($user['Apellido'] ?? ''));
        $_SESSION['usuario_rol'] = $idRol;
        $_SESSION['usuario_rol_nombre'] = (string)($user['Nombre_Rol'] ?? '');
        $_SESSION['usuario_ministerio'] = $user['Id_Ministerio'] ?? null;
        $_SESSION['permisos'] = $permisos;
        $_SESSION['active_account_id'] = $idUsuario;

        $_SESSION['account_pool'][$idUsuario] = [
            'id' => $idUsuario,
            'nombre' => $_SESSION['usuario_nombre'],
            'rol_nombre' => $_SESSION['usuario_rol_nombre'],
            'ministerio_id' => $_SESSION['usuario_ministerio'],
            'permisos' => $permisos
        ];

        $this->personaModel->actualizarUltimoAcceso($idUsuario);
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
