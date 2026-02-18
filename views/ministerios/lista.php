<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Ministerios</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/crear" class="btn btn-primary">+ Nuevo Ministerio</a>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Total Miembros</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ministerios)): ?>
                <?php foreach ($ministerios as $ministerio): ?>
                    <tr>
                        <td><?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?></td>
                        <td><?= htmlspecialchars($ministerio['Descripcion']) ?></td>
                        <td><?= $ministerio['Total_Miembros'] ?></td>
                        <td>
                            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/editar&id=<?= $ministerio['Id_Ministerio'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="<?= PUBLIC_URL ?>index.php?url=ministerios/eliminar&id=<?= $ministerio['Id_Ministerio'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este ministerio?')">Eliminar</a>
                        </td>
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
