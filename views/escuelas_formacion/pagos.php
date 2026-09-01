<?php include VIEWS . '/layout/header.php'; ?>
<?php
$programa = (string)($programa ?? 'universidad_vida');
$lineaNavPagos = (strpos($programa, 'capacitacion_destino') === 0) ? 'capacitacion_destino' : 'universidad_vida';
require_once APP . '/Helpers/ProgramasNavegacion.php';
ProgramasNavegacion::incluirPartial([
    'linea' => $lineaNavPagos,
    'seccion' => 'pagos',
    'forzar' => true,
]);

$programa = $programa === 'capacitacion_destino' ? 'capacitacion_destino_nivel_1' : $programa;
$programaLabel = ($programa === 'universidad_vida') ? 'Universidad de la Vida' : 'Capacitación Destino';
$esUv = $programa === 'universidad_vida';
$buscar = (string)($buscar ?? '');
$filtroGenero = (string)($filtro_genero ?? '');
$filtroMinisterio = (string)($filtro_ministerio ?? '');
$filtroEncuentro = (string)($filtro_encuentro ?? '');
$bloquearSelectorPrograma = !empty($bloquear_selector_programa);
$urlVolverPagos = (string)($url_volver_pagos ?? (PUBLIC_URL . '?url=home'));
$etiquetaVolverPagos = (string)($etiqueta_volver_pagos ?? 'Volver al panel');
require_once APP . '/Helpers/SegmentoRedUv.php';
$redPagos = SegmentoRedUv::normalizarRed((string)($red_pagos ?? ($_GET['red'] ?? '')));
$modoRedPagos = $esUv && $redPagos !== '';
$etiquetaRedPagos = SegmentoRedUv::etiqueta($redPagos);
$urlHubRedesPagos = (string)($url_hub_redes ?? '');
$tituloPagos = $modoRedPagos
    ? ('Pagos Universidad de la Vida · ' . $etiquetaRedPagos)
    : 'Escuelas de Formación - Pagos, asistencia y evaluación';
?>

