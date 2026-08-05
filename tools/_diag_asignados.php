<?php
require __DIR__ . '/../conexion.php';

$cols = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='persona' AND COLUMN_NAME IN ('Origen_Ganar','Es_Antiguo','Invitado_Por','Canal_Creacion')")->fetchAll(PDO::FETCH_COLUMN);
echo 'cols: ' . implode(',', $cols) . PHP_EOL;

$row = $pdo->query("SELECT 
  COUNT(*) AS total,
  SUM(CASE WHEN (Id_Lider>0 OR Id_Ministerio>0) AND TRIM(COALESCE(Invitado_Por,''))='' THEN 1 ELSE 0 END) AS con_asignacion_sin_invitador,
  SUM(CASE WHEN LOWER(TRIM(COALESCE(Tipo_Reunion,'')))='asignados' THEN 1 ELSE 0 END) AS tipo_asignados,
  SUM(CASE WHEN (Id_Lider>0 OR Id_Ministerio>0) AND TRIM(COALESCE(Invitado_Por,''))<>'' THEN 1 ELSE 0 END) AS con_asignacion_con_invitador,
  SUM(CASE WHEN Id_Ministerio>0 AND Id_Lider>0 AND Id_Celula>0 THEN 1 ELSE 0 END) AS ubicacion_completa
FROM persona WHERE (Estado_Cuenta='Activo' OR Estado_Cuenta IS NULL)")->fetch(PDO::FETCH_ASSOC);
print_r($row);

echo PHP_EOL . 'Sample assigned without inviter:' . PHP_EOL;
$q = "SELECT Id_Persona, Nombre, Apellido, Tipo_Reunion, Origen_Ganar, Invitado_Por, Id_Lider, Id_Ministerio, Id_Celula, Es_Antiguo, Canal_Creacion, Fecha_Registro, Proceso
FROM persona 
WHERE (Id_Lider>0 OR Id_Ministerio>0) AND TRIM(COALESCE(Invitado_Por,''))=''
ORDER BY Fecha_Registro DESC LIMIT 20";
foreach ($pdo->query($q) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . PHP_EOL;
}
