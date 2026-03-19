<?php
/**
 * Restaurar personas afectadas por reasignacion automatica masiva.
 *
 * Uso web:
 *   - Simulacion: /tools/restaurar_reasignados_automaticos.php
 *   - Aplicar:    /tools/restaurar_reasignados_automaticos.php?aplicar=1
 *
 * Uso CLI:
 *   - Simulacion: php tools/restaurar_reasignados_automaticos.php
 *   - Aplicar:    php tools/restaurar_reasignados_automaticos.php --aplicar
 */

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../conexion.php';

$isCli = PHP_SAPI === 'cli';
$aplicar = false;

if ($isCli) {
    $aplicar = in_array('--aplicar', $argv ?? [], true);
} else {
    $aplicar = isset($_GET['aplicar']) && (string)$_GET['aplicar'] === '1';
    header('Content-Type: text/html; charset=utf-8');
}

function out($text, $isCli) {
    if ($isCli) {
        echo $text . PHP_EOL;
    } else {
        echo '<div style="font-family:Arial,sans-serif; margin:6px 0;">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

function normalizeChecklist($rawChecklist) {
    $decoded = [];
    if (is_string($rawChecklist) && trim($rawChecklist) !== '') {
        $tmp = json_decode($rawChecklist, true);
        if (is_array($tmp)) {
            $decoded = $tmp;
        }
    }

    if (!isset($decoded['Ganar']) || !is_array($decoded['Ganar'])) {
        $decoded['Ganar'] = [false, false, false, false];
    }
    for ($i = 0; $i < 4; $i++) {
        if (!array_key_exists($i, $decoded['Ganar'])) {
            $decoded['Ganar'][$i] = false;
        }
        $decoded['Ganar'][$i] = !empty($decoded['Ganar'][$i]);
    }

    if (!isset($decoded['_meta']) || !is_array($decoded['_meta'])) {
        $decoded['_meta'] = [];
    }

    return $decoded;
}

function tableHasColumn(PDO $pdo, $table, $column) {
    $sql = "SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([(string)$table, (string)$column]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return (int)($row['total'] ?? 0) > 0;
}

try {
    $colMinisterioCelula = tableHasColumn($pdo, 'celula', 'Id_Ministerio_Lider')
        ? 'Id_Ministerio_Lider'
        : (tableHasColumn($pdo, 'celula', 'Id_Ministerio') ? 'Id_Ministerio' : '');

    $exprMinisterio = $colMinisterioCelula !== ''
        ? 'c.' . $colMinisterioCelula
        : 'pl.Id_Ministerio';

    $sql = "SELECT
                p.Id_Persona,
                p.Nombre,
                p.Apellido,
                p.Id_Celula,
                p.Escalera_Checklist,
                c.Id_Lider AS Celula_Id_Lider,
                {$exprMinisterio} AS Celula_Id_Ministerio
            FROM persona p
            LEFT JOIN celula c ON c.Id_Celula = p.Id_Celula
            LEFT JOIN persona pl ON pl.Id_Persona = c.Id_Lider
            WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
              AND (p.Proceso = 'Ganar' OR p.Proceso IS NULL OR p.Proceso = '')
              AND p.Id_Lider IS NULL
              AND p.Id_Ministerio IS NULL
              AND p.Escalera_Checklist LIKE '%\"reasignado_automatico\":true%'";

    $stmt = $pdo->query($sql);
    $afectados = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    if (!$isCli) {
        echo '<h2 style="font-family:Arial,sans-serif;">Restauracion de reasignados automaticos</h2>';
        echo '<div style="font-family:Arial,sans-serif; margin-bottom:10px;">Modo: <strong>' . ($aplicar ? 'APLICAR' : 'SIMULACION') . '</strong></div>';
    }

    out('Detectados candidatos: ' . count($afectados), $isCli);

    if (empty($afectados)) {
        out('No hay registros para restaurar.', $isCli);
        exit;
    }

    $restaurables = [];
    $noRestaurables = [];

    foreach ($afectados as $fila) {
        $idLider = (int)($fila['Celula_Id_Lider'] ?? 0);
        $idMinisterio = (int)($fila['Celula_Id_Ministerio'] ?? 0);

        if ($idLider > 0 && $idMinisterio > 0) {
            $restaurables[] = $fila;
        } else {
            $noRestaurables[] = $fila;
        }
    }

    out('Restaurables por celula: ' . count($restaurables), $isCli);
    out('No restaurables (sin datos de celula/lider/min): ' . count($noRestaurables), $isCli);

    $mostrar = array_slice($restaurables, 0, 20);
    foreach ($mostrar as $fila) {
        $nombre = trim((string)($fila['Nombre'] ?? '') . ' ' . (string)($fila['Apellido'] ?? ''));
        out(' - ID ' . (int)$fila['Id_Persona'] . ' | ' . $nombre . ' | Celula ' . (int)($fila['Id_Celula'] ?? 0), $isCli);
    }
    if (count($restaurables) > 20) {
        out(' ... y ' . (count($restaurables) - 20) . ' mas', $isCli);
    }

    if (!$aplicar) {
        out('Simulacion completada. Para aplicar use ?aplicar=1 o --aplicar.', $isCli);
        exit;
    }

    $pdo->beginTransaction();

    $sqlUpdate = "UPDATE persona
                  SET Id_Lider = ?,
                      Id_Ministerio = ?,
                      Fecha_Asignacion_Lider = NOW(),
                      Escalera_Checklist = ?
                  WHERE Id_Persona = ?
                    AND Id_Lider IS NULL
                    AND Id_Ministerio IS NULL";
    $upd = $pdo->prepare($sqlUpdate);

    $aplicados = 0;

    foreach ($restaurables as $fila) {
        $idPersona = (int)$fila['Id_Persona'];
        $idLider = (int)$fila['Celula_Id_Lider'];
        $idMinisterio = (int)$fila['Celula_Id_Ministerio'];

        $checklist = normalizeChecklist((string)($fila['Escalera_Checklist'] ?? ''));
        $checklist['Ganar'][0] = true;

        if (isset($checklist['_meta'])) {
            unset($checklist['_meta']['reasignado_automatico']);
            unset($checklist['_meta']['reasignado_automatico_at']);
            unset($checklist['_meta']['reasignado_automatico_motivo']);
            if (empty($checklist['_meta'])) {
                unset($checklist['_meta']);
            }
        }

        $checklistJson = json_encode($checklist, JSON_UNESCAPED_UNICODE);
        if ($checklistJson === false) {
            throw new RuntimeException('No se pudo serializar checklist para persona ' . $idPersona);
        }

        $upd->execute([$idLider, $idMinisterio, $checklistJson, $idPersona]);
        $aplicados += $upd->rowCount() > 0 ? 1 : 0;
    }

    $pdo->commit();

    out('Restauracion aplicada. Personas restauradas: ' . $aplicados, $isCli);
    out('Listo. Revise Pendiente por consolidar > Reasignados.', $isCli);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('ERROR: ' . $e->getMessage(), $isCli);
    http_response_code(500);
}
