<?php include VIEWS . '/layout/header.php'; ?>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/discipular-equipo.css?v=20261013d">

<?php
$idMinisterioFiltro = (int)($id_ministerio_filtro ?? 0);
$nombreMinisterioFiltro = trim((string)($nombre_ministerio_filtro ?? ''));
$hayFiltroMinisterio = $idMinisterioFiltro > 0;
$esCoberturaPastoralGlobal = !$hayFiltroMinisterio;
$esVistaPropiaLider12 = !empty($es_vista_propia_lider_12);
$esVistaPropiaLider144 = !empty($es_vista_propia_lider_144);
$idUsuarioSesion = (int)($id_usuario_sesion ?? 0);
$puedeConfigurarLideresPrincipales = !isset($puede_configurar_lideres_principales) || !empty($puede_configurar_lideres_principales);

$encabezado = is_array($encabezado_equipo_principal ?? null) ? $encabezado_equipo_principal : [];
$lideresEquipo = is_array($lideres_equipo_principal ?? null) ? $lideres_equipo_principal : [];
$liderazgoRed = is_array($liderazgo_red ?? null) ? $liderazgo_red : [];
$discipulosRed = is_array($discipulos_red ?? null) ? $discipulos_red : [];
$personasAsignablesUrl = trim((string)($personas_asignables_url ?? ''));
if ($personasAsignablesUrl === '' && function_exists('public_app_url')) {
    $personasAsignablesUrl = public_app_url('discipular/ministerios/personas-asignables');
}
$jerarquiaPorLiderId = is_array($jerarquia_por_lider_id ?? null) ? $jerarquia_por_lider_id : [];
$totalesTabs = is_array($totales_tabs ?? null) ? $totales_tabs : [];
$ministeriosNavegacion = is_array($ministerios_navegacion ?? null) ? $ministerios_navegacion : [];

$nombrePastor = trim((string)($encabezado['nombre'] ?? 'LIDER PRINCIPAL'));
$emailPastor = trim((string)($encabezado['email'] ?? ''));
$telefonoPastor = trim((string)($encabezado['telefono'] ?? ''));
$sedePastor = trim((string)($encabezado['sede'] ?? 'Madrid'));
$idUsuarioEncabezado = (int)($encabezado['id_usuario'] ?? 0);

$equipoPrincipalTotal = (int)($encabezado['equipo_principal'] ?? count($lideresEquipo));
$equipoPrincipalHombres = (int)($encabezado['equipo_principal_hombres'] ?? count($lideres_equipo_hombres ?? []));
$equipoPrincipalMujeres = (int)($encabezado['equipo_principal_mujeres'] ?? count($lideres_equipo_mujeres ?? []));
$ministerioCantidad = (int)($encabezado['ministerio_cantidad'] ?? count($ministeriosNavegacion));

$totalEquipoPrincipal = (int)($totalesTabs['equipo_principal'] ?? $equipoPrincipalTotal);
$totalLideres144 = (int)($totalesTabs['lideres_144'] ?? 0);
$totalLideresCelula = (int)($totalesTabs['lideres_celula'] ?? 0);
$totalDiscipulos = (int)($totalesTabs['discipulos'] ?? 0);
$totalCuposEquipoPrincipal = max(0, $totalEquipoPrincipal * 12);
$totalCuposDisponibles = 0;

$urlMinisteriosLista = PUBLIC_URL . '?url=discipular/ministerios';
$urlMinisteriosCrear = PUBLIC_URL . '?url=discipular/ministerios/crear';
$puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::puede('ministerios:crear');

$asignacionOk = (string)($_GET['asignacion_ok'] ?? '') === '1';
$asignacionError = (string)($_GET['asignacion_error'] ?? '') === '1';
$asignacionMsg = trim((string)($_GET['asignacion_msg'] ?? ''));
$reasignacionOk = (string)($_GET['reasignacion_ok'] ?? '') === '1';
$reasignacionError = (string)($_GET['reasignacion_error'] ?? '') === '1';
$reasignacionMsg = trim((string)($_GET['reasignacion_msg'] ?? ''));
$lpOk = (string)($_GET['lp_ok'] ?? '') === '1';
$lpError = (string)($_GET['lp_error'] ?? '') === '1';
$lpMsg = trim((string)($_GET['lp_msg'] ?? ''));
$idLiderPrincipal1 = (int)($id_lider_principal_1 ?? 0);
$idLiderPrincipal2 = (int)($id_lider_principal_2 ?? 0);
$nombreLiderPrincipal1 = trim((string)($nombre_lider_principal_1 ?? ''));
$nombreLiderPrincipal2 = trim((string)($nombre_lider_principal_2 ?? ''));
$candidatosLideresPrincipales = is_array($candidatos_lideres_principales ?? null) ? $candidatos_lideres_principales : [];
$equipoDirectoDesdeController = is_array($equipo_directo_por_lider ?? null) ? $equipo_directo_por_lider : null;

$normalizarTextoMinisterio = static function($texto) {
    $valor = function_exists('mb_strtolower') ? mb_strtolower(trim((string)$texto), 'UTF-8') : strtolower(trim((string)$texto));
    return strtr($valor, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
        'à' => 'a',
        'è' => 'e',
        'ì' => 'i',
        'ò' => 'o',
        'ù' => 'u',
    ]);
};
$normalizarTextoBusqueda = $normalizarTextoMinisterio;
$nombreMinisterioNormalizado = $normalizarTextoMinisterio($nombreMinisterioFiltro);
$esMinisterioPastores = !empty($es_ministerio_pastoral)
    || ($hayFiltroMinisterio && (
        strpos($nombreMinisterioNormalizado, 'pastor') !== false
        || strpos($nombreMinisterioNormalizado, 'pastoral') !== false
    ));
$usarEtiquetasPastorales = !$esVistaPropiaLider12 && ($esCoberturaPastoralGlobal || $esMinisterioPastores);
// Pastor/admin en cobertura general: solo consulta de 144, célula y discípulos; cupos solo bajo pastores principales.
$vistaPastoralRedSoloLectura = $esCoberturaPastoralGlobal && $usarEtiquetasPastorales && !$esVistaPropiaLider12;
$idsLideres12CoberturaCupo = is_array($ids_lideres_12_cobertura_cupo ?? null)
    ? array_values(array_map('intval', $ids_lideres_12_cobertura_cupo))
    : [];

$labelLiderazgoPrincipal1 = $usarEtiquetasPastorales ? 'Pastor principal' : 'Líder principal (hombres)';
$labelLiderazgoPrincipal2 = $usarEtiquetasPastorales ? 'Pastora principal' : 'Líder principal (mujeres)';
$contactoLiderPrincipal1 = is_array($contacto_lider_principal_1 ?? null) ? $contacto_lider_principal_1 : ['email' => '', 'telefono' => ''];
$contactoLiderPrincipal2 = is_array($contacto_lider_principal_2 ?? null) ? $contacto_lider_principal_2 : ['email' => '', 'telefono' => ''];
$tarjetaCoberturaPastoralGeneral = $esCoberturaPastoralGlobal && $usarEtiquetasPastorales;
$tarjetaDirectoresMinisterio = $esVistaPropiaLider12 || ($hayFiltroMinisterio && !$usarEtiquetasPastorales);
$tarjetaEnfocadaDirectores = $tarjetaDirectoresMinisterio || $tarjetaCoberturaPastoralGeneral;
$tituloTarjetaDirectores = $tarjetaCoberturaPastoralGeneral
    ? 'Cobertura pastoral general'
    : ($nombreMinisterioFiltro !== '' ? $nombreMinisterioFiltro : 'Ministerio');
$subtituloTarjetaDirectores = $tarjetaCoberturaPastoralGeneral
    ? 'Pastores principales de la iglesia'
    : 'Líderes que dirigen este ministerio';
$placeholderLiderazgoPrincipal1 = $usarEtiquetasPastorales ? 'Seleccionar pastor...' : 'Seleccionar lider de 12...';
$placeholderLiderazgoPrincipal2 = $usarEtiquetasPastorales ? 'Seleccionar pastora...' : 'Seleccionar lider de 12...';
$textoBotonGuardarLiderazgo = $usarEtiquetasPastorales ? 'Guardar pastores' : 'Guardar lideres de 12';
$textoErrorGuardarLiderazgo = $usarEtiquetasPastorales
    ? 'No se pudo guardar la configuracion de pastores principales.'
    : 'No se pudo guardar la configuracion de lideres principales.';
$textoOkGuardarLiderazgo = $usarEtiquetasPastorales
    ? 'Pastores principales guardados correctamente.'
    : 'Lideres principales guardados correctamente.';
$textoAvisoConfigurarLideres = $usarEtiquetasPastorales
    ? 'Configura primero el pastor y la pastora principal del ministerio para gestionar las casillas del 1 al 12.'
    : 'Configura primero los lideres principales del ministerio para gestionar las casillas del 1 al 12.';
$labelCoberturaSeleccionada = $usarEtiquetasPastorales ? 'Pastor/Pastora seleccionado(a)' : 'Lider principal seleccionado(a)';
$mostrarBotonesCupoPastoral = $esVistaPropiaLider12
    ? ($idUsuarioSesion > 0)
    : ($idLiderPrincipal1 > 0 || $idLiderPrincipal2 > 0);
$textoBotonGestionarCupos = $esVistaPropiaLider12
    ? 'Gestionar 12 cupos de líderes de 144'
    : ($usarEtiquetasPastorales ? 'Gestionar 12 cupos del pastor' : 'Gestionar 12 cupos del líder');
$labelSelectorMinisterio = $hayFiltroMinisterio ? 'Ministerio:' : 'Cobertura:';
$textoOpcionGeneral = $hayFiltroMinisterio ? 'Todos' : 'Cobertura pastoral general';
$textoBotonEditarLiderazgo = $hayFiltroMinisterio ? 'Editar líderes principales' : 'Configurar cabeza pastoral';
$tituloBotonEditarLiderazgo = $usarEtiquetasPastorales ? 'Configurar cabeza pastoral' : 'Configurar lideres principales';
$tituloModalEditarLiderazgo = $hayFiltroMinisterio ? 'Editar líderes principales del ministerio' : 'Configurar cobertura pastoral general';
$labelSeccionLiderazgo = $hayFiltroMinisterio ? 'Líderes principales del ministerio' : 'Cobertura pastoral general';
$idsLideresPrincipalesCupo = array_values(array_filter([$idLiderPrincipal1, $idLiderPrincipal2], static function($id) {
    return (int)$id > 0;
}));
$coberturaPrincipalActual = '';
$generoRedActual = strtolower(trim((string)($_GET['genero_red'] ?? '')));
if (!in_array($generoRedActual, ['hombres', 'mujeres'], true)) {
    $generoRedActual = '';
}

$idsLideresValidosCobertura = array_values(array_unique(array_filter(array_merge(
    [$idLiderPrincipal1, $idLiderPrincipal2],
    array_map(static function ($row) {
        return (int)($row['Id_Persona'] ?? 0);
    }, $liderazgoRed)
), static function ($id) {
    return (int)$id > 0;
})));

