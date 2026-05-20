<?php
/**
 * Dashboard UV simplificado: inscripciones + pagos por ministerio (modo Consolidar).
 *
 * @var array<int, array<string, mixed>> $tablaUvModoConsolidar
 * @var array<int, array<string, mixed>> $tablaPagosUv
 * @var string $fecha_inicio_mes
 * @var string $fecha_fin_mes
 * @var int $anio
 * @var int $mes
 */
$tablaInscritos = is_array($tablaUvModoConsolidar ?? null) ? $tablaUvModoConsolidar : [];
$tablaPagos = is_array($tablaPagosUv ?? null) ? $tablaPagosUv : [];
$indicadoresEncuentro = is_array($indicadores_encuentro_uv ?? null) ? $indicadores_encuentro_uv : [];
$kpiTotalPersonas = (int)($indicadoresEncuentro['total_personas'] ?? 0);
$kpiAsistieron = (int)($indicadoresEncuentro['asistieron_encuentro'] ?? 0);
$kpiSinAsistencia = (int)($indicadoresEncuentro['sin_asistencia_encuentro'] ?? 0);
$kpiPctAsistieron = (float)($indicadoresEncuentro['pct_asistieron'] ?? 0);
$kpiPctSinAsistencia = (float)($indicadoresEncuentro['pct_sin_asistencia'] ?? 0);
$fechaIni = (string)($fechaInicioMes ?? '');
$fechaFin = (string)($fechaFinMes ?? '');
$anioRep = (int)($anio ?? date('Y'));
$semestreRep = (int)($semestre_uv ?? 0);
$etiquetaPeriodo = trim((string)($etiqueta_periodo_uv ?? ''));
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];

$dashSlugMinisterio = static function ($nombre) {
    $s = strtolower(trim(preg_replace('/\s+/u', ' ', (string)$nombre)));
    return $s === '' ? 'sin-ministerio' : $s;
};

$dashAttrsInscritosRow = static function (array $fila) use ($dashSlugMinisterio) {
    $slug = $dashSlugMinisterio($fila['ministerio'] ?? '');
    $h = (int)($fila['hombres'] ?? 0);
    $m = (int)($fila['mujeres'] ?? 0);
    $j = (int)($fila['jovenes'] ?? 0);
    $t = (int)($fila['teens'] ?? 0);
    $tot = (int)($fila['total'] ?? 0);
    $ah = (int)($fila['asist_hombres'] ?? 0);
    $am = (int)($fila['asist_mujeres'] ?? 0);
    $aj = (int)($fila['asist_jovenes'] ?? 0);
    $at = (int)($fila['asist_teens'] ?? 0);
    $asist = (int)($fila['asistencias_reales'] ?? 0);
    return ' data-dash-row="1" data-dash-profile="uv-inscritos" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $h . '" data-dash-m="' . $m . '" data-dash-j="' . $j . '" data-dash-t="' . $t
        . '" data-dash-asist-h="' . $ah . '" data-dash-asist-m="' . $am . '" data-dash-asist-j="' . $aj . '" data-dash-asist-t="' . $at
        . '" data-dash-ins="' . $tot . '" data-dash-extra="' . $asist . '" data-dash-pag="0" data-dash-skip-pago="1"';
};

$dashAttrsPagosRow = static function (array $fila) use ($dashSlugMinisterio) {
    $slug = $dashSlugMinisterio($fila['Ministerio'] ?? '');
    $ih = (int)($fila['Inscritos_Hombres'] ?? 0);
    $im = (int)($fila['Inscritos_Mujeres'] ?? 0);
    $ij = (int)($fila['Inscritos_Jovenes'] ?? 0);
    $it = (int)($fila['Inscritos_Teens'] ?? 0);
    $ph = (int)($fila['Pagados_Hombres'] ?? 0);
    $pm = (int)($fila['Pagados_Mujeres'] ?? 0);
    $pj = (int)($fila['Pagados_Jovenes'] ?? 0);
    $pt = (int)($fila['Pagados_Teens'] ?? 0);
    $totPag = (int)($fila['Pagados'] ?? 0);
    $totIns = (int)($fila['Inscritos'] ?? 0);
    return ' data-dash-row="1" data-dash-profile="uv-pagos" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $ih . '" data-dash-m="' . $im . '" data-dash-j="' . $ij . '" data-dash-t="' . $it
        . '" data-dash-pag-h="' . $ph . '" data-dash-pag-m="' . $pm . '" data-dash-pag-j="' . $pj . '" data-dash-pag-t="' . $pt
        . '" data-dash-ins="' . $totIns . '" data-dash-extra="' . $totPag . '" data-dash-pag="' . $totPag . '"';
};

