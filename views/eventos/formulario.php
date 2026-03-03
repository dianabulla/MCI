<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><?= isset($evento) ? 'Editar' : 'Nuevo' ?> Evento</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=eventos" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 15px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="nombre_evento">Nombre del Evento</label>
            <input type="text" id="nombre_evento" name="nombre_evento" class="form-control" 
                   value="<?= htmlspecialchars($evento['Nombre_Evento'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion_evento">Descripción</label>
            <textarea id="descripcion_evento" name="descripcion_evento" class="form-control" rows="4" required><?= htmlspecialchars($evento['Descripcion_Evento'] ?? '') ?></textarea>
            <small style="display:block; margin-top:6px; color:#666;">Este texto se mostrará también en la vista pública de próximos eventos.</small>
        </div>

        <div class="form-group">
            <label for="fecha_evento">Fecha</label>
            <input type="date" id="fecha_evento" name="fecha_evento" class="form-control" 
                   value="<?= htmlspecialchars($evento['Fecha_Evento'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="hora_evento">Hora</label>
            <input type="time" id="hora_evento" name="hora_evento" class="form-control" 
                   value="<?= htmlspecialchars($evento['Hora_Evento'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="lugar_evento">Lugar</label>
            <input type="text" id="lugar_evento" name="lugar_evento" class="form-control" 
                   value="<?= htmlspecialchars($evento['Lugar_Evento'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="imagen_evento">Imagen del Evento (opcional)</label>
            <input type="file" id="imagen_evento" name="imagen_evento" class="form-control" accept="image/*">
            <?php if (!empty($evento['Imagen_Evento'])): ?>
                <div style="margin-top:10px;">
                    <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($evento['Imagen_Evento']) ?>" alt="Imagen evento" style="max-width:220px; border-radius:8px;">
                </div>
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                    <input type="checkbox" name="eliminar_imagen" value="1"> Eliminar imagen actual
                </label>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="video_evento">Video del Evento (opcional)</label>
            <input type="file" id="video_evento" name="video_evento" class="form-control" accept="video/mp4,video/webm,video/quicktime,video/x-m4v">
            <?php if (!empty($evento['Video_Evento'])): ?>
                <div style="margin-top:10px;">
                    <video controls style="max-width:320px; border-radius:8px;">
                        <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode($evento['Video_Evento']) ?>">
                        Tu navegador no soporta video HTML5.
                    </video>
                </div>
                <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                    <input type="checkbox" name="eliminar_video" value="1"> Eliminar video actual
                </label>
            <?php endif; ?>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=eventos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