$coberturaSolicitada = trim((string)($_GET['cobertura_principal'] ?? ''));
if ($coberturaSolicitada !== '' && ctype_digit($coberturaSolicitada)) {
    $coberturaId = (int)$coberturaSolicitada;
    if (in_array($coberturaId, $idsLideresValidosCobertura, true)) {
        $coberturaPrincipalActual = (string)$coberturaId;
    }
}

$esRolLider12Fn = static function ($idRolRaw, $nombreRolRaw) {
    $idRol = (int)$idRolRaw;
    $nombreRol = strtolower(trim((string)$nombreRolRaw));
    if ($idRol === 8) {
        return true;
    }
    return strpos($nombreRol, 'lider de 12') !== false
        || strpos($nombreRol, 'lider 12') !== false
        || strpos($nombreRol, 'lideres de 12') !== false;
};

$normalizarGenero = static function ($generoRaw) {
    $genero = strtolower(trim((string)$generoRaw));
    $genero = strtr($genero, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
    ]);
    if ($genero === '') {
        return 'sin_genero';
    }
    if (strpos($genero, 'mujer') !== false
        || strpos($genero, 'femen') !== false
        || $genero === 'f'
        || $genero === 'fem'
        || $genero === 'female'
        || $genero === 'jf') {
        return 'mujeres';
    }
    if (strpos($genero, 'hombre') !== false
        || strpos($genero, 'mascul') !== false
        || $genero === 'h'
        || $genero === 'm'
        || $genero === 'masc'
        || $genero === 'male'
        || $genero === 'jm') {
        return 'hombres';
    }
    return 'sin_genero';
};

$normalizarProcesoEscalera = static function ($raw): string {
    $valor = strtolower(trim((string)$raw));
    $valor = strtr($valor, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
    ]);
    if (in_array($valor, ['ganar', 'consolidar', 'discipular', 'enviar'], true)) {
        return $valor;
    }
    return 'ganar';
};

$candidatosHombresModal = array_filter($candidatosLideresPrincipales, function($cand) use ($idMinisterioFiltro, $hayFiltroMinisterio) {
    $idCand = (int)($cand['id_persona'] ?? 0);
    $idMinCand = (int)($cand['id_ministerio'] ?? 0);
    $genero = strtolower(trim((string)($cand['genero'] ?? $cand['Genero'] ?? '')));
    $okMinisterio = !$hayFiltroMinisterio || $idMinCand === $idMinisterioFiltro;
    return $idCand > 0 && $okMinisterio && (strpos($genero, 'mujer') === false && strpos($genero, 'femen') === false);
});
$candidatosMujeresModal = array_filter($candidatosLideresPrincipales, function($cand) use ($idMinisterioFiltro, $hayFiltroMinisterio) {
    $idCand = (int)($cand['id_persona'] ?? 0);
    $idMinCand = (int)($cand['id_ministerio'] ?? 0);
    $genero = strtolower(trim((string)($cand['genero'] ?? $cand['Genero'] ?? '')));
    $okMinisterio = !$hayFiltroMinisterio || $idMinCand === $idMinisterioFiltro;
    return $idCand > 0 && $okMinisterio && (strpos($genero, 'mujer') !== false || strpos($genero, 'femen') !== false);
});

$rowsTabla = [];
foreach ($liderazgoRed as $row) {
    $idPersona = (int)($row['Id_Persona'] ?? 0);
    if ($idPersona <= 0) {
        continue;
    }

    $esLider12 = (int)($row['Es_Lider_12'] ?? 0) === 1;
    $esLiderCelula = (int)($row['Es_Lider_Celula'] ?? 0) === 1;
    $esLider144 = (int)($row['es_lider_144'] ?? 0) === 1 || (int)($row['Es_Lider_144'] ?? 0) === 1;
    $rolLiderazgoTmp = strtolower(trim((string)($row['Nombre_Rol'] ?? '')));
    if (!$esLider144 && strpos($rolLiderazgoTmp, '144') !== false) {
        $esLider144 = true;
    }

    $rowsTabla[] = [
        'id' => $idPersona,
        'id_ministerio' => (int)($row['Id_Ministerio'] ?? 0),
        'nombre_ministerio' => trim((string)($row['Nombre_Ministerio'] ?? '')),
        'nombre_rol' => trim((string)($row['Nombre_Rol'] ?? '')),
        'numero_documento' => trim((string)($row['Numero_Documento'] ?? '')),
        'nombre' => trim((string)($row['Nombre'] ?? '')),
        'apellido' => trim((string)($row['Apellido'] ?? '')),
        'email' => trim((string)($row['Email'] ?? '')),
        'telefono' => trim((string)($row['Telefono'] ?? '')),
        'genero' => $normalizarGenero($row['Genero'] ?? ''),
        'es_lider' => true,
        'es_equipo_principal' => $esLider12,
        'es_lider_144' => $esLider144,
        'es_lider_celula' => $esLiderCelula,
        'es_discipulo' => !$esLider12 && !$esLiderCelula,
        'sin_asignacion_red' => false,
        'equipo_directo' => (int)($row['Equipo_Directo'] ?? 0),
        'cupos_disponibles' => (int)($row['Cupos_Disponibles'] ?? 12),
        'id_lider_actual' => (int)($row['Id_Lider'] ?? 0),
        'nombre_lider_actual' => trim((string)($row['Nombre_Lider'] ?? '')),
        'proceso' => $normalizarProcesoEscalera($row['Proceso'] ?? ''),
        'escalera_checklist' => (string)($row['Escalera_Checklist'] ?? ''),
        'id_celula' => (int)($row['Id_Celula'] ?? 0),
    ];

    if ($esLider12) {
        $totalCuposDisponibles += (int)($row['Cupos_Disponibles'] ?? 12);
    }
}

foreach ($discipulosRed as $row) {
    $idPersona = (int)($row['Id_Persona'] ?? 0);
    if ($idPersona <= 0) {
        continue;
    }

    $rowsTabla[] = [
        'id' => $idPersona,
        'id_ministerio' => (int)($row['Id_Ministerio'] ?? 0),
        'nombre_ministerio' => trim((string)($row['Nombre_Ministerio'] ?? '')),
        'nombre_rol' => trim((string)($row['Nombre_Rol'] ?? '')),
        'numero_documento' => trim((string)($row['Numero_Documento'] ?? '')),
        'nombre' => trim((string)($row['Nombre'] ?? '')),
        'apellido' => trim((string)($row['Apellido'] ?? '')),
        'email' => trim((string)($row['Email'] ?? '')),
        'telefono' => trim((string)($row['Telefono'] ?? '')),
        'genero' => $normalizarGenero($row['Genero'] ?? ''),
        'es_lider' => false,
        'es_equipo_principal' => false,
        'es_lider_144' => false,
        'es_lider_celula' => false,
        'es_discipulo' => true,
        'sin_asignacion_red' => (int)($row['Sin_Asignacion_Red'] ?? 0) === 1
            || (
                (int)($row['Id_Lider'] ?? 0) <= 0
                && (int)($row['Id_Ministerio'] ?? 0) <= 0
            ),
        'equipo_directo' => 0,
        'cupos_disponibles' => -1,
        'id_lider_actual' => (int)($row['Id_Lider'] ?? 0),
        'nombre_lider_actual' => trim((string)($row['Nombre_Lider'] ?? '')),
        'proceso' => $normalizarProcesoEscalera($row['Proceso'] ?? ''),
        'escalera_checklist' => (string)($row['Escalera_Checklist'] ?? ''),
        'id_celula' => (int)($row['Id_Celula'] ?? 0),
    ];
}

usort($rowsTabla, static function ($a, $b) {
    $na = trim((string)$a['nombre'] . ' ' . (string)$a['apellido']);
    $nb = trim((string)$b['nombre'] . ' ' . (string)$b['apellido']);
    return strcasecmp($na, $nb);
});

$lideresParaAsignacion = array_values(array_filter($rowsTabla, static function($row) {
    return !empty($row['es_equipo_principal']);
}));

$personasParaReasignar = array_values(array_filter($rowsTabla, static function($row) {
    return (int)($row['id_lider_actual'] ?? 0) > 0 && empty($row['es_equipo_principal']);
}));

$tabSolicitado = strtolower(trim((string)($_GET['tab'] ?? '')));
$vistaAvanzadaActiva = isset($_GET['vista_avanzada']) && (string)$_GET['vista_avanzada'] === '1';
$tabPorDefecto = $vistaAvanzadaActiva
    ? 'discipulos'
    : ($hayFiltroMinisterio ? 'lideres_144' : 'equipo_principal');
$tabActivo = $tabSolicitado !== '' ? $tabSolicitado : $tabPorDefecto;
if (!in_array($tabActivo, ['equipo_principal', 'lideres_144', 'lideres_celula', 'discipulos'], true)) {
    $tabActivo = $tabPorDefecto;
}
if (!$vistaAvanzadaActiva && $hayFiltroMinisterio && $tabActivo === 'equipo_principal') {
    $tabActivo = 'lideres_144';
}

$etapaEscaleraFiltro = strtolower(trim((string)($_GET['etapa'] ?? '')));
if (!in_array($etapaEscaleraFiltro, ['ganar', 'consolidar', 'discipular', 'enviar'], true)) {
    $etapaEscaleraFiltro = '';
}

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$generoEnUrl = array_key_exists('genero', $_GET)
    ? strtolower(trim((string)$_GET['genero']))
    : null;
if ($generoEnUrl !== null && in_array($generoEnUrl, ['todos', 'hombres', 'mujeres'], true)) {
    $filtroGeneroGet = $generoEnUrl;
    $_SESSION['discipular_equipo_genero'] = $filtroGeneroGet;
} elseif (in_array($generoRedActual, ['hombres', 'mujeres'], true)) {
    $filtroGeneroGet = $generoRedActual;
    $_SESSION['discipular_equipo_genero'] = $filtroGeneroGet;
} else {
    $generoSesion = strtolower(trim((string)($_SESSION['discipular_equipo_genero'] ?? 'todos')));
    $filtroGeneroGet = in_array($generoSesion, ['todos', 'hombres', 'mujeres'], true)
        ? $generoSesion
        : 'todos';
}

$filtroLiderRaw = trim((string)($_GET['lider'] ?? ''));
$filtroLiderGet = '';
if ($filtroLiderRaw === '0') {
    $filtroLiderGet = '0';
} elseif ($filtroLiderRaw !== '' && ctype_digit($filtroLiderRaw) && (int)$filtroLiderRaw > 0) {
    $filtroLiderGet = (string)((int)$filtroLiderRaw);
}

$filaPasaFiltroLider = static function (array $row) use ($filtroLiderGet): bool {
    if ($filtroLiderGet === '') {
        return true;
    }
    $idLiderFila = (int)($row['id_lider_actual'] ?? 0);
    if ($filtroLiderGet === '0') {
        return $idLiderFila <= 0;
    }
    return $idLiderFila === (int)$filtroLiderGet;
};

$labelsTabDiscipular = [
    'equipo_principal' => 'Equipo principal',
    'lideres_144' => 'Líderes de 144',
    'lideres_celula' => 'Líderes de célula',
    'discipulos' => 'Discípulos',
];
$tituloExportTablaDiscipular = 'Discipular — '
    . ($hayFiltroMinisterio && $nombreMinisterioFiltro !== '' ? $nombreMinisterioFiltro : 'Cobertura pastoral')
    . ' · '
    . ($labelsTabDiscipular[$tabActivo] ?? 'Red pastoral');

