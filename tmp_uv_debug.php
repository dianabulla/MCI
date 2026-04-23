<?php
require_once __DIR__ . '/conexion.php';

$generoExpr = "LOWER(TRIM(COALESCE(NULLIF(CONVERT(s.Genero USING utf8mb4) COLLATE utf8mb4_general_ci, ''), NULLIF(CONVERT(p.Genero USING utf8mb4) COLLATE utf8mb4_general_ci, ''), '')))";
$ministerioExpr = "COALESCE(NULLIF(TRIM(s.Nombre_Ministerio), ''), NULLIF(TRIM(ms.Nombre_Ministerio), ''), NULLIF(TRIM(mp.Nombre_Ministerio), ''), 'Sin ministerio')";
$esHombre = "($generoExpr LIKE '%hombre%' OR $generoExpr LIKE '%mascul%' OR $generoExpr IN ('m','masc','male','h'))";
$esMujer = "($generoExpr LIKE '%mujer%' OR $generoExpr LIKE '%femen%' OR $generoExpr IN ('f','fem','female'))";

echo "=== 1) RESUMEN GLOBAL UV ===\n";
$sql1 = "SELECT
COUNT(*) AS total,
SUM(CASE WHEN $esHombre THEN 1 ELSE 0 END) AS hombres,
SUM(CASE WHEN $esMujer THEN 1 ELSE 0 END) AS mujeres,
SUM(CASE WHEN NOT ($esHombre OR $esMujer) THEN 1 ELSE 0 END) AS sin_genero,
SUM(CASE WHEN s.Asistio_Clase = 1 THEN 1 ELSE 0 END) AS asistieron_total,
SUM(CASE WHEN s.Asistio_Clase = 1 AND $esHombre THEN 1 ELSE 0 END) AS asistieron_h,
SUM(CASE WHEN s.Asistio_Clase = 1 AND $esMujer THEN 1 ELSE 0 END) AS asistieron_m,
SUM(CASE WHEN s.Asistio_Clase = 1 AND NOT ($esHombre OR $esMujer) THEN 1 ELSE 0 END) AS asistieron_sin_genero
FROM escuela_formacion_inscripcion s
LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
WHERE s.Programa = 'universidad_vida'";
$r1 = $pdo->query($sql1)->fetch(PDO::FETCH_ASSOC);
print_r($r1);

echo "\n=== 2) RESUMEN POR MINISTERIO (marcando total!=h+m) ===\n";
$sql2 = "SELECT
$ministerioExpr AS ministerio,
COUNT(*) AS total,
SUM(CASE WHEN $esHombre THEN 1 ELSE 0 END) AS h,
SUM(CASE WHEN $esMujer THEN 1 ELSE 0 END) AS m,
SUM(CASE WHEN NOT ($esHombre OR $esMujer) THEN 1 ELSE 0 END) AS sin_genero,
SUM(CASE WHEN s.Asistio_Clase = 1 THEN 1 ELSE 0 END) AS asistieron_total
FROM escuela_formacion_inscripcion s
LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
LEFT JOIN ministerio ms ON s.Id_Ministerio = ms.Id_Ministerio
LEFT JOIN ministerio mp ON p.Id_Ministerio = mp.Id_Ministerio
WHERE s.Programa = 'universidad_vida'
GROUP BY $ministerioExpr
ORDER BY $ministerioExpr";
$rows2 = $pdo->query($sql2)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows2 as $row) {
    $diff = ((int)$row['total'] !== ((int)$row['h'] + (int)$row['m'])) ? '  <-- DIFERENCIA' : '';
    echo str_pad($row['ministerio'], 35) . " total=" . $row['total'] . " h=" . $row['h'] . " m=" . $row['m'] . " sin_genero=" . $row['sin_genero'] . " asistieron=" . $row['asistieron_total'] . $diff . "\n";
}

echo "\n=== 3) TOP 20 DUPLICADOS UV POR Id_Persona ===\n";
$sql3 = "SELECT s.Id_Persona, COUNT(*) AS repeticiones,
MAX(TRIM(COALESCE(s.Nombre, ''))) AS nombre_inscripcion,
MAX(TRIM(CONCAT(COALESCE(p.Nombre,''),' ',COALESCE(p.Apellido,'')))) AS nombre_persona
FROM escuela_formacion_inscripcion s
LEFT JOIN persona p ON s.Id_Persona = p.Id_Persona
WHERE s.Programa='universidad_vida' AND s.Id_Persona > 0
GROUP BY s.Id_Persona
HAVING COUNT(*) > 1
ORDER BY repeticiones DESC, s.Id_Persona ASC
LIMIT 20";
$rows3 = $pdo->query($sql3)->fetchAll(PDO::FETCH_ASSOC);
if (empty($rows3)) {
    echo "Sin duplicados por Id_Persona.\n";
} else {
    foreach ($rows3 as $row) {
        echo "Id_Persona=" . $row['Id_Persona'] . " reps=" . $row['repeticiones'] . " nombre_insc='" . $row['nombre_inscripcion'] . "' nombre_persona='" . $row['nombre_persona'] . "'\n";
    }
}
