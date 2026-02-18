<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Eventos</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=eventos/crear" class="btn btn-primary">+ Nuevo Evento</a>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Lugar</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($eventos)): ?>
                <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= htmlspecialchars($evento['Nombre_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Fecha_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Hora_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Lugar_Evento']) ?></td>
                        <td><?= htmlspecialchars($evento['Descripcion_Evento']) ?></td>
                        <td>
                            <a href="<?= PUBLIC_URL ?>index.php?url=eventos/editar&id=<?= $evento['Id_Evento'] ?>" class="btn btn-sm btn-warning">Editar</a>
                            <a href="<?= PUBLIC_URL ?>index.php?url=eventos/eliminar&id=<?= $evento['Id_Evento'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este evento?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No hay eventos registrados</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
