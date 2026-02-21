<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Peticiones de Oración</h2>
    <?php $puedeCrearPeticion = AuthController::esAdministrador() || AuthController::tienePermiso('peticiones', 'crear'); ?>
    <?php $puedeEditarPeticion = AuthController::esAdministrador() || AuthController::tienePermiso('peticiones', 'editar'); ?>
    <?php $puedeEliminarPeticion = AuthController::esAdministrador() || AuthController::tienePermiso('peticiones', 'eliminar'); ?>
    <?php $puedeGestionarPeticion = $puedeEditarPeticion || $puedeEliminarPeticion; ?>
    <?php if ($puedeCrearPeticion): ?>
    <a href="<?= PUBLIC_URL ?>index.php?url=peticiones/crear" class="btn btn-primary">+ Nueva Petición</a>
    <?php endif; ?>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Persona</th>
                <th>Petición</th>
                <th>Fecha</th>
                <th>Estado</th>
                <?php if ($puedeGestionarPeticion): ?><th>Acciones</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($peticiones)): ?>
                <?php foreach ($peticiones as $peticion): ?>
                    <tr>
                        <td><?= htmlspecialchars($peticion['Nombre_Completo']) ?></td>
                        <td><?= htmlspecialchars($peticion['Descripcion_Peticion']) ?></td>
                        <td><?= htmlspecialchars($peticion['Fecha_Peticion']) ?></td>
                        <td>
                            <span class="badge <?= $peticion['Estado_Peticion'] == 'Pendiente' ? 'badge-warning' : 'badge-success' ?>">
                                <?= htmlspecialchars($peticion['Estado_Peticion']) ?>
                            </span>
                        </td>
                        <?php if ($puedeGestionarPeticion): ?>
                        <td>
                            <?php if ($puedeEditarPeticion): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=peticiones/editar&id=<?= $peticion['Id_Peticion'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if ($puedeEliminarPeticion): ?>
                            <a href="<?= PUBLIC_URL ?>index.php?url=peticiones/eliminar&id=<?= $peticion['Id_Peticion'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta petición?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No hay peticiones registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
