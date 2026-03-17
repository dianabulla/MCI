<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Actividad de Líderes de Célula</h2>
    <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-secondary">Volver al panel</a>
</div>

<div class="alert alert-info" style="margin-bottom: 20px;">
    <i class="bi bi-info-circle"></i>
    Resumen de actividad por líder: último ingreso, último reporte de célula y total de personas asignadas.
</div>

<div class="main-content">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Líder</th>
                    <th>Ministerio</th>
                    <th>Última vez que ingresó</th>
                    <th>Última vez que reportó célula</th>
                    <th>Personas asignadas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($lideres)): ?>
                    <?php foreach ($lideres as $lider): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''))) ?>
                            </td>
                            <td><?= htmlspecialchars((string)($lider['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                            <td>
                                <?php if (!empty($lider['Ultimo_Acceso'])): ?>
                                    <?= date('d/m/Y H:i', strtotime((string)$lider['Ultimo_Acceso'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin registro</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($lider['Ultimo_Reporte_Celula'])): ?>
                                    <?= date('d/m/Y', strtotime((string)$lider['Ultimo_Reporte_Celula'])) ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin reportes</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)($lider['Total_Personas'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay líderes de célula para mostrar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>