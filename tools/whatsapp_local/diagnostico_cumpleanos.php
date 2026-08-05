<?php
declare(strict_types=1);

require_once __DIR__ . '/db_from_env.php';

$pdo = wa_pdo_from_env();
$row = $pdo->query('SELECT NOW() AS now_mysql, CURDATE() AS curdate')->fetch();
echo 'MySQL NOW: ' . ($row['now_mysql'] ?? '?') . ' CURDATE: ' . ($row['curdate'] ?? '?') . PHP_EOL;

$month = (int)date('m');
$day = (int)date('d');
echo "PHP hoy (servidor): {$month}/{$day}" . PHP_EOL;

echo PHP_EOL . '=== Personas que cumplen años hoy ===' . PHP_EOL;
$st = $pdo->prepare(
    "SELECT Id_Persona, Nombre, Apellido, Telefono, Fecha_Nacimiento, Estado_Cuenta
     FROM persona
     WHERE Fecha_Nacimiento IS NOT NULL
       AND Fecha_Nacimiento <> '0000-00-00'
       AND MONTH(Fecha_Nacimiento) = ?
       AND DAY(Fecha_Nacimiento) = ?
     LIMIT 30"
);
$st->execute([$month, $day]);
$personas = $st->fetchAll();
if (!$personas) {
    echo "(ninguna)\n";
} else {
    foreach ($personas as $p) {
        echo json_encode($p, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}

echo PHP_EOL . '=== Cola felicitacion_cumpleanos (todas) ===' . PHP_EOL;
$sql = "SELECT id, telefono, estado, intentos, DATE(creado_en) AS dia_creado, creado_en, programado_en,
        LEFT(COALESCE(ultimo_error, ''), 150) AS err
        FROM whatsapp_local_queue
        WHERE tipo_evento = 'felicitacion_cumpleanos'
        ORDER BY id DESC
        LIMIT 30";
foreach ($pdo->query($sql) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo PHP_EOL . '=== Pendientes que el worker SÍ procesa hoy (WA_ONLY_TODAY) ===' . PHP_EOL;
$sql2 = "SELECT id, tipo_evento, telefono, estado, intentos, creado_en
         FROM whatsapp_local_queue
         WHERE estado = 'pendiente' AND intentos < 3
           AND (
             (programado_en IS NULL AND DATE(creado_en) = CURDATE())
             OR (programado_en IS NOT NULL AND DATE(programado_en) = CURDATE())
           )
           AND (programado_en IS NULL OR programado_en <= NOW())
         ORDER BY CASE
           WHEN tipo_evento IN ('mensaje_capacitacion_destino', 'programacion_mensaje_capacitacion_destino') THEN 0
           WHEN tipo_evento = 'felicitacion_cumpleanos' THEN 1
           ELSE 2
         END, id ASC
         LIMIT 30";
$pendientesHoy = $pdo->query($sql2)->fetchAll();
if (!$pendientesHoy) {
    echo "(ninguno — el worker no enviará nada hoy con la regla de solo-hoy)\n";
} else {
    foreach ($pendientesHoy as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}

echo PHP_EOL . '=== Cumpleaños pendientes pero creados OTRO día (ignorados por WA_ONLY_TODAY) ===' . PHP_EOL;
$sql3 = "SELECT id, estado, DATE(creado_en) AS dia, creado_en, intentos, LEFT(COALESCE(ultimo_error,''), 80) AS err
         FROM whatsapp_local_queue
         WHERE tipo_evento = 'felicitacion_cumpleanos'
           AND estado IN ('pendiente', 'procesando')
           AND DATE(creado_en) <> CURDATE()
         ORDER BY id";
$stale = $pdo->query($sql3)->fetchAll();
if (!$stale) {
    echo "(ninguno)\n";
} else {
    foreach ($stale as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}

echo PHP_EOL . '=== Resumen estados cumpleaños ===' . PHP_EOL;
$sql4 = "SELECT estado, COUNT(*) AS n FROM whatsapp_local_queue
         WHERE tipo_evento = 'felicitacion_cumpleanos' GROUP BY estado";
foreach ($pdo->query($sql4) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

echo PHP_EOL . '=== Detalle enviados hoy (1172+) ===' . PHP_EOL;
$sql5 = "SELECT id, telefono, estado, procesado_en, media_url, media_tipo,
         LEFT(mensaje, 60) AS mensaje_inicio
         FROM whatsapp_local_queue
         WHERE tipo_evento = 'felicitacion_cumpleanos' AND DATE(creado_en) = CURDATE()
         ORDER BY id";
foreach ($pdo->query($sql5) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

$t = $pdo->query("SELECT media_url, media_tipo FROM whatsapp_mensaje_template WHERE clave = 'felicitacion_cumpleanos' LIMIT 1")->fetch();
echo PHP_EOL . 'Plantilla media: ' . json_encode($t ?: [], JSON_UNESCAPED_UNICODE) . PHP_EOL;
