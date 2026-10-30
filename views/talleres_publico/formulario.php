<?php
require_once APP . '/Models/TallerFormulario.php';

$tituloPagina = (string)($formulario['Titulo'] ?? 'Formulario');

$slug = (string)($formulario['Slug'] ?? '');

$descripcion = trim((string)($formulario['Descripcion'] ?? ''));

$mensajeGracias = trim((string)($formulario['Mensaje_Gracias'] ?? ''));

if ($mensajeGracias === '') {

    $mensajeGracias = '¡Gracias! Hemos recibido su información.';

}

$errores = is_array($errores ?? null) ? $errores : [];

$valores = is_array($valores ?? null) ? $valores : [];

function taller_valor($valores, $clave, $default = '') {

    $v = $valores[$clave] ?? $default;

    return is_array($v) ? '' : (string)$v;

}



function taller_h($texto): string {

    if (is_array($texto) || is_object($texto)) {

        $texto = json_encode($texto, JSON_UNESCAPED_UNICODE);

        if ($texto === false) {

            $texto = '';

        }

    }

    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');

}



function taller_json_hidden(array $data): string {

    if (!is_array($data)) {

        return '[]';

    }

    $json = json_encode($data, JSON_UNESCAPED_UNICODE);

    return ($json !== false && $json !== '') ? $json : '[]';

}



function taller_tabla_valor_hidden(array $valores, string $nombre, array $filas): string {

    if (!array_key_exists($nombre, $valores)) {

        return taller_json_hidden($filas);

    }

    $raw = $valores[$nombre];

    if (is_string($raw)) {

        return $raw;

    }

    if (is_array($raw)) {

        return taller_json_hidden($raw);

    }

    return taller_json_hidden($filas);

}



function taller_celda_tabla($celda): string {

    if (is_array($celda) || is_object($celda)) {

        return '';

    }

    return (string)$celda;

}



$textoAutorizacion = trim((string)($texto_autorizacion ?? ''));

if ($textoAutorizacion === '') {

    require_once APP . '/Helpers/TallerAutorizacionSync.php';

    $textoAutorizacion = TallerAutorizacionSync::textoDefault();

}

$fechaFirmaDefault = taller_valor($valores, 'autorizacion_fecha', date('Y-m-d'));

$firmaGuardada = taller_valor($valores, 'autorizacion_firma');

$esPresentacionNinos = !empty($es_presentacion_ninos);

$esTourLevantate = !empty($es_tour_levantate);

$imagenHeader = trim((string)($formulario['Imagen_Header'] ?? ''));

$tieneBloqueDocumentos = false;
foreach (($bloques ?? []) as $itemBloqueDoc) {
    if ((string)(($itemBloqueDoc['bloque'] ?? [])['Tipo'] ?? '') === 'documentos') {
        $tieneBloqueDocumentos = true;
        break;
    }
}



