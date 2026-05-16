<?php
/**
 * Script de prueba para crear evaluación de ejemplo y verificar que se guarde correctamente
 * Uso: Acceder a http://localhost/mcimadrid/tools/test_crear_evaluacion.php
 */

require_once dirname(__DIR__) . '/app/Config/Database.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_POST['accion'] === 'crear_test') {
    try {
        $db = Database::getInstance();
        
        // Datos de evaluación de prueba
        $titulo = 'EVALUACIÓN DE PRUEBA - ' . date('Y-m-d H:i:s');
        $nivel = 1;
        $modulo = 1;
        $leccion = 'Sin lección';
        $puntaje_minimo = 80.0;
        
        // Preguntas de prueba
        $preguntas = [
            [
                'tipo' => 'cerrada',
                'enunciado' => 'Pregunta de prueba 1',
                'opciones' => [
                    'a' => 'Opción A (correcta)',
                    'b' => 'Opción B',
                    'c' => 'Opción C',
                    'd' => 'Opción D'
                ],
                'respuesta_correcta' => 'a'
            ],
            [
                'tipo' => 'cerrada',
                'enunciado' => 'Pregunta de prueba 2',
                'opciones' => [
                    'a' => 'Opción A',
                    'b' => 'Opción B (correcta)',
                    'c' => 'Opción C',
                ],
                'respuesta_correcta' => 'b'
            ]
        ];
        
        $preguntas_json = json_encode($preguntas, JSON_UNESCAPED_UNICODE);
        
        // Insertar evaluación
        $stmt = $db->prepare("INSERT INTO discipular_evaluaciones 
            (Titulo, Descripcion, Nivel, Modulo_Numero, Leccion, Puntaje_Minimo, Preguntas_JSON, Activa, Creado_Por)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
        
        $stmt->execute([
            $titulo,
            'Esta es una evaluación de prueba',
            $nivel,
            $modulo,
            $leccion,
            $puntaje_minimo,
            $preguntas_json,
            1  // usuario admin
        ]);
        
        $id_eval = $db->lastInsertId();
        
        // Recuperar y verificar
        $stmt = $db->prepare("SELECT * FROM discipular_evaluaciones WHERE Id_Evaluacion = ?");
        $stmt->execute([$id_eval]);
        $eval = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $preguntas_recuperadas = json_decode($eval['Preguntas_JSON'], true);
        
        $mensaje = "✓ Evaluación creada exitosamente<br>";
        $mensaje .= "ID: <strong>$id_eval</strong><br>";
        $mensaje .= "Título: <strong>" . htmlspecialchars($eval['Titulo']) . "</strong><br>";
        $mensaje .= "Preguntas guardadas: " . count($preguntas_recuperadas) . "<br>";
        $mensaje .= "JSON válido: " . (json_last_error() === JSON_ERROR_NONE ? '✓ Sí' : '✗ No') . "<br>";
        $tipo_mensaje = 'success';
        
    } catch (Exception $e) {
        $mensaje = "✗ Error: " . htmlspecialchars($e->getMessage());
        $tipo_mensaje = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Guardado de Evaluaciones</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .alert { padding: 15px; border-radius: 5px; margin: 20px 0; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Prueba de Guardado de Evaluaciones</h1>
    
    <div class="card">
        <h2>Crear Evaluación de Prueba</h2>
        <p>Haz clic para crear una evaluación de prueba con 2 preguntas y verificar que se guarden correctamente.</p>
        
        <form method="POST">
            <input type="hidden" name="accion" value="crear_test">
            <button type="submit">Crear Evaluación de Prueba</button>
        </form>
        
        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?>">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Evaluaciones Recientes</h2>
        <?php
        try {
            $db = \Database::getInstance();
            $stmt = $db->query("SELECT * FROM discipular_evaluaciones ORDER BY Fecha_Creacion DESC LIMIT 10");
            $evaluaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($evaluaciones) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Título</th><th>Nivel</th><th>Módulo</th><th>Preguntas</th><th>Activa</th><th>Creada</th></tr>";
                
                foreach ($evaluaciones as $eval) {
                    $preguntas = json_decode($eval['Preguntas_JSON'], true);
                    $num_preguntas = is_array($preguntas) ? count($preguntas) : 0;
                    $activa = $eval['Activa'] ? '✓' : '✗';
                    
                    echo "<tr>";
                    echo "<td>" . $eval['Id_Evaluacion'] . "</td>";
                    echo "<td>" . htmlspecialchars($eval['Titulo']) . "</td>";
                    echo "<td>" . $eval['Nivel'] . "</td>";
                    echo "<td>" . $eval['Modulo_Numero'] . "</td>";
                    echo "<td>" . $num_preguntas . "</td>";
                    echo "<td>" . $activa . "</td>";
                    echo "<td>" . substr($eval['Fecha_Creacion'], 0, 16) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            } else {
                echo "<p>No hay evaluaciones aún.</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>
    
</body>
</html>
