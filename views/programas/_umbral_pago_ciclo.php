<?php
require_once APP . '/Helpers/UmbralPagoCompletoUv.php';
require_once APP . '/Helpers/PeriodoSemestre.php';
require_once APP . '/Controllers/AuthController.php';

$periodoUmbral = is_array($periodo_semestre ?? null) ? $periodo_semestre : PeriodoSemestre::resolver();
$anioUmbral = (int)($periodoUmbral['anio'] ?? date('Y'));
$semestreUmbral = (int)($periodoUmbral['semestre'] ?? 1);
$umbralPagoUv = UmbralPagoCompletoUv::valor($anioUmbral, $semestreUmbral);
$puedeDefinirUmbralUv = AuthController::puedeGestionarPagosProgramaUv();
$redUmbral = trim((string)($red_pagos ?? ''));
$umbralGuardado = ((string)($_GET['umbral_ok'] ?? '') === '1');
$textoUmbral = '$' . number_format($umbralPagoUv, 0, ',', '.');
?>
<?php if ($puedeDefinirUmbralUv): ?>
<form method="post" action="<?= htmlspecialchars(rtrim((string)PUBLIC_URL, '/') . '/?url=escuelas_formacion/pagos/umbral-ciclo', ENT_QUOTES, 'UTF-8') ?>" class="uv-umbral-ciclo">
    <input type="hidden" name="anio" value="<?= $anioUmbral ?>">
    <input type="hidden" name="semestre" value="<?= $semestreUmbral ?>">
    <?php if ($redUmbral !== ''): ?>
    <input type="hidden" name="red" value="<?= htmlspecialchars($redUmbral, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <label for="umbral-pago-ciclo">Pago total de este ciclo</label>
    <input type="text" id="umbral-pago-ciclo" name="valor" inputmode="numeric" value="<?= (int)$umbralPagoUv ?>" autocomplete="off">
    <button type="submit" class="btn btn-outline-primary btn-sm">Guardar</button>
    <small>Ahora: <?= htmlspecialchars($textoUmbral, ENT_QUOTES, 'UTF-8') ?>. Solo cambia desde qué monto este semestre cuenta como pago total. Los pagos ya registrados no se borran.</small>
</form>
<?php if ($umbralGuardado): ?>
<p class="uv-umbral-ciclo-ok">Quedó guardado el pago total de este ciclo: <?= htmlspecialchars($textoUmbral, ENT_QUOTES, 'UTF-8') ?>.</p>
<?php endif; ?>
<style>
.uv-umbral-ciclo { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin:0 0 12px; }
.uv-umbral-ciclo label { font-size:13px; color:#475569; font-weight:600; }
.uv-umbral-ciclo input { width:140px; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; }
.uv-umbral-ciclo small { flex:1 1 220px; color:#64748b; font-size:12px; line-height:1.35; }
.uv-umbral-ciclo-ok { margin:-4px 0 12px; color:#166534; font-size:13px; }
</style>
<?php endif; ?>
