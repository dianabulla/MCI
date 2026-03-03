<?php
/**
 * Normalización web de líderes Nehemías.
 *
 * URL:
 *   /tools/normalizar_lideres_nehemias_web.php
 *
 * Parámetros:
 *   ?column=lider_nehemias|lider|both (default: lider_nehemias)
 *   ?mode=basic|advanced (default: advanced)
 *
 * Para aplicar cambios:
 *   Enviar POST con confirmar=SI
 */

declare(strict_types=1);

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'No se pudo inicializar la conexión a base de datos.';
    exit;
}

$customAliasesFile = __DIR__ . '/config/nehemias_aliases.php';
$customAliases = [
    'global' => [],
    'by_ministerio' => [],
    'token_aliases' => [],
];
if (is_file($customAliasesFile)) {
    $loadedAliases = require $customAliasesFile;
    if (is_array($loadedAliases)) {
        $customAliases = array_merge($customAliases, $loadedAliases);
    }
}

function normalizeNameWeb(string $value): string
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

function normalizeKeyWeb(string $value): string
{
    $value = normalizeNameWeb($value);

    return str_replace(
        ['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
        ['A', 'E', 'I', 'O', 'U', 'N'],
        $value
    );
}

function normalizeCompactKeyWeb(string $value): string
{
    $value = normalizeKeyWeb($value);
    $value = preg_replace('/[^A-Z0-9 ]+/u', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', trim($value)) ?? $value;
    return $value;
}

function sqlNormalizeExpr(string $field): string
{
    return "UPPER(TRIM(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE($field, ''), 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'ñ', 'n'), 'Á', 'A'), 'É', 'E'), 'Í', 'I'), 'Ó', 'O'), 'Ú', 'U'), 'Ñ', 'N')))";
}

function tokenizeNameWeb(string $value): array
{
    $compact = normalizeCompactKeyWeb($value);
    if ($compact === '') {
        return [];
    }

    $tokens = explode(' ', $compact);
    $expanded = [];
    foreach ($tokens as $token) {
        $token = trim($token);
        if ($token === '') {
            continue;
        }

        if (strlen($token) >= 4 && str_starts_with($token, 'Y')) {
            $expanded[] = 'Y';
            $expanded[] = substr($token, 1);
            continue;
        }

        $expanded[] = $token;
    }

    return $expanded;
}

function normalizeTokenWeb(string $token, array $tokenAliases): string
{
    $token = trim($token);
    if ($token === '') {
        return '';
    }

    if (isset($tokenAliases[$token])) {
        return (string)$tokenAliases[$token];
    }

    return $token;
}

function phoneticTokenWeb(string $token): string
{
    $token = normalizeCompactKeyWeb($token);
    if ($token === '') {
        return '';
    }

    $token = str_replace('H', '', $token);
    $token = str_replace('V', 'B', $token);
    $token = str_replace('Z', 'S', $token);
    $token = str_replace('SS', 'S', $token);
    $token = preg_replace('/^Y([EI])/u', 'J$1', $token) ?? $token;

    return $token;
}

function comparableTokensWeb(string $leftToken, string $rightToken): bool
{
    $leftToken = phoneticTokenWeb($leftToken);
    $rightToken = phoneticTokenWeb($rightToken);

    if ($leftToken === $rightToken) {
        return true;
    }

    $leftLen = strlen($leftToken);
    $rightLen = strlen($rightToken);

    if ($leftLen <= 2 || $rightLen <= 2) {
        return $leftToken !== '' && $rightToken !== '' && $leftToken[0] === $rightToken[0];
    }

    if ((str_starts_with($leftToken, $rightToken) || str_starts_with($rightToken, $leftToken))
        && abs($leftLen - $rightLen) <= 5) {
        return true;
    }

    if (min($leftLen, $rightLen) >= 4 && levenshtein($leftToken, $rightToken) <= 1) {
        return true;
    }

    if (min($leftLen, $rightLen) >= 6 && levenshtein($leftToken, $rightToken) <= 2) {
        return true;
    }

    return false;
}

function nameSimilarityScoreWeb(string $left, string $right, array $tokenAliases): int
{
    $leftTokens = array_map(static function ($token) use ($tokenAliases) {
        return normalizeTokenWeb($token, $tokenAliases);
    }, tokenizeNameWeb($left));

    $rightTokens = array_map(static function ($token) use ($tokenAliases) {
        return normalizeTokenWeb($token, $tokenAliases);
    }, tokenizeNameWeb($right));

    $leftTokens = array_values(array_filter($leftTokens, static fn($t) => $t !== ''));
    $rightTokens = array_values(array_filter($rightTokens, static fn($t) => $t !== ''));

    if (empty($leftTokens) || empty($rightTokens)) {
        return 0;
    }

    if (implode(' ', $leftTokens) === implode(' ', $rightTokens)) {
        return 100;
    }

    $maxTokens = max(count($leftTokens), count($rightTokens));
    $matches = 0;
    $usedRight = [];

    foreach ($leftTokens as $leftToken) {
        foreach ($rightTokens as $idx => $rightToken) {
            if (isset($usedRight[$idx])) {
                continue;
            }
            if (comparableTokensWeb($leftToken, $rightToken)) {
                $matches++;
                $usedRight[$idx] = true;
                break;
            }
        }
    }

    $tokenScore = (int)round(($matches / $maxTokens) * 100);

    $leftCompact = implode(' ', $leftTokens);
    $rightCompact = implode(' ', $rightTokens);
    $simPercent = 0.0;
    similar_text($leftCompact, $rightCompact, $simPercent);
    $stringScore = (int)round($simPercent);

    return (int)round(($tokenScore * 0.7) + ($stringScore * 0.3));
}

function areLikelySameNameWeb(string $left, string $right, array $tokenAliases, int $threshold = 80): bool
{
    return nameSimilarityScoreWeb($left, $right, $tokenAliases) >= $threshold;
}

function buildBasicPlanned(array $values, array $aliasMap): array
{
    $planned = [];

    foreach ($values as $rawValue) {
        $rawValue = (string)$rawValue;
        $normalized = normalizeNameWeb($rawValue);
        $normalizedKey = normalizeKeyWeb($rawValue);

        if (isset($aliasMap[$normalizedKey])) {
            $normalized = normalizeNameWeb((string)$aliasMap[$normalizedKey]);
        }

        if ($normalized !== $rawValue) {
            $planned[] = [
                'from' => $rawValue,
                'to' => $normalized,
                'group_key' => null,
                'group_label' => null,
            ];
        }
    }

    return $planned;
}

function buildAdvancedPlannedForColumn(PDO $pdo, string $column, array $aliasMap, array $customAliases, int $threshold): array
{
    $planned = [];
    $tokenAliases = [];
    foreach (($customAliases['token_aliases'] ?? []) as $k => $v) {
        $tokenAliases[normalizeKeyWeb((string)$k)] = normalizeKeyWeb((string)$v);
    }

    $globalAliases = [];
    foreach (($customAliases['global'] ?? []) as $k => $v) {
        $globalAliases[normalizeKeyWeb((string)$k)] = normalizeNameWeb((string)$v);
    }

    $byMinisterioAliases = [];
    foreach (($customAliases['by_ministerio'] ?? []) as $ministerioKey => $list) {
        $mk = normalizeKeyWeb((string)$ministerioKey);
        if (!is_array($list)) {
            continue;
        }
        $byMinisterioAliases[$mk] = [];
        foreach ($list as $k => $v) {
            $byMinisterioAliases[$mk][normalizeKeyWeb((string)$k)] = normalizeNameWeb((string)$v);
        }
    }

    if ($column === 'Lider_Nehemias') {
        $exprLider = sqlNormalizeExpr('Lider');
        $sql = "SELECT
                    $exprLider AS group_key,
                    COALESCE(NULLIF(TRIM(Lider), ''), 'SIN MINISTERIO') AS group_label,
                    Lider_Nehemias AS valor,
                    COUNT(*) AS total
                FROM nehemias
                WHERE Lider_Nehemias IS NOT NULL AND TRIM(Lider_Nehemias) <> ''
                GROUP BY $exprLider, Lider_Nehemias
                ORDER BY group_label ASC, total DESC, valor ASC";

        $rows = $pdo->query($sql)->fetchAll();
        $byGroup = [];
        foreach ($rows as $row) {
            $groupKey = trim((string)($row['group_key'] ?? 'SIN MINISTERIO'));
            $byGroup[$groupKey]['label'] = (string)($row['group_label'] ?? 'SIN MINISTERIO');
            $byGroup[$groupKey]['items'][] = [
                'value' => (string)$row['valor'],
                'count' => (int)$row['total'],
            ];
        }

        foreach ($byGroup as $groupKey => $groupData) {
            $items = $groupData['items'] ?? [];
            usort($items, static function ($a, $b) {
                if ($a['count'] === $b['count']) {
                    return strnatcasecmp((string)$a['value'], (string)$b['value']);
                }
                return $b['count'] <=> $a['count'];
            });

            $clusters = [];
            foreach ($items as $item) {
                $value = (string)$item['value'];
                $assigned = false;

                foreach ($clusters as &$cluster) {
                    $score = nameSimilarityScoreWeb($value, (string)$cluster['representative'], $tokenAliases);
                    if ($score >= $threshold) {
                        $cluster['items'][] = $item;
                        $assigned = true;
                        break;
                    }
                }
                unset($cluster);

                if (!$assigned) {
                    $clusters[] = [
                        'representative' => $value,
                        'items' => [$item],
                    ];
                }
            }

            foreach ($clusters as $cluster) {
                if (count($cluster['items']) <= 1) {
                    continue;
                }

                $canonical = (string)$cluster['items'][0]['value'];
                $canonicalKey = normalizeKeyWeb($canonical);
                $groupKeyNorm = normalizeKeyWeb((string)$groupData['label']);

                if (isset($byMinisterioAliases[$groupKeyNorm][$canonicalKey])) {
                    $canonical = normalizeNameWeb((string)$byMinisterioAliases[$groupKeyNorm][$canonicalKey]);
                } elseif (isset($globalAliases[$canonicalKey])) {
                    $canonical = normalizeNameWeb((string)$globalAliases[$canonicalKey]);
                }

                if (isset($aliasMap[$canonicalKey])) {
                    $canonical = normalizeNameWeb((string)$aliasMap[$canonicalKey]);
                } else {
                    $canonical = normalizeNameWeb($canonical);
                }

                foreach ($cluster['items'] as $clusterItem) {
                    $from = (string)$clusterItem['value'];
                    $to = $canonical;

                    $fromKey = normalizeKeyWeb($from);
                    if (isset($byMinisterioAliases[$groupKeyNorm][$fromKey])) {
                        $to = normalizeNameWeb((string)$byMinisterioAliases[$groupKeyNorm][$fromKey]);
                    } elseif (isset($globalAliases[$fromKey])) {
                        $to = normalizeNameWeb((string)$globalAliases[$fromKey]);
                    }

                    if (isset($aliasMap[$fromKey])) {
                        $to = normalizeNameWeb((string)$aliasMap[$fromKey]);
                    }

                    if ($from !== $to) {
                        $score = nameSimilarityScoreWeb($from, $to, $tokenAliases);
                        $planned[] = [
                            'from' => $from,
                            'to' => $to,
                            'group_key' => $groupKey,
                            'group_label' => $groupData['label'] ?? 'SIN MINISTERIO',
                            'score' => $score,
                        ];
                    }
                }
            }
        }

        return $planned;
    }

    $sqlDistinct = "SELECT DISTINCT {$column} AS valor
                    FROM nehemias
                    WHERE {$column} IS NOT NULL AND TRIM({$column}) <> ''";
    $values = $pdo->query($sqlDistinct)->fetchAll(PDO::FETCH_COLUMN);

    $items = array_map(static function ($value) {
        return ['value' => (string)$value, 'count' => 1];
    }, $values);

    $clusters = [];
    foreach ($items as $item) {
        $value = (string)$item['value'];
        $assigned = false;

        foreach ($clusters as &$cluster) {
            $score = nameSimilarityScoreWeb($value, (string)$cluster['representative'], $tokenAliases);
            if ($score >= $threshold) {
                $cluster['items'][] = $item;
                $assigned = true;
                break;
            }
        }
        unset($cluster);

        if (!$assigned) {
            $clusters[] = [
                'representative' => $value,
                'items' => [$item],
            ];
        }
    }

    foreach ($clusters as $cluster) {
        if (count($cluster['items']) <= 1) {
            continue;
        }

        $canonical = normalizeNameWeb((string)$cluster['items'][0]['value']);
        $canonicalKey = normalizeKeyWeb($canonical);
        if (isset($globalAliases[$canonicalKey])) {
            $canonical = normalizeNameWeb((string)$globalAliases[$canonicalKey]);
        }
        if (isset($aliasMap[$canonicalKey])) {
            $canonical = normalizeNameWeb((string)$aliasMap[$canonicalKey]);
        }

        foreach ($cluster['items'] as $clusterItem) {
            $from = (string)$clusterItem['value'];
            $to = $canonical;

            $fromKey = normalizeKeyWeb($from);
            if (isset($globalAliases[$fromKey])) {
                $to = normalizeNameWeb((string)$globalAliases[$fromKey]);
            }
            if (isset($aliasMap[$fromKey])) {
                $to = normalizeNameWeb((string)$aliasMap[$fromKey]);
            }

            if ($from !== $to) {
                $score = nameSimilarityScoreWeb($from, $to, $tokenAliases);
                $planned[] = [
                    'from' => $from,
                    'to' => $to,
                    'group_key' => null,
                    'group_label' => null,
                    'score' => $score,
                ];
            }
        }
    }

    return $planned;
}

$columnOption = strtolower(trim((string)($_GET['column'] ?? 'lider_nehemias')));
$modeOption = strtolower(trim((string)($_GET['mode'] ?? 'advanced')));
$thresholdInput = (int)($_GET['threshold'] ?? 80);
$threshold = max(60, min(100, $thresholdInput));
$columnMap = [
    'lider' => ['Lider'],
    'lider_nehemias' => ['Lider_Nehemias'],
    'both' => ['Lider', 'Lider_Nehemias'],
];

if (!isset($columnMap[$columnOption])) {
    $columnOption = 'lider_nehemias';
}

if (!in_array($modeOption, ['basic', 'advanced'], true)) {
    $modeOption = 'advanced';
}

$columnsToNormalize = $columnMap[$columnOption];

$aliasByColumn = [
    'Lider' => [
        'ALEJANDRO Y MADELINE' => 'ALEJANDRO Y MADELINE',
        'MADELINE Y ALEJANDRO' => 'ALEJANDRO Y MADELINE',
    ],
    'Lider_Nehemias' => [],
];

$applyRequested = ($_SERVER['REQUEST_METHOD'] === 'POST');
$confirmarRaw = trim((string)($_POST['confirmar'] ?? ''));
$confirmarNormalizado = strtoupper(str_replace(['Í', 'í'], 'I', $confirmarRaw));
$confirmOk = $applyRequested && $confirmarNormalizado === 'SI';
$selectedIdsInput = $_POST['selected'] ?? [];
if (!is_array($selectedIdsInput)) {
    $selectedIdsInput = [];
}
$selectedIdsMap = [];
foreach ($selectedIdsInput as $selectedId) {
    $selectedId = trim((string)$selectedId);
    if ($selectedId !== '') {
        $selectedIdsMap[$selectedId] = true;
    }
}
$hasSelection = !empty($selectedIdsMap);

$messages = [];
$details = [];
$totalTransforms = 0;
$totalRowsAffected = 0;
$totalSelected = 0;

foreach ($columnsToNormalize as $column) {
    $aliasMap = $aliasByColumn[$column] ?? [];

    if ($modeOption === 'advanced') {
        $planned = buildAdvancedPlannedForColumn($pdo, $column, $aliasMap, $customAliases, $threshold);
        $sqlDistinctCount = "SELECT COUNT(DISTINCT {$column}) AS total FROM nehemias WHERE {$column} IS NOT NULL AND TRIM({$column}) <> ''";
        $distinctInfo = $pdo->query($sqlDistinctCount)->fetch();
        $distinctCount = (int)($distinctInfo['total'] ?? 0);
    } else {
        $sqlDistinct = "SELECT DISTINCT {$column} AS valor
                        FROM nehemias
                        WHERE {$column} IS NOT NULL AND TRIM({$column}) <> ''";
        $stmtDistinct = $pdo->query($sqlDistinct);
        $values = $stmtDistinct ? $stmtDistinct->fetchAll(PDO::FETCH_COLUMN) : [];
        $distinctCount = count($values);
        $planned = buildBasicPlanned($values, $aliasMap);
    }

    $uniquePlannedMap = [];
    $dedupPlanned = [];
    foreach ($planned as $item) {
        $dedupKey = ($item['group_key'] ?? '__ALL__') . '|' . $item['from'] . '|' . $item['to'];
        if (!isset($uniquePlannedMap[$dedupKey])) {
            $uniquePlannedMap[$dedupKey] = true;
            $dedupPlanned[] = $item;
        }
    }
    $planned = $dedupPlanned;

    foreach ($planned as &$item) {
        $item['row_id'] = sha1($column . '|' . (($item['group_key'] ?? '__ALL__')) . '|' . $item['from'] . '|' . $item['to']);
    }
    unset($item);

    $plannedToApply = [];
    if ($confirmOk && $hasSelection) {
        foreach ($planned as $item) {
            if (isset($selectedIdsMap[$item['row_id']])) {
                $plannedToApply[] = $item;
            }
        }
    }

    $rowsAffectedColumn = 0;

    if ($confirmOk && $hasSelection && !empty($plannedToApply)) {
        $pdo->beginTransaction();
        try {
            $exprLider = sqlNormalizeExpr('Lider');
            $stmtUpdateGlobal = $pdo->prepare("UPDATE nehemias SET {$column} = ? WHERE {$column} = ?");
            $stmtUpdateByGroup = $pdo->prepare("UPDATE nehemias SET {$column} = ? WHERE {$column} = ? AND {$exprLider} = ?");

            foreach ($plannedToApply as $item) {
                if ($column === 'Lider_Nehemias' && !empty($item['group_key'])) {
                    $stmtUpdateByGroup->execute([$item['to'], $item['from'], $item['group_key']]);
                    $rowsAffectedColumn += (int)$stmtUpdateByGroup->rowCount();
                } else {
                    $stmtUpdateGlobal->execute([$item['to'], $item['from']]);
                    $rowsAffectedColumn += (int)$stmtUpdateGlobal->rowCount();
                }
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            $messages[] = ['type' => 'error', 'text' => 'Error aplicando cambios en ' . $column . ': ' . $e->getMessage()];
        }
    }

    $totalTransforms += count($planned);
    $totalRowsAffected += $rowsAffectedColumn;
    $totalSelected += count($plannedToApply);

    $details[] = [
        'column' => $column,
        'distinct' => $distinctCount,
        'planned' => count($planned),
        'selected' => count($plannedToApply),
        'rows_affected' => $rowsAffectedColumn,
        'preview' => $planned,
    ];
}

if ($applyRequested && !$confirmOk) {
    $messages[] = ['type' => 'warn', 'text' => 'Confirmación inválida. Escribe SI para aplicar cambios.'];
}

if ($applyRequested && $confirmOk && !$hasSelection) {
    $messages[] = ['type' => 'warn', 'text' => 'No seleccionaste sugerencias para aplicar.'];
}

if ($confirmOk && $hasSelection) {
    $messages[] = ['type' => 'success', 'text' => 'Normalización aplicada (' . strtoupper($modeOption) . '). Sugerencias totales: ' . $totalTransforms . ' | Seleccionadas: ' . $totalSelected . ' | Filas afectadas: ' . $totalRowsAffected];
} else {
    $messages[] = ['type' => 'info', 'text' => 'Modo revisión (' . strtoupper($modeOption) . ') sin cambios. Marca las sugerencias que quieras aplicar.'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Normalizar Líderes Nehemías</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f7fb; }
        .wrap { max-width: 1100px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 20px; border: 1px solid #e5e7eb; }
        h1 { margin-top: 0; }
        .msg { padding: 10px 12px; border-radius: 8px; margin: 8px 0; }
        .info { background: #eef2ff; color: #3730a3; }
        .success { background: #ecfdf5; color: #065f46; }
        .warn { background: #fffbeb; color: #92400e; }
        .error { background: #fef2f2; color: #991b1b; }
        .toolbar { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-bottom: 15px; }
        .btn { border: 0; background: #2563eb; color: #fff; padding: 9px 14px; border-radius: 7px; cursor: pointer; }
        .btn-secondary { background: #6b7280; text-decoration: none; display: inline-block; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f9fafb; }
        code { background: #f3f4f6; padding: 2px 5px; border-radius: 4px; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; margin-top: 14px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Normalización de Líderes Nehemías</h1>

    <div class="toolbar">
        <strong>Columna:</strong>
        <a class="btn btn-secondary" href="?column=lider_nehemias&mode=<?= urlencode($modeOption) ?>&threshold=<?= (int)$threshold ?>">Lider_Nehemias</a>
        <a class="btn btn-secondary" href="?column=lider&mode=<?= urlencode($modeOption) ?>&threshold=<?= (int)$threshold ?>">Lider</a>
        <a class="btn btn-secondary" href="?column=both&mode=<?= urlencode($modeOption) ?>&threshold=<?= (int)$threshold ?>">Ambas</a>
        <strong style="margin-left:10px;">Modo:</strong>
        <a class="btn btn-secondary" href="?column=<?= urlencode($columnOption) ?>&mode=basic&threshold=<?= (int)$threshold ?>">Básico</a>
        <a class="btn btn-secondary" href="?column=<?= urlencode($columnOption) ?>&mode=advanced&threshold=<?= (int)$threshold ?>">Avanzado</a>
        <span style="margin-left:10px;"><strong>Umbral:</strong> <?= (int)$threshold ?></span>
    </div>

    <?php foreach ($messages as $msg): ?>
        <div class="msg <?= htmlspecialchars($msg['type']) ?>"><?= htmlspecialchars($msg['text']) ?></div>
    <?php endforeach; ?>

    <form method="GET" action="" class="card" style="margin-bottom:12px;">
        <input type="hidden" name="column" value="<?= htmlspecialchars($columnOption) ?>">
        <input type="hidden" name="mode" value="<?= htmlspecialchars($modeOption) ?>">
        <label for="threshold"><strong>Sensibilidad de candidatos por similitud (60-100):</strong></label>
        <input id="threshold" type="number" min="60" max="100" name="threshold" value="<?= (int)$threshold ?>" style="margin-left:8px; width:90px;">
        <button type="submit" class="btn">Recalcular</button>
        <p style="margin-top:8px;">Más bajo = más candidatos (incluye dudosos). Más alto = menos candidatos (más estrictos).</p>
    </form>

    <form method="POST" action="?column=<?= urlencode($columnOption) ?>&mode=<?= urlencode($modeOption) ?>&threshold=<?= (int)$threshold ?>">
        <div class="toolbar" style="margin-top:8px;">
            <button type="button" class="btn btn-secondary" onclick="toggleAllSuggestions(true)">Seleccionar todo</button>
            <button type="button" class="btn btn-secondary" onclick="toggleAllSuggestions(false)">Deseleccionar todo</button>
        </div>

        <?php foreach ($details as $detail): ?>
            <div class="card">
                <h3 style="margin:0 0 8px;">Columna <?= htmlspecialchars($detail['column']) ?></h3>
                <p>
                    Valores distintos: <strong><?= (int)$detail['distinct'] ?></strong> |
                    Sugerencias: <strong><?= (int)$detail['planned'] ?></strong>
                    <?php if ($confirmOk): ?> |
                        Seleccionadas: <strong><?= (int)$detail['selected'] ?></strong> |
                        Filas afectadas: <strong><?= (int)$detail['rows_affected'] ?></strong>
                    <?php endif; ?>
                </p>

                <?php if (!empty($detail['preview'])): ?>
                    <table>
                        <thead>
                        <tr>
                            <th>Aplicar</th>
                            <th>Contexto</th>
                            <th>Original</th>
                            <th>Normalizado</th>
                            <th>Score</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($detail['preview'] as $row): ?>
                            <?php $rowId = (string)($row['row_id'] ?? ''); ?>
                            <?php $checked = $applyRequested ? isset($selectedIdsMap[$rowId]) : true; ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="suggestion-checkbox" name="selected[]" value="<?= htmlspecialchars($rowId) ?>" <?= $checked ? 'checked' : '' ?>>
                                </td>
                                <td><?= htmlspecialchars((string)($row['group_label'] ?? 'GLOBAL')) ?></td>
                                <td><?= htmlspecialchars($row['from']) ?></td>
                                <td><?= htmlspecialchars($row['to']) ?></td>
                                <td><?= (int)($row['score'] ?? 100) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No hay cambios pendientes.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="card">
            <h3 style="margin:0 0 8px;">Aplicar cambios seleccionados</h3>
            <p>Revisa la lista, marca solo lo que apruebas y escribe <strong>SI</strong> para ejecutar.</p>
            <input type="text" name="confirmar" required placeholder="Escribe SI" value="<?= htmlspecialchars($applyRequested ? $confirmarRaw : '') ?>">
            <button type="submit" class="btn">Aplicar seleccionados</button>
            <p style="margin-top:10px;">En modo avanzado, <strong>Lider_Nehemias</strong> se agrupa por ministerio para evitar mezclar líderes de ministerios distintos.</p>
            <p style="margin-top:8px;">Recomendado: hacer backup antes de aplicar.</p>
        </div>
    </form>
</div>
<script>
function toggleAllSuggestions(checked) {
    var checkboxes = document.querySelectorAll('.suggestion-checkbox');
    checkboxes.forEach(function (checkbox) {
        checkbox.checked = checked;
    });
}
</script>
</body>
</html>
