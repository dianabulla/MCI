<?php include VIEWS . '/layout/header.php'; ?>
<?php
$anio = (int)($anio ?? date('Y'));
$escala = (string)($escala ?? 'semestral');
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroLider = (string)($filtro_lider ?? '');
$ministeriosDisp = $ministerios_disponibles ?? [];
$lideresDisp = $lideres_disponibles ?? [];
$reporteRedes = is_array($reporte_redes ?? null) ? $reporte_redes : [];
$redes = is_array($reporteRedes['redes'] ?? null) ? $reporteRedes['redes'] : [];
$jerarquia = is_array($reporteRedes['jerarquia'] ?? null) ? $reporteRedes['jerarquia'] : [];

require_once APP . '/Helpers/DashboardSelector.php';
$dashModuloActivo = 'ganar';

$clavesMetrica = ['gano_iglesia', 'gano_celula', 'encuentro', 'capacitacion_destino', 'realizan_celula', 'total'];
$etiquetasMetrica = [
    'gano_iglesia' => 'Ganó iglesia',
    'gano_celula' => 'Ganó célula',
    'encuentro' => 'Encuentro / UV',
    'capacitacion_destino' => 'Cap. Destino',
    'realizan_celula' => 'Realizan célula',
    'total' => 'Total',
];

$escalas = [
    'mensual' => 'Mensual',
    'semestral' => 'Semestral',
    'anual' => 'Anual',
];

$qsBase = [
    'url' => 'reportes/dashboard-ganar-redes',
    'anio' => $anio,
    'escala' => $escala,
];
if ($filtroMinisterio !== '') {
    $qsBase['ministerio'] = $filtroMinisterio;
}
if ($filtroLider !== '') {
    $qsBase['lider'] = $filtroLider;
}

$buildUrl = static function (array $extra) use ($qsBase): string {
    return PUBLIC_URL . 'index.php?' . http_build_query(array_merge($qsBase, $extra));
};

$urlDashGanar = PUBLIC_URL . 'index.php?url=reportes/dashboard-ganar&anio=' . $anio;
if ($filtroMinisterio !== '') {
    $urlDashGanar .= '&ministerio=' . urlencode($filtroMinisterio);
}
if ($filtroLider !== '') {
    $urlDashGanar .= '&lider=' . urlencode($filtroLider);
}

$metricasDe = static function (array $nodo, string $escalaActiva) use ($clavesMetrica): array {
    $vacias = array_fill_keys($clavesMetrica, 0);
    if ($escalaActiva === 'semestral') {
        $rows = is_array($nodo['semestral']['rows'] ?? null) ? $nodo['semestral']['rows'] : [];
        $s1 = is_array($rows[0] ?? null) ? $rows[0] : $vacias;
        $s2 = is_array($rows[1] ?? null) ? $rows[1] : $vacias;
        $anioM = is_array($nodo['anual']['totales'] ?? null) ? $nodo['anual']['totales'] : $vacias;

        return ['s1' => array_merge($vacias, $s1), 's2' => array_merge($vacias, $s2), 'anio' => array_merge($vacias, $anioM)];
    }
    $totales = is_array($nodo[$escalaActiva]['totales'] ?? null) ? $nodo[$escalaActiva]['totales'] : $vacias;
    if ($escalaActiva === 'mensual') {
        $totales = is_array($nodo['anual']['totales'] ?? null) ? $nodo['anual']['totales'] : $totales;
    }

    return ['anio' => array_merge($vacias, $totales)];
};

$colIdentidad = 3;
$colCount = $escala === 'semestral'
    ? ($colIdentidad + (count($clavesMetrica) * 2))
    : ($colIdentidad + count($clavesMetrica));

$contarEquipo = static function (array $nodo): int {
    $n = 0;
    foreach ((array)($nodo['hijos'] ?? []) as $hijo) {
        $hijo = (array)$hijo;
        if ((string)($hijo['tipo'] ?? '') === 'directos' && (int)($hijo['anual']['totales']['total'] ?? 0) <= 0) {
            continue;
        }
        $n++;
        $n += count((array)($hijo['hijos'] ?? []));
    }

    return $n;
};

$contarCelulas = static function (array $nodo) use (&$contarCelulas): int {
    $n = ((string)($nodo['tipo'] ?? '') === 'lider_celula') ? 1 : 0;
    foreach ((array)($nodo['hijos'] ?? []) as $hijo) {
        $n += $contarCelulas((array)$hijo);
    }

    return $n;
};

$celdaNum = static function (int $valor, bool $esTotal = false): string {
    $cls = 'num' . ($esTotal ? ' is-total' : '') . ($valor === 0 ? ' is-zero' : '');

    return '<td class="' . $cls . '">' . $valor . '</td>';
};

