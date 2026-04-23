<?php
/**
 * Herramienta de administración: Asignar rol Discipulo a personas sin rol
 *
 * Actualiza Id_Rol = <id_discipulo> en todas las personas donde
 * Id_Rol IS NULL o Id_Rol = 0.
 *
 * Acceso: solo desde IP local o con clave de seguridad.
 * URL de ejemplo: /tools/asignar_rol_discipulo_sin_rol.php?clave=TU_CLAVE_AQUI
 */

// ─── Seguridad ────────────────────────────────────────────────────────────────

define('CLAVE_ACCESO', 'mci_admin_2026');   // ← Cambia esto antes de usar en producción

$claveRecibida = trim((string)($_GET['clave'] ?? ''));
$ipCliente     = $_SERVER['REMOTE_ADDR'] ?? '';
$ipsLocales    = ['127.0.0.1', '::1'];
$esLocal       = in_array($ipCliente, $ipsLocales, true);

if (!$esLocal && !hash_equals(CLAVE_ACCESO, $claveRecibida)) {
    http_response_code(403);
    exit('Acceso denegado. Incluye ?clave=TU_CLAVE en la URL.');
}

// ─── Bootstrap ────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../conexion.php';  // provee $pdo

// ─── Helpers de salida ────────────────────────────────────────────────────────

$soloSimulacion = isset($_GET['simular']);   // ?simular para dry-run sin escribir

header('Content-Type: text/html; charset=utf-8');

function titulo(string $t): void {
    echo "<h2 style='font-family:sans-serif;margin-top:30px'>$t</h2>\n";
}
function linea(string $msg, string $color = '#333'): void {
    echo "<p style='font-family:monospace;color:$color;margin:4px 0'>$msg</p>\n";
}
function ok(string $msg): void   { linea('✅ ' . $msg, '#155724'); }
function info(string $msg): void { linea('ℹ️  ' . $msg, '#004085'); }
function warn(string $msg): void { linea('⚠️  ' . $msg, '#856404'); }
function error(string $msg): void{ linea('❌ ' . $msg, '#721c24'); }

// ─── Inicio ───────────────────────────────────────────────────────────────────

