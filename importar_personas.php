<?php
/**
 * Importador de personas desde Excel/CSV
 * - Soporta: .xlsx, .csv, .txt
 * - Evita duplicados por: Numero_Documento, Email y Telefono
 */

require_once __DIR__ . '/conexion.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function normalizarEncabezadoImport($valor) {
    $valor = trim((string)$valor);
    $valor = str_replace(
        ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
        ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
        $valor
    );
    $valor = strtolower($valor);
    $valor = str_replace(['_', '-', '.', '/', '\\'], ' ', $valor);
    $valor = preg_replace('/[^a-z0-9\s]/', ' ', $valor);
    $valor = preg_replace('/\s+/', ' ', $valor);
    return trim((string)$valor);
}

function normalizarDocumentoImport($valor) {
    $valor = trim((string)$valor);
    $valor = preg_replace('/\D+/', '', $valor);
    return trim((string)$valor);
}

function normalizarTelefonoImport($valor) {
    $valor = trim((string)$valor);
    $valor = preg_replace('/\D+/', '', $valor);
    return trim((string)$valor);
}

function normalizarEmailImport($valor) {
    return strtolower(trim((string)$valor));
}

function excelColToIndexImport($letters) {
    $letters = strtoupper((string)$letters);
    $len = strlen($letters);
    $index = 0;
    for ($i = 0; $i < $len; $i++) {
        $index = $index * 26 + (ord($letters[$i]) - 64);
    }
    return max($index - 1, 0);
}

function leerFilasCsvImport($archivoTmp) {
    $filas = [];
    $handle = fopen($archivoTmp, 'r');
    if ($handle === false) {
        throw new Exception('No se pudo abrir el archivo CSV');
    }

    $primeraLinea = fgets($handle);
    rewind($handle);

    $delimitador = ',';
    $conteoComa = substr_count((string)$primeraLinea, ',');
    $conteoPuntoComa = substr_count((string)$primeraLinea, ';');
    $conteoTab = substr_count((string)$primeraLinea, "\t");

    if ($conteoTab >= $conteoComa && $conteoTab >= $conteoPuntoComa) {
        $delimitador = "\t";
    } elseif ($conteoPuntoComa > $conteoComa) {
        $delimitador = ';';
    }

    while (($data = fgetcsv($handle, 20000, $delimitador)) !== false) {
        if (!is_array($data)) {
            continue;
        }

        if (isset($data[0])) {
            $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$data[0]);
        }

        $filas[] = array_map(static function ($v) {
            return trim((string)$v);
        }, $data);
    }

    fclose($handle);
    return $filas;
}

function parsearFilasSheetXmlImport($sheetXml, $sharedStrings) {
    $sx = @simplexml_load_string($sheetXml);
    if ($sx === false) {
        return [];
    }

    $filas = [];
    $rows = $sx->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]');
    if ($rows === false) {
        return [];
    }

    foreach ($rows as $row) {
        $linea = [];
        $cells = $row->xpath('./*[local-name()="c"]');
        if ($cells === false) {
            continue;
        }

        foreach ($cells as $cell) {
            $cellRef = (string)$cell['r'];
            $cellType = (string)$cell['t'];
            $colLetters = preg_replace('/\d+/', '', $cellRef);
            $colIndex = excelColToIndexImport($colLetters);

            $value = '';
            if ($cellType === 's') {
                $vNode = $cell->xpath('./*[local-name()="v"]');
                $idx = isset($vNode[0]) ? (int)$vNode[0] : 0;
                $value = $sharedStrings[$idx] ?? '';
            } elseif ($cellType === 'inlineStr') {
                $textNodes = $cell->xpath('.//*[local-name()="is"]//*[local-name()="t"]');
                if ($textNodes !== false && !empty($textNodes)) {
                    $inlineText = '';
                    foreach ($textNodes as $tn) {
                        $inlineText .= (string)$tn;
                    }
                    $value = $inlineText;
                }
            } elseif ($cellType === 'str') {
                $vNode = $cell->xpath('./*[local-name()="v"]');
                $value = isset($vNode[0]) ? (string)$vNode[0] : '';
            } else {
                $vNode = $cell->xpath('./*[local-name()="v"]');
                $value = isset($vNode[0]) ? (string)$vNode[0] : '';
            }

            $linea[$colIndex] = trim((string)$value);
        }

        if (!empty($linea)) {
            ksort($linea);
            $maxIndex = max(array_keys($linea));
            $rowCompleta = [];
            for ($i = 0; $i <= $maxIndex; $i++) {
                $rowCompleta[] = $linea[$i] ?? '';
            }
            $filas[] = $rowCompleta;
        }
    }

    return $filas;
}

