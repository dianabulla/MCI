
<?php
require "conexion.php";

try {
    if (!isset($conn)) {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8";
        $options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
        $conn = new PDO($dsn, $username, $password, $options);
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$today = date("Y-m-d H:i:s");
echo "Fecha actual: $today\n";

try {
    $sql = "SELECT Id_Contenido, Tipo_Modulo, Titulo, Estado_Activo, Fecha_Publicacion_Desde, Fecha_Publicacion_Hasta, Fecha_Creacion, Imagen, Video 
            FROM evento_modulo_contenido";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $all_contents = $stmt->fetchAll();
    
    $modulos = [];
    foreach($all_contents as $row) {
        $modulos[$row["Tipo_Modulo"]][] = $row;
    }

    if (empty($modulos)) {
        echo "No se encontraron contenidos.\n";
    }

    foreach ($modulos as $nombre_modulo => $contents) {
        echo "\n--- MODULO: $nombre_modulo ---\n";
        printf("%-5s | %-25s | %-4s | %-19s | %-19s | %s\n", "ID", "Titulo", "Act", "Desde", "Hasta", "Vigente");
        echo str_repeat("-", 100) . "\n";

        $total = count($contents);
        $vigentes = 0;

        foreach ($contents as $row) {
            $is_active = ($row["Estado_Activo"] == 1);
            $is_in_range = true;
            
            if (!empty($row["Fecha_Publicacion_Desde"]) && $today < $row["Fecha_Publicacion_Desde"]) $is_in_range = false;
            if (!empty($row["Fecha_Publicacion_Hasta"]) && $today > $row["Fecha_Publicacion_Hasta"]) $is_in_range = false;
            
            $vigente = ($is_active && $is_in_range) ? "SI" : "NO";
            if ($vigente == "SI") $vigentes++;

            printf("%-5d | %-25.25s | %-4d | %-19s | %-19s | %s\n", 
                $row["Id_Contenido"], 
                $row["Titulo"], 
                $row["Estado_Activo"], 
                $row["Fecha_Publicacion_Desde"] ?? "N/A", 
                $row["Fecha_Publicacion_Hasta"] ?? "N/A",
                $vigente
            );
        }
        echo "Resumen $nombre_modulo: Total: $total | Vigentes: $vigentes\n";
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>