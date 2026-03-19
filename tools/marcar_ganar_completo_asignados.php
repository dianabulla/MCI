<?php
/**
 * Script: Marcar Ganar completo para personas antiguas asignadas
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
 * 
 * Ideal para migración de datos históricos que ya están completos
 */

require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';

$db = Database::getInstance()->getConnection();

// Paso 1: Obtener todas las personas completamente asignadas
$query = "
    SELECT 
        p.Id_Persona,
        p.Escalera_Checklist,
        p.Proceso,
        p.Nombre,
        p.Apellido,
        p.Id_Lider,
        p.Id_Ministerio,
        p.Id_Celula
    FROM persona p
    WHERE p.Id_Lider IS NOT NULL 
      AND p.Id_Lider > 0
      AND p.Id_Ministerio IS NOT NULL 
      AND p.Id_Ministerio > 0
      AND p.Id_Celula IS NOT NULL 
      AND p.Id_Celula > 0
      AND p.Proceso = 'Ganar'
    ORDER BY p.Id_Persona
";

$stmt = $db->prepare($query);
$stmt->execute();
$personas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Personas encontradas completamente asignadas: " . count($personas) . "\n";
echo str_repeat("=", 80) . "\n\n";

$actualizadas = 0;
$errores = 0;

foreach ($personas as $persona) {
    $idPersona = (int)$persona['Id_Persona'];
    $nombre = trim($persona['Nombre'] . ' ' . $persona['Apellido']);
    
    // Decodificar Escalera_Checklist actual
    $escalerRaw = $persona['Escalera_Checklist'] ?? '';
    $escalera = [];
    
    if (!empty($escalerRaw)) {
        $tmp = json_decode($escalerRaw, true);
        if (is_array($tmp)) {
            $escalera = $tmp;
        }
    }
    
    // Normalizar estructura
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
    
    // Verificar si ya está completo
    $yaCompleto = ($escalera['Ganar'][0] && $escalera['Ganar'][1] && $escalera['Ganar'][2]);
    
    if ($yaCompleto) {
        echo "✓ [Ya completo] ID: $idPersona - $nombre\n";
        continue;
    }
    
    // Marcar los 3 primeros pasos como completados
    $escalera['Ganar'][0] = true;  // Asignado a líder
    $escalera['Ganar'][1] = true;  // Primer contacto
    $escalera['Ganar'][2] = true;  // Ubicado en célula
    // Ganar[3] ("No se dispone") se deja como estaba
    
    // En _meta, registrar que fue actualizado automáticamente
    $escalera['_meta']['actualizado_automatico'] = true;
    $escalera['_meta']['actualizado_automatico_at'] = date('Y-m-d H:i:s');
    $escalera['_meta']['actualizado_automatico_nota'] = 'Marcar Ganar completo (asignación histórica)';
    
    // Serializar
    $escalerJson = json_encode($escalera, JSON_UNESCAPED_UNICODE);
    if ($escalerJson === false) {
        echo "✗ [Error JSON] ID: $idPersona - $nombre\n";
        $errores++;
        continue;
    }
    
    // Actualizar en BD
    $updateStmt = $db->prepare("
        UPDATE persona 
        SET Escalera_Checklist = ?
        WHERE Id_Persona = ?
    ");
    
    try {
        $updateStmt->execute([$escalerJson, $idPersona]);
        echo "✓ [Actualizado] ID: $idPersona - $nombre\n";
        $actualizadas++;
    } catch (Exception $e) {
        echo "✗ [Error BD] ID: $idPersona - $nombre - " . $e->getMessage() . "\n";
        $errores++;
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RESUMEN:\n";
echo "  ✓ Actualizadas: $actualizadas\n";
echo "  ✗ Errores: $errores\n";
echo "  Total procesadas: " . count($personas) . "\n";
echo str_repeat("=", 80) . "\n";
?>
