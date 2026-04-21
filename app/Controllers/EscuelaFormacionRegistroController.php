<?php
/**
 * Registro público de Escuelas de Formación
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';

class EscuelaFormacionRegistroController extends BaseController {
    private $personaModel;
    private $ministerioModel;
    private $inscripcionModel;
    private $soportaProceso = false;
    private $soportaOrigenGanar = false;
    private $soportaEsAntiguo = false;
    private $soportaObservacionGanadoEn = false;
    private $soportaCreadoPor = false;
    private $soportaCanalCreacion = false;
    private $soportaChecklistEscalera = false;
    private $idRolAsistenteCache = null;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
        $this->inscripcionModel = new EscuelaFormacionInscripcion();

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
        $urlRegistro = $this->buildAbsolutePublicUrl('escuelas_formacion/registro-publico');
        $urlAsistencia = $this->buildAbsolutePublicUrl('escuelas_formacion/asistencia-publica');

        $this->view('escuelas_formacion_publico/codigos', [
            'url_registro' => $urlRegistro,
            'url_asistencia' => $urlAsistencia,
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

    private function marcarAsistenciaAutomaticaDesdeRegistroPublico($idInscripcion, $idPersona, $programa, $fechaAsistencia = null) {
        $idInscripcion = (int)$idInscripcion;
        $idPersona = (int)$idPersona;
        $programaNormalizado = $this->normalizarProgramaInscripcion($programa);
        $fechaAsistencia = trim((string)($fechaAsistencia ?? date('Y-m-d')));

        if ($idInscripcion > 0) {
            // Mantener actualizado el indicador histórico de asistencia en inscripción.
            $this->inscripcionModel->actualizarAsistenciaClase($idInscripcion, true);
        }

        $modulo = $this->resolverModuloFormacionPorPrograma($programaNormalizado);
        if ($idPersona <= 0 || $modulo === '' || $programaNormalizado === '') {
            return;
        }

        require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();
        $numeroClase = $asistenciaModel->getNumeroClasePorFecha($modulo, $programaNormalizado, $fechaAsistencia);
        if ($numeroClase <= 0) {
            return;
        }

        // Marca la clase que tenga configurada la misma fecha del registro de asistencia.
        $asistenciaModel->upsertAsistencia($idPersona, $modulo, $programaNormalizado, $numeroClase, true);
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

    private function crearPersonaNueva($nombreCompleto, $telefono, $cedula, $idMinisterio, $idLider = 0, $genero = '') {
        $partesNombre = $this->separarNombreApellido($nombreCompleto);
        $genero = trim((string)$genero);

        $data = [
            'Nombre' => $partesNombre['nombre'],
            'Apellido' => $partesNombre['apellido'],
            'Tipo_Documento' => $cedula !== '' ? 'Cedula de Ciudadania' : null,
            'Numero_Documento' => $cedula !== '' ? $cedula : null,
            'Genero' => $genero !== '' ? $genero : null,
            'Telefono' => $telefono !== '' ? $telefono : null,
            'Id_Ministerio' => $idMinisterio > 0 ? (int)$idMinisterio : null,
            'Id_Lider' => $idLider > 0 ? $idLider : null,
            'Tipo_Reunion' => 'Domingo',
            'Fecha_Registro' => date('Y-m-d H:i:s'),
            'Fecha_Registro_Unix' => time(),
            'Estado_Cuenta' => 'Activo'
        ];

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
        $this->view('escuelas_formacion_publico/formulario', [
            'ministerios' => $this->ministerioModel->getAll(),
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo_mensaje' => $_GET['tipo'] ?? null,
            'registro_exitoso' => isset($_GET['exito']) && $_GET['exito'] === '1',
            'old' => [
                'nombre' => (string)($_GET['nombre'] ?? ''),
                'genero' => (string)($_GET['genero'] ?? ''),
                'edad' => (string)($_GET['edad'] ?? ''),
                'telefono' => (string)($_GET['telefono'] ?? ''),
                'cedula' => (string)($_GET['cedula'] ?? ''),
                'lider' => (string)($_GET['lider'] ?? ''),
                'id_lider' => (string)($_GET['id_lider'] ?? ''),
                'id_ministerio' => (string)($_GET['id_ministerio'] ?? ''),
                'programa' => (string)($_GET['programa'] ?? '')
            ]
        ]);
    }

    public function asistenciaPublica() {
        $this->view('escuelas_formacion_publico/asistencia', [
            'mensaje' => $_GET['mensaje'] ?? null,
            'tipo_mensaje' => $_GET['tipo'] ?? null,
            'registro_exitoso' => isset($_GET['exito']) && $_GET['exito'] === '1',
            'old' => [
                'telefono' => (string)($_GET['telefono'] ?? ''),
                'cedula' => (string)($_GET['cedula'] ?? ''),
            ]
        ]);
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
        $telefono = $this->normalizarTelefono($_GET['telefono'] ?? '');
        $nombre = $this->normalizarTextoMayusculas($_GET['nombre'] ?? '');

        if ($cedula === '' && $telefono === '' && $nombre === '') {
            $this->json([
                'encontrado' => false,
                'mensaje' => 'Ingrese cédula, teléfono o nombre para buscar.'
            ]);
        }

        $persona = $this->personaModel->buscarParaInscripcionEscuela($cedula, $telefono, $nombre);
        if (empty($persona)) {
            $this->json([
                'encontrado' => false,
                'mensaje' => 'No existe coincidencias para esta persona. Puedes registrarla y quedará en Personas nuevas.'
            ]);
        }

        $faltaLider = trim((string)($persona['Nombre_Lider'] ?? '')) === '';
        $faltaMinisterio = (int)($persona['Id_Ministerio'] ?? 0) <= 0;

        $this->json([
            'encontrado' => true,
            'persona' => [
                'id_persona' => (int)($persona['Id_Persona'] ?? 0),
                'nombre' => trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? '')),
                'genero' => (string)($persona['Genero'] ?? ''),
                'telefono' => (string)($persona['Telefono'] ?? ''),
                'cedula' => (string)($persona['Numero_Documento'] ?? ''),
                'lider' => trim((string)($persona['Nombre_Lider'] ?? '')),
                'id_lider' => (int)($persona['Id_Lider'] ?? 0),
                'id_ministerio' => (string)($persona['Id_Ministerio'] ?? ''),
                'ministerio' => (string)($persona['Nombre_Ministerio'] ?? '')
            ],
            'requiere_asignacion' => [
                'lider' => $faltaLider,
                'ministerio' => $faltaMinisterio
            ],
            'mensaje' => ($faltaLider || $faltaMinisterio)
                ? 'Persona encontrada, pero no tiene asignado líder y/o ministerio. Debes completarlos antes de guardar.'
                : 'Persona encontrada. Se completaron los campos automáticamente.',
            'busqueda' => [
                'por' => ($cedula !== '' || $telefono !== '') ? 'cedula_telefono' : 'nombre'
            ]
        ]);
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=escuelas_formacion/registro-publico');
            exit;
        }

        $nombre = $this->normalizarTextoMayusculas($_POST['nombre'] ?? '');
        $genero = $this->normalizarGeneroBinario($_POST['genero'] ?? '');
        $edad = $this->normalizarEdad($_POST['edad'] ?? '');
        $telefono = $this->normalizarTelefono($_POST['telefono'] ?? '');
        $cedula = $this->normalizarDocumento($_POST['cedula'] ?? '');
        $lider = $this->normalizarTextoMayusculas($_POST['lider'] ?? '');
        $idLider = ctype_digit(trim((string)($_POST['id_lider'] ?? ''))) ? (int)$_POST['id_lider'] : 0;
        $idMinisterioRaw = trim((string)($_POST['id_ministerio'] ?? ''));
        $idMinisterio = ctype_digit($idMinisterioRaw) ? (int)$idMinisterioRaw : 0;
        $programa = trim((string)($_POST['programa'] ?? ''));

        $errores = [];

        if ($nombre === '') {
            $errores[] = 'El nombre es requerido.';
        }

        if ($edad <= 0) {
            $errores[] = 'La edad es requerida.';
        } elseif ($edad < 7 || $edad > 120) {
            $errores[] = 'La edad debe estar entre 7 y 120 anos.';
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

        if ($telefono === '') {
            $errores[] = 'El telefono es obligatorio.';
        }

        if ($cedula === '') {
            $errores[] = 'La cedula es obligatoria.';
        }

        if ($idMinisterio <= 0) {
            $errores[] = 'Debe seleccionar un ministerio.';
        }

        if (!in_array($programa, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            $errores[] = 'Debe seleccionar un programa válido.';
        }

        $generosValidos = ['Hombre', 'Mujer'];
        if (!in_array($genero, $generosValidos, true)) {
            $errores[] = 'Debe seleccionar un género válido.';
        }

        $liderReal = $this->obtenerLiderPorId($idLider);
        if (empty($liderReal)) {
            $errores[] = 'Debe seleccionar un líder real de la lista.';
        } else {
            $lider = $this->normalizarTextoMayusculas(trim((string)($liderReal['Nombre'] ?? '') . ' ' . (string)($liderReal['Apellido'] ?? '')));
        }

        $persona = $this->personaModel->buscarParaInscripcionEscuela($cedula, $telefono, $nombre);
        $idPersona = (int)($persona['Id_Persona'] ?? 0);
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
            if (trim((string)($persona['Telefono'] ?? '')) === '' && $telefono !== '') {
                $actualizarPersona['Telefono'] = $telefono;
            }
            if (trim((string)($persona['Genero'] ?? '')) === '' && in_array($genero, $generosValidos, true)) {
                $actualizarPersona['Genero'] = $genero;
            }
            if (trim((string)($persona['Numero_Documento'] ?? '')) === '' && $cedula !== '') {
                $actualizarPersona['Tipo_Documento'] = 'Cedula de Ciudadania';
                $actualizarPersona['Numero_Documento'] = $cedula;
            }
            if ((int)($persona['Id_Rol'] ?? 0) <= 0 && $idRolAsistente > 0) {
                $actualizarPersona['Id_Rol'] = $idRolAsistente;
            }

            if (!empty($actualizarPersona)) {
                $this->personaModel->update($idPersona, $actualizarPersona);
            }
        }

        if ($lider === '') {
            $errores[] = 'Debe registrar el líder.';
        }

        $ministerio = $this->ministerioModel->getById($idMinisterio);
        if (empty($ministerio)) {
            $errores[] = 'El ministerio seleccionado no existe.';
        }

        if ($idPersona <= 0 && empty($errores)) {
            $idPersona = $this->crearPersonaNueva($nombre, $telefono, $cedula, $idMinisterio, $idLider, $genero);
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
                'lider' => $lider,
                'id_lider' => (string)$idLider,
                'id_ministerio' => $idMinisterioRaw,
                'programa' => $programa
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
            'Id_Ministerio' => (int)$idMinisterio,
            'Nombre_Ministerio' => (string)($ministerio['Nombre_Ministerio'] ?? ''),
            'Programa' => $programa,
            'Fuente' => 'Formulario público'
        ];

        $idInscripcionExistente = 0;
        if ($idPersona > 0) {
            $idInscripcionExistente = (int)$this->inscripcionModel->getIdInscripcionPersonaPrograma($idPersona, $programa);
        }

        if ($idInscripcionExistente > 0) {
            $this->marcarAsistenciaAutomaticaDesdeRegistroPublico($idInscripcionExistente, $idPersona, $programa);

            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
                'mensaje' => 'Esta persona ya estaba inscrita en ese programa. Se marcó la asistencia automáticamente.',
                'tipo' => 'success',
                'exito' => '1'
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }

        try {
            $idInscripcionCreada = (int)$this->inscripcionModel->create($data);
            $this->marcarAsistenciaAutomaticaDesdeRegistroPublico($idInscripcionCreada, $idPersona, $programa);

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
            $query = http_build_query([
                'url' => 'escuelas_formacion/registro-publico',
                'mensaje' => 'Inscripción registrada correctamente y asistencia marcada automáticamente.',
                'tipo' => 'success',
                'exito' => '1'
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
                'lider' => $lider,
                'id_lider' => (string)$idLider,
                'id_ministerio' => (string)$idMinisterio,
                'programa' => $programa
            ]);
            header('Location: ' . PUBLIC_URL . '?' . $query);
            exit;
        }
    }
}
