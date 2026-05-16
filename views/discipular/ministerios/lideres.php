<?php include VIEWS . '/layout/header.php'; ?>

<?php
$idMinisterioFiltro = (int)($id_ministerio_filtro ?? 0);
$nombreMinisterioFiltro = trim((string)($nombre_ministerio_filtro ?? ''));
$hayFiltroMinisterio = $idMinisterioFiltro > 0;
$esCoberturaPastoralGlobal = !$hayFiltroMinisterio;

$encabezado = is_array($encabezado_equipo_principal ?? null) ? $encabezado_equipo_principal : [];
$lideresEquipo = is_array($lideres_equipo_principal ?? null) ? $lideres_equipo_principal : [];
$liderazgoRed = is_array($liderazgo_red ?? null) ? $liderazgo_red : [];
$discipulosRed = is_array($discipulos_red ?? null) ? $discipulos_red : [];
$personasAsignables = is_array($personas_asignables ?? null) ? $personas_asignables : [];
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
$puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'crear');

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
$esMinisterioPastores = $hayFiltroMinisterio && (
    strpos($nombreMinisterioNormalizado, 'pastor') !== false
    || strpos($nombreMinisterioNormalizado, 'pastoral') !== false
);
$usarEtiquetasPastorales = $esCoberturaPastoralGlobal || $esMinisterioPastores;

