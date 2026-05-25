<?php
$evaluacion = (array)($evaluacion ?? []);
$estadoIntento = (array)($estado_intento ?? []);
$preview = !empty($preview);
$urlListado = (string)($url_listado ?? '');
$urlPresentar = (string)($url_presentar ?? '');
$contextoHiddenHtml = (string)($contexto_hidden_html ?? '');
$mensajeFlash = (string)($_GET['mensaje'] ?? '');
$tipoFlash = (string)($_GET['tipo'] ?? '');

$preguntasEvaluacion = json_decode((string)($evaluacion['Preguntas_JSON'] ?? '[]'), true);
if (!is_array($preguntasEvaluacion)) {
    $preguntasEvaluacion = [];
}

$idEvaluacion = (int)($evaluacion['Id_Evaluacion'] ?? 0);
$puedeResponder = !$preview
    && !empty($estadoIntento['puede_responder'])
    && !empty($estadoIntento['intento_iniciado']);
$intentosAgotados = !$preview && empty($estadoIntento['puede_responder']);
$esVistaDiscipuloPresentar = class_exists('AuthController') && AuthController::esVistaDiscipuloSimplificada();
$urlInicioDiscipuloPresentar = PUBLIC_URL . '?url=programas/evaluaciones';
$urlAtrasDiscipuloPresentar = $urlListado !== '' ? $urlListado : $urlInicioDiscipuloPresentar;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)($pageTitle ?? 'Evaluación'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/styles.css?v=20260516-eval-presentar-1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body.eval-presentar-body {
            margin: 0;
            background: #f1f5f9;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            color: #0f172a;
        }
        .eval-presentar-shell {
            max-width: 720px;
            margin: 0 auto;
            padding: 12px 14px 28px;
        }
        .eval-presentar-top {
            position: sticky;
            top: 0;
            z-index: 20;
            background: #fff;
            border: 1px solid #dbe3f0;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 12px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
        }
        .eval-presentar-top h1 {
            margin: 0 0 6px;
            font-size: 1.15rem;
            line-height: 1.35;
        }
        .eval-timer-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #1d4ed8;
            font-weight: 700;
            font-size: 0.95rem;
        }
        .eval-timer-badge.is-low {
            background: #fef2f2;
            color: #b91c1c;
        }
        .eval-pregunta-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
            margin-bottom: 10px;
        }
        .eval-pregunta-card label {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            margin-top: 8px;
            cursor: pointer;
        }
        .eval-presentar-actions {
            position: sticky;
            bottom: 0;
            background: linear-gradient(180deg, rgba(241,245,249,0) 0%, #f1f5f9 24%);
            padding-top: 12px;
            margin-top: 8px;
        }
        .eval-presentar-actions .btn {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
        }
        @media (min-width: 768px) {
            .eval-presentar-shell { padding: 18px 20px 32px; }
            .eval-presentar-top h1 { font-size: 1.35rem; }
        }
        .disc-nav-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-disc-nav {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 0.84rem;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .btn-disc-nav--back {
            background: #fff;
            color: #1e40af;
            border-color: #bfdbfe;
        }
        .btn-disc-nav--home {
            background: #1f4f93;
            color: #fff;
        }
    </style>
</head>
<body class="eval-presentar-body">
<div class="eval-presentar-shell">
    <div class="eval-presentar-top">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:flex-start;">
            <div style="flex:1;min-width:0;">
                <h1><?= htmlspecialchars((string)($evaluacion['Titulo'] ?? 'Evaluación'), ENT_QUOTES, 'UTF-8') ?></h1>
                <small style="color:#64748b;">
                    Nivel <?= (int)($evaluacion['Nivel'] ?? 0) ?> · Módulo <?= (int)($evaluacion['Modulo_Numero'] ?? 0) ?>
                    · <?= htmlspecialchars((string)($evaluacion['Leccion'] ?? 'Sin lección'), ENT_QUOTES, 'UTF-8') ?>
                </small>
            </div>
            <?php if (!$preview && !empty($estadoIntento['intento_iniciado'])): ?>
                <span class="eval-timer-badge" id="evalTimerBadge">
                    <i class="bi bi-clock"></i>
                    <span id="evalTimerDisplay" data-segundos="<?= (int)($estadoIntento['tiempo_restante'] ?? 0) ?>">--:--</span>
                </span>
            <?php endif; ?>
        </div>
        <?php if ($urlListado !== ''): ?>
            <div style="margin-top:10px;">
                <?php if ($esVistaDiscipuloPresentar): ?>
                    <div class="disc-nav-actions">
                        <a class="btn-disc-nav btn-disc-nav--back" href="<?= htmlspecialchars($urlAtrasDiscipuloPresentar, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-arrow-left" aria-hidden="true"></i> Atrás
                        </a>
                        <a class="btn-disc-nav btn-disc-nav--home" href="<?= htmlspecialchars($urlInicioDiscipuloPresentar, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="bi bi-house-door" aria-hidden="true"></i> Inicio
                        </a>
                    </div>
                <?php else: ?>
                    <a class="btn btn-secondary btn-sm" href="<?= htmlspecialchars($urlListado, ENT_QUOTES, 'UTF-8') ?>">Volver al listado</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($mensajeFlash !== ''): ?>
        <div class="alert alert-<?= $tipoFlash === 'success' ? 'success' : 'danger' ?>" style="margin-bottom:12px;">
            <?= htmlspecialchars($mensajeFlash, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($preview): ?>
        <div class="alert alert-info" style="margin-bottom:12px;">Vista de revisión (sin cronómetro ni envío).</div>
    <?php elseif (!empty($estadoIntento['intento_iniciado'])): ?>
        <div class="alert alert-warning" style="margin-bottom:12px;">
            El tiempo de <strong>20 minutos</strong> ya está corriendo. Debes presentar la evaluación antes de que termine.
        </div>
    <?php endif; ?>

    <?php if ($intentosAgotados): ?>
        <div class="alert alert-danger">Ya agotaste el máximo de intentos para esta evaluación.</div>
    <?php elseif ($preview): ?>
        <?php foreach ($preguntasEvaluacion as $idx => $pregunta): ?>
            <div class="eval-pregunta-card">
                <strong><?= ($idx + 1) ?>. <?= htmlspecialchars((string)($pregunta['enunciado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                <?php foreach ((array)($pregunta['opciones'] ?? []) as $claveOpcion => $textoOpcion): ?>
                    <div style="margin-top:6px;"><small><strong><?= strtoupper((string)$claveOpcion) ?>.</strong> <?= htmlspecialchars((string)$textoOpcion, ENT_QUOTES, 'UTF-8') ?></small></div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php elseif ($puedeResponder): ?>
        <form method="POST" action="<?= htmlspecialchars($urlPresentar, ENT_QUOTES, 'UTF-8') ?>" id="formPresentarEvaluacion">
            <input type="hidden" name="accion" value="presentar_evaluacion">
            <input type="hidden" name="id_evaluacion" value="<?= $idEvaluacion ?>">
            <input type="hidden" name="tiempo_inicio" value="<?= (int)($estadoIntento['tiempo_inicio'] ?? 0) ?>">
            <input type="hidden" name="retorno_lista" value="1">
            <?= $contextoHiddenHtml ?>

            <div class="alert alert-info" style="margin-bottom:12px;">
                Solo preguntas cerradas. Puedes enviar aunque dejes preguntas sin responder.
            </div>

            <?php foreach ($preguntasEvaluacion as $idx => $pregunta): ?>
                <div class="eval-pregunta-card">
                    <strong><?= ($idx + 1) ?>. <?= htmlspecialchars((string)($pregunta['enunciado'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    <?php foreach ((array)($pregunta['opciones'] ?? []) as $claveOpcion => $textoOpcion): ?>
                        <label>
                            <input type="radio" name="respuesta[<?= (int)$idx ?>]" value="<?= htmlspecialchars((string)$claveOpcion, ENT_QUOTES, 'UTF-8') ?>">
                            <span><strong><?= strtoupper((string)$claveOpcion) ?>.</strong> <?= htmlspecialchars((string)$textoOpcion, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>

            <div class="eval-presentar-actions">
                <button type="submit" class="btn btn-primary" id="btnEnviarEvaluacion">Enviar evaluación</button>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">No se pudo iniciar el intento. Vuelve al listado e inténtalo de nuevo.</div>
    <?php endif; ?>
</div>

<script>
(function() {
    const form = document.getElementById('formPresentarEvaluacion');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Enviar la evaluación ahora? No podrás modificar las respuestas después.')) {
                e.preventDefault();
            }
        });
    }

    const timerEl = document.getElementById('evalTimerDisplay');
    const timerBadge = document.getElementById('evalTimerBadge');
    const btnEnviar = document.getElementById('btnEnviarEvaluacion');
    if (!timerEl || !btnEnviar) {
        return;
    }

    let segundos = parseInt(timerEl.getAttribute('data-segundos') || '0', 10);
    if (Number.isNaN(segundos) || segundos < 0) {
        segundos = 0;
    }

    function formatearTiempo(totalSegundos) {
        const minutos = Math.floor(totalSegundos / 60);
        const segs = totalSegundos % 60;
        return String(minutos).padStart(2, '0') + ':' + String(segs).padStart(2, '0');
    }

    function render() {
        timerEl.textContent = formatearTiempo(segundos);
        if (timerBadge) {
            timerBadge.classList.toggle('is-low', segundos > 0 && segundos <= 120);
        }
        if (segundos <= 0) {
            btnEnviar.disabled = true;
            btnEnviar.textContent = 'Tiempo agotado';
        }
    }

    render();
    if (segundos <= 0) {
        return;
    }

    const intervalId = setInterval(function() {
        segundos -= 1;
        render();
        if (segundos <= 0) {
            clearInterval(intervalId);
        }
    }, 1000);
})();
</script>
</body>
</html>
