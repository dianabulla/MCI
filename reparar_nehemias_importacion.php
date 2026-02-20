<?php
/**
 * Reparar registros de nehemias mal importados (campos pegados en Nombres)
 * Uso: ejecutar por navegador y confirmar.
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/conexion.php';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

function detectarDelimitador(string $texto): string {
    $conteoTab = substr_count($texto, "\t");
    $conteoPyc = substr_count($texto, ';');
    $conteoComa = substr_count($texto, ',');

    if ($conteoTab > $conteoPyc && $conteoTab > $conteoComa) {
        return "\t";
    }
    if ($conteoPyc >= $conteoComa) {
        return ';';
    }
    return ',';
}

function mapearPartes(array $partes): array {
    $limpiar = static function ($v): string {
        return trim((string) $v);
    };

    $obtener = static function (array $arr, int $idx) use ($limpiar): string {
        return isset($arr[$idx]) ? $limpiar($arr[$idx]) : '';
    };

    $esTexto = static function (string $valor): bool {
        return preg_match('/[A-Za-zÁÉÍÓÚÑáéíóúñ]/u', $valor) === 1;
    };

    $cedulaIndex = null;
    foreach ($partes as $i => $valor) {
        if ($i < 2) {
            continue;
        }

        $soloNumeros = preg_replace('/\D+/', '', (string) $valor);
        $largo = strlen($soloNumeros);
        if ($soloNumeros !== '' && $largo >= 5 && $largo <= 12 && $esTexto($obtener($partes, $i - 1)) && $esTexto($obtener($partes, $i - 2))) {
            $cedulaIndex = $i;
            break;
        }
    }

    if ($cedulaIndex === null && count($partes) >= 12) {
        $primero = $obtener($partes, 0);
        $segundo = strtolower($obtener($partes, 1));
        $esFechaInicial = preg_match('/^\s*\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $primero) === 1;
        $esAcepta = in_array($segundo, ['aceptar', 'acepta', 'si', 'sí', 'no', '0', '1'], true);
        if ($esFechaInicial || $esAcepta) {
            $cedulaIndex = 4;
        }
    }

    if ($cedulaIndex === null && count($partes) >= 10) {
        $cedulaIndex = 2;
    }

    if ($cedulaIndex === null || $cedulaIndex < 2) {
        return [];
    }

    return [
        'Nombres' => $obtener($partes, $cedulaIndex - 2),
        'Apellidos' => $obtener($partes, $cedulaIndex - 1),
        'Numero_Cedula' => $obtener($partes, $cedulaIndex),
        'Telefono' => $obtener($partes, $cedulaIndex + 1),
        'Lider_Nehemias' => $obtener($partes, $cedulaIndex + 2),
        'Lider' => $obtener($partes, $cedulaIndex + 3),
        'Subido_Link' => $obtener($partes, $cedulaIndex + 4),
        'En_Bogota_Subio' => $obtener($partes, $cedulaIndex + 5),
        'Puesto_Votacion' => $obtener($partes, $cedulaIndex + 6),
        'Mesa_Votacion' => $obtener($partes, $cedulaIndex + 7)
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $sqlCount = "SELECT COUNT(*) AS total
                 FROM nehemias
                 WHERE (Apellidos IS NULL OR Apellidos = '')
                   AND (Numero_Cedula IS NULL OR Numero_Cedula = '')
                   AND (Nombres LIKE '%;%' OR Nombres LIKE '%,%' OR Nombres LIKE '%\t%')";
    $resCount = $conn->query($sqlCount);
    $total = 0;
    if ($resCount && $row = $resCount->fetch_assoc()) {
        $total = (int) $row['total'];
    }

    echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Reparar Importación Nehemias</title>
    <style>body{font-family:Arial;padding:20px;background:#f5f5f5}.box{max-width:900px;margin:auto;background:#fff;padding:20px;border-radius:8px}button{background:#f37021;color:#fff;border:0;padding:10px 16px;border-radius:6px;cursor:pointer}</style>
    </head><body><div class='box'>";
    echo "<h1>Reparar registros mal importados</h1>";
    echo "<p>Registros candidatos detectados: <strong>{$total}</strong></p>";
    echo "<p>Este proceso intentará descomponer el valor de <strong>Nombres</strong> y mover cada dato a su columna correcta.</p>";
    echo "<form method='POST'><button type='submit' name='reparar' value='1'>Ejecutar reparación</button></form>";
    echo "</div></body></html>";
    $conn->close();
    exit;
}

$errores = [];
$actualizados = 0;
$omitidos = 0;

$sql = "SELECT Id_Nehemias, Nombres
        FROM nehemias
        WHERE (Apellidos IS NULL OR Apellidos = '')
          AND (Numero_Cedula IS NULL OR Numero_Cedula = '')
          AND (Nombres LIKE '%;%' OR Nombres LIKE '%,%' OR Nombres LIKE '%\t%')";

$result = $conn->query($sql);
if (!$result) {
    die('Error consultando registros: ' . $conn->error);
}

$updateSql = "UPDATE nehemias
              SET Nombres = ?,
                  Apellidos = ?,
                  Numero_Cedula = ?,
                  Telefono = ?,
                  Lider_Nehemias = ?,
                  Lider = ?,
                  Subido_Link = ?,
                  En_Bogota_Subio = ?,
                  Puesto_Votacion = ?,
                  Mesa_Votacion = ?
              WHERE Id_Nehemias = ?";
$updateStmt = $conn->prepare($updateSql);
if (!$updateStmt) {
    die('Error preparando actualización: ' . $conn->error);
}

while ($row = $result->fetch_assoc()) {
    $id = (int) $row['Id_Nehemias'];
    $raw = (string) $row['Nombres'];

    $del = detectarDelimitador($raw);
    $partes = str_getcsv($raw, $del);
    $mapeado = mapearPartes($partes);

    if (empty($mapeado)) {
        $omitidos++;
        $errores[] = "ID {$id}: no se pudo identificar la cédula para mapear columnas";
        continue;
    }

    $updateStmt->bind_param(
        'ssssssssssi',
        $mapeado['Nombres'],
        $mapeado['Apellidos'],
        $mapeado['Numero_Cedula'],
        $mapeado['Telefono'],
        $mapeado['Lider_Nehemias'],
        $mapeado['Lider'],
        $mapeado['Subido_Link'],
        $mapeado['En_Bogota_Subio'],
        $mapeado['Puesto_Votacion'],
        $mapeado['Mesa_Votacion'],
        $id
    );

    if ($updateStmt->execute()) {
        $actualizados++;
    } else {
        $omitidos++;
        $errores[] = "ID {$id}: error al actualizar - " . $updateStmt->error;
    }
}

$updateStmt->close();
$conn->close();

echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>Resultado reparación</title>
<style>body{font-family:Arial;padding:20px;background:#f5f5f5}.box{max-width:900px;margin:auto;background:#fff;padding:20px;border-radius:8px}.ok{color:green}.warn{color:#a76b00}.err{color:#b00020}</style>
</head><body><div class='box'>";
echo "<h1>Resultado de reparación</h1>";
echo "<p class='ok'>Registros actualizados: <strong>{$actualizados}</strong></p>";
echo "<p class='warn'>Registros omitidos: <strong>{$omitidos}</strong></p>";
if (!empty($errores)) {
    echo "<h3>Detalle (primeros 50):</h3><ul>";
    foreach (array_slice($errores, 0, 50) as $e) {
        echo "<li class='err'>" . htmlspecialchars($e, ENT_QUOTES, 'UTF-8') . "</li>";
    }
    echo "</ul>";
}
echo "<p><a href='?url=nehemias/lista'>Volver a lista Nehemias</a></p>";
echo "</div></body></html>";