$labelLiderazgoPrincipal1 = $usarEtiquetasPastorales ? 'Pastor principal' : 'Lider principal';
$labelLiderazgoPrincipal2 = $usarEtiquetasPastorales ? 'Pastora principal' : 'Lider principal';
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
$urlRetornoEquipo = PUBLIC_URL . '?url=discipular/ministerios/equipo-principal' . ($hayFiltroMinisterio ? '&id_ministerio=' . $idMinisterioFiltro : '');
$mostrarBotonesCupoPastoral = ($idLiderPrincipal1 > 0 || $idLiderPrincipal2 > 0);
$labelSelectorMinisterio = $hayFiltroMinisterio ? 'Ministerio:' : 'Cobertura:';
$textoOpcionGeneral = $hayFiltroMinisterio ? 'Todos' : 'Cobertura pastoral general';
$textoBotonEditarLiderazgo = $hayFiltroMinisterio ? 'Editar líderes principales' : 'Configurar cabeza pastoral';
$tituloBotonEditarLiderazgo = $usarEtiquetasPastorales ? 'Configurar cabeza pastoral' : 'Configurar lideres principales';
$tituloModalEditarLiderazgo = $hayFiltroMinisterio ? 'Editar líderes principales del ministerio' : 'Configurar cobertura pastoral general';
$labelSeccionLiderazgo = $hayFiltroMinisterio ? 'Líderes principales del ministerio' : 'Cobertura pastoral general';
$coberturaPrincipalActual = '';
if ($hayFiltroMinisterio) {
    $coberturaSolicitada = trim((string)($_GET['cobertura_principal'] ?? ''));
    if ($coberturaSolicitada !== '' && ctype_digit($coberturaSolicitada)) {
        $coberturaId = (int)$coberturaSolicitada;
        if (in_array($coberturaId, array_filter([$idLiderPrincipal1, $idLiderPrincipal2]), true)) {
            $coberturaPrincipalActual = (string)$coberturaId;
        }
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
    $esLider144 = (int)($row['es_lider_144'] ?? 0) === 1;

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

$tabActivo = strtolower(trim((string)($_GET['tab'] ?? 'equipo_principal')));
if (!in_array($tabActivo, ['equipo_principal', 'lideres_144', 'lideres_celula', 'discipulos'], true)) {
    $tabActivo = 'equipo_principal';
}

$filtroGeneroGet = strtolower(trim((string)($_GET['genero'] ?? 'todos')));
if (!in_array($filtroGeneroGet, ['todos', 'hombres', 'mujeres'], true)) {
    $filtroGeneroGet = 'todos';
}

$buscarGet = strtolower(trim((string)($_GET['buscar'] ?? '')));
$soloDigitosBuscar = preg_replace('/\D+/', '', $buscarGet);

$rowsTablaFiltradas = array_values(array_filter($rowsTabla, static function($row) use ($tabActivo, $filtroGeneroGet, $buscarGet, $soloDigitosBuscar, $coberturaPrincipalActual) {
    $okTab = true;
    if ($tabActivo === 'equipo_principal') {
        $okTab = !empty($row['es_equipo_principal']);
    } elseif ($tabActivo === 'lideres_144') {
        $okTab = !empty($row['es_lider_144']);
    } elseif ($tabActivo === 'lideres_celula') {
        $okTab = !empty($row['es_lider_celula']);
    } elseif ($tabActivo === 'discipulos') {
        $okTab = !empty($row['es_discipulo']);
    }

    $generoRow = strtolower(trim((string)($row['genero'] ?? 'hombres')));
    $okGenero = $filtroGeneroGet === 'todos' || $generoRow === $filtroGeneroGet;

    $texto = strtolower(trim(
        (string)($row['nombre'] ?? '') . ' ' .
        (string)($row['apellido'] ?? '') . ' ' .
        (string)($row['numero_documento'] ?? '') . ' ' .
        (string)($row['telefono'] ?? '') . ' ' .
        (string)($row['email'] ?? '')
    ));
    $digitos = preg_replace('/\D+/', '', (string)($row['numero_documento'] ?? '') . ' ' . (string)($row['telefono'] ?? ''));
    $okBuscar = $buscarGet === '' || strpos($texto, $buscarGet) !== false || ($soloDigitosBuscar !== '' && strpos($digitos, $soloDigitosBuscar) !== false);

    $okCobertura = true;
    if ($tabActivo === 'equipo_principal' && $coberturaPrincipalActual !== '') {
        $okCobertura = (string)($row['id_lider_actual'] ?? '0') === $coberturaPrincipalActual;
    }

    return $okTab && $okGenero && $okBuscar && $okCobertura;
}));

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

$equipoDirectoPorLider = [];
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

$cupoNumeroPorPersona = [];
foreach ($equipoDirectoPorLider as $equipoLider) {
    foreach ($equipoLider as $personaEquipo) {
        $idPersonaEquipo = (int)($personaEquipo['id_persona'] ?? 0);
        $slotNumeroEquipo = (int)($personaEquipo['slot_numero'] ?? 0);
        if ($idPersonaEquipo > 0 && $slotNumeroEquipo > 0) {
            $cupoNumeroPorPersona[$idPersonaEquipo] = $slotNumeroEquipo;
        }
    }
}

$equipoDirectoPorLiderJson = json_encode($equipoDirectoPorLider, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($equipoDirectoPorLiderJson)) {
    $equipoDirectoPorLiderJson = '{}';
}

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
    if ($idLiderPrincipal1 > 0) {
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

?>

<div class="equipo-shell">
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

    <?php include VIEWS . '/discipular/partials/jerarquia_g12_panel.php'; ?>

    <section class="equipo-hero card">
        <div class="equipo-hero-grid">
            <div class="equipo-avatar" aria-hidden="true"></div>
            <div class="equipo-perfil">
                <p class="equipo-nombre"><?= htmlspecialchars($nombrePastor) ?></p>
                <p>Email: <?= htmlspecialchars($emailPastor !== '' ? $emailPastor : 'Sin registro') ?></p>
                <p>Telefono: <?= htmlspecialchars($telefonoPastor !== '' ? $telefonoPastor : 'Sin registro') ?></p>
                <p>Sede: <?= htmlspecialchars($sedePastor) ?></p>
                <p id="lineaPastorPrincipal1"><?= htmlspecialchars($labelLiderazgoPrincipal1) ?>: <?= htmlspecialchars($nombreLiderPrincipal1 !== '' ? $nombreLiderPrincipal1 : 'Sin definir') ?></p>
                <p id="lineaPastorPrincipal2"><?= htmlspecialchars($labelLiderazgoPrincipal2) ?>: <?= htmlspecialchars($nombreLiderPrincipal2 !== '' ? $nombreLiderPrincipal2 : 'Sin definir') ?></p>
                <?php if (!$hayFiltroMinisterio): ?>
                <p>Tipo de cobertura: Cobertura pastoral general</p>
                <?php endif; ?>
                <div class="equipo-perfil-actions">
                    <button type="button" id="btnEditarLiderazgo" class="btn btn-liderazgo" title="<?= htmlspecialchars($tituloBotonEditarLiderazgo) ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </button>
                    <button type="button" id="btnAbrirAsignarLider" class="btn btn-primary btn-sm" <?= !$mostrarBotonesCupoPastoral ? 'disabled' : '' ?> title="<?= $mostrarBotonesCupoPastoral ? 'Abrir la ventana para asignar las 12 casillas del equipo' : htmlspecialchars($textoAvisoConfigurarLideres) ?>">
                        <?= $usarEtiquetasPastorales ? 'Gestionar 12 cupos del pastor' : 'Gestionar 12 cupos del líder' ?>
                    </button>
                </div>
            </div>
            <div class="equipo-kpi equipo-kpi-main">
                <span class="equipo-kpi-label">Equipo principal</span>
                <strong><?= $totalEquipoPrincipal ?></strong>
                <div class="equipo-kpi-mini-actions">
                    <a class="kpi-mini-btn" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'equipo_principal', 'genero' => 'hombres'])) ?>">Hombres <?= $equipoPrincipalHombres ?></a>
                    <a class="kpi-mini-btn" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'equipo_principal', 'genero' => 'mujeres'])) ?>">Mujeres <?= $equipoPrincipalMujeres ?></a>
                </div>
            </div>
            <a class="equipo-kpi equipo-kpi-link" href="<?= htmlspecialchars($urlMinisteriosLista) ?>" title="Ver ministerios y organizar parejas">
                <span class="equipo-kpi-label">Ministerios</span>
                <strong><?= $ministerioCantidad ?></strong>
                <small>Organizar parejas</small>
            </a>
        </div>
        <div class="equipo-ministerio-row">
            <label for="ministerioSelect"><?= htmlspecialchars($labelSelectorMinisterio) ?></label>
            <select id="ministerioSelect" class="form-control form-control-sm">
                <option value="0" <?= !$hayFiltroMinisterio ? 'selected' : '' ?>><?= htmlspecialchars($textoOpcionGeneral) ?></option>
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
                <?php if ($hayFiltroMinisterio && ($idLiderPrincipal1 > 0 || $idLiderPrincipal2 > 0)): ?>
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
                <input type="hidden" id="modo_cupo_asignar" value="pastoral">
                <input type="hidden" name="id_persona_actual_slot" id="id_persona_actual_slot" value="">
                <input type="hidden" name="numero_cupo" id="numero_cupo_asignar" value="">

                <div class="cupos-header-info">
                    <div>
                        <label id="labelCoberturaCupo"><?= htmlspecialchars($labelCoberturaSeleccionada) ?></label>
                        <div id="liderSeleccionadoText" class="form-control form-control-sm cupo-resumen-box">Sin seleccionar</div>
                    </div>
                </div>

                <div class="cupos-wizard-hint" id="cuposWizardHint">
                    <p class="cupos-wizard-lead"><strong>Cómo usar esta ventana</strong> (tres pasos)</p>
                    <ol class="cupos-wizard-steps">
                        <li><span class="cupos-wizard-n">1</span> Revisa la lista de las <strong>12 casillas</strong>: libre u ocupada.</li>
                        <li><span class="cupos-wizard-n">2</span> Pulsa <strong>Elegir casilla</strong> en la fila del número o cámbiala desde la tabla principal.</li>
                        <li><span class="cupos-wizard-n">3</span> Busca la persona, tócala en los resultados y pulsa <strong>Confirmar asignación</strong>.</li>
                    </ol>
                </div>

                <div class="cupos-list-wrap">
                    <div class="cupos-list-title">Vista de las 12 casillas del equipo directo</div>
                    <ul id="listaCuposEquipo" class="cupos-list" aria-label="Casillas del 1 al 12"></ul>
                    <p class="cupos-list-help">Cada <?=$usarEtiquetasPastorales ? 'pastor' : 'líder principal' ?> tiene hasta 12 personas. Aquí ves el estado y eliges cuál editar.</p>
                </div>

                <div class="cupos-asignar-section" style="display:none;">
                    <div style="border-top: 1px solid #d7e2f3; padding-top: 12px; margin-top: 12px;">
                        <div id="cupoActualResumen" style="margin-bottom: 12px; padding: 8px; background: #f8fbff; border-left: 3px solid #4f66d4; border-radius: 4px;">
                            <small style="color: #60708f;">Casilla seleccionada:</small>
                            <strong id="cupoResumenTexto" style="color: #2d4e77; display: block;">-</strong>
                        </div>
                        <label for="buscarCupoUniversal">Buscar persona</label>
                        <input id="buscarCupoUniversal" type="text" class="form-control form-control-sm" placeholder="Nombre, cédula, teléfono o email…" style="margin-bottom: 8px;">
                        <small id="buscarCupoAyuda" class="cupos-buscar-ayuda">Solo se listan perfiles que pueden quedar bajo este líder según la jerarquía (el servidor valida al guardar).</small>
                        <div id="resultadosBuscarPersona" class="resultados-persona-list" aria-live="polite"></div>
                        <select id="id_persona_asignar" name="id_persona" class="form-control form-control-sm" required style="display:none;">
                            <option value="">Seleccionar persona...</option>
                            <?php foreach ($personasAsignables as $persona): ?>
                                <?php
                                    $idP = (int)($persona['Id_Persona'] ?? 0);
                                    $idMinP = (int)($persona['Id_Ministerio'] ?? 0);
                                    $nombreP = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
                                    $docP = trim((string)($persona['Numero_Documento'] ?? ''));
                                    $telP = trim((string)($persona['Telefono'] ?? ''));
                                    $emailP = trim((string)($persona['Email'] ?? ''));
                                    $nombreRolP = trim((string)($persona['Nombre_Rol'] ?? ''));
                                    $nombreLiderActualP = trim((string)($persona['Nombre_Lider'] ?? ''));
                                    $idLiderActualP = (int)($persona['Id_Lider'] ?? 0);
                                    $esLider12P = $esRolLider12Fn(($persona['Id_Rol'] ?? 0), ($persona['Nombre_Rol'] ?? '')) ? 1 : 0;
                                    $textoP = strtolower(trim($nombreP . ' ' . $docP . ' ' . $telP . ' ' . $emailP . ' ' . $nombreRolP . ' ' . $nombreLiderActualP));
                                ?>
                                <?php if ($idP > 0): ?>
                                <option
                                    value="<?= $idP ?>"
                                    data-ministerio="<?= $idMinP ?>"
                                    data-search="<?= htmlspecialchars($textoP) ?>"
                                    data-jerarquia="<?= htmlspecialchars((string)($persona['_jerarquia'] ?? 'miembro'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-es-lider12="<?= $esLider12P ?>"
                                    data-id-lider-actual="<?= $idLiderActualP ?>"
                                    data-nombre="<?= htmlspecialchars($nombreP) ?>"
                                    data-documento="<?= htmlspecialchars($docP) ?>"
                                    data-telefono="<?= htmlspecialchars($telP) ?>"
                                    data-email="<?= htmlspecialchars($emailP) ?>"
                                    data-nombre-rol="<?= htmlspecialchars($nombreRolP) ?>"
                                    data-nombre-lider-actual="<?= htmlspecialchars($nombreLiderActualP) ?>"
                                >
                                    <?= htmlspecialchars($nombreP !== '' ? $nombreP : ('Persona ' . $idP)) ?>
                                    <?= $docP !== '' ? ' | CC ' . htmlspecialchars($docP) : '' ?>
                                    <?= $telP !== '' ? ' | TEL ' . htmlspecialchars($telP) : '' ?>
                                    <?= $nombreLiderActualP !== '' ? ' | Lider actual: ' . htmlspecialchars($nombreLiderActualP) : '' ?>
                                </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div id="personaNuevaPreview" class="cupo-persona-card is-empty" style="margin-top:8px;">
                            <strong>Sin persona seleccionada</strong>
                            <span>Elige la persona para esta casilla.</span>
                        </div>
                    </div>
                </div>

                <div class="cupos-footer-row">
                    <small id="helpModoCupo" style="display:block; margin-top:4px; color:#60708f;">Selecciona una persona y pulsa Confirmar asignación.</small>
                    <button type="submit" id="btnAsignarCupo" class="btn btn-primary btn-sm">Confirmar asignación</button>
                </div>
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

    <section class="equipo-tabs card">
        <div class="equipo-tabs-row">
            <a class="equipo-tab <?= $tabActivo === 'equipo_principal' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'equipo_principal'])) ?>" data-tab="equipo_principal">Equipo principal <span><?= $totalEquipoPrincipal ?></span></a>
            <a class="equipo-tab <?= $tabActivo === 'lideres_144' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'lideres_144'])) ?>" data-tab="lideres_144">Líderes de 144 <span><?= $totalLideres144 ?></span></a>
            <a class="equipo-tab <?= $tabActivo === 'lideres_celula' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'lideres_celula'])) ?>" data-tab="lideres_celula">Líderes de célula <span><?= $totalLideresCelula ?></span></a>
            <a class="equipo-tab <?= $tabActivo === 'discipulos' ? 'is-active' : '' ?>" href="<?= htmlspecialchars($buildEquipoUrl(['tab' => 'discipulos'])) ?>" data-tab="discipulos">Discípulos <span><?= $totalDiscipulos ?></span></a>
        </div>
        <form class="equipo-filtros-row" method="get" action="<?= PUBLIC_URL ?>">
            <input type="hidden" name="url" value="discipular/ministerios/equipo-principal">
            <?php if ($idMinisterioFiltro > 0): ?>
                <input type="hidden" name="id_ministerio" value="<?= $idMinisterioFiltro ?>">
            <?php endif; ?>
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActivo) ?>">

            <select id="filtroGenero" name="genero" class="form-control form-control-sm equipo-select" onchange="this.form.submit()">
                <option value="todos" <?= $filtroGeneroGet === 'todos' ? 'selected' : '' ?>>Todos</option>
                <option value="hombres" <?= $filtroGeneroGet === 'hombres' ? 'selected' : '' ?>>Hombres</option>
                <option value="mujeres" <?= $filtroGeneroGet === 'mujeres' ? 'selected' : '' ?>>Mujeres</option>
            </select>
            <input id="busquedaUniversal" name="buscar" class="form-control form-control-sm" type="search" value="<?= htmlspecialchars((string)($_GET['buscar'] ?? '')) ?>" placeholder="Buscar por cédula, teléfono, nombre, apellido o email...">
            <button type="submit" class="btn btn-sm btn-secondary">Buscar</button>
        </form>
    </section>

    <?php if ($tabActivo === 'equipo_principal'): ?>
    <section class="equipo-guia-cupos card" aria-label="Guía para cupos">
        <div class="equipo-guia-head">
            <strong><?= $usarEtiquetasPastorales ? 'Guía rápida: equipo del pastor (12 cupos)' : 'Guía rápida: 12 cupos del líder principal' ?></strong>
        </div>
        <ol class="equipo-guia-pasos">
            <li><span class="equipo-guia-num">1</span> <?= $usarEtiquetasPastorales ? 'Configura <strong>cabeza pastoral</strong> con el botón del lápiz arriba.' : 'Configura los <strong>líderes principales</strong> con el botón del lápiz.' ?> Sin eso no habrá casillas activas.</li>
            <li><span class="equipo-guia-num">2</span> En las pestañas de arriba, deja seleccionada <strong>Equipo principal</strong>. Si hay pastor hombre/mujer, usa el selector <strong>Equipo principal</strong> del bloque superior para la cobertura de las casillas.</li>
            <li><span class="equipo-guia-num">3</span> En la tabla, pulsa el botón de la columna <strong>Casilla</strong> (número o +). También sirve el botón azul del perfil: <strong><?= $usarEtiquetasPastorales ? 'Gestionar 12 cupos del pastor' : 'Gestionar 12 cupos del líder' ?></strong>.</li>
            <li><span class="equipo-guia-num">4</span> En la ventana, elige la fila del 1 al 12, busca a la persona y confirma.</li>
        </ol>
        <?php if (!$mostrarBotonesCupoPastoral): ?>
            <p class="equipo-guia-aviso"><?= htmlspecialchars($textoAvisoConfigurarLideres) ?> También puedes usar el botón azul del perfil cuando esté activo.</p>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <div class="table-container">
        <table class="data-table ministerios-equipo-table">
            <thead>
                <tr>
                    <th title="Casilla del 1 al 12 en el equipo directo del líder o pastor">Casilla</th>
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
                $lideresEquipoPrincipal = array_filter($rowsTabla, function($row) {
                    return !empty($row['es_equipo_principal']);
                });
                if (!empty($rowsTablaFiltradas)):
                    foreach ($rowsTablaFiltradas as $row):
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
                            $nombre . ' ' . $apellido . ' ' . $documento . ' ' . $telefono . ' ' . $email
                        ));
                        $digitosBusqueda = $soloDigitos($documento) . ' ' . $soloDigitos($telefono);
                ?>
                        <tr
                            data-genero="<?= htmlspecialchars($genero) ?>"
                            data-equipo-principal="<?= !empty($row['es_equipo_principal']) ? '1' : '0' ?>"
                            data-lideres-144="<?= !empty($row['es_lider_144']) ? '1' : '0' ?>"
                            data-lideres-celula="<?= !empty($row['es_lider_celula']) ? '1' : '0' ?>"
                            data-discipulos="<?= !empty($row['es_discipulo']) ? '1' : '0' ?>"
                            data-cupos-disponibles="<?= $cuposDisponibles ?>"
                            data-search="<?= htmlspecialchars($textoBusqueda) ?>"
                            data-search-digits="<?= htmlspecialchars($digitosBusqueda) ?>"
                        >
                            <td>
                                <?php if ($liderObjetivoFila > 0): ?>
                                    <button
                                        type="button"
                                        class="btn btn-xs <?= $cupoNumeroFila > 0 ? 'btn-outline-primary' : 'btn-outline-success' ?> js-asignar-desde-cupo"
                                        data-id-lider="<?= $liderObjetivoFila ?>"
                                        data-id-ministerio="<?= $idMinisterioFiltro > 0 ? $idMinisterioFiltro : $idMinisterioFila ?>"
                                        data-nombre-lider="<?= htmlspecialchars($nombreLiderObjetivoFila) ?>"
                                        data-jerarquia-lider="<?= htmlspecialchars($jerarquiaPorLiderId[$liderObjetivoFila] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-nombre-ministerio="<?= htmlspecialchars($nombreMinisterioFila) ?>"
                                        data-nombre-rol="<?= htmlspecialchars($nombreRolFila) ?>"
                                        data-slot-numero="<?= $cupoNumeroFila > 0 ? $cupoNumeroFila : '' ?>"
                                        data-id-persona-objetivo="<?= $idPersona ?>"
                                        title="<?= $cupoNumeroFila > 0 ? ('Abrir ventana: casilla ' . $cupoNumeroFila . ' (cambiar persona)') : 'Abrir ventana: asignar a una casilla libre' ?>"
                                    >
                                        <?= $cupoNumeroFila > 0 ? $cupoNumeroFila : '+' ?>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" title="Configura la cabeza pastoral primero" disabled>?</button>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($documento !== '' ? $documento : '-') ?></td>
                            <td><?= htmlspecialchars($nombre !== '' ? $nombre : '-') ?></td>
                            <td><?= htmlspecialchars($apellido !== '' ? $apellido : '-') ?></td>
                            <td><?= htmlspecialchars($telefono !== '' ? $telefono : '-') ?></td>
                            <td><?= htmlspecialchars($email !== '' ? $email : '-') ?></td>
                            <td style="padding:2px 0; min-width:120px;">
                                <div class="acciones-fila-compacta">
                                    <a class="btn btn-xs btn-outline-primary" href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $idPersona ?>">Perfil</a>
                                    <a class="btn btn-xs btn-outline-secondary" href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $idPersona ?>" title="Editar">✎</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php
                    // Si hay cupos libres, mostrar fila especial con botón
                    $cuposOcupados = count($lideresEquipoPrincipal);
                    $cuposTotales = 12;
                    $cuposLibres = $cuposTotales - $cuposOcupados;
                    ?>
                    <tr id="rowCuposEquipo" style="<?= $tabActivo === 'equipo_principal' ? '' : 'display:none;' ?>">
                        <td colspan="7" class="text-center cupos-libre-row">
                            <span class="cupos-libre-label">Recordatorio:</span>
                            La columna <strong>Casilla</strong> abre la ventana de los 12 cupos. El símbolo <strong>+</strong> asigna una casilla libre; si ya hay número, sirve para sustituir a esa persona.
                        </td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay líderes asignados en el equipo principal.</td>
                    </tr>
                    <tr id="rowCuposEquipo" style="<?= $tabActivo === 'equipo_principal' ? '' : 'display:none;' ?>">
                        <td colspan="7" class="text-center cupos-libre-row">
                            <span class="cupos-libre-label">Siguiente paso:</span>
                            <?php if ($hayFiltroMinisterio): ?>
                                <?= htmlspecialchars($textoAvisoConfigurarLideres) ?>
                            <?php else: ?>
                                Configura primero la cabeza pastoral con el botón del lápiz; después podrás usar la columna Casilla o el botón azul «Gestionar 12 cupos».
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
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
    overflow-x: hidden;
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
    grid-template-columns: 80px 1fr auto;
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
}