$returnQueryDiscipular = ['tab' => $tabActivo];
if ($hayFiltroMinisterio) {
    $returnQueryDiscipular['id_ministerio'] = $idMinisterioFiltro;
}
$buscarRetorno = trim((string)($_GET['buscar'] ?? ''));
if ($buscarRetorno !== '') {
    $returnQueryDiscipular['buscar'] = $buscarRetorno;
}
$returnQueryDiscipular['genero'] = $filtroGeneroGet;
if ($filtroLiderGet !== '') {
    $returnQueryDiscipular['lider'] = $filtroLiderGet;
}
$coberturaRetorno = trim((string)($_GET['cobertura_principal'] ?? ''));
if ($coberturaRetorno !== '') {
    $returnQueryDiscipular['cobertura_principal'] = $coberturaRetorno;
}
$urlRetornoEquipo = PUBLIC_URL . '?url=discipular/ministerios/equipo-principal&' . http_build_query($returnQueryDiscipular);

$puedeEliminarPersonaDiscipular = AuthController::puedeEliminarPersonaDesdeDiscipular();
$puedeEditarPersonaDiscipular = AuthController::puedeEditarPersonaDesdeDiscipular();

$mostrarTabEquipoPrincipal = !$hayFiltroMinisterio;
$idsLideres12CupoMinisterio = [];
$agregarLider12MinisterioCupo = static function (int $idL12) use (&$idsLideres12CupoMinisterio): void {
    if ($idL12 > 0) {
        $idsLideres12CupoMinisterio[] = $idL12;
    }
};
if ($hayFiltroMinisterio || $esVistaPropiaLider12) {
    $idsLideres12CupoMinisterio = !empty($idsLideres12CoberturaCupo)
        ? $idsLideres12CoberturaCupo
        : array_values(array_filter([$idLiderPrincipal1, $idLiderPrincipal2, $idUsuarioSesion], static function ($id) {
            return (int)$id > 0;
        }));
}

$buscarGet = strtolower(trim((string)($_GET['buscar'] ?? '')));
$soloDigitosBuscar = preg_replace('/\D+/', '', $buscarGet);
$hayBusquedaActiva = $buscarGet !== '';

$textoBusquedaFila = static function (array $row): string {
    return strtolower(trim(
        (string)($row['nombre'] ?? '') . ' ' .
        (string)($row['apellido'] ?? '') . ' ' .
        (string)($row['numero_documento'] ?? '') . ' ' .
        (string)($row['telefono'] ?? '') . ' ' .
        (string)($row['email'] ?? '') . ' ' .
        (string)($row['nombre_ministerio'] ?? '') . ' ' .
        (string)($row['nombre_rol'] ?? '') . ' ' .
        (string)($row['nombre_lider_actual'] ?? '')
    ));
};

