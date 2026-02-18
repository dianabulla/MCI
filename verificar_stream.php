<?php
/**
 * Script de verificación del sistema ESP32-CAM
 * Verifica que todos los archivos y permisos estén correctos
 */

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación Sistema ESP32-CAM</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #252526;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        h1 {
            color: #4ec9b0;
            margin-bottom: 10px;
            font-size: 1.8em;
        }
        .subtitle {
            color: #858585;
            margin-bottom: 30px;
            font-size: 0.9em;
        }
        .check-group {
            margin-bottom: 30px;
        }
        .check-group h2 {
            color: #569cd6;
            font-size: 1.2em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3e3e42;
        }
        .check-item {
            padding: 12px;
            margin-bottom: 8px;
            background: #1e1e1e;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .check-label {
            color: #d4d4d4;
        }
        .check-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }
        .status-ok {
            background: #2d6a2e;
            color: #4ec9b0;
        }
        .status-error {
            background: #5a1d1d;
            color: #f48771;
        }
        .status-warning {
            background: #5a4d1d;
            color: #dcdcaa;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #1e1e1e;
            border-radius: 8px;
            border-left: 4px solid #4ec9b0;
        }
        .summary h3 {
            color: #4ec9b0;
            margin-bottom: 10px;
        }
        .code-block {
            background: #1e1e1e;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            overflow-x: auto;
            font-size: 0.85em;
            color: #ce9178;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificación Sistema ESP32-CAM</h1>
        <p class="subtitle">Comprobando instalación y configuración...</p>

<?php

$checks = [];
$errors = 0;
$warnings = 0;

// Verificar carpeta de stream
$streamDir = __DIR__ . '/public/assets/stream/';
$checks['Carpeta stream existe'] = file_exists($streamDir);
if (!$checks['Carpeta stream existe']) $errors++;

$checks['Carpeta stream escribible'] = is_writable($streamDir);
if ($checks['Carpeta stream existe'] && !$checks['Carpeta stream escribible']) $errors++;

// Verificar archivos
$requiredFiles = [
    'API Stream' => __DIR__ . '/api/stream.php',
    'Controlador Stream' => __DIR__ . '/app/Controllers/StreamController.php',
    'Vista Live' => __DIR__ . '/views/stream/live.php',
    'Vista Gallery' => __DIR__ . '/views/stream/gallery.php',
    'Página de pruebas' => __DIR__ . '/test_esp32cam.html',
    'Guía ESP32-CAM' => __DIR__ . '/ESP32_CAM_SETUP.md',
    'Documentación' => __DIR__ . '/STREAM_README.md',
];

echo '<div class="check-group">';
echo '<h2>📁 Archivos del Sistema</h2>';

foreach ($requiredFiles as $name => $path) {
    $exists = file_exists($path);
    $checks[$name] = $exists;
    if (!$exists) $errors++;
    
    echo '<div class="check-item">';
    echo '<span class="check-label">' . htmlspecialchars($name) . '</span>';
    echo '<span class="check-status ' . ($exists ? 'status-ok' : 'status-error') . '">';
    echo $exists ? '✓ OK' : '✗ FALTA';
    echo '</span>';
    echo '</div>';
}

echo '</div>';

// Verificar permisos
echo '<div class="check-group">';
echo '<h2>🔐 Permisos y Carpetas</h2>';

echo '<div class="check-item">';
echo '<span class="check-label">Carpeta stream existe</span>';
echo '<span class="check-status ' . ($checks['Carpeta stream existe'] ? 'status-ok' : 'status-error') . '">';
echo $checks['Carpeta stream existe'] ? '✓ OK' : '✗ NO EXISTE';
echo '</span>';
echo '</div>';

echo '<div class="check-item">';
echo '<span class="check-label">Carpeta stream escribible</span>';
echo '<span class="check-status ' . ($checks['Carpeta stream escribible'] ? 'status-ok' : 'status-error') . '">';
echo $checks['Carpeta stream escribible'] ? '✓ OK' : '✗ SIN PERMISOS';
echo '</span>';
echo '</div>';

// Verificar si hay imágenes
$imageCount = 0;
if (file_exists($streamDir)) {
    $images = glob($streamDir . 'stream_*.jpg');
    $imageCount = count($images);
}

echo '<div class="check-item">';
echo '<span class="check-label">Imágenes almacenadas</span>';
echo '<span class="check-status ' . ($imageCount > 0 ? 'status-ok' : 'status-warning') . '">';
echo $imageCount . ' fotos';
echo '</span>';
echo '</div>';

echo '</div>';

// Verificar rutas
echo '<div class="check-group">';
echo '<h2>🛣️ Configuración de Rutas</h2>';

$routesFile = __DIR__ . '/app/Config/routes.php';
$indexFile = __DIR__ . '/public/index.php';

$routesContent = file_exists($routesFile) ? file_get_contents($routesFile) : '';
$indexContent = file_exists($indexFile) ? file_get_contents($indexFile) : '';

$routeLiveExists = strpos($routesContent, 'stream/live') !== false;
$routeGalleryExists = strpos($routesContent, 'stream/gallery') !== false;
$publicRouteExists = strpos($indexContent, 'stream/live') !== false;

echo '<div class="check-item">';
echo '<span class="check-label">Ruta stream/live configurada</span>';
echo '<span class="check-status ' . ($routeLiveExists ? 'status-ok' : 'status-error') . '">';
echo $routeLiveExists ? '✓ OK' : '✗ FALTA';
echo '</span>';
echo '</div>';

if (!$routeLiveExists) $errors++;

echo '<div class="check-item">';
echo '<span class="check-label">Ruta stream/gallery configurada</span>';
echo '<span class="check-status ' . ($routeGalleryExists ? 'status-ok' : 'status-error') . '">';
echo $routeGalleryExists ? '✓ OK' : '✗ FALTA';
echo '</span>';
echo '</div>';

if (!$routeGalleryExists) $errors++;

echo '<div class="check-item">';
echo '<span class="check-label">Rutas públicas configuradas</span>';
echo '<span class="check-status ' . ($publicRouteExists ? 'status-ok' : 'status-error') . '">';
echo $publicRouteExists ? '✓ OK' : '✗ FALTA';
echo '</span>';
echo '</div>';

if (!$publicRouteExists) $errors++;

echo '</div>';

// Verificar PHP
echo '<div class="check-group">';
echo '<h2>⚙️ Entorno PHP</h2>';

$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '7.0', '>=');

