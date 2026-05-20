<?php
/**
 * Dashboard Capacitación Destino: inscripciones y pagos por ministerio (niveles 1–3).
 *
 * @var array<int, array<string, mixed>> $tablaCapModoConsolidar
 * @var array<int, array<string, mixed>> $tablaPagosCap
 * @var int $total_inscripciones_cap_periodo
 */
$tablaInscritos = is_array($tablaCapModoConsolidar ?? null) ? $tablaCapModoConsolidar : [];
$tablaPagos = is_array($tablaPagosCap ?? null) ? $tablaPagosCap : [];
$totalInscripcionesCap = (int)($total_inscripciones_cap_periodo ?? 0);
$fechaIni = (string)($fechaInicioMes ?? '');
$fechaFin = (string)($fechaFinMes ?? '');
$anioRep = (int)($anio ?? date('Y'));
$mesRep = (int)($mes ?? date('n'));
$meses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
];
$etiquetaMes = $meses[$mesRep] ?? (string)$mesRep;

$dashSlugMinisterio = static function ($nombre) {
    $s = strtolower(trim(preg_replace('/\s+/u', ' ', (string)$nombre)));
    return $s === '' ? 'sin-ministerio' : $s;
};

$dashAttrsInscritosRow = static function (array $fila) use ($dashSlugMinisterio) {
    $slug = $dashSlugMinisterio($fila['ministerio'] ?? '');
    $n1 = (int)($fila['nivel_1'] ?? 0);
    $n2 = (int)($fila['nivel_2'] ?? 0);
    $n3 = (int)($fila['nivel_3'] ?? 0);
    $tot = (int)($fila['total'] ?? 0);
    $asist = (int)($fila['asistencias_reales'] ?? 0);
    return ' data-dash-row="1" data-dash-profile="cap-inscritos" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $n1 . '" data-dash-m="' . $n2 . '" data-dash-j="' . $n3 . '" data-dash-t="0"'
        . '" data-dash-ins="' . $tot . '" data-dash-extra="' . $asist . '" data-dash-pag="0" data-dash-skip-pago="1"';
};

$dashAttrsPagosRow = static function (array $fila) use ($dashSlugMinisterio) {
    $slug = $dashSlugMinisterio($fila['Ministerio'] ?? '');
    $i1 = (int)($fila['Inscritos_Nivel_1'] ?? 0);
    $i2 = (int)($fila['Inscritos_Nivel_2'] ?? 0);
    $i3 = (int)($fila['Inscritos_Nivel_3'] ?? 0);
    $p1 = (int)($fila['Pagados_Nivel_1'] ?? 0);
    $p2 = (int)($fila['Pagados_Nivel_2'] ?? 0);
    $p3 = (int)($fila['Pagados_Nivel_3'] ?? 0);
    $totPag = (int)($fila['Pagados'] ?? 0);
    $totIns = (int)($fila['Inscritos'] ?? 0);
    return ' data-dash-row="1" data-dash-profile="cap-pagos" data-dash-min="' . htmlspecialchars($slug, ENT_QUOTES, 'UTF-8')
        . '" data-dash-h="' . $i1 . '" data-dash-m="' . $i2 . '" data-dash-j="' . $i3 . '" data-dash-t="0"'
        . '" data-dash-pag-h="' . $p1 . '" data-dash-pag-m="' . $p2 . '" data-dash-pag-j="' . $p3 . '" data-dash-pag-t="0"'
        . '" data-dash-ins="' . $totIns . '" data-dash-extra="' . $totPag . '" data-dash-pag="' . $totPag . '"';
};

$totIns = ['n1' => 0, 'n2' => 0, 'n3' => 0, 'total' => 0, 'asist' => 0];
foreach ($tablaInscritos as $fila) {
    $totIns['n1'] += (int)($fila['nivel_1'] ?? 0);
    $totIns['n2'] += (int)($fila['nivel_2'] ?? 0);
    $totIns['n3'] += (int)($fila['nivel_3'] ?? 0);
    $totIns['total'] += (int)($fila['total'] ?? 0);
    $totIns['asist'] += (int)($fila['asistencias_reales'] ?? 0);
}

$totPag = ['n1' => 0, 'n2' => 0, 'n3' => 0, 'inscritos' => 0, 'pagados' => 0, 'pag_n1' => 0, 'pag_n2' => 0, 'pag_n3' => 0];
foreach ($tablaPagos as $fila) {
    $totPag['n1'] += (int)($fila['Inscritos_Nivel_1'] ?? 0);
    $totPag['n2'] += (int)($fila['Inscritos_Nivel_2'] ?? 0);
    $totPag['n3'] += (int)($fila['Inscritos_Nivel_3'] ?? 0);
    $totPag['inscritos'] += (int)($fila['Inscritos'] ?? 0);
    $totPag['pagados'] += (int)($fila['Pagados'] ?? 0);
    $totPag['pag_n1'] += (int)($fila['Pagados_Nivel_1'] ?? 0);
    $totPag['pag_n2'] += (int)($fila['Pagados_Nivel_2'] ?? 0);
    $totPag['pag_n3'] += (int)($fila['Pagados_Nivel_3'] ?? 0);
}
?>

<div class="dash-card uv-encuentro-kpis cap-dash-kpis">
    <h4 class="section-title" style="margin-bottom:6px;">Capacitación Destino — resumen</h4>
    <p class="uv-dash-kpi-note">
        Inscripciones activas en los programas <strong>Nivel 1</strong>, <strong>Nivel 2</strong> y <strong>Nivel 3</strong>
        (misma base que Consolidar).
    </p>
    <div class="uv-kpi-row uv-kpi-row-encuentro">
        <div class="uv-kpi uv-kpi-total">
            <span>Total inscripciones Cap.</span>
            <strong><?= $totalInscripcionesCap ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-ok">
            <span>Ministerios con inscritos</span>
            <strong><?= count($tablaInscritos) ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-warn">
            <span>Con asistencia registrada</span>
            <strong><?= $totIns['asist'] ?></strong>
        </div>
    </div>