function leerFilasXlsxImport($archivoTmp) {
    if (!class_exists('ZipArchive')) {
        throw new Exception('La extensión ZipArchive no está disponible en PHP');
    }

    $zip = new ZipArchive();
    if ($zip->open($archivoTmp) !== true) {
        throw new Exception('No se pudo abrir el archivo XLSX');
    }

    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sx = @simplexml_load_string($sharedXml);
        if ($sx !== false) {
            $items = $sx->xpath('//*[local-name()="si"]');
            if ($items !== false) {
                foreach ($items as $si) {
                    $text = '';
                    $textNodes = $si->xpath('.//*[local-name()="t"]');
                    if ($textNodes !== false) {
                        foreach ($textNodes as $tn) {
                            $text .= (string)$tn;
                        }
                    }
                    $sharedStrings[] = trim($text);
                }
            }
        }
    }

    $sheetPaths = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos((string)$name, 'xl/worksheets/') === 0 && substr((string)$name, -4) === '.xml') {
            $sheetPaths[] = (string)$name;
        }
    }

    if (empty($sheetPaths)) {
        $zip->close();
        throw new Exception('No se encontró ninguna hoja en el archivo XLSX');
    }

    sort($sheetPaths);
    $sheetXml = $zip->getFromName($sheetPaths[0]);
    $zip->close();

    if ($sheetXml === false) {
        throw new Exception('No se pudo leer la hoja principal del XLSX');
    }

    return parsearFilasSheetXmlImport($sheetXml, $sharedStrings);
}

function leerFilasArchivoImport($archivoTmp, $archivoNombre) {
    $extension = strtolower(pathinfo((string)$archivoNombre, PATHINFO_EXTENSION));

    if ($extension === 'xlsx') {
        return leerFilasXlsxImport($archivoTmp);
    }

    if (in_array($extension, ['csv', 'txt'], true)) {
        return leerFilasCsvImport($archivoTmp);
    }

    throw new Exception('Formato no soportado. Use .xlsx, .csv o .txt');
}

function detectarIndiceEncabezadoImport($filas) {
    $tokens = ['nombre', 'apellido', 'documento', 'cedula', 'telefono', 'email', 'correo'];
    $limite = min(count($filas), 30);
    $mejorIndice = null;
    $mejorPuntaje = -1;

    for ($i = 0; $i < $limite; $i++) {
        $fila = (array)$filas[$i];
        if (empty($fila)) {
            continue;
        }

        $puntaje = 0;
        foreach ($fila as $celda) {
            $normalizada = normalizarEncabezadoImport($celda);
            if ($normalizada === '') {
                continue;
            }
            foreach ($tokens as $token) {
                if (strpos($normalizada, $token) !== false) {
                    $puntaje += 1;
                    break;
                }
            }
        }

        if ($puntaje > $mejorPuntaje) {
            $mejorPuntaje = $puntaje;
            $mejorIndice = $i;
        }
    }

    if ($mejorPuntaje >= 2) {
        return $mejorIndice;
    }

    return 0;
}

function construirMapaEncabezadosImport($encabezados) {
    $map = [];
    foreach ((array)$encabezados as $idx => $h) {
        $key = normalizarEncabezadoImport($h);
        if ($key !== '') {
            $map[$key] = (int)$idx;
        }
    }
    return $map;
}

