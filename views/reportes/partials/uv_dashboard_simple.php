<?php
/**
 * Dashboard UV simplificado: inscripciones + pagos por ministerio (modo Consolidar).
 *
 * @var array<int, array<string, mixed>> $tablaUvModoConsolidar
 * @var array<int, array<string, mixed>> $tablaPagosUv
 * @var array<int, array<string, mixed>> $tablaPagosUvLiderCelula
 * @var string $fecha_inicio_mes
 * @var string $fecha_fin_mes
 * @var int $anio
 * @var int $mes
 */
$tablaInscritos = is_array($tablaUvModoConsolidar ?? null) ? $tablaUvModoConsolidar : [];
$tablaPagos = is_array($tablaPagosUv ?? null) ? $tablaPagosUv : (array)($tabla_pagos_uv ?? []);
$tablaPagosLider = is_array($tablaPagosUvLiderCelula ?? null) ? $tablaPagosUvLiderCelula : (array)($tabla_pagos_uv_lider_celula ?? []);
$indicadoresEscalera = is_array($indicadores_encuentro_uv ?? null) ? $indicadores_encuentro_uv : [];
$kpiTotalPersonas = (int)($indicadoresEscalera['total_personas'] ?? 0);
$kpiConUv = (int)($indicadoresEscalera['con_uv_escalera'] ?? 0);
$kpiSinUv = (int)($indicadoresEscalera['sin_uv_escalera'] ?? 0);
$kpiConEncuentro = (int)($indicadoresEscalera['con_encuentro_escalera'] ?? 0);
$kpiConBautismo = (int)($indicadoresEscalera['con_bautismo_escalera'] ?? 0);
$kpiConCapDestino = (int)($indicadoresEscalera['con_capacitacion_destino'] ?? 0);
$kpiPctConUv = (float)($indicadoresEscalera['pct_con_uv'] ?? 0);
$kpiPctSinUv = (float)($indicadoresEscalera['pct_sin_uv'] ?? 0);
$kpiPctCapDestino = (float)($indicadoresEscalera['pct_capacitacion_destino'] ?? 0);
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