echo '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8">
<title>Asignar rol Discipulo · Sin rol</title>
<style>body{background:#f5f5f5;padding:20px} pre{background:#eee;padding:10px;border-radius:4px}</style>
</head><body>';

titulo('Asignar rol Discipulo a personas sin rol');

if ($soloSimulacion) {
    warn('MODO SIMULACIÓN — no se escribirá nada en la base de datos.');
    warn('Quita <code>?simular</code> de la URL para ejecutar de verdad.');
    echo '<hr>';
}

// ─── 1. Buscar Id del rol a asignar ──────────────────────────────────────────

try {
    $roles = $pdo->query("SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC")->fetchAll();
} catch (PDOException $e) {
    error('No se pudo leer la tabla rol: ' . htmlspecialchars($e->getMessage()));
    echo '</body></html>';
    exit;
}

// Parámetro manual: ?id_rol=X sobreescribe la búsqueda automática
$idRolForzado = (int)($_GET['id_rol'] ?? 0);

$idRolDiscipulo  = 0;
$nombreDiscipulo = '';

if ($idRolForzado > 0) {
    // Buscar el rol con ese Id exacto
    foreach ($roles as $row) {
        if ((int)$row['Id_Rol'] === $idRolForzado) {
            $idRolDiscipulo  = $idRolForzado;
            $nombreDiscipulo = (string)$row['Nombre_Rol'];
            break;
        }
    }
    if ($idRolDiscipulo <= 0) {
        error("El id_rol=$idRolForzado no existe en la tabla <code>rol</code>.");
        echo '</body></html>';
        exit;
    }
} else {
    // Auto-detección por nombre
    foreach ($roles as $row) {
        $nombre = strtolower(trim((string)($row['Nombre_Rol'] ?? '')));
        $nombre = strtr($nombre, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
        if (strpos($nombre, 'discipulo') !== false || strpos($nombre, 'disipulo') !== false || strpos($nombre, 'miembro') !== false) {
            $idRolDiscipulo  = (int)$row['Id_Rol'];
            $nombreDiscipulo = (string)$row['Nombre_Rol'];
            break;
        }
    }
}

if ($idRolDiscipulo <= 0) {
    warn('No se encontró un rol "Discipulo"/"Miembro" automáticamente.');
    warn('Elige el rol que quieres asignar haciendo clic en uno de estos enlaces:');
    $baseUrl = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    $extras  = $soloSimulacion ? '&simular' : '';
    $claveParam = $claveRecibida !== '' ? '&clave=' . urlencode($claveRecibida) : '';
    echo '<ul style="font-family:sans-serif">';
    foreach ($roles as $r) {
        $url = $baseUrl . '?id_rol=' . (int)$r['Id_Rol'] . $claveParam . $extras;
        echo '<li><a href="' . htmlspecialchars($url) . '">'
            . 'Asignar <strong>' . htmlspecialchars($r['Nombre_Rol']) . '</strong>'
            . ' (Id=' . (int)$r['Id_Rol'] . ')</a></li>';
    }
    echo '</ul>';
    echo '</body></html>';
    exit;
}

info("Rol seleccionado: <strong>" . htmlspecialchars($nombreDiscipulo) . "</strong> (Id_Rol = $idRolDiscipulo)");

// ─── 2. Contar personas sin rol ───────────────────────────────────────────────

try {
    $totalStmt = $pdo->query(
        "SELECT COUNT(*) AS total FROM persona WHERE Id_Rol IS NULL OR Id_Rol = 0"
    );
    $total = (int)($totalStmt->fetchColumn() ?? 0);
} catch (PDOException $e) {
    error('No se pudo consultar personas: ' . htmlspecialchars($e->getMessage()));
    echo '</body></html>';
    exit;
}

if ($total === 0) {
    ok('No hay personas sin rol. Nada que actualizar.');
    echo '</body></html>';
    exit;
}

info("Personas sin rol (Id_Rol IS NULL o 0): <strong>$total</strong>");

// ─── 3. Listar previamente (máx 50) ──────────────────────────────────────────

titulo('Vista previa (máx. 50 registros)');

try {
    $previas = $pdo->query(
        "SELECT Id_Persona, Nombre, Apellido, Id_Rol FROM persona
         WHERE Id_Rol IS NULL OR Id_Rol = 0
         ORDER BY Id_Persona ASC LIMIT 50"
    )->fetchAll();
} catch (PDOException $e) {
    error('Error al listar: ' . htmlspecialchars($e->getMessage()));
    echo '</body></html>';
    exit;
}

echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-family:sans-serif;font-size:13px">
<tr style="background:#ddd"><th>Id</th><th>Nombre</th><th>Apellido</th><th>Id_Rol actual</th></tr>';
foreach ($previas as $p) {
    $rolActual = $p['Id_Rol'] === null ? 'NULL' : $p['Id_Rol'];
    echo '<tr>'
        . '<td>' . htmlspecialchars((string)$p['Id_Persona']) . '</td>'
        . '<td>' . htmlspecialchars((string)$p['Nombre'])     . '</td>'
        . '<td>' . htmlspecialchars((string)$p['Apellido'])   . '</td>'
        . '<td style="color:#c00">' . htmlspecialchars((string)$rolActual) . '</td>'
        . '</tr>';
}
echo '</table>';

if ($total > 50) {
    warn("...y " . ($total - 50) . " más (solo se listan 50 en preview).");
}

// ─── 4. Ejecutar actualización ────────────────────────────────────────────────

titulo('Resultado');

if ($soloSimulacion) {
    warn("Simulación: se <em>habrían</em> actualizado $total personas → rol \"$nombreDiscipulo\" (Id=$idRolDiscipulo).");
    warn('Accede sin <code>?simular</code> para aplicar los cambios.');
} else {
    try {
        $stmt = $pdo->prepare(
            "UPDATE persona SET Id_Rol = :idRol WHERE Id_Rol IS NULL OR Id_Rol = 0"
        );
        $stmt->execute([':idRol' => $idRolDiscipulo]);
        $afectadas = $stmt->rowCount();
        ok("Actualización completada. Filas afectadas: <strong>$afectadas</strong>");
        ok("Rol asignado: \"$nombreDiscipulo\" (Id_Rol = $idRolDiscipulo)");
    } catch (PDOException $e) {
        error('Error al actualizar: ' . htmlspecialchars($e->getMessage()));
    }
}

echo '</body></html>';
