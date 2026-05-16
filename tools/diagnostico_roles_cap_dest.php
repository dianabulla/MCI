<?php
require_once __DIR__ . '/../app/Config/config.php';

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'Error de conexion: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

function normalizar_texto(string $texto): string {
    $texto = strtolower(trim($texto));
    return strtr($texto, [
        'a' => 'a',
        'e' => 'e',
        'i' => 'i',
        'o' => 'o',
        'u' => 'u',
        'n' => 'n',
        'á' => 'a',
        'é' => 'e',
        'í' => 'i',
        'ó' => 'o',
        'ú' => 'u',
        'ü' => 'u',
        'ñ' => 'n',
    ]);
}

$roles = $pdo->query('SELECT Id_Rol, Nombre_Rol FROM rol ORDER BY Id_Rol ASC')->fetchAll();
$idRolDiscipulo = 0;

foreach ($roles as $rol) {
    $nombre = normalizar_texto((string)($rol['Nombre_Rol'] ?? ''));
    if (
        strpos($nombre, 'discipul') !== false
        || strpos($nombre, 'disipul') !== false
        || strpos($nombre, 'discipl') !== false
        || strpos($nombre, 'disipl') !== false
    ) {
        $idRolDiscipulo = (int)($rol['Id_Rol'] ?? 0);
        break;
    }
}

echo 'ROL_DISCIPULO_ID=' . $idRolDiscipulo . PHP_EOL;

$triggers = $pdo->query("SHOW TRIGGERS LIKE 'escuela_formacion_inscripcion'")->fetchAll();
$triggerNames = [];
foreach ($triggers as $trigger) {
    $triggerNames[] = (string)($trigger['Trigger'] ?? '');
}
echo 'TRIGGERS=' . (!empty($triggerNames) ? implode(',', $triggerNames) : 'NINGUNO') . PHP_EOL;

if ($idRolDiscipulo <= 0) {
    echo 'No se encontro rol discipulo por alias.' . PHP_EOL;
    exit(0);
}

$sqlFaltantes = "SELECT COUNT(DISTINCT efi.Id_Persona) AS total
FROM escuela_formacion_inscripcion efi
LEFT JOIN user_roles ur ON ur.Id_Persona = efi.Id_Persona
    AND ur.Id_Rol = ?
    AND ur.Activo = 1
WHERE efi.Id_Persona IS NOT NULL
  AND efi.Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')
  AND ur.Id_User_Role IS NULL";

$stmt = $pdo->prepare($sqlFaltantes);
$stmt->execute([$idRolDiscipulo]);
$faltantes = (int)$stmt->fetchColumn();

echo 'INSCRITOS_CAP_DEST_SIN_ROL_DISCIPULO=' . $faltantes . PHP_EOL;

$sqlDetalle = "SELECT efi.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Id_Rol AS Rol_Principal
FROM escuela_formacion_inscripcion efi
INNER JOIN persona p ON p.Id_Persona = efi.Id_Persona
LEFT JOIN user_roles ur ON ur.Id_Persona = efi.Id_Persona
    AND ur.Id_Rol = ?
    AND ur.Activo = 1
WHERE efi.Id_Persona IS NOT NULL
  AND efi.Programa IN ('capacitacion_destino', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3')
  AND ur.Id_User_Role IS NULL
GROUP BY efi.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Id_Rol
ORDER BY efi.Id_Persona DESC
LIMIT 20";

$stmt = $pdo->prepare($sqlDetalle);
$stmt->execute([$idRolDiscipulo]);
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $idPersona = (int)($row['Id_Persona'] ?? 0);
    $nombre = trim((string)($row['Nombre'] ?? '') . ' ' . (string)($row['Apellido'] ?? ''));
    $doc = (string)($row['Numero_Documento'] ?? '');
    $rolPrincipal = (int)($row['Rol_Principal'] ?? 0);
    echo 'FALTA|' . $idPersona . '|' . $nombre . '|DOC=' . $doc . '|ROL_PRINCIPAL=' . $rolPrincipal . PHP_EOL;
}
