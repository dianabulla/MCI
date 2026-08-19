<?php
$puedeMostrarCampanaNotif = !empty($puedeVerPendientesGanar) || !empty($puedeVerGanarArea);
if (!$puedeMostrarCampanaNotif) {
    return;
}

$urlNotifAlmasGanadas = PUBLIC_URL . '?url=personas/ganar';
$urlNotifDiscipulos = PUBLIC_URL . '?url=personas';
$urlNotifCentro = PUBLIC_URL . '?url=personas/notificaciones';
$totalNotifAlmas = (int)($totalPendientesGanar ?? 0);
$totalNotifDiscipulos = (int)($totalPendientesPorConectar ?? 0);
$totalNotifCampana = max(0, $totalNotifAlmas + $totalNotifDiscipulos);
$paginaNotificacionesActiva = ($currentUrl ?? '') === 'personas/notificaciones';
?>
<div class="app-topbar" role="banner">
    <div class="app-topbar-inner">
        <div class="fb-notif" data-fb-notif>
            <button
                type="button"
                class="fb-notif-bell"
                id="fbNotifBell"
                aria-label="Notificaciones"
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="fbNotifPanel"
            >
                <i class="bi bi-bell-fill" aria-hidden="true"></i>
                <?php if ($totalNotifCampana > 0): ?>
                    <span class="fb-notif-badge" aria-hidden="true"><?= $totalNotifCampana > 99 ? '99+' : $totalNotifCampana ?></span>
                <?php endif; ?>
            </button>

            <div class="fb-notif-panel" id="fbNotifPanel" role="menu" aria-labelledby="fbNotifBell" hidden>
                <div class="fb-notif-panel-head">
                    <strong>Notificaciones</strong>
                    <a href="<?= htmlspecialchars($urlNotifCentro, ENT_QUOTES, 'UTF-8') ?>" class="fb-notif-panel-link">Ver todas</a>
                </div>

                <a href="<?= htmlspecialchars($urlNotifAlmasGanadas, ENT_QUOTES, 'UTF-8') ?>" class="fb-notif-item" role="menuitem">
                    <span class="fb-notif-item-icon fb-notif-item-icon--almas" aria-hidden="true">
                        <i class="bi bi-person-plus-fill"></i>
                    </span>
                    <span class="fb-notif-item-body">
                        <span class="fb-notif-item-title">Almas ganadas</span>
                        <span class="fb-notif-item-desc">Nuevas aún sin ubicar (ministerio y líder) o sin avanzar de Ganar</span>
                    </span>
                    <?php if ($totalNotifAlmas > 0): ?>
                        <span class="fb-notif-item-count"><?= $totalNotifAlmas > 99 ? '99+' : $totalNotifAlmas ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= htmlspecialchars($urlNotifDiscipulos, ENT_QUOTES, 'UTF-8') ?>" class="fb-notif-item" role="menuitem">
                    <span class="fb-notif-item-icon fb-notif-item-icon--discipulos" aria-hidden="true">
                        <i class="bi bi-people-fill"></i>
                    </span>
                    <span class="fb-notif-item-body">
                        <span class="fb-notif-item-title">Discípulos</span>
                        <span class="fb-notif-item-desc">Pendientes por conectar: ministerio, líder o célula</span>
                    </span>
                    <?php if ($totalNotifDiscipulos > 0): ?>
                        <span class="fb-notif-item-count"><?= $totalNotifDiscipulos > 99 ? '99+' : $totalNotifDiscipulos ?></span>
                    <?php endif; ?>
                </a>

                <?php if ($totalNotifCampana <= 0): ?>
                    <div class="fb-notif-empty">No tienes pendientes por ahora.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= htmlspecialchars(function_exists('asset_url') ? asset_url('js/notificaciones-topbar.js') : (ASSETS_URL . '/js/notificaciones-topbar.js?v=' . date('Ymd')), ENT_QUOTES, 'UTF-8') ?>"></script>
