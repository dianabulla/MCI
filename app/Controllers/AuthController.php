<?php
/**
 * Controlador de Autenticación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UsuarioAcceso.php';
require_once APP . '/Models/UserRole.php';
require_once APP . '/Models/Rol.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Helpers/DataIsolation.php';
require_once APP . '/Helpers/PermisosCatalogo.php';

class AuthController extends BaseController {
    private $personaModel;
    private $usuarioAccesoModel;
    private $userRoleModel;
    private $rolModel;

    private const ROL_ADMINISTRADOR_ID = 6;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->usuarioAccesoModel = new UsuarioAcceso();
        $this->userRoleModel = new UserRole();
        $this->rolModel = new Rol();
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

                $this->redirigirPostLogin();
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
        $this->redirigirPostLogin();
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
        $this->redirigirPostLogin();
    }

    public function selectorContexto() {
        if (!self::estaAutenticado()) {
            $this->redirect('auth/login');
            return;
        }

        $rolesDisponibles = (array)($_SESSION['available_roles'] ?? []);
        if (count($rolesDisponibles) <= 1) {
            $this->redirect('home');
            return;
        }

        $this->view('auth/selector_contexto', [
            'roles_disponibles' => $rolesDisponibles,
            'usuario_nombre' => (string)($_SESSION['usuario_nombre'] ?? 'Usuario'),
            'error' => (string)($_GET['error'] ?? ''),
        ]);
    }

    public function seleccionarContexto() {
        if (!self::estaAutenticado()) {
            $this->redirect('auth/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/selector-contexto');
            return;
        }

        $idRol = (int)($_POST['id_rol'] ?? 0);
        $rolesDisponibles = (array)($_SESSION['available_roles'] ?? []);

        $seleccion = null;
        foreach ($rolesDisponibles as $rol) {
            if ((int)($rol['id_rol'] ?? 0) === $idRol) {
                $seleccion = $rol;
                break;
            }
        }

        if ($seleccion === null) {
            $this->redirect('auth/selector-contexto&error=' . urlencode('Selecciona un perfil válido.'));
            return;
        }

        $this->aplicarContextoActivo((int)$seleccion['id_rol'], (string)($seleccion['context_key'] ?? ''));
        $_SESSION['require_context_selection'] = false;
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

            $extras = PermisosCatalogo::mapaDesdeFila((array)$permiso);
            foreach ($extras as $claveExtra => $valorExtra) {
                $resultado[$modulo][$claveExtra] = !empty($valorExtra) ? 1 : 0;
            }
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
        $rolesDisponibles = $this->resolverRolesDisponibles($idUsuario, $idRol, (string)($user['Nombre_Rol'] ?? ''));
        $rolActivo = $this->resolverRolActivoInicial($rolesDisponibles, $idRol);
        $idRolActivo = (int)($rolActivo['id_rol'] ?? $idRol);
        $permisos = $this->obtenerPermisosNormalizados($idRolActivo);

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
        $_SESSION['usuario_rol'] = $idRolActivo;
        $_SESSION['usuario_rol_nombre'] = (string)($rolActivo['nombre_rol'] ?? ($user['Nombre_Rol'] ?? ''));
        $_SESSION['available_roles'] = $rolesDisponibles;
        $_SESSION['active_context'] = (string)($rolActivo['context_key'] ?? $this->resolverContextoPorRolNombre((string)($rolActivo['nombre_rol'] ?? '')));
        $_SESSION['active_context_role_id'] = $idRolActivo;
        $_SESSION['require_context_selection'] = count($rolesDisponibles) > 1;
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
                'active_context' => $_SESSION['active_context'],
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
     * Centro de materiales: tarjeta del inicio y ruta home/material.
     * El módulo "material" en permisos manda cuando existe configuración explícita.
     * Si no hay fila "material" para el rol, se mantiene compatibilidad con los submódulos
     * de Células, Teens y Capacitación Destino (no con Material UV: ese acceso es aparte).
     */
    public static function puedeVerCentroMaterial(): bool {
        if (self::esAdministrador()) {
            return true;
        }

        self::sincronizarPermisosSesionActual();

        if (self::tienePermiso('material', 'ver')) {
            return true;
        }

        $permisos = (array)($_SESSION['permisos'] ?? []);
        if (!array_key_exists('material', $permisos)) {
            return self::tienePermiso('materiales_celulas', 'ver')
                || self::tienePermiso('material_capacitacion_destino', 'ver')
                || self::tienePermiso('teen', 'ver');
        }

        return false;
    }

    /**
     * Ver listados y fichas de personas (Discípulos, Universidad de la Vida, detalle).
     * Incluye el permiso dedicado personas_consulta sin abrir el módulo completo Ganar-Consolidar.
     */
    public static function puedeVerPersonasConsulta(): bool {
        if (self::esAdministrador()) {
            return true;
        }
        if (self::tieneCoordinacionTotalProgramas()) {
            return true;
        }
        self::sincronizarPermisosSesionActual();
        return !empty($_SESSION['permisos']['personas']['ver'])
            || !empty($_SESSION['permisos']['personas_consulta']['ver']);
    }

    /**
     * Módulo Ganar-Consolidar (menú, campaña, vista personas/ganar).
     */
    public static function puedeVerModuloPersonasGanar(): bool {
        if (self::esAdministrador()) {
            return true;
        }
        self::sincronizarPermisosSesionActual();
        return !empty($_SESSION['permisos']['personas']['ver']);
    }

    /**
     * Sin modulo Personas completo pero con acceso a Programas (UV, Cap. Destino o programas ver).
     * Quien este en este modo gestiona inscritos desde Programas; no se muestra panel Personas en inicio
     * y las entradas personas/ redirigen al consolidado correspondiente.
     */
    public static function debeUsarSoloVistaProgramasPersonas(): bool {
        if (self::esAdministrador()) {
            return false;
        }
        self::sincronizarPermisosSesionActual();
        // No usar tienePermiso('personas','ver') aquí: coordinacion_total amplía personas en código
        // pero la UI debe seguir siendo «solo Programas» salvo que el rol tenga Personas en BD.
        if (!empty($_SESSION['permisos']['personas']['ver'])) {
            return false;
        }
        $prog = $_SESSION['permisos']['programas'] ?? [];
        if (!is_array($prog)) {
            return false;
        }
        return !empty($prog['ver'])
            || !empty($prog['ver_universidad_vida'])
            || !empty($prog['ver_capacitacion_destino'])
            || !empty($prog['coordinacion_total']);
    }

    /**
     * Ruta relativa (?url=...) hacia Programas segun permisos del rol.
     */
    public static function urlProgramasPreferidaRelativa(): string {
        $full = self::tienePermiso('programas', 'ver');
        $uv = $full || self::tienePermiso('programas', 'ver_universidad_vida');
        $cap = $full || self::tienePermiso('programas', 'ver_capacitacion_destino');
        if ($uv && !$cap) {
            return 'programas/consolidar&insc_programa=universidad_vida';
        }
        if (!$uv && $cap) {
            return 'programas/consolidar&insc_programa=capacitacion_destino';
        }
        return 'programas';
    }

    /**
     * Rol de coordinación de formación: no es administrador global, pero debe poder
     * operar el ecosistema Programas + personas inscritas + escuelas sin recibir «acceso denegado».
     * Se activa solo con la acción avanzada programas → coordinacion_total en Permisos.
     */
    public static function tieneCoordinacionTotalProgramas(): bool {
        if (!self::estaAutenticado() || self::esAdministrador()) {
            return false;
        }
        self::sincronizarPermisosSesionActual();
        $p = $_SESSION['permisos']['programas'] ?? null;
        return is_array($p) && !empty($p['coordinacion_total']);
    }

    /**
     * Módulos cubiertos por coordinacion_total (sin acceso al resto de la aplicación).
     *
     * @return array<int, string>
     */
    private static function modulosAmbitoCoordinacionProgramas(): array {
        return [
            'programas',
            'personas',
            'personas_consulta',
            'personas_formulario_publico',
            'escuelas_formacion',
            'escuelas_formacion_marcar_asistencia',
            'escuelas_formacion_editar_fechas',
            'material_universidad_vida',
            'material_capacitacion_destino',
            'material',
        ];
    }

    public static function moduloEnAmbitoCoordinacionProgramas(string $modulo): bool {
        $modulo = strtolower(trim($modulo));
        return in_array($modulo, self::modulosAmbitoCoordinacionProgramas(), true);
    }

    /**
     * Verificar si tiene permiso
     */
    public static function tienePermiso($modulo, $accion = 'ver') {
        if (self::esAdministrador()) {
            return true;
        }

        self::sincronizarPermisosSesionActual();

        if (self::tieneCoordinacionTotalProgramas() && self::moduloEnAmbitoCoordinacionProgramas($modulo)) {
            return true;
        }

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
        $rolNombre = trim((string)($_SESSION['usuario_rol_nombre'] ?? ''));
        // Misma regla que "rol protegido" en Permisos: NO usar substr "admin"
        // (p. ej. "Administrativo" no es administrador global).
        return PermisosCatalogo::esRolProtegidoPermisos($rolId, $rolNombre);
    }

    /**
     * Cuenta en usuario_acceso sin persona vinculada (pestaña "Usuarios administrativos" en Cuentas).
     */
    public static function esCuentaUsuarioAccesoAdministrativaPura(): bool {
        if (empty($_SESSION['auth_user_source'])) {
            return false;
        }
        $src = (string)$_SESSION['auth_user_source'];
        $uid = (int)($_SESSION['usuario_id'] ?? 0);
        return $src === 'acceso' && $uid === 0;
    }

    /**
     * Permisos de módulo necesarios para operar pagos/abonos (sesión actual).
     */
    private static function sesionPuedeOperarPagosEscuelasPorPermiso(): bool {
        if (self::esAdministrador()) {
            return true;
        }
        if (self::tieneCoordinacionTotalProgramas()) {
            return true;
        }
        if (self::tienePermiso('asistencias', 'ver')) {
            return true;
        }
        if (self::tienePermiso('escuelas_formacion', 'ver')
            || self::tienePermiso('escuelas_formacion', 'crear')
            || self::tienePermiso('escuelas_formacion', 'editar')) {
            return true;
        }
        return false;
    }

    /**
     * Solo administradores (rol protegido) o cuentas administrativas puras (acceso sin Id_Persona),
     * con permisos de escuelas/asistencias o coordinación, pueden registrar recaudo en formularios públicos de Escuelas.
     */
    public static function puedeRecibirPagosEscuelasFormacion(): bool {
        if (!self::estaAutenticado()) {
            return false;
        }
        if (!self::sesionPuedeOperarPagosEscuelasPorPermiso()) {
            return false;
        }
        if (self::esAdministrador()) {
            return true;
        }
        return self::esCuentaUsuarioAccesoAdministrativaPura();
    }

    public static function esRolDiscipuloUsuario() {
        if (self::esAdministrador()) {
            return false;
        }

        $contextoActivo = self::normalizarTexto((string)($_SESSION['active_context'] ?? ''));
        if ($contextoActivo !== '') {
            return $contextoActivo === 'discipulo';
        }

        $rolId = isset($_SESSION['usuario_rol']) ? (int) $_SESSION['usuario_rol'] : 0;
        if ($rolId === 2) {
            return true;
        }

        $rolNombre = self::normalizarTexto((string)($_SESSION['usuario_rol_nombre'] ?? ''));
        return strpos($rolNombre, 'discipul') !== false
            || strpos($rolNombre, 'disipul') !== false
            || strpos($rolNombre, 'discipl') !== false
            || strpos($rolNombre, 'disipl') !== false;
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

    public static function getActiveContext(): string {
        return self::normalizarTexto((string)($_SESSION['active_context'] ?? ''));
    }

    private function redirigirPostLogin(): void {
        if ($this->debeMostrarSelectorContexto()) {
            $this->redirect('auth/selector-contexto');
            return;
        }

        $this->redirect('home');
    }

    private function debeMostrarSelectorContexto(): bool {
        if (!self::estaAutenticado()) {
            return false;
        }

        $rolesDisponibles = (array)($_SESSION['available_roles'] ?? []);
        if (count($rolesDisponibles) <= 1) {
            return false;
        }

        return !empty($_SESSION['require_context_selection']);
    }

    private function resolverRolesDisponibles(int $idPersona, int $idRolLegacy, string $nombreRolLegacy): array {
        $roles = [];

        if ($idPersona > 0) {
            $this->userRoleModel->asegurarTabla();
            if ($idRolLegacy > 0) {
                $this->userRoleModel->sincronizarRolPrincipal($idPersona, $idRolLegacy);
            }

            $rolesDb = $this->userRoleModel->listarRolesPersona($idPersona);
            foreach ($rolesDb as $rolDb) {
                $idRol = (int)($rolDb['Id_Rol'] ?? 0);
                $nombre = (string)($rolDb['Nombre_Rol'] ?? '');
                if ($idRol <= 0 || isset($roles[$idRol])) {
                    continue;
                }
                $roles[$idRol] = [
                    'id_rol' => $idRol,
                    'nombre_rol' => $nombre,
                    'context_key' => $this->resolverContextoPorRolNombre($nombre),
                ];
            }
        }

        if ($idRolLegacy > 0 && !isset($roles[$idRolLegacy])) {
            $roles[$idRolLegacy] = [
                'id_rol' => $idRolLegacy,
                'nombre_rol' => $nombreRolLegacy,
                'context_key' => $this->resolverContextoPorRolNombre($nombreRolLegacy),
            ];
        }

        // Si es lider Y está inscrito en capacitación destino, agregar el contexto de discipulo
        $esLider = false;
        foreach ($roles as $rol) {
            if (strpos(self::normalizarTexto($rol['nombre_rol'] ?? ''), 'lider') !== false) {
                $esLider = true;
                break;
            }
        }

        if ($esLider && $idPersona > 0) {
            // Verificar si está inscrito en capacitación destino
            $inscripcionModel = new EscuelaFormacionInscripcion();
            $programas = (array)$inscripcionModel->getProgramasInscritosPersona($idPersona);
            
            $estaEnCapacitacionDestino = false;
            foreach ($programas as $programa) {
                $prog = trim((string)$programa);
                if (strpos($prog, 'capacitacion_destino') !== false) {
                    $estaEnCapacitacionDestino = true;
                    break;
                }
            }

            if ($estaEnCapacitacionDestino) {
                // Buscar rol discipulo en BD
                $idRolDiscipulo = $this->userRoleModel->buscarRolPorAlias('discipulo');
                if ($idRolDiscipulo > 0 && !isset($roles[$idRolDiscipulo])) {
                    $rolDiscipulo = $this->rolModel->getById($idRolDiscipulo);
                    if (!empty($rolDiscipulo)) {
                        $roles[$idRolDiscipulo] = [
                            'id_rol' => $idRolDiscipulo,
                            'nombre_rol' => (string)($rolDiscipulo['Nombre_Rol'] ?? ''),
                            'context_key' => 'discipulo',
                        ];
                    }
                }
            }
        }

        return array_values($roles);
    }

    private function resolverRolActivoInicial(array $rolesDisponibles, int $idRolPreferido): array {
        foreach ($rolesDisponibles as $rol) {
            if ((int)($rol['id_rol'] ?? 0) === $idRolPreferido && $idRolPreferido > 0) {
                return $rol;
            }
        }

        if (!empty($rolesDisponibles)) {
            return $rolesDisponibles[0];
        }

        return [
            'id_rol' => $idRolPreferido,
            'nombre_rol' => '',
            'context_key' => 'lider',
        ];
    }

    private function resolverContextoPorRolNombre(string $nombreRol): string {
        $rol = self::normalizarTexto($nombreRol);
        if ($rol === '') {
            return 'lider';
        }

        if (strpos($rol, 'maestro') !== false || strpos($rol, 'teacher') !== false) {
            return 'maestro';
        }

        if (strpos($rol, 'discipul') !== false || strpos($rol, 'disipul') !== false || strpos($rol, 'discipl') !== false || strpos($rol, 'disipl') !== false) {
            return 'discipulo';
        }

        if (strpos($rol, 'lider') !== false || strpos($rol, 'pastor') !== false || strpos($rol, 'admin') !== false) {
            return 'lider';
        }

        return 'lider';
    }

    private function aplicarContextoActivo(int $idRol, string $contexto): void {
        if ($idRol <= 0) {
            return;
        }

        $rolesDisponibles = (array)($_SESSION['available_roles'] ?? []);
        $rolSeleccionado = null;
        foreach ($rolesDisponibles as $rol) {
            if ((int)($rol['id_rol'] ?? 0) === $idRol) {
                $rolSeleccionado = $rol;
                break;
            }
        }

        if ($rolSeleccionado === null) {
            return;
        }

        $_SESSION['usuario_rol'] = $idRol;
        $_SESSION['usuario_rol_nombre'] = (string)($rolSeleccionado['nombre_rol'] ?? '');
        $_SESSION['active_context'] = $contexto !== '' ? $contexto : (string)($rolSeleccionado['context_key'] ?? 'lider');
        $_SESSION['active_context_role_id'] = $idRol;
        $_SESSION['permisos'] = $this->obtenerPermisosNormalizados($idRol);
        $_SESSION['permisos_configurados'] = !empty($_SESSION['permisos']);
        $_SESSION['permisos_last_sync'] = time();

        $activeAccountId = (int)($_SESSION['active_account_id'] ?? 0);
        if ($activeAccountId > 0 && isset($_SESSION['account_pool'][$activeAccountId])) {
            $_SESSION['account_pool'][$activeAccountId]['rol_nombre'] = $_SESSION['usuario_rol_nombre'];
            $_SESSION['account_pool'][$activeAccountId]['active_context'] = $_SESSION['active_context'];
            $_SESSION['account_pool'][$activeAccountId]['permisos'] = $_SESSION['permisos'];
        }
    }

    /**
     * Página de acceso denegado
     */
    public function accesoDenegado() {
        $this->view('auth/acceso_denegado');
    }
}
