<?php
/**
 * Registro público de Escuelas de Formación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Models/UsuarioAcceso.php';
require_once APP . '/Controllers/AuthController.php';

class EscuelaFormacionRegistroController extends BaseController {
    private $personaModel;
    private $ministerioModel;
    private $inscripcionModel;
    private $usuarioAccesoModel;
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

        return AuthController::esAdministrador() || AuthController::tienePermiso('asistencias', 'ver');
    }

    public function __construct() {
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
        $this->inscripcionModel = new EscuelaFormacionInscripcion();
        $this->usuarioAccesoModel = new UsuarioAcceso();

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
        if ($idRol === 6) {
            return true;
        }

        $rolNombre = function_exists('mb_strtolower')
            ? mb_strtolower(trim((string)($user['Nombre_Rol'] ?? '')), 'UTF-8')
            : strtolower(trim((string)($user['Nombre_Rol'] ?? '')));

        return strpos($rolNombre, 'admin') !== false;
    }

    private function usuarioPuedeDesbloquearAbono(array $user): bool {
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
        if (!is_array($auth)) {
            return ['autorizado' => false, 'nombre' => ''];
        }

        $expira = (int)($auth['expira'] ?? 0);
        if ($expira <= time()) {
            unset($_SESSION['escuelas_formacion_abono_auth']);
            return ['autorizado' => false, 'nombre' => ''];
        }

        $nombre = trim((string)($auth['nombre'] ?? ''));
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
            $this->json(['success' => false, 'mensaje' => 'Este usuario no tiene permiso para registrar abonos.'], 403);
            return;
        }

        $nombre = $this->normalizarTextoMayusculas($this->construirNombreUsuarioAutorizado((array)$user));
        $this->asegurarSesionAbono();
        $_SESSION['escuelas_formacion_abono_auth'] = [
            'nombre' => $nombre,
            'usuario' => $usuario,
            'at' => time(),
            'expira' => time() + (8 * 60 * 60),
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

    public function codigos() {
        $urlFormularioUnico = $this->buildAbsolutePublicUrl('escuelas_formacion/registro-publico');

        $this->view('escuelas_formacion_publico/codigos', [
            'url_formulario_unico' => $urlFormularioUnico,
        ]);
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
            error_log("  -> NO ENCONTRADA CLASE para fecha=$fechaAsistencia, modulo=$modulo, programa=$programaNormalizado");
            return false;
        }

        // Marca la clase que tenga configurada la misma fecha del registro de asistencia.
        error_log("  -> Insertando asistencia: idPersona=$idPersona, modulo=$modulo, programa=$programaNormalizado, numeroClase=$numeroClase");
        $resultadoUpsert = $asistenciaModel->upsertAsistencia($idPersona, $modulo, $programaNormalizado, $numeroClase, true);
        error_log("  -> upsertAsistencia resultado: " . ($resultadoUpsert ? 'OK' : 'FALLO'));

        if ($idInscripcion > 0) {
            // Solo marcar asistencia histórica en inscripción cuando sí existe clase programada.
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

    private function crearPersonaNueva($nombreCompleto, $telefono, $cedula, $idMinisterio, $idLider = 0, $genero = '', $nombreLider = '', $email = '', $direccion = '', $fechaNacimiento = '') {
        $partesNombre = $this->separarNombreApellido($nombreCompleto);
        $genero = trim((string)$genero);
        $nombreLider = trim((string)$nombreLider);
        $email = $this->normalizarEmail($email);
        $direccion = trim((string)$direccion);
        $fechaNacimiento = trim((string)$fechaNacimiento);

        $data = [
            'Nombre' => $partesNombre['nombre'],
            'Apellido' => $partesNombre['apellido'],
            'Tipo_Documento' => $cedula !== '' ? 'Cedula de Ciudadania' : null,
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

    public function index() {
        // El formulario publico siempre debe abrir bloqueado; no reutiliza
        // una autorizacion vieja del mismo navegador o de una sesion previa.
        $this->limpiarAutorizacionAbonoSesion();
        $authAbono = ['autorizado' => false, 'nombre' => ''];
        $this->view('escuelas_formacion_publico/formulario', [
            'ministerios' => $this->ministerioModel->getAll(),
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo_mensaje' => $_GET['tipo'] ?? null,
            'registro_exitoso' => isset($_GET['exito']) && $_GET['exito'] === '1',
            'referencia_pago' => (string)($_GET['referencia_pago'] ?? ''),
            'abono_auth' => $authAbono,
            'old' => [
                'identificador' => (string)($_GET['identificador'] ?? ''),
                'nombre' => (string)($_GET['nombre'] ?? ''),
                'genero' => (string)($_GET['genero'] ?? ''),
                'edad' => (string)($_GET['edad'] ?? ''),
                'telefono' => (string)($_GET['telefono'] ?? ''),
                'cedula' => (string)($_GET['cedula'] ?? ''),
                'email' => (string)($_GET['email'] ?? ''),
                'direccion' => (string)($_GET['direccion'] ?? ''),
                'fecha_nacimiento' => (string)($_GET['fecha_nacimiento'] ?? ''),
                'lider' => (string)($_GET['lider'] ?? ''),
                'id_lider' => (string)($_GET['id_lider'] ?? ''),
                'id_ministerio' => (string)($_GET['id_ministerio'] ?? ''),
                'programa' => (string)($_GET['programa'] ?? ''),
                'metodo_pago' => (string)($_GET['metodo_pago'] ?? ''),
                'tipo_pago' => (string)($_GET['tipo_pago'] ?? ''),
                'valor_pago' => (string)($_GET['valor_pago'] ?? ''),
                'recibido_por' => (string)($_GET['recibido_por'] ?? ($authAbono['nombre'] ?? ''))
            ]
        ]);
    }

    public function pagos() {
        if (!$this->usuarioPuedeVerPagos()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $buscar = trim((string)($_GET['buscar'] ?? ''));
        $cedulaDetalle = trim((string)($_GET['cedula'] ?? ''));
        $programa = trim((string)($_GET['programa'] ?? 'universidad_vida'));
        if (!in_array($programa, ['universidad_vida', 'capacitacion_destino'], true)) {
            $programa = 'universidad_vida';
        }

        $resumen = $this->inscripcionModel->getResumenPagosAbonos($buscar, 400, $programa);
        $detalle = [];
        if ($cedulaDetalle !== '') {
            $detalle = $this->inscripcionModel->getDetallePagosPorCedula($cedulaDetalle, 100, $programa);
        }

        $this->view('escuelas_formacion/pagos', [
            'resumen' => $resumen,
            'detalle' => $detalle,
            'buscar' => $buscar,
            'cedula_detalle' => $cedulaDetalle,
            'programa' => $programa,
            'bloquear_selector_programa' => false,
        ]);
    }

    private function renderPagosPorPrograma($programa, $bloquearSelectorPrograma = true) {
        if (!$this->usuarioPuedeVerPagos()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $programa = trim((string)$programa);
        if (!in_array($programa, ['universidad_vida', 'capacitacion_destino'], true)) {
            $programa = 'universidad_vida';
        }

        $buscar = trim((string)($_GET['buscar'] ?? ''));
        $cedulaDetalle = trim((string)($_GET['cedula'] ?? ''));

        $resumen = $this->inscripcionModel->getResumenPagosAbonos($buscar, 400, $programa);
        $detalle = [];
        if ($cedulaDetalle !== '') {
            $detalle = $this->inscripcionModel->getDetallePagosPorCedula($cedulaDetalle, 100, $programa);
        }

        $this->view('escuelas_formacion/pagos', [
            'resumen' => $resumen,
            'detalle' => $detalle,
            'buscar' => $buscar,
            'cedula_detalle' => $cedulaDetalle,
            'programa' => $programa,
            'bloquear_selector_programa' => (bool)$bloquearSelectorPrograma,
        ]);
    }

    public function pagosConsolidar() {
        $this->renderPagosPorPrograma('universidad_vida', true);
    }

    public function pagosEnviar() {
        $this->renderPagosPorPrograma('capacitacion_destino', true);
    }

    public function asistenciaPublica() {
        // Formulario unificado: la asistencia ahora se registra desde el formulario principal
        header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/registro-publico');
        exit;
    }

    public function ticket() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $data = $_SESSION['escuelas_ticket'] ?? null;
        if (empty($data) || !is_array($data)) {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/registro-publico&mensaje=' . urlencode('No hay ticket disponible para mostrar.') . '&tipo=error');
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
            'mensaje' => 'Datos cargados correctamente. Selecciona el programa y registra asistencia.'
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
        $idLider = (int)$idLider;
        if ($idLider <= 0) {
            return null;
        }

        $rows = $this->personaModel->query(
            "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Id_Rol, p.Estado_Cuenta
             FROM persona p
             WHERE p.Id_Persona = ?
               AND p.Id_Rol IN (3, 6, 8)
               AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
             LIMIT 1",
            [$idLider]
        );

        return $rows[0] ?? null;
    }

    public function buscarLideres() {
        header('Content-Type: application/json');

        $term = trim((string)($_GET['term'] ?? ''));
        if (strlen($term) < 2) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }

        $like = '%' . $term . '%';
        $rows = $this->personaModel->query(
            "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Id_Rol,
                    COALESCE(r.Nombre_Rol, '') AS Rol
             FROM persona p
             LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
             WHERE p.Id_Rol IN (3, 6, 8)
               AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
               AND (p.Nombre LIKE ? OR p.Apellido LIKE ? OR CONCAT(p.Nombre, ' ', p.Apellido) LIKE ?)
             ORDER BY p.Nombre ASC, p.Apellido ASC
             LIMIT 20",
            [$like, $like, $like]
        );

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

        if ($cedula === '') {
            $this->json([
                'encontrado' => false,
                'mensaje' => 'Ingrese la cédula para buscar.'
            ]);
        }

        // Buscar primero en inscripciones para soportar casos donde el dato
        // existe en escuela_formacion_inscripcion, pero no resuelve por tabla persona.
        $inscripcionesRawBusqueda = $this->inscripcionModel->buscarInscripcionesPorTelefonoOCedula('', $cedula, 10);

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
            if (!empty($personaPorId)) {
                $edadPersona = (int)($personaPorId['Edad'] ?? 0);
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
            }
        }

        // Inscripciones existentes para mostrar opcion de asistencia
        $inscripcionesRaw = $this->inscripcionModel->buscarInscripcionesPorTelefonoOCedula(
            '',
            (string)($persona['Numero_Documento'] ?? ''),
            10
        );

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
                    ? 'Esta persona ya está inscrita. Puedes marcar asistencia y/o registrar abonos.'
                    : 'Hola, ' . trim((string)($persona['Nombre'] ?? '')) . '. Encontramos tus datos y ya puedes seleccionar el programa.'),
            'busqueda' => [
                'por' => 'cedula'
            ]
        ]);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/registro-publico');
            exit;
        }

        $cedulaFormulario = $this->normalizarDocumento($_POST['cedula'] ?? '');
        if ($cedulaFormulario === '') {
            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
                'mensaje' => 'La cedula es obligatoria en este formulario.',
                'tipo' => 'error',
                'telefono' => $this->normalizarTelefono($_POST['telefono'] ?? ''),
                'cedula' => ''
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $accion = trim((string)($_POST['accion'] ?? 'registro'));
        $idInscripcionAsistenciaRaw = trim((string)($_POST['id_inscripcion_asistencia'] ?? ''));
        $idInscripcionAsistencia = ctype_digit($idInscripcionAsistenciaRaw) && (int)$idInscripcionAsistenciaRaw > 0 ? (int)$idInscripcionAsistenciaRaw : 0;

        if ($accion === 'asistencia_abono') {
            $marcarAsistencia = !empty($_POST['marcar_asistencia']);
            $metodoPago = trim((string)($_POST['metodo_pago'] ?? '')) !== '' ? 'efectivo' : '';
            $tipoPago = 'abono';
            $valorPago = trim((string)($_POST['valor_pago'] ?? '')) !== '' ? round((float)$_POST['valor_pago'], 2) : 0;
            $authAbono = $this->obtenerAutorizacionAbonoSesion();
            $recibidoPor = $this->normalizarTextoMayusculas((string)($authAbono['nombre'] ?? ''));

            if ($idInscripcionAsistencia <= 0) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes seleccionar una inscripción válida.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcionAsistencia);
            if (empty($inscripcion)) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'La inscripción seleccionada no existe.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $quiereRegistrarPago = $metodoPago !== '' || $valorPago > 0;
            if (!$marcarAsistencia && !$quiereRegistrarPago) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes marcar asistencia y/o registrar un abono.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($quiereRegistrarPago && $metodoPago === '') {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes seleccionar método de pago para registrar abono.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($quiereRegistrarPago && $valorPago <= 0) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'El valor del abono debe ser mayor a 0.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($quiereRegistrarPago && empty($authAbono['autorizado'])) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes desbloquear la seccion de abonos con usuario y contrasena.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($quiereRegistrarPago && $recibidoPor === '') {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes indicar quién recibió el pago.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
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
                        'url' => 'escuelas_formacion/registro-publico',
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
                $this->inscripcionModel->actualizarPagoInscripcion($idInscripcionAsistencia, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor);
                $this->inscripcionModel->registrarMovimientoPago($inscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor);

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
                    'valor_pago' => number_format((float)$valorPago, 0, ',', '.'),
                    'referencia_pago' => $referenciaPago
                ];

                $partesMensaje[] = 'Abono registrado.';
            }

            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
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
            $tipoPago = 'abono';
            $valorPago = trim((string)($_POST['valor_pago'] ?? '')) !== '' ? round((float)$_POST['valor_pago'], 2) : 0;
            $authAbono = $this->obtenerAutorizacionAbonoSesion();
            $recibidoPor = $this->normalizarTextoMayusculas((string)($authAbono['nombre'] ?? ''));

            if (empty($authAbono['autorizado'])) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes desbloquear la seccion de abonos con usuario y contrasena.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($idInscripcionAsistencia <= 0) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes seleccionar una inscripción para registrar el abono.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcionAsistencia);
            if (empty($inscripcion)) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'La inscripción seleccionada no existe.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($metodoPago === '') {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes seleccionar método de pago para registrar abono.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($valorPago <= 0) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'El valor del abono debe ser mayor a 0.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            if ($recibidoPor === '') {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Debes indicar quién recibió el pago.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $referenciaPago = $this->generarReferenciaCorta();
            $this->inscripcionModel->actualizarPagoInscripcion($idInscripcionAsistencia, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor);
            $this->inscripcionModel->registrarMovimientoPago($inscripcion, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor);

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
                'valor_pago' => number_format((float)$valorPago, 0, ',', '.'),
                'referencia_pago' => $referenciaPago
            ];

            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
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
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'Inscripción no válida.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $inscripcion = $this->inscripcionModel->getByIdInscripcion($idInscripcionAsistencia);
            if (empty($inscripcion)) {
                $query = http_build_query(['url' => 'escuelas_formacion/registro-publico', 'mensaje' => 'La inscripción seleccionada no existe.', 'tipo' => 'error']);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
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
                    'url' => 'escuelas_formacion/registro-publico',
                    'mensaje' => 'No se registró asistencia: no hay clase programada para hoy en ese programa.',
                    'tipo' => 'error'
                ]);
                header('Location: ' . PUBLIC_URL . '?' . $query);
                exit;
            }

            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
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
        $tipoPago = 'abono';
        $valorPago = ($metodoPago !== '' && trim((string)($_POST['valor_pago'] ?? '')) !== '') ? round((float)$_POST['valor_pago'], 2) : null;
        $authAbono = $this->obtenerAutorizacionAbonoSesion();
        $recibidoPor = $metodoPago !== ''
            ? $this->normalizarTextoMayusculas((string)($authAbono['nombre'] ?? ''))
            : '';

        $errores = [];

        if ($cedula === '') {
            $errores[] = 'La cedula es obligatoria.';
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
            $errores[] = 'El valor del abono debe ser mayor a 0.';
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
            // Regla de negocio: toda persona nueva desde el formulario entra directo a Universidad de la Vida.
            $programa = 'universidad_vida';
        }

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
                $actualizarPersona['Tipo_Documento'] = 'Cedula de Ciudadania';
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
            $idPersona = $this->crearPersonaNueva($nombre, $telefono, $cedula, $idMinisterio, $idLider, $genero, $lider, $email, $direccion, $fechaNacimiento);
            if ($idPersona <= 0) {
                $errores[] = 'No se pudo crear la persona nueva en la lista de Personas.';
            }
        }

        if (!empty($errores)) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
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
                'id_ministerio' => $idMinisterioRaw,
                'programa' => $programa,
                'programa_nivel' => $programaNivel,
                'metodo_pago' => $metodoPago,
                'tipo_pago' => $tipoPago,
                'valor_pago' => $valorPago !== null ? (string)$valorPago : '',
                'recibido_por' => $recibidoPor
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $programasInscritos = $this->inscripcionModel->getProgramasInscritosPersona($idPersona);
        if (!empty($programasInscritos)) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
                'mensaje' => 'La persona ya está inscrita en formación. No se crea un registro nuevo; solo se permite marcar asistencia y/o registrar abonos.',
                'tipo' => 'error',
                'telefono' => $telefono,
                'cedula' => $cedula
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        $familiaNueva = $this->familiaPrograma($programa);
        $familiasExistentes = array_values(array_unique(array_filter(array_map(function($prog) {
            return $this->familiaPrograma((string)$prog);
        }, $programasInscritos))));

        if ($familiaNueva !== '' && !empty($familiasExistentes) && !in_array($familiaNueva, $familiasExistentes, true)) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
                'mensaje' => 'Esta persona ya está inscrita en otra línea de formación. No se puede mezclar Universidad de la Vida con Capacitación Destino.',
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
                'recibido_por' => $recibidoPor
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
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
            'Valor_Pago' => $valorPago
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
                'url' => 'escuelas_formacion/registro-publico',
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
                'valor_pago' => $valorPago !== null ? number_format((float)$valorPago, 0, ',', '.') : '0',
                'referencia_pago' => $referenciaPago
            ];

            if ($metodoPago !== '' && $valorPago !== null && $valorPago > 0) {
                $inscripcionCreada = $this->inscripcionModel->getByIdInscripcion($idInscripcionCreada);
                if (!empty($inscripcionCreada)) {
                    $this->inscripcionModel->registrarMovimientoPago($inscripcionCreada, $metodoPago, $tipoPago, $valorPago, $referenciaPago, $recibidoPor);
                }
            }

            $mensajeExito = 'Inscripción registrada correctamente.';
            if ($asistenciaMarcada) {
                $mensajeExito .= ' Asistencia marcada automáticamente.';
            } else {
                $mensajeExito .= ' No se marcó asistencia automática porque no hay clase configurada para ese día.';
            }

            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
                'mensaje' => $mensajeExito,
                'tipo' => 'success',
                'exito' => '1',
                'referencia_pago' => $referenciaPago
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        } catch (Exception $e) {
            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
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
                'recibido_por' => $recibidoPor
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }
    }
}
