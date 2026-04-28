<?php
define('ROOT', __DIR__);
define('APP', ROOT . '/app');
require_once 'app/Config/config.php';
require_once 'app/Config/Database.php';
require_once 'app/Models/BaseModel.php';
require_once 'app/Models/EscuelaFormacionAsistenciaClase.php';

$pdo = Database::getInstance()->getConnection();

echo "=== SIMULACIÓN: ¿POR QUÉ ARMADO BULLA NO TIENE ASISTENCIA? ===\n\n";

// Datos de Armado Bulla de hoy
$idInscripcion = 12;
$idPersona = (int)$pdo->query("SELECT Id_Persona FROM escuela_formacion_inscripcion WHERE Id_Inscripcion = $idInscripcion")->fetch(PDO::FETCH_ASSOC)['Id_Persona'];
$programa = 'universidad_vida';
$fecha_hoy = '2026-04-28';

echo "Simulando: marcarAsistenciaAutomaticaDesdeRegistroPublico($idInscripcion, $idPersona, '$programa', '$fecha_hoy')\n\n";

// Paso 1: Normalizar programa
$programaNormalizado = trim((string)$programa);
if ($programaNormalizado === 'capacitacion_destino') {
    $programaNormalizado = 'capacitacion_destino_nivel_1';
}
echo "1. Programa normalizado: $programaNormalizado\n";

// Paso 2: Resolver modulo
$modulo = '';
if (in_array($programaNormalizado, ['universidad_vida', 'encuentro'], true)) {
    $modulo = 'consolidar';
} elseif (in_array($programaNormalizado, ['bautismo', 'capacitacion_destino_nivel_1', 'capacitacion_destino_nivel_2', 'capacitacion_destino_nivel_3'], true)) {
    $modulo = 'discipular';
}
echo "2. Módulo resuelto: $modulo\n";

if ($idPersona <= 0 || $modulo === '' || $programaNormalizado === '') {
    echo "❌ ABORTADO: idPersona=$idPersona, modulo=$modulo, programa=$programaNormalizado\n";
    exit(1);
}

// Paso 3: Buscar clase
$asistenciaModel = new EscuelaFormacionAsistenciaClase();
$numeroClase = $asistenciaModel->getNumeroClasePorFecha($modulo, $programaNormalizado, $fecha_hoy);
echo "3. Búsqueda de clase para ($modulo, $programaNormalizado, $fecha_hoy): " . ($numeroClase > 0 ? "Clase $numeroClase encontrada ✅" : "NO ENCONTRADA ❌") . "\n";

if ($numeroClase <= 0) {
    echo "\n❌ FALLO: No se encontró clase\n";
    exit(1);
}

// Paso 4: Marcar asistencia
echo "\n4. Intentando upsertAsistencia($idPersona, $modulo, $programaNormalizado, $numeroClase, true)...\n";
try {
    $resultado = $asistenciaModel->upsertAsistencia($idPersona, $modulo, $programaNormalizado, $numeroClase, true);
    echo "   Resultado: " . ($resultado ? "✅ OK" : "❌ FALLO") . "\n";
} catch (Exception $e) {
    echo "   ❌ EXCEPCIÓN: " . $e->getMessage() . "\n";
    exit(1);
}

// Paso 5: Actualizar inscripción
echo "\n5. Verificando si inscripción fue actualizada...\n";
$stmt = $pdo->prepare('SELECT Asistio_Clase, Fecha_Asistencia_Clase FROM escuela_formacion_inscripcion WHERE Id_Inscripcion = ?');
$stmt->execute([$idInscripcion]);
$inscripcion = $stmt->fetch(PDO::FETCH_ASSOC);
echo "   Asistio_Clase actual: " . ($inscripcion['Asistio_Clase'] ? "SÍ ✅" : "NO ❌") . "\n";
echo "   Fecha_Asistencia_Clase: " . ($inscripcion['Fecha_Asistencia_Clase'] ?: "(vacía)") . "\n";

if (!$inscripcion['Asistio_Clase']) {
    echo "\n❌ PROBLEMA: upsertAsistencia se ejecutó, pero la inscripción NO fue actualizada\n";
    echo "   Esto sugiere que upsertAsistencia inserta en asistencia_clase pero NO actualiza inscripción\n";
}
?>
