<?php
/**
 * Controlador Home - Dashboard principal
 */

require_once APP . '/Models/EscuelaFormacionEstado.php';

class HomeController extends BaseController {

    private function getProgramaEscuelaLabel($programa) {
        $map = [
            'universidad_vida' => 'Universidad de la Vida',
            'encuentro' => 'Encuentro',
            'bautismo' => 'Bautismo',
            'capacitacion_destino' => 'Capacitación Destino',
            'capacitacion_destino_nivel_1' => 'Capacitación Destino - Nivel 1',
            'capacitacion_destino_nivel_2' => 'Capacitación Destino - Nivel 2',
            'capacitacion_destino_nivel_3' => 'Capacitación Destino - Nivel 3',
        ];

        $programa = trim((string)$programa);
        if ($programa === '') {
            return 'Todos';
        }

        return $map[$programa] ?? $programa;
    }

    private function construirOpcionesFiltroMinisterioLider($filtroCelulas) {
        require_once APP . '/Models/Celula.php';

        $celulaModel = new Celula();
        $celulasBase = $celulaModel->getAllWithMemberCountAndRole($filtroCelulas);

        $ministeriosDisponibles = [];
        $ministerioIdsPermitidos = [];
        $lideresDisponibles = [];
        $liderIdsPermitidos = [];
        $celulasDisponibles = [];

        foreach ($celulasBase as $celulaBase) {
            $idCelula = (int)($celulaBase['Id_Celula'] ?? 0);
            if ($idCelula > 0) {
                $celulasDisponibles[$idCelula] = [
                    'Id_Celula' => $idCelula,
                    'Nombre_Celula' => (string)($celulaBase['Nombre_Celula'] ?? '')
                ];
            }

            $idMinisterioLider = (int)($celulaBase['Id_Ministerio_Lider'] ?? 0);
            $nombreMinisterioLider = trim((string)($celulaBase['Nombre_Ministerio_Lider'] ?? ''));
            if ($idMinisterioLider > 0 && $nombreMinisterioLider !== '') {
                $ministeriosDisponibles[$idMinisterioLider] = [
                    'Id_Ministerio' => $idMinisterioLider,
                    'Nombre_Ministerio' => $nombreMinisterioLider
                ];
                $ministerioIdsPermitidos[$idMinisterioLider] = true;
            }

            $idLider = (int)($celulaBase['Id_Lider'] ?? 0);
            $nombreLider = trim((string)($celulaBase['Nombre_Lider'] ?? ''));
            if ($idLider > 0 && $nombreLider !== '') {
                $lideresDisponibles[$idLider] = [
                    'Id_Persona' => $idLider,
                    'Nombre_Completo' => $nombreLider,
                    'Id_Ministerio' => $idMinisterioLider
                ];
                $liderIdsPermitidos[$idLider] = true;
            }
        }

        ksort($ministeriosDisponibles);
        ksort($lideresDisponibles);
        ksort($celulasDisponibles);

        return [
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'ministerio_ids_permitidos' => $ministerioIdsPermitidos,
            'lideres_disponibles' => array_values($lideresDisponibles),
            'lider_ids_permitidos' => $liderIdsPermitidos,
            'celulas_disponibles' => array_values($celulasDisponibles)
        ];
    }

    private function normalizarProcesoValor($valor) {
        $proceso = trim((string)$valor);
        return in_array($proceso, ['Ganar', 'Consolidar', 'Discipular', 'Enviar'], true) ? $proceso : '';
    }

