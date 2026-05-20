<?php
/**
 * Reporte principal UV por ministerio (inscritos / pagos por segmento).
 *
 * @var array<int, array<string, mixed>> $reporte_uv_ministerios
 */
$reporteUv = is_array($reporte_uv_ministerios ?? null) ? $reporte_uv_ministerios : [];
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$anioRep = (int)($anio ?? date('Y'));
$mesRep = (int)($mes ?? date('n'));
$totalInsPeriodo = (int)($total_inscripciones_uv_periodo ?? 0);
$fechaIni = (string)($fecha_inicio_mes ?? '');
$fechaFin = (string)($fecha_fin_mes ?? '');

$tot = [
    'inscritos' => 0, 'pagados' => 0, 'pendientes' => 0, 'valor' => 0.0,
    'ins_h' => 0, 'ins_m' => 0, 'ins_j' => 0, 'ins_t' => 0,
    'pag_h' => 0, 'pag_m' => 0, 'pag_j' => 0, 'pag_t' => 0, 'asist' => 0,
];
foreach ($reporteUv as $fila) {
    $tot['inscritos'] += (int)($fila['inscritos'] ?? 0);
    $tot['pagados'] += (int)($fila['pagados'] ?? 0);
    $tot['pendientes'] += (int)($fila['pendientes'] ?? 0);
    $tot['valor'] += (float)($fila['valor_recaudado'] ?? 0);
    $tot['ins_h'] += (int)($fila['ins_hombres'] ?? 0);
    $tot['ins_m'] += (int)($fila['ins_mujeres'] ?? 0);
    $tot['ins_j'] += (int)($fila['ins_jovenes'] ?? 0);
    $tot['ins_t'] += (int)($fila['ins_teens'] ?? 0);
    $tot['pag_h'] += (int)($fila['pag_hombres'] ?? 0);
    $tot['pag_m'] += (int)($fila['pag_mujeres'] ?? 0);
    $tot['pag_j'] += (int)($fila['pag_jovenes'] ?? 0);
    $tot['pag_t'] += (int)($fila['pag_teens'] ?? 0);
    $tot['asist'] += (int)($fila['asistencias_reales'] ?? 0);
}
$totPct = $tot['inscritos'] > 0 ? round(($tot['pagados'] / $tot['inscritos']) * 100, 1) : 0;

$dashSlugMinisterio = static function ($nombre) {
    $s = strtolower(trim(preg_replace('/\s+/u', ' ', (string)$nombre)));
    return $s === '' ? 'sin-ministerio' : $s;
};

$dashAttrsReporteUv = static function (array $fila) use ($dashSlugMinisterio) {
    $slug = $dashSlugMinisterio($fila['ministerio'] ?? '');
    $ins = (int)($fila['inscritos'] ?? 0);
    $pag = (int)($fila['pagados'] ?? 0);
    $h = (int)($fila['ins_hombres'] ?? 0);
    $m = (int)($fila['ins_mujeres'] ?? 0);
    $j = (int)($fila['ins_jovenes'] ?? 0);
    $t = (int)($fila['ins_teens'] ?? 0);
    return ' data-dash-row="1" data-dash-profile="reporte-uv" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $h . '" data-dash-m="' . $m . '" data-dash-j="' . $j . '" data-dash-t="' . $t
        . '" data-dash-ins="' . $ins . '" data-dash-pag="' . $pag . '"';
};
?>