$filaCoincideBusqueda = static function (array $row) use ($buscarGet, $soloDigitosBuscar, $textoBusquedaFila, $normalizarTextoBusqueda): bool {
    if ($buscarGet === '') {
        return true;
    }

    $texto = $normalizarTextoBusqueda($textoBusquedaFila($row));
    $query = $normalizarTextoBusqueda($buscarGet);
    $digitos = preg_replace(
        '/\D+/',
        '',
        (string)($row['numero_documento'] ?? '') . (string)($row['telefono'] ?? '')
    );
    if ($soloDigitosBuscar !== '' && $digitos !== '' && strpos($digitos, $soloDigitosBuscar) !== false) {
        return true;
    }
    if ($query === '') {
        return true;
    }
    if (strpos($texto, $query) !== false) {
        return true;
    }

    $tokens = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $palabras = preg_split('/\s+/', $texto, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach ($tokens as $token) {
        if (strpos($texto, $token) !== false) {
            continue;
        }
        $okPalabra = false;
        foreach ($palabras as $palabra) {
            if (strpos($palabra, $token) === 0) {
                $okPalabra = true;
                break;
            }
            $lenToken = strlen($token);
            if ($lenToken >= 3 && function_exists('levenshtein')) {
                $prefijo = substr($palabra, 0, $lenToken);
                if ($prefijo !== '' && levenshtein($token, $prefijo) <= 1) {
                    $okPalabra = true;
                    break;
                }
            }
        }
        if (!$okPalabra) {
            return false;
        }
    }

    return $tokens !== [];
};

$totalesTabsVista = [
    'equipo_principal' => 0,
    'lideres_144' => 0,
    'lideres_celula' => 0,
    'discipulos' => 0,
];
$kpiHombresVista = 0;
$kpiMujeresVista = 0;
$kpiTotalVista = 0;
$idsKpiVistos = [];

foreach ($rowsTabla as $rowIndicador) {
    $idIndicador = (int)($rowIndicador['id'] ?? 0);
    $generoIndicador = strtolower(trim((string)($rowIndicador['genero'] ?? 'sin_genero')));
    $pasaGenero = $filtroGeneroGet === 'todos' || $generoIndicador === $filtroGeneroGet;
    $pasaBuscar = $filaCoincideBusqueda($rowIndicador);

    if ($idIndicador > 0 && !isset($idsKpiVistos[$idIndicador])) {
        $idsKpiVistos[$idIndicador] = true;
        $kpiTotalVista++;
        if ($generoIndicador === 'mujeres') {
            $kpiMujeresVista++;
        } elseif ($generoIndicador === 'hombres') {
            $kpiHombresVista++;
        }
    }

    if (!$pasaGenero || !$pasaBuscar) {
        continue;
    }

    $idLiderActualInd = (int)($rowIndicador['id_lider_actual'] ?? 0);
    if ($idLiderActualInd > 0 && in_array($idLiderActualInd, $idsLideresPrincipalesCupo, true)) {
        $totalesTabsVista['equipo_principal']++;
    }
    $ok144 = !empty($rowIndicador['es_lider_144']);
    if ($hayFiltroMinisterio) {
        $ok144 = $ok144 && $idLiderActualInd > 0 && in_array($idLiderActualInd, $idsLideres12CupoMinisterio, true);
    }
    if ($ok144) {
        $totalesTabsVista['lideres_144']++;
    }
    if (!empty($rowIndicador['es_lider_celula'])) {
        $totalesTabsVista['lideres_celula']++;
    }
    $okDiscipulo = !empty($rowIndicador['es_discipulo']);
    if ($vistaPastoralRedSoloLectura) {
        $okDiscipulo = $okDiscipulo && empty($rowIndicador['es_lider_144']) && empty($rowIndicador['es_lider_celula']);
    }
    if ($okDiscipulo) {
        $totalesTabsVista['discipulos']++;
    }
}

$totalEquipoPrincipal = (int)$totalesTabsVista['equipo_principal'];
$totalLideres144 = (int)$totalesTabsVista['lideres_144'];
$totalLideresCelula = (int)$totalesTabsVista['lideres_celula'];
$totalDiscipulos = (int)$totalesTabsVista['discipulos'];

$esLider144EnFilaLiderazgo = static function (array $liderRow): bool {
    if ((int)($liderRow['es_lider_144'] ?? 0) === 1 || (int)($liderRow['Es_Lider_144'] ?? 0) === 1) {
        return true;
    }
    $rol = strtolower(trim((string)($liderRow['Nombre_Rol'] ?? '')));
    return strpos($rol, '144') !== false;
};

$mapearLiderazgoAFilaTabla = static function (array $liderRow) use ($normalizarGenero, $esLider144EnFilaLiderazgo, $normalizarProcesoEscalera): array {
    $esLider12 = (int)($liderRow['Es_Lider_12'] ?? 0) === 1;
    $esLiderCelula = (int)($liderRow['Es_Lider_Celula'] ?? 0) === 1;
    $esLider144 = $esLider144EnFilaLiderazgo($liderRow);

    return [
        'id' => (int)($liderRow['Id_Persona'] ?? 0),
        'id_ministerio' => (int)($liderRow['Id_Ministerio'] ?? 0),
        'nombre_ministerio' => trim((string)($liderRow['Nombre_Ministerio'] ?? '')),
        'nombre_rol' => trim((string)($liderRow['Nombre_Rol'] ?? '')),
        'numero_documento' => trim((string)($liderRow['Numero_Documento'] ?? '')),
        'nombre' => trim((string)($liderRow['Nombre'] ?? '')),
        'apellido' => trim((string)($liderRow['Apellido'] ?? '')),
        'email' => trim((string)($liderRow['Email'] ?? '')),
        'telefono' => trim((string)($liderRow['Telefono'] ?? '')),
        'genero' => $normalizarGenero($liderRow['Genero'] ?? ''),
        'es_lider' => true,
        'es_equipo_principal' => $esLider12,
        'es_lider_144' => $esLider144,
        'es_lider_celula' => $esLiderCelula,
        'es_discipulo' => !$esLider12 && !$esLiderCelula && !$esLider144,
        'sin_asignacion_red' => false,
        'equipo_directo' => (int)($liderRow['Equipo_Directo'] ?? 0),
        'cupos_disponibles' => (int)($liderRow['Cupos_Disponibles'] ?? 12),
        'id_lider_actual' => (int)($liderRow['Id_Lider'] ?? 0),
        'nombre_lider_actual' => trim((string)($liderRow['Nombre_Lider'] ?? '')),
        'proceso' => $normalizarProcesoEscalera($liderRow['Proceso'] ?? ''),
    ];
};

$filaCumpleFiltrosGeneroBusqueda = static function (array $row) use ($filtroGeneroGet, $filaCoincideBusqueda, $tabActivo, $coberturaPrincipalActual, $hayBusquedaActiva, $filaPasaFiltroLider): bool {
    $generoRow = strtolower(trim((string)($row['genero'] ?? 'sin_genero')));
    $okGenero = $filtroGeneroGet === 'todos' || $generoRow === $filtroGeneroGet;
    $okBuscar = $filaCoincideBusqueda($row);

    $okCobertura = true;
    if (!$hayBusquedaActiva && $tabActivo === 'equipo_principal' && $coberturaPrincipalActual !== '') {
        $okCobertura = (string)($row['id_lider_actual'] ?? '0') === $coberturaPrincipalActual;
    }

    return $okGenero && $okBuscar && $okCobertura && $filaPasaFiltroLider($row);
};

if ($vistaPastoralRedSoloLectura && in_array($tabActivo, ['lideres_144', 'lideres_celula'], true) && !$hayBusquedaActiva) {
    $rowsTablaFiltradas = [];
    foreach ($liderazgoRed as $liderRowConsulta) {
        $esCelulaConsulta = (int)($liderRowConsulta['Es_Lider_Celula'] ?? 0) === 1;
        $es144Consulta = $esLider144EnFilaLiderazgo($liderRowConsulta);
        if ($tabActivo === 'lideres_144' && !$es144Consulta) {
            continue;
        }
        if ($tabActivo === 'lideres_celula' && !$esCelulaConsulta) {
            continue;
        }
        $filaConsulta = $mapearLiderazgoAFilaTabla($liderRowConsulta);
        if ($filaConsulta['id'] <= 0 || !$filaCumpleFiltrosGeneroBusqueda($filaConsulta)) {
            continue;
        }
        $rowsTablaFiltradas[] = $filaConsulta;
    }
    usort($rowsTablaFiltradas, static function ($a, $b) {
        $na = trim((string)$a['nombre'] . ' ' . (string)$a['apellido']);
        $nb = trim((string)$b['nombre'] . ' ' . (string)$b['apellido']);
        return strcasecmp($na, $nb);
    });
} else {
$rowsTablaFiltradas = array_values(array_filter($rowsTabla, static function($row) use ($tabActivo, $filtroGeneroGet, $filaCoincideBusqueda, $coberturaPrincipalActual, $idsLideresPrincipalesCupo, $hayFiltroMinisterio, $idsLideres12CupoMinisterio, $vistaPastoralRedSoloLectura, $hayBusquedaActiva, $filaPasaFiltroLider) {
    $okTab = true;
    if (!$hayBusquedaActiva) {
        if ($tabActivo === 'equipo_principal') {
            $idLiderActual = (int)($row['id_lider_actual'] ?? 0);
            $okTab = $idLiderActual > 0 && in_array($idLiderActual, $idsLideresPrincipalesCupo, true);
        } elseif ($tabActivo === 'lideres_144') {
            $okTab = !empty($row['es_lider_144']);
            if ($hayFiltroMinisterio) {
                $idLiderSup144 = (int)($row['id_lider_actual'] ?? 0);
                $okTab = $okTab && $idLiderSup144 > 0 && in_array($idLiderSup144, $idsLideres12CupoMinisterio, true);
            }
        } elseif ($tabActivo === 'lideres_celula') {
            $okTab = !empty($row['es_lider_celula']);
        } elseif ($tabActivo === 'discipulos') {
            $okTab = !empty($row['es_discipulo']);
            if ($vistaPastoralRedSoloLectura) {
                $okTab = $okTab && empty($row['es_lider_144']) && empty($row['es_lider_celula']);
            }
        }
    }

    $generoRow = strtolower(trim((string)($row['genero'] ?? 'sin_genero')));
    $okGenero = $filtroGeneroGet === 'todos' || $generoRow === $filtroGeneroGet;
    $okBuscar = $filaCoincideBusqueda($row);

    $okCobertura = true;
    if (!$hayBusquedaActiva && $tabActivo === 'equipo_principal' && $coberturaPrincipalActual !== '') {
        $okCobertura = (string)($row['id_lider_actual'] ?? '0') === $coberturaPrincipalActual;
    }

    return $okTab && $okGenero && $okBuscar && $okCobertura && $filaPasaFiltroLider($row);
}));
}

$totalSinAsignacionRedBusqueda = 0;
foreach ($rowsTablaFiltradas as $filaAsig) {
    if (!empty($filaAsig['sin_asignacion_red'])) {
        $totalSinAsignacionRedBusqueda++;
    }
}

$totalesEtapaEscalera = [
    'ganar' => 0,
    'consolidar' => 0,
    'discipular' => 0,
    'enviar' => 0,
];
foreach ($rowsTabla as $filaEtapa) {
    $okDiscipuloEtapa = !empty($filaEtapa['es_discipulo']);
    if ($vistaPastoralRedSoloLectura) {
        $okDiscipuloEtapa = $okDiscipuloEtapa && empty($filaEtapa['es_lider_144']) && empty($filaEtapa['es_lider_celula']);
    }
    if (!$okDiscipuloEtapa) {
        continue;
    }
    $generoEtapa = strtolower(trim((string)($filaEtapa['genero'] ?? 'sin_genero')));
    if ($filtroGeneroGet !== 'todos' && $generoEtapa !== $filtroGeneroGet) {
        continue;
    }
    if (!$filaPasaFiltroLider($filaEtapa)) {
        continue;
    }
    if (!$filaCoincideBusqueda($filaEtapa)) {
        continue;
    }
    $claveEtapa = (string)($filaEtapa['proceso'] ?? 'ganar');
    if ($claveEtapa === '' || !isset($totalesEtapaEscalera[$claveEtapa])) {
        $claveEtapa = 'ganar';
    }
    $totalesEtapaEscalera[$claveEtapa]++;
}
if ($etapaEscaleraFiltro !== '' && $tabActivo === 'discipulos') {
    $rowsTablaFiltradas = array_values(array_filter($rowsTablaFiltradas, static function ($row) use ($etapaEscaleraFiltro) {
        return (string)($row['proceso'] ?? '') === $etapaEscaleraFiltro;
    }));
}

$idPerfilPrincipal = 0;
if ($filtroGeneroGet === 'mujeres' && $idLiderPrincipal2 > 0) {
    $idPerfilPrincipal = $idLiderPrincipal2;
    if ($nombreLiderPrincipal2 !== '') {
        $nombrePastor = $nombreLiderPrincipal2;
    }
} elseif ($filtroGeneroGet === 'hombres' && $idLiderPrincipal1 > 0) {
    $idPerfilPrincipal = $idLiderPrincipal1;
    if ($nombreLiderPrincipal1 !== '') {
        $nombrePastor = $nombreLiderPrincipal1;
    }
} elseif ($idLiderPrincipal1 > 0) {
    $idPerfilPrincipal = $idLiderPrincipal1;
    if ($nombreLiderPrincipal1 !== '') {
        $nombrePastor = $nombreLiderPrincipal1;
    }
} elseif ($idLiderPrincipal2 > 0) {
    $idPerfilPrincipal = $idLiderPrincipal2;
    if ($nombreLiderPrincipal2 !== '') {
        $nombrePastor = $nombreLiderPrincipal2;
    }
}

if ($idPerfilPrincipal > 0) {
    $perfilPrincipal = null;
    foreach ($rowsTabla as $rowPerfil) {
        if ((int)($rowPerfil['id'] ?? 0) === $idPerfilPrincipal) {
            $perfilPrincipal = $rowPerfil;
            break;
        }
    }

    if (is_array($perfilPrincipal)) {
        $nombrePerfil = trim((string)($perfilPrincipal['nombre'] ?? '') . ' ' . (string)($perfilPrincipal['apellido'] ?? ''));
        if ($nombrePerfil !== '') {
            $nombrePastor = $nombrePerfil;
        }

        $emailPerfil = trim((string)($perfilPrincipal['email'] ?? ''));
        if ($emailPerfil !== '') {
            $emailPastor = $emailPerfil;
        }

        $telefonoPerfil = trim((string)($perfilPrincipal['telefono'] ?? ''));
        if ($telefonoPerfil !== '') {
            $telefonoPastor = $telefonoPerfil;
        }
    }
}

$equipoDirectoPorLider = $equipoDirectoDesdeController ?? [];
if ($equipoDirectoPorLider === []) {
foreach ($rowsTabla as $row) {
    $idLiderActualRow = (int)($row['id_lider_actual'] ?? 0);
    $idPersonaRow = (int)($row['id'] ?? 0);
    if ($idLiderActualRow <= 0 || $idPersonaRow <= 0) {
        continue;
    }

    $nombreCompletoRow = trim((string)($row['nombre'] ?? '') . ' ' . (string)($row['apellido'] ?? ''));
    $documentoRow = trim((string)($row['numero_documento'] ?? ''));
    $telefonoRow = trim((string)($row['telefono'] ?? ''));
    $emailRow = trim((string)($row['email'] ?? ''));
    $nombreRolRow = trim((string)($row['nombre_rol'] ?? ''));
    $searchRow = strtolower(trim($nombreCompletoRow . ' ' . $documentoRow . ' ' . $telefonoRow . ' ' . $emailRow . ' ' . $nombreRolRow));

    if (!isset($equipoDirectoPorLider[$idLiderActualRow])) {
        $equipoDirectoPorLider[$idLiderActualRow] = [];
    }

    $equipoDirectoPorLider[$idLiderActualRow][] = [
        'id_persona' => $idPersonaRow,
        'nombre' => $nombreCompletoRow,
        'documento' => $documentoRow,
        'telefono' => $telefonoRow,
        'email' => $emailRow,
        'nombre_rol' => $nombreRolRow,
        'search' => $searchRow,
    ];
}

foreach ($equipoDirectoPorLider as &$equipoLider) {
    usort($equipoLider, static function($a, $b) {
        return strcasecmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
    });

    foreach ($equipoLider as $index => &$personaEquipo) {
        $personaEquipo['slot_numero'] = $index + 1;
    }
    unset($personaEquipo);
}
unset($equipoLider);
}

$cupoNumeroPorPersona = [];
foreach ($equipoDirectoPorLider as $equipoLider) {
    if (!is_array($equipoLider)) {
        continue;
    }
    foreach ($equipoLider as $personaEquipo) {
        if (!is_array($personaEquipo) || empty($personaEquipo['id_persona'])) {
            continue;
        }
        $idPersonaEquipo = (int)$personaEquipo['id_persona'];
        $cupoPersistido = (int)($personaEquipo['numero_cupo'] ?? 0);
        if ($idPersonaEquipo > 0 && $cupoPersistido >= 1 && $cupoPersistido <= 12) {
            $cupoNumeroPorPersona[$idPersonaEquipo] = $cupoPersistido;
        }
    }
}

$resumenLiderPorId = [];
foreach ($liderazgoRed as $lrJer) {
    $idJer = (int)($lrJer['Id_Persona'] ?? 0);
    if ($idJer <= 0) {
        continue;
    }
    $resumenLiderPorId[$idJer] = [
        'nombre_completo' => trim((string)($lrJer['Nombre'] ?? '') . ' ' . (string)($lrJer['Apellido'] ?? '')),
        'nombre_rol' => trim((string)($lrJer['Nombre_Rol'] ?? '')),
        'red_total' => (int)($lrJer['Red_Total'] ?? 0),
        'equipo_directo' => (int)($lrJer['Equipo_Directo'] ?? 0),
        'id_lider' => (int)($lrJer['Id_Lider'] ?? 0),
    ];
}
foreach ([
    $idLiderPrincipal1 => $nombreLiderPrincipal1,
    $idLiderPrincipal2 => $nombreLiderPrincipal2,
] as $idPrincipalJer => $nombrePrincipalJer) {
    $idPrincipalJer = (int)$idPrincipalJer;
    if ($idPrincipalJer <= 0 || isset($resumenLiderPorId[$idPrincipalJer])) {
        continue;
    }
    $resumenLiderPorId[$idPrincipalJer] = [
        'nombre_completo' => trim((string)$nombrePrincipalJer),
        'nombre_rol' => $idPrincipalJer === $idLiderPrincipal1
            ? ($usarEtiquetasPastorales ? 'Pastor principal' : 'Líder principal hombres')
            : ($usarEtiquetasPastorales ? 'Pastora principal' : 'Líder principal mujeres'),
        'red_total' => 0,
        'equipo_directo' => 0,
        'id_lider' => 0,
    ];
}
if (($esVistaPropiaLider12 || $esVistaPropiaLider144) && $idUsuarioSesion > 0 && !isset($resumenLiderPorId[$idUsuarioSesion])) {
    $resumenLiderPorId[$idUsuarioSesion] = [
        'nombre_completo' => trim((string)($_SESSION['usuario_nombre'] ?? 'Tu equipo')),
        'nombre_rol' => $esVistaPropiaLider144 ? 'Líder de 144' : 'Líder de 12',
        'red_total' => 0,
        'equipo_directo' => 0,
        'id_lider' => 0,
    ];
}

$contarEquipoOcupadoFn = static function (array $mapa, int $idLider): int {
    if ($idLider <= 0) {
        return 0;
    }
    $slots = is_array($mapa[$idLider] ?? null) ? $mapa[$idLider] : [];
    $n = 0;
    foreach ($slots as $slot) {
        if (!is_array($slot) || empty($slot['id_persona'])) {
            continue;
        }
        $cupo = (int)($slot['numero_cupo'] ?? 0);
        if ($cupo >= 1 && $cupo <= 12) {
            $n++;
        }
    }
    return min(12, $n);
};

$ordenarMiembrosEquipoPrincipalEnSlots = static function (array $miembros): array {
    $slots = array_fill(0, 12, null);
    foreach ($miembros as $miembro) {
        if (!is_array($miembro) || empty($miembro['id_persona'])) {
            continue;
        }
        $n = (int)($miembro['numero_cupo'] ?? 0);
        if ($n >= 1 && $n <= 12 && $slots[$n - 1] === null) {
            $miembro['slot_numero'] = $n;
            $slots[$n - 1] = $miembro;
        }
    }
    return $slots;
};

$personaModelEquipoPrincipal = new Persona();
$idsLideresParaCupoJerarquia = array_values(array_unique(array_filter(array_merge(
    array_keys($resumenLiderPorId),
    array_keys($equipoDirectoPorLider),
    [$idLiderPrincipal1, $idLiderPrincipal2, (int)$coberturaPrincipalActual]
), static function ($id) {
    return (int)$id > 0;
})));

$equipoPrincipalPorCupoPorLider = $personaModelEquipoPrincipal->contarEquipoPrincipalPorCupoBatch($idsLideresParaCupoJerarquia);

foreach ($idsLideresParaCupoJerarquia as $idLiderCupoSync) {
    $idLiderCupoSync = (int)$idLiderCupoSync;
    if ($idLiderCupoSync <= 0) {
        continue;
    }

    $rowsDb = $personaModelEquipoPrincipal->getMiembrosEquipoPrincipalPorCupo($idLiderCupoSync);
    $miembrosFormateados = [];
    foreach ((array)$rowsDb as $rowDb) {
        $idPersonaDb = (int)($rowDb['Id_Persona'] ?? 0);
        if ($idPersonaDb <= 0) {
            continue;
        }
        $nombreDb = trim((string)($rowDb['Nombre'] ?? '') . ' ' . (string)($rowDb['Apellido'] ?? ''));
        $miembrosFormateados[] = [
            'id_persona' => $idPersonaDb,
            'nombre' => $nombreDb !== '' ? $nombreDb : ('Persona ' . $idPersonaDb),
            'documento' => trim((string)($rowDb['Numero_Documento'] ?? '')),
            'telefono' => trim((string)($rowDb['Telefono'] ?? '')),
            'email' => trim((string)($rowDb['Email'] ?? '')),
            'nombre_rol' => trim((string)($rowDb['Nombre_Rol'] ?? '')),
            'numero_cupo' => (int)($rowDb['Numero_Cupo'] ?? 0),
        ];
    }

    $equipoDirectoPorLider[$idLiderCupoSync] = $ordenarMiembrosEquipoPrincipalEnSlots($miembrosFormateados);
}

$equipoDirectoPorLiderJson = json_encode($equipoDirectoPorLider, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($equipoDirectoPorLiderJson)) {
    $equipoDirectoPorLiderJson = '{}';
}

$contarEquipoPrincipalFn = static function (array $mapa, int $idLider) use ($equipoPrincipalPorCupoPorLider, $contarEquipoOcupadoFn): int {
    $idLider = (int)$idLider;
    if ($idLider <= 0) {
        return 0;
    }
    if (isset($equipoPrincipalPorCupoPorLider[$idLider])) {
        return (int)$equipoPrincipalPorCupoPorLider[$idLider];
    }
    return $contarEquipoOcupadoFn($mapa, $idLider);
};

foreach ($resumenLiderPorId as $idJerSync => &$infoJer) {
    $idJerSync = (int)$idJerSync;
    if ($idJerSync <= 0) {
        continue;
    }
    $infoJer['equipo_directo'] = $contarEquipoPrincipalFn($equipoDirectoPorLider, $idJerSync);
}
unset($infoJer);

foreach ([$idLiderPrincipal1, $idLiderPrincipal2] as $idPrincipalSync) {
    $idPrincipalSync = (int)$idPrincipalSync;
    if ($idPrincipalSync <= 0 || !isset($resumenLiderPorId[$idPrincipalSync])) {
        continue;
    }
    $resumenLiderPorId[$idPrincipalSync]['equipo_directo'] = $contarEquipoPrincipalFn($equipoDirectoPorLider, $idPrincipalSync);
}

$obtenerNombreLiderFn = static function (int $id, array $mapa): string {
    if ($id <= 0) {
        return 'Sin asignar';
    }
    $row = $mapa[$id] ?? null;
    if (!is_array($row)) {
        return 'Líder #' . $id;
    }
    $nombre = trim((string)($row['nombre_completo'] ?? ''));
    return $nombre !== '' ? $nombre : ('Líder #' . $id);
};

$buildJerarquiaUrl = static function (int $nodoId, string $generoRed = '') use ($idMinisterioFiltro, $buscarGet): string {
    $params = ['url' => 'discipular/ministerios/equipo-principal'];
    if ($idMinisterioFiltro > 0) {
        $params['id_ministerio'] = $idMinisterioFiltro;
    }
    if ($nodoId > 0) {
        $params['cobertura_principal'] = $nodoId;
    }
    if ($generoRed !== '') {
        $params['genero_red'] = $generoRed;
    }
    if ($buscarGet !== '') {
        $params['buscar'] = $buscarGet;
    }
    return PUBLIC_URL . '?' . http_build_query($params);
};

$cadenaBreadcrumbJerarquia = [];
$nodoJerarquiaActivo = (int)$coberturaPrincipalActual;
if ($generoRedActual !== '' && $nodoJerarquiaActivo <= 0) {
    $cadenaBreadcrumbJerarquia[] = [
        'id' => 0,
        'nombre' => $generoRedActual === 'mujeres' ? 'Red Mujeres' : 'Red Hombres',
        'activo' => true,
        'url' => '',
    ];
} elseif ($nodoJerarquiaActivo > 0) {
    $cadenaCrumb = [];
    $actualCrumb = $nodoJerarquiaActivo;
    $visitadosCrumb = [];
    while ($actualCrumb > 0 && !isset($visitadosCrumb[$actualCrumb])) {
        $visitadosCrumb[$actualCrumb] = true;
        $infoCrumb = $resumenLiderPorId[$actualCrumb] ?? null;
        $nombreCrumb = is_array($infoCrumb) ? trim((string)($infoCrumb['nombre_completo'] ?? '')) : '';
        if ($nombreCrumb === '') {
            if ($actualCrumb === $idLiderPrincipal1) {
                $nombreCrumb = $nombreLiderPrincipal1;
            } elseif ($actualCrumb === $idLiderPrincipal2) {
                $nombreCrumb = $nombreLiderPrincipal2;
            } else {
                $nombreCrumb = 'Líder #' . $actualCrumb;
            }
        }
        array_unshift($cadenaCrumb, [
            'id' => $actualCrumb,
            'nombre' => $nombreCrumb,
            'activo' => false,
            'url' => '',
        ]);
        $padreCrumb = is_array($infoCrumb) ? (int)($infoCrumb['id_lider'] ?? 0) : 0;
        if ($padreCrumb <= 0) {
            break;
        }
        $actualCrumb = $padreCrumb;
    }
    if (!empty($cadenaCrumb)) {
        $cadenaCrumb[count($cadenaCrumb) - 1]['activo'] = true;
        if ($generoRedActual !== '') {
            array_unshift($cadenaCrumb, [
                'id' => 0,
                'nombre' => $generoRedActual === 'mujeres' ? 'Red Mujeres' : 'Red Hombres',
                'activo' => false,
                'url' => $buildJerarquiaUrl(0, $generoRedActual),
            ]);
        }
        foreach ($cadenaCrumb as &$crumbItem) {
            if (!empty($crumbItem['activo'])) {
                continue;
            }
            $genCrumb = $generoRedActual;
            if ($genCrumb === '' && (int)($crumbItem['id'] ?? 0) === $idLiderPrincipal2) {
                $genCrumb = 'mujeres';
            } elseif ($genCrumb === '' && (int)($crumbItem['id'] ?? 0) === $idLiderPrincipal1) {
                $genCrumb = 'hombres';
            }
            $crumbItem['url'] = $buildJerarquiaUrl((int)($crumbItem['id'] ?? 0), $genCrumb);
        }
        unset($crumbItem);
        $cadenaBreadcrumbJerarquia = $cadenaCrumb;
    }
}

if ($generoRedActual === '' && $nodoJerarquiaActivo > 0) {
    if ($nodoJerarquiaActivo === $idLiderPrincipal2) {
        $generoRedActual = 'mujeres';
    } elseif ($nodoJerarquiaActivo === $idLiderPrincipal1) {
        $generoRedActual = 'hombres';
    }
}

$cuposLibresTabla = [];
$construirCuposLibres12 = static function (
    array $idsLideresCupo,
    array $principalesMeta,
    array $equipoDirectoPorLider,
    string $coberturaPrincipalActual,
    string $filtroGeneroGet
) {
    $libres = [];
    foreach ($principalesMeta as $meta) {
        $idLiderMeta = (int)($meta['id_lider'] ?? 0);
        if ($idLiderMeta <= 0 || !in_array($idLiderMeta, $idsLideresCupo, true)) {
            continue;
        }
        if ($coberturaPrincipalActual !== '' && $coberturaPrincipalActual !== (string)$idLiderMeta) {
            continue;
        }
        if ($filtroGeneroGet !== 'todos' && $filtroGeneroGet !== (string)($meta['genero'] ?? '')) {
            continue;
        }

        $ocupados = [];
        $equipoLider = is_array($equipoDirectoPorLider[$idLiderMeta] ?? null) ? $equipoDirectoPorLider[$idLiderMeta] : [];
        foreach ($equipoLider as $personaEquipo) {
            if (!is_array($personaEquipo) || empty($personaEquipo['id_persona'])) {
                continue;
            }
            $slot = (int)($personaEquipo['numero_cupo'] ?? 0);
            if ($slot >= 1 && $slot <= 12) {
                $ocupados[$slot] = true;
            }
        }

        for ($slot = 1; $slot <= 12; $slot++) {
            if (!empty($ocupados[$slot])) {
                continue;
            }
            $libres[] = [
                'id_lider' => $idLiderMeta,
                'nombre_lider' => (string)($meta['nombre_lider'] ?? ('Líder ' . $idLiderMeta)),
                'genero' => (string)($meta['genero'] ?? 'hombres'),
                'slot_numero' => $slot,
            ];
        }
    }
    return $libres;
};

$construirFilasCupos12Tabla = static function (
    array $idsLideresCupo,
    array $principalesMeta,
    array $personaPorLiderCupo,
    string $coberturaPrincipalActual,
    string $filtroGeneroGet
): array {
    $filas = [];
    $metaPorIdLider = [];
    foreach ($principalesMeta as $meta) {
        $idMeta = (int)($meta['id_lider'] ?? 0);
        if ($idMeta > 0) {
            $metaPorIdLider[$idMeta] = $meta;
        }
    }

    foreach ($idsLideresCupo as $idLiderMetaRaw) {
        $idLiderMeta = (int)$idLiderMetaRaw;
        if ($idLiderMeta <= 0) {
            continue;
        }
        if ($coberturaPrincipalActual !== '' && $coberturaPrincipalActual !== (string)$idLiderMeta) {
            continue;
        }

        $meta = $metaPorIdLider[$idLiderMeta] ?? [
            'id_lider' => $idLiderMeta,
            'nombre_lider' => 'Líder de 12 ' . $idLiderMeta,
            'genero' => 'hombres',
        ];
        if ($filtroGeneroGet !== 'todos' && $filtroGeneroGet !== (string)($meta['genero'] ?? '')) {
            continue;
        }

        $nombreLider = (string)($meta['nombre_lider'] ?? ('Líder ' . $idLiderMeta));
        $generoLider = (string)($meta['genero'] ?? 'hombres');
        $cuposLider = is_array($personaPorLiderCupo[$idLiderMeta] ?? null) ? $personaPorLiderCupo[$idLiderMeta] : [];

        for ($slot = 1; $slot <= 12; $slot++) {
            if (!empty($cuposLider[$slot]) && is_array($cuposLider[$slot])) {
                $filas[] = [
                    'tipo' => 'ocupado',
                    'slot_numero' => $slot,
                    'id_lider' => $idLiderMeta,
                    'nombre_lider' => $nombreLider,
                    'genero' => $generoLider,
                    'row' => $cuposLider[$slot],
                ];
            } else {
                $filas[] = [
                    'tipo' => 'libre',
                    'slot_numero' => $slot,
                    'id_lider' => $idLiderMeta,
                    'nombre_lider' => $nombreLider,
                    'genero' => $generoLider,
                ];
            }
        }
    }
    return $filas;
};

$principalesMetaCupos = [];
$metaCuposPorId = [];
$registrarMetaCupoLider = static function (int $idL12, string $nombreL12, string $generoL12) use (&$metaCuposPorId): void {
    if ($idL12 <= 0 || isset($metaCuposPorId[$idL12])) {
        return;
    }
    $metaCuposPorId[$idL12] = [
        'id_lider' => $idL12,
        'nombre_lider' => $nombreL12 !== '' ? $nombreL12 : ('Líder de 12 ' . $idL12),
        'genero' => $generoL12 !== '' ? $generoL12 : 'hombres',
    ];
};
if ($hayFiltroMinisterio) {
    foreach ($idsLideres12CupoMinisterio as $idPrincipalTmp) {
        $nombreTmp = '';
        $generoTmp = 'hombres';
        if ((int)$idPrincipalTmp === $idLiderPrincipal1) {
            $nombreTmp = $nombreLiderPrincipal1;
            $generoTmp = 'hombres';
        } elseif ((int)$idPrincipalTmp === $idLiderPrincipal2) {
            $nombreTmp = $nombreLiderPrincipal2;
            $generoTmp = 'mujeres';
        }
        $registrarMetaCupoLider((int)$idPrincipalTmp, $nombreTmp, $generoTmp);
    }
    $principalesMetaCupos = array_values($metaCuposPorId);
} else {
    if ($idLiderPrincipal1 > 0) {
        $principalesMetaCupos[] = [
            'id_lider' => $idLiderPrincipal1,
            'nombre_lider' => $nombreLiderPrincipal1 !== '' ? $nombreLiderPrincipal1 : 'Pastor principal',
            'genero' => 'hombres',
        ];
    }
    if ($idLiderPrincipal2 > 0) {
        $principalesMetaCupos[] = [
            'id_lider' => $idLiderPrincipal2,
            'nombre_lider' => $nombreLiderPrincipal2 !== '' ? $nombreLiderPrincipal2 : 'Pastora principal',
            'genero' => 'mujeres',
        ];
    }
}

if ($tabActivo === 'equipo_principal' && $mostrarTabEquipoPrincipal && !empty($idsLideresPrincipalesCupo)) {
    $cuposLibresTabla = $construirCuposLibres12(
        $idsLideresPrincipalesCupo,
        $principalesMetaCupos,
        $equipoDirectoPorLider,
        $coberturaPrincipalActual,
        $filtroGeneroGet
    );
} elseif ($tabActivo === 'lideres_144' && $hayFiltroMinisterio && !empty($idsLideres12CupoMinisterio)) {
    $cuposLibresTabla = $construirCuposLibres12(
        $idsLideres12CupoMinisterio,
        $principalesMetaCupos,
        $equipoDirectoPorLider,
        $coberturaPrincipalActual,
        $filtroGeneroGet
    );
}

$idsLideresParaCupos144 = [];
$metaParaCupos144 = [];
if ($hayFiltroMinisterio || $esVistaPropiaLider12) {
    $idsLideresParaCupos144 = $idsLideres12CupoMinisterio;
    $metaParaCupos144 = $principalesMetaCupos;
}

$filasCupos144Ministerio = [];
$usarFilasCuposFijas144 = !$vistaPastoralRedSoloLectura
    && $tabActivo === 'lideres_144'
    && !empty($idsLideresParaCupos144);
$tabMuestraColumnaCupo = !$vistaPastoralRedSoloLectura || $tabActivo === 'equipo_principal';
$tablaDiscipulosCompacta = ($tabActivo === 'discipulos');
$tablaLideresPorMinisterio = !$tablaDiscipulosCompacta && !$tabMuestraColumnaCupo;
if ($tablaDiscipulosCompacta) {
    $colspanTabla = 7;
} elseif ($tablaLideresPorMinisterio) {
    $colspanTabla = 6;
} else {
    $colspanTabla = $tabMuestraColumnaCupo ? 7 : 8;
}
if ($usarFilasCuposFijas144) {
    $personaPorLiderCupo144 = [];
    $rowsTablaPorId = [];
    foreach ($rowsTabla as $rowMapa) {
        $idMapa = (int)($rowMapa['id'] ?? 0);
        if ($idMapa > 0) {
            $rowsTablaPorId[$idMapa] = $rowMapa;
        }
    }

    foreach ($idsLideresParaCupos144 as $idLiderCupoEquipo) {
        $idLiderCupoEquipo = (int)$idLiderCupoEquipo;
        if ($idLiderCupoEquipo <= 0) {
            continue;
        }
        $slotsEquipo = is_array($equipoDirectoPorLider[$idLiderCupoEquipo] ?? null)
            ? $equipoDirectoPorLider[$idLiderCupoEquipo]
            : [];
        foreach ($slotsEquipo as $idxSlot => $personaEquipo) {
            if (!is_array($personaEquipo) || empty($personaEquipo['id_persona'])) {
                continue;
            }
            $idPersonaCupo = (int)($personaEquipo['id_persona'] ?? 0);
            $numCupo = (int)($personaEquipo['numero_cupo'] ?? 0);
            if ($numCupo < 1 || $numCupo > 12) {
                $numCupo = (int)$idxSlot + 1;
            }
            if ($idPersonaCupo <= 0 || $numCupo < 1 || $numCupo > 12) {
                continue;
            }

            if (isset($rowsTablaPorId[$idPersonaCupo])) {
                $personaPorLiderCupo144[$idLiderCupoEquipo][$numCupo] = $rowsTablaPorId[$idPersonaCupo];
                continue;
            }

            $nombreCompleto = trim((string)($personaEquipo['nombre'] ?? ''));
            $partesNombre = preg_split('/\s+/', $nombreCompleto, 2) ?: [];
            $personaPorLiderCupo144[$idLiderCupoEquipo][$numCupo] = [
                'id' => $idPersonaCupo,
                'nombre' => (string)($partesNombre[0] ?? $nombreCompleto),
                'apellido' => (string)($partesNombre[1] ?? ''),
                'numero_documento' => trim((string)($personaEquipo['documento'] ?? '')),
                'telefono' => trim((string)($personaEquipo['telefono'] ?? '')),
                'email' => trim((string)($personaEquipo['email'] ?? '')),
                'nombre_rol' => trim((string)($personaEquipo['nombre_rol'] ?? '')),
                'id_lider_actual' => $idLiderCupoEquipo,
            ];
        }
    }

    $filasCupos144Ministerio = $construirFilasCupos12Tabla(
        $idsLideresParaCupos144,
        $metaParaCupos144,
        $personaPorLiderCupo144,
        $coberturaPrincipalActual,
        $filtroGeneroGet
    );

    $idsPersonasEnGrilla144 = [];
    foreach ($filasCupos144Ministerio as $filaGrilla144) {
        if (($filaGrilla144['tipo'] ?? '') !== 'ocupado' || !is_array($filaGrilla144['row'] ?? null)) {
            continue;
        }
        $idEnGrilla144 = (int)($filaGrilla144['row']['id'] ?? 0);
        if ($idEnGrilla144 > 0) {
            $idsPersonasEnGrilla144[$idEnGrilla144] = true;
        }
    }
    foreach ($rowsTabla as $row144Sueltos) {
        if (empty($row144Sueltos['es_lider_144'])) {
            continue;
        }
        $id144Sueltos = (int)($row144Sueltos['id'] ?? 0);
        if ($id144Sueltos <= 0 || isset($idsPersonasEnGrilla144[$id144Sueltos])) {
            continue;
        }
        $genero144 = (string)($row144Sueltos['genero'] ?? 'hombres');
        if ($filtroGeneroGet !== 'todos' && $genero144 !== $filtroGeneroGet) {
            continue;
        }
        if ($hayBusquedaActiva && !$filaCoincideBusqueda($row144Sueltos)) {
            continue;
        }
        $idLider144Sueltos = (int)($row144Sueltos['id_lider_actual'] ?? 0);
        $filasCupos144Ministerio[] = [
            'tipo' => 'ocupado',
            'slot_numero' => (int)($cupoNumeroPorPersona[$id144Sueltos] ?? 0),
            'id_lider' => $idLider144Sueltos,
            'nombre_lider' => trim((string)($row144Sueltos['nombre_lider_actual'] ?? '')),
            'genero' => $genero144,
            'row' => $row144Sueltos,
        ];
    }

    if ($hayBusquedaActiva) {
        $filasCupos144Ministerio = array_values(array_filter($filasCupos144Ministerio, static function($filaCupo144) use ($filaCoincideBusqueda) {
            if (($filaCupo144['tipo'] ?? '') === 'libre') {
                return false;
            }
            $rowBuscar = is_array($filaCupo144['row'] ?? null) ? $filaCupo144['row'] : [];
            return $filaCoincideBusqueda($rowBuscar);
        }));
    }
}

$mostrarCuerpoTabla = !empty($rowsTablaFiltradas) || !empty($cuposLibresTabla) || !empty($filasCupos144Ministerio);

$totalesPersonasMinisterio = is_array($totales_personas_ministerio ?? null)
    ? $totales_personas_ministerio
    : ['total' => 0, 'hombres' => 0, 'mujeres' => 0];
$totalPersonasMinisterio = (int)($totalesPersonasMinisterio['total'] ?? 0);
$totalPersonasMinisterioHombres = (int)($totalesPersonasMinisterio['hombres'] ?? 0);
$totalPersonasMinisterioMujeres = (int)($totalesPersonasMinisterio['mujeres'] ?? 0);
$totalEnRedPastoral = (int)($total_en_red_pastoral ?? 0);
$totalSinClasificarRed = max(0, $totalPersonasMinisterio - $totalEnRedPastoral);

$puedeEditarMinisterioKpi = AuthController::esAdministrador() || AuthController::puede('ministerios:editar');
$urlGestionarMinisterio = $urlMinisteriosLista;
$textoGestionarMinisterio = 'Gestionar ministerios';
$etiquetaKpiDerecho = 'Ministerios';
$valorKpiDerecho = $ministerioCantidad;
if ($hayFiltroMinisterio) {
    $etiquetaKpiDerecho = 'Ministerio';
    $valorKpiDerecho = 1;
    $textoGestionarMinisterio = 'Gestionar ministerio';
    if ($puedeEditarMinisterioKpi) {
        $urlGestionarMinisterio = PUBLIC_URL . '?url=discipular/ministerios/editar&id=' . $idMinisterioFiltro;
    }
}
$etiquetaKpiIzquierdo = $hayFiltroMinisterio
    ? 'Personas del ministerio'
    : ($esCoberturaPastoralGlobal ? 'Personas de la iglesia' : 'Personas en cobertura');
$totalKpiIzquierdo = $kpiTotalVista > 0 ? $kpiTotalVista : $totalPersonasMinisterio;
$kpiIzquierdoHombres = $kpiTotalVista > 0 ? $kpiHombresVista : $totalPersonasMinisterioHombres;
$kpiIzquierdoMujeres = $kpiTotalVista > 0 ? $kpiMujeresVista : $totalPersonasMinisterioMujeres;

$buildEquipoUrl = static function(array $override = []) use ($idMinisterioFiltro, $tabActivo, $filtroGeneroGet, $buscarGet, $coberturaPrincipalActual, $vistaAvanzadaActiva, $etapaEscaleraFiltro, $generoRedActual, $filtroLiderGet) {
    $params = [
        'url' => 'discipular/ministerios/equipo-principal',
        'tab' => $tabActivo,
        'genero' => $filtroGeneroGet,
    ];

    if ($vistaAvanzadaActiva) {
        $params['vista_avanzada'] = '1';
    }

    if ($etapaEscaleraFiltro !== '') {
        $params['etapa'] = $etapaEscaleraFiltro;
    }

    if ($idMinisterioFiltro > 0) {
        $params['id_ministerio'] = $idMinisterioFiltro;
    }

    if ($buscarGet !== '') {
        $params['buscar'] = $buscarGet;
    }

    if ($coberturaPrincipalActual !== '') {
        $params['cobertura_principal'] = $coberturaPrincipalActual;
    }

    if ($generoRedActual !== '') {
        $params['genero_red'] = $generoRedActual;
    }

    if ($filtroLiderGet !== '') {
        $params['lider'] = $filtroLiderGet;
    }

    foreach ($override as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
            continue;
        }
        $params[$k] = $v;
    }

    if (($params['tab'] ?? '') !== 'discipulos') {
        unset($params['etapa']);
    }

    return PUBLIC_URL . '?' . http_build_query($params);
};

