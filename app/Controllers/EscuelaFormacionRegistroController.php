<?php
/**
 * Registro público de Escuelas de Formación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
require_once APP . '/Models/WhatsappLocalQueue.php';
require_once APP . '/Models/WhatsappMensajeTemplate.php';
require_once APP . '/Models/UsuarioAcceso.php';
require_once APP . '/Models/UserRole.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/PermisosCatalogo.php';

class EscuelaFormacionRegistroController extends BaseController {
    private $personaModel;
    private $ministerioModel;
    private $inscripcionModel;
    private $escuelaAsistenciaClaseModel;
    private $whatsappLocalQueueModel;
    private $whatsappMensajeTemplateModel;
    private $usuarioAccesoModel;
    private $userRoleModel;
    private $soportaProceso = false;
    private $soportaOrigenGanar = false;
    private $soportaEsAntiguo = false;
    private $soportaObservacionGanadoEn = false;
    private $soportaCreadoPor = false;
    private $soportaCanalCreacion = false;
    private $soportaChecklistEscalera = false;
    private $soportaEmail = false;
    private $soportaDireccion = false;
    private $soportaFechaNacimiento = false;
    private $idRolAsistenteCache = null;

    private function usuarioPuedeVerPagos() {
        if (!class_exists('AuthController')) {
            return false;
        }

        return AuthController::esAdministrador()
            || AuthController::tienePermiso('asistencias', 'ver')
            || AuthController::tieneCoordinacionTotalProgramas();
    }

    public function __construct() {
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
        $this->inscripcionModel = new EscuelaFormacionInscripcion();
        $this->escuelaAsistenciaClaseModel = new EscuelaFormacionAsistenciaClase();
        $this->whatsappLocalQueueModel = new WhatsappLocalQueue();
        $this->whatsappMensajeTemplateModel = new WhatsappMensajeTemplate();
        $this->usuarioAccesoModel = new UsuarioAcceso();
        $this->userRoleModel = new UserRole();

        $this->personaModel->ensureProcesoColumnExists();
        $this->personaModel->ensureOrigenGanarColumnExists();
        $this->personaModel->ensureObservacionGanadoEnColumnExists();
        $this->personaModel->ensureCreadoPorColumnExists();
        $this->personaModel->ensureCanalCreacionColumnExists();
        $this->personaModel->ensureEscaleraChecklistColumnExists();

        $this->soportaProceso = $this->personaModel->tieneColumna('Proceso');
        $this->soportaOrigenGanar = $this->personaModel->tieneColumna('Origen_Ganar');
        $this->soportaEsAntiguo = $this->personaModel->tieneColumna('Es_Antiguo');
        $this->soportaObservacionGanadoEn = $this->personaModel->tieneColumna('Observacion_Ganado_En');
        $this->soportaCreadoPor = $this->personaModel->tieneColumna('Creado_Por');
        $this->soportaCanalCreacion = $this->personaModel->tieneColumna('Canal_Creacion');
        $this->soportaChecklistEscalera = $this->personaModel->tieneColumna('Escalera_Checklist');
        $this->soportaEmail = $this->personaModel->tieneColumna('Email');
        $this->soportaDireccion = $this->personaModel->tieneColumna('Direccion');
        $this->soportaFechaNacimiento = $this->personaModel->tieneColumna('Fecha_Nacimiento');
    }

    private function asegurarSesionAbono() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
    }

    private function construirNombreUsuarioAutorizado(array $user): string {
        $nombre = trim((string)($user['Nombre_Mostrar'] ?? ''));
        if ($nombre !== '') {
            return $nombre;
        }

        $nombrePersona = trim((string)($user['Nombre'] ?? '') . ' ' . (string)($user['Apellido'] ?? ''));
        if ($nombrePersona !== '') {
            return $nombrePersona;
        }

        $usuario = trim((string)($user['Usuario'] ?? ''));
        return $usuario !== '' ? $usuario : 'USUARIO AUTORIZADO';
    }

    private function esRolAdministradorSegunUsuario(array $user): bool {
        $idRol = (int)($user['Id_Rol'] ?? 0);
        $nombre = trim((string)($user['Nombre_Rol'] ?? ''));
        return PermisosCatalogo::esRolProtegidoPermisos($idRol, $nombre);
    }

    /**
     * Tras autenticar por credenciales: solo usuario_acceso sin Id_Persona, o persona con rol administrador protegido.
     */
    private function esCuentaUsuarioAutorizadaRecibirPagosDesdeCredenciales(array $user): bool {
        $idAcceso = (int)($user['Id_Usuario_Acceso'] ?? 0);
        if ($idAcceso > 0) {
            return (int)($user['Id_Persona'] ?? 0) <= 0;
        }
        return $this->esRolAdministradorSegunUsuario($user);
    }

    private function usuarioPuedeDesbloquearAbono(array $user): bool {
        if (!$this->esCuentaUsuarioAutorizadaRecibirPagosDesdeCredenciales($user)) {
            return false;
        }
        if ($this->esRolAdministradorSegunUsuario($user)) {
            return true;
        }

        $idRol = (int)($user['Id_Rol'] ?? 0);
        if ($idRol <= 0) {
            return false;
        }

        $permisos = (array)$this->personaModel->getPermisosPorRol($idRol);
        $map = [];
        foreach ($permisos as $permiso) {
            $modulo = trim((string)($permiso['Modulo'] ?? ''));
            if ($modulo === '') {
                continue;
            }
            $map[$modulo] = [
                'ver' => !empty($permiso['Puede_Ver']),
                'crear' => !empty($permiso['Puede_Crear']),
                'editar' => !empty($permiso['Puede_Editar']),
                'eliminar' => !empty($permiso['Puede_Eliminar']),
            ];
        }

        $tiene = static function(array $mapa, string $modulo): bool {
            if (!isset($mapa[$modulo])) {
                return false;
            }
            return !empty($mapa[$modulo]['ver']) || !empty($mapa[$modulo]['crear']) || !empty($mapa[$modulo]['editar']);
        };

        return $tiene($map, 'escuelas_formacion') || $tiene($map, 'asistencias');
    }

    private function obtenerAutorizacionAbonoSesion(): array {
        $this->asegurarSesionAbono();
        $auth = $_SESSION['escuelas_formacion_abono_auth'] ?? null;
        $sesionGlobalActiva = (int)($_SESSION['usuario_id'] ?? 0) > 0;

        $asegurarDesdeSesionGlobal = function() {
            $nombreSesion = trim((string)($_SESSION['usuario_nombre'] ?? 'USUARIO AUTORIZADO'));
            if ($nombreSesion === '') {
                $nombreSesion = 'USUARIO AUTORIZADO';
            }
            $nombreSesion = $this->normalizarTextoMayusculas($nombreSesion);
            $_SESSION['escuelas_formacion_abono_auth'] = [
                'nombre' => $nombreSesion,
                'usuario' => $nombreSesion,
                'at' => time(),
                'expira' => time() + (8 * 60 * 60),
                'via_credenciales' => false,
            ];
            return ['autorizado' => true, 'nombre' => $nombreSesion];
        };

        if (!is_array($auth)) {
            if ($sesionGlobalActiva || (class_exists('AuthController') && AuthController::estaAutenticado())) {
                if (class_exists('AuthController') && AuthController::puedeRecibirPagosEscuelasFormacion()) {
                    return $asegurarDesdeSesionGlobal();
                }
            }
            return ['autorizado' => false, 'nombre' => ''];
        }

        $expira = (int)($auth['expira'] ?? 0);
        if ($expira <= time()) {
            unset($_SESSION['escuelas_formacion_abono_auth']);
            if ($sesionGlobalActiva || (class_exists('AuthController') && AuthController::estaAutenticado())) {
                if (class_exists('AuthController') && AuthController::puedeRecibirPagosEscuelasFormacion()) {
                    return $asegurarDesdeSesionGlobal();
                }
            }
            return ['autorizado' => false, 'nombre' => ''];
        }

        $nombre = trim((string)($auth['nombre'] ?? ''));
        // Sesión interna sin derecho a recaudo: invalidar auto-rellenos viejos; mantener solo desbloqueo por credenciales.
        if (class_exists('AuthController') && AuthController::estaAutenticado()
            && !AuthController::puedeRecibirPagosEscuelasFormacion()
            && empty($auth['via_credenciales'])) {
            return ['autorizado' => false, 'nombre' => ''];
        }
        return ['autorizado' => $nombre !== '', 'nombre' => $nombre];
    }

    private function limpiarAutorizacionAbonoSesion(): void {
        $this->asegurarSesionAbono();
        unset($_SESSION['escuelas_formacion_abono_auth']);
    }

    public function validarAccesoAbono() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'mensaje' => 'Metodo no permitido'], 405);
            return;
        }

        $usuario = trim((string)($_POST['usuario'] ?? ''));
        $contrasena = (string)($_POST['contrasena'] ?? '');

        if ($usuario === '' || $contrasena === '') {
            $this->json(['success' => false, 'mensaje' => 'Usuario y contrasena son obligatorios.'], 422);
            return;
        }

        $user = $this->personaModel->autenticar($usuario, $contrasena);
        if (!$user && $this->usuarioAccesoModel->existeTabla()) {
            $user = $this->usuarioAccesoModel->autenticar($usuario, $contrasena);
        }

        if (!$user) {
            $this->json(['success' => false, 'mensaje' => 'Credenciales invalidas.'], 401);
            return;
        }

        if (!$this->usuarioPuedeDesbloquearAbono((array)$user)) {
            $this->json(['success' => false, 'mensaje' => 'Solo cuentas administrativas (usuario de acceso sin persona vinculada) o administradores del sistema pueden registrar pagos y abonos.'], 403);
            return;
        }

        $nombre = $this->normalizarTextoMayusculas($this->construirNombreUsuarioAutorizado((array)$user));
        $this->asegurarSesionAbono();
        $_SESSION['escuelas_formacion_abono_auth'] = [
            'nombre' => $nombre,
            'usuario' => $usuario,
            'at' => time(),
            'expira' => time() + (8 * 60 * 60),
            'via_credenciales' => true,
        ];

        $this->json([
            'success' => true,
            'mensaje' => 'Acceso a abonos habilitado.',
            'nombre' => $nombre,
        ]);
    }

    private function buildAbsolutePublicUrl($route) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(PUBLIC_URL, '/');
        return $scheme . '://' . $host . $base . '/index.php?url=' . urlencode($route);
    }

    private function obtenerProgramasPorGrupo(string $programa) {
        $programa = trim((string)$programa);
        if ($programa === 'universidad_vida') {
            return ['universidad_vida', 'encuentro', 'bautismo'];
        }
        if ($programa === 'capacitacion_destino') {
            return ['capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'];
        }
        return [];
    }

    private function obtenerRutaRegistroPublico(string $programa) {
        $programa = trim((string)$programa);
        if ($programa === 'universidad_vida') {
            return 'escuelas_formacion/registro-publico/universidad-vida';
        }
        if ($programa === 'capacitacion_destino') {
            return 'escuelas_formacion/registro-publico/capacitacion-destino';
        }
        return 'escuelas_formacion/registro-publico/universidad-vida';
    }

    private function redirectRegistroPublico(array $params = [], string $programa = '') {
        $ruta = $this->obtenerRutaRegistroPublico($programa);
        $params = array_merge(['url' => $ruta], $params);
        header('Location: ' . PUBLIC_URL . '?' . http_build_query($params));
        exit;
    }

    private function redirectRegistroConError(string $mensaje, string $programa = '', array $extraParams = []) {
        $ruta = $this->obtenerRutaRegistroPublico($programa);
        $params = array_merge(
            ['url' => $ruta, 'mensaje' => $mensaje, 'tipo' => 'error', 'programa' => $programa],
            $extraParams
        );
        header('Location: ' . PUBLIC_URL . '?' . http_build_query($params));
        exit;
    }

    private function encolarMensajeCapacitacionDestino(int $idPersona, string $telefono, string $nombre, string $programa): void {
        if (!in_array($programa, ['capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            return;
        }

        $telefono = trim((string)$telefono);
        if ($telefono === '') {
            return;
        }

        try {
            $payload = $this->whatsappMensajeTemplateModel->getTemplatePayload('mensaje_capacitacion_destino', [
                'persona_nombre' => $this->normalizarTextoMayusculas(trim((string)$nombre)),
                'url_capacitacion_destino' => $this->buildAbsolutePublicUrl('eventos/capacitacion-destino/publico')
            ]);

            $mensaje = trim((string)($payload['mensaje'] ?? ''));
            if ($mensaje === '') {
                return;
            }

            $this->whatsappLocalQueueModel->encolar(
                $telefono,
                $mensaje,
                'mensaje_capacitacion_destino',
                'capacitacion_destino:persona:' . $idPersona,
                $payload['media_url'] ?? null,
                $payload['media_tipo'] ?? null
            );
        } catch (Exception $e) {
            error_log('Error encolando mensaje de Capacitación Destino: ' . $e->getMessage());
        }
    }

    public function codigos() {
        $programaFiltro = trim((string)($_GET['programa'] ?? ''));
        if (!in_array($programaFiltro, ['universidad_vida', 'capacitacion_destino'], true)) {
            $programaFiltro = '';
        }

        $urlFormularioUv = $this->buildAbsolutePublicUrl('escuelas_formacion/registro-publico/universidad-vida');
        $urlFormularioCapacitacion = $this->buildAbsolutePublicUrl('escuelas_formacion/registro-publico/capacitacion-destino');

        $this->view('escuelas_formacion_publico/codigos', [
            'programa_filtro' => $programaFiltro,
            'url_formulario_uv' => $urlFormularioUv,
            'url_formulario_capacitacion' => $urlFormularioCapacitacion,
        ]);
    }

    public function registroPublicoUniversidadVida() {
        $this->index('universidad_vida');
    }

    public function registroPublicoCapacitacionDestino() {
        $this->index('capacitacion_destino');
    }

    private function normalizarChecklistEscalera($checklist) {
        $estructuraEtapas = [
            'Ganar' => 6,
            'Consolidar' => 3,
            'Discipular' => 3,
            'Enviar' => 3
        ];

        $normalizado = [];
        foreach ($estructuraEtapas as $etapa => $totalSubprocesos) {
            $normalizado[$etapa] = array_fill(0, $totalSubprocesos, false);
        }

        $normalizado['_meta'] = [
            'no_disponible_observacion' => '',
            'convenciones' => [],
            'reasignado_automatico' => false,
            'reasignado_automatico_at' => '',
            'reasignado_automatico_motivo' => '',
            'reasignado_manual' => false,
            'reasignado_manual_at' => '',
            'reasignado_manual_motivo' => ''
        ];

        if (!is_array($checklist)) {
            return $normalizado;
        }

        foreach ($estructuraEtapas as $etapa => $totalSubprocesos) {
            $valoresEtapa = $checklist[$etapa] ?? [];
            if (!is_array($valoresEtapa)) {
                continue;
            }

            for ($i = 0; $i < $totalSubprocesos; $i++) {
                $normalizado[$etapa][$i] = !empty($valoresEtapa[$i]);
            }
        }

        if (isset($checklist['_meta']) && is_array($checklist['_meta'])) {
            $normalizado['_meta']['no_disponible_observacion'] = trim((string)($checklist['_meta']['no_disponible_observacion'] ?? ''));
            $normalizado['_meta']['convenciones'] = array_values(array_filter((array)($checklist['_meta']['convenciones'] ?? []), static function($item) {
                return trim((string)$item) !== '';
            }));
            $normalizado['_meta']['reasignado_automatico'] = !empty($checklist['_meta']['reasignado_automatico']);
            $normalizado['_meta']['reasignado_automatico_at'] = trim((string)($checklist['_meta']['reasignado_automatico_at'] ?? ''));
            $normalizado['_meta']['reasignado_automatico_motivo'] = trim((string)($checklist['_meta']['reasignado_automatico_motivo'] ?? ''));
            $normalizado['_meta']['reasignado_manual'] = !empty($checklist['_meta']['reasignado_manual']);
            $normalizado['_meta']['reasignado_manual_at'] = trim((string)($checklist['_meta']['reasignado_manual_at'] ?? ''));
            $normalizado['_meta']['reasignado_manual_motivo'] = trim((string)($checklist['_meta']['reasignado_manual_motivo'] ?? ''));
        }

        return $normalizado;
    }

    private function calcularProcesoPorChecklist(array $checklistNormalizado) {
        if (!empty($checklistNormalizado['Ganar'][5])) {
            return 'Ganar';
        }

        $etapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $completadasSeguidas = 0;

        foreach ($etapas as $etapa) {
            $valores = $checklistNormalizado[$etapa] ?? [false, false, false];
            if ($etapa === 'Ganar') {
                $completa = !empty($valores[0]) && !empty($valores[1]) && !empty($valores[2]) && !empty($valores[3]) && !empty($valores[4]);
            } else {
                $completa = !empty($valores[0]) && !empty($valores[1]) && !empty($valores[2]);
            }

            if (!$completa) {
                break;
            }

            $completadasSeguidas++;
        }

        if ($completadasSeguidas === 0) {
            return 'Ganar';
        }

        if ($completadasSeguidas >= count($etapas)) {
            return 'Enviar';
        }

        return $etapas[$completadasSeguidas];
    }

    private function marcarProgramaConsolidarEnEscalera($idPersona, $programa) {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);

        $mapaProgramaAIndice = [
            'universidad_vida' => 0,
            'encuentro' => 1,
            'bautismo' => 2,
        ];

        if ($idPersona <= 0 || !$this->soportaChecklistEscalera || !isset($mapaProgramaAIndice[$programa])) {
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return;
        }

        $checklistActual = [];
        $checklistRaw = (string)($persona['Escalera_Checklist'] ?? '');
        if ($checklistRaw !== '') {
            $decoded = json_decode($checklistRaw, true);
            if (is_array($decoded)) {
                $checklistActual = $decoded;
            }
        }

        $checklistNormalizado = $this->normalizarChecklistEscalera($checklistActual);
        $checklistNormalizado['Ganar'][1] = !empty($persona['Id_Lider']) && !empty($persona['Id_Ministerio']);
        $checklistNormalizado['Ganar'][4] = !empty($persona['Id_Celula']);
        $checklistNormalizado['Consolidar'][(int)$mapaProgramaAIndice[$programa]] = true;

        $checklistJson = json_encode($checklistNormalizado, JSON_UNESCAPED_UNICODE);
        if ($checklistJson === false) {
            return;
        }

        $proceso = $this->soportaProceso ? $this->calcularProcesoPorChecklist($checklistNormalizado) : null;
        $this->personaModel->updateEscaleraChecklistYProceso($idPersona, $checklistJson, $proceso);
    }

    private function marcarNivelDiscipularEnEscalera($idPersona, $programa) {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);

        $mapaProgramaAIndice = [
            'capacitacion_destino_nivel_1' => 0,
            'capacitacion_destino_nivel_2' => 1,
            'capacitacion_destino_nivel_3' => 2,
        ];

        if ($idPersona <= 0 || !$this->soportaChecklistEscalera || !isset($mapaProgramaAIndice[$programa])) {
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return;
        }

        $checklistActual = [];
        $checklistRaw = (string)($persona['Escalera_Checklist'] ?? '');
        if ($checklistRaw !== '') {
            $decoded = json_decode($checklistRaw, true);
            if (is_array($decoded)) {
                $checklistActual = $decoded;
            }
        }

        $checklistNormalizado = $this->normalizarChecklistEscalera($checklistActual);
        $checklistNormalizado['Ganar'][1] = !empty($persona['Id_Lider']) && !empty($persona['Id_Ministerio']);
        $checklistNormalizado['Ganar'][4] = !empty($persona['Id_Celula']);
        $checklistNormalizado['Discipular'][(int)$mapaProgramaAIndice[$programa]] = true;

        $checklistJson = json_encode($checklistNormalizado, JSON_UNESCAPED_UNICODE);
        if ($checklistJson === false) {
            return;
        }

        $proceso = $this->soportaProceso ? $this->calcularProcesoPorChecklist($checklistNormalizado) : null;
        $this->personaModel->updateEscaleraChecklistYProceso($idPersona, $checklistJson, $proceso);
    }

    private function normalizarTextoMayusculas($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valor = preg_replace('/\s+/', ' ', $valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
    }

    private function normalizarDocumento($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valor = preg_replace('/\s+/', '', $valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
    }

    private function normalizarTipoDocumento($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $canonicos = [
            'Cedula de Ciudadania',
            'Cedula Extranjera',
            'Tarjeta de Identidad',
            'Registro Civil'
        ];
        if (in_array($valor, $canonicos, true)) {
            return $valor;
        }

        $valorNormalizado = strtoupper($valor);
        $valorNormalizado = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú'], ['A', 'E', 'I', 'O', 'U'], $valorNormalizado);

        $mapa = [
            'CC' => 'Cedula de Ciudadania',
            'CEDULA DE CIUDADANIA' => 'Cedula de Ciudadania',
            'CEDULA CIUDADANIA' => 'Cedula de Ciudadania',
            'CE' => 'Cedula Extranjera',
            'CEDULA EXTRANJERA' => 'Cedula Extranjera',
            'TI' => 'Tarjeta de Identidad',
            'TARJETA DE IDENTIDAD' => 'Tarjeta de Identidad',
            'RC' => 'Registro Civil',
            'REGISTRO CIVIL' => 'Registro Civil'
        ];

        if (isset($mapa[$valorNormalizado])) {
            return $mapa[$valorNormalizado];
        }

        return '';
    }

    private function normalizarEmail($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valor = strtolower($valor);
        return filter_var($valor, FILTER_VALIDATE_EMAIL) ? $valor : '';
    }

    private function normalizarTelefono($telefono) {
        $telefono = trim((string)$telefono);
        if ($telefono === '') {
            return '';
        }

        $telefono = preg_replace('/[^0-9+]/', '', $telefono);

        if (substr_count($telefono, '+') > 1) {
            $telefono = '+' . str_replace('+', '', $telefono);
        }

        if (strpos($telefono, '+') > 0) {
            $telefono = '+' . str_replace('+', '', $telefono);
        }

        return $telefono;
    }

    private function normalizarSoloDigitos($valor) {
        return preg_replace('/\D+/', '', (string)$valor);
    }

    private function esTextoBasuraDocumentoTelefono($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return false;
        }

        $normalizado = function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
        $normalizado = preg_replace('/\s+/', '', $normalizado);
        $normalizado = str_replace(['.', '-', '_', '/'], '', $normalizado);

        $bloqueados = ['NO', 'NA', 'N/A', 'NINGUNO', 'NINGUNA', 'SINDATO', 'SN', 'XX', 'XXX'];
        return in_array($normalizado, $bloqueados, true);
    }

    private function esNumeroRepetidoInvalido($valor, $minLen = 3) {
        $digits = $this->normalizarSoloDigitos($valor);
        if ($digits === '' || strlen($digits) < $minLen) {
            return false;
        }

        // Invalida solo repeticiones consecutivas (p.ej. 111, 000, 5555).
        return preg_match('/(\d)\1{' . max(1, $minLen - 1) . ',}/', $digits) === 1;
    }

    private function normalizarEdad($valor) {
        $valor = trim((string)$valor);
        if ($valor === '' || !ctype_digit($valor)) {
            return 0;
        }

        return (int)$valor;
    }

    private function normalizarEntregoLibro($valor) {
        $valor = strtolower(trim((string)$valor));
        if (in_array($valor, ['1', 'si', 'sí', 'true'], true)) {
            return 1;
        }

        return 0;
    }

    private function normalizarTipoPago($valor) {
        $valor = strtolower(trim((string)$valor));
        return $valor === 'completo' ? 'completo' : 'abono';
    }

    /**
     * Normaliza montos monetarios aceptando formatos como:
     * 180000, 180000.00, 180.000,00, 180,000.00
     * Devuelve float con 2 decimales listo para persistencia.
     */
    private function normalizarValorPago($valor): float {
        $raw = trim((string)$valor);
        if ($raw === '') {
            return 0.0;
        }

        $raw = str_replace([' ', '\u{00A0}'], '', $raw);

        $tieneComa = strpos($raw, ',') !== false;
        $tienePunto = strpos($raw, '.') !== false;

        if ($tieneComa && $tienePunto) {
            $ultimaComa = strrpos($raw, ',');
            $ultimoPunto = strrpos($raw, '.');
            if ($ultimaComa > $ultimoPunto) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($tieneComa && !$tienePunto) {
            if (preg_match('/,\d{1,2}$/', $raw) === 1) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($tienePunto && !$tieneComa) {
            if (preg_match('/\.\d{1,2}$/', $raw) !== 1) {
                $raw = str_replace('.', '', $raw);
            }
        }

        $raw = preg_replace('/[^0-9.\-]/', '', $raw);
        if ($raw === '' || !is_numeric($raw)) {
            return 0.0;
        }

        return round((float)$raw, 2);
    }

    private function calcularEdadDesdeFechaNacimiento($fechaNacimiento) {
        $fechaNacimiento = trim((string)$fechaNacimiento);
        if ($fechaNacimiento === '') {
            return 0;
        }

        try {
            $fecha = new DateTime($fechaNacimiento);
            $hoy = new DateTime('today');
            $edad = (int)$fecha->diff($hoy)->y;
            return $edad > 0 ? $edad : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    private function normalizarGeneroBinario($genero) {
        $genero = trim((string)$genero);
        if ($genero === '') {
            return '';
        }

        $generoLower = strtolower($genero);
        if (in_array($generoLower, ['hombre', 'joven hombre', 'joven_hombre', 'masculino', 'm'], true)) {
            return 'Hombre';
        }
        if (in_array($generoLower, ['mujer', 'joven mujer', 'joven_mujer', 'femenino', 'f'], true)) {
            return 'Mujer';
        }

        return '';
    }

    private function separarNombreApellido($nombreCompleto) {
        $nombreCompleto = preg_replace('/\s+/', ' ', trim((string)$nombreCompleto));
        if ($nombreCompleto === '') {
            return ['nombre' => '', 'apellido' => ''];
        }

        $partes = explode(' ', $nombreCompleto);
        if (count($partes) === 1) {
            return ['nombre' => $partes[0], 'apellido' => '.'];
        }

        $nombre = array_shift($partes);
        $apellido = trim(implode(' ', $partes));
        if ($apellido === '') {
            $apellido = '.';
        }

        return ['nombre' => $nombre, 'apellido' => $apellido];
    }

    private function etiquetaProgramaEscuela($programa) {
        $map = [
            'universidad_vida' => 'Universidad de la Vida',
            'encuentro' => 'Encuentro',
            'bautismo' => 'Bautismo',
            'capacitacion_destino' => 'Capacitacion Destino',
            'capacitacion_destino_nivel_1' => 'Capacitacion Destino - Nivel 1',
            'capacitacion_destino_nivel_2' => 'Capacitacion Destino - Nivel 2',
            'capacitacion_destino_nivel_3' => 'Capacitacion Destino - Nivel 3',
        ];

        $programa = trim((string)$programa);
        if ($programa === '') {
            return 'Programa';
        }

        return $map[$programa] ?? $programa;
    }

    private function obtenerIdRolAsistenteDefault() {
        if ($this->idRolAsistenteCache !== null) {
            return $this->idRolAsistenteCache;
        }

        $this->idRolAsistenteCache = 0;

        try {
            $rows = $this->personaModel->query("SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC");
            $prioridades = [
                'asistente' => 1,
                'miembro' => 2,
                'visitante' => 3,
                'colaborador' => 4,
            ];
            $mejorIdRol = 0;
            $mejorPrioridad = PHP_INT_MAX;

            foreach ((array)$rows as $row) {
                $nombreRol = strtolower(trim((string)($row['Nombre_Rol'] ?? '')));
                $nombreRol = strtr($nombreRol, [
                    'á' => 'a',
                    'é' => 'e',
                    'í' => 'i',
                    'ó' => 'o',
                    'ú' => 'u',
                    'ü' => 'u',
                    'ñ' => 'n'
                ]);

                $idRol = (int)($row['Id_Rol'] ?? 0);
                if ($idRol <= 0 || $nombreRol === '') {
                    continue;
                }

                foreach ($prioridades as $palabra => $prioridad) {
                    if (strpos($nombreRol, $palabra) !== false && $prioridad < $mejorPrioridad) {
                        $mejorPrioridad = $prioridad;
                        $mejorIdRol = $idRol;
                    }
                }
            }

            if ($mejorIdRol > 0) {
                $this->idRolAsistenteCache = $mejorIdRol;
            }
        } catch (Exception $e) {
            $this->idRolAsistenteCache = 0;
        }

        return $this->idRolAsistenteCache;
    }

    private function normalizarProgramaInscripcion($programa) {
        $programa = trim((string)$programa);
        if ($programa === 'capacitacion_destino') {
            return 'capacitacion_destino_nivel_1';
        }

        return $programa;
    }

    private function normalizarTextoRol($texto) {
        $texto = strtolower(trim((string)$texto));
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

    private function esRolDiscipuloPersona(int $idPersona): bool {
        if ($idPersona <= 0) {
            return false;
        }

        $rows = $this->personaModel->query(
            "SELECT p.Id_Rol, COALESCE(r.Nombre_Rol, '') AS Nombre_Rol
             FROM persona p
             LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
             WHERE p.Id_Persona = ?
             LIMIT 1",
            [$idPersona]
        );

        if (empty($rows)) {
            return false;
        }

        $row = (array)$rows[0];
        $idRol = (int)($row['Id_Rol'] ?? 0);
        if ($idRol === 2) {
            return true;
        }

        $rolNombre = $this->normalizarTextoRol((string)($row['Nombre_Rol'] ?? ''));
        return strpos($rolNombre, 'discipul') !== false
            || strpos($rolNombre, 'disipul') !== false
            || strpos($rolNombre, 'discipl') !== false
            || strpos($rolNombre, 'disipl') !== false;
    }

    private function asegurarCredencialesDiscipulo(int $idPersona, string $cedula): void {
        $idPersona = (int)$idPersona;
        $cedula = $this->normalizarDocumento($cedula);
        if ($idPersona <= 0 || $cedula === '') {
            return;
        }

        // Regla: solo crear credenciales automáticas para rol discípulo.
        // No sobreescribe cuentas existentes (p.ej. líderes u otros roles).
        if (!$this->esRolDiscipuloPersona($idPersona)) {
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return;
        }

        $usuarioActual = trim((string)($persona['Usuario'] ?? ''));
        $hashActual = trim((string)($persona['Contrasena'] ?? ''));

        if ($usuarioActual !== '') {
            return;
        }

        $this->personaModel->setUsuario($idPersona, $cedula, $cedula);
    }

    private function asignarSegundoRolDiscipuloAutomatico(int $idPersona, string $programa): void {
        $idPersona = (int)$idPersona;
        $programa = trim((string)$programa);
        if ($idPersona <= 0) {
            return;
        }

        if (!in_array($programa, ['capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            return;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return;
        }

        $idRolActual = (int)($persona['Id_Rol'] ?? 0);
        if ($idRolActual > 0) {
            $this->userRoleModel->sincronizarRolPrincipal($idPersona, $idRolActual);
        }

        $idRolDiscipulo = $this->userRoleModel->buscarRolPorAlias('discipulo');
        if ($idRolDiscipulo <= 0) {
            return;
        }

        // Inserción idempotente: no falla si ya existe la relación.
        $this->userRoleModel->asignarRol($idPersona, $idRolDiscipulo);
    }

    private function generarReferenciaCorta() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $ref = 'R';
        for ($i = 0; $i < 5; $i++) {
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $ref;
    }

    private function resolverModuloFormacionPorPrograma($programa) {
        $programa = $this->normalizarProgramaInscripcion($programa);

        if (in_array($programa, ['universidad_vida', 'encuentro'], true)) {
            return 'consolidar';
        }

        if (in_array($programa, ['bautismo', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            return 'discipular';
        }

        return '';
    }

    private function familiaPrograma($programa) {
        $programa = $this->normalizarProgramaInscripcion($programa);
        if (in_array($programa, ['universidad_vida', 'encuentro', 'bautismo'], true)) {
            return 'uv';
        }
        if (in_array($programa, ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            return 'destino';
        }
        return '';
    }

    private function renderTicketHtml($data) {
        $nombre = htmlspecialchars((string)($data['nombre'] ?? ''));
        $cedula = htmlspecialchars((string)($data['cedula'] ?? ''));
        $programa = htmlspecialchars((string)($data['programa'] ?? ''));
        $metodoPago = htmlspecialchars((string)($data['metodo_pago'] ?? ''));
        $recibidoPor = htmlspecialchars((string)($data['recibido_por'] ?? ''));
        $tipoPago = htmlspecialchars((string)($data['tipo_pago'] ?? ''));
        $valorPago = htmlspecialchars((string)($data['valor_pago'] ?? ''));
        $referencia = htmlspecialchars((string)($data['referencia_pago'] ?? ''));
        $fecha = htmlspecialchars((string)($data['fecha'] ?? date('Y-m-d H:i')));

        return '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Ticket de inscripción</title><style>body{font-family:Arial,sans-serif;background:#f3f7f7;padding:20px}.ticket{max-width:520px;margin:0 auto;background:#fff;border:1px solid #dce8e7;border-radius:12px;padding:18px}.title{margin:0 0 10px;color:#0a6e6a}.row{margin:8px 0;font-size:14px}.ref{font-family:monospace;font-size:28px;font-weight:700;letter-spacing:2px;color:#0a6e6a;margin:12px 0}.actions{margin-top:16px}.btn{display:inline-block;background:#0a6e6a;color:#fff;padding:10px 14px;border-radius:8px;text-decoration:none;border:none;cursor:pointer}</style></head><body><div class="ticket"><h2 class="title">Ticket de inscripción</h2><div class="row"><strong>Fecha:</strong> ' . $fecha . '</div><div class="row"><strong>Nombre:</strong> ' . $nombre . '</div><div class="row"><strong>Cédula:</strong> ' . $cedula . '</div><div class="row"><strong>Programa:</strong> ' . $programa . '</div><div class="row"><strong>Método pago:</strong> ' . $metodoPago . '</div><div class="row"><strong>Recibido por:</strong> ' . $recibidoPor . '</div><div class="row"><strong>Tipo pago:</strong> ' . $tipoPago . '</div><div class="row"><strong>Valor pagado:</strong> $' . $valorPago . '</div><div class="row"><strong>Referencia:</strong></div><div class="ref">' . $referencia . '</div><div class="actions"><button class="btn" onclick="window.print()">Imprimir ticket</button></div></div></body></html>';
    }

    private function marcarAsistenciaAutomaticaDesdeRegistroPublico($idInscripcion, $idPersona, $programa, $fechaAsistencia = null) {
        $idInscripcion = (int)$idInscripcion;
        $idPersona = (int)$idPersona;
        $programaNormalizado = $this->normalizarProgramaInscripcion($programa);
        $fechaAsistencia = trim((string)($fechaAsistencia ?? date('Y-m-d')));
        
        // LOG: Inicio del proceso
        $logMsg = "[" . date('Y-m-d H:i:s') . "] marcarAsistencia: idPersona=$idPersona, idInscripcion=$idInscripcion, programa=$programa, programaNormalizado=$programaNormalizado, fechaAsistencia=$fechaAsistencia";
        error_log($logMsg);

        $modulo = $this->resolverModuloFormacionPorPrograma($programaNormalizado);
        error_log("  -> modulo resuelto: $modulo");
        
        if ($idPersona <= 0 || $modulo === '' || $programaNormalizado === '') {
            error_log("  -> ABORTADO: idPersona=$idPersona, modulo=$modulo, programa=$programaNormalizado");
            return;
        }

        require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();
        $numeroClase = $asistenciaModel->getNumeroClasePorFecha($modulo, $programaNormalizado, $fechaAsistencia);
        error_log("  -> numeroClase buscado: $numeroClase (para fecha=$fechaAsistencia)");

        if ($numeroClase <= 0) {
            // Crear clase automáticamente si no existe
            $numeroClase = $asistenciaModel->getProximoNumeroClase($modulo, $programaNormalizado);
            error_log("  -> Creando clase nueva: numeroClase=$numeroClase para fecha=$fechaAsistencia");
            $asistenciaModel->upsertFechaClase($modulo, $programaNormalizado, $numeroClase, $fechaAsistencia);
        }

        // Marca la clase que tenga configurada la misma fecha del registro de asistencia.
        error_log("  -> Insertando asistencia: idPersona=$idPersona, modulo=$modulo, programa=$programaNormalizado, numeroClase=$numeroClase");
        $resultadoUpsert = $asistenciaModel->upsertAsistencia($idPersona, $modulo, $programaNormalizado, $numeroClase, true);
        error_log("  -> upsertAsistencia resultado: " . ($resultadoUpsert ? 'OK' : 'FALLO'));

        if ($idInscripcion > 0) {
            // Actualizar asistencia en inscripción
            error_log("  -> Actualizando inscripcion $idInscripcion");
            $resultadoInscripcion = $this->inscripcionModel->actualizarAsistenciaClase($idInscripcion, true);
            error_log("  -> actualizarAsistenciaClase resultado: " . ($resultadoInscripcion ? 'OK' : 'FALLO'));
        }

        error_log("  -> COMPLETADO exitosamente");
        return true;
    }

    private function resolverIdLiderPorNombre($nombreLider) {
        $nombreLider = $this->normalizarTextoMayusculas($nombreLider);
        if ($nombreLider === '') {
            return 0;
        }

        $rowsExactos = $this->personaModel->query(
            "SELECT Id_Persona
             FROM persona
             WHERE UPPER(TRIM(CONCAT(COALESCE(Nombre, ''), ' ', COALESCE(Apellido, '')))) = ?
             LIMIT 1",
            [$nombreLider]
        );

        if (!empty($rowsExactos)) {
            return (int)($rowsExactos[0]['Id_Persona'] ?? 0);
        }

        $rowsLike = $this->personaModel->query(
            "SELECT Id_Persona
             FROM persona
             WHERE UPPER(TRIM(CONCAT(COALESCE(Nombre, ''), ' ', COALESCE(Apellido, '')))) LIKE ?
             ORDER BY Id_Persona DESC
             LIMIT 1",
            ['%' . $nombreLider . '%']
        );

        return !empty($rowsLike) ? (int)($rowsLike[0]['Id_Persona'] ?? 0) : 0;
    }

    private function buscarPersonaPorDocumentoOEmail($documento = '', $email = '') {
        $documento = $this->normalizarDocumento($documento);
        $email = $this->normalizarEmail($email);

        if ($documento === '' && $email === '') {
            return null;
        }

        $sql = "SELECT
                    p.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Id_Rol,
                    p.Genero,
                    p.Edad,
                    p.Telefono,
                    p.Numero_Documento,
                    " . ($this->soportaEmail ? "p.Email," : "'' AS Email,") . "
                    p.Id_Ministerio,
                    m.Nombre_Ministerio,
                    p.Id_Lider,
                    CONCAT(COALESCE(lid.Nombre, ''), ' ', COALESCE(lid.Apellido, '')) AS Nombre_Lider
                FROM persona p
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                LEFT JOIN persona lid ON p.Id_Lider = lid.Id_Persona";

        $where = [];
        $params = [];

        if ($documento !== '') {
            $where[] = "REPLACE(REPLACE(REPLACE(UPPER(TRIM(COALESCE(p.Numero_Documento, ''))), ' ', ''), '.', ''), '-', '') = ?";
            $params[] = $documento;
        }

        if ($email !== '' && $this->soportaEmail) {
            $where[] = "LOWER(TRIM(COALESCE(p.Email, ''))) = ?";
            $params[] = $email;
        }

        if (empty($where)) {
            return null;
        }

        $rows = $this->personaModel->query(
            $sql . " WHERE (" . implode(' OR ', $where) . ") ORDER BY p.Id_Persona DESC LIMIT 1",
            $params
        );

        return $rows[0] ?? null;
    }

    private function crearPersonaNueva($nombreCompleto, $telefono, $cedula, $tipoDocumento, $idMinisterio, $idLider = 0, $genero = '', $nombreLider = '', $email = '', $direccion = '', $fechaNacimiento = '') {
        $partesNombre = $this->separarNombreApellido($nombreCompleto);
        $genero = trim((string)$genero);
        $nombreLider = trim((string)$nombreLider);
        $email = $this->normalizarEmail($email);
        $direccion = trim((string)$direccion);
        $fechaNacimiento = trim((string)$fechaNacimiento);
        $tipoDocumento = $this->normalizarTipoDocumento($tipoDocumento);

        $data = [
            'Nombre' => $partesNombre['nombre'],
            'Apellido' => $partesNombre['apellido'],
            'Tipo_Documento' => $tipoDocumento !== '' ? $tipoDocumento : 'Cedula de Ciudadania',
            'Numero_Documento' => $cedula !== '' ? $cedula : null,
            'Genero' => $genero !== '' ? $genero : null,
            'Telefono' => $telefono !== '' ? $telefono : null,
            'Id_Ministerio' => $idMinisterio > 0 ? (int)$idMinisterio : null,
            'Id_Lider' => $idLider > 0 ? $idLider : null,
            'Invitado_Por' => $nombreLider !== '' ? $nombreLider : 'Escuelas Formacion',
            'Tipo_Reunion' => 'Domingo',
            'Fecha_Registro' => date('Y-m-d H:i:s'),
            'Fecha_Registro_Unix' => time(),
            'Estado_Cuenta' => 'Activo'
        ];

        if ($this->soportaEmail) {
            $data['Email'] = $email !== '' ? $email : null;
        }

        if ($this->soportaDireccion) {
            $data['Direccion'] = $direccion !== '' ? $direccion : null;
        }

        if ($this->soportaFechaNacimiento) {
            $data['Fecha_Nacimiento'] = $fechaNacimiento !== '' ? $fechaNacimiento : null;
        }

        $idRolAsistente = $this->obtenerIdRolAsistenteDefault();
        if ($idRolAsistente > 0) {
            $data['Id_Rol'] = $idRolAsistente;
        }

        if ($this->soportaCreadoPor) {
            $data['Creado_Por'] = null;
        }

        if ($this->soportaCanalCreacion) {
            $data['Canal_Creacion'] = 'Escuelas Formacion (Formulario publico)';
        }

        if ($this->soportaProceso) {
            $data['Proceso'] = 'Ganar';
        }

        if ($this->soportaObservacionGanadoEn) {
            $data['Observacion_Ganado_En'] = 'Escuelas Formacion - Formulario publico';
        }

        if ($this->soportaOrigenGanar) {
            $data['Origen_Ganar'] = 'Domingo';
        }

        if ($this->soportaEsAntiguo) {
            $data['Es_Antiguo'] = 0;
        }

        return (int)$this->personaModel->create($data);
    }

    public function index(string $programaActual = '') {
        $modoAbono = (string)($_GET['abono'] ?? '') === '1';
        $usuarioInternoLogueado = class_exists('AuthController') && AuthController::estaAutenticado();

        if ($usuarioInternoLogueado && class_exists('AuthController') && AuthController::puedeRecibirPagosEscuelasFormacion()) {
            // Si ya existe sesión interna autorizada, no pedir desbloqueo adicional para abonos.
            $this->asegurarSesionAbono();
            $nombreAuth = trim((string)($_SESSION['usuario_nombre'] ?? 'USUARIO AUTORIZADO'));
            if ($nombreAuth === '') {
                $nombreAuth = 'USUARIO AUTORIZADO';
            }

            $_SESSION['escuelas_formacion_abono_auth'] = [
                'nombre'  => $this->normalizarTextoMayusculas($nombreAuth),
                'usuario' => $nombreAuth,
                'at'      => time(),
                'expira'  => time() + (8 * 60 * 60),
                'via_credenciales' => false,
            ];

            $authAbono = ['autorizado' => true, 'nombre' => $this->normalizarTextoMayusculas($nombreAuth)];
        } else {
            // Formulario estándar (con o sin ?abono=1): no borrar un desbloqueo válido;
            // así el usuario puede llenar datos y seguir con abonos sin recargar credenciales.
            $authAbono = $this->obtenerAutorizacionAbonoSesion();
            if (empty($authAbono['autorizado'])) {
                $authAbono = ['autorizado' => false, 'nombre' => ''];
            }
        }

        if ($programaActual === '') {
            $programaActual = trim((string)($_GET['programa'] ?? ''));
            if (!in_array($programaActual, ['universidad_vida', 'capacitacion_destino'], true)) {
                $programaActual = 'universidad_vida';
            }
        }

        $paginaTitulo = 'Inscripción pública';
        if ($programaActual === 'universidad_vida') {
            $paginaTitulo = 'Inscripción pública - Universidad de la Vida';
        } elseif ($programaActual === 'capacitacion_destino') {
            $paginaTitulo = 'Inscripción pública - Capacitación Destino';
        }

        $prefillInicial = null;
        if ($modoAbono) {
            $idPersonaPrefillParam = (int)($_GET['id_persona'] ?? 0);
            $idInscripcionPrefillParam = (int)($_GET['id_inscripcion'] ?? 0);
            $cedulaPrefill = $this->normalizarDocumento($_GET['cedula'] ?? '');
            $telefonoPrefill = $this->normalizarTelefono($_GET['telefono'] ?? '');
            $nombrePrefill = trim((string)($_GET['nombre'] ?? ''));

            if ($idPersonaPrefillParam > 0 || $idInscripcionPrefillParam > 0 || $cedulaPrefill !== '' || $telefonoPrefill !== '' || $nombrePrefill !== '') {
                $personaPrefill = null;

                $inscripcionDirecta = [];
                if ($idInscripcionPrefillParam > 0) {
                    $inscripcionDirecta = $this->inscripcionModel->getByIdInscripcion($idInscripcionPrefillParam);
                }

                if ($idPersonaPrefillParam > 0) {
                    $personaBase = $this->personaModel->getById($idPersonaPrefillParam);
                    if (!empty($personaBase)) {
                        $docBase = (string)($personaBase['Numero_Documento'] ?? '');
                        $telBase = (string)($personaBase['Telefono'] ?? '');
                        $nomBase = trim((string)($personaBase['Nombre'] ?? '') . ' ' . (string)($personaBase['Apellido'] ?? ''));
                        $personaPrefill = $this->personaModel->buscarParaInscripcionEscuela($docBase, $telBase, $nomBase);
                        if (empty($personaPrefill)) {
                            $personaPrefill = $personaBase;
                        }
                    }
                }

                if (empty($personaPrefill) && $idInscripcionPrefillParam > 0 && !empty($inscripcionDirecta)) {
                    $personaPrefill = [
                        'Id_Persona' => (int)($inscripcionDirecta['Id_Persona'] ?? 0),
                        'Nombre' => trim((string)($inscripcionDirecta['Nombre'] ?? '')),
                        'Apellido' => '',
                        'Genero' => (string)($inscripcionDirecta['Genero'] ?? ''),
                        'Edad' => (int)($inscripcionDirecta['Edad'] ?? 0),
                        'Fecha_Nacimiento' => (string)($inscripcionDirecta['Fecha_Nacimiento'] ?? ''),
                        'Direccion' => '',
                        'Telefono' => (string)($inscripcionDirecta['Telefono'] ?? ''),
                        'Numero_Documento' => (string)($inscripcionDirecta['Cedula'] ?? ''),
                        'Id_Ministerio' => (int)($inscripcionDirecta['Id_Ministerio'] ?? 0),
                        'Nombre_Ministerio' => (string)($inscripcionDirecta['Nombre_Ministerio'] ?? ''),
                        'Nombre_Lider' => (string)($inscripcionDirecta['Lider'] ?? ''),
                    ];
                }

                if (!empty($personaPrefill) && !empty($inscripcionDirecta)) {
                    $personaPrefill = array_merge($inscripcionDirecta, $personaPrefill);
                    if (empty(trim((string)($personaPrefill['Nombre'] ?? '')))) {
                        $personaPrefill['Nombre'] = trim((string)($inscripcionDirecta['Nombre'] ?? ''));
                    }
                    if (empty(trim((string)($personaPrefill['Apellido'] ?? '')))) {
                        $personaPrefill['Apellido'] = '';
                    }
                    if (empty(trim((string)($personaPrefill['Genero'] ?? '')))) {
                        $personaPrefill['Genero'] = (string)($inscripcionDirecta['Genero'] ?? '');
                    }
                    if (empty($personaPrefill['Edad'] ?? 0) && !empty($inscripcionDirecta['Edad'])) {
                        $personaPrefill['Edad'] = (int)($inscripcionDirecta['Edad'] ?? 0);
                    }
                    if (empty(trim((string)($personaPrefill['Telefono'] ?? '')))) {
                        $personaPrefill['Telefono'] = (string)($inscripcionDirecta['Telefono'] ?? '');
                    }
                    if (empty(trim((string)($personaPrefill['Numero_Documento'] ?? '')))) {
                        $personaPrefill['Numero_Documento'] = (string)($inscripcionDirecta['Cedula'] ?? '');
                    }
                    if (empty(trim((string)($personaPrefill['Id_Ministerio'] ?? '')))) {
                        $personaPrefill['Id_Ministerio'] = (int)($inscripcionDirecta['Id_Ministerio'] ?? 0);
                    }
                    if (empty(trim((string)($personaPrefill['Nombre_Ministerio'] ?? '')))) {
                        $personaPrefill['Nombre_Ministerio'] = (string)($inscripcionDirecta['Nombre_Ministerio'] ?? '');
                    }
                    if (empty(trim((string)($personaPrefill['Nombre_Lider'] ?? '')))) {
                        $personaPrefill['Nombre_Lider'] = (string)($inscripcionDirecta['Lider'] ?? '');
                    }
                }

                if (empty($personaPrefill)) {
                    $personaPrefill = $this->personaModel->buscarParaInscripcionEscuela($cedulaPrefill, $telefonoPrefill, $nombrePrefill);
                }

                if (!empty($personaPrefill) || !empty($inscripcionDirecta)) {
                    if (empty($personaPrefill)) {
                        $personaPrefill = [];
                    }
                    $programaLabels = [
                        'universidad_vida' => 'Universidad de la Vida',
                        'encuentro' => 'Encuentro',
                        'bautismo' => 'Bautismo',
                        'capacitacion_destino' => 'Capacitación Destino',
                        'capacitacion_destino_nivel_1' => 'Capacitación Destino - Nivel 1',
                        'capacitacion_destino_nivel_2' => 'Capacitación Destino - Nivel 2',
                        'capacitacion_destino_nivel_3' => 'Capacitación Destino - Nivel 3',
                    ];

                    $edadPersona = 0;
                    $direccionPersona = '';
                    $fechaNacimientoPersona = '';
                    $idPersonaPrefill = (int)($personaPrefill['Id_Persona'] ?? 0);
                    if ($idPersonaPrefill > 0) {
                        $personaCompletaPrefill = $this->personaModel->getById($idPersonaPrefill);
                        if (!empty($personaCompletaPrefill)) {
                            $edadRaw = isset($personaCompletaPrefill['Edad']) ? (int)$personaCompletaPrefill['Edad'] : 0;
                            if ($edadRaw > 0) {
                                $edadPersona = $edadRaw;
                            } else {
                                $fechaNacimiento = trim((string)($personaCompletaPrefill['Fecha_Nacimiento'] ?? ''));
                                if ($fechaNacimiento !== '') {
                                    try {
                                        $hoy = new DateTime('today');
                                        $fechaNac = new DateTime($fechaNacimiento);
                                        $edadCalculada = (int)$fechaNac->diff($hoy)->y;
                                        if ($edadCalculada > 0) {
                                            $edadPersona = $edadCalculada;
                                        }
                                    } catch (Exception $e) {
                                        // Ignorar fecha inválida
                                    }
                                }
                            }

                            if ($this->soportaDireccion) {
                                $direccionPersona = trim((string)($personaCompletaPrefill['Direccion'] ?? ''));
                            }
                            if ($this->soportaFechaNacimiento) {
                                $fechaNacimientoPersona = trim((string)($personaCompletaPrefill['Fecha_Nacimiento'] ?? ''));
                            }
                        }
                    }

                    if ($idInscripcionPrefillParam > 0) {
                        $inscripcionUnica = $this->inscripcionModel->getByIdInscripcion($idInscripcionPrefillParam);
                        $inscripcionesRawPrefill = !empty($inscripcionUnica) ? [$inscripcionUnica] : [];
                    } else {
                        $inscripcionesRawPrefill = $this->inscripcionModel->buscarInscripcionesPorTelefonoOCedula(
                            '',
                            (string)($personaPrefill['Numero_Documento'] ?? ''),
                            10
                        );
                    }
                    if ($programaActual !== '') {
                        $programasFiltroPrefill = $this->obtenerProgramasPorGrupo($programaActual);
                        $inscripcionesRawPrefill = array_values(array_filter((array)$inscripcionesRawPrefill, static function($item) use ($programasFiltroPrefill) {
                            return in_array((string)($item['Programa'] ?? ''), $programasFiltroPrefill, true);
                        }));
                    }

                    $inscripcionReferencia = !empty($inscripcionesRawPrefill) ? (array)$inscripcionesRawPrefill[0] : [];

                    $inscripcionesPrefill = array_map(static function($item) use ($programaLabels) {
                        $prog = (string)($item['Programa'] ?? '');
                        return [
                            'id_inscripcion' => (int)($item['Id_Inscripcion'] ?? 0),
                            'programa' => $prog,
                            'programa_label' => $programaLabels[$prog] ?? $prog,
                            'asistio_clase' => isset($item['Asistio_Clase']) ? (bool)$item['Asistio_Clase'] : false,
                            'fecha_asistencia' => (string)($item['Fecha_Asistencia_Clase'] ?? ''),
                        ];
                    }, (array)$inscripcionesRawPrefill);

                    $prefillInicial = [
                        'encontrado' => true,
                        'persona' => [
                            'id_persona' => (int)($personaPrefill['Id_Persona'] ?? 0),
                            'id_inscripcion' => (int)($inscripcionReferencia['Id_Inscripcion'] ?? 0),
                            'nombre' => trim((string)($personaPrefill['Nombre'] ?? '') . ' ' . (string)($personaPrefill['Apellido'] ?? '')) !== ''
                                ? trim((string)($personaPrefill['Nombre'] ?? '') . ' ' . (string)($personaPrefill['Apellido'] ?? ''))
                                : trim((string)($inscripcionReferencia['Nombre'] ?? ($_GET['nombre'] ?? ''))),
                            'edad' => $edadPersona > 0 ? $edadPersona : (int)($_GET['edad'] ?? 0),
                            'genero' => (string)($personaPrefill['Genero'] ?? ($inscripcionReferencia['Genero'] ?? ($_GET['genero'] ?? ''))),
                            'fecha_nacimiento' => $fechaNacimientoPersona !== '' ? $fechaNacimientoPersona : (string)($_GET['fecha_nacimiento'] ?? ''),
                            'direccion' => $direccionPersona !== '' ? $direccionPersona : (string)($_GET['direccion'] ?? ''),
                            'telefono' => (string)($personaPrefill['Telefono'] ?? ($inscripcionReferencia['Telefono'] ?? ($_GET['telefono'] ?? ''))),
                            'cedula' => (string)($personaPrefill['Numero_Documento'] ?? ($inscripcionReferencia['Cedula'] ?? ($_GET['cedula'] ?? ''))),
                            'lider' => trim((string)($personaPrefill['Nombre_Lider'] ?? ($_GET['lider'] ?? ''))),
                            'id_lider' => (int)($personaPrefill['Id_Lider'] ?? ($_GET['id_lider'] ?? 0)),
                            'id_ministerio' => (string)($personaPrefill['Id_Ministerio'] ?? ($inscripcionReferencia['Id_Ministerio'] ?? ($_GET['id_ministerio'] ?? ''))),
                            'ministerio' => (string)($personaPrefill['Nombre_Ministerio'] ?? ($inscripcionReferencia['Nombre_Ministerio'] ?? ''))
                        ],
                        'inscripciones' => $inscripcionesPrefill,
                    ];
                }
            }
        }

        $this->view('escuelas_formacion_publico/formulario', [
            'ministerios' => $this->ministerioModel->getAll(),
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo_mensaje' => $_GET['tipo'] ?? null,
            'registro_exitoso' => isset($_GET['exito']) && $_GET['exito'] === '1',
            'referencia_pago' => (string)($_GET['referencia_pago'] ?? ''),
            'abono_auth' => $authAbono,
            'modo_abono' => $modoAbono,
            'prefill_inicial' => $prefillInicial,
            'usuario_interno_logueado' => $usuarioInternoLogueado,
            'puede_recibir_pagos_escuelas' => class_exists('AuthController') && AuthController::puedeRecibirPagosEscuelasFormacion(),
            'programa_actual' => $programaActual,
            'programa_label' => $paginaTitulo,
            'old' => [
                'identificador' => (string)($_GET['identificador'] ?? ''),
                'nombre' => (string)($_GET['nombre'] ?? ''),
                'genero' => (string)($_GET['genero'] ?? ''),
                'edad' => (string)($_GET['edad'] ?? ''),
                'telefono' => (string)($_GET['telefono'] ?? ''),
                'cedula' => (string)($_GET['cedula'] ?? ''),
                'tipo_documento' => (string)($_GET['tipo_documento'] ?? 'Cedula de Ciudadania'),
                'email' => (string)($_GET['email'] ?? ''),
                'direccion' => (string)($_GET['direccion'] ?? ''),
                'fecha_nacimiento' => (string)($_GET['fecha_nacimiento'] ?? ''),
                'lider' => (string)($_GET['lider'] ?? ''),
                'id_lider' => (string)($_GET['id_lider'] ?? ''),
                'id_ministerio' => (string)($_GET['id_ministerio'] ?? ''),
                'programa' => $programaActual !== '' ? $programaActual : (string)($_GET['programa'] ?? ''),
                'programa_nivel' => (string)($_GET['programa_nivel'] ?? 'capacitacion_destino_nivel_1'),
                'metodo_pago' => (string)($_GET['metodo_pago'] ?? ''),
                'tipo_pago' => (string)($_GET['tipo_pago'] ?? ''),
                'valor_pago' => (string)($_GET['valor_pago'] ?? ''),
                'recibido_por' => (string)($_GET['recibido_por'] ?? ($authAbono['nombre'] ?? '')),
                'entrego_libro' => (string)($_GET['entrego_libro'] ?? '0')
            ]
        ]);
    }

    public function abonosUniversidadVida() {
        if (!class_exists('AuthController') || !AuthController::estaAutenticado()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/login');
            exit;
        }
        if (!AuthController::puedeRecibirPagosEscuelasFormacion()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->asegurarSesionAbono();
        $nombreAuth = trim((string)($_SESSION['usuario_nombre'] ?? ''));
        if ($nombreAuth === '') {
            $nombreAuth = 'USUARIO AUTORIZADO';
        }

        $_SESSION['escuelas_formacion_abono_auth'] = [
            'nombre'  => $this->normalizarTextoMayusculas($nombreAuth),
            'usuario' => $nombreAuth,
            'at'      => time(),
            'expira'  => time() + (8 * 60 * 60),
            'via_credenciales' => false,
        ];

        $cedula = $this->normalizarDocumento($_GET['cedula'] ?? '');
        $telefono = $this->normalizarTelefono($_GET['telefono'] ?? '');
        $inscripciones = [];
        $inscripcionActiva = null;

        if ($cedula !== '' || $telefono !== '') {
            $inscripciones = $this->inscripcionModel->buscarInscripcionesPorTelefonoOCedula($telefono, $cedula, 10);
            if (!empty($inscripciones)) {
                $inscripcionActiva = $inscripciones[0];
            }
        }

        $this->view('escuelas_formacion_publico/abonos', [
            'programa_actual' => 'universidad_vida',
            'programa_label' => 'Abonos - Universidad de la Vida',
            'abono_auth' => [
                'autorizado' => true,
                'nombre' => $this->normalizarTextoMayusculas($nombreAuth),
            ],
            'inscripciones' => $inscripciones,
            'inscripcion_activa' => $inscripcionActiva,
            'old' => [
                'cedula' => $cedula,
                'telefono' => $telefono,
            ],
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'tipo_mensaje' => (string)($_GET['tipo'] ?? ''),
        ]);
    }

    public function guardarAbonosUniversidadVida() {
        if (!class_exists('AuthController') || !AuthController::estaAutenticado()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/login');
            exit;
        }
        if (!AuthController::puedeRecibirPagosEscuelasFormacion()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/abonos/universidad-vida');
            exit;
        }

        $idInscripcion = (int)($_POST['id_inscripcion_asistencia'] ?? 0);
        $metodoPago = trim((string)($_POST['metodo_pago'] ?? '')) !== '' ? 'efectivo' : '';
        $tipoPagoRecibido = trim((string)($_POST['tipo_pago'] ?? ''));
        $tipoPago = $this->normalizarTipoPago($tipoPagoRecibido);
        
        // Si tipo_pago vino vacío, rechazar
        if ($metodoPago && !$tipoPagoRecibido) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Debes seleccionar el tipo de pago (Abono o Pago Total).'
            ]);
            exit;
        }
        
        $valorPago = trim((string)($_POST['valor_pago'] ?? '')) !== '' ? $this->normalizarValorPago($_POST['valor_pago'] ?? '') : 0;
        $entregoLibro = $this->normalizarEntregoLibro($_POST['entrego_libro'] ?? '0');

        $authAbono = $this->obtenerAutorizacionAbonoSesion();
        $recibidoPor = $this->normalizarTextoMayusculas((string)($authAbono['nombre'] ?? ''));

        if ($idInscripcion <= 0) {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/abonos/universidad-vida&tipo=error&mensaje=' . urlencode('Debes seleccionar una inscripción válida.'));
            exit;
        }

        $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcion);
        if (empty($inscripcion)) {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/abonos/universidad-vida&tipo=error&mensaje=' . urlencode('La inscripción seleccionada no existe.'));
            exit;
        }

        if ($metodoPago === '' || $valorPago <= 0 || $recibidoPor === '') {
            $mensaje = $metodoPago === ''
                ? 'Debes seleccionar método de pago.'
                : ($valorPago <= 0 ? 'El valor del pago debe ser mayor a 0.' : 'No hay usuario autorizado para recibir el pago.');

            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/abonos/universidad-vida&cedula=' . urlencode((string)($inscripcion['Cedula'] ?? '')) . '&telefono=' . urlencode((string)($inscripcion['Telefono'] ?? '')) . '&tipo=error&mensaje=' . urlencode($mensaje));
            exit;
        }

        $referenciaPago = $this->generarReferenciaCorta();
        $this->inscripcionModel->actualizarPagoInscripcion($idInscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor, $entregoLibro);
        $this->inscripcionModel->registrarMovimientoPago($inscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor, $entregoLibro);

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['escuelas_ticket'] = [
            'fecha' => date('Y-m-d H:i'),
            'nombre' => (string)($inscripcion['Nombre'] ?? ''),
            'cedula' => (string)($inscripcion['Cedula'] ?? ''),
            'programa' => $this->etiquetaProgramaEscuela((string)($inscripcion['Programa'] ?? 'universidad_vida')),
            'metodo_pago' => $metodoPago,
            'recibido_por' => $recibidoPor,
            'tipo_pago' => $tipoPago,
            'valor_pago' => $valorPago,
            'entrego_libro' => $entregoLibro,
            'referencia_pago' => $referenciaPago,
        ];

        header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/abonos/universidad-vida&cedula=' . urlencode((string)($inscripcion['Cedula'] ?? '')) . '&telefono=' . urlencode((string)($inscripcion['Telefono'] ?? '')) . '&tipo=success&mensaje=' . urlencode('Abono registrado correctamente.') . '&referencia_pago=' . urlencode($referenciaPago));
        exit;
    }

    /**
     * Volver desde pantalla de pagos hacia el consolidado de Programas (misma línea UV / Cap. Destino).
     *
     * @return array{href:string,label:string}
     */
    private function enlaceVolverPagosHaciaProgramas(string $programa): array {
        $programa = trim($programa);
        $tab = (strpos($programa, 'capacitacion_destino') === 0 || $programa === 'capacitacion_destino')
            ? 'capacitacion_destino'
            : 'universidad_vida';

        return [
            'href' => PUBLIC_URL . '?url=programas/consolidar&insc_programa=' . rawurlencode($tab),
            'label' => 'Volver a Programas',
        ];
    }

    public function pagos() {
        if (!$this->usuarioPuedeVerPagos()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $buscar = trim((string)($_GET['buscar'] ?? ''));
        $cedulaDetalle = trim((string)($_GET['cedula'] ?? ''));
        $programa = trim((string)($_GET['programa'] ?? 'universidad_vida'));
        $filtroGenero = trim((string)($_GET['filtro_genero'] ?? ''));
        $filtroMinisterio = trim((string)($_GET['filtro_ministerio'] ?? ''));
        
        if (!in_array($programa, ['universidad_vida', 'capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            $programa = 'universidad_vida';
        }

        if ((string)($_GET['ajax'] ?? '') === '1' || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            $this->responderPagosUnificadosJson($programa, $buscar, $cedulaDetalle, $filtroGenero, $filtroMinisterio);
        }

        $resumen = $this->inscripcionModel->getResumenPagosAbonos($buscar, 400, $programa);
        $detalle = [];
        if ($cedulaDetalle !== '') {
            $detalle = $this->inscripcionModel->getDetallePagosPorCedula($cedulaDetalle, 100, $programa);
        }

        $volverProgramas = $this->enlaceVolverPagosHaciaProgramas($programa);

        $this->view('escuelas_formacion/pagos', [
            'resumen' => $resumen,
            'detalle' => $detalle,
            'buscar' => $buscar,
            'cedula_detalle' => $cedulaDetalle,
            'programa' => $programa,
            'filtro_genero' => $filtroGenero,
            'filtro_ministerio' => $filtroMinisterio,
            'bloquear_selector_programa' => false,
            'url_volver_pagos' => $volverProgramas['href'],
            'etiqueta_volver_pagos' => $volverProgramas['label'],
        ]);
    }

    private function renderPagosPorPrograma($programa, $bloquearSelectorPrograma = true) {
        if (!$this->usuarioPuedeVerPagos()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $programa = trim((string)$programa);
        if (!in_array($programa, ['universidad_vida', 'capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            $programa = 'universidad_vida';
        }

        $buscar = trim((string)($_GET['buscar'] ?? ''));
        $cedulaDetalle = trim((string)($_GET['cedula'] ?? ''));
        $filtroGenero = trim((string)($_GET['filtro_genero'] ?? ''));
        $filtroMinisterio = trim((string)($_GET['filtro_ministerio'] ?? ''));
        
        if ((string)($_GET['ajax'] ?? '') === '1' || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            $this->responderPagosUnificadosJson($programa, $buscar, $cedulaDetalle, $filtroGenero, $filtroMinisterio);
        }

        $resumen = $this->inscripcionModel->getResumenPagosAbonos($buscar, 400, $programa);
        $detalle = [];
        if ($cedulaDetalle !== '') {
            $detalle = $this->inscripcionModel->getDetallePagosPorCedula($cedulaDetalle, 100, $programa);
        }

        $volverProgramas = $this->enlaceVolverPagosHaciaProgramas($programa);

        $this->view('escuelas_formacion/pagos', [
            'resumen' => $resumen,
            'detalle' => $detalle,
            'buscar' => $buscar,
            'cedula_detalle' => $cedulaDetalle,
            'programa' => $programa,
            'filtro_genero' => $filtroGenero,
            'filtro_ministerio' => $filtroMinisterio,
            'bloquear_selector_programa' => (bool)$bloquearSelectorPrograma,
            'url_volver_pagos' => $volverProgramas['href'],
            'etiqueta_volver_pagos' => $volverProgramas['label'],
        ]);
    }

    public function pagosConsolidar() {
        $this->renderPagosPorPrograma('universidad_vida', false);
    }

    public function pagosEnviar() {
        $this->renderPagosPorPrograma('capacitacion_destino', false);
    }

    public function asistenciaPublica() {
        // La asistencia se registra desde el formulario principal del programa.
        header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/registro-publico/universidad-vida');
        exit;
    }

    private function normalizarProgramaPagosEscuelas(string $programa): string {
        $programa = trim($programa);
        if ($programa === 'capacitacion_destino') {
            return 'capacitacion_destino_nivel_1';
        }

        if (in_array($programa, ['universidad_vida', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            return $programa;
        }

        return 'universidad_vida';
    }

    private function moduloSegunProgramaEscuela(string $programa): string {
        return $programa === 'universidad_vida' ? 'consolidar' : 'discipular';
    }

    private function nivelSegunProgramaCapDestino(string $programa): int {
        if ($programa === 'capacitacion_destino_nivel_1') {
            return 1;
        }
        if ($programa === 'capacitacion_destino_nivel_2') {
            return 2;
        }
        if ($programa === 'capacitacion_destino_nivel_3') {
            return 3;
        }
        return 0;
    }

    private function calcularSemaforoEscuela(string $programa, bool $pagoCompleto, float $asistenciaPct, ?float $notaFinal): string {
        if ($programa === 'universidad_vida') {
            if ($pagoCompleto && $asistenciaPct >= 100) {
                return 'verde';
            }
            if ($pagoCompleto || $asistenciaPct >= 80) {
                return 'amarillo';
            }
            return 'rojo';
        }

        if ($notaFinal !== null && $notaFinal >= 80 && $asistenciaPct >= 80 && $pagoCompleto) {
            return 'verde';
        }
        if (($notaFinal !== null && $notaFinal >= 60) || $asistenciaPct >= 60 || $pagoCompleto) {
            return 'amarillo';
        }

        return 'rojo';
    }

    private function coincideFiltroGeneroEscuela(string $filtroGenero, string $genero): bool {
        $filtroGenero = strtolower(trim($filtroGenero));
        $genero = strtolower(trim($genero));

        if ($filtroGenero === '') {
            return true;
        }

        if ($filtroGenero === 'hombres') {
            return $genero !== '' && (
                strpos($genero, 'hombre') !== false ||
                strpos($genero, 'mascul') !== false ||
                strpos($genero, 'adulto') !== false ||
                in_array($genero, ['m', 'masc', 'male', 'h'], true)
            );
        }

        if ($filtroGenero === 'mujeres') {
            return $genero !== '' && (
                strpos($genero, 'mujer') !== false ||
                strpos($genero, 'femen') !== false ||
                strpos($genero, 'adulta') !== false ||
                in_array($genero, ['f', 'fem', 'female'], true)
            );
        }

        if ($filtroGenero === 'jovenes') {
            return $genero !== '' && strpos($genero, 'joven') !== false;
        }

        return true;
    }

    private function construirDatosPagosEscuelasUnificados(string $programa, string $buscar, string $cedulaDetalle = '', string $filtroGenero = '', string $filtroMinisterio = ''): array {
        $programa = $this->normalizarProgramaPagosEscuelas($programa);
        $buscar = trim($buscar);
        $cedulaDetalle = trim($cedulaDetalle);
        $filtroGenero = trim((string)$filtroGenero);
        $filtroMinisterio = trim((string)$filtroMinisterio);

        $evaluacionModel = null;
        $programaLabel = $this->etiquetaProgramaEscuela($programa);
        $modulo = $this->moduloSegunProgramaEscuela($programa);
        $nivelCapDestino = $this->nivelSegunProgramaCapDestino($programa);

        $resumenPagos = $this->inscripcionModel->getResumenPagosAbonos($buscar, 400, $programa);
        $inscripciones = $this->inscripcionModel->getListado($programa, $buscar, 400);
        $inscripcionesPorClave = [];
        $idsPersonas = [];
        $ministerios = [];

        foreach ((array)$inscripciones as $inscripcion) {
            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $idsPersonas[$idPersona] = $idPersona;
            }

            $cedula = trim((string)($inscripcion['Cedula'] ?? ''));
            $clave = $cedula !== '' ? $cedula : 'SIN-CEDULA-' . max(0, $idPersona);
            if (!isset($inscripcionesPorClave[$clave])) {
                $inscripcionesPorClave[$clave] = $inscripcion;
            }

            // Agregar ministerios únicos
            $nomMinisterio = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
            if ($nomMinisterio !== '' && !isset($ministerios[$nomMinisterio])) {
                $ministerios[$nomMinisterio] = [
                    'id' => (int)($inscripcion['Id_Ministerio'] ?? 0),
                    'nombre' => $nomMinisterio,
                ];
            }
        }

        $asistenciasPorPersona = [];
        $totalClases = 0;
        if (!empty($idsPersonas)) {
            $asistenciasPorPersona = $this->escuelaAsistenciaClaseModel->getAsistenciasPorPrograma(array_values($idsPersonas), $modulo, $programa);
            $fechasClases = $this->escuelaAsistenciaClaseModel->getFechasClases($modulo, $programa, 6);
            $totalClases = count(array_filter((array)$fechasClases, static function($fecha) {
                return trim((string)$fecha) !== '';
            }));
        }

        $evaluacionesPorPersona = [];
        if ($programa !== 'universidad_vida' && $nivelCapDestino > 0) {
            require_once APP . '/Models/DiscipularEvaluacion.php';
            $evaluacionModel = new DiscipularEvaluacion();
            $resumenEvaluaciones = $evaluacionModel->listarResumenPorNivelCapacitacionDestino();
            foreach ((array)$resumenEvaluaciones as $filaEval) {
                if ((int)($filaEval['Nivel'] ?? 0) !== $nivelCapDestino) {
                    continue;
                }

                $idPersonaEval = (int)($filaEval['Id_Persona'] ?? 0);
                if ($idPersonaEval <= 0) {
                    continue;
                }

                if (!isset($evaluacionesPorPersona[$idPersonaEval])) {
                    $evaluacionesPorPersona[$idPersonaEval] = $filaEval;
                    continue;
                }

                $fechaActual = strtotime((string)($filaEval['Fecha_Presentacion'] ?? '')) ?: 0;
                $fechaGuardada = strtotime((string)($evaluacionesPorPersona[$idPersonaEval]['Fecha_Presentacion'] ?? '')) ?: 0;
                if ($fechaActual >= $fechaGuardada) {
                    $evaluacionesPorPersona[$idPersonaEval] = $filaEval;
                }
            }
        }

        $filas = [];
        foreach ((array)$resumenPagos as $filaPago) {
            $cedula = trim((string)($filaPago['Cedula'] ?? ''));
            $clave = $cedula !== '' ? $cedula : 'SIN-CEDULA-' . max(0, (int)preg_replace('/\D+/', '', (string)($filaPago['Cedula_Clave'] ?? '')));
            $inscripcionRef = $inscripcionesPorClave[$clave] ?? null;
            $idPersona = (int)($inscripcionRef['Id_Persona'] ?? 0);
            $nombre = trim((string)($filaPago['Nombre'] ?? ''));
            $telefono = trim((string)($filaPago['Telefono'] ?? ''));
            
            $genero = '';
            $nombreMinisterio = '';
            $idMinisterio = 0;

            if ($inscripcionRef) {
                if ($nombre === '') {
                    $nombre = trim((string)($inscripcionRef['Nombre'] ?? ''));
                }
                if ($telefono === '') {
                    $telefono = trim((string)($inscripcionRef['Telefono'] ?? ''));
                }
                if ($cedula === '') {
                    $cedula = trim((string)($inscripcionRef['Cedula'] ?? ''));
                }
                $genero = trim((string)($inscripcionRef['Genero'] ?? ''));
                $nombreMinisterio = trim((string)($inscripcionRef['Nombre_Ministerio'] ?? ''));
                $idMinisterio = (int)($inscripcionRef['Id_Ministerio'] ?? 0);
            }

            // Aplicar filtro de género
            if (!$this->coincideFiltroGeneroEscuela($filtroGenero, $genero)) {
                continue;
            }

            // Aplicar filtro de ministerio
            if ($filtroMinisterio !== '' && !($nombreMinisterio === $filtroMinisterio || (string)$idMinisterio === $filtroMinisterio)) {
                continue;
            }

            $asistencias = 0;
            $asistenciaPct = 0.0;
            if ($idPersona > 0 && isset($asistenciasPorPersona[$idPersona])) {
                $asistencias = count(array_filter((array)$asistenciasPorPersona[$idPersona], static function($asistio) {
                    return !empty($asistio);
                }));
                $asistenciaPct = $totalClases > 0 ? round(($asistencias / $totalClases) * 100, 1) : 0.0;
            }

            $notaFinal = null;
            $correctas = null;
            $totalPreguntas = null;
            if ($idPersona > 0 && isset($evaluacionesPorPersona[$idPersona])) {
                $filaEval = (array)$evaluacionesPorPersona[$idPersona];
                $notaFinal = (float)($filaEval['Puntaje'] ?? 0);
                $correctas = (int)($filaEval['Correctas'] ?? 0);
                $totalPreguntas = (int)($filaEval['Total_Preguntas'] ?? 0);
            }

            $totalPagadoFila = (float)($filaPago['Total_Pagado'] ?? 0);
            $totalAbonosFila = (float)($filaPago['Total_Abonos'] ?? 0);
            $totalPagoCompletoFila = array_key_exists('Total_Pago_Completo', (array)$filaPago)
                ? (float)($filaPago['Total_Pago_Completo'] ?? 0)
                : max(0, $totalPagadoFila - $totalAbonosFila);

            $pagoCompleto = $totalPagoCompletoFila > 0;
            $semaforo = $this->calcularSemaforoEscuela($programa, $pagoCompleto, $asistenciaPct, $notaFinal);

            $filas[] = [
                'cedula_clave' => (string)($filaPago['Cedula_Clave'] ?? $clave),
                'persona' => $nombre,
                'cedula' => $cedula,
                'telefono' => $telefono,
                'genero' => $genero,
                'ministerio' => $nombreMinisterio,
                'id_ministerio' => $idMinisterio,
                'programa' => $programa,
                'programa_label' => $programaLabel,
                'registros_pago' => (int)($filaPago['Registros_Pago'] ?? 0),
                'total_pagado' => $totalPagadoFila,
                'total_abonos' => $totalAbonosFila,
                'total_pago_completo' => $totalPagoCompletoFila,
                'cantidad_abonos' => (int)($filaPago['Cantidad_Abonos'] ?? 0),
                'ultimo_movimiento' => (string)($filaPago['Ultimo_Movimiento'] ?? ''),
                'asistencias' => $asistencias,
                'total_clases' => $totalClases,
                'asistencia_pct' => $asistenciaPct,
                'nota_final' => $notaFinal,
                'correctas' => $correctas,
                'total_preguntas' => $totalPreguntas,
                'pago_completo' => $pagoCompleto,
                'semaforo' => $semaforo,
            ];
        }

        $detalle = [];
        if ($cedulaDetalle !== '') {
            $detalle = $this->inscripcionModel->getDetallePagosPorCedula($cedulaDetalle, 100, $programa);
        }

        return [
            'programa' => $programa,
            'programa_label' => $programaLabel,
            'resumen' => $filas,
            'detalle' => $detalle,
            'ministerios' => array_values(array_map(static function($m) {
                return [
                    'id' => $m['id'],
                    'nombre' => $m['nombre'],
                ];
            }, $ministerios)),
            'filtro_genero' => $filtroGenero,
            'filtro_ministerio' => $filtroMinisterio,
        ];
    }

    private function responderPagosUnificadosJson(string $programa, string $buscar, string $cedulaDetalle = '', string $filtroGenero = '', string $filtroMinisterio = ''): void {
        $baseBufferLevel = ob_get_level();
        ob_start();

        $previousHandler = set_error_handler(static function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, (int)$severity, (string)$file, (int)$line);
        });

        try {
            $data = $this->construirDatosPagosEscuelasUnificados($programa, $buscar, $cedulaDetalle, $filtroGenero, $filtroMinisterio);
            $strayOutput = trim((string)ob_get_clean());

            restore_error_handler();

            if ($strayOutput !== '') {
                error_log('Salida inesperada en pagos JSON: ' . $strayOutput);
            }

            $this->json([
                'ok' => true,
                'programa' => $data['programa'],
                'programa_label' => $data['programa_label'],
                'resumen' => $data['resumen'],
                'detalle' => $data['detalle'],
                'ministerios' => $data['ministerios'] ?? [],
                'filtro_genero' => $data['filtro_genero'] ?? '',
                'filtro_ministerio' => $data['filtro_ministerio'] ?? '',
            ]);
        } catch (Throwable $e) {
            while (ob_get_level() > $baseBufferLevel) {
                ob_end_clean();
            }

            if ($previousHandler !== null) {
                set_error_handler($previousHandler);
            } else {
                restore_error_handler();
            }

            error_log('Error en pagos unificados (JSON): ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());

            $this->json([
                'ok' => false,
                'mensaje' => 'No se pudieron cargar los datos de pagos en este momento.'
            ], 500);
        }
    }

    public function ticket() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $data = $_SESSION['escuelas_ticket'] ?? null;
        if (empty($data) || !is_array($data)) {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/registro-publico/universidad-vida&mensaje=' . urlencode('No hay ticket disponible para mostrar.') . '&tipo=error');
            exit;
        }

        header('Content-Type: text/html; charset=UTF-8');
        echo $this->renderTicketHtml($data);
        exit;
    }

    public function buscarAsistenciaPublica() {
        $telefono = $this->normalizarTelefono($_GET['telefono'] ?? '');
        $cedula = $this->normalizarDocumento($_GET['cedula'] ?? '');

        if ($telefono === '' && $cedula === '') {
            $this->json([
                'encontrado' => false,
                'mensaje' => 'Ingresa teléfono o cédula para buscar.'
            ]);
        }

        $inscripciones = $this->inscripcionModel->buscarInscripcionesPorTelefonoOCedula($telefono, $cedula, 20);
        if (empty($inscripciones)) {
            $this->json([
                'encontrado' => false,
                'mensaje' => 'No encontramos inscripciones con esos datos.'
            ]);
        }

        $programaLabels = [
            'universidad_vida' => 'Universidad de la Vida',
            'encuentro' => 'Encuentro',
            'bautismo' => 'Bautismo',
            'capacitacion_destino' => 'Capacitación Destino',
            'capacitacion_destino_nivel_1' => 'Capacitación Destino - Nivel 1',
            'capacitacion_destino_nivel_2' => 'Capacitación Destino - Nivel 2',
            'capacitacion_destino_nivel_3' => 'Capacitación Destino - Nivel 3',
        ];

        $programas = array_map(static function($item) use ($programaLabels) {
            $programaRaw = (string)($item['Programa'] ?? '');
            return [
                'id_inscripcion' => (int)($item['Id_Inscripcion'] ?? 0),
                'programa' => $programaRaw,
                'programa_label' => $programaLabels[$programaRaw] ?? $programaRaw,
                'asistio_clase' => array_key_exists('Asistio_Clase', $item) ? $item['Asistio_Clase'] : null,
                'fecha_asistencia_clase' => (string)($item['Fecha_Asistencia_Clase'] ?? ''),
                'fecha_registro' => (string)($item['Fecha_Registro'] ?? ''),
            ];
        }, $inscripciones);

        $primera = $inscripciones[0];
        $idPersonaPrimera = (int)($primera['Id_Persona'] ?? 0);
        $personaReferencia = null;

        if ($idPersonaPrimera > 0) {
            $personaReferencia = $this->personaModel->getById($idPersonaPrimera);
        }

        if (empty($personaReferencia)) {
            $personaReferencia = $this->personaModel->buscarParaInscripcionEscuela($cedula, $telefono, '');
        }

        $nombreReferencia = '';
        if (!empty($personaReferencia)) {
            $nombreReferencia = trim((string)($personaReferencia['Nombre'] ?? '') . ' ' . (string)($personaReferencia['Apellido'] ?? ''));
        }

        $cedulaInscripcion = trim((string)($primera['Cedula'] ?? ''));
        $telefonoInscripcion = trim((string)($primera['Telefono'] ?? ''));
        $cedulaReferencia = trim((string)($personaReferencia['Numero_Documento'] ?? ''));
        $telefonoReferencia = trim((string)($personaReferencia['Telefono'] ?? ''));
        $generoReferencia = trim((string)($personaReferencia['Genero'] ?? ''));
        $ministerioReferencia = trim((string)($personaReferencia['Nombre_Ministerio'] ?? ''));
        $liderReferencia = trim((string)($personaReferencia['Nombre_Lider'] ?? ''));

        $this->json([
            'encontrado' => true,
            'persona' => [
                'nombre' => (string)($primera['Nombre'] ?? '') !== '' ? (string)$primera['Nombre'] : $nombreReferencia,
                'genero' => (string)($primera['Genero'] ?? '') !== '' ? (string)$primera['Genero'] : $generoReferencia,
                'telefono' => $telefonoInscripcion !== '' ? $telefonoInscripcion : $telefonoReferencia,
                'cedula' => $cedulaInscripcion !== '' ? $cedulaInscripcion : $cedulaReferencia,
                'lider' => (string)($primera['Lider'] ?? '') !== '' ? (string)$primera['Lider'] : $liderReferencia,
                'ministerio' => (string)($primera['Nombre_Ministerio'] ?? '') !== '' ? (string)$primera['Nombre_Ministerio'] : $ministerioReferencia,
            ],
            'programas' => $programas,
            'mensaje' => 'Esta persona ya está registrada. Selecciona su inscripción y registra solo el abono.'
        ]);
    }

    public function guardarAsistenciaPublica() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/asistencia-publica');
            exit;
        }

        $idInscripcionRaw = trim((string)($_POST['id_inscripcion'] ?? ''));
        $idInscripcion = ctype_digit($idInscripcionRaw) ? (int)$idInscripcionRaw : 0;
        $telefono = $this->normalizarTelefono($_POST['telefono'] ?? '');
        $cedula = $this->normalizarDocumento($_POST['cedula'] ?? '');

        if ($telefono === '') {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'El telefono es obligatorio.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if ($cedula === '') {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'La cedula es obligatoria.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if (!preg_match('/^\d+$/', $telefono)) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'El telefono solo puede contener numeros.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if (!preg_match('/^\d+$/', $cedula)) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'La cedula solo puede contener numeros.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if (strlen($telefono) < 4) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'El telefono debe tener al menos 4 numeros.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if (strlen($cedula) < 4) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'La cedula debe tener al menos 4 numeros.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if ($idInscripcion <= 0) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'Debes seleccionar una inscripción válida.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcion);
        if (empty($inscripcion)) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'La inscripción seleccionada no existe.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $programaLabel = $this->etiquetaProgramaEscuela((string)($inscripcion['Programa'] ?? ''));

        if ((string)($inscripcion['Asistio_Clase'] ?? '') === '1') {
            $this->marcarAsistenciaAutomaticaDesdeRegistroPublico(
                $idInscripcion,
                (int)($inscripcion['Id_Persona'] ?? 0),
                (string)($inscripcion['Programa'] ?? ''),
                date('Y-m-d')
            );

            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'La asistencia para ' . $programaLabel . ' ya estaba registrada. Se intentó marcar la clase con fecha de hoy en la matriz.',
                'tipo' => 'success',
                'exito' => '1'
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $ok = $this->inscripcionModel->actualizarAsistenciaClase($idInscripcion, true);
        if (!$ok) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/asistencia-publica',
                'mensaje' => 'No se pudo registrar la asistencia. Intenta nuevamente.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $this->marcarAsistenciaAutomaticaDesdeRegistroPublico(
            $idInscripcion,
            (int)($inscripcion['Id_Persona'] ?? 0),
            (string)($inscripcion['Programa'] ?? ''),
            date('Y-m-d')
        );

        $query = http_build_query([
            'url' => 'escuelas_formacion/asistencia-publica',
            'mensaje' => 'Asistencia registrada correctamente en ' . $programaLabel . '. Se marcó la clase con fecha de hoy en la matriz (si existe).',
            'tipo' => 'success',
            'exito' => '1'
        ]);
        header('Location: ' . PUBLIC_URL . '?' . $query);
        exit;
    }

    private function obtenerLiderPorId($idLider) {
        return $this->personaModel->obtenerPersonaLiderValidaEscuela((int)$idLider);
    }

    public function buscarLideres() {
        header('Content-Type: application/json');

        $term = trim((string)($_GET['term'] ?? ''));
        if (strlen($term) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $rows = $this->personaModel->buscarLideresParaRegistroEscuela($term, 60);

        $data = array_map(static function($row) {
            return [
                'id_persona' => (int)($row['Id_Persona'] ?? 0),
                'nombre' => trim((string)($row['Nombre'] ?? '') . ' ' . (string)($row['Apellido'] ?? '')),
                'rol' => (string)($row['Rol'] ?? '')
            ];
        }, $rows);

        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    public function buscarPersona() {
        $cedula = $this->normalizarDocumento($_GET['cedula'] ?? '');
        $telefono = '';
        // Misma regla que formulario(): si no viene programa válido, por defecto Universidad de la Vida.
        // Sin esto, programa vacío devolvía inscripciones de todos los programas y el JS activaba
        // modo "una sola inscripción / abono" al encontrar cualquier registro ajeno al formulario.
        $programaActual = trim((string)($_GET['programa'] ?? ''));
        if (!in_array($programaActual, ['universidad_vida', 'capacitacion_destino'], true)) {
            $programaActual = 'universidad_vida';
        }

        if ($cedula === '') {
            $this->json([
                'encontrado' => false,
                'mensaje' => 'Ingrese la cédula para buscar.'
            ]);
        }

        if (strlen($cedula) < 4) {
            $this->json([
                'encontrado' => false,
                'mensaje' => 'Escribe al menos 4 dígitos del documento para buscar.'
            ]);
        }

        // Buscar primero en inscripciones para soportar casos donde el dato
        // existe en escuela_formacion_inscripcion, pero no resuelve por tabla persona.
        $inscripcionesRawBusqueda = $this->inscripcionModel->buscarInscripcionesPorTelefonoOCedula('', $cedula, 10);
        if ($programaActual !== '') {
            $programasFiltro = $this->obtenerProgramasPorGrupo($programaActual);
            $inscripcionesRawBusqueda = array_values(array_filter((array)$inscripcionesRawBusqueda, static function($item) use ($programasFiltro) {
                return in_array((string)($item['Programa'] ?? ''), $programasFiltro, true);
            }));
        }

        $programaLabels = [
            'universidad_vida' => 'Universidad de la Vida',
            'encuentro' => 'Encuentro',
            'bautismo' => 'Bautismo',
            'capacitacion_destino' => 'Capacitación Destino',
            'capacitacion_destino_nivel_1' => 'Capacitación Destino - Nivel 1',
            'capacitacion_destino_nivel_2' => 'Capacitación Destino - Nivel 2',
            'capacitacion_destino_nivel_3' => 'Capacitación Destino - Nivel 3',
        ];

        $inscripcionesBusqueda = array_map(static function($item) use ($programaLabels) {
            $prog = (string)($item['Programa'] ?? '');
            return [
                'id_inscripcion' => (int)($item['Id_Inscripcion'] ?? 0),
                'programa' => $prog,
                'programa_label' => $programaLabels[$prog] ?? $prog,
                'asistio_clase' => isset($item['Asistio_Clase']) ? (bool)$item['Asistio_Clase'] : false,
                'fecha_asistencia' => (string)($item['Fecha_Asistencia_Clase'] ?? ''),
            ];
        }, (array)$inscripcionesRawBusqueda);

        $persona = $this->personaModel->buscarParaInscripcionEscuela($cedula, '', '');

        if (empty($persona)) {
            if (empty($inscripcionesRawBusqueda)) {
                $this->json([
                    'encontrado' => false,
                    'mensaje' => 'No encontramos la persona. Completa los datos personales para crear el registro.'
                ]);
            }

            $primeraInscripcion = (array)$inscripcionesRawBusqueda[0];
            $idPersonaInscripcion = (int)($primeraInscripcion['Id_Persona'] ?? 0);
            $personaPorId = $idPersonaInscripcion > 0 ? $this->personaModel->getById($idPersonaInscripcion) : null;

            $nombrePersona = trim((string)($primeraInscripcion['Nombre'] ?? ''));
            if ($nombrePersona === '' && !empty($personaPorId)) {
                $nombrePersona = trim((string)($personaPorId['Nombre'] ?? '') . ' ' . (string)($personaPorId['Apellido'] ?? ''));
            }

            $cedulaPersona = trim((string)($primeraInscripcion['Cedula'] ?? ''));
            if ($cedulaPersona === '' && !empty($personaPorId)) {
                $cedulaPersona = trim((string)($personaPorId['Numero_Documento'] ?? ''));
            }

            $telefonoPersona = trim((string)($primeraInscripcion['Telefono'] ?? ''));
            if ($telefonoPersona === '' && !empty($personaPorId)) {
                $telefonoPersona = trim((string)($personaPorId['Telefono'] ?? ''));
            }

            $edadPersona = 0;
            $direccionPersona = '';
            $fechaNacimientoPersona = '';
            if (!empty($personaPorId)) {
                $edadPersona = (int)($personaPorId['Edad'] ?? 0);
                if ($this->soportaDireccion) {
                    $direccionPersona = trim((string)($personaPorId['Direccion'] ?? ''));
                }
                if ($this->soportaFechaNacimiento) {
                    $fechaNacimientoPersona = trim((string)($personaPorId['Fecha_Nacimiento'] ?? ''));
                }
            }

            $nombreLider = '';
            $idLider = 0;
            $idMinisterio = '';
            $nombreMinisterio = '';

            if (!empty($personaPorId)) {
                $idLider = (int)($personaPorId['Id_Lider'] ?? 0);
                $idMinisterio = (string)($personaPorId['Id_Ministerio'] ?? '');

                if ($idLider > 0) {
                    $liderData = $this->personaModel->getById($idLider);
                    if (!empty($liderData)) {
                        $nombreLider = trim((string)($liderData['Nombre'] ?? '') . ' ' . (string)($liderData['Apellido'] ?? ''));
                    }
                }

                if ((int)$idMinisterio > 0) {
                    $ministerioData = $this->ministerioModel->getById((int)$idMinisterio);
                    if (!empty($ministerioData)) {
                        $nombreMinisterio = (string)($ministerioData['Nombre_Ministerio'] ?? '');
                    }
                }
            }

            $this->json([
                'encontrado' => true,
                'persona' => [
                    'id_persona' => $idPersonaInscripcion,
                    'nombre' => $nombrePersona,
                    'edad' => $edadPersona,
                    'genero' => (string)($primeraInscripcion['Genero'] ?? ''),
                    'fecha_nacimiento' => $fechaNacimientoPersona,
                    'direccion' => $direccionPersona,
                    'telefono' => $telefonoPersona,
                    'cedula' => $cedulaPersona,
                    'lider' => $nombreLider,
                    'id_lider' => $idLider,
                    'id_ministerio' => $idMinisterio,
                    'ministerio' => $nombreMinisterio,
                ],
                'inscripciones' => $inscripcionesBusqueda,
                'solo_asistencia' => !empty($inscripcionesBusqueda),
                'requiere_asignacion' => [
                    'lider' => false,
                    'ministerio' => false
                ],
                'mensaje' => 'Encontramos inscripción en Escuelas de Formación. Puedes marcar asistencia y/o registrar abonos.',
                'busqueda' => [
                    'por' => 'inscripcion_cedula'
                ]
            ]);
        }

        $faltaLider = trim((string)($persona['Nombre_Lider'] ?? '')) === '';
        $faltaMinisterio = (int)($persona['Id_Ministerio'] ?? 0) <= 0;

        $edadPersona = 0;
        $direccionPersona = '';
        $fechaNacimientoPersona = '';
        $idPersonaEncontrada = (int)($persona['Id_Persona'] ?? 0);
        if ($idPersonaEncontrada > 0) {
            $personaCompleta = $this->personaModel->getById($idPersonaEncontrada);
            if (!empty($personaCompleta)) {
                $edadRaw = isset($personaCompleta['Edad']) ? (int)$personaCompleta['Edad'] : 0;
                if ($edadRaw > 0) {
                    $edadPersona = $edadRaw;
                } else {
                    $fechaNacimiento = trim((string)($personaCompleta['Fecha_Nacimiento'] ?? ''));
                    if ($fechaNacimiento !== '') {
                        try {
                            $hoy = new DateTime('today');
                            $fechaNac = new DateTime($fechaNacimiento);
                            $edadCalculada = (int)$fechaNac->diff($hoy)->y;
                            if ($edadCalculada > 0) {
                                $edadPersona = $edadCalculada;
                            }
                        } catch (Exception $e) {
                            // Ignorar fecha invalida y continuar sin edad.
                        }
                    }
                }

                if ($this->soportaDireccion) {
                    $direccionPersona = trim((string)($personaCompleta['Direccion'] ?? ''));
                }

                if ($this->soportaFechaNacimiento) {
                    $fechaNacimientoPersona = trim((string)($personaCompleta['Fecha_Nacimiento'] ?? ''));
                }
            }
        }

        // Inscripciones existentes para mostrar opcion de asistencia
        $inscripcionesRaw = $this->inscripcionModel->buscarInscripcionesPorTelefonoOCedula(
            '',
            (string)($persona['Numero_Documento'] ?? ''),
            10
        );
        if ($programaActual !== '') {
            $programasFiltro = $this->obtenerProgramasPorGrupo($programaActual);
            $inscripcionesRaw = array_values(array_filter((array)$inscripcionesRaw, static function($item) use ($programasFiltro) {
                return in_array((string)($item['Programa'] ?? ''), $programasFiltro, true);
            }));
        }

        $inscripciones = array_map(static function($item) use ($programaLabels) {
            $prog = (string)($item['Programa'] ?? '');
            return [
                'id_inscripcion' => (int)($item['Id_Inscripcion'] ?? 0),
                'programa' => $prog,
                'programa_label' => $programaLabels[$prog] ?? $prog,
                'asistio_clase' => isset($item['Asistio_Clase']) ? (bool)$item['Asistio_Clase'] : false,
                'fecha_asistencia' => (string)($item['Fecha_Asistencia_Clase'] ?? ''),
            ];
        }, (array)$inscripcionesRaw);

        $this->json([
            'encontrado' => true,
            'persona' => [
                'id_persona' => (int)($persona['Id_Persona'] ?? 0),
                'nombre' => trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? '')),
                'edad' => $edadPersona,
                'genero' => (string)($persona['Genero'] ?? ''),
                'fecha_nacimiento' => $fechaNacimientoPersona,
                'direccion' => $direccionPersona,
                'telefono' => (string)($persona['Telefono'] ?? ''),
                'cedula' => (string)($persona['Numero_Documento'] ?? ''),
                'lider' => trim((string)($persona['Nombre_Lider'] ?? '')),
                'id_lider' => (int)($persona['Id_Lider'] ?? 0),
                'id_ministerio' => (string)($persona['Id_Ministerio'] ?? ''),
                'ministerio' => (string)($persona['Nombre_Ministerio'] ?? '')
            ],
            'inscripciones' => $inscripciones,
            'solo_asistencia' => !empty($inscripciones),
            'requiere_asignacion' => [
                'lider' => $faltaLider,
                'ministerio' => $faltaMinisterio
            ],
            'mensaje' => ($faltaLider || $faltaMinisterio)
                ? 'Persona encontrada, pero no tiene asignado líder y/o ministerio. Debes completarlos antes de guardar.'
                : (!empty($inscripciones)
                    ? 'Persona encontrada. Puedes registrar pagos/abonos desde la inscripción existente.'
                    : 'Hola, ' . trim((string)($persona['Nombre'] ?? '')) . '. Encontramos tus datos y ya puedes seleccionar el programa.'),
            'busqueda' => [
                'por' => 'cedula'
            ]
        ]);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/registro-publico/universidad-vida');
            exit;
        }

        $programaFormulario = trim((string)($_POST['programa'] ?? ''));
        if (!in_array($programaFormulario, ['universidad_vida', 'capacitacion_destino'], true)) {
            $programaFormulario = '';
        }
        $rutaFormulario = $this->obtenerRutaRegistroPublico($programaFormulario);

        $accion = trim((string)($_POST['accion'] ?? 'registro'));

        $cedulaFormulario = $this->normalizarDocumento($_POST['cedula'] ?? '');
        $tipoDocumentoFormulario = $this->normalizarTipoDocumento($_POST['tipo_documento'] ?? '');
        if ($cedulaFormulario === '') {
            $this->redirectRegistroConError(
                'La cedula es obligatoria en este formulario.',
                $programaFormulario,
                [
                    'telefono' => $this->normalizarTelefono($_POST['telefono'] ?? ''),
                    'cedula' => '',
                    'tipo_documento' => $tipoDocumentoFormulario
                ]
            );
        }

        $accionesSinTipoDocumentoEnPost = ['abono', 'asistencia_abono', 'asistencia'];
        if (
            $tipoDocumentoFormulario === ''
            && !in_array($accion, $accionesSinTipoDocumentoEnPost, true)
        ) {
            $this->redirectRegistroConError(
                'Debes seleccionar el tipo de documento.',
                $programaFormulario,
                [
                    'telefono' => $this->normalizarTelefono($_POST['telefono'] ?? ''),
                    'cedula' => $cedulaFormulario,
                    'tipo_documento' => ''
                ]
            );
        }
        $idInscripcionAsistenciaRaw = trim((string)($_POST['id_inscripcion_asistencia'] ?? ''));
        $idInscripcionAsistencia = ctype_digit($idInscripcionAsistenciaRaw) && (int)$idInscripcionAsistenciaRaw > 0 ? (int)$idInscripcionAsistenciaRaw : 0;

        if ($accion === 'asistencia_abono') {
            $marcarAsistencia = !empty($_POST['marcar_asistencia']);
            $metodoPago = trim((string)($_POST['metodo_pago'] ?? '')) !== '' ? 'efectivo' : '';
            $tipoPagoRecibido = trim((string)($_POST['tipo_pago'] ?? ''));
            $tipoPago = $this->normalizarTipoPago($tipoPagoRecibido);
            
            // Si tipo_pago vino vacío cuando hay método de pago, rechazar
            if ($metodoPago && !$tipoPagoRecibido) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Debes seleccionar el tipo de pago (Abono o Pago Total).'
                ]);
                exit;
            }
            
            $valorPago = trim((string)($_POST['valor_pago'] ?? '')) !== '' ? $this->normalizarValorPago($_POST['valor_pago'] ?? '') : 0;
            $entregoLibro = $this->normalizarEntregoLibro($_POST['entrego_libro'] ?? '0');
            $authAbono = $this->obtenerAutorizacionAbonoSesion();
            $recibidoPor = $this->normalizarTextoMayusculas((string)($authAbono['nombre'] ?? ''));

            if ($idInscripcionAsistencia <= 0) {
                $this->redirectRegistroConError('Debes seleccionar una inscripción válida.', $programaFormulario);
            }

            $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcionAsistencia);
            if (empty($inscripcion)) {
                $this->redirectRegistroConError('La inscripción seleccionada no existe.', $programaFormulario);
            }

            $quiereRegistrarPago = $metodoPago !== '' || $valorPago > 0;
            if (!$marcarAsistencia && !$quiereRegistrarPago) {
                $this->redirectRegistroConError('Debes marcar asistencia y/o registrar un pago.', $programaFormulario);
            }

            if ($quiereRegistrarPago && $metodoPago === '') {
                $this->redirectRegistroConError('Debes seleccionar método de pago para registrar el pago.', $programaFormulario);
            }

            if ($quiereRegistrarPago && $valorPago <= 0) {
                $this->redirectRegistroConError('El valor del pago debe ser mayor a 0.', $programaFormulario);
            }

            if ($quiereRegistrarPago && empty($authAbono['autorizado'])) {
                $this->redirectRegistroConError('Debes desbloquear la seccion de abonos con usuario y contrasena.', $programaFormulario);
            }

            if ($quiereRegistrarPago && $recibidoPor === '') {
                $this->redirectRegistroConError('Debes indicar quién recibió el pago.', $programaFormulario);
            }

            $partesMensaje = [];
            $referenciaPago = '';

            if ($marcarAsistencia) {
                $claseMarcada = $this->marcarAsistenciaAutomaticaDesdeRegistroPublico(
                    $idInscripcionAsistencia,
                    (int)($inscripcion['Id_Persona'] ?? 0),
                    (string)($inscripcion['Programa'] ?? ''),
                    date('Y-m-d')
                );

                if (!$claseMarcada) {
                    $query = http_build_query([
                        'url' => $rutaFormulario,
                        'mensaje' => 'No se registró asistencia: no hay clase programada para hoy en ese programa.',
                        'tipo' => 'error'
                    ]);
                    header('Location: ' . PUBLIC_URL . '?' . $query);
                    exit;
                }

                $partesMensaje[] = 'Asistencia registrada.';
            }

            if ($quiereRegistrarPago) {
                $referenciaPago = $this->generarReferenciaCorta();
                $this->inscripcionModel->actualizarPagoInscripcion($idInscripcionAsistencia, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor, $entregoLibro);
                $this->inscripcionModel->registrarMovimientoPago($inscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor, $entregoLibro);

                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $_SESSION['escuelas_ticket'] = [
                    'fecha' => date('Y-m-d H:i'),
                    'nombre' => (string)($inscripcion['Nombre'] ?? ''),
                    'cedula' => (string)($inscripcion['Cedula'] ?? ''),
                    'programa' => $this->etiquetaProgramaEscuela((string)($inscripcion['Programa'] ?? '')),
                    'metodo_pago' => $metodoPago,
                    'recibido_por' => $recibidoPor,
                    'tipo_pago' => $tipoPago,
                    'valor_pago' => $valorPago,
                    'entrego_libro' => $entregoLibro,
                    'referencia_pago' => $referenciaPago
                ];

                $partesMensaje[] = $tipoPago === 'completo' ? 'Pago total registrado.' : 'Abono registrado.';
            }

            $query = http_build_query([
                'url' => $rutaFormulario,
                'mensaje' => implode(' ', $partesMensaje),
                'tipo' => 'success',
                'exito' => '1',
                'referencia_pago' => $referenciaPago
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if ($accion === 'abono') {
            $metodoPago = trim((string)($_POST['metodo_pago'] ?? '')) !== '' ? 'efectivo' : '';
            $tipoPagoRecibido = trim((string)($_POST['tipo_pago'] ?? ''));
            $tipoPago = $this->normalizarTipoPago($tipoPagoRecibido);
            
            // Si tipo_pago vino vacío cuando hay método de pago, rechazar
            if ($metodoPago && !$tipoPagoRecibido) {
                $this->jsonResponse([
                    'success' => false,
                    'mensaje' => 'Debes seleccionar el tipo de pago (Abono o Pago Total).'
                ]);
                exit;
            }
            
            $valorPago = trim((string)($_POST['valor_pago'] ?? '')) !== '' ? $this->normalizarValorPago($_POST['valor_pago'] ?? '') : 0;
            $entregoLibro = $this->normalizarEntregoLibro($_POST['entrego_libro'] ?? '0');
            $authAbono = $this->obtenerAutorizacionAbonoSesion();
            $recibidoPor = $metodoPago !== ''
                ? $this->normalizarTextoMayusculas((string)($authAbono['nombre'] ?? ''))
                : '';

            if (empty($authAbono['autorizado'])) {
                $this->redirectRegistroConError('Debes desbloquear la seccion de abonos con usuario y contrasena.', $programaFormulario);
            }

            if ($idInscripcionAsistencia <= 0) {
                $this->redirectRegistroConError('Debes seleccionar una inscripción para registrar el abono.', $programaFormulario);
            }

            $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcionAsistencia);
            if (empty($inscripcion)) {
                $this->redirectRegistroConError('La inscripción seleccionada no existe.', $programaFormulario);
            }

            if ($metodoPago === '') {
                $this->redirectRegistroConError('Debes seleccionar método de pago para registrar el pago.', $programaFormulario);
            }

            if ($valorPago <= 0) {
                $this->redirectRegistroConError('El valor del pago debe ser mayor a 0.', $programaFormulario);
            }

            if ($recibidoPor === '') {
                $this->redirectRegistroConError('Debes indicar quién recibió el pago.', $programaFormulario);
            }

            $referenciaPago = $this->generarReferenciaCorta();
            $this->inscripcionModel->actualizarPagoInscripcion($idInscripcionAsistencia, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor, $entregoLibro);
            $this->inscripcionModel->registrarMovimientoPago($inscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor, $entregoLibro);

            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['escuelas_ticket'] = [
                'fecha' => date('Y-m-d H:i'),
                'nombre' => (string)($inscripcion['Nombre'] ?? ''),
                'cedula' => (string)($inscripcion['Cedula'] ?? ''),
                'programa' => $this->etiquetaProgramaEscuela((string)($inscripcion['Programa'] ?? '')),
                'metodo_pago' => $metodoPago,
                'recibido_por' => $recibidoPor,
                'tipo_pago' => $tipoPago,
                'valor_pago' => $valorPago,
                'entrego_libro' => $entregoLibro,
                'referencia_pago' => $referenciaPago
            ];

            $query = http_build_query([
                'url' => $rutaFormulario,
                'mensaje' => 'Pago/abono registrado correctamente.',
                'tipo' => 'success',
                'exito' => '1',
                'referencia_pago' => $referenciaPago
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        // Flujo de asistencia: marcar asistencia en inscripcion existente
        if ($accion === 'asistencia') {
            $cedula = $this->normalizarDocumento($_POST['cedula'] ?? '');
            $telefono = $this->normalizarTelefono($_POST['telefono'] ?? '');

            if ($idInscripcionAsistencia <= 0) {
                $this->redirectRegistroConError('Inscripción no válida.', $programaFormulario);
            }

            $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcionAsistencia);
            if (empty($inscripcion)) {
                $this->redirectRegistroConError('La inscripción seleccionada no existe.', $programaFormulario);
            }

            $programaLabel = $this->etiquetaProgramaEscuela((string)($inscripcion['Programa'] ?? ''));

            $claseMarcada = $this->marcarAsistenciaAutomaticaDesdeRegistroPublico(
                $idInscripcionAsistencia,
                (int)($inscripcion['Id_Persona'] ?? 0),
                (string)($inscripcion['Programa'] ?? ''),
                date('Y-m-d')
            );

            if (!$claseMarcada) {
                $query = http_build_query([
                    'url' => $rutaFormulario,
                    'mensaje' => 'No se registró asistencia: no hay clase programada para hoy en ese programa.',
                    'tipo' => 'error'
                ]);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $query = http_build_query([
                'url' => $rutaFormulario,
                'mensaje' => 'Asistencia registrada correctamente en ' . $programaLabel . '.',
                'tipo' => 'success',
                'exito' => '1'
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $nombre = $this->normalizarTextoMayusculas($_POST['nombre'] ?? '');
        $genero = $this->normalizarGeneroBinario($_POST['genero'] ?? '');
        $edad = $this->normalizarEdad($_POST['edad'] ?? '');
        $telefono = $this->normalizarTelefono($_POST['telefono'] ?? '');
        $cedula = $this->normalizarDocumento($_POST['cedula'] ?? '');
        $tipoDocumento = $this->normalizarTipoDocumento($_POST['tipo_documento'] ?? '');
        $email = $this->normalizarEmail($_POST['email'] ?? '');
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        $fechaNacimiento = trim((string)($_POST['fecha_nacimiento'] ?? ''));

        if ($edad <= 0 && $fechaNacimiento !== '') {
            $edad = $this->calcularEdadDesdeFechaNacimiento($fechaNacimiento);
        }
        $lider = $this->normalizarTextoMayusculas($_POST['lider'] ?? '');
        $idLider = ctype_digit(trim((string)($_POST['id_lider'] ?? ''))) ? (int)$_POST['id_lider'] : 0;
        $idMinisterioRaw = trim((string)($_POST['id_ministerio'] ?? ''));
        $idMinisterio = ctype_digit($idMinisterioRaw) ? (int)$idMinisterioRaw : 0;
        $programa = trim((string)($_POST['programa'] ?? ''));
        $programaNivel = trim((string)($_POST['programa_nivel'] ?? ''));
        $metodoPago = trim((string)($_POST['metodo_pago'] ?? '')) !== '' ? 'efectivo' : '';
        $tipoPagoRecibido = trim((string)($_POST['tipo_pago'] ?? ''));
        $tipoPago = $this->normalizarTipoPago($tipoPagoRecibido);
        
        // Si tipo_pago vino vacío cuando hay método de pago, rechazar
        if ($metodoPago && !$tipoPagoRecibido) {
            $this->jsonResponse([
                'success' => false,
                'mensaje' => 'Debes seleccionar el tipo de pago (Abono o Pago Total).'
            ]);
            exit;
        }
        
        $valorPago = ($metodoPago !== '' && trim((string)($_POST['valor_pago'] ?? '')) !== '') ? $this->normalizarValorPago($_POST['valor_pago'] ?? '') : null;
        $entregoLibro = $this->normalizarEntregoLibro($_POST['entrego_libro'] ?? '0');
        $authAbono = $this->obtenerAutorizacionAbonoSesion();
        $recibidoPor = $metodoPago !== ''
            ? $this->normalizarTextoMayusculas((string)($authAbono['nombre'] ?? ''))
            : '';

        $errores = [];

        if ($cedula === '') {
            $errores[] = 'La cedula es obligatoria.';
        }

        if ($tipoDocumento === '') {
            $errores[] = 'Debes seleccionar el tipo de documento.';
        }

        if ($telefono !== '' && !preg_match('/^\d+$/', $telefono)) {
            $errores[] = 'El telefono solo puede contener numeros.';
        }

        if ($telefono !== '' && strlen($telefono) < 4) {
            $errores[] = 'El telefono debe tener al menos 4 numeros.';
        }

        if ($cedula !== '' && !preg_match('/^\d+$/', $cedula)) {
            $errores[] = 'La cedula solo puede contener numeros.';
        }

        if ($cedula !== '' && strlen($cedula) < 4) {
            $errores[] = 'La cedula debe tener al menos 4 numeros.';
        }

        if ($metodoPago !== '' && ($valorPago === null || $valorPago <= 0)) {
            $errores[] = 'El valor del pago debe ser mayor a 0.';
        }

        if ($metodoPago !== '' && empty($authAbono['autorizado'])) {
            $errores[] = 'Debes desbloquear la seccion de abonos con usuario y contrasena.';
        }

        if ($metodoPago !== '' && $recibidoPor === '') {
            $errores[] = 'Debes indicar quién recibió el pago.';
        }

        if (!in_array($programa, ['universidad_vida', 'capacitacion_destino'], true)) {
            $programa = 'universidad_vida';
        }

        if ($programa === 'capacitacion_destino') {
            if (in_array($programaNivel, ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
                $programa = $programaNivel;
            } else {
                $programa = 'capacitacion_destino_nivel_1';
            }
        }

        $generosValidos = ['Hombre', 'Mujer'];

        $persona = $this->personaModel->buscarParaInscripcionEscuela($cedula, $telefono, $nombre);
        $idPersona = (int)($persona['Id_Persona'] ?? 0);
        $esPersonaNueva = $idPersona <= 0;

        if ($esPersonaNueva) {
            if ($nombre === '') {
                $errores[] = 'El nombre es requerido para crear una persona nueva.';
            }
            if ($edad <= 0) {
                $errores[] = 'La edad es requerida para persona nueva.';
            } elseif ($edad < 7 || $edad > 120) {
                $errores[] = 'La edad debe estar entre 7 y 120 anos.';
            }
            if ($telefono === '') {
                $errores[] = 'El telefono es obligatorio para persona nueva.';
            }
            if ($cedula === '') {
                $errores[] = 'La cedula es obligatoria para persona nueva.';
            }
            if (!in_array($genero, $generosValidos, true)) {
                $errores[] = 'Debe seleccionar un género válido para persona nueva.';
            }
        } elseif ($genero !== '' && !in_array($genero, $generosValidos, true)) {
            $errores[] = 'El género no es válido.';
        }

        $liderReal = null;
        if ($idLider > 0) {
            $liderReal = $this->obtenerLiderPorId($idLider);
            if (empty($liderReal)) {
                $errores[] = 'El líder seleccionado no es válido.';
            } else {
                $lider = $this->normalizarTextoMayusculas(trim((string)($liderReal['Nombre'] ?? '') . ' ' . (string)($liderReal['Apellido'] ?? '')));
            }
        }

        $idRolAsistente = $this->obtenerIdRolAsistenteDefault();

        if ($idPersona > 0) {
            $nombreCompletoPersona = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
            if ($nombreCompletoPersona !== '') {
                $nombre = $this->normalizarTextoMayusculas($nombreCompletoPersona);
            }
            if (trim((string)($persona['Telefono'] ?? '')) !== '') {
                $telefono = $this->normalizarTelefono((string)$persona['Telefono']);
            }
            if (trim((string)($persona['Genero'] ?? '')) !== '') {
                $genero = $this->normalizarGeneroBinario((string)$persona['Genero']);
            }
            if (trim((string)($persona['Numero_Documento'] ?? '')) !== '') {
                $cedula = $this->normalizarDocumento((string)$persona['Numero_Documento']);
            }
            if ($this->soportaEmail && trim((string)($persona['Email'] ?? '')) !== '') {
                $email = $this->normalizarEmail((string)$persona['Email']);
            }
            if (trim((string)($persona['Nombre_Lider'] ?? '')) !== '') {
                $lider = $this->normalizarTextoMayusculas((string)$persona['Nombre_Lider']);
            }
            if ((int)($persona['Id_Ministerio'] ?? 0) > 0) {
                $idMinisterio = (int)$persona['Id_Ministerio'];
                $idMinisterioRaw = (string)$idMinisterio;
            }

            $actualizarPersona = [];
            if ((int)($persona['Id_Ministerio'] ?? 0) <= 0 && $idMinisterio > 0) {
                $actualizarPersona['Id_Ministerio'] = $idMinisterio;
            }
            if ((int)($persona['Id_Lider'] ?? 0) <= 0 && $idLider > 0) {
                $actualizarPersona['Id_Lider'] = $idLider;
            }
            if ($telefono !== '' && $this->normalizarTelefono((string)($persona['Telefono'] ?? '')) !== $telefono) {
                $actualizarPersona['Telefono'] = $telefono;
            }
            if (trim((string)($persona['Genero'] ?? '')) === '' && in_array($genero, $generosValidos, true)) {
                $actualizarPersona['Genero'] = $genero;
            }
            if (trim((string)($persona['Numero_Documento'] ?? '')) === '' && $cedula !== '') {
                $actualizarPersona['Tipo_Documento'] = $tipoDocumento;
                $actualizarPersona['Numero_Documento'] = $cedula;
            }
            if ($this->soportaEmail && trim((string)($persona['Email'] ?? '')) === '' && $email !== '') {
                $actualizarPersona['Email'] = $email;
            }
            if ($this->soportaDireccion && $direccion !== '' && trim((string)($persona['Direccion'] ?? '')) !== $direccion) {
                $actualizarPersona['Direccion'] = $direccion;
            }
            if ($this->soportaFechaNacimiento && $fechaNacimiento !== '' && trim((string)($persona['Fecha_Nacimiento'] ?? '')) !== $fechaNacimiento) {
                $actualizarPersona['Fecha_Nacimiento'] = $fechaNacimiento;
            }
            if ((int)($persona['Id_Rol'] ?? 0) <= 0 && $idRolAsistente > 0) {
                $actualizarPersona['Id_Rol'] = $idRolAsistente;
            }

            if (!empty($actualizarPersona)) {
                $this->personaModel->update($idPersona, $actualizarPersona);
            }
        }

        $ministerio = null;
        if ($idMinisterio > 0) {
            $ministerio = $this->ministerioModel->getById($idMinisterio);
            if (empty($ministerio)) {
                $errores[] = 'El ministerio seleccionado no existe.';
            }
        }

        if ($idPersona <= 0 && empty($errores)) {
            $idPersona = $this->crearPersonaNueva($nombre, $telefono, $cedula, $tipoDocumento, $idMinisterio, $idLider, $genero, $lider, $email, $direccion, $fechaNacimiento);
            if ($idPersona <= 0) {
                $errores[] = 'No se pudo crear la persona nueva en la lista de Personas.';
            }
        }

        if (!empty($errores)) {
            $query = http_build_query([
                'url' => $rutaFormulario,
                'mensaje' => implode(' ', $errores),
                'tipo' => 'error',
                'nombre' => $nombre,
                'genero' => $genero,
                'edad' => (string)$edad,
                'telefono' => $telefono,
                'cedula' => $cedula,
                'email' => $email,
                'direccion' => $direccion,
                'fecha_nacimiento' => $fechaNacimiento,
                'lider' => $lider,
                'id_lider' => (string)$idLider,
                'id_ministerio' => (string)$idMinisterio,
                'programa' => $programa,
                'programa_nivel' => $programaNivel,
                'metodo_pago' => $metodoPago,
                'tipo_pago' => $tipoPago,
                'valor_pago' => $valorPago !== null ? (string)$valorPago : '',
                'recibido_por' => $recibidoPor,
                'entrego_libro' => (string)$entregoLibro
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        if ($idPersona > 0) {
            $this->asignarSegundoRolDiscipuloAutomatico($idPersona, $programa);
            $this->asegurarCredencialesDiscipulo($idPersona, $cedula);
        }

        $programasInscritos = $this->inscripcionModel->getProgramasInscritosPersona($idPersona);
        if (!empty($programasInscritos)) {
            $programasEtiquetas = array_values(array_unique(array_map(function($prog) {
                return $this->etiquetaProgramaEscuela((string)$prog);
            }, $programasInscritos)));
            $programasTexto = implode(', ', array_filter($programasEtiquetas, static function($p) {
                return trim((string)$p) !== '';
            }));

            $celulaTexto = trim((string)($persona['Nombre_Celula'] ?? ''));
            if ($celulaTexto === '') {
                $idCelulaPersona = (int)($persona['Id_Celula'] ?? 0);
                if ($idCelulaPersona > 0) {
                    $celulaTexto = 'ID ' . $idCelulaPersona;
                }
            }

            $mensajeDuplicado = 'Esta persona ya está registrada. Cédula: ' . $cedula . '.';
            // Solo mostrar error si es registro nuevo, no si es abono/pago
            if ($accion === 'registro') {
                if ($celulaTexto !== '') {
                    $mensajeDuplicado .= ' Célula: ' . $celulaTexto . '.';
                }
                if ($programasTexto !== '') {
                    $mensajeDuplicado .= ' Programa(s): ' . $programasTexto . '.';
                }
                $this->redirectRegistroConError(
                    $mensajeDuplicado,
                    $programaFormulario,
                    [
                        'telefono' => $telefono,
                        'cedula' => $cedula,
                        'tipo_documento' => $tipoDocumento
                    ]
                );
            }
        }

        $familiaNueva = $this->familiaPrograma($programa);
        $familiasExistentes = array_values(array_unique(array_filter(array_map(function($prog) {
            return $this->familiaPrograma((string)$prog);
        }, $programasInscritos))));

        if ($familiaNueva !== '' && !empty($familiasExistentes) && !in_array($familiaNueva, $familiasExistentes, true)) {
            $this->redirectRegistroConError(
                'Esta persona ya está inscrita en otra línea de formación. No se puede mezclar Universidad de la Vida con Capacitación Destino.',
                $programaFormulario,
                [
                    'nombre' => $nombre,
                    'genero' => $genero,
                    'edad' => (string)$edad,
                    'telefono' => $telefono,
                    'cedula' => $cedula,
                    'email' => $email,
                    'direccion' => $direccion,
                    'fecha_nacimiento' => $fechaNacimiento,
                    'lider' => $lider,
                    'id_lider' => (string)$idLider,
                    'id_ministerio' => (string)$idMinisterio,
                    'programa' => $programa,
                    'programa_nivel' => $programaNivel,
                    'metodo_pago' => $metodoPago
                ]
            );
        }

        $data = [
            'Id_Persona' => $idPersona > 0 ? $idPersona : null,
            'Nombre' => $nombre,
            'Genero' => $genero !== '' ? $genero : null,
            'Edad' => $edad > 0 ? $edad : null,
            'Telefono' => $telefono !== '' ? $telefono : null,
            'Cedula' => $cedula !== '' ? $cedula : null,
            'Lider' => $lider,
            'Id_Ministerio' => (int)$idMinisterio > 0 ? (int)$idMinisterio : null,
            'Nombre_Ministerio' => trim((string)($ministerio['Nombre_Ministerio'] ?? '')) !== '' ? (string)$ministerio['Nombre_Ministerio'] : null,
            'Programa' => $programa,
            'Fuente' => 'Formulario público',
            'Metodo_Pago' => $metodoPago !== '' ? $metodoPago : null,
            'Recibido_Por' => $metodoPago !== '' ? $recibidoPor : null,
            'Tipo_Pago' => $metodoPago !== '' ? $tipoPago : null,
            'Valor_Pago' => $valorPago,
            'Entrego_Libro' => $metodoPago !== '' ? $entregoLibro : null
        ];

        // Generar referencia corta automaticamente si hay pago registrado
        $referenciaPago = $metodoPago !== '' ? $this->generarReferenciaCorta() : '';
        if ($referenciaPago !== '') {
            $data['Referencia_Pago'] = $referenciaPago;
        }

        $idInscripcionExistente = 0;
        if ($idPersona > 0) {
            $idInscripcionExistente = (int)$this->inscripcionModel->getIdInscripcionPersonaPrograma($idPersona, $programa);
        }

        if ($idInscripcionExistente > 0) {
            $asistenciaMarcada = $this->marcarAsistenciaAutomaticaDesdeRegistroPublico($idInscripcionExistente, $idPersona, $programa);

            $mensaje = 'Esta persona ya estaba inscrita en ese programa.';
            if ($asistenciaMarcada) {
                $mensaje .= ' Se marcó la asistencia automáticamente.';
            } else {
                $mensaje .= ' No se marcó asistencia automática porque no hay clase configurada para ese día.';
            }

            $query = http_build_query([
                'url' => $rutaFormulario,
                'mensaje' => $mensaje,
                'tipo' => 'success',
                'exito' => '1',
                'referencia_pago' => $referenciaPago
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        try {
            $idInscripcionCreada = (int)$this->inscripcionModel->create($data);

            // Fallback defensivo: asegurar que la inscripción realmente exista para la persona/programa.
            if ($idInscripcionCreada <= 0 && $idPersona > 0) {
                $idInscripcionCreada = (int)$this->inscripcionModel->getIdInscripcionPersonaPrograma($idPersona, $programa);
            }

            if ($idInscripcionCreada <= 0 && $idPersona > 0) {
                $this->inscripcionModel->crearDesdePersonaSiNoExiste($idPersona, $programa, 'Formulario público');
                $idInscripcionCreada = (int)$this->inscripcionModel->getIdInscripcionPersonaPrograma($idPersona, $programa);
            }

            if ($idInscripcionCreada <= 0) {
                throw new Exception('No se pudo confirmar la inscripción en Escuelas de Formación.');
            }

            $this->encolarMensajeCapacitacionDestino($idPersona, $telefono, $nombre, $programa);

            $asistenciaMarcada = $this->marcarAsistenciaAutomaticaDesdeRegistroPublico($idInscripcionCreada, $idPersona, $programa);

            if (in_array($programa, ['universidad_vida', 'encuentro', 'bautismo'], true)) {
                $this->marcarProgramaConsolidarEnEscalera($idPersona, $programa);
            }
            // Compatibilidad con opción antigua: se toma como Nivel 1.
            if ($programa === 'capacitacion_destino') {
                $this->marcarNivelDiscipularEnEscalera($idPersona, 'capacitacion_destino_nivel_1');
            }
            if (in_array($programa, ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
                $this->marcarNivelDiscipularEnEscalera($idPersona, $programa);
            }

            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['escuelas_ticket'] = [
                'fecha' => date('Y-m-d H:i'),
                'nombre' => $nombre,
                'cedula' => $cedula,
                'programa' => $this->etiquetaProgramaEscuela($programa),
                'metodo_pago' => $metodoPago,
                'recibido_por' => $recibidoPor,
                'tipo_pago' => $metodoPago !== '' ? $tipoPago : '',
                'valor_pago' => $valorPago !== null ? $valorPago : 0,
                'entrego_libro' => $metodoPago !== '' ? $entregoLibro : 0,
                'referencia_pago' => $referenciaPago
            ];

            if ($metodoPago !== '' && $valorPago !== null && $valorPago > 0) {
                $inscripcionCreada = $this->inscripcionModel->getByIdInscripcion($idInscripcionCreada);
                if (!empty($inscripcionCreada)) {
                    $this->inscripcionModel->registrarMovimientoPago($inscripcionCreada, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor, $entregoLibro);
                }
            }

            $mensajeExito = 'Inscripción registrada correctamente.';
            if ($asistenciaMarcada) {
                $mensajeExito .= ' Asistencia marcada automáticamente.';
            } else {
                $mensajeExito .= ' No se marcó asistencia automática porque no hay clase configurada para ese día.';
            }

            $query = http_build_query([
                'url' => $rutaFormulario,
                'mensaje' => $mensajeExito,
                'tipo' => 'success',
                'exito' => '1',
                'referencia_pago' => $referenciaPago
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        } catch (Exception $e) {
            $query = http_build_query([
                'url' => $rutaFormulario,
                'mensaje' => 'Error al guardar la inscripción: ' . $e->getMessage(),
                'tipo' => 'error',
                'nombre' => $nombre,
                'genero' => $genero,
                'edad' => (string)$edad,
                'telefono' => $telefono,
                'cedula' => $cedula,
                'email' => $email,
                'direccion' => $direccion,
                'fecha_nacimiento' => $fechaNacimiento,
                'lider' => $lider,
                'id_lider' => (string)$idLider,
                'id_ministerio' => (string)$idMinisterio,
                'programa' => $programa,
                'programa_nivel' => $programaNivel,
                'metodo_pago' => $metodoPago,
                'tipo_pago' => $tipoPago,
                'valor_pago' => $valorPago !== null ? (string)$valorPago : '',
                'recibido_por' => $recibidoPor,
                'entrego_libro' => (string)$entregoLibro
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }
    }

    public function migrarConsolidadosADiscipular() {
        // Validar permisos
        if (!$this->usuarioModel->tienePermiso('migrar_consolidados')) {
            http_response_code(403);
            echo 'No tienes permisos para realizar esta acción.';
            return;
        }

        $personas = $this->personaModel->getConsolidados(); // Assuming this method fetches consolidated persons

        foreach ($personas as $persona) {
            if (!empty($persona['Id_Lider']) && !empty($persona['Id_Ministerio']) && !empty($persona['Id_Celula'])) {
                // Actualizar rol a 'discipular'
                $this->personaModel->actualizarRol($persona['Id_Persona'], 'discipular');

                // Log de migración
                $this->logger->info("Persona migrada a discipular", [
                    'id_persona' => $persona['Id_Persona'],
                    'id_lider' => $persona['Id_Lider'],
                    'id_ministerio' => $persona['Id_Ministerio'],
                    'id_celula' => $persona['Id_Celula']
                ]);
            }
        }

        echo 'Migración completada.';
    }

    // ─────────────────────────────────────────────────────────────────
    //  LISTADO INSCRITOS CON ASISTENCIA Y PAGOS  (UV)
    // ─────────────────────────────────────────────────────────────────

    public function listadoInscritos() {
        if (!$this->usuarioPuedeVerPagos()) {
            if (!class_exists('AuthController') || !AuthController::estaAutenticado()) {
                header('Location: ' . PUBLIC_URL . '/index.php?url=auth/login');
                exit;
            }
            http_response_code(403);
            echo 'Acceso denegado.';
            return;
        }

        $programa = 'universidad_vida';

        // ── AJAX: datos JSON ──────────────────────────────────────────
        if (!empty($_GET['ajax']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            $buscar = trim((string)($_GET['buscar'] ?? ''));
            $limit  = max(1, min(1000, (int)($_GET['limit'] ?? 600)));

            $personas = $this->inscripcionModel->getInscritosBasicos($programa, $buscar, $limit);

            // Mapa de pagos por persona (clave por cédula o SIN-CEDULA-id)
            $resumenPagos = $this->inscripcionModel->getResumenPagosAbonos('', 1000, $programa);
            $pagosPorClave = [];
            foreach ((array)$resumenPagos as $filaPago) {
                $clavePago = trim((string)($filaPago['Cedula_Clave'] ?? ''));
                if ($clavePago === '') {
                    continue;
                }
                $totalPagado = (float)($filaPago['Total_Pagado'] ?? 0);
                $totalAbonos = (float)($filaPago['Total_Abonos'] ?? 0);
                $totalPagoCompleto = array_key_exists('Total_Pago_Completo', (array)$filaPago)
                    ? (float)($filaPago['Total_Pago_Completo'] ?? 0)
                    : max(0, $totalPagado - $totalAbonos);

                $pagosPorClave[$clavePago] = [
                    'total_pagado' => $totalPagado,
                    'total_abonos' => $totalAbonos,
                    'total_pago_completo' => $totalPagoCompleto,
                    'registros_pago' => (int)($filaPago['Registros_Pago'] ?? 0),
                ];
            }

            $ids = array_filter(array_map(fn($p) => (int)$p['Id_Persona'], $personas));
            $ids = array_values(array_unique($ids));

            // 10 clases: 1-4 pre-encuentro, 5-6 encuentro, 7-10 post-encuentro
            $asistencias = [];
            if (!empty($ids)) {
                $asistencias = $this->escuelaAsistenciaClaseModel
                    ->getAsistenciasPorPrograma($ids, 'consolidar', $programa);
            }

            // Agregar flags de asistencia a cada persona
            foreach ($personas as &$p) {
                $idP = (int)$p['Id_Persona'];
                $map = $asistencias[$idP] ?? [];
                for ($c = 1; $c <= 10; $c++) {
                    $p["clase_{$c}"] = isset($map[$c]) ? (bool)$map[$c] : false;
                }

                $clavePersona = trim((string)($p['Cedula_Clave'] ?? ''));
                if ($clavePersona === '') {
                    $cedulaPersona = trim((string)($p['Cedula'] ?? ''));
                    $clavePersona = $cedulaPersona !== '' ? $cedulaPersona : ('SIN-CEDULA-' . $idP);
                }

                $pago = $pagosPorClave[$clavePersona] ?? [
                    'total_pagado' => 0,
                    'total_abonos' => 0,
                    'total_pago_completo' => 0,
                    'registros_pago' => 0,
                ];

                $p['total_pagado'] = (float)$pago['total_pagado'];
                $p['total_abonos'] = (float)$pago['total_abonos'];
                $p['total_pago_completo'] = (float)$pago['total_pago_completo'];
                $p['registros_pago'] = (int)$pago['registros_pago'];
                $p['tiene_pago_registrado'] = ((float)$pago['total_pagado'] > 0) || ((int)$pago['registros_pago'] > 0);
            }
            unset($p);

            $this->json(['success' => true, 'datos' => $personas, 'total' => count($personas)]);
            return;
        }

        // ── Vista HTML ────────────────────────────────────────────────
        $this->view('escuelas_formacion/listado_inscritos', [
            'programa'   => $programa,
            'titulo'     => 'Inscritos Universidad de la Vida',
            'public_url' => PUBLIC_URL,
        ]);
    }

    /**
     * Guarda/actualiza la asistencia de una clase individual (AJAX).
     * POST: id_persona, modulo, programa, numero_clase, asistio (0/1)
     */
    public function guardarAsistenciaClase() {
        if (!$this->usuarioPuedeVerPagos()) {
            $this->json(['success' => false, 'mensaje' => 'Sin permiso'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'mensaje' => 'Método no permitido'], 405);
            return;
        }

        $idPersona   = (int)($_POST['id_persona']    ?? 0);
        $modulo      = trim((string)($_POST['modulo']      ?? 'consolidar'));
        $programa    = trim((string)($_POST['programa']    ?? 'universidad_vida'));
        $numeroClase = (int)($_POST['numero_clase']  ?? 0);
        $asistio     = (int)($_POST['asistio']       ?? 0) === 1 ? 1 : 0;

        if ($idPersona <= 0 || $numeroClase < 1 || $numeroClase > 10) {
            $this->json(['success' => false, 'mensaje' => 'Parámetros inválidos'], 422);
            return;
        }

        // Sanitize modulo and programa
        $modulosPermitidos   = ['consolidar', 'pre_encuentro', 'encuentro', 'post_encuentro'];
        $programasPermitidos = ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino'];
        if (!in_array($modulo, $modulosPermitidos, true)) {
            $modulo = 'consolidar';
        }
        if (!in_array($programa, $programasPermitidos, true)) {
            $programa = 'universidad_vida';
        }

        $ok = $this->escuelaAsistenciaClaseModel->upsertAsistencia(
            $idPersona, $modulo, $programa, $numeroClase, $asistio
        );

        $this->json(['success' => (bool)$ok]);
    }

    /**
     * Pre-autoriza la sesión de abono usando la sesión de admin ya activa,
     * luego redirige al formulario público con los datos pre-llenados.
     * GET: cedula, nombre, telefono, programa
     */
    public function abonoAdminPreauth() {
        if (!class_exists('AuthController') || !AuthController::puedeRecibirPagosEscuelasFormacion()) {
            header('Location: ' . PUBLIC_URL . '/index.php?url=auth/login');
            exit;
        }

        // Determinar nombre del admin logueado
        $nombreAdmin = 'ADMIN';
        $this->asegurarSesionAbono();
        $sesNombre = trim((string)($_SESSION['usuario_nombre'] ?? ''));
        if ($sesNombre !== '') {
            $nombreAdmin = $this->normalizarTextoMayusculas($sesNombre);
        }

        // Registrar autorización de abono en sesión (válida 8 h)
        $this->asegurarSesionAbono();
        $_SESSION['escuelas_formacion_abono_auth'] = [
            'nombre'  => $nombreAdmin,
            'usuario' => $nombreAdmin,
            'at'      => time(),
            'expira'  => time() + (8 * 60 * 60),
            'via_credenciales' => false,
        ];

        // Construir URL del formulario público con datos pre-llenados
        $cedula   = trim((string)($_GET['cedula']   ?? ''));
        $nombre   = trim((string)($_GET['nombre']   ?? ''));
        $telefono = trim((string)($_GET['telefono'] ?? ''));
        $programa = trim((string)($_GET['programa'] ?? 'universidad_vida'));
        $idPersona = (int)($_GET['id_persona'] ?? 0);
        $idInscripcion = (int)($_GET['id_inscripcion'] ?? 0);
        $genero = trim((string)($_GET['genero'] ?? ''));
        $edad = trim((string)($_GET['edad'] ?? ''));
        $lider = trim((string)($_GET['lider'] ?? ''));
        $idMinisterio = trim((string)($_GET['id_ministerio'] ?? ''));
        $fechaNacimiento = trim((string)($_GET['fecha_nacimiento'] ?? ''));
        $direccion = trim((string)($_GET['direccion'] ?? ''));

        $qs = http_build_query([
            'url'      => 'escuelas_formacion/registro-publico/universidad-vida',
            'id_persona' => $idPersona > 0 ? $idPersona : '',
            'id_inscripcion' => $idInscripcion > 0 ? $idInscripcion : '',
            'cedula'   => $cedula,
            'nombre'   => $nombre,
            'telefono' => $telefono,
            'genero' => $genero,
            'edad' => $edad,
            'lider' => $lider,
            'id_ministerio' => $idMinisterio,
            'fecha_nacimiento' => $fechaNacimiento,
            'direccion' => $direccion,
            'programa' => $programa,
            'abono'    => '1',
        ]);

        header('Location: ' . PUBLIC_URL . '/index.php?' . $qs);
        exit;
    }
}
