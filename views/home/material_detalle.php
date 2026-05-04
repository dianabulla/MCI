<?php include VIEWS . '/layout/header.php'; ?>

<?php
$modulo = $modulo ?? [];
$temas = $temas ?? [];
$totalArchivos = (int)($total_archivos ?? 0);
$puedeGestionar = !empty($puede_gestionar);
$mensaje = (string)($mensaje ?? '');
$tipo = (string)($tipo ?? '');
$titulo = (string)($modulo['titulo'] ?? 'Material');
$ruta = (string)($modulo['ruta'] ?? 'home/material');
$color = (string)($modulo['color'] ?? '#2f73b7');
$icono = (string)($modulo['icono'] ?? 'bi bi-journal-bookmark-fill');
$clave = (string)($modulo['clave'] ?? '');
$tieneSubmodulos = !empty($tiene_submodulos);
$esCapacitacionDestino = $clave === 'capacitacion_destino';
$esUniversidadVida = $clave === 'universidad_vida';
$usaTarjetasTipoMaterial = $esCapacitacionDestino || $esUniversidadVida;
$configCapacitacionDestino = (array)($config_capacitacion_destino ?? []);
$profesoresModulos = (array)($profesores_modulos ?? []);
$rutaDetalleVistas = PUBLIC_URL . '?url=home/material/detalle-vistas&modulo=' . rawurlencode($clave);

$temasClase = [];
$temasProfesor = [];
if ($tieneSubmodulos) {
    foreach ($temas as $temaTmp) {
        $categoriaTmp = strtolower(trim((string)($temaTmp['categoria'] ?? 'clase')));
        if ($categoriaTmp === 'profesor') {
            $temasProfesor[] = $temaTmp;
        } else {
            $temasClase[] = $temaTmp;
        }
    }
}
?>