$liderGestionCuposId = 0;
$liderGestionCuposNombre = '';
if ($hayFiltroMinisterio) {
    if ($coberturaPrincipalActual !== '' && ctype_digit($coberturaPrincipalActual)) {
        $liderGestionCuposId = (int)$coberturaPrincipalActual;
    } elseif ($idLiderPrincipal1 > 0) {
        $liderGestionCuposId = $idLiderPrincipal1;
    } elseif ($idLiderPrincipal2 > 0) {
        $liderGestionCuposId = $idLiderPrincipal2;
    }
} else {
    if ($generoRedActual === 'mujeres' && $idLiderPrincipal2 > 0) {
        $liderGestionCuposId = $idLiderPrincipal2;
    } elseif ($generoRedActual === 'hombres' && $idLiderPrincipal1 > 0) {
        $liderGestionCuposId = $idLiderPrincipal1;
    } elseif ($filtroGeneroGet === 'mujeres' && $idLiderPrincipal2 > 0) {
        $liderGestionCuposId = $idLiderPrincipal2;
    } elseif ($filtroGeneroGet === 'hombres' && $idLiderPrincipal1 > 0) {
        $liderGestionCuposId = $idLiderPrincipal1;
    } elseif ($coberturaPrincipalActual !== '' && ctype_digit($coberturaPrincipalActual)) {
        $liderGestionCuposId = (int)$coberturaPrincipalActual;
    } elseif ($idLiderPrincipal1 > 0) {
        $liderGestionCuposId = $idLiderPrincipal1;
    } elseif ($idLiderPrincipal2 > 0) {
        $liderGestionCuposId = $idLiderPrincipal2;
    }
}