function taller_render_campo($campo, $valores, $errores, $model) {

    $nombre = (string)($campo['Nombre_Campo'] ?? '');

    $etiqueta = (string)($campo['Etiqueta'] ?? $nombre);

    $tipo = strtolower((string)($campo['Tipo'] ?? 'text'));

    $requerido = !empty($campo['Requerido']);

    $placeholder = (string)($campo['Placeholder'] ?? '');

    $ayuda = (string)($campo['Ayuda'] ?? '');

    $opciones = $model->decodificarOpcionesCampo($campo);

    $columnas = $model->decodificarColumnasTabla($campo);

    $valor = $valores[$nombre] ?? '';

    $valorChecked = is_array($valor) ? $valor : [];

    if (!is_array($valor)) {

        $valor = (string)$valor;

    }

    $errorCampo = $errores[$nombre] ?? '';

    $fid = 'f_' . preg_replace('/[^a-z0-9_]/', '_', $nombre);

    ?>

    <div class="field">

        <label for="<?= htmlspecialchars($fid, ENT_QUOTES, 'UTF-8') ?>">

            <?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?>

            <?php if ($requerido): ?><span class="req">*</span><?php endif; ?>

        </label>



        <?php if ($tipo === 'textarea'): ?>

            <textarea class="form-control" id="<?= htmlspecialchars($fid, ENT_QUOTES, 'UTF-8') ?>"

                      name="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"

                      rows="4" <?= $requerido ? 'required' : '' ?>><?= htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8') ?></textarea>



        <?php elseif ($tipo === 'select'): ?>

            <select class="form-select" id="<?= htmlspecialchars($fid, ENT_QUOTES, 'UTF-8') ?>"

                    name="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>" <?= $requerido ? 'required' : '' ?>>

                <option value="">— Seleccione —</option>

                <?php foreach ($opciones as $opt): ?>

                <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>" <?= $valor === $opt ? 'selected' : '' ?>>

                    <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>

                </option>

                <?php endforeach; ?>

            </select>



        <?php elseif ($tipo === 'radio'): ?>

            <?php foreach ($opciones as $i => $opt): ?>

            <div class="form-check">

                <input class="form-check-input" type="radio"

                       name="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"

                       id="<?= htmlspecialchars($fid . '_' . $i, ENT_QUOTES, 'UTF-8') ?>"

                       value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"

                       <?= $valor === $opt ? 'checked' : '' ?> <?= $requerido ? 'required' : '' ?>>

                <label class="form-check-label" for="<?= htmlspecialchars($fid . '_' . $i, ENT_QUOTES, 'UTF-8') ?>">

                    <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>

                </label>

            </div>

            <?php endforeach; ?>



        <?php elseif ($tipo === 'checkbox'): ?>

            <?php foreach ($opciones as $i => $opt): ?>

            <div class="form-check">

                <input class="form-check-input" type="checkbox"

                       name="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>[]"

                       id="<?= htmlspecialchars($fid . '_' . $i, ENT_QUOTES, 'UTF-8') ?>"

                       value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"

                       <?= in_array($opt, $valorChecked, true) ? 'checked' : '' ?>>

                <label class="form-check-label" for="<?= htmlspecialchars($fid . '_' . $i, ENT_QUOTES, 'UTF-8') ?>">

                    <?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>

                </label>

            </div>

            <?php endforeach; ?>



        <?php elseif ($tipo === 'tabla'): ?>

            <?php

            $filas = [];

            if (is_string($valor) && $valor !== '') {

                $decoded = json_decode($valor, true);

                if (is_array($decoded)) $filas = $decoded;

            } elseif (is_array($valor)) {

                $filas = $valor;

            }

            if (empty($filas)) {

                $filas = [[]];

            }

            ?>

            <div class="tabla-campo" data-campo="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"

                 data-columnas="<?= taller_h(json_encode($columnas, JSON_UNESCAPED_UNICODE) ?: '[]') ?>">

                <div class="tabla-campo-body">

                    <?php foreach ($filas as $rIdx => $fila): ?>

                    <div class="tabla-fila" style="display:grid;grid-template-columns:repeat(<?= max(1, count($columnas)) ?>,1fr) auto;gap:8px;margin-bottom:8px;">

                        <?php foreach ($columnas as $col): ?>

                        <?php $colKey = preg_replace('/[^a-z0-9_]/i', '_', strtolower($col)); ?>

                        <input type="text" class="form-control form-control-sm tabla-celda"

                               data-col="<?= htmlspecialchars($col, ENT_QUOTES, 'UTF-8') ?>"

                               placeholder="<?= htmlspecialchars($col, ENT_QUOTES, 'UTF-8') ?>"

                               value="<?= taller_h(taller_celda_tabla($fila[$col] ?? $fila[$colKey] ?? '')) ?>">

                        <?php endforeach; ?>

                        <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-fila" title="Quitar fila">×</button>

                    </div>

                    <?php endforeach; ?>

                </div>

                <button type="button" class="btn btn-outline-secondary btn-sm btn-agregar-fila">+ Agregar fila</button>

                <input type="hidden" name="<?= taller_h($nombre) ?>" class="tabla-json-input"

                       value="<?= taller_h(taller_tabla_valor_hidden($valores, $nombre, $filas)) ?>">

            </div>



        <?php else: ?>

            <input class="form-control" type="<?= in_array($tipo, ['email','tel','number','date'], true) ? $tipo : 'text' ?>"

                   id="<?= htmlspecialchars($fid, ENT_QUOTES, 'UTF-8') ?>"

                   name="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"

                   value="<?= htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8') ?>"

                   <?= $requerido ? 'required' : '' ?>

                   placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>">

        <?php endif; ?>



        <?php if ($ayuda !== ''): ?><div class="help"><?= htmlspecialchars($ayuda, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <?php if ($errorCampo !== ''): ?><div class="err"><?= htmlspecialchars($errorCampo, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    </div>

    <?php

}



$model = new TallerFormulario();

$urlBuscarPersona = PUBLIC_URL . '?url=talleres_publico/buscar-persona';

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?> — MCI Madrid</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>

        body { background:#f3f7ff; min-height:100vh; padding:24px 16px; font-family:system-ui,sans-serif; }

        .wrap { max-width:720px; margin:0 auto; background:#fff; border-radius:12px; box-shadow:0 12px 40px rgba(0,0,0,.12); padding:32px; }

        h1 { color:#325fa9; font-size:1.6rem; margin-bottom:8px; }

        .desc { color:#64748b; margin-bottom:24px; }

        .bloque-seccion { margin:28px 0 8px; padding-top:8px; border-top:2px solid #e2e8f0; }

        .bloque-seccion h2 { color:#1e40af; font-size:1.15rem; margin-bottom:16px; }

        .field { margin-bottom:18px; }

        label { font-weight:600; font-size:.92rem; margin-bottom:6px; display:block; }

        .req { color:#dc2626; }

        .help { font-size:.82rem; color:#64748b; margin-top:4px; }

        .err { color:#b91c1c; font-size:.85rem; margin-top:4px; }

        .ok-box { text-align:center; padding:32px 16px; }

        .ok-box h2 { color:#166534; font-size:1.4rem; }

        .persona-aviso { background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:10px 12px; font-size:.88rem; color:#1e3a8a; margin-bottom:16px; }
        .campo-bloqueado { background:#f1f5f9 !important; color:#334155; cursor:not-allowed; }
        .campo-editable { background:#fff !important; cursor:text; }

        .buscar-doc { display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; margin-bottom:16px; }

        .buscar-doc .form-control { max-width:220px; }

        .auth-texto { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:16px; font-size:.92rem; line-height:1.5; }

        .firma-wrap { margin-top:8px; }

        .firma-canvas { width:100%; height:140px; border:2px dashed #94a3b8; border-radius:8px; background:#fff; touch-action:none; cursor:crosshair; }

        .firma-actions { display:flex; gap:8px; margin-top:8px; flex-wrap:wrap; }

        .doc-upload-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-top:8px; }
        .doc-pendientes-panel { margin-top:12px; border:1px solid #c5ddd9; border-radius:10px; background:#f7fcfb; padding:12px; }
        .doc-pendientes-header { display:flex; align-items:center; gap:10px; margin-bottom:10px; font-size:14px; color:#1f3d3a; }
        .doc-pendientes-badge { display:inline-flex; align-items:center; justify-content:center; min-width:28px; height:28px; padding:0 8px; border-radius:999px; background:#0a6e6a; color:#fff; font-weight:700; font-size:14px; }
        .doc-archivos-lista { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:8px; }
        .doc-archivo-item { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border:1px solid #d8ebe8; border-radius:10px; background:#fff; }
        .doc-archivo-info { min-width:0; flex:1; }
        .doc-archivo-nombre { display:block; font-size:14px; font-weight:600; color:#234542; word-break:break-word; }
        .doc-archivo-meta { display:block; font-size:12px; color:#64748b; margin-top:2px; }
        .doc-archivo-quitar { border:none; background:#fee2e2; color:#b91c1c; width:32px; height:32px; border-radius:8px; font-size:18px; line-height:1; cursor:pointer; flex-shrink:0; }
        .doc-pendientes-vacio { margin:0; font-size:13px; color:#64748b; }

        .tour-header-img { width:100%; max-height:320px; object-fit:cover; border-radius:12px; margin-bottom:20px; display:block; box-shadow:0 8px 24px rgba(15,23,42,.12); }

    </style>

</head>

<body>

<div class="wrap">

    <?php if (!empty($enviado_ok)): ?>

        <div class="ok-box">

            <?php if (($_GET['ok'] ?? '') === 'docs'): ?>
            <h2>Documentos cargados correctamente</h2>
            <p class="text-muted">Los archivos se adjuntaron a la inscripción existente. Puede cerrar esta ventana.</p>
            <?php else: ?>
            <h2><?= htmlspecialchars($mensajeGracias, ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="text-muted">Puede cerrar esta ventana.</p>
            <?php endif; ?>

        </div>

    <?php else: ?>

        <?php
        $urlImagenHeader = '';
        if ($imagenHeader !== '') {
            $urlImagenHeader = preg_match('#^https?://#i', $imagenHeader)
                ? $imagenHeader
                : (PUBLIC_URL . '/' . ltrim($imagenHeader, '/'));
        }
        ?>
        <?php if ($urlImagenHeader !== ''): ?>
        <img src="<?= htmlspecialchars($urlImagenHeader, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?>" class="tour-header-img">
        <?php endif; ?>

        <h1><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></h1>

        <?php if ($descripcion !== ''): ?>

        <p class="desc"><?= nl2br(htmlspecialchars($descripcion, ENT_QUOTES, 'UTF-8')) ?></p>

        <?php endif; ?>



        <?php if (!empty($enlace_crear_persona)): ?>
            <div class="persona-aviso">
                <a href="<?= htmlspecialchars((string)$enlace_crear_persona, ENT_QUOTES, 'UTF-8') ?>">Abrir el formulario de personas</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= PUBLIC_URL ?>?url=talleres_publico/guardar" id="form-taller-publico"<?= ($esPresentacionNinos || $tieneBloqueDocumentos) ? ' enctype="multipart/form-data"' : '' ?>>

            <input type="hidden" name="slug" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($esPresentacionNinos): ?>
            <input type="hidden" name="solo_subir_documentos" id="solo_subir_documentos" value="0">
            <input type="hidden" name="id_respuesta_existente" id="id_respuesta_existente" value="">
            <?php endif; ?>



            <?php foreach (($bloques ?? []) as $item): ?>

                <?php

                $bloque = $item['bloque'] ?? [];

                $camposBloque = $item['campos'] ?? [];

                $tipoBloque = (string)($bloque['Tipo'] ?? 'personalizado');

                $tituloBloque = (string)($bloque['Titulo'] ?? '');

                ?>

                <section class="bloque-seccion">

                    <h2><?= htmlspecialchars($tituloBloque, ENT_QUOTES, 'UTF-8') ?></h2>



                    <?php if ($tipoBloque === 'persona'): ?>

                        <?php if ($esTourLevantate): ?>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field">
                                <label for="persona_nombre">Nombre <span class="req">*</span></label>
                                <input class="form-control" type="text" id="persona_nombre" name="persona_nombre" required
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_nombre'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($errores['persona_nombre'])): ?><div class="err"><?= htmlspecialchars($errores['persona_nombre'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="persona_apellido">Apellido <span class="req">*</span></label>
                                <input class="form-control" type="text" id="persona_apellido" name="persona_apellido" required
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_apellido'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($errores['persona_apellido'])): ?><div class="err"><?= htmlspecialchars($errores['persona_apellido'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="field">
                            <label for="persona_documento">Número de cédula <span class="req">*</span></label>
                            <input class="form-control" type="text" id="persona_documento" name="persona_documento" required
                                   placeholder="Ej.: 1234567890"
                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_documento'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['persona_documento'])): ?><div class="err"><?= htmlspecialchars($errores['persona_documento'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="persona_telefono">Teléfono <span class="req">*</span></label>
                            <input class="form-control" type="tel" id="persona_telefono" name="persona_telefono" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_telefono'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['persona_telefono'])): ?><div class="err"><?= htmlspecialchars($errores['persona_telefono'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field">
                                <label for="persona_lider">Líder</label>
                                <input class="form-control" type="text" id="persona_lider" name="persona_lider"
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_lider'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="field">
                                <label for="persona_ministerio">Ministerio</label>
                                <input class="form-control" type="text" id="persona_ministerio" name="persona_ministerio"
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_ministerio'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>

                        <?php else: ?>

                        <div class="field">

                            <label for="persona_nombre">Nombre completo <span class="req">*</span></label>

                            <input class="form-control" type="text" id="persona_nombre" name="persona_nombre" required

                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_nombre'), ENT_QUOTES, 'UTF-8') ?>">

                            <?php if (!empty($errores['persona_nombre'])): ?><div class="err"><?= htmlspecialchars($errores['persona_nombre'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

                        </div>



                        <div class="field">

                            <label for="persona_documento">Documento de identidad <span class="req">*</span></label>

                            <input class="form-control" type="text" id="persona_documento" name="persona_documento" required

                                   placeholder="Ej.: 1234567890"

                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_documento'), ENT_QUOTES, 'UTF-8') ?>">

                            <?php if (!empty($errores['persona_documento'])): ?><div class="err"><?= htmlspecialchars($errores['persona_documento'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

                        </div>



                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">

                            <div class="field">

                                <label for="persona_fecha_nacimiento">Fecha de nacimiento</label>

                                <input class="form-control" type="date" id="persona_fecha_nacimiento" name="persona_fecha_nacimiento"

                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_fecha_nacimiento'), ENT_QUOTES, 'UTF-8') ?>">

                            </div>

                            <div class="field">

                                <label for="persona_edad">Edad</label>

                                <input class="form-control" type="number" id="persona_edad" name="persona_edad" min="0" max="120"

                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_edad'), ENT_QUOTES, 'UTF-8') ?>">

                            </div>

                        </div>



                        <div class="field">

                            <label for="persona_telefono">Teléfono de contacto <span class="req">*</span></label>

                            <input class="form-control" type="tel" id="persona_telefono" name="persona_telefono" required

                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_telefono'), ENT_QUOTES, 'UTF-8') ?>">

                            <?php if (!empty($errores['persona_telefono'])): ?><div class="err"><?= htmlspecialchars($errores['persona_telefono'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field">
                                <label for="persona_lider">Líder</label>
                                <input class="form-control" type="text" id="persona_lider" name="persona_lider"
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_lider'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <div class="field">
                                <label for="persona_ministerio">Ministerio</label>
                                <input class="form-control" type="text" id="persona_ministerio" name="persona_ministerio"
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_ministerio'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                        </div>



                        <?php endif; ?>



                    <?php elseif ($tipoBloque === 'padres'): ?>

                        <div class="persona-aviso">
                            Complete solo estos datos. La inscripción queda únicamente en Talleres; no se crea ficha en Personas.
                        </div>
                        <div class="field">
                            <label for="padres_nombre">Nombre de los padres <span class="req">*</span></label>
                            <input class="form-control" type="text" id="padres_nombre" name="padres_nombre" required
                                   placeholder="Ej.: Juan Pérez y María López"
                                   value="<?= htmlspecialchars(taller_valor($valores, 'padres_nombre'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['padres_nombre'])): ?><div class="err"><?= htmlspecialchars($errores['padres_nombre'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>

                    <?php elseif ($tipoBloque === 'nino'): ?>

                        <?php
                        $ministeriosTaller = is_array($ministerios_asignacion ?? null) ? $ministerios_asignacion : [];
                        $ministerioElegido = trim((string)taller_valor($valores, 'id_ministerio_taller'));
                        if ($ministerioElegido === '' && strcasecmp(trim((string)taller_valor($valores, 'persona_ministerio')), 'Sin ministerio asignado') === 0) {
                            $ministerioElegido = 'sin';
                        }
                        ?>
                        <div class="field">
                            <label for="nino_nombre">Nombre del niño(a) <span class="req">*</span></label>
                            <input class="form-control" type="text" id="nino_nombre" name="nino_nombre" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'nino_nombre'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['nino_nombre'])): ?><div class="err"><?= htmlspecialchars($errores['nino_nombre'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="nino_edad">Edad <span class="req">*</span></label>
                            <input class="form-control" type="number" id="nino_edad" name="nino_edad" min="0" max="18" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'nino_edad'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['nino_edad'])): ?><div class="err"><?= htmlspecialchars($errores['nino_edad'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="id_ministerio_taller">Ministerio <span class="req">*</span></label>
                            <select class="form-control" id="id_ministerio_taller" name="id_ministerio_taller" required>
                                <option value="">Seleccione…</option>
                                <option value="sin" <?= $ministerioElegido === 'sin' ? 'selected' : '' ?>>Sin ministerio</option>
                                <?php foreach ($ministeriosTaller as $minOpt): ?>
                                <?php $idMinOpt = (int)($minOpt['id'] ?? 0); ?>
                                <option value="<?= $idMinOpt ?>" <?= (string)$idMinOpt === $ministerioElegido ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)($minOpt['nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errores['id_ministerio_taller'])): ?><div class="err"><?= htmlspecialchars($errores['id_ministerio_taller'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>

                    <?php elseif ($tipoBloque === 'documentos'): ?>
                        <?php if (!$esPresentacionNinos): ?>
                        <?php include VIEWS . '/talleres_publico/_bloque_documentos_presentacion.php'; ?>
                        <?php endif; ?>

                    <?php elseif ($tipoBloque === 'autorizacion' && !$esTourLevantate): ?>

                        <div class="auth-texto"><?= nl2br(htmlspecialchars($textoAutorizacion, ENT_QUOTES, 'UTF-8')) ?></div>

                        <div class="field">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="autorizacion_acepto" id="autorizacion_acepto" value="1"
                                       <?= !empty($valores['autorizacion_acepto']) ? 'checked' : '' ?> required>
                                <label class="form-check-label" for="autorizacion_acepto">
                                    <?= $esPresentacionNinos
                                        ? 'Acepto el consentimiento de tratamiento de datos e imágenes'
                                        : 'Acepto la autorización de tratamiento de datos' ?> <span class="req">*</span>
                                </label>
                            </div>
                            <?php if (!empty($errores['autorizacion_acepto'])): ?><div class="err"><?= htmlspecialchars($errores['autorizacion_acepto'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>

                        <div class="field firma-wrap">
                            <label>Firma <span class="req">*</span></label>
                            <p class="help" style="margin-bottom:8px;">Dibuje su firma con el mouse o el dedo en el recuadro.</p>
                            <canvas id="firma-canvas" class="firma-canvas"></canvas>
                            <div class="firma-actions">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-limpiar-firma">Limpiar firma</button>
                            </div>
                            <input type="hidden" name="autorizacion_firma" id="autorizacion_firma"
                                   value="<?= htmlspecialchars($firmaGuardada, ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['autorizacion_firma'])): ?><div class="err"><?= htmlspecialchars($errores['autorizacion_firma'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>

                        <div class="field">
                            <label for="autorizacion_fecha">Fecha</label>
                            <input class="form-control" type="date" id="autorizacion_fecha" name="autorizacion_fecha"
                                   value="<?= htmlspecialchars($fechaFirmaDefault, ENT_QUOTES, 'UTF-8') ?>">
                        </div>



                    <?php else: ?>

                        <?php foreach ($camposBloque as $campo): ?>

                            <?php
                            $nombreCampoTour = (string)($campo['Nombre_Campo'] ?? '');
                            if ($esTourLevantate && $nombreCampoTour === 'desea_comprar_libro') {
                                echo '<div id="wrap-comprar-libro-tour" style="display:none;">';
                            }
                            taller_render_campo($campo, $valores, $errores, $model);
                            if ($esTourLevantate && $nombreCampoTour === 'desea_comprar_libro') {
                                echo '</div>';
                            }
                            ?>

                        <?php endforeach; ?>

                    <?php endif; ?>

                </section>

            <?php endforeach; ?>

            <div id="panel-ya-inscrito-taller" class="persona-aviso" style="display:none;background:#fef3c7;border-color:#fcd34d;color:#92400e;margin-top:12px;"></div>

            <button type="submit" class="btn btn-primary w-100" id="btn-enviar-formulario-taller" style="background:#325fa9;border-color:#325fa9;padding:12px;margin-top:12px;">
                Enviar inscripción
            </button>

        </form>

    <?php endif; ?>

</div>

            <?php if ($esPresentacionNinos && empty($enviado_ok)): ?>
<script>
(function() {
    const form = document.getElementById('form-taller-publico');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        const ministerio = document.getElementById('id_ministerio_taller');
        if (ministerio && !String(ministerio.value || '').trim()) {
            e.preventDefault();
            alert('Seleccione el ministerio o «Sin ministerio».');
            ministerio.focus();
            return;
        }
        if (typeof window.tallerGuardarFirma === 'function') {
            window.tallerGuardarFirma();
        }
    });
})();
</script>
<?php endif; ?>

<?php if (empty($enviado_ok)): ?>
<script>
(function () {
    const inputDocs = document.getElementById('documentos_presentacion_ninos');
    if (!inputDocs) return;

    const btnAgregarDocs = document.getElementById('btn-taller-agregar-documentos');
    const btnQuitarDocs = document.getElementById('btn-taller-quitar-documentos');
    const listaDocs = document.getElementById('taller-doc-lista');
    const badgeDocs = document.getElementById('taller-doc-badge');
    const vacioDocs = document.getElementById('taller-doc-vacio');
    const extensionesDocs = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
    const tamanoMaxDoc = 8 * 1024 * 1024;
    let colaDocs = [];

    function extensionArchivo(nombre) {
        const partes = String(nombre || '').toLowerCase().split('.');
        return partes.length > 1 ? partes.pop() : '';
    }

    function formatearTamano(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function sincronizarInputDocumentos() {
        if (typeof DataTransfer === 'undefined') return colaDocs.length > 0;
        const dt = new DataTransfer();
        colaDocs.forEach(function (archivo) { dt.items.add(archivo); });
        inputDocs.files = dt.files;
        return colaDocs.length > 0 || (inputDocs.files && inputDocs.files.length > 0);
    }

    function tieneDocumentosListos() {
        sincronizarInputDocumentos();
        return colaDocs.length > 0 || (inputDocs.files && inputDocs.files.length > 0);
    }

    window.tallerDocsPresentacion = {
        sincronizar: sincronizarInputDocumentos,
        tieneArchivos: tieneDocumentosListos,
        totalPendientes: function () { return colaDocs.length; },
    };

    function renderColaDocumentos() {
        if (!listaDocs || !badgeDocs) return;
        const total = colaDocs.length;
        badgeDocs.textContent = String(total);
        if (btnQuitarDocs) btnQuitarDocs.style.display = total > 0 ? '' : 'none';
        if (vacioDocs) vacioDocs.style.display = total > 0 ? 'none' : '';
        listaDocs.style.display = total > 0 ? '' : 'none';
        listaDocs.innerHTML = '';
        colaDocs.forEach(function (archivo, indice) {
            const li = document.createElement('li');
            li.className = 'doc-archivo-item';
            const info = document.createElement('div');
            info.className = 'doc-archivo-info';
            const nombre = document.createElement('span');
            nombre.className = 'doc-archivo-nombre';
            nombre.textContent = archivo.name;
            const meta = document.createElement('span');
            meta.className = 'doc-archivo-meta';
            meta.textContent = extensionArchivo(archivo.name).toUpperCase() + ' · ' + formatearTamano(archivo.size);
            info.appendChild(nombre);
            info.appendChild(meta);
            const quitar = document.createElement('button');
            quitar.type = 'button';
            quitar.className = 'doc-archivo-quitar';
            quitar.textContent = '×';
            quitar.addEventListener('click', function () {
                colaDocs.splice(indice, 1);
                sincronizarInputDocumentos();
                renderColaDocumentos();
            });
            li.appendChild(info);
            li.appendChild(quitar);
            listaDocs.appendChild(li);
        });
    }

    function agregarArchivosDocumentos(fileList) {
        const errores = [];
        Array.from(fileList || []).forEach(function (archivo) {
            const ext = extensionArchivo(archivo.name);
            if (!extensionesDocs.includes(ext)) {
                errores.push('«' + archivo.name + '»: tipo no permitido.');
                return;
            }
            if (archivo.size <= 0 || archivo.size > tamanoMaxDoc) {
                errores.push('«' + archivo.name + '»: máximo 8 MB.');
                return;
            }
            const duplicado = colaDocs.some(function (a) {
                return a.name === archivo.name && a.size === archivo.size;
            });
            if (!duplicado) colaDocs.push(archivo);
        });
        if (errores.length) alert(errores.join('\n'));
        sincronizarInputDocumentos();
        renderColaDocumentos();
    }

    if (btnAgregarDocs) {
        btnAgregarDocs.addEventListener('click', function () { inputDocs.click(); });
    }
    inputDocs.addEventListener('change', function () {
        if (inputDocs.files && inputDocs.files.length) {
            agregarArchivosDocumentos(inputDocs.files);
        }
        inputDocs.value = '';
    });
    if (btnQuitarDocs) {
        btnQuitarDocs.addEventListener('click', function () {
            colaDocs = [];
            sincronizarInputDocumentos();
            renderColaDocumentos();
        });
    }

    const formTallerDocs = document.getElementById('form-taller-publico');
    if (formTallerDocs) {
        formTallerDocs.addEventListener('submit', function () {
            sincronizarInputDocumentos();
        }, true);
    }
})();
</script>
<?php endif; ?>

<?php if (empty($enviado_ok)): ?>
<script>
(function() {
    const canvas = document.getElementById('firma-canvas');
    const inputFirma = document.getElementById('autorizacion_firma');
    const btnLimpiarFirma = document.getElementById('btn-limpiar-firma');
    if (!canvas || !inputFirma) return;

    let dibujando = false;
    let huboTrazo = false;
    const ctx = canvas.getContext('2d');

    function guardarFirmaEnHidden() {
        if (huboTrazo) {
            inputFirma.value = canvas.toDataURL('image/png');
        }
    }
    window.tallerGuardarFirma = guardarFirmaEnHidden;

    function resizeCanvas() {
        const ratio = window.devicePixelRatio || 1;
        const rect = canvas.getBoundingClientRect();
        const prev = huboTrazo ? canvas.toDataURL('image/png') : '';
        canvas.width = Math.max(1, Math.floor(rect.width * ratio));
        canvas.height = Math.max(1, Math.floor(rect.height * ratio));
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.strokeStyle = '#1e293b';
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        const src = (prev && prev.indexOf('data:image') === 0) ? prev : inputFirma.value;
        if (src && src.indexOf('data:image') === 0) {
            const img = new Image();
            img.onload = function() {
                ctx.drawImage(img, 0, 0, rect.width, rect.height);
                huboTrazo = true;
            };
            img.src = src;
        }
    }

    function pos(e) {
        const rect = canvas.getBoundingClientRect();
        const src = e.touches ? e.touches[0] : e;
        return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    function iniciar(e) {
        e.preventDefault();
        dibujando = true;
        const p = pos(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    }

    function mover(e) {
        if (!dibujando) return;
        e.preventDefault();
        huboTrazo = true;
        const p = pos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
    }

    function terminar() {
        dibujando = false;
        guardarFirmaEnHidden();
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
    canvas.addEventListener('mousedown', iniciar);
    canvas.addEventListener('mousemove', mover);
    canvas.addEventListener('mouseup', terminar);
    canvas.addEventListener('mouseleave', terminar);
    canvas.addEventListener('touchstart', iniciar, { passive: false });
    canvas.addEventListener('touchmove', mover, { passive: false });
    canvas.addEventListener('touchend', terminar);

    if (btnLimpiarFirma) {
        btnLimpiarFirma.addEventListener('click', function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            inputFirma.value = '';
            huboTrazo = false;
        });
    }

    const form = document.getElementById('form-taller-publico');
    if (form) {
        form.addEventListener('submit', function() {
            guardarFirmaEnHidden();
        });
    }
})();
</script>
<?php endif; ?>

</body>

</html>

