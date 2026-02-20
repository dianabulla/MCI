<?php
/**
 * Script para importar datos de Excel a la tabla nehemias
 * Uso: Subir este archivo a la raíz del proyecto y el archivo Excel
 */

require_once __DIR__ . '/conexion.php';

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

if (!function_exists('mapearFilaNehemias')) {
    function mapearFilaNehemias(array $data): array {
        $limpiar = static function ($value): string {
            return trim((string) $value);
        };

        $obtener = static function (array $arr, int $idx) use ($limpiar): string {
            return isset($arr[$idx]) ? $limpiar($arr[$idx]) : '';
        };

        $esTexto = static function (string $valor): bool {
            return preg_match('/[A-Za-zÁÉÍÓÚÑáéíóúñ]/u', $valor) === 1;
        };

        $cedulaIndex = null;
        foreach ($data as $i => $valor) {
            if ($i < 2) {
                continue;
            }

            $v = preg_replace('/\D+/', '', (string) $valor);
            $largo = strlen($v);
            if ($v !== '' && $largo >= 5 && $largo <= 12 && $esTexto($obtener($data, $i - 1)) && $esTexto($obtener($data, $i - 2))) {
                $cedulaIndex = $i;
                break;
            }
        }

        if ($cedulaIndex === null && count($data) >= 12) {
            $primero = $obtener($data, 0);
            $segundo = strtolower($obtener($data, 1));
            $esFechaInicial = preg_match('/^\s*\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $primero) === 1;
            $esAcepta = in_array($segundo, ['aceptar', 'acepta', 'si', 'sí', 'no', '0', '1'], true);
            if ($esFechaInicial || $esAcepta) {
                $cedulaIndex = 4;
            }
        }

        if ($cedulaIndex === null && count($data) >= 10) {
            $cedulaIndex = 2;
        }

        if ($cedulaIndex !== null && $cedulaIndex >= 2) {
            return [
                'nombres' => $obtener($data, $cedulaIndex - 2),
                'apellidos' => $obtener($data, $cedulaIndex - 1),
                'numero_cedula' => $obtener($data, $cedulaIndex),
                'telefono' => $obtener($data, $cedulaIndex + 1),
                'lider_nehemias' => $obtener($data, $cedulaIndex + 2),
                'lider' => $obtener($data, $cedulaIndex + 3),
                'subido_link' => $obtener($data, $cedulaIndex + 4),
                'en_bogota_subio' => $obtener($data, $cedulaIndex + 5),
                'puesto_votacion' => $obtener($data, $cedulaIndex + 6),
                'mesa_votacion' => $obtener($data, $cedulaIndex + 7)
            ];
        }

        return [
            'nombres' => $obtener($data, 0),
            'apellidos' => $obtener($data, 1),
            'numero_cedula' => $obtener($data, 2),
            'telefono' => $obtener($data, 3),
            'lider_nehemias' => $obtener($data, 4),
            'lider' => $obtener($data, 5),
            'subido_link' => $obtener($data, 6),
            'en_bogota_subio' => $obtener($data, 7),
            'puesto_votacion' => $obtener($data, 8),
            'mesa_votacion' => $obtener($data, 9)
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $archivo = $_FILES['archivo']['tmp_name'];
    $extension = pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION);
    
    echo "<h2>Procesando archivo...</h2>";
    
    $errores = [];
    $exitosos = 0;
    $linea = 0;
    
    try {
        if ($extension === 'csv') {
            // Procesar CSV
            if (($handle = fopen($archivo, "r")) !== FALSE) {
                // Detectar delimitador real del archivo
                $primeraLinea = fgets($handle);
                rewind($handle);

                $delimitador = ',';
                if (substr_count($primeraLinea, "\t") > substr_count($primeraLinea, ",") && substr_count($primeraLinea, "\t") > substr_count($primeraLinea, ";")) {
                    $delimitador = "\t";
                } elseif (substr_count($primeraLinea, ";") > substr_count($primeraLinea, ",")) {
                    $delimitador = ";";
                }

                // Saltar encabezados y limpiar BOM en la primera celda
                $encabezados = fgetcsv($handle, 10000, $delimitador);
                if (is_array($encabezados) && isset($encabezados[0])) {
                    $encabezados[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $encabezados[0]);
                }
                
                while (($data = fgetcsv($handle, 10000, $delimitador)) !== FALSE) {
                    $linea++;

                    // Si la línea se leyó como una sola columna, intentar delimitadores alternos
                    if (count($data) <= 1 && isset($data[0])) {
                        $lineaRaw = $data[0];
                        foreach ([";", ",", "\t"] as $delAlt) {
                            if ($delAlt === $delimitador) {
                                continue;
                            }
                            $dataAlt = str_getcsv($lineaRaw, $delAlt);
                            if (count($dataAlt) > count($data)) {
                                $data = $dataAlt;
                            }
                        }
                    }
                    
                    // Extraer datos (soporta filas desplazadas por columnas extra)
                    $fila = mapearFilaNehemias($data);
                    $nombres = $fila['nombres'];
                    $apellidos = $fila['apellidos'];
                    $numero_cedula = $fila['numero_cedula'];
                    $telefono = $fila['telefono'];
                    $lider_nehemias = $fila['lider_nehemias'];
                    $lider = $fila['lider'];
                    $subido_link = $fila['subido_link'];
                    $en_bogota_subio = $fila['en_bogota_subio'];
                    $puesto_votacion = $fila['puesto_votacion'];
                    $mesa_votacion = $fila['mesa_votacion'];
                    
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