if ($liderGestionCuposId === $idLiderPrincipal1) {
    $liderGestionCuposNombre = $nombreLiderPrincipal1;
} elseif ($liderGestionCuposId === $idLiderPrincipal2) {
    $liderGestionCuposNombre = $nombreLiderPrincipal2;
}

$jerarquiaLiderGestionDefault = trim((string)($jerarquiaPorLiderId[$liderGestionCuposId] ?? ''));

$vistaJerarquiaLimpia = true;
$nodoJerarquiaActivo = (int)$coberturaPrincipalActual;
$nivelJerarquia = 'inicio';
if ($nodoJerarquiaActivo > 0) {
    $nivelJerarquia = 'equipo';
} elseif ($generoRedActual !== '') {
    $nivelJerarquia = 'red';
}

$urlVistaAvanzada = PUBLIC_URL . '?' . http_build_query(array_filter([
    'url' => 'discipular/ministerios/equipo-principal',
    'vista_avanzada' => '1',
    'tab' => 'discipulos',
    'id_ministerio' => $idMinisterioFiltro > 0 ? $idMinisterioFiltro : null,
    'genero' => $generoRedActual !== '' ? $generoRedActual : ($filtroGeneroGet !== 'todos' ? $filtroGeneroGet : null),
    'genero_red' => $generoRedActual !== '' ? $generoRedActual : null,
    'buscar' => $buscarGet !== '' ? $buscarGet : null,
    'cobertura_principal' => $coberturaPrincipalActual !== '' ? $coberturaPrincipalActual : null,
]));

