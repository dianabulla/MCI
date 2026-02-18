<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Asistencias</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=asistencias/registrar" class="btn btn-primary">+ Registrar Asistencia</a>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Persona</th>
                <th>Célula</th>
                <th>Fecha</th>
                <th>Asistió</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($asistencias)): ?>
                <?php foreach ($asistencias as $asistencia): ?>
                    <tr>
                        <td><?= htmlspecialchars($asistencia['Nombre_Persona']) ?></td>
                        <td><?= htmlspecialchars($asistencia['Nombre_Celula']) ?></td>
                        <td><?= htmlspecialchars($asistencia['Fecha_Asistencia']) ?></td>
                        <td>
                            <span class="badge <?= $asistencia['Asistio'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $asistencia['Asistio'] ? 'Sí' : 'No' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay asistencias registradas</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
