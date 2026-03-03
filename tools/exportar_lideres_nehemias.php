<?php
/**
 * Exporta líderes de 12 y líderes de célula a CSV compatible con Excel.
 *
 * Columnas de salida:
 *   Nombres | Apellidos | Cedula | Telefono | Lider | Lider Nehemias
 *
 * Uso:
 *   php tools/exportar_lideres_nehemias.php
 *   php tools/exportar_lideres_nehemias.php --output="C:\\ruta\\archivo.csv"
 */

declare(strict_types=1);

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, "No se pudo inicializar la conexión a base de datos.\n");
    exit(1);
}

$options = getopt('', ['output::']);

$userProfile = getenv('USERPROFILE') ?: '';
$desktopPath = $userProfile !== '' ? $userProfile . DIRECTORY_SEPARATOR . 'Desktop' : '';
$defaultName = 'lideres_nehemias_base_' . date('Ymd_His') . '.csv';
$defaultOutput = ($desktopPath !== '' ? $desktopPath . DIRECTORY_SEPARATOR : __DIR__ . DIRECTORY_SEPARATOR) . $defaultName;

$outputPath = isset($options['output']) && is_string($options['output']) && trim($options['output']) !== ''
    ? trim($options['output'])
    : $defaultOutput;

$outputDir = dirname($outputPath);
if (!is_dir($outputDir) && !@mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "No se pudo crear el directorio de salida: {$outputDir}\n");
    exit(1);
}

$sql = "
SELECT
    p.Id_Persona,
    COALESCE(r.Nombre_Rol, '') AS Nombre_Rol,
    COALESCE(p.Nombre, '') AS Nombre,
    COALESCE(p.Apellido, '') AS Apellido,
    COALESCE(p.Numero_Documento, '') AS Numero_Documento,
    COALESCE(p.Telefono, '') AS Telefono,
    TRIM(CONCAT(COALESCE(l1.Nombre, ''), ' ', COALESCE(l1.Apellido, ''))) AS Nombre_Lider,
    TRIM(CONCAT(COALESCE(l2.Nombre, ''), ' ', COALESCE(l2.Apellido, ''))) AS Nombre_Lider_Nehemias
FROM persona p
LEFT JOIN rol r ON p.Id_Rol = r.Id_Rol
LEFT JOIN persona l1 ON p.Id_Lider = l1.Id_Persona
LEFT JOIN persona l2 ON l1.Id_Lider = l2.Id_Persona
WHERE (
    p.Id_Rol IN (3, 8)
    OR LOWER(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i')) LIKE '%lider%12%'
    OR LOWER(REPLACE(REPLACE(REPLACE(COALESCE(r.Nombre_Rol, ''), 'á', 'a'), 'é', 'e'), 'í', 'i')) LIKE '%lider%celula%'
)
ORDER BY
    CASE WHEN p.Id_Rol = 8 THEN 0 ELSE 1 END,
    p.Apellido ASC,
    p.Nombre ASC
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$file = fopen($outputPath, 'wb');
if ($file === false) {
    fwrite(STDERR, "No se pudo abrir el archivo de salida: {$outputPath}\n");
    exit(1);
}

fwrite($file, "\xEF\xBB\xBF");
$delimiter = ';';

fputcsv($file, [
    'Nombres',
    'Apellidos',
    'Cedula',
    'Telefono',
    'Lider',
    'Lider Nehemias'
], $delimiter);

foreach ($rows as $row) {
    fputcsv($file, [
        trim((string)$row['Nombre']),
        trim((string)$row['Apellido']),
        trim((string)$row['Numero_Documento']),
        trim((string)$row['Telefono']),
        trim((string)$row['Nombre_Lider']),
        trim((string)$row['Nombre_Lider_Nehemias'])
    ], $delimiter);
}

fclose($file);

echo "Exportación completada.\n";
echo "Archivo: {$outputPath}\n";
echo "Total líderes exportados: " . count($rows) . "\n";
