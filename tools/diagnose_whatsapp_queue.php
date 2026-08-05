<?php
/**
 * Script de diagnóstico para cola de WhatsApp
 * Muestra filas pendientes/procesadas en `whatsapp_cola_envio` junto con la campaña y plantilla asociadas.
 * Uso (local): http://localhost/mcimadrid/tools/diagnose_whatsapp_queue.php
 * Parámetros opcionales: ?limit=100&estado=pendiente&plantilla_id=123
 */

require_once __DIR__ . '/../conexion.php';

// Parámetros
$limit = max(1, min(1000, (int)($_GET['limit'] ?? 200)));
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : null;
$filterPlantilla = isset($_GET['plantilla_id']) ? (int)$_GET['plantilla_id'] : null;

$where = [];
$params = [];
if ($estado !== null && $estado !== '') {
    $where[] = 'q.estado = ?';
    $params[] = $estado;
}
if ($filterPlantilla !== null && $filterPlantilla > 0) {
    $where[] = 'c.id_plantilla = ?';
    $params[] = $filterPlantilla;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "SELECT q.id AS id_cola, q.estado, q.programado_en, q.intentos, q.proveedor_message_id,
           q.id_campana, c.nombre AS campana_nombre, c.id_plantilla,
           p.id AS plantilla_id, p.nombre AS plantilla_nombre, p.cuerpo AS plantilla_cuerpo,
           q.id_nehemias, q.telefono
    FROM whatsapp_cola_envio q
    LEFT JOIN whatsapp_campanas c ON c.id = q.id_campana
    LEFT JOIN whatsapp_plantillas p ON p.id = c.id_plantilla
    $whereSql
    ORDER BY q.programado_en DESC, q.id DESC
    LIMIT " . ((int)$limit);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Conteo por plantilla
$sqlCount = "SELECT p.id AS plantilla_id, p.nombre AS plantilla_nombre, COUNT(*) AS total
             FROM whatsapp_cola_envio q
             LEFT JOIN whatsapp_campanas c ON c.id = q.id_campana
             LEFT JOIN whatsapp_plantillas p ON p.id = c.id_plantilla
             GROUP BY p.id, p.nombre
             ORDER BY total DESC";
$counts = $pdo->query($sqlCount)->fetchAll();

?><!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Diagnóstico WhatsApp - Cola</title>
    <style>body{font-family:Arial,Helvetica,sans-serif;padding:16px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:6px;text-align:left}th{background:#f4f6fb}</style>
</head>
<body>
<h2>Diagnóstico de la cola de WhatsApp</h2>
<p>Filtros: <strong>estado</strong>=<?= htmlspecialchars($estado ?? '(todos)') ?>, <strong>plantilla_id</strong>=<?= htmlspecialchars($filterPlantilla ?? '(todos)') ?>, <strong>limit</strong>=<?= $limit ?></p>

<h3>Resumen por plantilla</h3>
<table>
    <tr><th>plantilla_id</th><th>plantilla_nombre</th><th>total</th></tr>
    <?php foreach ($counts as $c): ?>
        <tr>
            <td><?= htmlspecialchars($c['plantilla_id'] ?? 'NULL') ?></td>
            <td><?= htmlspecialchars($c['plantilla_nombre'] ?? '(sin plantilla)') ?></td>
            <td><?= htmlspecialchars($c['total']) ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h3>Entradas (últimas <?= $limit ?>)</h3>
<table>
    <tr>
        <th>id_cola</th><th>estado</th><th>programado_en</th><th>intentos</th><th>proveedor_message_id</th>
        <th>id_campana</th><th>campana_nombre</th><th>id_plantilla</th><th>plantilla_nombre</th><th>telefono</th>
    </tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= htmlspecialchars($r['id_cola']) ?></td>
            <td><?= htmlspecialchars($r['estado']) ?></td>
            <td><?= htmlspecialchars($r['programado_en']) ?></td>
            <td><?= htmlspecialchars($r['intentos']) ?></td>
            <td><?= htmlspecialchars($r['proveedor_message_id'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['id_campana'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['campana_nombre'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['id_plantilla'] ?? '') ?></td>
            <td><?= htmlspecialchars($r['plantilla_nombre'] ?? '(sin plantilla)') ?></td>
            <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<h3>Detalles de plantillas listadas</h3>
<?php foreach ($rows as $r):
    if (trim((string)($r['plantilla_cuerpo'] ?? '')) === '') continue;
    $id = $r['plantilla_id'] ?? $r['id_plantilla'] ?? null;
?>
    <h4>Plantilla ID <?= htmlspecialchars($id ?? '(NULL)') ?> &mdash; <?= htmlspecialchars($r['plantilla_nombre'] ?? '') ?></h4>
    <pre style="white-space:pre-wrap;background:#f8f9fc;border:1px solid #e6e9f2;padding:8px"><?= htmlspecialchars(substr((string)($r['plantilla_cuerpo'] ?? ''),0,2000)) ?></pre>
<?php endforeach; ?>

<p>Si encuentras filas con <strong>plantilla_nombre vacío</strong> pero con <strong>id_plantilla</strong> no nulo, puede indicar plantillas eliminadas físicamente. Avísame y preparo pasos para corregir (recrear plantilla, eliminar campañas, o limpiar cola).</p>
</body>
</html>
