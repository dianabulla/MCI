<?php include VIEWS . '/layout/header.php'; ?>

<?php
$formularios = is_array($formularios ?? null) ? $formularios : [];
?>

<style>
.taller-graf-selector {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 16px;
    margin-top: 20px;
}
.taller-graf-selector-card {
    border: 1px solid #dbeafe;
    border-radius: 12px;
    padding: 20px;
    background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
}
.taller-graf-selector-card h3 {
    margin: 0 0 8px;
    font-size: 1.05rem;
    color: #0f172a;
}
.taller-graf-selector-card p {
    margin: 0 0 14px;
    font-size: 0.88rem;
    color: #64748b;
}
</style>

<div class="page-header">
    <h2>Gráficas de talleres</h2>
    <p class="text-muted" style="margin:4px 0 0;">Seleccione el formulario para ver las estadísticas del cuestionario.</p>
</div>

<?php if ($formularios === []): ?>
<div class="taller-resp-empty">No hay formularios disponibles para graficar.</div>
<?php else: ?>
<div class="taller-graf-selector">
    <?php foreach ($formularios as $f): ?>
        <?php
        $id = (int)($f['Id_Formulario'] ?? 0);
        $titulo = (string)($f['Titulo'] ?? 'Formulario');
        $totalResp = (int)($f['Total_Respuestas'] ?? 0);
        ?>
        <article class="taller-graf-selector-card">
            <h3><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= $totalResp ?> inscripción(es) registradas</p>
            <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>&tab=graficas" class="btn btn-primary btn-sm">
                <i class="bi bi-bar-chart-line"></i> Ver gráficas
            </a>
        </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php include VIEWS . '/layout/footer.php'; ?>
