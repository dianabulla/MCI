<?php
/**
 * Script para importar datos de Excel a la tabla nehemias
 * Uso: Subir este archivo a la raíz del proyecto y el archivo Excel
 */

require_once 'conexion.php';

// Configuración
set_time_limit(300); // 5 minutos máximo
ini_set('memory_limit', '256M');

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <title>Importar Datos Nehemías</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #0078D4; color: white; }
        .btn { background: #f37021; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #d85f12; }
    </style>
</head>
<body>
<div class='container'>
    <h1>Importar Datos Nehemías desde Excel/CSV</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo']['tmp_name'];
    $extension = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
    
    echo "<h2>Procesando archivo...</h2>";
    
    $errores = [];
    $exitosos = 0;
    $duplicados = 0;
    $linea = 0;
    
    try {
        if ($extension === 'csv') {
            // Procesar CSV
            if (($handle = fopen($archivo, "r")) !== FALSE) {
                // Saltar la primera línea (encabezados)
                $encabezados = fgetcsv($handle, 1000, "\t");
                
                while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                    $linea++;
                    
                    // Extraer datos de cada columna
                    $nombres = isset($data[0]) ? trim($data[0]) : '';
                    $apellidos = isset($data[1]) ? trim($data[1]) : '';
                    $numero_cedula = isset($data[2]) ? trim($data[2]) : '';
                    $telefono = isset($data[3]) ? trim($data[3]) : '';
                    $lider_nehemias = isset($data[4]) ? trim($data[4]) : '';
                    $lider = isset($data[5]) ? trim($data[5]) : '';
                    $subido_link = isset($data[6]) ? trim($data[6]) : '';
                    $en_bogota_subio = isset($data[7]) ? trim($data[7]) : '';
                    $puesto_votacion = isset($data[8]) ? trim($data[8]) : '';
                    $mesa_votacion = isset($data[9]) ? trim($data[9]) : '';
                    
                    // Validar datos mínimos
                    if (empty($nombres) || empty($apellidos) || empty($numero_cedula)) {
                        $errores[] = "Línea $linea: Faltan datos obligatorios (Nombres, Apellidos o Cédula)";
                        continue;
                    }
                    
                    // Verificar si ya existe
                    $stmt_check = $conn->prepare("SELECT Id FROM nehemias WHERE Numero_Cedula = ?");
                    $stmt_check->bind_param("s", $numero_cedula);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows > 0) {
                        $duplicados++;
                        $stmt_check->close();
                        continue;
                    }
                    $stmt_check->close();
                    
                    // Insertar registro
                    $sql = "INSERT INTO nehemias (Nombres, Apellidos, Numero_Cedula, Telefono, Lider_Nehemias, Lider, Subido_Link, En_Bogota_Subio, Puesto_Votacion, Mesa_Votacion, Fecha_Registro) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssssss", 
                        $nombres, 
                        $apellidos, 
                        $numero_cedula, 
                        $telefono, 
                        $lider_nehemias, 
                        $lider, 
                        $subido_link, 
                        $en_bogota_subio, 
                        $puesto_votacion, 
                        $mesa_votacion
                    );
                    
                    if ($stmt->execute()) {
                        $exitosos++;
                    } else {
                        $errores[] = "Línea $linea: Error al insertar - " . $stmt->error;
                    }
                    
                    $stmt->close();
                }
                
                fclose($handle);
            }
        } else {
            echo "<p class='error'>Formato de archivo no soportado. Use CSV con separador de tabulación.</p>";
        }
        
        // Mostrar resumen
        echo "<h2>Resumen de Importación</h2>";
        echo "<table>";
        echo "<tr><th>Concepto</th><th>Cantidad</th></tr>";
        echo "<tr><td>Registros procesados</td><td>$linea</td></tr>";
        echo "<tr><td class='success'>Registros importados exitosamente</td><td>$exitosos</td></tr>";
        echo "<tr><td class='info'>Registros duplicados (omitidos)</td><td>$duplicados</td></tr>";
        echo "<tr><td class='error'>Errores</td><td>" . count($errores) . "</td></tr>";
        echo "</table>";
        
        if (!empty($errores)) {
            echo "<h3>Detalle de Errores:</h3>";
            echo "<ul>";
            foreach (array_slice($errores, 0, 20) as $error) {
                echo "<li class='error'>$error</li>";
            }
            if (count($errores) > 20) {
                echo "<li class='info'>... y " . (count($errores) - 20) . " errores más</li>";
            }
            echo "</ul>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    // Formulario de carga
    echo "
    <p>Este script le permite importar datos desde un archivo CSV (separado por tabulaciones) a la tabla nehemias.</p>
    <p><strong>Instrucciones:</strong></p>
    <ol>
        <li>Prepare su archivo Excel con las siguientes columnas en este orden:
            <ul>
                <li>NOMBRES</li>
                <li>APELLIDOS</li>
                <li>NUMERO DE CEDULA</li>
                <li>TELEFONO</li>
                <li>LIDER NEHEMIAS</li>
                <li>LIDER</li>
                <li>Subido link de nehemias</li>
                <li>EN BOGOTA SE LE SUBIO</li>
                <li>PUESTO DE VOTACION</li>
                <li>MESA DE VOTACIÓN</li>
            </ul>
        </li>
        <li>Guarde el archivo como CSV (separado por tabulaciones)</li>
        <li>Seleccione el archivo y haga clic en Importar</li>
    </ol>
    
    <form method='POST' enctype='multipart/form-data'>
        <p>
            <label for='archivo'><strong>Seleccionar archivo CSV:</strong></label><br>
            <input type='file' name='archivo' id='archivo' accept='.csv,.txt' required>
        </p>
        <p>
            <button type='submit' class='btn'>Importar Datos</button>
        </p>
    </form>
    ";
}

echo "
    <hr>
    <p><a href='?url=nehemias/lista'>Volver a la lista de Nehemías</a></p>
</div>
</body>
</html>";

$conn->close();
?>
