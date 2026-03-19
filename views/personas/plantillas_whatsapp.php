<?php include VIEWS . '/layout/header.php'; ?>

<?php
$detectarTipoMedia = static function ($mediaUrl, $mediaTipo) {
    $tipo = strtolower(trim((string)$mediaTipo));
    if (strpos($tipo, 'image') !== false || $tipo === 'imagen') {
        return 'image';
    }
    if (strpos($tipo, 'video') !== false) {
        return 'video';
    }

    $path = strtolower((string)parse_url((string)$mediaUrl, PHP_URL_PATH));
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $imagenes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $videos = ['mp4', 'mov', 'avi', 'mkv', 'webm', 'm4v'];

    if (in_array($ext, $imagenes, true)) {
        return 'image';
    }
    if (in_array($ext, $videos, true)) {
        return 'video';
    }

    return 'other';
};

$renderMediaActual = static function ($mediaUrl, $mediaTipo, $previewId) use ($detectarTipoMedia) {
    if (empty($mediaUrl)) {
        echo '<div id="' . htmlspecialchars((string)$previewId) . '" class="wa-media-preview" style="display:none;"></div>';
        return;
    }

    $tipo = $detectarTipoMedia($mediaUrl, $mediaTipo);
    echo '<div id="' . htmlspecialchars((string)$previewId) . '" class="wa-media-preview" style="margin-top:8px;">';
    echo '<div style="font-size:12px; color:#5b6b84; margin-bottom:6px;">Vista previa actual:</div>';

    if ($tipo === 'image') {
        echo '<img src="' . htmlspecialchars((string)$mediaUrl) . '" alt="Media actual" style="max-width:260px; width:100%; border-radius:8px; border:1px solid #d9e0ea;" loading="lazy">';
    } elseif ($tipo === 'video') {
        echo '<video controls preload="metadata" style="max-width:320px; width:100%; border-radius:8px; border:1px solid #d9e0ea;">';
        echo '<source src="' . htmlspecialchars((string)$mediaUrl) . '">';
        echo 'Tu navegador no soporta video.';
        echo '</video>';
    } else {
        echo '<a href="' . htmlspecialchars((string)$mediaUrl) . '" target="_blank" class="btn btn-sm btn-info">Abrir media actual</a>';
    }

    echo '</div>';
};
?>

<div class="page-header">
    <h2>Plantillas mensaje what</h2>
    <div class="page-actions personas-mobile-stack">
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">Volver a Personas</a>
    </div>
</div>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body">
        <p style="margin: 0; color: #5b6b84; font-size: 13px;">
            Variables disponibles: <?= htmlspecialchars(implode(', ', $variablesPlantillasWhatsapp ?? [])) ?>
        </p>
        <p style="margin: 6px 0 0; color: #5b6b84; font-size: 12px;">
            Puedes subir imagen o video opcional por plantilla. Si hay media, se enviará junto al texto.
        </p>
    </div>
</div>

<?php if (!empty($plantillasGuardadas)): ?>
<div class="alert alert-success" style="margin-bottom: 12px;">
    Plantillas actualizadas correctamente.
