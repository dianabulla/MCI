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
        $this->soportaObservacionGanadoEn = $this->personaModel->tieneColumna('Observacion_Ganado_En');
        $this->soportaCreadoPor = $this->personaModel->tieneColumna('Creado_Por');
        $this->soportaCanalCreacion = $this->personaModel->tieneColumna('Canal_Creacion');
        $this->soportaChecklistEscalera = $this->personaModel->tieneColumna('Escalera_Checklist');
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

    private function obtenerIdRolAsistenteDefault() {
        if ($this->idRolAsistenteCache !== null) {
            return $this->idRolAsistenteCache;
        }

        $this->idRolAsistenteCache = 0;

        try {
            $rows = $this->personaModel->query("SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC");
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

                if (strpos($nombreRol, 'asistente') !== false) {
                    $this->idRolAsistenteCache = (int)($row['Id_Rol'] ?? 0);
                    break;
                }
            }
        } catch (Exception $e) {
            $this->idRolAsistenteCache = 0;
        }

        return $this->idRolAsistenteCache;
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
                'telefono' => (string)($_GET['telefono'] ?? ''),
                'cedula' => (string)($_GET['cedula'] ?? ''),
                'lider' => (string)($_GET['lider'] ?? ''),
                'id_lider' => (string)($_GET['id_lider'] ?? ''),
                'id_ministerio' => (string)($_GET['id_ministerio'] ?? ''),
                'programa' => (string)($_GET['programa'] ?? '')
            ]
        ]);
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
        $genero = trim((string)($_POST['genero'] ?? ''));
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

        if ($cedula === '' && $telefono === '') {
            $errores[] = 'Debe registrar cédula o teléfono para validar duplicados con precisión.';
        }

        if ($idMinisterio <= 0) {
            $errores[] = 'Debe seleccionar un ministerio.';
        }

        if (!in_array($programa, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            $errores[] = 'Debe seleccionar un programa válido.';
        }

        $generosValidos = ['Hombre', 'Mujer', 'Joven Hombre', 'Joven Mujer'];
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

        if ($idPersona > 0) {
            $nombreCompletoPersona = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
            if ($nombreCompletoPersona !== '') {
                $nombre = $this->normalizarTextoMayusculas($nombreCompletoPersona);
            }
            if (trim((string)($persona['Telefono'] ?? '')) !== '') {
                $telefono = $this->normalizarTelefono((string)$persona['Telefono']);
            }
            if (trim((string)($persona['Genero'] ?? '')) !== '') {
                $genero = trim((string)$persona['Genero']);
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
            'Telefono' => $telefono !== '' ? $telefono : null,
            'Cedula' => $cedula !== '' ? $cedula : null,
            'Lider' => $lider,
            'Id_Ministerio' => (int)$idMinisterio,
            'Nombre_Ministerio' => (string)($ministerio['Nombre_Ministerio'] ?? ''),
            'Programa' => $programa,
            'Fuente' => 'Formulario público'
        ];

        try {
            $this->inscripcionModel->create($data);
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
                'mensaje' => 'Inscripción registrada correctamente. La persona quedó en Personas nuevas si no existía.',
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
