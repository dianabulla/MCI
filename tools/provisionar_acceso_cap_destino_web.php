<?php
/**
 * Provisionar acceso discípulo — inscritos Capacitación Destino (todos los niveles).
 * Usa AccesoDiscipuloCapDestino (misma lógica que al inscribir).
 *
 * Subir a: /tools/provisionar_acceso_cap_destino_web.php
 *
 * Seguridad (elige una):
 *   A) Token: hash('sha256', DB_HOST|DB_NAME|DB_USER|DB_PASS)
 *   B) Clave: variable de entorno PROVISION_CAP_DEST_CLAVE o valor por defecto (cámbialo en producción)
 *
 * Navegador:
 *   Simulación: ?clave=TU_CLAVE
 *   Aplicar:    ?clave=TU_CLAVE&mode=apply&confirm=SI
 *   Con token:  ?token=TU_TOKEN&mode=dry-run
 *
 * CLI:
 *   php tools/provisionar_acceso_cap_destino_web.php --dry-run
 *   php tools/provisionar_acceso_cap_destino_web.php --apply
 */

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

require_once ROOT . '/conexion.php';
require_once APP . '/Config/config.php';
require_once APP . '/Helpers/AccesoDiscipuloCapDestino.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/UserRole.php';

$isCli = (PHP_SAPI === 'cli');

$programasCapDestino = [
    'capacitacion_destino',
    'capacitacion_destino_nivel_1',
    'capacitacion_destino_nivel_2',
    'capacitacion_destino_nivel_3',
];

function authTokenEsperado(): string
{
    return hash('sha256', (string)DB_HOST . '|' . (string)DB_NAME . '|' . (string)DB_USER . '|' . (string)(defined('DB_PASS') ? DB_PASS : ''));
}

function claveInstalacionEsperada(): string
{
    $env = getenv('PROVISION_CAP_DEST_CLAVE');
    if (is_string($env) && trim($env) !== '') {
        return trim($env);
    }
    return 'provisionar-cap-dest-2026';
}

function accesoAutorizado(): bool
{
    $token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
    if ($token !== '' && hash_equals(authTokenEsperado(), $token)) {
        return true;
    }
    $clave = (string)($_GET['clave'] ?? $_POST['clave'] ?? '');
    if ($clave !== '' && hash_equals(claveInstalacionEsperada(), $clave)) {
        return true;
    }
    return false;
}

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function esLiderazgoPorPersona(Persona $personaModel, array $persona): bool
{
    $idRol = (int)($persona['Id_Rol'] ?? 0);
    $jerarquia = $personaModel->getJerarquiaByRol($idRol);
    return in_array($jerarquia, ['pastor', 'lider_12', 'lider_144', 'lider_celula', 'administrativo'], true);
}

function queryParamAuth(): string
{
    $token = (string)($_GET['token'] ?? '');
    if ($token !== '') {
        return 'token=' . rawurlencode($token);
    }
    $clave = (string)($_GET['clave'] ?? '');
    return 'clave=' . rawurlencode($clave);
}

// --- Auth ---
if (!$isCli && !accesoAutorizado()) {
    header('Content-Type: text/html; charset=UTF-8');
    http_response_code(403);
    $self = basename(__FILE__);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Acceso</title></head><body style="font-family:sans-serif;max-width:720px;margin:2rem auto;padding:0 1rem;">';
    echo '<h1>Provisionar acceso Cap. Destino</h1>';
    echo '<p>Acceso denegado. Usa la <strong>clave</strong> o el <strong>token</strong> del servidor.</p>';
    echo '<form method="get" action="' . h($self) . '">';
    echo '<p><label>Clave: <input type="password" name="clave" size="40" autocomplete="off"></label></p>';
    echo '<p><button type="submit">Entrar (simulación)</button></p></form>';
    echo '<hr><p><small>Token (alternativo): <code>hash sha256</code> de <code>DB_HOST|DB_NAME|DB_USER|DB_PASS</code>. ';
    echo 'Clave por defecto del script: <code>provisionar-cap-dest-2026</code> — cámbiala con env <code>PROVISION_CAP_DEST_CLAVE</code> en producción.</small></p>';
    echo '</body></html>';
    exit;
}

$apply = false;
if ($isCli) {
    $apply = in_array('--apply', $argv ?? [], true);
} else {
    $mode = strtolower(trim((string)($_GET['mode'] ?? 'dry-run')));
    $confirm = strtoupper(trim((string)($_GET['confirm'] ?? '')));
    $apply = ($mode === 'apply' && $confirm === 'SI');
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    if ($isCli) {
        fwrite(STDERR, "Sin conexión PDO.\n");
    } else {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Sin conexión PDO.\n";
    }
    exit(1);
}

