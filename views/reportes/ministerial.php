<?php include VIEWS . '/layout/header.php'; ?>

<?php
$anio             = (int)($anio ?? date('Y'));
$tablasReportes   = $tablas_reportes ?? [];
$detallesTablas   = $detalles_tablas ?? [];
$tablaGanancia    = $tabla_ganancia   ?? [];
$detallesGanancia = $detalles_ganancia ?? [];
$tablaConsolidarMinisterio = $tabla_consolidar_ministerio ?? [];
$detallesConsolidarMinisterio = $detalles_consolidar_ministerio ?? [];

/**
 * Tabla GANANCIA POR MINISTERIO: filas = ministerios, columnas = meses × (Célula|Iglesia)
 */
$renderTablaGanancia = static function(array $tabla) {
    $rows    = $tabla['rows']    ?? [];
    $meses   = $tabla['meses']   ?? [];
    $totales = $tabla['totales'] ?? [];
    if (empty($rows)) {
        echo '<div class="card report-card mb-4"><div class="report-card__head"><h3>GANANCIA DE ALMAS POR MINISTERIO</h3></div><div class="report-empty-state">Sin datos para este filtro.</div></div>';
        return;
    }
    $tooltipsSiglas = [
        'G.C' => 'Ganados en célula',
        'G.I' => 'Ganados en iglesia',
        'F.V' => 'Fonovisitas',
        'V' => 'Visitas',
    ];
    ?>
    <div class="card report-card mb-4">
        <div class="report-card__head">
            <h3>GANANCIA DE ALMAS POR MINISTERIO — GANAR 2026</h3>
            <button type="button" class="btn-chart-toggle js-toggle-chart" data-chart="ganancia">Ver gráfica</button>
        </div>
        <div class="table-container reporte-metas-wrap">
            <table class="data-table rpt-ganancia-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="col-num-sm">N°</th>
                        <th rowspan="2" class="col-ministerio">MINISTERIO</th>
                        <?php foreach ($meses as $m => $label): ?>
                            <th colspan="2" class="col-mes-group"><?= htmlspecialchars($label) ?></th>
                        <?php endforeach; ?>
                        <th rowspan="2" class="col-sub col-anual-head">TOTAL</th>
                    </tr>
                    <tr>
                        <?php foreach ($meses as $m => $label): ?>
                            <th class="col-sub" title="<?= htmlspecialchars($tooltipsSiglas['G.C'], ENT_QUOTES, 'UTF-8') ?>">G.C</th>
                            <th class="col-sub" title="<?= htmlspecialchars($tooltipsSiglas['G.I'], ENT_QUOTES, 'UTF-8') ?>">G.I</th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $n = 1; foreach ($rows as $row):
                        $ministerio = (string)($row['ministerio'] ?? '');
                    ?>
                    <tr>
                        <td class="col-num-sm"><?= $n++ ?></td>
                        <td class="col-ministerio-label"><?= htmlspecialchars($ministerio) ?></td>
                        <?php foreach ($meses as $m => $label):
                            $vc = (int)($row['meses'][$m]['celula']  ?? 0);
                            $vi = (int)($row['meses'][$m]['iglesia'] ?? 0);
                        ?>
                            <td><?php if ($vc > 0): ?><button type="button" class="report-link-btn js-open-ganancia" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="celula"  data-mes="<?= $m ?>"><?= $vc ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                            <td><?php if ($vi > 0): ?><button type="button" class="report-link-btn js-open-ganancia" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="iglesia" data-mes="<?= $m ?>"><?= $vi ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                        <?php endforeach; ?>
                        <?php $at = (int)($row['anual']['total'] ?? 0); ?>
                        <td><?php if ($at > 0): ?><button type="button" class="report-link-btn js-open-ganancia bold" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="total" data-mes="0"><?= $at ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="rpt-total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <?php foreach ($meses as $m => $label):
                            $tc = (int)($totales['meses'][$m]['celula']  ?? 0);
                            $ti = (int)($totales['meses'][$m]['iglesia'] ?? 0);
                        ?>
                            <td><?php if ($tc > 0): ?><button type="button" class="report-link-btn js-open-ganancia bold" data-ministerio="__todos__" data-col="celula"  data-mes="<?= $m ?>"><?= $tc ?></button><?php else: ?>0<?php endif; ?></td>
                            <td><?php if ($ti > 0): ?><button type="button" class="report-link-btn js-open-ganancia bold" data-ministerio="__todos__" data-col="iglesia" data-mes="<?= $m ?>"><?= $ti ?></button><?php else: ?>0<?php endif; ?></td>
                        <?php endforeach; ?>
                        <?php $tat = (int)($totales['anual']['total'] ?? 0); ?>
                        <td><?php if ($tat > 0): ?><button type="button" class="report-link-btn js-open-ganancia bold" data-ministerio="__todos__" data-col="total" data-mes="0"><?= $tat ?></button><?php else: ?>0<?php endif; ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="rpt-chart-wrap" id="chart-wrap-ganancia" style="display:none;">
            <div id="chart-ganancia" class="rpt-chart"></div>
        </div>
    </div>
    <?php
};

