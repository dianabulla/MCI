<?php
require_once APP . '/Helpers/PeriodoSemestre.php';
$periodoSemestre = is_array($periodo_semestre ?? null) ? $periodo_semestre : PeriodoSemestre::resolver();
$anioSem = (int)($periodoSemestre['anio'] ?? date('Y'));
$semSem = (int)($periodoSemestre['semestre'] ?? 1);
$anioMin = 2023;
$anioMax = (int)date('Y') + 1;
$modoForm = !empty($filtro_semestre_form);
$actionForm = (string)($filtro_semestre_action ?? '');
?>
<style>
.filtro-semestre-row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin:0 0 14px; }
.filtro-semestre-row .filtro-group { display:flex; flex-direction:column; }
.filtro-semestre-row .filtro-group label { font-size:13px; color:#475569; margin-bottom:6px; font-weight:500; }
.filtro-semestre-row .filtro-group select { min-width:180px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; }
</style>
<?php if ($modoForm): ?>
<form method="get" action="<?= htmlspecialchars($actionForm !== '' ? $actionForm : (rtrim((string)PUBLIC_URL, '/') . '/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="filtro-semestre-row">
    <?php foreach ((array)($_GET ?? []) as $claveGet => $valorGet):
        if (in_array((string)$claveGet, ['anio', 'semestre'], true)) {
            continue;
        }
        if (is_array($valorGet)) {
            continue;
        }
    ?>
    <input type="hidden" name="<?= htmlspecialchars((string)$claveGet, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string)$valorGet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
<?php else: ?>
<div class="filtro-semestre-row" style="margin:0;">
<?php endif; ?>
    <div class="filtro-group">
        <label for="<?= $modoForm ? 'filtro-anio-form' : 'filtro-anio' ?>">Año</label>
        <select id="<?= $modoForm ? 'filtro-anio-form' : 'filtro-anio' ?>" name="anio" class="form-control" <?= $modoForm ? 'onchange="this.form.submit()"' : '' ?>>
            <?php for ($y = $anioMax; $y >= $anioMin; $y--): ?>
            <option value="<?= $y ?>" <?= $y === $anioSem ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
        </select>
    </div>
    <div class="filtro-group">
        <label for="<?= $modoForm ? 'filtro-semestre-form' : 'filtro-semestre' ?>">Semestre</label>
        <select id="<?= $modoForm ? 'filtro-semestre-form' : 'filtro-semestre' ?>" name="semestre" class="form-control" <?= $modoForm ? 'onchange="this.form.submit()"' : '' ?>>
            <option value="1" <?= $semSem === 1 ? 'selected' : '' ?>>Semestre 1 (Ene – Jun)</option>
            <option value="2" <?= $semSem === 2 ? 'selected' : '' ?>>Semestre 2 (Jul – Dic)</option>
        </select>
    </div>
<?php if ($modoForm): ?>
</form>
<?php else: ?>
</div>
<?php endif; ?>
