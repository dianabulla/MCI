<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Ministerios</h2>
    <?php $puedeCrearMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'crear'); ?>
    <?php $puedeEditarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'editar'); ?>
    <?php $puedeEliminarMinisterio = AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'eliminar'); ?>
    <?php $puedeGestionarMinisterio = $puedeEditarMinisterio || $puedeEliminarMinisterio; ?>
    <?php if ($puedeCrearMinisterio): ?>
    <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/crear" class="btn btn-primary">+ Nuevo Ministerio</a>
    <?php endif; ?>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Total Miembros</th>
                <?php if ($puedeGestionarMinisterio): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ministerios)): ?>
                <?php foreach ($ministerios as $ministerio): ?>
                    <tr>
                        <td><?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?></td>
                        <td><?= htmlspecialchars($ministerio['Descripcion']) ?></td>
                        <td><?= $ministerio['Total_Miembros'] ?></td>
                        <?php if ($puedeGestionarMinisterio): ?>
                        <td>
                            <?php if ($puedeEditarMinisterio): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/editar&id=<?= $ministerio['Id_Ministerio'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarMinisterio): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/eliminar&id=<?= $ministerio['Id_Ministerio'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este ministerio?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay ministerios registrados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