$renderTabla = static function(string $tablaKey, array $tabla, array $headers) {
    $rows    = $tabla['rows']    ?? [];
    $totales = $tabla['totales'] ?? [];
    $titulo  = $tabla['titulo']  ?? $tablaKey;
    $cols    = array_keys($headers);
    $tooltipsSiglas = [
        'GI' => 'Ganados en iglesia',
        'GC' => 'Ganados en célula',
        'FV' => 'Fonovisitas',
        'V' => 'Visitas',
        'U.V' => 'Universidad de la Vida',
    ];
    ?>
    <div class="card report-card mb-4">
        <div class="report-card__head">
            <h3><?= htmlspecialchars($titulo) ?></h3>
            <button type="button" class="btn-chart-toggle js-toggle-chart" data-chart="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>">Ver gráfica</button>
        </div>
        <div class="table-container reporte-metas-wrap">
            <table class="data-table rpt-min-table">
                <thead>
                    <tr>
                        <th class="col-mes">MES</th>
                        <?php foreach ($headers as $col => $label):
                            $labelTexto = (string)$label;
                            $tooltip = $tooltipsSiglas[$labelTexto] ?? '';
                        ?>
                            <th class="col-num"<?= $tooltip !== '' ? ' title="' . htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8') . '"' : '' ?>><?= htmlspecialchars($labelTexto) ?></th>
                        <?php endforeach; ?>
                        <th class="col-num col-total">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($m = 1; $m <= 12; $m++):
                        $row = $rows[$m] ?? [];
                    ?>
                    <tr>
                        <td class="col-mes-label"><?= htmlspecialchars((string)($row['mes'] ?? '')) ?></td>
                        <?php foreach ($cols as $col): ?>
                            <?php $val = (int)($row[$col] ?? 0); ?>
                            <td>
                                <?php if ($val > 0): ?>
                                    <button type="button"
                                        class="report-link-btn js-open-modal"
                                        data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>"
                                        data-col="<?= htmlspecialchars($col, ENT_QUOTES) ?>"
                                        data-mes="<?= $m ?>">
                                        <?= $val ?>
                                    </button>
                                <?php else: ?>
                                    <span class="rpt-cero">&#8212;</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php $total = (int)($row['total'] ?? 0); ?>
                        <td class="col-total-val">
                            <?php if ($total > 0): ?>
                                <button type="button"
                                    class="report-link-btn js-open-modal bold"
                                    data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>"
                                    data-col="total"
                                    data-mes="<?= $m ?>">
                                    <?= $total ?>
                                </button>
                            <?php else: ?>
                                <span class="rpt-cero">&#8212;</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </tbody>
                <tfoot>
                    <tr class="rpt-total-row">
                        <td><strong>TOTAL</strong></td>
                        <?php foreach ($cols as $col): ?>
                            <?php $val = (int)($totales[$col] ?? 0); ?>
                            <td>
                                <?php if ($val > 0): ?>
                                    <button type="button"
                                        class="report-link-btn js-open-modal bold"
                                        data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>"
                                        data-col="<?= htmlspecialchars($col, ENT_QUOTES) ?>"
                                        data-mes="0">
                                        <?= $val ?>
                                    </button>
                                <?php else: ?>&#8212;<?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php $totTotal = (int)($totales['total'] ?? 0); ?>
                        <td>
                            <?php if ($totTotal > 0): ?>
                                <button type="button"
                                    class="report-link-btn js-open-modal bold"
                                    data-tabla="<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>"
                                    data-col="total"
                                    data-mes="0">
                                    <?= $totTotal ?>
                                </button>
                            <?php else: ?>&#8212;<?php endif; ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="rpt-chart-wrap" id="chart-wrap-<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>" style="display:none;">
            <div id="chart-<?= htmlspecialchars($tablaKey, ENT_QUOTES) ?>" class="rpt-chart"></div>
        </div>
    </div>
    <?php
};

