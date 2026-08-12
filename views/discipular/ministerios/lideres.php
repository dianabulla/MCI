<?php include VIEWS . '/layout/header.php'; ?>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/discipular-equipo.css?v=20260811a">

<?php
$idMinisterioFiltro = (int)($id_ministerio_filtro ?? 0);
$nombreMinisterioFiltro = trim((string)($nombre_ministerio_filtro ?? ''));
$hayFiltroMinisterio = $idMinisterioFiltro > 0;
$esCoberturaPastoralGlobal = !$hayFiltroMinisterio;
$esVistaPropiaLider12 = !empty($es_vista_propia_lider_12);
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
    $valor = strtolower(trim((string)$texto));
    return strtr($valor, [
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
    ]);
};
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
    $esMujer = strpos($genero, 'mujer') !== false || strpos($genero, 'femen') !== false;
    return $esMujer ? 'mujeres' : 'hombres';
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
$tabActivo = $tabSolicitado !== '' ? $tabSolicitado : ($hayFiltroMinisterio ? 'lideres_144' : 'equipo_principal');
if (!in_array($tabActivo, ['equipo_principal', 'lideres_144', 'lideres_celula', 'discipulos'], true)) {
    $tabActivo = $hayFiltroMinisterio ? 'lideres_144' : 'equipo_principal';
}
if ($hayFiltroMinisterio && $tabActivo === 'equipo_principal') {
    $tabActivo = 'lideres_144';
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
} else {
    $generoSesion = strtolower(trim((string)($_SESSION['discipular_equipo_genero'] ?? 'todos')));
    $filtroGeneroGet = in_array($generoSesion, ['todos', 'hombres', 'mujeres'], true)
        ? $generoSesion
        : 'todos';
}

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

$filaCoincideBusqueda = static function (array $row) use ($buscarGet, $soloDigitosBuscar, $textoBusquedaFila): bool {
    if ($buscarGet === '') {
        return true;
    }

    $texto = $textoBusquedaFila($row);
    $digitos = preg_replace(
        '/\D+/',
        '',
        (string)($row['numero_documento'] ?? '') . (string)($row['telefono'] ?? '')
    );
    if ($soloDigitosBuscar !== '' && $digitos !== '' && strpos($digitos, $soloDigitosBuscar) !== false) {
        return true;
    }

    $tokens = preg_split('/\s+/', $buscarGet, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach ($tokens as $token) {
        if (strpos($texto, $token) === false) {
            return false;
        }
    }

    return $tokens !== [];
};

$esLider144EnFilaLiderazgo = static function (array $liderRow): bool {
    if ((int)($liderRow['es_lider_144'] ?? 0) === 1 || (int)($liderRow['Es_Lider_144'] ?? 0) === 1) {
        return true;
    }
    $rol = strtolower(trim((string)($liderRow['Nombre_Rol'] ?? '')));
    return strpos($rol, '144') !== false;
};

$mapearLiderazgoAFilaTabla = static function (array $liderRow) use ($normalizarGenero, $esLider144EnFilaLiderazgo): array {
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
    ];
};

$filaCumpleFiltrosGeneroBusqueda = static function (array $row) use ($filtroGeneroGet, $filaCoincideBusqueda, $tabActivo, $coberturaPrincipalActual, $hayBusquedaActiva): bool {
    $generoRow = strtolower(trim((string)($row['genero'] ?? 'hombres')));
    $okGenero = $filtroGeneroGet === 'todos' || $generoRow === $filtroGeneroGet;
    $okBuscar = $filaCoincideBusqueda($row);

    $okCobertura = true;
    if (!$hayBusquedaActiva && $tabActivo === 'equipo_principal' && $coberturaPrincipalActual !== '') {
        $okCobertura = (string)($row['id_lider_actual'] ?? '0') === $coberturaPrincipalActual;
    }

    return $okGenero && $okBuscar && $okCobertura;
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
$rowsTablaFiltradas = array_values(array_filter($rowsTabla, static function($row) use ($tabActivo, $filtroGeneroGet, $filaCoincideBusqueda, $coberturaPrincipalActual, $idsLideresPrincipalesCupo, $hayFiltroMinisterio, $idsLideres12CupoMinisterio, $vistaPastoralRedSoloLectura, $hayBusquedaActiva) {
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

    $generoRow = strtolower(trim((string)($row['genero'] ?? 'hombres')));
    $okGenero = $filtroGeneroGet === 'todos' || $generoRow === $filtroGeneroGet;
    $okBuscar = $filaCoincideBusqueda($row);

    $okCobertura = true;
    if (!$hayBusquedaActiva && $tabActivo === 'equipo_principal' && $coberturaPrincipalActual !== '') {
        $okCobertura = (string)($row['id_lider_actual'] ?? '0') === $coberturaPrincipalActual;
    }

    return $okTab && $okGenero && $okBuscar && $okCobertura;
}));
}