$celdasMetricas = static function (array $pack, string $escalaActiva, array $clavesMetrica) use ($celdaNum): string {
    $html = '';
    if ($escalaActiva === 'semestral') {
        foreach (['s1', 's2'] as $bloque) {
            foreach ($clavesMetrica as $clave) {
                $html .= $celdaNum((int)($pack[$bloque][$clave] ?? 0), $clave === 'total');
            }
        }

        return $html;
    }
    foreach ($clavesMetrica as $clave) {
        $html .= $celdaNum((int)($pack['anio'][$clave] ?? 0), $clave === 'total');
    }

    return $html;
};

$hayJerarquia = false;
foreach (['mujeres', 'hombres', 'otros'] as $claveJ) {
    if (!empty($jerarquia[$claveJ]['lideres_12'])) {
        $hayJerarquia = true;
        break;
    }
}
?>

<style>
.redes-page { max-width: 1280px; }
.redes-header { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.redes-header h2 { margin:0; }
.redes-header small { color:#64748b; }
.redes-actions { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.redes-pill {
    display:inline-flex; align-items:center; padding:7px 12px; border-radius:999px;
    border:1px solid #d1d5db; background:#fff; color:#334155; font-size:.84rem; font-weight:600; text-decoration:none;
}
.redes-pill.is-active { background:#1e3a8a; border-color:#1e3a8a; color:#fff; }
.redes-escala { display:flex; gap:6px; flex-wrap:wrap; margin:0 0 12px; }
.redes-help { margin:0 0 14px; font-size:.8rem; color:#64748b; line-height:1.45; }
.dash-filters-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:14px 16px; margin-bottom:16px; }
.dash-filters-form { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
.dash-filters-form .form-group { margin:0; }
.dash-filters-form select { padding:6px 10px; border-radius:8px; border:1px solid #d1d5db; font-size:.88rem; min-width:160px; }
.dash-anio-form { display:flex; gap:6px; align-items:center; }
.dash-anio-form select { padding:4px 10px; border-radius:6px; border:1px solid #d1d5db; }
.redes-table-wrap { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 18px; box-shadow:0 1px 4px rgba(15,23,42,.06); }
.redes-table-wrap .table-container { overflow-x:auto; }
.dash-min-table { width:100%; border-collapse:collapse; font-size:.88rem; min-width:720px; }
.dash-min-table th { background:#f1f5f9; padding:8px 10px; text-align:left; font-size:.74rem; color:#475569; font-weight:700; border-bottom:1px solid #e2e8f0; }
.dash-min-table th.num { text-align:right; white-space:nowrap; }
.dash-min-table td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.dash-min-table .num { text-align:right; font-variant-numeric: tabular-nums; }
.dash-min-table .is-total { font-weight:700; }
.dash-min-table .is-zero { color:#94a3b8; font-weight:400; }
.dash-min-table .col-num { width:36px; color:#94a3b8; text-align:center; font-weight:600; }
.dash-min-table .col-tipo { white-space:nowrap; color:#475569; }
.redes-nombre { font-weight:600; color:#0f172a; }
.redes-nombre-meta { margin:2px 0 0; font-size:.75rem; color:#64748b; font-weight:400; }
.redes-group td {
    background:#eef2ff; color:#3730a3; font-weight:700; border-bottom:none; padding:9px 12px;
}
.redes-group--mujeres td { background:#fce7f3; color:#9d174d; }
.redes-group--hombres td { background:#eef2ff; color:#3730a3; }
.redes-group--otros td { background:#f1f5f9; color:#334155; }
.redes-equipo td:nth-child(2) { padding-left:28px; }
.redes-nieto td:nth-child(2) { padding-left:44px; }
.redes-subtotal td { background:#f8fafc; font-weight:600; border-bottom:1px solid #e2e8f0; }
.redes-subtotal td:first-child { text-align:right; color:#475569; }
.redes-subtotal-red td { background:#f1f5f9; font-weight:700; }
.redes-subtotal-red td:first-child { text-align:right; }
.dash-min-table tfoot td { background:#f8fafc; font-weight:700; border-bottom:none; }
.redes-vacio { padding:28px; text-align:center; color:#94a3b8; }
@media print {
    .redes-actions, .dash-filters-card, .redes-escala { display:none !important; }
}
</style>

<div class="redes-page">
    <div class="redes-header">
        <div>
            <h2>Reporte Ganar por redes · <?= $anio ?></h2>
            <small>Una sola tabla: Red Mujeres / Red Hombres, líder de 12 y su equipo</small>
        </div>
        <div class="redes-actions">
            <?php
            DashboardSelector::incluirPartial([
                'activo' => $dashModuloActivo,
                'params' => [
                    'anio' => $anio,
                    'ministerio' => $filtroMinisterio,
                    'lider' => $filtroLider,
                ],
            ]);
            ?>
            <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-anio-form">
                <input type="hidden" name="url" value="reportes/dashboard-ganar-redes">
                <input type="hidden" name="escala" value="<?= htmlspecialchars($escala) ?>">
                <?php if ($filtroMinisterio !== ''): ?>
                    <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
                <?php endif; ?>
                <?php if ($filtroLider !== ''): ?>
                    <input type="hidden" name="lider" value="<?= htmlspecialchars($filtroLider) ?>">
                <?php endif; ?>
                <label for="anio_redes" style="font-size:.84rem;color:#475569;">Año:</label>
                <select id="anio_redes" name="anio" onchange="this.form.submit()">
                    <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                        <option value="<?= $y ?>" <?= $y === $anio ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <a class="redes-pill" href="<?= htmlspecialchars($urlDashGanar) ?>">Volver al dashboard</a>
            <a class="redes-pill" href="<?= htmlspecialchars($buildUrl(['exportar' => 1])) ?>">Excel</a>
        </div>
    </div>

    <div class="dash-filters-card">
        <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-filters-form">
            <input type="hidden" name="url" value="reportes/dashboard-ganar-redes">
            <input type="hidden" name="anio" value="<?= $anio ?>">
            <input type="hidden" name="escala" value="<?= htmlspecialchars($escala) ?>">
            <div class="form-group">
                <label style="font-size:.8rem;color:#475569;display:block;margin-bottom:4px;">Ministerio</label>
                <select name="ministerio" onchange="this.form.submit()">
                    <option value="">Todos (general)</option>
                    <?php foreach ($ministeriosDisp as $min): ?>
                        <option value="<?= (int)($min['Id_Ministerio'] ?? 0) ?>"
                            <?= (string)($min['Id_Ministerio'] ?? '') === $filtroMinisterio ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($min['Nombre_Ministerio'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label style="font-size:.8rem;color:#475569;display:block;margin-bottom:4px;">Líder</label>
                <select name="lider" onchange="this.form.submit()">
                    <option value="">Todos los líderes</option>
                    <?php foreach ($lideresDisp as $lid): ?>
                        <option value="<?= (int)($lid['Id_Persona'] ?? 0) ?>"
                            <?= (string)($lid['Id_Persona'] ?? '') === $filtroLider ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($lid['Nombre_Completo'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <nav class="redes-escala" aria-label="Escala del reporte">
        <?php foreach ($escalas as $claveEscala => $labelEscala): ?>
            <a class="redes-pill<?= $escala === $claveEscala ? ' is-active' : '' ?>"
               href="<?= htmlspecialchars($buildUrl(['escala' => $claveEscala])) ?>">
                <?= htmlspecialchars($labelEscala) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <p class="redes-help">
        Una sola tabla agrupada por red. Debajo de cada líder de 12 aparece su equipo (líderes de 144 y de célula).
        Personas <strong>nuevas</strong> del año. Los números de un líder de 12 ya incluyen a su equipo.
        <?php if ($escala === 'semestral'): ?>
            En semestral se muestran el 1er semestre (ene–jun) y el 2do (jul–dic).
        <?php elseif ($escala === 'mensual'): ?>
            En mensual los números son el total del año (el detalle mes a mes va en Excel).
        <?php endif; ?>
    </p>

    <div class="redes-table-wrap">
        <?php if (!$hayJerarquia): ?>
            <div class="redes-vacio">No hay líderes de 12 o de célula para mostrar con los filtros actuales.</div>
        <?php else: ?>
        <div class="table-container">
            <table class="dash-min-table">
                <thead>
                    <?php if ($escala === 'semestral'): ?>
                    <tr>
                        <th colspan="<?= (int)$colIdentidad ?>"></th>
                        <th class="num" colspan="<?= count($clavesMetrica) ?>">1er semestre</th>
                        <th class="num" colspan="<?= count($clavesMetrica) ?>">2do semestre</th>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th>#</th>
                        <th>Líder</th>
                        <th>Tipo</th>
                        <?php
                        $gruposCabeza = $escala === 'semestral' ? 2 : 1;
                        for ($g = 0; $g < $gruposCabeza; $g++):
                            foreach ($etiquetasMetrica as $etiquetaCol):
                        ?>
                            <th class="num"><?= htmlspecialchars($etiquetaCol) ?></th>
                        <?php
                            endforeach;
                        endfor;
                        ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $nFila = 1;
                $escalaMetricas = $escala === 'mensual' ? 'anual' : $escala;
                $totalesGenerales = $metricasDe($redes['general'] ?? ['anual' => ['totales' => []]], $escalaMetricas);

                $celdaNombre = static function (array $nodo): string {
                    $nombre = htmlspecialchars((string)($nodo['nombre'] ?? ''));
                    $minis = trim((string)($nodo['ministerio'] ?? ''));
                    $html = '<span class="redes-nombre">' . $nombre . '</span>';
                    if ($minis !== '' && $minis !== 'Sin ministerio') {
                        $html .= '<div class="redes-nombre-meta">' . htmlspecialchars($minis) . '</div>';
                    }

                    return $html;
                };

                foreach (['mujeres' => 'Red Mujeres', 'hombres' => 'Red Hombres', 'otros' => 'Sin red (sin género)'] as $claveRed => $tituloRed):
                    $lideres12Red = is_array($jerarquia[$claveRed]['lideres_12'] ?? null) ? $jerarquia[$claveRed]['lideres_12'] : [];
                    if ($lideres12Red === []) {
                        continue;
                    }
                    $resumenRed = $redes[$claveRed] ?? [];
                    $packRed = $metricasDe($resumenRed, $escalaMetricas);
                    $n12 = 0;
                    $nCelulasRed = 0;
                    foreach ($lideres12Red as $nodoConteo) {
                        $nodoConteo = (array)$nodoConteo;
                        if ((string)($nodoConteo['tipo'] ?? '') === 'lider_12') {
                            $n12++;
                        }
                        $nCelulasRed += $contarCelulas($nodoConteo);
                    }
                ?>
                    <tr class="redes-group redes-group--<?= htmlspecialchars($claveRed) ?>">
                        <td colspan="<?= (int)$colCount ?>">
                            <?= htmlspecialchars($tituloRed) ?>
                            · <?= $n12 ?> líder(es) de 12
                            <?php if ($nCelulasRed > 0): ?>
                                · <?= $nCelulasRed ?> célula(s)
                            <?php endif; ?>
                            · <?= (int)($packRed['anio']['total'] ?? 0) ?> ganados
                        </td>
                    </tr>
                    <?php foreach ($lideres12Red as $nodo12):
                        $nodo12 = (array)$nodo12;
                        $hijos = is_array($nodo12['hijos'] ?? null) ? $nodo12['hijos'] : [];
                        $nEquipo = $contarEquipo($nodo12);
                        $pack12 = $metricasDe($nodo12, $escalaMetricas);
                        $esGrupo12 = (string)($nodo12['tipo'] ?? '') === 'lider_12';
                    ?>
                    <tr>
                        <td class="col-num"><?= $nFila++ ?></td>
                        <td><?= $celdaNombre($nodo12) ?></td>
                        <td class="col-tipo"><?= htmlspecialchars((string)($nodo12['tipo_label'] ?? 'Líder de 12')) ?></td>
                        <?= $celdasMetricas($pack12, $escala, $clavesMetrica) ?>
                    </tr>
                    <?php foreach ($hijos as $hijo):
                        $hijo = (array)$hijo;
                        if ((string)($hijo['tipo'] ?? '') === 'directos' && (int)($hijo['anual']['totales']['total'] ?? 0) <= 0) {
                            continue;
                        }
                        $packHijo = $metricasDe($hijo, $escalaMetricas);
                        $nietos = is_array($hijo['hijos'] ?? null) ? $hijo['hijos'] : [];
                    ?>
                    <tr class="redes-equipo">
                        <td class="col-num"><?= $nFila++ ?></td>
                        <td><?= $celdaNombre($hijo) ?></td>
                        <td class="col-tipo"><?= htmlspecialchars((string)($hijo['tipo_label'] ?? '')) ?></td>
                        <?= $celdasMetricas($packHijo, $escala, $clavesMetrica) ?>
                    </tr>
                    <?php foreach ($nietos as $nieto):
                        $nieto = (array)$nieto;
                        $packNieto = $metricasDe($nieto, $escalaMetricas);
                    ?>
                    <tr class="redes-nieto">
                        <td class="col-num"><?= $nFila++ ?></td>
                        <td><?= $celdaNombre($nieto) ?></td>
                        <td class="col-tipo"><?= htmlspecialchars((string)($nieto['tipo_label'] ?? '')) ?></td>
                        <?= $celdasMetricas($packNieto, $escala, $clavesMetrica) ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                    <?php if ($esGrupo12 && $nEquipo > 0): ?>
                    <tr class="redes-subtotal">
                        <td colspan="<?= (int)$colIdentidad ?>">Subtotal <?= htmlspecialchars((string)($nodo12['nombre'] ?? '')) ?></td>
                        <?= $celdasMetricas($pack12, $escala, $clavesMetrica) ?>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <tr class="redes-subtotal-red">
                        <td colspan="<?= (int)$colIdentidad ?>">Subtotal <?= htmlspecialchars($tituloRed) ?></td>
                        <?= $celdasMetricas($packRed, $escala, $clavesMetrica) ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="<?= (int)$colIdentidad ?>">TOTAL GENERAL</td>
                        <?= $celdasMetricas($totalesGenerales, $escala, $clavesMetrica) ?>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
