<?php
/**
 * Controlador de Autenticación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UsuarioAcceso.php';
require_once APP . '/Helpers/DataIsolation.php';

class AuthController extends BaseController {
    private $personaModel;
    private $usuarioAccesoModel;

    private const ROL_ADMINISTRADOR_ID = 6;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->usuarioAccesoModel = new UsuarioAcceso();
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
            
            // Validar credenciales primero contra personas (compatibilidad) y
            // luego contra cuentas de acceso desacopladas.
            $user = $this->personaModel->autenticar($usuario, $contrasena);
            if (!$user) {
                $user = $this->usuarioAccesoModel->autenticar($usuario, $contrasena);
            }

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
     * Permite a la cuenta activa cambiar su propio usuario y contraseña.
     */
    public function miCuenta() {
        if (!self::estaAutenticado()) {
            $this->redirect('auth/login');
            return;
        }

        $origenCuenta = (string)($_SESSION['auth_user_source'] ?? 'persona');
        $idPersonaSesion = (int)($_SESSION['usuario_id'] ?? 0);
        $idAuthSesion = (int)($_SESSION['auth_user_id'] ?? 0);

        $cuentaActual = null;
        if ($origenCuenta === 'acceso' && $idAuthSesion > 0) {
            $cuentaActual = $this->usuarioAccesoModel->getById($idAuthSesion);
        } elseif ($idPersonaSesion > 0) {
            $cuentaActual = $this->personaModel->getById($idPersonaSesion);
            $origenCuenta = 'persona';
        }

        if (empty($cuentaActual)) {
            session_destroy();
            $this->redirect('auth/login');
            return;
        }

        $viewPersona = [
            'Usuario' => (string)($cuentaActual['Usuario'] ?? ''),
        ];
        $error = null;
        $success = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuarioNuevo = trim((string)($_POST['usuario'] ?? ''));
            $contrasenaActual = (string)($_POST['contrasena_actual'] ?? '');
            $contrasenaNueva = (string)($_POST['contrasena_nueva'] ?? '');
            $contrasenaConfirmacion = (string)($_POST['contrasena_nueva_confirmacion'] ?? '');

            if ($usuarioNuevo === '' || strlen($usuarioNuevo) < 3) {
                $error = 'El usuario debe tener mínimo 3 caracteres.';
            } elseif ($contrasenaActual === '') {
                $error = 'Debes escribir tu contraseña actual para confirmar los cambios.';
            } else {
                $hashActual = (string)($cuentaActual['Contrasena'] ?? '');
                if ($hashActual === '' || !password_verify($contrasenaActual, $hashActual)) {
                    $error = 'La contraseña actual no es correcta.';
                }
            }

            if ($error === null && $contrasenaNueva !== '') {
                if (strlen($contrasenaNueva) < 6) {
                    $error = 'La nueva contraseña debe tener mínimo 6 caracteres.';
                } elseif ($contrasenaNueva !== $contrasenaConfirmacion) {
                    $error = 'La confirmación de la nueva contraseña no coincide.';
                }
            }

            $excludePersonaId = $origenCuenta === 'persona' ? $idPersonaSesion : null;
            $excludeAccesoId = $origenCuenta === 'acceso' ? $idAuthSesion : null;

            if ($error === null) {
                $usuarioDuplicadoPersona = $this->personaModel->existeUsuario($usuarioNuevo, $excludePersonaId);
                $usuarioDuplicadoAcceso = $this->usuarioAccesoModel->existeUsuario($usuarioNuevo, $excludeAccesoId);
                if ($usuarioDuplicadoPersona || $usuarioDuplicadoAcceso) {
                    $error = 'Ese usuario ya existe en el sistema.';
                }
            }

            if ($error === null) {
                $dataUpdate = [
                    'Usuario' => $usuarioNuevo,
                ];

                if ($contrasenaNueva !== '') {
                    $dataUpdate['Contrasena'] = password_hash($contrasenaNueva, PASSWORD_BCRYPT);
                }

                if ($origenCuenta === 'acceso' && $idAuthSesion > 0) {
                    $this->usuarioAccesoModel->update($idAuthSesion, $dataUpdate);
                    $cuentaActual = $this->usuarioAccesoModel->getById($idAuthSesion);
                } else {
                    $this->personaModel->update($idPersonaSesion, $dataUpdate);
                    $cuentaActual = $this->personaModel->getById($idPersonaSesion);
                }

                $viewPersona['Usuario'] = (string)($cuentaActual['Usuario'] ?? $usuarioNuevo);
                $success = $contrasenaNueva !== ''
                    ? 'Tus credenciales fueron actualizadas correctamente.'
                    : 'Tu usuario fue actualizado correctamente.';
            } else {
                $viewPersona['Usuario'] = $usuarioNuevo !== '' ? $usuarioNuevo : (string)($cuentaActual['Usuario'] ?? '');
            }
        }

        $this->view('auth/mi_cuenta', [
            'persona' => $viewPersona,
            'error' => $error,
            'success' => $success,
        ]);
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
        return self::normalizarPermisosDesdeFilas((array)$permisos);
    }

    private static function normalizarPermisosDesdeFilas(array $permisos): array {
        $resultado = [];

        foreach ($permisos as $permiso) {
            $modulo = trim((string)($permiso['Modulo'] ?? ''));
            if ($modulo === '') {
                continue;
            }

            $resultado[$modulo] = [
                'ver' => !empty($permiso['Puede_Ver']) ? 1 : 0,
                'crear' => !empty($permiso['Puede_Crear']) ? 1 : 0,
                'editar' => !empty($permiso['Puede_Editar']) ? 1 : 0,
                'eliminar' => !empty($permiso['Puede_Eliminar']) ? 1 : 0
            ];
        }

        return $resultado;
    }

    private function iniciarSesionUsuario(array $user, $preservarPool = false) {
        if (!$preservarPool || !isset($_SESSION['account_pool']) || !is_array($_SESSION['account_pool'])) {
            $_SESSION['account_pool'] = [];
        }

        $origenCuenta = isset($user['Id_Usuario_Acceso']) ? 'acceso' : 'persona';
        $idUsuario = (int)($user['Id_Persona'] ?? 0);
        $idAuthUser = isset($user['Id_Usuario_Acceso'])
            ? (int)($user['Id_Usuario_Acceso'] ?? 0)
            : $idUsuario;
        $idRol = (int)($user['Id_Rol'] ?? 0);
        $permisos = $this->obtenerPermisosNormalizados($idRol);

        $nombreSesion = trim((string)($user['Nombre'] ?? '') . ' ' . (string)($user['Apellido'] ?? ''));
        if ($nombreSesion === '') {
            $nombreSesion = trim((string)($user['Nombre_Mostrar'] ?? ''));
        }
        if ($nombreSesion === '') {
            $nombreSesion = trim((string)($user['Usuario'] ?? 'Usuario'));
        }

        $idMinisterio = $user['Id_Ministerio'] ?? null;
        $idMinisterio = ($idMinisterio !== null && $idMinisterio !== '') ? (int)$idMinisterio : null;

        $_SESSION['auth_user_id'] = $idAuthUser;
        $_SESSION['auth_user_source'] = $origenCuenta;
        // Compatibilidad: usuario_id conserva semantica de Id_Persona.
        // Para cuentas administrativas puras queda en 0.
        $_SESSION['usuario_id'] = $idUsuario > 0 ? $idUsuario : 0;
        $_SESSION['usuario_persona_id'] = $idUsuario > 0 ? $idUsuario : null;
        $_SESSION['usuario_nombre'] = $nombreSesion;
        $_SESSION['usuario_rol'] = $idRol;
        $_SESSION['usuario_rol_nombre'] = (string)($user['Nombre_Rol'] ?? '');
        $_SESSION['usuario_ministerio'] = $idMinisterio;
        $_SESSION['permisos'] = $permisos;
        $_SESSION['permisos_configurados'] = !empty($permisos);
        $_SESSION['permisos_last_sync'] = time();
        $_SESSION['active_account_id'] = $idUsuario > 0 ? $idUsuario : 0;
        $_SESSION['mostrar_alerta_ganar_pendiente'] = true;

        if ($idUsuario > 0) {
            $_SESSION['account_pool'][$idUsuario] = [
                'id' => $idUsuario,
                'nombre' => $_SESSION['usuario_nombre'],
                'rol_nombre' => $_SESSION['usuario_rol_nombre'],
                'ministerio_id' => $_SESSION['usuario_ministerio'],
                'permisos' => $permisos
            ];
            $this->personaModel->actualizarUltimoAcceso($idUsuario);
        }

        if ($origenCuenta === 'acceso' && $idAuthUser > 0) {
            $this->usuarioAccesoModel->actualizarUltimoAcceso($idAuthUser);
        }
    }

    /**
     * Verificar si tiene permiso
     */
    public static function tienePermiso($modulo, $accion = 'ver') {
        if (self::esAdministrador()) {
            return true;
        }

        self::sincronizarPermisosSesionActual();

        $modulo = trim((string)$modulo);
        if ($modulo === '') {
            return false;
        }

        $accion = strtolower(trim((string)$accion));
        if ($accion === '') {
            $accion = 'ver';
        }

        // Si existe permiso explicito de modulo, SIEMPRE manda lo configurado
        // por el administrador.
        if (isset($_SESSION['permisos'][$modulo]) && is_array($_SESSION['permisos'][$modulo])) {
            return !empty($_SESSION['permisos'][$modulo][$accion]);
        }

        // Si el rol YA tiene matriz de permisos configurada,
        // cualquier modulo no configurado debe negar por defecto.
        if (!empty($_SESSION['permisos_configurados'])) {
            return false;
        }

        // Compatibilidad: para roles con acceso total de datos, permitir acceso
        // cuando aun no exista configuracion explicita del modulo.
        if (DataIsolation::tieneAccesoTotal()) {
            return true;
        }

        return false;
    }

    public static function sincronizarPermisosSesionActual($forzar = false) {
        if (!self::estaAutenticado()) {
            return;
        }

        $ultimoSync = (int)($_SESSION['permisos_last_sync'] ?? 0);
        if (!$forzar && $ultimoSync > 0 && (time() - $ultimoSync) < 30) {
            return;
        }

        $idRol = (int)($_SESSION['usuario_rol'] ?? 0);
        if ($idRol <= 0) {
            return;
        }

        try {
            $personaModel = new Persona();
            $filas = $personaModel->getPermisosPorRol($idRol);
            $permisos = self::normalizarPermisosDesdeFilas((array)$filas);

            $_SESSION['permisos'] = $permisos;
            $_SESSION['permisos_configurados'] = !empty($permisos);
            $_SESSION['permisos_last_sync'] = time();

            $activeAccountId = (int)($_SESSION['active_account_id'] ?? 0);
            if ($activeAccountId > 0 && isset($_SESSION['account_pool'][$activeAccountId])) {
                $_SESSION['account_pool'][$activeAccountId]['permisos'] = $permisos;
            }
        } catch (Throwable $e) {
            // Si falla sync, mantener permisos actuales en sesión.
        }
    }

    /**
     * Verificar si está autenticado
     */
    public static function estaAutenticado() {
        return isset($_SESSION['auth_user_id']) || isset($_SESSION['usuario_id']);
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

    public static function esRolDiscipuloUsuario() {
        if (self::esAdministrador()) {
            return false;
        }

        $rolNombre = self::normalizarTexto((string)($_SESSION['usuario_rol_nombre'] ?? ''));
        return strpos($rolNombre, 'discipul') !== false || strpos($rolNombre, 'disipul') !== false;
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
