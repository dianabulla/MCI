<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Personas</h2>
    <?php if (AuthController::tienePermiso('personas', 'crear')): ?>
    <a href="<?= PUBLIC_URL ?>?url=personas/crear" class="btn btn-primary">+ Nueva Persona</a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="card" style="margin-bottom: 20px;">
    <div class="card-body">
        <form method="GET" action="<?= PUBLIC_URL ?>" style="display: flex; gap: 15px; align-items: end;">
            <input type="hidden" name="url" value="personas">
            
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label for="filtro_ministerio" style="font-size: 14px; margin-bottom: 5px;">Ministerio</label>
                <select id="filtro_ministerio" name="ministerio" class="form-control">
                    <option value="">Todos los ministerios</option>
                    <option value="0" <?= (isset($_GET['ministerio']) && $_GET['ministerio'] === '0') ? 'selected' : '' ?>>
                        Sin ministerio
                    </option>
                    <?php if (!empty($ministerios)): ?>
                        <?php foreach ($ministerios as $ministerio): ?>
                            <option value="<?= $ministerio['Id_Ministerio'] ?>" 
                                    <?= (isset($_GET['ministerio']) && $_GET['ministerio'] == $ministerio['Id_Ministerio']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label for="filtro_lider" style="font-size: 14px; margin-bottom: 5px;">Líder</label>
                <select id="filtro_lider" name="lider" class="form-control">
                    <option value="">Todos los líderes</option>
                    <option value="0" <?= (isset($_GET['lider']) && $_GET['lider'] === '0') ? 'selected' : '' ?>>
                        Sin líder
                    </option>
                    <?php if (!empty($lideres)): ?>
                        <?php foreach ($lideres as $lider): ?>
                            <option value="<?= $lider['Id_Persona'] ?>" 
                                    <?= (isset($_GET['lider']) && $_GET['lider'] == $lider['Id_Persona']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lider['Nombre'] . ' ' . $lider['Apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <?php if (!empty($_GET['ministerio']) || !empty($_GET['lider'])): ?>
                <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($_GET['ministerio']) || !empty($_GET['lider'])): ?>
<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-info-circle"></i> 
    Mostrando resultados filtrados. Total: <strong><?= count($personas) ?></strong> persona(s)
</div>
<?php endif; ?>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre Completo</th>
                <th>Email</th>
                <th>Teléfono</th>
                <th>Célula</th>
                <th>Rol</th>
                <th>Ministerio</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($personas)): ?>
                <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td><?= htmlspecialchars($persona['Nombre'] . ' ' . $persona['Apellido']) ?></td>
                        <td><?= htmlspecialchars($persona['Email'] ?? '') ?></td>
                        <td><?= htmlspecialchars($persona['Telefono'] ?? '') ?></td>
                        <td><?= htmlspecialchars($persona['Nombre_Celula'] ?? 'Sin célula') ?></td>
                        <td><?= htmlspecialchars($persona['Nombre_Rol'] ?? 'Sin rol') ?></td>
                        <td><?= htmlspecialchars($persona['Nombre_Ministerio'] ?? 'Sin ministerio') ?></td>
                        <td>
                            <?php 
                            $estado = $persona['Estado_Cuenta'] ?? 'Activo';
                            $badgeClass = $estado == 'Activo' ? 'badge-success' : ($estado == 'Inactivo' ? 'badge-secondary' : 'badge-danger');
                            ?>
                            <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                        </td>
                        <td>
                            <?php if (AuthController::tienePermiso('personas', 'ver')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-info">Ver</a>
                            <?php endif; ?>
                            <?php if (AuthController::tienePermiso('personas', 'editar')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/editar&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <?php endif; ?>
                            <?php if (AuthController::tienePermiso('personas', 'eliminar')): ?>
                            <a href="<?= PUBLIC_URL ?>?url=personas/eliminar&id=<?= $persona['Id_Persona'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta persona?')">Eliminar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No hay personas registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
