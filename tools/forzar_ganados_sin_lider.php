<?php
/**
 * Forzar que TODAS las personas en Proceso=Ganar sin lider queden visibles
 * en el modulo Ganados y limpias del marcador reasignado_automatico.
 *
 * - Si tiene celula con lider: asigna lider + ministerio desde la celula.
 * - Si NO tiene celula o su celula no tiene lider: limpia el marcador y
 *   deja Proceso='Ganar' para asignacion manual.
 *
 * Uso web:
 *   Simulacion : /tools/forzar_ganados_sin_lider.php
 *   Aplicar    : /tools/forzar_ganados_sin_lider.php?aplicar=1
 *
 * Uso CLI:
 *   Simulacion : php tools/forzar_ganados_sin_lider.php
 *   Aplicar    : php tools/forzar_ganados_sin_lider.php --aplicar
 */

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../conexion.php';

$isCli = PHP_SAPI === 'cli';
$aplicar = $isCli
    ? in_array('--aplicar', $argv ?? [], true)
    : (isset($_GET['aplicar']) && (string)$_GET['aplicar'] === '1');

if (!$isCli) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8">
          <title>Forzar Ganados sin Lider</title>
          <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h2   { color: #333; }
            .ok  { color: green; }
            .warn{ color: #c90; font-weight:bold; }
            .err { color: red;  font-weight:bold; }
            .box { background:#f5f5f5; border:1px solid #ccc; padding:10px; border-radius:6px; margin-bottom:12px; }
            table { border-collapse:collapse; width:100%; margin-top:8px; }
            th,td { border:1px solid #ccc; padding:4px 8px; font-size:13px; text-align:left; }
            th { background:#eee; }
            a.btn { display:inline-block; margin-top:14px; padding:8px 16px; background:#c0392b; color:#fff;
                    text-decoration:none; border-radius:4px; font-weight:bold; }
          </style>
          </head><body>';
}

function out($text, $isCli, $class = '') {
    if ($isCli) {
        echo $text . PHP_EOL;
    } else {
        $cls = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
        echo '<div' . $cls . '>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}

function tableHasColumn(PDO $pdo, $table, $column) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->execute([(string)$table, (string)$column]);
    return (int)$stmt->fetchColumn() > 0;
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
    return $data;
}

try {
    if (!$isCli) {
        echo '<h2>Forzar Ganados sin Lider</h2>';
        echo '<div class="box">Modo: <strong>' . ($aplicar ? 'APLICAR' : 'SIMULACION') . '</strong></div>';
    }

    /* ----------------------------------------------------------------
       Detectar columna de ministerio en celula
    ---------------------------------------------------------------- */
    $colMinisterioCelula = tableHasColumn($pdo, 'celula', 'Id_Ministerio_Lider')
        ? 'Id_Ministerio_Lider'
        : (tableHasColumn($pdo, 'celula', 'Id_Ministerio') ? 'Id_Ministerio' : '');

    $exprMinisterio = $colMinisterioCelula !== ''
        ? 'c.' . $colMinisterioCelula
        : 'pl.Id_Ministerio';

    /* ----------------------------------------------------------------
       Consulta principal: ALL activas en Ganar sin lider
       Incluye tanto las que tienen marcador como las que no lo tienen
       (amplio para no dejar ninguna atrás)
    ---------------------------------------------------------------- */
    $sql = "SELECT
                p.Id_Persona,
                p.Nombre,
                p.Apellido,
                p.Id_Celula,
                p.Proceso,
                p.Escalera_Checklist,
                c.Id_Lider           AS Celula_Id_Lider,
                {$exprMinisterio}    AS Celula_Id_Ministerio
            FROM persona p
            LEFT JOIN celula c  ON c.Id_Celula  = p.Id_Celula
            LEFT JOIN persona pl ON pl.Id_Persona = c.Id_Lider
            WHERE (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
              AND (p.Proceso = 'Ganar' OR p.Proceso IS NULL OR p.Proceso = '')
              AND p.Id_Lider    IS NULL
              AND p.Id_Ministerio IS NULL";

    $stmt = $pdo->query($sql);
    $todos = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    out('Total personas en Ganar sin lider: ' . count($todos), $isCli);

    if (empty($todos)) {
        out('No hay personas que necesiten restauracion.', $isCli, 'ok');
        if (!$isCli) echo '</body></html>';
        exit;
    }

    /* ----------------------------------------------------------------
       Separar en grupos
    ---------------------------------------------------------------- */
    $conLider    = [];  // celula conocida con lider -> restaurar completo
    $sinLider    = [];  // sin celula o celula sin lider -> restaurar parcial

    foreach ($todos as $fila) {
        $idLider    = (int)($fila['Celula_Id_Lider']    ?? 0);
        $idMinister = (int)($fila['Celula_Id_Ministerio'] ?? 0);

        if ($idLider > 0 && $idMinister > 0) {
            $conLider[] = $fila;
        } else {
            $sinLider[] = $fila;
        }
    }

    out('Con celula+lider (restauracion completa)  : ' . count($conLider),  $isCli, 'ok');
    out('Sin celula o sin lider (restauracion parcial): ' . count($sinLider), $isCli, 'warn');

    /* ---- Vista previa (solo simulacion) ---- */
    if (!$aplicar) {
        if (!$isCli && !empty($todos)) {
            $preview = array_slice($todos, 0, 50);
            echo '<table><tr><th>ID</th><th>Nombre</th><th>Celula</th><th>Celula_Lider</th><th>Ministerio</th><th>Grupo</th></tr>';
            foreach ($preview as $f) {
                $nombre = trim((string)($f['Nombre'] ?? '') . ' ' . (string)($f['Apellido'] ?? ''));
                $grupo  = ((int)($f['Celula_Id_Lider'] ?? 0) > 0 && (int)($f['Celula_Id_Ministerio'] ?? 0) > 0)
                        ? '<span class="ok">Completa</span>'
                        : '<span class="warn">Parcial</span>';
                echo '<tr>'
                    . '<td>' . (int)$f['Id_Persona'] . '</td>'
                    . '<td>' . htmlspecialchars($nombre) . '</td>'
                    . '<td>' . (int)($f['Id_Celula'] ?? 0) . '</td>'
                    . '<td>' . (int)($f['Celula_Id_Lider'] ?? 0) . '</td>'
                    . '<td>' . (int)($f['Celula_Id_Ministerio'] ?? 0) . '</td>'
                    . '<td>' . $grupo . '</td>'
                    . '</tr>';
            }
            if (count($todos) > 50) {
                echo '<tr><td colspan="6">... y ' . (count($todos) - 50) . ' mas</td></tr>';
            }
            echo '</table>';
            echo '<a class="btn" href="?aplicar=1">Aplicar ahora (' . count($todos) . ' personas)</a>';
        } else {
            out('Simulacion completada. Para aplicar use ?aplicar=1 o --aplicar.', $isCli);
        }
        if (!$isCli) echo '</body></html>';
        exit;
    }

    /* ================================================================
       APLICAR
    ================================================================ */
    $pdo->beginTransaction();

    /* --- Grupo A: restauracion completa (con celula+lider) --- */
    $sqlA = "UPDATE persona
             SET Id_Lider            = ?,
                 Id_Ministerio       = ?,
                 Fecha_Asignacion_Lider = NOW(),
                 Proceso             = 'Ganar',
                 Escalera_Checklist  = ?
             WHERE Id_Persona        = ?
               AND Id_Lider IS NULL
               AND Id_Ministerio IS NULL";
    $updA = $pdo->prepare($sqlA);
    $aplicadosA = 0;

    foreach ($conLider as $fila) {
        $idPersona  = (int)$fila['Id_Persona'];
        $idLider    = (int)$fila['Celula_Id_Lider'];
        $idMinister = (int)$fila['Celula_Id_Ministerio'];

        $cl = normalizeChecklist((string)($fila['Escalera_Checklist'] ?? ''));
        $cl['Ganar'][0] = true;  // marcar primer paso Ganar como completado
        unset($cl['_meta']['reasignado_automatico']);
        unset($cl['_meta']['reasignado_automatico_at']);
        unset($cl['_meta']['reasignado_automatico_motivo']);
        if (empty($cl['_meta'])) unset($cl['_meta']);

        $json = json_encode($cl, JSON_UNESCAPED_UNICODE);
        $updA->execute([$idLider, $idMinister, $json, $idPersona]);
        $aplicadosA += $updA->rowCount() > 0 ? 1 : 0;
    }

    /* --- Grupo B: restauracion parcial (sin lider en celula) --- */
    $sqlB = "UPDATE persona
             SET Proceso            = 'Ganar',
                 Escalera_Checklist = ?
             WHERE Id_Persona       = ?
               AND Id_Lider IS NULL
               AND Id_Ministerio IS NULL";
    $updB = $pdo->prepare($sqlB);
    $aplicadosB = 0;

    foreach ($sinLider as $fila) {
        $idPersona = (int)$fila['Id_Persona'];

        $cl = normalizeChecklist((string)($fila['Escalera_Checklist'] ?? ''));
        unset($cl['_meta']['reasignado_automatico']);
        unset($cl['_meta']['reasignado_automatico_at']);
        unset($cl['_meta']['reasignado_automatico_motivo']);
        if (empty($cl['_meta'])) unset($cl['_meta']);

        $json = json_encode($cl, JSON_UNESCAPED_UNICODE);
        $updB->execute([$json, $idPersona]);
        $aplicadosB += $updB->rowCount() > 0 ? 1 : 0;
    }

    $pdo->commit();

    out('--- RESULTADO ---', $isCli);
    out('Restaurados completos (con lider+min)  : ' . $aplicadosA, $isCli, 'ok');
    out('Restaurados parciales (solo Ganar+limpieza): ' . $aplicadosB, $isCli, 'warn');
    out('TOTAL procesados: ' . ($aplicadosA + $aplicadosB), $isCli);
    out('', $isCli);
    if ($aplicadosB > 0) {
        out('ATENCION: ' . $aplicadosB . ' personas quedaron sin lider asignado.', $isCli, 'warn');
        out('Estan visibles en Ganados para asignacion manual.', $isCli, 'warn');
    }
    out('Listo.', $isCli, 'ok');

} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('ERROR: ' . $e->getMessage(), $isCli, 'err');
    if (!$isCli) http_response_code(500);
}

if (!$isCli) echo '</body></html>';
