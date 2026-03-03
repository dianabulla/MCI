<?php
/**
 * Normaliza nombres de líderes en la tabla nehemias.
 *
 * Uso:
 *   php tools/normalizar_lideres_nehemias.php
 *   php tools/normalizar_lideres_nehemias.php --apply
 *   php tools/normalizar_lideres_nehemias.php --column=lider
 *   php tools/normalizar_lideres_nehemias.php --column=both --apply
 */

declare(strict_types=1);

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, "No se pudo inicializar la conexión a base de datos.\n");
    exit(1);
}

$options = getopt('', ['apply', 'column::']);
$apply = array_key_exists('apply', $options);
$columnOption = strtolower(trim((string)($options['column'] ?? 'lider_nehemias')));

$columnMap = [
    'lider' => ['Lider'],
    'lider_nehemias' => ['Lider_Nehemias'],
    'both' => ['Lider', 'Lider_Nehemias'],
];

if (!isset($columnMap[$columnOption])) {
    fwrite(STDERR, "Parámetro inválido en --column. Usa: lider, lider_nehemias o both.\n");
    exit(1);
}

$columnsToNormalize = $columnMap[$columnOption];

function normalizeName(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    $value = preg_replace('/\s*\/\s*/u', ' / ', $value) ?? $value;
    $value = preg_replace('/\s*-\s*/u', ' - ', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    $value = trim($value);

    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($value, 'UTF-8');
    }

    return strtoupper($value);
}

function normalizeKey(string $value): string
{
    $value = normalizeName($value);

    $value = str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
        ['A', 'E', 'I', 'O', 'U', 'N'],
        $value
    );

    return $value;
}

$aliasByColumn = [
    'Lider' => [
        'ALEJANDRO Y MADELINE' => 'ALEJANDRO Y MADELINE',
        'MADELINE Y ALEJANDRO' => 'ALEJANDRO Y MADELINE',
    ],
    'Lider_Nehemias' => [
    ],
];

$totalUpdates = 0;
$totalRowsAffected = 0;

foreach ($columnsToNormalize as $column) {
    $sqlDistinct = "SELECT DISTINCT {$column} AS valor
                    FROM nehemias
                    WHERE {$column} IS NOT NULL AND TRIM({$column}) <> ''";
    $stmtDistinct = $pdo->query($sqlDistinct);
    $values = $stmtDistinct ? $stmtDistinct->fetchAll(PDO::FETCH_COLUMN) : [];

    $planned = [];

    foreach ($values as $rawValue) {
        $rawValue = (string)$rawValue;
        $normalized = normalizeName($rawValue);
        $aliasMap = $aliasByColumn[$column] ?? [];
        $normalizedKey = normalizeKey($rawValue);

        if (isset($aliasMap[$normalizedKey])) {
            $normalized = normalizeName((string)$aliasMap[$normalizedKey]);
        }

        if ($normalized !== $rawValue) {
            $planned[] = [
                'from' => $rawValue,
                'to' => $normalized,
            ];
        }
    }

    echo "\n=== Columna {$column} ===\n";
    echo "Valores distintos encontrados: " . count($values) . "\n";
    echo "Cambios propuestos: " . count($planned) . "\n";

    $previewCount = min(20, count($planned));
    if ($previewCount > 0) {
        echo "Vista previa (primeros {$previewCount}):\n";
        for ($i = 0; $i < $previewCount; $i++) {
            echo '- "' . $planned[$i]['from'] . '" => "' . $planned[$i]['to'] . '"' . "\n";
        }
    }

    if (!$apply || empty($planned)) {
        $totalUpdates += count($planned);
        continue;
    }

    $pdo->beginTransaction();
    try {
        $stmtUpdate = $pdo->prepare("UPDATE nehemias SET {$column} = ? WHERE {$column} = ?");

        foreach ($planned as $item) {
            $stmtUpdate->execute([$item['to'], $item['from']]);
            $totalRowsAffected += (int)$stmtUpdate->rowCount();
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        fwrite(STDERR, "Error aplicando cambios en {$column}: " . $e->getMessage() . "\n");
        exit(1);
    }

    $totalUpdates += count($planned);
}

if ($apply) {
    echo "\n✅ Normalización aplicada.\n";
    echo "Transformaciones ejecutadas: {$totalUpdates}\n";
    echo "Filas afectadas: {$totalRowsAffected}\n";
} else {
    echo "\nℹ️ Modo simulación (sin cambios).\n";
    echo "Transformaciones detectadas: {$totalUpdates}\n";
    echo "Para aplicar, ejecuta: php tools/normalizar_lideres_nehemias.php --apply\n";
}
