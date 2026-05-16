<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header" style="margin-bottom: 20px;">
    <h2 style="margin: 0;">Administración</h2>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <div class="page-actions personas-mobile-stack" style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=cuentas" class="btn btn-nav-pill">Cuentas</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=roles" class="btn btn-nav-pill active">Roles</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=permisos" class="btn btn-nav-pill">Permisos</a>
        </div>
    </div>
</div>

<div class="page-header">
    <h2><?= isset($rol) ? 'Editar' : 'Nuevo' ?> Rol</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=roles" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-group">
            <label for="nombre_rol">Nombre del Rol</label>
            <input type="text" id="nombre_rol" name="nombre_rol" class="form-control" 
                   value="<?= htmlspecialchars($rol['Nombre_Rol'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" class="form-control" rows="4" required><?= htmlspecialchars($rol['Descripcion'] ?? '') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=roles" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
