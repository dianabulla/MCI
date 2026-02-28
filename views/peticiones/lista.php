<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Peticiones de Oración</h2>
    <?php $puedeCrearPeticion = AuthController::esAdministrador() || AuthController::tienePermiso('peticiones', 'crear'); ?>
    <?php $puedeEditarPeticion = AuthController::esAdministrador() || AuthController::tienePermiso('peticiones', 'editar'); ?>
    <?php $puedeEliminarPeticion = AuthController::esAdministrador() || AuthController::tienePermiso('peticiones', 'eliminar'); ?>
    <?php $puedeGestionarPeticion = $puedeEditarPeticion || $puedeEliminarPeticion; ?>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=peticiones/exportarExcel<?= !empty($_GET['celula']) ? '&celula=' . urlencode((string)$_GET['celula']) : '' ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
        </a>
        <?php if ($puedeCrearPeticion): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=peticiones/crear" class="btn btn-primary">+ Nueva Petición</a>
        <?php endif; ?>
    </div>
</div>

<div class="form-container" style="margin-bottom: 20px;">
    <form method="GET" class="filter-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; align-items: end;">
        <input type="hidden" name="url" value="peticiones">

        <div class="form-group" style="margin-bottom: 0;">
            <label for="filtro_celula">Filtrar por Célula</label>
            <select id="filtro_celula" name="celula" class="form-control">
                <option value="">Todas</option>
                <option value="0" <?= ((string)($filtro_celula_actual ?? '') === '0') ? 'selected' : '' ?>>Sin célula</option>
                <?php foreach (($celulas_disponibles ?? []) as $celula): ?>
                    <option value="<?= (int)$celula['Id_Celula'] ?>" <?= ((string)$filtro_celula_actual === (string)$celula['Id_Celula']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($celula['Nombre_Celula']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="margin-bottom: 0; display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">Filtrar</button>
            <a href="<?= PUBLIC_URL ?>?url=peticiones" class="btn btn-secondary">Limpiar</a>
        </div>
    </form>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Persona</th>
                <th>Célula</th>
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
                        <td><?= htmlspecialchars($peticion['Nombre_Celula'] ?? 'Sin célula') ?></td>
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
                    <td colspan="6" class="text-center">No hay peticiones registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
