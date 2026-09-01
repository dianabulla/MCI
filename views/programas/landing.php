<?php
require_once APP . '/Helpers/PermisosProgramasAccess.php';
include VIEWS . '/layout/header.php';

$submodulosProgramas = is_array($submodulosProgramas ?? null) ? $submodulosProgramas : [];
$lineaMenu = strtolower(trim((string)($_GET['linea'] ?? '')));
if (!in_array($lineaMenu, ['universidad_vida', 'capacitacion_destino'], true)) {
    $lineaMenu = '';
}

$clavesPermitidas = array_column($submodulosProgramas, 'clave');
if ($lineaMenu !== '' && !in_array($lineaMenu, $clavesPermitidas, true)) {
    $lineaMenu = '';
}

$submoduloActivo = null;
if ($lineaMenu !== '') {
    foreach ($submodulosProgramas as $sub) {
        if ((string)($sub['clave'] ?? '') === $lineaMenu) {
            $submoduloActivo = $sub;
            break;
        }
    }
}

require_once APP . '/Helpers/ProgramasNavegacion.php';
ProgramasNavegacion::incluirPartial([
    'modo' => $submoduloActivo ? 'menu' : 'hub',
    'linea' => $submoduloActivo ? $lineaMenu : '',
]);

$modulosLinea = [];
if (is_array($submoduloActivo)) {
    $catalogoModulos = [
        [
            'clave' => 'asistencias',
            'label' => 'Asistencias',
            'descripcion' => 'Registro y consulta de asistencia.',
            'icono' => 'bi bi-calendar-check',
            'href' => $submoduloActivo['asistencias_href'] ?? '',
            'visible' => !empty($submoduloActivo['puede_asistencias']) && !empty($submoduloActivo['asistencias_href']),
        ],
        [
            'clave' => 'dashboard',
            'label' => 'Dashboard',
            'descripcion' => 'Indicadores y reportes del programa.',
            'icono' => 'bi bi-bar-chart-line',
            'href' => $submoduloActivo['dashboard_href'] ?? '',
            'visible' => !empty($submoduloActivo['puede_dashboard']) && !empty($submoduloActivo['dashboard_href']),
        ],
        [
            'clave' => 'pagos',
            'label' => 'Pagos',
            'descripcion' => 'Pago total, abonos y recaudo.',
            'icono' => 'bi bi-cash-coin',
            'href' => $submoduloActivo['pagos_href'] ?? '',
            'visible' => !empty($submoduloActivo['puede_pagos']) && !empty($submoduloActivo['pagos_href']),
        ],
        [
            'clave' => 'formulario',
            'label' => 'Formulario',
            'descripcion' => 'Inscripción pública del programa.',
            'icono' => 'bi bi-ui-checks',
            'href' => $submoduloActivo['formulario_href'] ?? '',
            'visible' => !empty($submoduloActivo['puede_formulario']) && !empty($submoduloActivo['formulario_href']),
            'externa' => true,
        ],
        [
            'clave' => 'material',
            'label' => 'Material',
            'descripcion' => 'Contenido y material de apoyo.',
            'icono' => 'bi bi-journal-richtext',
            'href' => $submoduloActivo['material_href'] ?? '',
            'visible' => !empty($submoduloActivo['puede_material']) && !empty($submoduloActivo['material_href']),
        ],
    ];
    foreach ($catalogoModulos as $mod) {
        if (!empty($mod['visible'])) {
            $modulosLinea[] = $mod;
        }
    }
}
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center; margin-bottom: 16px;">
    <div>
        <?php if ($submoduloActivo): ?>
        <h2 style="margin:0;"><?= htmlspecialchars((string)$submoduloActivo['titulo']) ?></h2>
        <small style="color:#637087;">Elige el módulo que quieres abrir.</small>
        <?php else: ?>
        <h2 style="margin:0;">Programas</h2>
        <small style="color:#637087;">Entra a Universidad de la Vida o Capacitación Destino.</small>
        <?php endif; ?>
    </div>
</div>

