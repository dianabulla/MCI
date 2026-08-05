<?php
/**
 * Compara conteos Pagos vs Asistencias (Universidad de la Vida / programa consolidar).
 * Uso: php tools/diagnostico_pagos_vs_asistencias.php [programa]
 */
declare(strict_types=1);

$root = dirname(__DIR__);
define('ROOT', $root);
define('APP', ROOT . '/app');
chdir($root);
ob_start();
require_once $root . '/conexion.php';
if (file_exists($root . '/app/Config/config.php')) {
    require_once $root . '/app/Config/config.php';
}
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
ob_end_clean();

$programa = trim((string)($argv[1] ?? 'universidad_vida'));
if ($programa === 'capacitacion_destino') {
    $programa = 'capacitacion_destino_nivel_1';
}

$modulo = $programa === 'universidad_vida' ? 'consolidar' : 'discipular';
$programasConsulta = [$programa];
if ($programa === 'universidad_vida') {
    $programasConsulta = ['universidad_vida', 'encuentro'];
}

echo "=== Diagnóstico pagos vs asistencias ===\n";
echo "Programa filtro: {$programa}\n";
echo "Programas consulta asistencias: " . implode(', ', $programasConsulta) . "\n";
echo "Módulo asistencia: {$modulo}\n";
echo "MySQL NOW: " . ($pdo->query('SELECT NOW()')->fetchColumn() ?: '?') . "\n\n";

$inscripcionModel = new EscuelaFormacionInscripcion();

// ── Pagos (misma fuente que escuelas_formacion/pagos) ──
$resumenPagos = $inscripcionModel->getResumenPagosAbonos('', 1000, $programa);
$clavesPagos = [];
foreach ((array)$resumenPagos as $fila) {
    $clave = trim((string)($fila['Cedula_Clave'] ?? ''));
    if ($clave !== '' && $clave !== 'SIN-CEDULA-0') {
        $clavesPagos[$clave] = $fila;
    }
}

// ── Inscripciones (misma base que matriz asistencias, sin aislamiento de rol) ──
$inscripciones = [];
foreach ($programasConsulta as $prog) {
    foreach ($inscripcionModel->getListado($prog, '', 1000) as $ins) {
        $idIns = (int)($ins['Id_Inscripcion'] ?? 0);
        if ($idIns > 0) {
            $inscripciones[$idIns] = $ins;
        }
    }
}

// Deduplicar por persona (como HomeController)
usort($inscripciones, static function ($a, $b) {
    $fa = (string)($a['Fecha_Registro'] ?? '');
    $fb = (string)($b['Fecha_Registro'] ?? '');
    if ($fa === $fb) {
        return (int)($b['Id_Inscripcion'] ?? 0) <=> (int)($a['Id_Inscripcion'] ?? 0);
    }
    return strcmp($fb, $fa);
});
$dedup = [];
$vistas = [];
foreach ($inscripciones as $ins) {
    $idP = (int)($ins['Id_Persona'] ?? 0);
    $clave = $idP > 0 ? ('id:' . $idP) : null;
    if ($clave === null) {
        $cc = preg_replace('/\D+/', '', (string)($ins['Cedula'] ?? ''));
        $clave = $cc !== '' ? ('cc:' . $cc) : ('ins:' . (int)($ins['Id_Inscripcion'] ?? 0));
    }
    if (isset($vistas[$clave])) {
        continue;
    }
    $vistas[$clave] = true;
    $dedup[] = $ins;
}

// Filas asistencia: solo Id_Persona > 0 (como obtenerDatosModuloFormacionAsistencias)
$filasAsistencia = [];
$clavesInscripcionPorPersona = [];
foreach ($dedup as $ins) {
    if (!in_array((string)($ins['Programa'] ?? ''), $programasConsulta, true)) {
        continue;
    }
    $idP = (int)($ins['Id_Persona'] ?? 0);
    $cedula = trim((string)($ins['Cedula'] ?? ''));
    $clavePago = $cedula !== '' ? $cedula : ('SIN-CEDULA-' . $idP);

    if ($idP > 0) {
        $filasAsistencia[] = $ins;
        $clavesInscripcionPorPersona[$clavePago] = $ins;
        $clavesInscripcionPorPersona['id:' . $idP] = $ins;
    }
}

// Inscripciones sin Id_Persona (no aparecen en matriz asistencias)
$sinPersona = array_values(array_filter($dedup, static function ($ins) {
    return (int)($ins['Id_Persona'] ?? 0) <= 0;
}));

