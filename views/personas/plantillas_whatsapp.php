<?php include VIEWS . '/layout/header.php'; ?>

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
                    <input type="file" name="media_bienvenida_persona" class="form-control" accept="image/*,video/*">
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
                    <input type="file" name="media_asignacion_lider" class="form-control" accept="image/*,video/*">
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
                    <input type="file" name="media_asignacion_ministerio" class="form-control" accept="image/*,video/*">
                    <?php if (!empty($plantillasWhatsapp['asignacion_ministerio']['media_url'])): ?>
                        <a href="<?= htmlspecialchars((string)$plantillasWhatsapp['asignacion_ministerio']['media_url']) ?>" target="_blank" class="btn btn-sm btn-info" style="margin-top:6px;">Ver media actual</a>
                        <label style="display:block; margin-top:6px;"><input type="checkbox" name="quitar_media_asignacion_ministerio" value="1"> Quitar media</label>
                    <?php endif; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar plantillas</button>
        </form>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
