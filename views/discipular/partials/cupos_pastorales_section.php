<?php
/**
 * Paneles visuales 12+12 para pastor/líder principal (incluir desde equipo principal).
 */
if (($tabActivo ?? '') !== 'equipo_principal') {
    return;
}

$usarEtiquetasPastorales = !empty($usarEtiquetasPastorales);
$mostrarBotonesCupoPastoral = !empty($mostrarBotonesCupoPastoral);
$textoAvisoConfigurarLideres = trim((string)($textoAvisoConfigurarLideres ?? ''));
$cuposPanelHombre = is_array($cupos_panel_hombre ?? null) ? $cupos_panel_hombre : null;
$cuposPanelMujer = is_array($cupos_panel_mujer ?? null) ? $cupos_panel_mujer : null;
?>
<section class="cupos-pastorales-wrap" aria-label="Asignación de cupos pastorales">
    <div class="cupos-pastorales-intro card">
        <h2 class="cupos-pastorales-title">Asignar líderes de 12 (cupos 1–12)</h2>
        <p class="cupos-pastorales-lead">
            Cada <?= $usarEtiquetasPastorales ? 'pastor o pastora' : 'líder principal' ?> tiene <strong>12 casillas</strong>.
            Pulsa un número libre para poner a alguien, o una ocupada para cambiarla o quitarla.
            En la tabla, cada líder de 12 usa <strong>Su equipo (12)</strong> para asignar sus líderes de 144; en la pestaña Líderes de 144, lo mismo hacia células.
        </p>
        <?php if (!$mostrarBotonesCupoPastoral && $textoAvisoConfigurarLideres !== ''): ?>
            <p class="equipo-guia-aviso"><?= htmlspecialchars($textoAvisoConfigurarLideres) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($mostrarBotonesCupoPastoral): ?>
    <div class="cupos-pastorales-grid">
        <?php if ($cuposPanelHombre): ?>
            <?php $panel = $cuposPanelHombre; include VIEWS . '/discipular/partials/cupos_equipo_grid.php'; ?>
        <?php endif; ?>
        <?php if ($cuposPanelMujer): ?>
            <?php $panel = $cuposPanelMujer; include VIEWS . '/discipular/partials/cupos_equipo_grid.php'; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</section>
