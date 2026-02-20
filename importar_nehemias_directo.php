
<?php
/**
 * Script de importación directa desde CSV
 * Uso: Colocar el archivo CSV en la raíz como 'datos_nehemias.csv' y acceder a este script
 */

// Mostrar errores para diagnóstico (comentar en producción después de solucionar)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir archivo de conexión para obtener credenciales
require_once __DIR__ . '/conexion.php';

// Crear conexión MySQLi usando las variables de conexion.php
$conn = new mysqli($host, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("<div style='padding:20px;background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:8px;margin:20px;'>
        <h2>❌ Error de Conexión a la Base de Datos</h2>
        <p><strong>Error:</strong> " . $conn->connect_error . "</p>
        <p><strong>Host:</strong> $host</p>
        <p><strong>Base de datos:</strong> $dbname</p>
        <p><strong>Usuario:</strong> $username</p>
        </div>");
}

// Configurar charset
$conn->set_charset("utf8mb4");

// Configuración
set_time_limit(0); // Sin límite de tiempo
ini_set('memory_limit', '512M');

$archivo_csv = __DIR__ . '/datos_nehemias.csv';

if (!function_exists('mapearFilaNehemiasDirecto')) {
    function mapearFilaNehemiasDirecto(array $data): array {
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

            $soloNumeros = preg_replace('/\D+/', '', (string) $valor);
            $largo = strlen($soloNumeros);
            if ($soloNumeros !== '' && $largo >= 5 && $largo <= 12 && $esTexto($obtener($data, $i - 1)) && $esTexto($obtener($data, $i - 2))) {
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

        return [
            'nombres' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex - 2 : 0),
            'apellidos' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex - 1 : 1),
            'numero_cedula' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex : 2),
            'telefono' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex + 1 : 3),
            'lider_nehemias' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex + 2 : 4),
            'lider' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex + 3 : 5),
            'subido_link' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex + 4 : 6),
            'en_bogota_subio' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex + 5 : 7),
            'puesto_votacion' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex + 6 : 8),
            'mesa_votacion' => $obtener($data, $cedulaIndex !== null ? $cedulaIndex + 7 : 9)
        ];
    }
}

if (!function_exists('detectarDelimitadorArchivoDirecto')) {
    function detectarDelimitadorArchivoDirecto(string $rutaArchivo): string {
        $muestras = @file($rutaArchivo, FILE_IGNORE_NEW_LINES);
        if ($muestras === false || empty($muestras)) {
            return ',';
        }

        $candidatos = [',', ';', "\t"];
        $puntajes = [',' => 0, ';' => 0, "\t" => 0];

        $limite = min(count($muestras), 40);
        for ($i = 0; $i < $limite; $i++) {
            $linea = (string) $muestras[$i];
            if (trim($linea) === '') {
                continue;
            }
            foreach ($candidatos as $del) {
                $puntajes[$del] += substr_count($linea, $del);
            }
        }

        arsort($puntajes);
        $mejor = array_key_first($puntajes);
        return $mejor ?: ',';
    }
}

