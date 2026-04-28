<?php include VIEWS . '/layout/header.php'; ?>

<?php
$esEdicion = isset($evento);
$tituloVista = $esEdicion ? 'Editar Evento' : 'Nuevo Evento';
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <div>
        <h2 style="margin:0;"><?= $tituloVista ?></h2>
        <small style="color:#637087;">Mantiene el flujo visual unificado con los demás módulos administrativos.</small>
    </div>
    <div class="header-actions">
        <div class="action-group action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=eventos" class="action-pill">Listado</a>
            <a href="<?= PUBLIC_URL ?>?url=eventos/crear" class="action-pill <?= !$esEdicion ? 'is-active' : '' ?>" <?= !$esEdicion ? 'aria-current="page"' : '' ?>>Nuevo evento</a>
            <?php if ($esEdicion): ?>
            <span class="action-pill is-active" aria-current="page">Editar evento</span>
            <?php endif; ?>
        </div>
        <div class="action-group">
            <a href="<?= PUBLIC_URL ?>?url=eventos/universidad-vida" class="action-pill">Universidad de la Vida</a>
            <a href="<?= PUBLIC_URL ?>?url=eventos/capacitacion-destino" class="action-pill">Capacitación Destino</a>
            <a href="<?= PUBLIC_URL ?>?url=eventos/otros" class="action-pill">Otros</a>
            <a href="<?= PUBLIC_URL ?>?url=home" class="action-pill">Volver al panel</a>
        </div>
    </div>
</div>

<div class="card report-card" style="padding:14px; margin-bottom:14px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;">
        <h3 style="margin:0;">Datos del evento</h3>
        <small style="color:#637087;">Completa toda la información para publicar correctamente.</small>
    </div>
</div>

<div class="card report-card form-container" style="padding:16px;">
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 15px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:12px;">
            <div class="form-group" style="margin:0;">
                <label for="nombre_evento">Nombre del Evento</label>
                <input type="text" id="nombre_evento" name="nombre_evento" class="form-control" value="<?= htmlspecialchars((string)($evento['Nombre_Evento'] ?? '')) ?>" required>
            </div>

            <div class="form-group" style="margin:0;">
                <label for="lugar_evento">Lugar</label>
                <input type="text" id="lugar_evento" name="lugar_evento" class="form-control" value="<?= htmlspecialchars((string)($evento['Lugar_Evento'] ?? '')) ?>" required>
            </div>

            <div class="form-group" style="margin:0;">
                <label for="fecha_evento">Fecha</label>
                <input type="date" id="fecha_evento" name="fecha_evento" class="form-control" value="<?= htmlspecialchars((string)($evento['Fecha_Evento'] ?? '')) ?>" required>
            </div>

            <div class="form-group" style="margin:0;">
                <label for="hora_evento">Hora</label>
                <input type="time" id="hora_evento" name="hora_evento" class="form-control" value="<?= htmlspecialchars((string)($evento['Hora_Evento'] ?? '')) ?>" required>
            </div>
        </div>

        <div class="form-group" style="margin-top:12px;">
            <label for="descripcion_evento">Descripción</label>
            <textarea id="descripcion_evento" name="descripcion_evento" class="form-control" rows="4" required><?= htmlspecialchars((string)($evento['Descripcion_Evento'] ?? '')) ?></textarea>
            <small style="display:block; margin-top:6px; color:#666;">Este texto se mostrará también en la vista pública de próximos eventos.</small>
        </div>

        <div class="form-group">
            <label style="display:flex; align-items:center; gap:8px; margin-bottom:0;">
                <input
                    type="checkbox"
                    name="permitir_compartir"
                    value="1"
                    <?= !isset($evento['Permitir_Compartir']) || (int)$evento['Permitir_Compartir'] === 1 ? 'checked' : '' ?>
                >
                Permitir compartir este evento públicamente
            </label>
            <small style="display:block; margin-top:6px; color:#666;">
                Si desmarcas esta opción, el evento seguirá saliendo en la página pública, pero no mostrará botón de compartir.
            </small>
        </div>

        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(320px, 1fr)); gap:12px; margin-top:8px;">
            <div class="form-group" style="margin:0;">
                <label for="imagen_evento">Imagen del Evento (opcional)</label>
                <input type="file" id="imagen_evento" name="imagen_evento" class="form-control" accept="image/*">
                <small style="display:block; margin-top:6px; color:#666;">Máximo recomendado: 50MB.</small>
                <?php if (!empty($evento['Imagen_Evento'])): ?>
                    <div style="margin-top:10px;">
                        <img src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$evento['Imagen_Evento']) ?>" alt="Imagen evento" style="max-width:220px; border-radius:8px; border:1px solid #d9e2ef;">
                    </div>
                    <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                        <input type="checkbox" name="eliminar_imagen" value="1"> Eliminar imagen actual
                    </label>
                <?php endif; ?>
            </div>

            <div class="form-group" style="margin:0;">
                <label for="video_evento">Video del Evento (opcional)</label>
                <input type="file" id="video_evento" name="video_evento" class="form-control" accept="video/mp4,video/webm,video/quicktime,video/x-m4v">
                <small style="display:block; margin-top:6px; color:#666;">Máximo recomendado: 500MB.</small>
                <?php if (!empty($evento['Video_Evento'])): ?>
                    <div style="margin-top:10px;">
                        <video controls style="max-width:320px; border-radius:8px; border:1px solid #d9e2ef;">
                            <source src="<?= rtrim(PUBLIC_URL, '/') . '/uploads/eventos/' . rawurlencode((string)$evento['Video_Evento']) ?>">
                            Tu navegador no soporta video HTML5.
                        </video>
                    </div>
                    <label style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                        <input type="checkbox" name="eliminar_video" value="1"> Eliminar video actual
                    </label>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $esEdicion ? 'Actualizar' : 'Guardar' ?></button>
            <a href="<?= PUBLIC_URL ?>?url=eventos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.header-actions {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
}

.action-group {
    display:inline-flex;
    align-items:center;
    gap:4px;
    padding:4px;
    border:1px solid #d5e2f3;
    border-radius:999px;
    background:#f8fbff;
}

.action-pill {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:7px 12px;
    border:1px solid transparent;
    border-radius:999px;
    color:#2a4a73;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    line-height:1;
    white-space:nowrap;
    transition:all .16s ease;
}

.action-pill:hover {
    background:#edf4ff;
    color:#1c4478;
}

.action-pill.is-active {
    background:#1f5ea8;
    border-color:#1f5ea8;
    color:#ffffff;
    box-shadow:0 1px 3px rgba(20, 58, 101, 0.28);
}

@media (max-width: 720px) {
    .header-actions {
        width: 100%;
        justify-content: stretch;
    }

    .action-group {
        width: 100%;
        justify-content: flex-start;
        overflow-x: auto;
    }

    .action-pill {
        min-height: 40px;
        font-size: 14px;
    }

    .form-container {
        padding: 14px !important;
    }

    .form-actions {
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .form-actions .btn {
        width: 100%;
        justify-content: center;
        min-height: 46px;
    }

    .form-group label {
        font-size: 14px;
    }

    .form-control,
    textarea.form-control,
    select.form-control {
        min-height: 46px;
        font-size: 16px;
    }

    textarea.form-control {
        min-height: 120px;
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>