$totalSinAsignacionRedBusqueda = 0;
foreach ($rowsTablaFiltradas as $filaAsig) {
    if (!empty($filaAsig['sin_asignacion_red'])) {
        $totalSinAsignacionRedBusqueda++;
    }
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
$colspanTabla = $tabMuestraColumnaCupo ? 7 : 8;
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
$totalKpiIzquierdo = $totalPersonasMinisterio;
$kpiIzquierdoHombres = $totalPersonasMinisterioHombres;
$kpiIzquierdoMujeres = $totalPersonasMinisterioMujeres;

$buildEquipoUrl = static function(array $override = []) use ($idMinisterioFiltro, $tabActivo, $filtroGeneroGet, $buscarGet, $coberturaPrincipalActual) {
    $params = [
        'url' => 'discipular/ministerios/equipo-principal',
        'tab' => $tabActivo,
        'genero' => $filtroGeneroGet,
    ];

    if ($idMinisterioFiltro > 0) {
        $params['id_ministerio'] = $idMinisterioFiltro;
    }

    if ($buscarGet !== '') {
        $params['buscar'] = $buscarGet;
    }

    if ($coberturaPrincipalActual !== '') {
        $params['cobertura_principal'] = $coberturaPrincipalActual;
    }

    foreach ($override as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
            continue;
        }
        $params[$k] = $v;
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

$vistaAvanzadaActiva = isset($_GET['vista_avanzada']) && (string)$_GET['vista_avanzada'] === '1';
$vistaJerarquiaLimpia = !$hayBusquedaActiva && !$vistaAvanzadaActiva;
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
    'id_ministerio' => $idMinisterioFiltro > 0 ? $idMinisterioFiltro : null,
    'tab' => $tabActivo !== '' ? $tabActivo : null,
    'genero' => $filtroGeneroGet !== 'todos' ? $filtroGeneroGet : null,
    'buscar' => $buscarGet !== '' ? $buscarGet : null,
    'cobertura_principal' => $coberturaPrincipalActual !== '' ? $coberturaPrincipalActual : null,
    'genero_red' => $generoRedActual !== '' ? $generoRedActual : null,
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
    <header class="jer-topbar">
        <div>
            <h2 class="jer-topbar-title">Discipular</h2>
            <p class="jer-topbar-sub">Elige una red y navega por niveles con clic.</p>
        </div>
        <div class="jer-topbar-actions">
            <?php if ($puedeConfigurarLideresPrincipales): ?>
            <button type="button" id="btnEditarLiderazgo" class="btn btn-sm btn-secondary" title="<?= htmlspecialchars($tituloBotonEditarLiderazgo) ?>">
                <i class="bi bi-gear"></i> Configurar
            </button>
            <?php endif; ?>
            <?php if (!$esVistaPropiaLider12): ?>
            <select id="ministerioSelect" class="form-control form-control-sm jer-ministerio-select" aria-label="Ministerio">
                <option value="0" <?= !$hayFiltroMinisterio ? 'selected' : '' ?>><?= htmlspecialchars($textoOpcionGeneral) ?></option>
                <?php foreach ($ministeriosNavegacion as $ministerioNav): ?>
                    <?php
                    $idNav = (int)($ministerioNav['Id_Ministerio'] ?? 0);
                    $nombreNav = trim((string)($ministerioNav['Nombre_Ministerio'] ?? 'Ministerio'));
                    if ($idNav <= 0) {
                        continue;
                    }
                    ?>
                    <option value="<?= $idNav ?>" <?= $idNav === $idMinisterioFiltro ? 'selected' : '' ?>><?= htmlspecialchars($nombreNav) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($urlVistaAvanzada) ?>" class="btn btn-sm btn-outline-secondary">Vista avanzada</a>
        </div>
    </header>
    <?php endif; ?>

    <?php if (!$vistaJerarquiaLimpia): ?>
    <section class="equipo-hero card">
        <div class="equipo-hero-grid">
            <div class="equipo-avatar" aria-hidden="true"></div>
            <div class="equipo-perfil">
                <?php if ($tarjetaEnfocadaDirectores): ?>
                <p class="equipo-nombre"><?= htmlspecialchars($tituloTarjetaDirectores) ?></p>
                <p class="equipo-subtitulo"><?= htmlspecialchars($subtituloTarjetaDirectores) ?></p>
                <?php if ($esVistaPropiaLider12): ?>
                <p class="equipo-rol-sesion">Tu rol: <strong>Líder de 12</strong></p>
                <?php endif; ?>
                <?php if ($tarjetaCoberturaPastoralGeneral): ?>
                <p class="equipo-meta-iglesia">Sede: <?= htmlspecialchars($sedePastor) ?> · Ministerios: <?= (int)$ministerioCantidad ?></p>
                <?php endif; ?>
                <div class="equipo-directores-grid">
                    <div class="equipo-director-bloque" id="lineaPastorPrincipal1">
                        <p class="equipo-director-etiqueta"><?= htmlspecialchars($labelLiderazgoPrincipal1) ?></p>
                        <p class="equipo-director-nombre"><?= htmlspecialchars($nombreLiderPrincipal1 !== '' ? $nombreLiderPrincipal1 : 'Sin definir') ?></p>
                        <?php if ($nombreLiderPrincipal1 !== ''): ?>
                        <p>Email: <?= htmlspecialchars($contactoLiderPrincipal1['email'] !== '' ? $contactoLiderPrincipal1['email'] : 'Sin registro') ?></p>
                        <p>Teléfono: <?= htmlspecialchars($contactoLiderPrincipal1['telefono'] !== '' ? $contactoLiderPrincipal1['telefono'] : 'Sin registro') ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="equipo-director-bloque" id="lineaPastorPrincipal2">
                        <p class="equipo-director-etiqueta"><?= htmlspecialchars($labelLiderazgoPrincipal2) ?></p>
                        <p class="equipo-director-nombre"><?= htmlspecialchars($nombreLiderPrincipal2 !== '' ? $nombreLiderPrincipal2 : 'Sin definir') ?></p>
                        <?php if ($nombreLiderPrincipal2 !== ''): ?>
                        <p>Email: <?= htmlspecialchars($contactoLiderPrincipal2['email'] !== '' ? $contactoLiderPrincipal2['email'] : 'Sin registro') ?></p>
                        <p>Teléfono: <?= htmlspecialchars($contactoLiderPrincipal2['telefono'] !== '' ? $contactoLiderPrincipal2['telefono'] : 'Sin registro') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <p class="equipo-nombre"><?= htmlspecialchars($nombrePastor) ?></p>
                <p>Email: <?= htmlspecialchars($emailPastor !== '' ? $emailPastor : 'Sin registro') ?></p>
                <p>Teléfono: <?= htmlspecialchars($telefonoPastor !== '' ? $telefonoPastor : 'Sin registro') ?></p>
                <p>Sede: <?= htmlspecialchars($sedePastor) ?></p>
                <p id="lineaPastorPrincipal1"><?= htmlspecialchars($labelLiderazgoPrincipal1) ?>: <?= htmlspecialchars($nombreLiderPrincipal1 !== '' ? $nombreLiderPrincipal1 : 'Sin definir') ?></p>
                <p id="lineaPastorPrincipal2"><?= htmlspecialchars($labelLiderazgoPrincipal2) ?>: <?= htmlspecialchars($nombreLiderPrincipal2 !== '' ? $nombreLiderPrincipal2 : 'Sin definir') ?></p>
                <?php endif; ?>
                <div class="equipo-perfil-actions">
                    <?php if ($puedeConfigurarLideresPrincipales): ?>
                    <button type="button" id="btnEditarLiderazgo" class="btn btn-liderazgo" title="<?= htmlspecialchars($tituloBotonEditarLiderazgo) ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </button>
                    <?php endif; ?>
                    <?php if (!$vistaPastoralRedSoloLectura || $tabActivo === 'equipo_principal'): ?>
                    <button type="button" id="btnAbrirAsignarLider" class="btn btn-primary btn-sm" <?= !$mostrarBotonesCupoPastoral ? 'disabled' : '' ?> title="<?= $mostrarBotonesCupoPastoral ? 'Abrir la ventana para asignar las 12 casillas bajo pastor/pastora principal' : htmlspecialchars($textoAvisoConfigurarLideres) ?>">
                        <?= htmlspecialchars($textoBotonGestionarCupos) ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="equipo-kpi equipo-kpi-main">
                <span class="equipo-kpi-label"><?= htmlspecialchars($etiquetaKpiIzquierdo) ?></span>
                <strong><?= $totalKpiIzquierdo ?></strong>
                <div class="equipo-kpi-mini-actions">
                    <a class="kpi-mini-btn" href="<?= htmlspecialchars($buildEquipoUrl(['genero' => 'hombres'])) ?>">Hombres <?= $kpiIzquierdoHombres ?></a>
                    <a class="kpi-mini-btn" href="<?= htmlspecialchars($buildEquipoUrl(['genero' => 'mujeres'])) ?>">Mujeres <?= $kpiIzquierdoMujeres ?></a>
                </div>
                <?php if ($esCoberturaPastoralGlobal && $totalEnRedPastoral > 0): ?>
                <p class="equipo-kpi-ayuda">
                    En red pastoral (pestañas): <strong><?= $totalEnRedPastoral ?></strong>
                    <?php if ($totalSinClasificarRed > 0): ?>
                        · Sin clasificar aquí: <?= $totalSinClasificarRed ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>
            <a class="equipo-kpi equipo-kpi-link" href="<?= htmlspecialchars($urlGestionarMinisterio) ?>" title="<?= htmlspecialchars($textoGestionarMinisterio) ?>">
                <span class="equipo-kpi-label"><?= htmlspecialchars($etiquetaKpiDerecho) ?></span>
                <strong><?= $valorKpiDerecho ?></strong>
                <small><?= htmlspecialchars($textoGestionarMinisterio) ?></small>
            </a>
        </div>
        <div class="equipo-ministerio-row">
            <label for="ministerioSelect"><?= htmlspecialchars($labelSelectorMinisterio) ?></label>
            <select id="ministerioSelect" class="form-control form-control-sm" <?= $esVistaPropiaLider12 ? 'disabled' : '' ?>>
                <?php if (!$esVistaPropiaLider12): ?>
                <option value="0" <?= !$hayFiltroMinisterio ? 'selected' : '' ?>><?= htmlspecialchars($textoOpcionGeneral) ?></option>
                <?php endif; ?>
                <?php foreach ($ministeriosNavegacion as $ministerioNav): ?>
                    <?php
                        $idNav = (int)($ministerioNav['Id_Ministerio'] ?? 0);
                        $nombreNav = trim((string)($ministerioNav['Nombre_Ministerio'] ?? 'Ministerio'));
                        if ($idNav <= 0) { continue; }
                    ?>
                    <option value="<?= $idNav ?>" <?= $idNav === $idMinisterioFiltro ? 'selected' : '' ?>>
                        <?= htmlspecialchars($nombreNav) ?>
                    </option>
                <?php endforeach; ?>
            </select>
                <?php if ($hayFiltroMinisterio && !$esVistaPropiaLider12 && ($idLiderPrincipal1 > 0 || $idLiderPrincipal2 > 0)): ?>
                <label for="coberturaPrincipalSelect">Equipo principal:</label>
                <select id="coberturaPrincipalSelect" class="form-control form-control-sm">
                    <option value="" <?= $coberturaPrincipalActual === '' ? 'selected' : '' ?>>Todos</option>
                    <?php if ($idLiderPrincipal1 > 0): ?>
                    <option value="<?= $idLiderPrincipal1 ?>" <?= $coberturaPrincipalActual === (string)$idLiderPrincipal1 ? 'selected' : '' ?>><?= htmlspecialchars($nombreLiderPrincipal1 !== '' ? $nombreLiderPrincipal1 : 'Líder principal hombre') ?></option>
                    <?php endif; ?>
                    <?php if ($idLiderPrincipal2 > 0): ?>
                    <option value="<?= $idLiderPrincipal2 ?>" <?= $coberturaPrincipalActual === (string)$idLiderPrincipal2 ? 'selected' : '' ?>><?= htmlspecialchars($nombreLiderPrincipal2 !== '' ? $nombreLiderPrincipal2 : 'Líder principal mujer') ?></option>
                    <?php endif; ?>
                </select>
                <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php include VIEWS . '/discipular/partials/jerarquia_red_navegacion.php'; ?>

    <div id="modalAsignarCupo" class="cupos-modal" aria-hidden="true">
        <div class="cupos-modal-backdrop" data-close-modal="1"></div>
        <section class="card cupos-card cupos-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalAsignarTitulo">
            <div class="cupos-head">
                <h4 id="modalAsignarTitulo"><?= $hayFiltroMinisterio ? 'Asignar o cambiar las 12 casillas del equipo principal' : 'Asignar o cambiar las 12 casillas (cobertura pastoral)' ?></h4>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span id="estadoCupoLider" class="kpi-mini">Selecciona un líder</span>
                    <button type="button" id="cerrarModalAsignarCupo" class="btn btn-sm btn-light" aria-label="Cerrar">✕</button>
                </div>
            </div>
            <form method="post" action="<?= PUBLIC_URL ?>?url=discipular/ministerios/asignar-cupo" class="cupos-form-ordenado" id="formAsignarCupo">
                <input type="hidden" name="id_lider" id="id_lider_asignar" value="">
                <input type="hidden" name="id_ministerio" id="id_ministerio_asignar" value="<?= $idMinisterioFiltro ?>">
                <input type="hidden" name="modo_cupo" id="modo_cupo_asignar" value="<?= $hayFiltroMinisterio ? 'lider_144' : 'pastoral' ?>">
                <input type="hidden" name="tab_retorno" id="tab_retorno_asignar" value="<?= htmlspecialchars($tabActivo) ?>">
                <input type="hidden" name="cobertura_principal_retorno" id="cobertura_principal_retorno_asignar" value="<?= htmlspecialchars($coberturaPrincipalActual) ?>">
                <input type="hidden" name="genero_red_retorno" id="genero_red_retorno_asignar" value="<?= htmlspecialchars($generoRedActual) ?>">
                <input type="hidden" name="id_persona_actual_slot" id="id_persona_actual_slot" value="">
                <input type="hidden" name="numero_cupo" id="numero_cupo_asignar" value="">

                <div class="cupos-header-info">
                    <div>
                        <label id="labelCoberturaCupo"><?= htmlspecialchars($labelCoberturaSeleccionada) ?></label>
                        <div id="liderSeleccionadoText" class="form-control form-control-sm cupo-resumen-box">Sin seleccionar</div>
                    </div>
                </div>

                <div class="cupos-wizard-hint cupos-wizard-hint--compact" id="cuposWizardHint">
                    <p><strong>Paso 1:</strong> elige una casilla libre (1–12). <strong>Paso 2:</strong> busca la persona. <strong>Paso 3:</strong> confirma. Solo cuentan las personas con casilla numerada en el equipo principal.</p>
                </div>

                <div class="cupos-list-wrap">
                    <div class="cupos-list-title">Vista de las 12 casillas del equipo directo</div>
                    <ul id="listaCuposEquipo" class="cupos-list" aria-label="Casillas del 1 al 12"></ul>
                    <p class="cupos-list-help">Cada líder tiene hasta <strong>12 casillas numeradas</strong>. Las personas con cobertura pero sin casilla no aparecen aquí hasta que les asignes cupo.</p>
                </div>

                <div class="cupos-asignar-section" style="display:none;">
                    <div style="border-top: 1px solid #d7e2f3; padding-top: 12px; margin-top: 12px;">
                        <div class="cupos-asignar-grid">
                            <div class="cupos-asignar-col">
                                <div id="cupoActualResumen" style="margin-bottom: 12px; padding: 8px; background: #f8fbff; border-left: 3px solid #4f66d4; border-radius: 4px;">
                                    <small style="color: #60708f;">Actual:</small>
                                    <strong id="cupoResumenTexto" style="color: #2d4e77; display: block;">-</strong>
                                </div>
                                <label for="buscarCupoUniversal" id="labelBuscarPersona">Buscar persona</label>
                                <input id="buscarCupoUniversal" type="text" class="form-control form-control-sm" placeholder="Nombre, cédula, teléfono o email…" style="margin-bottom: 8px;">
                                <small id="buscarCupoAyuda" class="cupos-buscar-ayuda">Personas de tu ministerio. Al asignar, el rol se actualiza solo (p. ej. discípulo → líder de 144 o de célula).</small>
                                <div id="resultadosBuscarPersona" class="resultados-persona-list" aria-live="polite"></div>
                            </div>
                            <div class="cupos-asignar-col">
                                <label id="labelPersonaNuevaPreview" style="font-weight:700; color:#2d4e77;">Persona por quien se cambiará</label>
                                <div id="personaNuevaPreview" class="cupo-persona-card is-empty" style="margin-top:8px;">
                                    <strong>Sin persona seleccionada</strong>
                                    <span>Elige la persona para esta casilla.</span>
                                </div>
                            </div>
                        </div>
                        <select id="id_persona_asignar" name="id_persona" class="form-control form-control-sm" required style="display:none;">
                            <option value="">Seleccionar persona...</option>
                        </select>
                    </div>
                </div>

                <div class="cupos-footer-row">
                    <small id="helpModoCupo" style="display:block; margin-top:4px; color:#60708f;">Selecciona una persona y pulsa Confirmar asignación.</small>
                    <div class="cupos-footer-actions">
                        <button type="button" id="btnLiberarCupo" class="btn btn-outline-danger btn-sm" style="display:none;">Liberar cupo</button>
                        <button type="submit" id="btnAsignarCupo" class="btn btn-primary btn-sm">Confirmar asignación</button>
                    </div>
                </div>
            </form>
            <form id="formLiberarCupo" method="post" action="<?= PUBLIC_URL ?>?url=discipular/ministerios/liberar-cupo" style="display:none;">
                <input type="hidden" name="id_lider" id="liberar_id_lider" value="">
                <input type="hidden" name="id_persona" id="liberar_id_persona" value="">
                <input type="hidden" name="id_ministerio" id="liberar_id_ministerio" value="<?= $idMinisterioFiltro ?>">
                <input type="hidden" name="numero_cupo" id="liberar_numero_cupo" value="">
                <input type="hidden" name="tab_retorno" value="<?= htmlspecialchars($tabActivo) ?>">
                <input type="hidden" name="cobertura_principal_retorno" value="<?= htmlspecialchars($coberturaPrincipalActual) ?>">
                <input type="hidden" name="modo_cupo" value="<?= $hayFiltroMinisterio ? 'lider_144' : 'pastoral' ?>">
            </form>
        </section>
    </div>

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

    <?php if (!$vistaJerarquiaLimpia): ?>
    <p class="jer-volver-limpia"><a href="<?= htmlspecialchars(PUBLIC_URL . '?' . http_build_query(array_filter(['url' => 'discipular/ministerios/equipo-principal', 'id_ministerio' => $idMinisterioFiltro > 0 ? $idMinisterioFiltro : null]))) ?>"><i class="bi bi-arrow-left"></i> Volver a vista por redes</a></p>
    <section class="equipo-tabs card">
        <p class="jer-tabs-hint">También puedes usar la vista detallada por pestañas:</p>
        <div class="equipo-tabs-row">
            <?php if ($mostrarTabEquipoPrincipal): ?>
            <a class="equipo-tab <?= $tabActivo === 'equipo_principal' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'equipo_principal'])) ?>" data-tab="equipo_principal">Equipo principal <span><?= $totalEquipoPrincipal ?></span></a>
            <?php endif; ?>
            <a class="equipo-tab <?= $tabActivo === 'lideres_144' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'lideres_144'])) ?>" data-tab="lideres_144">Líderes de 144 <span><?= $totalLideres144 ?></span></a>
            <a class="equipo-tab <?= $tabActivo === 'lideres_celula' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'lideres_celula'])) ?>" data-tab="lideres_celula">Líderes de célula <span><?= $totalLideresCelula ?></span></a>
            <a class="equipo-tab <?= $tabActivo === 'discipulos' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'discipulos'])) ?>" data-tab="discipulos">Discípulos <span><?= $totalDiscipulos ?></span></a>
        </div>
        <form id="formFiltrosEquipoDiscipular" class="equipo-filtros-row" method="get" action="<?= htmlspecialchars(rtrim(PUBLIC_URL, '/') . '/index.php', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="url" value="discipular/ministerios/equipo-principal">
            <?php if ($idMinisterioFiltro > 0): ?>
                <input type="hidden" name="id_ministerio" value="<?= $idMinisterioFiltro ?>">
            <?php endif; ?>
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActivo) ?>">

            <select id="filtroGenero" name="genero" class="form-control form-control-sm equipo-select">
                <option value="todos" <?= $filtroGeneroGet === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="hombres" <?= $filtroGeneroGet === 'hombres' ? 'selected' : '' ?>>Hombres</option>
                <option value="mujeres" <?= $filtroGeneroGet === 'mujeres' ? 'selected' : '' ?>>Mujeres</option>
            </select>
            <input id="busquedaUniversal" name="buscar" class="form-control form-control-sm" type="search" value="<?= htmlspecialchars((string)($_GET['buscar'] ?? '')) ?>" placeholder="Nombre, cédula, teléfono, email, rol, ministerio o líder…" autocomplete="off">
            <button type="submit" class="btn btn-sm btn-secondary">Buscar</button>
            <button
                type="button"
                class="btn btn-sm btn-outline-secondary btn-descargar-imagen-tabla discipular-btn-export-tabla"
                data-tabla-id="discipularEquipoTableWrap"
                data-export-title="<?= htmlspecialchars($tituloExportTablaDiscipular, ENT_QUOTES, 'UTF-8') ?>"
                data-filename="discipular-equipo"
                data-export-subtitle-from="formFiltrosEquipoDiscipular"
                data-label-default="Imagen"
                title="Descargar la tabla visible como PNG">
                <i class="bi bi-image" aria-hidden="true"></i> Imagen
            </button>
        </form>
    </section>

    <?php if ($hayBusquedaActiva && $totalSinAsignacionRedBusqueda > 0): ?>
    <div class="alert alert-warning" style="margin:0 0 12px; border-radius:10px;">
        <strong><?= (int)$totalSinAsignacionRedBusqueda ?></strong> persona(s) aparecen en la búsqueda pero
        <strong>no tienen líder ni ministerio</strong> (por eso antes no salían en Discipular).
        Ábrelas con <em>Editar</em> o <em>Perfil</em> y asígnales ministerio/líder.
        Si ya existían como antiguas, revisa que no haya un <strong>duplicado</strong> del Tour.
    </div>
    <?php endif; ?>

    <?php if ($vistaPastoralRedSoloLectura && in_array($tabActivo, ['lideres_144', 'lideres_celula', 'discipulos'], true)): ?>
    <p class="equipo-aviso-consulta" style="margin:0 0 12px; padding:10px 14px; background:#f0f6ff; border:1px solid #c5d9f5; border-radius:8px; color:#365581; font-size:14px;">
        Vista de consulta: personas ya asignadas en cada ministerio. Para asignar cupos de líderes de 144 o de célula, entra al ministerio correspondiente.
        En esta cobertura solo se gestionan los <strong>12 cupos bajo pastor/pastora principal</strong> (pestaña Equipo principal).
    </p>
    <?php endif; ?>

    <div class="discipular-tabla-export-meta">
        <small id="resumenFiltrado" class="discipular-resumen-filtrado">Mostrando 0</small>
        <?php if ($hayBusquedaActiva): ?>
        <small class="discipular-aviso-busqueda-global"> · Búsqueda en <strong>todas las pestañas</strong> (<?= count($rowsTablaFiltradas) ?> coincidencia<?= count($rowsTablaFiltradas) === 1 ? '' : 's' ?>)</small>
        <?php endif; ?>
    </div>

    <div id="discipularEquipoTableWrap" class="table-container">
        <table class="data-table ministerios-equipo-table">
            <thead>
                <tr>
                    <?php if ($tabMuestraColumnaCupo): ?>
                    <th title="Gestionar el cupo del líder">Cupo</th>
                    <?php else: ?>
                    <th>Rol</th>
                    <th>Ministerio</th>
                    <?php endif; ?>
                    <th>Identificación</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Telefono</th>
                    <th>Email</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Mostrar toda la red y filtrar por tabs en frontend
                $lideresEquipoPrincipal = array_filter($rowsTabla, function($row) use ($idsLideresPrincipalesCupo) {
                    $idLiderActual = (int)($row['id_lider_actual'] ?? 0);
                    return $idLiderActual > 0 && in_array($idLiderActual, $idsLideresPrincipalesCupo, true);
                });
                if ($usarFilasCuposFijas144 && !empty($filasCupos144Ministerio)):
                    foreach ($filasCupos144Ministerio as $filaCupo144):
                        $tipoFilaCupo = (string)($filaCupo144['tipo'] ?? 'libre');
                        $slotCupo144 = (int)($filaCupo144['slot_numero'] ?? 0);
                        $idLiderCupo144 = (int)($filaCupo144['id_lider'] ?? 0);
                        $nombreLiderCupo144 = (string)($filaCupo144['nombre_lider'] ?? ('Líder ' . $idLiderCupo144));
                        $generoCupo144 = (string)($filaCupo144['genero'] ?? 'hombres');
                        $jerarquiaLiderCupo144 = trim((string)($jerarquiaPorLiderId[$idLiderCupo144] ?? 'lider_12'));

                        if ($tipoFilaCupo === 'ocupado' && is_array($filaCupo144['row'] ?? null)):
                            $row = $filaCupo144['row'];
                            $nombre = trim((string)($row['nombre'] ?? ''));
                            $apellido = trim((string)($row['apellido'] ?? ''));
                            $documento = trim((string)($row['numero_documento'] ?? ''));
                            $telefono = trim((string)($row['telefono'] ?? ''));
                            $email = trim((string)($row['email'] ?? ''));
                            $idPersona = (int)($row['id'] ?? 0);
                            $nombreRolFila = trim((string)($row['nombre_rol'] ?? ''));
                            $nombreMinisterioCupo = trim((string)($row['nombre_ministerio'] ?? ''));
                            $nombreLiderCupo = trim((string)($row['nombre_lider_actual'] ?? $nombreLiderCupo144));
                            $cupoNumeroFila = $slotCupo144 > 0 ? $slotCupo144 : (int)($cupoNumeroPorPersona[$idPersona] ?? 0);
                            $textoBusqueda = strtolower(trim(
                                $nombre . ' ' . $apellido . ' ' . $documento . ' ' . $telefono . ' ' . $email . ' '
                                . $nombreMinisterioCupo . ' ' . $nombreRolFila . ' ' . $nombreLiderCupo
                            ));
                            $digitosBusqueda = preg_replace('/\D+/', '', $documento . $telefono);
                ?>
                        <tr
                            class="fila-cupo-ministerio-144"
                            data-cupo-fijo="1"
                            data-genero="<?= htmlspecialchars($generoCupo144) ?>"
                            data-equipo-principal="1"
                            data-lideres-144="1"
                            data-lideres-celula="0"
                            data-discipulos="0"
                            data-cupos-disponibles="12"
                            data-search="<?= htmlspecialchars($textoBusqueda) ?>"
                            data-search-digits="<?= htmlspecialchars($digitosBusqueda) ?>"
                        >
                            <td>
                                <div class="cupos-tabla-acciones">
                                <button
                                    type="button"
                                    class="btn btn-xs btn-primary js-asignar-desde-cupo"
                                    data-id-lider="<?= $idLiderCupo144 ?>"
                                    data-id-ministerio="<?= $idMinisterioFiltro ?>"
                                    data-nombre-lider="<?= htmlspecialchars($nombreLiderCupo144, ENT_QUOTES, 'UTF-8') ?>"
                                    data-jerarquia-lider="lider_12"
                                    data-modo-cupo="lider_144"
                                    data-slot-numero="<?= (int)$cupoNumeroFila ?>"
                                    data-id-persona-objetivo="<?= $idPersona ?>"
                                    data-nombre-rol="<?= htmlspecialchars($nombreRolFila) ?>"
                                    title="Gestionar cupo de líder de 144 (12 bajo líder de 12)"
                                >Cupo <?= (int)$cupoNumeroFila ?></button>
                                <?php if ($idPersona > 0): ?>
                                <button
                                    type="button"
                                    class="btn btn-xs btn-outline-danger js-liberar-cupo-directo"
                                    data-id-lider="<?= $idLiderCupo144 ?>"
                                    data-id-persona="<?= $idPersona ?>"
                                    data-id-ministerio="<?= $idMinisterioFiltro ?>"
                                    data-numero-cupo="<?= (int)$cupoNumeroFila ?>"
                                    title="Liberar cupo (quedará vacío)"
                                >Liberar</button>
                                <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($documento !== '' ? $documento : '-') ?></td>
                            <td>
                                <?= htmlspecialchars($nombre !== '' ? $nombre : '-') ?>
                                <?php if (!empty($row['sin_asignacion_red'])): ?>
                                <span class="badge badge-warning" style="margin-left:6px; font-size:10px; vertical-align:middle;" title="Sin líder ni ministerio: no estaba en la red pastoral">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($apellido !== '' ? $apellido : '-') ?></td>
                            <td><?= htmlspecialchars($telefono !== '' ? $telefono : '-') ?></td>
                            <td><?= htmlspecialchars($email !== '' ? $email : '-') ?></td>
                            <td style="padding:2px 0; min-width:120px;">
                                <div class="acciones-fila-compacta">
                                    <a class="btn btn-xs btn-outline-primary" href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $idPersona ?>">Perfil</a>
                                    <?php if ($puedeEditarPersonaDiscipular): ?>
                                    <a class="btn btn-xs btn-outline-secondary" href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $idPersona ?>&return_url=<?= urlencode($urlRetornoEquipo) ?>" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <?php if ($puedeEliminarPersonaDiscipular): ?>
                                    <a class="btn btn-xs btn-outline-danger" href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= $idPersona ?>&return_url=<?= urlencode($urlRetornoEquipo) ?>" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar esta persona? Esta acción no se puede deshacer.')"><i class="bi bi-trash"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                <?php
                        else:
                ?>
                        <tr
                            class="fila-cupo-ministerio-144"
                            data-cupo-fijo="1"
                            data-genero="<?= htmlspecialchars($generoCupo144) ?>"
                            data-equipo-principal="1"
                            data-lideres-144="1"
                            data-lideres-celula="0"
                            data-discipulos="0"
                            data-cupo-libre="1"
                            data-cupos-disponibles="12"
                            data-search=""
                            data-search-digits=""
                        >
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-xs btn-primary js-asignar-desde-cupo"
                                    data-id-lider="<?= $idLiderCupo144 ?>"
                                    data-id-ministerio="<?= $idMinisterioFiltro ?>"
                                    data-nombre-lider="<?= htmlspecialchars($nombreLiderCupo144, ENT_QUOTES, 'UTF-8') ?>"
                                    data-jerarquia-lider="<?= htmlspecialchars($jerarquiaLiderCupo144, ENT_QUOTES, 'UTF-8') ?>"
                                    data-modo-cupo="lider_144"
                                    data-slot-numero="<?= (int)$slotCupo144 ?>"
                                    title="Asignar líder de 144 en este cupo"
                                >Cupo <?= (int)$slotCupo144 ?> (Libre)</button>
                            </td>
                            <td>—</td>
                            <td>Cupo libre</td>
                            <td><?= htmlspecialchars($nombreLiderCupo144) ?></td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                        </tr>
                <?php
                        endif;
                    endforeach;
                ?>
                    <tr id="rowCuposEquipo">
                        <td colspan="<?= (int)$colspanTabla ?>" class="text-center cupos-libre-row">
                            <span class="cupos-libre-label">Recordatorio:</span>
                            Pulsa <strong>Cupo X (Libre)</strong> para buscar una persona; al guardar queda como líder de 144.
                        </td>
                    </tr>
                <?php elseif ($usarFilasCuposFijas144): ?>
                    <tr>
                        <td colspan="<?= (int)$colspanTabla ?>" class="text-center">
                            <?php if ($buscarGet !== ''): ?>
                                No hay líderes de 144 que coincidan con la búsqueda.
                            <?php else: ?>
                                Configura el <strong>líder de 12</strong> del ministerio (botón lápiz) para ver los 12 cupos.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php elseif ($mostrarCuerpoTabla):
                    $rowsTablaRender = $rowsTablaFiltradas;
                    if (!$hayBusquedaActiva && ($tabActivo === 'equipo_principal' || ($tabActivo === 'lideres_144' && $hayFiltroMinisterio && !$usarFilasCuposFijas144))) {
                        $idsLideresCupoTab = $tabActivo === 'equipo_principal' ? $idsLideresPrincipalesCupo : $idsLideres12CupoMinisterio;
                        $rowsTablaRender = array_values(array_filter($rowsTablaRender, static function($row) use ($cupoNumeroPorPersona, $idsLideresCupoTab, $tabActivo) {
                            $idPersonaTmp = (int)($row['id'] ?? 0);
                            $idLiderTmp = (int)($row['id_lider_actual'] ?? 0);
                            $cupoTmp = (int)($cupoNumeroPorPersona[$idPersonaTmp] ?? 0);
                            if ($tabActivo === 'lideres_144') {
                                return $idPersonaTmp > 0
                                    && !empty($row['es_lider_144'])
                                    && $cupoTmp >= 1
                                    && $cupoTmp <= 12
                                    && in_array($idLiderTmp, $idsLideresCupoTab, true);
                            }
                            return $idPersonaTmp > 0
                                && $cupoTmp >= 1
                                && $cupoTmp <= 12
                                && in_array($idLiderTmp, $idsLideresCupoTab, true);
                        }));
                        usort($rowsTablaRender, static function($a, $b) use ($cupoNumeroPorPersona) {
                            $liderA = (int)($a['id_lider_actual'] ?? 0);
                            $liderB = (int)($b['id_lider_actual'] ?? 0);
                            if ($liderA !== $liderB) {
                                return $liderA <=> $liderB;
                            }
                            $cupoA = (int)($cupoNumeroPorPersona[(int)($a['id'] ?? 0)] ?? 0);
                            $cupoB = (int)($cupoNumeroPorPersona[(int)($b['id'] ?? 0)] ?? 0);
                            if ($cupoA > 0 && $cupoB > 0 && $cupoA !== $cupoB) {
                                return $cupoA <=> $cupoB;
                            }
                            if ($cupoA > 0 && $cupoB <= 0) {
                                return -1;
                            }
                            if ($cupoA <= 0 && $cupoB > 0) {
                                return 1;
                            }
                            $na = strtolower(trim((string)($a['nombre'] ?? '') . ' ' . (string)($a['apellido'] ?? '')));
                            $nb = strtolower(trim((string)($b['nombre'] ?? '') . ' ' . (string)($b['apellido'] ?? '')));
                            return strcasecmp($na, $nb);
                        });
                    }
                    foreach ($rowsTablaRender as $row):
                        $nombre = trim((string)($row['nombre'] ?? ''));
                        $apellido = trim((string)($row['apellido'] ?? ''));
                        $documento = trim((string)($row['numero_documento'] ?? ''));
                        $telefono = trim((string)($row['telefono'] ?? ''));
                        $email = trim((string)($row['email'] ?? ''));
                        $idPersona = (int)($row['id'] ?? 0);
                        $idMinisterioFila = (int)($row['id_ministerio'] ?? 0);
                        $nombreMinisterioFila = trim((string)($row['nombre_ministerio'] ?? ''));
                        $nombreRolFila = trim((string)($row['nombre_rol'] ?? ''));
                        $idLiderActualFila = (int)($row['id_lider_actual'] ?? 0);
                        $nombreLiderActualFila = trim((string)($row['nombre_lider_actual'] ?? ''));
                        $cupoNumeroFila = (int)($cupoNumeroPorPersona[$idPersona] ?? 0);
                        $puedeGestionarPropioEquipo = !empty($row['es_equipo_principal']) || !empty($row['es_lider_144']) || !empty($row['es_lider_celula']);
                        if (!empty($row['es_equipo_principal'])) {
                            $modoCupoPropio = 'lider_12';
                        } elseif (!empty($row['es_lider_144'])) {
                            $modoCupoPropio = 'lider_144';
                        } else {
                            $modoCupoPropio = 'lider_celula';
                        }
                        $jerarquiaPropio = trim((string)($jerarquiaPorLiderId[$idPersona] ?? $modoCupoPropio));
                        $nombreCompletoFila = trim($nombre . ' ' . $apellido);
                        $liderObjetivoFila = $idLiderActualFila > 0 ? $idLiderActualFila : $liderGestionCuposId;
                        $nombreLiderObjetivoFila = $nombreLiderActualFila;
                        if ($nombreLiderObjetivoFila === '' && $liderObjetivoFila === $idLiderPrincipal1) {
                            $nombreLiderObjetivoFila = $nombreLiderPrincipal1;
                        }
                        if ($nombreLiderObjetivoFila === '' && $liderObjetivoFila === $idLiderPrincipal2) {
                            $nombreLiderObjetivoFila = $nombreLiderPrincipal2;
                        }
                        if ($nombreLiderObjetivoFila === '') {
                            $nombreLiderObjetivoFila = $liderGestionCuposNombre !== '' ? $liderGestionCuposNombre : $nombrePastor;
                        }
                        $generoRaw = strtolower(trim((string)($row['genero'] ?? $row['Genero'] ?? '')));
                        $genero = (strpos($generoRaw, 'mujer') !== false || strpos($generoRaw, 'femen') !== false) ? 'mujeres' : 'hombres';
                        $cuposDisponibles = (int)($row['cupos_disponibles'] ?? -1);
                        $equipoDirecto = (int)($row['equipo_directo'] ?? 0);
                        $soloDigitos = static function($valor) {
                            return preg_replace('/\D+/', '', (string)$valor);
                        };
                        $textoBusqueda = strtolower(trim(
                            $nombre . ' ' . $apellido . ' ' . $documento . ' ' . $telefono . ' ' . $email . ' '
                            . $nombreMinisterioFila . ' ' . $nombreRolFila . ' ' . $nombreLiderActualFila
                        ));
                        $digitosBusqueda = $soloDigitos($documento) . $soloDigitos($telefono);
                ?>
                        <?php
                            $esFilaEquipoPrincipal = !$hayFiltroMinisterio && in_array($idLiderActualFila, $idsLideresPrincipalesCupo, true);
                            $esFila144BajoLider12 = !empty($row['es_lider_144'])
                                && $hayFiltroMinisterio
                                && in_array($idLiderActualFila, $idsLideres12CupoMinisterio, true);
                            $esGestionCupoPrincipal = $tabActivo === 'equipo_principal' && $esFilaEquipoPrincipal;
                            $esGestionCupo144Ministerio = $tabActivo === 'lideres_144' && $esFila144BajoLider12 && $hayFiltroMinisterio;
                            // Líderes de célula: sin cupos numerados; se vinculan por Id_Lider en el perfil.
                            $esGestionEquipoCelula = false;
                            $esDiscipuloFilaTab = !empty($row['es_discipulo']);
                            if ($vistaPastoralRedSoloLectura) {
                                $esDiscipuloFilaTab = $esDiscipuloFilaTab && empty($row['es_lider_144']) && empty($row['es_lider_celula']);
                            }
                            $modoCupoPastoral = 'pastoral';
                            $modoCupoLider12 = 'lider_12';
                            $jerarquiaLiderObjetivo = trim((string)($jerarquiaPorLiderId[$liderObjetivoFila] ?? ($hayFiltroMinisterio ? 'lider_12' : 'pastor')));
                        ?>
                        <tr
                            data-genero="<?= htmlspecialchars($genero) ?>"
                            data-equipo-principal="<?= ($esFilaEquipoPrincipal || $esFila144BajoLider12) ? '1' : '0' ?>"
                            data-lideres-144="<?= !empty($row['es_lider_144']) ? '1' : '0' ?>"
                            data-lideres-celula="<?= !empty($row['es_lider_celula']) ? '1' : '0' ?>"
                            data-discipulos="<?= $esDiscipuloFilaTab ? '1' : '0' ?>"
                            data-cupos-disponibles="<?= $cuposDisponibles ?>"
                            data-search="<?= htmlspecialchars($textoBusqueda) ?>"
                            data-search-digits="<?= htmlspecialchars($digitosBusqueda) ?>"
                        >
                            <?php if ($tabMuestraColumnaCupo): ?>
                            <td>
                                <?php if ($esGestionCupoPrincipal): ?>
                                    <div class="cupos-tabla-acciones">
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-primary js-asignar-desde-cupo"
                                        data-id-lider="<?= $liderObjetivoFila ?>"
                                        data-id-ministerio="<?= $idMinisterioFiltro > 0 ? $idMinisterioFiltro : $idMinisterioFila ?>"
                                        data-nombre-lider="<?= htmlspecialchars($nombreLiderObjetivoFila !== '' ? $nombreLiderObjetivoFila : ('Líder ' . $liderObjetivoFila)) ?>"
                                        data-jerarquia-lider="<?= htmlspecialchars($jerarquiaLiderObjetivo, ENT_QUOTES, 'UTF-8') ?>"
                                        data-modo-cupo="<?= htmlspecialchars($modoCupoPastoral, ENT_QUOTES, 'UTF-8') ?>"
                                        data-slot-numero="<?= $cupoNumeroFila > 0 ? (int)$cupoNumeroFila : '' ?>"
                                        data-id-persona-objetivo="<?= $idPersona ?>"
                                        data-nombre-ministerio="<?= htmlspecialchars($nombreMinisterioFila) ?>"
                                        data-nombre-rol="<?= htmlspecialchars($nombreRolFila) ?>"
                                        title="Gestionar cupo (líderes de 12 bajo cobertura pastoral)"
                                    >
                                        <?= $cupoNumeroFila > 0 ? ('Cupo ' . (int)$cupoNumeroFila) : 'Gestionar cupo' ?>
                                    </button>
                                    <?php if ($idPersona > 0 && $cupoNumeroFila > 0): ?>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-outline-danger js-liberar-cupo-directo"
                                        data-id-lider="<?= $liderObjetivoFila ?>"
                                        data-id-persona="<?= $idPersona ?>"
                                        data-id-ministerio="<?= $idMinisterioFiltro > 0 ? $idMinisterioFiltro : $idMinisterioFila ?>"
                                        data-numero-cupo="<?= (int)$cupoNumeroFila ?>"
                                        title="Liberar cupo (quedará vacío)"
                                    >Liberar</button>
                                    <?php endif; ?>
                                    </div>
                                <?php elseif ($esGestionCupo144Ministerio): ?>
                                    <div class="cupos-tabla-acciones">
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-primary js-asignar-desde-cupo"
                                        data-id-lider="<?= $liderObjetivoFila ?>"
                                        data-id-ministerio="<?= $idMinisterioFiltro ?>"
                                        data-nombre-lider="<?= htmlspecialchars($nombreLiderObjetivoFila !== '' ? $nombreLiderObjetivoFila : ('Líder ' . $liderObjetivoFila)) ?>"
                                        data-jerarquia-lider="lider_12"
                                        data-modo-cupo="lider_144"
                                        data-slot-numero="<?= $cupoNumeroFila > 0 ? (int)$cupoNumeroFila : '' ?>"
                                        data-id-persona-objetivo="<?= $idPersona ?>"
                                        data-nombre-ministerio="<?= htmlspecialchars($nombreMinisterioFila) ?>"
                                        data-nombre-rol="<?= htmlspecialchars($nombreRolFila) ?>"
                                        title="Gestionar cupo de líder de 144 (12 bajo líder de 12)"
                                    >
                                        <?= $cupoNumeroFila > 0 ? ('Cupo ' . (int)$cupoNumeroFila) : 'Gestionar cupo' ?>
                                    </button>
                                    <?php if ($idPersona > 0 && $cupoNumeroFila > 0): ?>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-outline-danger js-liberar-cupo-directo"
                                        data-id-lider="<?= $liderObjetivoFila ?>"
                                        data-id-persona="<?= $idPersona ?>"
                                        data-id-ministerio="<?= $idMinisterioFiltro ?>"
                                        data-numero-cupo="<?= (int)$cupoNumeroFila ?>"
                                        title="Liberar cupo (quedará vacío)"
                                    >Liberar</button>
                                    <?php endif; ?>
                                    </div>
                                <?php elseif ($esGestionEquipoCelula): ?>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-primary js-asignar-desde-cupo"
                                        data-id-lider="<?= $idPersona ?>"
                                        data-id-ministerio="<?= $idMinisterioFiltro > 0 ? $idMinisterioFiltro : $idMinisterioFila ?>"
                                        data-nombre-lider="<?= htmlspecialchars($nombreCompletoFila !== '' ? $nombreCompletoFila : ('Persona ' . $idPersona)) ?>"
                                        data-jerarquia-lider="lider_celula"
                                        data-modo-cupo="lider_celula"
                                        data-nombre-ministerio="<?= htmlspecialchars($nombreMinisterioFila) ?>"
                                        data-nombre-rol="<?= htmlspecialchars($nombreRolFila) ?>"
                                        title="Gestionar equipo (sin límite de 12)"
                                    >Gestionar equipo</button>
                                <?php elseif ($puedeGestionarPropioEquipo && !$hayFiltroMinisterio && !$vistaPastoralRedSoloLectura): ?>
                                    <button
                                        type="button"
                                        class="btn btn-xs btn-primary js-asignar-desde-cupo"
                                        data-id-lider="<?= $idPersona ?>"
                                        data-id-ministerio="<?= $idMinisterioFiltro > 0 ? $idMinisterioFiltro : $idMinisterioFila ?>"
                                        data-nombre-lider="<?= htmlspecialchars($nombreCompletoFila !== '' ? $nombreCompletoFila : ('Persona ' . $idPersona)) ?>"
                                        data-jerarquia-lider="<?= htmlspecialchars($jerarquiaPropio, ENT_QUOTES, 'UTF-8') ?>"
                                        data-modo-cupo="<?= htmlspecialchars($modoCupoPropio, ENT_QUOTES, 'UTF-8') ?>"
                                        data-nombre-ministerio="<?= htmlspecialchars($nombreMinisterioFila) ?>"
                                        data-nombre-rol="<?= htmlspecialchars($nombreRolFila) ?>"
                                        title="Abrir las 12 casillas de su equipo directo"
                                    >
                                        <?= $cupoNumeroFila > 0 ? ('Cupo ' . (int)$cupoNumeroFila) : 'Gestionar cupo' ?>
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:12px;">Sin cupo</span>
                                <?php endif; ?>
                            </td>
                            <?php else: ?>
                            <td><?= htmlspecialchars($nombreRolFila !== '' ? $nombreRolFila : '-') ?></td>
                            <td><?= htmlspecialchars($nombreMinisterioFila !== '' ? $nombreMinisterioFila : '-') ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($documento !== '' ? $documento : '-') ?></td>
                            <td>
                                <?= htmlspecialchars($nombre !== '' ? $nombre : '-') ?>
                                <?php if (!empty($row['sin_asignacion_red'])): ?>
                                <span class="badge badge-warning" style="margin-left:6px; font-size:10px; vertical-align:middle;" title="Sin líder ni ministerio: no estaba en la red pastoral">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($apellido !== '' ? $apellido : '-') ?></td>
                            <td><?= htmlspecialchars($telefono !== '' ? $telefono : '-') ?></td>
                            <td><?= htmlspecialchars($email !== '' ? $email : '-') ?></td>
                            <td style="padding:2px 0; min-width:120px;">
                                <div class="acciones-fila-compacta">
                                    <a class="btn btn-xs btn-outline-primary" href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $idPersona ?>">Perfil</a>
                                    <?php if ($puedeEditarPersonaDiscipular): ?>
                                    <a class="btn btn-xs btn-outline-secondary" href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $idPersona ?>&return_url=<?= urlencode($urlRetornoEquipo) ?>" title="Editar" aria-label="Editar"><i class="bi bi-pencil"></i></a>
                                    <?php endif; ?>
                                    <?php if ($puedeEliminarPersonaDiscipular): ?>
                                    <a class="btn btn-xs btn-outline-danger" href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= $idPersona ?>&return_url=<?= urlencode($urlRetornoEquipo) ?>" title="Eliminar" aria-label="Eliminar" onclick="return confirm('¿Eliminar esta persona? Esta acción no se puede deshacer.')"><i class="bi bi-trash"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$usarFilasCuposFijas144 && $tabMuestraColumnaCupo): ?>
                    <?php foreach ($cuposLibresTabla as $cupoLibre): ?>
                        <?php
                            $idLiderLibre = (int)($cupoLibre['id_lider'] ?? 0);
                            $modoCupoLibre = ($tabActivo === 'lideres_144' && $hayFiltroMinisterio) ? 'lider_144' : ($hayFiltroMinisterio ? 'lider_12' : 'pastoral');
                            $jerarquiaLiderLibre = trim((string)($jerarquiaPorLiderId[$idLiderLibre] ?? ($hayFiltroMinisterio ? 'lider_12' : 'pastor')));
                            $etiquetaLiderLibre = (string)($cupoLibre['nombre_lider'] ?? 'Líder');
                        ?>
                        <tr
                            data-genero="<?= htmlspecialchars((string)($cupoLibre['genero'] ?? 'hombres')) ?>"
                            data-equipo-principal="1"
                            data-lideres-144="<?= ($tabActivo === 'lideres_144') ? '1' : '0' ?>"
                            data-lideres-celula="0"
                            data-discipulos="0"
                            data-cupo-libre="1"
                            data-cupos-disponibles="12"
                            data-search=""
                            data-search-digits=""
                        >
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-xs btn-primary js-asignar-desde-cupo"
                                    data-id-lider="<?= $idLiderLibre ?>"
                                    data-id-ministerio="<?= $idMinisterioFiltro ?>"
                                    data-nombre-lider="<?= htmlspecialchars($etiquetaLiderLibre, ENT_QUOTES, 'UTF-8') ?>"
                                    data-jerarquia-lider="<?= htmlspecialchars($jerarquiaLiderLibre, ENT_QUOTES, 'UTF-8') ?>"
                                    data-modo-cupo="<?= htmlspecialchars($modoCupoLibre, ENT_QUOTES, 'UTF-8') ?>"
                                    data-slot-numero="<?= (int)($cupoLibre['slot_numero'] ?? 0) ?>"
                                    title="Gestionar este cupo libre"
                                >Cupo <?= (int)($cupoLibre['slot_numero'] ?? 0) ?> (Libre)</button>
                            </td>
                            <td>—</td>
                            <td>Cupo libre</td>
                            <td><?= htmlspecialchars($etiquetaLiderLibre) ?></td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                        </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php
                    // Si hay cupos libres, mostrar fila especial con botón
                    $cuposOcupados = count($lideresEquipoPrincipal);
                    $cuposTotales = 12;
                    $cuposLibres = $cuposTotales - $cuposOcupados;
                    ?>
                    <?php if ($tabMuestraColumnaCupo): ?>
                    <tr id="rowCuposEquipo" style="<?= in_array($tabActivo, ['equipo_principal', 'lideres_144'], true) ? '' : 'display:none;' ?>">
                        <td colspan="<?= (int)$colspanTabla ?>" class="text-center cupos-libre-row">
                            <span class="cupos-libre-label">Recordatorio:</span>
                            <?php if ($hayFiltroMinisterio): ?>
                                En ministerio: <strong>12 cupos</strong> para líderes de 144 bajo el líder de 12; líderes de célula <strong>sin límite</strong>.
                            <?php elseif ($vistaPastoralRedSoloLectura): ?>
                                En cobertura pastoral: asigna los <strong>12 cupos bajo pastor/pastora principal</strong> desde esta pestaña o el botón superior.
                            <?php else: ?>
                                Cobertura pastoral: gestiona cupos y consulta toda la red desde las pestañas.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= (int)$colspanTabla ?>" class="text-center">
                            <?php if ($esVistaPropiaLider12 && $tabActivo === 'lideres_144' && empty($filasCupos144Ministerio) && empty($idsLideres12CupoMinisterio)): ?>
                                No se encontró tu ministerio asignado. Pide al administrador que vincule tu perfil a un ministerio.
                            <?php elseif ($hayFiltroMinisterio && empty($idsLideres12CupoMinisterio)): ?>
                                Configura el <strong>líder de 12</strong> del ministerio (botón lápiz) para ver los 12 cupos de líderes de 144.
                            <?php elseif (($hayFiltroMinisterio || $esVistaPropiaLider12) && $tabActivo === 'lideres_144'): ?>
                                Aún no hay líderes de 144 asignados. Pulsa <strong>Cupo</strong> en una fila libre para asignar.
                            <?php elseif ($hayBusquedaActiva): ?>
                                No hay coincidencias en la red con «<?= htmlspecialchars((string)($_GET['buscar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>». Prueba con cédula, teléfono o solo el apellido.
                            <?php elseif ($vistaPastoralRedSoloLectura && in_array($tabActivo, ['lideres_144', 'lideres_celula', 'discipulos'], true)): ?>
                                No hay personas en esta pestaña con los filtros actuales.
                            <?php elseif (!$hayFiltroMinisterio && $tabActivo === 'equipo_principal'): ?>
                                No hay líderes asignados en los cupos del pastor principal. Usa <strong>Gestionar 12 cupos del pastor</strong>.
                            <?php else: ?>
                                No hay registros para esta pestaña con los filtros actuales.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($tabMuestraColumnaCupo): ?>
                    <tr id="rowCuposEquipo" style="<?= in_array($tabActivo, ['equipo_principal', 'lideres_144'], true) ? '' : 'display:none;' ?>">
                        <td colspan="<?= (int)$colspanTabla ?>" class="text-center cupos-libre-row">
                            <span class="cupos-libre-label">Recordatorio:</span>
                            <?php if ($hayFiltroMinisterio): ?>
                                En ministerio: <strong>12 cupos</strong> por cada líder de 12 para asignar líderes de 144; células sin límite.
                            <?php elseif ($vistaPastoralRedSoloLectura): ?>
                                Solo en <strong>Equipo principal</strong> se asignan cupos bajo pastor/pastora principal.
                            <?php else: ?>
                                Cobertura pastoral: gestiona cupos desde la columna <strong>Cupo</strong>.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.equipo-shell {
    display: grid;
    gap: 14px;
}

.equipo-hero {
    padding: 14px;
    border-radius: 14px;
    border: 1px solid #d7e2f2;
    background: linear-gradient(180deg, #f8fbff 0%, #f2f6fd 100%);
}

.equipo-subtitulo {
    margin: 0 0 10px;
    font-size: 0.88rem;
    color: #5a6f8f;
}

.equipo-meta-iglesia {
    margin: 0 0 12px;
    font-size: 0.86rem;
    color: #4d5f7a;
}

.equipo-rol-sesion {
    margin: 0 0 12px;
    font-size: 0.9rem;
    color: #2d4e77;
}

.equipo-directores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 12px 18px;
    margin-top: 4px;
}

.equipo-director-bloque {
    padding: 10px 12px;
    border-radius: 10px;
    border: 1px solid #dce6f5;
    background: #fff;
}

.equipo-director-etiqueta {
    margin: 0 0 4px;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    color: #1d4f93;
}

.equipo-director-nombre {
    margin: 0 0 6px;
    font-size: 1rem;
    font-weight: 700;
    color: #1f365f;
}

.equipo-director-bloque p {
    margin: 0 0 3px;
    font-size: 0.86rem;
    color: #4d5f7a;
}

.equipo-hero-grid {
    display: grid;
    grid-template-columns: 110px 1fr minmax(180px, 220px) minmax(180px, 220px);
    gap: 12px;
    align-items: center;
}

.equipo-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: #84a7dc;
}

.equipo-perfil {
    border: 1px solid #d9e5f6;
    border-radius: 12px;
    padding: 10px 12px;
    background: #ffffff;
}

.equipo-nombre {
    margin: 0 0 6px;
    font-weight: 700;
    color: #2d3f5f;
    text-transform: uppercase;
    font-size: 12px;
}

.equipo-perfil p {
    margin: 3px 0;
    color: #4f6180;
    font-size: 12px;
}

.equipo-perfil-actions {
    margin-top: 8px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    align-items: center;
}

.equipo-guia-cupos {
    border: 1px solid #c8daf4;
    border-radius: 12px;
    padding: 12px 14px;
    background: linear-gradient(180deg, #f9fbff 0%, #f3f7fd 100%);
}

.equipo-guia-head {
    margin-bottom: 10px;
    color: #2d4e77;
    font-size: 13px;
}

.equipo-guia-pasos {
    margin: 0;
    padding-left: 0;
    list-style: none;
    display: grid;
    gap: 8px;
    color: #415570;
    font-size: 12px;
    line-height: 1.45;
}

.equipo-guia-pasos li {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}

.equipo-guia-num {
    flex: 0 0 22px;
    height: 22px;
    border-radius: 999px;
    background: #4f66d4;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.equipo-guia-aviso {
    margin: 10px 0 0;
    padding: 8px 10px;
    border-radius: 8px;
    background: #fff8ed;
    border: 1px solid #f0dcc2;
    color: #7a5a32;
    font-size: 12px;
}

.cupos-wizard-hint {
    margin: 4px 0 0;
    padding: 10px 12px;
    border-radius: 10px;
    background: #f4f7fc;
    border: 1px solid #dbe3f2;
}

.cupos-wizard-lead {
    margin: 0 0 8px;
    color: #2d4e77;
    font-size: 12px;
}

.cupos-wizard-steps {
    margin: 0;
    padding-left: 0;
    list-style: none;
    display: grid;
    gap: 6px;
    font-size: 12px;
    color: #4b5f7d;
    line-height: 1.4;
}

.cupos-wizard-steps li {
    display: flex;
    gap: 8px;
    align-items: flex-start;
}

.cupos-wizard-n {
    flex: 0 0 20px;
    height: 20px;
    border-radius: 6px;
    background: #e8eef9;
    color: #3d58a8;
    font-size: 11px;
    font-weight: 800;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.cupos-buscar-ayuda {
    display: block;
    margin: -4px 0 8px;
    color: #60708f;
    font-size: 11px;
    line-height: 1.35;
}

.cupos-list-item.is-selected {
    border-color: #4f66d4;
    box-shadow: 0 0 0 2px rgba(79, 102, 212, 0.15);
}

.cupos-list-help {
    margin: 8px 0 0;
    font-size: 11px;
    color: #60708f;
}

.btn-liderazgo {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    padding: 0;
    background: linear-gradient(135deg, #4f66d4 0%, #5a7ae0 100%);
    color: #fff;
    border: none;
    border-radius: 999px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(79, 102, 212, 0.3);
}

.btn-liderazgo:hover {
    background: linear-gradient(135deg, #5a7ae0 0%, #6585e8 100%);
    box-shadow: 0 6px 16px rgba(79, 102, 212, 0.4);
    transform: translateY(-2px);
}

.btn-liderazgo:active {
    transform: translateY(0);
    box-shadow: 0 2px 8px rgba(79, 102, 212, 0.3);
}


.cupo-slot-vacio {
    color: #8ca0be;
    font-weight: 700;
}

.equipo-kpi {
    border: 0;
    border-radius: 12px;
    background: #9caee3;
    padding: 12px;
    color: #fff;
    min-height: 82px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    text-decoration: none;
    text-align: left;
    cursor: pointer;
}

.equipo-kpi-main {
    background: #9daee6;
}

.equipo-kpi-mini-actions {
    margin-top: 6px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.kpi-mini-btn {
    display: inline-block;
    border: 1px solid rgba(255, 255, 255, 0.45);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.15);
    color: #fff;
    text-decoration: none;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    cursor: pointer;
}

.equipo-kpi-label {
    font-size: 12px;
    font-weight: 700;
}

.equipo-kpi strong {
    font-size: 30px;
    line-height: 1.1;
}

.equipo-kpi small {
    font-size: 11px;
    opacity: 0.95;
}

.equipo-kpi-ayuda {
    margin: 8px 0 0;
    font-size: 11px;
    line-height: 1.35;
    opacity: 0.92;
    max-width: 220px;
}

.equipo-kpi-link {
    background: #7b90d8;
}

.equipo-ministerio-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
}

.equipo-tabs {
    padding: 12px;
    border-radius: 12px;
    border: 1px solid #dbe3f2;
}

.cupos-card {
    border: 1px solid #dbe3f2;
    border-radius: 12px;
    padding: 12px;
}

.cupos-modal {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: none;
    box-sizing: border-box;
    padding: max(12px, env(safe-area-inset-top, 0px)) max(12px, env(safe-area-inset-right, 0px)) max(12px, env(safe-area-inset-bottom, 0px)) max(12px, env(safe-area-inset-left, 0px));
}

.cupos-modal.is-open {
    display: grid;
    grid-template: "stack" 1fr / 1fr;
    align-items: center;
    justify-items: center;
}

.cupos-modal-backdrop {
    grid-area: stack;
    align-self: stretch;
    justify-self: stretch;
    width: 100%;
    min-height: 100%;
    background: rgba(17, 31, 52, 0.5);
    cursor: pointer;
}

.cupos-modal-dialog {
    grid-area: stack;
    position: relative;
    z-index: 1;
    justify-self: center;
    align-self: center;
    width: min(1100px, calc(100vw - 24px));
    max-width: calc(100vw - 24px);
    max-height: min(92vh, calc(100dvh - 24px));
    margin: 0;
    overflow-x: auto;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    background: #fff;
    box-shadow: 0 18px 50px rgba(19, 42, 79, 0.22);
}

.cupos-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}

.cupos-head h4 {
    margin: 0;
    font-size: 14px;
    color: #2d4e77;
}

.cupos-form {
    display: grid;
    grid-template-columns: minmax(250px, 1.2fr) minmax(220px, 1fr) minmax(280px, 1.6fr) 190px;
    gap: 10px;
    align-items: end;
}

.cupos-form-ordenado {
    display: grid;
    gap: 14px;
}

.cupos-asignar-grid {
    display: grid;
    grid-template-columns: 1.25fr 1fr;
    gap: 12px;
    align-items: start;
}

.cupos-asignar-col {
    min-width: 0;
}

.cupos-toolbar-grid {
    display: grid;
    grid-template-columns: minmax(260px, 1fr) minmax(320px, 1.4fr);
    gap: 12px;
}

.cupo-resumen-box {
    display: flex;
    align-items: center;
    background: #f8fbff;
    min-height: 40px;
}

.cupos-slots-wrap {
    border: 1px solid #d9e4f6;
    border-radius: 12px;
    padding: 12px;
    background: #f9fbff;
}

.cupos-slots-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
}

.cupos-slots-head strong {
    display: block;
    color: #2d4e77;
    font-size: 14px;
}

.cupos-slots-head small {
    color: #60708f;
}

.cupos-list-wrap {
    margin: 12px 0;
}

.cupos-list-title {
    margin-bottom: 8px;
    color: #2d4e77;
}

.cupos-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.cupos-list-item {
    display: grid;
    grid-template-columns: 80px minmax(0, 1fr) minmax(220px, auto);
    gap: 12px;
    align-items: center;
    padding: 10px 12px;
    border: 1px solid #cfdbf2;
    border-radius: 8px;
    background: #fff;
    transition: border-color 0.18s ease, background 0.18s ease;
}

.cupos-list-item:hover {
    border-color: #85a4dd;
    background: #f8fbff;
}

.cupos-list-item.is-occupied {
    background: linear-gradient(90deg, #ffffff 0%, #f4f8ff 100%);
    border-color: #d7e2f3;
}

.cupos-item-numero {
    font-weight: 700;
    color: #2d4e77;
    font-size: 14px;
    min-width: 60px;
}

.cupos-item-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.cupos-item-status {
    font-weight: 600;
    color: #324b70;
    font-size: 13px;
}

.cupos-item-status.libre {
    color: #2f6f3f;
    font-size: 12px;
}

.cupos-item-meta {
    font-size: 11px;
    color: #627695;
}

.cupos-item-btn {
    padding: 6px 12px;
    white-space: nowrap;
    border-radius: 6px;
    border: 1px solid #d0d9e8;
    background: #f8fafd;
    color: #2d4e77;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.cupos-item-btn:hover {
    background: #e8f0ff;
    border-color: #85a4dd;
    color: #4f66d4;
}

.cupos-item-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    justify-content: flex-end;
}

.cupos-item-btn--liberar {
    border-color: #e8c4c4;
    background: #fff5f5;
    color: #b42318;
    font-weight: 700;
}

.cupos-item-btn--liberar:hover {
    background: #fee8e8;
    border-color: #e08a8a;
    color: #9b1c1c;
}

@media (max-width: 720px) {
    .cupos-list-item {
        grid-template-columns: 1fr;
        gap: 8px;
    }

    .cupos-item-actions {
        justify-content: flex-start;
    }
}

.cupos-tabla-acciones {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}

.cupos-header-info {
    margin-bottom: 12px;
}

.cupos-asignar-section {
    border-top: 1px solid #d7e2f3;
    padding-top: 12px;
    margin-top: 12px;
}

.cupo-persona-card {
    min-height: 116px;
    border: 1px solid #d7e2f3;
    border-radius: 12px;
    background: #fff;
    padding: 12px;
    display: grid;
    gap: 4px;
}

.cupo-persona-card strong {
    color: #2d4e77;
}

.cupo-persona-card span {
    color: #5d7395;
    font-size: 12px;
    line-height: 1.45;
}

.cupo-persona-card.is-empty {
    background: #f8fbff;
}

.cupos-footer-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 12px;
    position: sticky;
    bottom: 0;
    z-index: 5;
    margin-top: 12px;
    padding: 12px 0 4px;
    background: linear-gradient(180deg, rgba(255,255,255,0.75) 0%, #fff 35%);
    border-top: 1px solid #e2e8f0;
}

.cupos-footer-row small {
    max-width: 760px;
}

#btnAsignarCupo:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

#btnAsignarCupo:not(:disabled) {
    min-width: 180px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.28);
}

.cupos-form label {
    display: block;
    margin-bottom: 4px;
    font-size: 12px;
    color: #476388;
    font-weight: 700;
}

.equipo-tabs-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.equipo-tab {
    display: inline-block;
    border: 1px solid #c9d7ec;
    border-radius: 999px;
    padding: 7px 12px;
    background: #f5f8ff;
    color: #27496f;
    text-decoration: none;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
}

.equipo-tab span {
    margin-left: 6px;
    padding: 2px 7px;
    border-radius: 999px;
    background: #dfeafe;
    color: #1f4471;
}

.equipo-tab.is-active {
    background: #4f66d4;
    border-color: #4f66d4;
    color: #fff;
}

.equipo-tab.is-active span {
    background: rgba(255, 255, 255, 0.2);
    color: #fff;
}

.equipo-filtros-row {
    display: grid;
    grid-template-columns: minmax(140px, 200px) minmax(220px, 1fr) auto auto;
    gap: 10px;
    align-items: center;
}

.discipular-btn-export-tabla {
    white-space: nowrap;
}

.discipular-tabla-export-meta {
    display: flex;
    justify-content: flex-end;
    margin: 0 0 8px;
}

.discipular-resumen-filtrado {
    color: #64748b;
    font-size: 0.82rem;
}

.equipo-select {
    min-width: 180px;
}

.ministerios-equipo-table th,
.ministerios-equipo-table td {
    padding: 9px 10px;
    font-size: 12px;
    line-height: 1.3;
    vertical-align: middle;
}

.ministerios-equipo-table th:nth-child(6),
.ministerios-equipo-table td:nth-child(6) {
    min-width: 150px;
}

.ministerios-equipo-table th:nth-child(8),
.ministerios-equipo-table td:nth-child(8) {
    min-width: 126px;
    white-space: nowrap;
}

.ministerios-equipo-table td:nth-child(7) {
    word-break: break-word;
}

.acciones-fila {
    display: flex;
    gap: 6px;
    align-items: center;
    justify-content: flex-start;
    flex-wrap: nowrap;
}

.js-asignar-desde-cupo {
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.1;
    padding: 6px 10px;
    min-height: 28px;
    white-space: nowrap;
}

.kpi-mini {
    border-radius: 999px;
    background: #eef3ff;
    color: #2e4e76;
    padding: 4px 10px;
    font-size: 12px;
    font-weight: 700;
}

.equipo-compacta td, .equipo-compacta th {
    padding: 4px 6px !important;
    font-size: 13px;
    vertical-align: middle;
}

.acciones-fila-compacta {
    display: flex;
    gap: 4px;
    justify-content: center;
    align-items: center;
}

.btn-xs {
    padding: 2px 7px !important;
    font-size: 12px !important;
    border-radius: 6px !important;
    line-height: 1.2 !important;
}

.btn-xs.btn-outline-danger {
    color: #b42318;
    border-color: #f2c6c2;
    background: #fff5f5;
}

.btn-xs.btn-outline-danger:hover {
    color: #fff;
    background: #d92d20;
    border-color: #d92d20;
}

.cupos-libre-row {
    background: #f8fbff;
    font-weight: 600;
    font-size: 14px;
    padding: 8px 0 !important;
}

.cupos-libre-label {
    color: #365581;
    margin-right: 4px;
}

.cupos-footer-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 8px;
}

.cupos-libre-num {
    color: #2f6f3f;
    font-weight: bold;
    margin-right: 4px;
}

.resultados-persona-list {
    display: grid;
    gap: 6px;
    max-height: 220px;
    overflow: auto;
    margin-bottom: 8px;
    padding-right: 2px;
}

.persona-result-item {
    width: 100%;
    border: 1px solid #d7e2f3;
    border-radius: 8px;
    background: #fff;
    text-align: left;
    padding: 8px 10px;
    cursor: pointer;
}

.persona-result-item:hover {
    border-color: #9bb4e6;
    background: #f6f9ff;
}

.persona-result-item.is-active {
    border-color: #4f66d4;
    box-shadow: 0 0 0 2px rgba(79, 102, 212, 0.12);
}

.persona-result-nombre {
    display: block;
    font-weight: 700;
    color: #2d4e77;
}

.persona-result-meta {
    display: block;
    font-size: 11px;
    color: #60708f;
}

.persona-result-empty {
    border: 1px dashed #d7e2f3;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 12px;
    color: #60708f;
    background: #fbfcff;
}

@media (max-width: 800px) {
    .equipo-hero-grid {
        grid-template-columns: 1fr;
    }

    .equipo-filtros-row {
        grid-template-columns: 1fr;
    }

    .cupos-form {
        grid-template-columns: 1fr;
    }

    .cupos-asignar-grid {
        grid-template-columns: 1fr;
    }

    .cupos-toolbar-grid,
    .cupos-personas-grid {
        grid-template-columns: 1fr;
    }

    .cupos-footer-row {
        flex-direction: column;
        align-items: stretch;
    }

    .acciones-fila {
        flex-wrap: wrap;
    }
}
</style>

<script>
(function() {
    const filas = Array.from(document.querySelectorAll('.ministerios-equipo-table tbody tr[data-search]:not([data-cupo-fijo="1"])'));
    const filasCuposFijos = Array.from(document.querySelectorAll('.ministerios-equipo-table tbody tr[data-cupo-fijo="1"]'));
    const tabs = Array.from(document.querySelectorAll('.equipo-tab'));
    const filtroGenero = document.getElementById('filtroGenero');
    const buscador = document.getElementById('busquedaUniversal');
    const resumen = document.getElementById('resumenFiltrado');
    const ministerioSelect = document.getElementById('ministerioSelect');
    const coberturaPrincipalSelect = document.getElementById('coberturaPrincipalSelect');
    const kpiGenero = Array.from(document.querySelectorAll('.js-kpi-genero'));
    const lineaPastorPrincipal1 = document.getElementById('lineaPastorPrincipal1');
    const lineaPastorPrincipal2 = document.getElementById('lineaPastorPrincipal2');
    const liderAsignar = document.getElementById('id_lider_asignar');
    const idMinisterioAsignar = document.getElementById('id_ministerio_asignar');
    const liderSeleccionadoText = document.getElementById('liderSeleccionadoText');
    const labelCoberturaCupo = document.getElementById('labelCoberturaCupo');
    const helpModoCupo = document.getElementById('helpModoCupo');
    const modoCupoAsignar = document.getElementById('modo_cupo_asignar');
    const estadoCupoLider = document.getElementById('estadoCupoLider');
    const btnAsignarCupo = document.getElementById('btnAsignarCupo');
    const buscarCupoUniversal = document.getElementById('buscarCupoUniversal');
    const labelBuscarPersona = document.getElementById('labelBuscarPersona');
    const buscarCupoAyuda = document.getElementById('buscarCupoAyuda');
    const selectPersonaAsignar = document.getElementById('id_persona_asignar');
    const resultadosBuscarPersona = document.getElementById('resultadosBuscarPersona');
    const idPersonaActualSlot = document.getElementById('id_persona_actual_slot');
    const numeroCupoAsignar = document.getElementById('numero_cupo_asignar');
    const listaCuposEquipo = document.getElementById('listaCuposEquipo');
    const cuposListWrap = document.querySelector('.cupos-list-wrap');
    const personaNuevaPreview = document.getElementById('personaNuevaPreview');
    const labelPersonaNuevaPreview = document.getElementById('labelPersonaNuevaPreview');
    const cupoResumenTexto = document.getElementById('cupoResumenTexto');
    const botonesAsignar = Array.from(document.querySelectorAll('.js-asignar-desde-cupo'));
    const botonesCupoPastoral = Array.from(document.querySelectorAll('.js-gestionar-cupo-pastoral'));
    const modalAsignarCupo = document.getElementById('modalAsignarCupo');
    const cerrarModalAsignarCupo = document.getElementById('cerrarModalAsignarCupo');
    const btnEditarLiderazgo = document.getElementById('btnEditarLiderazgo');
    const modalEditarLiderazgo = document.getElementById('modalEditarLiderazgo');
    const btnCerrarModalEditarLiderazgo = document.getElementById('cerrarModalEditarLiderazgo');
    const btnAbrirAsignarLider = document.getElementById('btnAbrirAsignarLider');
    const rowCuposEquipo = document.getElementById('rowCuposEquipo');
    const idLiderGestionDefault = '<?= (int)$liderGestionCuposId ?>';
    const nombreLiderGestionDefault = '<?= htmlspecialchars($liderGestionCuposNombre !== '' ? $liderGestionCuposNombre : $nombrePastor, ENT_QUOTES, 'UTF-8') ?>';
    const esVistaMinisterio = <?= $hayFiltroMinisterio ? 'true' : 'false' ?>;
    const vistaPastoralRedSoloLectura = <?= $vistaPastoralRedSoloLectura ? 'true' : 'false' ?>;
    const usaEtiquetasPastorales = <?= $usarEtiquetasPastorales ? 'true' : 'false' ?>;
    const equipoDirectoPorLider = <?= $equipoDirectoPorLiderJson ?>;
    const urlPersonasAsignables = <?= json_encode($personasAsignablesUrl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let slotActualSeleccionado = null;
    let liderSinCuposDisponibles = false;
    let jerarquiaLiderActiva = '';
    let modoSlotUnico = false;
    let modoSlotUnicoForzado = false;
    const jerarquiaLiderGestionDefaultPhp = '<?= htmlspecialchars($jerarquiaLiderGestionDefault, ENT_QUOTES, 'UTF-8') ?>';

    function jerarquiaPermiteAsignacion(jerLider, jerPersona) {
        if (!jerPersona || jerPersona === 'administrativo') {
            return false;
        }
        if (jerPersona === 'pastor') {
            return false;
        }
        if (!jerLider || jerLider === 'miembro' || jerLider === 'administrativo') {
            return false;
        }
        if (jerLider === 'lider_celula') {
            return jerPersona === 'miembro';
        }
        if (jerLider === 'lider_144') {
            return jerPersona === 'lider_celula' || jerPersona === 'miembro';
        }
        if (jerLider === 'lider_12') {
            return jerPersona === 'lider_144' || jerPersona === 'lider_celula' || jerPersona === 'miembro';
        }
        if (jerLider === 'pastor') {
            return jerPersona === 'lider_12' || jerPersona === 'lider_144' || jerPersona === 'lider_celula' || jerPersona === 'miembro';
        }
        return false;
    }

    function textoAyudaSinCoincidenciasBusqueda() {
        return 'No hay resultados en tu ministerio con ese texto. Prueba nombre, cédula o teléfono.';
    }

    const idMinisterioFiltroPagina = '<?= (int)$idMinisterioFiltro ?>';

    function textoAyudaBusquedaCupoParaLider(idLider) {
        const id = parseInt(String(idLider || '0'), 10);
        if (id === idLiderPrincipal2Pagina) {
            return 'Personas mujeres activas. Al asignar en Red Mujeres, el rol se actualiza según la casilla (p. ej. discípula → líder de 12).';
        }
        if (id === idLiderPrincipal1Pagina) {
            return 'Personas hombres activas. Al asignar en Red Hombres, el rol se actualiza según la casilla (p. ej. discípulo → líder de 12).';
        }
        return 'Personas activas. Al asignar, el rol se actualiza según la casilla del líder seleccionado.';
    }

    function sincronizarAyudaBusquedaCupoParaLider(idLider) {
        if (!buscarCupoAyuda) {
            return;
        }
        buscarCupoAyuda.textContent = textoAyudaBusquedaCupoParaLider(idLider);
    }

    function sincronizarTarjetaPastores() {
        const genero = filtroGenero ? String(filtroGenero.value || 'todos') : 'todos';
        if (!lineaPastorPrincipal1 && !lineaPastorPrincipal2) {
            return;
        }

        if (genero === 'mujeres') {
            if (lineaPastorPrincipal1) { lineaPastorPrincipal1.style.display = 'none'; }
            if (lineaPastorPrincipal2) { lineaPastorPrincipal2.style.display = ''; }
            return;
        }

        if (genero === 'hombres') {
            if (lineaPastorPrincipal1) { lineaPastorPrincipal1.style.display = ''; }
            if (lineaPastorPrincipal2) { lineaPastorPrincipal2.style.display = 'none'; }
            return;
        }

        if (lineaPastorPrincipal1) { lineaPastorPrincipal1.style.display = ''; }
        if (lineaPastorPrincipal2) { lineaPastorPrincipal2.style.display = ''; }
    }

    function soloDigitos(valor) {
        return String(valor || '').replace(/\D+/g, '');
    }

    function escapeHtml(valor) {
        return String(valor || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function obtenerSlotsEquipo(idLider) {
        const key = String(idLider || '0');
        const equipo = Array.isArray(equipoDirectoPorLider[key]) ? equipoDirectoPorLider[key] : [];
        const slots = [];
        for (let i = 1; i <= 12; i++) {
            const raw = equipo[i - 1];
            const persona = raw && raw.id_persona ? raw : null;
            slots.push({
                slot_numero: i,
                persona: persona,
            });
        }
        return slots;
    }

    function construirCardPersona(persona, tituloVacio, descripcionVacia) {
        if (!persona || !persona.id_persona) {
            return '<strong>' + escapeHtml(tituloVacio) + '</strong><span>' + escapeHtml(descripcionVacia) + '</span>';
        }

        const meta = [];
        if (persona.documento) {
            meta.push('CC ' + persona.documento);
        }
        if (persona.telefono) {
            meta.push('TEL ' + persona.telefono);
        }
        if (persona.email) {
            meta.push(persona.email);
        }
        if (persona.nombre_rol) {
            meta.push(persona.nombre_rol);
        }

        return '<strong>' + escapeHtml(persona.nombre || ('Persona ' + persona.id_persona)) + '</strong>'
            + '<span>Casilla ' + escapeHtml(persona.slot_numero || '') + '</span>'
            + '<span>' + escapeHtml(meta.join(' | ') || 'Sin datos adicionales') + '</span>';
    }

    function actualizarTextoBotonAsignacion() {
        if (!btnAsignarCupo) {
            return;
        }

        const tieneLider = liderAsignar && String(liderAsignar.value || '').trim() !== '';
        const tieneSlot = !!(slotActualSeleccionado && slotActualSeleccionado.slot_numero);
        const idNuevaPersona = selectPersonaAsignar ? String(selectPersonaAsignar.value || '').trim() : '';

        if (!tieneLider || !tieneSlot || idNuevaPersona === '') {
            btnAsignarCupo.disabled = true;
            btnAsignarCupo.textContent = 'Elegir persona y casilla';
            return;
        }

        const slotOcupado = !!(slotActualSeleccionado && slotActualSeleccionado.persona && slotActualSeleccionado.persona.id_persona);

        // Si la casilla elegida está libre, siempre permitir asignar.
        // El flag liderSinCuposDisponibles cuenta personas con Id_Lider, y a veces
        // marca "lleno" aunque queden casillas 1–12 sin Numero_Cupo.
        if (!slotOcupado) {
            btnAsignarCupo.disabled = false;
            btnAsignarCupo.textContent = 'Asignar a la casilla ' + slotActualSeleccionado.slot_numero;
            return;
        }

        // Sustituir ocupante de una casilla siempre está permitido.
        btnAsignarCupo.disabled = false;
        btnAsignarCupo.textContent = 'Sustituir casilla ' + slotActualSeleccionado.slot_numero;
    }

    function actualizarPreviewPersonaNueva() {
        if (!personaNuevaPreview || !selectPersonaAsignar) {
            return;
        }

        const option = selectPersonaAsignar.options[selectPersonaAsignar.selectedIndex] || null;
        if (!option || !option.value) {
            personaNuevaPreview.classList.add('is-empty');
            personaNuevaPreview.innerHTML = '<strong>Sin reemplazo seleccionado</strong><span>Elige la persona nueva para esta casilla.</span>';
            actualizarTextoBotonAsignacion();
            return;
        }

        personaNuevaPreview.classList.remove('is-empty');
        const meta = [];
        if (option.dataset.documento) {
            meta.push('CC ' + option.dataset.documento);
        }
        if (option.dataset.telefono) {
            meta.push('TEL ' + option.dataset.telefono);
        }
        if (option.dataset.email) {
            meta.push(option.dataset.email);
        }
        if (option.dataset.nombreRol) {
            meta.push(option.dataset.nombreRol);
        }
        if (option.dataset.nombreLiderActual) {
            meta.push('Líder actual: ' + option.dataset.nombreLiderActual);
        }

        personaNuevaPreview.innerHTML = '<strong>' + escapeHtml(option.dataset.nombre || option.textContent || 'Persona seleccionada') + '</strong>'
            + '<span>' + escapeHtml(meta.join(' | ') || 'Sin datos adicionales') + '</span>';

        actualizarTextoBotonAsignacion();
    }

    function renderResultadosBusquedaPersonas() {
        if (!resultadosBuscarPersona || !selectPersonaAsignar) {
            return;
        }

        const opciones = Array.from(selectPersonaAsignar.options || []).filter(function(op, idx) {
            return idx > 0 && !op.hidden;
        });

        if (!opciones.length) {
            resultadosBuscarPersona.innerHTML = '<div class="persona-result-empty">' + escapeHtml(textoAyudaSinCoincidenciasBusqueda()) + '</div>';
            return;
        }

        resultadosBuscarPersona.innerHTML = opciones.slice(0, 60).map(function(op) {
            const id = String(op.value || '');
            const nombre = String(op.dataset.nombre || op.textContent || 'Persona');
            const meta = [
                op.dataset.documento ? ('CC ' + op.dataset.documento) : '',
                op.dataset.telefono ? ('TEL ' + op.dataset.telefono) : '',
                op.dataset.nombreRol || ''
            ].filter(Boolean).join(' | ');
            const activo = String(selectPersonaAsignar.value || '') === id ? ' is-active' : '';
            return '<button type="button" class="persona-result-item' + activo + '" data-id-persona="' + escapeHtml(id) + '">'
                + '<span class="persona-result-nombre">' + escapeHtml(nombre) + '</span>'
                + '<span class="persona-result-meta">' + escapeHtml(meta || 'Sin datos adicionales') + '</span>'
                + '</button>';
        }).join('');

        Array.from(resultadosBuscarPersona.querySelectorAll('.persona-result-item')).forEach(function(btn) {
            btn.addEventListener('click', function() {
                const idPersona = String(btn.dataset.idPersona || '').trim();
                if (idPersona === '') {
                    return;
                }
                selectPersonaAsignar.value = idPersona;
                actualizarPreviewPersonaNueva();
                renderResultadosBusquedaPersonas();
            });
        });
    }

    function seleccionarSlot(slotInfo) {
        slotActualSeleccionado = slotInfo || null;
        aplicarModoSlotSeleccionado(slotInfo || null);

        if (numeroCupoAsignar) {
            numeroCupoAsignar.value = slotInfo && slotInfo.slot_numero ? String(slotInfo.slot_numero) : '';
        }
        if (idPersonaActualSlot) {
            idPersonaActualSlot.value = slotInfo && slotInfo.persona && slotInfo.persona.id_persona ? String(slotInfo.persona.id_persona) : '';
        }

        const idLiderActual = liderAsignar ? String(liderAsignar.value || '').trim() : '';
        if (idLiderActual !== '') {
            renderSlotsEquipo(idLiderActual);
        }

        actualizarTextoBotonAsignacion();
    }

    function abrirGestionSlot(slotInfo, limpiarSeleccionPersona) {
        const seccionAsignar = document.querySelector('.cupos-asignar-section');
        if (!seccionAsignar) {
            return;
        }

        const persona = slotInfo && slotInfo.persona ? slotInfo.persona : null;
        const ocupado = !!(persona && persona.id_persona);
        const nombre = ocupado ? (persona.nombre || ('Persona ' + persona.id_persona)) : 'Libre';
        const statusTexto = slotInfo && slotInfo.slot_numero
            ? ('Casilla ' + slotInfo.slot_numero + ' (' + nombre + ')')
            : 'Selecciona una casilla';

        if (cupoResumenTexto) {
            cupoResumenTexto.textContent = statusTexto;
        }

        if (limpiarSeleccionPersona && selectPersonaAsignar) {
            selectPersonaAsignar.value = '';
        }
        if (limpiarSeleccionPersona && personaNuevaPreview) {
            personaNuevaPreview.classList.add('is-empty');
            personaNuevaPreview.innerHTML = '<strong>Sin persona seleccionada</strong><span>Elige la persona para esta casilla.</span>';
        }

        seccionAsignar.style.display = 'block';
        if (selectPersonaAsignar) {
            selectPersonaAsignar.focus();
        }
    }

    function aplicarModoSlotSeleccionado(slotInfo) {
        modoSlotUnico = modoSlotUnicoForzado && !!(slotInfo && slotInfo.slot_numero);
        if (cuposListWrap) {
            cuposListWrap.style.display = modoSlotUnico ? 'none' : '';
        }
        if (labelBuscarPersona) {
            labelBuscarPersona.textContent = modoSlotUnico
                ? ('Buscar persona para el cupo ' + String(slotInfo && slotInfo.slot_numero ? slotInfo.slot_numero : ''))
                : 'Buscar persona';
        }
        if (labelPersonaNuevaPreview) {
            const ocupado = !!(slotInfo && slotInfo.persona && slotInfo.persona.id_persona);
            labelPersonaNuevaPreview.textContent = ocupado
                ? 'Persona por quien se cambiará'
                : 'Persona que se asignará';
        }
        if (buscarCupoAyuda) {
            sincronizarAyudaBusquedaCupoParaLider(liderAsignar ? String(liderAsignar.value || '').trim() : '');
        }
    }

    function renderSlotsEquipo(idLider) {
        if (!listaCuposEquipo) {
            return;
        }

        const slots = obtenerSlotsEquipo(idLider);
        const selNum = slotActualSeleccionado && slotActualSeleccionado.slot_numero ? Number(slotActualSeleccionado.slot_numero) : 0;

        listaCuposEquipo.innerHTML = slots.map(function(slotInfo) {
            const persona = slotInfo.persona || null;
            const ocupado = !!(persona && persona.id_persona);
            const nombre = ocupado ? (persona.nombre || ('Persona ' + persona.id_persona)) : '';
            const meta = [];
            if (ocupado) {
                if (persona.documento) meta.push('CC ' + persona.documento);
                if (persona.telefono) meta.push('TEL ' + persona.telefono);
                if (persona.nombre_rol) meta.push(persona.nombre_rol);
            }

            const selClass = Number(slotInfo.slot_numero) === selNum ? ' is-selected' : '';
            const idPersonaSlot = ocupado ? String(persona.id_persona || '') : '';
            const btnLiberar = ocupado
                ? ('<button type="button" class="cupos-item-btn cupos-item-btn--liberar js-liberar-cupo-item"'
                    + ' data-slot-numero="' + slotInfo.slot_numero + '"'
                    + ' data-id-persona="' + idPersonaSlot + '"'
                    + ' title="Dejar la casilla vacía">Liberar cupo</button>')
                : '';

            return '<li class="cupos-list-item ' + (ocupado ? 'is-occupied' : '') + selClass + '" data-slot-numero="' + slotInfo.slot_numero + '">'
                + '<div class="cupos-item-numero">Casilla ' + slotInfo.slot_numero + '</div>'
                + '<div class="cupos-item-content">'
                + '<span class="cupos-item-status ' + (ocupado ? '' : 'libre') + '">' + (ocupado ? escapeHtml(nombre) : 'Libre') + '</span>'
                + (meta.length > 0 ? '<span class="cupos-item-meta">' + escapeHtml(meta.join(' | ')) + '</span>' : '')
                + '</div>'
                + '<div class="cupos-item-actions">'
                + '<button type="button" class="cupos-item-btn js-gestionar-cupo-item" data-slot-numero="' + slotInfo.slot_numero + '">Elegir casilla</button>'
                + btnLiberar
                + '</div>'
                + '</li>';
        }).join('');

        Array.from(listaCuposEquipo.querySelectorAll('.js-gestionar-cupo-item')).forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const numero = parseInt(String(btn.dataset.slotNumero || '0'), 10);
                const slotInfo = slots.find(function(item) {
                    return Number(item.slot_numero) === numero;
                }) || null;
                if (slotInfo) {
                    seleccionarSlot(slotInfo);
                    abrirGestionSlot(slotInfo, true);
                }
            });
        });

        Array.from(listaCuposEquipo.querySelectorAll('.js-liberar-cupo-item')).forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const idPersonaBtn = String(btn.dataset.idPersona || '').trim();
                const numeroCupo = parseInt(String(btn.dataset.slotNumero || '0'), 10);
                if (idPersonaBtn === '') {
                    return;
                }
                ejecutarLiberarCupo({
                    id_lider: idLider,
                    id_persona: idPersonaBtn,
                    id_ministerio: idMinisterioAsignar ? String(idMinisterioAsignar.value || '0') : '0',
                    numero_cupo: numeroCupo
                });
            });
        });
    }

    function prepararSlotsEquipo(idLider) {
        const slots = obtenerSlotsEquipo(idLider);
        renderSlotsEquipo(idLider);
        if (!slots.length) {
            seleccionarSlot(null);
            return;
        }

        const preferido = slots.find(function(slot) {
            return !slot.persona || !slot.persona.id_persona;
        }) || slots[0];

        seleccionarSlot(preferido);
    }

    let tabActual = '<?= htmlspecialchars($tabActivo, ENT_QUOTES, 'UTF-8') ?>';
    const STORAGE_GENERO_KEY = 'discipular_equipo_genero';
    const baseUrlEquipoDiscipular = '<?= htmlspecialchars(rtrim(PUBLIC_URL, '/') . '/index.php', ENT_QUOTES, 'UTF-8') ?>';
    const idLiderPrincipal1Pagina = <?= (int)$idLiderPrincipal1 ?>;
    const idLiderPrincipal2Pagina = <?= (int)$idLiderPrincipal2 ?>;
    const generoRedActualPagina = '<?= htmlspecialchars($generoRedActual, ENT_QUOTES, 'UTF-8') ?>';

    function generoFiltroActual() {
        return filtroGenero ? String(filtroGenero.value || 'todos') : 'todos';
    }

    function guardarGeneroFiltroLocal(genero) {
        try {
            sessionStorage.setItem(STORAGE_GENERO_KEY, genero);
        } catch (err) {
            // ignore
        }
    }

    function construirUrlEquipoDiscipular(override) {
        const opts = override || {};
        const params = new URLSearchParams();
        params.set('url', 'discipular/ministerios/equipo-principal');
        params.set('tab', opts.tab !== undefined ? String(opts.tab) : tabActual);
        const genero = opts.genero !== undefined ? String(opts.genero) : generoFiltroActual();
        params.set('genero', genero);
        guardarGeneroFiltroLocal(genero);

        if (idMinisterioFiltroPagina && idMinisterioFiltroPagina !== '0') {
            params.set('id_ministerio', idMinisterioFiltroPagina);
        }

        const cobertura = coberturaPrincipalSelect
            ? String(coberturaPrincipalSelect.value || '')
            : '<?= htmlspecialchars($coberturaPrincipalActual, ENT_QUOTES, 'UTF-8') ?>';
        if (cobertura !== '') {
            params.set('cobertura_principal', cobertura);
        }

        const buscar = opts.buscar !== undefined
            ? String(opts.buscar)
            : (buscador ? String(buscador.value || '').trim() : '');
        if (buscar !== '') {
            params.set('buscar', buscar);
        }

        return baseUrlEquipoDiscipular + '?' + params.toString();
    }

    function irADiscipularEquipo(override) {
        window.location.href = construirUrlEquipoDiscipular(override);
    }

    guardarGeneroFiltroLocal('<?= htmlspecialchars($filtroGeneroGet, ENT_QUOTES, 'UTF-8') ?>');

    function debeMostrarFilaCuposEquipo() {
        if (vistaPastoralRedSoloLectura) {
            return tabActual === 'equipo_principal';
        }
        return tabActual === 'equipo_principal' || tabActual === 'lideres_144';
    }

    function sincronizarUiVistaPastoral() {
        if (btnAbrirAsignarLider) {
            if (vistaPastoralRedSoloLectura) {
                btnAbrirAsignarLider.style.display = tabActual === 'equipo_principal' ? '' : 'none';
            } else {
                btnAbrirAsignarLider.style.display = '';
            }
        }
    }

    function coincideTab(fila, tab) {
        if (vistaPastoralRedSoloLectura && tab !== 'equipo_principal') {
            return true;
        }
        if (tab === 'equipo_principal') {
            return String(fila.dataset.equipoPrincipal || '0') === '1';
        }
        if (tab === 'lideres_144') {
            return String(fila.dataset.lideres144 || '0') === '1';
        }
        if (tab === 'lideres_celula') {
            return String(fila.dataset.lideresCelula || '0') === '1';
        }
        if (tab === 'discipulos') {
            return String(fila.dataset.discipulos || '0') === '1';
        }
        return true;
    }

    function textoCoincideBusqueda(textoFila, digitosFila, texto, textoDigitos) {
        if (texto === '') {
            return true;
        }
        if (textoDigitos !== '' && digitosFila.indexOf(textoDigitos) !== -1) {
            return true;
        }
        const tokens = texto.split(/\s+/).filter(Boolean);
        if (!tokens.length) {
            return true;
        }
        return tokens.every(function(token) {
            return textoFila.indexOf(token) !== -1;
        });
    }

    function aplicarFiltros() {
        const genero = filtroGenero ? String(filtroGenero.value || 'todos') : 'todos';
        const texto = buscador ? String((buscador.value || '').toLowerCase().trim()) : '';
        const textoDigitos = soloDigitos(texto);
        const busquedaGlobal = texto !== '';
        let visibles = 0;

        filasCuposFijos.forEach(function(filaCupo) {
            const generoFilaCupo = String(filaCupo.dataset.genero || 'hombres');
            const okGeneroCupo = genero === 'todos' || genero === generoFilaCupo;
            const okTabCupo = busquedaGlobal
                || tabActual === 'lideres_144'
                || (esVistaMinisterio && tabActual === 'equipo_principal');
            const esCupoLibreFijo = String(filaCupo.dataset.cupoLibre || '0') === '1';
            const textoFilaCupo = String(filaCupo.dataset.search || '');
            const digitosFilaCupo = String(filaCupo.dataset.searchDigits || '');
            const okTextoCupo = esCupoLibreFijo
                ? (texto === '')
                : textoCoincideBusqueda(textoFilaCupo, digitosFilaCupo, texto, textoDigitos);
            const mostrarCupo = okGeneroCupo && okTabCupo && okTextoCupo;
            filaCupo.style.display = mostrarCupo ? '' : 'none';
            if (mostrarCupo) {
                visibles++;
            }
        });

        if (!filas.length) {
            if (resumen) {
                resumen.textContent = 'Mostrando ' + visibles;
            }
            if (rowCuposEquipo) {
                rowCuposEquipo.style.display = debeMostrarFilaCuposEquipo() ? '' : 'none';
            }
            sincronizarUiVistaPastoral();
            sincronizarTarjetaPastores();
            return;
        }

        filas.forEach(function(fila) {
            const generoFila = String(fila.dataset.genero || 'hombres');
            const textoFila = String(fila.dataset.search || '');
            const digitosFila = String(fila.dataset.searchDigits || '');

            const okGenero = genero === 'todos' || genero === generoFila;
            const okTab = busquedaGlobal || coincideTab(fila, tabActual);

            const esCupoLibre = String(fila.dataset.cupoLibre || '0') === '1';
            const okTexto = esCupoLibre
                ? (texto === '')
                : textoCoincideBusqueda(textoFila, digitosFila, texto, textoDigitos);

            const mostrar = okGenero && okTab && okTexto;

            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) {
                visibles++;
            }
        });

        if (resumen) {
            resumen.textContent = busquedaGlobal
                ? ('Mostrando ' + visibles + ' (todas las pestañas)')
                : ('Mostrando ' + visibles);
        }

        if (rowCuposEquipo) {
            rowCuposEquipo.style.display = debeMostrarFilaCuposEquipo() ? '' : 'none';
        }

        sincronizarUiVistaPastoral();
        sincronizarTarjetaPastores();
    }

    const formFiltrosDiscipular = document.getElementById('formFiltrosEquipoDiscipular');
    let timerBuscarDiscipular = null;

    tabs.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            irADiscipularEquipo({ tab: String(btn.dataset.tab || 'equipo_principal') });
        });
    });

    if (filtroGenero) {
        filtroGenero.addEventListener('change', function() {
            irADiscipularEquipo({ genero: generoFiltroActual(), tab: tabActual });
        });
    }

    if (formFiltrosDiscipular) {
        formFiltrosDiscipular.addEventListener('submit', function() {
            const hiddenTab = formFiltrosDiscipular.querySelector('input[name="tab"]');
            if (hiddenTab) {
                hiddenTab.value = tabActual;
            }
            guardarGeneroFiltroLocal(generoFiltroActual());
        });
    }

    if (buscador) {
        buscador.addEventListener('input', function() {
            aplicarFiltros();
            if (!formFiltrosDiscipular) {
                return;
            }
            clearTimeout(timerBuscarDiscipular);
            const val = String(buscador.value || '').trim();
            timerBuscarDiscipular = setTimeout(function() {
                if (val.length >= 2 || val === '') {
                    formFiltrosDiscipular.requestSubmit();
                }
            }, 650);
        });
        buscador.addEventListener('keydown', function(ev) {
            if (ev.key === 'Enter' && formFiltrosDiscipular) {
                ev.preventDefault();
                clearTimeout(timerBuscarDiscipular);
                formFiltrosDiscipular.requestSubmit();
            }
        });
    }

    kpiGenero.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const genero = String(btn.dataset.genero || 'todos');
            const tabKpi = esVistaMinisterio ? 'lideres_144' : 'equipo_principal';
            irADiscipularEquipo({ genero: genero, tab: tabKpi });
        });
    });

    if (ministerioSelect) {
        ministerioSelect.addEventListener('change', function() {
            const id = String(ministerioSelect.value || '0');
            const params = new URLSearchParams();
            params.set('url', 'discipular/ministerios/equipo-principal');
            if (id !== '0') {
                params.set('id_ministerio', id);
                params.set('tab', 'lideres_144');
            } else {
                params.set('tab', 'equipo_principal');
            }
            params.set('genero', generoFiltroActual());
            guardarGeneroFiltroLocal(generoFiltroActual());
            window.location.href = baseUrlEquipoDiscipular + '?' + params.toString();
        });
    }

    if (coberturaPrincipalSelect) {
        coberturaPrincipalSelect.addEventListener('change', function() {
            irADiscipularEquipo({ tab: tabActual });
        });
    }

    let personasAsignablesOpcionesListas = false;
    let personasAsignablesPromesa = null;
    let personasAsignablesCacheKey = '';

    function resolverIdMinisterioAsignacion() {
        const desdeModal = idMinisterioAsignar
            ? parseInt(String(idMinisterioAsignar.value || '0'), 10)
            : 0;
        if (desdeModal > 0) {
            return desdeModal;
        }
        const desdePagina = parseInt(String(idMinisterioFiltroPagina || '0'), 10);
        return desdePagina > 0 ? desdePagina : 0;
    }

    function poblarOpcionesPersonasAsignables(items) {
        if (!selectPersonaAsignar || !Array.isArray(items)) {
            personasAsignablesOpcionesListas = true;
            return;
        }

        const valorActual = String(selectPersonaAsignar.value || '');
        while (selectPersonaAsignar.options.length > 1) {
            selectPersonaAsignar.remove(1);
        }

        const fragment = document.createDocumentFragment();
        items.forEach(function(item) {
            const id = parseInt(String(item.id || '0'), 10);
            if (id <= 0) {
                return;
            }

            const option = document.createElement('option');
            option.value = String(id);
            option.dataset.ministerio = String(parseInt(String(item.ministerio || '0'), 10));
            option.dataset.search = String(item.search || '');
            option.dataset.jerarquia = String(item.jerarquia || 'miembro');
            option.dataset.esLider12 = String(parseInt(String(item.es_lider12 || '0'), 10));
            option.dataset.idLiderActual = String(parseInt(String(item.id_lider_actual || '0'), 10));
            option.dataset.nombre = String(item.nombre || '');
            option.dataset.documento = String(item.documento || '');
            option.dataset.telefono = String(item.telefono || '');
            option.dataset.email = String(item.email || '');
            option.dataset.nombreRol = String(item.nombre_rol || '');
            option.dataset.nombreLiderActual = String(item.nombre_lider_actual || '');
            option.textContent = String(item.etiqueta || item.nombre || ('Persona ' + id));
            fragment.appendChild(option);
        });

        selectPersonaAsignar.appendChild(fragment);
        if (valorActual !== '') {
            const existe = Array.from(selectPersonaAsignar.options).some(function(opt) {
                return String(opt.value || '') === valorActual;
            });
            if (existe) {
                selectPersonaAsignar.value = valorActual;
            }
        }
        personasAsignablesOpcionesListas = true;
    }

    function ensurePersonasAsignablesOptions() {
        if (!selectPersonaAsignar) {
            return Promise.resolve();
        }

        const idMinisterio = resolverIdMinisterioAsignacion();
        const idLider = liderAsignar ? String(liderAsignar.value || '').trim() : '';
        const cacheKey = 'min:' + String(idMinisterio) + ':lid:' + idLider;
        if (personasAsignablesOpcionesListas && personasAsignablesCacheKey === cacheKey) {
            return Promise.resolve();
        }
        if (personasAsignablesPromesa) {
            return personasAsignablesPromesa;
        }

        let url = String(urlPersonasAsignables || '').trim();
        if (url === '') {
            personasAsignablesOpcionesListas = true;
            personasAsignablesCacheKey = cacheKey;
            return Promise.resolve();
        }

        url += (url.indexOf('?') >= 0 ? '&' : '?') + 'id_ministerio=' + encodeURIComponent(String(idMinisterio));
        if (idLider !== '' && idLider !== '0') {
            url += '&id_lider=' + encodeURIComponent(idLider);
        }

        personasAsignablesOpcionesListas = false;
        personasAsignablesPromesa = fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function(resp) {
                if (!resp.ok) {
                    throw new Error('fetch personas asignables');
                }
                return resp.json();
            })
            .then(function(items) {
                poblarOpcionesPersonasAsignables(items);
                personasAsignablesCacheKey = cacheKey;
            })
            .catch(function() {
                personasAsignablesOpcionesListas = true;
                personasAsignablesCacheKey = cacheKey;
            })
            .finally(function() {
                personasAsignablesPromesa = null;
            });

        return personasAsignablesPromesa;
    }

    function filtrarPersonasAsignables() {
        if (!selectPersonaAsignar) {
            return;
        }

        ensurePersonasAsignablesOptions().then(function() {
            filtrarPersonasAsignablesAplicado();
        });
    }

    function filtrarPersonasAsignablesAplicado() {
        if (!selectPersonaAsignar) {
            return;
        }

        const texto = buscarCupoUniversal ? String((buscarCupoUniversal.value || '').toLowerCase().trim()) : '';
        const opciones = Array.from(selectPersonaAsignar.options || []);

        opciones.forEach(function(op, idx) {
            if (idx === 0) {
                op.hidden = false;
                return;
            }

            const search = String(op.dataset.search || '').toLowerCase();
            const okTexto = texto === '' || search.indexOf(texto) !== -1;
            op.hidden = !okTexto;
        });

        const seleccionActual = String(selectPersonaAsignar.value || '');
        if (seleccionActual !== '') {
            const opcionSeleccionadaVisible = opciones.some(function(op, idx) {
                return idx > 0 && !op.hidden && String(op.value || '') === seleccionActual;
            });
            if (!opcionSeleccionadaVisible) {
                selectPersonaAsignar.value = '';
            }
        }

        renderResultadosBusquedaPersonas();

        actualizarPreviewPersonaNueva();
        if (liderAsignar && String(liderAsignar.value || '').trim() !== '') {
            renderSlotsEquipo(String(liderAsignar.value || '').trim());
        }
        sincronizarAyudaBusquedaCupoParaLider(liderAsignar ? String(liderAsignar.value || '').trim() : '');
    }

    const btnLiberarCupo = document.getElementById('btnLiberarCupo');
    const formLiberarCupo = document.getElementById('formLiberarCupo');
    const liberarIdLider = document.getElementById('liberar_id_lider');
    const liberarIdPersona = document.getElementById('liberar_id_persona');
    const liberarIdMinisterio = document.getElementById('liberar_id_ministerio');
    const liberarNumeroCupo = document.getElementById('liberar_numero_cupo');

    function activarModoCupo(modo) {
        const m = String(modo || 'pastoral');
        if (modoCupoAsignar) {
            modoCupoAsignar.value = m;
        }
        if (labelCoberturaCupo) {
            if (m === 'lider_12') {
                labelCoberturaCupo.textContent = 'Líder de 12 — su equipo directo (hasta 144)';
            } else if (m === 'lider_144') {
                labelCoberturaCupo.textContent = 'Líder de 144 — su equipo directo';
            } else if (m === 'lider_celula') {
                labelCoberturaCupo.textContent = 'Líder de célula — su equipo directo';
            } else {
                labelCoberturaCupo.textContent = usaEtiquetasPastorales ? 'Pastor/Pastora seleccionado(a)' : 'Líder principal seleccionado(a)';
            }
        }
        if (helpModoCupo) {
            helpModoCupo.textContent = 'Elige una persona y confirma. Si la casilla está ocupada, puedes sustituir o usar «Liberar cupo». Máximo 12 por líder.';
        }
        sincronizarAyudaBusquedaCupoParaLider(liderAsignar ? String(liderAsignar.value || '').trim() : '');
    }

    function activarModoCupoPastoral() {
        activarModoCupo('pastoral');
    }

    function sincronizarBotonLiberar() {
        if (!btnLiberarCupo) {
            return;
        }
        const persona = slotActualSeleccionado && slotActualSeleccionado.persona;
        const idOcupante = persona && persona.id_persona ? String(persona.id_persona) : '';
        const mostrar = idOcupante !== '' && liderAsignar && String(liderAsignar.value || '').trim() !== '';
        btnLiberarCupo.style.display = mostrar ? '' : 'none';
        if (liberarIdLider && liderAsignar) {
            liberarIdLider.value = String(liderAsignar.value || '');
        }
        if (liberarIdPersona) {
            liberarIdPersona.value = idOcupante;
        }
        if (liberarNumeroCupo && slotActualSeleccionado) {
            liberarNumeroCupo.value = String(slotActualSeleccionado.slot_numero || '');
        }
        if (liberarIdMinisterio && idMinisterioAsignar) {
            liberarIdMinisterio.value = String(idMinisterioAsignar.value || '0');
        }
    }

    function ejecutarLiberarCupo(params) {
        if (!formLiberarCupo) {
            return;
        }
        const idLider = String((params && params.id_lider) || '').trim();
        const idPersona = String((params && params.id_persona) || '').trim();
        const idMinisterio = String((params && params.id_ministerio) || '0').trim();
        const numeroCupo = parseInt(String((params && params.numero_cupo) || '0'), 10);

        if (idLider === '' || idPersona === '') {
            return;
        }

        const msgCupo = numeroCupo > 0
            ? ('¿Liberar la casilla ' + numeroCupo + '? Quedará vacía y la persona sin líder asignado.')
            : '¿Liberar este cupo? La persona quedará sin líder asignado.';
        if (!window.confirm(msgCupo)) {
            return;
        }

        if (liberarIdLider) {
            liberarIdLider.value = idLider;
        }
        if (liberarIdPersona) {
            liberarIdPersona.value = idPersona;
        }
        if (liberarIdMinisterio) {
            liberarIdMinisterio.value = idMinisterio;
        }
        if (liberarNumeroCupo) {
            liberarNumeroCupo.value = numeroCupo > 0 ? String(numeroCupo) : '';
        }

        formLiberarCupo.submit();
    }

    if (buscarCupoUniversal && selectPersonaAsignar) {
        buscarCupoUniversal.addEventListener('input', filtrarPersonasAsignables);
    }

    if (selectPersonaAsignar) {
        selectPersonaAsignar.addEventListener('change', actualizarPreviewPersonaNueva);
    }

    function validarCupoLiderSeleccionado(idLider, idMinisterio) {
        if (!estadoCupoLider || !btnAsignarCupo) {
            return;
        }

        if (!idLider) {
            liderSinCuposDisponibles = false;
            estadoCupoLider.textContent = 'Selecciona un líder';
            btnAsignarCupo.disabled = true;
            seleccionarSlot(null);
            return;
        }

        liderSinCuposDisponibles = false;
        estadoCupoLider.textContent = 'Validando cupo...';
        btnAsignarCupo.disabled = true;

        const url = '<?= PUBLIC_URL ?>?url=discipular/ministerios/validar-cupo-lider&id_lider=' + encodeURIComponent(String(idLider)) + '&id_ministerio=' + encodeURIComponent(String(idMinisterio || 0));

        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data || data.ok !== true) {
                    throw new Error('No fue posible validar el cupo.');
                }

                const equipoDirecto = parseInt(String(data.equipo_directo || 0), 10);
                const limite = parseInt(String(data.limite_equipo || 12), 10);
                const disponibles = parseInt(String(data.cupos_disponibles || 0), 10);

                liderSinCuposDisponibles = disponibles <= 0;
                if (liderSinCuposDisponibles) {
                    estadoCupoLider.textContent = 'Equipo completo (' + equipoDirecto + '/' + limite + '). Puedes sustituir un cupo ocupado o usar una casilla libre si aparece.';
                    actualizarTextoBotonAsignacion();
                    return;
                }

                estadoCupoLider.textContent = 'Cupos disponibles: ' + disponibles + ' (' + equipoDirecto + '/' + limite + ')';
                actualizarTextoBotonAsignacion();
            })
            .catch(function() {
                estadoCupoLider.textContent = 'Error al validar cupo';
                btnAsignarCupo.disabled = true;
            });
    }

    function abrirModalAsignar() {
        if (!modalAsignarCupo) {
            return;
        }
        ensurePersonasAsignablesOptions();
        modalAsignarCupo.classList.add('is-open');
        modalAsignarCupo.setAttribute('aria-hidden', 'false');
    }

    function cerrarModalAsignar() {
        if (!modalAsignarCupo) {
            return;
        }
        modalAsignarCupo.classList.remove('is-open');
        modalAsignarCupo.setAttribute('aria-hidden', 'true');
    }

    function abrirModalEditarLiderazgo() {
        if (!modalEditarLiderazgo) {
            return;
        }
        modalEditarLiderazgo.classList.add('is-open');
        modalEditarLiderazgo.setAttribute('aria-hidden', 'false');
    }

    function cerrarModalEditarLiderazgo() {
        if (!modalEditarLiderazgo) {
            return;
        }
        modalEditarLiderazgo.classList.remove('is-open');
        modalEditarLiderazgo.setAttribute('aria-hidden', 'true');
    }

    if (btnEditarLiderazgo) {
        btnEditarLiderazgo.addEventListener('click', abrirModalEditarLiderazgo);
    }

    if (btnCerrarModalEditarLiderazgo) {
        btnCerrarModalEditarLiderazgo.addEventListener('click', cerrarModalEditarLiderazgo);
    }

    if (modalEditarLiderazgo) {
        modalEditarLiderazgo.addEventListener('click', function(e) {
            const target = e.target;
            if (target && target.getAttribute && target.getAttribute('data-close-modal-liderazgo') === '1') {
                cerrarModalEditarLiderazgo();
            }
        });
    }

    if (btnAbrirAsignarLider) {
        btnAbrirAsignarLider.addEventListener('click', function() {
            const idL = String(idLiderGestionDefault || '').trim();
            if (idL === '' || idL === '0') {
                return;
            }
            const btnSynth = document.createElement('button');
            btnSynth.dataset.idLider = idL;
            btnSynth.dataset.idMinisterio = String(<?= (int)$idMinisterioFiltro ?>);
            btnSynth.dataset.nombreLider = nombreLiderGestionDefault;
            btnSynth.dataset.jerarquiaLider = jerarquiaLiderGestionDefaultPhp;
            btnSynth.dataset.modoCupo = esVistaMinisterio ? 'lider_144' : 'pastoral';
            prepararAsignacionDesdeBoton(btnSynth);
        });
    }

    document.addEventListener('click', function(e) {
        const btnJer = e.target && e.target.closest ? e.target.closest('.js-abrir-cupos-jerarquia') : null;
        if (!btnJer) {
            return;
        }
        e.preventDefault();
        const idL = String(btnJer.dataset.idLider || '').trim();
        if (idL === '' || idL === '0') {
            return;
        }
        const btnSynth = document.createElement('button');
        btnSynth.dataset.idLider = idL;
        btnSynth.dataset.idMinisterio = String(btnJer.dataset.idMinisterio || '0');
        btnSynth.dataset.nombreLider = String(btnJer.dataset.nombreLider || 'Líder');
        btnSynth.dataset.modoCupo = String(btnJer.dataset.modoCupo || (esVistaMinisterio ? 'lider_144' : 'pastoral'));
        prepararAsignacionDesdeBoton(btnSynth);
    });

    if (cerrarModalAsignarCupo) {
        cerrarModalAsignarCupo.addEventListener('click', cerrarModalAsignar);
    }

    if (modalAsignarCupo) {
        modalAsignarCupo.addEventListener('click', function(e) {
            const target = e.target;
            if (target && target.getAttribute && target.getAttribute('data-close-modal') === '1') {
                cerrarModalAsignar();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') {
            return;
        }
        if (modalAsignarCupo && modalAsignarCupo.classList.contains('is-open')) {
            cerrarModalAsignar();
        }
        if (modalEditarLiderazgo && modalEditarLiderazgo.classList.contains('is-open')) {
            cerrarModalEditarLiderazgo();
        }
    });

    if (btnLiberarCupo && formLiberarCupo) {
        btnLiberarCupo.addEventListener('click', function() {
            sincronizarBotonLiberar();
            ejecutarLiberarCupo({
                id_lider: liberarIdLider ? String(liberarIdLider.value || '').trim() : '',
                id_persona: liberarIdPersona ? String(liberarIdPersona.value || '').trim() : '',
                id_ministerio: liberarIdMinisterio ? String(liberarIdMinisterio.value || '0').trim() : '0',
                numero_cupo: liberarNumeroCupo ? parseInt(String(liberarNumeroCupo.value || '0'), 10) : 0
            });
        });
    }

    document.addEventListener('click', function(e) {
        const btnLiberar = e.target && e.target.closest ? e.target.closest('.js-liberar-cupo-directo') : null;
        if (!btnLiberar) {
            return;
        }
        e.preventDefault();
        ejecutarLiberarCupo({
            id_lider: String(btnLiberar.dataset.idLider || '').trim(),
            id_persona: String(btnLiberar.dataset.idPersona || '').trim(),
            id_ministerio: String(btnLiberar.dataset.idMinisterio || '0').trim(),
            numero_cupo: parseInt(String(btnLiberar.dataset.numeroCupo || '0'), 10)
        });
    });

    function prepararAsignacionDesdeBoton(btn) {
        const enVistaJerarquia = document.querySelector('.equipo-shell--jerarquia') !== null;
        if (vistaPastoralRedSoloLectura && tabActual !== 'equipo_principal' && !enVistaJerarquia) {
            return;
        }
        const modoBtn = String(btn.dataset.modoCupo || 'pastoral').trim();
        activarModoCupo(modoBtn);
        const jerRaw = String(btn.dataset.jerarquiaLider || '').trim();
        jerarquiaLiderActiva = jerRaw !== '' ? jerRaw : (usaEtiquetasPastorales ? 'pastor' : (esVistaMinisterio ? 'lider_12' : 'pastor'));
        let idLider = String(btn.dataset.idLider || '').trim();
        const idMinisterio = String(btn.dataset.idMinisterio || '0').trim();
        let nombreLider = String(btn.dataset.nombreLider || 'Líder seleccionado').trim();
        const nombreMinisterio = String(btn.dataset.nombreMinisterio || '').trim();
        const nombreRol = String(btn.dataset.nombreRol || '').trim();
        const slotNumeroSeleccionado = parseInt(String(btn.dataset.slotNumero || '0'), 10);
        const idPersonaObjetivoBtn = String(btn.dataset.idPersonaObjetivo || '').trim();
        modoSlotUnicoForzado = slotNumeroSeleccionado > 0;

        if (idLider === '' || idLider === '0') {
            idLider = String(idLiderGestionDefault || '').trim();
            if (nombreLider === '' || nombreLider === 'Líder seleccionado') {
                nombreLider = String(nombreLiderGestionDefault || 'Líder seleccionado').trim();
            }
        }

        if (idLider === '' || idLider === '0') {
            return;
        }

        if (liderAsignar) {
            liderAsignar.value = idLider;
        }
        if (idMinisterioAsignar) {
            idMinisterioAsignar.value = idMinisterio === '' ? '0' : idMinisterio;
        }
        const generoRedRetorno = document.getElementById('genero_red_retorno_asignar');
        if (generoRedRetorno && generoRedActualPagina !== '') {
            generoRedRetorno.value = generoRedActualPagina;
        }
        const cobRetorno = document.getElementById('cobertura_principal_retorno_asignar');
        if (cobRetorno && idLider !== '' && idLider !== '0') {
            cobRetorno.value = idLider;
        }

        personasAsignablesOpcionesListas = false;
        personasAsignablesCacheKey = '';

        if (liderSeleccionadoText) {
            let texto = nombreLider;
            if (nombreRol !== '') {
                texto += ' (' + nombreRol + ')';
            }
            if (nombreMinisterio !== '') {
                texto += ' - ' + nombreMinisterio;
            }
            liderSeleccionadoText.textContent = texto;
        }

        if (buscarCupoUniversal) {
            buscarCupoUniversal.value = '';
        }
        if (selectPersonaAsignar) {
            selectPersonaAsignar.value = '';
            if (idPersonaObjetivoBtn !== '' && !(slotNumeroSeleccionado > 0)) {
                const existeOpcion = Array.from(selectPersonaAsignar.options).some(function(opt) {
                    return String(opt.value || '') === idPersonaObjetivoBtn;
                });
                if (existeOpcion) {
                    selectPersonaAsignar.value = idPersonaObjetivoBtn;
                }
            }
        }
        if (personaNuevaPreview) {
            personaNuevaPreview.classList.add('is-empty');
            personaNuevaPreview.innerHTML = '<strong>Sin reemplazo seleccionado</strong><span>Elige la persona nueva para esta casilla.</span>';
        }

        const slots = obtenerSlotsEquipo(idLider);
        let slotInfoObjetivo = null;

        if (slotNumeroSeleccionado > 0) {
            slotInfoObjetivo = slots.find(function(item) {
                return Number(item.slot_numero) === Number(slotNumeroSeleccionado);
            }) || null;
        }

        if (!slotInfoObjetivo) {
            slotInfoObjetivo = slots.find(function(slot) {
                return !slot.persona || !slot.persona.id_persona;
            }) || (slots.length ? slots[0] : null);
        }

        // Si se abrió desde una fila ocupada, asegurar ocupante actual del slot
        if (slotNumeroSeleccionado > 0 && idPersonaActualSlot && idPersonaObjetivoBtn !== '') {
            idPersonaActualSlot.value = idPersonaObjetivoBtn;
        }

        seleccionarSlot(slotInfoObjetivo);
        abrirGestionSlot(slotInfoObjetivo, true);

        filtrarPersonasAsignables();
        actualizarPreviewPersonaNueva();
        validarCupoLiderSeleccionado(idLider, idMinisterio);
        abrirModalAsignar();
    }

    botonesCupoPastoral.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            prepararAsignacionDesdeBoton(btn);
        });
    });

    botonesAsignar.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            prepararAsignacionDesdeBoton(btn);
        });
    });

    document.addEventListener('click', function(e) {
        const btn = e.target && e.target.closest ? (e.target.closest('.js-gestionar-cupo-pastoral') || e.target.closest('.js-asignar-desde-cupo')) : null;
        if (!btn) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        prepararAsignacionDesdeBoton(btn);
    });

    if (btnAsignarCupo) {
        btnAsignarCupo.disabled = true;
    }

    actualizarPreviewPersonaNueva();

    aplicarFiltros();
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
<script src="<?= ASSETS_URL ?>/js/descargar_tabla_asistencia.js?v=20260529-discipular"></script>

<?php include VIEWS . '/layout/footer.php'; ?>