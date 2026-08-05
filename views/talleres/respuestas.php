<?php include VIEWS . '/layout/header.php'; ?>

<?php
$id = (int)($formulario['Id_Formulario'] ?? 0);
$titulo = (string)($formulario['Titulo'] ?? 'Formulario');
$slug = (string)($formulario['Slug'] ?? '');
$urlQr = $slug !== '' ? (PUBLIC_URL . '?url=talleres_publico/qr&slug=' . urlencode($slug)) : '';
$secciones = is_array($secciones ?? null) ? $secciones : [];
$listaRespuestas = is_array($respuestas ?? null) ? $respuestas : [];
$total = count($listaRespuestas);
$statsGraficas = is_array($estadisticas_graficas ?? null) ? $estadisticas_graficas : [];
$graficasList = is_array($statsGraficas['graficas'] ?? null) ? $statsGraficas['graficas'] : [];
$totalHijosTabla = (int)($statsGraficas['total_hijos_tabla'] ?? 0);
$permisosTaller = is_array($permisos_taller ?? null) ? $permisos_taller : [];
$puedeEditar = !empty($permisosTaller['editar']);
$puedeExportar = !empty($permisosTaller['exportar_excel']);
$puedeGraficas = !empty($permisosTaller['ver_graficas']);
$puedeGestionarEnlace = !empty($permisosTaller['gestionar_enlace']);
$puedeRegistrarPago = !empty($permisosTaller['ver_respuestas']) || !empty($permisosTaller['editar']);
$esPresentacionNinos = !empty($es_presentacion_ninos);
$esTourLevantate = !empty($es_tour_levantate);
$resumenPorMinisterio = is_array($resumen_por_ministerio ?? null) ? $resumen_por_ministerio : [];
$totalResumenMinisterios = array_sum(array_map(static fn(array $f): int => (int)($f['total'] ?? 0), $resumenPorMinisterio));
$soloGraficas = !empty($solo_graficas);
$tabActiva = (string)($tab_activa ?? 'lista');
$totalInscripciones = (int)($statsGraficas['total'] ?? $total);
$urlPagoBase = PUBLIC_URL . '?url=talleres/pago&id=' . $id;

function taller_resp_valor(array $valores, string $clave): string {
    $v = $valores[$clave] ?? '';
    return is_array($v) ? implode(', ', array_map('strval', $v)) : trim((string)$v);
}
?>

