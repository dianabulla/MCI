<?php
// Sincroniza acceso ministerial para discípulos sin fila en usuario_acceso.
// Usuario = cédula, clave = hash de la cédula, estado de acceso = Activo.
// Uso:
//   php tools/sync_discipulos_access.php --dry-run
//   php tools/sync_discipulos_access.php --apply
//
// Uso navegador (seguro con token):
//   /tools/sync_discipulos_access.php?token=TU_TOKEN&mode=dry-run
//   /tools/sync_discipulos_access.php?token=TU_TOKEN&mode=apply&confirm=SI
//
// Token esperado:
//   hash('sha256', DB_HOST . '|' . DB_NAME . '|' . DB_USER . '|' . DB_PASS)

require_once __DIR__ . '/../app/Config/config.php';

$isCli = (PHP_SAPI === 'cli');

if (!$isCli) {
    header('Content-Type: text/plain; charset=UTF-8');
}

$apply = false;
$dryRun = true;

if ($isCli) {
    $apply = in_array('--apply', $argv, true);
    $dryRun = in_array('--dry-run', $argv, true) || !$apply;
} else {
    $expectedToken = hash('sha256', (string)DB_HOST . '|' . (string)DB_NAME . '|' . (string)DB_USER . '|' . (string)(defined('DB_PASS') ? DB_PASS : ''));
    $token = (string)($_GET['token'] ?? '');
    $mode = strtolower(trim((string)($_GET['mode'] ?? 'dry-run')));
    $confirm = strtoupper(trim((string)($_GET['confirm'] ?? '')));

    if ($token === '' || !hash_equals($expectedToken, $token)) {
        http_response_code(403);
        echo "Acceso denegado. Token inválido o ausente.\n";
        echo "Modo permitido por navegador:\n";
        echo "- dry-run: ?token=TU_TOKEN&mode=dry-run\n";
        echo "- apply:   ?token=TU_TOKEN&mode=apply&confirm=SI\n";
        exit(1);
    }

    $apply = ($mode === 'apply' && $confirm === 'SI');
    $dryRun = !$apply;
}

if (!defined('DB_HOST') || !defined('DB_NAME') || !defined('DB_USER')) {
    if ($isCli) {
        fwrite(STDERR, "No se pudo cargar config de BD.\n");
    } else {
        echo "No se pudo cargar config de BD.\n";
    }
    exit(1);
}

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4');

try {
    $pdo = new PDO($dsn, DB_USER, defined('DB_PASS') ? DB_PASS : '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    if ($isCli) {
        fwrite(STDERR, 'Error de conexion: ' . $e->getMessage() . "\n");
    } else {
        echo 'Error de conexion: ' . $e->getMessage() . "\n";
    }
    exit(1);
}

$sql = "SELECT p.Id_Persona, p.Numero_Documento, p.Id_Rol, p.Id_Ministerio, r.Nombre_Rol,
               TRIM(CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, ''))) AS Nombre_Mostrar
        FROM persona p
        INNER JOIN rol r ON r.Id_Rol = p.Id_Rol
        INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
        LEFT JOIN usuario_acceso ua ON ua.Id_Persona = p.Id_Persona
        WHERE (
            p.Id_Rol = 2
            OR LOWER(r.Nombre_Rol) LIKE '%discipul%'
            OR LOWER(r.Nombre_Rol) LIKE '%disipul%'
            OR LOWER(r.Nombre_Rol) LIKE '%discipl%'
            OR LOWER(r.Nombre_Rol) LIKE '%disipl%'
        )
        AND ua.Id_Usuario_Acceso IS NULL
        AND (p.Numero_Documento IS NOT NULL AND TRIM(p.Numero_Documento) <> '')
        GROUP BY p.Id_Persona, p.Numero_Documento, p.Id_Rol, p.Id_Ministerio, r.Nombre_Rol, p.Nombre, p.Apellido
        ORDER BY p.Id_Persona ASC";

