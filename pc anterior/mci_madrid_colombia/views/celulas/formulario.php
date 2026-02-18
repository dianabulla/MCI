<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><?= isset($celula) ? 'Editar' : 'Nueva' ?> Célula</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=celulas" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-group">
            <label for="nombre_celula">Nombre de la Célula</label>
            <input type="text" id="nombre_celula" name="nombre_celula" class="form-control" 
                   value="<?= htmlspecialchars($celula['Nombre_Celula'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="direccion_celula">Dirección</label>
            <input type="text" id="direccion_celula" name="direccion_celula" class="form-control" 
                   value="<?= htmlspecialchars($celula['Direccion_Celula'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="dia_reunion">Día de Reunión</label>
            <select id="dia_reunion" name="dia_reunion" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="Lunes" <?= isset($celula) && $celula['Dia_Reunion'] == 'Lunes' ? 'selected' : '' ?>>Lunes</option>
                <option value="Martes" <?= isset($celula) && $celula['Dia_Reunion'] == 'Martes' ? 'selected' : '' ?>>Martes</option>
                <option value="Miércoles" <?= isset($celula) && $celula['Dia_Reunion'] == 'Miércoles' ? 'selected' : '' ?>>Miércoles</option>
                <option value="Jueves" <?= isset($celula) && $celula['Dia_Reunion'] == 'Jueves' ? 'selected' : '' ?>>Jueves</option>
                <option value="Viernes" <?= isset($celula) && $celula['Dia_Reunion'] == 'Viernes' ? 'selected' : '' ?>>Viernes</option>
                <option value="Sábado" <?= isset($celula) && $celula['Dia_Reunion'] == 'Sábado' ? 'selected' : '' ?>>Sábado</option>
                <option value="Domingo" <?= isset($celula) && $celula['Dia_Reunion'] == 'Domingo' ? 'selected' : '' ?>>Domingo</option>
            </select>
        </div>

        <div class="form-group">
            <label for="hora_reunion">Hora de Reunión</label>
            <input type="time" id="hora_reunion" name="hora_reunion" class="form-control" 
                   value="<?= htmlspecialchars($celula['Hora_Reunion'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="id_lider">Líder</label>
            <select id="id_lider" name="id_lider" class="form-control">
                <option value="">Sin líder asignado</option>
                <?php if (!empty($personas)): ?>
                    <?php foreach ($personas as $persona): ?>
                        <option value="<?= $persona['Id_Persona'] ?>" 
                                <?= isset($celula) && $celula['Id_Lider'] == $persona['Id_Persona'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=celulas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
