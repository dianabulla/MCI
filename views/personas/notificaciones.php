<?php include VIEWS . '/layout/header.php'; ?>

<?php
$pendientesConectar = is_array($pendientesConectar ?? null) ? $pendientesConectar : [];
$nuevasAlmasGanadas = is_array($nuevasAlmasGanadas ?? null) ? $nuevasAlmasGanadas : [];
$linkGestionPendientes = (string)($linkGestionPendientes ?? (PUBLIC_URL . '?url=personas'));
$linkGestionNuevos = (string)($linkGestionNuevos ?? (PUBLIC_URL . '?url=personas/ganar'));

$resumenCategorias = [
    [
        'id' => 'conectar',
        'titulo' => 'Pendientes por conectar',
        'descripcion' => 'Personas por ubicar en el padrón o inscripciones de Universidad de la Vida.',
        'total' => count($pendientesConectar),
        'icono' => 'bi-people-fill',
        'color' => '#1877f2',
        'link' => $linkGestionPendientes,
        'cta' => 'Ir a Discípulos',
    ],
    [
        'id' => 'nuevas',
        'titulo' => 'Nuevas en Almas ganadas',
        'descripcion' => 'Personas nuevas aún sin ubicar (falta ministerio o líder) y todavía en Ganar.',
        'total' => count($nuevasAlmasGanadas),
        'icono' => 'bi-person-plus-fill',
        'color' => '#00a884',
        'link' => $linkGestionNuevos,
        'cta' => 'Ir a Almas ganadas',
    ],
];
?>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/notificaciones-page.css?v=20260519-notif-page-1">

<div class="notif-page-head">
    <div>
        <h2>Notificaciones</h2>
        <p>Elige un acceso para ir directo al módulo de gestión correspondiente.</p>
    </div>
    <div class="personas-header-actions">
        <nav class="personas-action-group-nav" aria-label="Secciones Ganar-Consolidar">
            <a href="<?= PUBLIC_URL ?>?url=personas" class="personas-action-pill">Discípulos</a>
            <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="personas-action-pill">Almas ganadas</a>
            <a href="<?= PUBLIC_URL ?>?url=personas/notificaciones" class="personas-action-pill is-active" aria-current="page">Notificaciones</a>
        </nav>
    </div>
</div>

<div class="notif-page-panel">
    <div class="notif-page-panel-head">
        <h3>Resumen de pendientes</h3>
        <p>Los números se actualizan al cargar la página según tu rol y filtros del sistema.</p>
    </div>
    <div class="notif-page-cards">
        <?php foreach ($resumenCategorias as $cat): ?>
            <?php $totalCat = (int)($cat['total'] ?? 0); ?>
            <a
                href="<?= htmlspecialchars((string)$cat['link'], ENT_QUOTES, 'UTF-8') ?>"
                class="notif-page-card"
                style="--notif-accent: <?= htmlspecialchars((string)$cat['color'], ENT_QUOTES, 'UTF-8') ?>"
            >
                <span class="notif-page-card-icon" aria-hidden="true">
                    <i class="bi <?= htmlspecialchars((string)$cat['icono'], ENT_QUOTES, 'UTF-8') ?>"></i>
                </span>
                <span class="notif-page-card-body">
                    <span class="notif-page-card-title"><?= htmlspecialchars((string)$cat['titulo']) ?></span>
                    <span class="notif-page-card-desc"><?= htmlspecialchars((string)$cat['descripcion']) ?></span>
                </span>
                <span class="notif-page-card-side">
                    <span class="notif-page-card-count<?= $totalCat > 0 ? ' is-hot' : '' ?>"><?= $totalCat ?></span>
                    <span class="notif-page-card-cta">
                        <?= htmlspecialchars((string)$cat['cta']) ?>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
