<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><?= isset($peticion) ? 'Editar' : 'Nueva' ?> Petición</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=peticiones" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-group">
            <label for="id_persona">Persona</label>
            <select id="id_persona" name="id_persona" class="form-control" required>
                <option value="">Seleccione...</option>
                <?php if (!empty($personas)): ?>
                    <?php foreach ($personas as $persona): ?>
                        <option value="<?= $persona['Id_Persona'] ?>" 
                                <?= isset($peticion) && $peticion['Id_Persona'] == $persona['Id_Persona'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="descripcion_peticion">Descripción de la Petición</label>
            <textarea id="descripcion_peticion" name="descripcion_peticion" class="form-control" rows="5" required><?= htmlspecialchars($peticion['Descripcion_Peticion'] ?? '') ?></textarea>
        </div>

        <?php if (isset($peticion)): ?>
        <div class="form-group">
            <label for="estado_peticion">Estado</label>
            <select id="estado_peticion" name="estado_peticion" class="form-control" required>
                <option value="Pendiente" <?= $peticion['Estado_Peticion'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="Respondida" <?= $peticion['Estado_Peticion'] == 'Respondida' ? 'selected' : '' ?>>Respondida</option>
            </select>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=peticiones" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
