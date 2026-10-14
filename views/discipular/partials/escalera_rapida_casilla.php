<?php
$escaleraPersonaId = (int)($escaleraPersonaId ?? 0);
$escaleraPuedeEditar = !empty($escaleraPuedeEditar);
$escaleraIdCelula = (int)($escaleraIdCelula ?? 0);
$escaleraIdMinisterio = (int)($escaleraIdMinisterio ?? 0);
$escaleraAsignado = $escaleraIdMinisterio > 0;
$procesoEscalera = trim((string)($escaleraProceso ?? 'ganar'));
$procesoEscalera = $procesoEscalera !== '' ? ucfirst($procesoEscalera) : 'Ganar';

$checklistEscalera = [
    'Ganar' => [false, false, false, false, false, false],
    'Consolidar' => [false, false, false],
    'Discipular' => [false, false, false],
    'Enviar' => [false, false, false],
];
$checklistRaw = (string)($escaleraChecklistRaw ?? '');
if ($checklistRaw !== '') {
    $tmpChecklist = json_decode($checklistRaw, true);
    if (is_array($tmpChecklist)) {
        $checklistEscalera = array_merge($checklistEscalera, $tmpChecklist);
    }
}
foreach (['Ganar' => 5, 'Consolidar' => 2, 'Discipular' => 2, 'Enviar' => 2] as $etapaEsc => $maxIndice) {
    if (!isset($checklistEscalera[$etapaEsc]) || !is_array($checklistEscalera[$etapaEsc])) {
        $checklistEscalera[$etapaEsc] = array_fill(0, $maxIndice + 1, false);
    }
    for ($iEsc = 0; $iEsc <= $maxIndice; $iEsc++) {
        $checklistEscalera[$etapaEsc][$iEsc] = !empty($checklistEscalera[$etapaEsc][$iEsc] ?? null);
    }
}
$checklistEscaleraJson = htmlspecialchars((string)json_encode($checklistEscalera, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
$observacionNoDisponible = '';
if (isset($checklistEscalera['_meta']) && is_array($checklistEscalera['_meta'])) {
    $observacionNoDisponible = (string)($checklistEscalera['_meta']['no_disponible_observacion'] ?? '');
}
$attrDisabled = $escaleraPuedeEditar ? '' : 'disabled';
?>
<div
    class="escalera-inline-card js-inline-escalera"
    data-persona-id="<?= $escaleraPersonaId ?>"
    data-checklist="<?= $checklistEscaleraJson ?>"
    data-observacion-no-disponible="<?= htmlspecialchars($observacionNoDisponible, ENT_QUOTES, 'UTF-8') ?>"
    data-asignado="<?= $escaleraAsignado ? '1' : '0' ?>"
    data-celula="<?= $escaleraIdCelula > 0 ? '1' : '0' ?>"
    data-puede-editar="<?= $escaleraPuedeEditar ? '1' : '0' ?>"
    data-proceso="<?= htmlspecialchars($procesoEscalera, ENT_QUOTES, 'UTF-8') ?>"
>
    <div class="escalera-inline-steps">
        <button type="button" class="escalera-step-btn etapa-ganar js-inline-step-btn" data-etapa="Ganar" title="Ganar" aria-label="Ganar">
            <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">G</span></span>
            <span class="escalera-step-name">GANAR</span>
        </button>
        <button type="button" class="escalera-step-btn etapa-consolidar js-inline-step-btn" data-etapa="Consolidar" title="Consolidar" aria-label="Consolidar">
            <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">C</span></span>
            <span class="escalera-step-name">CONSOLIDAR</span>
        </button>
        <button type="button" class="escalera-step-btn etapa-discipular js-inline-step-btn" data-etapa="Discipular" title="Discipular" aria-label="Discipular">
            <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">D</span></span>
            <span class="escalera-step-name">DISCIPULAR</span>
        </button>
        <button type="button" class="escalera-step-btn etapa-enviar js-inline-step-btn" data-etapa="Enviar" title="Enviar" aria-label="Enviar">
            <span class="escalera-step-icon" aria-hidden="true"><span class="step-initial">E</span></span>
            <span class="escalera-step-name">ENVIAR</span>
        </button>
    </div>
    <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Ganar" hidden>
        <label class="escalera-inline-item" title="Primer contacto">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="0" <?= !empty($checklistEscalera['Ganar'][0]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Primer contacto</span>
        </label>
        <label class="escalera-inline-item" title="Fonovisita">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="2" <?= !empty($checklistEscalera['Ganar'][2]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Fonovisita</span>
        </label>
        <label class="escalera-inline-item" title="Visita">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="3" <?= !empty($checklistEscalera['Ganar'][3]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Visita</span>
        </label>
        <label class="escalera-inline-item" title="No se dispone">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Ganar" data-indice="5" <?= !empty($checklistEscalera['Ganar'][5]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>No se dispone</span>
        </label>
    </div>
    <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Consolidar" hidden>
        <label class="escalera-inline-item" title="Universidad de la vida">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Consolidar" data-indice="0" <?= !empty($checklistEscalera['Consolidar'][0]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Universidad de la vida</span>
        </label>
        <label class="escalera-inline-item" title="Encuentro">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Consolidar" data-indice="1" <?= !empty($checklistEscalera['Consolidar'][1]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Encuentro</span>
        </label>
        <label class="escalera-inline-item" title="Bautismo">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Consolidar" data-indice="2" <?= !empty($checklistEscalera['Consolidar'][2]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Bautismo</span>
        </label>
    </div>
    <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Discipular" hidden>
        <label class="escalera-inline-item" title="Capacitación destino nivel 1">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Discipular" data-indice="0" <?= !empty($checklistEscalera['Discipular'][0]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Destino nivel 1</span>
        </label>
        <label class="escalera-inline-item" title="Capacitación destino nivel 2">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Discipular" data-indice="1" <?= !empty($checklistEscalera['Discipular'][1]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Destino nivel 2</span>
        </label>
        <label class="escalera-inline-item" title="Capacitación destino nivel 3">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Discipular" data-indice="2" <?= !empty($checklistEscalera['Discipular'][2]) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Destino nivel 3</span>
        </label>
    </div>
    <div class="escalera-inline-panel js-inline-stage-panel" data-etapa="Enviar" hidden>
        <label class="escalera-inline-item" title="Célula">
            <input type="checkbox" class="js-inline-escalera-check" data-etapa="Enviar" data-indice="2" <?= (!empty($checklistEscalera['Enviar'][2]) || $escaleraIdCelula > 0) ? 'checked' : '' ?> <?= $attrDisabled ?>>
            <span>Célula</span>
        </label>
    </div>
</div>