function obtenerValorAliasImport($fila, $mapEncabezados, $aliases = [], $fallbackIndex = null) {
    foreach ($aliases as $alias) {
        $key = normalizarEncabezadoImport($alias);
        if (isset($mapEncabezados[$key])) {
            return trim((string)($fila[$mapEncabezados[$key]] ?? ''));
        }
    }

    if ($fallbackIndex !== null) {
        return trim((string)($fila[$fallbackIndex] ?? ''));
    }

    return '';
}

function normalizarGeneroImport($valor) {
    $v = normalizarEncabezadoImport($valor);
    $map = [
        'hombre' => 'Hombre',
        'mujer' => 'Mujer',
        'joven hombre' => 'Joven Hombre',
        'joven mujer' => 'Joven Mujer'
    ];
    return $map[$v] ?? null;
}

function normalizarHoraLlamadaImport($valor) {
    $v = normalizarEncabezadoImport($valor);
    $map = [
        'manana' => 'Mañana',
        'medio dia' => 'Medio Dia',
        'tarde' => 'Tarde',
        'noche' => 'Noche',
        'cualquier hora' => 'Cualquier Hora'
    ];
    return $map[$v] ?? null;
}

function normalizarTipoDocumentoImport($valor) {
    $v = normalizarEncabezadoImport($valor);
    $map = [
        'registro civil' => 'Registro Civil',
        'cedula de ciudadania' => 'Cedula de Ciudadania',
        'cedula ciudadania' => 'Cedula de Ciudadania',
        'cc' => 'Cedula de Ciudadania',
        'cedula extranjera' => 'Cedula Extranjera',
        'ce' => 'Cedula Extranjera'
    ];
    return $map[$v] ?? 'Cedula de Ciudadania';
}

function normalizarTipoReunionImport($valor) {
    $v = normalizarEncabezadoImport($valor);
    $map = [
        'domingo' => 'Domingo',
        'celula' => 'Celula',
        'reu jovenes' => 'Reu Jovenes',
        'reunion jovenes' => 'Reu Jovenes',
        'reu hombre' => 'Reu Hombre',
        'reunion hombres' => 'Reu Hombre',
        'reu mujeres' => 'Reu Mujeres',
        'reunion mujeres' => 'Reu Mujeres',
        'grupo go' => 'Grupo Go',
        'seminario' => 'Seminario',
        'pesca' => 'Pesca',
        'semana santa' => 'Semana Santa',
        'otro' => 'Otro'
    ];
    return $map[$v] ?? null;
}

function normalizarFechaImport($valor) {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return null;
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        return $valor;
    }

    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/', $valor, $m)) {
        $d = (int)$m[1];
        $mes = (int)$m[2];
        $anio = (int)$m[3];
        if ($anio < 100) {
            $anio += 2000;
        }
        if (checkdate($mes, $d, $anio)) {
            return sprintf('%04d-%02d-%02d', $anio, $mes, $d);
        }
    }

    if (is_numeric($valor)) {
        $serial = (int)$valor;
        if ($serial > 59) {
            $serial -= 1;
        }
        $timestamp = ($serial - 25569) * 86400;
        if ($timestamp > 0) {
            return gmdate('Y-m-d', $timestamp);
        }
    }

    return null;
}

