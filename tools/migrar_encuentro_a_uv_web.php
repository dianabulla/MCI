<?php
declare(strict_types=1);

require_once __DIR__ . '/../conexion.php';

header('Content-Type: text/plain; charset=utf-8');

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo "ERROR: No se pudo inicializar la conexion PDO desde conexion.php\n";
    exit;
}

$apply = isset($_GET['apply']) && $_GET['apply'] === '1';
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'SI_MIGRAR';
$ejecutarReal = $apply && $confirm;

$basePath = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/tools'), '/\\');
$selfUrl = $basePath . '/migrar_encuentro_a_uv_web.php';

echo "Migracion Encuentro -> Universidad de la Vida (via URL)\n";
echo $ejecutarReal
    ? "Modo: APPLY (cambios reales)\n\n"
    : "Modo: DRY-RUN (simulacion, sin cambios)\n\n";

echo "Uso:\n";
echo "- Simulacion: {$selfUrl}\n";
echo "- Aplicar:    {$selfUrl}?apply=1&confirm=SI_MIGRAR\n\n";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    $totalEncuentroInicial = (int)$pdo->query(
        "SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Programa = 'encuentro'"
    )->fetchColumn();

    $totalUvInicial = (int)$pdo->query(
        "SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Programa = 'universidad_vida'"
    )->fetchColumn();

    $sqlEliminarDuplicados = "
        DELETE e
        FROM escuela_formacion_inscripcion e
        INNER JOIN escuela_formacion_inscripcion uv
            ON uv.Programa = 'universidad_vida'
           AND uv.Id_Persona = e.Id_Persona
           AND uv.Id_Inscripcion <> e.Id_Inscripcion
        WHERE e.Programa = 'encuentro'
          AND e.Id_Persona > 0
    ";
    $stmtEliminar = $pdo->prepare($sqlEliminarDuplicados);
    $stmtEliminar->execute();
    $eliminadosDuplicado = (int)$stmtEliminar->rowCount();

    $sqlActualizarConPersona = "
        UPDATE escuela_formacion_inscripcion e
        LEFT JOIN escuela_formacion_inscripcion uv
            ON uv.Programa = 'universidad_vida'
           AND uv.Id_Persona = e.Id_Persona
        SET e.Programa = 'universidad_vida'
        WHERE e.Programa = 'encuentro'
          AND e.Id_Persona > 0
          AND uv.Id_Inscripcion IS NULL
    ";
    $stmtActualizarConPersona = $pdo->prepare($sqlActualizarConPersona);
    $stmtActualizarConPersona->execute();
    $actualizadosConPersona = (int)$stmtActualizarConPersona->rowCount();

    $sqlActualizarSinPersona = "
        UPDATE escuela_formacion_inscripcion
        SET Programa = 'universidad_vida'
        WHERE Programa = 'encuentro'
          AND (Id_Persona IS NULL OR Id_Persona <= 0)
    ";
    $stmtActualizarSinPersona = $pdo->prepare($sqlActualizarSinPersona);
    $stmtActualizarSinPersona->execute();
    $actualizadosSinPersona = (int)$stmtActualizarSinPersona->rowCount();

    $actualizadosTotal = $actualizadosConPersona + $actualizadosSinPersona;

    $totalEncuentroFinal = (int)$pdo->query(
        "SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Programa = 'encuentro'"
    )->fetchColumn();

    $totalUvFinal = (int)$pdo->query(
        "SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Programa = 'universidad_vida'"
    )->fetchColumn();

    $duplicadosUv = (int)$pdo->query(
        "SELECT COUNT(*) FROM (
            SELECT Id_Persona
            FROM escuela_formacion_inscripcion
            WHERE Programa = 'universidad_vida'
              AND Id_Persona > 0
            GROUP BY Id_Persona
            HAVING COUNT(*) > 1
        ) t"
    )->fetchColumn();

    if ($ejecutarReal) {
        $pdo->commit();
    } else {
        $pdo->rollBack();
    }

    echo "Total Encuentro inicial: {$totalEncuentroInicial}\n";
    echo "Total UV inicial: {$totalUvInicial}\n";
    echo "Eliminados por duplicado: {$eliminadosDuplicado}\n";
    echo "Actualizados con persona: {$actualizadosConPersona}\n";
    echo "Actualizados sin persona: {$actualizadosSinPersona}\n";
    echo "Actualizados total: {$actualizadosTotal}\n";
    echo "Total Encuentro final: {$totalEncuentroFinal}\n";
    echo "Total UV final: {$totalUvFinal}\n";
    echo "Duplicados UV por Id_Persona: {$duplicadosUv}\n\n";

    echo $ejecutarReal
        ? "Resultado: CAMBIOS aplicados correctamente.\n"
        : "Resultado: SIMULACION completada (sin cambios persistidos).\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
}
