<?php
/**
 * Dashboard UV: Escalera del éxito — Consolidar con desglose por líder de 12.
 *
 * @var array<string, mixed> $indicadores_encuentro_uv
 */
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
$desglosePorLider12 = is_array($indicadoresEscalera['desglose_por_lider12'] ?? null)
    ? $indicadoresEscalera['desglose_por_lider12']
    : [];

$kpiIndicadores = [
    'total' => ['label' => 'Total personas', 'valor' => $kpiTotalPersonas, 'clase' => 'uv-kpi-total'],
    'con_uv' => ['label' => 'Universidad de la Vida', 'valor' => $kpiConUv, 'clase' => 'uv-kpi-ok', 'pct' => $kpiPctConUv],
    'sin_uv' => ['label' => 'Sin UV en escalera', 'valor' => $kpiSinUv, 'clase' => 'uv-kpi-warn', 'pct' => $kpiPctSinUv],
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
    <p class="uv-dash-kpi-hint">Clic en un indicador para ver cuántas personas tiene cada líder principal de 12.</p>
    <div class="uv-kpi-row uv-kpi-row-encuentro" id="uvKpiRowEscalera">
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
        <span class="uv-legend-pendiente">Sin UV en escalera</span>
    </div>
    <?php else: ?>
    <p class="uv-dash-kpi-empty">No hay personas en la base con los filtros actuales.</p>
    <?php endif; ?>

    <div id="uvKpiDesglosePanel" class="uv-kpi-desglose-panel" hidden>
        <div class="uv-kpi-desglose-head">
            <h5 id="uvKpiDesgloseTitulo">Por líder principal de 12</h5>
            <button type="button" class="uv-kpi-desglose-cerrar" id="uvKpiDesgloseCerrar" aria-label="Cerrar detalle">&times;</button>
        </div>
        <p class="uv-dash-kpi-hint uv-kpi-desglose-note">Solo líderes principales de 12 del ministerio. Las personas de su red se suman a cada uno.</p>
        <div class="table-container">
            <table class="dash-min-table" id="uvKpiDesgloseTabla">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Líder</th>
                        <th>Ministerio</th>
                        <th>Personas</th>
                    </tr>
                </thead>
                <tbody id="uvKpiDesgloseBody"></tbody>
                <tfoot>
                    <tr style="background:#f8fafc; font-weight:700;">
                        <td colspan="3">TOTAL</td>
                        <td id="uvKpiDesgloseTotal">0</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <p id="uvKpiDesgloseVacio" class="uv-dash-kpi-empty" hidden>No hay personas en este indicador para los filtros actuales.</p>
    </div>
</div>

<script>
window.UV_ESCALERA_DESGLOSE = <?= json_encode($desglosePorLider12, JSON_UNESCAPED_UNICODE) ?>;
(function () {
    const desglose = window.UV_ESCALERA_DESGLOSE || {};
    const panel = document.getElementById('uvKpiDesglosePanel');
    const titulo = document.getElementById('uvKpiDesgloseTitulo');
    const body = document.getElementById('uvKpiDesgloseBody');
    const totalEl = document.getElementById('uvKpiDesgloseTotal');
    const vacio = document.getElementById('uvKpiDesgloseVacio');
    const btnCerrar = document.getElementById('uvKpiDesgloseCerrar');
    const botones = document.querySelectorAll('.uv-kpi-clickable[data-uv-kpi]');
    let kpiActivo = '';

    function escHtml(texto) {
        const div = document.createElement('div');
        div.textContent = texto == null ? '' : String(texto);
        return div.innerHTML;
    }

    function renderDesglose(kpiKey, label) {
        const filas = Array.isArray(desglose[kpiKey]) ? desglose[kpiKey] : [];
        const total = filas.reduce(function (acc, fila) {
            return acc + (parseInt(fila.total, 10) || 0);
        }, 0);

        if (titulo) {
            titulo.textContent = label + ' · por líder principal de 12';
        }
        if (body) {
            body.innerHTML = '';
            filas.forEach(function (fila, idx) {
                const cant = parseInt(fila.total, 10) || 0;
                const lider = fila.lider || 'Sin líder de 12';
                const ministerio = fila.ministerio || 'Sin ministerio';
                const tr = document.createElement('tr');
                tr.innerHTML = '<td style="color:#94a3b8;">' + (idx + 1) + '</td>'
                    + '<td style="font-weight:600;">' + escHtml(lider) + '</td>'
                    + '<td>' + escHtml(ministerio) + '</td>'
                    + '<td>' + (cant > 0 ? '<strong>' + cant + '</strong>' : '0') + '</td>';
                body.appendChild(tr);
            });
        }
        if (totalEl) {
            totalEl.textContent = String(total);
        }
        if (vacio) {
            vacio.hidden = filas.length > 0;
        }
        if (panel) {
            panel.hidden = false;
        }
    }

    function cerrarDesglose() {
        kpiActivo = '';
        botones.forEach(function (btn) {
            btn.classList.remove('is-active');
            btn.setAttribute('aria-pressed', 'false');
        });
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
})();
</script>
