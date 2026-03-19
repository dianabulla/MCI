<?php
/**
 * Asigna lider y ministerio a personas usando un CSV exportado del Excel.
 *
 * Fuente esperada:
 *   tools/data/arreglar.csv
 *
 * Uso web:
 *   - Simulacion: /tools/asignar_lider_ministerio_desde_csv.php
 *   - Aplicar:    /tools/asignar_lider_ministerio_desde_csv.php?aplicar=1
 *
 * Uso CLI:
 *   - Simulacion: php tools/asignar_lider_ministerio_desde_csv.php
 *   - Aplicar:    php tools/asignar_lider_ministerio_desde_csv.php --aplicar
 */

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../conexion.php';

$isCli = PHP_SAPI === 'cli';
$aplicar = $isCli
    ? in_array('--aplicar', $argv ?? [], true)
    : (isset($_GET['aplicar']) && (string)$_GET['aplicar'] === '1');

$csvPath = __DIR__ . '/data/arreglar.csv';

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">'
        . '<title>Asignar lider y ministerio desde CSV</title>'
        . '<style>'
        . 'body{font-family:Arial,sans-serif;margin:20px;}'
        . '.ok{color:green}.warn{color:#b36b00}.err{color:#b00020;font-weight:bold}'
        . '.box{background:#f5f5f5;border:1px solid #ddd;padding:10px;border-radius:6px;margin-bottom:10px}'
        . 'table{border-collapse:collapse;width:100%;margin-top:10px}'
        . 'th,td{border:1px solid #ccc;padding:6px;font-size:12px;text-align:left}'
        . 'th{background:#eee}'
        . 'a.btn{display:inline-block;margin-top:12px;padding:8px 14px;background:#c0392b;color:#fff;text-decoration:none;border-radius:4px}'
        . '</style></head><body>';
}

function out($text, $isCli, $class = '') {
    if ($isCli) {
        echo $text . PHP_EOL;
        return;
    }

    $cls = $class !== '' ? ' class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '"' : '';
    echo '<div' . $cls . '>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
}

function normalizeText($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $map = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n',
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n'
    ];

    $value = strtr($value, $map);
    $value = mb_strtolower($value, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', $value);
    return trim((string)$value);
}

function normalizeDigits($value) {
    return preg_replace('/\D+/', '', (string)$value) ?: '';
}

function normalizeChecklist($raw) {
    $data = [];
    if (is_string($raw) && trim($raw) !== '') {
        $tmp = json_decode($raw, true);
        if (is_array($tmp)) {
            $data = $tmp;
        }
    }

    if (!isset($data['Ganar']) || !is_array($data['Ganar'])) {
        $data['Ganar'] = [false, false, false, false];
    }
    for ($i = 0; $i < 4; $i++) {
        $data['Ganar'][$i] = !empty($data['Ganar'][$i]);
    }

    if (!isset($data['_meta']) || !is_array($data['_meta'])) {
        $data['_meta'] = [];
    }

    unset($data['_meta']['reasignado_automatico']);
    unset($data['_meta']['reasignado_automatico_at']);
    unset($data['_meta']['reasignado_automatico_motivo']);
    if (empty($data['_meta'])) {
        unset($data['_meta']);
    }

    return $data;
}

function readCsvRows($path) {
    if (!is_file($path)) {
        throw new RuntimeException('No existe el archivo CSV: ' . $path);
    }

    $fh = fopen($path, 'rb');
    if ($fh === false) {
        throw new RuntimeException('No se pudo abrir el CSV: ' . $path);
    }

    $rows = [];
    $headers = null;

    while (($line = fgetcsv($fh)) !== false) {
        if ($headers === null) {
            $headers = $line;
            if (isset($headers[0])) {
                $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$headers[0]);
            }
            continue;
        }

        if (!is_array($line) || empty(array_filter($line, function ($v) {
            return trim((string)$v) !== '';
        }))) {
            continue;
        }

        $row = [];
        foreach ($headers as $idx => $h) {
            $key = trim((string)$h);
            if ($key === '') {
                $key = 'col_' . $idx;
            }
            $row[$key] = (string)($line[$idx] ?? '');
        }
        $rows[] = $row;
    }

    fclose($fh);
    return $rows;
}