function obtenerColumnasTablaImport(PDO $pdo, $tabla) {
    $stmt = $pdo->query('SHOW COLUMNS FROM ' . $tabla);
    $columns = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        $columns[] = $col['Field'];
    }
    return $columns;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Importar Personas</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 980px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 24px; }
        h1 { margin-top: 0; color: #0078D4; }
        .btn { background: #f37021; color: #fff; border: 0; border-radius: 6px; padding: 10px 16px; cursor: pointer; }
        .btn:hover { background: #d85f12; }
        .ok { color: #1f7a1f; }
        .warn { color: #946200; }
        .err { color: #b00020; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #0078D4; color: #fff; }
        .box { background: #f8f9fa; border: 1px solid #e2e6ea; border-radius: 8px; padding: 12px; margin: 10px 0; }
        ul { margin-top: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Importar Personas desde Excel / CSV</h1>
    <div class="box">
        Se importa a la tabla <strong>persona</strong> y se evitan duplicados por <strong>cédula, email o teléfono</strong>.
        Si un registro ya existe, se omite automáticamente.
    </div>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    try {
        if (!isset($_FILES['archivo']['tmp_name']) || !is_uploaded_file($_FILES['archivo']['tmp_name'])) {
            throw new Exception('No se recibió un archivo válido');
        }

        $archivoTmp = $_FILES['archivo']['tmp_name'];
        $archivoNombre = $_FILES['archivo']['name'] ?? 'archivo';
        $filas = leerFilasArchivoImport($archivoTmp, $archivoNombre);

        if (empty($filas)) {
            throw new Exception('El archivo está vacío o no se pudieron leer filas');
        }

        $indiceHeader = detectarIndiceEncabezadoImport($filas);
        $encabezados = $filas[$indiceHeader] ?? [];
        $mapEncabezados = construirMapaEncabezadosImport($encabezados);

        $columnasTabla = obtenerColumnasTablaImport($pdo, 'persona');

        $baseInsert = [
            'Nombre', 'Apellido', 'Tipo_Documento', 'Numero_Documento', 'Fecha_Nacimiento', 'Edad',
            'Genero', 'Telefono', 'Email', 'Hora_Llamada', 'Direccion', 'Barrio', 'Peticion',
            'Tipo_Reunion', 'Id_Lider', 'Id_Celula', 'Id_Rol', 'Id_Ministerio', 'Fecha_Registro', 'Fecha_Registro_Unix'
        ];
        $insertColumns = array_values(array_filter($baseInsert, static function ($col) use ($columnasTabla) {
            return in_array($col, $columnasTabla, true);
        }));

        if (!in_array('Nombre', $insertColumns, true) || !in_array('Apellido', $insertColumns, true)) {
            throw new Exception('La tabla persona no contiene columnas básicas esperadas');
        }

        $placeholders = implode(', ', array_fill(0, count($insertColumns), '?'));
        $sqlInsert = 'INSERT INTO persona (' . implode(', ', $insertColumns) . ') VALUES (' . $placeholders . ')';
        $stmtInsert = $pdo->prepare($sqlInsert);

        $existentes = $pdo->query('SELECT Numero_Documento, Email, Telefono FROM persona')->fetchAll(PDO::FETCH_ASSOC);
        $docsExistentes = [];
        $emailsExistentes = [];
        $telefonosExistentes = [];
        foreach ($existentes as $ex) {
            $d = normalizarDocumentoImport($ex['Numero_Documento'] ?? '');
            $e = normalizarEmailImport($ex['Email'] ?? '');
            $t = normalizarTelefonoImport($ex['Telefono'] ?? '');
            if ($d !== '') { $docsExistentes[$d] = true; }
            if ($e !== '') { $emailsExistentes[$e] = true; }
            if ($t !== '') { $telefonosExistentes[$t] = true; }
        }

        $docsArchivo = [];
        $emailsArchivo = [];
        $telefonosArchivo = [];

        $procesados = 0;
        $insertados = 0;
        $omitidosDuplicado = 0;
        $omitidosDupCedula = 0;
        $omitidosDupCorreo = 0;
        $omitidosDupTelefono = 0;
        $omitidosVacios = 0;
        $errores = [];
        $detalleDuplicados = [];
        $detalleInsertados = [];

        $inicioDatos = $indiceHeader + 1;
        $totalFilas = count($filas);

        $pdo->beginTransaction();

        for ($i = $inicioDatos; $i < $totalFilas; $i++) {
            $fila = (array)$filas[$i];
            $procesados++;

            $nombre = obtenerValorAliasImport($fila, $mapEncabezados, ['nombre', 'nombres'], 0);
            $apellido = obtenerValorAliasImport($fila, $mapEncabezados, ['apellido', 'apellidos'], 1);
            $tipoDocumento = obtenerValorAliasImport($fila, $mapEncabezados, ['tipo documento', 'tipo de documento']);
            $numeroDocumentoRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['numero documento', 'n documento', 'documento', 'cedula', 'no cedula'], 2);
            $telefonoRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['telefono', 'celular', 'movil'], 3);
            $emailRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['email', 'correo', 'correo electronico']);
            $fechaNacimientoRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['fecha nacimiento', 'fecha de nacimiento']);
            $edadRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['edad']);
            $generoRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['genero', 'sexo']);
            $horaLlamadaRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['hora llamada', 'hora de llamada']);
            $direccionRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['direccion']);
            $barrioRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['barrio']);
            $peticionRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['peticion']);
            $tipoReunionRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['tipo reunion', 'tipo de reunion']);
            $idLiderRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['id lider']);
            $idCelulaRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['id celula']);
            $idRolRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['id rol']);
            $idMinisterioRaw = obtenerValorAliasImport($fila, $mapEncabezados, ['id ministerio']);

            $numeroDocumento = normalizarDocumentoImport($numeroDocumentoRaw);
            $telefono = normalizarTelefonoImport($telefonoRaw);
            $email = normalizarEmailImport($emailRaw);

            $filaTieneDatos = false;
            foreach ($fila as $celdaFila) {
                if (trim((string)$celdaFila) !== '') {
                    $filaTieneDatos = true;
                    break;
                }
            }

            if (!$filaTieneDatos) {
                $omitidosVacios++;
                continue;
            }

            if ($nombre === '') {
                $nombre = 'Sin nombre';
            }

            if ($apellido === '') {
                $apellido = 'Sin apellido';
            }

            $esDuplicado = false;
            $motivoDuplicado = '';

            // Regla anti-duplicados por cualquiera de estos campos:
            // cédula, email o teléfono.
            if ($numeroDocumento !== '' && (isset($docsExistentes[$numeroDocumento]) || isset($docsArchivo[$numeroDocumento]))) {
                $esDuplicado = true;
                $motivoDuplicado = 'cedula';
            }
            if (!$esDuplicado && $email !== '' && (isset($emailsExistentes[$email]) || isset($emailsArchivo[$email]))) {
                $esDuplicado = true;
                $motivoDuplicado = 'correo';
            }
            if (!$esDuplicado && $telefono !== '' && (isset($telefonosExistentes[$telefono]) || isset($telefonosArchivo[$telefono]))) {
                $esDuplicado = true;
                $motivoDuplicado = 'telefono';
            }

            if ($esDuplicado) {
                $omitidosDuplicado++;
                if ($motivoDuplicado === 'cedula') {
                    $omitidosDupCedula++;
                } elseif ($motivoDuplicado === 'correo') {
                    $omitidosDupCorreo++;
                } elseif ($motivoDuplicado === 'telefono') {
                    $omitidosDupTelefono++;
                }

                $detalleDuplicados[] = [
                    'fila' => $i + 1,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'cedula' => $numeroDocumento,
                    'correo' => $email,
                    'telefono' => $telefono,
                    'motivo' => $motivoDuplicado
                ];
                continue;
            }

            $fechaNacimiento = normalizarFechaImport($fechaNacimientoRaw);
            $edad = is_numeric($edadRaw) ? (int)$edadRaw : null;
            if ($edad !== null && $edad <= 0) {
                $edad = null;
            }

            $genero = normalizarGeneroImport($generoRaw);
            $horaLlamada = normalizarHoraLlamadaImport($horaLlamadaRaw);
            $tipoDocFinal = normalizarTipoDocumentoImport($tipoDocumento);
            $tipoReunion = normalizarTipoReunionImport($tipoReunionRaw);

            $idLider = is_numeric($idLiderRaw) ? (int)$idLiderRaw : null;
            $idCelula = is_numeric($idCelulaRaw) ? (int)$idCelulaRaw : null;
            $idRol = is_numeric($idRolRaw) ? (int)$idRolRaw : null;
            $idMinisterio = is_numeric($idMinisterioRaw) ? (int)$idMinisterioRaw : null;

            $data = [
                'Nombre' => $nombre,
                'Apellido' => $apellido,
                'Tipo_Documento' => $tipoDocFinal,
                'Numero_Documento' => $numeroDocumento !== '' ? $numeroDocumento : null,
                'Fecha_Nacimiento' => $fechaNacimiento,
                'Edad' => $edad,
                'Genero' => $genero,
                'Telefono' => $telefono !== '' ? $telefono : null,
                'Email' => $email !== '' ? $email : null,
                'Hora_Llamada' => $horaLlamada,
                'Direccion' => trim((string)$direccionRaw) !== '' ? trim((string)$direccionRaw) : null,
                'Barrio' => trim((string)$barrioRaw) !== '' ? trim((string)$barrioRaw) : null,
                'Peticion' => trim((string)$peticionRaw) !== '' ? trim((string)$peticionRaw) : null,
                'Tipo_Reunion' => $tipoReunion,
                'Id_Lider' => $idLider,
                'Id_Celula' => $idCelula,
                'Id_Rol' => $idRol,
                'Id_Ministerio' => $idMinisterio,
                'Fecha_Registro' => date('Y-m-d H:i:s'),
                'Fecha_Registro_Unix' => time()
            ];

            $params = [];
            foreach ($insertColumns as $col) {
                $params[] = $data[$col] ?? null;
            }

            try {
                $stmtInsert->execute($params);
                $insertados++;

                $detalleInsertados[] = [
                    'fila' => $i + 1,
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'cedula' => $numeroDocumento,
                    'correo' => $email,
                    'telefono' => $telefono
                ];

                if ($numeroDocumento !== '') { $docsArchivo[$numeroDocumento] = true; }
                if ($email !== '') { $emailsArchivo[$email] = true; }
                if ($telefono !== '') { $telefonosArchivo[$telefono] = true; }
            } catch (Throwable $e) {
                $errores[] = 'Fila ' . ($i + 1) . ': ' . $e->getMessage();
            }
        }

        $pdo->commit();

        echo '<h2>Resultado de importación</h2>';
        echo '<table>';
        echo '<tr><th>Concepto</th><th>Cantidad</th></tr>';
        echo '<tr><td>Filas procesadas</td><td>' . h($procesados) . '</td></tr>';
        echo '<tr><td class="ok">Insertadas</td><td>' . h($insertados) . '</td></tr>';
        echo '<tr><td class="warn">Omitidas por duplicado</td><td>' . h($omitidosDuplicado) . '</td></tr>';
        echo '<tr><td>&nbsp;&nbsp;↳ Duplicado por cédula</td><td>' . h($omitidosDupCedula) . '</td></tr>';
        echo '<tr><td>&nbsp;&nbsp;↳ Duplicado por correo</td><td>' . h($omitidosDupCorreo) . '</td></tr>';
        echo '<tr><td>&nbsp;&nbsp;↳ Duplicado por teléfono</td><td>' . h($omitidosDupTelefono) . '</td></tr>';
        echo '<tr><td class="warn">Omitidas por fila totalmente vacía</td><td>' . h($omitidosVacios) . '</td></tr>';
        echo '<tr><td class="err">Errores</td><td>' . h(count($errores)) . '</td></tr>';
        echo '</table>';

        if (!empty($detalleDuplicados)) {
            echo '<h3>Personas omitidas por duplicado</h3>';
            echo '<table>';
            echo '<tr>';
            echo '<th>Fila</th><th>Nombre</th><th>Apellido</th><th>Cédula</th><th>Correo</th><th>Teléfono</th><th>Motivo</th>';
            echo '</tr>';

            $limiteDetalleDuplicados = min(count($detalleDuplicados), 300);
            for ($d = 0; $d < $limiteDetalleDuplicados; $d++) {
                $item = $detalleDuplicados[$d];
                echo '<tr>';
                echo '<td>' . h($item['fila']) . '</td>';
                echo '<td>' . h($item['nombre']) . '</td>';
                echo '<td>' . h($item['apellido']) . '</td>';
                echo '<td>' . h($item['cedula']) . '</td>';
                echo '<td>' . h($item['correo']) . '</td>';
                echo '<td>' . h($item['telefono']) . '</td>';

                $motivoTexto = $item['motivo'];
                if ($motivoTexto === 'cedula') {
                    $motivoTexto = 'Cédula repetida';
                } elseif ($motivoTexto === 'correo') {
                    $motivoTexto = 'Correo repetido';
                } elseif ($motivoTexto === 'telefono') {
                    $motivoTexto = 'Teléfono repetido';
                }

                echo '<td>' . h($motivoTexto) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
            if (count($detalleDuplicados) > $limiteDetalleDuplicados) {
                echo '<p class="warn">Mostrando ' . h($limiteDetalleDuplicados) . ' de ' . h(count($detalleDuplicados)) . ' duplicados.</p>';
            }
        }

        if (!empty($detalleInsertados)) {
            echo '<h3>Personas insertadas</h3>';
            echo '<table>';
            echo '<tr>';
            echo '<th>Fila</th><th>Nombre</th><th>Apellido</th><th>Cédula</th><th>Correo</th><th>Teléfono</th>';
            echo '</tr>';

            $limiteDetalleInsertados = min(count($detalleInsertados), 300);
            for ($d = 0; $d < $limiteDetalleInsertados; $d++) {
                $item = $detalleInsertados[$d];
                echo '<tr>';
                echo '<td>' . h($item['fila']) . '</td>';
                echo '<td>' . h($item['nombre']) . '</td>';
                echo '<td>' . h($item['apellido']) . '</td>';
                echo '<td>' . h($item['cedula']) . '</td>';
                echo '<td>' . h($item['correo']) . '</td>';
                echo '<td>' . h($item['telefono']) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
            if (count($detalleInsertados) > $limiteDetalleInsertados) {
                echo '<p class="warn">Mostrando ' . h($limiteDetalleInsertados) . ' de ' . h(count($detalleInsertados)) . ' insertados.</p>';
            }
        }

        if (!empty($errores)) {
            echo '<div class="box">';
            echo '<strong>Primeros errores:</strong>';
            echo '<ul>';
            foreach (array_slice($errores, 0, 30) as $error) {
                echo '<li class="err">' . h($error) . '</li>';
            }
            if (count($errores) > 30) {
                echo '<li>... y ' . h(count($errores) - 30) . ' errores más.</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo '<p class="err"><strong>Error:</strong> ' . h($e->getMessage()) . '</p>';
    }

    echo '<p><a href="importar_personas.php" class="btn">Importar otro archivo</a></p>';
} else {
    ?>
    <div class="box">
        <strong>Formato recomendado:</strong>
        <ul>
            <li>Se permiten datos vacíos: si faltan <strong>Nombre</strong> o <strong>Apellido</strong> se guardan como <strong>Sin nombre</strong> y <strong>Sin apellido</strong>.</li>
            <li>Acepta alias comunes de encabezado: por ejemplo <em>Nombres</em>, <em>Apellidos</em>, <em>Cédula</em>, <em>Correo</em>.</li>
            <li>Formatos de archivo: <strong>.xlsx</strong>, <strong>.csv</strong>, <strong>.txt</strong>.</li>
        </ul>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <p>
            <label for="archivo"><strong>Seleccione el archivo:</strong></label><br>
            <input type="file" id="archivo" name="archivo" accept=".xlsx,.csv,.txt" required>
        </p>
        <p>
            <button type="submit" class="btn">Importar a PERSONA</button>
        </p>
    </form>
    <?php
}
?>
</div>
</body>
</html>