<style>
.taller-resp-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
}
.taller-resp-toolbar input {
    max-width: 320px;
}
.taller-resp-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.taller-resp-item {
    border: 1px solid #dbeafe;
    border-radius: 10px;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.taller-resp-item[open] {
    border-color: #93c5fd;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.1);
}
.taller-resp-item > summary {
    list-style: none;
    cursor: pointer;
    padding: 14px 16px;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 12px 16px;
    align-items: center;
    background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
}
.taller-resp-item > summary::-webkit-details-marker { display: none; }
.taller-resp-item > summary::after {
    content: 'Ver detalle ▾';
    font-size: 0.82rem;
    color: #2563eb;
    font-weight: 600;
    white-space: nowrap;
}
.taller-resp-item[open] > summary::after { content: 'Ocultar ▴'; }
.taller-resp-num {
    font-weight: 700;
    color: #64748b;
    font-size: 0.85rem;
    min-width: 2rem;
}
.taller-resp-resumen {
    display: flex;
    flex-wrap: wrap;
    gap: 8px 20px;
    align-items: baseline;
    min-width: 0;
}
.taller-resp-resumen strong {
    color: #0f172a;
    font-size: 1rem;
}
.taller-resp-meta {
    font-size: 0.85rem;
    color: #64748b;
}
.taller-resp-meta span + span::before {
    content: '·';
    margin: 0 6px;
    color: #cbd5e1;
}
.taller-resp-body {
    padding: 16px 18px 20px;
    border-top: 1px solid #e2e8f0;
}
.taller-resp-seccion {
    margin-bottom: 22px;
}
.taller-resp-seccion:last-child { margin-bottom: 0; }
.taller-resp-seccion h4 {
    margin: 0 0 12px;
    font-size: 0.95rem;
    color: #1e40af;
    padding-bottom: 6px;
    border-bottom: 2px solid #dbeafe;
}
.taller-resp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 12px 20px;
}
.taller-resp-campo {
    min-width: 0;
}
.taller-resp-campo.full-width {
    grid-column: 1 / -1;
}
.taller-resp-campo dt {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.02em;
    margin-bottom: 4px;
    line-height: 1.35;
}
.taller-resp-campo dd {
    margin: 0;
    font-size: 0.92rem;
    color: #0f172a;
    line-height: 1.5;
    word-break: break-word;
    white-space: pre-wrap;
}
.taller-resp-campo dd.empty {
    color: #94a3b8;
    font-style: italic;
}
.taller-resp-firma {
    max-width: 320px;
    max-height: 120px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: #fff;
}
.taller-resp-docs-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 999px;
    background: #ecfdf5;
    border: 1px solid #86efac;
    color: #166534;
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
}
.taller-resp-docs-quick {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}
.taller-resp-docs-quick a {
    font-size: 0.78rem;
    color: #1d4ed8;
    text-decoration: none;
    border: 1px solid #bfdbfe;
    background: #eff6ff;
    border-radius: 6px;
    padding: 2px 8px;
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.taller-resp-docs-quick a:hover {
    background: #dbeafe;
}
.taller-resp-empty {
    text-align: center;
    padding: 48px 20px;
    color: #64748b;
    background: #f8fafc;
    border-radius: 10px;
    border: 1px dashed #cbd5e1;
}
.taller-resp-resumen-ministerios {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 2px solid #e2e8f0;
}
.taller-resp-resumen-ministerios h3 {
    margin: 0 0 6px;
    font-size: 1.05rem;
    color: #0f172a;
}
.taller-resp-resumen-ministerios .subtitulo {
    margin: 0 0 16px;
    font-size: 0.88rem;
    color: #64748b;
}
.taller-resp-tabla-ministerios {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border: 1px solid #dbeafe;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}
.taller-resp-tabla-ministerios th,
.taller-resp-tabla-ministerios td {
    padding: 12px 16px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.taller-resp-tabla-ministerios th {
    background: linear-gradient(180deg, #f8fbff 0%, #f1f5f9 100%);
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.taller-resp-tabla-ministerios tbody tr:last-child td {
    border-bottom: none;
}
.taller-resp-tabla-ministerios tbody tr:hover {
    background: #f8fafc;
}
.taller-resp-tabla-ministerios .col-total {
    width: 120px;
    text-align: center;
    font-weight: 700;
    color: #1e40af;
}
.taller-resp-tabla-ministerios tfoot td {
    background: #eff6ff;
    font-weight: 700;
    color: #0f172a;
    border-top: 2px solid #bfdbfe;
}
.taller-resp-item.hidden-by-filter { display: none; }
.taller-resp-pago {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
    min-width: 110px;
}
.taller-resp-pago-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: #0f766e;
    color: #fff;
    text-decoration: none;
    font-size: 1.1rem;
    box-shadow: 0 2px 6px rgba(15, 118, 110, 0.25);
}
.taller-resp-pago-btn:hover { background: #0d5f58; color: #fff; }
.taller-resp-pago-status {
    font-size: 0.78rem;
    color: #64748b;
    white-space: nowrap;
}
.taller-resp-pago-status.ok { color: #0f766e; font-weight: 600; }
.taller-resp-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0;
}
.taller-resp-tabs button {
    border: none;
    background: transparent;
    padding: 10px 18px;
    font-weight: 600;
    font-size: 0.92rem;
    color: #64748b;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    cursor: pointer;
    border-radius: 6px 6px 0 0;
}
.taller-resp-tabs button:hover { color: #2563eb; background: #f8fafc; }
.taller-resp-tabs button.active {
    color: #1d4ed8;
    border-bottom-color: #2563eb;
    background: #eff6ff;
}
.taller-resp-panel { display: none; }
.taller-resp-panel.active { display: block; }
.taller-graf-resumen {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.taller-graf-card {
    background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
    border: 1px solid #dbeafe;
    border-radius: 10px;
    padding: 16px 18px;
}
.taller-graf-card strong {
    display: block;
    font-size: 1.75rem;
    color: #1e40af;
    line-height: 1.2;
}
.taller-graf-card span {
    font-size: 0.85rem;
    color: #64748b;
}
.taller-graf-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(420px, 1fr));
    gap: 20px;
}
.taller-graf-item {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
}
.taller-graf-item h5 {
    margin: 0 0 12px;
    font-size: 0.95rem;
    color: #0f172a;
    line-height: 1.35;
}
.taller-graf-item .chart-mount { min-height: 280px; }
.taller-graf-item.donut .chart-mount { min-height: 300px; }
@media (max-width: 640px) {
    .taller-resp-item > summary {
        grid-template-columns: 1fr;
    }
    .taller-resp-item > summary::after {
        justify-self: start;
    }
    .taller-graf-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-header">
    <h2><?= $soloGraficas ? 'Gráficas' : 'Respuestas' ?>: <?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
        <?php if ($soloGraficas): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres" class="btn btn-secondary btn-sm">← Elegir taller</a>
        <?php else: ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres" class="btn btn-secondary btn-sm">← Formularios</a>
        <?php endif; ?>
        <?php if ($puedeEditar): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/editar&id=<?= $id ?>" class="btn btn-outline-secondary btn-sm">Editar formulario</a>
        <?php endif; ?>
        <?php if ($puedeExportar): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/exportar&id=<?= $id ?>" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Descargar Excel
        </a>
        <?php endif; ?>
        <?php if (!$soloGraficas && $puedeGraficas && $totalInscripciones > 0 && $graficasList !== []): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>&tab=graficas" class="btn btn-primary btn-sm">
            <i class="bi bi-bar-chart-line"></i> Ver gráficas
        </a>
        <?php endif; ?>
        <?php if ($puedeGestionarEnlace && !empty($formulario['Activo']) && $slug !== ''): ?>
        <a href="<?= PUBLIC_URL ?>?url=talleres_publico&slug=<?= urlencode($slug) ?>" class="btn btn-outline-info btn-sm" target="_blank" rel="noopener">
            Abrir formulario
        </a>
        <a href="<?= htmlspecialchars($urlQr, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm" target="_blank" rel="noopener">
            <i class="bi bi-qr-code"></i> QR inscripción
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$soloGraficas && $totalInscripciones > 0 && $puedeGraficas && $graficasList !== []): ?>
<nav class="taller-resp-tabs" aria-label="Vista de respuestas">
    <button type="button" class="<?= $tabActiva === 'lista' ? 'active' : '' ?>" data-tab="lista">Lista de inscripciones</button>
    <button type="button" class="<?= $tabActiva === 'graficas' ? 'active' : '' ?>" data-tab="graficas">Gráficas del cuestionario</button>
</nav>
<?php endif; ?>

<?php if (!$soloGraficas): ?>
<div id="panel-lista" class="taller-resp-panel <?= $tabActiva === 'lista' ? 'active' : '' ?>">
<p class="text-muted">
    <?= $total ?> respuesta(s). Use la lista para revisar cada inscripción; pulse una fila para ver todas las respuestas por bloque.
    Para análisis masivo, descargue Excel o consulte la pestaña Gráficas.
</p>

<?php if ($total > 0): ?>
<div class="taller-resp-toolbar">
    <input type="search" id="filtro-respuestas" class="form-control" placeholder="Buscar por nombre, documento o teléfono…" autocomplete="off">
    <span class="text-muted" style="font-size:0.88rem;" id="filtro-contador"><?= $total ?> mostradas</span>
</div>
<?php endif; ?>

<?php if (empty($listaRespuestas)): ?>
<div class="taller-resp-empty">Aún no hay respuestas para este formulario.</div>
<?php else: ?>
<div class="taller-resp-list" id="lista-respuestas">
    <?php foreach ($listaRespuestas as $idx => $resp): ?>
        <?php
        $valores = is_array($resp['valores'] ?? null) ? $resp['valores'] : [];
        $nombre = $esPresentacionNinos ? taller_resp_valor($valores, 'nino_nombre') : taller_resp_valor($valores, 'persona_nombre');
        if ($nombre === '' && $esPresentacionNinos) {
            $nombre = taller_resp_valor($valores, 'padres_nombre');
        }
        $documento = $esPresentacionNinos ? taller_resp_valor($valores, 'nino_documento') : taller_resp_valor($valores, 'persona_documento');
        $telefono = $esPresentacionNinos
            ? taller_resp_valor($valores, 'padres_telefono')
            : taller_resp_valor($valores, 'persona_telefono');
        $fecha = substr((string)($resp['fecha'] ?? ''), 0, 16);
        $busqueda = strtolower($nombre . ' ' . $documento . ' ' . $telefono);
        $firmaImg = (string)($resp['firma_imagen'] ?? '');
        $idRespuesta = (int)($resp['id'] ?? 0);
        $totalPagado = (float)($resp['total_pagado'] ?? 0);
        $documentosPresentacion = is_array($resp['documentos_presentacion'] ?? null) ? $resp['documentos_presentacion'] : [];
        $totalDocumentos = count($documentosPresentacion);
        $urlPago = $urlPagoBase . '&id_respuesta=' . $idRespuesta;
        ?>
        <details class="taller-resp-item" data-busqueda="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            <summary>
                <span class="taller-resp-num">#<?= (int)($resp['id'] ?? ($idx + 1)) ?></span>
                <div class="taller-resp-resumen">
                    <strong><?= htmlspecialchars($nombre !== '' ? $nombre : 'Sin nombre', ENT_QUOTES, 'UTF-8') ?></strong>
                    <div class="taller-resp-meta">
                        <?php if ($documento !== ''): ?><span>Doc. <?= htmlspecialchars($documento, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if ($telefono !== ''): ?><span><?= htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if ($fecha !== ''): ?><span><?= htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if ((int)($resp['id_persona'] ?? 0) > 0): ?>
                        <span>Persona #<?= (int)$resp['id_persona'] ?></span>
                        <?php endif; ?>
                        <?php if ($totalDocumentos > 0): ?>
                        <span class="taller-resp-docs-badge" title="<?= $totalDocumentos ?> documento(s) adjunto(s)">📎 <?= $totalDocumentos ?> doc<?= $totalDocumentos === 1 ? '' : 's' ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($totalDocumentos > 0): ?>
                    <div class="taller-resp-docs-quick">
                        <?php foreach (array_slice($documentosPresentacion, 0, 3) as $docResumen): ?>
                        <?php $urlDocRes = trim((string)($docResumen['url'] ?? '')); ?>
                        <?php if ($urlDocRes !== ''): ?>
                        <a href="<?= htmlspecialchars($urlDocRes, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" onclick="event.stopPropagation();" title="<?= htmlspecialchars((string)($docResumen['nombre'] ?? 'Documento'), ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars((string)($docResumen['nombre'] ?? 'Documento'), ENT_QUOTES, 'UTF-8') ?>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($totalDocumentos > 3): ?>
                        <span style="font-size:0.78rem;color:#64748b;">+<?= $totalDocumentos - 3 ?> más (ver detalle)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($puedeRegistrarPago && $esPresentacionNinos): ?>
                <div class="taller-resp-pago">
                    <a class="taller-resp-pago-btn" href="<?= htmlspecialchars($urlPago, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" title="Registrar pago/abono" aria-label="Registrar pago">💳</a>
                    <?php if ($totalPagado > 0): ?>
                    <span class="taller-resp-pago-status ok">$<?= number_format($totalPagado, 0, ',', '.') ?></span>
                    <?php else: ?>
                    <span class="taller-resp-pago-status">Sin pago</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </summary>
            <div class="taller-resp-body">
                <?php foreach ($secciones as $seccion): ?>
                    <?php $camposSec = is_array($seccion['campos'] ?? null) ? $seccion['campos'] : []; ?>
                    <?php if ($camposSec === []) continue; ?>
                    <section class="taller-resp-seccion">
                        <h4><?= htmlspecialchars((string)($seccion['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h4>
                        <dl class="taller-resp-grid">
                            <?php foreach ($camposSec as $campo): ?>
                                <?php
                                $clave = (string)($campo['clave'] ?? '');
                                $etiqueta = (string)($campo['etiqueta'] ?? $clave);
                                $tipoCampo = (string)($campo['tipo'] ?? 'text');
                                $texto = taller_resp_valor($valores, $clave);
                                $esLargo = $tipoCampo === 'textarea' || $tipoCampo === 'tabla' || mb_strlen($texto) > 80;
                                $claseCampo = 'taller-resp-campo' . ($esLargo ? ' full-width' : '');
                                ?>
                                <div class="<?= $claseCampo ?>">
                                    <dt><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></dt>
                                    <dd class="<?= ($tipoCampo === 'firma' && $firmaImg === '' && $texto === '') || ($tipoCampo !== 'firma' && $texto === '') ? 'empty' : '' ?>">
                                        <?php if ($tipoCampo === 'firma' && $firmaImg !== ''): ?>
                                            <img src="<?= htmlspecialchars($firmaImg, ENT_QUOTES, 'UTF-8') ?>" alt="Firma" class="taller-resp-firma">
                                        <?php elseif ($texto !== ''): ?>
                                            <?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </section>
                <?php endforeach; ?>
                <?php if ($totalDocumentos > 0): ?>
                <section class="taller-resp-seccion">
                    <h4>Documentos adjuntos</h4>
                    <ul class="taller-resp-docs" style="list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px;">
                        <?php foreach ($documentosPresentacion as $doc): ?>
                        <?php
                        $nombreDoc = trim((string)($doc['nombre'] ?? 'Documento'));
                        $urlDoc = trim((string)($doc['url'] ?? ''));
                        $fechaDoc = trim((string)($doc['fecha'] ?? ''));
                        ?>
                        <li style="border:1px solid #dbeafe;border-radius:8px;padding:10px 12px;background:#f8fafc;">
                            <?php if ($urlDoc !== ''): ?>
                            <a href="<?= htmlspecialchars($urlDoc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" style="font-weight:600;color:#1d4ed8;text-decoration:none;">
                                <?= htmlspecialchars($nombreDoc, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <?php else: ?>
                            <strong><?= htmlspecialchars($nombreDoc, ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php endif; ?>
                            <?php if ($fechaDoc !== ''): ?>
                            <div style="font-size:0.82rem;color:#64748b;margin-top:4px;">Subido: <?= htmlspecialchars($fechaDoc, ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>
            </div>
        </details>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($esTourLevantate && $resumenPorMinisterio !== []): ?>
<section class="taller-resp-resumen-ministerios" aria-label="Resumen por ministerio">
    <h3>Resumen por ministerio</h3>
    <p class="subtitulo">Cantidad de inscritas en el Tour Levántate y Resplandece agrupadas por ministerio.</p>
    <div class="table-container">
        <table class="taller-resp-tabla-ministerios">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ministerio</th>
                    <th class="col-total">Inscritas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resumenPorMinisterio as $i => $filaResumen): ?>
                <tr>
                    <td style="color:#94a3b8;font-weight:600;"><?= (int)$i + 1 ?></td>
                    <td><?= htmlspecialchars((string)($filaResumen['ministerio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="col-total"><?= (int)($filaResumen['total'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total inscritas</td>
                    <td class="col-total"><?= (int)$totalResumenMinisterios ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</section>
<?php endif; ?>

</div>
<?php endif; ?>

<?php if ($puedeGraficas && $graficasList !== []): ?>
<div id="panel-graficas" class="taller-resp-panel <?= ($soloGraficas || $tabActiva === 'graficas') ? 'active' : '' ?>">
    <p class="text-muted">
        Resumen visual de las <?= $totalInscripciones ?> inscripción(es). Las gráficas se generan a partir de las respuestas guardadas en el cuestionario.
    </p>

    <div class="taller-graf-resumen">
        <div class="taller-graf-card">
            <strong><?= $totalInscripciones ?></strong>
            <span>Inscripciones</span>
        </div>
        <div class="taller-graf-card">
            <strong><?= count($graficasList) ?></strong>
            <span>Indicadores graficados</span>
        </div>
        <?php if ($totalHijosTabla > 0): ?>
        <div class="taller-graf-card">
            <strong><?= $totalHijosTabla ?></strong>
            <span>Hijos registrados (tabla)</span>
        </div>
        <?php endif; ?>
    </div>

    <div class="taller-graf-grid" id="taller-graf-grid">
        <?php foreach ($graficasList as $graf): ?>
            <?php
            $chartId = (string)($graf['id'] ?? 'chart');
            $chartTipo = (string)($graf['tipo'] ?? 'bar');
            $chartTitulo = (string)($graf['titulo'] ?? '');
            $claseDonut = $chartTipo === 'doughnut' ? ' donut' : '';
            ?>
            <article class="taller-graf-item<?= $claseDonut ?>">
                <h5><?= htmlspecialchars($chartTitulo, ENT_QUOTES, 'UTF-8') ?></h5>
                <div class="chart-mount" id="<?= htmlspecialchars($chartId, ENT_QUOTES, 'UTF-8') ?>"></div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif ($soloGraficas): ?>
<div class="taller-resp-empty">Aún no hay datos para graficar en este formulario.</div>
<?php endif; ?>

<script>
(function() {
    const input = document.getElementById('filtro-respuestas');
    const contador = document.getElementById('filtro-contador');
    const items = document.querySelectorAll('.taller-resp-item');
    if (!input || !items.length) return;

    function filtrar() {
        const q = input.value.trim().toLowerCase();
        let visibles = 0;
        items.forEach(function(el) {
            const ok = q === '' || (el.getAttribute('data-busqueda') || '').indexOf(q) >= 0;
            el.classList.toggle('hidden-by-filter', !ok);
            if (ok) visibles++;
        });
        if (contador) {
            contador.textContent = visibles + ' mostradas';
        }
    }

    input.addEventListener('input', filtrar);
})();
</script>

<?php if ($puedeGraficas && $graficasList !== []): ?>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
(function() {
    const graficasData = <?= json_encode($graficasList, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    const colores = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2', '#4f46e5', '#be185d', '#65a30d', '#ea580c'];
    const chartInstances = {};
    let graficasRenderizadas = false;

    const tabs = document.querySelectorAll('.taller-resp-tabs button[data-tab]');
    const panelLista = document.getElementById('panel-lista');
    const panelGraficas = document.getElementById('panel-graficas');

    function activarTab(tab) {
        tabs.forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tab);
        });
        if (panelLista) panelLista.classList.toggle('active', tab === 'lista');
        if (panelGraficas) panelGraficas.classList.toggle('active', tab === 'graficas');
        if (tab === 'graficas') {
            renderGraficas();
        }
    }

    tabs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            activarTab(btn.getAttribute('data-tab') || 'lista');
        });
    });

    function renderGraficas() {
        if (graficasRenderizadas || typeof ApexCharts === 'undefined') return;
        graficasData.forEach(function(chartData) {
            const mount = document.getElementById(chartData.id);
            if (!mount || chartInstances[chartData.id]) return;

            const labels = chartData.labels || [];
            const data = chartData.data || [];
            const tipo = chartData.tipo || 'bar';
            const horizontal = !!chartData.horizontal;

            let options;
            if (tipo === 'doughnut') {
                options = {
                    chart: { type: 'donut', height: 300, toolbar: { show: false } },
                    series: data,
                    labels: labels,
                    colors: colores,
                    legend: { position: 'bottom', fontSize: '12px' },
                    dataLabels: { enabled: true },
                    plotOptions: {
                        pie: {
                            donut: {
                                size: '58%',
                                labels: {
                                    show: true,
                                    total: {
                                        show: true,
                                        label: 'Total',
                                        formatter: function(w) {
                                            return w.globals.seriesTotals.reduce(function(a, b) { return a + b; }, 0);
                                        }
                                    }
                                }
                            }
                        }
                    }
                };
            } else if (horizontal) {
                options = {
                    chart: { type: 'bar', height: Math.max(300, labels.length * 32), toolbar: { show: false } },
                    series: [{ name: 'Respuestas', data: data }],
                    colors: ['#2563eb'],
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            borderRadius: 4,
                            barHeight: '72%'
                        }
                    },
                    dataLabels: { enabled: true },
                    xaxis: {
                        categories: labels
                    },
                    yaxis: {
                        labels: {
                            maxWidth: 300,
                            style: { fontSize: '11px' }
                        }
                    },
                    legend: { show: false }
                };
            } else {
                options = {
                    chart: { type: 'bar', height: 300, toolbar: { show: false } },
                    series: [{ name: 'Respuestas', data: data }],
                    colors: colores,
                    xaxis: {
                        categories: labels,
                        labels: { rotate: -35, trim: true, maxHeight: 80 }
                    },
                    yaxis: {
                        min: 0,
                        forceNiceScale: true,
                        labels: { formatter: function(v) { return Math.round(v); } }
                    },
                    dataLabels: { enabled: true },
                    plotOptions: {
                        bar: {
                            horizontal: false,
                            borderRadius: 4,
                            columnWidth: '55%',
                            distributed: true
                        }
                    },
                    legend: { show: false }
                };
            }

            chartInstances[chartData.id] = new ApexCharts(mount, options);
            chartInstances[chartData.id].render();
        });
        graficasRenderizadas = true;
    }

    if (document.getElementById('panel-graficas')?.classList.contains('active')) {
        renderGraficas();
    }
})();
</script>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