    private function obtenerChecklist(array $persona): array {
        $raw = trim((string)($persona['Escalera_Checklist'] ?? ''));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function peldanoMarcado(array $checklist, string $etapa, int $indice, string $procesoActual): bool {
        $ordenEtapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $idxActual  = array_search($procesoActual, $ordenEtapas, true);
        $idxEtapa   = array_search($etapa, $ordenEtapas, true);

        if ($idxActual !== false && $idxEtapa !== false && $idxEtapa < $idxActual) {
            return true;
        }

        $checksEtapa = $checklist[$etapa] ?? [];
        if (array_key_exists($indice, $checksEtapa)) {
            return !empty($checksEtapa[$indice]);
        }

        if ($etapa === $procesoActual && $indice === 0) {
            return true;
        }

        return false;
    }

    private function esOrigenValidoEscuela($tipoReunion): bool {
        $tipo = strtolower(trim((string)$tipoReunion));
        if ($tipo === '') {
            return false;
        }

        if (strpos($tipo, 'migrados') !== false) {
            return false;
        }

        return strpos($tipo, 'celula') !== false
            || strpos($tipo, 'célula') !== false
            || strpos($tipo, 'domingo') !== false
            || strpos($tipo, 'somos uno') !== false
            || strpos($tipo, 'somosuno') !== false
            || strpos($tipo, 'otro') !== false;
    }

    private function esPersonaNueva(array $persona): bool {
        return (int)($persona['Es_Antiguo'] ?? 1) === 0;
    }

    private function construirReporteUniversidadVida(array $personas): array {
        $rows = [];
        $vistos = [];

        foreach ($personas as $persona) {
            if (!$this->esPersonaNueva($persona)) {
                continue;
            }

            if (!$this->esOrigenValidoEscuela($persona['Tipo_Reunion'] ?? '')) {
                continue;
            }

            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona <= 0 || isset($vistos[$idPersona])) {
                continue;
            }
            $vistos[$idPersona] = true;

            $nombre = trim(trim((string)($persona['Nombre'] ?? '')) . ' ' . trim((string)($persona['Apellido'] ?? '')));
            $rows[] = [
                'id_persona' => $idPersona,
                'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
                'ministerio' => trim((string)($persona['Nombre_Ministerio'] ?? '')) ?: 'Sin ministerio',
                'lider' => trim((string)($persona['Nombre_Lider'] ?? '')) ?: 'Sin líder',
                'celula' => trim((string)($persona['Nombre_Celula'] ?? '')) ?: 'Sin célula',
                'fecha_registro' => substr(trim((string)($persona['Fecha_Registro'] ?? '')), 0, 10),
            ];
        }

        usort($rows, static function($a, $b) {
            return strcmp((string)$a['nombre'], (string)$b['nombre']);
        });

        return [
            'total' => count($rows),
            'rows' => $rows,
        ];
    }

    private function esGeneroMujer($genero) {
        $genero = strtolower(trim((string)$genero));
        return strpos($genero, 'mujer') !== false;
    }

    private function normalizarFiltroGeneroEscuela($valor) {
        $valor = strtolower(trim((string)$valor));
        return in_array($valor, ['', 'todos', 'hombres', 'mujeres', 'joven_hombre', 'joven_mujer'], true) ? $valor : '';
    }

    private function esGeneroHombre($genero) {
        $genero = strtolower(trim((string)$genero));
        if ($genero === '') {
            return false;
        }

        return strpos($genero, 'hombre') !== false
            || strpos($genero, 'mascul') !== false
            || $genero === 'm';
    }

    private function esGeneroJovenHombre($genero) {
        $genero = strtolower(trim((string)$genero));
        if ($genero === '') {
            return false;
        }

        return strpos($genero, 'joven') !== false
            && (strpos($genero, 'hombre') !== false || strpos($genero, 'mascul') !== false || $genero === 'jm');
    }

    private function esGeneroJovenMujer($genero) {
        $genero = strtolower(trim((string)$genero));
        if ($genero === '') {
            return false;
        }

        return strpos($genero, 'joven') !== false
            && (strpos($genero, 'mujer') !== false || strpos($genero, 'femen') !== false || $genero === 'jf');
    }

    private function filtrarPersonasEscuelaPorGenero(array $personas, $filtroGenero) {
        $filtroGenero = $this->normalizarFiltroGeneroEscuela($filtroGenero);
        if ($filtroGenero === '' || $filtroGenero === 'todos') {
            return $personas;
        }

        return array_values(array_filter($personas, function($persona) use ($filtroGenero) {
            $genero = (string)($persona['Genero'] ?? '');
            if ($filtroGenero === 'mujeres') {
                return $this->esGeneroMujer($genero);
            }

            if ($filtroGenero === 'joven_hombre') {
                return $this->esGeneroJovenHombre($genero);
            }

            if ($filtroGenero === 'joven_mujer') {
                return $this->esGeneroJovenMujer($genero);
            }

            return $this->esGeneroHombre($genero);
        }));
    }

