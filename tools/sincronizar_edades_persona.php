<?php
/**
 * Recalcula persona.Edad a partir de Fecha_Nacimiento.
 *
 * Web (producción, con sesión iniciada o clave):
 *   https://mcimadridcolombia.com/tools/sincronizar_edades_persona.php
 *   https://mcimadridcolombia.com/tools/sincronizar_edades_persona.php?clave=mci_admin_2026
 *
 * Dentro de la app (recomendado):
 *   ?url=personas/sincronizar-edades
 *
 * CLI:
 *   php tools/sincronizar_edades_persona.php
 */

define('CLAVE_ACCESO', 'mci_admin_2026');
define('APP', dirname(__DIR__) . '/app');
define('ROOT', dirname(__DIR__));

$esCli = (PHP_SAPI === 'cli');

if (!$esCli) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $claveRecibida = trim((string)($_GET['clave'] ?? $_POST['clave'] ?? ''));
    $logueado = !empty($_SESSION['usuario_id']);
    $claveOk = $claveRecibida !== '' && hash_equals(CLAVE_ACCESO, $claveRecibida);
    if (!$logueado && !$claveOk) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Acceso denegado</title></head><body style="font-family:sans-serif;padding:24px;">';
        echo '<h1>Acceso denegado</h1><p>Inicia sesión en el sistema o abre la URL con <code>?clave=mci_admin_2026</code>.</p>';
        echo '</body></html>';
        exit;
    }
}

require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/conexion.php';
require_once APP . '/Models/BaseModel.php';
require_once APP . '/Models/Persona.php';

$personaModel = new Persona();

if ($esCli) {
    $actualizadas = $personaModel->sincronizarEdadesDesdeFechaNacimiento();
    echo "Personas con edad actualizada: {$actualizadas}\n";
    exit(0);
}

header('Content-Type: text/html; charset=utf-8');
$mensaje = '';
$pendientes = $personaModel->contarEdadesPendientesDeSincronizar();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['confirmar'] ?? '') === '1') {
    $actualizadas = $personaModel->sincronizarEdadesDesdeFechaNacimiento();
    $pendientes = $personaModel->contarEdadesPendientesDeSincronizar();
    $mensaje = 'Se actualizó la edad de ' . $actualizadas . ' persona(s).';
}

$claveQs = isset($_GET['clave']) ? ('?clave=' . rawurlencode((string)$_GET['clave'])) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Actualizar edades</title>
    <style>
        body { font-family: sans-serif; background: #f4f6fb; margin: 0; padding: 24px; color: #1e293b; }
        .box { max-width: 560px; margin: 0 auto; background: #fff; border: 1px solid #dbe4f0; border-radius: 12px; padding: 24px; }
        h1 { margin: 0 0 12px; font-size: 1.35rem; }
        .ok { background: #e8f7ee; color: #166534; padding: 10px 12px; border-radius: 8px; margin-bottom: 14px; }
        .btn { background: #2563eb; color: #fff; border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
<div class="box">
    <h1>Actualizar edades</h1>
    <p>Calcula la columna Edad con la fecha de nacimiento de cada persona.</p>
    <?php if ($mensaje !== ''): ?>
        <div class="ok"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <p>Pendientes: <strong><?= (int)$pendientes ?></strong></p>
    <?php if ((int)$pendientes > 0): ?>
    <form method="POST" action="<?= htmlspecialchars($claveQs, ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('¿Actualizar <?= (int)$pendientes ?> persona(s)?');">
        <input type="hidden" name="confirmar" value="1">
        <?php if (!empty($_GET['clave'])): ?>
        <input type="hidden" name="clave" value="<?= htmlspecialchars((string)$_GET['clave'], ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>
        <button class="btn" type="submit">Actualizar edades ahora</button>
    </form>
    <?php else: ?>
    <p>No hay registros pendientes.</p>
    <?php endif; ?>
</div>
</body>
</html>
