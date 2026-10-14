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
$puedeGestionarCupos = (!empty($mostrarBotonesCupoPastoral) || !empty($esVistaPropiaLider12) || !empty($esVistaPropiaLider144))
    && !empty($puedeEditarPersonaDiscipular);
$encerrarEquipoPropio = !empty($esVistaPropiaLider144) || !empty($esVistaPropiaLider12);

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

<?php
// Un solo "Volver": va al nivel inmediato anterior.
$urlVolverPaso = $buildJerarquiaUrl(0, $generoRedActual !== '' ? $generoRedActual : '');
if ($nivelJerarquia === 'red') {
    $urlVolverPaso = $buildJerarquiaUrl(0, '');
} elseif ($nivelJerarquia === 'equipo') {
    foreach (array_reverse($cadenaBreadcrumbJerarquia) as $crumbPadre) {
        if (empty($crumbPadre['activo']) && !empty($crumbPadre['url'])) {
            $urlVolverPaso = (string)$crumbPadre['url'];
            break;
        }
    }
}
?>
<section class="jer-red-panel jer-red-panel--limpio" aria-label="Navegación por redes">
    <?php if ($nivelJerarquia === 'inicio'): ?>
    <div class="jer-paso jer-paso-inicio">
        <p class="jer-paso-hint">Elige una red</p>
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
        <a href="<?= htmlspecialchars($urlVolverPaso) ?>" class="jer-back-link"><i class="bi bi-arrow-left"></i> Volver</a>
        <h3 class="jer-paso-titulo"><?= htmlspecialchars($redActiva['titulo']) ?></h3>
        <?php if ($idPastor > 0): ?>
        <a href="<?= htmlspecialchars($urlPastor) ?>" class="jer-pastor-card <?= htmlspecialchars($redActiva['clase']) ?>">
            <span class="jer-pastor-etiqueta"><?= htmlspecialchars($redActiva['subtitulo']) ?></span>
            <strong class="jer-pastor-nombre"><?= htmlspecialchars($redActiva['nombre'] !== '' ? $redActiva['nombre'] : 'Sin nombre') ?></strong>
            <span class="jer-pastor-meta">Equipo de 12: <strong><?= $equipoPastor ?>/12</strong></span>
            <span class="jer-pastor-cta">Ver equipo <i class="bi bi-chevron-right"></i></span>
        </a>
        <?php else: ?>
        <div class="jer-empty-state">
            <p>Sin <?= htmlspecialchars(strtolower($redActiva['subtitulo'])) ?> configurado.</p>
            <?php if (!empty($puedeConfigurarLideresPrincipales)): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnEditarLiderazgo')&&document.getElementById('btnEditarLiderazgo').click()">Configurar ahora</button>
            <?php endif; ?>
        </div>
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
                    <h4>Líderes de 12</h4>
                </div>
                <?php if (empty($filasReporteL12)): ?>
                <p class="jer-reporte-empty">Aún no hay líderes de 12 en esta red.</p>
                <?php else: ?>
                <div class="table-container jer-reporte-table-wrap">
                    <table class="data-table jer-reporte-table">
                        <thead>
                            <tr>
                                <th style="width:64px;">Casilla</th>
                                <th>Líder de 12</th>
                                <th style="width:200px;">Su equipo</th>
                                <th style="width:90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($filasReporteL12 as $filaRep): ?>
                                <?php
                                $eqPrin = (int)($filaRep['equipo_principal'] ?? 0);
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
                                        <span class="jer-reporte-equipo-num"><strong><?= $eqPrin ?></strong> de 12</span>
                                        <div class="jer-reporte-bar" aria-hidden="true">
                                            <span class="jer-reporte-bar-fill" style="width:<?= min(100, (int)round(($eqPrin / 12) * 100)) ?>%;"></span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= htmlspecialchars((string)($filaRep['url'] ?? '#')) ?>" class="btn btn-sm btn-primary" title="Ver equipo">Ver equipo</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php elseif ($nivelJerarquia === 'equipo' && $nodoActivoId > 0): ?>
    <div class="jer-paso jer-paso-equipo">
        <?php if (empty($encerrarEquipoPropio)): ?>
        <a href="<?= htmlspecialchars($urlVolverPaso) ?>" class="jer-back-link"><i class="bi bi-arrow-left"></i> Volver</a>
        <?php endif; ?>

        <header class="jer-nodo-head">
            <div>
                <p class="jer-nodo-etiqueta">
                    <?= $generoRedActual === 'mujeres' ? 'Red Mujeres' : ($generoRedActual === 'hombres' ? 'Red Hombres' : 'Equipo de 12') ?>
                </p>
                <h4><?= htmlspecialchars($obtenerNombre($nodoActivoId, $resumenLiderPorId)) ?></h4>
            </div>
            <div class="jer-nodo-actions">
                <span class="jer-kpi"><strong><?= $ocupadosNodo ?></strong> de 12</span>
            </div>
        </header>

        <?php
        $slotsPorNumero = [];
        if (is_array($slotsNodoActivo)) {
            foreach ($slotsNodoActivo as $miembroSlot) {
                if (!is_array($miembroSlot) || empty($miembroSlot['id_persona'])) {
                    continue;
                }
                $cupoNumerado = (int)($miembroSlot['numero_cupo'] ?? 0);
                if ($cupoNumerado >= 1 && $cupoNumerado <= 12) {
                    $slotsPorNumero[$cupoNumerado] = $miembroSlot;
                }
            }
        }
        $idMinisterioCasillas = (int)($idMinisterioFiltro ?? 0);
        $modoCupoCasillas = !empty($hayFiltroMinisterio) ? 'lider_144' : 'pastoral';
        ?>
        <section
            class="asig-equipo"
            data-id-lider="<?= (int)$nodoActivoId ?>"
            data-id-ministerio="<?= $idMinisterioCasillas ?>"
            data-modo-cupo="<?= htmlspecialchars($modoCupoCasillas, ENT_QUOTES, 'UTF-8') ?>"
            data-jerarquia-lider="<?= htmlspecialchars((string)($jerarquiaPorLiderId[$nodoActivoId] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            data-genero-red="<?= htmlspecialchars($generoRedActual, ENT_QUOTES, 'UTF-8') ?>"
            data-tab="<?= htmlspecialchars((string)($tabActivo ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            data-url-personas="<?= htmlspecialchars((string)($personasAsignablesUrl ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            data-url-asignar="<?= htmlspecialchars(PUBLIC_URL . '?url=discipular/ministerios/asignar-cupo', ENT_QUOTES, 'UTF-8') ?>"
            data-url-liberar="<?= htmlspecialchars(PUBLIC_URL . '?url=discipular/ministerios/liberar-cupo', ENT_QUOTES, 'UTF-8') ?>"
            data-puede-gestionar="<?= $puedeGestionarCupos ? '1' : '0' ?>"
        >
        <ul class="jer-miembros-lista">
            <?php for ($slotNum = 1; $slotNum <= 12; $slotNum++): ?>
                <?php
                $miembro = $slotsPorNumero[$slotNum] ?? null;
                $idMiembro = $miembro ? (int)($miembro['id_persona'] ?? 0) : 0;
                ?>
                <?php if ($idMiembro <= 0): ?>
                <li class="jer-slot-card is-vacia" data-slot="<?= $slotNum ?>" data-id-persona="0" data-nombre="">
                    <div class="jer-slot-top">
                        <span class="jer-miembro-cupo"><?= $slotNum ?></span>
                        <div class="jer-slot-ident">
                            <strong class="jer-slot-nombre jer-slot-libre">Casilla libre</strong>
                        </div>
                    </div>
                    <?php if ($puedeGestionarCupos): ?>
                    <div class="jer-slot-acciones">
                        <button type="button" class="btn btn-sm btn-primary js-casilla-abrir">Asignar persona</button>
                    </div>
                    <div class="asig-panel-slot"></div>
                    <?php endif; ?>
                </li>
                <?php else: ?>
                <?php
                $nombreMiembro = trim((string)($miembro['nombre'] ?? ''));
                if ($nombreMiembro === '') {
                    $nombreMiembro = 'Persona #' . $idMiembro;
                }
                $equipoMiembro = $contarEquipo($equipoDirectoPorLider, $idMiembro);
                $redMiembro = (int)($resumenLiderPorId[$idMiembro]['red_total'] ?? 0);
                $puedeDrill = empty($encerrarEquipoPropio)
                    && ($equipoMiembro > 0 || $redMiembro > 0 || isset($equipoDirectoPorLider[$idMiembro]));
                $urlMiembro = $puedeDrill ? $buildJerarquiaUrl($idMiembro, $generoRedActual) : '';
                $rolMiembro = trim((string)($miembro['nombre_rol'] ?? ''));
                ?>
                <li class="jer-slot-card" data-slot="<?= $slotNum ?>" data-id-persona="<?= $idMiembro ?>" data-nombre="<?= htmlspecialchars($nombreMiembro, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="jer-slot-top">
                        <span class="jer-miembro-cupo"><?= $slotNum ?></span>
                        <div class="jer-slot-ident">
                            <strong class="jer-slot-nombre"><?= htmlspecialchars($nombreMiembro) ?></strong>
                            <?php if ($rolMiembro !== ''): ?>
                            <small><?= htmlspecialchars($rolMiembro) ?></small>
                            <?php endif; ?>
                            <?php if ($puedeDrill || $equipoMiembro > 0): ?>
                            <span class="jer-miembro-meta">Su equipo: <?= $equipoMiembro ?> de 12</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($puedeDrill && $urlMiembro !== ''): ?>
                    <div class="jer-slot-acciones">
                        <a href="<?= htmlspecialchars($urlMiembro) ?>" class="btn btn-sm btn-primary">Ver equipo</a>
                        <?php if ($puedeGestionarCupos): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary js-casilla-abrir">Cambiar</button>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($puedeGestionarCupos): ?>
                    <div class="jer-slot-acciones">
                        <button type="button" class="btn btn-sm btn-outline-primary js-casilla-abrir">Cambiar</button>
                    </div>
                    <?php endif; ?>
                    <div class="jer-miembro-escalera tabla-discipulos-compacta">
                        <?php
                        $escaleraPersonaId = $idMiembro;
                        $escaleraChecklistRaw = (string)($miembro['escalera_checklist'] ?? '');
                        $escaleraProceso = (string)($miembro['proceso'] ?? '');
                        $escaleraIdCelula = (int)($miembro['id_celula'] ?? 0);
                        $escaleraIdMinisterio = (int)($miembro['id_ministerio'] ?? 0);
                        $escaleraPuedeEditar = !empty($puedeEditarPersonaDiscipular);
                        include VIEWS . '/discipular/partials/escalera_rapida_casilla.php';
                        ?>
                    </div>
                    <?php if ($puedeGestionarCupos): ?>
                    <div class="asig-panel-slot"></div>
                    <?php endif; ?>
                </li>
                <?php endif; ?>
            <?php endfor; ?>
        </ul>
        </section>
    </div>
    <?php endif; ?>
</section>