?>

<div class="equipo-shell<?= !empty($vistaJerarquiaLimpia) ? ' equipo-shell--jerarquia' : '' ?>">
    <?php if ($asignacionOk || $asignacionError): ?>
    <div class="alert <?= $asignacionError ? 'alert-danger' : 'alert-success' ?>" style="margin-bottom:4px;">
        <?= htmlspecialchars($asignacionMsg !== '' ? $asignacionMsg : ($asignacionError ? 'No se pudo asignar el cupo.' : 'Cupo asignado correctamente.')) ?>
    </div>
    <?php endif; ?>
    <?php if ($reasignacionOk || $reasignacionError): ?>
    <div class="alert <?= $reasignacionError ? 'alert-danger' : 'alert-success' ?>" style="margin-bottom:4px;">
        <?= htmlspecialchars($reasignacionMsg !== '' ? $reasignacionMsg : ($reasignacionError ? 'No se pudo reasignar el cupo.' : 'Reasignación realizada correctamente.')) ?>
    </div>
    <?php endif; ?>
    <?php if ($lpOk || $lpError): ?>
    <div class="alert <?= $lpError ? 'alert-danger' : 'alert-success' ?>" style="margin-bottom:4px;">
        <?= htmlspecialchars($lpMsg !== '' ? $lpMsg : ($lpError ? $textoErrorGuardarLiderazgo : $textoOkGuardarLiderazgo)) ?>
    </div>
    <?php endif; ?>

    <?php if ($vistaJerarquiaLimpia): ?>
    <header class="jer-topbar jer-topbar--solo-titulo">
        <h2 class="jer-topbar-title">Discipular</h2>
        <?php if (AuthController::esAdministrador() || AuthController::puede('ministerios:ver')): ?>
        <a href="<?= htmlspecialchars($urlMinisteriosLista) ?>" class="btn btn-primary jer-btn-ministerios-metas" title="Ver los ministerios, sus células y configurar sus metas">
            <i class="bi bi-flag-fill" aria-hidden="true"></i> Ministerios y metas
        </a>
        <?php endif; ?>
        <?php if ($puedeConfigurarLideresPrincipales): ?>
        <button type="button" id="btnEditarLiderazgo" class="visually-hidden" tabindex="-1" aria-hidden="true" title="<?= htmlspecialchars($tituloBotonEditarLiderazgo) ?>">Configurar</button>
        <?php endif; ?>
    </header>
    <?php endif; ?>

    <?php if ($vistaJerarquiaLimpia): ?>
    <?php include VIEWS . '/discipular/partials/jerarquia_red_navegacion.php'; ?>
    <?php endif; ?>

    <div id="modalEditarLiderazgo" class="cupos-modal" aria-hidden="true">
        <div class="cupos-modal-backdrop" data-close-modal-liderazgo="1"></div>
        <section class="card cupos-card cupos-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalEditarLiderazgoTitulo">
            <div class="cupos-head">
                <h4 id="modalEditarLiderazgoTitulo"><?= htmlspecialchars($tituloModalEditarLiderazgo) ?></h4>
                <button type="button" id="cerrarModalEditarLiderazgo" class="btn btn-sm btn-light" aria-label="Cerrar">✕</button>
            </div>

            <div class="form-group" style="margin-bottom:0;">
                <label style="font-weight:700; color:#365581;"><?= htmlspecialchars($labelSeccionLiderazgo) ?></label>
                <form method="post" action="<?= PUBLIC_URL ?>?url=discipular/ministerios/actualizar-lideres-principales" class="cupos-form" style="grid-template-columns:1fr 1fr 180px; margin-top:8px;">
                    <input type="hidden" name="id_ministerio" value="<?= $idMinisterioFiltro ?>">
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($urlRetornoEquipo) ?>">

                    <div>
                        <label for="id_lider_principal_1"><?= htmlspecialchars($labelLiderazgoPrincipal1) ?></label>
                        <select id="id_lider_principal_1" name="id_lider_principal_1" class="form-control form-control-sm">
                            <option value=""><?= htmlspecialchars($placeholderLiderazgoPrincipal1) ?></option>
                            <?php foreach ($candidatosHombresModal as $cand): ?>
                                <?php
                                    $idCand = (int)($cand['id_persona'] ?? 0);
                                    $nomCand = trim((string)($cand['nombre'] ?? ''));
                                    $rolCand = trim((string)($cand['rol'] ?? ''));
                                ?>
                                <option value="<?= $idCand ?>" <?= $idCand === $idLiderPrincipal1 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nomCand !== '' ? $nomCand : ('Persona ' . $idCand)) ?><?= $rolCand !== '' ? ' - ' . htmlspecialchars($rolCand) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="id_lider_principal_2"><?= htmlspecialchars($labelLiderazgoPrincipal2) ?></label>
                        <select id="id_lider_principal_2" name="id_lider_principal_2" class="form-control form-control-sm">
                            <option value=""><?= htmlspecialchars($placeholderLiderazgoPrincipal2) ?></option>
                            <?php foreach ($candidatosMujeresModal as $cand): ?>
                                <?php
                                    $idCand = (int)($cand['id_persona'] ?? 0);
                                    $nomCand = trim((string)($cand['nombre'] ?? ''));
                                    $rolCand = trim((string)($cand['rol'] ?? ''));
                                ?>
                                <option value="<?= $idCand ?>" <?= $idCand === $idLiderPrincipal2 ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($nomCand !== '' ? $nomCand : ('Persona ' . $idCand)) ?><?= $rolCand !== '' ? ' - ' . htmlspecialchars($rolCand) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:flex; align-items:flex-end;">
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;"><?= htmlspecialchars($textoBotonGuardarLiderazgo) ?></button>
                    </div>
                </form>
            </div>
        </section>
    </div>

