<?php
/**
 * Cuadrícula de 12 casillas para un líder (pastor, líder de 12, etc.).
 *
 * @var array<string, mixed>|null $panel
 */
$panel = is_array($panel ?? null) ? $panel : null;
if ($panel === null || empty($panel['id_lider'])) {
    return;
}

$idLider = (int)($panel['id_lider'] ?? 0);
$nombreLider = trim((string)($panel['nombre_lider'] ?? ''));
$jerarquia = trim((string)($panel['jerarquia_lider'] ?? 'pastor'));
$idMinisterio = (int)($panel['id_ministerio'] ?? 0);
$tipoPanel = trim((string)($panel['tipo_panel'] ?? ''));
$slots = is_array($panel['slots'] ?? null) ? $panel['slots'] : [];
$ocupados = (int)($panel['ocupados'] ?? 0);
$disponibles = (int)($panel['disponibles'] ?? max(0, 12 - $ocupados));

$esPastorHombre = $tipoPanel === 'pastor_hombre';
$esPastorMujer = $tipoPanel === 'pastor_mujer';
$tituloPanel = $esPastorHombre
    ? '12 cupos — Pastor / líder principal (hombres)'
    : ($esPastorMujer
        ? '12 cupos — Pastora / líder principal (mujeres)'
        : '12 cupos — Equipo directo');
$modoCupo = ($jerarquia === 'lider_12' || $jerarquia === 'pastor') ? 'lider_12' : ($jerarquia === 'lider_144' ? 'lider_144' : 'pastoral');
if ($esPastorHombre || $esPastorMujer) {
    $modoCupo = 'pastoral';
}
?>

<section class="cupos-panel card" data-tipo-panel="<?= htmlspecialchars($tipoPanel) ?>">
    <header class="cupos-panel-head">
        <div>
            <h3 class="cupos-panel-title"><?= htmlspecialchars($tituloPanel) ?></h3>
            <p class="cupos-panel-sub"><?= htmlspecialchars($nombreLider) ?> · <strong><?= $ocupados ?>/12</strong> ocupados · <?= $disponibles ?> libre<?= $disponibles === 1 ? '' : 's' ?></p>
        </div>
    </header>

    <div class="cupos-casillas-grid" role="list" aria-label="Casillas del 1 al 12">
        <?php foreach ($slots as $slot): ?>
            <?php
                $numero = (int)($slot['numero'] ?? 0);
                $persona = is_array($slot['persona'] ?? null) ? $slot['persona'] : null;
                $libre = !empty($slot['libre']) || empty($persona);
                $idPersona = (int)($persona['id_persona'] ?? 0);
                $nombrePersona = trim((string)($persona['nombre'] ?? ''));
            ?>
            <button
                type="button"
                class="cupo-casilla-btn js-asignar-desde-cupo <?= $libre ? 'is-free' : 'is-occupied' ?>"
                role="listitem"
                data-id-lider="<?= $idLider ?>"
                data-id-ministerio="<?= $idMinisterio ?>"
                data-nombre-lider="<?= htmlspecialchars($nombreLider, ENT_QUOTES, 'UTF-8') ?>"
                data-jerarquia-lider="<?= htmlspecialchars($jerarquia, ENT_QUOTES, 'UTF-8') ?>"
                data-modo-cupo="<?= htmlspecialchars($modoCupo, ENT_QUOTES, 'UTF-8') ?>"
                data-slot-numero="<?= $numero ?>"
                <?= $idPersona > 0 ? 'data-id-persona-objetivo="' . $idPersona . '"' : '' ?>
                title="<?= $libre ? ('Asignar casilla ' . $numero) : ('Cambiar o quitar: ' . $nombrePersona) ?>"
            >
                <span class="cupo-casilla-numero"><?= $numero ?></span>
                <span class="cupo-casilla-estado"><?= $libre ? 'Libre' : htmlspecialchars($nombrePersona) ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</section>