// Pagos sin inscripción enlazable por cédula
$soloEnPagos = [];
foreach ($clavesPagos as $clave => $filaPago) {
    $clave = (string)$clave;
    $enInscripcion = isset($clavesInscripcionPorPersona[$clave]);
    if (!$enInscripcion) {
        // Buscar por cédula numérica en inscripciones dedup
        $ccNorm = preg_replace('/\D+/', '', $clave);
        foreach ($dedup as $ins) {
            $ccIns = preg_replace('/\D+/', '', (string)($ins['Cedula'] ?? ''));
            if ($ccNorm !== '' && $ccIns !== '' && $ccNorm === $ccIns) {
                $enInscripcion = true;
                if ((int)($ins['Id_Persona'] ?? 0) <= 0) {
                    $soloEnPagos[$clave] = [
                        'motivo' => 'Tiene pago e inscripción por cédula, pero Id_Persona vacío (no sale en asistencias)',
                        'pago' => $filaPago,
                        'inscripcion' => $ins,
                    ];
                }
                break;
            }
        }
    }
    if (!$enInscripcion && !isset($soloEnPagos[$clave])) {
        $soloEnPagos[$clave] = [
            'motivo' => 'Pago en movimientos sin inscripción coincidente en listado',
            'pago' => $filaPago,
            'inscripcion' => null,
        ];
    }
}

// Inscripciones con persona que no tienen fila en resumen pagos
$soloEnAsistencias = [];
foreach ($filasAsistencia as $ins) {
    $cedula = trim((string)($ins['Cedula'] ?? ''));
    $idP = (int)($ins['Id_Persona'] ?? 0);
    $clave = $cedula !== '' ? $cedula : ('SIN-CEDULA-' . $idP);
    if (!isset($clavesPagos[$clave])) {
        $soloEnAsistencias[$clave] = $ins;
    }
}

echo "--- Totales ---\n";
echo "Personas en resumen PAGOS (escuela_formacion_pago_movimiento): " . count($clavesPagos) . "\n";
echo "Inscripciones deduplicadas (listado): " . count($dedup) . "\n";
echo "Filas matriz ASISTENCIAS (Id_Persona > 0): " . count($filasAsistencia) . "\n";
echo "Inscripciones SIN Id_Persona: " . count($sinPersona) . "\n";
echo "En PAGOS pero no en asistencias: " . count($soloEnPagos) . "\n";
echo "En ASISTENCIAS pero sin movimiento de pago: " . count($soloEnAsistencias) . "\n\n";

if ($soloEnPagos) {
    echo "--- Detalle: están en pagos y NO en matriz asistencias ---\n";
    foreach ($soloEnPagos as $clave => $info) {
        $p = (array)($info['pago'] ?? []);
        echo "Clave: {$clave}\n";
        echo "  Motivo: " . ($info['motivo'] ?? '') . "\n";
        echo "  Nombre (pago): " . ($p['Nombre'] ?? '') . "\n";
        echo "  Total pagado: " . ($p['Total_Pagado'] ?? '') . "\n";
        echo "  Registros pago: " . ($p['Registros_Pago'] ?? '') . "\n";
        $ins = $info['inscripcion'] ?? null;
        if (is_array($ins)) {
            echo "  Id_Inscripcion: " . ($ins['Id_Inscripcion'] ?? '') . "\n";
            echo "  Id_Persona inscripción: " . ($ins['Id_Persona'] ?? 'NULL') . "\n";
        }
        echo "\n";
    }
}

if ($soloEnAsistencias) {
    echo "--- Detalle: en asistencias pero sin fila en resumen pagos ---\n";
    foreach ($soloEnAsistencias as $clave => $ins) {
        echo "Clave: {$clave} | " . ($ins['Nombre'] ?? '') . " | Id_Persona=" . ($ins['Id_Persona'] ?? '') . "\n";
    }
    echo "\n";
}

// Movimientos huérfanos (pagos sin Id_Persona en tabla movimientos)
$sql = "SELECT COUNT(DISTINCT COALESCE(NULLIF(TRIM(Cedula),''), CONCAT('SIN-CEDULA-', COALESCE(Id_Persona,0)))) AS n
        FROM escuela_formacion_pago_movimiento s
        WHERE (Id_Persona IS NULL OR Id_Persona = 0)";
$st = $pdo->query($sql);
echo "Claves de pago distintas sin Id_Persona en movimiento: " . ($st->fetchColumn() ?: 0) . "\n\n";

// Pagos UV que incluyen programa bautismo (en pagos sí, en asistencias UV no)
if ($programa === 'universidad_vida') {
    $sqlBautismo = "SELECT COUNT(DISTINCT COALESCE(NULLIF(TRIM(Cedula),''), CONCAT('SIN-CEDULA-', COALESCE(Id_Persona,0)))) AS n
        FROM escuela_formacion_pago_movimiento
        WHERE Programa = 'bautismo'";
    $nBautismo = (int)($pdo->query($sqlBautismo)->fetchColumn() ?: 0);
    echo "Personas con pago solo bajo Programa='bautismo' (cuentan en Pagos UV, no en matriz UV): {$nBautismo}\n";

    $sqlBautismoLista = "SELECT DISTINCT COALESCE(NULLIF(TRIM(Cedula),''), CONCAT('SIN-CEDULA-', COALESCE(Id_Persona,0))) AS clave,
        MAX(Nombre) AS nombre, SUM(Valor_Pago) AS total
        FROM escuela_formacion_pago_movimiento WHERE Programa = 'bautismo'
        GROUP BY clave LIMIT 20";
    foreach ($pdo->query($sqlBautismoLista)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "  - " . ($r['clave'] ?? '') . ' | ' . ($r['nombre'] ?? '') . ' | $' . ($r['total'] ?? '') . "\n";
    }
}
