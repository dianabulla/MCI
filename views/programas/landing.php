<?php include VIEWS . '/layout/header.php'; ?>

<?php
$submodulosProgramas = is_array($submodulosProgramas ?? null) ? $submodulosProgramas : [];
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center; margin-bottom: 16px;">
    <div>
        <h2 style="margin:0;">Programas</h2>
        <small style="color:#637087;">Selecciona Universidad de la Vida o Capacitación Destino. Usa el menú lateral si necesitas otra sección del sistema.</small>
    </div>
</div>

<div class="programas-grid">
    <?php foreach ($submodulosProgramas as $submodulo): ?>
    <a
        href="<?= htmlspecialchars((string)($submodulo['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
        class="programas-card"
        style="--programa-gradiente: <?= htmlspecialchars((string)($submodulo['gradiente'] ?? 'linear-gradient(135deg, #1e4a89 0%, #3f73be 100%)'), ENT_QUOTES, 'UTF-8') ?>;"
    >
        <div class="programas-card-head">
            <span class="programas-card-icon"><i class="<?= htmlspecialchars((string)($submodulo['icono'] ?? 'bi bi-grid-1x2-fill')) ?>"></i></span>
            <span class="programas-card-arrow"><i class="bi bi-arrow-up-right"></i></span>
        </div>

        <h3><?= htmlspecialchars((string)($submodulo['titulo'] ?? 'Submódulo')) ?></h3>
        <p><?= htmlspecialchars((string)($submodulo['descripcion'] ?? '')) ?></p>

        <div class="programas-card-footer">
            <span class="programas-chip">Abrir registro</span>
            <span class="programas-chip">Ver consolidado</span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<style>
.programas-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 18px;
}

.programas-card {
    position: relative;
    display: block;
    text-decoration: none;
    border-radius: 18px;
    padding: 20px;
    color: #10223d;
    background: #ffffff;
    border: 1px solid #d7e3f4;
    box-shadow: 0 10px 26px rgba(22, 46, 79, 0.12);
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease;
}

.programas-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--programa-gradiente);
    opacity: .08;
    pointer-events: none;
}

.programas-card:hover,
.programas-card:focus-visible {
    transform: translateY(-3px);
    box-shadow: 0 16px 34px rgba(22, 46, 79, 0.18);
}

.programas-card-head,
.programas-card h3,
.programas-card p,
.programas-card-footer {
    position: relative;
    z-index: 1;
}

.programas-card-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.programas-card-icon {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #ffffff;
    background: var(--programa-gradiente);
}

.programas-card-arrow {
    color: #2d4d79;
    font-size: 1rem;
}

.programas-card h3 {
    margin: 0 0 8px;
    font-size: 1.12rem;
}

.programas-card p {
    margin: 0;
    color: #4d6281;
    line-height: 1.45;
    min-height: 44px;
}

.programas-card-footer {
    margin-top: 14px;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.programas-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 5px 10px;
    font-size: .77rem;
    font-weight: 600;
    color: #2a3f60;
    background: #eef3fb;
}

</style>
