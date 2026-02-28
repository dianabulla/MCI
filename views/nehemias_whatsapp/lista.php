<?php include VIEWS . '/layout/header.php'; ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center module-header-card">
        <h2 class="page-title">
            <i class="bi bi-whatsapp"></i> Campañas WhatsApp
        </h2>
        <div>
            <a href="?url=nehemias/lista" class="btn btn-secondary btn-action me-2">
                <i class="bi bi-arrow-left"></i> Volver a Nehemías
            </a>
            <a href="?url=nehemias/whatsapp-campanas/procesar-cola&limite=50" class="btn btn-primary btn-action me-2"
               onclick="return confirm('¿Procesar ahora un lote de 50 mensajes?')">
                <i class="bi bi-send"></i> Procesar lote
            </a>
            <a href="?url=nehemias/whatsapp-campanas/procesar-cola&limite=20&dry_run=1" class="btn btn-warning btn-action me-2"
               onclick="return confirm('¿Ejecutar simulación (dry-run) de 20 mensajes?')">
                <i class="bi bi-bezier"></i> Simular lote
            </a>
            <a href="?url=nehemias/whatsapp-campanas/crear" class="btn btn-success btn-action">
                <i class="bi bi-plus-circle"></i> Nueva Campaña
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-<?= ($tipo ?? '') === 'error' ? 'danger' : 'success' ?>" style="margin-top: 15px;">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <div class="card mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-no-card">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Campaña</th>
                            <th>Programada</th>
                            <th>Estado</th>
                            <th>Total Cola</th>
                            <th>Pendientes</th>
                            <th>Enviados</th>
                            <th>Fallidos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($campanas)): ?>
                            <?php foreach ($campanas as $campana): ?>
                                <tr>
                                    <td><?= (int)$campana['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($campana['nombre']) ?></strong><br>
                                        <small class="text-muted">Plantilla: <?= htmlspecialchars($campana['plantilla_nombre'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($campana['fecha_programada']) ?></td>
                                    <td><?= htmlspecialchars($campana['estado']) ?></td>
                                    <td><?= (int)($campana['total_cola'] ?? 0) ?></td>
                                    <td><?= (int)($campana['pendientes'] ?? 0) ?></td>
                                    <td><?= (int)($campana['enviados'] ?? 0) ?></td>
                                    <td><?= (int)($campana['fallidos'] ?? 0) ?></td>
                                    <td>
                                        <a href="?url=nehemias/whatsapp-campanas/generar-cola&id=<?= (int)$campana['id'] ?>"
                                           class="btn btn-sm btn-primary"
                                           onclick="return confirm('¿Generar/actualizar cola para esta campaña?')">
                                            Generar cola
                                        </a>
                                        <?php if ((int)($campana['fallidos'] ?? 0) > 0): ?>
                                            <a href="?url=nehemias/whatsapp-campanas/reintentar-fallidos&id=<?= (int)$campana['id'] ?>"
                                               class="btn btn-sm btn-warning"
                                               onclick="return confirm('¿Reintentar solo los mensajes fallidos de esta campaña?')">
                                                Reintentar fallidos
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">No hay campañas registradas</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
