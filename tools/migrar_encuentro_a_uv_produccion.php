<?php
declare(strict_types=1);

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    fwrite(STDERR, "ERROR: No se pudo inicializar la conexion PDO desde conexion.php\n");
    exit(1);
}

$args = $argv ?? [];
$modoAplicar = in_array('--apply', $args, true);
$modoDryRun = !$modoAplicar;

echo "Migracion Encuentro -> Universidad de la Vida\n";
echo $modoDryRun
    ? "Modo: DRY-RUN (simulacion, se revierte al final)\n\n"
    : "Modo: APPLY (aplica cambios reales)\n\n";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    $totalEncuentroInicial = (int)$pdo->query(
        "SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Programa = 'encuentro'"
    )->fetchColumn();

    $totalUvInicial = (int)$pdo->query(
        "SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Programa = 'universidad_vida'"
    )->fetchColumn();

    // 1) Eliminar registros 'encuentro' que ya tienen UV para la misma persona.
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

    // 2) Migrar 'encuentro' -> 'universidad_vida' cuando NO existe UV para esa persona.
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

    // 3) Migrar filas sin persona asociada (Id_Persona NULL/0).
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

    if ($modoDryRun) {
        $pdo->rollBack();
    } else {
        $pdo->commit();
    }

    echo "Total Encuentro inicial: {$totalEncuentroInicial}\n";
    echo "Total UV inicial: {$totalUvInicial}\n";
    echo "Eliminados por duplicado: {$eliminadosDuplicado}\n";
    echo "Actualizados con persona: {$actualizadosConPersona}\n";
    echo "Actualizados sin persona: {$actualizadosSinPersona}\n";
    echo "Actualizados total: {$actualizadosTotal}\n";
    echo "Total Encuentro final: {$totalEncuentroFinal}\n";
    echo "Total UV final: {$totalUvFinal}\n";
    echo "Duplicados UV por Id_Persona: {$duplicadosUv}\n";
    echo $modoDryRun
        ? "\nResultado: SIMULACION completada (sin cambios persistidos).\n"
        : "\nResultado: CAMBIOS aplicados correctamente.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