</div>
<?php endif; ?>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-body">
        <form method="POST" action="<?= PUBLIC_URL ?>?url=personas/guardarPlantillasWhatsapp" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom: 14px;">
                <label for="tpl_bienvenida_persona" style="font-weight: 600;">Bienvenida persona</label>
                <textarea id="tpl_bienvenida_persona" name="tpl_bienvenida_persona" class="form-control" rows="3" required><?= htmlspecialchars((string)($plantillasWhatsapp['bienvenida_persona']['plantilla'] ?? '')) ?></textarea>
                <div style="margin-top:8px;">
                    <input type="file" name="media_bienvenida_persona" class="form-control js-wa-media-input" accept="image/*,video/*" data-preview-target="preview_bienvenida_persona">
                    <?php $renderMediaActual($plantillasWhatsapp['bienvenida_persona']['media_url'] ?? null, $plantillasWhatsapp['bienvenida_persona']['media_tipo'] ?? null, 'preview_bienvenida_persona'); ?>
                    <?php if (!empty($plantillasWhatsapp['bienvenida_persona']['media_url'])): ?>
                        <a href="<?= htmlspecialchars((string)$plantillasWhatsapp['bienvenida_persona']['media_url']) ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top:6px;">Ver media actual</a>
                        <label style="display:block; margin-top:6px;"><input type="checkbox" name="quitar_media_bienvenida_persona" value="1"> Quitar media</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="tpl_asignacion_lider" style="font-weight: 600;">Asignación a líder</label>
                <textarea id="tpl_asignacion_lider" name="tpl_asignacion_lider" class="form-control" rows="3" required><?= htmlspecialchars((string)($plantillasWhatsapp['asignacion_lider']['plantilla'] ?? '')) ?></textarea>
                <div style="margin-top:8px;">
                    <input type="file" name="media_asignacion_lider" class="form-control js-wa-media-input" accept="image/*,video/*" data-preview-target="preview_asignacion_lider">
                    <?php $renderMediaActual($plantillasWhatsapp['asignacion_lider']['media_url'] ?? null, $plantillasWhatsapp['asignacion_lider']['media_tipo'] ?? null, 'preview_asignacion_lider'); ?>
                    <?php if (!empty($plantillasWhatsapp['asignacion_lider']['media_url'])): ?>
                        <a href="<?= htmlspecialchars((string)$plantillasWhatsapp['asignacion_lider']['media_url']) ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top:6px;">Ver media actual</a>
                        <label style="display:block; margin-top:6px;"><input type="checkbox" name="quitar_media_asignacion_lider" value="1"> Quitar media</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="tpl_asignacion_ministerio" style="font-weight: 600;">Asignación a ministerio</label>
                <textarea id="tpl_asignacion_ministerio" name="tpl_asignacion_ministerio" class="form-control" rows="3" required><?= htmlspecialchars((string)($plantillasWhatsapp['asignacion_ministerio']['plantilla'] ?? '')) ?></textarea>
                <div style="margin-top:8px;">
                    <input type="file" name="media_asignacion_ministerio" class="form-control js-wa-media-input" accept="image/*,video/*" data-preview-target="preview_asignacion_ministerio">
                    <?php $renderMediaActual($plantillasWhatsapp['asignacion_ministerio']['media_url'] ?? null, $plantillasWhatsapp['asignacion_ministerio']['media_tipo'] ?? null, 'preview_asignacion_ministerio'); ?>
                    <?php if (!empty($plantillasWhatsapp['asignacion_ministerio']['media_url'])): ?>
                        <a href="<?= htmlspecialchars((string)$plantillasWhatsapp['asignacion_ministerio']['media_url']) ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top:6px;">Ver media actual</a>
                        <label style="display:block; margin-top:6px;"><input type="checkbox" name="quitar_media_asignacion_ministerio" value="1"> Quitar media</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="tpl_felicitacion_cumpleanos" style="font-weight: 600;">Felicitación de cumpleaños</label>
                <textarea id="tpl_felicitacion_cumpleanos" name="tpl_felicitacion_cumpleanos" class="form-control" rows="6" required><?= htmlspecialchars((string)($plantillasWhatsapp['felicitacion_cumpleanos']['plantilla'] ?? '')) ?></textarea>
                <small style="display:block; margin-top:6px; color:#666;">Se envía automáticamente a las personas que cumplen años hoy.</small>
                <div style="margin-top:8px;">
                    <input type="file" name="media_felicitacion_cumpleanos" class="form-control js-wa-media-input" accept="image/*,video/*" data-preview-target="preview_felicitacion_cumpleanos">
                    <?php $renderMediaActual($plantillasWhatsapp['felicitacion_cumpleanos']['media_url'] ?? null, $plantillasWhatsapp['felicitacion_cumpleanos']['media_tipo'] ?? null, 'preview_felicitacion_cumpleanos'); ?>
                    <?php if (!empty($plantillasWhatsapp['felicitacion_cumpleanos']['media_url'])): ?>
                        <a href="<?= htmlspecialchars((string)$plantillasWhatsapp['felicitacion_cumpleanos']['media_url']) ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top:6px;">Ver media actual</a>
                        <label style="display:block; margin-top:6px;"><input type="checkbox" name="quitar_media_felicitacion_cumpleanos" value="1"> Quitar media</label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label for="tpl_asignacion_celula_universidad" style="font-weight: 600;">Asignación a célula - Universidad de la vida</label>
                <textarea id="tpl_asignacion_celula_universidad" name="tpl_asignacion_celula_universidad" class="form-control" rows="3" required><?= htmlspecialchars((string)($plantillasWhatsapp['asignacion_celula_universidad']['plantilla'] ?? '')) ?></textarea>
                <small style="display:block; margin-top:6px; color:#666;">Nota: Se incluye automáticamente el próximo contenido de Universidad de la Vida</small>
                <div style="margin-top:8px;">
                    <input type="file" name="media_asignacion_celula_universidad" class="form-control js-wa-media-input" accept="image/*,video/*" data-preview-target="preview_asignacion_celula_universidad">
                    <?php $renderMediaActual($plantillasWhatsapp['asignacion_celula_universidad']['media_url'] ?? null, $plantillasWhatsapp['asignacion_celula_universidad']['media_tipo'] ?? null, 'preview_asignacion_celula_universidad'); ?>
                    <?php if (!empty($plantillasWhatsapp['asignacion_celula_universidad']['media_url'])): ?>
                        <a href="<?= htmlspecialchars((string)$plantillasWhatsapp['asignacion_celula_universidad']['media_url']) ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top:6px;">Ver media actual</a>
                        <label style="display:block; margin-top:6px;"><input type="checkbox" name="quitar_media_asignacion_celula_universidad" value="1"> Quitar media</label>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar plantillas</button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var inputs = document.querySelectorAll('.js-wa-media-input');

    var createPreviewHtml = function (file, src) {
        if (file.type && file.type.indexOf('image/') === 0) {
            return '<div style="font-size:12px;color:#5b6b84;margin-bottom:6px;">Vista previa seleccionada:</div>' +
                '<img src="' + src + '" alt="Vista previa" style="max-width:260px;width:100%;border-radius:8px;border:1px solid #d9e0ea;">';
        }

        if (file.type && file.type.indexOf('video/') === 0) {
            return '<div style="font-size:12px;color:#5b6b84;margin-bottom:6px;">Vista previa seleccionada:</div>' +
                '<video controls preload="metadata" style="max-width:320px;width:100%;border-radius:8px;border:1px solid #d9e0ea;">' +
                '<source src="' + src + '">' +
                'Tu navegador no soporta video.' +
                '</video>';
        }

        return '<div class="alert alert-info" style="margin-top:8px;">Archivo seleccionado: ' +
            (file.name || 'archivo') + '</div>';
    };

    inputs.forEach(function (input) {
        input.addEventListener('change', function () {
            var previewId = input.getAttribute('data-preview-target');
            if (!previewId) {
                return;
            }

            var preview = document.getElementById(previewId);
            if (!preview) {
                return;
            }

            var file = input.files && input.files[0] ? input.files[0] : null;
            if (!file) {
                return;
            }

            var src = URL.createObjectURL(file);
            preview.style.display = 'block';
            preview.innerHTML = createPreviewHtml(file, src);
        });
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
