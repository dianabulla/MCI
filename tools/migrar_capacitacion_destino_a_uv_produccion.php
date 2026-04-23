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

$programasOrigen = [
    'capacitacion_destino',
    'capacitacion_destino_nivel_1',
    'capacitacion_destino_nivel_2',
    'capacitacion_destino_nivel_3',
];

$placeholders = implode(',', array_fill(0, count($programasOrigen), '?'));

echo "Migracion Capacitacion Destino -> Universidad de la Vida\n";
echo $modoDryRun
    ? "Modo: DRY-RUN (simulacion, se revierte al final)\n\n"
    : "Modo: APPLY (aplica cambios reales)\n\n";

echo "Programas origen: " . implode(', ', $programasOrigen) . "\n\n";

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->beginTransaction();

    $stmtTotalesOrigen = $pdo->prepare(
        "SELECT Programa, COUNT(*) AS Total
         FROM escuela_formacion_inscripcion
         WHERE Programa IN ({$placeholders})
         GROUP BY Programa"
    );
    $stmtTotalesOrigen->execute($programasOrigen);
    $totalesOrigenPorPrograma = $stmtTotalesOrigen->fetchAll(PDO::FETCH_ASSOC);

    $totalOrigenInicial = 0;
    $mapaOrigen = [];
    foreach ($totalesOrigenPorPrograma as $fila) {
        $programa = (string)($fila['Programa'] ?? '');
        $total = (int)($fila['Total'] ?? 0);
        $mapaOrigen[$programa] = $total;
        $totalOrigenInicial += $total;
    }

    $totalUvInicial = (int)$pdo->query(
        "SELECT COUNT(*) FROM escuela_formacion_inscripcion WHERE Programa = 'universidad_vida'"
    )->fetchColumn();

    $stmtPersonasUv = $pdo->query(
        "SELECT DISTINCT Id_Persona
         FROM escuela_formacion_inscripcion
         WHERE Programa = 'universidad_vida' AND Id_Persona > 0"
    );
    $personasConUv = [];
    foreach ($stmtPersonasUv->fetchAll(PDO::FETCH_ASSOC) as $filaUv) {
        $idPersona = (int)($filaUv['Id_Persona'] ?? 0);
        if ($idPersona > 0) {
            $personasConUv[$idPersona] = true;
        }
    }

    $stmtOrigen = $pdo->prepare(
        "SELECT Id_Inscripcion, Id_Persona, Programa, Fecha_Registro
         FROM escuela_formacion_inscripcion
         WHERE Programa IN ({$placeholders})
         ORDER BY Fecha_Registro DESC, Id_Inscripcion DESC"
    );
    $stmtOrigen->execute($programasOrigen);
    $rowsOrigen = $stmtOrigen->fetchAll(PDO::FETCH_ASSOC);

    $stmtDelete = $pdo->prepare("DELETE FROM escuela_formacion_inscripcion WHERE Id_Inscripcion = ?");
    $stmtUpdate = $pdo->prepare("UPDATE escuela_formacion_inscripcion SET Programa = 'universidad_vida' WHERE Id_Inscripcion = ?");

    $actualizados = 0;
    $eliminadosDuplicado = 0;
    $eliminadosMultiples = 0;
    $vistosEnOrigen = [];

    foreach ($rowsOrigen as $row) {
        $idInscripcion = (int)($row['Id_Inscripcion'] ?? 0);
        $idPersona = (int)($row['Id_Persona'] ?? 0);

        if ($idInscripcion <= 0) {
            continue;
        }

        if ($idPersona > 0) {
            if (isset($vistosEnOrigen[$idPersona])) {
                $stmtDelete->execute([$idInscripcion]);
                $eliminadosMultiples++;
                continue;
            }

            $vistosEnOrigen[$idPersona] = true;

            if (isset($personasConUv[$idPersona])) {
                $stmtDelete->execute([$idInscripcion]);
                $eliminadosDuplicado++;
                continue;
            }

            $stmtUpdate->execute([$idInscripcion]);
            $actualizados++;
            $personasConUv[$idPersona] = true;
            continue;
        }

        $stmtUpdate->execute([$idInscripcion]);
        $actualizados++;
    }

    $stmtTotalOrigenFinal = $pdo->prepare(
        "SELECT COUNT(*)
         FROM escuela_formacion_inscripcion
         WHERE Programa IN ({$placeholders})"
    );
    $stmtTotalOrigenFinal->execute($programasOrigen);
    $totalOrigenFinal = (int)$stmtTotalOrigenFinal->fetchColumn();

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

    echo "Totales origen inicial por programa:\n";
    foreach ($programasOrigen as $prog) {
        echo "- {$prog}: " . (int)($mapaOrigen[$prog] ?? 0) . "\n";
    }
    echo "Total origen inicial: {$totalOrigenInicial}\n";
    echo "Total UV inicial: {$totalUvInicial}\n";
    echo "Actualizados a UV: {$actualizados}\n";
    echo "Eliminados por duplicado UV existente: {$eliminadosDuplicado}\n";
    echo "Eliminados por multiples del mismo origen: {$eliminadosMultiples}\n";
    echo "Total origen final: {$totalOrigenFinal}\n";
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
