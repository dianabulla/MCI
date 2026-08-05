<?php
require __DIR__ . '/../conexion.php';

$idLider = 9173;
$doc = '4192117';

echo "=== CÉLULAS DEL LÍDER Id_Persona = $idLider ===\n\n";

$p = $pdo->prepare("SELECT Id_Persona, Nombre, Apellido, Numero_Documento, Tipo_Documento, Telefono, Id_Ministerio, Id_Celula
    FROM persona WHERE Id_Persona = ? OR Numero_Documento = ?");
$p->execute([$idLider, $doc]);
$persona = $p->fetch(PDO::FETCH_ASSOC);
if ($persona) {
    echo "Persona encontrada:\n";
    echo "  Id: {$persona['Id_Persona']}\n";
    echo "  Nombre: {$persona['Nombre']} {$persona['Apellido']}\n";
    echo "  Doc: {$persona['Tipo_Documento']} {$persona['Numero_Documento']}\n";
    echo "  Id_Celula (como miembro): " . ($persona['Id_Celula'] ?? 'NULL') . "\n\n";
    $idLider = (int)$persona['Id_Persona'];
} else {
    echo "No se encontró persona con Id $idLider ni doc $doc\n\n";
}

$sql = "SELECT c.Id_Celula, c.Nombre_Celula, c.Direccion_Celula, c.Dia_Reunion, c.Hora_Reunion,
        c.Id_Lider, c.Id_Anfitrion, c.Id_Lider_Inmediato, c.Estado_Celula, c.Es_Antiguo, c.Fecha_Apertura,
        CONCAT(a.Nombre, ' ', a.Apellido) AS Nombre_Anfitrion,
        (SELECT COUNT(*) FROM persona m WHERE m.Id_Celula = c.Id_Celula AND (m.Estado_Cuenta='Activo' OR m.Estado_Cuenta IS NULL)) AS Miembros_Activos
    FROM celula c
    LEFT JOIN persona a ON c.Id_Anfitrion = a.Id_Persona
    WHERE c.Id_Lider = ?
    ORDER BY c.Nombre_Celula";
$stmt = $pdo->prepare($sql);
$stmt->execute([$idLider]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Células donde Id_Lider = $idLider: " . count($rows) . "\n\n";
foreach ($rows as $r) {
    echo "---\n";
    echo "  Id_Celula: {$r['Id_Celula']}\n";
    echo "  Nombre: {$r['Nombre_Celula']}\n";
    echo "  Dirección: {$r['Direccion_Celula']}\n";
    echo "  Día/Hora: {$r['Dia_Reunion']} {$r['Hora_Reunion']}\n";
    echo "  Anfitrión: " . ($r['Nombre_Anfitrion'] ?: '(sin anfitrión)') . " [Id={$r['Id_Anfitrion']}]\n";
    echo "  Estado: " . ($r['Estado_Celula'] ?? 'N/A') . " | Es_Antiguo: {$r['Es_Antiguo']}\n";
    echo "  Miembros activos: {$r['Miembros_Activos']}\n";
}

echo "\n=== Células SIN líder pero con miembros de este líder (si Id_Lider se perdió) ===\n";
$sql2 = "SELECT DISTINCT c.Id_Celula, c.Nombre_Celula, c.Id_Lider,
         (SELECT COUNT(*) FROM persona m WHERE m.Id_Celula = c.Id_Celula) AS Total_Miembros
         FROM celula c
         INNER JOIN persona m ON m.Id_Celula = c.Id_Celula
         WHERE (c.Id_Lider IS NULL OR c.Id_Lider = 0 OR c.Id_Lider <> ?)
         AND m.Id_Lider = ?
         ORDER BY c.Id_Celula DESC LIMIT 20";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute([$idLider, $idLider]);
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "Encontradas: " . count($rows2) . "\n";
foreach ($rows2 as $r) {
    echo "  #{$r['Id_Celula']} {$r['Nombre_Celula']} | Id_Lider actual={$r['Id_Lider']} | {$r['Total_Miembros']} miembros\n";
}

echo "\n=== Células con nombre que contiene 'FABIAN' o 'VILLA' (por si cambió el nombre) ===\n";
$q3 = $pdo->query("SELECT Id_Celula, Nombre_Celula, Id_Lider, Id_Anfitrion FROM celula
    WHERE Nombre_Celula LIKE '%FABIAN%' OR Nombre_Celula LIKE '%FABIÁN%'
       OR Nombre_Celula LIKE '%VILLA%' OR Nombre_Celula LIKE '%SOGAMOSO%'
    ORDER BY Id_Celula DESC LIMIT 20");
foreach ($q3 as $r) {
    echo "  #{$r['Id_Celula']} | {$r['Nombre_Celula']} | Lid={$r['Id_Lider']} | Anf={$r['Id_Anfitrion']}\n";
}
