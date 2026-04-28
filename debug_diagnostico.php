<?php
define('ROOT', __DIR__);
define('APP', ROOT . '/app');
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';
require_once 'app/Models/BaseModel.php';
require_once 'app/Models/Persona.php';
require_once 'app/Models/EscuelaFormacionInscripcion.php';
require_once 'app/Models/EscuelaFormacionAsistenciaClase.php';

$pdo = Database::getInstance()->getConnection();
$ayer = date('Y-m-d', strtotime('-1 day'));

echo "=== DIAGNÓSTICO: ¿POR QUÉ NO SE MARCÓ ASISTENCIA AYER? ===\n\n";

// 1. Buscar inscripciones creadas ayer
echo "1. INSCRIPCIONES CREADAS AYER:\n";
$sql = "SELECT Id_Inscripcion, Id_Persona, Programa FROM escuela_formacion_inscripcion WHERE DATE(Fecha_Creacion) = '" . $ayer . "' LIMIT 5";
$inscripciones = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

if (empty($inscripciones)) {
    echo "   No hay inscripciones creadas ayer.\n\n";
} else {
    echo "   Encontradas " . count($inscripciones) . " inscripciones\n\n";
    
    foreach ($inscripciones as $insc) {
        $idInsc = $insc['Id_Inscripcion'];
        $idPersona = $insc['Id_Persona'];
        $programa = $insc['Programa'];
        
        echo "   === PROBANDO INSCRIPCION $idInsc (Persona: $idPersona, Programa: $programa) ===\n";
        
        // Simular lo que hace marcarAsistenciaAutomaticaDesdeRegistroPublico
        $programaNormalizado = normalizarPrograma($programa);
        $modulo = resolverModulo($programaNormalizado);
        
        echo "      Programa normalizado: $programaNormalizado\n";
        echo "      Modulo resuelto: $modulo\n";
        
        $asistenciaModel = new EscuelaFormacionAsistenciaClase();
        $numeroClase = $asistenciaModel->getNumeroClasePorFecha($modulo, $programaNormalizado, $ayer);
        
        echo "      Número de clase encontrada para $ayer: ";
        if ($numeroClase <= 0) {
            echo "NO ENCONTRADA (PROBLEMA!)\n";
        } else {
            echo "$numeroClase (OK)\n";
            
            // Intentar marcar asistencia
            echo "      Intentando upsertAsistencia...\n";
            $resultado = $asistenciaModel->upsertAsistencia($idPersona, $modulo, $programaNormalizado, $numeroClase, true);
            echo "      Resultado: " . ($resultado ? "OK" : "FALLO") . "\n";
        }
        echo "\n";
    }
}

// 2. Verificar si hay asistencias marcadas para ayer
echo "2. ASISTENCIAS QUE SÍ SE MARCARON AYER:\n";
$sql = "SELECT COUNT(*) as count FROM escuela_formacion_asistencia_clase WHERE DATE(Fecha_Actualizacion) = '" . $ayer . "'";
$result = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
echo "   Total de asistencias marcadas ayer: " . $result['count'] . "\n\n";

// Funciones helper
function normalizarPrograma($programa) {
    $programa = trim((string)$programa);
    if ($programa === 'capacitacion_destino') {
        return 'capacitacion_destino_nivel_1';
    }
    return $programa;
}

function resolverModulo($programa) {
    if (in_array($programa, ['universidad_vida', 'encuentro'], true)) {
        return 'consolidar';
    }
    if (in_array($programa, ['bautismo', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
        return 'discipular';
    }
    return '';
}
?>