$dashAttrsPagosLiderRow = static function (array $fila) use ($dashSlugMinisterio) {
    $slug = trim((string)($fila['Lider_Slug'] ?? ''));
    if ($slug === '') {
        $slug = $dashSlugMinisterio($fila['Lider'] ?? '');
    }
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
    return ' data-dash-row="1" data-dash-profile="uv-pagos-lider" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $ih . '" data-dash-m="' . $im . '" data-dash-j="' . $ij . '" data-dash-t="' . $it
        . '" data-dash-pag-h="' . $ph . '" data-dash-pag-m="' . $pm . '" data-dash-pag-j="' . $pj . '" data-dash-pag-t="' . $pt
        . '" data-dash-ins="' . $totIns . '" data-dash-extra="' . $totPag . '" data-dash-pag="' . $totPag . '"';
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

$renderFiltrosTablaUv = static function (string $tableId, bool $filtroPagosActivo): void {
    $tid = htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="dash-table-tool-row js-dash-filtros-estaticos" data-dash-table-id="<?= $tid ?>">
        <div class="dash-filtros-panel" style="flex:1;min-width:0;">
            <p class="dash-filter-hint">Jóvenes = 14–28 años o segmento «jóvenes» (no incluye Teens). Teens = 9–13 años. H/M = por género. Con filtro «Con pendiente», la columna Pagos muestra cuántos faltan por pagar en el segmento elegido (misma base que la pantalla Pagos UV del semestre).</p>
            <div class="dash-inline-filters">
                <div class="group">
                    <label for="dash-min-<?= $tid ?>">Ministerio</label>
                    <select id="dash-min-<?= $tid ?>" class="js-dash-sel-min" data-dash-table="<?= $tid ?>">
                        <option value="">Todos los ministerios</option>
                    </select>
                </div>
                <div class="group">
                    <span class="dash-group-label">Segmento</span>
                    <div class="dash-segment-checks">
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="h" data-dash-table="<?= $tid ?>"> Hombres</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="m" data-dash-table="<?= $tid ?>"> Mujeres</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="j" data-dash-table="<?= $tid ?>"> Jóvenes</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="t" data-dash-table="<?= $tid ?>"> Teens</label>
                    </div>
                    <div class="dash-segment-hint">Marca uno o más: oculta otras columnas y recalcula totales.</div>
                </div>
                <div class="group">
                    <label for="dash-pago-<?= $tid ?>">Pagos</label>
                    <select id="dash-pago-<?= $tid ?>" class="js-dash-sel-pago" data-dash-table="<?= $tid ?>"
                        <?= $filtroPagosActivo ? '' : 'disabled title="Filtro de pagos solo en la tabla de pagos"' ?>>
                        <option value="all">Pagos: todos</option>
                        <option value="pend">Con pendiente</option>
                        <option value="ok">Sin pendiente (al día)</option>
                    </select>
                </div>
                <button type="button" class="btn btn-secondary btn-sm js-dash-btn-limpiar" data-dash-table="<?= $tid ?>" style="margin-top:14px;">Limpiar</button>
            </div>
        </div>
    </div>
    <?php
};

$renderFiltrosTablaUvLider = static function (string $tableId): void {
    $tid = htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="dash-table-tool-row js-dash-filtros-estaticos" data-dash-table-id="<?= $tid ?>">
        <div class="dash-filtros-panel" style="flex:1;min-width:0;">
            <p class="dash-filter-hint">Filtros por líder y segmento. Jóvenes = mismos criterios que inscripciones UV. «Sin pendiente» = del segmento elegido, inscritos con pago registrado.</p>
            <div class="dash-inline-filters">
                <div class="group">
                    <label for="dash-lid-<?= $tid ?>">Líder de célula</label>
                    <select id="dash-lid-<?= $tid ?>" class="js-dash-sel-min" data-dash-table="<?= $tid ?>">
                        <option value="">Todos los líderes</option>
                    </select>
                </div>
                <div class="group">
                    <span class="dash-group-label">Segmento</span>
                    <div class="dash-segment-checks">
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="h" data-dash-table="<?= $tid ?>"> Hombres</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="m" data-dash-table="<?= $tid ?>"> Mujeres</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="j" data-dash-table="<?= $tid ?>"> Jóvenes</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="t" data-dash-table="<?= $tid ?>"> Teens</label>
                    </div>
                    <div class="dash-segment-hint">Marca uno o más: oculta otras columnas y recalcula totales.</div>
                </div>
                <div class="group">
                    <label for="dash-pago-<?= $tid ?>">Pagos</label>
                    <select id="dash-pago-<?= $tid ?>" class="js-dash-sel-pago" data-dash-table="<?= $tid ?>">
                        <option value="all">Pagos: todos</option>
                        <option value="pend">Con pendiente</option>
                        <option value="ok">Sin pendiente (al día)</option>
                    </select>
                </div>
                <button type="button" class="btn btn-secondary btn-sm js-dash-btn-limpiar" data-dash-table="<?= $tid ?>" style="margin-top:14px;">Limpiar</button>
            </div>
        </div>
    </div>
    <?php
};

$totPagLider = ['h' => 0, 'm' => 0, 'j' => 0, 't' => 0, 'inscritos' => 0, 'pagados' => 0];
foreach ($tablaPagosLider as $fila) {
    $totPagLider['h'] += (int)($fila['Inscritos_Hombres'] ?? 0);
    $totPagLider['m'] += (int)($fila['Inscritos_Mujeres'] ?? 0);
    $totPagLider['j'] += (int)($fila['Inscritos_Jovenes'] ?? 0);
    $totPagLider['t'] += (int)($fila['Inscritos_Teens'] ?? 0);
    $totPagLider['inscritos'] += (int)($fila['Inscritos'] ?? 0);
    $totPagLider['pagados'] += (int)($fila['Pagados'] ?? 0);
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

$filtroEncuentroUvActivo = trim((string)($filtro_encuentro_uv ?? ''));
$etiquetasFiltroEncuentroUv = [
    'excluir_asistieron' => 'Excluyendo quienes ya asistieron al encuentro (día 1 y/o 2)',
    'sin_encuentro' => 'Solo inscritos sin asistencia al encuentro',
    'con_al_menos_uno' => 'Solo inscritos con asistencia al encuentro (al menos un día)',
    'con_ambos' => 'Solo inscritos que asistieron ambos días del encuentro',
];
?>

<?php if ($filtroEncuentroUvActivo !== '' && $filtroEncuentroUvActivo !== 'todos' && isset($etiquetasFiltroEncuentroUv[$filtroEncuentroUvActivo])): ?>
<p class="dash-filter-active-banner" style="margin:0 0 12px;padding:10px 14px;background:#eff6ff;border:1px solid #93c5fd;border-radius:8px;font-size:0.85rem;color:#1e40af;">
    <strong>Filtro encuentro activo:</strong> <?= htmlspecialchars($etiquetasFiltroEncuentroUv[$filtroEncuentroUvActivo], ENT_QUOTES, 'UTF-8') ?>
    — Los totales de inscripciones y pagos del semestre reflejan solo esas personas.
</p>
<?php endif; ?>

<div class="dash-card uv-encuentro-kpis" id="uvIndicadoresEscalera">
    <h4 class="section-title" style="margin-bottom:6px;">Escalera del éxito — Consolidar</h4>
    <p class="uv-dash-kpi-note">
        <?php
        $alcanceKpi = (string)($indicadoresEscalera['alcance'] ?? 'toda_la_iglesia');
        if ($alcanceKpi === 'ministerio'): ?>
            Personas del ministerio filtrado según el campo <strong>Escalera del éxito</strong> en su ficha (misma lógica que el formulario).
        <?php elseif ($alcanceKpi === 'lider'): ?>
            Personas bajo el líder filtrado, según <strong>Escalera_Checklist</strong> en Consolidar.
        <?php elseif ($alcanceKpi === 'alcance_rol'): ?>
            Personas visibles para tu rol; los totales reflejan los peldaños marcados en la escalera (no inscripciones del semestre).
        <?php else: ?>
            <strong>Toda la iglesia:</strong> peldaños en <strong>Consolidar</strong> (UV, Encuentro, Bautismo) y paso a <strong>Capacitación Destino</strong> (Discipular en escalera o inscripción Cap).
        <?php endif; ?>
    </p>
    <div class="uv-kpi-row uv-kpi-row-encuentro">
        <div class="uv-kpi uv-kpi-total">
            <span>Total personas</span>
            <strong><?= $kpiTotalPersonas ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-ok">
            <span>Universidad de la Vida</span>
            <strong><?= $kpiConUv ?></strong>
            <em class="uv-kpi-pct"><?= number_format($kpiPctConUv, 1) ?>%</em>
        </div>
        <div class="uv-kpi uv-kpi-warn">
            <span>Sin UV en escalera</span>
            <strong><?= $kpiSinUv ?></strong>
            <em class="uv-kpi-pct"><?= number_format($kpiPctSinUv, 1) ?>%</em>
        </div>
        <div class="uv-kpi uv-kpi-ok" style="background:#ecfdf5;border-color:#6ee7b7;">
            <span>Encuentro</span>
            <strong><?= $kpiConEncuentro ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-ok" style="background:#f5f3ff;border-color:#c4b5fd;">
            <span>Bautismo</span>
            <strong><?= $kpiConBautismo ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-ok" style="background:#fffbeb;border-color:#e9c46a;">
            <span>Capacitación Destino</span>
            <strong style="color:#7a4e08;"><?= $kpiConCapDestino ?></strong>
            <em class="uv-kpi-pct"><?= number_format($kpiPctCapDestino, 1) ?>%</em>
        </div>
    </div>
    <?php if ($kpiTotalPersonas > 0): ?>
    <div class="uv-encuentro-bar" role="img" aria-label="Distribución UV en escalera">
        <div class="uv-encuentro-bar__asistio" style="width:<?= min(100, max(0, $kpiPctConUv)) ?>%;" title="Con UV: <?= $kpiConUv ?>"></div>
        <div class="uv-encuentro-bar__pendiente" style="width:<?= min(100, max(0, $kpiPctSinUv)) ?>%;" title="Sin UV: <?= $kpiSinUv ?>"></div>
    </div>
    <div class="uv-encuentro-bar-legend">
        <span class="uv-legend-asistio">Con Universidad de la Vida</span>
        <span class="uv-legend-pendiente">Sin UV en escalera</span>
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
        <a href="<?= PUBLIC_URL ?>index.php?url=escuelas_formacion/pagos/consolidar&amp;anio=<?= (int)$anioRep ?>&amp;semestre=<?= (int)$semestreRep ?>" target="_blank" rel="noopener">Pagos UV</a>
    </span>
</p>

<div class="uv-dash-tables-grid">
<div class="dash-card uv-dash-table-card">
    <h4 class="section-title">Inscripciones por ministerio</h4>
    <small class="uv-dash-table-note">Personas inscritas en Universidad de la Vida en el periodo, por segmento (H / M / Jóvenes / Teens).</small>
    <?php $renderFiltrosTablaUv('tablaUvInscripciones', false); ?>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-dash-filterable uv-dash-tabla-detalle"
               id="tablaUvInscripciones" data-uv-vista-inicial="todas"
               data-dash-gen-mode="hmjt" data-dash-enable-pago="0"
               data-dash-global-ministerio-id="<?= htmlspecialchars((string)($filtroMinisterio ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <colgroup>
                <col class="uv-col-min">
                <col class="uv-col-num" span="5">
                <col class="uv-col-num-wide">
            </colgroup>
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th class="uv-num" data-dash-seg="h">H</th>
                    <th class="uv-num" data-dash-seg="m">M</th>
                    <th class="uv-num" data-dash-seg="j">Jov.</th>
                    <th class="uv-num" data-dash-seg="t">Teens</th>
                    <th class="uv-num">Tot.</th>
                    <th class="uv-num">Asist.</th>
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
                            <td class="uv-num" data-dash-seg="t"><?= (int)($fila['teens'] ?? 0) ?></td>
                            <td class="uv-num uv-num-total uv-dash-col-total"><?= (int)($fila['total'] ?? 0) ?></td>
                            <td class="uv-num uv-dash-col-extra"><?= (int)($fila['asistencias_reales'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay inscripciones de Universidad de la Vida para este periodo y filtros.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr>
                    <th>TOTAL</th>
                    <th class="uv-num" data-dash-seg="h"><?= $totIns['h'] ?></th>
                    <th class="uv-num" data-dash-seg="m"><?= $totIns['m'] ?></th>
                    <th class="uv-num" data-dash-seg="j"><?= $totIns['j'] ?></th>
                    <th class="uv-num" data-dash-seg="t"><?= $totIns['t'] ?></th>
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
    <?php $renderFiltrosTablaUv('tablaUvPagos', true); ?>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-dash-filterable uv-dash-tabla-detalle"
               id="tablaUvPagos" data-uv-vista-inicial="pagos"
               data-dash-gen-mode="hmjt" data-dash-enable-pago="1"
               data-dash-global-ministerio-id="<?= htmlspecialchars((string)($filtroMinisterio ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <colgroup>
                <col class="uv-col-min">
                <col class="uv-col-num" span="5">
                <col class="uv-col-num-wide">
            </colgroup>
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th class="uv-num" data-dash-seg="h">H</th>
                    <th class="uv-num" data-dash-seg="m">M</th>
                    <th class="uv-num" data-dash-seg="j">Jov.</th>
                    <th class="uv-num" data-dash-seg="t">Teens</th>
                    <th class="uv-num">Tot.</th>
                    <th class="uv-num">Pagos</th>
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
                            <td class="uv-num" data-dash-seg="t"><?= (int)($fila['Inscritos_Teens'] ?? 0) ?></td>
                            <td class="uv-num uv-num-total uv-dash-col-total"><?= (int)($fila['Inscritos'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag uv-dash-col-extra"><?= (int)($fila['Pagados'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay datos de pago para este periodo y filtros.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr>
                    <th>TOTAL</th>
                    <th class="uv-num" data-dash-seg="h"><?= $totPag['h'] ?></th>
                    <th class="uv-num" data-dash-seg="m"><?= $totPag['m'] ?></th>
                    <th class="uv-num" data-dash-seg="j"><?= $totPag['j'] ?></th>
                    <th class="uv-num" data-dash-seg="t"><?= $totPag['t'] ?></th>
                    <th class="uv-num uv-dash-col-total"><?= $totPag['inscritos'] ?></th>
                    <th class="uv-num uv-dash-col-extra"><?= $totPag['pagados'] ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="dash-card uv-dash-table-card">
    <h4 class="section-title">Pagos reales por líder de célula</h4>
    <small class="uv-dash-table-note">Inscritos bajo cada líder de célula y cuántos ya pagaron (misma lógica que la tabla por ministerio).</small>
    <?php $renderFiltrosTablaUvLider('tablaUvPagosLider'); ?>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-dash-filterable uv-dash-tabla-detalle"
               id="tablaUvPagosLider" data-uv-vista-inicial="pagos"
               data-uv-detalle-tipo="lider"
               data-dash-gen-mode="hmjt" data-dash-enable-pago="1"
               data-dash-skip-global-ministerio="1">
            <colgroup>
                <col class="uv-col-min">
                <col class="uv-col-num" span="5">
                <col class="uv-col-num-wide">
            </colgroup>
            <thead>
                <tr>
                    <th>Líder de célula</th>
                    <th class="uv-num" data-dash-seg="h">H</th>
                    <th class="uv-num" data-dash-seg="m">M</th>
                    <th class="uv-num" data-dash-seg="j">Jov.</th>
                    <th class="uv-num" data-dash-seg="t">Teens</th>
                    <th class="uv-num">Tot.</th>
                    <th class="uv-num">Pagos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tablaPagosLider)): ?>
                    <?php foreach ($tablaPagosLider as $fila): ?>
                        <tr<?= $dashAttrsPagosLiderRow($fila) ?>>
                            <td class="uv-min-clickable" title="Ver personas inscritas bajo este líder"><?= htmlspecialchars((string)($fila['Lider'] ?? 'Sin líder de célula')) ?></td>
                            <td class="uv-num" data-dash-seg="h"><?= (int)($fila['Inscritos_Hombres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="m"><?= (int)($fila['Inscritos_Mujeres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="j"><?= (int)($fila['Inscritos_Jovenes'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="t"><?= (int)($fila['Inscritos_Teens'] ?? 0) ?></td>
                            <td class="uv-num uv-num-total uv-dash-col-total"><?= (int)($fila['Inscritos'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag uv-dash-col-extra"><?= (int)($fila['Pagados'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No hay inscripciones UV con líder de célula para este periodo y filtros.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr>
                    <th>TOTAL</th>
                    <th class="uv-num" data-dash-seg="h"><?= $totPagLider['h'] ?></th>
                    <th class="uv-num" data-dash-seg="m"><?= $totPagLider['m'] ?></th>
                    <th class="uv-num" data-dash-seg="j"><?= $totPagLider['j'] ?></th>
                    <th class="uv-num" data-dash-seg="t"><?= $totPagLider['t'] ?></th>
                    <th class="uv-num uv-dash-col-total"><?= $totPagLider['inscritos'] ?></th>
                    <th class="uv-num uv-dash-col-extra"><?= $totPagLider['pagados'] ?></th>
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
