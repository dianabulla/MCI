<?php
/**
 * Auditoría rápida: posibles afectados por registro público UV / Cap. Destino
 * o reasignación automática (sin líder/ministerio pero con célula).
 *
 * Uso: php tools/auditar_formulario_uv_afectados.php
 */

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, "Sin conexión a BD.\n");
    exit(1);
}

$programas = "'universidad_vida','encuentro','capacitacion_destino','capacitacion_destino_nivel_1','capacitacion_destino_nivel_2','capacitacion_destino_nivel_3'";

$tableHasColumn = static function (PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
};

$colMinCelula = $tableHasColumn($pdo, 'celula', 'Id_Ministerio_Lider')
    ? 'c.Id_Ministerio_Lider'
    : ($tableHasColumn($pdo, 'celula', 'Id_Ministerio') ? 'c.Id_Ministerio' : 'pl.Id_Ministerio');
$exprMinisterioCelula = "COALESCE(pl.Id_Ministerio, {$colMinCelula}, 0)";

$consultas = [
    'inscritos_uv_cd_sin_celula' => "
        SELECT COUNT(DISTINCT p.Id_Persona) AS total
        FROM persona p
        INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
        WHERE efi.Programa IN ({$programas})
          AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
          AND COALESCE(p.Es_Antiguo, 0) = 0
    ",
    'inscritos_existentes_antes_inscripcion_sin_celula' => "
        SELECT COUNT(DISTINCT p.Id_Persona) AS total
        FROM persona p
        INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
        WHERE efi.Programa IN ({$programas})
          AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
          AND p.Fecha_Registro < DATE_SUB(efi.Fecha_Registro, INTERVAL 1 DAY)
    ",
    'con_celula_sin_lider_marcados_reasignado' => "
        SELECT COUNT(*) AS total
        FROM persona p
        WHERE p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
          AND (p.Id_Lider IS NULL OR p.Id_Lider = 0)
          AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
          AND p.Escalera_Checklist LIKE '%\"reasignado_automatico\":true%'
    ",
    'restaurables_desde_celula' => "
        SELECT COUNT(*) AS total
        FROM persona p
        INNER JOIN celula c ON c.Id_Celula = p.Id_Celula
        LEFT JOIN persona pl ON pl.Id_Persona = c.Id_Lider
        WHERE p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
          AND (p.Id_Lider IS NULL OR p.Id_Lider = 0)
          AND (p.Id_Ministerio IS NULL OR p.Id_Ministerio = 0)
          AND c.Id_Lider IS NOT NULL AND c.Id_Lider > 0
          AND {$exprMinisterioCelula} > 0
    ",
];

echo "=== Auditoría formulario UV / Cap. Destino ===\n";
echo 'BD: ' . ($pdo->query('SELECT DATABASE()')->fetchColumn() ?: '?') . "\n\n";

foreach ($consultas as $clave => $sql) {
    $total = (int)($pdo->query($sql)->fetchColumn() ?: 0);
    echo str_pad($clave, 45) . ': ' . $total . "\n";
}

echo "\n--- Muestra: inscritos existentes sin célula (máx. 15) ---\n";
$sqlMuestra = "
    SELECT
        p.Id_Persona,
        TRIM(CONCAT(COALESCE(p.Nombre,''), ' ', COALESCE(p.Apellido,''))) AS nombre,
        p.Numero_Documento,
        p.Telefono,
        p.Fecha_Registro AS fecha_persona,
        MIN(efi.Fecha_Registro) AS primera_inscripcion,
        GROUP_CONCAT(DISTINCT efi.Programa ORDER BY efi.Programa) AS programas
    FROM persona p
    INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
    WHERE efi.Programa IN ({$programas})
      AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
      AND p.Fecha_Registro < DATE_SUB(MIN(efi.Fecha_Registro), INTERVAL 1 DAY)
    GROUP BY p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono, p.Fecha_Registro
    ORDER BY primera_inscripcion DESC
    LIMIT 15
";
// Fix: can't use MIN in HAVING with same group - simplify
$sqlMuestra = "
    SELECT *
    FROM (
        SELECT
            p.Id_Persona,
            TRIM(CONCAT(COALESCE(p.Nombre,''), ' ', COALESCE(p.Apellido,''))) AS nombre,
            p.Numero_Documento,
            p.Telefono,
            p.Fecha_Registro AS fecha_persona,
            MIN(efi.Fecha_Registro) AS primera_inscripcion,
            GROUP_CONCAT(DISTINCT efi.Programa ORDER BY efi.Programa) AS programas
        FROM persona p
        INNER JOIN escuela_formacion_inscripcion efi ON efi.Id_Persona = p.Id_Persona
        WHERE efi.Programa IN ({$programas})
          AND (p.Id_Celula IS NULL OR p.Id_Celula = 0)
        GROUP BY p.Id_Persona, p.Nombre, p.Apellido, p.Numero_Documento, p.Telefono, p.Fecha_Registro
    ) t
    WHERE t.fecha_persona < DATE_SUB(t.primera_inscripcion, INTERVAL 1 DAY)
    ORDER BY t.primera_inscripcion DESC
    LIMIT 15
";

foreach ($pdo->query($sqlMuestra) as $row) {
    echo sprintf(
        "ID %d | %s | doc %s | insc %s | prog %s\n",
        (int)$row['Id_Persona'],
        $row['nombre'],
        $row['Numero_Documento'] ?? '',
        $row['primera_inscripcion'] ?? '',
        $row['programas'] ?? ''
    );
}

echo "\nListo. Ver docs/sql/auditar_recuperar_formulario_uv_cd.sql para SQL completo.\n";