$rows = $pdo->query($sql)->fetchAll();
$total = count($rows);

echo "Discipulos sin cuenta de acceso: {$total}\n";

if ($total === 0) {
    exit(0);
}

if ($dryRun) {
    echo "Modo DRY-RUN (sin cambios). Primeros 20:\n";
    $limit = min(20, $total);
    for ($i = 0; $i < $limit; $i++) {
        $r = $rows[$i];
        echo '- Id_Persona=' . (int)$r['Id_Persona'] . ' | Doc=' . (string)$r['Numero_Documento'] . ' | Rol=' . (string)$r['Nombre_Rol'] . "\n";
    }
    echo "\nPara aplicar cambios:\n";
    if ($isCli) {
        echo "php tools/sync_discipulos_access.php --apply\n";
    } else {
        echo "?token=TU_TOKEN&mode=apply&confirm=SI\n";
    }
    exit(0);
}

$insert = $pdo->prepare("INSERT INTO usuario_acceso
    (Usuario, Contrasena, Nombre_Mostrar, Id_Rol, Id_Ministerio, Id_Persona, Estado_Cuenta)
    VALUES (?, ?, ?, ?, ?, ?, 'Activo')");
$existeUsuario = $pdo->prepare("SELECT Id_Usuario_Acceso FROM usuario_acceso WHERE Usuario = ? LIMIT 1");

$updated = 0;
$omitidas = 0;
$pdo->beginTransaction();
try {
    foreach ($rows as $r) {
        $doc = trim((string)$r['Numero_Documento']);
        $idPersona = (int)$r['Id_Persona'];
        $idRol = (int)$r['Id_Rol'];
        if ($doc === '' || $idPersona <= 0 || $idRol <= 0) {
            $omitidas++;
            continue;
        }
        $existeUsuario->execute([$doc]);
        if ($existeUsuario->fetch()) {
            $omitidas++;
            continue;
        }
        $idMinisterio = (int)($r['Id_Ministerio'] ?? 0);
        $nombre = trim((string)($r['Nombre_Mostrar'] ?? ''));
        $hash = password_hash($doc, PASSWORD_BCRYPT);
        try {
            $ok = $insert->execute([
                $doc,
                $hash,
                $nombre !== '' ? $nombre : $doc,
                $idRol,
                $idMinisterio > 0 ? $idMinisterio : null,
                $idPersona,
            ]);
        } catch (Throwable $e) {
            $ok = $insert->execute([
                $doc,
                $hash,
                $nombre !== '' ? $nombre : $doc,
                $idRol,
                null,
                $idPersona,
            ]);
        }
        if ($ok) {
            $updated++;
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    if ($isCli) {
        fwrite(STDERR, 'Error aplicando cambios: ' . $e->getMessage() . "\n");
    } else {
        echo 'Error aplicando cambios: ' . $e->getMessage() . "\n";
    }
    exit(1);
}

echo "Creadas: {$updated}\n";
echo "Omitidas: {$omitidas}\n";

$verifySql = "SELECT COUNT(*) AS total
              FROM persona p
              INNER JOIN rol r ON r.Id_Rol = p.Id_Rol
              INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
              LEFT JOIN usuario_acceso ua ON ua.Id_Persona = p.Id_Persona
              WHERE (
                  p.Id_Rol = 2
                  OR LOWER(r.Nombre_Rol) LIKE '%discipul%'
                  OR LOWER(r.Nombre_Rol) LIKE '%disipul%'
                  OR LOWER(r.Nombre_Rol) LIKE '%discipl%'
                  OR LOWER(r.Nombre_Rol) LIKE '%disipl%'
              )
              AND ua.Id_Usuario_Acceso IS NULL
              AND (p.Numero_Documento IS NOT NULL AND TRIM(p.Numero_Documento) <> '')";

$left = (int)$pdo->query($verifySql)->fetchColumn();
echo "Pendientes sin cuenta de acceso despues de sincronizar: {$left}\n";