<div class="dash-card uv-reporte-principal" id="uvReportePrincipal">
    <div class="uv-reporte-head">
        <div>
            <h3 class="uv-reporte-title">Reporte por ministerio — Universidad de la Vida</h3>
            <p class="uv-reporte-sub">
                Periodo: <strong><?= htmlspecialchars($meses[$mesRep] ?? (string)$mesRep) ?> <?= $anioRep ?></strong>
                (<?= htmlspecialchars($fechaIni) ?> a <?= htmlspecialchars($fechaFin) ?>).
                Misma base que <strong>Consolidar</strong>: inscripciones del programa con fecha de registro en el mes.
                <strong><?= $totalInsPeriodo ?></strong> inscripción(es) UV en el periodo (filtros aplicados).
            </p>
            <p class="uv-reporte-sub uv-reporte-sub-muted">
                Pagado = inscripción con valor, método o referencia de pago. Hombres/mujeres = adultos; jóvenes 13–30; teens 9–12.
            </p>
        </div>
    </div>

    <div class="uv-kpi-row">
        <div class="uv-kpi"><span>Inscritos</span><strong><?= $tot['inscritos'] ?></strong>
        <div class="uv-kpi uv-kpi-ok"><span>Pagados</span><strong><?= $tot['pagados'] ?></strong></div>
        <div class="uv-kpi uv-kpi-warn"><span>Pendientes</span><strong><?= $tot['pendientes'] ?></strong></div>
        <div class="uv-kpi"><span>% cobro</span><strong><?= number_format($totPct, 1) ?>%</strong></div>
        <div class="uv-kpi"><span>Recaudado</span><strong>$ <?= number_format($tot['valor'], 0, ',', '.') ?></strong></div>
    </div>

    <div class="table-wrap">
        <table class="leader-table uv-reporte-table js-dash-filterable" id="tablaReporteUvMinisterio"
               data-dash-gen-mode="hmjt" data-dash-enable-pago="1"
               data-dash-global-ministerio-id="<?= htmlspecialchars((string)($filtro_ministerio ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <thead>
                <tr>
                    <th rowspan="2" class="uv-th-ministerio">Ministerio</th>
                    <th colspan="4" class="uv-th-group uv-th-ins" data-dash-colspan-ins>Inscritos</th>
                    <th colspan="4" class="uv-th-group uv-th-pag" data-dash-colspan-pag>Pagados</th>
                    <th rowspan="2">Pend.</th>
                    <th rowspan="2">% pago</th>
                    <th rowspan="2">Asist. real</th>
                    <th rowspan="2">Recaudado</th>
                </tr>
                <tr>
                    <th class="uv-th-seg" data-dash-seg="h">H</th>
                    <th class="uv-th-seg" data-dash-seg="m">M</th>
                    <th class="uv-th-seg" data-dash-seg="j">Jov.</th>
                    <th class="uv-th-seg" data-dash-seg="t">Teen</th>
                    <th class="uv-th-seg uv-th-pag" data-dash-seg="h">H</th>
                    <th class="uv-th-seg uv-th-pag" data-dash-seg="m">M</th>
                    <th class="uv-th-seg uv-th-pag" data-dash-seg="j">Jov.</th>
                    <th class="uv-th-seg uv-th-pag" data-dash-seg="t">Teen</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($reporteUv)): ?>
                    <?php foreach ($reporteUv as $fila): ?>
                        <?php
                        $ins = (int)($fila['inscritos'] ?? 0);
                        $pag = (int)($fila['pagados'] ?? 0);
                        $pend = (int)($fila['pendientes'] ?? 0);
                        $pct = (float)($fila['pct_pago'] ?? 0);
                        $valor = (float)($fila['valor_recaudado'] ?? 0);
                        $pctClass = $pct >= 80 ? 'uv-pct-ok' : ($pct >= 50 ? 'uv-pct-mid' : 'uv-pct-low');
                        ?>
                        <tr<?= $dashAttrsReporteUv($fila) ?>>
                            <td class="uv-td-ministerio"><?= htmlspecialchars((string)($fila['ministerio'] ?? '')) ?></td>
                            <td class="uv-num" data-dash-seg="h"><?= (int)($fila['ins_hombres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="m"><?= (int)($fila['ins_mujeres'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="j"><?= (int)($fila['ins_jovenes'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="t"><?= (int)($fila['ins_teens'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag" data-dash-seg="h"><?= (int)($fila['pag_hombres'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag" data-dash-seg="m"><?= (int)($fila['pag_mujeres'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag" data-dash-seg="j"><?= (int)($fila['pag_jovenes'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag" data-dash-seg="t"><?= (int)($fila['pag_teens'] ?? 0) ?></td>
                            <td class="uv-num uv-pend"><?= $pend ?></td>
                            <td class="uv-num <?= $pctClass ?>"><?= number_format($pct, 1) ?>%</td>
                            <td class="uv-num"><?= (int)($fila['asistencias_reales'] ?? 0) ?></td>
                            <td class="uv-num">$ <?= number_format($valor, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="13">No hay inscripciones de Universidad de la Vida para el periodo y filtros seleccionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr class="uv-tfoot-row">
                    <th>TOTAL</th>
                    <th data-dash-seg="h"><?= $tot['ins_h'] ?></th>
                    <th data-dash-seg="m"><?= $tot['ins_m'] ?></th>
                    <th data-dash-seg="j"><?= $tot['ins_j'] ?></th>
                    <th data-dash-seg="t"><?= $tot['ins_t'] ?></th>
                    <th data-dash-seg="h"><?= $tot['pag_h'] ?></th>
                    <th data-dash-seg="m"><?= $tot['pag_m'] ?></th>
                    <th data-dash-seg="j"><?= $tot['pag_j'] ?></th>
                    <th data-dash-seg="t"><?= $tot['pag_t'] ?></th>
                    <th><?= $tot['pendientes'] ?></th>
                    <th><?= number_format($totPct, 1) ?>%</th>
                    <th><?= $tot['asist'] ?></th>
                    <th>$ <?= number_format($tot['valor'], 0, ',', '.') ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
