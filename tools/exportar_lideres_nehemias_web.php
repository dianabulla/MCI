<?php
/**
 * Exportación web (navegador) de líderes para Nehemías.
 *
 * Columnas:
 * Nombres | Apellidos | Cedula | Telefono | Lider | Lider Nehemias
 */

declare(strict_types=1);

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'Error de conexión a base de datos.';
    exit;
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

$filename = 'lideres_nehemias_base_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'wb');
$delimiter = ';';

fputcsv($output, ['Nombres', 'Apellidos', 'Cedula', 'Telefono', 'Lider', 'Lider Nehemias'], $delimiter);

foreach ($rows as $row) {
    fputcsv($output, [
        trim((string)($row['Nombre'] ?? '')),
        trim((string)($row['Apellido'] ?? '')),
        trim((string)($row['Numero_Documento'] ?? '')),
        trim((string)($row['Telefono'] ?? '')),
        trim((string)($row['Nombre_Lider'] ?? '')),
        trim((string)($row['Nombre_Lider_Nehemias'] ?? '')),
    ], $delimiter);
}

fclose($output);
exit;
