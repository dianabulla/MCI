<?php
/**
 * Marca números como no contactables por WhatsApp (opt-out).
 *
 * Uso CLI:
 *   php tools/marcar_optout_whatsapp_numeros.php           (vista previa)
 *   php tools/marcar_optout_whatsapp_numeros.php --apply   (aplica cambios)
 *
 * Uso web:
 *   /tools/marcar_optout_whatsapp_numeros.php
 *   /tools/marcar_optout_whatsapp_numeros.php?apply=1
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../conexion.php';

$isCli = (PHP_SAPI === 'cli');
$args = $isCli ? ($_SERVER['argv'] ?? []) : [];
$apply = in_array('--apply', $args, true) || (($_GET['apply'] ?? '0') === '1');

function out($text, $isCli = true) {
    if ($isCli) {
        echo $text . PHP_EOL;
    } else {
        echo nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')) . "<br>";
    }
}

function normalizarTelefonoParaOptout($raw) {
    $valor = trim((string)$raw);
    if ($valor === '') {
        return null;
    }

    if (stripos($valor, 'E+') !== false || stripos($valor, 'E-') !== false) {
        return null;
    }

    $digitos = preg_replace('/\D+/', '', $valor);
    if ($digitos === '') {
        return null;
    }

    if (strpos($digitos, '57') === 0 && strlen($digitos) >= 12) {
        $digitos = substr($digitos, -10);
    }

    if (strlen($digitos) !== 10) {
        return null;
    }

    return '+57' . $digitos;
}

$rawLista = <<<TXT
573156061608
573113226487
573002092108
573103186528
573003949001
573205550954
573006022379
573203579146
573214249184
573152523459
573124860470
573148517408
573144587995
324336000
573117105477
573144066433
573102509619
573024677465
573227960341
573203094073
573104814202
573222970546
573227883527
573186934780
3,21E+12
573019224259
573102935612
573196223669
573229569723
573124117742
31327000162
573235993901
573204502618
573115440080
573123883822
573216966270
573145517087
571018445065
573228439813
573183177742
TXT;

$lineas = preg_split('/\r\n|\r|\n/', $rawLista);
$telefonos = [];
$telefonosRawDigitos = [];
$invalidos = [];

foreach ($lineas as $linea) {
    $original = trim((string)$linea);
    if ($original === '') {
        continue;
    }

    $soloDigitos = preg_replace('/\D+/', '', $original);
    if ($soloDigitos !== '') {
        $telefonosRawDigitos[] = $soloDigitos;
    }

    $normalizado = normalizarTelefonoParaOptout($original);
    if ($normalizado === null) {
        $invalidos[] = $original;
        continue;
    }

    $telefonos[] = $normalizado;
}

$telefonos = array_values(array_unique($telefonos));
$telefonosRawDigitos = array_values(array_unique($telefonosRawDigitos));

if (empty($telefonos)) {
    out('❌ No hay teléfonos válidos para procesar.', $isCli);
    if (!empty($invalidos)) {
        out('Inválidos: ' . implode(', ', $invalidos), $isCli);
    }
    exit(1);
}

$motivo = 'Solicitud explícita: no recibir más mensajes';
$origen = 'carga_manual_produccion_2026-03-04';

try {
    $placeholders = implode(',', array_fill(0, count($telefonos), '?'));

    $stmtExistentes = $pdo->prepare("SELECT telefono, activo FROM whatsapp_optout WHERE telefono IN ($placeholders)");
    $stmtExistentes->execute($telefonos);
    $existentes = $stmtExistentes->fetchAll(PDO::FETCH_ASSOC);

    $mapExistentes = [];
    foreach ($existentes as $row) {
        $mapExistentes[(string)$row['telefono']] = (int)$row['activo'];
    }

    $yaActivos = 0;
    $reactivables = 0;
    $nuevos = 0;

    foreach ($telefonos as $telefono) {
        if (!array_key_exists($telefono, $mapExistentes)) {
            $nuevos++;
            continue;
        }

        if ((int)$mapExistentes[$telefono] === 1) {
            $yaActivos++;
        } else {
            $reactivables++;
        }
    }

    out('Total teléfonos válidos únicos: ' . count($telefonos), $isCli);
    out('Nuevos para insertar: ' . $nuevos, $isCli);
    out('Registros para reactivar: ' . $reactivables, $isCli);
    out('Ya activos: ' . $yaActivos, $isCli);

    if (!empty($invalidos)) {
        out('⚠️ No procesados (formato inválido): ' . implode(', ', $invalidos), $isCli);
    }

    if (!$apply) {
        out('', $isCli);
        out('Modo vista previa. No se aplicaron cambios.', $isCli);
        out('Para aplicar: php tools/marcar_optout_whatsapp_numeros.php --apply', $isCli);
        exit;
    }

    $pdo->beginTransaction();

    $stmtUpsert = $pdo->prepare(
        "INSERT INTO whatsapp_optout (telefono, motivo, origen, activo)
         VALUES (?, ?, ?, 1)
         ON DUPLICATE KEY UPDATE
            motivo = VALUES(motivo),
            origen = VALUES(origen),
            activo = 1"
    );

    foreach ($telefonos as $telefono) {
        $stmtUpsert->execute([$telefono, $motivo, $origen]);
    }

    if (!empty($telefonosRawDigitos)) {
        $placeholdersRaw = implode(',', array_fill(0, count($telefonosRawDigitos), '?'));
        $stmtDeleteLegacy = $pdo->prepare(
            "DELETE FROM whatsapp_optout
             WHERE telefono IN ($placeholdersRaw)
               AND telefono NOT LIKE '+57%'"
        );
        $stmtDeleteLegacy->execute($telefonosRawDigitos);
    }

    $stmtColumnas = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'nehemias'
           AND COLUMN_NAME IN ('Consentimiento_Whatsapp', 'no_recibir_mas')"
    );
    $stmtColumnas->execute();
    $columnas = $stmtColumnas->fetchAll(PDO::FETCH_COLUMN);
    $hasConsentimiento = in_array('Consentimiento_Whatsapp', $columnas, true);
    $hasNoRecibirMas = in_array('no_recibir_mas', $columnas, true);

    $consentimientosActualizados = 0;
    $noRecibirMasActualizados = 0;

    if ($hasConsentimiento) {
        $stmtUpdateConsent = $pdo->prepare(
            "UPDATE nehemias
             SET Consentimiento_Whatsapp = 0
             WHERE Telefono_Normalizado IN ($placeholders)"
        );
        $stmtUpdateConsent->execute($telefonos);
        $consentimientosActualizados = (int)$stmtUpdateConsent->rowCount();
    }

    if ($hasNoRecibirMas) {
        $stmtUpdateNoRecibirMas = $pdo->prepare(
            "UPDATE nehemias
             SET no_recibir_mas = 1
             WHERE Telefono_Normalizado IN ($placeholders)"
        );
        $stmtUpdateNoRecibirMas->execute($telefonos);
        $noRecibirMasActualizados = (int)$stmtUpdateNoRecibirMas->rowCount();
    }

    $stmtVerificarOptout = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM whatsapp_optout
         WHERE telefono IN ($placeholders)
           AND activo = 1"
    );
    $stmtVerificarOptout->execute($telefonos);
    $optoutActivos = (int)($stmtVerificarOptout->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

    $pdo->commit();

    out('', $isCli);
    out('✅ Opt-out aplicado correctamente.', $isCli);
    out('Teléfonos activos en whatsapp_optout (de esta lista): ' . $optoutActivos, $isCli);
    out('Registros en nehemias con Consentimiento_Whatsapp=0 actualizados: ' . $consentimientosActualizados, $isCli);
    out('Registros en nehemias con no_recibir_mas=1 actualizados: ' . $noRecibirMasActualizados, $isCli);

    if (!$hasConsentimiento) {
        out('⚠️ Columna no encontrada: Consentimiento_Whatsapp (omitida).', $isCli);
    }
    if (!$hasNoRecibirMas) {
        out('⚠️ Columna no encontrada: no_recibir_mas (omitida).', $isCli);
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    out('❌ Error: ' . $e->getMessage(), $isCli);
    http_response_code(500);
    exit(1);
}
