<?php
require_once APP . '/Helpers/PermisosProgramasAccess.php';
include VIEWS . '/layout/header.php';

require_once APP . '/Helpers/ProgramasNavegacion.php';
ProgramasNavegacion::incluirPartial(['modo' => 'hub']);

$submodulosProgramas = is_array($submodulosProgramas ?? null) ? $submodulosProgramas : [];
$resumenProgramas = is_array($resumenProgramas ?? null) ? $resumenProgramas : [];
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center; margin-bottom: 16px;">
    <div>
        <h2 style="margin:0;">Programas</h2>
        <small style="color:#637087;">Solo aparecen los accesos que tu rol tiene permitidos. Los datos se limitan a personas vinculadas a tu usuario.</small>
    </div>
</div>

<?php if (!empty($submodulosProgramas)): ?>
<div class="programas-resumen-dash card" style="margin-bottom:18px; padding:16px;">
    <h3 style="margin:0 0 12px; font-size:1rem;">Resumen (tu alcance)</h3>
    <div class="programas-resumen-grid">
        <?php foreach ($submodulosProgramas as $sub):
            $clave = (string)($sub['clave'] ?? '');
            $total = (int)($resumenProgramas[$clave] ?? 0);
        ?>
        <div class="programas-resumen-item">
            <span class="programas-resumen-label"><?= htmlspecialchars((string)($sub['titulo'] ?? '')) ?></span>
            <strong class="programas-resumen-value"><?= $total ?></strong>
            <span class="programas-resumen-hint">inscritos visibles</span>
            <?php if (!empty($sub['puede_dashboard']) && !empty($sub['dashboard_href'])): ?>
            <a href="<?= htmlspecialchars((string)$sub['dashboard_href'], ENT_QUOTES, 'UTF-8') ?>" class="programas-resumen-link">Dashboard</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<div class="programas-grid">
    <?php foreach ($submodulosProgramas as $submodulo): ?>
    <article class="programas-card" style="--programa-gradiente: <?= htmlspecialchars((string)($submodulo['gradiente'] ?? 'linear-gradient(135deg, #1e4a89 0%, #3f73be 100%)'), ENT_QUOTES, 'UTF-8') ?>;">
        <div class="programas-card-head">
            <span class="programas-card-icon"><i class="<?= htmlspecialchars((string)($submodulo['icono'] ?? 'bi bi-grid-1x2-fill')) ?>"></i></span>
        </div>

        <h3><?= htmlspecialchars((string)($submodulo['titulo'] ?? 'Submódulo')) ?></h3>
        <p><?= htmlspecialchars((string)($submodulo['descripcion'] ?? '')) ?></p>

        <div class="programas-card-footer">
            <?php if (!empty($submodulo['puede_consolidar']) && !empty($submodulo['href'])): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['href'], ENT_QUOTES, 'UTF-8') ?>" class="programas-chip">Consolidado</a>
            <?php endif; ?>
            <?php if (!empty($submodulo['puede_asistencias']) && !empty($submodulo['asistencias_href'])): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['asistencias_href'], ENT_QUOTES, 'UTF-8') ?>" class="programas-chip">Asistencias</a>
            <?php endif; ?>
            <?php if (!empty($submodulo['puede_dashboard']) && !empty($submodulo['dashboard_href'])): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['dashboard_href'], ENT_QUOTES, 'UTF-8') ?>" class="programas-chip">Dashboard</a>
            <?php endif; ?>
            <?php if (!empty($submodulo['puede_pagos']) && !empty($submodulo['pagos_href'])): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['pagos_href'], ENT_QUOTES, 'UTF-8') ?>" class="programas-chip">Pagos</a>
            <?php endif; ?>
            <?php if (!empty($submodulo['puede_formulario']) && !empty($submodulo['formulario_href'])): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['formulario_href'], ENT_QUOTES, 'UTF-8') ?>" class="programas-chip" target="_blank" rel="noopener">Formulario</a>
            <?php endif; ?>
            <?php if (!empty($submodulo['puede_material']) && !empty($submodulo['material_href'])): ?>
            <a href="<?= htmlspecialchars((string)$submodulo['material_href'], ENT_QUOTES, 'UTF-8') ?>" class="programas-chip">Material</a>
            <?php endif; ?>
        </div>
    </article>
    <?php endforeach; ?>
</div>

<?php if (empty($submodulosProgramas)): ?>
<div class="alert alert-warning">
    No tienes ninguna línea de Programas activa. En <strong>Permisos → Programas → Acciones permitidas</strong> active al menos
    «Ver consolidado Universidad de la Vida» o «Ver consolidado Capacitación Destino», y las acciones que necesite (dashboard, pagos, formulario, etc.).
</div>
<?php endif; ?>

<style>
.programas-resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; }
.programas-resumen-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
.programas-resumen-label { display: block; font-size: 13px; color: #64748b; }
.programas-resumen-value { display: block; font-size: 1.6rem; color: #1e3a8a; margin: 4px 0; }
.programas-resumen-hint { font-size: 12px; color: #94a3b8; }
.programas-resumen-link { display: inline-block; margin-top: 8px; font-size: 13px; font-weight: 600; }
.programas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; }
.programas-card { position: relative; border-radius: 18px; padding: 20px; background: #fff; border: 1px solid #d7e3f4; box-shadow: 0 10px 26px rgba(22, 46, 79, 0.12); overflow: hidden; }
.programas-card::before { content: ''; position: absolute; inset: 0; background: var(--programa-gradiente); opacity: .08; pointer-events: none; }
.programas-card-head { position: relative; z-index: 1; margin-bottom: 12px; }
.programas-card-icon { width: 46px; height: 46px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; background: var(--programa-gradiente); }
.programas-card h3, .programas-card p, .programas-card-footer { position: relative; z-index: 1; }
.programas-card h3 { margin: 0 0 8px; font-size: 1.12rem; }
.programas-card p { margin: 0; color: #4d6281; line-height: 1.45; min-height: 44px; }
.programas-card-footer { margin-top: 14px; display: flex; flex-wrap: wrap; gap: 8px; }
.programas-chip { display: inline-block; padding: 6px 10px; border-radius: 999px; background: #eef4ff; color: #1e4a89; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid #c7d9f5; }
.programas-chip:hover { background: #dbeafe; }
</style>

<?php require_once VIEWS . '/layout/footer.php'; ?>
