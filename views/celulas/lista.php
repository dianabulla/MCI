<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Células</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=celulas/crear" class="btn btn-primary">+ Nueva Célula</a>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Líder</th>
                <th>Dirección</th>
                <th>Día de Reunión</th>
                <th>Hora</th>
                <th>Miembros</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($celulas)): ?>
                <?php foreach ($celulas as $celula): ?>
                    <tr>
                        <td><?= htmlspecialchars($celula['Nombre_Celula']) ?></td>
                        <td><?= htmlspecialchars($celula['Nombre_Lider'] ?? 'Sin líder') ?></td>
                        <td><?= htmlspecialchars($celula['Direccion_Celula']) ?></td>
                        <td><?= htmlspecialchars($celula['Dia_Reunion']) ?></td>
                        <td><?= htmlspecialchars($celula['Hora_Reunion']) ?></td>
                        <td><?= $celula['Total_Miembros'] ?></td>
                        <td>
                            <a href="<?= PUBLIC_URL ?>index.php?url=celulas/detalle&id=<?= $celula['Id_Celula'] ?>" class="btn btn-sm btn-info">Ver</a>
                            <a href="<?= PUBLIC_URL ?>index.php?url=celulas/editar&id=<?= $celula['Id_Celula'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="<?= PUBLIC_URL ?>index.php?url=celulas/eliminar&id=<?= $celula['Id_Celula'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta célula?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No hay células registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
