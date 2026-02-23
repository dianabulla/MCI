<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Panel de Control</h2>
</div>

<div class="dashboard-grid">
    <div class="dashboard-card">
        <h3>Personas</h3>
        <div class="value"><?= $totalPersonas ?? 0 ?></div>
        <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-primary btn-sm">Ver todas</a>
    </div>

    <div class="dashboard-card" style="border-left-color: #28a745;">
        <h3>Células</h3>
        <div class="value" style="color: #28a745;"><?= $totalCelulas ?? 0 ?></div>
        <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-primary btn-sm">Ver todas</a>
    </div>

    <div class="dashboard-card" style="border-left-color: #17a2b8;">
        <h3>Ministerios</h3>
        <div class="value" style="color: #17a2b8;"><?= $totalMinisterios ?? 0 ?></div>
        <a href="<?= PUBLIC_URL ?>?url=ministerios" class="btn btn-primary btn-sm">Ver todos</a>
    </div>

    <div class="dashboard-card" style="border-left-color: #ffc107;">
        <h3>Próximos Eventos</h3>
        <div class="value" style="color: #ffc107;"><?= count($eventosProximos ?? []) ?></div>
        <a href="<?= PUBLIC_URL ?>?url=eventos" class="btn btn-primary btn-sm">Ver todos</a>
    </div>
</div>

<?php if (!empty($eventosProximos)): ?>
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
