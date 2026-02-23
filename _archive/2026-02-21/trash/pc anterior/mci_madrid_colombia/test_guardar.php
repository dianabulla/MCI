<?php
// Verificar registros en la tabla
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';

echo "<h2>Registros en ninos_navidad</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "SELECT * FROM ninos_navidad ORDER BY Fecha_Registro DESC LIMIT 10";
    $stmt = $db->query($sql);
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($registros)) {
        echo "<p style='color: red; font-size: 20px; font-weight: bold;'>❌ NO HAY REGISTROS EN LA TABLA</p>";
        echo "<p>Esto significa que el formulario NO está guardando datos.</p>";
    } else {
        echo "<p style='color: green; font-size: 20px; font-weight: bold;'>✅ Total de registros: " . count($registros) . "</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #333; color: white;'><th>ID</th><th>Nombre</th><th>Fecha Nac</th><th>Edad</th><th>Acudiente</th><th>Tel</th><th>Barrio</th><th>Ministerio</th><th>Fecha Registro</th></tr>";
        foreach ($registros as $r) {
            echo "<tr>";
            echo "<td>{$r['Id_Registro']}</td>";
            echo "<td>{$r['Nombre_Apellidos']}</td>";
            echo "<td>{$r['Fecha_Nacimiento']}</td>";
            echo "<td>{$r['Edad']}</td>";
            echo "<td>{$r['Nombre_Acudiente']}</td>";
            echo "<td>{$r['Telefono_Acudiente']}</td>";
            echo "<td>{$r['Barrio']}</td>";
            echo "<td>{$r['Id_Ministerio']}</td>";
            echo "<td>{$r['Fecha_Registro']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-size: 18px;'>❌ ERROR: " . $e->getMessage() . "</p>";
}
?>