if (!function_exists('parsearLineaNehemiasDirecto')) {
    function parsearLineaNehemiasDirecto(string $linea, string $delimitador): array {
        $linea = preg_replace('/^\xEF\xBB\xBF/', '', $linea);
        $data = str_getcsv($linea, $delimitador);

        if (count($data) <= 1) {
            foreach ([";", ",", "\t"] as $delAlt) {
                if ($delAlt === $delimitador) {
                    continue;
                }
                $dataAlt = str_getcsv($linea, $delAlt);
                if (count($dataAlt) > count($data)) {
                    $data = $dataAlt;
                }
            }
        }

        return $data;
    }
}
?>
<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importación Directa Nehemías</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #0078D4; }
        .success { color: green; font-weight: bold; }
        .error { color: red; }
        .info { color: #0078D4; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; font-size: 14px; }
        th { background-color: #0078D4; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .progress { background: #e0e0e0; border-radius: 10px; height: 30px; margin: 20px 0; }
        .progress-bar { background: #f37021; height: 100%; border-radius: 10px; text-align: center; line-height: 30px; color: white; transition: width 0.3s; }
        .btn { background: #f37021; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; margin: 10px 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #d85f12; }
        .btn-secondary { background: #0078D4; }
        .btn-secondary:hover { background: #0b4aa2; }
        .log { background: #f5f5f5; padding: 10px; border-left: 4px solid #0078D4; margin: 10px 0; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🚀 Importación Directa de Datos Nehemías</h1>
<?php
if (!file_exists($archivo_csv)) {
    echo "<div class='error'>
        <h2>❌ Archivo no encontrado</h2>
        <p>Por favor, suba el archivo CSV con el nombre <strong>datos_nehemias.csv</strong> a la raíz del proyecto:</p>
        <p><code>{$archivo_csv}</code></p>
        <h3>Instrucciones:</h3>
        <ol>
            <li>Abra su archivo Excel</li>
            <li>Vaya a Archivo > Guardar como</li>
            <li>Seleccione el formato <strong>CSV UTF-8 (delimitado por comas)</strong></li>
            <li>Guarde con el nombre <strong>datos_nehemias.csv</strong></li>
            <li>Suba el archivo a: <code>" . __DIR__ . "</code></li>
            <li>Recargue esta página</li>
        </ol>
    </div>";
} else {
    echo "<div class='info'>
        <p>📁 Archivo encontrado: <strong>datos_nehemias.csv</strong></p>
        <p>📊 Tamaño: " . number_format(filesize($archivo_csv) / 1024, 2) . " KB</p>
    </div>";
    
    if (isset($_POST['importar'])) {
        echo "<h2>⚙️ Procesando importación...</h2>";
        echo "<div class='log' id='log'>";
        
        flush();
        ob_flush();
        
        $inicio = microtime(true);
        $errores = [];
        $exitosos = 0;
        $linea = 0;
        $total_lineas = 0;
        
        // Contar líneas totales
        $handle_count = fopen($archivo_csv, "r");
        while (fgets($handle_count) !== false) {
            $total_lineas++;
        }
        fclose($handle_count);
        $total_lineas--; // Restar encabezado
        
        echo "<p>Total de registros a procesar: <strong>$total_lineas</strong></p>";
        flush();
        ob_flush();
        
        // Procesar archivo
        if (($handle = fopen($archivo_csv, "r")) !== FALSE) {
            // Detectar delimitador de forma robusta
            $delimitador = detectarDelimitadorArchivoDirecto($archivo_csv);
            
            echo "<p>Delimitador detectado: <strong>" . ($delimitador === "\t" ? "tabulación" : ($delimitador === ";" ? "punto y coma" : "coma")) . "</strong></p>";
            
            // Leer encabezados (primera línea física)
            $encabezadoRaw = fgets($handle);
            $encabezados = $encabezadoRaw !== false ? parsearLineaNehemiasDirecto($encabezadoRaw, $delimitador) : [];
            
            // Preparar statement
            $sql = "INSERT INTO nehemias (Nombres, Apellidos, Numero_Cedula, Telefono, Lider_Nehemias, Lider, Subido_Link, En_Bogota_Subio, Puesto_Votacion, Mesa_Votacion, Fecha_Registro) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                die("Error al preparar consulta: " . $conn->error);
            }
            
            while (($lineaRaw = fgets($handle)) !== false) {
                $linea++;
                if (trim($lineaRaw) === '') {
                    continue;
                }

                $data = parsearLineaNehemiasDirecto($lineaRaw, $delimitador);
                
                if ($linea % 100 == 0) {
                    $porcentaje = round(($linea / $total_lineas) * 100, 1);
                    echo "<script>document.getElementById('progress-bar').style.width='{$porcentaje}%'; document.getElementById('progress-bar').innerText='{$porcentaje}%';</script>";
                    flush();
                    ob_flush();
                }
                
                // Extraer datos (soporta filas corridas por columnas extra)
                $fila = mapearFilaNehemiasDirecto($data);
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
                
                // Insertar
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
                    $errores[] = "Línea $linea: " . $stmt->error;
                }
            }
            
            fclose($handle);
            if ($stmt) {
                $stmt->close();
            }
        }
        
        $tiempo_total = round(microtime(true) - $inicio, 2);
        
        echo "</div>";
        
        echo "<div class='progress'>
            <div class='progress-bar' id='progress-bar' style='width: 100%;'>100%</div>
        </div>";
        
        echo "<h2>✅ Importación Completada</h2>";
        echo "<table>
            <tr><th>Concepto</th><th>Cantidad</th></tr>
            <tr><td>Total de líneas procesadas</td><td class='info'>" . number_format($linea) . "</td></tr>
            <tr><td>Registros importados exitosamente</td><td class='success'>" . number_format($exitosos) . "</td></tr>
            <tr><td>Errores</td><td class='error'>" . number_format(count($errores)) . "</td></tr>
            <tr><td>Tiempo total</td><td>{$tiempo_total} segundos</td></tr>
            <tr><td>Velocidad</td><td>" . round($linea / $tiempo_total, 2) . " registros/segundo</td></tr>
        </table>";

        if ($linea < $total_lineas) {
            echo "<p class='info'>Nota: se detectaron " . number_format($total_lineas - $linea) . " líneas físicas vacías o no procesables.</p>";
        }
        
        if (!empty($errores) && count($errores) <= 50) {
            echo "<h3>Errores encontrados:</h3><ul>";
            foreach ($errores as $error) {
                echo "<li class='error'>$error</li>";
            }
            echo "</ul>";
        } elseif (count($errores) > 50) {
            echo "<p class='error'>Se encontraron " . count($errores) . " errores. Solo se muestran los primeros 50:</p><ul>";
            foreach (array_slice($errores, 0, 50) as $error) {
                echo "<li class='error'>$error</li>";
            }
            echo "</ul>";
        }
        
        echo "<p><a href='?url=nehemias/lista' class='btn btn-secondary'>Ver Lista de Nehemías</a></p>";
        echo "<p><a href='importar_nehemias_directo.php' class='btn'>Nueva Importación</a></p>";
        
    } else {
        echo "<div class='progress'>
            <div class='progress-bar' id='progress-bar' style='width: 0%;'>0%</div>
        </div>";
        
        echo "<form method='POST'>
            <p><strong>¿Está seguro de que desea importar los datos?</strong></p>
            <p>Esta operación puede tardar varios minutos dependiendo del tamaño del archivo.</p>
            <button type='submit' name='importar' class='btn'>✅ Iniciar Importación</button>
            <a href='?url=nehemias/lista' class='btn btn-secondary'>Cancelar</a>
        </form>";
    }
}

echo "</div>
</body>
</html>";

$conn->close();
?>
