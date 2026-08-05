<?php
/** @var array<string, string> $errores */
$errores = is_array($errores ?? null) ? $errores : [];
?>
<div class="field" id="section-documentos-presentacion">
    <p class="help" style="margin-bottom:12px;">
        Puede adjuntar <strong>varios archivos</strong> (PDF, JPG, PNG, WEBP, DOC o DOCX; máx. 8 MB c/u).
        Los documentos se guardan junto con la inscripción al enviar el formulario.
    </p>
    <?php if (!empty($errores['documentos_presentacion'])): ?>
    <div class="err" style="margin-bottom:10px;"><?= htmlspecialchars((string)$errores['documentos_presentacion'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <input type="file" id="documentos_presentacion_ninos" name="documentos_presentacion_ninos[]" multiple
           accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*,application/pdf" style="display:none;">
    <div class="doc-upload-actions">
        <button type="button" class="btn btn-outline-secondary" id="btn-taller-agregar-documentos">+ Agregar archivos</button>
        <button type="button" class="btn btn-outline-secondary" id="btn-taller-quitar-documentos" style="display:none;">Quitar todos</button>
    </div>
    <div class="doc-pendientes-panel">
        <div class="doc-pendientes-header">
            <span class="doc-pendientes-badge" id="taller-doc-badge">0</span>
            <strong>Archivos listos para enviar</strong>
        </div>
        <p id="taller-doc-vacio" class="doc-pendientes-vacio">Ningún archivo seleccionado. Pulse «+ Agregar archivos».</p>
        <ul id="taller-doc-lista" class="doc-archivos-lista" style="display:none;"></ul>
    </div>
</div>
