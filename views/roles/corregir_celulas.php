<?php
include VIEWS . '/layout/header.php';
$admin_nav_active = 'roles';
include VIEWS . '/partials/admin_nav.php';

$rolesIds = is_array($roles_ids ?? null) ? $roles_ids : [];
$plan = is_array($plan ?? null) ? $plan : [];
$lista12 = is_array($plan['lider_12'] ?? null) ? $plan['lider_12'] : [];
$lista144 = is_array($plan['lider_144'] ?? null) ? $plan['lider_144'] : [];
$listaCel = is_array($plan['lider_celula'] ?? null) ? $plan['lider_celula'] : [];
$conservarTotal = (int)($plan['conservar_total'] ?? 0);
$conservarMuestra = is_array($plan['conservar'] ?? null) ? $plan['conservar'] : [];
$mensaje = trim((string)($mensaje ?? ''));
$tipo = trim((string)($tipo ?? ''));
$idCelulas = (int)($rolesIds['id_celulas'] ?? 0);
$id12 = (int)($rolesIds['id_lider_12'] ?? 0);
$id144 = (int)($rolesIds['id_lider_144'] ?? 0);
$idCel = (int)($rolesIds['id_lider_celula'] ?? 0);
$nombreCelulas = (string)($rolesIds['nombre_celulas'] ?? 'Células');
$nombre12 = (string)($rolesIds['nombre_lider_12'] ?? 'Líder de 12');
$nombre144 = (string)($rolesIds['nombre_lider_144'] ?? 'Líder de 144');
$nombreCel = (string)($rolesIds['nombre_lider_celula'] ?? 'Líder de célula');
$totalAplicar = count($lista12) + count($lista144) + count($listaCel);
$puedeAplicar = $totalAplicar > 0 && $idCel > 0 && ($lista12 === [] || $id12 > 0) && ($lista144 === [] || $id144 > 0);

$renderTabla = static function (array $filas, bool $mostrarCelula): void {
    if ($filas === []) {
        echo '<p>Ninguno.</p>';
        return;
    }
    echo '<div class="table-container"><table class="data-table"><thead><tr>';
    echo '<th>Persona</th><th>Documento</th>';
    if ($mostrarCelula) {
        echo '<th>Célula(s)</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($filas as $fila) {
        $nombre = htmlspecialchars(trim((string)($fila['Nombre'] ?? '') . ' ' . (string)($fila['Apellido'] ?? '')), ENT_QUOTES, 'UTF-8');
        $doc = htmlspecialchars((string)($fila['Numero_Documento'] ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<tr><td>' . $nombre . '</td><td>' . $doc . '</td>';
        if ($mostrarCelula) {
            echo '<td>' . htmlspecialchars((string)($fila['Nombres_Celula'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
};
?>

<div class="page-header">
    <h2>Corregir rol Células (ministerial)</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=roles" class="btn btn-secondary">Volver a Roles</a>
</div>

<?php if ($mensaje !== ''): ?>
<div class="alert alert-<?= $tipo === 'error' ? 'danger' : ($tipo === 'success' ? 'success' : 'info') ?>">
    <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <p><strong><?= htmlspecialchars($nombreCelulas, ENT_QUOTES, 'UTF-8') ?></strong> es administrativo. Esta pantalla devuelve el rol ministerial según la red:</p>
        <ul>
            <li>Aparece como líder inmediato de células → <strong><?= htmlspecialchars($nombre12, ENT_QUOTES, 'UTF-8') ?></strong></li>
            <li>Dirige célula (o está bajo un 12) → <strong><?= htmlspecialchars($nombre144, ENT_QUOTES, 'UTF-8') ?></strong></li>
            <li>Dirige célula y no encaja arriba → <strong><?= htmlspecialchars($nombreCel, ENT_QUOTES, 'UTF-8') ?></strong></li>
        </ul>
        <p style="margin-bottom:0;">Quien no tenga esa red se deja en Células (staff).</p>
        <ul>
            <li>Células: <?= $idCelulas > 0 ? '#' . $idCelulas : 'no encontrado' ?></li>
            <li>Líder de 12: <?= $id12 > 0 ? '#' . $id12 . ' — ' . htmlspecialchars($nombre12, ENT_QUOTES, 'UTF-8') : 'no encontrado (créalo si hay gente en esa lista)' ?></li>
            <li>Líder de 144: <?= $id144 > 0 ? '#' . $id144 . ' — ' . htmlspecialchars($nombre144, ENT_QUOTES, 'UTF-8') : 'no encontrado (créalo si hay gente en esa lista)' ?></li>
            <li>Líder de célula: <?= $idCel > 0 ? '#' . $idCel . ' — ' . htmlspecialchars($nombreCel, ENT_QUOTES, 'UTF-8') : 'no encontrado' ?></li>
        </ul>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <h3 style="margin-top:0;">A líder de 12 (<?= count($lista12) ?>)</h3>
        <?php $renderTabla($lista12, false); ?>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <h3 style="margin-top:0;">A líder de 144 (<?= count($lista144) ?>)</h3>
        <?php $renderTabla($lista144, true); ?>
    </div>
</div>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <h3 style="margin-top:0;">A líder de célula (<?= count($listaCel) ?>)</h3>
        <?php $renderTabla($listaCel, true); ?>
    </div>
</div>

<?php if ($totalAplicar > 0): ?>
<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <?php if (!$puedeAplicar): ?>
            <div class="alert alert-warning">Falta crear en Roles el ministerial que falta (12, 144 o líder de célula). Sin ese nombre el botón no aplica.</div>
        <?php else: ?>
            <form method="post" action="<?= PUBLIC_URL ?>index.php?url=herramientas/corregir-rol-celulas/aplicar">
                <label style="display:flex; gap:8px; align-items:flex-start; margin-bottom:12px;">
                    <input type="checkbox" name="confirmar" value="1" required>
                    <span>Revisé los tres listados y quiero corregir <?= (int)$totalAplicar ?> ficha(s).</span>
                </label>
                <button type="submit" class="btn btn-primary">Aplicar corrección</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h3 style="margin-top:0;">Se dejan con Células (<?= $conservarTotal ?>)</h3>
        <p>No aparecen como líder inmediato, no dirigen célula y no están bajo un líder de 12.</p>
        <?php $renderTabla($conservarMuestra, false); ?>
        <?php if ($conservarTotal > count($conservarMuestra)): ?>
            <p>Se muestran las primeras <?= count($conservarMuestra) ?> de <?= $conservarTotal ?>.</p>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
