<?php
/**
 * Script de verificación del sistema de registro de obsequios
 * Verifica que la tabla exista y que el guardado funcione
 */

require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';
require_once 'app/Models/BaseModel.php';
require_once 'app/Models/NinoNavidad.php';
require_once 'app/Models/Ministerio.php';

echo "<h2>Verificación del Sistema de Registro de Obsequios</h2>";
echo "<hr>";

// 1. Verificar conexión a base de datos
echo "<h3>1. Verificando conexión a base de datos...</h3>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Conexión exitosa<br>";
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
    die();
}

// 2. Verificar que la tabla existe
echo "<h3>2. Verificando tabla ninos_navidad...</h3>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'ninos_navidad'");
    $tableExists = $stmt->rowCount() > 0;
    
    if ($tableExists) {
        echo "✅ La tabla 'ninos_navidad' existe<br>";
        
        // Mostrar estructura
        $stmt = $db->query("DESCRIBE ninos_navidad");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "❌ La tabla 'ninos_navidad' NO existe<br>";
        echo "<strong>ACCIÓN REQUERIDA:</strong> Debes ejecutar el archivo 'tabla_ninos_navidad.sql' en tu base de datos<br>";
    }
} catch (Exception $e) {
    echo "❌ Error al verificar tabla: " . $e->getMessage() . "<br>";
}

// 3. Verificar modelo
echo "<h3>3. Verificando modelo NinoNavidad...</h3>";
try {
    $ninoModel = new NinoNavidad();
    echo "✅ Modelo instanciado correctamente<br>";
    
    // Probar método calcularEdad
    $edad = $ninoModel->calcularEdad('2015-01-01');
    echo "✅ Método calcularEdad() funciona: Edad para fecha 2015-01-01 = {$edad} años<br>";
    
    // Probar validación
    $valido = $ninoModel->validarEdad('2015-01-01');
    echo "✅ Método validarEdad() funciona: Edad válida (<=10) = " . ($valido ? 'SÍ' : 'NO') . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error en modelo: " . $e->getMessage() . "<br>";
}

// 4. Verificar ministerios
echo "<h3>4. Verificando ministerios disponibles...</h3>";
try {
    $ministerioModel = new Ministerio();
    $ministerios = $ministerioModel->getAll();
    
    if (!empty($ministerios)) {
        echo "✅ Se encontraron " . count($ministerios) . " ministerios:<br>";
        echo "<ul>";
        foreach ($ministerios as $m) {
            echo "<li>ID: {$m['Id_Ministerio']} - {$m['Nombre_Ministerio']}</li>";
        }
        echo "</ul>";
    } else {
        echo "⚠️ No hay ministerios registrados<br>";
    }
} catch (Exception $e) {
    echo "❌ Error al obtener ministerios: " . $e->getMessage() . "<br>";
}

// 5. Probar registro (solo si la tabla existe)
if ($tableExists) {
    echo "<h3>5. Probando registro de prueba...</h3>";
    try {
        $dataPrueba = [
            'Nombre_Apellidos' => 'Test Niño Prueba',
            'Fecha_Nacimiento' => '2018-06-15',
            'Nombre_Acudiente' => 'Test Acudiente Prueba',
            'Telefono_Acudiente' => '3001234567',
            'Barrio' => 'Test Barrio',
            'Id_Ministerio' => !empty($ministerios) ? $ministerios[0]['Id_Ministerio'] : 1
        ];
        
        $resultado = $ninoModel->registrarNino($dataPrueba);
        
        if ($resultado['success']) {
            echo "✅ " . $resultado['message'] . "<br>";
            echo "ID del registro: " . $resultado['id'] . "<br>";
            
            // Verificar que se guardó
            $stmt = $db->prepare("SELECT * FROM ninos_navidad WHERE Id_Registro = ?");
            $stmt->execute([$resultado['id']]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registro) {
                echo "✅ Registro verificado en base de datos:<br>";
                echo "<pre>" . print_r($registro, true) . "</pre>";
                
                // Eliminar registro de prueba
                $db->exec("DELETE FROM ninos_navidad WHERE Id_Registro = {$resultado['id']}");
                echo "✅ Registro de prueba eliminado<br>";
            }
        } else {
            echo "❌ " . $resultado['message'] . "<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ Error al probar registro: " . $e->getMessage() . "<br>";
    }
}

// 6. Verificar rutas públicas
echo "<h3>6. Verificando configuración de rutas públicas...</h3>";
$indexContent = file_get_contents('public/index.php');
if (strpos($indexContent, "'registro_obsequio'") !== false) {
    echo "✅ Ruta 'registro_obsequio' configurada como pública<br>";
} else {
    echo "❌ Ruta 'registro_obsequio' NO está en rutas públicas<br>";
}

echo "<hr>";
echo "<h3>Resumen:</h3>";
if ($tableExists) {
    echo "✅ El sistema está listo para funcionar<br>";
    echo "<strong>URL para acceder:</strong> <a href='public/?url=registro_obsequio' target='_blank'>public/?url=registro_obsequio</a><br>";
} else {
    echo "❌ Debes ejecutar el archivo 'tabla_ninos_navidad.sql' antes de usar el sistema<br>";
}
?>