<style>
.unified-pagos-shell { display:flex; flex-direction:column; gap:16px; }
.unified-pagos-head { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; }
.unified-pagos-toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.unified-pagos-card { background:#fff; border:1px solid #dbe7f3; border-radius:12px; box-shadow:0 1px 4px rgba(15,23,42,0.08); padding:14px; }
.unified-pagos-topbar { display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; align-items:flex-start; margin-bottom:12px; }
.unified-pagos-status { display:flex; gap:12px; align-items:center; flex-wrap:wrap; justify-content:flex-end; }
.unified-pagos-summary { display:flex; gap:10px; flex-wrap:wrap; width:100%; }
.unified-pagos-summary-card { min-width:240px; flex:1 1 240px; padding:12px 14px; border:1px solid #cfe0f5; border-radius:12px; background:linear-gradient(180deg, #fbfdff 0%, #f1f7ff 100%); box-shadow:inset 0 1px 0 rgba(255,255,255,0.7); }
button.unified-pagos-summary-card { font: inherit; color: inherit; text-align: left; appearance: none; -webkit-appearance: none; }
.unified-pagos-summary-card strong { display:block; margin-bottom:6px; color:#1e3a5f; font-size:0.84rem; line-height:1.25; }
.unified-pagos-summary-metric { display:flex; align-items:baseline; gap:8px; margin-bottom:4px; }
.unified-pagos-summary-metric span { color:#0f172a; font-size:1.35rem; font-weight:800; line-height:1; }
.unified-pagos-summary-metric small { color:#4b6482; font-size:0.76rem; font-weight:600; text-transform:uppercase; letter-spacing:.03em; }
.unified-pagos-summary-card p { margin:0; color:#52657d; font-size:0.78rem; }
.unified-pagos-summary-card--abonos { background:linear-gradient(180deg, #fffdfa 0%, #fff4e8 100%); border-color:#f2dcc1; }
.unified-pagos-summary-card--ok { background:linear-gradient(180deg, #f0fdf4 0%, #ecfdf5 100%); border-color:#bbf7d0; }
.unified-pagos-summary-card--warn { background:linear-gradient(180deg, #fffbeb 0%, #fef3c7 100%); border-color:#fde68a; }
.unified-pagos-summary-card--click { cursor:pointer; transition:box-shadow .15s, transform .15s, border-color .15s; }
.unified-pagos-summary-card--click:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(15,23,42,.1); }
.unified-pagos-summary-card--click.is-active { box-shadow:inset 0 0 0 2px #1e4a89; }
.unified-pagos-resumen-filtro { margin:0 0 12px; padding:8px 12px; font-size:0.78rem; color:#1e40af; background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; }
.unified-pagos-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.unified-pagos-table { width:100%; border-collapse:collapse; }
.unified-pagos-table th, .unified-pagos-table td { border-bottom:1px solid #eef2f7; padding:9px 10px; font-size:0.82rem; text-align:left; vertical-align:middle; }
.unified-pagos-table th { background:#f8fafc; color:#475569; font-size:0.72rem; text-transform:uppercase; letter-spacing:.03em; }
.unified-pagos-table tfoot td { background:#f8fafc; border-top:2px solid #dbe7f3; font-weight:700; }
.badge-sem { display:inline-flex; align-items:center; justify-content:center; border-radius:999px; padding:3px 10px; font-weight:700; font-size:0.72rem; }
.badge-sem.verde { background:#dcfce7; color:#166534; }
.badge-sem.amarillo { background:#fef3c7; color:#92400e; }
.badge-sem.rojo { background:#fee2e2; color:#991b1b; }
.badge-pago-total { display:inline-flex; align-items:center; justify-content:center; border-radius:999px; padding:2px 8px; font-size:0.72rem; font-weight:700; }
.badge-pago-total.si { background:#dcfce7; color:#166534; border:1px solid #a7e3bd; }
.badge-pago-total.no { background:#fee2e2; color:#991b1b; border:1px solid #f4c5c5; }
.mini-muted { color:#64748b; }
.detail-modal { position:fixed; inset:0; z-index:9999; display:none; }
.detail-modal.is-open { display:block; }
.detail-modal__backdrop { position:absolute; inset:0; background:rgba(15,23,42,0.55); }
.detail-modal__panel { position:relative; width:min(980px, calc(100% - 24px)); margin:48px auto; background:#fff; border-radius:14px; box-shadow:0 20px 60px rgba(15,23,42,.25); padding:16px; max-height:calc(100vh - 96px); overflow:auto; }
.detail-modal__head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin-bottom:12px; }
.detail-modal__table { width:100%; border-collapse:collapse; }
.detail-modal__table th, .detail-modal__table td { border-bottom:1px solid #eef2f7; padding:8px 10px; font-size:0.82rem; }
.filtros-row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; width:100%; }
.filtro-group { display:flex; flex-direction:column; }
.filtro-group label { font-size:13px; color:#475569; margin-bottom:6px; display:block; font-weight:500; }
.filtro-group select, .filtro-group input { 
    min-width:200px; 
    padding:8px 10px; 
    border:1px solid #cbd5e1; 
    border-radius:6px; 
    font-size:14px; 
    font-family:inherit; 
}
.filtro-group button { margin-top:4px; }
.tabs-navegacion { display:flex; gap:0; border-bottom:2px solid #eef2f7; margin-bottom:16px; }
.tabs-navegacion button { background:none; border:none; padding:12px 16px; cursor:pointer; font-size:14px; color:#64748b; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all 0.2s; }
.tabs-navegacion button.is-active { color:#0f766e; border-bottom-color:#0f766e; }
.tabs-contenido { display:none; }
.tabs-contenido.is-active { display:block; }
.btn-ministerio { background:none; border:none; color:#0f766e; cursor:pointer; text-decoration:underline; padding:0; font-size:inherit; font-family:inherit; }
.btn-ministerio:hover { text-decoration:none; }
.ticket-pago-export { width:400px; padding:22px 20px 18px; background:#fff; border:2px solid #0a6e6a; border-radius:14px; color:#0f172a; font-family:Arial,sans-serif; }
.ticket-pago-export__brand { font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#0a6e6a; }
.ticket-pago-export__title { margin:8px 0 16px; font-size:22px; font-weight:800; }
.ticket-pago-export__row { display:flex; justify-content:space-between; gap:12px; margin:0 0 8px; font-size:13px; }
.ticket-pago-export__row span { color:#64748b; }
.ticket-pago-export__row strong { color:#1e293b; text-align:right; }
.ticket-pago-export__sep { height:1px; background:#dbeafe; margin:12px 0; }
.ticket-pago-export__ref-label { font-size:12px; color:#64748b; }
.ticket-pago-export__ref { font-family:"Courier New",monospace; font-size:28px; font-weight:700; color:#0a6e6a; letter-spacing:1px; margin:4px 0 12px; }
.ticket-pago-export__foot { font-size:11px; color:#94a3b8; text-align:center; }
.ticket-pago-export-offscreen { position:fixed; left:-9999px; top:0; z-index:-1; pointer-events:none; }
.pagos-ticket-preview { display:block; max-width:100%; height:auto; margin:0 auto 14px; border:1px solid #e2e8f0; border-radius:12px; background:#fff; }
.pagos-ticket-actions { display:flex; flex-wrap:wrap; gap:8px; justify-content:flex-end; }
.detail-modal__panel--ticket { width:min(460px, calc(100% - 24px)); }
@media (max-width:768px) {
    .unified-pagos-head h2 { font-size:1.2rem; }
    .unified-pagos-toolbar { width:100%; }
    .unified-pagos-toolbar .btn, .unified-pagos-toolbar select, .unified-pagos-toolbar input { width:100%; }
    .detail-modal__panel { margin:12px auto; width:calc(100% - 16px); max-height:calc(100vh - 24px); }
    .filtros-row { flex-direction:column; }
    .filtro-group select, .filtro-group input { min-width:100%; }
    .unified-pagos-topbar { flex-direction:column; align-items:stretch; }
    .unified-pagos-status { justify-content:flex-start; }
    .unified-pagos-summary-card { min-width:100%; }
}
</style>

<div class="unified-pagos-shell">
    <div class="unified-pagos-head">
        <div>
            <h2 style="margin:0;"><?= htmlspecialchars($tituloPagos) ?></h2>
            <small class="mini-muted">Vista actual: <strong id="programa-label"><?= htmlspecialchars($programaLabel) ?></strong><?= $modoRedPagos ? ' · solo personas con pago o abono' : '' ?></small>
        </div>
        <div class="unified-pagos-toolbar">
            <?php if ($modoRedPagos && $urlHubRedesPagos !== ''): ?>
            <a href="<?= htmlspecialchars($urlHubRedesPagos, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">← Cambiar red</a>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($urlVolverPagos, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm"><?= htmlspecialchars($etiquetaVolverPagos, ENT_QUOTES, 'UTF-8') ?></a>
            <?php if ($esUv): ?>
            <a href="<?= htmlspecialchars(PUBLIC_URL . '?url=programas/consolidar/asistencias&insc_programa=universidad_vida', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">Ir a asistencias</a>
            <?php endif; ?>
            <button type="button" id="btn-descargar-png" class="btn btn-primary btn-sm" title="Descargar tabla como PNG"><span>📥</span> PNG</button>
        </div>
    </div>

    <div class="unified-pagos-card">
        <form id="filtro-unificado-form">
            <div class="filtros-row">
                <?php if (!$modoRedPagos): ?>
                <div class="filtro-group">
                    <label for="programa-select">Programa</label>
                    <select id="programa-select" class="form-control" <?= $bloquearSelectorPrograma ? 'disabled' : '' ?>>
                        <?php if ($programa === 'universidad_vida'): ?>
                            <option value="universidad_vida" selected>Universidad de la Vida</option>
                        <?php else: ?>
                            <option value="capacitacion_destino_nivel_1" <?= $programa === 'capacitacion_destino_nivel_1' ? 'selected' : '' ?>>Capacitación Destino - Nivel 1</option>
                            <option value="capacitacion_destino_nivel_2" <?= $programa === 'capacitacion_destino_nivel_2' ? 'selected' : '' ?>>Capacitación Destino - Nivel 2</option>
                            <option value="capacitacion_destino_nivel_3" <?= $programa === 'capacitacion_destino_nivel_3' ? 'selected' : '' ?>>Capacitación Destino - Nivel 3</option>
                        <?php endif; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" id="programa-select" value="universidad_vida">
                <?php endif; ?>
                <?php if ($esUv): ?>
                <?php
                $filtro_semestre_form = false;
                include VIEWS . '/programas/_filtro_semestre.php';
                ?>
                <?php endif; ?>
                <?php if ($modoRedPagos): ?>
                <div class="filtro-group">
                    <label for="filtro-lider">Líder</label>
                    <select id="filtro-lider" class="form-control">
                        <option value="">Todos los líderes</option>
                    </select>
                </div>
                <?php endif; ?>
                <div class="filtro-group">
                    <label for="filtro-ministerio">Ministerio</label>
                    <select id="filtro-ministerio" class="form-control">
                        <option value="">Todos los ministerios</option>
                    </select>
                </div>
                <?php if (!$modoRedPagos): ?>
                <div class="filtro-group">
                    <label for="filtro-genero">Género</label>
                    <select id="filtro-genero" class="form-control">
                        <option value="">Todos</option>
                        <option value="hombres" <?= $filtroGenero === 'hombres' ? 'selected' : '' ?>>Hombres (todas las edades)</option>
                        <option value="mujeres" <?= $filtroGenero === 'mujeres' ? 'selected' : '' ?>>Mujeres (todas las edades)</option>
                        <option value="jovenes" <?= $filtroGenero === 'jovenes' ? 'selected' : '' ?>>Jóvenes (14–28 años)</option>
                        <option value="teens" <?= $filtroGenero === 'teens' ? 'selected' : '' ?>>Teens (9–13 años)</option>
                    </select>
                    <small class="mini-muted" style="display:block;margin-top:4px;">H/M por género. Jóvenes y Teens se clasifican por edad (14–28 y 9–13) o por segmento preferido, igual que asistencias UV.</small>
                </div>
                <?php else: ?>
                <input type="hidden" id="filtro-genero" value="<?= htmlspecialchars($filtroGenero, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <input type="hidden" id="filtro-encuentro" value="todos">
                <div class="filtro-group">
                    <label for="buscar">Buscar</label>
                    <input type="text" id="buscar" value="<?= htmlspecialchars($buscar) ?>" class="form-control" placeholder="Nombre, cédula, teléfono...">
                </div>
                <div class="filtro-group">
                    <button type="submit" class="btn btn-primary btn-sm">Aplicar</button>
                    <button type="button" id="btn-limpiar" class="btn btn-outline-secondary btn-sm">Limpiar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="unified-pagos-card">
        <div class="unified-pagos-topbar">
            <div>
                <?php if (!$modoRedPagos): ?>
                <div class="tabs-navegacion">
                    <button type="button" class="tab-btn is-active" data-tab="unificada">Tabla unificada</button>
                    <button type="button" class="tab-btn" data-tab="por-genero">Por género</button>
                </div>
                <?php endif; ?>
            </div>
            <div class="unified-pagos-status">
                <small id="resumen-recaudo" class="mini-muted"><strong>Total recaudado:</strong> $0</small>
                <small id="estado-carga" class="mini-muted">Cargando datos...</small>
            </div>
        </div>

        <?php if ($modoRedPagos): ?>
        <div id="pagos-resumen-personas" class="unified-pagos-summary">
            <button type="button" class="unified-pagos-summary-card unified-pagos-summary-card--ok unified-pagos-summary-card--click" data-filtro-indicador="pago_total" aria-pressed="false">
                <strong>Pago total (≥ $180.000)</strong>
                <div class="unified-pagos-summary-metric">
                    <span id="pagos-total-pago-completo">0</span>
                    <small>personas</small>
                </div>
            </button>
            <button type="button" class="unified-pagos-summary-card unified-pagos-summary-card--warn unified-pagos-summary-card--click" data-filtro-indicador="abono" aria-pressed="false">
                <strong>Abono (sin completar)</strong>
                <div class="unified-pagos-summary-metric">
                    <span id="pagos-total-abonos">0</span>
                    <small>personas</small>
                </div>
            </button>
        </div>
        <?php else: ?>
        <div id="resumen-genero-uv" class="unified-pagos-summary" hidden>
            <div class="unified-pagos-summary-card">
                <strong id="resumen-genero-uv-titulo">Consolidado de personas con pagos para el encuentro</strong>
                <div class="unified-pagos-summary-metric">
                    <span id="resumen-genero-uv-cantidad">0</span>
                    <small>personas</small>
                </div>
                <p id="resumen-genero-uv-detalle">Valor recaudado: $0</p>
            </div>
            <div class="unified-pagos-summary-card unified-pagos-summary-card--abonos">
                <strong id="resumen-abonos-uv-titulo">Consolidado de personas con abonos</strong>
                <div class="unified-pagos-summary-metric">
                    <span id="resumen-abonos-uv-cantidad">0</span>
                    <small>personas</small>
                </div>
                <p id="resumen-abonos-uv-detalle">Valor en abonos: $0</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- TAB: UNIFICADA -->
        <div id="tab-unificada" class="tabs-contenido is-active">
            <div class="unified-pagos-table-wrap">
                <table class="unified-pagos-table">
                    <thead id="tabla-head-unificada"></thead>
                    <tbody id="tabla-body-unificada">
                        <tr><td colspan="10">Cargando...</td></tr>
                    </tbody>
                    <tfoot id="tabla-foot-unificada"></tfoot>
                </table>
            </div>
        </div>

        <!-- TAB: POR GÉNERO -->
        <?php if (!$modoRedPagos): ?>
        <div id="tab-por-genero" class="tabs-contenido">
            <div style="margin-bottom:16px;">
                <h4 style="margin:0 0 12px 0;">Hombres (Jóvenes + Adultos)</h4>
                <div class="unified-pagos-table-wrap">
                    <table class="unified-pagos-table">
                        <thead id="tabla-head-hombres"></thead>
                        <tbody id="tabla-body-hombres">
                            <tr><td colspan="10">Cargando...</td></tr>
                        </tbody>
                        <tfoot id="tabla-foot-hombres"></tfoot>
                    </table>
                </div>
            </div>

            <div>
                <h4 style="margin:0 0 12px 0;">Mujeres (Jóvenes + Adultas)</h4>
                <div class="unified-pagos-table-wrap">
                    <table class="unified-pagos-table">
                        <thead id="tabla-head-mujeres"></thead>
                        <tbody id="tabla-body-mujeres">
                            <tr><td colspan="10">Cargando...</td></tr>
                        </tbody>
                        <tfoot id="tabla-foot-mujeres"></tfoot>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="detalle-modal" class="detail-modal" aria-hidden="true">
    <div class="detail-modal__backdrop"></div>
    <div class="detail-modal__panel">
        <div class="detail-modal__head">
            <div>
                <h3 id="detalle-modal-titulo" style="margin:0;">Detalle de pagos</h3>
                <small id="detalle-modal-subtitulo" class="mini-muted"></small>
            </div>
            <button type="button" id="detalle-modal-cerrar" class="btn btn-outline-secondary btn-sm">Cerrar</button>
        </div>
        <div class="unified-pagos-table-wrap">
            <table class="detail-modal__table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Programa</th>
                        <th>Método</th>
                        <th>Quién recibió</th>
                        <th>Tipo</th>
                        <th>Libro</th>
                        <th>Valor</th>
                        <th>Referencia</th>
                        <th>Ticket</th>
                    </tr>
                </thead>
                <tbody id="detalle-modal-body">
                    <tr><td colspan="9">Sin datos.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="ticket-modal" class="detail-modal" aria-hidden="true">
    <div class="detail-modal__backdrop"></div>
    <div class="detail-modal__panel detail-modal__panel--ticket">
        <div class="detail-modal__head">
            <div>
                <h3 style="margin:0;">Ticket de pago</h3>
                <small id="ticket-modal-subtitulo" class="mini-muted"></small>
            </div>
            <button type="button" id="ticket-modal-cerrar" class="btn btn-outline-secondary btn-sm">Cerrar</button>
        </div>
        <img id="ticket-pago-preview" class="pagos-ticket-preview" alt="Vista previa del ticket" hidden>
        <p id="ticket-modal-estado" class="mini-muted">Preparando imagen...</p>
        <div class="pagos-ticket-actions">
            <button type="button" id="btn-pagos-ticket-descargar" class="btn btn-outline-secondary btn-sm">Descargar imagen</button>
            <button type="button" id="btn-pagos-ticket-enviar" class="btn btn-primary btn-sm">Enviar imagen por WhatsApp</button>
        </div>
    </div>
</div>
<script type="application/json" id="ticket-pago-data">{}</script>
<div id="ticket-pago-export" class="ticket-pago-export ticket-pago-export-offscreen" aria-hidden="true">
    <div class="ticket-pago-export__brand">MCI Madrid Colombia</div>
    <div class="ticket-pago-export__title">Ticket de pago</div>
    <div class="ticket-pago-export__row"><span>Programa</span><strong id="ticket-exp-programa">—</strong></div>
    <div class="ticket-pago-export__row"><span>Fecha</span><strong id="ticket-exp-fecha">—</strong></div>
    <div class="ticket-pago-export__row"><span>Nombre</span><strong id="ticket-exp-nombre">—</strong></div>
    <div class="ticket-pago-export__row"><span>Documento</span><strong id="ticket-exp-documento">—</strong></div>
    <div class="ticket-pago-export__sep"></div>
    <div class="ticket-pago-export__row"><span>Método</span><strong id="ticket-exp-metodo">—</strong></div>
    <div class="ticket-pago-export__row"><span>Recibido por</span><strong id="ticket-exp-recibido">—</strong></div>
    <div class="ticket-pago-export__row"><span>Tipo</span><strong id="ticket-exp-tipo">—</strong></div>
    <div class="ticket-pago-export__row"><span>Valor</span><strong id="ticket-exp-valor">$0</strong></div>
    <div class="ticket-pago-export__ref-label">Referencia</div>
    <div id="ticket-exp-ref" class="ticket-pago-export__ref">—</div>
    <div id="ticket-exp-pie" class="ticket-pago-export__foot">Comprobante de pago</div>
</div>

<!-- Modal: Personas del ministerio -->
<div id="ministerio-modal" class="detail-modal" aria-hidden="true">
    <div class="detail-modal__backdrop"></div>
    <div class="detail-modal__panel">
        <div class="detail-modal__head">
            <div>
                <h3 id="ministerio-modal-titulo" style="margin:0;">Personas inscritas</h3>
                <small id="ministerio-modal-subtitulo" class="mini-muted"></small>
            </div>
            <button type="button" id="ministerio-modal-cerrar" class="btn btn-outline-secondary btn-sm">Cerrar</button>
        </div>
        <div class="unified-pagos-table-wrap">
            <table class="detail-modal__table">
                <thead>
                    <tr>
                        <th>Persona</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Segmento</th>
                        <th>Pagos</th>
                    </tr>
                </thead>
                <tbody id="ministerio-modal-body">
                    <tr><td colspan="5">Sin datos.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$urlHtml2canvas = function_exists('asset_url')
    ? asset_url('js/vendor/html2canvas.min.js')
    : (rtrim(ASSETS_URL, '/') . '/js/vendor/html2canvas.min.js?v=' . date('Ymd'));
$urlTicketJs = function_exists('asset_url')
    ? asset_url('js/taller_ticket_pago.js')
    : (rtrim(ASSETS_URL, '/') . '/js/taller_ticket_pago.js?v=' . date('Ymd'));
?>
<script src="<?= htmlspecialchars($urlHtml2canvas, ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($urlTicketJs, ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
(function() {
    const baseUrl = <?= json_encode(PUBLIC_URL . '?url=' . ($esUv ? 'escuelas_formacion/pagos/consolidar' : 'escuelas_formacion/pagos')) ?>;
    const urlReenviarTicket = <?= json_encode(PUBLIC_URL . '?url=escuelas_formacion/pagos/reenviar-ticket') ?>;
    const currentProgram = <?= json_encode($programa) ?>;
    const MODO_RED = <?= $modoRedPagos ? 'true' : 'false' ?>;
    const RED_PAGOS = <?= json_encode($redPagos) ?>;
    const FILTRO_GENERO_INICIAL = <?= json_encode($filtroGenero) ?>;
    
    // Elementos del DOM
    const programSelect = document.getElementById('programa-select');
    const buscarInput = document.getElementById('buscar');
    const filtroGeneroInput = document.getElementById('filtro-genero');
    const filtroMinisterioInput = document.getElementById('filtro-ministerio');
    const filtroLiderInput = document.getElementById('filtro-lider');
    const filtroEncuentroInput = document.getElementById('filtro-encuentro');
    const filtroEncuentroWrap = document.getElementById('filtro-encuentro-wrap');
    const filtroForm = document.getElementById('filtro-unificado-form');
    const btnLimpiar = document.getElementById('btn-limpiar');
    const btnDescargarPng = document.getElementById('btn-descargar-png');
    const estadoCarga = document.getElementById('estado-carga');
    const resumenRecaudo = document.getElementById('resumen-recaudo');
    const resumenGeneroUv = document.getElementById('resumen-genero-uv');
    const resumenGeneroUvTitulo = document.getElementById('resumen-genero-uv-titulo');
    const resumenGeneroUvCantidad = document.getElementById('resumen-genero-uv-cantidad');
    const resumenGeneroUvDetalle = document.getElementById('resumen-genero-uv-detalle');
    const resumenAbonosUvTitulo = document.getElementById('resumen-abonos-uv-titulo');
    const resumenAbonosUvCantidad = document.getElementById('resumen-abonos-uv-cantidad');
    const resumenAbonosUvDetalle = document.getElementById('resumen-abonos-uv-detalle');
    const programaLabel = document.getElementById('programa-label');
    
    // Tabs
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabsContenido = document.querySelectorAll('.tabs-contenido');
    
    // Modal detalle
    const detalleModal = document.getElementById('detalle-modal');
    const detalleModalBody = document.getElementById('detalle-modal-body');
    const detalleModalTitulo = document.getElementById('detalle-modal-titulo');
    const detalleModalSubtitulo = document.getElementById('detalle-modal-subtitulo');
    const detalleModalCerrar = document.getElementById('detalle-modal-cerrar');
    const ticketModal = document.getElementById('ticket-modal');
    const ticketModalCerrar = document.getElementById('ticket-modal-cerrar');
    const ticketModalSubtitulo = document.getElementById('ticket-modal-subtitulo');
    const ticketModalEstado = document.getElementById('ticket-modal-estado');
    const btnPagosTicketDescargar = document.getElementById('btn-pagos-ticket-descargar');
    const btnPagosTicketEnviar = document.getElementById('btn-pagos-ticket-enviar');
    
    // Modal ministerio
    const ministerioModal = document.getElementById('ministerio-modal');
    const ministerioModalTitulo = document.getElementById('ministerio-modal-titulo');
    const ministerioModalSubtitulo = document.getElementById('ministerio-modal-subtitulo');
    const ministerioModalBody = document.getElementById('ministerio-modal-body');
    const ministerioModalCerrar = document.getElementById('ministerio-modal-cerrar');
    
    let datosActuales = null;
    let resumenTablaBase = [];
    let filtroIndicador = '';
    const UMBRAL_PAGO_COMPLETO_UV = 180000;

    function etiquetaPrograma(programa) {
        const mapa = {
            universidad_vida: 'Universidad de la Vida',
            capacitacion_destino_nivel_1: 'Capacitación Destino - Nivel 1',
            capacitacion_destino_nivel_2: 'Capacitación Destino - Nivel 2',
            capacitacion_destino_nivel_3: 'Capacitación Destino - Nivel 3'
        };
        return mapa[programa] || programa;
    }

    function badge(semaforo) {
        const s = String(semaforo || 'rojo');
        return '<span class="badge-sem ' + s + '">' + s.charAt(0).toUpperCase() + s.slice(1) + '</span>';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function money(value) {
        const n = Number(value || 0);
        if (!Number.isFinite(n)) {
            return '$0';
        }
        return '$' + n.toLocaleString('es-CO');
    }

    function etiquetaProgramaTicket(codigo) {
        const map = {
            universidad_vida: 'Universidad de la Vida',
            encuentro: 'Encuentro',
            bautismo: 'Bautismo',
            capacitacion_destino: 'Capacitación Destino',
            capacitacion_destino_nivel_1: 'Capacitación Destino - Nivel 1',
            capacitacion_destino_nivel_2: 'Capacitación Destino - Nivel 2',
            capacitacion_destino_nivel_3: 'Capacitación Destino - Nivel 3'
        };
        const key = String(codigo || '').trim();
        return map[key] || key || 'Programa';
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    }

    function payloadTicketDesdeMovimiento(mov) {
        const programaLabelTicket = etiquetaProgramaTicket(mov.Programa || '');
        const valorTxt = money(mov.Valor_Pago || 0).replace(/^\$\s?/, '');
        return {
            formulario: programaLabelTicket,
            fecha: String(mov.Fecha_Registro || ''),
            etiqueta_nombre: 'Nombre',
            nombre: String(mov.Nombre || ''),
            documento: String(mov.Cedula || ''),
            metodo_pago: String(mov.Metodo_Pago || ''),
            recibido_por: String(mov.Recibido_Por || ''),
            tipo_pago: String(mov.Tipo_Pago || ''),
            valor_pago: valorTxt,
            referencia_pago: String(mov.Referencia_Pago || ''),
            pie_comprobante: 'Comprobante de pago — ' + programaLabelTicket
        };
    }

    function pintarTicketExport(data) {
        setText('ticket-exp-programa', data.formulario);
        setText('ticket-exp-fecha', data.fecha);
        setText('ticket-exp-nombre', data.nombre);
        setText('ticket-exp-documento', data.documento);
        setText('ticket-exp-metodo', data.metodo_pago);
        setText('ticket-exp-recibido', data.recibido_por);
        setText('ticket-exp-tipo', data.tipo_pago);
        setText('ticket-exp-valor', '$' + (data.valor_pago || '0'));
        setText('ticket-exp-ref', data.referencia_pago);
        setText('ticket-exp-pie', data.pie_comprobante);
    }

    async function blobToDataUrl(blob) {
        return new Promise(function (resolve, reject) {
            const reader = new FileReader();
            reader.onload = function () { resolve(String(reader.result || '')); };
            reader.onerror = function () { reject(new Error('No se pudo leer la imagen del ticket.')); };
            reader.readAsDataURL(blob);
        });
    }

    async function abrirTicket(mov) {
        const data = payloadTicketDesdeMovimiento(mov);
        if (!data.referencia_pago) {
            alert('Este movimiento no tiene referencia de ticket.');
            return;
        }
        pintarTicketExport(data);
        if (window.TallerTicketPago && typeof window.TallerTicketPago.setTicketData === 'function') {
            window.TallerTicketPago.setTicketData(data);
        }
        if (ticketModalSubtitulo) {
            ticketModalSubtitulo.textContent = (data.nombre || '') + ' · ' + data.referencia_pago;
        }
        const preview = document.getElementById('ticket-pago-preview');
        if (preview) {
            preview.hidden = true;
            preview.removeAttribute('src');
        }
        if (ticketModalEstado) ticketModalEstado.textContent = 'Preparando imagen...';
        ticketModal.classList.add('is-open');
        ticketModal.setAttribute('aria-hidden', 'false');
        try {
            if (window.TallerTicketPago && typeof window.TallerTicketPago.mostrarVistaPrevia === 'function') {
                await window.TallerTicketPago.mostrarVistaPrevia();
            }
            if (preview && preview.getAttribute('src')) {
                preview.hidden = false;
            }
            if (ticketModalEstado) ticketModalEstado.textContent = 'Puedes descargar el ticket o reenviarlo por WhatsApp como imagen.';
        } catch (err) {
            if (ticketModalEstado) ticketModalEstado.textContent = err.message || 'No se pudo generar la imagen.';
        }
    }

    function cerrarTicket() {
        if (!ticketModal) return;
        ticketModal.classList.remove('is-open');
        ticketModal.setAttribute('aria-hidden', 'true');
    }

    function clasificarGeneroBase(genero) {
        const g = String(genero || '').trim().toLowerCase();
        if (!g) return 'otro';
        const esMujer = g.includes('mujer') || g.includes('femen')
            || /(^|[^a-z])(f|fem|female)([^a-z]|$)/.test(g);
        const esHombre = g.includes('hombre') || g.includes('mascul')
            || /(^|[^a-z])(m|masc|male|h)([^a-z]|$)/.test(g);
        if (esHombre && !esMujer) return 'hombre';
        if (esMujer && !esHombre) return 'mujer';
        return 'otro';
    }

    function resolverSegmento(row) {
        const segPref = String(row.segmento_preferido || row.Segmento_Preferido || '').trim().toLowerCase();
        if (['jovenes', 'teens', 'hombres_adultos', 'mujeres_adultas'].includes(segPref)) {
            return segPref;
        }

        const edad = Number(row.edad || row.Edad || 0);
        const gc = clasificarGeneroBase(row.genero);
        if (edad >= 14 && edad <= 28) return 'jovenes';
        if (edad >= 9 && edad <= 13) return 'teens';
        if ((edad >= 29 || edad <= 0) && gc === 'hombre') return 'hombres_adultos';
        if ((edad >= 29 || edad <= 0) && gc === 'mujer') return 'mujeres_adultas';

        const g = String(row.genero || '').trim().toLowerCase();
        if (g.includes('joven')) return 'jovenes';
        return 'otros';
    }

    function getSegmento(rowOrGenero) {
        if (typeof rowOrGenero !== 'object') {
            const gc = clasificarGeneroBase(rowOrGenero);
            if (gc === 'hombre') return 'Hombre';
            if (gc === 'mujer') return 'Mujer';
            return '-';
        }
        const partes = [];
        const gc = clasificarGeneroBase(rowOrGenero.genero || rowOrGenero.Genero);
        if (gc === 'hombre') partes.push('Hombre');
        if (gc === 'mujer') partes.push('Mujer');
        const edad = Number(rowOrGenero.edad || rowOrGenero.Edad || 0);
        if (edad >= 9 && edad <= 13) {
            partes.push('Teen');
        } else if (edad >= 14 && edad <= 28) {
            partes.push('Joven');
        }
        return partes.length ? partes.join(' · ') : '-';
    }

    function esGeneroHombre(row) {
        return clasificarGeneroBase(row.genero || row.Genero) === 'hombre';
    }

    function esGeneroMujer(row) {
        return clasificarGeneroBase(row.genero || row.Genero) === 'mujer';
    }

    function esGeneroJoven(row) {
        return resolverSegmento(row) === 'jovenes';
    }

    function esGeneroTeen(row) {
        return resolverSegmento(row) === 'teens';
    }

    function poblarFiltroLideresPagos(rows) {
        if (!filtroLiderInput) return;
        const actual = String(filtroLiderInput.value || '').trim();
        const lideres = [];
        const seen = {};
        (rows || []).forEach(function (row) {
            const nombre = String(row.lider || '').trim();
            if (nombre === '' || seen[nombre]) return;
            seen[nombre] = true;
            lideres.push(nombre);
        });
        lideres.sort(function (a, b) {
            return a.localeCompare(b, 'es', { sensitivity: 'base' });
        });
        filtroLiderInput.innerHTML = '<option value="">Todos los líderes</option>';
        lideres.forEach(function (nombre) {
            const opt = document.createElement('option');
            opt.value = nombre;
            opt.textContent = nombre;
            filtroLiderInput.appendChild(opt);
        });
        if (actual && seen[actual]) {
            filtroLiderInput.value = actual;
        }
    }

    function calcularTotales(rows) {
        return (rows || []).reduce((acc, row) => {
            acc.pagado += Number(row.total_pagado || 0);
            acc.pagadoCompleto += Number(row.total_pago_completo || 0);
            acc.abonos += Number(row.total_abonos || 0);
            return acc;
        }, { pagado: 0, pagadoCompleto: 0, abonos: 0 });
    }

    function tienePagoRegistradoRow(row) {
        return Number(row && row.total_pagado ? row.total_pagado : 0) > 0
            || Number(row && row.registros_pago ? row.registros_pago : 0) > 0;
    }

    function filasResumenActivas() {
        const rows = (datosActuales && datosActuales.resumen) ? datosActuales.resumen : [];
        const filtroGenero = filtroGeneroInput ? String(filtroGeneroInput.value || '') : '';
        return filtrarResumenPorGenero(rows, filtroGenero);
    }

    function hayFiltrosPagosActivos() {
        const buscar = buscarInput ? String(buscarInput.value || '').trim() : '';
        const genero = filtroGeneroInput ? String(filtroGeneroInput.value || '') : '';
        const ministerio = filtroMinisterioInput ? String(filtroMinisterioInput.value || '') : '';
        const encuentro = filtroEncuentroInput ? String(filtroEncuentroInput.value || '') : '';
        return buscar !== '' || genero !== '' || ministerio !== '' || (encuentro !== '' && encuentro !== 'todos');
    }

    function getEtiquetaFiltroEncuentro(valor) {
        const mapa = {
            excluir_asistieron: 'Excluir quienes ya asistieron al encuentro',
            sin_encuentro: 'Sin asistir al encuentro',
            sin_dia1: 'Sin asistencia día 1',
            sin_dia2: 'Sin asistencia día 2',
            con_dia1: 'Con asistencia día 1',
            con_dia2: 'Con asistencia día 2',
            con_ambos: 'Asistieron ambos días',
            con_al_menos_uno: 'Asistieron al menos un día del encuentro'
        };
        return mapa[String(valor || '')] || '';
    }

    function actualizarVisibilidadFiltroEncuentro(programa) {
        if (!filtroEncuentroWrap) {
            return;
        }
        filtroEncuentroWrap.hidden = true;
        if (filtroEncuentroInput) {
            filtroEncuentroInput.value = 'todos';
        }
    }

    function ensurePagosResumenPersonasDom() {
        if (MODO_RED) {
            return;
        }
        if (document.getElementById('pagos-resumen-personas')) {
            return;
        }
        const ref = document.getElementById('resumen-genero-uv');
        if (!ref || !ref.parentNode) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.id = 'pagos-resumen-personas';
        wrap.className = 'unified-pagos-summary';
        wrap.innerHTML = MODO_RED
            ? (''
                + '<button type="button" class="unified-pagos-summary-card unified-pagos-summary-card--ok unified-pagos-summary-card--click" data-filtro-indicador="pago_total" aria-pressed="false"><strong>Pago completo UV (&ge; $180.000)</strong>'
                + '<div class="unified-pagos-summary-metric"><span id="pagos-total-pago-completo">0</span><small>personas</small></div></button>'
                + '<button type="button" class="unified-pagos-summary-card unified-pagos-summary-card--warn unified-pagos-summary-card--click" data-filtro-indicador="abono" aria-pressed="false"><strong>Solo abono (sin completar)</strong>'
                + '<div class="unified-pagos-summary-metric"><span id="pagos-total-abonos">0</span><small>personas</small></div></button>')
            : (''
            + '<div class="unified-pagos-summary-card"><strong id="pagos-label-inscritas">Inscritas UV</strong>'
            + '<div class="unified-pagos-summary-metric"><span id="pagos-total-inscritas">0</span><small>inscritas</small></div>'
            + '<p id="pagos-sub-inscritas" style="margin:0;font-size:0.78rem;color:#52657d;">Con movimiento en tabla: <span id="pagos-total-personas">0</span></p></div>'
            + '<div class="unified-pagos-summary-card unified-pagos-summary-card--ok"><strong id="pagos-label-con-pago">Con pago registrado</strong>'
            + '<div class="unified-pagos-summary-metric"><span id="pagos-total-con-pago">0</span><small>personas</small></div>'
            + '<p id="pagos-sub-con-pago" style="margin:0;font-size:0.78rem;color:#52657d;" hidden></p></div>'
            + '<button type="button" class="unified-pagos-summary-card unified-pagos-summary-card--ok unified-pagos-summary-card--click" data-filtro-indicador="pago_total" aria-pressed="false"><strong>Pago completo UV (&ge; $180.000)</strong>'
            + '<div class="unified-pagos-summary-metric"><span id="pagos-total-pago-completo">0</span><small>personas</small></div></button>'
            + '<button type="button" class="unified-pagos-summary-card unified-pagos-summary-card--warn unified-pagos-summary-card--click" data-filtro-indicador="abono" aria-pressed="false"><strong>Solo abono (sin completar)</strong>'
            + '<div class="unified-pagos-summary-metric"><span id="pagos-total-abonos">0</span><small>personas</small></div></button>');

        const filtro = document.createElement('p');
        filtro.id = 'pagos-resumen-filtro';
        filtro.className = 'unified-pagos-resumen-filtro';
        filtro.hidden = true;

        ref.parentNode.insertBefore(wrap, ref);
        ref.parentNode.insertBefore(filtro, ref);
    }

    function actualizarResumenPersonasPagos(programa, rows) {
        ensurePagosResumenPersonasDom();

        const esUv = String(programa) === 'universidad_vida';
        const filasNorm = esUv ? (rows || []).map((row) => normalizarFilaPagoUv(row)) : (rows || []);
        const statsIns = (datosActuales && datosActuales.stats_inscripciones) ? datosActuales.stats_inscripciones : null;

        const total = filasNorm.length;
        const conPago = filasNorm.filter((row) => tienePagoRegistradoRow(row)).length;
        const pagoCompleto = filasNorm.filter((row) => tienePagoTotalUv(row)).length;
        const conAbono = filasNorm.filter((row) => tieneSoloAbonoUv(row)).length;

        if (MODO_RED) {
            const elAbonosRed = document.getElementById('pagos-total-abonos');
            const elCompletoRed = document.getElementById('pagos-total-pago-completo');
            if (elAbonosRed) elAbonosRed.textContent = String(conAbono);
            if (elCompletoRed) elCompletoRed.textContent = String(pagoCompleto);
            if (resumenGeneroUv) resumenGeneroUv.hidden = true;
            const elFiltroRed = document.getElementById('pagos-resumen-filtro');
            if (elFiltroRed) elFiltroRed.hidden = true;
            return;
        }

        const filtrosActivos = hayFiltrosPagosActivos();
        const rowsGlobal = (datosActuales && datosActuales.resumen) ? datosActuales.resumen : rows;
        const totalGlobal = rowsGlobal.length;
        const conPagoGlobal = rowsGlobal.filter((row) => tienePagoRegistradoRow(row)).length;

        const inscritas = statsIns ? Number(statsIns.inscritas || 0) : total;
        const conPagoIns = statsIns ? Number(statsIns.con_pago_registrado || 0) : conPago;
        const completoIns = statsIns ? Number(statsIns.pago_completo || 0) : pagoCompleto;
        const abonoIns = statsIns ? Number(statsIns.solo_abono || 0) : conAbono;

        const elTotal = document.getElementById('pagos-total-personas');
        const elInscritas = document.getElementById('pagos-total-inscritas');
        const elConPago = document.getElementById('pagos-total-con-pago');
        const elAbonos = document.getElementById('pagos-total-abonos');
        const elCompleto = document.getElementById('pagos-total-pago-completo');
        const elSubConPago = document.getElementById('pagos-sub-con-pago');
        const elFiltro = document.getElementById('pagos-resumen-filtro');
        const elLabelInscritas = document.getElementById('pagos-label-inscritas');

        if (elInscritas) elInscritas.textContent = String(inscritas);
        if (elTotal) elTotal.textContent = String(total);
        if (elConPago) elConPago.textContent = String(esUv && statsIns ? conPagoIns : conPago);
        if (elAbonos) elAbonos.textContent = String(esUv && statsIns ? abonoIns : conAbono);
        if (elCompleto) elCompleto.textContent = String(esUv && statsIns ? completoIns : pagoCompleto);
        if (elLabelInscritas && filtrosActivos && filtroGeneroInput && filtroGeneroInput.value) {
            elLabelInscritas.textContent = 'Inscritas (' + getEtiquetaFiltroGenero(filtroGeneroInput.value) + ')';
        } else if (elLabelInscritas) {
            elLabelInscritas.textContent = 'Inscritas UV';
        }

        if (conPago > total) {
            console.warn('Conteo de pagos: con pago supera filas visibles', { total, conPago });
        }

        if (elSubConPago) {
            if (filtrosActivos && total !== totalGlobal) {
                elSubConPago.textContent = 'de ' + conPagoGlobal + ' con pago en total';
                elSubConPago.hidden = false;
            } else {
                elSubConPago.hidden = true;
            }
        }

        if (elFiltro) {
            if (filtrosActivos) {
                const partes = [];
                if (buscarInput && buscarInput.value.trim() !== '') {
                    partes.push('Búsqueda: «' + buscarInput.value.trim() + '»');
                }
                if (filtroGeneroInput && filtroGeneroInput.value !== '') {
                    partes.push(getEtiquetaFiltroGenero(filtroGeneroInput.value));
                }
                if (filtroMinisterioInput && filtroMinisterioInput.value !== '') {
                    partes.push('Ministerio: ' + filtroMinisterioInput.value);
                }
                if (filtroEncuentroInput && filtroEncuentroInput.value && filtroEncuentroInput.value !== 'todos') {
                    const ee = getEtiquetaFiltroEncuentro(filtroEncuentroInput.value);
                    if (ee) partes.push(ee);
                }
                const sinPagoIns = statsIns ? Number(statsIns.sin_pago || 0) : 0;
                const detalleFiltro = esUv && statsIns
                    ? (' — ' + inscritas + ' inscritas · ' + sinPagoIns + ' sin pago · ' + completoIns + ' pago completo · ' + abonoIns + ' solo abono · ' + conPagoIns + ' con pago')
                    : (' — ' + total + ' en tabla · ' + conPago + ' con pago');
                elFiltro.textContent = 'Filtro activo: ' + partes.join(' · ') + detalleFiltro;
                elFiltro.hidden = false;
            } else {
                elFiltro.hidden = true;
                elFiltro.textContent = '';
            }
        }

        if (String(programa) !== 'universidad_vida' && document.getElementById('pagos-resumen-personas')) {
            const cardCompleto = document.querySelector('#pagos-resumen-personas .unified-pagos-summary-card:last-child');
            if (cardCompleto) cardCompleto.style.display = 'none';
        }
    }

    function valorPagosSinAbonos(row) {
        return Number(row && row.total_pago_completo ? row.total_pago_completo : 0);
    }

    function tienePagoTotalUv(row) {
        return Number(row && row.total_pagado ? row.total_pagado : 0) >= UMBRAL_PAGO_COMPLETO_UV;
    }

    function tieneSoloAbonoUv(row) {
        if (!row || tienePagoTotalUv(row)) {
            return false;
        }
        return Number(row.total_abonos || 0) > 0;
    }

    function normalizarFilaPagoUv(row) {
        if (!row) {
            return row;
        }
        const totalPagado = Number(row.total_pagado || 0);
        if (totalPagado >= UMBRAL_PAGO_COMPLETO_UV) {
            return Object.assign({}, row, {
                total_abonos: 0,
                total_pago_completo: totalPagado,
                pago_completo: true,
            });
        }
        return row;
    }

    function filasPorIndicador(rows) {
        const lista = rows || [];
        const programa = datosActuales && datosActuales.programa ? String(datosActuales.programa) : String(currentProgram || '');
        const norm = programa === 'universidad_vida' ? lista.map((row) => normalizarFilaPagoUv(row)) : lista;
        if (filtroIndicador === 'pago_total') {
            return norm.filter((row) => tienePagoTotalUv(row));
        }
        if (filtroIndicador === 'abono') {
            return norm.filter((row) => tieneSoloAbonoUv(row));
        }
        return norm;
    }

    function marcarTarjetasIndicador() {
        document.querySelectorAll('[data-filtro-indicador]').forEach((btn) => {
            const activo = String(btn.getAttribute('data-filtro-indicador') || '') === filtroIndicador;
            btn.classList.toggle('is-active', activo);
            btn.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });
    }

    function pintarTablaIndicador(programa) {
        const filas = filasPorIndicador(resumenTablaBase);
        renderHead(programa, 'unificada');
        renderRows(programa, filas, null, 'unificada');
        marcarTarjetasIndicador();
        if (estadoCarga) {
            let extra = '';
            if (filtroIndicador === 'pago_total') extra = ' · filtro: pago total';
            if (filtroIndicador === 'abono') extra = ' · filtro: abono';
            estadoCarga.textContent = 'Datos actualizados'
                + (filas.length ? (' · ' + filas.length + ' en tabla') : '')
                + extra;
        }
    }

    function renderFooter(programa, rows, tabId = 'unificada') {
        const isUv = String(programa) === 'universidad_vida';
        const footId = tabId === 'unificada' ? 'tabla-foot-unificada' : (tabId === 'hombres' ? 'tabla-foot-hombres' : 'tabla-foot-mujeres');
        const footElement = document.getElementById(footId);
        if (!footElement) {
            return;
        }


        const sumaPagosCompletos = (rows || []).reduce((acc, row) => acc + Number(row.total_pago_completo || 0), 0);
        const sumaTodosPagos = (rows || []).reduce((acc, row) => acc + Number(row.total_pagado || 0), 0);
        const sumaAbonos = (rows || []).reduce((acc, row) => acc + Number(row.total_abonos || 0), 0);

        if (isUv) {
            footElement.innerHTML = '<tr>' +
                '<td colspan="' + (MODO_RED ? '4' : '5') + '">TOTAL RECAUDADO</td>' +
                '<td>' + sumaAbonos.toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                '<td>' + sumaPagosCompletos.toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                '<td>' + sumaTodosPagos.toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                '<td></td>' +
            '</tr>';
            return;
        }

        footElement.innerHTML = '<tr>' +
            '<td colspan="6">TOTAL RECAUDADO</td>' +
            '<td>' + sumaTodosPagos.toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
            '<td>' + sumaAbonos.toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
            '<td></td>' +
            '<td></td>' +
        '</tr>';
    }

    function actualizarResumenRecaudo(programa, rows) {
        if (!resumenRecaudo) {
            return;
        }
        const totals = calcularTotales(rows);
        const etiqueta = String(programa) === 'universidad_vida' ? 'Total recaudado U. de la Vida:' : 'Total recaudado:';
        resumenRecaudo.innerHTML = '<strong>' + etiqueta + '</strong> ' + money(totals.pagado);
    }

    function getEtiquetaFiltroGenero(genero) {
        const valor = String(genero || '').trim().toLowerCase();
        if (valor === 'hombres') {
            return 'hombres';
        }
        if (valor === 'mujeres') {
            return 'mujeres';
        }
        if (valor === 'jovenes') {
            return 'jóvenes';
        }
        if (valor === 'teens') {
            return 'teens';
        }
        return 'personas';
    }

    function filtrarResumenPorGenero(rows, genero) {
        const valor = String(genero || '').trim().toLowerCase();
        if (valor === 'hombres_adultos') {
            return (rows || []).filter((row) => resolverSegmento(row) === 'hombres_adultos');
        }
        if (valor === 'mujeres_adultas') {
            return (rows || []).filter((row) => resolverSegmento(row) === 'mujeres_adultas');
        }
        if (valor === 'hombres') {
            return (rows || []).filter((row) => esGeneroHombre(row));
        }
        if (valor === 'mujeres') {
            return (rows || []).filter((row) => esGeneroMujer(row));
        }
        if (valor === 'jovenes') {
            return (rows || []).filter((row) => esGeneroJoven(row));
        }
        if (valor === 'teens') {
            return (rows || []).filter((row) => esGeneroTeen(row));
        }
        return rows || [];
    }

    function actualizarResumenGeneroUv(programa, rows, filtroGenero) {
        if (!resumenGeneroUv || !resumenGeneroUvTitulo || !resumenGeneroUvCantidad || !resumenGeneroUvDetalle || !resumenAbonosUvTitulo || !resumenAbonosUvCantidad || !resumenAbonosUvDetalle) {
            return;
        }

        if (MODO_RED || String(programa) !== 'universidad_vida') {
            resumenGeneroUv.hidden = true;
            resumenGeneroUvTitulo.textContent = 'Consolidado de personas con pagos para el encuentro';
            resumenGeneroUvCantidad.textContent = '0';
            resumenGeneroUvDetalle.textContent = 'Valor recaudado: $0';
            resumenAbonosUvTitulo.textContent = 'Consolidado de personas con abonos';
            resumenAbonosUvCantidad.textContent = '0';
            resumenAbonosUvDetalle.textContent = 'Valor en abonos: $0';
            return;
        }

        const etiqueta = getEtiquetaFiltroGenero(filtroGenero);
        const filasBase = filtrarResumenPorGenero(rows, filtroGenero).map((row) => normalizarFilaPagoUv(row));
        const personasConPagoTotal = filasBase.filter((row) => tienePagoTotalUv(row));

        const totalPersonas = personasConPagoTotal.length;
        const valorTotal = personasConPagoTotal.reduce((acc, row) => acc + Number(row.total_pagado || 0), 0);
        const personasConAbono = filasBase.filter((row) => tieneSoloAbonoUv(row));
        const totalAbonosPersonas = personasConAbono.length;
        const valorAbonos = personasConAbono.reduce((acc, row) => acc + Number(row.total_abonos || 0), 0);

        resumenGeneroUv.hidden = false;
        resumenGeneroUvTitulo.textContent = 'Consolidado de ' + etiqueta + ' con pago total (>= ' + money(UMBRAL_PAGO_COMPLETO_UV) + ')';
        resumenGeneroUvCantidad.textContent = String(totalPersonas);
        resumenGeneroUvDetalle.textContent = 'Valor total completo: ' + money(valorTotal);
        resumenAbonosUvTitulo.textContent = 'Consolidado de ' + etiqueta + ' con abonos';
        resumenAbonosUvCantidad.textContent = String(totalAbonosPersonas);
        resumenAbonosUvDetalle.textContent = 'Valor en abonos: ' + money(valorAbonos);
    }

    function renderHead(programa, tabId = 'unificada') {
        const isUv = String(programa) === 'universidad_vida';
        const columns = isUv
            ? (MODO_RED
                ? ['Persona', 'Cédula', 'Teléfono', 'Ministerio', 'Abonos', 'Pago total', 'Pago general', 'Detalle']
                : ['Persona', 'Cédula', 'Teléfono', 'Ministerio', 'Segmento', 'Abonos', 'Pago total', 'Pago general', 'Detalle'])
            : ['Persona', 'Cédula', 'Teléfono', 'Ministerio', 'Segmento', 'Nivel', 'Pagos', 'Abonos', 'Nota', 'Detalle'];

        const headId = tabId === 'unificada' ? 'tabla-head-unificada' : (tabId === 'hombres' ? 'tabla-head-hombres' : 'tabla-head-mujeres');
        const headElement = document.getElementById(headId);
        if (headElement) {
            headElement.innerHTML = '<tr>' + columns.map((col) => '<th>' + escapeHtml(col) + '</th>').join('') + '</tr>';
        }
    }

    function renderRows(programa, rows, filtrarPor = null, tabId = 'unificada') {
        const isUv = String(programa) === 'universidad_vida';
        
        // Filtrar por género si es necesario
        let rowsFiltradas = rows;
        if (filtrarPor) {
            rowsFiltradas = rows.filter(r => {
                if (filtrarPor === 'hombres') {
                    return esGeneroHombre(r);
                }
                if (filtrarPor === 'mujeres') {
                    return esGeneroMujer(r);
                }
                return true;
            });
        }

        const bodyId = tabId === 'unificada' ? 'tabla-body-unificada' : (tabId === 'hombres' ? 'tabla-body-hombres' : 'tabla-body-mujeres');
        const bodyElement = document.getElementById(bodyId);
        
        if (!rowsFiltradas || !rowsFiltradas.length) {
            const colspan = isUv ? (MODO_RED ? 8 : 9) : 10;
            if (bodyElement) {
                bodyElement.innerHTML = '<tr><td colspan="' + colspan + '">No hay datos para este filtro.</td></tr>';
            }
            renderFooter(programa, [], tabId);
            return;
        }

        if (bodyElement) {
            if (isUv) {
                rowsFiltradas = rowsFiltradas.map((row) => normalizarFilaPagoUv(row));
            }
            bodyElement.innerHTML = rowsFiltradas.map((row) => {
                const detalleClave = escapeHtml(row.cedula_clave || row.cedula || '');
                const segmento = getSegmento(row);
                const notaTxt = row.nota_final === null || row.nota_final === undefined || row.nota_final === ''
                    ? '-'
                    : Number(row.nota_final).toFixed(1) + '%';
                
                const ministerioHtml = row.ministerio && String(row.ministerio).trim() !== ''
                    ? escapeHtml(row.ministerio)
                    : '<span class="mini-muted">-</span>';

                if (isUv) {
                    const celdaSegmento = MODO_RED ? '' : ('<td>' + escapeHtml(segmento) + '</td>');
                    return '<tr>' +
                        '<td>' + escapeHtml(row.persona || '') + '</td>' +
                        '<td>' + escapeHtml(row.cedula || '') + '</td>' +
                        '<td>' + escapeHtml(row.telefono || '') + '</td>' +
                        '<td>' + ministerioHtml + '</td>' +
                        celdaSegmento +
                        '<td>' + Number(row.total_abonos || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                        '<td>' + Number(row.total_pago_completo || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                        '<td>' + Number(row.total_pagado || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                        '<td><button type="button" class="btn btn-outline-secondary btn-sm js-ver-detalle" data-cedula="' + detalleClave + '">Ver</button></td>' +
                    '</tr>';
                }

                return '<tr>' +
                    '<td>' + escapeHtml(row.persona || '') + '</td>' +
                    '<td>' + escapeHtml(row.cedula || '') + '</td>' +
                    '<td>' + escapeHtml(row.telefono || '') + '</td>' +
                    '<td>' + ministerioHtml + '</td>' +
                    '<td>' + escapeHtml(segmento) + '</td>' +
                    '<td>' + escapeHtml(row.programa_label || '') + '</td>' +
                    '<td>' + Number(row.total_pagado || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                    '<td>' + Number(row.total_abonos || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 }) + '</td>' +
                    '<td>' + notaTxt + '</td>' +
                    '<td><button type="button" class="btn btn-outline-secondary btn-sm js-ver-detalle" data-cedula="' + detalleClave + '">Ver</button></td>' +
                '</tr>';
            }).join('');

            // Agregar event listeners
            document.querySelectorAll('.js-ver-detalle').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const cedula = String(btn.dataset.cedula || '');
                    if (!cedula) return;
                    await cargarDetalle(cedula);
                });
            });

            renderFooter(programa, rowsFiltradas, tabId);
        }
    }

    async function cargarDatos() {
        const params = new URLSearchParams();
        params.set('ajax', '1');
        params.set('programa', programSelect ? String(programSelect.value || currentProgram) : currentProgram);
        params.set('buscar', String(buscarInput ? buscarInput.value : ''));
        params.set('filtro_genero', String(filtroGeneroInput ? filtroGeneroInput.value : ''));
        params.set('filtro_ministerio', String(filtroMinisterioInput ? filtroMinisterioInput.value : ''));
        params.set('filtro_encuentro', String(filtroEncuentroInput ? filtroEncuentroInput.value : 'todos'));
        if (RED_PAGOS) {
            params.set('red', RED_PAGOS);
        }
        const anioInput = document.getElementById('filtro-anio');
        const semestreInput = document.getElementById('filtro-semestre');
        if (anioInput && anioInput.value) {
            params.set('anio', anioInput.value);
        }
        if (semestreInput && semestreInput.value) {
            params.set('semestre', semestreInput.value);
        }

        estadoCarga.textContent = 'Cargando...';

        try {
            const response = await fetch(baseUrl + '&' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const raw = await response.text();

            let data = null;
            try {
                data = JSON.parse(raw);
            } catch (parseError) {
                throw new Error('Respuesta inválida del servidor. Intenta recargar la página.');
            }

            if (!response.ok || !data || !data.ok) {
                throw new Error((data && (data.mensaje || data.error)) || 'No se pudieron cargar los datos');
            }

            datosActuales = data;

            // Actualizar selector de ministerios
            if (data.ministerios && filtroMinisterioInput) {
                const ministeriosOptions = '<option value="">Todos los ministerios</option>' + 
                    data.ministerios.map(m => '<option value="' + escapeHtml(m.nombre) + '"' + (<?= json_encode($filtroMinisterio) ?> === m.nombre ? ' selected' : '') + '>' + escapeHtml(m.nombre) + '</option>').join('');
                filtroMinisterioInput.innerHTML = ministeriosOptions;
            }

            if (programaLabel) {
                programaLabel.textContent = data.programa_label || etiquetaPrograma(data.programa);
            }

            actualizarVisibilidadFiltroEncuentro(data.programa);

            let resumenTabla = data.resumen || [];
            if (String(data.programa) === 'universidad_vida') {
                resumenTabla = resumenTabla.filter((row) => tienePagoRegistradoRow(row) || Number(row.total_abonos || 0) > 0);
                poblarFiltroLideresPagos(resumenTabla);
                const liderSel = filtroLiderInput ? String(filtroLiderInput.value || '').trim() : '';
                if (liderSel !== '') {
                    resumenTabla = resumenTabla.filter((row) => String(row.lider || '').trim() === liderSel);
                }
                resumenTabla.sort(function (a, b) {
                    const la = String(a.lider || '').trim() || '\uffff';
                    const lb = String(b.lider || '').trim() || '\uffff';
                    const cmpL = la.localeCompare(lb, 'es', { sensitivity: 'base' });
                    if (cmpL !== 0) return cmpL;
                    return String(a.persona || '').localeCompare(String(b.persona || ''), 'es', { sensitivity: 'base' });
                });
            }

            resumenTablaBase = resumenTabla;

            if (!MODO_RED) {
                renderHead(data.programa, 'hombres');
                renderRows(data.programa, data.resumen || [], 'hombres', 'hombres');

                renderHead(data.programa, 'mujeres');
                renderRows(data.programa, data.resumen || [], 'mujeres', 'mujeres');
            }

            const filasActivas = String(data.programa) === 'universidad_vida'
                ? resumenTabla.map((row) => normalizarFilaPagoUv(row))
                : resumenTabla;
            actualizarResumenRecaudo(data.programa, filasPorIndicador(resumenTablaBase));
            actualizarResumenPersonasPagos(data.programa, filasActivas);
            actualizarResumenGeneroUv(data.programa, filasActivas, filtroGeneroInput ? filtroGeneroInput.value : '');
            pintarTablaIndicador(data.programa);
        } catch (error) {
            estadoCarga.textContent = 'Error al cargar';
            console.error(error);
            const colspan = 10;
            const msg = '<tr><td colspan="' + colspan + '">' + escapeHtml(error.message || 'No se pudo cargar la información') + '</td></tr>';
            const bodyUni = document.getElementById('tabla-body-unificada');
            if (bodyUni) bodyUni.innerHTML = msg;
            const bodyH = document.getElementById('tabla-body-hombres');
            if (bodyH) bodyH.innerHTML = msg;
            const bodyM = document.getElementById('tabla-body-mujeres');
            if (bodyM) bodyM.innerHTML = msg;
            const footUni = document.getElementById('tabla-foot-unificada');
            if (footUni) footUni.innerHTML = '';
            const footH = document.getElementById('tabla-foot-hombres');
            if (footH) footH.innerHTML = '';
            const footM = document.getElementById('tabla-foot-mujeres');
            if (footM) footM.innerHTML = '';
            if (resumenRecaudo) {
                resumenRecaudo.innerHTML = '<strong>Total recaudado:</strong> $0';
            }
            if (resumenGeneroUv) {
                resumenGeneroUv.hidden = true;
            }
        }
    }

    async function cargarDetalle(cedula) {
        const params = new URLSearchParams();
        params.set('ajax', '1');
        params.set('programa', programSelect ? String(programSelect.value || currentProgram) : currentProgram);
        params.set('cedula', cedula);

        try {
            const response = await fetch(baseUrl + '&' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const raw = await response.text();

            let data = null;
            try {
                data = JSON.parse(raw);
            } catch (parseError) {
                throw new Error('No se pudo cargar el detalle.');
            }

            if (!response.ok || !data || !data.ok) {
                throw new Error((data && (data.mensaje || data.error)) || 'No se pudo cargar el detalle.');
            }

            detalleModalTitulo.textContent = 'Detalle de pagos';
            detalleModalSubtitulo.textContent = 'Clave de persona: ' + cedula;

            const detalle = data.detalle || [];
            if (!detalle.length) {
                detalleModalBody.innerHTML = '<tr><td colspan="9">No hay movimientos para esta persona.</td></tr>';
            } else {
                detalleModalBody.innerHTML = detalle.map((mov) => {
                    const entregoLibroRaw = mov.Entrego_Libro ?? null;
                    const entregoLibroTxt = entregoLibroRaw === null || entregoLibroRaw === '' ? '-' : (Number(entregoLibroRaw) === 1 ? 'Sí' : 'No');
                    const refPago = String(mov.Referencia_Pago || '').trim();
                    const ticketBtn = refPago
                        ? '<button type="button" class="btn btn-outline-primary btn-sm js-ver-ticket" data-ref="' + escapeHtml(refPago) + '">Ver ticket</button>'
                        : '<span class="mini-muted">—</span>';
                    return '<tr>' +
                        '<td>' + escapeHtml(mov.Fecha_Registro || '') + '</td>' +
                        '<td>' + escapeHtml(mov.Programa || '') + '</td>' +
                        '<td>' + escapeHtml(mov.Metodo_Pago || '') + '</td>' +
                        '<td>' + escapeHtml(mov.Recibido_Por || '-') + '</td>' +
                        '<td>' + escapeHtml(mov.Tipo_Pago || '') + '</td>' +
                        '<td>' + escapeHtml(entregoLibroTxt) + '</td>' +
                        '<td>' + money(mov.Valor_Pago || 0) + '</td>' +
                        '<td style="font-family:monospace;">' + escapeHtml(refPago) + '</td>' +
                        '<td>' + ticketBtn + '</td>' +
                    '</tr>';
                }).join('');
                detalleModalBody.querySelectorAll('.js-ver-ticket').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const ref = String(btn.getAttribute('data-ref') || '');
                        const mov = detalle.find((item) => String(item.Referencia_Pago || '') === ref);
                        if (mov) abrirTicket(mov);
                    });
                });
            }

            detalleModal.classList.add('is-open');
            detalleModal.setAttribute('aria-hidden', 'false');
        } catch (error) {
            alert('Error: ' + error.message);
        }
    }

    async function cargarMinisterio(ministerio) {
        ministerioModalTitulo.textContent = 'Personas inscritas en ' + escapeHtml(ministerio);
        ministerioModalSubtitulo.textContent = '';

        if (!datosActuales || !datosActuales.resumen) {
            ministerioModalBody.innerHTML = '<tr><td colspan="5">Sin datos.</td></tr>';
        } else {
            const personas = datosActuales.resumen.filter(r => r.ministerio === ministerio);
            if (!personas.length) {
                ministerioModalBody.innerHTML = '<tr><td colspan="5">Sin personas inscritas en este ministerio con los filtros actuales.</td></tr>';
            } else {
                ministerioModalBody.innerHTML = personas.map(p => {
                    const segmento = getSegmento(p);
                    return '<tr>' +
                        '<td>' + escapeHtml(p.persona || '') + '</td>' +
                        '<td>' + escapeHtml(p.cedula || '') + '</td>' +
                        '<td>' + escapeHtml(p.telefono || '') + '</td>' +
                        '<td>' + escapeHtml(segmento) + '</td>' +
                        '<td>' + money(valorPagosSinAbonos(p)) + '</td>' +
                    '</tr>';
                }).join('');
            }
        }

        ministerioModal.classList.add('is-open');
        ministerioModal.setAttribute('aria-hidden', 'false');
    }

    function cerrarDetalle() {
        detalleModal.classList.remove('is-open');
        detalleModal.setAttribute('aria-hidden', 'true');
    }

    function cerrarMinisterio() {
        ministerioModal.classList.remove('is-open');
        ministerioModal.setAttribute('aria-hidden', 'true');
    }

    // Event listeners: Tabs
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;
            
            // Desactivar todos los tabs
            tabBtns.forEach(b => b.classList.remove('is-active'));
            tabsContenido.forEach(t => t.classList.remove('is-active'));
            
            // Activar el tab seleccionado
            btn.classList.add('is-active');
            document.getElementById('tab-' + tabName).classList.add('is-active');
        });
    });

    // Event listeners: Filtros
    if (filtroForm) {
        filtroForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    }

    if (programSelect) {
        programSelect.addEventListener('change', async () => {
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    }

    if (filtroGeneroInput) {
        filtroGeneroInput.addEventListener('change', async () => {
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    }

    if (filtroMinisterioInput) {
        filtroMinisterioInput.addEventListener('change', async () => {
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    }
    const filtroAnioInput = document.getElementById('filtro-anio');
    const filtroSemestreInput = document.getElementById('filtro-semestre');
    [filtroAnioInput, filtroSemestreInput].forEach((el) => {
        if (!el) return;
        el.addEventListener('change', async () => {
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    });

    if (filtroLiderInput) {
        filtroLiderInput.addEventListener('change', async () => {
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    }

    if (filtroEncuentroInput) {
        filtroEncuentroInput.addEventListener('change', async () => {
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    }

    document.addEventListener('click', (event) => {
        const btn = event.target && event.target.closest ? event.target.closest('[data-filtro-indicador]') : null;
        if (!btn || !document.getElementById('pagos-resumen-personas') || !document.getElementById('pagos-resumen-personas').contains(btn)) {
            return;
        }
        const siguiente = String(btn.getAttribute('data-filtro-indicador') || '');
        filtroIndicador = (filtroIndicador === siguiente) ? '' : siguiente;
        const programa = (datosActuales && datosActuales.programa) ? datosActuales.programa : currentProgram;
        actualizarResumenRecaudo(programa, filasPorIndicador(resumenTablaBase));
        pintarTablaIndicador(programa);
    });

    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', async () => {
            if (buscarInput) buscarInput.value = '';
            if (filtroGeneroInput) filtroGeneroInput.value = MODO_RED ? FILTRO_GENERO_INICIAL : '';
            if (filtroMinisterioInput) filtroMinisterioInput.value = '';
            if (filtroLiderInput) filtroLiderInput.value = '';
            if (filtroEncuentroInput) filtroEncuentroInput.value = 'todos';
            filtroIndicador = '';
            try {
                await cargarDatos();
            } catch (error) {
                estadoCarga.textContent = 'Error al cargar';
            }
        });
    }

    // Descargar PNG - simple (requeriría librería externa para mejor implementación)
    if (btnDescargarPng) {
        btnDescargarPng.addEventListener('click', () => {
            alert('Para descargar como PNG:\n1. Abre las herramientas de desarrollador (F12)\n2. Ve a la pestaña "Elementos"\n3. Busca y haz clic derecho en la tabla\n4. Selecciona "Tomar captura de pantalla"\n\nO simplemente haz una captura de pantalla manualmente.');
        });
    }

    // Modales
    if (detalleModalCerrar) {
        detalleModalCerrar.addEventListener('click', cerrarDetalle);
    }
    if (detalleModal) {
        detalleModal.addEventListener('click', (event) => {
            if (event.target && event.target.classList.contains('detail-modal__backdrop')) {
                cerrarDetalle();
            }
        });
    }

    if (ministerioModalCerrar) {
        ministerioModalCerrar.addEventListener('click', cerrarMinisterio);
    }
    if (ministerioModal) {
        ministerioModal.addEventListener('click', (event) => {
            if (event.target && event.target.classList.contains('detail-modal__backdrop')) {
                cerrarMinisterio();
            }
        });
    }

    if (ticketModalCerrar) {
        ticketModalCerrar.addEventListener('click', cerrarTicket);
    }
    if (ticketModal) {
        ticketModal.addEventListener('click', (event) => {
            if (event.target && event.target.classList.contains('detail-modal__backdrop')) {
                cerrarTicket();
            }
        });
    }
    if (btnPagosTicketDescargar) {
        btnPagosTicketDescargar.addEventListener('click', () => {
            if (!window.TallerTicketPago || typeof window.TallerTicketPago.descargarTicketImagen !== 'function') {
                alert('No se pudo preparar la imagen del ticket.');
                return;
            }
            btnPagosTicketDescargar.disabled = true;
            window.TallerTicketPago.descargarTicketImagen()
                .catch((err) => alert(err.message || 'Error al descargar'))
                .finally(() => { btnPagosTicketDescargar.disabled = false; });
        });
    }
    if (btnPagosTicketEnviar) {
        btnPagosTicketEnviar.addEventListener('click', async () => {
            if (!window.TallerTicketPago || typeof window.TallerTicketPago.generarTicketPng !== 'function') {
                alert('No se pudo preparar la imagen del ticket.');
                return;
            }
            const dataEl = document.getElementById('ticket-pago-data');
            let referencia = '';
            try {
                referencia = String((JSON.parse(dataEl.textContent || '{}') || {}).referencia_pago || '');
            } catch (e) {
                referencia = '';
            }
            if (!referencia) {
                alert('Falta la referencia del ticket.');
                return;
            }
            btnPagosTicketEnviar.disabled = true;
            if (ticketModalEstado) ticketModalEstado.textContent = 'Enviando imagen por WhatsApp...';
            try {
                const blob = await window.TallerTicketPago.generarTicketPng();
                const imagen = await blobToDataUrl(blob);
                const response = await fetch(urlReenviarTicket, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ referencia: referencia, imagen: imagen })
                });
                const raw = await response.text();
                let data = null;
                try { data = JSON.parse(raw); } catch (e) { data = null; }
                if (!response.ok || !data || !data.ok) {
                    throw new Error((data && data.mensaje) || 'No se pudo reenviar el ticket.');
                }
                if (ticketModalEstado) ticketModalEstado.textContent = data.mensaje || 'Ticket encolado.';
            } catch (err) {
                alert(err.message || 'Error al enviar');
                if (ticketModalEstado) ticketModalEstado.textContent = 'No se pudo enviar. Puedes descargar la imagen e intentarlo de nuevo.';
            } finally {
                btnPagosTicketEnviar.disabled = false;
            }
        });
    }

    // Cargar datos iniciales
    cargarDatos().catch((error) => {
        estadoCarga.textContent = 'Error al cargar';
        console.error(error);
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