$inscripcionModel = new EscuelaFormacionInscripcion();
$personaModel = new Persona();
$userRoleModel = new UserRole();
$userRoleModel->asegurarTabla();

$placeholders = implode(',', array_fill(0, count($programasCapDestino), '?'));
$sql = "SELECT i.*
        FROM escuela_formacion_inscripcion i
        WHERE LOWER(TRIM(i.Programa)) IN ('capacitacion_destino','capacitacion_destino_nivel_1','capacitacion_destino_nivel_2','capacitacion_destino_nivel_3')
        ORDER BY i.Id_Inscripcion ASC";
$inscripciones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$porPersona = [];
$sinPersona = [];

foreach ($inscripciones as $ins) {
    $idPersona = (int)($ins['Id_Persona'] ?? 0);
    if ($idPersona <= 0) {
        $idPersona = $inscripcionModel->resolverIdPersonaDesdeInscripcion($ins);
    }
    if ($idPersona <= 0) {
        $sinPersona[] = $ins;
        continue;
    }
    if (!isset($porPersona[$idPersona])) {
        $porPersona[$idPersona] = $ins;
    }
}

$filasPreview = [];
foreach ($porPersona as $idPersona => $ins) {
    $persona = $personaModel->getById($idPersona);
    if (empty($persona)) {
        continue;
    }
    $cedula = AccesoDiscipuloCapDestino::normalizarDocumento((string)($ins['Cedula'] ?? ''));
    if ($cedula === '') {
        $cedula = AccesoDiscipuloCapDestino::normalizarDocumento((string)($persona['Numero_Documento'] ?? ''));
    }
    $roles = $userRoleModel->listarRolesPersona($idPersona);
    $nombresRoles = array_map(static fn($r) => (string)($r['Nombre_Rol'] ?? ''), $roles);

    $filasPreview[] = [
        'Id_Persona' => $idPersona,
        'Nombre' => trim(
            (string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? '')
            ?: (string)($ins['Nombre'] ?? '')
        ),
        'Cedula' => $cedula,
        'Programa' => (string)($ins['Programa'] ?? ''),
        'Rol_Principal' => (string)($persona['Nombre_Rol'] ?? ''),
        'Es_Liderazgo' => esLiderazgoPorPersona($personaModel, $persona),
        'Tiene_Usuario' => trim((string)($persona['Usuario'] ?? '')) !== '',
        'Usuario' => trim((string)($persona['Usuario'] ?? '')),
        'Roles_Actuales' => implode(' | ', array_filter($nombresRoles)),
        'Puede_Provisionar' => $cedula !== '',
    ];
}

$ok = 0;
$fail = 0;
$errores = [];

if ($apply) {
    foreach ($porPersona as $idPersona => $ins) {
        try {
            if (AccesoDiscipuloCapDestino::provisionarDesdeInscripcion($ins)) {
                $ok++;
            } else {
                $fail++;
                $errores[] = 'Id_Persona ' . $idPersona . ': no se pudo provisionar (sin cédula o rol discípulo).';
            }
        } catch (Throwable $e) {
            $fail++;
            $errores[] = 'Id_Persona ' . $idPersona . ': ' . $e->getMessage();
        }
    }
}

// --- Salida CLI ---
if ($isCli) {
    echo 'Inscripciones Cap. Destino: ' . count($inscripciones) . "\n";
    echo 'Personas únicas a procesar: ' . count($porPersona) . "\n";
    echo 'Sin persona resoluble: ' . count($sinPersona) . "\n";
    if (!$apply) {
        echo "Modo DRY-RUN. Usa --apply para ejecutar.\n";
        exit(0);
    }
    echo "Aplicado OK: {$ok} | Fallos: {$fail}\n";
    foreach ($errores as $err) {
        echo "- {$err}\n";
    }
    exit($fail > 0 ? 1 : 0);
}

// --- Salida HTML ---
header('Content-Type: text/html; charset=UTF-8');
$authQs = queryParamAuth();
$self = basename(__FILE__);
$urlDry = $self . '?' . $authQs . '&mode=dry-run';
$urlApply = $self . '?' . $authQs . '&mode=apply&confirm=SI';