.cupos-footer-row small {
    max-width: 760px;
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
    grid-template-columns: minmax(160px, 220px) minmax(180px, 220px) minmax(260px, 1fr) auto;
    gap: 10px;
    align-items: center;
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

.cupos-libre-num {
    color: #2f6f3f;
    font-weight: bold;
    margin-right: 4px;
}

.cupos-casillas-grid {
    display: grid;
    grid-template-columns: repeat(6, minmax(92px, 1fr));
    gap: 8px;
    margin-top: 10px;
}

.cupo-casilla-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    min-height: 56px;
    border-radius: 10px;
    border: 1px solid #d4dfef;
    background: #fff;
    padding: 6px;
}

.cupo-casilla-btn.is-free {
    background: #f3fbf5;
    border-color: #b8dfc0;
}

.cupo-casilla-btn.is-occupied {
    background: #f5f8ff;
    border-color: #c8d8f0;
}

.cupo-casilla-numero {
    font-weight: 800;
    font-size: 14px;
    color: #2c4f79;
}

.cupo-casilla-estado {
    font-size: 11px;
    color: #4b6387;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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

    .cupos-casillas-grid {
        grid-template-columns: repeat(3, minmax(84px, 1fr));
    }
}
</style>

<script>
(function() {
    const filas = Array.from(document.querySelectorAll('.ministerios-equipo-table tbody tr[data-search]'));
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
    const buscarCupoAyuda = document.getElementById('buscarCupoAyuda');
    const selectPersonaAsignar = document.getElementById('id_persona_asignar');
    const resultadosBuscarPersona = document.getElementById('resultadosBuscarPersona');
    const idPersonaActualSlot = document.getElementById('id_persona_actual_slot');
    const numeroCupoAsignar = document.getElementById('numero_cupo_asignar');
    const listaCuposEquipo = document.getElementById('listaCuposEquipo');
    const personaNuevaPreview = document.getElementById('personaNuevaPreview');
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
    const usaEtiquetasPastorales = <?= $usarEtiquetasPastorales ? 'true' : 'false' ?>;
    const equipoDirectoPorLider = <?= $equipoDirectoPorLiderJson ?>;
    let slotActualSeleccionado = null;
    let liderSinCuposDisponibles = false;
    let jerarquiaLiderActiva = '';
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
        const jer = jerarquiaLiderActiva || (usaEtiquetasPastorales ? 'pastor' : 'lider_12');
        if (jer === 'pastor') {
            return 'No hay resultados con ese texto. Bajo un pastor pueden asignarse líderes de 12, 144, célula o discípulos. Prueba otra palabra o revisa el ministerio.';
        }
        if (jer === 'lider_12') {
            return 'No hay resultados. Bajo un líder de 12 solo entran líderes de 144, de célula o discípulos. Prueba otra palabra.';
        }
        if (jer === 'lider_144') {
            return 'No hay resultados. Bajo un líder de 144 solo entran líderes de célula o discípulos.';
        }
        if (jer === 'lider_celula') {
            return 'No hay resultados. Bajo un líder de célula solo entran discípulos.';
        }
        return 'No hay coincidencias para la búsqueda actual.';
    }

    function sincronizarAyudaBusquedaCupo() {
        if (!buscarCupoAyuda) {
            return;
        }
        const jer = jerarquiaLiderActiva || (usaEtiquetasPastorales ? 'pastor' : 'lider_12');
        if (jer === 'pastor') {
            buscarCupoAyuda.textContent = 'Puedes asignar líderes de 12, 144, de célula o discípulos. El sistema comprueba el rol al guardar.';
        } else if (jer === 'lider_12') {
            buscarCupoAyuda.textContent = 'Puedes asignar líderes de 144, de célula o discípulos bajo este líder de 12.';
        } else if (jer === 'lider_144') {
            buscarCupoAyuda.textContent = 'Puedes asignar líderes de célula o discípulos bajo este líder de 144.';
        } else if (jer === 'lider_celula') {
            buscarCupoAyuda.textContent = 'Solo aparecen discípulos u otros perfiles compatibles con un líder de célula.';
        } else {
            buscarCupoAyuda.textContent = 'Solo se listan perfiles compatibles con este líder; el servidor valida al guardar.';
        }
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
            slots.push({
                slot_numero: i,
                persona: equipo[i - 1] || null,
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
        if (liderSinCuposDisponibles && !slotOcupado) {
            btnAsignarCupo.disabled = true;
            btnAsignarCupo.textContent = 'Equipo completo';
            return;
        }

        btnAsignarCupo.disabled = false;
        btnAsignarCupo.textContent = slotActualSeleccionado.persona && slotActualSeleccionado.persona.id_persona
            ? ('Sustituir casilla ' + slotActualSeleccionado.slot_numero)
            : ('Asignar a la casilla ' + slotActualSeleccionado.slot_numero);
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

            return '<li class="cupos-list-item ' + (ocupado ? 'is-occupied' : '') + selClass + '" data-slot-numero="' + slotInfo.slot_numero + '">'
                + '<div class="cupos-item-numero">Casilla ' + slotInfo.slot_numero + '</div>'
                + '<div class="cupos-item-content">'
                + '<span class="cupos-item-status ' + (ocupado ? '' : 'libre') + '">' + (ocupado ? escapeHtml(nombre) : 'Libre') + '</span>'
                + (meta.length > 0 ? '<span class="cupos-item-meta">' + escapeHtml(meta.join(' | ')) + '</span>' : '')
                + '</div>'
                + '<button type="button" class="cupos-item-btn js-gestionar-cupo-item" data-slot-numero="' + slotInfo.slot_numero + '">Elegir casilla</button>'
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

    function coincideTab(fila, tab) {
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

    function aplicarFiltros() {
        if (!filas.length) {
            sincronizarTarjetaPastores();
            return;
        }

        const genero = filtroGenero ? String(filtroGenero.value || 'todos') : 'todos';
        const texto = buscador ? String((buscador.value || '').toLowerCase().trim()) : '';
        const textoDigitos = soloDigitos(texto);
        let visibles = 0;

        filas.forEach(function(fila) {
            const generoFila = String(fila.dataset.genero || 'hombres');
            const textoFila = String(fila.dataset.search || '');
            const digitosFila = String(fila.dataset.searchDigits || '');

            const okGenero = genero === 'todos' || genero === generoFila;
            const okTab = coincideTab(fila, tabActual);

            const okTextoPlano = texto === '' || textoFila.indexOf(texto) !== -1;
            const okTextoDigitos = textoDigitos === '' || digitosFila.indexOf(textoDigitos) !== -1;
            const okTexto = okTextoPlano || (textoDigitos !== '' && okTextoDigitos);

            const mostrar = okGenero && okTab && okTexto;

            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) {
                visibles++;
            }
        });

        if (resumen) {
            resumen.textContent = 'Mostrando ' + visibles;
        }

        if (rowCuposEquipo) {
            rowCuposEquipo.style.display = (tabActual === 'equipo_principal') ? '' : 'none';
        }

        sincronizarTarjetaPastores();
    }

    tabs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            tabActual = String(btn.dataset.tab || 'equipo_principal');
            tabs.forEach(function(t) { t.classList.remove('is-active'); });
            btn.classList.add('is-active');
            aplicarFiltros();
        });
    });

    if (filtroGenero) {
        filtroGenero.addEventListener('change', aplicarFiltros);
    }

    if (buscador) {
        buscador.addEventListener('input', aplicarFiltros);
    }

    kpiGenero.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const genero = String(btn.dataset.genero || 'todos');
            filtroGenero.value = genero;
            tabActual = 'equipo_principal';
            tabs.forEach(function(t) {
                t.classList.toggle('is-active', String(t.dataset.tab || '') === 'equipo_principal');
            });
            aplicarFiltros();
        });
    });

    if (ministerioSelect) {
        ministerioSelect.addEventListener('change', function() {
            const id = String(ministerioSelect.value || '0');
            let url = '<?= PUBLIC_URL ?>?url=discipular/ministerios/equipo-principal';
            if (id !== '0') {
                url += '&id_ministerio=' + encodeURIComponent(id);
            }
            window.location.href = url;
        });
    }

    if (coberturaPrincipalSelect) {
        coberturaPrincipalSelect.addEventListener('change', function() {
            let url = '<?= PUBLIC_URL ?>?url=discipular/ministerios/equipo-principal&id_ministerio=<?= (int)$idMinisterioFiltro ?>';
            const cobertura = String(coberturaPrincipalSelect.value || '');
            if (cobertura !== '') {
                url += '&cobertura_principal=' + encodeURIComponent(cobertura);
            }
            if (filtroGenero) {
                url += '&genero=' + encodeURIComponent(String(filtroGenero.value || 'todos'));
            }
            if (buscador && String(buscador.value || '').trim() !== '') {
                url += '&buscar=' + encodeURIComponent(String(buscador.value || '').trim());
            }
            window.location.href = url;
        });
    }

    function filtrarPersonasAsignables() {
        if (!selectPersonaAsignar) {
            return;
        }

        const texto = buscarCupoUniversal ? String((buscarCupoUniversal.value || '').toLowerCase().trim()) : '';
        const idCobertura = liderAsignar ? String(liderAsignar.value || '0') : '0';
        const modoCupo = modoCupoAsignar ? String(modoCupoAsignar.value || 'pastoral') : 'pastoral';
        const opciones = Array.from(selectPersonaAsignar.options || []);
        const jerLiderEfectiva = jerarquiaLiderActiva || (usaEtiquetasPastorales ? 'pastor' : 'lider_12');

        opciones.forEach(function(op, idx) {
            if (idx === 0) {
                op.hidden = false;
                return;
            }

            const idLiderActual = String(op.dataset.idLiderActual || '0');
            const search = String(op.dataset.search || '').toLowerCase();
            const jerPersona = String(op.dataset.jerarquia || 'miembro');
            let okPerfil = true;
            if (modoCupo === 'pastoral') {
                okPerfil = jerarquiaPermiteAsignacion(jerLiderEfectiva, jerPersona)
                    || (idCobertura !== '0' && idLiderActual === idCobertura);
            }
            const okTexto = texto === '' || search.indexOf(texto) !== -1;
            op.hidden = !(okPerfil && okTexto);
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
        sincronizarAyudaBusquedaCupo();
    }

    function activarModoCupoPastoral() {
        if (modoCupoAsignar) {
            modoCupoAsignar.value = 'pastoral';
        }
        if (labelCoberturaCupo) {
            labelCoberturaCupo.textContent = usaEtiquetasPastorales ? 'Pastor/Pastora seleccionado(a)' : 'Lider principal seleccionado(a)';
        }
        if (helpModoCupo) {
            helpModoCupo.textContent = 'Elige una persona en los resultados y pulsa el botón azul de abajo. Si el equipo ya tiene 12 personas, solo puedes sustituir una casilla ocupada.';
        }
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
                    estadoCupoLider.textContent = 'Equipo completo (' + equipoDirecto + '/' + limite + '). Puedes reemplazar un cupo ocupado.';
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
            prepararAsignacionDesdeBoton(btnSynth);
        });
    }

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

    function prepararAsignacionDesdeBoton(btn) {
        activarModoCupoPastoral();
        const jerRaw = String(btn.dataset.jerarquiaLider || '').trim();
        jerarquiaLiderActiva = jerRaw !== '' ? jerRaw : (usaEtiquetasPastorales ? 'pastor' : 'lider_12');
        let idLider = String(btn.dataset.idLider || '').trim();
        const idMinisterio = String(btn.dataset.idMinisterio || '0').trim();
        let nombreLider = String(btn.dataset.nombreLider || 'Líder seleccionado').trim();
        const nombreMinisterio = String(btn.dataset.nombreMinisterio || '').trim();
        const nombreRol = String(btn.dataset.nombreRol || '').trim();
        const slotNumeroSeleccionado = parseInt(String(btn.dataset.slotNumero || '0'), 10);

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
            const idPersonaObjetivo = String(btn.dataset.idPersonaObjetivo || '').trim();
            selectPersonaAsignar.value = '';
            if (idPersonaObjetivo !== '' && !(slotNumeroSeleccionado > 0)) {
                const existeOpcion = Array.from(selectPersonaAsignar.options).some(function(opt) {
                    return String(opt.value || '') === idPersonaObjetivo;
                });
                if (existeOpcion) {
                    selectPersonaAsignar.value = idPersonaObjetivo;
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
    filtrarPersonasAsignables();

    aplicarFiltros();
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>