$renderTablaConsolidarMinisterio = static function(array $tabla) {
    $rows = $tabla['rows'] ?? [];
    $totales = $tabla['totales'] ?? [];
    if (empty($rows)) {
        echo '<div class="card report-card mb-4"><div class="report-card__head"><h3>CONSOLIDAR POR MINISTERIO</h3></div><div class="report-empty-state">Sin datos para este filtro.</div></div>';
        return;
    }
    ?>
    <div class="card report-card mb-4">
        <div class="report-card__head">
            <h3>CONSOLIDAR POR MINISTERIO</h3>
            <button type="button" class="btn-chart-toggle js-toggle-chart" data-chart="consolidar-min">Ver gráfica</button>
        </div>
        <div class="table-container reporte-metas-wrap">
            <table class="data-table rpt-consolidar-min-table">
                <thead>
                    <tr>
                        <th class="col-num-sm">N°</th>
                        <th class="col-ministerio">MINISTERIO</th>
                        <th class="col-num">U.V</th>
                        <th class="col-num">E</th>
                        <th class="col-num">B</th>
                        <th class="col-num col-total">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $n = 1; foreach ($rows as $row):
                        $ministerio = (string)($row['ministerio'] ?? '');
                        $uv = (int)($row['uv'] ?? 0);
                        $e = (int)($row['e'] ?? 0);
                        $b = (int)($row['b'] ?? 0);
                        $total = (int)($row['total'] ?? 0);
                    ?>
                    <tr>
                        <td class="col-num-sm"><?= $n++ ?></td>
                        <td class="col-ministerio-label"><?= htmlspecialchars($ministerio) ?></td>
                        <td><?php if ($uv > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="uv"><?= $uv ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                        <td><?php if ($e > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="e"><?= $e ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                        <td><?php if ($b > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="b"><?= $b ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                        <td><?php if ($total > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min bold" data-ministerio="<?= htmlspecialchars($ministerio, ENT_QUOTES) ?>" data-col="total"><?= $total ?></button><?php else: ?><span class="rpt-cero">0</span><?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="rpt-total-row">
                        <td colspan="2"><strong>TOTAL</strong></td>
                        <td><?php $tuv = (int)($totales['uv'] ?? 0); ?><?php if ($tuv > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min bold" data-ministerio="__todos__" data-col="uv"><?= $tuv ?></button><?php else: ?>0<?php endif; ?></td>
                        <td><?php $te = (int)($totales['e'] ?? 0); ?><?php if ($te > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min bold" data-ministerio="__todos__" data-col="e"><?= $te ?></button><?php else: ?>0<?php endif; ?></td>
                        <td><?php $tb = (int)($totales['b'] ?? 0); ?><?php if ($tb > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min bold" data-ministerio="__todos__" data-col="b"><?= $tb ?></button><?php else: ?>0<?php endif; ?></td>
                        <td><?php $tt = (int)($totales['total'] ?? 0); ?><?php if ($tt > 0): ?><button type="button" class="report-link-btn js-open-consolidar-min bold" data-ministerio="__todos__" data-col="total"><?= $tt ?></button><?php else: ?>0<?php endif; ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="rpt-chart-wrap" id="chart-wrap-consolidar-min" style="display:none;">
            <div id="chart-consolidar-min" class="rpt-chart"></div>
        </div>
    </div>
    <?php
};
?>

<!-- ENCABEZADO -->
<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:18px;">
    <div>
        <h2 style="margin:0;">Reporte <?= $anio ?> &mdash; GANAR 2026</h2>
        <small style="color:#637087;">Haz clic en cualquier n&uacute;mero para ver el listado de personas.</small>
    </div>
    <a href="<?= PUBLIC_URL ?>index.php?url=reportes" class="btn btn-secondary">&larr; Volver a Reportes</a>
</div>

<!-- FILTROS -->
<div class="card report-card mb-4">
    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="filters-inline" style="padding:14px;flex-wrap:wrap;gap:12px;">
        <input type="hidden" name="url" value="reportes/ministerial">
        <div class="form-group" style="margin:0;">
            <label for="anio">A&ntilde;o</label>
            <input type="number" min="2020" max="<?= (int)date('Y') + 1 ?>" id="anio" name="anio"
                   class="form-control" value="<?= $anio ?>" style="width:90px;">
        </div>
        <div class="form-group" style="margin:0;">
            <label for="filtro_ministerio">Ministerio</label>
            <select id="filtro_ministerio" name="ministerio" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($ministerios_disponibles ?? []) as $min): ?>
                    <option value="<?= (int)$min['Id_Ministerio'] ?>"
                        <?= ((string)($filtro_ministerio ?? '') === (string)$min['Id_Ministerio']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$min['Nombre_Ministerio']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="filtro_lider">L&iacute;der</label>
            <select id="filtro_lider" name="lider" class="form-control">
                <option value="">Todos</option>
                <?php foreach (($lideres_disponibles ?? []) as $lider): ?>
                    <option value="<?= (int)$lider['Id_Persona'] ?>"
                        <?= ((string)($filtro_lider ?? '') === (string)$lider['Id_Persona']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$lider['Nombre_Completo']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group" style="margin:0;">
            <label for="filtro_celula">C&eacute;lula</label>
            <select id="filtro_celula" name="celula" class="form-control">
                <option value="">Todas</option>
                <?php foreach (($celulas_disponibles ?? []) as $cel): ?>
                    <option value="<?= (int)$cel['Id_Celula'] ?>"
                        <?= ((string)($filtro_celula ?? '') === (string)$cel['Id_Celula']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$cel['Nombre_Celula']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filters-actions" style="margin:0;">
            <button type="submit" class="btn btn-primary">Aplicar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=reportes/ministerial&anio=<?= $anio ?>" class="btn btn-secondary">Resetear</a>
        </div>
    </form>
</div>

<!-- TABLA GANANCIA POR MINISTERIO (GANAR 2026) -->
<?php $renderTablaGanancia($tablaGanancia); ?>

<!-- TABLA CONSOLIDAR POR MINISTERIO -->
<?php $renderTablaConsolidarMinisterio($tablaConsolidarMinisterio); ?>

<!-- TABLAS EN GRID 2x2 -->
<div class="rpt-grid">

    <!-- GANAR: GI | GC | FV | V -->
    <?php $renderTabla('ganar', $tablasReportes['ganar'] ?? [], [
        'gi' => 'GI',
        'gc' => 'GC',
        'fv' => 'FV',
        'v'  => 'V',
    ]); ?>

    <!-- CONSOLIDAR: U.V | E | B -->
    <?php $renderTabla('consolidar', $tablasReportes['consolidar'] ?? [], [
        'uv' => 'U.V',
        'e'  => 'E',
        'b'  => 'B',
    ]); ?>

    <!-- DISCIPULAR: CD-M1-2 | CD-M3-4 | CD-M5-6 -->
    <?php $renderTabla('discipular', $tablasReportes['discipular'] ?? [], [
        'cdm12' => 'CD-M1-2',
        'cdm34' => 'CD-M3-4',
        'cdm56' => 'CD-M5-6',
    ]); ?>

    <!-- ENVIAR: # CELULAS -->
    <?php $renderTabla('enviar', $tablasReportes['enviar'] ?? [], [
        'celulas' => '# CELULAS',
    ]); ?>

</div>

<!-- MODAL -->
<div id="rptDetalleModal" class="celula-modal" aria-hidden="true">
    <div class="celula-modal__overlay" data-rpt-close></div>
    <div class="celula-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="rptModalTitle">
        <div class="celula-modal__header">
            <h3 id="rptModalTitle" class="celula-modal__title">Detalle</h3>
            <button type="button" class="celula-modal__close" data-rpt-close aria-label="Cerrar">&times;</button>
        </div>
        <div class="celula-modal__body">
            <p id="rptModalSubtitle" style="margin:0 0 10px;color:#637087;font-size:13px;"></p>
            <div class="table-container">
                <table class="data-table data-table--sm">
                    <thead>
                        <tr>
                            <th>Persona</th>
                            <th>Ministerio</th>
                            <th>L&iacute;der</th>
                            <th>C&eacute;lula</th>
                            <th>Proceso</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody id="rptModalBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function () {
    const detallesTablas  = <?= json_encode($detallesTablas,  JSON_UNESCAPED_UNICODE) ?>;
    const detallesGanancia = <?= json_encode($detallesGanancia, JSON_UNESCAPED_UNICODE) ?>;
    const detallesConsolidarMinisterio = <?= json_encode($detallesConsolidarMinisterio, JSON_UNESCAPED_UNICODE) ?>;
    const tablasReportesData = <?= json_encode($tablasReportes, JSON_UNESCAPED_UNICODE) ?>;
    const tablaGananciaData = <?= json_encode($tablaGanancia, JSON_UNESCAPED_UNICODE) ?>;
    const tablaConsolidarMinisterioData = <?= json_encode($tablaConsolidarMinisterio, JSON_UNESCAPED_UNICODE) ?>;

    const etiquetasCol = {
        ganar:      { gi: 'Ganados en Iglesia', gc: 'Ganados en C\u00e9lula', fv: 'Fonovisitas', v: 'Visitas', total: 'Total' },
        consolidar: { uv: 'Universidad de la Vida', e: 'Encuentro', b: 'Bautismo', total: 'Total' },
        discipular: { cdm12: 'CD Nivel 1-2', cdm34: 'CD Nivel 3-4', cdm56: 'CD Nivel 5-6', total: 'Total' },
        enviar:     { celulas: '# C\u00e9lulas', total: 'Total' },
    };

    const nombresMes = {
        0:'Anual',1:'Enero',2:'Febrero',3:'Marzo',4:'Abril',5:'Mayo',6:'Junio',
        7:'Julio',8:'Agosto',9:'Septiembre',10:'Octubre',11:'Noviembre',12:'Diciembre'
    };

    const modal    = document.getElementById('rptDetalleModal');
    const title    = document.getElementById('rptModalTitle');
    const subtitle = document.getElementById('rptModalSubtitle');
    const body     = document.getElementById('rptModalBody');

    const esc = (v) => String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    function abrirModal(tabla, col, mes) {
        const datos = ((detallesTablas[tabla]||{})[col]||{});
        const filas = mes === 0 ? Object.values(datos).flat() : (datos[mes]||[]);
        const etTabla = (etiquetasCol[tabla]||{})[col] || col;
        title.textContent    = (tabla||'').toUpperCase() + ' \u2014 ' + etTabla;
        subtitle.textContent = (nombresMes[mes]||'') + ' \u00b7 ' + filas.length + ' persona(s)';
        if (!filas.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center" style="padding:18px;color:#637087;">Sin personas para este filtro.</td></tr>';
        } else {
            body.innerHTML = filas.map((f) =>
                '<tr><td>'+esc(f.nombre)+'</td><td>'+esc(f.ministerio)+'</td><td>'+esc(f.lider)+'</td><td>'+esc(f.celula)+'</td><td>'+esc(f.proceso)+'</td><td>'+esc(f.fecha_registro)+'</td></tr>'
            ).join('');
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden','false');
    }

    function cerrarModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden','true');
    }

    document.querySelectorAll('.js-open-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            abrirModal(String(btn.dataset.tabla||''), String(btn.dataset.col||''), parseInt(btn.dataset.mes||'0',10));
        });
    });

    document.querySelectorAll('.js-open-ganancia').forEach((btn) => {
        btn.addEventListener('click', () => {
            const ministerio = String(btn.dataset.ministerio || '');
            const col  = String(btn.dataset.col  || '');
            const mes  = parseInt(btn.dataset.mes || '0', 10);
            let filas;
            if (ministerio === '__todos__') {
                const allMins = Object.values(detallesGanancia);
                const allCols = allMins.map((m) => (m[col] || {}));
                filas = mes === 0
                    ? allCols.flatMap((c) => Object.values(c)).flat()
                    : allCols.flatMap((c) => (c[mes] || []));
            } else {
                const datos = ((detallesGanancia[ministerio] || {})[col] || {});
                filas = mes === 0 ? Object.values(datos).flat() : (datos[mes] || []);
            }
            const etCol = col === 'celula' ? 'Ganados en C\u00e9lula' : col === 'iglesia' ? 'Ganados en Iglesia' : 'Total';
            const etMin = ministerio === '__todos__' ? 'Todos los ministerios' : ministerio;
            title.textContent    = 'GANANCIA \u2014 ' + etMin;
            subtitle.textContent = etCol + ' \u00b7 ' + (nombresMes[mes] || '') + ' \u00b7 ' + filas.length + ' persona(s)';
            if (!filas.length) {
                body.innerHTML = '<tr><td colspan="6" class="text-center" style="padding:18px;color:#637087;">Sin personas para este filtro.</td></tr>';
            } else {
                body.innerHTML = filas.map((f) =>
                    '<tr><td>'+esc(f.nombre)+'</td><td>'+esc(f.ministerio)+'</td><td>'+esc(f.lider)+'</td><td>'+esc(f.celula)+'</td><td>'+esc(f.proceso)+'</td><td>'+esc(f.fecha_registro)+'</td></tr>'
                ).join('');
            }
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('.js-open-consolidar-min').forEach((btn) => {
        btn.addEventListener('click', () => {
            const ministerio = String(btn.dataset.ministerio || '');
            const col = String(btn.dataset.col || '');
            let filas;

            if (ministerio === '__todos__') {
                const allMins = Object.values(detallesConsolidarMinisterio || {});
                const allCols = allMins.map((m) => (m[col] || []));
                filas = allCols.flat();
            } else {
                filas = ((detallesConsolidarMinisterio[ministerio] || {})[col] || []);
            }

            const etiquetaCol = col === 'uv'
                ? 'Universidad de la Vida'
                : (col === 'e' ? 'Encuentro' : (col === 'b' ? 'Bautismo' : 'Total'));
            const etiquetaMin = ministerio === '__todos__' ? 'Todos los ministerios' : ministerio;

            title.textContent = 'CONSOLIDAR POR MINISTERIO — ' + etiquetaCol;
            subtitle.textContent = etiquetaMin + ' · ' + filas.length + ' persona(s)';

            if (!filas.length) {
                body.innerHTML = '<tr><td colspan="6" class="text-center" style="padding:18px;color:#637087;">Sin personas para este filtro.</td></tr>';
            } else {
                body.innerHTML = filas.map((f) =>
                    '<tr><td>' + esc(f.nombre) + '</td><td>' + esc(f.ministerio) + '</td><td>' + esc(f.lider) + '</td><td>' + esc(f.celula) + '</td><td>' + esc(f.proceso) + '</td><td>' + esc(f.fecha_registro) + '</td></tr>'
                ).join('');
            }

            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        });
    });

    document.querySelectorAll('[data-rpt-close]').forEach((el) => el.addEventListener('click', cerrarModal));

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) cerrarModal();
    });

    const chartInstances = {};

    const getMesLabels = () => Array.from({ length: 12 }, (_, i) => (nombresMes[i + 1] || '').substring(0, 3).toUpperCase());

    function getMonthlySeries(tabla, col) {
        const rows = (tabla && tabla.rows) ? tabla.rows : {};
        return Array.from({ length: 12 }, (_, i) => {
            const m = i + 1;
            return parseInt(((rows[m] || {})[col] || 0), 10);
        });
    }

    function buildChartConfig(key) {
        if (key === 'ganancia') {
            const meses = getMesLabels();
            const totMes = ((tablaGananciaData || {}).totales || {}).meses || {};
            const serieGc = Array.from({ length: 12 }, (_, i) => parseInt((((totMes[i + 1] || {}).celula) || 0), 10));
            const serieGi = Array.from({ length: 12 }, (_, i) => parseInt((((totMes[i + 1] || {}).iglesia) || 0), 10));
            return {
                categories: meses,
                series: [
                    { name: 'G.C', data: serieGc },
                    { name: 'G.I', data: serieGi }
                ]
            };
        }

        if (key === 'consolidar-min') {
            const rows = (tablaConsolidarMinisterioData || {}).rows || [];
            return {
                categories: rows.map((r) => String(r.ministerio || 'Sin ministerio')),
                series: [
                    { name: 'U.V', data: rows.map((r) => parseInt(r.uv || 0, 10)) },
                    { name: 'E', data: rows.map((r) => parseInt(r.e || 0, 10)) },
                    { name: 'B', data: rows.map((r) => parseInt(r.b || 0, 10)) }
                ]
            };
        }

        const tabla = (tablasReportesData || {})[key] || {};
        const meses = getMesLabels();
        if (key === 'ganar') {
            return {
                categories: meses,
                series: [
                    { name: 'G.I', data: getMonthlySeries(tabla, 'gi') },
                    { name: 'G.C', data: getMonthlySeries(tabla, 'gc') },
                    { name: 'F.V', data: getMonthlySeries(tabla, 'fv') },
                    { name: 'V', data: getMonthlySeries(tabla, 'v') }
                ]
            };
        }
        if (key === 'consolidar') {
            return {
                categories: meses,
                series: [
                    { name: 'U.V', data: getMonthlySeries(tabla, 'uv') },
                    { name: 'E', data: getMonthlySeries(tabla, 'e') },
                    { name: 'B', data: getMonthlySeries(tabla, 'b') }
                ]
            };
        }
        if (key === 'discipular') {
            return {
                categories: meses,
                series: [
                    { name: 'CD-M1-2', data: getMonthlySeries(tabla, 'cdm12') },
                    { name: 'CD-M3-4', data: getMonthlySeries(tabla, 'cdm34') },
                    { name: 'CD-M5-6', data: getMonthlySeries(tabla, 'cdm56') }
                ]
            };
        }
        return {
            categories: meses,
            series: [{ name: '# Células', data: getMonthlySeries(tabla, 'celulas') }]
        };
    }

    function renderChart(key) {
        if (chartInstances[key]) {
            return;
        }
        const mount = document.getElementById('chart-' + key);
        if (!mount || typeof ApexCharts === 'undefined') {
            return;
        }
        const config = buildChartConfig(key);
        const options = {
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            series: config.series,
            xaxis: {
                categories: config.categories,
                labels: { rotate: -35, trim: false }
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: { formatter: (v) => Math.round(v) }
            },
            dataLabels: { enabled: true },
            legend: { position: 'top' },
            plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
            colors: ['#1e4a89', '#2c6db8', '#4e8bcb', '#6f9ed8']
        };
        chartInstances[key] = new ApexCharts(mount, options);
        chartInstances[key].render();
    }

    document.querySelectorAll('.js-toggle-chart').forEach((btn) => {
        btn.addEventListener('click', () => {
            const key = String(btn.dataset.chart || '');
            const wrap = document.getElementById('chart-wrap-' + key);
            if (!wrap) {
                return;
            }
            const isHidden = wrap.style.display === 'none' || wrap.style.display === '';
            if (isHidden) {
                wrap.style.display = 'block';
                btn.textContent = 'Ocultar gráfica';
                renderChart(key);
            } else {
                wrap.style.display = 'none';
                btn.textContent = 'Ver gráfica';
            }
        });
    });
}());
</script>

<style>
.rpt-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 18px;
}
@media (max-width: 1100px) { .rpt-grid { grid-template-columns: 1fr; } }

.report-card { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,0,0,.08); overflow:hidden; }
.report-card__head { background:#1a3a6b; padding:10px 16px; display:flex; justify-content:space-between; align-items:center; gap:12px; }
.report-card__head h3 { margin:0; color:#fff; font-size:14px; font-weight:800; letter-spacing:.5px; text-transform:uppercase; }
.mb-4 { margin-bottom:18px; }

.btn-chart-toggle {
    border:1px solid rgba(255,255,255,.45);
    background:rgba(255,255,255,.15);
    color:#fff;
    font-size:12px;
    font-weight:700;
    border-radius:7px;
    padding:6px 10px;
    cursor:pointer;
}

.btn-chart-toggle:hover { background:rgba(255,255,255,.25); }

.rpt-chart-wrap {
    border-top:1px solid #e3eaf5;
    padding:12px 14px 6px;
    background:#fafcff;
}

.rpt-chart { width:100%; min-height:280px; }

.rpt-min-table { border-collapse:collapse; width:max-content; min-width:100%; table-layout:auto; }
.rpt-min-table th {
    background:#f5c800; color:#111; font-weight:800; font-size:12px;
    text-align:center; padding:8px 10px; border:1px solid #e0c000; white-space:nowrap;
    line-height:1.35; vertical-align:middle;
}
.rpt-min-table td { text-align:center; padding:7px 10px; font-size:13px; border:1px solid #e9ecef; line-height:1.35; vertical-align:middle; }
.col-mes { text-align:left !important; min-width:84px; }
.col-mes-label {
    text-align:left;
    font-weight:700;
    font-size:12px;
    color:#333;
    background:#fafafa;
    white-space:nowrap;
    word-break:normal;
    overflow-wrap:normal;
    writing-mode:horizontal-tb;
    text-orientation:mixed;
}
.col-num { min-width:64px; }
.col-total { background:#111 !important; color:#f5c800 !important; border-color:#333 !important; }

.rpt-total-row { background:#111; }
.rpt-total-row td { color:#fff; font-weight:800; border-color:#333; }
.reporte-metas-wrap { overflow-x:auto; }

.rpt-ganancia-wrap { overflow-x:auto; margin-bottom:24px; }
.rpt-ganancia-table {
    border-collapse:collapse; width:max-content; min-width:980px; font-size:12px;
    table-layout:auto !important;
}
.rpt-ganancia-table th,
.rpt-ganancia-table td {
    white-space:nowrap !important;
    word-break:normal !important;
    overflow-wrap:normal !important;
}
.rpt-ganancia-table th {
    background:#1e4a89; color:#fff; font-weight:700; text-align:center;
    padding:6px 8px; border:1px solid #16397a;
    line-height:1.3; vertical-align:middle;
}
.rpt-ganancia-table td { text-align:center; padding:6px 8px; border:1px solid #dde4f0; font-size:12px; line-height:1.3; vertical-align:middle; }
.col-ministerio { width:130px; min-width:130px; max-width:130px; text-align:left !important; }
.col-ministerio-label {
    text-align:left !important; font-weight:600; font-size:12px; padding-left:8px !important;
    white-space:normal !important; word-break:keep-all !important; overflow-wrap:break-word !important;
}
.col-mes-group { min-width:72px; }
.col-anual-head { background:#0c2a54 !important; color:#e0eaff !important; border-color:#091e3d !important; }
.col-sub { min-width:44px; font-size:11px; padding:6px 6px !important; }
.col-num-sm { width:26px; }
.rpt-ganancia-table .rpt-total-row td { color:#fff; font-weight:800; background:#1a3a6b; border-color:#16397a; }

.reporte-metas-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }

@media (max-width: 900px) {
    .rpt-min-table { min-width:640px; }
    .rpt-ganancia-table { min-width:1080px; }
    .rpt-min-table th,
    .rpt-min-table td,
    .rpt-ganancia-table th,
    .rpt-ganancia-table td { font-size:11px; }
}

.rpt-consolidar-min-table { border-collapse:collapse; width:100%; min-width:540px; }
.rpt-consolidar-min-table th {
    background:#1e4a89; color:#fff; font-weight:700; font-size:12px;
    text-align:center; padding:6px 8px; border:1px solid #16397a; white-space:nowrap;
}
.rpt-consolidar-min-table td { text-align:center; padding:6px 8px; font-size:13px; border:1px solid #dde4f0; }

.report-link-btn { border:0; background:transparent; color:#1a3a6b; font-weight:700; text-decoration:underline; cursor:pointer; padding:0 2px; font-size:14px; }
.report-link-btn.bold { font-weight:800; }
.report-link-btn:hover { color:#0c1f45; }
.rpt-total-row .report-link-btn { color:#f5c800; }
.rpt-cero { color:#ccc; font-size:13px; }

.celula-modal { position:fixed; inset:0; z-index:1050; opacity:0; visibility:hidden; pointer-events:none; transition:opacity .2s ease; }
.celula-modal.is-open { opacity:1; visibility:visible; pointer-events:auto; }
.celula-modal__overlay { position:absolute; inset:0; background:rgba(15,27,46,.58); backdrop-filter:blur(2px); }
.celula-modal__dialog {
    position:relative; width:min(1100px,calc(100vw - 36px)); max-height:calc(100vh - 36px);
    margin:18px auto; border-radius:14px; background:#fff;
    box-shadow:0 18px 45px rgba(20,39,72,.24); overflow:hidden;
    display:flex; flex-direction:column;
    transform:translateY(22px) scale(.985); opacity:0;
    transition:transform .24s ease, opacity .24s ease;
}
.celula-modal.is-open .celula-modal__dialog { transform:translateY(0) scale(1); opacity:1; }
.celula-modal__header {
    display:flex; align-items:center; justify-content:space-between; gap:12px;
    padding:14px 18px; border-bottom:1px solid #d9e4f2;
    background:linear-gradient(180deg,#f8fbff 0%,#eef4ff 100%);
}
.celula-modal__title { margin:0; color:#21457e; font-size:18px; font-weight:700; }
.celula-modal__close { border:0; background:#dbe6f8; color:#1e4a89; width:34px; height:34px; border-radius:50%; font-size:22px; line-height:1; cursor:pointer; }
.celula-modal__body { padding:14px 18px 18px; overflow:auto; }
.data-table--sm th, .data-table--sm td { padding:6px 8px; font-size:13px; }
@media (max-width:900px) {
    .celula-modal__dialog { width:calc(100vw - 16px); max-height:calc(100vh - 16px); margin:8px auto; }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