$totIns = ['h' => 0, 'm' => 0, 'j' => 0, 't' => 0, 'total' => 0, 'asist' => 0, 'asist_h' => 0, 'asist_m' => 0, 'asist_j' => 0, 'asist_t' => 0];
foreach ($tablaInscritos as $fila) {
    $totIns['h'] += (int)($fila['hombres'] ?? 0);
    $totIns['m'] += (int)($fila['mujeres'] ?? 0);
    $totIns['j'] += (int)($fila['jovenes'] ?? 0);
    $totIns['t'] += (int)($fila['teens'] ?? 0);
    $totIns['total'] += (int)($fila['total'] ?? 0);
    $totIns['asist'] += (int)($fila['asistencias_reales'] ?? 0);
    $totIns['asist_h'] += (int)($fila['asist_hombres'] ?? 0);
    $totIns['asist_m'] += (int)($fila['asist_mujeres'] ?? 0);
    $totIns['asist_j'] += (int)($fila['asist_jovenes'] ?? 0);
    $totIns['asist_t'] += (int)($fila['asist_teens'] ?? 0);
}

$totPag = ['h' => 0, 'm' => 0, 'j' => 0, 't' => 0, 'inscritos' => 0, 'pagados' => 0, 'pag_h' => 0, 'pag_m' => 0, 'pag_j' => 0, 'pag_t' => 0];
foreach ($tablaPagos as $fila) {
    $totPag['h'] += (int)($fila['Inscritos_Hombres'] ?? 0);
    $totPag['m'] += (int)($fila['Inscritos_Mujeres'] ?? 0);
    $totPag['j'] += (int)($fila['Inscritos_Jovenes'] ?? 0);
    $totPag['t'] += (int)($fila['Inscritos_Teens'] ?? 0);
    $totPag['inscritos'] += (int)($fila['Inscritos'] ?? 0);
    $totPag['pagados'] += (int)($fila['Pagados'] ?? 0);
    $totPag['pag_h'] += (int)($fila['Pagados_Hombres'] ?? 0);
    $totPag['pag_m'] += (int)($fila['Pagados_Mujeres'] ?? 0);
    $totPag['pag_j'] += (int)($fila['Pagados_Jovenes'] ?? 0);
    $totPag['pag_t'] += (int)($fila['Pagados_Teens'] ?? 0);
}
?>

<div class="dash-card uv-encuentro-kpis" id="uvIndicadoresEncuentro">
    <h4 class="section-title" style="margin-bottom:6px;">Asistencia al Encuentro — base de la iglesia</h4>
    <p class="uv-dash-kpi-note">
        <?php
        $alcanceKpi = (string)($indicadoresEncuentro['alcance'] ?? 'toda_la_iglesia');
        if ($alcanceKpi === 'ministerio'): ?>
            Todas las personas registradas en la tabla <strong>persona</strong> del ministerio filtrado.
        <?php elseif ($alcanceKpi === 'lider'): ?>
            Todas las personas de la tabla <strong>persona</strong> bajo el líder filtrado.
        <?php elseif ($alcanceKpi === 'alcance_rol'): ?>
            Personas de la tabla <strong>persona</strong> según el alcance de tu rol (no es solo inscritos UV).
        <?php else: ?>
            <strong>Toda la iglesia:</strong> todos los registros de la tabla <strong>persona</strong> (sin filtrar por semestre ni inscripción UV).
        <?php endif; ?>
        «Asistieron» = al menos una clase del programa <strong>Encuentro</strong> marcada en asistencias (Consolidar).
    </p>
    <div class="uv-kpi-row uv-kpi-row-encuentro">
        <div class="uv-kpi uv-kpi-total">
            <span>Total en base de datos</span>
            <strong><?= $kpiTotalPersonas ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-ok">
            <span>Asistieron al encuentro</span>
            <strong><?= $kpiAsistieron ?></strong>
            <em class="uv-kpi-pct"><?= number_format($kpiPctAsistieron, 1) ?>%</em>
        </div>
        <div class="uv-kpi uv-kpi-warn">
            <span>Sin asistencia al encuentro</span>
            <strong><?= $kpiSinAsistencia ?></strong>
            <em class="uv-kpi-pct"><?= number_format($kpiPctSinAsistencia, 1) ?>%</em>
        </div>
    </div>
    <?php if ($kpiTotalPersonas > 0): ?>
    <div class="uv-encuentro-bar" role="img" aria-label="Distribución asistencia encuentro">
        <div class="uv-encuentro-bar__asistio" style="width:<?= min(100, max(0, $kpiPctAsistieron)) ?>%;" title="Asistieron: <?= $kpiAsistieron ?>"></div>
        <div class="uv-encuentro-bar__pendiente" style="width:<?= min(100, max(0, $kpiPctSinAsistencia)) ?>%;" title="Sin asistencia: <?= $kpiSinAsistencia ?>"></div>
    </div>
    <div class="uv-encuentro-bar-legend">
        <span class="uv-legend-asistio">Asistieron</span>
        <span class="uv-legend-pendiente">Sin asistencia</span>
    </div>
    <?php else: ?>
    <p class="uv-dash-kpi-empty">No hay personas en la base con los filtros actuales.</p>
    <?php endif; ?>
