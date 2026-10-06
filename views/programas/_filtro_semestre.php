<?php
require_once APP . '/Helpers/PeriodoSemestre.php';
$periodoSemestre = is_array($periodo_semestre ?? null) ? $periodo_semestre : PeriodoSemestre::resolver();
$anioSem = (int)($periodoSemestre['anio'] ?? date('Y'));
$semSem = (int)($periodoSemestre['semestre'] ?? 1);
$anioMin = 2023;
$anioMax = (int)date('Y') + 1;
$modoForm = !empty($filtro_semestre_form);
$actionForm = (string)($filtro_semestre_action ?? '');
$mostrarBuscar = !empty($filtro_semestre_buscar);
$valorBuscar = preg_replace('/\s+/u', ' ', trim((string)($_GET['buscar'] ?? ($filtro_semestre_buscar_valor ?? ''))));
?>
<style>
.filtro-semestre-row { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; margin:0 0 14px; }
.filtro-semestre-row .filtro-group { display:flex; flex-direction:column; }
.filtro-semestre-row .filtro-group label { font-size:13px; color:#475569; margin-bottom:6px; font-weight:500; }
.filtro-semestre-row .filtro-group select { min-width:180px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; }
.filtro-semestre-row .filtro-group-buscar { flex: 1 1 260px; min-width: 240px; max-width: 420px; }
.filtro-semestre-row .filtro-group-buscar input[type="search"] {
    width: 100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px;
}
.filtro-semestre-row .filtro-buscar-btn {
    padding: 8px 14px; border: 0; border-radius: 6px; background: #1e4a89; color: #fff;
    font-size: 14px; font-weight: 700; cursor: pointer; height: 38px;
}
.filtro-semestre-row .filtro-buscar-btn:hover { background: #163a6d; }
</style>
<?php if ($modoForm): ?>
<form method="get" action="<?= htmlspecialchars($actionForm !== '' ? $actionForm : (rtrim((string)PUBLIC_URL, '/') . '/index.php'), ENT_QUOTES, 'UTF-8') ?>" class="filtro-semestre-row">
    <?php foreach ((array)($_GET ?? []) as $claveGet => $valorGet):
        if (in_array((string)$claveGet, ['anio', 'semestre', 'buscar'], true)) {
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
    <?php if ($mostrarBuscar): ?>
    <div class="filtro-group filtro-group-buscar">
        <label for="filtro-buscar-personas">Buscar persona</label>
        <input type="search" id="filtro-buscar-personas" name="buscar"
               value="<?= htmlspecialchars((string)$valorBuscar, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Nombre, cédula o teléfono"
               autocomplete="off">
    </div>
    <div class="filtro-group">
        <label for="filtro-buscar-btn">&nbsp;</label>
        <button type="submit" class="filtro-buscar-btn" id="filtro-buscar-btn">Buscar</button>
    </div>
    <?php endif; ?>
<?php if ($modoForm): ?>
</form>
<?php else: ?>
</div>
<?php endif; ?>
