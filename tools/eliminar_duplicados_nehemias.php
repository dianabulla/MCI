<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/conexion.php';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

$modo = isset($_GET['modo']) && $_GET['modo'] === 'estricto' ? 'estricto' : 'normal';
$comparacion = $modo === 'estricto'
    ? "BINARY TRIM(n1.Nombres) = BINARY TRIM(n2.Nombres) AND BINARY TRIM(n1.Apellidos) = BINARY TRIM(n2.Apellidos) AND BINARY TRIM(n1.Numero_Cedula) = BINARY TRIM(n2.Numero_Cedula)"
    : "TRIM(n1.Nombres) = TRIM(n2.Nombres) AND TRIM(n1.Apellidos) = TRIM(n2.Apellidos) AND TRIM(n1.Numero_Cedula) = TRIM(n2.Numero_Cedula)";

$gruposSql = $modo === 'estricto'
    ? "SELECT TRIM(Nombres) Nombres, TRIM(Apellidos) Apellidos, TRIM(Numero_Cedula) Numero_Cedula, COUNT(*) cantidad
       FROM nehemias
       WHERE TRIM(COALESCE(Nombres,''))<>''
         AND TRIM(COALESCE(Apellidos,''))<>''
         AND TRIM(COALESCE(Numero_Cedula,''))<>''
         AND TRIM(Numero_Cedula) NOT IN ('1','01')
       GROUP BY BINARY TRIM(Nombres), BINARY TRIM(Apellidos), BINARY TRIM(Numero_Cedula)
       HAVING COUNT(*) > 1
       ORDER BY cantidad DESC"
    : "SELECT TRIM(Nombres) Nombres, TRIM(Apellidos) Apellidos, TRIM(Numero_Cedula) Numero_Cedula, COUNT(*) cantidad
       FROM nehemias
       WHERE TRIM(COALESCE(Nombres,''))<>''
         AND TRIM(COALESCE(Apellidos,''))<>''
         AND TRIM(COALESCE(Numero_Cedula,''))<>''
         AND TRIM(Numero_Cedula) NOT IN ('1','01')
       GROUP BY TRIM(Nombres), TRIM(Apellidos), TRIM(Numero_Cedula)
       HAVING COUNT(*) > 1
       ORDER BY cantidad DESC";

$idsSql = "SELECT DISTINCT n1.Id_Nehemias
           FROM nehemias n1
           JOIN nehemias n2
             ON n1.Id_Nehemias > n2.Id_Nehemias
            AND {$comparacion}
           WHERE TRIM(COALESCE(n1.Nombres,''))<>''
             AND TRIM(COALESCE(n1.Apellidos,''))<>''
             AND TRIM(COALESCE(n1.Numero_Cedula,''))<>''
             AND TRIM(n1.Numero_Cedula) NOT IN ('1','01')";

$mensaje = '';
$confirmarRaw = isset($_POST['confirmar']) ? trim((string)$_POST['confirmar']) : '';
$confirmarNormalizado = strtoupper(str_replace(['Í', 'í'], 'I', $confirmarRaw));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $confirmarNormalizado === 'SI') {
    $conn->begin_transaction();
    try {
        $conn->query("DROP TEMPORARY TABLE IF EXISTS tmp_ids_duplicados_nehemias_web");
        $conn->query("CREATE TEMPORARY TABLE tmp_ids_duplicados_nehemias_web (Id_Nehemias INT PRIMARY KEY)");
        $conn->query("INSERT INTO tmp_ids_duplicados_nehemias_web (Id_Nehemias) {$idsSql}");
        $conn->query("DELETE n FROM nehemias n JOIN tmp_ids_duplicados_nehemias_web t ON t.Id_Nehemias = n.Id_Nehemias");
        $eliminados = $conn->affected_rows;
        $conn->commit();
        $mensaje = "<p style='color:green'><strong>✅ Registros eliminados:</strong> {$eliminados}</p>";
    } catch (Throwable $e) {
        $conn->rollback();
        $mensaje = "<p style='color:red'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mensaje = "<p style='color:#a76b00'><strong>⚠️ Confirmación inválida:</strong> escribe SI para ejecutar la eliminación.</p>";
}

$grupos = $conn->query($gruposSql);
$preview = [];
$totalGrupos = 0;
if ($grupos) {
    while ($row = $grupos->fetch_assoc()) {
        $totalGrupos++;
        if (count($preview) < 20) {
            $preview[] = $row;
        }
    }
}

$totalAEliminar = 0;
$resEliminar = $conn->query("SELECT COUNT(*) total FROM ({$idsSql}) x");
if ($resEliminar && ($r = $resEliminar->fetch_assoc())) {
    $totalAEliminar = (int)$r['total'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Duplicados Nehemias</title>
    <style>
        body{font-family:Arial;padding:20px;background:#f5f5f5}
        .box{max-width:980px;margin:auto;background:#fff;padding:20px;border-radius:8px}
        table{width:100%;border-collapse:collapse;margin-top:15px}
        th,td{border:1px solid #ddd;padding:8px;font-size:14px}
        th{background:#0078D4;color:#fff}
        .btn{background:#f37021;color:#fff;border:0;padding:10px 16px;border-radius:5px;cursor:pointer}
        .muted{color:#666}
    </style>
</head>
<body>
<div class="box">
    <h1>Eliminar Duplicados Nehemias</h1>
    <p><strong>Modo:</strong> <?php echo $modo === 'estricto' ? 'Estricto' : 'Normal'; ?> | <a href="?modo=normal">Normal</a> | <a href="?modo=estricto">Estricto</a></p>
    <p class="muted">No elimina registros con cédula <strong>1</strong> o <strong>01</strong>.</p>

    <?php echo $mensaje; ?>

    <p><strong>Grupos duplicados detectados:</strong> <?php echo $totalGrupos; ?></p>
    <p><strong>Registros a eliminar:</strong> <?php echo $totalAEliminar; ?></p>

    <?php if (!empty($preview)): ?>
    <table>
        <tr><th>Nombres</th><th>Apellidos</th><th>Cédula</th><th>Cantidad</th></tr>
        <?php foreach ($preview as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['Nombres'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['Apellidos'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars($row['Numero_Cedula'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int)$row['cantidad']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>

    <form method="POST" style="margin-top:16px">
        <label>Escribe <strong>SI</strong> para confirmar eliminación:</label>
        <input type="text" name="confirmar" required>
        <button class="btn" type="submit">Eliminar duplicados</button>
    </form>
</div>
</body>
</html>
