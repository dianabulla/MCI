<?php
require_once dirname(__DIR__) . '/conexion.php';

echo "<h2>Debug Teen Module</h2>";

$stmt = $pdo->query("SELECT id, titulo, archivos_pdf, LENGTH(archivos_pdf) as longitud FROM teens ORDER BY id DESC LIMIT 10");
$registros = $stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Título</th><th>Longitud JSON</th><th>Contenido JSON</th><th>Decodificado</th></tr>";

foreach ($registros as $reg) {
    $id = $reg['id'];
    $titulo = htmlspecialchars((string)($reg['titulo'] ?? ''));
    $longitud = $reg['longitud'] ?? 0;
    $json = (string)($reg['archivos_pdf'] ?? '');
    $decodificado = json_decode($json, true);
    
    echo "<tr>";
    echo "<td>$id</td>";
    echo "<td>$titulo</td>";
    echo "<td>$longitud bytes</td>";
    echo "<td><pre>" . htmlspecialchars($json) . "</pre></td>";
    echo "<td><pre>" . print_r($decodificado, true) . "</pre></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Archivos físicos en servidor:</h3>";
$dir = dirname(__DIR__) . '/public/uploads/teens';
if (is_dir($dir)) {
    $archivos = scandir($dir);
    echo "<ul>";
    foreach ($archivos as $archivo) {
        if ($archivo !== '.' && $archivo !== '..') {
            $ruta = $dir . '/' . $archivo;
            $tamanio = filesize($ruta);
            echo "<li>$archivo (" . number_format($tamanio / 1024, 2) . " KB)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "Directorio no encontrado: $dir";
}
?>
