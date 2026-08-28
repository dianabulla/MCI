<?php
/**
 * Dashboard Capacitación Destino: inscripciones y pagos por ministerio (niveles 1–3).
 *
 * @var array<int, array<string, mixed>> $tablaCapModoConsolidar
 * @var array<int, array<string, mixed>> $tablaPagosCap
 * @var int $total_inscripciones_cap_periodo
 */
$tablaAvanceRedes = is_array($tabla_cap_avance_redes ?? null) ? $tabla_cap_avance_redes : [];
$tablaInscritos = is_array($tablaCapModoConsolidar ?? null) ? $tablaCapModoConsolidar : (array)($tabla_cap_modo_consolidar ?? []);
$tablaPagos = is_array($tablaPagosCap ?? null) ? $tablaPagosCap : (array)($tabla_pagos_cap ?? []);
$totalInscripcionesCap = (int)($totalInscripcionesCapPeriodo ?? (int)($total_inscripciones_cap_periodo ?? 0));
$totalAsistieronCap = (int)($totalAsistenciasCap ?? (int)($total_asistencias_cap ?? 0));
$totalPasaronCap = (int)($total_pasaron_cap ?? 0);
$fechaIni = (string)($fechaInicioMes ?? '');
$fechaFin = (string)($fechaFinMes ?? '');
$anioRep = (int)($anio ?? date('Y'));
$etiquetaPeriodoCap = trim((string)($etiqueta_periodo_uv ?? ''));
if ($etiquetaPeriodoCap === '') {
    $etiquetaPeriodoCap = 'Periodo seleccionado';
}

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
        . ' data-dash-ins="' . $tot . '" data-dash-extra="' . $asist . '" data-dash-pag="0" data-dash-skip-pago="1"';
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
        . ' data-dash-pag-h="' . $p1 . '" data-dash-pag-m="' . $p2 . '" data-dash-pag-j="' . $p3 . '" data-dash-pag-t="0"'
        . ' data-dash-ins="' . $totIns . '" data-dash-extra="' . $totPag . '" data-dash-pag="' . $totPag . '"';
};

if (!isset($totalAsistenciasCap)) {
    $totalAsistenciasCap = (int)($total_asistencias_cap ?? 0);
}