    private function coincideBusquedaLider(array $lider, $buscar) {
        $buscar = strtolower(trim((string)$buscar));
        if ($buscar === '') {
            return true;
        }

        $nombre = strtolower(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? '')));
        $ministerio = strtolower(trim((string)($lider['Nombre_Ministerio'] ?? '')));

        return strpos($nombre, $buscar) !== false || strpos($ministerio, $buscar) !== false;
    }

    private function textoSinAcentos($texto) {
        $texto = trim((string)$texto);
        if ($texto === '') {
            return '';
        }

        if (class_exists('Normalizer')) {
            $normalizado = Normalizer::normalize($texto, Normalizer::FORM_D);
            if ($normalizado !== false) {
                $texto = preg_replace('/\p{Mn}+/u', '', $normalizado);
            }
        }

        $mapa = [
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ä' => 'A',
            'É' => 'E', 'È' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Í' => 'I', 'Ì' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O', 'Ö' => 'O',
            'Ú' => 'U', 'Ù' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ñ' => 'n',
        ];

        $texto = strtr($texto, $mapa);
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $texto);
        return preg_replace('/\s+/u', ' ', trim($texto));
    }

    private function textoCompacto($texto) {
        return str_replace(' ', '', $this->textoSinAcentos($texto));
    }

    private function coincideBusquedaFlexible($textoObjetivo, $terminoNormalizado, $terminoCompacto, array $tokens) {
        $textoNormalizado = $this->textoSinAcentos($textoObjetivo);
        if ($textoNormalizado === '') {
            return false;
        }

        $textoCompacto = $this->textoCompacto($textoObjetivo);

        if ($terminoNormalizado !== '' && strpos($textoNormalizado, $terminoNormalizado) !== false) {
            return true;
        }

        if ($terminoCompacto !== '' && strpos($textoCompacto, $terminoCompacto) !== false) {
            return true;
        }

        if (empty($tokens)) {
            return false;
        }

        $tokensCoinciden = true;
        foreach ($tokens as $token) {
            if (strpos($textoNormalizado, $token) === false && strpos($textoCompacto, $token) === false) {
                $tokensCoinciden = false;
                break;
            }
        }

        if ($tokensCoinciden) {
            return true;
        }

        // Tolerancia de una letra para busquedas con un solo token largo.
        if (count($tokens) === 1) {
            $token = $tokens[0];
            if (strlen($token) >= 5 && function_exists('levenshtein')) {
                foreach (explode(' ', $textoNormalizado) as $parte) {
                    if ($parte === '') {
                        continue;
                    }
                    if (abs(strlen($parte) - strlen($token)) <= 1 && levenshtein($parte, $token) <= 1) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function filtrarReportePorNombreFlexible(array $rows, $termino) {
        $termino = preg_replace('/\s+/u', ' ', trim((string)$termino));
        if ($termino === '') {
            return $rows;
        }

        $terminoNormalizado = $this->textoSinAcentos($termino);
        $terminoCompacto = $this->textoCompacto($termino);
        $tokens = array_values(array_filter(explode(' ', $terminoNormalizado), static function($token) {
            return $token !== '';
        }));

        return array_values(array_filter($rows, function($row) use ($terminoNormalizado, $terminoCompacto, $tokens) {
            $nombre = (string)($row['nombre'] ?? '');
            return $this->coincideBusquedaFlexible($nombre, $terminoNormalizado, $terminoCompacto, $tokens);
        }));
    }

    private function filtrarInscripcionesPorNombreFlexible(array $rows, $termino) {
        $termino = preg_replace('/\s+/u', ' ', trim((string)$termino));
        if ($termino === '') {
            return $rows;
        }

        $terminoNormalizado = $this->textoSinAcentos($termino);
        $terminoCompacto = $this->textoCompacto($termino);
        $tokens = array_values(array_filter(explode(' ', $terminoNormalizado), static function($token) {
            return $token !== '';
        }));

        return array_values(array_filter($rows, function($row) use ($terminoNormalizado, $terminoCompacto, $tokens) {
            $nombre = (string)($row['Nombre'] ?? '');
            return $this->coincideBusquedaFlexible($nombre, $terminoNormalizado, $terminoCompacto, $tokens);
        }));
    }
    
    public function index() {
        require_once APP . '/Models/Persona.php';
        require_once APP . '/Models/Celula.php';
        require_once APP . '/Models/Ministerio.php';
        require_once APP . '/Models/Evento.php';
        require_once APP . '/Helpers/DataIsolation.php';
        
        $personaModel = new Persona();
        $estadoEscuelaModel = new EscuelaFormacionEstado();
        $celulaModel = new Celula();
        $ministerioModel = new Ministerio();
        $eventoModel = new Evento();

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $filtroEventos = DataIsolation::generarFiltroEventos();
        $personasActivas = $personaModel->getWithFiltersAndRole($filtroPersonas, null, null, null, 'Activo', null, null, null, null, null);
        $reporteUniversidadVida = $this->construirReporteUniversidadVida($personasActivas);
        $estadosUv = $estadoEscuelaModel->getEstadosPorPrograma(array_column($reporteUniversidadVida['rows'], 'id_persona'), 'universidad_vida');
        foreach ($reporteUniversidadVida['rows'] as &$rowUv) {
            $rowUv['va'] = !empty($estadosUv[(int)($rowUv['id_persona'] ?? 0)]);
        }
        unset($rowUv);
        
        $data = [
            'totalPersonas' => count($personaModel->getAllActivosWithRole($filtroPersonas)),
            'totalCelulas' => count($celulaModel->getAllWithMemberCountAndRole($filtroCelulas)),
            'totalMinisterios' => count($ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios)),
            'totalLideresCelula' => $personaModel->getTotalLideresCelulaWithRole($filtroPersonas),
            'eventosProximos' => $eventoModel->getUpcomingWithRole($filtroEventos),
            'reporteUniversidadVida' => $reporteUniversidadVida,
        ];
        
        $this->view('home/dashboard', $data);
    }

    public function escuelasFormacion() {
        require_once APP . '/Models/Persona.php';
        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        require_once APP . '/Helpers/DataIsolation.php';

        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $personaModel = new Persona();
        $estadoEscuelaModel = new EscuelaFormacionEstado();
        $inscripcionModel = new EscuelaFormacionInscripcion();
        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();

        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $celulasDisponibles = $opcionesFiltro['celulas_disponibles'];
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasDisponibles);

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroBuscarUv = preg_replace('/\s+/u', ' ', trim((string)($_GET['buscar_uv'] ?? '')));
        $filtroGenero = $this->normalizarFiltroGeneroEscuela($_GET['genero'] ?? 'todos');
        $filtroProgramaInscripcion = trim((string)($_GET['insc_programa'] ?? ''));
        if ($filtroProgramaInscripcion === '') {
            $filtroProgramaInscripcion = 'universidad_vida';
        }
        $filtroBusquedaInscripcion = '';

        if (!in_array($filtroProgramaInscripcion, ['universidad_vida', 'encuentro', 'bautismo', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
            $filtroProgramaInscripcion = 'universidad_vida';
        }

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;

        $inscripcionesPublicas = $inscripcionModel->getListado(
            $filtroProgramaInscripcion,
            $filtroBusquedaInscripcion,
            300,
            $filtroGenero,
            $idMinisterioFiltro,
            $idLiderFiltro
        );
        $inscripcionesPublicas = array_values(array_filter($inscripcionesPublicas, static function($ins) use ($filtroProgramaInscripcion) {
            return (string)($ins['Programa'] ?? '') === $filtroProgramaInscripcion;
        }));
        $inscripcionesPublicas = $this->filtrarInscripcionesPorNombreFlexible($inscripcionesPublicas, $filtroBuscarUv);

        $programaReporte = $filtroProgramaInscripcion;
        $reporteUniversidadVida = [
            'total' => 0,
            'rows' => []
        ];

        $rowsDetalle = [];
        $personasIncluidas = [];
        foreach ($inscripcionesPublicas as $ins) {
            if ($programaReporte !== '' && (string)($ins['Programa'] ?? '') !== $programaReporte) {
                continue;
            }

            $idPersona = (int)($ins['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            if (isset($personasIncluidas[$idPersona])) {
                continue;
            }
            $personasIncluidas[$idPersona] = true;

            $rowsDetalle[] = [
                'id_persona' => $idPersona,
                'nombre' => (string)($ins['Nombre'] ?? ''),
                'lider' => (string)($ins['Lider'] ?? ''),
                'ministerio' => (string)($ins['Nombre_Ministerio'] ?? ''),
            ];
        }

        $rowsDetalle = $this->filtrarReportePorNombreFlexible($rowsDetalle, $filtroBuscarUv);

        $estadosUvDetalle = $estadoEscuelaModel->getEstadosDetallePorPrograma(array_column($rowsDetalle, 'id_persona'), $programaReporte);
        foreach ($rowsDetalle as &$rowUv) {
            $idPersonaUv = (int)($rowUv['id_persona'] ?? 0);
            $detalleEstado = $estadosUvDetalle[$idPersonaUv] ?? ['existe' => false, 'va' => false];
            $rowUv['va'] = !empty($detalleEstado['va']);
            $rowUv['procesado'] = !empty($detalleEstado['existe']);
        }
        unset($rowUv);

        $rowsDetalle = array_values(array_filter($rowsDetalle, static function($row) {
            return empty($row['procesado']);
        }));

        $reporteUniversidadVida['rows'] = $rowsDetalle;
        $reporteUniversidadVida['total'] = count($rowsDetalle);

        $resumenInscripciones = $inscripcionModel->getResumenProgramas();

        $this->view('home/escuelas_formacion', [
            'reporteUniversidadVida' => $reporteUniversidadVida,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles' => $opcionesFiltro['lideres_disponibles'],
            'celulas_disponibles' => $celulasDisponibles,
            'filtro_ministerio' => (string)$filtroMinisterio,
            'filtro_lider' => (string)$filtroLider,
            'filtro_buscar_uv' => $filtroBuscarUv,
            'filtro_genero' => $filtroGenero,
            'programa_reporte' => $programaReporte,
            'programa_reporte_label' => $this->getProgramaEscuelaLabel($programaReporte),
            'inscripciones_publicas' => $inscripcionesPublicas,
            'resumen_inscripciones' => $resumenInscripciones,
            'filtro_insc_programa' => $filtroProgramaInscripcion,
            'filtro_insc_buscar' => $filtroBusquedaInscripcion,
        ]);
    }

    public function exportarEscuelasFormacion() {
        require_once APP . '/Models/Persona.php';
        require_once APP . '/Helpers/DataIsolation.php';

        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $personaModel = new Persona();
        $estadoEscuelaModel = new EscuelaFormacionEstado();
        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);
        $celulasDisponibles = $opcionesFiltro['celulas_disponibles'];
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasDisponibles);

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroBuscarUv = preg_replace('/\s+/u', ' ', trim((string)($_GET['buscar_uv'] ?? '')));
        $filtroGenero = $this->normalizarFiltroGeneroEscuela($_GET['genero'] ?? 'todos');

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;
        $idCelulaFiltro = null;

        $personasActivas = $personaModel->getWithFiltersAndRole(
            $filtroPersonas,
            $idMinisterioFiltro,
            $idLiderFiltro,
            null,
            'Activo',
            $idCelulaFiltro,
            null,
            null,
            null,
            null
        );

        $personasActivas = $this->filtrarPersonasEscuelaPorGenero($personasActivas, $filtroGenero);

        $reporteUniversidadVida = $this->construirReporteUniversidadVida($personasActivas);
        $reporteUniversidadVida['rows'] = $this->filtrarReportePorNombreFlexible($reporteUniversidadVida['rows'] ?? [], $filtroBuscarUv);
        $reporteUniversidadVida['total'] = count($reporteUniversidadVida['rows'] ?? []);
        $estadosUv = $estadoEscuelaModel->getEstadosPorPrograma(array_column($reporteUniversidadVida['rows'], 'id_persona'), 'universidad_vida');
        $rows = array_map(static function($row) use ($estadosUv) {
            return [
                $row['nombre'] ?? '',
                $row['ministerio'] ?? '',
                $row['lider'] ?? '',
                !empty($estadosUv[(int)($row['id_persona'] ?? 0)]) ? 'Si' : 'No',
            ];
        }, $reporteUniversidadVida['rows'] ?? []);

        $this->exportCsv(
            'escuelas_formacion_universidad_vida',
            ['Persona', 'Ministerio', 'Lider', 'Va'],
            $rows,
            false
        );
    }

    public function actualizarEstadoEscuelaFormacion() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'editar')) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $programa = trim((string)($_POST['programa'] ?? ''));
        $va = (int)($_POST['va'] ?? 0) === 1;

        if ($idPersona <= 0 || $programa === '') {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 422);
        }

        $estadoEscuelaModel = new EscuelaFormacionEstado();
        $ok = $estadoEscuelaModel->upsertEstado($idPersona, $programa, $va);

        $registroActualizado = false;
        if ($ok && $va) {
            require_once APP . '/Models/EscuelaFormacionInscripcion.php';
            $inscripcionModel = new EscuelaFormacionInscripcion();
            $registroActualizado = (bool)$inscripcionModel->crearDesdePersonaSiNoExiste($idPersona, $programa);
        }

        $this->json([
            'ok' => (bool)$ok,
            'va' => $va,
            'registro_actualizado' => $registroActualizado
        ]);
    }

    public function actualizarAsistenciaClaseEscuelaFormacion() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'editar')) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $idInscripcion = (int)($_POST['id_inscripcion'] ?? 0);
        $asistioRaw = trim((string)($_POST['asistio'] ?? ''));

        if ($idInscripcion <= 0) {
            $this->json(['ok' => false, 'error' => 'Inscripción inválida'], 422);
        }

        $asistio = null;
        if ($asistioRaw === '1') {
            $asistio = true;
        } elseif ($asistioRaw === '0') {
            $asistio = false;
        } elseif ($asistioRaw === '') {
            $asistio = null;
        } else {
            $this->json(['ok' => false, 'error' => 'Valor de asistencia inválido'], 422);
        }

        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        $inscripcionModel = new EscuelaFormacionInscripcion();
        $ok = $inscripcionModel->actualizarAsistenciaClase($idInscripcion, $asistio);

        $this->json([
            'ok' => (bool)$ok,
            'id_inscripcion' => $idInscripcion,
            'asistio' => $asistio
        ]);
    }

    public function lideresCelula() {
        require_once APP . '/Models/Persona.php';
        require_once APP . '/Helpers/DataIsolation.php';

        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $personaModel = new Persona();
        $filtroPersonas = DataIsolation::generarFiltroPersonas();

        $lideres = $personaModel->getResumenLideresCelulaWithRole($filtroPersonas);

        $filtroGenero = strtolower(trim((string)($_GET['genero'] ?? 'todos')));
        if (!in_array($filtroGenero, ['todos', 'hombres', 'mujeres'], true)) {
            $filtroGenero = 'todos';
        }

        $filtroTipoLiderazgo = strtolower(trim((string)($_GET['tipo_liderazgo'] ?? 'todos')));
        if (!in_array($filtroTipoLiderazgo, ['todos', 'celula', 'doce', 'ambos'], true)) {
            $filtroTipoLiderazgo = 'todos';
        }

        $filtroBuscar = trim((string)($_GET['buscar'] ?? ''));
        $filtroMinisterio = trim((string)($_GET['ministerio'] ?? ''));

        $ministeriosDisponibles = [];
        foreach ($lideres as $lider) {
            $idMinisterio = (int)($lider['Id_Ministerio'] ?? 0);
            $nombreMinisterio = trim((string)($lider['Nombre_Ministerio'] ?? ''));
            if ($idMinisterio > 0 && $nombreMinisterio !== '') {
                $ministeriosDisponibles[$idMinisterio] = [
                    'id' => $idMinisterio,
                    'nombre' => $nombreMinisterio
                ];
            }
        }
        ksort($ministeriosDisponibles);

        $lideresFiltrados = array_values(array_filter($lideres, function($lider) use ($filtroGenero, $filtroTipoLiderazgo, $filtroBuscar, $filtroMinisterio) {
            $esMujer = $this->esGeneroMujer($lider['Genero'] ?? '');
            $esLiderCelula = (int)($lider['Es_Lider_Celula'] ?? 0) === 1;
            $esLider12 = (int)($lider['Es_Lider_12'] ?? 0) === 1;

            if ($filtroGenero === 'mujeres' && !$esMujer) {
                return false;
            }
            if ($filtroGenero === 'hombres' && $esMujer) {
                return false;
            }

            if ($filtroTipoLiderazgo === 'celula' && !$esLiderCelula) {
                return false;
            }
            if ($filtroTipoLiderazgo === 'doce' && !$esLider12) {
                return false;
            }
            if ($filtroTipoLiderazgo === 'ambos' && !($esLiderCelula && $esLider12)) {
                return false;
            }

            if ($filtroMinisterio !== '') {
                if ((int)($lider['Id_Ministerio'] ?? 0) !== (int)$filtroMinisterio) {
                    return false;
                }
            }

            return $this->coincideBusquedaLider($lider, $filtroBuscar);
        }));

        $lideresHombres = [];
        $lideresMujeres = [];
        foreach ($lideresFiltrados as $lider) {
            if ($this->esGeneroMujer($lider['Genero'] ?? '')) {
                $lideresMujeres[] = $lider;
            } else {
                $lideresHombres[] = $lider;
            }
        }

        $this->view('home/lideres_celula', [
            'lideres_hombres' => $lideresHombres,
            'lideres_mujeres' => $lideresMujeres,
            'total_hombres' => count($lideresHombres),
            'total_mujeres' => count($lideresMujeres),
            'total_lideres' => count($lideresFiltrados),
            'ministerios_disponibles' => array_values($ministeriosDisponibles),
            'filtro_genero' => $filtroGenero,
            'filtro_tipo_liderazgo' => $filtroTipoLiderazgo,
            'filtro_buscar' => $filtroBuscar,
            'filtro_ministerio' => $filtroMinisterio
        ]);
    }
}