<style>
    .submodulo-wrap {
        border: 1px solid #dbe6f5;
        border-radius: 12px;
        margin-bottom: 14px;
        overflow: hidden;
        background: #fff;
    }

    .submodulo-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-bottom: 1px solid #e6eef9;
    }

    .submodulo-title {
        margin: 0;
        font-size: 16px;
    }

    .submodulo-meta {
        font-size: 12px;
        color: #50647f;
        font-weight: 600;
    }

    .submodulo-clase .submodulo-head {
        background: linear-gradient(180deg, #edf4ff 0%, #f8fbff 100%);
    }

    .submodulo-profesor .submodulo-head {
        background: linear-gradient(180deg, #fff5e8 0%, #fffaf3 100%);
    }

    .submodulo-body {
        padding: 10px;
    }

    .submodulo-tabs {
        display: inline-flex;
        gap: 6px;
        padding: 4px;
        border: 1px solid #d8e2f1;
        border-radius: 999px;
        background: #f7fbff;
        margin-bottom: 12px;
    }

    .submodulo-tab {
        border: 1px solid transparent;
        border-radius: 999px;
        background: transparent;
        color: #31527d;
        font-weight: 700;
        font-size: 13px;
        padding: 7px 12px;
        cursor: pointer;
    }

    .submodulo-tab:hover {
        background: #ebf3ff;
    }

    .submodulo-tab.is-active {
        background: #3c82c8;
        color: #fff;
        border-color: #3c82c8;
        box-shadow: 0 1px 3px rgba(45, 94, 146, 0.22);
    }

    .is-hidden {
        display: none;
    }

    .submodulo-body .table-container {
        overflow-x: auto;
    }

    .submodulo-body .data-table {
        min-width: 1240px;
        table-layout: fixed;
    }

    .submodulo-body .data-table th,
    .submodulo-body .data-table td {
        word-break: normal;
        overflow-wrap: normal;
        white-space: normal;
    }

    .submodulo-body .data-table th.col-titulo,
    .submodulo-body .data-table td.col-titulo {
        width: 240px;
        max-width: 240px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .submodulo-body .data-table th.col-descripcion,
    .submodulo-body .data-table td.col-descripcion {
        width: 520px;
        max-width: 520px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .descripcion-cell {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        min-width: 0;
    }

    .descripcion-preview {
        display: inline-block;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .btn-link-compact {
        border: 0;
        background: transparent;
        color: #3c82c8;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        padding: 0;
        text-decoration: underline;
        white-space: nowrap;
    }

    .btn-link-compact:hover {
        color: #2a5f99;
    }

    .submodulo-body .data-table th.col-acciones,
    .submodulo-body .data-table td.col-acciones {
        width: 300px;
    }

    .cap-destino-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 12px;
    }

    .cap-destino-grid .submodulo-wrap {
        margin-bottom: 0;
    }

    .cap-nivel-section {
        margin-bottom: 18px;
    }

    .cap-nivel-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 8px 14px;
        border-radius: 10px 10px 0 0;
        background: linear-gradient(90deg, #2f73b7 0%, #4f8fd0 100%);
        color: #fff;
        margin-bottom: 0;
    }

    .cap-nivel-label {
        font-size: 15px;
        font-weight: 700;
        margin: 0;
    }

    .cap-nivel-section .cap-destino-grid {
        border: 1px solid #c8d9ef;
        border-top: none;
        border-radius: 0 0 10px 10px;
        padding: 10px;
        background: #f8fbff;
    }

    .cap-modulo-profesor-wrap {
        border-left: 1px solid #c8d9ef;
        border-right: 1px solid #c8d9ef;
        background: #f6f9ff;
        padding: 8px 10px;
    }

    .cap-modulo-profesor-row {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 0 2px 0;
        font-size: 12px;
        color: #4a6080;
    }

    .cap-modulo-profesor-nombre {
        font-weight: 600;
        color: #2f73b7;
    }

    .cap-modulo-profesor-form {
        display: none;
        margin-top: 6px;
        padding: 8px;
        background: #f0f5ff;
        border-radius: 8px;
        border: 1px solid #ccdcf5;
    }

    .cap-modulo-profesor-form.is-open {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }

    .cap-destino-main-switch {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 12px;
    }

    .cap-main-tab {
        border: 1px solid #dbe6f5;
        border-radius: 14px;
        padding: 12px 14px;
        background: linear-gradient(180deg, #f7fbff 0%, #eef4ff 100%);
        color: #2f4f78;
        font-weight: 700;
        text-align: left;
        cursor: pointer;
        transition: all .16s ease;
    }

    .cap-main-tab small {
        display: block;
        margin-top: 4px;
        font-weight: 500;
        color: #5f7596;
    }

    .cap-main-tab:hover {
        border-color: #bfd3ee;
        transform: translateY(-1px);
    }

    .cap-main-tab.is-active {
        border-color: #3c82c8;
        background: linear-gradient(180deg, #3c82c8 0%, #2f73b7 100%);
        color: #fff;
        box-shadow: 0 6px 18px rgba(45, 94, 146, 0.22);
    }

    .cap-main-tab.is-active small {
        color: #dbe8ff;
    }

    .cap-destino-grid .submodulo-title {
        font-size: 15px;
    }

    .cap-entry-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
        margin-top: 14px;
        margin-bottom: 16px;
    }

    .cap-entry-card {
        border: 1px solid #d6e3f4;
        border-radius: 16px;
        padding: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f6fbff 100%);
        box-shadow: 0 10px 20px rgba(22, 63, 110, 0.08);
        cursor: pointer;
    }

    .cap-entry-card:hover {
        border-color: #b9d0ec;
        transform: translateY(-1px);
    }

    .cap-entry-card.is-active {
        border-color: #3c82c8;
        background: linear-gradient(180deg, #3c82c8 0%, #2f73b7 100%);
        box-shadow: 0 8px 20px rgba(45, 94, 146, 0.24);
    }

    .cap-entry-card.is-active h4,
    .cap-entry-card.is-active p {
        color: #ffffff;
    }

    .cap-entry-card h4 {
        margin: 0 0 6px 0;
        color: #1f4d84;
    }

    .cap-entry-card p {
        margin: 0;
        color: #617694;
        font-size: 13px;
    }

    .cap-panel {
        display: none;
        margin-top: 12px;
    }

    .cap-panel.is-open {
        display: block;
    }

    .folder-tree-explorer {
        margin-bottom: 14px;
        border: 1px solid #d6e3f4;
        border-radius: 14px;
        background: #f8fbff;
        padding: 12px;
    }

    .folder-tree-row {
        margin-bottom: 10px;
    }

    .folder-tree-row:last-child {
        margin-bottom: 0;
    }

    .folder-tree-label {
        display: block;
        margin-bottom: 6px;
        font-size: 12px;
        color: #5e7290;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .folder-tree-items {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .folder-node {
        border: 1px solid #cdddf1;
        border-radius: 10px;
        background: #fff;
        color: #32577f;
        font-size: 13px;
        font-weight: 700;
        padding: 8px 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .folder-node:hover {
        border-color: #adc8e6;
        background: #f2f8ff;
    }

    .folder-node.is-active {
        border-color: #3c82c8;
        background: linear-gradient(180deg, #3c82c8 0%, #2f73b7 100%);
        color: #fff;
    }

    .folder-node .folder-meta {
        font-size: 11px;
        opacity: 0.82;
        font-weight: 600;
    }

    .cap-destino-grid .submodulo-wrap {
        margin-bottom: 10px;
    }

    .cap-destino-grid .submodulo-head {
        cursor: pointer;
    }

    .cap-destino-grid .submodulo-body {
        display: none;
    }

    .cap-destino-grid .submodulo-wrap.is-selected {
        border-color: #3c82c8;
        box-shadow: 0 6px 14px rgba(45, 94, 146, 0.16);
    }

    .cap-nivel-section.is-focus-mode .cap-destino-grid {
        grid-template-columns: 1fr;
    }

    .cap-nivel-section.is-focus-mode .cap-destino-grid .submodulo-wrap {
        display: none;
    }

    .cap-nivel-section.is-focus-mode .cap-destino-grid .submodulo-wrap.is-focused {
        display: block;
        grid-column: 1 / -1;
    }

    .cap-destino-grid .submodulo-wrap.is-selected .submodulo-head {
        background: linear-gradient(180deg, #eef5ff 0%, #f8fbff 100%);
    }

    .cap-nivel-section .submodulo-body {
        padding: 14px;
    }

    .cap-nivel-section .data-table {
        width: 100%;
        min-width: 0;
        table-layout: fixed;
    }

    .cap-nivel-section .data-table th.col-titulo,
    .cap-nivel-section .data-table td.col-titulo {
        width: 42%;
        max-width: 42%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cap-nivel-section .data-table th.col-descripcion,
    .cap-nivel-section .data-table td.col-descripcion {
        width: 10%;
        max-width: 10%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cap-nivel-section .data-table th.col-acciones,
    .cap-nivel-section .data-table td.col-acciones {
        width: 48%;
        max-width: 48%;
    }

    .tema-acciones {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: flex-start;
    }

    .tema-acciones-row {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }

    .tema-acciones-row.is-danger {
        padding-top: 2px;
    }

    .cap-nivel-section .descripcion-cell,
    .cap-nivel-section .descripcion-preview {
        display: inline-block;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .cap-detail-view {
        margin-top: 14px;
        border: 1px solid #dbe6f5;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
    }

    .cap-detail-view.is-hidden {
        display: none;
    }

    .cap-detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-bottom: 1px solid #e6eef9;
        background: #f7fbff;
    }

    .cap-detail-header h4 {
        margin: 0;
        color: #356ea8;
        font-size: 15px;
    }

    .cap-detail-header small {
        color: #60758f;
        font-weight: 600;
    }

    .cap-detail-body {
        padding: 10px;
    }

    .material-gallery-modal {
        position: fixed;
        inset: 0;
        z-index: 1200;
        display: none;
        background: rgba(8, 14, 27, 0.92);
        backdrop-filter: blur(4px);
    }

    .material-gallery-modal.is-open {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .material-gallery-shell {
        width: min(1200px, 100%);
        max-height: calc(100vh - 40px);
        background: linear-gradient(180deg, #1b2f4d 0%, #234062 100%);
        border: 1px solid rgba(190, 210, 240, 0.16);
        border-radius: 20px;
        box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .material-gallery-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border-bottom: 1px solid rgba(190, 210, 240, 0.12);
        color: #f4f8ff;
    }

    .material-gallery-topbar h3 {
        margin: 0;
        font-size: 18px;
    }

    .material-gallery-topbar small {
        display: block;
        color: #bdd0ec;
        margin-top: 4px;
    }

    .material-gallery-close {
        border: 0;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        width: 38px;
        height: 38px;
        font-size: 20px;
        cursor: pointer;
    }

    .material-gallery-stage {
        display: grid;
        grid-template-columns: 68px minmax(0, 1fr) 68px;
        align-items: stretch;
        gap: 8px;
        padding: 14px 16px 10px;
        min-height: 0;
        flex: 1;
    }

    .material-gallery-nav {
        border: 0;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.08);
        color: #fff;
        font-size: 28px;
        cursor: pointer;
        transition: background .15s ease;
    }

    .material-gallery-nav:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.14);
    }

    .material-gallery-nav:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }

    .material-gallery-figure {
        min-height: 0;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .material-gallery-frame {
        flex: 1;
        min-height: 360px;
        max-height: calc(100vh - 240px);
        border-radius: 18px;
        overflow: hidden;
        background: rgba(2, 8, 18, 0.78);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .material-gallery-frame img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #0a1220;
    }

    .material-gallery-caption {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        color: #e8f0fc;
        font-size: 14px;
    }

    .material-gallery-caption strong {
        display: block;
        font-size: 15px;
    }

    .material-gallery-caption a {
        color: #8ec5ff;
        font-weight: 700;
        text-decoration: none;
    }

    .material-gallery-caption a:hover {
        text-decoration: underline;
    }

    .material-gallery-thumbs {
        display: flex;
        gap: 10px;
        padding: 0 16px 16px;
        overflow-x: auto;
    }

    .material-gallery-thumb {
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 0;
        background: transparent;
        cursor: pointer;
        overflow: hidden;
        width: 92px;
        min-width: 92px;
        height: 66px;
        opacity: 0.7;
    }

    .material-gallery-thumb.is-active {
        border-color: #7fc2ff;
        opacity: 1;
        box-shadow: 0 0 0 3px rgba(127, 194, 255, 0.16);
    }

    .material-gallery-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .material-item-preview-btn {
        border: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
        background: transparent;
        position: relative;
        display: block;
    }

    .material-item-preview-btn img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .material-item-preview-btn::after {
        content: '‹  ›';
        position: absolute;
        right: 8px;
        bottom: 8px;
        padding: 2px 7px;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.55);
        color: #fff;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
    }

    @media (max-width: 768px) {
        .material-gallery-modal.is-open {
            padding: 10px;
        }

        .material-gallery-shell {
            max-height: calc(100vh - 20px);
        }

        .material-gallery-stage {
            grid-template-columns: 48px minmax(0, 1fr) 48px;
            padding: 10px;
        }

        .material-gallery-frame {
            min-height: 240px;
            max-height: calc(100vh - 260px);
        }

        .material-gallery-caption {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= htmlspecialchars($titulo) ?></h2>
        <small style="color:#637087;">Gestiona módulos de material con varios archivos por creación.</small>
    </div>
    <a href="<?= PUBLIC_URL ?>?url=home/material" class="btn btn-secondary">Volver a Material</a>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-<?= $tipo === 'success' ? 'success' : 'danger' ?>" style="margin-top:14px;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-top:14px; margin-bottom:14px; padding:14px; border-left:4px solid <?= htmlspecialchars($color) ?>;">
    <div style="display:flex; align-items:center; gap:10px;">
        <span style="width:38px; height:38px; border-radius:10px; background:<?= htmlspecialchars($color) ?>; color:#fff; display:inline-flex; align-items:center; justify-content:center; font-size:18px;">
            <i class="<?= htmlspecialchars($icono) ?>"></i>
        </span>
        <div>
            <strong style="display:block;"><?= htmlspecialchars($titulo) ?></strong>
            <small style="color:#5a6f8d;"><?= count($temas) ?> tema(s) y <?= $totalArchivos ?> archivo(s), ordenado por creación reciente.</small>
        </div>
    </div>
</div>

<?php if ($puedeGestionar): ?>
<div class="form-container" style="margin-bottom: 16px;">
    <h3 style="margin-top:0;">Crear módulo de material</h3>
    <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>">
        <input type="hidden" name="accion" value="subir">
        <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="titulo">Título</label>
            <input type="text" id="titulo" name="titulo" class="form-control" maxlength="255" required placeholder="Ej: Guía Semana 1">
        </div>
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="3" placeholder="Descripción opcional del material"></textarea>
        </div>
        <?php if ($tieneSubmodulos): ?>
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="categoria">Submódulo</label>
                <select id="categoria" name="categoria" class="form-control" required>
                    <option value="clase">Material clase</option>
                    <option value="profesor">Material profesor</option>
                </select>
            </div>
        <?php endif; ?>
        <?php if ($esCapacitacionDestino): ?>
            <div class="form-group" style="margin-bottom: 12px;">
                <label for="nivel">Nivel</label>
                <select id="nivel" name="nivel" class="form-control" required>
                    <option value="1">Nivel 1</option>
                    <option value="2">Nivel 2</option>
                    <option value="3">Nivel 3</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="modulo_numero">Módulo</label>
                <select id="modulo_numero" name="modulo_numero" class="form-control" required>
                    <option value="1">Módulo 1</option>
                    <option value="2">Módulo 2</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 12px;">
                <label for="leccion">Lección <span class="req">*</span></label>
                <input type="text" id="leccion" name="leccion" class="form-control" maxlength="120" required placeholder="Ej: Lección 1">
            </div>
        <?php endif; ?>
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="material_pdf">Archivo(s)</label>
            <input type="file" id="material_pdf" name="material_pdf[]" class="form-control" multiple required>
            <small style="display:block; margin-top:6px; color:#666;">Máximo 20MB por archivo. Puedes subir varios en un solo tema y se permiten varios formatos (pdf, docx, xlsx, pptx, mp4, etc.).</small>
        </div>
        <button type="submit" class="btn btn-primary">Subir archivos</button>
    </form>
</div>
<?php endif; ?>

<?php if ($usaTarjetasTipoMaterial): ?>
<?php if ($esCapacitacionDestino): ?>
<div id="cap-folder-explorer" class="folder-tree-explorer">
    <div class="folder-tree-row">
        <span class="folder-tree-label">Carpeta principal</span>
        <div class="folder-tree-items" id="cap-folder-niveles"></div>
    </div>
    <div class="folder-tree-row">
        <span class="folder-tree-label">Subcarpetas por módulo</span>
        <div class="folder-tree-items" id="cap-folder-modulos"></div>
    </div>
    <div class="folder-tree-row">
        <span class="folder-tree-label">Lecciones creadas</span>
        <div class="folder-tree-items" id="cap-folder-lecciones"></div>
    </div>
    <div class="folder-tree-row">
        <span class="folder-tree-label">Carpeta de material</span>
        <div class="folder-tree-items" id="cap-folder-categorias"></div>
    </div>
</div>
<?php else: ?>
<div class="cap-entry-grid">
    <article class="cap-entry-card js-open-cap-modal" data-target="profesor" role="button" tabindex="0" aria-label="Abrir Material profesor">
        <h4>Material profesor</h4>
        <p>Ver por niveles y subtarjetas por módulo el contenido exclusivo para profesor.</p>
    </article>
    <article class="cap-entry-card js-open-cap-modal" data-target="clase" role="button" tabindex="0" aria-label="Abrir Material clase">
        <h4>Material clase</h4>
        <p>Ver por niveles y subtarjetas por módulo todo el contenido de clase en pantalla grande.</p>
    </article>
</div>
<?php endif; ?>

<div id="cap-inline-panel" class="cap-panel" aria-hidden="true">
<?php endif; ?>

<div class="card" style="padding:14px;">
    <h3 style="margin-top:0;">Módulos de material</h3>

    <?php if ($tieneSubmodulos && !$usaTarjetasTipoMaterial): ?>
        <div class="submodulo-tabs" role="tablist" aria-label="Submódulos de material">
            <button type="button" class="submodulo-tab is-active js-submodulo-tab" data-target="submodulo-panel-clase" role="tab" aria-selected="true">Material clase</button>
            <button type="button" class="submodulo-tab js-submodulo-tab" data-target="submodulo-panel-profesor" role="tab" aria-selected="false">Material profesor</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($temas)): ?>
        <?php
            if ($tieneSubmodulos && $esCapacitacionDestino) {
                $bloques = [];
                foreach ($configCapacitacionDestino as $nivelTmp => $modulosTmp) {
                    foreach ((array)$modulosTmp as $moduloTmp) {
                        foreach (['profesor', 'clase'] as $categoriaTmp) {
                            $temasBloqueTmp = array_values(array_filter($temas, static function($temaTmp) use ($nivelTmp, $moduloTmp, $categoriaTmp) {
                                $categoriaTemaTmp = strtolower(trim((string)($temaTmp['categoria'] ?? 'clase')));
                                if ($categoriaTemaTmp !== 'profesor') {
                                    $categoriaTemaTmp = 'clase';
                                }

                                return (int)($temaTmp['nivel'] ?? 0) === (int)$nivelTmp
                                    && (int)($temaTmp['modulo_numero'] ?? 0) === (int)$moduloTmp
                                    && $categoriaTemaTmp === $categoriaTmp;
                            }));

                            $bloques[] = [
                                'titulo' => 'Módulo ' . (int)$moduloTmp,
                                'temas' => $temasBloqueTmp,
                                'nivel' => (int)$nivelTmp,
                                'modulo_numero' => (int)$moduloTmp,
                                'categoria' => $categoriaTmp,
                            ];
                        }
                    }
                }
            } elseif ($tieneSubmodulos && $usaTarjetasTipoMaterial) {
                $bloques = [
                    ['titulo' => 'Material clase',    'temas' => $temasClase,    'categoria' => 'clase'],
                    ['titulo' => 'Material profesor', 'temas' => $temasProfesor, 'categoria' => 'profesor'],
                ];
            } else {
                $bloques = $tieneSubmodulos
                    ? [
                        ['titulo' => 'Material clase',    'temas' => $temasClase],
                        ['titulo' => 'Material profesor', 'temas' => $temasProfesor],
                    ]
                    : [
                        ['titulo' => 'Temas', 'temas' => $temas],
                    ];
            }
        ?>

        <?php if ($usaTarjetasTipoMaterial && !$esCapacitacionDestino): ?><div class="cap-destino-grid"><?php endif; ?>

        <?php
        $bloqueIndex = 0;
        $lastNivelRender = null;
        foreach ($bloques as $bloque):
            if ($esCapacitacionDestino) {
                $nivelActualRender = (int)($bloque['nivel'] ?? 0);
                if ($nivelActualRender !== $lastNivelRender) {
                    // Cerrar sección anterior
                    if ($lastNivelRender !== null) {
                        echo '</div></div>'; // .cap-destino-grid y .cap-nivel-section
                    }
                    // Abrir nueva sección de nivel
                    echo '<div class="cap-nivel-section" data-modulo-grupo="' . (int)$nivelActualRender . '">';
                    echo '<div class="cap-nivel-header"><h4 class="cap-nivel-label">Nivel ' . (int)$nivelActualRender . '</h4></div>';

                    echo '<div class="cap-destino-grid">';
                    $lastNivelRender = $nivelActualRender;
                }
            }
        ?>
            <?php
                $tituloBloque = (string)($bloque['titulo'] ?? 'Temas');
                $categoriaBloque = strtolower(trim((string)($bloque['categoria'] ?? 'general')));
                if ($categoriaBloque === '') {
                    $categoriaBloque = 'general';
                }

                if ($esCapacitacionDestino) {
                    $tituloBloque = 'Módulo ' . (int)($bloque['modulo_numero'] ?? 0)
                        . ' - Material ' . ($categoriaBloque === 'profesor' ? 'profesor' : 'clase');
                }

                $claseCssBloque = 'submodulo-wrap';
                $panelIdBloque = 'submodulo-panel-' . $bloqueIndex;

                // Para cap destino usamos la categoría directamente; para otros módulos miramos el título
                if ($esCapacitacionDestino) {
                    if ($categoriaBloque === 'profesor') {
                        $claseCssBloque .= ' submodulo-profesor';
                    } else {
                        $claseCssBloque .= ' submodulo-clase';
                    }
                } else {
                    if (stripos($tituloBloque, 'profesor') !== false) {
                        $claseCssBloque .= ' submodulo-profesor';
                        if (!$usaTarjetasTipoMaterial) {
                            $panelIdBloque = 'submodulo-panel-profesor';
                        }
                    } elseif (stripos($tituloBloque, 'clase') !== false) {
                        $claseCssBloque .= ' submodulo-clase';
                        if (!$usaTarjetasTipoMaterial) {
                            $panelIdBloque = 'submodulo-panel-clase';
                        }
                    }
                }
                $totalTemasBloque = count((array)($bloque['temas'] ?? []));

                $nivelBloqueProf = (int)($bloque['nivel'] ?? 0);
                $moduloBloqueProf = (int)($bloque['modulo_numero'] ?? 0);
                $keyProfesorBloque = $nivelBloqueProf . '_' . $moduloBloqueProf;
                $nombreProfesorBloque = trim((string)($profesoresModulos[$keyProfesorBloque] ?? ''));
                $formIdProfesorBloque = 'form-prof-bloque-' . $bloqueIndex;

                if ($tieneSubmodulos && !$usaTarjetasTipoMaterial && $panelIdBloque === 'submodulo-panel-profesor') {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <?php
                if ($usaTarjetasTipoMaterial) {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <div
                id="<?= htmlspecialchars($panelIdBloque, ENT_QUOTES, 'UTF-8') ?>"
                class="<?= htmlspecialchars($claseCssBloque, ENT_QUOTES, 'UTF-8') ?> js-cap-block"
                data-cap-categoria="<?= htmlspecialchars($categoriaBloque, ENT_QUOTES, 'UTF-8') ?>"
                data-cap-nivel="<?= (int)$nivelBloqueProf ?>"
                data-cap-modulo="<?= (int)$moduloBloqueProf ?>"
                data-cap-titulo="<?= htmlspecialchars($tituloBloque, ENT_QUOTES, 'UTF-8') ?>"
                data-cap-total="<?= (int)$totalTemasBloque ?>"
                role="tabpanel">
                <div class="submodulo-head">
                    <h4 class="submodulo-title"><?= htmlspecialchars($tituloBloque) ?></h4>
                    <span class="submodulo-meta"><?= (int)$totalTemasBloque ?> tema(s)</span>
                </div>

                <?php if ($esCapacitacionDestino): ?>
                    <div class="cap-modulo-profesor-row" style="padding:8px 10px; border-bottom:1px solid #e6eef9; background:#f8fbff;">
                        <i class="bi bi-person-badge" style="font-size:13px;"></i>
                        <span>Profesor de este módulo:</span>
                        <span class="cap-modulo-profesor-nombre">
                            <?= $nombreProfesorBloque !== '' ? htmlspecialchars($nombreProfesorBloque) : '<em style="color:#9aabbd;">Sin asignar</em>' ?>
                        </span>
                        <?php if ($puedeGestionar): ?>
                            <button type="button" class="btn btn-sm btn-secondary js-toggle-profesor-form"
                                data-target="<?= htmlspecialchars($formIdProfesorBloque, ENT_QUOTES, 'UTF-8') ?>"
                                style="font-size:11px; padding:2px 8px;">Editar</button>
                        <?php endif; ?>
                    </div>
                    <?php if ($puedeGestionar): ?>
                        <form id="<?= htmlspecialchars($formIdProfesorBloque, ENT_QUOTES, 'UTF-8') ?>" method="POST"
                            action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>"
                            class="cap-modulo-profesor-form" style="margin:8px 10px 0 10px;">
                            <input type="hidden" name="accion" value="guardar_profesor_modulo">
                            <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                            <input type="hidden" name="nivel" value="<?= (int)$nivelBloqueProf ?>">
                            <input type="hidden" name="modulo_numero" value="<?= (int)$moduloBloqueProf ?>">
                            <input type="hidden" name="contexto_nivel" value="<?= (int)$nivelBloqueProf ?>">
                            <input type="hidden" name="contexto_modulo" value="<?= (int)$moduloBloqueProf ?>">
                            <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaBloque, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="text" name="profesor_nombre" class="form-control" style="font-size:12px; padding:4px 8px; flex:1; min-width:170px;"
                                placeholder="Nombre del profesor" maxlength="255"
                                value="<?= htmlspecialchars($nombreProfesorBloque, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-sm btn-primary" style="font-size:12px; padding:4px 10px;">Guardar</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="submodulo-body">
                    <div class="table-container" style="margin-bottom:0;">
                        <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-titulo">Título</th>
                            <th class="col-descripcion">Lección</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $temasBloqueRender = (array)($bloque['temas'] ?? []);
                        if ($esCapacitacionDestino && !empty($temasBloqueRender)) {
                            usort($temasBloqueRender, static function(array $a, array $b) {
                                $leccionA = trim((string)($a['leccion'] ?? ''));
                                $leccionB = trim((string)($b['leccion'] ?? ''));

                                $numA = null;
                                $numB = null;
                                if (preg_match('/\d+/', $leccionA, $mA) === 1) {
                                    $numA = (int)$mA[0];
                                }
                                if (preg_match('/\d+/', $leccionB, $mB) === 1) {
                                    $numB = (int)$mB[0];
                                }

                                // Lecciones con número primero (1,2,3...), sin número al final.
                                if ($numA === null && $numB !== null) {
                                    return 1;
                                }
                                if ($numA !== null && $numB === null) {
                                    return -1;
                                }
                                if ($numA !== null && $numB !== null) {
                                    $cmpNum = $numA <=> $numB;
                                    if ($cmpNum !== 0) {
                                        return $cmpNum;
                                    }
                                }

                                $tsA = (int)($a['creado_ts'] ?? 0);
                                $tsB = (int)($b['creado_ts'] ?? 0);
                                return $tsB <=> $tsA;
                            });
                        }
                        ?>
                        <?php if (!empty($temasBloqueRender)): ?>
                            <?php foreach ($temasBloqueRender as $index => $tema): ?>
                                <?php
                                $temaId = 'tema-' . $bloqueIndex . '-' . $index;
                                $temaEditId = 'tema-edit-' . $bloqueIndex . '-' . $index;
                                $temaAgregarArchivosId = 'tema-add-files-' . $bloqueIndex . '-' . $index;
                                $archivosTema = (array)($tema['archivos'] ?? []);
                                $categoriaTema = strtolower(trim((string)($tema['categoria'] ?? 'general')));
                                $imagenesTema = [];
                                foreach ($archivosTema as $archivoTemaGaleria) {
                                    $nombreGaleria = (string)($archivoTemaGaleria['nombre'] ?? '');
                                    $extGaleria = strtolower((string)pathinfo($nombreGaleria, PATHINFO_EXTENSION));
                                    if (!in_array($extGaleria, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                                        continue;
                                    }

                                    $imagenesTema[] = [
                                        'src' => rtrim(PUBLIC_URL, '/') . '/uploads/material_hub/' . rawurlencode($clave) . '/' . rawurlencode($nombreGaleria),
                                        'nombre' => $nombreGaleria,
                                        'abrir' => (string)($archivoTemaGaleria['url'] ?? '#'),
                                    ];
                                }
                                $imagenesTemaJson = json_encode($imagenesTema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                if ($imagenesTemaJson === false) {
                                    $imagenesTemaJson = '[]';
                                }
                                $leccionTemaData = trim((string)($tema['leccion'] ?? ''));
                                if ($leccionTemaData === '') {
                                    $leccionTemaData = 'Sin lección';
                                }
                                ?>
                                <tr class="js-tema-row"
                                    data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>"
                                    data-lote-id="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-cap-nivel="<?= (int)$nivelBloqueProf ?>"
                                    data-cap-modulo="<?= (int)$moduloBloqueProf ?>"
                                    data-cap-categoria="<?= htmlspecialchars($categoriaBloque, ENT_QUOTES, 'UTF-8') ?>"
                                    data-cap-leccion="<?= htmlspecialchars($leccionTemaData, ENT_QUOTES, 'UTF-8') ?>">
                                    <td class="col-titulo" title="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>">
                                        <strong><?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material')) ?></strong>
                                        <?php if ($esCapacitacionDestino && trim((string)($tema['leccion'] ?? '')) !== ''): ?>
                                            <div style="margin-top:4px;"><small style="color:#5b6f8d; font-weight:600;"><?= htmlspecialchars((string)$tema['leccion']) ?></small></div>
                                        <?php endif; ?>
                                    </td>
                                    <?php $leccionTema = trim((string)($tema['leccion'] ?? '')); ?>
                                    <?php
                                    $leccionNumero = '';
                                    if ($leccionTema !== '' && preg_match('/\d+/', $leccionTema, $mLeccion) === 1) {
                                        $leccionNumero = (string)$mLeccion[0];
                                    }
                                    if ($leccionNumero === '') {
                                        $leccionNumero = '—';
                                    }
                                    ?>
                                    <td class="col-descripcion" title="<?= htmlspecialchars($leccionTema !== '' ? $leccionTema : 'Sin lección', ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="descripcion-cell">
                                            <span class="descripcion-preview"><?= htmlspecialchars($leccionNumero) ?></span>
                                        </span>
                                    </td>
                                    <td class="col-acciones">
                                        <div class="tema-acciones">
                                            <div class="tema-acciones-row">
                                                <button type="button" class="btn btn-sm btn-secondary js-toggle-tema" data-target="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>">Ver archivos</button>
                                                <button type="button" class="btn btn-sm btn-info js-ver-vistas" data-lote="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Ver quién vio</button>
                                                <?php if (!empty($imagenesTema)): ?>
                                                    <button type="button" class="btn btn-sm btn-warning js-abrir-galeria-tema"
                                                        data-tema="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-images='<?= htmlspecialchars($imagenesTemaJson, ENT_QUOTES, 'UTF-8') ?>'>Presentar</button>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($puedeGestionar): ?>
                                                <div class="tema-acciones-row">
                                                    <button type="button" class="btn btn-sm btn-success js-toggle-agregar-archivos" data-target="<?= htmlspecialchars($temaAgregarArchivosId, ENT_QUOTES, 'UTF-8') ?>">Agregar archivos</button>
                                                    <button type="button" class="btn btn-sm btn-primary js-toggle-editar-tema" data-target="<?= htmlspecialchars($temaEditId, ENT_QUOTES, 'UTF-8') ?>">Editar</button>
                                                </div>
                                                <div class="tema-acciones-row is-danger">
                                                    <button type="button" class="btn btn-sm btn-danger js-eliminar-tema"
                                                        data-lote="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-titulo="<?= htmlspecialchars((string)($tema['titulo'] ?? 'este tema'), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-contexto-nivel="<?= (int)($tema['nivel'] ?? 0) ?>"
                                                        data-contexto-modulo="<?= (int)($tema['modulo_numero'] ?? 0) ?>"
                                                        data-contexto-categoria="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-contexto-leccion="<?= htmlspecialchars((string)($tema['leccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Eliminar clase</button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <?php if ($puedeGestionar): ?>
                                    <tr id="<?= htmlspecialchars($temaEditId, ENT_QUOTES, 'UTF-8') ?>" data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#fff9f0;">
                                        <td colspan="3">
                                            <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; align-items:end;">
                                                <input type="hidden" name="accion" value="editar_tema">
                                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                <input type="hidden" name="lote_id" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_lote" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_panel" value="editar">

                                                <div>
                                                    <label style="font-size:12px; color:#576b86;">Título</label>
                                                    <input type="text" name="titulo" class="form-control" maxlength="255" required value="<?= htmlspecialchars((string)($tema['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>

                                                <div>
                                                    <label style="font-size:12px; color:#576b86;">Descripción</label>
                                                    <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars((string)($tema['descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                </div>

                                                <?php if ($tieneSubmodulos): ?>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Submódulo</label>
                                                        <select name="categoria" class="form-control">
                                                            <option value="clase" <?= $categoriaTema === 'clase' ? 'selected' : '' ?>>Material clase</option>
                                                            <option value="profesor" <?= $categoriaTema === 'profesor' ? 'selected' : '' ?>>Material profesor</option>
                                                        </select>
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="categoria" value="general">
                                                <?php endif; ?>

                                                <?php if ($esCapacitacionDestino): ?>
                                                    <?php
                                                        $nivelTemaEdit = (int)($tema['nivel'] ?? 0);
                                                        $nivelTemaEdit = in_array($nivelTemaEdit, [1, 2, 3], true) ? $nivelTemaEdit : 1;
                                                        $moduloTemaEdit = (int)($tema['modulo_numero'] ?? 0);
                                                        $leccionTemaEdit = trim((string)($tema['leccion'] ?? ''));
                                                    ?>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Nivel</label>
                                                        <select name="nivel" class="form-control js-cap-destino-nivel">
                                                            <option value="1" <?= $nivelTemaEdit === 1 ? 'selected' : '' ?>>Nivel 1</option>
                                                            <option value="2" <?= $nivelTemaEdit === 2 ? 'selected' : '' ?>>Nivel 2</option>
                                                            <option value="3" <?= $nivelTemaEdit === 3 ? 'selected' : '' ?>>Nivel 3</option>
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Módulo</label>
                                                        <select name="modulo_numero" class="form-control js-cap-destino-modulo" data-selected="<?= (int)$moduloTemaEdit ?>"></select>
                                                    </div>
                                                    <div>
                                                        <label style="font-size:12px; color:#576b86;">Lección</label>
                                                        <input type="text" name="leccion" class="form-control" maxlength="120" required value="<?= htmlspecialchars($leccionTemaEdit, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Lección 1">
                                                    </div>
                                                <?php else: ?>
                                                    <input type="hidden" name="nivel" value="0">
                                                    <input type="hidden" name="modulo_numero" value="0">
                                                    <input type="hidden" name="leccion" value="">
                                                <?php endif; ?>

                                                <div>
                                                    <button type="submit" class="btn btn-sm btn-primary">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($puedeGestionar): ?>
                                    <tr id="<?= htmlspecialchars($temaAgregarArchivosId, ENT_QUOTES, 'UTF-8') ?>" data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#eefaf4;">
                                        <td colspan="3">
                                            <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:10px; align-items:end;">
                                                <input type="hidden" name="accion" value="agregar_archivos_tema">
                                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                <input type="hidden" name="lote_id" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_nivel" value="<?= (int)($tema['nivel'] ?? 0) ?>">
                                                <input type="hidden" name="contexto_modulo" value="<?= (int)($tema['modulo_numero'] ?? 0) ?>">
                                                <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_leccion" value="<?= htmlspecialchars((string)($tema['leccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_lote" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                <input type="hidden" name="contexto_open_panel" value="agregar">

                                                <div>
                                                    <label style="font-size:12px; color:#576b86;">Agregar más archivos a este tema</label>
                                                    <input type="file" name="material_pdf[]" class="form-control" multiple required>
                                                    <small style="display:block; margin-top:4px; color:#6b7d95;">Puedes subir varios archivos adicionales (máx. 20MB por archivo).</small>
                                                </div>

                                                <div>
                                                    <button type="submit" class="btn btn-sm btn-success">Subir al tema</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <tr id="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" data-tema-key="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#f9fbff;">
                                    <td colspan="3">
                                        <?php if (!empty($archivosTema)): ?>
                                            <div style="display:flex; flex-wrap:wrap; gap:12px;">
                                                <?php foreach ($archivosTema as $indexArchivoActual => $archivo): ?>
                                                    <?php
                                                    $nombreArchivo = (string)($archivo['nombre'] ?? '');
                                                    $extArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
                                                    $urlDirectaArchivo = rtrim(PUBLIC_URL, '/') . '/uploads/material_hub/' . rawurlencode($clave) . '/' . rawurlencode($nombreArchivo);
                                                    $urlVerArchivo = htmlspecialchars((string)($archivo['url'] ?? '#'));
                                                    $esImagen = in_array($extArchivo, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                    $esVideo  = in_array($extArchivo, ['mp4', 'webm', 'mov']);
                                                    $esPdf    = $extArchivo === 'pdf';
                                                    $esOffice = in_array($extArchivo, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
                                                    $urlPreviewOffice = '';
                                                    if ($esOffice) {
                                                        $urlAbsolutaPreview = $urlDirectaArchivo;
                                                        if (stripos($urlAbsolutaPreview, 'http://') !== 0 && stripos($urlAbsolutaPreview, 'https://') !== 0) {
                                                            $hostActual = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . (string)($_SERVER['HTTP_HOST'] ?? '');
                                                            $pathPreview = (string)(parse_url($urlAbsolutaPreview, PHP_URL_PATH) ?? $urlAbsolutaPreview);
                                                            $urlAbsolutaPreview = rtrim($hostActual, '/') . '/' . ltrim($pathPreview, '/');
                                                        }

                                                        $hostPreview = strtolower((string)parse_url($urlAbsolutaPreview, PHP_URL_HOST));
                                                        $esHostLocal = in_array($hostPreview, ['localhost', '127.0.0.1', '::1'], true);
                                                        if (!$esHostLocal && preg_match('/^https?:\/\//i', $urlAbsolutaPreview)) {
                                                            $urlPreviewOffice = 'https://view.officeapps.live.com/op/embed.aspx?src=' . rawurlencode($urlAbsolutaPreview);
                                                        }
                                                    }
                                                    $indexImagenEnTema = 0;
                                                    if ($esImagen) {
                                                        foreach ($archivosTema as $i => $arch) {
                                                            if ($i >= $indexArchivoActual) break;
                                                            $extArch = strtolower((string)pathinfo((string)($arch['nombre'] ?? ''), PATHINFO_EXTENSION));
                                                            if (in_array($extArch, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                                                                $indexImagenEnTema++;
                                                            }
                                                        }
                                                    }
                                                    // Iconos por tipo para archivos no previsualizable
                                                    $iconosExt = ['docx'=>'bi-file-word','doc'=>'bi-file-word','xlsx'=>'bi-file-excel','xls'=>'bi-file-excel','pptx'=>'bi-file-ppt','ppt'=>'bi-file-ppt','zip'=>'bi-file-zip','rar'=>'bi-file-zip','mp3'=>'bi-file-music','wav'=>'bi-file-music'];
                                                    $iconoCls = $iconosExt[$extArchivo] ?? 'bi-file-earmark';
                                                    ?>
                                                    <div style="width:160px; border:1px solid #dce6f5; border-radius:10px; overflow:hidden; background:#fff; display:flex; flex-direction:column; box-shadow:0 1px 4px rgba(30,74,137,0.08);">
                                                        <!-- Zona de preview -->
                                                        <div style="height:140px; background:#f2f7ff; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative; border-bottom:1px solid #e1eaf8;">
                                                            <?php if ($esImagen): ?>
                                                                <button type="button"
                                                                    class="material-item-preview-btn js-abrir-galeria-desde-archivo"
                                                                    data-tema="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-images='<?= htmlspecialchars($imagenesTemaJson, ENT_QUOTES, 'UTF-8') ?>'
                                                                    data-index="<?= (int)$indexImagenEnTema ?>"
                                                                    aria-label="Abrir galería de imágenes">
                                                                    <img src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>"
                                                                         alt="<?= htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8') ?>">
                                                                </button>
                                                            <?php elseif ($esVideo): ?>
                                                                <video style="width:100%; height:100%; object-fit:cover; display:block;" muted preload="metadata">
                                                                    <source src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>#t=0.5">
                                                                </video>
                                                                <div style="position:absolute; bottom:6px; right:8px; background:rgba(0,0,0,0.55); border-radius:4px; padding:2px 6px;">
                                                                    <i class="bi bi-play-fill" style="color:#fff; font-size:14px;"></i>
                                                                </div>
                                                            <?php elseif ($esPdf): ?>
                                                                <div style="width:100%; height:100%; position:relative; background:#ffffff;">
                                                                    <iframe
                                                                        src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>#page=1&view=FitH&toolbar=0&navpanes=0&scrollbar=0"
                                                                        title="Vista previa PDF"
                                                                        loading="lazy"
                                                                        style="width:100%; height:100%; border:0; pointer-events:none; background:#fff;">
                                                                    </iframe>
                                                                    <div style="position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.55); border-radius:4px; padding:2px 6px; color:#fff; font-size:10px; text-transform:uppercase; letter-spacing:.5px;">
                                                                        PDF
                                                                    </div>
                                                                </div>
                                                            <?php elseif ($esOffice && $urlPreviewOffice !== ''): ?>
                                                                <div style="width:100%; height:100%; position:relative; background:#ffffff;">
                                                                    <iframe
                                                                        src="<?= htmlspecialchars($urlPreviewOffice, ENT_QUOTES, 'UTF-8') ?>"
                                                                        title="Vista previa Office"
                                                                        loading="lazy"
                                                                        style="width:100%; height:100%; border:0; pointer-events:none; background:#fff;">
                                                                    </iframe>
                                                                    <div style="position:absolute; top:6px; right:6px; background:rgba(0,0,0,0.55); border-radius:4px; padding:2px 6px; color:#fff; font-size:10px; text-transform:uppercase; letter-spacing:.5px;">
                                                                        <?= htmlspecialchars(strtoupper($extArchivo)) ?>
                                                                    </div>
                                                                </div>
                                                            <?php elseif ($esOffice): ?>
                                                                <div style="display:flex; flex-direction:column; align-items:center; gap:6px; text-align:center; padding:10px;">
                                                                    <i class="bi <?= htmlspecialchars($iconoCls) ?>" style="font-size:48px; color:#5f86b7;"></i>
                                                                    <span style="color:#5f7ea3; font-size:11px; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars(strtoupper($extArchivo)) ?></span>
                                                                    <small style="color:#7c90ac; line-height:1.25;">Vista previa disponible al abrir el archivo.</small>
                                                                </div>
                                                            <?php else: ?>
                                                                <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                                                                    <i class="bi <?= htmlspecialchars($iconoCls) ?>" style="font-size:48px; color:#5f86b7;"></i>
                                                                    <span style="color:#5f7ea3; font-size:11px; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars(strtoupper($extArchivo)) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <!-- Info y acciones -->
                                                        <div style="padding:8px 10px; flex:1; display:flex; flex-direction:column; gap:6px;">
                                                            <div style="font-size:12px; font-weight:600; color:#1e3a5f; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8') ?>">
                                                                <?= htmlspecialchars($nombreArchivo) ?>
                                                            </div>
                                                            <div style="font-size:11px; color:#8a9bb5;"><?= number_format((float)($archivo['peso_kb'] ?? 0), 1) ?> KB</div>
                                                            <div style="display:flex; gap:6px; flex-wrap:wrap; margin-top:auto;">
                                                                <?php if ($esImagen): ?>
                                                                    <button type="button" class="btn btn-sm btn-success js-abrir-galeria-desde-archivo"
                                                                        data-tema="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                                        data-images='<?= htmlspecialchars($imagenesTemaJson, ENT_QUOTES, 'UTF-8') ?>'
                                                                        data-index="<?= (int)$indexImagenEnTema ?>"
                                                                        style="font-size:11px; padding:3px 8px;">Abrir</button>
                                                                <?php else: ?>
                                                                    <a href="<?= $urlVerArchivo ?>" target="_blank" class="btn btn-sm btn-success" style="font-size:11px; padding:3px 8px;">Abrir</a>
                                                                <?php endif; ?>
                                                                <?php if ($puedeGestionar): ?>
                                                                    <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" onsubmit="return confirm('¿Eliminar este archivo?');" style="margin:0;">
                                                                        <input type="hidden" name="accion" value="eliminar">
                                                                        <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                                        <input type="hidden" name="archivo" value="<?= htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_nivel" value="<?= (int)($tema['nivel'] ?? 0) ?>">
                                                                        <input type="hidden" name="contexto_modulo" value="<?= (int)($tema['modulo_numero'] ?? 0) ?>">
                                                                        <input type="hidden" name="contexto_categoria" value="<?= htmlspecialchars($categoriaTema, ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_leccion" value="<?= htmlspecialchars((string)($tema['leccion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_open_lote" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                        <input type="hidden" name="contexto_open_panel" value="archivos">
                                                                        <button type="submit" class="btn btn-sm btn-danger" style="font-size:11px; padding:3px 8px;">Eliminar</button>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color:#6b7d95;">Este tema no tiene archivos.</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align:center; color:#6b7d95;">No hay temas cargados en este submódulo.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php $bloqueIndex++; endforeach; ?>
        <?php if ($esCapacitacionDestino && $lastNivelRender !== null): ?>
            </div></div><?php // cierre .cap-destino-grid y .cap-nivel-section de la última sección ?>
        <?php endif; ?>

        <?php if ($usaTarjetasTipoMaterial && !$esCapacitacionDestino): ?></div><?php endif; ?>
    <?php else: ?>
        <p style="margin:0; color:#666;">No hay temas cargados en este módulo.</p>
    <?php endif; ?>
</div>

<?php if ($usaTarjetasTipoMaterial): ?>
</div>
<?php endif; ?>

<form id="form-eliminar-tema" method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:none;">
    <input type="hidden" name="accion" value="eliminar_tema">
    <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
    <input type="hidden" id="form-eliminar-tema-lote" name="lote_id" value="">
    <input type="hidden" id="form-eliminar-tema-contexto-nivel" name="contexto_nivel" value="0">
    <input type="hidden" id="form-eliminar-tema-contexto-modulo" name="contexto_modulo" value="0">
    <input type="hidden" id="form-eliminar-tema-contexto-categoria" name="contexto_categoria" value="">
    <input type="hidden" id="form-eliminar-tema-contexto-leccion" name="contexto_leccion" value="">
</form>

<div id="modal-vistas-material" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow:auto;">
    <div style="background:white; margin:40px auto; padding:30px; border-radius:8px; max-width:700px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;">Personas que vieron el material</h3>
            <button type="button" class="btn btn-sm" onclick="document.getElementById('modal-vistas-material').style.display='none';" style="padding:5px 10px;">x</button>
        </div>

        <div id="modal-content-loading" style="text-align:center; padding:20px;">
            <p>Cargando...</p>
        </div>

        <div id="modal-content-vistas" style="display:none;">
            <div style="background:#f8f9fa; padding:12px; border-radius:4px; margin-bottom:16px;">
                <strong>Tema:</strong> <span id="modal-tema-nombre"></span>
            </div>

            <div style="background:#f8f9fa; padding:12px; border-radius:4px; margin-bottom:16px;">
                <strong>Total de personas:</strong> <span id="modal-total-personas" style="font-size:1.2em; color:#007bff;">0</span>
            </div>

            <table class="table table-hover" style="margin-bottom:0;">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="width:150px;">Ministerio</th>
                        <th style="width:120px;">Vistas</th>
                        <th style="width:160px;">Última vista</th>
                    </tr>
                </thead>
                <tbody id="modal-vistas-list"></tbody>
            </table>
        </div>

        <div id="modal-content-error" style="display:none; background:#f8d7da; padding:12px; border-radius:4px; color:#721c24;"></div>
    </div>
</div>

<div id="material-gallery-modal" class="material-gallery-modal" aria-hidden="true">
    <div class="material-gallery-shell" role="dialog" aria-modal="true" aria-labelledby="material-gallery-title">
        <div class="material-gallery-topbar">
            <div>
                <h3 id="material-gallery-title">Presentación de imágenes</h3>
                <small id="material-gallery-counter">0 / 0</small>
            </div>
            <button type="button" class="material-gallery-close" id="material-gallery-close" aria-label="Cerrar presentación">×</button>
        </div>
        <div class="material-gallery-stage">
            <button type="button" class="material-gallery-nav" id="material-gallery-prev" aria-label="Imagen anterior">‹</button>
            <div class="material-gallery-figure">
                <div class="material-gallery-frame">
                    <img id="material-gallery-image" src="" alt="">
                </div>
                <div class="material-gallery-caption">
                    <div>
                        <strong id="material-gallery-name">Imagen</strong>
                        <span id="material-gallery-help">Usa las flechas del teclado o las miniaturas para navegar.</span>
                    </div>
                    <a id="material-gallery-open" href="#" target="_blank" rel="noopener">Abrir archivo</a>
                </div>
            </div>
            <button type="button" class="material-gallery-nav" id="material-gallery-next" aria-label="Imagen siguiente">›</button>
        </div>
        <div id="material-gallery-thumbs" class="material-gallery-thumbs"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var configCapDestino = <?= json_encode($configCapacitacionDestino, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    var rutaEvaluacionesCap = <?= json_encode(PUBLIC_URL . '?url=home/discipular/evaluaciones&from_material=1', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    function poblarModulosCapDestino(selectNivel, selectModulo) {
        if (!selectNivel || !selectModulo) {
            return;
        }

        var nivel = String(selectNivel.value || '1');
        var modulos = configCapDestino[nivel] || [];
        var seleccionadoPrevio = String(selectModulo.getAttribute('data-selected') || selectModulo.value || '');

        selectModulo.innerHTML = '';
        modulos.forEach(function(moduloNumero) {
            var opt = document.createElement('option');
            opt.value = String(moduloNumero);
            opt.textContent = 'Módulo ' + String(moduloNumero);
            if (String(moduloNumero) === seleccionadoPrevio) {
                opt.selected = true;
            }
            selectModulo.appendChild(opt);
        });

        if (selectModulo.options.length > 0 && selectModulo.selectedIndex === -1) {
            selectModulo.selectedIndex = 0;
        }

        selectModulo.setAttribute('data-selected', selectModulo.value || '');
    }

    var nivelNuevo = document.getElementById('nivel');
    var moduloNuevo = document.getElementById('modulo_numero');
    if (nivelNuevo && moduloNuevo) {
        poblarModulosCapDestino(nivelNuevo, moduloNuevo);
        nivelNuevo.addEventListener('change', function() {
            moduloNuevo.setAttribute('data-selected', '');
            poblarModulosCapDestino(nivelNuevo, moduloNuevo);
        });
    }

    document.querySelectorAll('.js-cap-destino-nivel').forEach(function(selectNivel) {
        var contenedor = selectNivel.closest('form');
        if (!contenedor) {
            return;
        }

        var selectModulo = contenedor.querySelector('.js-cap-destino-modulo');
        if (!selectModulo) {
            return;
        }

        poblarModulosCapDestino(selectNivel, selectModulo);
        selectNivel.addEventListener('change', function() {
            selectModulo.setAttribute('data-selected', '');
            poblarModulosCapDestino(selectNivel, selectModulo);
        });
    });

    var modalElement = document.getElementById('modal-vistas-material');
    var botones = document.querySelectorAll('.js-ver-vistas');
    var botonesTema = document.querySelectorAll('.js-toggle-tema');
    var galeriaModal = document.getElementById('material-gallery-modal');
    var galeriaTitulo = document.getElementById('material-gallery-title');
    var galeriaContador = document.getElementById('material-gallery-counter');
    var galeriaImagen = document.getElementById('material-gallery-image');
    var galeriaNombre = document.getElementById('material-gallery-name');
    var galeriaAbrir = document.getElementById('material-gallery-open');
    var galeriaPrev = document.getElementById('material-gallery-prev');
    var galeriaNext = document.getElementById('material-gallery-next');
    var galeriaThumbs = document.getElementById('material-gallery-thumbs');
    var galeriaClose = document.getElementById('material-gallery-close');
    var estadoGaleria = {
        items: [],
        index: 0,
        tema: ''
    };

    function renderizarGaleria() {
        if (!galeriaModal || !galeriaImagen || !estadoGaleria.items.length) {
            return;
        }

        if (estadoGaleria.index < 0) {
            estadoGaleria.index = 0;
        }
        if (estadoGaleria.index >= estadoGaleria.items.length) {
            estadoGaleria.index = estadoGaleria.items.length - 1;
        }

        var actual = estadoGaleria.items[estadoGaleria.index];
        galeriaImagen.src = actual.src || '';
        galeriaImagen.alt = actual.nombre || 'Imagen del material';
        galeriaNombre.textContent = actual.nombre || 'Imagen';
        galeriaAbrir.href = actual.abrir || actual.src || '#';
        galeriaContador.textContent = (estadoGaleria.index + 1) + ' / ' + estadoGaleria.items.length;
        galeriaTitulo.textContent = estadoGaleria.tema || 'Presentación de imágenes';
        galeriaPrev.disabled = estadoGaleria.items.length <= 1;
        galeriaNext.disabled = estadoGaleria.items.length <= 1;

        if (galeriaThumbs) {
            Array.prototype.forEach.call(galeriaThumbs.querySelectorAll('.material-gallery-thumb'), function(btn, idx) {
                btn.classList.toggle('is-active', idx === estadoGaleria.index);
            });
        }
    }

    function abrirGaleria(items, tema, indexInicial) {
        if (!galeriaModal || !Array.isArray(items) || !items.length) {
            return;
        }

        estadoGaleria.items = items;
        estadoGaleria.index = typeof indexInicial === 'number' ? indexInicial : 0;
        estadoGaleria.tema = tema || 'Presentación de imágenes';

        if (galeriaThumbs) {
            galeriaThumbs.innerHTML = '';
            items.forEach(function(item, idx) {
                var thumb = document.createElement('button');
                thumb.type = 'button';
                thumb.className = 'material-gallery-thumb';
                thumb.setAttribute('aria-label', 'Ir a imagen ' + (idx + 1));

                var thumbImg = document.createElement('img');
                thumbImg.src = item.src || '';
                thumbImg.alt = item.nombre || ('Imagen ' + (idx + 1));
                thumb.appendChild(thumbImg);

                thumb.addEventListener('click', function() {
                    estadoGaleria.index = idx;
                    renderizarGaleria();
                });

                galeriaThumbs.appendChild(thumb);
            });
        }

        galeriaModal.classList.add('is-open');
        galeriaModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        renderizarGaleria();
    }

    function cerrarGaleria() {
        if (!galeriaModal) {
            return;
        }

        galeriaModal.classList.remove('is-open');
        galeriaModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        estadoGaleria.items = [];
        estadoGaleria.index = 0;
        estadoGaleria.tema = '';

        if (galeriaImagen) {
            galeriaImagen.src = '';
            galeriaImagen.alt = '';
        }
        if (galeriaThumbs) {
            galeriaThumbs.innerHTML = '';
        }
    }

    function moverGaleria(delta) {
        if (!estadoGaleria.items.length) {
            return;
        }

        var total = estadoGaleria.items.length;
        estadoGaleria.index = (estadoGaleria.index + delta + total) % total;
        renderizarGaleria();
    }

    document.querySelectorAll('.js-abrir-galeria-tema').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tema = btn.getAttribute('data-tema') || 'Presentación de imágenes';
            var data = btn.getAttribute('data-images') || '[]';

            try {
                var items = JSON.parse(data);
                abrirGaleria(items, tema, 0);
            } catch (error) {
                console.error('No se pudo abrir la galería del tema.', error);
            }
        });
    });

    document.querySelectorAll('.js-abrir-galeria-desde-archivo').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tema = btn.getAttribute('data-tema') || 'Presentación de imágenes';
            var data = btn.getAttribute('data-images') || '[]';
            var index = parseInt(btn.getAttribute('data-index') || '0', 10);

            try {
                var items = JSON.parse(data);
                abrirGaleria(items, tema, index);
            } catch (error) {
                console.error('No se pudo abrir la galería desde el archivo.', error);
            }
        });
    });

    if (galeriaPrev) {
        galeriaPrev.addEventListener('click', function() {
            moverGaleria(-1);
        });
    }

    if (galeriaNext) {
        galeriaNext.addEventListener('click', function() {
            moverGaleria(1);
        });
    }

    if (galeriaClose) {
        galeriaClose.addEventListener('click', cerrarGaleria);
    }

    botonesTema.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (!row) {
                return;
            }
            row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
        });
    });

    var tabsSubmodulo = document.querySelectorAll('.js-submodulo-tab');
    tabsSubmodulo.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            if (!targetId) {
                return;
            }

            tabsSubmodulo.forEach(function(tabBtn) {
                tabBtn.classList.remove('is-active');
                tabBtn.setAttribute('aria-selected', 'false');
            });
            this.classList.add('is-active');
            this.setAttribute('aria-selected', 'true');

            ['submodulo-panel-clase', 'submodulo-panel-profesor'].forEach(function(panelId) {
                var panel = document.getElementById(panelId);
                if (!panel) {
                    return;
                }
                if (panelId === targetId) {
                    panel.classList.remove('is-hidden');
                } else {
                    panel.classList.add('is-hidden');
                }
            });
        });
    });

    var capEntryCards = document.querySelectorAll('.js-open-cap-modal');
    var esCapacitacionDestinoVista = <?= $esCapacitacionDestino ? 'true' : 'false' ?>;
    var capInlinePanel = document.getElementById('cap-inline-panel');

    function marcarTarjetaPrincipalActiva(categoriaObjetivo) {
        var categoria = (categoriaObjetivo || '').toLowerCase();
        capEntryCards.forEach(function(card) {
            var target = (card.getAttribute('data-target') || '').toLowerCase();
            card.classList.toggle('is-active', target === categoria);
        });
    }

    function activarCategoriaPrincipal(categoriaObjetivo) {
        var categoria = (categoriaObjetivo || '').toLowerCase();
        if (!categoria) {
            categoria = 'clase';
        }

        marcarTarjetaPrincipalActiva(categoria);

        document.querySelectorAll('.js-cap-block').forEach(function(panel) {
            var categoriaPanel = (panel.getAttribute('data-cap-categoria') || '').toLowerCase();
            var mostrar = categoriaPanel === categoria;
            panel.classList.toggle('is-hidden', !mostrar);
            panel.classList.remove('is-selected');
            var body = panel.querySelector('.submodulo-body');
            if (body) {
                body.style.display = 'none';
            }
        });

        // Oculta secciones de modulo vacias segun la categoria activa
        document.querySelectorAll('.cap-nivel-section').forEach(function(section) {
            var visibles = section.querySelectorAll('.js-cap-block:not(.is-hidden)').length;
            section.style.display = visibles > 0 ? '' : 'none';
        });

    }

    function normalizarLeccion(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[^\w\s-]/g, '')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function abrirPanelCapInline() {
        if (!capInlinePanel) {
            return;
        }
        capInlinePanel.classList.add('is-open');
        capInlinePanel.setAttribute('aria-hidden', 'false');
    }

    function aplicarFiltroCapacitacion(state) {
        var nivelObjetivo = String(state.nivel || '');
        var moduloObjetivo = String(state.modulo || '');
        var categoriaObjetivo = String(state.categoria || 'clase').toLowerCase();
        var leccionObjetivo = normalizarLeccion(state.leccion || '');

        abrirPanelCapInline();

        document.querySelectorAll('.cap-nivel-section').forEach(function(section) {
            var nivelSeccion = String(section.getAttribute('data-modulo-grupo') || '');
            section.style.display = nivelSeccion === nivelObjetivo ? '' : 'none';
            section.classList.remove('is-focus-mode');
        });

        document.querySelectorAll('.js-cap-block').forEach(function(panel) {
            var nivelPanel = String(panel.getAttribute('data-cap-nivel') || '');
            var moduloPanel = String(panel.getAttribute('data-cap-modulo') || '');
            var categoriaPanel = String(panel.getAttribute('data-cap-categoria') || '').toLowerCase();

            var mostrar = nivelPanel === nivelObjetivo
                && moduloPanel === moduloObjetivo
                && categoriaPanel === categoriaObjetivo;

            panel.classList.toggle('is-hidden', !mostrar);
            panel.classList.toggle('is-selected', mostrar);
            panel.classList.toggle('is-focused', mostrar);

            var body = panel.querySelector('.submodulo-body');
            if (body) {
                body.style.display = mostrar ? 'block' : 'none';
            }

            if (!mostrar) {
                return;
            }

            panel.querySelectorAll('.js-tema-row').forEach(function(mainRow) {
                var key = String(mainRow.getAttribute('data-tema-key') || '');
                var leccionRow = normalizarLeccion(mainRow.getAttribute('data-cap-leccion') || '');
                var coincideLeccion = (leccionObjetivo === '' || leccionRow === leccionObjetivo);

                mainRow.style.display = coincideLeccion ? '' : 'none';

                if (!coincideLeccion && key !== '') {
                    panel.querySelectorAll('tr[data-tema-key="' + key.replace(/"/g, '\\"') + '"]').forEach(function(relatedRow) {
                        relatedRow.style.display = 'none';
                    });
                }
            });
        });
    }

    var folderExplorer = document.getElementById('cap-folder-explorer');
    if (esCapacitacionDestinoVista && folderExplorer) {
        var contNiveles = document.getElementById('cap-folder-niveles');
        var contModulos = document.getElementById('cap-folder-modulos');
        var contLecciones = document.getElementById('cap-folder-lecciones');
        var contCategorias = document.getElementById('cap-folder-categorias');

        var carpetaState = {
            nivel: '1',
            modulo: '',
            leccion: '',
            categoria: 'clase'
        };

        var queryParams = new URLSearchParams(window.location.search || '');
        var nivelQuery = String(queryParams.get('cap_nivel') || '').trim();
        var moduloQuery = String(queryParams.get('cap_modulo') || '').trim();
        var categoriaQuery = String(queryParams.get('cap_categoria') || '').trim().toLowerCase();
        var leccionQuery = String(queryParams.get('cap_leccion') || '').trim();
        var openLoteQuery = String(queryParams.get('cap_open_lote') || '').trim();
        var openPanelQuery = String(queryParams.get('cap_open_panel') || '').trim().toLowerCase();
        var aperturaRestaurada = false;

        if (nivelQuery !== '' && Object.prototype.hasOwnProperty.call(configCapDestino, nivelQuery)) {
            carpetaState.nivel = nivelQuery;
        }
        if (moduloQuery !== '') {
            carpetaState.modulo = moduloQuery;
        }
        if (categoriaQuery === 'clase' || categoriaQuery === 'profesor') {
            carpetaState.categoria = categoriaQuery;
        }
        if (leccionQuery !== '') {
            carpetaState.leccion = leccionQuery;
        }

        function temasCapDestino() {
            return Array.prototype.slice.call(document.querySelectorAll('.js-tema-row'));
        }

        function renderNiveles() {
            if (!contNiveles) return;
            contNiveles.innerHTML = '';

            Object.keys(configCapDestino).forEach(function(nivel) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'folder-node' + (String(carpetaState.nivel) === String(nivel) ? ' is-active' : '');
                btn.innerHTML = '<i class="bi bi-folder2-open"></i> Nivel ' + nivel;
                btn.addEventListener('click', function() {
                    carpetaState.nivel = String(nivel);
                    carpetaState.modulo = '';
                    carpetaState.leccion = '';
                    renderNiveles();
                    renderModulos();
                    renderLecciones();
                    renderCategorias();
                });
                contNiveles.appendChild(btn);
            });
        }

        function renderModulos() {
            if (!contModulos) return;
            contModulos.innerHTML = '';

            var modulos = configCapDestino[String(carpetaState.nivel)] || [];
            if (!carpetaState.modulo && modulos.length > 0) {
                carpetaState.modulo = String(modulos[0]);
            }

            modulos.forEach(function(moduloNum) {
                var modulo = String(moduloNum);
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'folder-node' + (carpetaState.modulo === modulo ? ' is-active' : '');
                btn.innerHTML = '<i class="bi bi-folder"></i> Módulo ' + modulo;
                btn.addEventListener('click', function() {
                    carpetaState.modulo = modulo;
                    carpetaState.leccion = '';
                    renderModulos();
                    renderLecciones();
                    renderCategorias();
                });
                contModulos.appendChild(btn);
            });
        }

        function renderLecciones() {
            if (!contLecciones) return;
            contLecciones.innerHTML = '';

            var accesos = document.createElement('div');
            accesos.style.display = 'flex';
            accesos.style.gap = '8px';
            accesos.style.flexWrap = 'wrap';
            accesos.style.marginBottom = '8px';

            var btnLecciones = document.createElement('button');
            btnLecciones.type = 'button';
            btnLecciones.className = 'folder-node' + (carpetaState.categoria === 'clase' ? ' is-active' : '');
            btnLecciones.innerHTML = '<i class="bi bi-book"></i> Lecciones';
            btnLecciones.addEventListener('click', function() {
                carpetaState.categoria = 'clase';
                carpetaState.leccion = '';
                renderCategorias();
                renderLecciones();
                aplicarFiltroCapacitacion(carpetaState);
            });
            accesos.appendChild(btnLecciones);

            var btnEvaluaciones = document.createElement('button');
            btnEvaluaciones.type = 'button';
            btnEvaluaciones.className = 'folder-node' + (carpetaState.categoria === 'evaluaciones' ? ' is-active' : '');
            btnEvaluaciones.innerHTML = '<i class="bi bi-journal-check"></i> Evaluaciones';
            btnEvaluaciones.addEventListener('click', function() {
                carpetaState.categoria = 'evaluaciones';
                renderLecciones();
                var url = rutaEvaluacionesCap
                    + '&nivel=' + encodeURIComponent(String(carpetaState.nivel || ''))
                    + '&modulo=' + encodeURIComponent(String(carpetaState.modulo || ''))
                    + '&leccion=' + encodeURIComponent(String(carpetaState.leccion || 'Sin lección'));
                window.location.href = url;
            });
            accesos.appendChild(btnEvaluaciones);

            contLecciones.appendChild(accesos);

            var mapa = {};
            temasCapDestino().forEach(function(row) {
                if (String(row.getAttribute('data-cap-nivel') || '') !== String(carpetaState.nivel)) return;
                if (String(row.getAttribute('data-cap-modulo') || '') !== String(carpetaState.modulo)) return;

                var leccionRaw = String(row.getAttribute('data-cap-leccion') || 'Sin lección');
                var leccionKey = normalizarLeccion(leccionRaw);
                if (!mapa[leccionKey]) {
                    mapa[leccionKey] = { label: leccionRaw, total: 0 };
                }
                mapa[leccionKey].total += 1;
            });

            var keys = Object.keys(mapa);
            var totalTemas = 0;
            keys.forEach(function(leccionKey) {
                totalTemas += (mapa[leccionKey] && mapa[leccionKey].total) ? mapa[leccionKey].total : 0;
            });

            var resumenTexto = document.createElement('small');
            resumenTexto.style.color = '#637087';
            resumenTexto.style.display = 'block';
            resumenTexto.style.marginTop = '2px';
            if (keys.length === 0) {
                resumenTexto.textContent = 'Sin lecciones registradas en este módulo.';
            } else {
                resumenTexto.textContent = 'Lecciones registradas: ' + totalTemas + ' items';
            }
            contLecciones.appendChild(resumenTexto);

            // Sin subcarpetas por lección: mostrar todo lo del nivel/módulo actual.
            carpetaState.leccion = '';

            aplicarFiltroCapacitacion(carpetaState);
        }

        function renderCategorias() {
            if (!contCategorias) return;
            contCategorias.innerHTML = '';

            ['profesor', 'clase'].forEach(function(cat) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'folder-node' + (carpetaState.categoria === cat ? ' is-active' : '');
                if (cat === 'clase') {
                    btn.innerHTML = '<i class="bi bi-folder2"></i> Material clase';
                } else {
                    btn.innerHTML = '<i class="bi bi-folder2"></i> Material ' + cat;
                }
                btn.addEventListener('click', function() {
                    carpetaState.categoria = cat;
                    renderCategorias();
                    aplicarFiltroCapacitacion(carpetaState);
                });
                contCategorias.appendChild(btn);
            });
        }

        function restaurarFilaAbierta() {
            if (aperturaRestaurada || openLoteQuery === '') {
                return;
            }

            var mainRow = null;
            document.querySelectorAll('.js-tema-row').forEach(function(row) {
                if (mainRow) {
                    return;
                }
                if (String(row.getAttribute('data-lote-id') || '') === openLoteQuery) {
                    mainRow = row;
                }
            });

            if (!mainRow || mainRow.style.display === 'none') {
                return;
            }

            var temaKey = String(mainRow.getAttribute('data-tema-key') || '');
            var bloque = mainRow.closest('.js-cap-block');
            if (bloque) {
                var body = bloque.querySelector('.submodulo-body');
                if (body) {
                    body.style.display = 'block';
                }
                bloque.classList.add('is-selected');
                bloque.classList.add('is-focused');
                var seccion = bloque.closest('.cap-nivel-section');
                if (seccion) {
                    seccion.classList.add('is-focus-mode');
                }
            }

            var filaObjetivo = null;
            if (openPanelQuery === 'editar') {
                document.querySelectorAll('tr[data-tema-key="' + temaKey.replace(/"/g, '\\"') + '"]').forEach(function(row) {
                    if (filaObjetivo) {
                        return;
                    }
                    var rowId = String(row.id || '');
                    if (rowId.indexOf('tema-edit-') === 0) {
                        filaObjetivo = row;
                    }
                });
            } else if (openPanelQuery === 'agregar') {
                document.querySelectorAll('tr[data-tema-key="' + temaKey.replace(/"/g, '\\"') + '"]').forEach(function(row) {
                    if (filaObjetivo) {
                        return;
                    }
                    var rowId = String(row.id || '');
                    if (rowId.indexOf('tema-add-files-') === 0) {
                        filaObjetivo = row;
                    }
                });
            } else {
                filaObjetivo = document.getElementById(temaKey);
            }

            if (filaObjetivo) {
                filaObjetivo.style.display = 'table-row';
                filaObjetivo.scrollIntoView({ behavior: 'smooth', block: 'center' });
            } else {
                mainRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            aperturaRestaurada = true;
        }

        renderNiveles();
        renderModulos();
        renderCategorias();
        renderLecciones();
        restaurarFilaAbierta();
    } else {
        document.querySelectorAll('.js-open-cap-modal').forEach(function(card) {
            var abrirPanel = function() {
                if (!capInlinePanel) {
                    return;
                }
                var target = card.getAttribute('data-target') || 'clase';
                activarCategoriaPrincipal(target);
                capInlinePanel.classList.add('is-open');
                capInlinePanel.setAttribute('aria-hidden', 'false');
                capInlinePanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            };

            card.addEventListener('click', abrirPanel);
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    abrirPanel();
                }
            });
        });
    }

    document.querySelectorAll('.cap-destino-grid .submodulo-head').forEach(function(head) {
        head.addEventListener('click', function() {
            var bloque = head.closest('.submodulo-wrap');
            if (!bloque || bloque.classList.contains('is-hidden')) {
                return;
            }

            var body = bloque.querySelector('.submodulo-body');
            if (!body) {
                return;
            }

            if (esCapacitacionDestinoVista) {
                var seccion = bloque.closest('.cap-nivel-section');
                if (!seccion) {
                    return;
                }

                var yaAbierto = body.style.display !== 'none' && body.style.display !== '';

                seccion.querySelectorAll('.js-cap-block').forEach(function(item) {
                    var itemBody = item.querySelector('.submodulo-body');
                    if (itemBody) {
                        itemBody.style.display = 'none';
                    }
                    item.classList.remove('is-selected');
                    item.classList.remove('is-focused');
                });

                if (yaAbierto) {
                    seccion.classList.remove('is-focus-mode');
                    return;
                }

                seccion.classList.add('is-focus-mode');
                body.style.display = 'block';
                bloque.classList.add('is-selected');
                bloque.classList.add('is-focused');
                return;
            }

            var abrir = body.style.display === 'none' || body.style.display === '';
            document.querySelectorAll('.js-cap-block').forEach(function(item) {
                var itemBody = item.querySelector('.submodulo-body');
                if (itemBody) {
                    itemBody.style.display = 'none';
                }
                item.classList.remove('is-selected');
            });

            if (abrir) {
                body.style.display = 'block';
                bloque.classList.add('is-selected');
            }
        });
    });

    var botonesEditarTema = document.querySelectorAll('.js-toggle-editar-tema');
    botonesEditarTema.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (!row) {
                return;
            }
            row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
        });
    });

    document.querySelectorAll('.js-toggle-profesor-form').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var form = document.getElementById(targetId);
            if (!form) {
                return;
            }
            form.classList.toggle('is-open');
        });
    });

    var botonesAgregarArchivos = document.querySelectorAll('.js-toggle-agregar-archivos');
    botonesAgregarArchivos.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var row = document.getElementById(targetId);
            if (!row) {
                return;
            }
            row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
        });
    });

    botones.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var lote = this.getAttribute('data-lote') || '';
            abrirModalVistas(lote);
        });
    });

    function abrirModalVistas(lote) {
        document.getElementById('modal-content-loading').style.display = 'block';
        document.getElementById('modal-content-vistas').style.display = 'none';
        document.getElementById('modal-content-error').style.display = 'none';
        modalElement.style.display = 'block';

        fetch(<?= json_encode($rutaDetalleVistas, JSON_UNESCAPED_SLASHES) ?> + '&lote=' + encodeURIComponent(lote))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                document.getElementById('modal-content-loading').style.display = 'none';

                if (data.success) {
                    document.getElementById('modal-tema-nombre').textContent = data.tema || 'Tema de material';
                    document.getElementById('modal-total-personas').textContent = data.total_personas;

                    var tbody = document.getElementById('modal-vistas-list');
                    tbody.innerHTML = '';

                    if (data.vistas && data.vistas.length > 0) {
                        data.vistas.forEach(function(vista) {
                            var nombre = (vista.Nombre ? vista.Nombre : '') + ' ' + (vista.Apellido ? vista.Apellido : '');
                            nombre = nombre.trim() || 'Sin nombre';
                            var ministerio = vista.Nombre_Ministerio || 'Sin ministerio';
                            var totalVistas = vista.Total_Vistas || 0;
                            var ultimaVista = vista.Fecha_Ultima_Vista ? new Date(vista.Fecha_Ultima_Vista).toLocaleString('es-ES') : '-';

                            var tr = document.createElement('tr');
                            tr.innerHTML = '<td>' + escapeHtml(nombre) + '</td>' +
                                '<td>' + escapeHtml(ministerio) + '</td>' +
                                '<td>' + String(totalVistas) + '</td>' +
                                '<td>' + escapeHtml(ultimaVista) + '</td>';
                            tbody.appendChild(tr);
                        });
                    } else {
                        var trVacio = document.createElement('tr');
                        trVacio.innerHTML = '<td colspan="4" style="text-align:center; color:#999;">Aún no hay registro de vistas</td>';
                        tbody.appendChild(trVacio);
                    }

                    document.getElementById('modal-content-vistas').style.display = 'block';
                } else {
                    document.getElementById('modal-content-error').textContent = data.message || 'Error al cargar los datos';
                    document.getElementById('modal-content-error').style.display = 'block';
                }
            })
            .catch(function() {
                document.getElementById('modal-content-loading').style.display = 'none';
                document.getElementById('modal-content-error').textContent = 'Error al cargar los datos';
                document.getElementById('modal-content-error').style.display = 'block';
            });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    modalElement.addEventListener('click', function(e) {
        if (e.target === modalElement) {
            modalElement.style.display = 'none';
        }
    });

    if (galeriaModal) {
        galeriaModal.addEventListener('click', function(e) {
            if (e.target === galeriaModal) {
                cerrarGaleria();
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (!galeriaModal || !galeriaModal.classList.contains('is-open')) {
            return;
        }

        if (e.key === 'Escape') {
            cerrarGaleria();
            return;
        }

        if (e.key === 'ArrowLeft') {
            e.preventDefault();
            moverGaleria(-1);
            return;
        }

        if (e.key === 'ArrowRight' || e.key === ' ') {
            e.preventDefault();
            moverGaleria(1);
        }
    });

    // === Eliminar clase ===
    var formEliminarTema = document.getElementById('form-eliminar-tema');
    var formEliminarTemaLote = document.getElementById('form-eliminar-tema-lote');
    var formEliminarTemaContextoNivel = document.getElementById('form-eliminar-tema-contexto-nivel');
    var formEliminarTemaContextoModulo = document.getElementById('form-eliminar-tema-contexto-modulo');
    var formEliminarTemaContextoCategoria = document.getElementById('form-eliminar-tema-contexto-categoria');
    var formEliminarTemaContextoLeccion = document.getElementById('form-eliminar-tema-contexto-leccion');

    document.querySelectorAll('.js-eliminar-tema').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var lote = this.getAttribute('data-lote');
            var titulo = this.getAttribute('data-titulo');
            if (!confirm('¿Eliminar la clase "' + titulo + '" y todos sus archivos?\n\nEsta acción no se puede deshacer.')) {
                return;
            }
            formEliminarTemaLote.value = lote;
            if (formEliminarTemaContextoNivel) {
                formEliminarTemaContextoNivel.value = String(this.getAttribute('data-contexto-nivel') || '0');
            }
            if (formEliminarTemaContextoModulo) {
                formEliminarTemaContextoModulo.value = String(this.getAttribute('data-contexto-modulo') || '0');
            }
            if (formEliminarTemaContextoCategoria) {
                formEliminarTemaContextoCategoria.value = String(this.getAttribute('data-contexto-categoria') || '');
            }
            if (formEliminarTemaContextoLeccion) {
                formEliminarTemaContextoLeccion.value = String(this.getAttribute('data-contexto-leccion') || '');
            }
            formEliminarTema.submit();
        });
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>