<?php include VIEWS . '/layout/header.php'; ?>

<?php
$tipoActiva = (string)($tipoNotificacionActiva ?? '');
$pendientesConectar = is_array($pendientesConectar ?? null) ? $pendientesConectar : [];
$nuevasAlmasGanadas = is_array($nuevasAlmasGanadas ?? null) ? $nuevasAlmasGanadas : [];
$linkGestionPendientes = (string)($linkGestionPendientes ?? (PUBLIC_URL . '?url=personas&panel=pendientes_ubicacion'));
$linkGestionNuevos = (string)($linkGestionNuevos ?? (PUBLIC_URL . '?url=personas/ganar'));

$resumenCategorias = [
    [
        'id' => 'conectar',
        'titulo' => 'Pendientes por conectar',
        'descripcion' => 'Discipulos antiguos con asignacion incompleta',
        'total' => count($pendientesConectar),
        'icono' => 'bi-diagram-3',
        'color' => '#1877f2',
        'link' => $linkGestionPendientes,
        'accion' => 'Gestionar en Discipulos'
    ],
    [
        'id' => 'nuevas',
        'titulo' => 'Nuevas en Almas ganadas',
        'descripcion' => 'Personas nuevas asignadas o recien llegadas',
        'total' => count($nuevasAlmasGanadas),
        'icono' => 'bi-person-plus-fill',
        'color' => '#00a884',
        'link' => $linkGestionNuevos,
        'accion' => 'Gestionar en Almas ganadas'
    ]
];
?>

<div class="notif-page-head">
    <div>
        <h2>Notificaciones</h2>
        <p>Selecciona la notificacion para ir directo al modulo de gestion.</p>
    </div>
    <div class="personas-header-actions">
        <div class="personas-action-group personas-action-group-nav">
            <a href="<?= PUBLIC_URL ?>?url=personas" class="personas-action-pill">Discipulos</a>
            <a href="<?= PUBLIC_URL ?>?url=personas/ganar" class="personas-action-pill">Almas ganadas</a>
            <a href="<?= PUBLIC_URL ?>?url=personas/notificaciones" class="personas-action-pill is-active" aria-current="page">Notificaciones</a>
        </div>
    </div>
</div>

<div class="notif-layout">
    <section class="notif-feed" style="grid-column: 1 / -1;">
        <div class="notif-feed-head">
            <div>
                <h3>Accesos de gestion</h3>
                <p>Dos notificaciones separadas para gestionar pendientes y nuevos.</p>
            </div>
        </div>
        <div class="notif-items">
            <?php foreach ($resumenCategorias as $cat): ?>
                <a
                    href="<?= htmlspecialchars((string)$cat['link']) ?>"
                    class="notif-category"
                    style="--notif-color: <?= htmlspecialchars((string)$cat['color']) ?>; margin-bottom: 10px; text-decoration: none;"
                >
                    <div class="notif-category-icon"><i class="bi <?= htmlspecialchars((string)$cat['icono']) ?>"></i></div>
                    <div class="notif-category-body">
                        <div class="notif-category-title"><?= htmlspecialchars((string)$cat['titulo']) ?></div>
                        <div class="notif-category-desc"><?= htmlspecialchars((string)$cat['descripcion']) ?></div>
                    </div>
                    <div class="notif-category-count" style="margin-right:8px;"><?= (int)$cat['total'] ?></div>
                    <span class="btn btn-sm btn-primary"><?= htmlspecialchars((string)$cat['accion']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<style>
.personas-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.personas-action-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.personas-action-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    border-radius: 999px;
    text-decoration: none;
    border: 1px solid #cfdcf2;
    background: #f8fbff;
    color: #27446d;
    font-weight: 600;
    font-size: 13px;
    line-height: 1;
    transition: .15s ease;
}

.personas-action-pill:hover {
    border-color: #9db8e5;
    background: #eef5ff;
}

.personas-action-pill.is-active {
    border-color: #1877f2;
    background: #1877f2;
    color: #fff;
}

.notif-page-head {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
    margin-bottom: 14px;
}

.notif-page-head h2 {
    margin: 0;
    font-size: 32px;
    line-height: 1.1;
    color: #1c2b44;
}

.notif-page-head p {
    margin: 4px 0 0;
    color: #5a6a82;
    font-size: 14px;
}

