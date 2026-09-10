<?php
$tablaOperativaFormUrl = (string)($tablaOperativaFormUrl ?? 'reportes/dashboard-ganar');
$anio = (int)($anio ?? date('Y'));
$filtroMinisterio = (string)($filtroMinisterio ?? '');
$filtroLider = (string)($filtroLider ?? '');
$celFiltroDesde = (string)($celFiltroDesde ?? '');
$celFiltroHasta = (string)($celFiltroHasta ?? '');
$reporteCelulasPorLider = $reporteCelulasPorLider ?? ($reporte_celulas_por_lider ?? []);
$reporteCelulasGrupos = is_array($reporteCelulasPorLider['grupos'] ?? null) ? $reporteCelulasPorLider['grupos'] : [];
$reporteCelulasTotales = is_array($reporteCelulasPorLider['totales'] ?? null) ? $reporteCelulasPorLider['totales'] : [];
$reporteCelulasFechaDesde = (string)($reporteCelulasPorLider['fecha_desde'] ?? '');
$reporteCelulasFechaHasta = (string)($reporteCelulasPorLider['fecha_hasta'] ?? '');
$reporteCelulasRangoPersonalizado = !empty($reporteCelulasPorLider['rango_personalizado']);
$exportCelulasPorLiderUrl = (string)($exportCelulasPorLiderUrl ?? '');
$celFiltroResetUrl = (string)($celFiltroResetUrl ?? (PUBLIC_URL . 'index.php?url=' . $tablaOperativaFormUrl));
$tablaOperativaHiddenExtras = is_array($tablaOperativaHiddenExtras ?? null) ? $tablaOperativaHiddenExtras : [];
?>
<style>
.dash-min-table { width:100%; border-collapse:collapse; font-size:.88rem; }
.dash-min-table th { text-align:left; padding:8px 10px; background:#eef2ff; color:#3730a3; font-size:.75rem; text-transform:uppercase; letter-spacing:.03em; }
.dash-min-table td { padding:8px 10px; border-bottom:1px solid #eef2f7; }
.dash-celulas-rango-form { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin-bottom:14px; padding:12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
.dash-celulas-rango-form .form-group { margin:0; }
.dash-celulas-rango-form label { font-size:.8rem; color:#475569; display:block; margin-bottom:4px; }
.dash-cel-metrica-btn { border:none; background:none; padding:0; font:inherit; font-weight:600; cursor:pointer; text-decoration:underline; text-underline-offset:2px; }
.dash-cel-metrica-btn.is-alerta { color:#dc2626; }
.dash-cel-metrica-btn.is-personas { color:#1d4ed8; }
.dash-cel-detalle-modal { position:fixed; inset:0; z-index:1200; display:flex; align-items:center; justify-content:center; padding:16px; }
.dash-cel-detalle-modal[hidden] { display:none !important; }
.dash-cel-detalle-backdrop { position:absolute; inset:0; background:rgba(15,23,42,.45); }
.dash-cel-detalle-panel { position:relative; z-index:1; background:#fff; border-radius:12px; max-width:720px; width:100%; max-height:85vh; overflow:auto; padding:18px 20px; box-shadow:0 12px 40px rgba(15,23,42,.2); }
.dash-cel-detalle-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:14px; }
.dash-cel-detalle-head h4 { margin:0; font-size:1.05rem; color:#1e293b; }
.dash-cel-detalle-sub { margin:4px 0 0; font-size:.84rem; color:#64748b; }
.dash-cel-detalle-cerrar { border:none; background:#f1f5f9; width:32px; height:32px; border-radius:8px; font-size:1.25rem; line-height:1; cursor:pointer; color:#475569; }
.dash-cel-detalle-ayuda { margin:0 0 14px; padding:12px 14px; border-radius:10px; font-size:.84rem; line-height:1.45; }
.dash-cel-detalle-ayuda.is-sobre { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; }
.dash-cel-detalle-ayuda.is-reporte { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
.dash-cel-detalle-resumen { margin:0 0 10px; font-size:.82rem; color:#475569; font-weight:600; }
.dash-cel-detalle-table { width:100%; border-collapse:collapse; font-size:.84rem; }
.dash-cel-detalle-table th { text-align:left; padding:8px 10px; background:#f8fafc; color:#475569; font-size:.74rem; text-transform:uppercase; border-bottom:1px solid #e2e8f0; }
.dash-cel-detalle-table td { padding:10px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.dash-cel-detalle-vacio { padding:20px; text-align:center; color:#94a3b8; }
.dash-cel-min-list { display:flex; flex-direction:column; gap:8px; }
.dash-cel-min-item { border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; background:#fff; }
.dash-cel-min-toggle { width:100%; display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 14px; border:0; background:#eef2ff; color:#3730a3; font:inherit; font-weight:700; text-align:left; cursor:pointer; }
.dash-cel-min-toggle:hover { background:#e0e7ff; }
.dash-cel-min-toggle-meta { font-weight:600; color:#6366f1; font-size:.84rem; white-space:nowrap; }
.dash-cel-min-chevron { display:inline-block; transition:transform .15s ease; }
.dash-cel-min-item.is-open .dash-cel-min-chevron { transform:rotate(90deg); }
.dash-cel-min-body { display:none; padding:0 0 8px; }
.dash-cel-min-item.is-open .dash-cel-min-body { display:block; }
.dash-cel-lider-row td { background:#f8fafc; font-weight:700; color:#1e293b; border-bottom:1px solid #e2e8f0; }
.dash-cel-celula-row td:first-child { padding-left:28px; color:#64748b; }
</style>

<div class="card report-card" style="margin-bottom:22px; padding:18px;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; margin-bottom:14px;">
        <h4 style="margin:0; font-size:.97rem; color:#374151;">
            Reporte operativo de células por líder
            <?php if ($reporteCelulasRangoPersonalizado): ?>
                · <?= $reporteCelulasFechaDesde !== '' ? date('d/m/Y', strtotime($reporteCelulasFechaDesde)) : '—' ?>
                – <?= $reporteCelulasFechaHasta !== '' ? date('d/m/Y', strtotime($reporteCelulasFechaHasta)) : '—' ?>
            <?php else: ?>
                · <?= $anio ?>
            <?php endif; ?>
        </h4>
        <?php if ($exportCelulasPorLiderUrl !== ''): ?>
        <a href="<?= htmlspecialchars($exportCelulasPorLiderUrl) ?>" class="btn btn-sm btn-success" title="Exportar esta tabla a Excel">
            <i class="bi bi-file-earmark-excel"></i> Exportar Excel
        </a>
        <?php endif; ?>
    </div>

    <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="dash-celulas-rango-form">
        <input type="hidden" name="url" value="<?= htmlspecialchars($tablaOperativaFormUrl) ?>">
        <?php foreach ($tablaOperativaHiddenExtras as $hiddenName => $hiddenValue): ?>
            <?php
            $hiddenName = trim((string)$hiddenName);
            if ($hiddenName === '' || $hiddenName === 'url' || $hiddenName === 'cel_desde' || $hiddenName === 'cel_hasta') {
                continue;
            }
            ?>
            <input type="hidden" name="<?= htmlspecialchars($hiddenName) ?>" value="<?= htmlspecialchars((string)$hiddenValue) ?>">
        <?php endforeach; ?>
        <input type="hidden" name="anio" value="<?= $anio ?>">
        <?php if ($filtroMinisterio !== ''): ?>
            <input type="hidden" name="ministerio" value="<?= htmlspecialchars($filtroMinisterio) ?>">
        <?php endif; ?>
        <?php if ($filtroLider !== ''): ?>
            <input type="hidden" name="lider" value="<?= htmlspecialchars($filtroLider) ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="cel_desde">Desde</label>
            <input type="date" id="cel_desde" name="cel_desde" class="form-control"
                   value="<?= htmlspecialchars($celFiltroDesde !== '' ? $celFiltroDesde : $reporteCelulasFechaDesde) ?>"
                   max="<?= htmlspecialchars(date('Y-m-d')) ?>">
        </div>
        <div class="form-group">
            <label for="cel_hasta">Hasta</label>
            <input type="date" id="cel_hasta" name="cel_hasta" class="form-control"
                   value="<?= htmlspecialchars($celFiltroHasta !== '' ? $celFiltroHasta : $reporteCelulasFechaHasta) ?>"
                   max="<?= htmlspecialchars(date('Y-m-d')) ?>">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Aplicar rango</button>
        <?php if ($reporteCelulasRangoPersonalizado): ?>
            <a href="<?= htmlspecialchars($celFiltroResetUrl) ?>" class="btn btn-sm btn-outline-secondary">Restablecer año</a>
        <?php endif; ?>
    </form>

    <small style="color:#64748b; display:block; margin-bottom:12px;">
        Abre un ministerio para ver sus líderes y el resumen de cada célula. Clic en un número para ver personas o el detalle. Cierre con la X.
    </small>

    <?php
    $renderMetricaCelulasClick = static function(int $valor, array $detalle, string $tituloModal, string $nombreLider, string $tipo) {
        if ($valor <= 0) {
            return '<span style="color:#64748b;">0</span>';
        }
        $ayuda = $tipo === 'sin_sobre'
            ? 'En estas semanas la célula sí reportó asistencia, pero no quedó marcada la casilla «Sobre entregado» en Enviar.'
            : 'Estas son las semanas consecutivas sin reporte de asistencia de célula.';
        $payload = htmlspecialchars(json_encode([
            'titulo' => $tituloModal,
            'lider' => $nombreLider,
            'tipo' => $tipo,
            'ayuda' => $ayuda,
            'total' => $valor,
            'items' => array_values($detalle),
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        return '<button type="button" class="dash-cel-metrica-btn is-alerta" data-detalle="' . $payload . '">' . $valor . '</button>';
    };
    $renderPersonasCelulasClick = static function(int $valor, array $personas, string $tituloModal, string $nombreCelula, string $nombreLider) {
        if ($valor <= 0) {
            return '<span style="color:#64748b;">0</span>';
        }
        $payload = htmlspecialchars(json_encode([
            'titulo' => $tituloModal,
            'celula' => $nombreCelula,
            'lider' => $nombreLider,
            'tipo' => 'personas',
            'total' => $valor,
            'items' => array_values($personas),
        ], JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
        return '<button type="button" class="dash-cel-metrica-btn is-personas" data-detalle="' . $payload . '">' . $valor . '</button>';
    };
    ?>

    <?php if ($reporteCelulasGrupos === []): ?>
        <p style="margin:0; color:#64748b;">No hay células para mostrar con los filtros actuales.</p>
    <?php else: ?>
    <div class="dash-cel-min-list">
        <?php foreach ($reporteCelulasGrupos as $idxMin => $grupoCel):
            $ministerioGrupo = (string)($grupoCel['ministerio'] ?? 'Sin ministerio');
            $subtotalesGrupo = is_array($grupoCel['subtotales'] ?? null) ? $grupoCel['subtotales'] : [];
            $filasGrupo = is_array($grupoCel['filas'] ?? null) ? $grupoCel['filas'] : [];
            $filasPorLider = [];
            foreach ($filasGrupo as $rowCel) {
                $claveLider = trim((string)($rowCel['lider'] ?? '')) ?: 'Sin líder';
                if (!isset($filasPorLider[$claveLider])) {
                    $filasPorLider[$claveLider] = [];
                }
                $filasPorLider[$claveLider][] = $rowCel;
            }
            $totalCelulasMin = (int)($subtotalesGrupo['celulas'] ?? count($filasGrupo));
            $totalLideresMin = count($filasPorLider);
        ?>
        <div class="dash-cel-min-item" data-min-item="1">
            <button type="button" class="dash-cel-min-toggle" data-min-toggle="1" aria-expanded="false">
                <span>
                    <span class="dash-cel-min-chevron">▸</span>
                    Ministerio: <?= htmlspecialchars($ministerioGrupo) ?>
                </span>
                <span class="dash-cel-min-toggle-meta">
                    <?= $totalLideresMin ?> líder(es) · <?= $totalCelulasMin ?> célula(s)
                </span>
            </button>
            <div class="dash-cel-min-body">
                <div class="table-container" style="overflow-x:auto;">
                    <table class="dash-min-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Asistentes</th>
                                <th>Nuevos</th>
                                <th>Antiguas</th>
                                <th>Frecuentes</th>
                                <th>Regulares</th>
                                <th>Sem. sin entregar sobre</th>
                                <th>Sem. sin reportar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $nCelLider = 0;
                            foreach ($filasPorLider as $nombreLiderFila => $celulasLider):
                                $nCelLider++;
                                $sumAsistentes = 0;
                                $sumNuevos = 0;
                                $sumAntiguas = 0;
                                $sumFrecuentes = 0;
                                $sumRegulares = 0;
                                $sumSinSobre = 0;
                                $sumSinReportar = 0;
                                foreach ($celulasLider as $rowCel) {
                                    $sumAsistentes += (int)($rowCel['asistentes'] ?? 0);
                                    $sumNuevos += (int)($rowCel['nuevos'] ?? 0);
                                    $sumAntiguas += (int)($rowCel['antiguas'] ?? 0);
                                    $sumFrecuentes += (int)($rowCel['frecuentes'] ?? 0);
                                    $sumRegulares += (int)($rowCel['regulares'] ?? 0);
                                    $sumSinSobre += (int)($rowCel['semanas_sin_entregar_sobre'] ?? 0);
                                    $sumSinReportar += (int)($rowCel['semanas_sin_reportar'] ?? 0);
                                }
                                $cantCelulasLider = count($celulasLider);
                            ?>
                            <tr class="dash-cel-lider-row">
                                <td colspan="8">
                                    <?= $nCelLider ?>. <?= htmlspecialchars((string)$nombreLiderFila) ?>
                                    · <?= $cantCelulasLider ?> célula(s)
                                    · Asist. <?= $sumAsistentes ?>
                                    · Nuevos <?= $sumNuevos ?>
                                    · Ant. <?= $sumAntiguas ?>
                                    · Frec. <?= $sumFrecuentes ?>
                                    · Reg. <?= $sumRegulares ?>
                                    <?php if ($sumSinSobre > 0 || $sumSinReportar > 0): ?>
                                        · Sin sobre <?= $sumSinSobre ?>
                                        · Sin reportar <?= $sumSinReportar ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            $nCelulaLider = 0;
                            foreach ($celulasLider as $rowCel):
                                $nCelulaLider++;
                                $etiquetaCelula = 'Célula ' . $nCelulaLider;
                            ?>
                            <tr class="dash-cel-celula-row">
                                <td><?= $etiquetaCelula ?></td>
                                <td><?= $renderPersonasCelulasClick((int)($rowCel['asistentes'] ?? 0), is_array($rowCel['asistentes_personas'] ?? null) ? $rowCel['asistentes_personas'] : [], 'Asistentes', $etiquetaCelula, (string)$nombreLiderFila) ?></td>
                                <td><?= $renderPersonasCelulasClick((int)($rowCel['nuevos'] ?? 0), is_array($rowCel['nuevos_personas'] ?? null) ? $rowCel['nuevos_personas'] : [], 'Asistentes nuevos', $etiquetaCelula, (string)$nombreLiderFila) ?></td>
                                <td><?= $renderPersonasCelulasClick((int)($rowCel['antiguas'] ?? 0), is_array($rowCel['antiguas_personas'] ?? null) ? $rowCel['antiguas_personas'] : [], 'Asistentes antiguas', $etiquetaCelula, (string)$nombreLiderFila) ?></td>
                                <td><?= $renderPersonasCelulasClick((int)($rowCel['frecuentes'] ?? 0), is_array($rowCel['frecuentes_personas'] ?? null) ? $rowCel['frecuentes_personas'] : [], 'Asistentes frecuentes', $etiquetaCelula, (string)$nombreLiderFila) ?></td>
                                <td><?= $renderPersonasCelulasClick((int)($rowCel['regulares'] ?? 0), is_array($rowCel['regulares_personas'] ?? null) ? $rowCel['regulares_personas'] : [], 'Asistentes regulares', $etiquetaCelula, (string)$nombreLiderFila) ?></td>
                                <td><?= $renderMetricaCelulasClick((int)($rowCel['semanas_sin_entregar_sobre'] ?? 0), is_array($rowCel['detalle_sin_entregar_sobre'] ?? null) ? $rowCel['detalle_sin_entregar_sobre'] : [], 'Semanas sin entregar sobre', (string)$nombreLiderFila, 'sin_sobre') ?></td>
                                <td><?= $renderMetricaCelulasClick((int)($rowCel['semanas_sin_reportar'] ?? 0), is_array($rowCel['detalle_sin_reportar'] ?? null) ? $rowCel['detalle_sin_reportar'] : [], 'Semanas sin reportar célula', (string)$nombreLiderFila, 'sin_reportar') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                            <tr style="background:#f8fafc; font-weight:600;">
                                <td style="text-align:right;">Subtotal</td>
                                <td><?= (int)($subtotalesGrupo['asistentes'] ?? 0) ?></td>
                                <td><?= (int)($subtotalesGrupo['nuevos'] ?? 0) ?></td>
                                <td><?= (int)($subtotalesGrupo['antiguas'] ?? 0) ?></td>
                                <td><?= (int)($subtotalesGrupo['frecuentes'] ?? 0) ?></td>
                                <td><?= (int)($subtotalesGrupo['regulares'] ?? 0) ?></td>
                                <td><?= (int)($subtotalesGrupo['semanas_sin_entregar_sobre'] ?? 0) ?></td>
                                <td><?= (int)($subtotalesGrupo['semanas_sin_reportar'] ?? 0) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="table-container" style="overflow-x:auto; margin-top:10px;">
        <table class="dash-min-table">
            <tfoot>
                <tr style="background:#f8fafc; font-weight:700;">
                    <td>TOTAL GENERAL</td>
                    <td><?= (int)($reporteCelulasTotales['asistentes'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['nuevos'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['antiguas'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['frecuentes'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['regulares'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['semanas_sin_entregar_sobre'] ?? 0) ?></td>
                    <td><?= (int)($reporteCelulasTotales['semanas_sin_reportar'] ?? 0) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <div id="dashCelDetalleModal" class="dash-cel-detalle-modal" hidden>
        <div class="dash-cel-detalle-backdrop" data-cerrar-detalle-cel="1"></div>
        <div class="dash-cel-detalle-panel" role="dialog" aria-modal="true" aria-labelledby="dashCelDetalleTitulo">
            <div class="dash-cel-detalle-head">
                <div>
                    <h4 id="dashCelDetalleTitulo">Detalle</h4>
                    <p class="dash-cel-detalle-sub" id="dashCelDetalleSub"></p>
                </div>
                <button type="button" class="dash-cel-detalle-cerrar" data-cerrar-detalle-cel="1" aria-label="Cerrar">&times;</button>
            </div>
            <div class="dash-cel-detalle-ayuda" id="dashCelDetalleAyuda" hidden></div>
            <p class="dash-cel-detalle-resumen" id="dashCelDetalleResumen"></p>
            <div class="table-container" id="dashCelDetalleContenedor"></div>
        </div>
    </div>
    <script>
    (function() {
        var modal = document.getElementById('dashCelDetalleModal');
        if (!modal) return;
        var titulo = document.getElementById('dashCelDetalleTitulo');
        var subtitulo = document.getElementById('dashCelDetalleSub');
        var ayuda = document.getElementById('dashCelDetalleAyuda');
        var resumen = document.getElementById('dashCelDetalleResumen');
        var contenedor = document.getElementById('dashCelDetalleContenedor');
        function cerrarModal() {
            modal.hidden = true;
            contenedor.innerHTML = '';
            ayuda.hidden = true;
            ayuda.textContent = '';
            resumen.textContent = '';
        }
        function escapeHtml(text) {
            return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function abrirModal(payload) {
            if (!payload || !Array.isArray(payload.items)) return;
            if (payload.tipo === 'personas') {
                titulo.textContent = payload.titulo || 'Personas';
                subtitulo.textContent = [payload.celula ? ('Célula: ' + payload.celula) : '', payload.lider ? ('Líder: ' + payload.lider) : ''].filter(Boolean).join(' · ');
                ayuda.hidden = true;
                resumen.textContent = (payload.total || payload.items.length) + ' persona(s)';
                var filasPers = payload.items.map(function(item, index) {
                    return '<tr><td>' + (index + 1) + '</td><td>' + escapeHtml(item.nombre || '') + '</td><td>' + escapeHtml(item.documento || 'N/A') + '</td><td>' + escapeHtml(item.telefono || 'N/A') + '</td><td>' + escapeHtml(item.tipo || '') + '</td><td>' + escapeHtml(item.frecuencia || '') + '</td><td>' + escapeHtml(String(item.asistencias != null ? item.asistencias : '')) + '</td></tr>';
                }).join('');
                contenedor.innerHTML = '<table class="dash-cel-detalle-table"><thead><tr><th>#</th><th>Nombre</th><th>Documento</th><th>Teléfono</th><th>Tipo</th><th>Frecuencia</th><th>Asistencias</th></tr></thead><tbody>' + filasPers + '</tbody></table>';
                modal.hidden = false;
                return;
            }
            titulo.textContent = payload.titulo || 'Detalle';
            subtitulo.textContent = payload.lider ? ('Líder: ' + payload.lider) : '';
            ayuda.className = 'dash-cel-detalle-ayuda ' + (payload.tipo === 'sin_sobre' ? 'is-sobre' : 'is-reporte');
            ayuda.innerHTML = '<strong>¿Qué significa esto?</strong>' + escapeHtml(payload.ayuda || '');
            ayuda.hidden = false;
            resumen.textContent = (payload.total || payload.items.length) + ' semana(s)';
            var filas = payload.items.map(function(item, index) {
                return '<tr><td>' + (index + 1) + '</td><td>' + escapeHtml(item.periodo || item.etiqueta || item.inicio || '') + '</td><td>' + escapeHtml(item.situacion || '') + '</td></tr>';
            }).join('');
            contenedor.innerHTML = '<table class="dash-cel-detalle-table"><thead><tr><th>#</th><th>Semana</th><th>Qué ocurrió</th></tr></thead><tbody>' + filas + '</tbody></table>';
            modal.hidden = false;
        }
        document.addEventListener('click', function(ev) {
            var btn = ev.target.closest('.dash-cel-metrica-btn');
            if (btn && btn.dataset.detalle) {
                try { abrirModal(JSON.parse(btn.dataset.detalle)); } catch (e) {}
                return;
            }
            if (ev.target.closest('[data-cerrar-detalle-cel="1"]')) cerrarModal();
        });
        document.addEventListener('keydown', function(ev) {
            if (ev.key === 'Escape' && !modal.hidden) cerrarModal();
        });
    })();
    (function() {
        var list = document.querySelector('.dash-cel-min-list');
        if (!list) return;
        list.addEventListener('click', function(ev) {
            var btn = ev.target.closest('[data-min-toggle="1"]');
            if (!btn) return;
            var item = btn.closest('[data-min-item="1"]');
            if (!item) return;
            var abrir = !item.classList.contains('is-open');
            list.querySelectorAll('[data-min-item="1"]').forEach(function(el) {
                el.classList.remove('is-open');
                var toggle = el.querySelector('[data-min-toggle="1"]');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            });
            if (abrir) {
                item.classList.add('is-open');
                btn.setAttribute('aria-expanded', 'true');
            }
        });
    })();
    </script>
</div>
