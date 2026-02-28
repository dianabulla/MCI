<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Roles</h2>
    <?php $puedeCrearRoles = AuthController::esAdministrador() || AuthController::tienePermiso('roles', 'crear'); ?>
    <?php $puedeEditarRoles = AuthController::esAdministrador() || AuthController::tienePermiso('roles', 'editar'); ?>
    <?php $puedeEliminarRoles = AuthController::esAdministrador() || AuthController::tienePermiso('roles', 'eliminar'); ?>
    <?php $puedeGestionarRoles = $puedeEditarRoles || $puedeEliminarRoles; ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=roles/exportarExcel" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if ($puedeCrearRoles): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=roles/crear" class="btn btn-primary">+ Nuevo Rol</a>
        <?php endif; ?>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Total Personas</th>
                <?php if ($puedeGestionarRoles): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($roles)): ?>
                <?php foreach ($roles as $rol): ?>
                    <tr>
                        <td><?= htmlspecialchars($rol['Nombre_Rol']) ?></td>
                        <td><?= htmlspecialchars($rol['Descripcion']) ?></td>
                        <td><?= $rol['Total_Personas'] ?></td>
                        <?php if ($puedeGestionarRoles): ?>
                        <td>
                            <?php if ($puedeEditarRoles): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=roles/editar&id=<?= $rol['Id_Rol'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarRoles): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=roles/eliminar&id=<?= $rol['Id_Rol'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este rol?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
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