$total = count($filasPreview);
$lideres = count(array_filter($filasPreview, static fn($f) => $f['Es_Liderazgo']));
$conUsuario = count(array_filter($filasPreview, static fn($f) => $f['Tiene_Usuario']));
$sinCedula = count(array_filter($filasPreview, static fn($f) => !$f['Puede_Provisionar']));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Provisionar acceso Cap. Destino</title>
    <style>
        body { font-family: system-ui, sans-serif; margin: 1.5rem; color: #1a1a1a; }
        h1 { font-size: 1.35rem; }
        .meta { background: #f4f6f8; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .ok { color: #0d6b0d; }
        .warn { color: #a66b00; }
        .err { color: #b00020; }
        table { border-collapse: collapse; width: 100%; font-size: 0.85rem; }
        th, td { border: 1px solid #ddd; padding: 0.4rem 0.5rem; text-align: left; }
        th { background: #eee; position: sticky; top: 0; }
        .actions { margin: 1rem 0; }
        .btn { display: inline-block; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-weight: 600; margin-right: 0.5rem; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-muted { background: #e5e7eb; color: #111; }
        ul.errores { color: #b00020; }
    </style>
</head>
<body>
<h1>Provisionar acceso discípulo — Capacitación Destino</h1>

<div class="meta">
    <p><strong>Modo:</strong>
        <?php if ($apply): ?>
            <span class="ok">APLICADO (cambios en BD)</span>
        <?php else: ?>
            <span class="warn">SIMULACIÓN (sin cambios)</span>
        <?php endif; ?>
    </p>
    <p>Inscripciones: <strong><?= count($inscripciones) ?></strong> —
       Personas únicas: <strong><?= $total ?></strong> —
       Líderes (mantienen rol principal + 2º discípulo): <strong><?= $lideres ?></strong> —
       Ya con usuario (no se cambia clave): <strong><?= $conUsuario ?></strong> —
       Sin cédula: <strong class="err"><?= $sinCedula ?></strong> —
       Sin persona en BD: <strong class="err"><?= count($sinPersona) ?></strong></p>
    <?php if ($apply): ?>
        <p class="ok">Provisionados OK: <strong><?= (int)$ok ?></strong> — Fallos: <strong><?= (int)$fail ?></strong></p>
    <?php endif; ?>
</div>

<div class="actions">
    <a class="btn btn-muted" href="<?= h($urlDry) ?>">Ver simulación</a>
    <?php if (!$apply): ?>
        <a class="btn btn-danger" href="<?= h($urlApply) ?>"
           onclick="return confirm('¿Aplicar acceso discípulo a <?= (int)$total ?> personas?');">Aplicar cambios</a>
    <?php endif; ?>
</div>

<?php if ($apply && !empty($errores)): ?>
<ul class="errores">
    <?php foreach ($errores as $err): ?>
        <li><?= h($err) ?></li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>

<?php if (!empty($sinPersona)): ?>
<h2 class="err">Inscripciones sin persona (revisar manual)</h2>
<table>
    <tr><th>Id_Inscripcion</th><th>Programa</th><th>Cédula</th><th>Nombre</th><th>Teléfono</th></tr>
    <?php foreach (array_slice($sinPersona, 0, 100) as $ins): ?>
    <tr>
        <td><?= (int)($ins['Id_Inscripcion'] ?? 0) ?></td>
        <td><?= h((string)($ins['Programa'] ?? '')) ?></td>
        <td><?= h((string)($ins['Cedula'] ?? '')) ?></td>
        <td><?= h((string)($ins['Nombre'] ?? '')) ?></td>
        <td><?= h((string)($ins['Telefono'] ?? '')) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<h2>Detalle (<?= $total ?> personas)</h2>
<div style="overflow:auto; max-height:70vh;">
<table>
    <thead>
    <tr>
        <th>Id</th>
        <th>Nombre</th>
        <th>Cédula</th>
        <th>Programa</th>
        <th>Rol principal</th>
        <th>¿Líder?</th>
        <th>Usuario actual</th>
        <th>Roles en user_roles</th>
        <th>¿OK?</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($filasPreview as $f): ?>
    <tr>
        <td><?= (int)$f['Id_Persona'] ?></td>
        <td><?= h($f['Nombre']) ?></td>
        <td><?= h($f['Cedula']) ?></td>
        <td><?= h($f['Programa']) ?></td>
        <td><?= h($f['Rol_Principal']) ?></td>
        <td><?= $f['Es_Liderazgo'] ? 'Sí' : 'No' ?></td>
        <td><?= $f['Tiene_Usuario'] ? h($f['Usuario']) : '<em>(nuevo = cédula)</em>' ?></td>
        <td><?= h($f['Roles_Actuales']) ?></td>
        <td><?= $f['Puede_Provisionar'] ? 'Sí' : '<span class="err">Sin cédula</span>' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<p><small>Usuario y contraseña = cédula (bcrypt). Líderes conservan su rol principal y reciben segundo rol Discípulo.</small></p>
<p><small><strong>Importante:</strong> borra o renombra este archivo en el servidor cuando termines.</small></p>
</body>
</html>