function getRowValue(array $row, array $candidateKeys) {
    foreach ($candidateKeys as $key) {
        if (array_key_exists($key, $row)) {
            return trim((string)$row[$key]);
        }
    }

    // Fallback robusto por normalizacion de texto de llave.
    $normalized = [];
    foreach ($row as $k => $v) {
        $normalized[normalizeText((string)$k)] = (string)$v;
    }

    foreach ($candidateKeys as $key) {
        $nk = normalizeText((string)$key);
        if (array_key_exists($nk, $normalized)) {
            return trim((string)$normalized[$nk]);
        }
    }

    return '';
}

function findPersonaByDocumentPhoneName(PDO $pdo, $doc, $phone, $name, $surname) {
    $doc = normalizeDigits($doc);
    $phone = normalizeDigits($phone);
    $nameN = normalizeText($name);
    $surnameN = normalizeText($surname);

    if ($doc !== '') {
        $stmt = $pdo->prepare("SELECT Id_Persona, Nombre, Apellido, Numero_Documento, Telefono, Id_Celula, Id_Lider, Id_Ministerio, Escalera_Checklist
                               FROM persona
                               WHERE Numero_Documento = ?
                               LIMIT 1");
        $stmt->execute([$doc]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    if ($phone !== '') {
        $stmt = $pdo->prepare("SELECT Id_Persona, Nombre, Apellido, Numero_Documento, Telefono, Id_Celula, Id_Lider, Id_Ministerio, Escalera_Checklist
                               FROM persona
                               WHERE REPLACE(REPLACE(REPLACE(Telefono, ' ', ''), '-', ''), '+', '') = ?
                               LIMIT 1");
        $stmt->execute([$phone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    if ($nameN !== '' && $surnameN !== '') {
        $stmt = $pdo->query("SELECT Id_Persona, Nombre, Apellido, Numero_Documento, Telefono, Id_Celula, Id_Lider, Id_Ministerio, Escalera_Checklist
                             FROM persona");
        $all = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($all as $p) {
            if (normalizeText($p['Nombre'] ?? '') === $nameN && normalizeText($p['Apellido'] ?? '') === $surnameN) {
                return $p;
            }
        }
    }

    return null;
}

function findLeaderByName(PDO $pdo, $leaderName) {
    $leaderName = normalizeText($leaderName);
    if ($leaderName === '') {
        return null;
    }

    $stmt = $pdo->query("SELECT Id_Persona, Nombre, Apellido, Id_Ministerio FROM persona");
    $all = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    foreach ($all as $row) {
        $full = normalizeText(trim((string)($row['Nombre'] ?? '') . ' ' . (string)($row['Apellido'] ?? '')));
        if ($full === $leaderName) {
            return $row;
        }
    }

    foreach ($all as $row) {
        $full = normalizeText(trim((string)($row['Nombre'] ?? '') . ' ' . (string)($row['Apellido'] ?? '')));
        if ($full !== '' && strpos($leaderName, $full) !== false) {
            return $row;
        }
    }

    return null;
}

function findCelulaByLeader(PDO $pdo, $idLeader) {
    $idLeader = (int)$idLeader;
    if ($idLeader <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT Id_Celula FROM celula WHERE Id_Lider = ? LIMIT 1");
    $stmt->execute([$idLeader]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['Id_Celula'] : null;
}

function getLeaderAndMinisterioFromCelula(PDO $pdo, $idCelula) {
    $idCelula = (int)$idCelula;
    if ($idCelula <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("SELECT c.Id_Lider, l.Id_Ministerio
                           FROM celula c
                           LEFT JOIN persona l ON l.Id_Persona = c.Id_Lider
                           WHERE c.Id_Celula = ?
                           LIMIT 1");
    $stmt->execute([$idCelula]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return null;
    }

    $idLider = (int)($row['Id_Lider'] ?? 0);
    $idMin = (int)($row['Id_Ministerio'] ?? 0);
    if ($idLider <= 0 || $idMin <= 0) {
        return null;
    }

    return [
        'id_lider' => $idLider,
        'id_ministerio' => $idMin,
        'id_celula' => $idCelula,
    ];
}

try {
    if (!$isCli) {
        echo '<h2>Asignar lider y ministerio desde CSV</h2>';
        echo '<div class="box">Modo: <strong>' . ($aplicar ? 'APLICAR' : 'SIMULACION') . '</strong><br>Archivo: '
            . htmlspecialchars($csvPath, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    $rows = readCsvRows($csvPath);
    out('Filas leidas del CSV: ' . count($rows), $isCli);

    $procesables = [];
    foreach ($rows as $row) {
        $doc = getRowValue($row, ['NUMERO DE DOCUMENTO', 'NUMERO DOCUMENTO']);
        $nom = getRowValue($row, ['NOMBRES', 'NOMBRE']);
        $ape = getRowValue($row, ['APELLIDOS', 'APELLIDO']);
        $tel = getRowValue($row, ['TELEFONO', 'TELFONO', 'CELULAR']);

        $liderPreferido = getRowValue($row, ['lider de 12', 'LIDER DE 12']);
        $liderAlterno = getRowValue($row, ['LIDER', 'LIDER CELULA']);
        $liderNombre = $liderPreferido !== '' ? $liderPreferido : $liderAlterno;

        if ($doc === '' && $nom === '' && $ape === '') {
            continue;
        }

        $procesables[] = [
            'doc' => $doc,
            'nombre' => $nom,
            'apellido' => $ape,
            'telefono' => $tel,
            'lider_nombre' => $liderNombre,
            'lider_grupo' => $liderAlterno,
            'raw' => $row,
        ];
    }

    // Construir mapa de grupo -> nombre de lider individual usando filas que si lo traen.
    $mapGrupoToLider = [];
    foreach ($procesables as $it) {
        $grupo = normalizeText($it['lider_grupo'] ?? '');
        $lider = trim((string)($it['lider_nombre'] ?? ''));
        if ($grupo !== '' && $lider !== '' && strpos($lider, ' Y ') === false) {
            if (!isset($mapGrupoToLider[$grupo])) {
                $mapGrupoToLider[$grupo] = $lider;
            }
        }
    }

    out('Filas procesables: ' . count($procesables), $isCli);

    $resumen = [
        'persona_no_encontrada' => 0,
        'lider_no_encontrado' => 0,
        'lider_sin_ministerio' => 0,
        'resuelto_por_celula' => 0,
        'resuelto_por_nombre_lider' => 0,
        'ok_listo' => 0,
        'actualizables' => 0,
        'actualizados' => 0,
    ];

    $detalles = [];

    if ($aplicar) {
        $pdo->beginTransaction();
    }

    $upd = $pdo->prepare("UPDATE persona
                          SET Id_Lider = ?,
                              Id_Ministerio = ?,
                              Proceso = 'Ganar',
                              Escalera_Checklist = ?,
                              Fecha_Asignacion_Lider = NOW(),
                              Id_Celula = CASE WHEN (Id_Celula IS NULL OR Id_Celula = 0) THEN ? ELSE Id_Celula END
                          WHERE Id_Persona = ?");

    foreach ($procesables as $item) {
        $persona = findPersonaByDocumentPhoneName(
            $pdo,
            $item['doc'],
            $item['telefono'],
            $item['nombre'],
            $item['apellido']
        );

        if (!$persona) {
            $resumen['persona_no_encontrada']++;
            $detalles[] = [
                'estado' => 'Persona no encontrada',
                'doc' => $item['doc'],
                'nombre' => trim($item['nombre'] . ' ' . $item['apellido']),
                'lider' => $item['lider_nombre'],
            ];
            continue;
        }

        $idPersona = (int)$persona['Id_Persona'];

        $liderNombreCsv = trim((string)($item['lider_nombre'] ?? ''));
        $grupoNorm = normalizeText((string)($item['lider_grupo'] ?? ''));
        if (($liderNombreCsv === '' || strpos($liderNombreCsv, ' Y ') !== false) && $grupoNorm !== '') {
            if (isset($mapGrupoToLider[$grupoNorm]) && trim((string)$mapGrupoToLider[$grupoNorm]) !== '') {
                $liderNombreCsv = trim((string)$mapGrupoToLider[$grupoNorm]);
            }
        }

        // 1) Prioridad: resolver por célula actual de la persona.
        $asignacion = getLeaderAndMinisterioFromCelula($pdo, (int)($persona['Id_Celula'] ?? 0));
        $liderNombreFinal = '';
        if ($asignacion) {
            $resumen['resuelto_por_celula']++;
            $idLider = (int)$asignacion['id_lider'];
            $idMin = (int)$asignacion['id_ministerio'];
            $idCelulaLider = (int)$asignacion['id_celula'];

            $stmtLider = $pdo->prepare("SELECT Nombre, Apellido FROM persona WHERE Id_Persona = ? LIMIT 1");
            $stmtLider->execute([$idLider]);
            $rowL = $stmtLider->fetch(PDO::FETCH_ASSOC);
            $liderNombreFinal = trim((string)($rowL['Nombre'] ?? '') . ' ' . (string)($rowL['Apellido'] ?? ''));
        } else {
            // 2) Respaldo: resolver por nombre de líder en el CSV.
            $lider = findLeaderByName($pdo, $liderNombreCsv);
            if (!$lider) {
                $resumen['lider_no_encontrado']++;
                $detalles[] = [
                    'estado' => 'Lider no encontrado',
                    'doc' => $item['doc'],
                    'nombre' => trim((string)$persona['Nombre'] . ' ' . (string)$persona['Apellido']),
                    'lider' => $liderNombreCsv,
                ];
                continue;
            }

            $idMin = (int)($lider['Id_Ministerio'] ?? 0);
            if ($idMin <= 0) {
                $resumen['lider_sin_ministerio']++;
                $detalles[] = [
                    'estado' => 'Lider sin ministerio',
                    'doc' => $item['doc'],
                    'nombre' => trim((string)$persona['Nombre'] . ' ' . (string)$persona['Apellido']),
                    'lider' => trim((string)$lider['Nombre'] . ' ' . (string)$lider['Apellido']),
                ];
                continue;
            }

            $resumen['resuelto_por_nombre_lider']++;
            $idLider = (int)$lider['Id_Persona'];
            $idCelulaLider = findCelulaByLeader($pdo, $idLider) ?: 0;
            $liderNombreFinal = trim((string)$lider['Nombre'] . ' ' . (string)$lider['Apellido']);
        }

        $yaOk = ((int)($persona['Id_Lider'] ?? 0) === $idLider) && ((int)($persona['Id_Ministerio'] ?? 0) === $idMin);

        if ($yaOk) {
            $resumen['ok_listo']++;
            continue;
        }

        $resumen['actualizables']++;

        if ($aplicar) {
            $checklist = normalizeChecklist((string)($persona['Escalera_Checklist'] ?? ''));
            $checklist['Ganar'][0] = true;
            $json = json_encode($checklist, JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new RuntimeException('Error serializando checklist de persona ' . $idPersona);
            }

            $upd->execute([$idLider, $idMin, $json, $idCelulaLider, $idPersona]);
            if ($upd->rowCount() > 0) {
                $resumen['actualizados']++;
            }
        }

        $detalles[] = [
            'estado' => $aplicar ? 'Actualizado' : 'Actualizar',
            'doc' => (string)($persona['Numero_Documento'] ?? $item['doc']),
            'nombre' => trim((string)$persona['Nombre'] . ' ' . (string)$persona['Apellido']),
            'lider' => $liderNombreFinal,
        ];
    }

    if ($aplicar) {
        $pdo->commit();
    }

    out('Persona no encontrada: ' . $resumen['persona_no_encontrada'], $isCli, 'warn');
    out('Lider no encontrado: ' . $resumen['lider_no_encontrado'], $isCli, 'warn');
    out('Lider sin ministerio: ' . $resumen['lider_sin_ministerio'], $isCli, 'warn');
    out('Resueltas por celula: ' . $resumen['resuelto_por_celula'], $isCli, 'ok');
    out('Resueltas por nombre de lider: ' . $resumen['resuelto_por_nombre_lider'], $isCli, 'ok');
    out('Ya estaban correctas: ' . $resumen['ok_listo'], $isCli, 'ok');
    out('Candidatas a actualizar: ' . $resumen['actualizables'], $isCli, 'ok');
    out('Actualizadas: ' . $resumen['actualizados'], $isCli, 'ok');

    if (!$isCli) {
        $preview = array_slice($detalles, 0, 120);
        echo '<table><tr><th>Estado</th><th>Documento</th><th>Persona</th><th>Lider objetivo</th></tr>';
        foreach ($preview as $d) {
            echo '<tr>'
                . '<td>' . htmlspecialchars((string)$d['estado'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string)$d['doc'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string)$d['nombre'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td>' . htmlspecialchars((string)$d['lider'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '</tr>';
        }
        if (count($detalles) > 120) {
            echo '<tr><td colspan="4">... y ' . (count($detalles) - 120) . ' mas</td></tr>';
        }
        echo '</table>';

        if (!$aplicar) {
            echo '<a class="btn" href="?aplicar=1">Aplicar cambios</a>';
        }
    }

    out($aplicar ? 'Listo: cambios aplicados.' : 'Simulacion completada.', $isCli, 'ok');
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('ERROR: ' . $e->getMessage(), $isCli, 'err');
    if (!$isCli) {
        http_response_code(500);
    }
}

if (!$isCli) {
    echo '</body></html>';
}