</div>

<p class="uv-dash-intro uv-dash-intro-compact">
    <strong><?= htmlspecialchars($etiquetaPeriodo !== '' ? $etiquetaPeriodo : ('Semestre ' . $semestreRep)) ?> <?= $anioRep ?></strong>
    · <?= htmlspecialchars($fechaIni) ?> – <?= htmlspecialchars($fechaFin) ?>
    · Clic en ministerio para detalle.
    <span class="uv-dash-alineacion-inline">
        <a href="<?= PUBLIC_URL ?>index.php?url=home/consolidar/asistencias" target="_blank" rel="noopener">Asistencias</a>
        ·
        <a href="<?= PUBLIC_URL ?>index.php?url=escuelas_formacion/pagos/consolidar" target="_blank" rel="noopener">Pagos UV</a>
    </span>
</p>

<div class="uv-dash-tables-grid">
<div class="dash-card uv-dash-table-card">
    <h4 class="section-title">Inscripciones por ministerio</h4>
    <small class="uv-dash-table-note">Personas inscritas en Universidad de la Vida en el periodo, por segmento (H / M / Jóvenes).</small>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-dash-filterable uv-dash-tabla-detalle"
               id="tablaUvInscripciones" data-uv-vista-inicial="todas"
               data-dash-gen-mode="hmjt" data-dash-enable-pago="0"
               data-dash-global-ministerio-id="<?= htmlspecialchars((string)($filtroMinisterio ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <colgroup>
                <col class="uv-col-min">
                <col class="uv-col-num" span="4">
                <col class="uv-col-num-wide">
            </colgroup>
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th class="uv-num" data-dash-seg="h">Hombres</th>
                    <th class="uv-num" data-dash-seg="m">Mujeres</th>
                    <th class="uv-num" data-dash-seg="j">Jóvenes</th>
                    <th class="uv-num">Total</th>
                    <th class="uv-num">Asistencias reales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tablaInscritos)): ?>
                    <?php foreach ($tablaInscritos as $fila): ?>
                        <tr<?= $dashAttrsInscritosRow($fila) ?>>
                            <td class="uv-min-clickable" title="Ver personas de este ministerio"><?= htmlspecialchars((string)($fila['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td class="uv-num" data-dash-seg="h"><?= (int)($fila['hombres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="m"><?= (int)($fila['mujeres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="j"><?= (int)($fila['jovenes'] ?? 0) ?></td>
                            <td class="uv-num uv-num-total uv-dash-col-total"><?= (int)($fila['total'] ?? 0) ?></td>
                            <td class="uv-num uv-dash-col-extra"><?= (int)($fila['asistencias_reales'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No hay inscripciones de Universidad de la Vida para este periodo y filtros.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr>
                    <th>TOTAL</th>
                    <th class="uv-num" data-dash-seg="h"><?= $totIns['h'] ?></th>
                    <th class="uv-num" data-dash-seg="m"><?= $totIns['m'] ?></th>
                    <th class="uv-num" data-dash-seg="j"><?= $totIns['j'] ?></th>
                    <th class="uv-num uv-dash-col-total"><?= $totIns['total'] ?></th>
                    <th class="uv-num uv-dash-col-extra"><?= $totIns['asist'] ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="dash-card uv-dash-table-card">
    <h4 class="section-title">Pagos reales por ministerio</h4>
    <small class="uv-dash-table-note">Misma vista que inscripciones; la última columna muestra cuántos ya pagaron (valor, método o referencia en la ficha).</small>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-dash-filterable uv-dash-tabla-detalle"
               id="tablaUvPagos" data-uv-vista-inicial="pagos"
               data-dash-gen-mode="hmjt" data-dash-enable-pago="1"
               data-dash-global-ministerio-id="<?= htmlspecialchars((string)($filtroMinisterio ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <colgroup>
                <col class="uv-col-min">
                <col class="uv-col-num" span="4">
                <col class="uv-col-num-wide">
            </colgroup>
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th class="uv-num" data-dash-seg="h">Hombres</th>
                    <th class="uv-num" data-dash-seg="m">Mujeres</th>
                    <th class="uv-num" data-dash-seg="j">Jóvenes</th>
                    <th class="uv-num">Total</th>
                    <th class="uv-num">Pagos reales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tablaPagos)): ?>
                    <?php foreach ($tablaPagos as $fila): ?>
                        <tr<?= $dashAttrsPagosRow($fila) ?>>
                            <td class="uv-min-clickable" title="Ver personas con pago de este ministerio"><?= htmlspecialchars((string)($fila['Ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td class="uv-num" data-dash-seg="h"><?= (int)($fila['Inscritos_Hombres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="m"><?= (int)($fila['Inscritos_Mujeres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="j"><?= (int)($fila['Inscritos_Jovenes'] ?? 0) ?></td>
                            <td class="uv-num uv-num-total uv-dash-col-total"><?= (int)($fila['Inscritos'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag uv-dash-col-extra"><?= (int)($fila['Pagados'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No hay datos de pago para este periodo y filtros.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr>
                    <th>TOTAL</th>
                    <th class="uv-num" data-dash-seg="h"><?= $totPag['h'] ?></th>
                    <th class="uv-num" data-dash-seg="m"><?= $totPag['m'] ?></th>
                    <th class="uv-num" data-dash-seg="j"><?= $totPag['j'] ?></th>
                    <th class="uv-num uv-dash-col-total"><?= $totPag['inscritos'] ?></th>
                    <th class="uv-num uv-dash-col-extra"><?= $totPag['pagados'] ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</div>

<div id="uvDetalleModal" class="uv-detalle-modal" hidden aria-hidden="true">
    <div class="uv-detalle-backdrop" data-uv-cerrar="1"></div>
    <div class="uv-detalle-panel" role="dialog" aria-labelledby="uvDetalleTitulo">
        <div class="uv-detalle-head">
            <div>
                <h4 id="uvDetalleTitulo">Detalle del ministerio</h4>
                <p id="uvDetalleSub" class="uv-detalle-sub"></p>
            </div>
            <button type="button" class="uv-detalle-cerrar" data-uv-cerrar="1" aria-label="Cerrar">&times;</button>
        </div>
        <div class="uv-detalle-filtros">
            <button type="button" class="uv-det-filt active" data-uv-filt="todas">Todas</button>
            <button type="button" class="uv-det-filt" data-uv-filt="asistencias">Con asistencia</button>
            <button type="button" class="uv-det-filt" data-uv-filt="pagos">Con pago</button>
        </div>
        <p id="uvDetalleAlineacion" class="uv-detalle-alineacion"></p>
        <div class="uv-detalle-loading" id="uvDetalleLoading" hidden>Cargando…</div>
        <div class="table-wrap uv-detalle-table-wrap">
            <table class="leader-table" id="uvDetalleTabla">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Líder</th>
                        <th>Segmento</th>
                        <th>Registro</th>
                        <th>Pago</th>
                        <th>Asistencia</th>
                    </tr>
                </thead>
                <tbody id="uvDetalleBody"></tbody>
            </table>
        </div>
        <p id="uvDetalleVacio" class="uv-detalle-vacio" hidden>No hay personas para este criterio.</p>
    </div>
</div>

<script>
window.UV_DASH_DETALLE = {
    route: 'reportes/dashboard-escuelas-uv-detalle',
    anio: <?= (int)$anioRep ?>,
    semestre: <?= (int)$semestreRep ?>,
    filtroMinisterio: <?= json_encode((string)($filtroMinisterio ?? ''), JSON_UNESCAPED_UNICODE) ?>,
    filtroLider: <?= json_encode((string)($filtroLider ?? ''), JSON_UNESCAPED_UNICODE) ?>
};
</script>
