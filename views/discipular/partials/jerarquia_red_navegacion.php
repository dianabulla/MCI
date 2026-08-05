<?php
$nivelJerarquia = (string)($nivelJerarquia ?? 'inicio');
$buildJerarquiaUrl = is_callable($buildJerarquiaUrl ?? null) ? $buildJerarquiaUrl : static function () {
    return '#';
};
$resumenLiderPorId = is_array($resumenLiderPorId ?? null) ? $resumenLiderPorId : [];
$equipoDirectoPorLider = is_array($equipoDirectoPorLider ?? null) ? $equipoDirectoPorLider : [];
$cadenaBreadcrumbJerarquia = is_array($cadenaBreadcrumbJerarquia ?? null) ? $cadenaBreadcrumbJerarquia : [];
$generoRedActual = (string)($generoRedActual ?? '');
$nodoActivoId = (int)($coberturaPrincipalActual ?? 0);

$contarEquipo = is_callable($contarEquipoPrincipalFn ?? null)
    ? $contarEquipoPrincipalFn
    : (is_callable($contarEquipoOcupadoFn ?? null)
        ? $contarEquipoOcupadoFn
        : static function ($mapa, $idLider) {
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
        });

$obtenerNombre = is_callable($obtenerNombreLiderFn ?? null)
    ? $obtenerNombreLiderFn
    : static function ($id, $mapa) {
        $id = (int)$id;
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

$redesRaiz = [
    'hombres' => [
        'titulo' => 'Red Hombres',
        'subtitulo' => !empty($usarEtiquetasPastorales) ? 'Pastor principal' : 'Líder principal',
        'id_lider' => (int)($idLiderPrincipal1 ?? 0),
        'nombre' => trim((string)($nombreLiderPrincipal1 ?? '')),
        'icono' => 'bi-person-badge',
        'clase' => 'jer-red-hombres',
    ],
    'mujeres' => [
        'titulo' => 'Red Mujeres',
        'subtitulo' => !empty($usarEtiquetasPastorales) ? 'Pastora principal' : 'Líder principal',
        'id_lider' => (int)($idLiderPrincipal2 ?? 0),
        'nombre' => trim((string)($nombreLiderPrincipal2 ?? '')),
        'icono' => 'bi-person-heart',
        'clase' => 'jer-red-mujeres',
    ],
];

$redActiva = $generoRedActual !== '' && isset($redesRaiz[$generoRedActual])
    ? $redesRaiz[$generoRedActual]
    : null;

$slotsNodoActivo = ($nodoActivoId > 0 && isset($equipoDirectoPorLider[$nodoActivoId]))
    ? $equipoDirectoPorLider[$nodoActivoId]
    : [];
$resumenNodoActivo = $resumenLiderPorId[$nodoActivoId] ?? null;
$ocupadosNodo = $contarEquipo($equipoDirectoPorLider, $nodoActivoId);
$redTotalNodo = is_array($resumenNodoActivo) ? (int)($resumenNodoActivo['red_total'] ?? 0) : 0;
$puedeGestionarCupos = !empty($mostrarBotonesCupoPastoral) || !empty($esVistaPropiaLider12);

$construirReporteLideres12BajoPastor = static function (int $idPastorRed) use (
    $equipoDirectoPorLider,
    $contarEquipo,
    $resumenLiderPorId,
    $buildJerarquiaUrl,
    $generoRedActual,
    $liderazgoRed
): array {
    if ($idPastorRed <= 0) {
        return [];
    }

    $filas = [];
    $idsVistos = [];
    $slotsPastor = is_array($equipoDirectoPorLider[$idPastorRed] ?? null)
        ? $equipoDirectoPorLider[$idPastorRed]
        : [];

    foreach ($slotsPastor as $idxSlot => $slot) {
        if (!is_array($slot) || empty($slot['id_persona'])) {
            continue;
        }
        $idLider12 = (int)$slot['id_persona'];
        if ($idLider12 <= 0 || isset($idsVistos[$idLider12])) {
            continue;
        }
        $idsVistos[$idLider12] = true;
        $equipoPrincipal = $contarEquipo($equipoDirectoPorLider, $idLider12);
        $filas[] = [
            'id' => $idLider12,
            'cupo' => (int)($slot['numero_cupo'] ?? ($slot['slot_numero'] ?? ($idxSlot + 1))),
            'nombre' => trim((string)($slot['nombre'] ?? '')),
            'rol' => trim((string)($slot['nombre_rol'] ?? '')),
            'equipo_principal' => $equipoPrincipal,
            'red_total' => (int)($resumenLiderPorId[$idLider12]['red_total'] ?? 0),
            'url' => $buildJerarquiaUrl($idLider12, $generoRedActual),
        ];
    }

    if (empty($filas) && is_array($liderazgoRed)) {
        foreach ($liderazgoRed as $lr) {
            $idLider12 = (int)($lr['Id_Persona'] ?? 0);
            if ($idLider12 <= 0 || isset($idsVistos[$idLider12])) {
                continue;
            }
            if ((int)($lr['Id_Lider'] ?? 0) !== $idPastorRed) {
                continue;
            }
            if ((int)($lr['Es_Lider_12'] ?? 0) !== 1) {
                continue;
            }
            $idsVistos[$idLider12] = true;
            $nombreCompleto = trim((string)($lr['Nombre'] ?? '') . ' ' . (string)($lr['Apellido'] ?? ''));
            $equipoPrincipal = $contarEquipo($equipoDirectoPorLider, $idLider12);
            $filas[] = [
                'id' => $idLider12,
                'cupo' => (int)($lr['Numero_Cupo'] ?? 0),
                'nombre' => $nombreCompleto,
                'rol' => trim((string)($lr['Nombre_Rol'] ?? '')),
                'equipo_principal' => $equipoPrincipal,
                'red_total' => (int)($lr['Red_Total'] ?? 0),
                'url' => $buildJerarquiaUrl($idLider12, $generoRedActual),
            ];
        }
    }

    usort($filas, static function ($a, $b) {
        $cupoA = (int)($a['cupo'] ?? 0);
        $cupoB = (int)($b['cupo'] ?? 0);
        if ($cupoA > 0 && $cupoB > 0 && $cupoA !== $cupoB) {
            return $cupoA <=> $cupoB;
        }
        if ($cupoA > 0 && $cupoB <= 0) {
            return -1;
        }
        if ($cupoB > 0 && $cupoA <= 0) {
            return 1;
        }
        return strcasecmp((string)($a['nombre'] ?? ''), (string)($b['nombre'] ?? ''));
    });

    return $filas;
};

$clasificarEstadoEquipoPrincipal = static function (int $ocupados): array {
    $ocupados = min(12, max(0, $ocupados));
    if ($ocupados >= 12) {
        return ['label' => 'Completo', 'clase' => 'jer-estado-completo'];
    }
    if ($ocupados >= 6) {
        return ['label' => 'En avance', 'clase' => 'jer-estado-avance'];
    }
    if ($ocupados >= 1) {
        return ['label' => 'Iniciado', 'clase' => 'jer-estado-iniciado'];
    }
    return ['label' => 'Sin equipo', 'clase' => 'jer-estado-vacio'];
};
?>

<section class="jer-red-panel jer-red-panel--limpio" aria-label="Navegación por redes">
    <?php if (!empty($cadenaBreadcrumbJerarquia)): ?>
    <nav class="jer-breadcrumb" aria-label="Ruta jerárquica">
        <a href="<?= htmlspecialchars($buildJerarquiaUrl(0, '')) ?>" class="jer-breadcrumb-inicio"><i class="bi bi-house"></i> Inicio</a>
        <?php foreach ($cadenaBreadcrumbJerarquia as $crumb): ?>
            <span class="jer-breadcrumb-sep" aria-hidden="true">›</span>
            <?php if (!empty($crumb['activo'])): ?>
                <span class="jer-breadcrumb-activo"><?= htmlspecialchars((string)($crumb['nombre'] ?? '')) ?></span>
            <?php else: ?>
                <a href="<?= htmlspecialchars((string)($crumb['url'] ?? '#')) ?>"><?= htmlspecialchars((string)($crumb['nombre'] ?? '')) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>

    <?php if ($nivelJerarquia === 'inicio'): ?>
    <div class="jer-paso jer-paso-inicio">
        <p class="jer-paso-hint">Selecciona una red para continuar</p>
        <div class="jer-red-grid jer-red-grid--inicio">
            <?php foreach ($redesRaiz as $generoKey => $red): ?>
                <a href="<?= htmlspecialchars($buildJerarquiaUrl(0, $generoKey)) ?>" class="jer-red-choice <?= htmlspecialchars($red['clase']) ?>">
                    <i class="bi <?= htmlspecialchars($red['icono']) ?>" aria-hidden="true"></i>
                    <span class="jer-red-choice-title"><?= htmlspecialchars($red['titulo']) ?></span>
                    <span class="jer-red-choice-sub"><?= htmlspecialchars($red['subtitulo']) ?></span>
                    <span class="jer-red-choice-cta">Entrar <i class="bi bi-arrow-right"></i></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php elseif ($nivelJerarquia === 'red' && is_array($redActiva)): ?>
    <?php
    $idPastor = (int)($redActiva['id_lider'] ?? 0);
    $equipoPastor = $idPastor > 0 ? $contarEquipo($equipoDirectoPorLider, $idPastor) : 0;
    $urlPastor = $idPastor > 0 ? $buildJerarquiaUrl($idPastor, $generoRedActual) : '#';
    ?>
    <div class="jer-paso jer-paso-red">
        <a href="<?= htmlspecialchars($buildJerarquiaUrl(0, '')) ?>" class="jer-back-link"><i class="bi bi-arrow-left"></i> Volver a redes</a>
        <p class="jer-paso-hint"><?= htmlspecialchars($redActiva['titulo']) ?></p>
        <?php if ($idPastor > 0): ?>
        <a href="<?= htmlspecialchars($urlPastor) ?>" class="jer-pastor-card <?= htmlspecialchars($redActiva['clase']) ?>">
            <span class="jer-pastor-etiqueta"><?= htmlspecialchars($redActiva['subtitulo']) ?></span>
            <strong class="jer-pastor-nombre"><?= htmlspecialchars($redActiva['nombre'] !== '' ? $redActiva['nombre'] : 'Sin nombre') ?></strong>
            <span class="jer-pastor-meta">Equipo principal: <strong><?= $equipoPastor ?>/12</strong><?php if ($equipoPastor < 12): ?> · <em><?= (12 - $equipoPastor) ?> casilla(s) libre(s)</em><?php endif; ?></span>
            <span class="jer-pastor-cta">Ver equipo del 12 <i class="bi bi-chevron-right"></i></span>
        </a>
        <?php else: ?>
        <div class="jer-empty-state">
            <p>Sin <?= htmlspecialchars(strtolower($redActiva['subtitulo'])) ?> configurado.</p>
            <?php if (!empty($puedeConfigurarLideresPrincipales)): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnEditarLiderazgo')&&document.getElementById('btnEditarLiderazgo').click()">Configurar ahora</button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($idPastor > 0 && !empty($puedeGestionarCupos)): ?>
        <p class="jer-paso-hint jer-paso-hint--accion">
            <?php if ($equipoPastor < 12): ?>
            Hay <?= (12 - $equipoPastor) ?> casilla(s) libre(s) bajo <?= htmlspecialchars($redActiva['subtitulo']) ?>.
            <?php else: ?>
            Equipo principal completo (12/12).
            <?php endif; ?>
        </p>
        <button
            type="button"
            class="btn btn-sm btn-primary js-abrir-cupos-jerarquia jer-btn-gestionar-red"
            data-id-lider="<?= $idPastor ?>"
            data-id-ministerio="<?= (int)($idMinisterioFiltro ?? 0) ?>"
            data-nombre-lider="<?= htmlspecialchars($redActiva['nombre'] !== '' ? $redActiva['nombre'] : 'Pastor/a', ENT_QUOTES, 'UTF-8') ?>"
            data-modo-cupo="<?= !empty($hayFiltroMinisterio) ? 'lider_144' : 'pastoral' ?>"
        ><i class="bi bi-grid-3x3-gap"></i> Gestionar cupos del equipo (<?= $equipoPastor ?>/12)</button>
        <?php endif; ?>

        <?php if ($idPastor > 0): ?>
            <?php
            $filasReporteL12 = $construirReporteLideres12BajoPastor($idPastor);
            $totalPersonasEquipos = 0;
            foreach ($filasReporteL12 as $fRep) {
                $totalPersonasEquipos += (int)($fRep['equipo_principal'] ?? 0);
            }
            ?>
            <div class="jer-reporte-wrap">
                <div class="jer-reporte-head">
                    <h4>Reporte · Equipo principal de líderes de 12</h4>
                    <p>Personas asignadas bajo <strong><?= htmlspecialchars($redActiva['nombre'] !== '' ? $redActiva['nombre'] : 'pastor') ?></strong> en <?= htmlspecialchars($redActiva['titulo']) ?>.</p>
                </div>
                <?php if (empty($filasReporteL12)): ?>
                <p class="jer-reporte-empty">Aún no hay líderes de 12 asignados en esta red.</p>
                <?php else: ?>
                <div class="table-container jer-reporte-table-wrap">
                    <table class="data-table jer-reporte-table">
                        <thead>
                            <tr>
                                <th style="width:52px;">Cupo</th>
                                <th>Líder de 12</th>
                                <th style="width:140px;">Equipo principal</th>
                                <th style="width:110px;">Estado</th>
                                <th style="width:72px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filasReporteL12 as $filaRep): ?>
                                <?php
                                $eqPrin = (int)($filaRep['equipo_principal'] ?? 0);
                                $estadoRep = $clasificarEstadoEquipoPrincipal($eqPrin);
                                $nombreRep = trim((string)($filaRep['nombre'] ?? ''));
                                ?>
                                <tr>
                                    <td class="text-center"><?= (int)($filaRep['cupo'] ?? 0) > 0 ? (int)$filaRep['cupo'] : '—' ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($nombreRep !== '' ? $nombreRep : ('Líder #' . (int)$filaRep['id'])) ?></strong>
                                        <?php if (trim((string)($filaRep['rol'] ?? '')) !== ''): ?>
                                        <br><small class="jer-reporte-rol"><?= htmlspecialchars((string)$filaRep['rol']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="jer-reporte-equipo-num">Tiene <strong><?= $eqPrin ?></strong> de 12</span>
                                        <div class="jer-reporte-bar" aria-hidden="true">
                                            <span class="jer-reporte-bar-fill" style="width:<?= min(100, (int)round(($eqPrin / 12) * 100)) ?>%;"></span>
                                        </div>
                                    </td>
                                    <td><span class="jer-estado-badge <?= htmlspecialchars($estadoRep['clase']) ?>"><?= htmlspecialchars($estadoRep['label']) ?></span></td>
                                    <td class="text-center">
                                        <a href="<?= htmlspecialchars((string)($filaRep['url'] ?? '#')) ?>" class="btn btn-xs btn-secondary" title="Ver equipo">Ver</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2"><strong><?= count($filasReporteL12) ?></strong> líder(es) de 12</td>
                                <td colspan="3"><strong><?= $totalPersonasEquipos ?></strong> persona(s) en equipos principales (suma)</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php elseif ($nivelJerarquia === 'equipo' && $nodoActivoId > 0): ?>
    <div class="jer-paso jer-paso-equipo">
        <?php
        $urlVolverRed = $generoRedActual !== ''
            ? $buildJerarquiaUrl(0, $generoRedActual)
            : $buildJerarquiaUrl(0, '');
        ?>
        <a href="<?= htmlspecialchars($urlVolverRed) ?>" class="jer-back-link"><i class="bi bi-arrow-left"></i> Volver</a>

        <header class="jer-nodo-head">
            <div>
                <p class="jer-nodo-etiqueta">
                    <?= $generoRedActual === 'mujeres' ? 'Red Mujeres' : ($generoRedActual === 'hombres' ? 'Red Hombres' : 'Equipo') ?>
                </p>
                <h4><?= htmlspecialchars($obtenerNombre($nodoActivoId, $resumenLiderPorId)) ?></h4>
            </div>
            <div class="jer-nodo-actions">
                <span class="jer-kpi"><strong><?= $ocupadosNodo ?></strong>/12</span>
                <?php if ($redTotalNodo > 0): ?>
                <span class="jer-kpi jer-kpi-muted"><?= $redTotalNodo ?> en red</span>
                <?php endif; ?>
                <?php if ($puedeGestionarCupos): ?>
                <button
                    type="button"
                    class="btn btn-sm btn-primary js-abrir-cupos-jerarquia"
                    data-id-lider="<?= $nodoActivoId ?>"
                    data-id-ministerio="<?= (int)($idMinisterioFiltro ?? 0) ?>"
                    data-nombre-lider="<?= htmlspecialchars($obtenerNombre($nodoActivoId, $resumenLiderPorId), ENT_QUOTES, 'UTF-8') ?>"
                    data-modo-cupo="<?= !empty($hayFiltroMinisterio) ? 'lider_144' : 'pastoral' ?>"
                >Gestionar cupos</button>
                <?php endif; ?>
            </div>
        </header>

        <?php
        $miembrosVisibles = [];
        if (is_array($slotsNodoActivo)) {
            foreach ($slotsNodoActivo as $idxSlot => $miembroSlot) {
                if (!is_array($miembroSlot) || empty($miembroSlot['id_persona'])) {
                    continue;
                }
                $cupoNumerado = (int)($miembroSlot['numero_cupo'] ?? 0);
                if ($cupoNumerado < 1 || $cupoNumerado > 12) {
                    continue;
                }
                $miembrosVisibles[] = [
                    'slot' => $cupoNumerado,
                    'miembro' => $miembroSlot,
                ];
            }
        }
        usort($miembrosVisibles, static function ($a, $b) {
            return (int)($a['slot'] ?? 0) <=> (int)($b['slot'] ?? 0);
        });
        ?>

        <?php if (empty($miembrosVisibles)): ?>
        <div class="jer-empty-state">
            <p>Este líder aún no tiene personas con casilla numerada (1–12) en su equipo principal.</p>
            <p class="jer-empty-sub">Si hay personas en cobertura sin casilla, asígnalas con el botón de abajo.</p>
            <?php if ($puedeGestionarCupos): ?>
            <button type="button" class="btn btn-sm btn-primary js-abrir-cupos-jerarquia" data-id-lider="<?= $nodoActivoId ?>" data-id-ministerio="<?= (int)($idMinisterioFiltro ?? 0) ?>" data-nombre-lider="<?= htmlspecialchars($obtenerNombre($nodoActivoId, $resumenLiderPorId), ENT_QUOTES, 'UTF-8') ?>" data-modo-cupo="<?= !empty($hayFiltroMinisterio) ? 'lider_144' : 'pastoral' ?>">Asignar cupos</button>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <ul class="jer-miembros-lista">
            <?php foreach ($miembrosVisibles as $itemMiembro): ?>
                <?php
                $miembro = $itemMiembro['miembro'];
                $slotNum = (int)($itemMiembro['slot'] ?? 0);
                $idMiembro = (int)($miembro['id_persona'] ?? 0);
                $nombreMiembro = trim((string)($miembro['nombre'] ?? ''));
                $equipoMiembro = $contarEquipo($equipoDirectoPorLider, $idMiembro);
                $redMiembro = (int)($resumenLiderPorId[$idMiembro]['red_total'] ?? 0);
                $puedeDrill = $equipoMiembro > 0 || $redMiembro > 0 || isset($equipoDirectoPorLider[$idMiembro]);
                $urlMiembro = $puedeDrill ? $buildJerarquiaUrl($idMiembro, $generoRedActual) : '';
                ?>
                <li class="jer-miembro-item<?= $puedeDrill ? ' is-clickable' : '' ?>">
                    <?php if ($puedeDrill && $urlMiembro !== ''): ?>
                    <a href="<?= htmlspecialchars($urlMiembro) ?>" class="jer-miembro-link">
                        <span class="jer-miembro-cupo"><?= $slotNum > 0 ? $slotNum : '·' ?></span>
                        <span class="jer-miembro-body">
                            <strong><?= htmlspecialchars($nombreMiembro !== '' ? $nombreMiembro : ('Persona #' . $idMiembro)) ?></strong>
                            <?php if (trim((string)($miembro['nombre_rol'] ?? '')) !== ''): ?>
                            <small><?= htmlspecialchars((string)$miembro['nombre_rol']) ?></small>
                            <?php endif; ?>
                            <span class="jer-miembro-meta">Equipo: <?= $equipoMiembro ?>/12<?= $redMiembro > 0 ? ' · Red: ' . $redMiembro : '' ?></span>
                        </span>
                        <i class="bi bi-chevron-right jer-miembro-arrow" aria-hidden="true"></i>
                    </a>
                    <?php else: ?>
                    <div class="jer-miembro-static">
                        <span class="jer-miembro-cupo"><?= $slotNum > 0 ? $slotNum : '·' ?></span>
                        <span class="jer-miembro-body">
                            <strong><?= htmlspecialchars($nombreMiembro !== '' ? $nombreMiembro : ('Persona #' . $idMiembro)) ?></strong>
                            <small>Sin equipo debajo</small>
                        </span>
                    </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($ocupadosNodo < 12): ?>
        <p class="jer-cupos-libres"><?= (12 - $ocupadosNodo) ?> cupo(s) libre(s)</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>
