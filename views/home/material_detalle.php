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
$color = (string)($modulo['color'] ?? '#1e4a89');
$icono = (string)($modulo['icono'] ?? 'bi bi-journal-bookmark-fill');
$clave = (string)($modulo['clave'] ?? '');
$tieneSubmodulos = !empty($tiene_submodulos);
$esCapacitacionDestino = $clave === 'capacitacion_destino';
$esUniversidadVida = $clave === 'universidad_vida';
$usaTarjetasTipoMaterial = $esCapacitacionDestino || $esUniversidadVida;
$configCapacitacionDestino = (array)($config_capacitacion_destino ?? []);
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
        background: #1f5ea8;
        color: #fff;
        border-color: #1f5ea8;
        box-shadow: 0 1px 3px rgba(20, 58, 101, 0.28);
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
        color: #1f5ea8;
        font-weight: 700;
        font-size: 12px;
        cursor: pointer;
        padding: 0;
        text-decoration: underline;
        white-space: nowrap;
    }

    .btn-link-compact:hover {
        color: #143a65;
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
        border-color: #1f5ea8;
        background: linear-gradient(180deg, #1f5ea8 0%, #1a518f 100%);
        color: #fff;
        box-shadow: 0 6px 18px rgba(23, 62, 110, 0.25);
    }

    .cap-main-tab.is-active small {
        color: #dbe8ff;
    }

    .cap-destino-grid .submodulo-title {
        font-size: 15px;
    }

    .cap-entry-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
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
        border-color: #1f5ea8;
        background: linear-gradient(180deg, #1f5ea8 0%, #1a518f 100%);
        box-shadow: 0 8px 20px rgba(23, 62, 110, 0.28);
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
        border-color: #1f5ea8;
        box-shadow: 0 6px 14px rgba(23, 62, 110, 0.16);
    }

    .cap-destino-grid .submodulo-wrap.is-selected .submodulo-head {
        background: linear-gradient(180deg, #eef5ff 0%, #f8fbff 100%);
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
        color: #274f81;
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
        background: linear-gradient(180deg, #0f1b30 0%, #132645 100%);
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
<div class="cap-entry-grid">
    <article class="cap-entry-card js-open-cap-modal" data-target="clase" role="button" tabindex="0" aria-label="Abrir Material clase">
        <h4>Material clase</h4>
        <p>Haz clic para ver en esta misma pantalla el contenido de clase.</p>
    </article>
    <article class="cap-entry-card js-open-cap-modal" data-target="profesor" role="button" tabindex="0" aria-label="Abrir Material profesor">
        <h4>Material profesor</h4>
        <p>Haz clic para ver en esta misma pantalla el contenido para profesor.</p>
    </article>
</div>

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
                $colecciones = [
                    ['categoria' => 'clase', 'titulo' => 'Material clase', 'temas' => $temasClase],
                    ['categoria' => 'profesor', 'titulo' => 'Material profesor', 'temas' => $temasProfesor],
                ];

                foreach ($colecciones as $coleccionTmp) {
                    foreach ($configCapacitacionDestino as $nivelTmp => $modulosTmp) {
                        foreach ((array)$modulosTmp as $moduloTmp) {
                            $temasBloqueTmp = array_values(array_filter((array)$coleccionTmp['temas'], static function($temaTmp) use ($nivelTmp, $moduloTmp) {
                                return (int)($temaTmp['nivel'] ?? 0) === (int)$nivelTmp
                                    && (int)($temaTmp['modulo_numero'] ?? 0) === (int)$moduloTmp;
                            }));

                            $bloques[] = [
                                'titulo' => 'Nivel ' . (int)$nivelTmp . ' / Módulo ' . (int)$moduloTmp,
                                'temas' => $temasBloqueTmp,
                                'nivel' => (int)$nivelTmp,
                                'modulo_numero' => (int)$moduloTmp,
                                'categoria' => (string)$coleccionTmp['categoria'],
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

        <?php if ($usaTarjetasTipoMaterial): ?><div class="cap-destino-grid"><?php endif; ?>

        <?php foreach ($bloques as $bloqueIndex => $bloque): ?>
            <?php
                $tituloBloque = (string)($bloque['titulo'] ?? 'Temas');
                $claseCssBloque = 'submodulo-wrap';
                $panelIdBloque = 'submodulo-panel-' . $bloqueIndex;

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
                $totalTemasBloque = count((array)($bloque['temas'] ?? []));

                if ($tieneSubmodulos && !$usaTarjetasTipoMaterial && $panelIdBloque === 'submodulo-panel-profesor') {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <?php
                $categoriaBloque = strtolower(trim((string)($bloque['categoria'] ?? 'general')));
                if ($categoriaBloque === '') {
                    $categoriaBloque = 'general';
                }
                if ($usaTarjetasTipoMaterial) {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <div
                id="<?= htmlspecialchars($panelIdBloque, ENT_QUOTES, 'UTF-8') ?>"
                class="<?= htmlspecialchars($claseCssBloque, ENT_QUOTES, 'UTF-8') ?> js-cap-block"
                data-cap-categoria="<?= htmlspecialchars($categoriaBloque, ENT_QUOTES, 'UTF-8') ?>"
                data-cap-titulo="<?= htmlspecialchars($tituloBloque, ENT_QUOTES, 'UTF-8') ?>"
                data-cap-total="<?= (int)$totalTemasBloque ?>"
                role="tabpanel">
                <div class="submodulo-head">
                    <h4 class="submodulo-title"><?= htmlspecialchars($tituloBloque) ?></h4>
                    <span class="submodulo-meta"><?= (int)$totalTemasBloque ?> tema(s)</span>
                </div>

                <div class="submodulo-body">
                    <div class="table-container" style="margin-bottom:0;">
                        <table class="data-table">
                    <thead>
                        <tr>
                            <th class="col-titulo">Título</th>
                            <th class="col-descripcion">Descripción</th>
                            <th style="width:120px;">Archivos</th>
                            <th style="width:150px;">Vistas</th>
                            <th style="width:190px;">Creado</th>
                            <th class="col-acciones">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bloque['temas'])): ?>
                            <?php foreach ((array)$bloque['temas'] as $index => $tema): ?>
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
                                ?>
                                <tr>
                                    <td class="col-titulo" title="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"><strong><?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material')) ?></strong></td>
                                    <?php $descripcionTema = (string)($tema['descripcion'] ?? 'Sin descripción'); ?>
                                    <td class="col-descripcion" title="<?= htmlspecialchars($descripcionTema, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="descripcion-cell">
                                            <span class="descripcion-preview"><?= htmlspecialchars($descripcionTema) ?></span>
                                        </span>
                                    </td>
                                    <td><?= (int)($tema['total_archivos'] ?? 0) ?></td>
                                    <td><?= (int)($tema['personas_vieron'] ?? 0) ?></td>
                                    <td>
                                        <?php
                                        $ts = (int)($tema['creado_ts'] ?? 0);
                                        echo $ts > 0 ? date('Y-m-d H:i', $ts) : '—';
                                        ?>
                                    </td>
                                    <td class="col-acciones" style="display:flex; gap:8px; flex-wrap:wrap;">
                                        <button type="button" class="btn btn-sm btn-secondary js-toggle-tema" data-target="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>">Ver archivos</button>
                                        <?php if (!empty($imagenesTema)): ?>
                                            <button type="button" class="btn btn-sm btn-warning js-abrir-galeria-tema"
                                                data-tema="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-images='<?= htmlspecialchars($imagenesTemaJson, ENT_QUOTES, 'UTF-8') ?>'>Presentar</button>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-sm btn-info js-ver-vistas" data-lote="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">Ver quién vio</button>
                                        <?php if ($puedeGestionar): ?>
                                            <button type="button" class="btn btn-sm btn-success js-toggle-agregar-archivos" data-target="<?= htmlspecialchars($temaAgregarArchivosId, ENT_QUOTES, 'UTF-8') ?>">Agregar archivos</button>
                                            <button type="button" class="btn btn-sm btn-primary js-toggle-editar-tema" data-target="<?= htmlspecialchars($temaEditId, ENT_QUOTES, 'UTF-8') ?>">Editar</button>
                                            <button type="button" class="btn btn-sm btn-danger js-eliminar-tema"
                                                data-lote="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                data-titulo="<?= htmlspecialchars((string)($tema['titulo'] ?? 'este tema'), ENT_QUOTES, 'UTF-8') ?>">Eliminar clase</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <?php if ($puedeGestionar): ?>
                                    <tr id="<?= htmlspecialchars($temaEditId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#fff9f0;">
                                        <td colspan="6">
                                            <form method="POST" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:10px; align-items:end;">
                                                <input type="hidden" name="accion" value="editar_tema">
                                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                <input type="hidden" name="lote_id" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

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
                                                <?php else: ?>
                                                    <input type="hidden" name="nivel" value="0">
                                                    <input type="hidden" name="modulo_numero" value="0">
                                                <?php endif; ?>

                                                <div>
                                                    <button type="submit" class="btn btn-sm btn-primary">Guardar cambios</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <?php if ($puedeGestionar): ?>
                                    <tr id="<?= htmlspecialchars($temaAgregarArchivosId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#eefaf4;">
                                        <td colspan="6">
                                            <form method="POST" enctype="multipart/form-data" action="<?= PUBLIC_URL ?>?url=<?= htmlspecialchars($ruta) ?>" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr)); gap:10px; align-items:end;">
                                                <input type="hidden" name="accion" value="agregar_archivos_tema">
                                                <input type="hidden" name="modulo" value="<?= htmlspecialchars($clave) ?>">
                                                <input type="hidden" name="lote_id" value="<?= htmlspecialchars((string)($tema['lote_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

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

                                <tr id="<?= htmlspecialchars($temaId, ENT_QUOTES, 'UTF-8') ?>" style="display:none; background:#f9fbff;">
                                    <td colspan="6">
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
                                                        <div style="height:140px; background:#1a1a2e; display:flex; align-items:center; justify-content:center; overflow:hidden; position:relative;">
                                                            <?php if ($esImagen): ?>
                                                                <img src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>"
                                                                     alt="<?= htmlspecialchars($nombreArchivo, ENT_QUOTES, 'UTF-8') ?>"
                                                                     style="width:100%; height:100%; object-fit:cover; display:block;">
                                                            <?php elseif ($esVideo): ?>
                                                                <video style="width:100%; height:100%; object-fit:cover; display:block;" muted preload="metadata">
                                                                    <source src="<?= htmlspecialchars($urlDirectaArchivo, ENT_QUOTES, 'UTF-8') ?>#t=0.5">
                                                                </video>
                                                                <div style="position:absolute; bottom:6px; right:8px; background:rgba(0,0,0,0.55); border-radius:4px; padding:2px 6px;">
                                                                    <i class="bi bi-play-fill" style="color:#fff; font-size:14px;"></i>
                                                                </div>
                                                            <?php elseif ($esPdf): ?>
                                                                <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                                                                    <i class="bi bi-file-earmark-pdf" style="font-size:48px; color:#e44d26;"></i>
                                                                    <span style="color:#aec1d8; font-size:11px; text-transform:uppercase; letter-spacing:1px;">PDF</span>
                                                                </div>
                                                            <?php else: ?>
                                                                <div style="display:flex; flex-direction:column; align-items:center; gap:6px;">
                                                                    <i class="bi <?= htmlspecialchars($iconoCls) ?>" style="font-size:48px; color:#7fa8d8;"></i>
                                                                    <span style="color:#aec1d8; font-size:11px; text-transform:uppercase; letter-spacing:1px;"><?= htmlspecialchars(strtoupper($extArchivo)) ?></span>
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
                                <td colspan="6" style="text-align:center; color:#6b7d95;">No hay temas cargados en este submódulo.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if ($usaTarjetasTipoMaterial): ?></div>
            <div id="cap-detail-view" class="cap-detail-view is-hidden" aria-live="polite">
                <div class="cap-detail-header">
                    <h4 id="cap-detail-title">Selecciona una categoría de material</h4>
                    <small id="cap-detail-meta"></small>
                </div>
                <div id="cap-detail-body" class="cap-detail-body"></div>
            </div>
        <?php endif; ?>
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

    var tabsMainCapDestino = document.querySelectorAll('.js-cap-main-tab');
    var capEntryCards = document.querySelectorAll('.js-open-cap-modal');

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

        tabsMainCapDestino.forEach(function(tabBtn) {
            var target = (tabBtn.getAttribute('data-target') || '').toLowerCase();
            var activo = target === categoria;
            tabBtn.classList.toggle('is-active', activo);
            tabBtn.setAttribute('aria-selected', activo ? 'true' : 'false');
        });

        document.querySelectorAll('.js-cap-block').forEach(function(panel) {
            var categoriaPanel = (panel.getAttribute('data-cap-categoria') || '').toLowerCase();
            var mostrar = categoriaPanel === categoria;
            panel.classList.toggle('is-hidden', !mostrar);
            panel.classList.remove('is-selected');
        });

        if (capDetailView && capDetailBody) {
            restaurarDetalleCapacitacion();
            capDetailView.classList.add('is-hidden');
            capDetailBody.innerHTML = '';
            if (capDetailTitle) {
                capDetailTitle.textContent = 'Selecciona un nivel y módulo';
            }
            if (capDetailMeta) {
                capDetailMeta.textContent = '';
            }
        }
    }

    var capInlinePanel = document.getElementById('cap-inline-panel');

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
            // Si solo hay un bloque visible (ej. UV sin niveles/módulos), lo seleccionamos automáticamente
            var bloquesVisibles = Array.from(document.querySelectorAll('.js-cap-block')).filter(function(b) {
                return !b.classList.contains('is-hidden');
            });
            if (bloquesVisibles.length === 1) {
                mostrarDetalleCapacitacion(bloquesVisibles[0]);
            }
        };

        card.addEventListener('click', abrirPanel);
        card.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                abrirPanel();
            }
        });
    });

    var capDetailView = document.getElementById('cap-detail-view');
    var capDetailTitle = document.getElementById('cap-detail-title');
    var capDetailMeta = document.getElementById('cap-detail-meta');
    var capDetailBody = document.getElementById('cap-detail-body');
    var capDetalleActualBloque = null;
    var capDetalleActualBody = null;

    function restaurarDetalleCapacitacion() {
        if (!capDetalleActualBloque || !capDetalleActualBody) {
            return;
        }
        capDetalleActualBloque.appendChild(capDetalleActualBody);
        capDetalleActualBody.style.display = 'none';
        capDetalleActualBloque = null;
        capDetalleActualBody = null;
    }

    function mostrarDetalleCapacitacion(bloque) {
        if (!bloque || !capDetailView || !capDetailBody) {
            return;
        }

        document.querySelectorAll('.js-cap-block').forEach(function(item) {
            item.classList.remove('is-selected');
        });
        bloque.classList.add('is-selected');

        var body = bloque.querySelector('.submodulo-body');
        if (!body) {
            return;
        }

        restaurarDetalleCapacitacion();
        capDetailBody.appendChild(body);
        body.style.display = 'block';
        capDetalleActualBloque = bloque;
        capDetalleActualBody = body;

        if (capDetailTitle) {
            capDetailTitle.textContent = bloque.getAttribute('data-cap-titulo') || 'Detalle';
        }
        if (capDetailMeta) {
            capDetailMeta.textContent = (bloque.getAttribute('data-cap-total') || '0') + ' tema(s)';
        }

        capDetailView.classList.remove('is-hidden');
    }

    document.querySelectorAll('.cap-destino-grid .submodulo-head').forEach(function(head) {
        head.addEventListener('click', function() {
            var bloque = head.closest('.submodulo-wrap');
            if (!bloque || bloque.classList.contains('is-hidden')) {
                return;
            }
            mostrarDetalleCapacitacion(bloque);
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

    document.querySelectorAll('.js-eliminar-tema').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var lote = this.getAttribute('data-lote');
            var titulo = this.getAttribute('data-titulo');
            if (!confirm('¿Eliminar la clase "' + titulo + '" y todos sus archivos?\n\nEsta acción no se puede deshacer.')) {
                return;
            }
            formEliminarTemaLote.value = lote;
            formEliminarTema.submit();
        });
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>