.notif-layout {
    display: grid;
    grid-template-columns: 320px minmax(0, 1fr);
    gap: 14px;
    align-items: start;
}

.notif-sidebar,
.notif-feed {
    background: #ffffff;
    border: 1px solid #d7e2f2;
    border-radius: 14px;
    box-shadow: 0 8px 20px rgba(15, 38, 79, 0.06);
}

.notif-sidebar {
    padding: 12px;
    position: sticky;
    top: 18px;
}

.notif-sidebar-title {
    font-size: 13px;
    letter-spacing: .02em;
    color: #6b7b95;
    margin: 4px 6px 10px;
    font-weight: 700;
}

.notif-category {
    display: grid;
    grid-template-columns: 38px minmax(0, 1fr) auto;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: inherit;
    border: 1px solid #dde6f2;
    border-radius: 12px;
    padding: 10px;
    margin-bottom: 8px;
    transition: .15s ease;
    background: #f9fbff;
}

.notif-category:hover {
    transform: translateY(-1px);
    border-color: var(--notif-color);
}

.notif-category.is-active {
    border-color: var(--notif-color);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--notif-color) 22%, white);
    background: #fff;
}

.notif-category-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: #fff;
    background: var(--notif-color);
    font-size: 16px;
}

.notif-category-title {
    font-size: 14px;
    font-weight: 700;
    color: #24354f;
    line-height: 1.2;
}

.notif-category-desc {
    font-size: 12px;
    color: #60728f;
    line-height: 1.25;
    margin-top: 2px;
}

.notif-category-count {
    min-width: 32px;
    height: 24px;
    border-radius: 999px;
    display: grid;
    place-items: center;
    background: #ebf2ff;
    color: #1f4fa8;
    font-size: 13px;
    font-weight: 700;
}

.notif-clear-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    color: #556b8b;
    font-size: 13px;
    margin: 6px 6px 2px;
}

.notif-feed {
    padding: 0;
    overflow: hidden;
}

.notif-feed-head {
    border-bottom: 1px solid #e4ecf8;
    padding: 14px 16px;
}

.notif-feed-head h3 {
    margin: 0;
    color: #1f2e47;
    font-size: 18px;
}

.notif-feed-head p {
    margin: 3px 0 0;
    color: #627591;
    font-size: 13px;
}

.notif-items {
    padding: 6px;
}

.notif-item-card {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto;
    gap: 10px;
    align-items: start;
    padding: 10px;
    border: 1px solid #e2eaf6;
    border-radius: 12px;
    margin-bottom: 8px;
    background: #ffffff;
}

.notif-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #1b74e4, #5aa8ff);
    color: #fff;
    font-weight: 700;
    font-size: 13px;
}

.notif-item-title {
    font-size: 15px;
    font-weight: 700;
    color: #1d2e4b;
    margin-bottom: 5px;
}

.notif-item-meta {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-bottom: 6px;
}

.pill {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #edf3ff;
    color: #3059a8;
    padding: 3px 8px;
    font-size: 12px;
    font-weight: 600;
}

.pill.is-warn {
    background: #fff3db;
    color: #8a5a00;
}

.notif-item-context {
    display: grid;
    gap: 3px;
    color: #5b6f8e;
    font-size: 13px;
}

.notif-item-context strong {
    color: #384f76;
    font-weight: 700;
}

.notif-item-actions {
    padding-top: 2px;
}

.notif-empty,
.notif-empty-inline {
    text-align: center;
    color: #5f708c;
}

.notif-empty {
    padding: 44px 16px;
}

.notif-empty i {
    font-size: 28px;
    color: #7d90af;
}

.notif-empty h3 {
    margin: 10px 0 4px;
    color: #1f2f4b;
    font-size: 18px;
}

.notif-empty p {
    margin: 0;
    font-size: 14px;
}

.notif-empty-inline {
    padding: 24px 10px;
    font-size: 14px;
}

@media (max-width: 980px) {
    .notif-layout {
        grid-template-columns: 1fr;
    }

    .notif-sidebar {
        position: static;
    }
}

@media (max-width: 720px) {
    .notif-page-head h2 {
        font-size: 27px;
    }

    .notif-item-card {
        grid-template-columns: 40px minmax(0, 1fr);
    }

    .notif-item-actions {
        grid-column: 1 / -1;
        padding-top: 0;
    }
}
</style>

<?php include VIEWS . '/layout/footer.php'; ?>