<?php if ($submoduloActivo): ?>
<div class="programas-grid programas-grid--modulos">
    <?php foreach ($modulosLinea as $modulo): ?>
    <a href="<?= htmlspecialchars((string)$modulo['href'], ENT_QUOTES, 'UTF-8') ?>"
       class="programas-card programas-card--link"
       style="--programa-gradiente: <?= htmlspecialchars((string)($submoduloActivo['gradiente'] ?? 'linear-gradient(135deg, #1e4a89 0%, #3f73be 100%)'), ENT_QUOTES, 'UTF-8') ?>;"
       <?= !empty($modulo['externa']) ? 'target="_blank" rel="noopener"' : '' ?>>
        <div class="programas-card-head">
            <span class="programas-card-icon"><i class="<?= htmlspecialchars((string)$modulo['icono']) ?>"></i></span>
        </div>
        <h3><?= htmlspecialchars((string)$modulo['label']) ?></h3>
        <p><?= htmlspecialchars((string)$modulo['descripcion']) ?></p>
        <span class="programas-card-cta">Abrir<?= !empty($modulo['externa']) ? ' <i class="bi bi-box-arrow-up-right"></i>' : '' ?></span>
    </a>
    <?php endforeach; ?>
</div>
<?php if (empty($modulosLinea)): ?>
<div class="alert alert-warning">
    No tienes módulos activos en este programa. Revisa los permisos de Programas.
</div>
<?php endif; ?>

<?php elseif (!empty($submodulosProgramas)): ?>
<div class="programas-grid programas-grid--hub">
    <?php foreach ($submodulosProgramas as $submodulo): ?>
    <a href="<?= htmlspecialchars(PUBLIC_URL . '?url=programas&linea=' . rawurlencode((string)$submodulo['clave']), ENT_QUOTES, 'UTF-8') ?>"
       class="programas-card programas-card--link programas-card--hub"
       style="--programa-gradiente: <?= htmlspecialchars((string)($submodulo['gradiente'] ?? 'linear-gradient(135deg, #1e4a89 0%, #3f73be 100%)'), ENT_QUOTES, 'UTF-8') ?>;">
        <div class="programas-card-head">
            <span class="programas-card-icon"><i class="<?= htmlspecialchars((string)($submodulo['icono'] ?? 'bi bi-grid-1x2-fill')) ?>"></i></span>
        </div>
        <h3><?= htmlspecialchars((string)($submodulo['titulo'] ?? 'Submódulo')) ?></h3>
        <p><?= htmlspecialchars((string)($submodulo['descripcion'] ?? '')) ?></p>
        <span class="programas-card-cta">Entrar</span>
    </a>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-warning">
    No tienes ninguna línea de Programas activa. En <strong>Permisos → Programas → Acciones permitidas</strong> active al menos
    «Ver consolidado Universidad de la Vida» o «Ver consolidado Capacitación Destino», y las acciones que necesite (dashboard, pagos, formulario, etc.).
</div>
<?php endif; ?>

<style>
.programas-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; }
.programas-grid--hub { grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); max-width: 920px; }
.programas-grid--modulos { grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
.programas-card { position: relative; border-radius: 18px; padding: 20px; background: #fff; border: 1px solid #d7e3f4; box-shadow: 0 10px 26px rgba(22, 46, 79, 0.12); overflow: hidden; }
.programas-card::before { content: ''; position: absolute; inset: 0; background: var(--programa-gradiente); opacity: .08; pointer-events: none; }
.programas-card--link { display: block; text-decoration: none; color: inherit; transition: transform .15s, box-shadow .15s, border-color .15s; }
.programas-card--link:hover { transform: translateY(-3px); box-shadow: 0 14px 30px rgba(22, 46, 79, 0.16); border-color: #93c5fd; }
.programas-card--hub { min-height: 220px; padding: 28px 24px; }
.programas-card-head { position: relative; z-index: 1; margin-bottom: 12px; }
.programas-card-icon { width: 46px; height: 46px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #fff; background: var(--programa-gradiente); }
.programas-card h3, .programas-card p, .programas-card-cta { position: relative; z-index: 1; }
.programas-card h3 { margin: 0 0 8px; font-size: 1.12rem; }
.programas-card p { margin: 0; color: #4d6281; line-height: 1.45; }
.programas-card-cta { display: inline-flex; align-items: center; gap: 6px; margin-top: 16px; font-size: 0.85rem; font-weight: 700; color: #1e4a89; }
</style>

<?php require_once VIEWS . '/layout/footer.php'; ?>
