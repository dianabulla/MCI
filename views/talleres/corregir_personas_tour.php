<?php include VIEWS . '/layout/header.php'; ?>

<?php
$pendientes = is_array($pendientes ?? null) ? $pendientes : [];
$total = count($pendientes);
$aplicado = !empty($aplicado);
$mensaje = trim((string)($mensaje ?? ''));
$totalCorregidas = (int)($total_corregidas ?? 0);
?>

<div class="page-header">
    <h2>Corregir personas del Tour Levántate</h2>
    <p class="text-muted" style="margin:4px 0 0;">
        Marca como <strong>antiguas</strong> las fichas creadas por el tour que quedaron como almas nuevas en Ganar.
        No modifica las inscripciones del formulario.
    </p>
    <div style="margin-top:12px;">
        <a href="<?= PUBLIC_URL ?>?url=talleres" class="btn btn-outline-secondary btn-sm">← Volver a Talleres</a>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
<div class="alert alert-<?= $aplicado ? 'success' : 'info' ?>"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($total === 0 && !$aplicado): ?>
<div class="alert alert-success">
    No hay personas del Tour pendientes de corregir. Todo está al día.
</div>
<?php elseif ($total > 0 && !$aplicado): ?>
<div class="alert alert-warning">
    Se encontraron <strong><?= $total ?></strong> persona(s) creadas por el tour que aún aparecen como nuevas en Ganar.
</div>

<div class="table-container" style="margin-bottom:20px;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Id</th>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Teléfono</th>
                <th>Proceso actual</th>
                <th>Registro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pendientes as $row): ?>
            <tr>
                <td><?= (int)($row['Id_Persona'] ?? 0) ?></td>
                <td><?= htmlspecialchars(trim((string)($row['Nombre'] ?? '') . ' ' . (string)($row['Apellido'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['Numero_Documento'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['Telefono'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string)($row['Proceso'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(substr((string)($row['Fecha_Registro'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<form method="POST" action="<?= PUBLIC_URL ?>?url=talleres/corregir-personas-tour" onsubmit="return confirm('¿Aplicar la corrección a <?= $total ?> persona(s)? Las inscripciones del tour no se modificarán.');">
    <button type="submit" class="btn btn-primary">Aplicar corrección (<?= $total ?> persona<?= $total === 1 ? '' : 's' ?>)</button>
</form>
<?php elseif ($aplicado && $totalCorregidas > 0): ?>
<p class="text-muted">Puede volver a abrir esta página para verificar que ya no queden pendientes.</p>
<a href="<?= PUBLIC_URL ?>?url=talleres/corregir-personas-tour" class="btn btn-outline-primary btn-sm">Verificar de nuevo</a>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
