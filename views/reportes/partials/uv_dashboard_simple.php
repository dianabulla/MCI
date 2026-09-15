<?php
/**
 * Dashboard UV: avance por red (asistencia lista + Encuentro) y Escalera del éxito.
 *
 * @var array<string, mixed> $indicadores_encuentro_uv
 */
$tablaUvAvanceRedes = is_array($tabla_uv_avance_redes ?? null) ? $tabla_uv_avance_redes : [];
$etiquetaPeriodoUvDash = trim((string)($etiqueta_periodo_uv ?? ''));
if ($etiquetaPeriodoUvDash === '') {
    $etiquetaPeriodoUvDash = 'Periodo seleccionado';
}
$anioRepUv = (int)($anio ?? date('Y'));
$fechaIniUv = (string)($fechaInicioMes ?? '');
$fechaFinUv = (string)($fechaFinMes ?? '');

$etiquetaRedUv = static function ($red) {
    if ($red === 'hombres') {
        return 'Red Hombres';
    }
    if ($red === 'mujeres') {
        return 'Red Mujeres';
    }
    return 'Sin red';
};

$totUvAvance = ['inscritos' => 0, 'asistieron' => 0, 'pasaron' => 0, 'h_ins' => 0, 'h_as' => 0, 'h_pa' => 0, 'm_ins' => 0, 'm_as' => 0, 'm_pa' => 0];
foreach ($tablaUvAvanceRedes as $filaUvAv) {
    $ins = (int)($filaUvAv['inscritos'] ?? 0);
    $as = (int)($filaUvAv['asistieron'] ?? 0);
    $pa = (int)($filaUvAv['pasaron'] ?? 0);
    $totUvAvance['inscritos'] += $ins;
    $totUvAvance['asistieron'] += $as;
    $totUvAvance['pasaron'] += $pa;
    $redAv = (string)($filaUvAv['red'] ?? '');
    if ($redAv === 'hombres') {
        $totUvAvance['h_ins'] += $ins;
        $totUvAvance['h_as'] += $as;
        $totUvAvance['h_pa'] += $pa;
    } elseif ($redAv === 'mujeres') {
        $totUvAvance['m_ins'] += $ins;
        $totUvAvance['m_as'] += $as;
        $totUvAvance['m_pa'] += $pa;
    }
}

$indicadoresEscaleraSemestres = is_array($indicadores_encuentro_uv_semestres ?? null)
    ? $indicadores_encuentro_uv_semestres
    : [];
if ($indicadoresEscaleraSemestres === [] && is_array($indicadores_encuentro_uv ?? null) && $indicadores_encuentro_uv !== []) {
    $indicadoresEscaleraSemestres = [$indicadores_encuentro_uv];
}
?>

<div class="dash-card uv-encuentro-kpis cap-dash-kpis">
    <h4 class="section-title" style="margin-bottom:6px;">Universidad de la Vida — avance por red</h4>
    <p class="uv-dash-kpi-note">
        Periodo: <strong><?= htmlspecialchars($etiquetaPeriodoUvDash) ?></strong>
        (<?= htmlspecialchars($fechaIniUv) ?> a <?= htmlspecialchars($fechaFinUv) ?>).
        <strong>Asistieron</strong> = al menos una clase UV marcada en la lista de asistencias (clases 1 a 4).
        <strong>Pasaron al Encuentro</strong> = 70% de esas clases (3 de 4) y al menos un día de Encuentro (clases 5 o 6 de la misma lista).
    </p>
    <div class="uv-kpi-row uv-kpi-row-encuentro">
        <div class="uv-kpi uv-kpi-total">
            <span>Inscritos</span>
            <strong><?= (int)$totUvAvance['inscritos'] ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-ok">
            <span>Asistieron a clase</span>
            <strong><?= (int)$totUvAvance['asistieron'] ?></strong>
        </div>
        <div class="uv-kpi uv-kpi-warn">
            <span>Pasaron al Encuentro</span>
            <strong><?= (int)$totUvAvance['pasaron'] ?></strong>
        </div>
    </div>
    <div class="uv-kpi-row" style="margin-top:10px;">
        <div class="uv-kpi">
            <span>Red Hombres</span>
            <strong><?= (int)$totUvAvance['h_ins'] ?></strong>
            <em class="uv-kpi-pct">Asist. <?= (int)$totUvAvance['h_as'] ?> · Encuentro <?= (int)$totUvAvance['h_pa'] ?></em>
        </div>
        <div class="uv-kpi">
            <span>Red Mujeres</span>
            <strong><?= (int)$totUvAvance['m_ins'] ?></strong>
            <em class="uv-kpi-pct">Asist. <?= (int)$totUvAvance['m_as'] ?> · Encuentro <?= (int)$totUvAvance['m_pa'] ?></em>
        </div>
    </div>
