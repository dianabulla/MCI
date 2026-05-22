<?php include VIEWS . '/layout/header.php'; ?>

<?php
$totalModulos = (int)($total_modulos ?? 0);
$totalArchivos = (int)($total_archivos_registrados ?? 0);
$totalOk = (int)($total_archivos_ok ?? 0);
$totalFaltan = (int)($total_archivos_faltantes ?? 0);
$pdfsEnCarpeta = (int)($pdfs_en_carpeta ?? 0);
$puedeSubir = !empty($puede_subir);
$puedeEditar = !empty($puede_editar);
$puedeEliminar = !empty($puede_eliminar);
$soloVerMaterial = !empty($solo_ver_material);
?>

<div class="teen-material-page">
    <div class="page-header teen-material-header">
        <div>
            <h2 style="margin:0;">Material Teens</h2>
            <p class="teen-material-subtitle">Gestiona y comparte PDF con los equipos.</p>
        </div>
        <div class="page-actions personas-mobile-stack teen-nav-pills">
            <?php if (!$soloVerMaterial): ?>
            <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-nav-pill">Registro</a>
            <a href="<?= PUBLIC_URL ?>index.php?url=teen/codigos" class="btn btn-nav-pill">Códigos</a>
            <?php endif; ?>
            <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-nav-pill active">Material</a>
            <?php if (!$soloVerMaterial): ?>
            <a href="<?= PUBLIC_URL ?>index.php?url=entrega_obsequio" class="btn btn-nav-pill">Obsequios</a>
            <?php endif; ?>
            <?php if ($soloVerMaterial): ?>
            <a href="<?= PUBLIC_URL ?>?url=home/material" class="btn btn-nav-pill">Volver a Material</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="teen-stats-row">
        <div class="teen-stat-card">
            <span class="teen-stat-label">Módulos</span>
            <strong class="teen-stat-value"><?= $totalModulos ?></strong>
        </div>
        <div class="teen-stat-card teen-stat-card--ok">
            <span class="teen-stat-label">PDF disponibles</span>
            <strong class="teen-stat-value"><?= $totalOk ?><span class="teen-stat-muted">/<?= $totalArchivos ?></span></strong>
        </div>
        <?php if ($totalFaltan > 0): ?>
        <div class="teen-stat-card teen-stat-card--warn">
            <span class="teen-stat-label">Pendientes</span>
            <strong class="teen-stat-value"><?= $totalFaltan ?></strong>
        </div>
        <?php endif; ?>
        <div class="teen-stat-card">
            <span class="teen-stat-label">En carpeta servidor</span>
            <strong class="teen-stat-value"><?= $pdfsEnCarpeta ?></strong>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= ($tipo ?? '') === 'success' ? 'success' : 'danger' ?> teen-flash" role="alert">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($puedeSubir): ?>
    <section class="teen-upload-section" id="formSubidaTeen">
        <div class="teen-upload-card">
            <div class="teen-upload-card__head">
                <div>
                    <h3>Subir material</h3>
                    <p class="teen-upload-hint">Un título por lote · varios PDF a la vez (máx. 20 MB c/u)</p>
                </div>
            </div>

            <form action="<?= PUBLIC_URL ?>index.php?url=teen" method="POST" enctype="multipart/form-data" class="teen-upload-form">
                <div class="teen-upload-form__fields">
                    <div class="form-group">
                        <label for="titulo">Título del módulo</label>
                        <input
                            type="text"
                            id="titulo"
                            name="titulo"
                            class="form-control"
                            required
                            maxlength="255"
                            placeholder="Ej: Domingo 03 de mayo — Equipo 3"
                        >
                    </div>

                    <div class="form-group">
                        <label for="descripcion">Descripción <span class="teen-optional">(opcional)</span></label>
                        <textarea
                            id="descripcion"
                            name="descripcion"
                            class="form-control"
                            rows="1"
                            placeholder="Notas para el equipo"
                        ></textarea>
                    </div>

                    <div class="form-group teen-upload-form__files">
                        <label for="archivo_pdf">Archivos PDF</label>
                        <input
                            type="file"
                            id="archivo_pdf"
                            name="archivo_pdf[]"
                            class="form-control"
                            accept="application/pdf"
                            multiple
                            required
                        >
                    </div>
                </div>

                <div class="teen-upload-form__action">
                    <button type="submit" class="btn btn-primary teen-upload-btn">
                        <i class="bi bi-cloud-upload"></i> Publicar material
                    </button>
                </div>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <section class="teen-published-section">
            <div class="teen-panel-head">
                <h3>Materiales publicados</h3>
                <?php if ($puedeEditar && $totalFaltan > 0): ?>
                    <a
                        href="<?= PUBLIC_URL ?>index.php?url=teen/recuperar-archivos"
                        class="btn btn-sm btn-outline-secondary"
                        title="Volver a buscar PDF en el servidor"
                    >
                        Sincronizar archivos
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!empty($materiales)): ?>
                <div class="teen-files-panel">
                    <ul class="teen-files-stack">
                        <?php foreach ($materiales as $material): ?>
                            <?php
                                $archivos = $material['archivos'] ?? [];
                                $idMaterial = (int)($material['id'] ?? 0);
                                $okMod = (int)($material['archivos_ok'] ?? 0);
                                $totMod = (int)($material['archivos_total'] ?? count($archivos));
                                $completo = $totMod > 0 && $okMod >= $totMod;
                                $tituloMod = (string)($material['titulo'] ?? 'Sin título');
                                $fechaMod = (string)($material['created_at'] ?? '');
                            ?>
                            <li class="teen-files-stack__group">
                                <div class="teen-files-stack__group-head<?= $completo ? '' : ' teen-files-stack__group-head--warn' ?>">
                                    <div class="teen-files-stack__group-title">
                                        <strong><?= htmlspecialchars($tituloMod) ?></strong>
                                        <?php if (!empty($material['descripcion'])): ?>
                                            <span class="teen-files-stack__group-desc"><?= htmlspecialchars((string)$material['descripcion']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="teen-files-stack__group-meta">
                                        <span class="teen-badge <?= $completo ? 'teen-badge--ok' : 'teen-badge--warn' ?>"><?= $okMod ?>/<?= $totMod ?></span>
                                        <span class="teen-meta-item" title="Visualizaciones"><i class="bi bi-eye"></i> <?= (int)($material['vistas_totales'] ?? 0) ?></span>
                                        <?php if ($fechaMod !== ''): ?><span class="teen-files-stack__date"><?= htmlspecialchars($fechaMod) ?></span><?php endif; ?>
                                    </div>
                                    <div class="teen-files-stack__group-actions">
                                        <?php if ($puedeEditar): ?><a href="<?= PUBLIC_URL ?>index.php?url=teen/editar&id=<?= $idMaterial ?>" class="btn btn-xs btn-warning">Editar</a><?php endif; ?>
                                        <?php if ($puedeEliminar): ?><a href="<?= PUBLIC_URL ?>index.php?url=teen/eliminar&id=<?= $idMaterial ?>" class="btn btn-xs btn-danger" onclick="return confirm('¿Eliminar este módulo y sus archivos?');">Eliminar</a><?php endif; ?>
                                    </div>
                                </div>
                                <?php if (empty($archivos)): ?>
                                    <div class="teen-files-stack__row teen-files-stack__row--empty">Sin archivos en el registro.</div>
                                <?php else: ?>
                                    <?php foreach ($archivos as $archivo): ?>
                                        <?php
                                            $nombrePdf = (string)($archivo['nombre'] ?? '');
                                            $label = basename($nombrePdf, '.pdf');
                                            if (strlen($label) > 56) { $label = substr($label, 0, 53) . '…'; }
                                            $urlVer = (string)($archivo['url'] ?? '');
                                            $existe = !empty($archivo['existe']);
                                        ?>
                                        <div class="teen-files-stack__row<?= $existe ? '' : ' teen-files-stack__row--missing' ?>">
                                            <span class="teen-files-stack__icon"><i class="bi bi-file-earmark-pdf"></i></span>
                                            <span class="teen-files-stack__name" title="<?= htmlspecialchars($nombrePdf) ?>"><?= htmlspecialchars($label) ?></span>
                                            <span class="teen-files-stack__size">
                                                <?php if ($existe): ?>
                                                    <?= number_format((float)($archivo['peso_kb'] ?? 0), 0) ?> KB
                                                <?php else: ?>
                                                    <span class="teen-file-tag">Pendiente</span>
                                                <?php endif; ?>
                                            </span>
                                            <span class="teen-files-stack__actions">
                                                <?php if ($existe): ?>
                                                    <button type="button" class="btn btn-xs btn-primary" data-nombre="<?= htmlspecialchars($nombrePdf, ENT_QUOTES, 'UTF-8') ?>" data-url-ver="<?= htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8') ?>" data-url-embed="<?= htmlspecialchars((string)($archivo['url_embed'] ?? $urlVer), ENT_QUOTES, 'UTF-8') ?>" onclick="abrirPreviewPdfTeen(this)">Ver</button>
                                                    <button type="button" class="btn btn-xs btn-light" onclick="verDetalleVistas('<?= htmlspecialchars($nombrePdf, ENT_QUOTES, 'UTF-8') ?>')">Vistas</button>
                                                    <a href="<?= htmlspecialchars($urlVer, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-xs btn-outline-secondary">PDF</a>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="teen-empty-state">
                    <i class="bi bi-journal-x"></i>
                    <p>No hay materiales publicados todavía.</p>
                    <?php if ($puedeSubir): ?>
                        <p class="teen-empty-hint">Usa el formulario superior para subir el primer PDF.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
    </section>
</div>

<div id="modalPreviewPdfTeen" class="teen-pdf-modal" style="display:none;" aria-hidden="true">
    <div class="teen-pdf-modal__backdrop" onclick="cerrarPreviewPdfTeen()"></div>
    <div class="teen-pdf-modal__panel" role="dialog" aria-modal="true" aria-labelledby="teenPdfPreviewTitle">
        <header class="teen-pdf-modal__header">
            <div>
                <h3 id="teenPdfPreviewTitle">Vista previa</h3>
                <p id="teenPdfPreviewSubtitle" class="teen-pdf-modal__subtitle"></p>
            </div>
            <div class="teen-pdf-modal__header-actions">
                <a id="teenPdfPreviewOpenTab" href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">Abrir en pestaña</a>
                <button type="button" class="teen-pdf-modal__close" onclick="cerrarPreviewPdfTeen()" aria-label="Cerrar">&times;</button>
            </div>
        </header>
        <div class="teen-pdf-modal__body">
            <iframe
                id="teenPdfPreviewFrame"
                title="Documento PDF"
                src="about:blank"
            ></iframe>
        </div>
    </div>
</div>

<div id="modalVistasTeen" class="modal teen-modal" style="display:none;" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="tituloModalVistasTeen">Visualizaciones</h3>
            <button type="button" class="close" onclick="cerrarModalVistasTeen()" aria-label="Cerrar">&times;</button>
        </div>
        <div class="modal-body" id="contenidoModalVistasTeen">
            <p>Cargando…</p>
        </div>
    </div>
</div>

<style>
.teen-material-page {
    --teen-accent: #d1457b;
    --teen-accent-dark: #b83868;
    --teen-border: #e3eaf4;
    --teen-bg: #f8fafc;
    width: 100%;
    max-width: none;
    margin: 0;
    padding: 0 4px 24px;
    box-sizing: border-box;
}

.teen-material-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.teen-material-subtitle {
    margin: 4px 0 0;
    color: #64748b;
    font-size: 0.95rem;
}

.teen-nav-pills {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.teen-stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.teen-stat-card {
    background: #fff;
    border: 1px solid var(--teen-border);
    border-radius: 12px;
    padding: 12px 16px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
}

.teen-stat-label {
    display: block;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
    margin-bottom: 4px;
}

.teen-stat-value {
    font-size: 1.5rem;
    color: #0f172a;
    line-height: 1.2;
}

.teen-stat-muted {
    font-size: 1rem;
    color: #94a3b8;
    font-weight: 500;
}

.teen-stat-card--ok .teen-stat-value { color: #15803d; }
.teen-stat-card--warn { border-color: #fcd34d; background: #fffbeb; }
.teen-stat-card--warn .teen-stat-value { color: #b45309; }

.teen-flash { margin-bottom: 16px; border-radius: 10px; }

.teen-upload-section {
    margin-bottom: 24px;
}

.teen-published-section {
    width: 100%;
}

@media (max-width: 640px) {
    .teen-files-stack__row {
        grid-template-columns: 28px 1fr;
        grid-template-rows: auto auto;
    }

    .teen-files-stack__size,
    .teen-files-stack__actions {
        grid-column: 2;
    }

    .teen-pdf-modal__panel {
        width: 100%;
        max-height: 96vh;
    }

    .teen-pdf-modal__body iframe {
        height: 65vh;
    }
}

.teen-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.teen-panel-head h3 {
    margin: 0;
    font-size: 1.15rem;
    color: #1e293b;
}

.teen-files-panel {
    width: 100%;
}

.teen-files-stack {
    list-style: none;
    margin: 0;
    padding: 0;
    background: #fff;
    border: 1px solid var(--teen-border);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05);
}

.teen-files-stack__group + .teen-files-stack__group {
    border-top: 2px solid #e2e8f0;
}

.teen-files-stack__group-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px 14px;
    padding: 8px 14px;
    background: #f1f5f9;
    border-bottom: 1px solid #e2e8f0;
    font-size: 0.84rem;
}

.teen-files-stack__group-head--warn {
    background: #fffbeb;
    border-bottom-color: #fde68a;
}

.teen-files-stack__group-title {
    flex: 1 1 200px;
    min-width: 0;
}

.teen-files-stack__group-title strong {
    display: block;
    color: #0f172a;
    font-size: 0.9rem;
    line-height: 1.3;
}

.teen-files-stack__group-desc {
    display: block;
    color: #64748b;
    font-size: 0.78rem;
    margin-top: 2px;
}

.teen-files-stack__group-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.teen-files-stack__date {
    font-size: 0.75rem;
    color: #94a3b8;
}

.teen-files-stack__group-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.teen-files-stack__row {
    display: grid;
    grid-template-columns: 32px 1fr auto auto;
    gap: 10px 14px;
    align-items: center;
    padding: 7px 14px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.86rem;
}

.teen-files-stack__row:last-child {
    border-bottom: none;
}

.teen-files-stack__row--missing {
    background: #fffbeb;
}

.teen-files-stack__row--empty {
    padding: 10px 14px;
    color: #64748b;
    font-size: 0.85rem;
}

.teen-files-stack__icon {
    color: #dc2626;
    font-size: 1.15rem;
    line-height: 1;
}

.teen-files-stack__name {
    color: #1e293b;
    font-weight: 500;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.teen-files-stack__size {
    color: #64748b;
    font-size: 0.78rem;
    white-space: nowrap;
}

.teen-files-stack__actions {
    display: flex;
    gap: 4px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn.btn-xs {
    padding: 2px 8px;
    font-size: 0.72rem;
    line-height: 1.4;
    border-radius: 5px;
}

.teen-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 999px;
}

.teen-badge--ok { background: #dcfce7; color: #166534; }
.teen-badge--warn { background: #fef3c7; color: #92400e; }

.teen-meta-item {
    font-size: 0.85rem;
    color: #64748b;
}

.teen-file-list {
    list-style: none;
    margin: 0 0 14px;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.teen-file-item {
    display: grid;
    grid-template-columns: 112px 1fr;
    gap: 14px;
    align-items: start;
    padding: 12px;
    background: #ffffff;
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
}

.teen-file-item--missing {
    grid-template-columns: 72px 1fr;
    background: #fffbeb;
    border-color: #fde68a;
}

.teen-pdf-thumb {
    display: block;
    width: 112px;
    padding: 0;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #ffffff;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;
}

.teen-pdf-thumb:hover {
    border-color: var(--teen-accent);
    box-shadow: 0 4px 12px rgba(209, 69, 123, 0.2);
}

.teen-pdf-thumb__frame {
    display: block;
    width: 100%;
    height: 140px;
    background: #ffffff;
    overflow: hidden;
}

.teen-pdf-thumb__frame iframe {
    width: 100%;
    height: 100%;
    border: 0;
    background: #ffffff;
    pointer-events: none;
    transform: scale(1);
}

.teen-pdf-thumb__overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.82);
    color: #be185d;
    font-size: 0.82rem;
    font-weight: 600;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.teen-pdf-thumb:hover .teen-pdf-thumb__overlay {
    opacity: 1;
}

.teen-pdf-thumb--empty {
    width: 72px;
    height: 72px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fef3c7;
    color: #b45309;
    font-size: 1.5rem;
    cursor: default;
    border-color: #fde68a;
}

.teen-file-details {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.teen-file-name {
    font-size: 0.92rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.35;
    word-break: break-word;
}

.teen-file-size {
    font-size: 0.78rem;
    color: #64748b;
}

.teen-file-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 4px;
}

.teen-file-actions .btn-primary {
    background: var(--teen-accent);
    border-color: var(--teen-accent);
}

.teen-file-actions .btn-primary:hover {
    background: var(--teen-accent-dark);
    border-color: var(--teen-accent-dark);
}

.teen-file-missing {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #b45309;
    font-size: 0.88rem;
    min-width: 0;
}

.teen-file-missing span {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.teen-file-tag {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #b45309;
    background: #fef3c7;
    padding: 2px 8px;
    border-radius: 6px;
    flex-shrink: 0;
}

.teen-module-card__foot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}

.teen-date {
    font-size: 0.8rem;
    color: #94a3b8;
}

.teen-module-actions {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.teen-empty-state {
    text-align: center;
    padding: 48px 24px;
    background: #fff;
    border: 1px dashed var(--teen-border);
    border-radius: 14px;
    color: #64748b;
}

.teen-empty-state i {
    font-size: 2.5rem;
    color: #cbd5e1;
    display: block;
    margin-bottom: 12px;
}

.teen-empty-hint { font-size: 0.9rem; margin-top: 8px; }

.teen-upload-card {
    background: #fff;
    border: 1px solid var(--teen-border);
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
    width: 100%;
}

.teen-upload-card__head {
    margin-bottom: 16px;
}

.teen-upload-card h3 {
    margin: 0 0 4px;
    font-size: 1.15rem;
    color: #0f172a;
}

.teen-upload-hint {
    margin: 0;
    font-size: 0.88rem;
    color: #64748b;
    line-height: 1.45;
}

.teen-upload-form {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 16px;
}

.teen-upload-form__fields {
    flex: 1 1 520px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr;
    gap: 14px 16px;
    min-width: 0;
}

.teen-upload-form__action {
    flex: 0 0 auto;
}

.teen-upload-form .form-group {
    margin-bottom: 0;
}

.teen-upload-form label {
    font-weight: 600;
    font-size: 0.88rem;
    margin-bottom: 4px;
    display: block;
}

.teen-optional {
    font-weight: 400;
    color: #94a3b8;
}

.teen-upload-btn {
    white-space: nowrap;
    padding: 10px 22px;
    min-height: 42px;
    background: var(--teen-accent);
    border-color: var(--teen-accent);
}

@media (max-width: 1100px) {
    .teen-upload-form__fields {
        grid-template-columns: 1fr 1fr;
    }

    .teen-upload-form__files {
        grid-column: 1 / -1;
    }
}

@media (max-width: 768px) {
    .teen-upload-form {
        flex-direction: column;
        align-items: stretch;
    }

    .teen-upload-form__fields {
        grid-template-columns: 1fr;
    }

    .teen-upload-form__action .teen-upload-btn {
        width: 100%;
    }

}

.teen-upload-btn:hover {
    background: var(--teen-accent-dark);
    border-color: var(--teen-accent-dark);
}

.teen-pdf-modal {
    position: fixed;
    z-index: 10000;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
}

.teen-pdf-modal__backdrop {
    position: absolute;
    inset: 0;
    background: rgba(241, 245, 249, 0.92);
    backdrop-filter: blur(2px);
}

.teen-pdf-modal__panel {
    position: relative;
    z-index: 1;
    width: min(1100px, 96vw);
    max-height: 92vh;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.teen-pdf-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 18px;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
}

.teen-pdf-modal__header h3 {
    margin: 0;
    font-size: 1.05rem;
    color: #0f172a;
}

.teen-pdf-modal__subtitle {
    margin: 4px 0 0;
    font-size: 0.82rem;
    color: #64748b;
    word-break: break-all;
}

.teen-pdf-modal__header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-shrink: 0;
}

.teen-pdf-modal__close {
    width: 36px;
    height: 36px;
    border: none;
    border-radius: 8px;
    background: #e2e8f0;
    color: #334155;
    font-size: 1.4rem;
    line-height: 1;
    cursor: pointer;
}

.teen-pdf-modal__close:hover {
    background: #cbd5e1;
}

.teen-pdf-modal__body {
    flex: 1;
    min-height: 0;
    background: #ffffff;
    padding: 0;
}

.teen-pdf-modal__body iframe {
    display: block;
    width: 100%;
    height: min(78vh, 820px);
    border: 0;
    background: #ffffff;
}

.teen-modal.modal {
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(241, 245, 249, 0.88);
    overflow: auto;
}

.teen-modal .modal-content {
    background: #fff;
    margin: 4% auto;
    border-radius: 12px;
    max-width: 960px;
    width: 94%;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

.teen-modal .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
}

.teen-modal .modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
}

.teen-modal .close {
    background: none;
    border: none;
    font-size: 1.75rem;
    line-height: 1;
    cursor: pointer;
    color: #64748b;
}

.table-modal {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.table-modal th,
.table-modal td {
    border: 1px solid #e2e8f0;
    padding: 8px 10px;
    text-align: left;
}

.table-modal th {
    background: #f8fafc;
}
</style>

<script>
function abrirPreviewPdfTeen(trigger) {
    var modal = document.getElementById('modalPreviewPdfTeen');
    var frame = document.getElementById('teenPdfPreviewFrame');
    var titulo = document.getElementById('teenPdfPreviewTitle');
    var subtitulo = document.getElementById('teenPdfPreviewSubtitle');
    var enlaceTab = document.getElementById('teenPdfPreviewOpenTab');

    if (!modal || !frame || !trigger) {
        return;
    }

    var nombre = trigger.getAttribute('data-nombre') || 'Documento PDF';
    var urlVer = trigger.getAttribute('data-url-ver') || '';
    var urlEmbed = trigger.getAttribute('data-url-embed') || urlVer;
    var src = urlVer;

    if (urlVer.indexOf('/uploads/teens/') !== -1) {
        src = urlVer + '#view=FitH&toolbar=1&navpanes=0';
    }

    titulo.textContent = 'Vista previa del material';
    subtitulo.textContent = nombre;
    enlaceTab.href = urlVer;
    frame.src = 'about:blank';
    modal.style.display = 'flex';
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';

    window.setTimeout(function() {
        frame.src = src;
    }, 50);
}

function cerrarPreviewPdfTeen() {
    var modal = document.getElementById('modalPreviewPdfTeen');
    var frame = document.getElementById('teenPdfPreviewFrame');
    if (!modal) {
        return;
    }
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    if (frame) {
        frame.src = 'about:blank';
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        cerrarPreviewPdfTeen();
    }
});

function verDetalleVistas(archivo) {
    const modal = document.getElementById('modalVistasTeen');
    const contenido = document.getElementById('contenidoModalVistasTeen');
    const titulo = document.getElementById('tituloModalVistasTeen');

    modal.style.display = 'block';
    modal.setAttribute('aria-hidden', 'false');
    titulo.textContent = 'Visualizaciones';
    contenido.innerHTML = '<p>Cargando…</p>';

    fetch('<?= PUBLIC_URL ?>index.php?url=teen/detalleVistas&archivo=' + encodeURIComponent(archivo))
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data.success) {
                contenido.innerHTML = '<p class="text-danger">' + escaparHtml(data.message || 'Error al consultar') + '</p>';
                return;
            }

            var html = '<p><strong>Archivo:</strong> ' + escaparHtml(data.archivo) + '</p>';
            html += '<p><strong>Personas:</strong> ' + (data.total_personas || 0) + '</p>';

            if (!data.vistas || !data.vistas.length) {
                html += '<p style="color:#64748b;">Sin visualizaciones registradas.</p>';
            } else {
                html += '<div class="table-container"><table class="table-modal"><thead><tr>';
                html += '<th>Nombre</th><th>Teléfono</th><th>Ministerio</th><th>Vistas</th><th>Primera</th><th>Última</th>';
                html += '</tr></thead><tbody>';
                data.vistas.forEach(function(item) {
                    var nombre = ((item.Nombre || '') + ' ' + (item.Apellido || '')).trim() || '—';
                    html += '<tr><td>' + escaparHtml(nombre) + '</td>';
                    html += '<td>' + escaparHtml(item.Telefono || '') + '</td>';
                    html += '<td>' + escaparHtml(item.Nombre_Ministerio || '') + '</td>';
                    html += '<td>' + (item.total_vistas || 0) + '</td>';
                    html += '<td>' + escaparHtml(item.fecha_primera_vista || '') + '</td>';
                    html += '<td>' + escaparHtml(item.fecha_ultima_vista || '') + '</td></tr>';
                });
                html += '</tbody></table></div>';
            }
            contenido.innerHTML = html;
        })
        .catch(function() {
            contenido.innerHTML = '<p class="text-danger">No se pudo cargar la información.</p>';
        });
}

function cerrarModalVistasTeen() {
    var modal = document.getElementById('modalVistasTeen');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
}

window.addEventListener('click', function(event) {
    var modal = document.getElementById('modalVistasTeen');
    if (event.target === modal) {
        cerrarModalVistasTeen();
    }
});

function escaparHtml(texto) {
    var div = document.createElement('div');
    div.textContent = texto === null || texto === undefined ? '' : texto;
    return div.innerHTML;
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