echo '<div class="check-item">';
echo '<span class="check-label">Versión PHP</span>';
echo '<span class="check-status ' . ($phpOk ? 'status-ok' : 'status-error') . '">';
echo $phpVersion;
echo '</span>';
echo '</div>';

$extensions = ['gd', 'json', 'fileinfo'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo '<div class="check-item">';
    echo '<span class="check-label">Extensión ' . $ext . '</span>';
    echo '<span class="check-status ' . ($loaded ? 'status-ok' : 'status-warning') . '">';
    echo $loaded ? '✓ OK' : '⚠ NO INSTALADA';
    echo '</span>';
    echo '</div>';
    if (!$loaded) $warnings++;
}

echo '</div>';

// Resumen
echo '<div class="summary">';
echo '<h3>📊 Resumen</h3>';

if ($errors === 0 && $warnings === 0) {
    echo '<p style="color: #4ec9b0;">✓ Todo está configurado correctamente</p>';
    echo '<p style="margin-top: 10px; color: #858585;">El sistema está listo para recibir transmisiones desde ESP32-CAM</p>';
} else {
    if ($errors > 0) {
        echo '<p style="color: #f48771;">✗ Se encontraron ' . $errors . ' error(es) crítico(s)</p>';
    }
    if ($warnings > 0) {
        echo '<p style="color: #dcdcaa;">⚠ Se encontraron ' . $warnings . ' advertencia(s)</p>';
    }
}

echo '<div class="code-block">';
echo '<strong>URLs de Acceso:</strong><br>';
echo '📹 Live: ' . htmlspecialchars($_SERVER['HTTP_HOST']) . '/mci_madrid_colombia/public/index.php?route=stream/live<br>';
echo '🖼️ Gallery: ' . htmlspecialchars($_SERVER['HTTP_HOST']) . '/mci_madrid_colombia/public/index.php?route=stream/gallery<br>';
echo '🔧 API: ' . htmlspecialchars($_SERVER['HTTP_HOST']) . '/mci_madrid_colombia/api/stream.php<br>';
echo '🧪 Test: ' . htmlspecialchars($_SERVER['HTTP_HOST']) . '/mci_madrid_colombia/test_esp32cam.html';
echo '</div>';

if (!$checks['Carpeta stream escribible'] && $checks['Carpeta stream existe']) {
    echo '<div class="code-block" style="margin-top: 15px;">';
    echo '<strong>⚠️ Para corregir permisos (Windows):</strong><br>';
    echo '1. Click derecho en la carpeta "stream"<br>';
    echo '2. Propiedades > Seguridad > Editar<br>';
    echo '3. Dar control total a tu usuario';
    echo '</div>';
}

echo '</div>';

?>
    </div>
</body>
</html>
