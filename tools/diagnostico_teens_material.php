<?php
/**
 * Diagnóstico: material teens (BD vs archivos en disco).
 * Uso: https://tu-dominio/tools/diagnostico_teens_material.php
 * Requiere sesión de administrador (abre primero la app en el navegador).
 */
declare(strict_types=1);

session_start();

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');

require_once ROOT . '/conexion.php';
require_once APP . '/Config/config.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Models/Teen.php';
require_once APP . '/Controllers/TeenController.php';

if (!AuthController::estaAutenticado() || !AuthController::esAdministrador()) {
    http_response_code(403);
    echo 'Acceso denegado. Inicia sesión como administrador en la aplicación y vuelve a abrir esta página.';
    exit;
}

$controller = new TeenController();
$ref = new ReflectionClass($controller);
$parsear = $ref->getMethod('parsearArchivosPdfRegistro');
$parsear->setAccessible(true);
$clave = $ref->getMethod('obtenerClaveComparacionPdfTeen');
$clave->setAccessible(true);
$dirMethod = $ref->getMethod('obtenerDirectorioMaterialesTeen');
$dirMethod->setAccessible(true);

$dirPrincipal = $dirMethod->invoke($controller);
$dirsLegacy = [
    ROOT . '/uploads/teens',
    ROOT . '/public/uploads/material_hub/teens',
    ROOT . '/public/uploads/material_teens',
];
$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
if ($docRoot !== '') {
    $dirsLegacy[] = $docRoot . '/uploads/teens';
    $dirsLegacy[] = $docRoot . '/public/uploads/teens';
}

$pdfsEnDisco = [];
foreach (array_merge([$dirPrincipal], $dirsLegacy) as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    foreach (@scandir($dir) ?: [] as $f) {
        if ($f === '.' || $f === '..' || strtolower(pathinfo($f, PATHINFO_EXTENSION)) !== 'pdf') {
            continue;
        }
        $pdfsEnDisco[strtolower($f)] = ['nombre' => $f, 'dir' => $dir];
    }
}

$teenModel = new Teen();
$materiales = $teenModel->getAll();
$esperadosBd = [];

header('Content-Type: text/html; charset=utf-8');
echo '<h1>Diagnóstico material Teens</h1>';
echo '<p><strong>ROOT:</strong> ' . htmlspecialchars(ROOT) . '</p>';
echo '<p><strong>Carpeta principal:</strong> ' . htmlspecialchars($dirPrincipal) . ' — ' . (is_dir($dirPrincipal) ? 'existe' : 'NO existe') . '</p>';
echo '<p><strong>PDF en disco (todas las rutas):</strong> ' . count($pdfsEnDisco) . '</p>';
echo '<p><a href="../public/index.php?url=teen/recuperar-archivos">Buscar y emparejar archivos automáticamente</a></p>';

$ok = 0;
$faltan = 0;
$emparejable = 0;

echo '<table border="1" cellpadding="6" style="border-collapse:collapse;font-size:14px;width:100%;">';
echo '<tr><th>ID</th><th>Título</th><th>Archivo en BD</th><th>Estado</th></tr>';

foreach ((array)$materiales as $material) {
    $nombres = $parsear->invoke($controller, $material['archivos_pdf'] ?? '');
    if (empty($nombres)) {
        echo '<tr><td>' . (int)($material['id'] ?? 0) . '</td><td>' . htmlspecialchars((string)($material['titulo'] ?? '')) . '</td><td colspan="2"><em>JSON vacío o ilegible</em></td></tr>';
        continue;
    }

    foreach ($nombres as $nombre) {
        $esperadosBd[strtolower($nombre)] = true;
        $ruta = $dirPrincipal . '/' . $nombre;
        $estado = is_file($ruta) ? 'OK (nombre exacto)' : 'Falta nombre exacto';

        if ($estado !== 'OK (nombre exacto)') {
            $lower = strtolower($nombre);
            if (isset($pdfsEnDisco[$lower])) {
                $estado = 'En disco con mismo nombre en: ' . basename($pdfsEnDisco[$lower]['dir']);
                $emparejable++;
            } else {
                $slug = $clave->invoke($controller, $nombre);
                $match = null;
                foreach ($pdfsEnDisco as $disk) {
                    if ($clave->invoke($controller, $disk['nombre']) === $slug) {
                        $match = $disk;
                        break;
                    }
                }
                if ($match !== null) {
                    $estado = 'Emparejable → en disco como: ' . htmlspecialchars($match['nombre']) . ' (usa emparejar)';
                    $emparejable++;
                } else {
                    $faltan++;
                }
            }
        } else {
            $ok++;
        }

        echo '<tr>';
        echo '<td>' . (int)($material['id'] ?? 0) . '</td>';
        echo '<td>' . htmlspecialchars((string)($material['titulo'] ?? '')) . '</td>';
        echo '<td><code>' . htmlspecialchars($nombre) . '</code></td>';
        echo '<td>' . htmlspecialchars($estado) . '</td>';
        echo '</tr>';
    }
}

echo '</table>';

$huerfanos = [];
foreach ($pdfsEnDisco as $disk) {
    $slug = $clave->invoke($controller, $disk['nombre']);
    $usado = false;
    foreach ($esperadosBd as $nomLower => $_) {
        if ($clave->invoke($controller, $nomLower) === $slug || $nomLower === strtolower($disk['nombre'])) {
            $usado = true;
            break;
        }
    }
    if (!$usado) {
        $huerfanos[] = $disk['nombre'] . ' (' . $disk['dir'] . ')';
    }
}

echo '<p><strong>OK:</strong> ' . $ok . ' · <strong>Sin archivo:</strong> ' . $faltan . ' · <strong>Emparejables:</strong> ' . $emparejable . '</p>';

if (!empty($huerfanos)) {
    echo '<h2>PDF en disco no referenciados en BD (' . count($huerfanos) . ')</h2><ul>';
    foreach (array_slice($huerfanos, 0, 30) as $h) {
        echo '<li><code>' . htmlspecialchars($h) . '</code></li>';
    }
    echo '</ul>';
}

echo '<p>Los archivos en BD deben existir en <code>' . htmlspecialchars($dirPrincipal) . '</code>. Si subiste por FTP con otro nombre, usa emparejar o renómbralos.</p>';