</div>


<script>
(function() {
    const btnAbrir = document.getElementById('btnEditarLiderazgo');
    const modal = document.getElementById('modalEditarLiderazgo');
    const btnCerrar = document.getElementById('cerrarModalEditarLiderazgo');
    if (!modal) {
        return;
    }
    function abrir() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
    }
    function cerrar() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }
    if (btnAbrir) { btnAbrir.addEventListener('click', abrir); }
    if (btnCerrar) { btnCerrar.addEventListener('click', cerrar); }
    modal.addEventListener('click', function(e) {
        if (e.target && e.target.getAttribute && e.target.getAttribute('data-close-modal-liderazgo') === '1') {
            cerrar();
        }
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { cerrar(); }
    });
})();
</script>
<script src="<?= ASSETS_URL ?>/js/discipular-casillas.js?v=20261013c"></script>
<script>
(function() {
    const endpointChecklist = '<?= PUBLIC_URL ?>?url=personas/actualizarChecklistEscalera';
    const etapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
    const tarjetasEscalera = document.querySelectorAll('.js-inline-escalera');
    if (!tarjetasEscalera.length) {
        return;
    }

    function parseChecklist(raw) {
        if (!raw) {
            return {
                Ganar: [false, false, false, false, false, false],
                Consolidar: [false, false, false],
                Discipular: [false, false, false],
                Enviar: [false, false, false],
                _meta: {}
            };
        }

        try {
            const parsed = JSON.parse(raw);
            if (!parsed || typeof parsed !== 'object') {
                throw new Error('Checklist inválido');
            }

            if (!Array.isArray(parsed.Ganar)) parsed.Ganar = [false, false, false, false, false, false];
            if (!Array.isArray(parsed.Consolidar)) parsed.Consolidar = [false, false, false];
            if (!Array.isArray(parsed.Discipular)) parsed.Discipular = [false, false, false];
            if (!Array.isArray(parsed.Enviar)) parsed.Enviar = [false, false, false];

            for (let i = 0; i <= 5; i++) parsed.Ganar[i] = !!parsed.Ganar[i];
            for (let i = 0; i <= 2; i++) {
                parsed.Consolidar[i] = !!parsed.Consolidar[i];
                parsed.Discipular[i] = !!parsed.Discipular[i];
                parsed.Enviar[i] = !!parsed.Enviar[i];
            }

            if (!parsed._meta || typeof parsed._meta !== 'object') {
                parsed._meta = {};
            }

            return parsed;
        } catch (e) {
            return {
                Ganar: [false, false, false, false, false, false],
                Consolidar: [false, false, false],
                Discipular: [false, false, false],
                Enviar: [false, false, false],
                _meta: {}
            };
        }
    }

    function aplicarChecksEnVista(container, checklist) {
        container.querySelectorAll('.js-inline-escalera-check').forEach(function(check) {
            const etapa = check.getAttribute('data-etapa') || '';
            const indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
            if (!etapa || indice < 0 || !Array.isArray(checklist[etapa])) {
                return;
            }
            check.checked = !!checklist[etapa][indice];
        });
    }

    function aplicarBloqueoNoDisponible(container, checklist, puedeEditar) {
        const noDisponible = !!(checklist.Ganar && checklist.Ganar[5]);
        container.querySelectorAll('.js-inline-escalera-check').forEach(function(check) {
            const etapa = check.getAttribute('data-etapa') || 'Ganar';
            const indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
            let disabled = !puedeEditar;
            if (!disabled && noDisponible && !(etapa === 'Ganar' && indice === 5)) {
                disabled = true;
            }
            check.disabled = disabled;
        });
        container.classList.toggle('is-bloqueado', noDisponible);
    }

    async function guardarChecklistInline(container, etapa, indice, marcado) {
        const idPersona = parseInt(container.getAttribute('data-persona-id') || '0', 10);
        if (!idPersona) {
            return false;
        }

        const checklist = parseChecklist(container.getAttribute('data-checklist') || '');
        let observacionNoDisponible = '';

        if (etapa === 'Ganar' && indice === 5 && marcado) {
            const observacionActual = String((checklist._meta && checklist._meta.no_disponible_observacion) || '');
            const ingresada = window.prompt('Escribe una observación para marcar "No se dispone":', observacionActual);
            if (ingresada === null) {
                return false;
            }
            const limpia = String(ingresada || '').trim();
            if (limpia === '') {
                alert('La observación es obligatoria para "No se dispone".');
                return false;
            }
            observacionNoDisponible = limpia;
        }

        if (etapa === 'Ganar' && indice === 5 && !marcado && checklist._meta) {
            checklist._meta.no_disponible_observacion = '';
        }

        container.classList.add('is-saving');
        try {
            const response = await fetch(endpointChecklist, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    id_persona: idPersona,
                    etapa: etapa,
                    indice: indice,
                    marcado: marcado ? 1 : 0,
                    observacion_no_disponible: (etapa === 'Ganar' && indice === 5) ? observacionNoDisponible : ''
                })
            });

            const data = await response.json();
            if (!response.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'No se pudo guardar.');
            }

            const checklistServidor = parseChecklist(JSON.stringify(data.checklist || {}));
            container.setAttribute('data-checklist', JSON.stringify(checklistServidor));
            aplicarChecksEnVista(container, checklistServidor);
            if (data.proceso) {
                aplicarProcesoEnFila(container, data.proceso, true);
            }
            return true;
        } catch (error) {
            alert(error.message || 'Error al guardar checklist.');
            return false;
        } finally {
            container.classList.remove('is-saving');
        }
    }

    function normalizarProcesoEscaleraJs(proceso) {
        const valor = String(proceso || 'Ganar');
        return etapas.includes(valor) ? valor : 'Ganar';
    }

    function aplicarProcesoEnFila(container, proceso, abrirSiCambio) {
        const procesoNorm = normalizarProcesoEscaleraJs(proceso);
        const procesoAnterior = normalizarProcesoEscaleraJs(container.getAttribute('data-proceso') || 'Ganar');
        container.setAttribute('data-proceso', procesoNorm);

        const fila = container.closest('tr');
        if (fila) {
            fila.setAttribute('data-etapa', procesoNorm.toLowerCase());
        }

        container.querySelectorAll('.js-inline-step-btn').forEach(function(btn) {
            btn.classList.toggle('is-current', btn.getAttribute('data-etapa') === procesoNorm);
        });

        if (procesoAnterior !== procesoNorm) {
            const cuentaAnterior = document.querySelector('.escalera-ind[data-etapa="' + procesoAnterior.toLowerCase() + '"] .escalera-ind-count');
            const cuentaNueva = document.querySelector('.escalera-ind[data-etapa="' + procesoNorm.toLowerCase() + '"] .escalera-ind-count');
            if (cuentaAnterior) {
                cuentaAnterior.textContent = String(Math.max(0, (parseInt(cuentaAnterior.textContent, 10) || 0) - 1));
            }
            if (cuentaNueva) {
                cuentaNueva.textContent = String((parseInt(cuentaNueva.textContent, 10) || 0) + 1);
            }

            const filtroActivo = document.querySelector('.escalera-ind.is-active');
            const etapaFiltro = filtroActivo ? String(filtroActivo.getAttribute('data-etapa') || '') : '';
            if (fila && etapaFiltro && etapaFiltro !== procesoNorm.toLowerCase()) {
                fila.hidden = true;
            }

            if (abrirSiCambio && typeof container._mostrarPanelEscalera === 'function') {
                container._mostrarPanelEscalera(procesoNorm);
            }
        }
    }

    tarjetasEscalera.forEach(function(container) {
        const checklist = parseChecklist(container.getAttribute('data-checklist') || '');
        const puedeEditar = container.getAttribute('data-puede-editar') === '1';
        if (container.getAttribute('data-asignado') === '1') {
            checklist.Ganar[1] = true;
        } else {
            checklist.Ganar[1] = false;
        }
        if (container.getAttribute('data-celula') === '1') {
            checklist.Ganar[4] = true;
            checklist.Enviar[2] = true;
        } else {
            checklist.Ganar[4] = false;
        }
        container.setAttribute('data-checklist', JSON.stringify(checklist));

        aplicarChecksEnVista(container, checklist);
        aplicarBloqueoNoDisponible(container, checklist, puedeEditar);

        let etapaAbierta = null;
        function mostrarPanel(etapaObjetivo) {
            container.querySelectorAll('.js-inline-stage-panel').forEach(function(panel) {
                if (etapaObjetivo && panel.getAttribute('data-etapa') === etapaObjetivo) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            });

            container.querySelectorAll('.js-inline-step-btn').forEach(function(btn) {
                btn.classList.toggle('active', !!etapaObjetivo && btn.getAttribute('data-etapa') === etapaObjetivo);
            });

            etapaAbierta = etapaObjetivo || null;
        }

        container._mostrarPanelEscalera = mostrarPanel;
        aplicarProcesoEnFila(container, container.getAttribute('data-proceso') || 'Ganar', false);
        mostrarPanel(null);

        container.querySelectorAll('.js-inline-step-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const etapa = btn.getAttribute('data-etapa') || 'Ganar';
                if (etapaAbierta === etapa) {
                    mostrarPanel(null);
                    return;
                }
                mostrarPanel(etapa);
            });
        });

        container.querySelectorAll('.js-inline-escalera-check').forEach(function(check) {
            check.addEventListener('change', async function() {
                const etapa = String(check.getAttribute('data-etapa') || 'Ganar');
                const indice = parseInt(check.getAttribute('data-indice') || '-1', 10);
                if (!etapas.includes(etapa) || indice < 0) {
                    return;
                }

                const nuevoValor = !!check.checked;
                const guardado = await guardarChecklistInline(container, etapa, indice, nuevoValor);
                if (!guardado) {
                    check.checked = !nuevoValor;
                    return;
                }

                const checklistActualizado = parseChecklist(container.getAttribute('data-checklist') || '');
                aplicarBloqueoNoDisponible(container, checklistActualizado, puedeEditar);
            });
        });
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
