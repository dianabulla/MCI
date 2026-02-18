<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><?= isset($evento) ? 'Editar' : 'Nuevo' ?> Evento</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=eventos" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-group">
            <label for="nombre_evento">Nombre del Evento</label>
            <input type="text" id="nombre_evento" name="nombre_evento" class="form-control" 
                   value="<?= htmlspecialchars($evento['Nombre_Evento'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion_evento">Descripción</label>
            <textarea id="descripcion_evento" name="descripcion_evento" class="form-control" rows="4" required><?= htmlspecialchars($evento['Descripcion_Evento'] ?? '') ?></textarea>
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

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=eventos" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
