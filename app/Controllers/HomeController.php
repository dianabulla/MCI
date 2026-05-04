<?php
/**
 * Controlador Home - Dashboard principal
 */

require_once APP . '/Models/EscuelaFormacionEstado.php';

class HomeController extends BaseController {

    private function getProgramaEscuelaLabel($programa) {
        $map = [
            'universidad_vida' => 'Universidad de la Vida',
            'encuentro' => 'Universidad de la Vida',
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

    private function normalizarProgramaConsolidar($programa) {
        $programa = trim((string)$programa);
        return $programa === 'encuentro' ? 'universidad_vida' : $programa;
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
        if ($valor === 'joven_hombre') {
            return 'hombres';
        }
        if ($valor === 'joven_mujer') {
            return 'mujeres';
        }
        return in_array($valor, ['', 'todos', 'hombres', 'mujeres'], true) ? $valor : '';
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

            return $this->esGeneroHombre($genero);
        }));
    }

    private function construirClaveUnicaInscripcion(array $inscripcion): string {
        $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
        if ($idPersona > 0) {
            return 'id:' . $idPersona;
        }

        $cedula = preg_replace('/\D+/', '', (string)($inscripcion['Cedula'] ?? ''));
        if ($cedula !== '') {
            return 'cc:' . $cedula;
        }

        $telefono = preg_replace('/\D+/', '', (string)($inscripcion['Telefono'] ?? ''));
        if ($telefono !== '') {
            return 'tel:' . $telefono;
        }

        $nombre = strtolower(trim((string)($inscripcion['Nombre'] ?? '')));
        if ($nombre !== '') {
            return 'nom:' . $nombre;
        }

        return 'ins:' . (int)($inscripcion['Id_Inscripcion'] ?? 0);
    }

    private function deduplicarInscripcionesPorPersona(array $inscripciones): array {
        $inscripciones = array_values($inscripciones);
        usort($inscripciones, static function($a, $b) {
            $fa = (string)($a['Fecha_Registro'] ?? '');
            $fb = (string)($b['Fecha_Registro'] ?? '');
            if ($fa === $fb) {
                return (int)($b['Id_Inscripcion'] ?? 0) <=> (int)($a['Id_Inscripcion'] ?? 0);
            }
            return strcmp($fb, $fa);
        });

        $deduplicadas = [];
        $vistas = [];
        foreach ($inscripciones as $inscripcion) {
            $clave = $this->construirClaveUnicaInscripcion((array)$inscripcion);
            if (isset($vistas[$clave])) {
                continue;
            }
            $vistas[$clave] = true;
            $deduplicadas[] = $inscripcion;
        }

        return $deduplicadas;
    }

    private function obtenerMapaPersonasPermitidasFormacion(): array {
        static $cache = null;
        if (is_array($cache)) {
            return $cache;
        }

        require_once APP . '/Models/Persona.php';
        require_once APP . '/Helpers/DataIsolation.php';

        if (DataIsolation::tieneAccesoTotal()) {
            $cache = ['__all__' => true];
            return $cache;
        }

        $filtroRol = DataIsolation::generarFiltroPersonas();
        $personaModel = new Persona();
        $personas = $personaModel->getWithFiltersAndRole($filtroRol, null, null, null);

        $map = [];
        foreach ((array)$personas as $persona) {
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona > 0) {
                $map[$idPersona] = true;
            }
        }

        $cache = $map;
        return $cache;
    }

    private function filtrarInscripcionesPorAislamientoFormacion(array $inscripciones): array {
        $permitidas = $this->obtenerMapaPersonasPermitidasFormacion();
        if (!empty($permitidas['__all__'])) {
            return array_values($inscripciones);
        }

        if (empty($permitidas)) {
            return [];
        }

        return array_values(array_filter($inscripciones, static function($inscripcion) use ($permitidas) {
            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            return $idPersona > 0 && isset($permitidas[$idPersona]);
        }));
    }

    private function puedeGestionarPersonaFormacion(int $idPersona): bool {
        if ($idPersona <= 0) {
            return false;
        }

        $permitidas = $this->obtenerMapaPersonasPermitidasFormacion();
        if (!empty($permitidas['__all__'])) {
            return true;
        }

        return isset($permitidas[$idPersona]);
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
    
    private function obtenerConfiguracionModuloFormacion($modulo) {
        $modulo = strtolower(trim((string)$modulo));

        if ($modulo === 'discipular') {
            return [
                'modulo' => 'discipular',
                'titulo' => 'Discipular',
                'ruta_base' => 'home/discipular',
                'ruta_asistencias' => 'home/discipular/asistencias',
                'ruta_exportar' => 'home/discipular/exportar',
                'vista' => 'home/discipular',
                'programa_default' => 'capacitacion_destino_nivel_1',
                'programas_permitidos' => ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'],
                'programas_opciones' => [
                    'capacitacion_destino_nivel_1' => 'Capacitación Destino - Nivel 1',
                    'capacitacion_destino_nivel_2' => 'Capacitación Destino - Nivel 2',
                    'capacitacion_destino_nivel_3' => 'Capacitación Destino - Nivel 3',
                ],
                'resumen_programas' => ['capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'],
            ];
        }

        return [
            'modulo' => 'consolidar',
            'titulo' => 'Consolidar',
            'ruta_base' => 'home/consolidar',
            'ruta_asistencias' => 'home/consolidar/asistencias',
            'ruta_exportar' => 'home/consolidar/exportar',
            'vista' => 'home/consolidar',
            'programa_default' => 'universidad_vida',
            'programas_permitidos' => ['universidad_vida'],
            'programas_opciones' => [
                'universidad_vida' => 'Universidad de la Vida',
            ],
            'resumen_programas' => ['universidad_vida'],
        ];
    }

    private function obtenerDatosModuloFormacion($modulo) {
        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
        require_once APP . '/Helpers/DataIsolation.php';

        $config = $this->obtenerConfiguracionModuloFormacion($modulo);
        $estadoEscuelaModel = new EscuelaFormacionEstado();
        $inscripcionModel = new EscuelaFormacionInscripcion();
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroLider = $_GET['lider'] ?? '';
        $filtroBuscar = preg_replace('/\s+/u', ' ', trim((string)($_GET['buscar'] ?? ($_GET['buscar_uv'] ?? ''))));
        $filtroGenero = $this->normalizarFiltroGeneroEscuela($_GET['genero'] ?? 'todos');
        $filtroProgramaInscripcion = trim((string)($_GET['insc_programa'] ?? ($_GET['programa'] ?? '')));
        if ($filtroProgramaInscripcion === 'capacitacion_destino') {
            $filtroProgramaInscripcion = 'capacitacion_destino_nivel_1';
        }
        if ((string)($config['modulo'] ?? '') === 'consolidar') {
            $filtroProgramaInscripcion = $this->normalizarProgramaConsolidar($filtroProgramaInscripcion);
        }

        if ($filtroProgramaInscripcion === '' || !in_array($filtroProgramaInscripcion, $config['programas_permitidos'], true)) {
            $filtroProgramaInscripcion = $config['programa_default'];
        }

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroLider = ($filtroLider !== '' && isset($opcionesFiltro['lider_ids_permitidos'][(int)$filtroLider])) ? (int)$filtroLider : '';

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;
        $idLiderFiltro = ($filtroLider !== '' && (int)$filtroLider > 0) ? (int)$filtroLider : null;

        $programasConsulta = ((string)($config['modulo'] ?? '') === 'consolidar' && $filtroProgramaInscripcion === 'universidad_vida')
            ? ['universidad_vida', 'encuentro']
            : [$filtroProgramaInscripcion];

        $inscripcionesPublicas = [];
        foreach ($programasConsulta as $programaConsulta) {
            $inscripcionesPrograma = $inscripcionModel->getListado(
                $programaConsulta,
                '',
                300,
                $filtroGenero,
                $idMinisterioFiltro,
                $idLiderFiltro
            );

            foreach ($inscripcionesPrograma as $inscripcionTmp) {
                $idIns = (int)($inscripcionTmp['Id_Inscripcion'] ?? 0);
                if ($idIns <= 0) {
                    continue;
                }
                $inscripcionesPublicas[$idIns] = $inscripcionTmp;
            }
        }

        $inscripcionesPublicas = $this->deduplicarInscripcionesPorPersona($inscripcionesPublicas);

        $inscripcionesPublicas = array_values(array_filter($inscripcionesPublicas, static function($ins) use ($programasConsulta) {
            return in_array((string)($ins['Programa'] ?? ''), $programasConsulta, true);
        }));
        $inscripcionesPublicas = $this->filtrarInscripcionesPorAislamientoFormacion($inscripcionesPublicas);

        foreach ($inscripcionesPublicas as &$inscripcionTmp) {
            $nombreActual = trim((string)($inscripcionTmp['Nombre_Persona_Actual'] ?? ''));
            $generoActual = trim((string)($inscripcionTmp['Genero_Persona_Actual'] ?? ''));
            $edadActual = (int)($inscripcionTmp['Edad_Persona_Actual'] ?? 0);
            $liderActual = trim((string)($inscripcionTmp['Lider_Persona_Actual'] ?? ''));
            $ministerioActual = trim((string)($inscripcionTmp['Nombre_Ministerio_Persona_Actual'] ?? ''));

            if ($nombreActual !== '') {
                $inscripcionTmp['Nombre'] = $nombreActual;
            }
            if ($generoActual !== '') {
                $inscripcionTmp['Genero'] = $generoActual;
            }
            if ($edadActual > 0) {
                $inscripcionTmp['Edad'] = $edadActual;
            }
            if ($liderActual !== '') {
                $inscripcionTmp['Lider'] = $liderActual;
            }
            if ($ministerioActual !== '') {
                $inscripcionTmp['Nombre_Ministerio'] = $ministerioActual;
            }
        }
        unset($inscripcionTmp);

        $inscripcionesPublicas = $this->filtrarInscripcionesPorNombreFlexible($inscripcionesPublicas, $filtroBuscar);

        $rowsDetalle = [];
        $personasIncluidas = [];
        foreach ($inscripcionesPublicas as $ins) {
            $idPersona = (int)($ins['Id_Persona'] ?? 0);
            if ($idPersona <= 0 || isset($personasIncluidas[$idPersona])) {
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

        $rowsDetalle = $this->filtrarReportePorNombreFlexible($rowsDetalle, $filtroBuscar);

        $estadosDetalle = [];
        foreach ($programasConsulta as $programaConsulta) {
            $estadosPrograma = $estadoEscuelaModel->getEstadosDetallePorPrograma(array_column($rowsDetalle, 'id_persona'), $programaConsulta);
            foreach ($estadosPrograma as $idPersonaEstado => $estadoTmp) {
                $idPersonaEstado = (int)$idPersonaEstado;
                if (!isset($estadosDetalle[$idPersonaEstado])) {
                    $estadosDetalle[$idPersonaEstado] = ['existe' => false, 'va' => false];
                }
                $estadosDetalle[$idPersonaEstado]['existe'] = !empty($estadosDetalle[$idPersonaEstado]['existe']) || !empty($estadoTmp['existe']);
                $estadosDetalle[$idPersonaEstado]['va'] = !empty($estadosDetalle[$idPersonaEstado]['va']) || !empty($estadoTmp['va']);
            }
        }
        foreach ($rowsDetalle as &$rowDetalle) {
            $idPersona = (int)($rowDetalle['id_persona'] ?? 0);
            $detalleEstado = $estadosDetalle[$idPersona] ?? ['existe' => false, 'va' => false];
            $rowDetalle['va'] = !empty($detalleEstado['va']);
            $rowDetalle['procesado'] = !empty($detalleEstado['existe']);
        }
        unset($rowDetalle);

        $rowsDetalle = array_values(array_filter($rowsDetalle, static function($row) {
            return empty($row['procesado']);
        }));

        $idsPersonaInscritas = array_values(array_unique(array_filter(array_map(static function($inscripcion) {
            return (int)($inscripcion['Id_Persona'] ?? 0);
        }, $inscripcionesPublicas), static function($idPersona) {
            return $idPersona > 0;
        })));

        $personasConAsistenciaReal = [];
        foreach ($programasConsulta as $programaConsulta) {
            $asistenciasPrograma = $asistenciaModel->getAsistenciasPorPrograma($idsPersonaInscritas, (string)($config['modulo'] ?? ''), $programaConsulta);
            foreach ($asistenciasPrograma as $idPersonaAsistencia => $clasesAsistencia) {
                foreach ((array)$clasesAsistencia as $asistioClase) {
                    if (!empty($asistioClase)) {
                        $personasConAsistenciaReal[(int)$idPersonaAsistencia] = true;
                        break;
                    }
                }
            }
        }

        $tablaUvPorMinisterioMap = [];
        $detalleLideresMinisterioUvMap = [];
        foreach ($inscripcionesPublicas as $inscripcionUv) {
            if ((string)($inscripcionUv['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcionUv['Nombre_Ministerio'] ?? ''));
            if ($ministerioNombre === '') {
                $ministerioNombre = 'Sin ministerio';
            }

            $liderNombre = trim((string)($inscripcionUv['Lider'] ?? ''));
            if ($liderNombre === '') {
                $liderNombre = 'Sin lider';
            }

            $edadUv = (int)($inscripcionUv['Edad'] ?? 0);
            $generoUv = strtolower(trim((string)($inscripcionUv['Genero'] ?? '')));
            $esMujerUv = strpos($generoUv, 'mujer') !== false
                || strpos($generoUv, 'femen') !== false
                || in_array($generoUv, ['f', 'fem', 'female'], true);
            $esHombreUv = strpos($generoUv, 'hombre') !== false
                || strpos($generoUv, 'mascul') !== false
                || in_array($generoUv, ['m', 'masc', 'male', 'h'], true);
            $esJovenUv = $edadUv >= 14 && $edadUv <= 28;

            if (!isset($tablaUvPorMinisterioMap[$ministerioNombre])) {
                $tablaUvPorMinisterioMap[$ministerioNombre] = [
                    'ministerio' => $ministerioNombre,
                    'hombres' => 0,
                    'mujeres' => 0,
                    'jovenes' => 0,
                    'asistencias_reales' => 0,
                    'total' => 0,
                ];
            }
            if (!isset($detalleLideresMinisterioUvMap[$ministerioNombre])) {
                $detalleLideresMinisterioUvMap[$ministerioNombre] = [];
            }
            if (!isset($detalleLideresMinisterioUvMap[$ministerioNombre][$liderNombre])) {
                $detalleLideresMinisterioUvMap[$ministerioNombre][$liderNombre] = [
                    'lider' => $liderNombre,
                    'hombres' => 0,
                    'mujeres' => 0,
                    'jovenes' => 0,
                    'asistencias_reales' => 0,
                    'total' => 0,
                ];
            }

            if ($esJovenUv) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['jovenes']++;
                $detalleLideresMinisterioUvMap[$ministerioNombre][$liderNombre]['jovenes']++;
            } elseif ($esHombreUv) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['hombres']++;
                $detalleLideresMinisterioUvMap[$ministerioNombre][$liderNombre]['hombres']++;
            } elseif ($esMujerUv) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['mujeres']++;
                $detalleLideresMinisterioUvMap[$ministerioNombre][$liderNombre]['mujeres']++;
            }

            $tablaUvPorMinisterioMap[$ministerioNombre]['total']++;
            $detalleLideresMinisterioUvMap[$ministerioNombre][$liderNombre]['total']++;

            $idPersonaInscripcionUv = (int)($inscripcionUv['Id_Persona'] ?? 0);
            if ($idPersonaInscripcionUv > 0 && !empty($personasConAsistenciaReal[$idPersonaInscripcionUv])) {
                $tablaUvPorMinisterioMap[$ministerioNombre]['asistencias_reales']++;
                $detalleLideresMinisterioUvMap[$ministerioNombre][$liderNombre]['asistencias_reales']++;
            }
        }

        $tablaUvPorMinisterio = array_values($tablaUvPorMinisterioMap);
        usort($tablaUvPorMinisterio, static function($a, $b) {
            $cmpTotal = ((int)($b['total'] ?? 0)) <=> ((int)($a['total'] ?? 0));
            if ($cmpTotal !== 0) {
                return $cmpTotal;
            }
            return strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
        });

        $detalleLideresMinisterioUv = [];
        foreach ($detalleLideresMinisterioUvMap as $ministerioNombre => $detalleLideres) {
            $rowsLideres = array_values($detalleLideres);
            usort($rowsLideres, static function($a, $b) {
                $cmpTotal = ((int)($b['total'] ?? 0)) <=> ((int)($a['total'] ?? 0));
                if ($cmpTotal !== 0) {
                    return $cmpTotal;
                }
                return strcmp((string)($a['lider'] ?? ''), (string)($b['lider'] ?? ''));
            });
            $detalleLideresMinisterioUv[$ministerioNombre] = $rowsLideres;
        }

        $resumenInscripciones = $inscripcionModel->getResumenProgramas();
        $tarjetasResumen = [];
        foreach ($config['resumen_programas'] as $programa) {
            $totalTarjeta = (int)($resumenInscripciones[$programa] ?? 0);
            if ((string)($config['modulo'] ?? '') === 'consolidar' && $programa === 'universidad_vida') {
                $totalTarjeta += (int)($resumenInscripciones['encuentro'] ?? 0);
            }
            $tarjetasResumen[] = [
                'programa' => $programa,
                'label' => $this->getProgramaEscuelaLabel($programa),
                'total' => $totalTarjeta,
            ];
        }

        return [
            'config_modulo' => $config,
            'vista_actual' => 'registro',
            'reporte_pendientes' => [
                'total' => count($rowsDetalle),
                'rows' => $rowsDetalle,
            ],
            'inscripciones_publicas' => $inscripcionesPublicas,
            'resumen_inscripciones' => $resumenInscripciones,
            'tarjetas_resumen' => $tarjetasResumen,
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles' => $opcionesFiltro['lideres_disponibles'],
            'filtro_ministerio' => (string)$filtroMinisterio,
            'filtro_lider' => (string)$filtroLider,
            'filtro_buscar' => $filtroBuscar,
            'filtro_genero' => $filtroGenero,
            'programa_reporte' => $filtroProgramaInscripcion,
            'programa_reporte_label' => $this->getProgramaEscuelaLabel($filtroProgramaInscripcion),
            'filtro_insc_programa' => $filtroProgramaInscripcion,
            'programas_opciones' => $config['programas_opciones'],
            'tabla_uv_ministerio' => $tablaUvPorMinisterio,
            'detalle_lideres_ministerio_uv' => $detalleLideresMinisterioUv,
        ];
    }

    private function obtenerDatosModuloFormacionAsistencias($modulo) {
        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
        require_once APP . '/Helpers/DataIsolation.php';

        $config = $this->obtenerConfiguracionModuloFormacion($modulo);
        $inscripcionModel = new EscuelaFormacionInscripcion();
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $opcionesFiltro = $this->construirOpcionesFiltroMinisterioLider($filtroCelulas);

        $filtroMinisterio = $_GET['ministerio'] ?? '';
        $filtroFechaDesde = trim((string)($_GET['fecha_desde'] ?? ''));
        $filtroFechaHasta = trim((string)($_GET['fecha_hasta'] ?? ''));
        $filtroProgramaInscripcion = trim((string)($_GET['insc_programa'] ?? ($_GET['programa'] ?? '')));
        if ($filtroProgramaInscripcion === 'capacitacion_destino') {
            $filtroProgramaInscripcion = 'capacitacion_destino_nivel_1';
        }
        if ((string)($config['modulo'] ?? '') === 'consolidar') {
            $filtroProgramaInscripcion = $this->normalizarProgramaConsolidar($filtroProgramaInscripcion);
        }

        if ($filtroProgramaInscripcion === '' || !in_array($filtroProgramaInscripcion, $config['programas_permitidos'], true)) {
            $filtroProgramaInscripcion = $config['programa_default'];
        }

        $filtroMinisterio = ($filtroMinisterio !== '' && isset($opcionesFiltro['ministerio_ids_permitidos'][(int)$filtroMinisterio])) ? (int)$filtroMinisterio : '';
        $filtroFechaDesde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroFechaDesde) ? $filtroFechaDesde : '';
        $filtroFechaHasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtroFechaHasta) ? $filtroFechaHasta : '';
        if ($filtroFechaDesde !== '' && $filtroFechaHasta !== '' && $filtroFechaDesde > $filtroFechaHasta) {
            $tmpFecha = $filtroFechaDesde;
            $filtroFechaDesde = $filtroFechaHasta;
            $filtroFechaHasta = $tmpFecha;
        }

        $idMinisterioFiltro = ($filtroMinisterio !== '' && (int)$filtroMinisterio > 0) ? (int)$filtroMinisterio : null;

        $programasConsulta = ((string)($config['modulo'] ?? '') === 'consolidar' && $filtroProgramaInscripcion === 'universidad_vida')
            ? ['universidad_vida', 'encuentro']
            : [$filtroProgramaInscripcion];

        $inscripcionesPublicas = [];
        foreach ($programasConsulta as $programaConsulta) {
            $inscripcionesPrograma = $inscripcionModel->getListado(
                $programaConsulta,
                '',
                300,
                'todos',
                $idMinisterioFiltro,
                null
            );

            foreach ($inscripcionesPrograma as $inscripcionTmp) {
                $idIns = (int)($inscripcionTmp['Id_Inscripcion'] ?? 0);
                if ($idIns <= 0) {
                    continue;
                }
                $inscripcionesPublicas[$idIns] = $inscripcionTmp;
            }
        }

        $inscripcionesPublicas = $this->deduplicarInscripcionesPorPersona($inscripcionesPublicas);

        $inscripcionesPublicas = array_values(array_filter($inscripcionesPublicas, static function($ins) use ($programasConsulta) {
            return in_array((string)($ins['Programa'] ?? ''), $programasConsulta, true);
        }));
        $inscripcionesPublicas = $this->filtrarInscripcionesPorAislamientoFormacion($inscripcionesPublicas);

        foreach ($inscripcionesPublicas as &$inscripcionTmp) {
            $nombreActual = trim((string)($inscripcionTmp['Nombre_Persona_Actual'] ?? ''));
            $generoActual = trim((string)($inscripcionTmp['Genero_Persona_Actual'] ?? ''));
            $edadActual = (int)($inscripcionTmp['Edad_Persona_Actual'] ?? 0);
            $liderActual = trim((string)($inscripcionTmp['Lider_Persona_Actual'] ?? ''));
            $ministerioActual = trim((string)($inscripcionTmp['Nombre_Ministerio_Persona_Actual'] ?? ''));

            if ($nombreActual !== '') {
                $inscripcionTmp['Nombre'] = $nombreActual;
            }
            if ($generoActual !== '') {
                $inscripcionTmp['Genero'] = $generoActual;
            }
            if ($edadActual > 0) {
                $inscripcionTmp['Edad'] = $edadActual;
            }
            if ($liderActual !== '') {
                $inscripcionTmp['Lider'] = $liderActual;
            }
            if ($ministerioActual !== '') {
                $inscripcionTmp['Nombre_Ministerio'] = $ministerioActual;
            }
        }
        unset($inscripcionTmp);

        $rowsAsistencia = [];
        $personasIncluidas = [];
        foreach ($inscripcionesPublicas as $ins) {
            $idPersona = (int)($ins['Id_Persona'] ?? 0);
            if ($idPersona <= 0 || isset($personasIncluidas[$idPersona])) {
                continue;
            }

            $personasIncluidas[$idPersona] = true;
            $rowsAsistencia[] = [
                'id_persona' => $idPersona,
                'nombre' => (string)($ins['Nombre'] ?? ''),
                'lider' => (string)($ins['Lider'] ?? ''),
                'ministerio' => (string)($ins['Nombre_Ministerio'] ?? ''),
                'edad' => (int)($ins['Edad'] ?? 0),
                'genero' => (string)($ins['Genero'] ?? ''),
            ];
        }

        usort($rowsAsistencia, static function($a, $b) {
            return strcasecmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
        });

        $encuentroDobleClase5 = ((string)($config['modulo'] ?? '') === 'consolidar' && $filtroProgramaInscripcion === 'universidad_vida');
        $totalClases = $encuentroDobleClase5 ? 6 : 5;
        $idsPersona = array_map(static function($row) {
            return (int)($row['id_persona'] ?? 0);
        }, $rowsAsistencia);
        $asistencias = [];
        foreach ($programasConsulta as $programaConsulta) {
            $asistenciasPrograma = $asistenciaModel->getAsistenciasPorPrograma($idsPersona, (string)$config['modulo'], $programaConsulta);
            foreach ($asistenciasPrograma as $idPersonaAsistencia => $clasesTmp) {
                $idPersonaAsistencia = (int)$idPersonaAsistencia;
                if (!isset($asistencias[$idPersonaAsistencia])) {
                    $asistencias[$idPersonaAsistencia] = [];
                }
                foreach ((array)$clasesTmp as $numeroClaseTmp => $valorClaseTmp) {
                    $numeroClaseTmp = (int)$numeroClaseTmp;
                    if ($numeroClaseTmp <= 0) {
                        continue;
                    }
                    $asistencias[$idPersonaAsistencia][$numeroClaseTmp] = !empty($asistencias[$idPersonaAsistencia][$numeroClaseTmp]) || !empty($valorClaseTmp);
                }
            }
        }

        $fechasClases = [];
        $fechasClasesHombres = [];
        $fechasClasesMujeres = [];
        for ($i = 1; $i <= $totalClases; $i++) {
            $fechasClases[$i] = '';
            $fechasClasesHombres[$i] = '';
            $fechasClasesMujeres[$i] = '';
        }
        foreach ($programasConsulta as $programaConsulta) {
            $fechasPrograma = $asistenciaModel->getFechasClases((string)$config['modulo'], $programaConsulta, $totalClases);
            $fechasProgramaHombres = $asistenciaModel->getFechasClases((string)$config['modulo'], $programaConsulta, $totalClases, 'hombres');
            $fechasProgramaMujeres = $asistenciaModel->getFechasClases((string)$config['modulo'], $programaConsulta, $totalClases, 'mujeres');
            for ($i = 1; $i <= $totalClases; $i++) {
                if (($fechasClases[$i] ?? '') === '' && !empty($fechasPrograma[$i])) {
                    $fechasClases[$i] = (string)$fechasPrograma[$i];
                }
                if (($fechasClasesHombres[$i] ?? '') === '' && !empty($fechasProgramaHombres[$i])) {
                    $fechasClasesHombres[$i] = (string)$fechasProgramaHombres[$i];
                }
                if (($fechasClasesMujeres[$i] ?? '') === '' && !empty($fechasProgramaMujeres[$i])) {
                    $fechasClasesMujeres[$i] = (string)$fechasProgramaMujeres[$i];
                }
            }
        }

        foreach ($rowsAsistencia as &$row) {
            $idPersona = (int)($row['id_persona'] ?? 0);
            $row['clases'] = [];
            for ($i = 1; $i <= $totalClases; $i++) {
                $row['clases'][$i] = !empty($asistencias[$idPersona][$i]);
            }
        }
        unset($row);

        if ($filtroFechaDesde !== '' || $filtroFechaHasta !== '') {
            $rowsAsistencia = array_values(array_filter($rowsAsistencia, static function($row) use ($fechasClases, $fechasClasesHombres, $fechasClasesMujeres, $totalClases, $filtroFechaDesde, $filtroFechaHasta) {
                $generoRegistro = strtolower(trim((string)($row['genero'] ?? '')));
                $esMujer = strpos($generoRegistro, 'mujer') !== false
                    || strpos($generoRegistro, 'femen') !== false
                    || in_array($generoRegistro, ['f', 'fem', 'female'], true);
                $mapaFechas = $esMujer ? $fechasClasesMujeres : $fechasClasesHombres;

                for ($i = 1; $i <= $totalClases; $i++) {
                    if (empty($row['clases'][$i])) {
                        continue;
                    }

                    $fechaClase = (string)($mapaFechas[$i] ?? ($fechasClases[$i] ?? ''));
                    if ($fechaClase === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaClase)) {
                        continue;
                    }

                    if ($filtroFechaDesde !== '' && $fechaClase < $filtroFechaDesde) {
                        continue;
                    }

                    if ($filtroFechaHasta !== '' && $fechaClase > $filtroFechaHasta) {
                        continue;
                    }

                    return true;
                }

                return false;
            }));
        }

        $resumenInscripciones = $inscripcionModel->getResumenProgramas();
        $tarjetasResumen = [];
        foreach ($config['resumen_programas'] as $programa) {
            $totalTarjeta = (int)($resumenInscripciones[$programa] ?? 0);
            if ((string)($config['modulo'] ?? '') === 'consolidar' && $programa === 'universidad_vida') {
                $totalTarjeta += (int)($resumenInscripciones['encuentro'] ?? 0);
            }
            $tarjetasResumen[] = [
                'programa' => $programa,
                'label' => $this->getProgramaEscuelaLabel($programa),
                'total' => $totalTarjeta,
            ];
        }

        return [
            'config_modulo' => $config,
            'vista_actual' => 'asistencias',
            'ministerios_disponibles' => $opcionesFiltro['ministerios_disponibles'],
            'lideres_disponibles' => $opcionesFiltro['lideres_disponibles'],
            'filtro_ministerio' => (string)$filtroMinisterio,
            'filtro_fecha_desde' => $filtroFechaDesde,
            'filtro_fecha_hasta' => $filtroFechaHasta,
            'programa_reporte' => $filtroProgramaInscripcion,
            'programa_reporte_label' => $this->getProgramaEscuelaLabel($filtroProgramaInscripcion),
            'filtro_insc_programa' => $filtroProgramaInscripcion,
            'programas_opciones' => $config['programas_opciones'],
            'tarjetas_resumen' => $tarjetasResumen,
            'total_clases' => $totalClases,
            'fechas_clases' => $fechasClases,
            'fechas_clases_hombres' => $fechasClasesHombres,
            'fechas_clases_mujeres' => $fechasClasesMujeres,
            'rows_asistencia' => $rowsAsistencia,
            'encuentro_doble_clase5' => $encuentroDobleClase5,
            'puede_marcar_asistencia' => $this->puedeMarcarAsistenciaEscuelasFormacion(),
            'puede_editar_fechas_asistencia' => $this->puedeEditarFechasEscuelasFormacion(),
        ];
    }

    private function exportarModuloFormacion($modulo) {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $data = $this->obtenerDatosModuloFormacion($modulo);
        $rows = array_map(static function($row) {
            return [
                $row['nombre'] ?? '',
                $row['ministerio'] ?? '',
                $row['lider'] ?? '',
            ];
        }, $data['reporte_pendientes']['rows'] ?? []);

        $filename = 'formacion_' . strtolower((string)($data['config_modulo']['modulo'] ?? 'modulo')) . '_' . strtolower((string)($data['programa_reporte'] ?? 'programa'));
        $this->exportCsv($filename, ['Persona', 'Ministerio', 'Lider'], $rows, false);
    }

    public function index() {
        require_once APP . '/Models/Persona.php';
        require_once APP . '/Models/Celula.php';
        require_once APP . '/Models/Ministerio.php';
        require_once APP . '/Models/Evento.php';
        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        require_once APP . '/Helpers/DataIsolation.php';

        $personaModel = new Persona();
        $celulaModel = new Celula();
        $ministerioModel = new Ministerio();
        $eventoModel = new Evento();
        $inscripcionModel = new EscuelaFormacionInscripcion();

        $filtroPersonas = DataIsolation::generarFiltroPersonas();
        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $filtroMinisterios = DataIsolation::generarFiltroMinisterios();
        $filtroEventos = DataIsolation::generarFiltroEventos();
        $resumenInscripciones = $inscripcionModel->getResumenProgramas();

        // La tarjeta principal de Consolidar debe reflejar exactamente lo mismo
        // que se muestra en el panel de Consolidar (Hombres + Mujeres visibles).
        $datosConsolidar = $this->obtenerDatosModuloFormacion('consolidar');
        $inscripcionesConsolidar = $datosConsolidar['inscripciones_publicas'] ?? [];
        $totalConsolidarPanel = 0;
        foreach ($inscripcionesConsolidar as $inscripcionConsolidar) {
            $generoRegistro = strtolower(trim((string)($inscripcionConsolidar['Genero'] ?? '')));
            $esMujer = strpos($generoRegistro, 'mujer') !== false
                || strpos($generoRegistro, 'femen') !== false
                || in_array($generoRegistro, ['f', 'fem', 'female'], true);
            $esHombre = strpos($generoRegistro, 'hombre') !== false
                || strpos($generoRegistro, 'mascul') !== false
                || in_array($generoRegistro, ['m', 'masc', 'male', 'h'], true);
            if ($esMujer || $esHombre) {
                $totalConsolidarPanel++;
            }
        }

        $data = [
            'totalPersonas' => count($personaModel->getAllActivosWithRole($filtroPersonas)),
            'totalCelulas' => count($celulaModel->getAllWithMemberCountAndRole($filtroCelulas)),
            'totalMinisterios' => count($ministerioModel->getAllWithMemberCountAndRole($filtroMinisterios)),
            'totalLideresCelula' => $personaModel->getTotalLideresCelulaWithRole($filtroPersonas),
            'eventosProximos' => $eventoModel->getUpcomingWithRole($filtroEventos),
            'totalConsolidar' => (int)$totalConsolidarPanel,
            'totalDiscipular' => (int)($resumenInscripciones['bautismo'] ?? 0)
                + (int)($resumenInscripciones['capacitacion_destino_nivel_1'] ?? 0)
                + (int)($resumenInscripciones['capacitacion_destino_nivel_2'] ?? 0)
                + (int)($resumenInscripciones['capacitacion_destino_nivel_3'] ?? 0),
        ];

        $this->view('home/dashboard', $data);
    }

    private function obtenerModulosMaterial(): array {
        return [
            'celulas' => [
                'clave' => 'celulas',
                'titulo' => 'Material de Celulas',
                'permiso' => 'materiales_celulas',
                'prefijo_archivo' => 'material_celulas',
                'ruta' => 'celulas/materiales',
                'icono' => 'bi bi-journal-bookmark-fill',
                'color' => '#fd7e14',
            ],
            'teens' => [
                'clave' => 'teens',
                'titulo' => 'Material Teens',
                'permiso' => 'teen',
                'prefijo_archivo' => 'material_teens',
                'ruta' => 'home/material/teens',
                'icono' => 'bi bi-emoji-sunglasses-fill',
                'color' => '#e83e8c',
            ],
            'universidad_vida' => [
                'clave' => 'universidad_vida',
                'titulo' => 'Material Universidad de la Vida',
                'permiso' => 'eventos',
                'prefijo_archivo' => 'material_universidad_vida',
                'ruta' => 'home/material/universidad-vida',
                'icono' => 'bi bi-mortarboard-fill',
                'color' => '#1e4a89',
            ],
            'capacitacion_destino' => [
                'clave' => 'capacitacion_destino',
                'titulo' => 'Material Capacitacion Destino',
                'permiso' => 'eventos',
                'prefijo_archivo' => 'material_capacitacion_destino',
                'ruta' => 'home/material/capacitacion-destino',
                'icono' => 'bi bi-signpost-split-fill',
                'color' => '#7a4e08',
            ],
        ];
    }

    private function puedeVerModuloMaterial(array $modulo): bool {
        return AuthController::esAdministrador() || AuthController::tienePermiso((string)$modulo['permiso'], 'ver');
    }

    private function puedeGestionarModuloMaterial(array $modulo): bool {
        if (AuthController::esAdministrador()) {
            return true;
        }

        $permiso = (string)$modulo['permiso'];
        return AuthController::tienePermiso($permiso, 'crear') || AuthController::tienePermiso($permiso, 'editar');
    }

    private function obtenerDirectorioModuloMaterial(array $modulo): string {
        return ROOT . '/public/uploads/material_hub/' . trim((string)$modulo['clave']);
    }

    private function contarArchivosMaterialCelulasExistente(): int {
        $directorio = ROOT . '/public/assets/celulas_materiales';
        $this->migrarMaterialesCelulasLegacy($directorio);

        if (!is_dir($directorio)) {
            return 0;
        }

        $total = 0;
        $archivos = @scandir($directorio) ?: [];
        foreach ($archivos as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }

            $ruta = $directorio . '/' . $archivo;
            if (!is_file($ruta)) {
                continue;
            }

            if (strtolower((string)pathinfo($archivo, PATHINFO_EXTENSION)) === 'pdf') {
                $total++;
            }
        }

        return $total;
    }

    private function contarArchivosMaterialTeensExistente(): int {
        require_once APP . '/Models/Teen.php';

        try {
            $teenModel = new Teen();
            $materiales = $teenModel->getAll();
            $total = 0;

            foreach ((array)$materiales as $material) {
                $archivosJson = (string)($material['archivos_pdf'] ?? '');
                if ($archivosJson === '') {
                    continue;
                }

                $archivos = json_decode($archivosJson, true);
                if (!is_array($archivos)) {
                    continue;
                }

                foreach ($archivos as $archivo) {
                    if (strtolower((string)pathinfo((string)$archivo, PATHINFO_EXTENSION)) === 'pdf') {
                        $total++;
                    }
                }
            }

            return $total;
        } catch (Throwable $e) {
            return 0;
        }
    }

    private function migrarMaterialesCelulasLegacy(string $directorioDestino): void {
        $directorioLegacy = ROOT . '/public/uploads/material_hub/celulas';

        if (!is_dir($directorioLegacy)) {
            return;
        }

        if (!is_dir($directorioDestino) && !@mkdir($directorioDestino, 0775, true) && !is_dir($directorioDestino)) {
            return;
        }

        $archivos = @scandir($directorioLegacy) ?: [];
        foreach ($archivos as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }

            if (strtolower((string)pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
                continue;
            }

            $origen = $directorioLegacy . '/' . $archivo;
            if (!is_file($origen)) {
                continue;
            }

            $destino = $directorioDestino . '/' . $archivo;
            if (!is_file($destino)) {
                @rename($origen, $destino);
                continue;
            }

            $base = (string)pathinfo($archivo, PATHINFO_FILENAME);
            $ext = (string)pathinfo($archivo, PATHINFO_EXTENSION);
            $i = 1;
            do {
                $destinoAlterno = $directorioDestino . '/' . $base . '_legacy_' . $i . '.' . $ext;
                $i++;
            } while (is_file($destinoAlterno));

            @rename($origen, $destinoAlterno);
        }
    }

    private function listarArchivosModuloMaterial(array $modulo): array {
        $directorio = $this->obtenerDirectorioModuloMaterial($modulo);
        if (!is_dir($directorio)) {
            return [];
        }

        $vistasPorArchivo = $this->obtenerConteoVistasMaterialModulo((string)($modulo['clave'] ?? ''));

        $items = [];
        $archivos = @scandir($directorio) ?: [];
        foreach ($archivos as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }

            $ruta = $directorio . '/' . $archivo;
            if (!is_file($ruta)) {
                continue;
            }

            $creadoTs = @filectime($ruta);
            if (!$creadoTs) {
                $creadoTs = @filemtime($ruta);
            }

            $items[] = [
                'nombre' => $archivo,
                'peso_kb' => round((int)@filesize($ruta) / 1024, 2),
                'creado_ts' => (int)$creadoTs,
                'url' => PUBLIC_URL . '?url=home/material/ver&modulo=' . rawurlencode((string)$modulo['clave']) . '&archivo=' . rawurlencode($archivo),
                'personas_vieron' => (int)($vistasPorArchivo[$archivo] ?? 0),
            ];
        }

        usort($items, static function($a, $b) {
            return ($b['creado_ts'] ?? 0) <=> ($a['creado_ts'] ?? 0);
        });

        return $items;
    }

    private function extraerInfoLoteMaterial(array $modulo, string $archivo): array {
        $base = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower((string)$modulo['prefijo_archivo']));
        $pattern = '/^' . preg_quote($base, '/') . '_(\d{8}_\d{6}_\d{4})_(\d+)\.[a-z0-9]{1,10}$/i';

        if (preg_match($pattern, $archivo, $match) === 1) {
            return [
                'lote_id' => (string)$match[1],
                'orden' => (int)$match[2],
            ];
        }

        return [
            'lote_id' => (string)pathinfo($archivo, PATHINFO_FILENAME),
            'orden' => 1,
        ];
    }

    private function formatearTituloTemaMaterial(string $loteId, int $creadoTs): string {
        if (preg_match('/^(\d{8})_(\d{6})_\d{4}$/', $loteId, $match) === 1) {
            $dt = DateTime::createFromFormat('Ymd_His', $match[1] . '_' . $match[2]);
            if ($dt instanceof DateTime) {
                return 'Tema ' . $dt->format('Y-m-d H:i');
            }
        }

        if ($creadoTs > 0) {
            return 'Tema ' . date('Y-m-d H:i', $creadoTs);
        }

        return 'Tema de material';
    }

    private function obtenerConteoVistasMaterialLote(string $modulo, array $archivos): int {
        $modulo = trim($modulo);
        $archivos = array_values(array_filter(array_map('basename', $archivos), static function($item) {
            return $item !== '';
        }));

        if ($modulo === '' || empty($archivos)) {
            return 0;
        }

        $this->asegurarTablaVistasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($archivos), '?'));
        $sql = "SELECT COUNT(DISTINCT Id_Persona) AS total FROM material_hub_vista WHERE Modulo = ? AND Archivo IN ({$placeholders})";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$modulo], $archivos));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    private function listarTemasModuloMaterial(array $modulo): array {
        $archivos = $this->listarArchivosModuloMaterial($modulo);
        if (empty($archivos)) {
            return [];
        }

        $metadatosTemas = $this->obtenerMetadatosTemasMaterialHub((string)($modulo['clave'] ?? ''));

        $temas = [];
        foreach ($archivos as $archivo) {
            $nombre = (string)($archivo['nombre'] ?? '');
            if ($nombre === '') {
                continue;
            }

            $loteInfo = $this->extraerInfoLoteMaterial($modulo, $nombre);
            $loteId = (string)($loteInfo['lote_id'] ?? '');
            if ($loteId === '') {
                $loteId = (string)pathinfo($nombre, PATHINFO_FILENAME);
            }

            if (!isset($temas[$loteId])) {
                $temas[$loteId] = [
                    'lote_id' => $loteId,
                    'creado_ts' => (int)($archivo['creado_ts'] ?? 0),
                    'archivos' => [],
                ];
            }

            $temas[$loteId]['creado_ts'] = max(
                (int)($temas[$loteId]['creado_ts'] ?? 0),
                (int)($archivo['creado_ts'] ?? 0)
            );

            $archivo['orden_lote'] = (int)($loteInfo['orden'] ?? 1);
            $temas[$loteId]['archivos'][] = $archivo;
        }

        foreach ($temas as $loteId => $tema) {
            usort($tema['archivos'], static function($a, $b) {
                $ordenCmp = ((int)($a['orden_lote'] ?? 1)) <=> ((int)($b['orden_lote'] ?? 1));
                if ($ordenCmp !== 0) {
                    return $ordenCmp;
                }
                return ((int)($a['creado_ts'] ?? 0)) <=> ((int)($b['creado_ts'] ?? 0));
            });

            $nombres = array_map(static function($item) {
                return (string)($item['nombre'] ?? '');
            }, $tema['archivos']);

            $tema['total_archivos'] = count($tema['archivos']);
            $tema['personas_vieron'] = $this->obtenerConteoVistasMaterialLote((string)($modulo['clave'] ?? ''), $nombres);
            $meta = $metadatosTemas[(string)$loteId] ?? [];
            $tituloMeta = trim((string)($meta['titulo'] ?? ''));
            $tema['titulo'] = $tituloMeta !== ''
                ? $tituloMeta
                : $this->formatearTituloTemaMaterial((string)$loteId, (int)($tema['creado_ts'] ?? 0));
            $tema['descripcion'] = trim((string)($meta['descripcion'] ?? ''));
            $tema['categoria'] = $this->normalizarCategoriaMaterialTema(
                (string)($modulo['clave'] ?? ''),
                (string)($meta['categoria'] ?? 'general')
            );
            $tema['nivel'] = $this->normalizarNivelMaterialTema(
                (string)($modulo['clave'] ?? ''),
                (int)($meta['nivel'] ?? 0)
            );
            $tema['modulo_numero'] = $this->normalizarModuloMaterialTema(
                (string)($modulo['clave'] ?? ''),
                (int)($tema['nivel'] ?? 0),
                (int)($meta['modulo_numero'] ?? 0)
            );
            $tema['leccion'] = trim((string)($meta['leccion'] ?? ''));

            $fechaMeta = trim((string)($meta['fecha_creacion'] ?? ''));
            if ($fechaMeta !== '') {
                $tsMeta = strtotime($fechaMeta);
                if ($tsMeta !== false && $tsMeta > 0) {
                    $tema['creado_ts'] = (int)$tsMeta;
                }
            }

            foreach ($tema['archivos'] as &$archivoTema) {
                unset($archivoTema['orden_lote']);
            }
            unset($archivoTema);

            $temas[$loteId] = $tema;
        }

        uasort($temas, static function($a, $b) {
            return ((int)($b['creado_ts'] ?? 0)) <=> ((int)($a['creado_ts'] ?? 0));
        });

        return array_values($temas);
    }

    private function moduloMaterialTieneSubmodulos(string $modulo): bool {
        return in_array(trim($modulo), ['universidad_vida', 'capacitacion_destino'], true);
    }

    private function obtenerConfiguracionNivelesCapacitacionDestino(): array {
        return [
            1 => [1, 2],
            2 => [3, 4],
            3 => [5, 6],
        ];
    }

    private function normalizarNivelMaterialTema(string $modulo, $nivel): int {
        if (trim($modulo) !== 'capacitacion_destino') {
            return 0;
        }

        $nivel = (int)$nivel;
        return isset($this->obtenerConfiguracionNivelesCapacitacionDestino()[$nivel]) ? $nivel : 0;
    }

    private function normalizarModuloMaterialTema(string $modulo, int $nivel, $moduloNumero): int {
        if (trim($modulo) !== 'capacitacion_destino') {
            return 0;
        }

        $moduloNumero = (int)$moduloNumero;
        $config = $this->obtenerConfiguracionNivelesCapacitacionDestino();

        if (!isset($config[$nivel]) || !in_array($moduloNumero, $config[$nivel], true)) {
            return 0;
        }

        return $moduloNumero;
    }

    private function normalizarCategoriaMaterialTema(string $modulo, string $categoria): string {
        $modulo = trim($modulo);
        $categoria = strtolower(trim($categoria));

        if (!$this->moduloMaterialTieneSubmodulos($modulo)) {
            return 'general';
        }

        if (!in_array($categoria, ['clase', 'profesor'], true)) {
            // Compatibilidad hacia atras para temas creados antes de los submodulos.
            return 'clase';
        }

        return $categoria;
    }

    private function obtenerDetalleVistasMaterialLote(string $modulo, array $archivos): array {
        $modulo = trim($modulo);
        $archivos = array_values(array_filter(array_map('basename', $archivos), static function($item) {
            return $item !== '';
        }));

        if ($modulo === '' || empty($archivos)) {
            return [];
        }

        $this->asegurarTablaVistasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($archivos), '?'));
        $sql = "SELECT
                    mv.Id_Persona,
                    p.Nombre,
                    p.Apellido,
                    p.Telefono,
                    m.Nombre_Ministerio,
                    SUM(mv.Total_Vistas) AS Total_Vistas,
                    MIN(mv.Fecha_Primera_Vista) AS Fecha_Primera_Vista,
                    MAX(mv.Fecha_Ultima_Vista) AS Fecha_Ultima_Vista
                FROM material_hub_vista mv
                LEFT JOIN persona p ON mv.Id_Persona = p.Id_Persona
                LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                WHERE mv.Modulo = ? AND mv.Archivo IN ({$placeholders})
                GROUP BY mv.Id_Persona, p.Nombre, p.Apellido, p.Telefono, m.Nombre_Ministerio
                ORDER BY MAX(mv.Fecha_Ultima_Vista) DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_merge([$modulo], $archivos));
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function asegurarTablaVistasMaterialHub(): void {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS material_hub_vista (
                    Id_Vista INT AUTO_INCREMENT PRIMARY KEY,
                    Modulo VARCHAR(80) NOT NULL,
                    Archivo VARCHAR(255) NOT NULL,
                    Id_Persona INT NOT NULL,
                    Total_Vistas INT NOT NULL DEFAULT 1,
                    Fecha_Primera_Vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    Fecha_Ultima_Vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_modulo_archivo_persona (Modulo, Archivo, Id_Persona),
                    KEY idx_modulo_archivo (Modulo, Archivo),
                    KEY idx_persona (Id_Persona)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    }

    private function asegurarTablaTemasMaterialHub(): void {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS material_hub_tema (
                    Id_Tema INT AUTO_INCREMENT PRIMARY KEY,
                    Modulo VARCHAR(80) NOT NULL,
                    Lote_Id VARCHAR(120) NOT NULL,
                    Titulo VARCHAR(255) NOT NULL,
                    Descripcion TEXT NULL,
                    Categoria VARCHAR(30) NOT NULL DEFAULT 'general',
                    Fecha_Creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_modulo_lote (Modulo, Lote_Id),
                    KEY idx_modulo (Modulo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);

        try {
            $columna = $pdo->query("SHOW COLUMNS FROM material_hub_tema LIKE 'Categoria'");
            $existeCategoria = $columna ? $columna->fetch(PDO::FETCH_ASSOC) : false;
            if (!$existeCategoria) {
                $pdo->exec("ALTER TABLE material_hub_tema ADD COLUMN Categoria VARCHAR(30) NOT NULL DEFAULT 'general' AFTER Descripcion");
            }
        } catch (Throwable $e) {
            // Evitar bloquear la carga por compatibilidad de esquema.
        }

        try {
            $columnaNivel = $pdo->query("SHOW COLUMNS FROM material_hub_tema LIKE 'Nivel'");
            $existeNivel = $columnaNivel ? $columnaNivel->fetch(PDO::FETCH_ASSOC) : false;
            if (!$existeNivel) {
                $pdo->exec("ALTER TABLE material_hub_tema ADD COLUMN Nivel TINYINT UNSIGNED NULL AFTER Categoria");
            }
        } catch (Throwable $e) {
            // Compatibilidad hacia atras.
        }

        try {
            $columnaModuloNumero = $pdo->query("SHOW COLUMNS FROM material_hub_tema LIKE 'Modulo_Numero'");
            $existeModuloNumero = $columnaModuloNumero ? $columnaModuloNumero->fetch(PDO::FETCH_ASSOC) : false;
            if (!$existeModuloNumero) {
                $pdo->exec("ALTER TABLE material_hub_tema ADD COLUMN Modulo_Numero TINYINT UNSIGNED NULL AFTER Nivel");
            }
        } catch (Throwable $e) {
            // Compatibilidad hacia atras.
        }

        try {
            $columnaLeccion = $pdo->query("SHOW COLUMNS FROM material_hub_tema LIKE 'Leccion'");
            $existeLeccion = $columnaLeccion ? $columnaLeccion->fetch(PDO::FETCH_ASSOC) : false;
            if (!$existeLeccion) {
                $pdo->exec("ALTER TABLE material_hub_tema ADD COLUMN Leccion VARCHAR(120) NULL AFTER Modulo_Numero");
            }
        } catch (Throwable $e) {
            // Compatibilidad hacia atras.
        }
    }

    private function asegurarTablaConfigModuloMaterial(): void {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS material_hub_modulo_config (
                    Id_Config INT AUTO_INCREMENT PRIMARY KEY,
                    Modulo VARCHAR(80) NOT NULL,
                    Nivel TINYINT UNSIGNED NOT NULL,
                    Modulo_Numero TINYINT UNSIGNED NOT NULL,
                    Profesor_Nombre VARCHAR(255) NOT NULL DEFAULT '',
                    UNIQUE KEY uq_modulo_nivel_numero (Modulo, Nivel, Modulo_Numero)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    }

    private function guardarProfesorNombreModulo(string $modulo, int $nivel, int $moduloNumero, string $profesorNombre): void {
        $modulo = trim($modulo);
        if ($modulo === '' || $nivel <= 0 || $moduloNumero <= 0) {
            return;
        }

        $this->asegurarTablaConfigModuloMaterial();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "INSERT INTO material_hub_modulo_config (Modulo, Nivel, Modulo_Numero, Profesor_Nombre)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE Profesor_Nombre = VALUES(Profesor_Nombre)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$modulo, $nivel, $moduloNumero, trim($profesorNombre)]);
    }

    private function obtenerProfesoresModulos(string $modulo): array {
        $modulo = trim($modulo);
        if ($modulo === '') {
            return [];
        }

        $this->asegurarTablaConfigModuloMaterial();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT Nivel, Modulo_Numero, Profesor_Nombre FROM material_hub_modulo_config WHERE Modulo = ?");
        $stmt->execute([$modulo]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $key = (int)($row['Nivel'] ?? 0) . '_' . (int)($row['Modulo_Numero'] ?? 0);
            $map[$key] = (string)($row['Profesor_Nombre'] ?? '');
        }

        return $map;
    }

    private function guardarTemaMaterialHub(string $modulo, string $loteId, string $titulo, string $descripcion = '', string $categoria = 'general', int $nivel = 0, int $moduloNumero = 0, string $leccion = ''): void {
        $modulo = trim($modulo);
        $loteId = trim($loteId);
        $titulo = trim($titulo);
        $descripcion = trim($descripcion);
        $categoria = $this->normalizarCategoriaMaterialTema($modulo, $categoria);
        $nivel = $this->normalizarNivelMaterialTema($modulo, $nivel);
        $moduloNumero = $this->normalizarModuloMaterialTema($modulo, $nivel, $moduloNumero);
        $leccion = trim($leccion);

        if ($modulo === '' || $loteId === '' || $titulo === '') {
            return;
        }

        $this->asegurarTablaTemasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "INSERT INTO material_hub_tema (Modulo, Lote_Id, Titulo, Descripcion, Categoria, Nivel, Modulo_Numero, Leccion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                Titulo = VALUES(Titulo),
                Descripcion = VALUES(Descripcion),
                Categoria = VALUES(Categoria),
                Nivel = VALUES(Nivel),
                Modulo_Numero = VALUES(Modulo_Numero),
                Leccion = VALUES(Leccion)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $modulo,
            $loteId,
            $titulo,
            $descripcion !== '' ? $descripcion : null,
            $categoria,
            $nivel > 0 ? $nivel : null,
            $moduloNumero > 0 ? $moduloNumero : null,
            $leccion !== '' ? $leccion : null,
        ]);
    }

    private function obtenerMetadatosTemasMaterialHub(string $modulo): array {
        $modulo = trim($modulo);
        if ($modulo === '') {
            return [];
        }

        $this->asegurarTablaTemasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT Lote_Id, Titulo, Descripcion, Categoria, Nivel, Modulo_Numero, Leccion, Fecha_Creacion FROM material_hub_tema WHERE Modulo = ?");
        $stmt->execute([$modulo]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $loteId = (string)($row['Lote_Id'] ?? '');
            if ($loteId === '') {
                continue;
            }

            $map[$loteId] = [
                'titulo' => (string)($row['Titulo'] ?? ''),
                'descripcion' => (string)($row['Descripcion'] ?? ''),
                'categoria' => (string)($row['Categoria'] ?? 'general'),
                'nivel' => (int)($row['Nivel'] ?? 0),
                'modulo_numero' => (int)($row['Modulo_Numero'] ?? 0),
                'leccion' => (string)($row['Leccion'] ?? ''),
                'fecha_creacion' => (string)($row['Fecha_Creacion'] ?? ''),
            ];
        }

        return $map;
    }

    private function obtenerConteoVistasMaterialModulo(string $modulo): array {
        $modulo = trim($modulo);
        if ($modulo === '') {
            return [];
        }

        $this->asegurarTablaVistasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $stmt = $pdo->prepare("SELECT Archivo, COUNT(DISTINCT Id_Persona) AS Personas_Vieron FROM material_hub_vista WHERE Modulo = ? GROUP BY Archivo");
        $stmt->execute([$modulo]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $archivo = (string)($row['Archivo'] ?? '');
            if ($archivo === '') {
                continue;
            }
            $map[$archivo] = (int)($row['Personas_Vieron'] ?? 0);
        }

        return $map;
    }

    private function registrarVistaMaterialHub(string $modulo, string $archivo, int $idPersona): void {
        $modulo = trim($modulo);
        $archivo = basename($archivo);
        if ($modulo === '' || $archivo === '' || $idPersona <= 0) {
            return;
        }

        $this->asegurarTablaVistasMaterialHub();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "INSERT INTO material_hub_vista (Modulo, Archivo, Id_Persona, Total_Vistas)
                VALUES (?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE Total_Vistas = Total_Vistas + 1, Fecha_Ultima_Vista = NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$modulo, $archivo, $idPersona]);
    }

    private function guardarArchivoModuloMaterial(array $modulo, array $archivo, string $loteId = '', int $indice = 1): void {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo.');
        }

        $tamano = (int)($archivo['size'] ?? 0);
        if ($tamano <= 0) {
            throw new Exception('Archivo vacio o invalido.');
        }

        if ($tamano > 20 * 1024 * 1024) {
            throw new Exception('El archivo supera el maximo de 20MB.');
        }

        $nombreOriginal = trim((string)($archivo['name'] ?? 'material.bin'));
        $extension = strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extension = preg_replace('/[^a-z0-9]/', '', $extension);
        if ($extension === '') {
            $extension = 'bin';
        }
        $extension = substr($extension, 0, 10);

        $directorio = $this->obtenerDirectorioModuloMaterial($modulo);
        if (!is_dir($directorio) && !@mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new Exception('No se pudo crear el directorio de materiales.');
        }

        $base = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower((string)$modulo['prefijo_archivo']));
        if ($loteId !== '') {
            $indiceSeguro = max(1, $indice);
            $nombreFinal = $base . '_' . $loteId . '_' . $indiceSeguro . '.' . $extension;
            while (is_file($directorio . '/' . $nombreFinal)) {
                $indiceSeguro++;
                $nombreFinal = $base . '_' . $loteId . '_' . $indiceSeguro . '.' . $extension;
            }
        } else {
            $nombreFinal = $base . '_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $extension;
        }
        $destino = $directorio . '/' . $nombreFinal;

        if (!@move_uploaded_file((string)($archivo['tmp_name'] ?? ''), $destino)) {
            throw new Exception('No se pudo guardar el archivo en el servidor.');
        }
    }

    private function guardarArchivosModuloMaterial(array $modulo, array $archivos, string $titulo = '', string $descripcion = '', string $categoria = 'general', int $nivel = 0, int $moduloNumero = 0, string $leccion = ''): array {
        $count = 0;
        $loteId = date('Ymd_His') . '_' . mt_rand(1000, 9999);
        $indice = 0;

        if (!isset($archivos['name'])) {
            throw new Exception('Debes seleccionar al menos un archivo.');
        }

        if (is_array($archivos['name'])) {
            $total = count($archivos['name']);
            for ($i = 0; $i < $total; $i++) {
                if ((int)($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $indice++;

                $archivo = [
                    'name' => $archivos['name'][$i] ?? '',
                    'type' => $archivos['type'][$i] ?? '',
                    'tmp_name' => $archivos['tmp_name'][$i] ?? '',
                    'error' => $archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $archivos['size'][$i] ?? 0,
                ];

                $this->guardarArchivoModuloMaterial($modulo, $archivo, $loteId, $indice);
                $count++;
            }
        } else {
            $this->guardarArchivoModuloMaterial($modulo, $archivos, $loteId, 1);
            $count = 1;
        }

        if ($count <= 0) {
            throw new Exception('No se detectaron archivos válidos para subir.');
        }

        $titulo = trim($titulo);
        if ($titulo === '') {
            $titulo = $this->formatearTituloTemaMaterial($loteId, time());
        }
        $this->guardarTemaMaterialHub((string)($modulo['clave'] ?? ''), $loteId, $titulo, $descripcion, $categoria, $nivel, $moduloNumero, $leccion);

        return [
            'cantidad' => $count,
            'lote_id' => $loteId,
        ];
    }

    private function obtenerSiguienteIndiceLoteMaterial(array $modulo, string $loteId): int {
        $loteId = trim($loteId);
        if ($loteId === '') {
            return 1;
        }

        $directorio = $this->obtenerDirectorioModuloMaterial($modulo);
        if (!is_dir($directorio)) {
            return 1;
        }

        $base = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower((string)$modulo['prefijo_archivo']));
        $patron = '/^' . preg_quote($base . '_' . $loteId . '_', '/') . '(\d+)\.[a-z0-9]+$/i';
        $maxIndice = 0;

        $items = @scandir($directorio);
        if (!is_array($items)) {
            return 1;
        }

        foreach ($items as $item) {
            if (!is_string($item) || $item === '.' || $item === '..') {
                continue;
            }
            if (preg_match($patron, $item, $matches)) {
                $indice = (int)($matches[1] ?? 0);
                if ($indice > $maxIndice) {
                    $maxIndice = $indice;
                }
            }
        }

        return $maxIndice + 1;
    }

    private function agregarArchivosATemaMaterial(array $modulo, string $loteId, array $archivos): int {
        $loteId = trim($loteId);
        if ($loteId === '') {
            throw new Exception('Tema invalido para agregar archivos.');
        }

        if (!isset($archivos['name'])) {
            throw new Exception('Debes seleccionar al menos un archivo.');
        }

        $count = 0;
        $indice = $this->obtenerSiguienteIndiceLoteMaterial($modulo, $loteId);

        if (is_array($archivos['name'])) {
            $total = count($archivos['name']);
            for ($i = 0; $i < $total; $i++) {
                if ((int)($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $archivo = [
                    'name' => $archivos['name'][$i] ?? '',
                    'type' => $archivos['type'][$i] ?? '',
                    'tmp_name' => $archivos['tmp_name'][$i] ?? '',
                    'error' => $archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $archivos['size'][$i] ?? 0,
                ];

                $this->guardarArchivoModuloMaterial($modulo, $archivo, $loteId, $indice);
                $count++;
                $indice++;
            }
        } else {
            $this->guardarArchivoModuloMaterial($modulo, $archivos, $loteId, $indice);
            $count = 1;
        }

        if ($count <= 0) {
            throw new Exception('No se detectaron archivos válidos para subir.');
        }

        return $count;
    }

    private function eliminarTemaMaterialHub(array $modulo, string $loteId): int {
        $loteId = trim($loteId);
        if ($loteId === '') {
            return 0;
        }

        $temas = $this->listarTemasModuloMaterial($modulo);
        $nombresArchivos = [];

        foreach ($temas as $tema) {
            if ((string)($tema['lote_id'] ?? '') !== $loteId) {
                continue;
            }
            foreach ((array)($tema['archivos'] ?? []) as $archivo) {
                $nombre = (string)($archivo['nombre'] ?? '');
                if ($nombre !== '') {
                    $this->eliminarArchivoModuloMaterial($modulo, $nombre);
                    $nombresArchivos[] = $nombre;
                }
            }
            break;
        }

        global $pdo;
        if ($pdo instanceof PDO) {
            $claveMod = (string)($modulo['clave'] ?? '');
            $stmt = $pdo->prepare("DELETE FROM material_hub_tema WHERE Modulo = ? AND Lote_Id = ?");
            $stmt->execute([$claveMod, $loteId]);

            if (!empty($nombresArchivos)) {
                $placeholders = implode(',', array_fill(0, count($nombresArchivos), '?'));
                $stmt = $pdo->prepare("DELETE FROM material_hub_vista WHERE Modulo = ? AND Archivo IN ({$placeholders})");
                $stmt->execute(array_merge([$claveMod], $nombresArchivos));
            }
        }

        return count($nombresArchivos);
    }

    private function eliminarArchivoModuloMaterial(array $modulo, $archivo): bool {
        $archivoRaw = (string)$archivo;
        $archivo = basename($archivoRaw);
        if ($archivo === '' || $archivo !== $archivoRaw) {
            return false;
        }

        $ruta = $this->obtenerDirectorioModuloMaterial($modulo) . '/' . $archivo;
        if (!is_file($ruta)) {
            return false;
        }

        return @unlink($ruta);
    }

    private function construirRutaMaterialConContexto(array $modulo, array $contexto = [], string $mensaje = '', string $tipo = 'success'): string {
        $rutaBase = (string)($modulo['ruta'] ?? 'home/material');

        if ((string)($modulo['clave'] ?? '') === 'capacitacion_destino') {
            $nivel = (int)($contexto['nivel'] ?? 0);
            $moduloNumero = (int)($contexto['modulo'] ?? 0);
            $categoria = strtolower(trim((string)($contexto['categoria'] ?? '')));
            $leccion = trim((string)($contexto['leccion'] ?? ''));
            $openLote = trim((string)($contexto['open_lote'] ?? ''));
            $openPanel = strtolower(trim((string)($contexto['open_panel'] ?? '')));

            if ($nivel > 0) {
                $rutaBase .= '&cap_nivel=' . $nivel;
            }
            if ($moduloNumero > 0) {
                $rutaBase .= '&cap_modulo=' . $moduloNumero;
            }
            if ($categoria === 'clase' || $categoria === 'profesor') {
                $rutaBase .= '&cap_categoria=' . rawurlencode($categoria);
            }
            if ($leccion !== '') {
                $rutaBase .= '&cap_leccion=' . rawurlencode($leccion);
            }
            if ($openLote !== '') {
                $rutaBase .= '&cap_open_lote=' . rawurlencode($openLote);
            }
            if (in_array($openPanel, ['editar', 'archivos', 'agregar'], true)) {
                $rutaBase .= '&cap_open_panel=' . rawurlencode($openPanel);
            }
        }

        if ($mensaje !== '') {
            $rutaBase .= '&mensaje=' . urlencode($mensaje);
        }
        if ($tipo !== '') {
            $rutaBase .= '&tipo=' . urlencode($tipo);
        }

        return $rutaBase;
    }

    private function renderDetalleMaterial(string $moduloActual): void {
        $modulos = $this->obtenerModulosMaterial();
        $modulosVisibles = array_filter($modulos, function($modulo) {
            return $this->puedeVerModuloMaterial($modulo);
        });

        if (empty($modulosVisibles)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if (!isset($modulosVisibles[$moduloActual])) {
            $this->redirect('home/material');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = trim((string)($_POST['accion'] ?? ''));
            $moduloPost = trim((string)($_POST['modulo'] ?? ''));

            if (!isset($modulosVisibles[$moduloPost])) {
                $this->redirect((string)($modulosVisibles[$moduloActual]['ruta'] ?? 'home/material') . '&mensaje=' . urlencode('Modulo de material no valido.') . '&tipo=error');
            }

            $moduloSeleccionado = $modulosVisibles[$moduloPost];
            $contextoRetorno = [
                'nivel' => (int)($_POST['contexto_nivel'] ?? $_POST['nivel'] ?? 0),
                'modulo' => (int)($_POST['contexto_modulo'] ?? $_POST['modulo_numero'] ?? 0),
                'categoria' => trim((string)($_POST['contexto_categoria'] ?? $_POST['categoria'] ?? '')),
                'leccion' => trim((string)($_POST['contexto_leccion'] ?? $_POST['leccion'] ?? '')),
                'open_lote' => trim((string)($_POST['contexto_open_lote'] ?? '')),
                'open_panel' => trim((string)($_POST['contexto_open_panel'] ?? '')),
            ];

            if (!$this->puedeGestionarModuloMaterial($moduloSeleccionado)) {
                $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, 'No tienes permiso para gestionar este material.', 'error'));
            }

            try {
                if ($accion === 'subir') {
                    if (!isset($_FILES['material_pdf'])) {
                        throw new Exception('Debes seleccionar al menos un archivo.');
                    }
                    $tituloTema = trim((string)($_POST['titulo'] ?? ''));
                    $descripcionTema = trim((string)($_POST['descripcion'] ?? ''));
                    $categoriaTema = trim((string)($_POST['categoria'] ?? 'general'));
                    $nivelTema = (int)($_POST['nivel'] ?? 0);
                    $moduloNumeroTema = (int)($_POST['modulo_numero'] ?? 0);
                    $leccionTema = trim((string)($_POST['leccion'] ?? ''));
                    if ($tituloTema === '') {
                        throw new Exception('El titulo del modulo es obligatorio.');
                    }

                    if ((string)($moduloSeleccionado['clave'] ?? '') === 'capacitacion_destino') {
                        $nivelValido = $this->normalizarNivelMaterialTema('capacitacion_destino', $nivelTema);
                        $moduloValido = $this->normalizarModuloMaterialTema('capacitacion_destino', $nivelValido, $moduloNumeroTema);
                        if ($nivelValido <= 0 || $moduloValido <= 0) {
                            throw new Exception('Selecciona una combinación válida de nivel y módulo para Capacitación Destino.');
                        }
                        if ($leccionTema === '') {
                            throw new Exception('La lección es obligatoria para Capacitación Destino (ej: Lección 1).');
                        }
                    }

                    $resultadoCarga = $this->guardarArchivosModuloMaterial($moduloSeleccionado, $_FILES['material_pdf'], $tituloTema, $descripcionTema, $categoriaTema, $nivelTema, $moduloNumeroTema, $leccionTema);
                    $cantidadSubida = (int)($resultadoCarga['cantidad'] ?? 0);
                    $mensajeCarga = $cantidadSubida > 1
                        ? 'Material creado en una sola carga con ' . $cantidadSubida . ' archivos.'
                        : 'Material creado correctamente con 1 archivo.';
                    $contextoRetorno['nivel'] = $nivelTema;
                    $contextoRetorno['modulo'] = $moduloNumeroTema;
                    $contextoRetorno['categoria'] = $categoriaTema;
                    $contextoRetorno['leccion'] = $leccionTema;
                    $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, $mensajeCarga, 'success'));
                }

                if ($accion === 'editar_tema') {
                    $loteId = trim((string)($_POST['lote_id'] ?? ''));
                    $tituloTema = trim((string)($_POST['titulo'] ?? ''));
                    $descripcionTema = trim((string)($_POST['descripcion'] ?? ''));
                    $categoriaTema = trim((string)($_POST['categoria'] ?? 'general'));
                    $nivelTema = (int)($_POST['nivel'] ?? 0);
                    $moduloNumeroTema = (int)($_POST['modulo_numero'] ?? 0);
                    $leccionTema = trim((string)($_POST['leccion'] ?? ''));

                    if ($loteId === '') {
                        throw new Exception('Tema invalido para editar.');
                    }

                    if ($tituloTema === '') {
                        throw new Exception('El titulo del modulo es obligatorio.');
                    }

                    if ((string)($moduloSeleccionado['clave'] ?? '') === 'capacitacion_destino') {
                        $nivelValido = $this->normalizarNivelMaterialTema('capacitacion_destino', $nivelTema);
                        $moduloValido = $this->normalizarModuloMaterialTema('capacitacion_destino', $nivelValido, $moduloNumeroTema);
                        if ($nivelValido <= 0 || $moduloValido <= 0) {
                            throw new Exception('Selecciona una combinación válida de nivel y módulo para Capacitación Destino.');
                        }
                        if ($leccionTema === '') {
                            throw new Exception('La lección es obligatoria para Capacitación Destino (ej: Lección 1).');
                        }
                    }

                    $this->guardarTemaMaterialHub((string)($moduloSeleccionado['clave'] ?? ''), $loteId, $tituloTema, $descripcionTema, $categoriaTema, $nivelTema, $moduloNumeroTema, $leccionTema);
                    $contextoRetorno['nivel'] = $nivelTema;
                    $contextoRetorno['modulo'] = $moduloNumeroTema;
                    $contextoRetorno['categoria'] = $categoriaTema;
                    $contextoRetorno['leccion'] = $leccionTema;
                    $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, 'Material editado correctamente.', 'success'));
                }

                if ($accion === 'guardar_profesor_modulo') {
                    $nivelProf = (int)($_POST['nivel'] ?? 0);
                    $moduloNumProf = (int)($_POST['modulo_numero'] ?? 0);
                    $profesorNombrePost = trim((string)($_POST['profesor_nombre'] ?? ''));

                    if ($nivelProf <= 0 || $moduloNumProf <= 0) {
                        throw new Exception('Nivel o módulo inválido.');
                    }

                    $this->guardarProfesorNombreModulo((string)($moduloSeleccionado['clave'] ?? ''), $nivelProf, $moduloNumProf, $profesorNombrePost);
                    $contextoRetorno['nivel'] = $nivelProf;
                    $contextoRetorno['modulo'] = $moduloNumProf;
                    $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, 'Profesor guardado correctamente.', 'success'));
                }

                if ($accion === 'guardar_profesor_modulo_grupo') {
                    $moduloGrupo = (int)($_POST['modulo_grupo'] ?? 0);
                    $profesorNombrePost = trim((string)($_POST['profesor_nombre'] ?? ''));

                    if ((string)($moduloSeleccionado['clave'] ?? '') !== 'capacitacion_destino') {
                        throw new Exception('Esta acción solo aplica para Capacitación Destino.');
                    }

                    $configCap = $this->obtenerConfiguracionNivelesCapacitacionDestino();
                    $modulosDelGrupo = (array)($configCap[$moduloGrupo] ?? []);
                    if ($moduloGrupo <= 0 || empty($modulosDelGrupo)) {
                        throw new Exception('Módulo inválido.');
                    }

                    foreach ($modulosDelGrupo as $moduloNumeroTmp) {
                        $this->guardarProfesorNombreModulo(
                            (string)($moduloSeleccionado['clave'] ?? ''),
                            $moduloGrupo,
                            (int)$moduloNumeroTmp,
                            $profesorNombrePost
                        );
                    }

                    $contextoRetorno['nivel'] = $moduloGrupo;
                    $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, 'Profesor del módulo guardado correctamente.', 'success'));
                }

                if ($accion === 'agregar_archivos_tema') {
                    if (!isset($_FILES['material_pdf'])) {
                        throw new Exception('Debes seleccionar al menos un archivo.');
                    }

                    $loteId = trim((string)($_POST['lote_id'] ?? ''));
                    $cantidadSubida = $this->agregarArchivosATemaMaterial($moduloSeleccionado, $loteId, $_FILES['material_pdf']);
                    $mensajeCarga = $cantidadSubida > 1
                        ? 'Se agregaron ' . $cantidadSubida . ' archivos al tema.'
                        : 'Se agregó 1 archivo al tema.';
                    $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, $mensajeCarga, 'success'));
                }

                if ($accion === 'eliminar') {
                    $archivo = (string)($_POST['archivo'] ?? '');
                    if (!$this->eliminarArchivoModuloMaterial($moduloSeleccionado, $archivo)) {
                        throw new Exception('No se pudo eliminar el archivo.');
                    }
                    $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, 'Archivo eliminado correctamente.', 'success'));
                }

                if ($accion === 'eliminar_tema') {
                    $loteId = trim((string)($_POST['lote_id'] ?? ''));
                    if ($loteId === '') {
                        throw new Exception('Tema inválido para eliminar.');
                    }
                    $cant = $this->eliminarTemaMaterialHub($moduloSeleccionado, $loteId);
                    $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, 'Clase eliminada correctamente (' . $cant . ' archivo(s) borrados).', 'success'));
                }

                throw new Exception('Accion no valida.');
            } catch (Exception $e) {
                $this->redirect($this->construirRutaMaterialConContexto($moduloSeleccionado, $contextoRetorno, $e->getMessage(), 'error'));
            }
        }

        $modulo = $modulosVisibles[$moduloActual];
        $temas = $this->listarTemasModuloMaterial($modulo);
        $totalArchivos = 0;
        foreach ($temas as $tema) {
            $totalArchivos += (int)($tema['total_archivos'] ?? 0);
        }

        $this->view('home/material_detalle', [
            'modulo' => $modulo,
            'temas' => $temas,
            'total_archivos' => $totalArchivos,
            'tiene_submodulos' => $this->moduloMaterialTieneSubmodulos((string)($modulo['clave'] ?? '')),
            'config_capacitacion_destino' => $this->obtenerConfiguracionNivelesCapacitacionDestino(),
            'profesores_modulos' => (string)($modulo['clave'] ?? '') === 'capacitacion_destino'
                ? $this->obtenerProfesoresModulos('capacitacion_destino')
                : [],
            'puede_gestionar' => $this->puedeGestionarModuloMaterial($modulo),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'tipo' => (string)($_GET['tipo'] ?? ''),
        ]);
    }

    public function material() {
        $modulos = $this->obtenerModulosMaterial();
        $modulosVisibles = array_filter($modulos, function($modulo) {
            return $this->puedeVerModuloMaterial($modulo);
        });

        if (empty($modulosVisibles)) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $modulosData = [];
        foreach ($modulosVisibles as $clave => $modulo) {
            $totalArchivos = 0;
            if ($clave === 'celulas') {
                // Este modulo reutiliza la vista legacy de celulas/materiales y su directorio propio.
                $totalArchivos = $this->contarArchivosMaterialCelulasExistente();
            } else {
                $archivos = $this->listarArchivosModuloMaterial($modulo);
                $totalArchivos = count($archivos);
            }

            $modulosData[$clave] = [
                'meta' => $modulo,
                'total_archivos' => $totalArchivos,
            ];
        }

        $this->view('home/material', [
            'modulos_material' => $modulosData,
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'tipo' => (string)($_GET['tipo'] ?? ''),
        ]);
    }

    public function materialCelulas() {
        $this->redirect('celulas/materiales');
    }

    public function materialTeens() {
        $this->renderDetalleMaterial('teens');
    }

    public function materialUniversidadVida() {
        $this->renderDetalleMaterial('universidad_vida');
    }

    public function materialCapacitacionDestino() {
        $this->renderDetalleMaterial('capacitacion_destino');
    }

    public function materialVerPdf() {
        $modulos = $this->obtenerModulosMaterial();
        $moduloClave = trim((string)($_GET['modulo'] ?? ''));
        $archivo = basename((string)($_GET['archivo'] ?? ''));

        if (!isset($modulos[$moduloClave]) || !$this->puedeVerModuloMaterial($modulos[$moduloClave])) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        if ($archivo === '') {
            $this->redirect((string)($modulos[$moduloClave]['ruta'] ?? 'home/material') . '&mensaje=' . urlencode('Archivo invalido.') . '&tipo=error');
        }

        $rutaFisica = $this->obtenerDirectorioModuloMaterial($modulos[$moduloClave]) . '/' . $archivo;
        if (!is_file($rutaFisica)) {
            $this->redirect((string)($modulos[$moduloClave]['ruta'] ?? 'home/material') . '&mensaje=' . urlencode('El archivo no existe.') . '&tipo=error');
        }

        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);
        try {
            $this->registrarVistaMaterialHub($moduloClave, $archivo, $idPersona);
        } catch (Throwable $e) {
            // Si falla tracking, no bloquear apertura del archivo.
        }

        $extension = strtolower((string)pathinfo($rutaFisica, PATHINFO_EXTENSION));
        $mimePorExtension = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'mov' => 'video/quicktime',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'txt' => 'text/plain; charset=UTF-8',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        $mime = $mimePorExtension[$extension] ?? 'application/octet-stream';
        $tamano = (int)@filesize($rutaFisica);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($archivo) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        if ($tamano > 0) {
            header('Content-Length: ' . $tamano);
        }

        $fp = fopen($rutaFisica, 'rb');
        if ($fp === false) {
            $this->redirect((string)($modulos[$moduloClave]['ruta'] ?? 'home/material') . '&mensaje=' . urlencode('No se pudo abrir el archivo.') . '&tipo=error');
        }

        fpassthru($fp);
        fclose($fp);
        exit;
    }

    public function materialDetalleVistas() {
        header('Content-Type: application/json');

        $modulos = $this->obtenerModulosMaterial();
        $moduloClave = trim((string)($_GET['modulo'] ?? ''));
        $loteId = trim((string)($_GET['lote'] ?? ''));
        $archivo = basename((string)($_GET['archivo'] ?? ''));

        if (!isset($modulos[$moduloClave]) || !$this->puedeVerModuloMaterial($modulos[$moduloClave])) {
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        try {
            if ($loteId !== '') {
                $temas = $this->listarTemasModuloMaterial($modulos[$moduloClave]);
                $temaActual = null;
                foreach ($temas as $tema) {
                    if ((string)($tema['lote_id'] ?? '') === $loteId) {
                        $temaActual = $tema;
                        break;
                    }
                }

                if (empty($temaActual)) {
                    echo json_encode(['success' => false, 'message' => 'Tema invalido']);
                    exit;
                }

                $archivosLote = array_map(static function($item) {
                    return (string)($item['nombre'] ?? '');
                }, (array)($temaActual['archivos'] ?? []));

                $vistas = $this->obtenerDetalleVistasMaterialLote($moduloClave, $archivosLote);

                echo json_encode([
                    'success' => true,
                    'tema' => (string)($temaActual['titulo'] ?? 'Tema de material'),
                    'modulo' => $moduloClave,
                    'total_personas' => count($vistas),
                    'vistas' => $vistas,
                ]);
                exit;
            }

            if ($archivo === '') {
                echo json_encode(['success' => false, 'message' => 'Archivo invalido']);
                exit;
            }

            $vistas = $this->obtenerDetalleVistasMaterialLote($moduloClave, [$archivo]);

            echo json_encode([
                'success' => true,
                'archivo' => htmlspecialchars($archivo, ENT_QUOTES, 'UTF-8'),
                'modulo' => $moduloClave,
                'total_personas' => count($vistas),
                'vistas' => $vistas,
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener datos']);
        }
        exit;
    }

    public function consolidar() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->view('home/consolidar', $this->obtenerDatosModuloFormacion('consolidar'));
    }

    public function consolidarAsistencias() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->view('home/consolidar_asistencias', $this->obtenerDatosModuloFormacionAsistencias('consolidar'));
    }

    public function discipular() {
        $puedeVerDiscipular = AuthController::esAdministrador()
            || AuthController::tienePermiso('personas', 'ver');

        if (!$puedeVerDiscipular) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->view('home/discipular', $this->obtenerDatosModuloFormacion('discipular'));
    }

    public function discipularAsistencias() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->view('home/discipular_asistencias', $this->obtenerDatosModuloFormacionAsistencias('discipular'));
    }

    private function puedeVerEscuelasFormacion(): bool {
        return AuthController::esAdministrador()
            || AuthController::tienePermiso('escuelas_formacion', 'ver')
            || AuthController::tienePermiso('personas', 'ver');
    }

    private function puedeEditarEscuelasFormacion(): bool {
        return AuthController::esAdministrador()
            || AuthController::tienePermiso('escuelas_formacion', 'editar')
            || AuthController::tienePermiso('personas', 'editar');
    }

    private function puedeMarcarAsistenciaEscuelasFormacion(): bool {
        return AuthController::esAdministrador()
            || AuthController::tienePermiso('escuelas_formacion_marcar_asistencia', 'editar');
    }

    private function puedeEditarFechasEscuelasFormacion(): bool {
        return AuthController::esAdministrador()
            || AuthController::tienePermiso('escuelas_formacion_editar_fechas', 'editar');
    }

    public function escuelasFormacion() {
        $this->redirect('home/consolidar');
    }

    public function exportarConsolidar() {
        $this->exportarModuloFormacion('consolidar');
    }

    public function exportarDiscipular() {
        $this->exportarModuloFormacion('discipular');
    }

    public function exportarEscuelasFormacion() {
        $this->redirect('home/consolidar');
    }

    public function actualizarEstadoEscuelaFormacion() {
        if (!$this->puedeEditarEscuelasFormacion()) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $programa = trim((string)($_POST['programa'] ?? ''));
        $va = (int)($_POST['va'] ?? 0) === 1;
        $programa = $this->normalizarProgramaConsolidar($programa);

        if ($idPersona <= 0 || $programa === '') {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 422);
        }

        if (!$this->puedeGestionarPersonaFormacion($idPersona)) {
            $this->json(['ok' => false, 'error' => 'Sin acceso a esta persona'], 403);
        }

        $estadoEscuelaModel = new EscuelaFormacionEstado();
        $ok = $estadoEscuelaModel->upsertEstado($idPersona, $programa, $va);

        $registroActualizado = false;
        if (
            $ok
            && $va
            && !in_array($programa, ['universidad_vida', 'encuentro', 'bautismo'], true)
        ) {
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

    public function eliminarInscripcionFormacion() {
        if (!AuthController::tienePermiso('personas', 'eliminar')) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $idInscripcion = (int)($_POST['id_inscripcion'] ?? 0);
        $returnUrl = trim((string)($_POST['return_url'] ?? ''));
        if ($returnUrl === '' || $returnUrl[0] !== '?') {
            $returnUrl = '?url=home/consolidar';
        }

        if ($idInscripcion <= 0) {
            header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=error&mensaje=' . urlencode('Inscripción inválida para eliminar.'));
            exit;
        }

        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        $inscripcionModel = new EscuelaFormacionInscripcion();
        $inscripcion = $inscripcionModel->getByIdInscripcion($idInscripcion);

        if (empty($inscripcion)) {
            header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=error&mensaje=' . urlencode('La inscripción no existe o ya fue eliminada.'));
            exit;
        }

        $idPersonaInscripcion = (int)($inscripcion['Id_Persona'] ?? 0);
        if ($idPersonaInscripcion > 0 && !$this->puedeGestionarPersonaFormacion($idPersonaInscripcion)) {
            header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=error&mensaje=' . urlencode('Sin acceso para eliminar esta inscripción.'));
            exit;
        }

        $ok = $inscripcionModel->delete($idInscripcion);
        $mensaje = $ok
            ? 'Inscripción eliminada correctamente.'
            : 'No se pudo eliminar la inscripción.';
        $tipo = $ok ? 'success' : 'error';

        header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=' . urlencode($tipo) . '&mensaje=' . urlencode($mensaje));
        exit;
    }

    public function actualizarAsistenciaClaseEscuelaFormacion() {
        if (!$this->puedeMarcarAsistenciaEscuelasFormacion()) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $idInscripcion = (int)($_POST['id_inscripcion'] ?? 0);
        $asistioRaw = trim((string)($_POST['asistio'] ?? ''));

        if ($idInscripcion <= 0) {
            $this->json(['ok' => false, 'error' => 'Inscripción inválida'], 422);
        }

        require_once APP . '/Models/EscuelaFormacionInscripcion.php';
        $inscripcionModel = new EscuelaFormacionInscripcion();
        $inscripcion = $inscripcionModel->getByIdInscripcion($idInscripcion);
        $idPersonaInscripcion = (int)($inscripcion['Id_Persona'] ?? 0);
        if ($idPersonaInscripcion <= 0 || !$this->puedeGestionarPersonaFormacion($idPersonaInscripcion)) {
            $this->json(['ok' => false, 'error' => 'Sin acceso a esta inscripción'], 403);
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

        $ok = $inscripcionModel->actualizarAsistenciaClase($idInscripcion, $asistio);

        $this->json([
            'ok' => (bool)$ok,
            'id_inscripcion' => $idInscripcion,
            'asistio' => $asistio
        ]);
    }

        public function cambiarSegmentoInscripcion() {
                $accion = trim((string)($_POST['accion'] ?? ''));
                if ($accion === 'eliminar_inscripcion') {
                    if (!AuthController::tienePermiso('personas', 'eliminar')) {
                        $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
                    }

                    $idInscripcion = (int)($_POST['id_inscripcion'] ?? 0);
                    $returnUrl = trim((string)($_POST['return_url'] ?? ''));
                    if ($returnUrl === '' || $returnUrl[0] !== '?') {
                        $returnUrl = '?url=home/consolidar';
                    }

                    if ($idInscripcion <= 0) {
                        header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=error&mensaje=' . urlencode('Inscripción inválida para eliminar.'));
                        exit;
                    }

                    require_once APP . '/Models/EscuelaFormacionInscripcion.php';
                    $inscripcionModel = new EscuelaFormacionInscripcion();
                    $inscripcion = $inscripcionModel->getByIdInscripcion($idInscripcion);

                    if (empty($inscripcion)) {
                        header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=error&mensaje=' . urlencode('La inscripción no existe o ya fue eliminada.'));
                        exit;
                    }

                    $idPersonaInscripcion = (int)($inscripcion['Id_Persona'] ?? 0);
                    if ($idPersonaInscripcion > 0 && !$this->puedeGestionarPersonaFormacion($idPersonaInscripcion)) {
                        header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=error&mensaje=' . urlencode('Sin acceso para eliminar esta inscripción.'));
                        exit;
                    }

                    $ok = $inscripcionModel->delete($idInscripcion);
                    $mensaje = $ok
                        ? 'Inscripción eliminada correctamente.'
                        : 'No se pudo eliminar la inscripción.';
                    $tipo = $ok ? 'success' : 'error';

                    header('Location: ' . PUBLIC_URL . $returnUrl . '&tipo=' . urlencode($tipo) . '&mensaje=' . urlencode($mensaje));
                    exit;
                }

                if (!$this->puedeEditarEscuelasFormacion()) {
                    $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
                }

            $idInscripcion = (int)($_POST['id_inscripcion'] ?? 0);
            $segmentoNuevo = trim((string)($_POST['segmento_nuevo'] ?? ''));

            if ($idInscripcion <= 0) {
                $this->json(['ok' => false, 'error' => 'Inscripción inválida'], 422);
            }

            require_once APP . '/Models/EscuelaFormacionInscripcion.php';
            $inscripcionModel = new EscuelaFormacionInscripcion();
            $inscripcion = $inscripcionModel->getByIdInscripcion($idInscripcion);
            $idPersonaInscripcion = (int)($inscripcion['Id_Persona'] ?? 0);
            if ($idPersonaInscripcion <= 0 || !$this->puedeGestionarPersonaFormacion($idPersonaInscripcion)) {
                $this->json(['ok' => false, 'error' => 'Sin acceso a esta inscripción'], 403);
            }

            $ok = $inscripcionModel->actualizarSegmentoPreferido($idInscripcion, $segmentoNuevo);

            $this->json([
                'ok' => (bool)$ok,
                'id_inscripcion' => $idInscripcion,
                'segmento_nuevo' => $segmentoNuevo
            ]);
        }

    public function actualizarAsistenciaMatrizEscuelaFormacion() {
        if (!$this->puedeMarcarAsistenciaEscuelasFormacion()) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $idPersona = (int)($_POST['id_persona'] ?? 0);
        $modulo = trim((string)($_POST['modulo'] ?? ''));
        $programa = trim((string)($_POST['programa'] ?? ''));
        $clase = (int)($_POST['clase'] ?? 0);
        $asistioRaw = trim((string)($_POST['asistio'] ?? ''));
        if ($modulo === 'consolidar') {
            $programa = $this->normalizarProgramaConsolidar($programa);
        }

        if ($idPersona <= 0 || $modulo === '' || $programa === '' || $clase <= 0) {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 422);
        }

        if (!$this->puedeGestionarPersonaFormacion($idPersona)) {
            $this->json(['ok' => false, 'error' => 'Sin acceso a esta persona'], 403);
        }

        $asistio = ($asistioRaw === '1');

        require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();
        $ok = $asistenciaModel->upsertAsistencia($idPersona, $modulo, $programa, $clase, $asistio);

        $this->json([
            'ok' => (bool)$ok,
            'id_persona' => $idPersona,
            'modulo' => $modulo,
            'programa' => $programa,
            'clase' => $clase,
            'asistio' => $asistio,
        ]);
    }

    public function actualizarFechaClaseEscuelaFormacion() {
        if (!$this->puedeEditarFechasEscuelasFormacion()) {
            $this->json(['ok' => false, 'error' => 'No autorizado'], 403);
        }

        $modulo = trim((string)($_POST['modulo'] ?? ''));
        $programa = trim((string)($_POST['programa'] ?? ''));
        $clase = (int)($_POST['clase'] ?? 0);
        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $grupo = strtolower(trim((string)($_POST['grupo'] ?? 'general')));
        if ($modulo === 'consolidar') {
            $programa = $this->normalizarProgramaConsolidar($programa);
        }

        if ($modulo === '' || $programa === '' || $clase <= 0) {
            $this->json(['ok' => false, 'error' => 'Datos incompletos'], 422);
        }

        if ($fecha !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $this->json(['ok' => false, 'error' => 'Fecha invalida'], 422);
        }

        if (!in_array($grupo, ['hombres', 'mujeres', 'general'], true)) {
            $grupo = 'general';
        }

        require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();
        $ok = $asistenciaModel->upsertFechaClase($modulo, $programa, $clase, $fecha !== '' ? $fecha : null, $grupo);

        $this->json([
            'ok' => (bool)$ok,
            'modulo' => $modulo,
            'programa' => $programa,
            'clase' => $clase,
            'grupo' => $grupo,
            'fecha' => $fecha,
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

        $filtroMinisterio = trim((string)($_GET['ministerio'] ?? ''));
        $tipoLiderazgoDefault = $filtroMinisterio !== '' ? 'doce' : 'todos';
        $filtroTipoLiderazgo = strtolower(trim((string)($_GET['tipo_liderazgo'] ?? $tipoLiderazgoDefault)));
        if (!in_array($filtroTipoLiderazgo, ['todos', 'celula', 'doce', 'ambos'], true)) {
            $filtroTipoLiderazgo = 'todos';
        }

        $filtroBuscar = trim((string)($_GET['buscar'] ?? ''));

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