$renderFiltrosTablaCap = static function (string $tableId, bool $filtroPagosActivo): void {
    $tid = htmlspecialchars($tableId, ENT_QUOTES, 'UTF-8');
    ?>
    <div class="dash-table-tool-row js-dash-filtros-estaticos" data-dash-table-id="<?= $tid ?>">
        <div class="dash-filtros-panel" style="flex:1;min-width:0;">
            <p class="dash-filter-hint">Filtros: ministerio y nivel (1 / 2 / 3). Sin marcar niveles = ver todos.</p>
            <div class="dash-inline-filters">
                <div class="group">
                    <label for="dash-min-<?= $tid ?>">Ministerio</label>
                    <select id="dash-min-<?= $tid ?>" class="js-dash-sel-min" data-dash-table="<?= $tid ?>">
                        <option value="">Todos los ministerios</option>
                    </select>
                </div>
                <div class="group">
                    <span class="dash-group-label">Nivel</span>
                    <div class="dash-segment-checks">
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="h" data-dash-table="<?= $tid ?>"> Nivel 1</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="m" data-dash-table="<?= $tid ?>"> Nivel 2</label>
                        <label class="dash-segment-opt"><input type="checkbox" class="js-dash-seg" value="j" data-dash-table="<?= $tid ?>"> Nivel 3</label>
                    </div>
                    <div class="dash-segment-hint">Marca uno o más: oculta columnas y recalcula totales.</div>
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

$etiquetaRedCap = static function ($red) {
    if ($red === 'hombres') {
        return 'Red Hombres';
    }
    if ($red === 'mujeres') {
        return 'Red Mujeres';
    }
    return 'Sin red';
};

$totAvance = ['inscritos' => 0, 'asistieron' => 0, 'pasaron' => 0, 'h_ins' => 0, 'h_as' => 0, 'h_pa' => 0, 'm_ins' => 0, 'm_as' => 0, 'm_pa' => 0];
foreach ($tablaAvanceRedes as $filaAv) {
    $ins = (int)($filaAv['inscritos'] ?? 0);
    $as = (int)($filaAv['asistieron'] ?? 0);
    $pa = (int)($filaAv['pasaron'] ?? 0);
    $totAvance['inscritos'] += $ins;
    $totAvance['asistieron'] += $as;
    $totAvance['pasaron'] += $pa;
    $redAv = (string)($filaAv['red'] ?? '');
    if ($redAv === 'hombres') {
        $totAvance['h_ins'] += $ins;
        $totAvance['h_as'] += $as;
        $totAvance['h_pa'] += $pa;
    } elseif ($redAv === 'mujeres') {
        $totAvance['m_ins'] += $ins;
        $totAvance['m_as'] += $as;
        $totAvance['m_pa'] += $pa;
    }
}
if ($totAvance['inscritos'] > 0) {
    $totalInscripcionesCap = $totAvance['inscritos'];
    $totalAsistieronCap = $totAvance['asistieron'];
    $totalPasaronCap = $totAvance['pasaron'];
}
?>

<div class="dash-card uv-encuentro-kpis cap-dash-kpis">
    <h4 class="section-title" style="margin-bottom:6px;">Capacitación Destino — avance por red</h4>
    <p class="uv-dash-kpi-note">
        Periodo: <strong><?= htmlspecialchars($etiquetaPeriodoCap) ?></strong>
        (<?= htmlspecialchars($fechaIni) ?> a <?= htmlspecialchars($fechaFin) ?>).
        <strong>Asistieron</strong> = al menos una clase marcada en la planilla del nivel.
        <strong>Pasaron de nivel</strong> = 70% de las 10 clases y 70% de las evaluaciones del nivel aprobadas (último intento).
    </p>
    <div class="uv-kpi-row uv-kpi-row-encuentro">
        <div class="uv-kpi uv-kpi-total">
            <span>Inscritos</span>
            <strong><?= $totalInscripcionesCap ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-ok">
            <span>Asistieron a clase</span>
            <strong><?= $totalAsistieronCap ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-warn">
            <span>Pasaron al siguiente nivel</span>
            <strong><?= $totalPasaronCap ?></strong>
        </div>
    </div>
    <div class="uv-kpi-row" style="margin-top:10px;">
        <div class="uv-kpi">
            <span>Red Hombres</span>
            <strong><?= (int)$totAvance['h_ins'] ?></strong>
            <em class="uv-kpi-pct">Asist. <?= (int)$totAvance['h_as'] ?> · Pasan <?= (int)$totAvance['h_pa'] ?></em>
        </div>
        <div class="uv-kpi">
            <span>Red Mujeres</span>
            <strong><?= (int)$totAvance['m_ins'] ?></strong>
            <em class="uv-kpi-pct">Asist. <?= (int)$totAvance['m_as'] ?> · Pasan <?= (int)$totAvance['m_pa'] ?></em>
        </div>
    </div>
</div>

<p class="uv-dash-intro uv-dash-intro-compact">
    <strong><?= htmlspecialchars($etiquetaPeriodoCap) ?> <?= $anioRep ?></strong>
    · Inscripciones con fecha de registro en el periodo
    <span class="uv-dash-alineacion-inline">
        <a href="<?= PUBLIC_URL ?>index.php?url=programas/consolidar&amp;insc_programa=capacitacion_destino" target="_blank" rel="noopener">Consolidar Cap.</a>
        ·
        <a href="<?= PUBLIC_URL ?>index.php?url=escuelas_formacion/pagos/enviar" target="_blank" rel="noopener">Pagos Cap.</a>
    </span>
</p>

<div class="dash-card uv-dash-table-card" style="margin-bottom:16px;">
    <h4 class="section-title">Inscritos, asistencia y avance por red</h4>
    <small class="uv-dash-table-note">Una fila por ministerio y red. Usa los botones para ver solo hombres o solo mujeres.</small>
    <div class="rpt-ganados-semana-filtros cap-red-filtros" role="group" aria-label="Filtrar por red" style="margin:10px 0 8px;">
        <button type="button" class="rpt-ganados-filtro is-active js-cap-red-filtro" data-red="todos">Todos</button>
        <button type="button" class="rpt-ganados-filtro js-cap-red-filtro" data-red="hombres">Red Hombres</button>
        <button type="button" class="rpt-ganados-filtro js-cap-red-filtro" data-red="mujeres">Red Mujeres</button>
    </div>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table" id="tablaCapAvanceRedes">
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th>Red</th>
                    <th class="uv-num">Inscritos</th>
                    <th class="uv-num">Asistieron</th>
                    <th class="uv-num">Pasaron de nivel</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tablaAvanceRedes)): ?>
                    <?php foreach ($tablaAvanceRedes as $filaAv): ?>
                        <tr class="js-cap-avance-row" data-cap-red="<?= htmlspecialchars((string)($filaAv['red'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td><?= htmlspecialchars((string)($filaAv['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td><?= htmlspecialchars($etiquetaRedCap((string)($filaAv['red'] ?? ''))) ?></td>
                            <td class="uv-num js-cap-col-ins"><?= (int)($filaAv['inscritos'] ?? 0) ?></td>
                            <td class="uv-num js-cap-col-as"><?= (int)($filaAv['asistieron'] ?? 0) ?></td>
                            <td class="uv-num js-cap-col-pa"><?= (int)($filaAv['pasaron'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No hay inscripciones de Capacitación Destino para este periodo.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">TOTAL</th>
                    <th class="uv-num" id="capAvanceTotIns"><?= (int)$totAvance['inscritos'] ?></th>
                    <th class="uv-num" id="capAvanceTotAs"><?= (int)$totAvance['asistieron'] ?></th>
                    <th class="uv-num" id="capAvanceTotPa"><?= (int)$totAvance['pasaron'] ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="uv-dash-tables-grid">
<div class="dash-card uv-dash-table-card">
    <h4 class="section-title">Inscripciones por ministerio</h4>
    <small class="uv-dash-table-note">Inscritos en Capacitación Destino por nivel (1, 2 y 3).</small>
    <?php $renderFiltrosTablaCap('tablaCapInscripciones', false); ?>
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
    <?php $renderFiltrosTablaCap('tablaCapPagos', true); ?>
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

<style>
.cap-red-filtros { display:flex; flex-wrap:wrap; gap:8px; }
.cap-red-filtros .rpt-ganados-filtro {
    border: 1px solid #cfdff5;
    background: #fff;
    color: #1f3f74;
    border-radius: 999px;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
}
.cap-red-filtros .rpt-ganados-filtro.is-active {
    background: #1f3f74;
    border-color: #1f3f74;
    color: #fff;
}
</style>
<script>
(function () {
    const botones = document.querySelectorAll('.js-cap-red-filtro');
    const filas = document.querySelectorAll('#tablaCapAvanceRedes .js-cap-avance-row');
    const totIns = document.getElementById('capAvanceTotIns');
    const totAs = document.getElementById('capAvanceTotAs');
    const totPa = document.getElementById('capAvanceTotPa');
    if (!botones.length) {
        return;
    }

    function aplicarFiltroRed(red) {
        let ins = 0;
        let asis = 0;
        let pasa = 0;
        filas.forEach(function (fila) {
            const redFila = String(fila.getAttribute('data-cap-red') || '');
            const mostrar = red === 'todos' || redFila === red;
            fila.style.display = mostrar ? '' : 'none';
            if (mostrar) {
                ins += parseInt((fila.querySelector('.js-cap-col-ins') || {}).textContent || '0', 10) || 0;
                asis += parseInt((fila.querySelector('.js-cap-col-as') || {}).textContent || '0', 10) || 0;
                pasa += parseInt((fila.querySelector('.js-cap-col-pa') || {}).textContent || '0', 10) || 0;
            }
        });
        if (totIns) totIns.textContent = String(ins);
        if (totAs) totAs.textContent = String(asis);
        if (totPa) totPa.textContent = String(pasa);
    }

    botones.forEach(function (boton) {
        boton.addEventListener('click', function () {
            const red = String(boton.getAttribute('data-red') || 'todos');
            botones.forEach(function (otro) {
                otro.classList.toggle('is-active', otro === boton);
            });
            aplicarFiltroRed(red);
        });
    });
})();
</script>
