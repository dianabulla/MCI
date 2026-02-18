<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Roles</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=roles/crear" class="btn btn-primary">+ Nuevo Rol</a>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Total Personas</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($roles)): ?>
                <?php foreach ($roles as $rol): ?>
                    <tr>
                        <td><?= htmlspecialchars($rol['Nombre_Rol']) ?></td>
                        <td><?= htmlspecialchars($rol['Descripcion']) ?></td>
                        <td><?= $rol['Total_Personas'] ?></td>
                        <td>
                            <a href="<?= PUBLIC_URL ?>index.php?url=roles/editar&id=<?= $rol['Id_Rol'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="<?= PUBLIC_URL ?>index.php?url=roles/eliminar&id=<?= $rol['Id_Rol'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este rol?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay roles registrados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
