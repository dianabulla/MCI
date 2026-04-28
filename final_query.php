
<?php
require "conexion.php";
try {
    if (!isset($conn)) {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8";
        $conn = new PDO($dsn, $username, $password);
    }
    $stmt = $conn->query("SELECT * FROM evento_modulo_contenido");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $today = date("Y-m-d H:i:s");
    
    $summary = [];
    echo "DETALLE DE CONTENIDOS:\n";
    foreach ($rows as $r) {
        $mod = $r["Tipo_Modulo"];
        if (!isset($summary[$mod])) $summary[$mod] = ["total" => 0, "vigentes" => 0];
        $summary[$mod]["total"]++;
        
        $vigente = ($r["Estado_Activo"] == 1);
        if ($r["Fecha_Publicacion_Desde"] && $today < $r["Fecha_Publicacion_Desde"]) $vigente = false;
        if ($r["Fecha_Publicacion_Hasta"] && $today > $r["Fecha_Publicacion_Hasta"]) $vigente = false;
        
        if ($vigente) $summary[$mod]["vigentes"]++;
        
        printf("[%s] ID: %d | Titulo: %s | Activo: %d | Vigente: %s\n", 
            $mod, $r["Id_Contenido"], substr($r["Titulo"], 0, 20), $r["Estado_Activo"], $vigente ? "SI" : "NO");
    }
    
    echo "\nRESUMEN POR MODULO:\n";
    foreach ($summary as $mod => $stats) {
        echo "Modulo: $mod | Total: {$stats["total"]} | Vigentes: {$stats["vigentes"]}\n";
    }
} catch (Exception $e) { echo $e->getMessage(); }
?>