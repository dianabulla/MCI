<?php
/**
 * Script Web: Marcar Ganar completo para personas antiguas asignadas
 * 
 * Acceso: [producción]/tools/marcar_ganar_completo_web.php
 * 
 * Actualiza el Escalera_Checklist de todas las personas que ya tienen:
 * - Id_Lider asignado
 * - Id_Ministerio asignado  
 * - Id_Celula asignada
 * 
 * Marca como completados automáticamente:
 * - Ganar[0] = "Asignado a líder" ✓
 * - Ganar[1] = "Primer contacto" ✓
 * - Ganar[2] = "Ubicado en célula" ✓
 */

session_start();

require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';

// Seguridad: Solo permitir acceso autenticado o con token específico
$tokenValido = false;
$usuarioAutenticado = false;

// Verificar si hay token en GET
$tokenEsperado = hash('sha256', md5('marcar_ganar_completo_' . date('Y-m-d')));
if (!empty($_GET['token']) && $_GET['token'] === $tokenEsperado) {
    $tokenValido = true;
}

// Verificar si el usuario está autenticado en la aplicación
if (!empty($_SESSION['usuario'])) {
    $usuarioAutenticado = true;
}

if (!$tokenValido && !$usuarioAutenticado) {
    http_response_code(403);
    die('<h2>Acceso Denegado</h2><p>Se requiere autenticación.</p>');
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marcar Ganar Completo - Producción</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #856404;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #0c5460;
        }
        .button-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        button {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #28a745;
            color: white;
        }
        .btn-primary:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        #resultado {
            margin-top: 30px;
            padding: 20px;
            border-radius: 4px;
            display: none;
        }
        #resultado.success {
            display: block;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        #resultado.error {
            display: block;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 15px;
            overflow: hidden;
            display: none;
        }
        .progress-bar.active {
            display: block;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: 0%;
            transition: width 0.3s;
        }
        .stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            margin-top: 20px;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .log-output {
            background: #f4f4f4;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 15px;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📊 Marcar Ganar Completo</h1>
        <p class="subtitle">Procesar personas antiguas completamente asignadas</p>
        
        <div class="info-box">
            <strong>ℹ️ Información:</strong><br>
            Este script marca automáticamente como completados todos los pasos de "Ganar" para las personas que ya tienen <strong>célula + líder + ministerio</strong> asignados.
        </div>

        <div class="warning-box">
            <strong>⚠️ Importante:</strong><br>
            Esta operación es irreversible y afectará el registro histórico de todas las personas completamente asignadas. Se recomienda hacer un backup antes de ejecutar.
        </div>

        <div class="button-group">
            <button class="btn-primary" onclick="ejecutar()">Ejecutar Ahora</button>
            <button class="btn-secondary" onclick="preview()">Vista Previa</button>
        </div>

        <div id="resultado"></div>
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
    </div>

    <script>
        function preview() {
            fetch('?action=preview')
                .then(r => r.json())
                .then(data => {
                    const res = document.getElementById('resultado');
                    if (data.success) {
                        res.className = 'success';
                        res.innerHTML = `
                            <h3>✓ Vista Previa</h3>
                            <p>Encontradas <strong>${data.cantidad}</strong> personas completamente asignadas que serán procesadas:</p>
                            <div class="log-output">${data.personas.map(p => `${p.id} - ${p.nombre}`).join('<br>')}</div>
                            <div class="stats">
                                <div class="stat-item">
                                    <div class="stat-value">${data.cantidad}</div>
                                    <div class="stat-label">A Procesar</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">3</div>
                                    <div class="stat-label">Pasos c/u</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">${data.cantidad * 3}</div>
                                    <div class="stat-label">Total checks</div>
                                </div>
                            </div>
                        `;
                    } else {
                        res.className = 'error';
                        res.innerHTML = `<h3>✗ Error</h3><p>${data.error}</p>`;
                    }
                })
                .catch(e => {
                    const res = document.getElementById('resultado');
                    res.className = 'error';
                    res.innerHTML = `<h3>✗ Error</h3><p>${e.message}</p>`;
                });
        }

        function ejecutar() {
            if (!confirm('¿Está seguro de que desea continuar? Esto marcará todos los pasos de Ganar para personas completamente asignadas.')) {
                return;
            }

            const pb = document.querySelector('.progress-bar');
            pb.classList.add('active');
            
            fetch('?action=ejecutar')
                .then(r => r.json())
                .then(data => {
                    pb.classList.remove('active');
                    const res = document.getElementById('resultado');
                    if (data.success) {
                        res.className = 'success';
                        res.innerHTML = `
                            <h3>✓ Operación Completada</h3>
                            <div class="stats">
                                <div class="stat-item">
                                    <div class="stat-value">${data.actualizadas}</div>
                                    <div class="stat-label">Actualizadas</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">${data.errores}</div>
                                    <div class="stat-label">Errores</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-value">${data.total}</div>
                                    <div class="stat-label">Total Procesadas</div>
                                </div>
                            </div>
                            <p style="margin-top: 15px;">El script se ejecutó exitosamente. Las personas completamente asignadas han sido marcadas.</p>
                        `;
                    } else {
                        res.className = 'error';
                        res.innerHTML = `<h3>✗ Error</h3><p>${data.error}</p>`;
                    }
                })
                .catch(e => {
                    pb.classList.remove('active');
                    const res = document.getElementById('resultado');
                    res.className = 'error';
                    res.innerHTML = `<h3>✗ Error</h3><p>${e.message}</p>`;
                });
        }
    </script>
</body>
</html>

<?php
// Procesar acciones AJAX
if (!empty($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    
    try {
        $db = Database::getInstance()->getConnection();
        
        if ($action === 'preview') {
            $query = "
                SELECT 
                    p.Id_Persona as id,
                    CONCAT(p.Nombre, ' ', p.Apellido) as nombre,
                    p.Id_Lider,
                    p.Id_Ministerio,
                    p.Id_Celula
                FROM persona p
                WHERE p.Id_Lider IS NOT NULL AND p.Id_Lider > 0
                  AND p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0
                  AND p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
                  AND p.Proceso = 'Ganar'
                ORDER BY p.Id_Persona
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'cantidad' => count($personas),
                'personas' => $personas
            ]);
            exit;
        }
        
        if ($action === 'ejecutar') {
            $query = "
                SELECT 
                    p.Id_Persona,
                    p.Escalera_Checklist,
                    p.Nombre,
                    p.Apellido,
                    p.Id_Lider,
                    p.Id_Ministerio,
                    p.Id_Celula
                FROM persona p
                WHERE p.Id_Lider IS NOT NULL AND p.Id_Lider > 0
                  AND p.Id_Ministerio IS NOT NULL AND p.Id_Ministerio > 0
                  AND p.Id_Celula IS NOT NULL AND p.Id_Celula > 0
                  AND p.Proceso = 'Ganar'
                ORDER BY p.Id_Persona
            ";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $personas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $actualizadas = 0;
            $errores = 0;
            
            foreach ($personas as $persona) {
                $idPersona = (int)$persona['Id_Persona'];
                
                $escalerRaw = $persona['Escalera_Checklist'] ?? '';
                $escalera = [];
                
                if (!empty($escalerRaw)) {
                    $tmp = json_decode($escalerRaw, true);
                    if (is_array($tmp)) {
                        $escalera = $tmp;
                    }
                }
                
                if (!isset($escalera['Ganar']) || !is_array($escalera['Ganar'])) {
                    $escalera['Ganar'] = [false, false, false, false];
                } else {
                    while (count($escalera['Ganar']) < 4) {
                        $escalera['Ganar'][] = false;
                    }
                }
                
                if (!isset($escalera['_meta'])) {
                    $escalera['_meta'] = [];
                }
                
                // Marcar los 3 primeros pasos
                $escalera['Ganar'][0] = true;
                $escalera['Ganar'][1] = true;
                $escalera['Ganar'][2] = true;
                
                $escalera['_meta']['actualizado_automatico'] = true;
                $escalera['_meta']['actualizado_automatico_at'] = date('Y-m-d H:i:s');
                $escalera['_meta']['actualizado_automatico_nota'] = 'Marcar Ganar completo (asignación histórica)';
                
                $escalerJson = json_encode($escalera, JSON_UNESCAPED_UNICODE);
                if ($escalerJson === false) {
                    $errores++;
                    continue;
                }
                
                $updateStmt = $db->prepare("UPDATE persona SET Escalera_Checklist = ? WHERE Id_Persona = ?");
                
                try {
                    $updateStmt->execute([$escalerJson, $idPersona]);
                    $actualizadas++;
                } catch (Exception $e) {
                    $errores++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'actualizadas' => $actualizadas,
                'errores' => $errores,
                'total' => count($personas)
            ]);
            exit;
        }
        
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
        exit;
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}
?>
