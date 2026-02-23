<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><?= isset($ministerio) ? 'Editar' : 'Nuevo' ?> Ministerio</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=ministerios" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-group">
            <label for="nombre_ministerio">Nombre del Ministerio</label>
            <input type="text" id="nombre_ministerio" name="nombre_ministerio" class="form-control" 
                   value="<?= htmlspecialchars($ministerio['Nombre_Ministerio'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="4" required><?= htmlspecialchars($ministerio['Descripcion'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
