<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Panel de Control</h2>
</div>

<div class="dashboard-grid">
    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver')): ?>
    <div class="dashboard-card">
        <h3>Ganar</h3>
        <div class="value"><?= $totalPersonas ?? 0 ?></div>
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-primary btn-sm">Ver todas</a>
    </div>
    <?php endif; ?>

    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver')): ?>
    <div class="dashboard-card" style="border-left-color: #1e4a89;">
        <h3>Consolidar</h3>
        <div class="value" style="color: #1e4a89;"><?= (int)($totalConsolidar ?? 0) ?></div>
        <a href="<?= PUBLIC_URL ?>?url=home/consolidar" class="btn btn-primary btn-sm">Ver modulo</a>
    </div>
    <?php endif; ?>

    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('personas', 'ver')): ?>
    <div class="dashboard-card" style="border-left-color: #7a4e08;">
        <h3>Discipular</h3>
        <div class="value" style="color: #7a4e08;"><?= (int)($totalDiscipular ?? 0) ?></div>
        <a href="<?= PUBLIC_URL ?>?url=home/discipular" class="btn btn-primary btn-sm">Ver modulo</a>
    </div>
    <?php endif; ?>

    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('celulas', 'ver')): ?>
    <div class="dashboard-card" style="border-left-color: #28a745;">
        <h3>Enviar</h3>
        <div class="value" style="color: #28a745;"><?= $totalCelulas ?? 0 ?></div>
        <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-primary btn-sm">Ver todas</a>
    </div>
    <?php endif; ?>

    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('materiales_celulas', 'ver') || AuthController::tienePermiso('teen', 'ver') || AuthController::tienePermiso('eventos', 'ver')): ?>
    <div class="dashboard-card" style="border-left-color: #fd7e14;">
        <h3>Material</h3>
        <div class="value" style="color: #fd7e14;">📘</div>
        <a href="<?= PUBLIC_URL ?>?url=home/material" class="btn btn-primary btn-sm">Abrir materiales</a>
    </div>
    <?php endif; ?>

    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('ministerios', 'ver')): ?>
    <div class="dashboard-card" style="border-left-color: #17a2b8;">
        <h3>Ministerios</h3>
        <div class="value" style="color: #17a2b8;"><?= $totalMinisterios ?? 0 ?></div>
        <a href="<?= PUBLIC_URL ?>?url=ministerios" class="btn btn-primary btn-sm">Ver todos</a>
    </div>
    <?php endif; ?>

    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('teen', 'ver')): ?>
    <div class="dashboard-card" style="border-left-color: #e83e8c;">
        <h3>Teens</h3>
        <div class="value" style="color: #e83e8c;">📚</div>
        <a href="<?= PUBLIC_URL ?>?url=teen/registro-menores" class="btn btn-primary btn-sm">Abrir Teens</a>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($eventosProximos) && (AuthController::esAdministrador() || AuthController::tienePermiso('eventos', 'ver'))): ?>
<div class="main-content" style="margin-top: 30px;">
    <h3>Próximos Eventos</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th>Evento</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Lugar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($eventosProximos as $evento): ?>
            <tr>
                <td><?= htmlspecialchars($evento['Nombre_Evento']) ?></td>
                <td><?= date('d/m/Y', strtotime($evento['Fecha_Evento'])) ?></td>
                <td><?= $evento['Hora_Evento'] ? date('h:i A', strtotime($evento['Hora_Evento'])) : 'No especificada' ?></td>
                <td><?= htmlspecialchars($evento['Lugar_Evento'] ?? 'No especificado') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
