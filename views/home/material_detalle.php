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
        <div class="form-group" style="margin-bottom: 12px;">
            <label for="material_pdf">Archivo(s)</label>
            <input type="file" id="material_pdf" name="material_pdf[]" class="form-control" multiple required>
            <small style="display:block; margin-top:6px; color:#666;">Máximo 20MB por archivo. Puedes subir varios en un solo tema y se permiten varios formatos (pdf, docx, xlsx, pptx, mp4, etc.).</small>
        </div>
        <button type="submit" class="btn btn-primary">Subir archivos</button>
    </form>
</div>
<?php endif; ?>

<div class="card" style="padding:14px;">
    <h3 style="margin-top:0;">Módulos de material</h3>

    <?php if ($tieneSubmodulos): ?>
        <div class="submodulo-tabs" role="tablist" aria-label="Submódulos de material">
            <button type="button" class="submodulo-tab is-active js-submodulo-tab" data-target="submodulo-panel-clase" role="tab" aria-selected="true">Material clase</button>
            <button type="button" class="submodulo-tab js-submodulo-tab" data-target="submodulo-panel-profesor" role="tab" aria-selected="false">Material profesor</button>
        </div>
    <?php endif; ?>

    <?php if (!empty($temas)): ?>
        <?php
            $bloques = $tieneSubmodulos
                ? [
                    ['titulo' => 'Material clase', 'temas' => $temasClase],
                    ['titulo' => 'Material profesor', 'temas' => $temasProfesor],
                ]
                : [
                    ['titulo' => 'Temas', 'temas' => $temas],
                ];
        ?>

        <?php foreach ($bloques as $bloqueIndex => $bloque): ?>
            <?php
                $tituloBloque = (string)($bloque['titulo'] ?? 'Temas');
                $claseCssBloque = 'submodulo-wrap';
                $panelIdBloque = '';
                if (stripos($tituloBloque, 'profesor') !== false) {
                    $claseCssBloque .= ' submodulo-profesor';
                    $panelIdBloque = 'submodulo-panel-profesor';
                } elseif (stripos($tituloBloque, 'clase') !== false) {
                    $claseCssBloque .= ' submodulo-clase';
                    $panelIdBloque = 'submodulo-panel-clase';
                }
                $totalTemasBloque = count((array)($bloque['temas'] ?? []));

                if ($tieneSubmodulos && $panelIdBloque === 'submodulo-panel-profesor') {
                    $claseCssBloque .= ' is-hidden';
                }
            ?>

            <div id="<?= htmlspecialchars($panelIdBloque, ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($claseCssBloque, ENT_QUOTES, 'UTF-8') ?>" role="tabpanel">
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
                                ?>
                                <tr>
                                    <td class="col-titulo" title="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"><strong><?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material')) ?></strong></td>
                                    <?php $descripcionTema = (string)($tema['descripcion'] ?? 'Sin descripción'); ?>
                                    <td class="col-descripcion" title="<?= htmlspecialchars($descripcionTema, ENT_QUOTES, 'UTF-8') ?>">
                                        <span class="descripcion-cell">
                                            <span class="descripcion-preview"><?= htmlspecialchars($descripcionTema) ?></span>
                                            <button
                                                type="button"
                                                class="btn-link-compact js-leer-descripcion"
                                                data-titulo="<?= htmlspecialchars((string)($tema['titulo'] ?? 'Tema de material'), ENT_QUOTES, 'UTF-8') ?>"
                                                data-descripcion="<?= htmlspecialchars($descripcionTema, ENT_QUOTES, 'UTF-8') ?>"
                                            >Leer más</button>
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
                                                <?php foreach ($archivosTema as $archivo): ?>
                                                    <?php
                                                    $nombreArchivo = (string)($archivo['nombre'] ?? '');
                                                    $extArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
                                                    $urlDirectaArchivo = rtrim(PUBLIC_URL, '/') . '/uploads/material_hub/' . rawurlencode($clave) . '/' . rawurlencode($nombreArchivo);
                                                    $urlVerArchivo = htmlspecialchars((string)($archivo['url'] ?? '#'));
                                                    $esImagen = in_array($extArchivo, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                                    $esVideo  = in_array($extArchivo, ['mp4', 'webm', 'mov']);
                                                    $esPdf    = $extArchivo === 'pdf';
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
                                                                <a href="<?= $urlVerArchivo ?>" target="_blank" class="btn btn-sm btn-success" style="font-size:11px; padding:3px 8px;">Abrir</a>
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
    <?php else: ?>
        <p style="margin:0; color:#666;">No hay temas cargados en este módulo.</p>
    <?php endif; ?>
</div>

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

<div id="modal-descripcion-material"    <div style="background:white; margin:40px auto; padding:24px; border-radius:8px; max-width:720px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; gap:10px;">
            <h3 id="modal-descripcion-titulo" style="margin:0;">Descripción</h3>
            <button type="button" class="btn btn-sm" id="modal-descripcion-cerrar" style="padding:5px 10px;">x</button>
        </div>
        <div id="modal-descripcion-texto" style="white-space:pre-wrap; color:#2c3e55; line-height:1.45;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalElement = document.getElementById('modal-vistas-material');
    var modalDescripcion = document.getElementById('modal-descripcion-material');
    var modalDescripcionTitulo = document.getElementById('modal-descripcion-titulo');
    var modalDescripcionTexto = document.getElementById('modal-descripcion-texto');
    var modalDescripcionCerrar = document.getElementById('modal-descripcion-cerrar');
    var botones = document.querySelectorAll('.js-ver-vistas');
    var botonesTema = document.querySelectorAll('.js-toggle-tema');
    var botonesLeerDescripcion = document.querySelectorAll('.js-leer-descripcion');

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

    botonesLeerDescripcion.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var titulo = this.getAttribute('data-titulo') || 'Descripción';
            var descripcion = this.getAttribute('data-descripcion') || 'Sin descripción';
            modalDescripcionTitulo.textContent = titulo;
            modalDescripcionTexto.textContent = descripcion;
            modalDescripcion.style.display = 'block';
        });
    });

    if (modalDescripcionCerrar) {
        modalDescripcionCerrar.addEventListener('click', function() {
            modalDescripcion.style.display = 'none';
        });
    }

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

    if (modalDescripcion) {
        modalDescripcion.addEventListener('click', function(e) {
            if (e.target === modalDescripcion) {
                modalDescripcion.style.display = 'none';
            }
        });
    }

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