</div>

<p class="uv-dash-intro uv-dash-intro-compact">
    <strong><?= htmlspecialchars($etiquetaPeriodoUvDash) ?> <?= $anioRepUv ?></strong>
    · Inscripciones con fecha de registro en el periodo
</p>

<div class="dash-card uv-dash-table-card" style="margin-bottom:16px;">
    <h4 class="section-title">Inscritos, asistencia y Encuentro por red</h4>
    <small class="uv-dash-table-note">Una fila por ministerio y red. El Encuentro se toma de la lista de asistencias, no de la escalera.</small>
    <div class="rpt-ganados-semana-filtros cap-red-filtros" role="group" aria-label="Filtrar por red" style="margin:10px 0 8px;">
        <button type="button" class="rpt-ganados-filtro is-active js-uv-red-filtro" data-red="todos">Todos</button>
        <button type="button" class="rpt-ganados-filtro js-uv-red-filtro" data-red="hombres">Red Hombres</button>
        <button type="button" class="rpt-ganados-filtro js-uv-red-filtro" data-red="mujeres">Red Mujeres</button>
    </div>
    <div class="table-wrap">
        <table class="leader-table uv-simple-table" id="tablaUvAvanceRedes">
            <thead>
                <tr>
                    <th>Ministerio</th>
                    <th>Red</th>
                    <th class="uv-num">Inscritos</th>
                    <th class="uv-num">Asistieron</th>
                    <th class="uv-num">Pasaron al Encuentro</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($tablaUvAvanceRedes)): ?>
                    <?php foreach ($tablaUvAvanceRedes as $filaUvAv): ?>
                        <tr class="js-uv-avance-row" data-cap-red="<?= htmlspecialchars((string)($filaUvAv['red'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td><?= htmlspecialchars((string)($filaUvAv['ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td><?= htmlspecialchars($etiquetaRedUv((string)($filaUvAv['red'] ?? ''))) ?></td>
                            <td class="uv-num js-uv-col-ins"><?= (int)($filaUvAv['inscritos'] ?? 0) ?></td>
                            <td class="uv-num js-uv-col-as"><?= (int)($filaUvAv['asistieron'] ?? 0) ?></td>
                            <td class="uv-num js-uv-col-pa"><?= (int)($filaUvAv['pasaron'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No hay inscripciones de Universidad de la Vida para este periodo.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2">TOTAL</th>
                    <th class="uv-num" id="uvAvanceTotIns"><?= (int)$totUvAvance['inscritos'] ?></th>
                    <th class="uv-num" id="uvAvanceTotAs"><?= (int)$totUvAvance['asistieron'] ?></th>
                    <th class="uv-num" id="uvAvanceTotPa"><?= (int)$totUvAvance['pasaron'] ?></th>
                </tr>
            </tfoot>
        </table>
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
    const botones = document.querySelectorAll('.js-uv-red-filtro');
    const filas = document.querySelectorAll('#tablaUvAvanceRedes .js-uv-avance-row');
    const totIns = document.getElementById('uvAvanceTotIns');
    const totAs = document.getElementById('uvAvanceTotAs');
    const totPa = document.getElementById('uvAvanceTotPa');
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
                ins += parseInt((fila.querySelector('.js-uv-col-ins') || {}).textContent || '0', 10) || 0;
                asis += parseInt((fila.querySelector('.js-uv-col-as') || {}).textContent || '0', 10) || 0;
                pasa += parseInt((fila.querySelector('.js-uv-col-pa') || {}).textContent || '0', 10) || 0;
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

<?php
foreach ($indicadoresEscaleraSemestres as $idxEscalera => $indicadoresEscalera):
    $indicadoresEscalera = is_array($indicadoresEscalera) ? $indicadoresEscalera : [];
    $kpiTotalPersonas = (int)($indicadoresEscalera['total_personas'] ?? 0);
    $kpiConUv = (int)($indicadoresEscalera['con_uv_escalera'] ?? 0);
    $kpiSinUv = (int)($indicadoresEscalera['sin_uv_escalera'] ?? 0);
    $kpiConEncuentro = (int)($indicadoresEscalera['con_encuentro_escalera'] ?? 0);
    $kpiConBautismo = (int)($indicadoresEscalera['con_bautismo_escalera'] ?? 0);
    $kpiConCapDestino = (int)($indicadoresEscalera['con_capacitacion_destino'] ?? 0);
    $kpiPctConUv = (float)($indicadoresEscalera['pct_con_uv'] ?? 0);
    $kpiPctSinUv = (float)($indicadoresEscalera['pct_sin_uv'] ?? 0);
    $kpiPctCapDestino = (float)($indicadoresEscalera['pct_capacitacion_destino'] ?? 0);
    $desglosePorLider12 = is_array($indicadoresEscalera['desglose_por_lider12'] ?? null)
        ? $indicadoresEscalera['desglose_por_lider12']
        : [];
    $etiquetaEscalera = trim((string)($indicadoresEscalera['etiqueta_periodo'] ?? $etiquetaPeriodoUvDash));
    $fechaIniEscalera = trim((string)($indicadoresEscalera['fecha_inicio'] ?? $fechaIniUv));
    $fechaFinEscalera = trim((string)($indicadoresEscalera['fecha_fin'] ?? $fechaFinUv));
    $sufijoEscalera = (int)$idxEscalera;
    $kpiIndicadores = [
        'total' => ['label' => 'Total personas', 'valor' => $kpiTotalPersonas, 'clase' => 'uv-kpi-total'],
        'con_uv' => ['label' => 'Universidad de la Vida', 'valor' => $kpiConUv, 'clase' => 'uv-kpi-ok', 'pct' => $kpiPctConUv],
        'sin_uv' => ['label' => 'Sin Universidad de la Vida', 'valor' => $kpiSinUv, 'clase' => 'uv-kpi-warn', 'pct' => $kpiPctSinUv],
        'encuentro' => [
            'label' => 'Encuentro',
            'valor' => $kpiConEncuentro,
            'clase' => 'uv-kpi-ok',
            'style' => 'background:#ecfdf5;border-color:#6ee7b7;',
        ],
        'bautismo' => [
            'label' => 'Bautismo',
            'valor' => $kpiConBautismo,
            'clase' => 'uv-kpi-ok',
            'style' => 'background:#f5f3ff;border-color:#c4b5fd;',
        ],
        'cap_destino' => [
            'label' => 'Capacitación Destino',
            'valor' => $kpiConCapDestino,
            'clase' => 'uv-kpi-ok',
            'style' => 'background:#fffbeb;border-color:#e9c46a;',
            'pct' => $kpiPctCapDestino,
            'strong_style' => 'color:#7a4e08;',
        ],
    ];
?>
<div class="dash-card uv-encuentro-kpis js-uv-escalera-bloque" id="uvIndicadoresEscalera-<?= $sufijoEscalera ?>" data-escalera-idx="<?= $sufijoEscalera ?>">
    <h4 class="section-title" style="margin-bottom:6px;">Escalera del éxito — Consolidar</h4>
    <p class="uv-dash-kpi-note">
        Periodo: <strong><?= htmlspecialchars($etiquetaEscalera !== '' ? $etiquetaEscalera : $etiquetaPeriodoUvDash) ?></strong>
        (<?= htmlspecialchars($fechaIniEscalera) ?> a <?= htmlspecialchars($fechaFinEscalera) ?>).
        <?php
        $alcanceKpi = (string)($indicadoresEscalera['alcance'] ?? 'toda_la_iglesia');
        if ($alcanceKpi === 'ministerio'): ?>
            Personas <strong>nuevas</strong> (casilla Nuevo) <strong>registradas en este semestre</strong> del ministerio filtrado.
        <?php elseif ($alcanceKpi === 'lider'): ?>
            Personas <strong>nuevas</strong> (casilla Nuevo) <strong>registradas en este semestre</strong> bajo el líder filtrado.
        <?php elseif ($alcanceKpi === 'alcance_rol'): ?>
            Personas <strong>nuevas</strong> visibles para tu rol, <strong>registradas en este semestre</strong>.
        <?php else: ?>
            Personas <strong>nuevas</strong> (casilla Nuevo) <strong>registradas en este semestre</strong> en toda la iglesia.
        <?php endif; ?>
            No se mezclan con el semestre anterior. <strong>UV</strong> = inscripción/asistencia de UV, peldaño de Universidad de la vida en la <strong>escalera rápida</strong>, o etapa Consolidar ya superada (Proceso en Discipular/Enviar).
            <strong>Sin UV</strong> = no tiene ese peldaño ni inscripción UV ni etapas posteriores.
            <strong>Encuentro</strong> = lista de asistencias (clases 5–6).
            <strong>Bautismo</strong> = inscripción o asistencia de bautismo.
            <strong>Capacitación Destino</strong> = inscripción a un nivel.
    </p>
    <p class="uv-dash-kpi-hint">Clic en un indicador para ver cuántas personas tiene cada líder principal de 12.</p>
    <div class="uv-kpi-row uv-kpi-row-encuentro js-uv-kpi-row">
        <?php foreach ($kpiIndicadores as $kpiKey => $kpiMeta): ?>
            <?php
            $styleInline = trim((string)($kpiMeta['style'] ?? ''));
            $styleAttr = $styleInline !== '' ? ' style="' . htmlspecialchars($styleInline, ENT_QUOTES, 'UTF-8') . '"' : '';
            $strongStyle = trim((string)($kpiMeta['strong_style'] ?? ''));
            $strongStyleAttr = $strongStyle !== '' ? ' style="' . htmlspecialchars($strongStyle, ENT_QUOTES, 'UTF-8') . '"' : '';
            ?>
            <button type="button"
                    class="uv-kpi uv-kpi-clickable <?= htmlspecialchars((string)($kpiMeta['clase'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    data-uv-kpi="<?= htmlspecialchars($kpiKey, ENT_QUOTES, 'UTF-8') ?>"
                    data-uv-kpi-label="<?= htmlspecialchars((string)($kpiMeta['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    aria-pressed="false"
                    <?= $styleAttr ?>>
                <span><?= htmlspecialchars((string)($kpiMeta['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <strong<?= $strongStyleAttr ?>><?= (int)($kpiMeta['valor'] ?? 0) ?></strong>
                <?php if (isset($kpiMeta['pct'])): ?>
                    <em class="uv-kpi-pct"><?= number_format((float)$kpiMeta['pct'], 1) ?>%</em>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php if ($kpiTotalPersonas > 0): ?>
    <div class="uv-encuentro-bar" role="img" aria-label="Distribución UV en escalera">
        <div class="uv-encuentro-bar__asistio" style="width:<?= min(100, max(0, $kpiPctConUv)) ?>%;" title="Con UV: <?= $kpiConUv ?>"></div>
        <div class="uv-encuentro-bar__pendiente" style="width:<?= min(100, max(0, $kpiPctSinUv)) ?>%;" title="Sin UV: <?= $kpiSinUv ?>"></div>
    </div>
    <div class="uv-encuentro-bar-legend">
        <span class="uv-legend-asistio">Con Universidad de la Vida</span>
        <span class="uv-legend-pendiente">Sin Universidad de la Vida</span>
    </div>
    <?php else: ?>
    <p class="uv-dash-kpi-empty">No hay personas nuevas registradas en este semestre con los filtros actuales.</p>
    <?php endif; ?>

    <div class="uv-kpi-desglose-panel js-uv-desglose-panel" hidden>
        <div class="uv-kpi-desglose-head">
            <h5 class="js-uv-desglose-titulo">Por líder principal de 12</h5>
            <button type="button" class="uv-kpi-desglose-cerrar js-uv-desglose-cerrar" aria-label="Cerrar detalle">&times;</button>
        </div>
        <p class="uv-dash-kpi-hint uv-kpi-desglose-note">Solo líderes principales de 12 del ministerio. Las personas de su red se suman a cada uno. Clic en el líder para ver el listado y abrir su ficha.</p>
        <div class="js-uv-vista-lideres">
            <div class="table-container">
                <table class="dash-min-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Líder</th>
                            <th>Ministerio</th>
                            <th>Personas</th>
                        </tr>
                    </thead>
                    <tbody class="js-uv-desglose-body"></tbody>
                    <tfoot>
                        <tr style="background:#f8fafc; font-weight:700;">
                            <td colspan="3">TOTAL</td>
                            <td class="js-uv-desglose-total">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <p class="uv-dash-kpi-empty js-uv-desglose-vacio" hidden>No hay personas en este indicador para los filtros actuales.</p>
        </div>
        <div class="js-uv-vista-personas" hidden>
            <button type="button" class="uv-kpi-desglose-volver js-uv-personas-volver">
                <span class="uv-kpi-desglose-volver-icon" aria-hidden="true">←</span>
                Volver a líderes
            </button>
            <h5 class="uv-kpi-detalle-titulo js-uv-personas-titulo">Personas</h5>
            <p class="uv-dash-kpi-hint">Clic en <strong>Editar ficha</strong> para actualizar esa persona.</p>
            <div class="table-container">
                <table class="dash-min-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Persona</th>
                            <th>Ficha</th>
                        </tr>
                    </thead>
                    <tbody class="js-uv-personas-body"></tbody>
                </table>
            </div>
            <p class="uv-dash-kpi-empty js-uv-personas-vacio" hidden>Este líder no tiene personas en este indicador.</p>
        </div>
    </div>
    <script type="application/json" class="js-uv-escalera-desglose"><?= json_encode($desglosePorLider12, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
</div>
<?php endforeach; ?>

<script>
window.UV_URL_EDITAR_PERSONA = <?= json_encode(function_exists('public_app_url') ? public_app_url('personas/editar') : (rtrim((string)PUBLIC_URL, '/') . '/index.php?url=personas/editar'), JSON_UNESCAPED_UNICODE) ?>;
(function () {
    const urlEditarBase = String(window.UV_URL_EDITAR_PERSONA || '');

    function escHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function urlEditarPersona(idPersona) {
        const id = parseInt(idPersona, 10) || 0;
        if (id <= 0 || !urlEditarBase) {
            return '';
        }
        const sep = urlEditarBase.indexOf('?') >= 0 ? '&' : '?';
        return urlEditarBase + sep + 'id=' + encodeURIComponent(String(id))
            + '&return_url=' + encodeURIComponent(window.location.href);
    }

    function initBloqueEscalera(bloque) {
        let desglose = {};
        const nodoJson = bloque.querySelector('.js-uv-escalera-desglose');
        if (nodoJson) {
            try {
                desglose = JSON.parse(nodoJson.textContent || '{}') || {};
            } catch (e) {
                desglose = {};
            }
        }
        const panel = bloque.querySelector('.js-uv-desglose-panel');
        const titulo = bloque.querySelector('.js-uv-desglose-titulo');
        const body = bloque.querySelector('.js-uv-desglose-body');
        const totalEl = bloque.querySelector('.js-uv-desglose-total');
        const vacio = bloque.querySelector('.js-uv-desglose-vacio');
        const btnCerrar = bloque.querySelector('.js-uv-desglose-cerrar');
        const vistaLideres = bloque.querySelector('.js-uv-vista-lideres');
        const vistaPersonas = bloque.querySelector('.js-uv-vista-personas');
        const personasTitulo = bloque.querySelector('.js-uv-personas-titulo');
        const personasBody = bloque.querySelector('.js-uv-personas-body');
        const personasVacio = bloque.querySelector('.js-uv-personas-vacio');
        const btnVolverPersonas = bloque.querySelector('.js-uv-personas-volver');
        const botones = bloque.querySelectorAll('.uv-kpi-clickable[data-uv-kpi]');
        let kpiActivo = '';
        let filasKpi = [];

        function mostrarVistaLideres() {
            if (vistaLideres) {
                vistaLideres.hidden = false;
            }
            if (vistaPersonas) {
                vistaPersonas.hidden = true;
            }
        }

        function renderPersonasLider(fila) {
            const personas = Array.isArray(fila && fila.personas) ? fila.personas : [];
            const nombreLider = (fila && fila.lider) ? fila.lider : 'Sin líder de 12';
            if (personasTitulo) {
                personasTitulo.textContent = 'Personas de ' + nombreLider + ' (' + personas.length + ')';
            }
            if (personasBody) {
                personasBody.innerHTML = '';
                personas.forEach(function (persona, idx) {
                    const idPersona = parseInt(persona.id, 10) || 0;
                    const nombre = persona.nombre || 'Sin nombre';
                    const href = urlEditarPersona(idPersona);
                    const tr = document.createElement('tr');
                    const celdaFicha = href
                        ? '<a class="uv-kpi-ficha-link" href="' + escHtml(href) + '">Editar ficha</a>'
                        : '—';
                    tr.innerHTML = '<td style="color:#94a3b8;">' + (idx + 1) + '</td>'
                        + '<td>' + escHtml(nombre) + '</td>'
                        + '<td>' + celdaFicha + '</td>';
                    personasBody.appendChild(tr);
                });
            }
            if (personasVacio) {
                personasVacio.hidden = personas.length > 0;
            }
            if (vistaLideres) {
                vistaLideres.hidden = true;
            }
            if (vistaPersonas) {
                vistaPersonas.hidden = false;
            }
        }

        function renderDesglose(kpiKey, label) {
            filasKpi = Array.isArray(desglose[kpiKey]) ? desglose[kpiKey] : [];
            const total = filasKpi.reduce(function (acc, fila) {
                return acc + (parseInt(fila.total, 10) || 0);
            }, 0);
            if (titulo) {
                titulo.textContent = label + ' · por líder principal de 12';
            }
            if (body) {
                body.innerHTML = '';
                filasKpi.forEach(function (fila, idx) {
                    const cant = parseInt(fila.total, 10) || 0;
                    const lider = fila.lider || 'Sin líder de 12';
                    const ministerio = fila.ministerio || 'Sin ministerio';
                    const tr = document.createElement('tr');
                    const btnLider = cant > 0
                        ? '<button type="button" class="uv-kpi-lider-link" data-uv-lider-idx="' + idx + '">' + escHtml(lider) + '</button>'
                        : escHtml(lider);
                    tr.innerHTML = '<td style="color:#94a3b8;">' + (idx + 1) + '</td>'
                        + '<td>' + btnLider + '</td>'
                        + '<td>' + escHtml(ministerio) + '</td>'
                        + '<td>' + (cant > 0 ? '<strong>' + cant + '</strong>' : '0') + '</td>';
                    body.appendChild(tr);
                });
                body.querySelectorAll('.uv-kpi-lider-link').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const idx = parseInt(btn.getAttribute('data-uv-lider-idx') || '-1', 10);
                        if (idx < 0 || !filasKpi[idx]) {
                            return;
                        }
                        renderPersonasLider(filasKpi[idx]);
                    });
                });
            }
            if (totalEl) {
                totalEl.textContent = String(total);
            }
            if (vacio) {
                vacio.hidden = filasKpi.length > 0;
            }
            mostrarVistaLideres();
            if (panel) {
                panel.hidden = false;
            }
        }

        function cerrarDesglose() {
            kpiActivo = '';
            filasKpi = [];
            botones.forEach(function (btn) {
                btn.classList.remove('is-active');
                btn.setAttribute('aria-pressed', 'false');
            });
            mostrarVistaLideres();
            if (panel) {
                panel.hidden = true;
            }
        }

        botones.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const kpiKey = btn.getAttribute('data-uv-kpi') || '';
                const label = btn.getAttribute('data-uv-kpi-label') || 'Indicador';
                if (kpiActivo === kpiKey) {
                    cerrarDesglose();
                    return;
                }
                kpiActivo = kpiKey;
                botones.forEach(function (otro) {
                    const activo = otro === btn;
                    otro.classList.toggle('is-active', activo);
                    otro.setAttribute('aria-pressed', activo ? 'true' : 'false');
                });
                renderDesglose(kpiKey, label);
            });
        });
        if (btnCerrar) {
            btnCerrar.addEventListener('click', cerrarDesglose);
        }
        if (btnVolverPersonas) {
            btnVolverPersonas.addEventListener('click', mostrarVistaLideres);
        }
    }

    document.querySelectorAll('.js-uv-escalera-bloque').forEach(initBloqueEscalera);
})();
</script>
