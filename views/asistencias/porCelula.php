<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Asistencias por Célula</h2>
    <div>
        <a href="<?= PUBLIC_URL ?>?url=asistencias" class="btn btn-secondary">← Volver</a>
        <?php if (!empty($celula['Id_Celula'])): ?>
            <a href="<?= PUBLIC_URL ?>?url=asistencias/registrar&celula=<?= (int)$celula['Id_Celula'] ?>" class="btn btn-primary">+ Registrar Asistencia</a>
        <?php endif; ?>
    </div>
</div>

<div class="form-container" style="margin-bottom: 20px;">
    <p style="margin: 0; color: #555;">
        <strong>Célula:</strong> <?= htmlspecialchars($celula['Nombre_Celula'] ?? 'Sin nombre') ?>
    </p>
</div>

<div class="table-responsive ministerio-table-wrap">
    <table class="table table-hover ministerio-detail-table">
        <thead>
            <tr>
                <th style="width: 70px;">Nro</th>
                <th>Persona</th>
                <th style="width: 160px;">Fecha</th>
                <th style="width: 110px;">Asistió</th>
                <th>Tema</th>
                <th style="width: 160px;">Tipo Célula</th>
                <th>Observaciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($asistencias)): ?>
                <?php $nro = 1; ?>
                <?php foreach ($asistencias as $asistencia): ?>
                    <tr>
                        <td><?= $nro++ ?></td>
                        <td>
                            <?php if ((int)($asistencia['Id_Persona'] ?? 0) > 0): ?>
                                <a class="group-link" href="<?= PUBLIC_URL ?>?url=personas/detalle&id=<?= (int)$asistencia['Id_Persona'] ?>">
                                    <?= htmlspecialchars($asistencia['Nombre_Persona'] ?? 'Sin nombre') ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($asistencia['Nombre_Persona'] ?? 'Sin nombre') ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($asistencia['Fecha_Asistencia'] ?? '') ?></td>
                        <td>
                            <?php $asistio = (int)($asistencia['Asistio'] ?? 0) === 1; ?>
                            <span class="badge <?= $asistio ? 'badge-success' : 'badge-danger' ?>">
                                <?= $asistio ? 'Sí' : 'No' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($asistencia['Tema'] ?? '') ?></td>
                        <td><?= htmlspecialchars($asistencia['Tipo_Celula'] ?? '') ?></td>
                        <td><?= htmlspecialchars($asistencia['Observaciones'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="text-center">No hay asistencias registradas para esta célula</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