</div>

<p class="uv-dash-intro uv-dash-intro-compact">
    <strong><?= htmlspecialchars($etiquetaMes) ?> <?= $anioRep ?></strong>
    · Periodo del tablero: <?= htmlspecialchars($fechaIni) ?> – <?= htmlspecialchars($fechaFin) ?>
    <span class="uv-dash-alineacion-inline">
        <a href="<?= PUBLIC_URL ?>index.php?url=programas/consolidar&amp;insc_programa=capacitacion_destino" target="_blank" rel="noopener">Consolidar Cap.</a>
        ·
        <a href="<?= PUBLIC_URL ?>index.php?url=escuelas_formacion/pagos/enviar" target="_blank" rel="noopener">Pagos Cap.</a>
    </span>
</p>

<div class="uv-dash-tables-grid">
<div class="dash-card uv-dash-table-card">
    <h4 class="section-title">Inscripciones por ministerio</h4>
    <small class="uv-dash-table-note">Inscritos en Capacitación Destino por nivel (1, 2 y 3).</small>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-dash-filterable"
               id="tablaCapInscripciones"
               data-dash-gen-mode="n123" data-dash-enable-pago="0"
               data-dash-global-ministerio-id="<?= htmlspecialchars((string)($filtroMinisterio ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <colgroup>
                <col class="uv-col-min">
                <col class="uv-col-num" span="3">
                <col class="uv-col-num-wide">
            </colgroup>
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th class="uv-num" data-dash-seg="h">Nivel 1</th>
                    <th class="uv-num" data-dash-seg="m">Nivel 2</th>
                    <th class="uv-num" data-dash-seg="j">Nivel 3</th>
                    <th class="uv-num">Total</th>
                    <th class="uv-num">Asistencias reales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tablaInscritos)): ?>
                    <?php foreach ($tablaInscritos as $fila): ?>
                        <tr<?= $dashAttrsInscritosRow($fila) ?>>
                            <td><?= htmlspecialchars((string)($fila['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td class="uv-num" data-dash-seg="h"><?= (int)($fila['nivel_1'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="m"><?= (int)($fila['nivel_2'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="j"><?= (int)($fila['nivel_3'] ?? 0) ?></td>
                            <td class="uv-num uv-num-total uv-dash-col-total"><?= (int)($fila['total'] ?? 0) ?></td>
                            <td class="uv-num uv-dash-col-extra"><?= (int)($fila['asistencias_reales'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No hay inscripciones de Capacitación Destino para este filtro.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr>
                    <th>TOTAL</th>
                    <th class="uv-num" data-dash-seg="h"><?= $totIns['n1'] ?></th>
                    <th class="uv-num" data-dash-seg="m"><?= $totIns['n2'] ?></th>
                    <th class="uv-num" data-dash-seg="j"><?= $totIns['n3'] ?></th>
                    <th class="uv-num uv-dash-col-total"><?= $totIns['total'] ?></th>
                    <th class="uv-num uv-dash-col-extra"><?= $totIns['asist'] ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="dash-card uv-dash-table-card">
    <h4 class="section-title">Pagos reales por ministerio</h4>
    <small class="uv-dash-table-note">Inscritos y pagos por nivel; la última columna es el total con pago registrado.</small>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table js-dash-filterable"
               id="tablaCapPagos"
               data-dash-gen-mode="n123" data-dash-enable-pago="1"
               data-dash-global-ministerio-id="<?= htmlspecialchars((string)($filtroMinisterio ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <colgroup>
                <col class="uv-col-min">
                <col class="uv-col-num" span="3">
                <col class="uv-col-num-wide">
            </colgroup>
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th class="uv-num" data-dash-seg="h">Nivel 1</th>
                    <th class="uv-num" data-dash-seg="m">Nivel 2</th>
                    <th class="uv-num" data-dash-seg="j">Nivel 3</th>
                    <th class="uv-num">Inscritos</th>
                    <th class="uv-num">Pagos reales</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tablaPagos)): ?>
                    <?php foreach ($tablaPagos as $fila): ?>
                        <tr<?= $dashAttrsPagosRow($fila) ?>>
                            <td><?= htmlspecialchars((string)($fila['Ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td class="uv-num" data-dash-seg="h"><?= (int)($fila['Inscritos_Nivel_1'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="m"><?= (int)($fila['Inscritos_Nivel_2'] ?? 0) ?></td>
                            <td class="uv-num" data-dash-seg="j"><?= (int)($fila['Inscritos_Nivel_3'] ?? 0) ?></td>
                            <td class="uv-num uv-num-total uv-dash-col-total"><?= (int)($fila['Inscritos'] ?? 0) ?></td>
                            <td class="uv-num uv-num-pag uv-dash-col-extra"><?= (int)($fila['Pagados'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No hay datos de pago para este filtro.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot class="js-dash-tfoot">
                <tr>
                    <th>TOTAL</th>
                    <th class="uv-num" data-dash-seg="h"><?= $totPag['n1'] ?></th>
                    <th class="uv-num" data-dash-seg="m"><?= $totPag['n2'] ?></th>
                    <th class="uv-num" data-dash-seg="j"><?= $totPag['n3'] ?></th>
                    <th class="uv-num uv-dash-col-total"><?= $totPag['inscritos'] ?></th>
                    <th class="uv-num uv-dash-col-extra"><?= $totPag['pagados'] ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</div>
