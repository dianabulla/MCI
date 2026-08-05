<?php
require __DIR__ . '/../conexion.php';

echo "=== DIAGNÓSTICO CÉLULAS ===\n\n";

$total = (int)$pdo->query('SELECT COUNT(*) FROM celula')->fetchColumn();
echo "Total células en BD: $total\n\n";

echo "Células SIN líder (Id_Lider NULL o 0):\n";
$q = "SELECT Id_Celula, Nombre_Celula, Id_Lider, Id_Anfitrion, Direccion_Celula, Es_Antiguo, Estado_Celula
      FROM celula WHERE Id_Lider IS NULL OR Id_Lider = 0 ORDER BY Id_Celula DESC LIMIT 15";
foreach ($pdo->query($q) as $r) {
    echo "  #{$r['Id_Celula']} | {$r['Nombre_Celula']} | Lid={$r['Id_Lider']} | Anf={$r['Id_Anfitrion']} | {$r['Estado_Celula']}\n";
}

echo "\nÚltimas 15 células por Id_Celula (más recientes creadas):\n";
$q2 = "SELECT c.Id_Celula, c.Nombre_Celula, c.Id_Lider, c.Id_Anfitrion, c.Fecha_Apertura,
       CONCAT(l.Nombre,' ',l.Apellido) AS Lider, CONCAT(a.Nombre,' ',a.Apellido) AS Anfitrion,
       (SELECT COUNT(*) FROM persona p WHERE p.Id_Celula=c.Id_Celula) AS Miembros
       FROM celula c
       LEFT JOIN persona l ON c.Id_Lider=l.Id_Persona
       LEFT JOIN persona a ON c.Id_Anfitrion=a.Id_Persona
       ORDER BY c.Id_Celula DESC LIMIT 15";
foreach ($pdo->query($q2) as $r) {
    echo "  #{$r['Id_Celula']} | {$r['Nombre_Celula']} | Lid:{$r['Lider']} | Anf:{$r['Anfitrion']} | {$r['Miembros']} miembros\n";
}

echo "\nPersonas con Id_Celula que NO existe en celula (huérfanas):\n";
$q3 = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Id_Celula
       FROM persona p
       LEFT JOIN celula c ON p.Id_Celula = c.Id_Celula
       WHERE p.Id_Celula IS NOT NULL AND p.Id_Celula > 0 AND c.Id_Celula IS NULL
       LIMIT 20";
$orphans = $pdo->query($q3)->fetchAll(PDO::FETCH_ASSOC);
echo '  Total huérfanas: '.count($orphans)."\n";
foreach ($orphans as $r) {
    echo "  Persona #{$r['Id_Persona']} {$r['Nombre']} -> Id_Celula={$r['Id_Celula']} (borrada)\n";
}

// Check columns for audit
$cols = $pdo->query("SHOW COLUMNS FROM celula")->fetchAll(PDO::FETCH_COLUMN);
echo "\nColumnas celula: ".implode(', ', $cols)."\n";
