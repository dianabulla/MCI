<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Detalle de Célula</h2>
    <div>
        <?php if (AuthController::tienePermiso('asistencias', 'crear')): ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=asistencias/registrar&celula=<?= $celula['Id_Celula'] ?>" class="btn btn-sm btn-success">Asistencias</a>
        <?php endif; ?>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas/editar&id=<?= $celula['Id_Celula'] ?>" class="btn btn-sm btn-warning">Editar</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=celulas" class="btn btn-sm btn-secondary">Volver</a>
    </div>
</div>

<div class="detail-container">
    <div class="detail-section">
        <h3>Información de la Célula</h3>
        <div class="detail-grid">
            <div class="detail-item">
                <span class="detail-label">Nombre:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Nombre_Celula']) ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Dirección:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Direccion_Celula'] ?? 'No especificada') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Día de Reunión:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Dia_Reunion'] ?? 'No especificado') ?></span>
            </div>

            <div class="detail-item">
                <span class="detail-label">Hora de Reunión:</span>
                <span class="detail-value"><?= htmlspecialchars($celula['Hora_Reunion'] ?? 'No especificada') ?></span>
            </div>
        </div>
    </div>

    <div class="detail-section">
        <h3>Miembros de la Célula (<?= count($celula['miembros'] ?? []) ?>)</h3>
        
        <?php if (!empty($celula['miembros'])): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($celula['miembros'] as $miembro): ?>
                            <tr>
                                <td><?= htmlspecialchars($miembro['Nombre']) ?></td>
                                <td><?= htmlspecialchars($miembro['Apellido']) ?></td>
                                <td><?= htmlspecialchars($miembro['Telefono'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($miembro['Email'] ?? 'N/A') ?></td>
                                <td>
                                    <a href="<?= PUBLIC_URL ?>index.php?url=personas/detalle&id=<?= $miembro['Id_Persona'] ?>" class="btn btn-sm btn-info">Ver</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center" style="padding: 20px; color: #666;">No hay miembros registrados en esta célula</p>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
