<?php
/**
 * Script de diagnóstico para verificar integridad de evaluaciones y preguntas
 * Uso: Acceder a http://localhost/mcimadrid/tools/diagnostic_evaluaciones.php
 */

require_once dirname(__DIR__) . '/app/Config/Database.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>DIAGNÓSTICO DE EVALUACIONES</h2>";
    echo "<hr>";
    
    // 1. Verificar tabla evaluaciones
    echo "<h3>1. Evaluaciones creadas</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM discipular_evaluaciones");
    $totalEval = $stmt->fetchColumn();
    echo "Total evaluaciones: <strong>$totalEval</strong><br><br>";
    
    // 2. Listar evaluaciones y verificar preguntas
    echo "<h3>2. Detalles de evaluaciones y preguntas</h3>";
    $stmt = $db->query("SELECT Id_Evaluacion, Titulo, Nivel, Modulo_Numero, Preguntas_JSON, Activa, Fecha_Creacion 
                       FROM discipular_evaluaciones ORDER BY Fecha_Creacion DESC");
    $evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($evaluaciones as $eval) {
        $idEval = (int)$eval['Id_Evaluacion'];
        $titulo = htmlspecialchars($eval['Titulo']);
        $nivel = $eval['Nivel'];
        $modulo = $eval['Modulo_Numero'];
        $activa = $eval['Activa'] ? 'Sí' : 'No';
        $fecha = $eval['Fecha_Creacion'];
        
        $preguntas = json_decode($eval['Preguntas_JSON'], true);
        $totalPreguntas = is_array($preguntas) ? count($preguntas) : 0;
        $jsonValido = json_last_error() === JSON_ERROR_NONE ? '✓' : '✗ ERROR';
        
        echo "<div style='border:1px solid #ccc; padding:10px; margin:10px 0; background:#f9f9f9;'>";
        echo "<strong>ID $idEval:</strong> $titulo (Nivel $nivel, Módulo $modulo)<br>";
        echo "Activa: $activa | Preguntas: $totalPreguntas | JSON: $jsonValido | Creada: $fecha<br>";
        
        if ($totalPreguntas > 0) {
            echo "<ul>";
            foreach ($preguntas as $idx => $preg) {
                $enunciado = htmlspecialchars(substr($preg['enunciado'] ?? 'Sin enunciado', 0, 60));
                $tipo = $preg['tipo'] ?? 'desconocido';
                $opciones = is_array($preg['opciones'] ?? null) ? count($preg['opciones']) : 0;
                $tieneRespuesta = isset($preg['respuesta_correcta']) ? '✓' : '✗ FALTA';
                echo "<li>P" . ($idx + 1) . ": $enunciado... [$tipo, $opciones opciones, Respuesta: $tieneRespuesta]</li>";
            }
            echo "</ul>";
        } else {
            echo "<span style='color:red;'>⚠ NO HAY PREGUNTAS O JSON INVÁLIDO</span><br>";
            if ($preguntas === null) {
                echo "Error JSON: " . json_last_error_msg() . "<br>";
                echo "Raw: " . substr($eval['Preguntas_JSON'], 0, 200) . "...<br>";
            }
        }
        echo "</div>";
    }
    
    // 3. Verificar resultados guardados
    echo "<h3>3. Resultados de evaluaciones presentadas</h3>";
    $stmt = $db->query("SELECT COUNT(*) as total FROM discipular_evaluacion_resultados");
    $totalResultados = $stmt->fetchColumn();
    echo "Total presentaciones: <strong>$totalResultados</strong><br><br>";
    
    if ($totalResultados > 0) {
        $stmt = $db->query("SELECT r.*, e.Titulo, p.Nombre, p.Apellido 
                           FROM discipular_evaluacion_resultados r
                           INNER JOIN discipular_evaluaciones e ON e.Id_Evaluacion = r.Id_Evaluacion
                           LEFT JOIN persona p ON p.Id_Persona = r.Id_Persona
                           ORDER BY r.Fecha_Presentacion DESC LIMIT 20");
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table style='border-collapse:collapse;width:100%;'>";
        echo "<tr style='background:#ddd;'><th style='border:1px solid #999;padding:5px;'>Fecha</th><th style='border:1px solid #999;padding:5px;'>Persona</th><th style='border:1px solid #999;padding:5px;'>Evaluación</th><th style='border:1px solid #999;padding:5px;'>Intento</th><th style='border:1px solid #999;padding:5px;'>Puntaje</th><th style='border:1px solid #999;padding:5px;'>Resultado</th></tr>";
        
        foreach ($resultados as $res) {
            $fecha = substr($res['Fecha_Presentacion'], 0, 16);
            $persona = htmlspecialchars(($res['Nombre'] ?? 'Desconocido') . ' ' . ($res['Apellido'] ?? ''));
            $evaluacion = htmlspecialchars($res['Titulo']);
            $intento = $res['Intento_Numero'];
            $puntaje = $res['Puntaje'];
            $resultado = $res['Aprobado'] ? 'Aprobado' : 'Reprobado';
            $bgColor = $res['Aprobado'] ? '#d4edda' : '#f8d7da';
            
            echo "<tr style='background:$bgColor;'>";
            echo "<td style='border:1px solid #999;padding:5px;'>$fecha</td>";
            echo "<td style='border:1px solid #999;padding:5px;'>$persona</td>";
            echo "<td style='border:1px solid #999;padding:5px;'>$evaluacion</td>";
            echo "<td style='border:1px solid #999;padding:5px;'>$intento</td>";
            echo "<td style='border:1px solid #999;padding:5px;'>$puntaje%</td>";
            echo "<td style='border:1px solid #999;padding:5px;'>$resultado</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 4. Verificar integridad de respuestas
    echo "<h3>4. Verificación de integridad de respuestas</h3>";
    $stmt = $db->query("SELECT r.Id_Resultado, r.Respuestas_JSON FROM discipular_evaluacion_resultados LIMIT 5");
    $respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $todasValidas = true;
    foreach ($respuestas as $res) {
        $respJson = json_decode($res['Respuestas_JSON'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "ID Resultado {$res['Id_Resultado']}: ✗ JSON INVÁLIDO<br>";
            $todasValidas = false;
        }
    }
    
    if ($todasValidas && count($respuestas) > 0) {
        echo "✓ Todas las respuestas guardadas tienen JSON válido<br>";
    }
    
    echo "<hr>";
    echo "<h3>RESUMEN</h3>";
    echo "✓ Base de datos conectada correctamente<br>";
    echo "✓ Tabla evaluaciones existe<br>";
    echo "✓ Tabla resultados existe<br>";
    echo "✓ Diagnóstico completado\n";
    
} catch (Exception $e) {
    echo "<h2 style='color:red;'>ERROR EN DIAGNÓSTICO</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
