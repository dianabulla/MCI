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

$estadosCiviles = is_array($estados_civiles ?? null) ? $estados_civiles : [];



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

                        <div class="persona-aviso">
                            Escriba su número de cédula. Si ya está en nuestra base de datos o en el histórico de la iglesia, cargaremos sus datos; si no, crearemos su ficha como <strong>miembro antiguo</strong> (no como alma nueva en Ganar).
                        </div>

                        <div class="buscar-doc">
                            <div>
                                <label for="buscar_documento">Número de cédula <span class="req">*</span></label>
                                <input type="text" id="buscar_documento" class="form-control"
                                       placeholder="Ej.: 1234567890"
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_documento'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="btn-buscar-persona">Buscar</button>
                            <span id="buscar-persona-msg" class="text-muted" style="font-size:.85rem;"></span>
                        </div>

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

                        <input type="hidden" id="persona_documento" name="persona_documento"
                               value="<?= htmlspecialchars(taller_valor($valores, 'persona_documento'), ENT_QUOTES, 'UTF-8') ?>">

                        <div class="field">
                            <label for="persona_telefono">Teléfono <span class="req">*</span></label>
                            <input class="form-control" type="tel" id="persona_telefono" name="persona_telefono" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_telefono'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['persona_telefono'])): ?><div class="err"><?= htmlspecialchars($errores['persona_telefono'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>

                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field">
                                <label for="persona_lider">Líder</label>
                                <input class="form-control campo-bloqueado" type="text" id="persona_lider" name="persona_lider" readonly
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_lider'), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Se completa al buscar por cédula">
                            </div>
                            <div class="field">
                                <label for="persona_ministerio">Ministerio</label>
                                <input class="form-control campo-bloqueado" type="text" id="persona_ministerio" name="persona_ministerio" readonly
                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_ministerio'), ENT_QUOTES, 'UTF-8') ?>"
                                       placeholder="Se completa al buscar por cédula">
                            </div>
                        </div>

                        <?php else: ?>

                        <div class="persona-aviso">

                            Si ya está en nuestra base de datos, al escribir documento o teléfono cargaremos sus datos. Si no está registrado, solo guardaremos su inscripción al taller (no se crea una ficha nueva en Personas).

                        </div>

                        <div class="buscar-doc">

                            <div>

                                <label for="buscar_documento">Documento de identidad</label>

                                <input type="text" id="buscar_documento" class="form-control"

                                       placeholder="Ej.: 1234567890"

                                       value="<?= htmlspecialchars(taller_valor($valores, 'persona_documento'), ENT_QUOTES, 'UTF-8') ?>">

                            </div>

                            <button type="button" class="btn btn-outline-primary" id="btn-buscar-persona">Buscar</button>

                            <span id="buscar-persona-msg" class="text-muted" style="font-size:.85rem;"></span>

                        </div>



                        <div class="field">

                            <label for="persona_nombre">Nombre completo <span class="req">*</span></label>

                            <input class="form-control" type="text" id="persona_nombre" name="persona_nombre" required

                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_nombre'), ENT_QUOTES, 'UTF-8') ?>">

                            <?php if (!empty($errores['persona_nombre'])): ?><div class="err"><?= htmlspecialchars($errores['persona_nombre'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

                        </div>



                        <div class="field">

                            <label for="persona_documento">Documento de identidad <span class="req">*</span></label>

                            <input class="form-control" type="text" id="persona_documento" name="persona_documento" required

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



                        <div class="field">

                            <label for="persona_email">Correo electrónico</label>

                            <input class="form-control" type="email" id="persona_email" name="persona_email"

                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_email'), ENT_QUOTES, 'UTF-8') ?>">

                            <?php if (!empty($errores['persona_email'])): ?><div class="err"><?= htmlspecialchars($errores['persona_email'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

                        </div>



                        <div class="field">

                            <label for="persona_direccion">Dirección de residencia</label>

                            <input class="form-control" type="text" id="persona_direccion" name="persona_direccion"

                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_direccion'), ENT_QUOTES, 'UTF-8') ?>">

                        </div>



                        <div class="field">

                            <label>Estado civil</label>

                            <?php $estadoSel = taller_valor($valores, 'persona_estado_civil'); ?>

                            <?php foreach ($estadosCiviles as $i => $ec): ?>

                            <div class="form-check">

                                <input class="form-check-input" type="radio" name="persona_estado_civil"

                                       id="ec_<?= $i ?>" value="<?= htmlspecialchars($ec, ENT_QUOTES, 'UTF-8') ?>"

                                       <?= $estadoSel === $ec ? 'checked' : '' ?>>

                                <label class="form-check-label" for="ec_<?= $i ?>"><?= htmlspecialchars($ec, ENT_QUOTES, 'UTF-8') ?></label>

                            </div>

                            <?php endforeach; ?>

                        </div>



                        <div class="field">

                            <label for="persona_ocupacion">Ocupación</label>

                            <input class="form-control" type="text" id="persona_ocupacion" name="persona_ocupacion"

                                   value="<?= htmlspecialchars(taller_valor($valores, 'persona_ocupacion'), ENT_QUOTES, 'UTF-8') ?>">

                        </div>



                        <?php endif; ?>



                    <?php elseif ($tipoBloque === 'padres'): ?>

                        <div class="persona-aviso">
                            Escriba el documento del padre, madre o acudiente. Si ya está en Personas cargamos sus datos; si no, complete el formulario manualmente.
                        </div>
                        <div class="buscar-doc">
                            <div>
                                <label for="buscar_padres_documento">Documento de identidad</label>
                                <input type="text" id="buscar_padres_documento" class="form-control"
                                       placeholder="Ej.: 1234567890"
                                       value="<?= htmlspecialchars(taller_valor($valores, 'padres_documento'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="btn-buscar-padres">Buscar</button>
                            <span id="buscar-padres-msg" class="text-muted" style="font-size:.85rem;"></span>
                        </div>
                        <div class="field">
                            <label for="padres_nombre">Nombre completo <span class="req">*</span></label>
                            <input class="form-control" type="text" id="padres_nombre" name="padres_nombre" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'padres_nombre'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['padres_nombre'])): ?><div class="err"><?= htmlspecialchars($errores['padres_nombre'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="padres_documento">Documento <span class="req">*</span></label>
                            <input class="form-control" type="text" id="padres_documento" name="padres_documento" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'padres_documento'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['padres_documento'])): ?><div class="err"><?= htmlspecialchars($errores['padres_documento'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="padres_telefono">Teléfono <span class="req">*</span></label>
                            <input class="form-control" type="tel" id="padres_telefono" name="padres_telefono" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'padres_telefono'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['padres_telefono'])): ?><div class="err"><?= htmlspecialchars($errores['padres_telefono'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <input type="hidden" name="padres_encontrado_bd" id="padres_encontrado_bd" value="<?= htmlspecialchars(taller_valor($valores, 'padres_encontrado_bd', '0'), ENT_QUOTES, 'UTF-8') ?>">

                    <?php elseif ($tipoBloque === 'nino'): ?>

                        <div class="persona-aviso">
                            Escriba el documento del niño(a). Si ya está en Personas cargamos sus datos; si no, complete el formulario manualmente.
                        </div>
                        <div class="buscar-doc">
                            <div>
                                <label for="buscar_nino_documento">Documento del niño(a)</label>
                                <input type="text" id="buscar_nino_documento" class="form-control"
                                       placeholder="Ej.: 1234567890"
                                       value="<?= htmlspecialchars(taller_valor($valores, 'nino_documento'), ENT_QUOTES, 'UTF-8') ?>">
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="btn-buscar-nino">Buscar</button>
                            <span id="buscar-nino-msg" class="text-muted" style="font-size:.85rem;"></span>
                        </div>
                        <div class="field">
                            <label for="nino_nombre">Nombre del niño(a) <span class="req">*</span></label>
                            <input class="form-control" type="text" id="nino_nombre" name="nino_nombre" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'nino_nombre'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['nino_nombre'])): ?><div class="err"><?= htmlspecialchars($errores['nino_nombre'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="nino_documento">Documento <span class="req">*</span></label>
                            <input class="form-control" type="text" id="nino_documento" name="nino_documento" required
                                   value="<?= htmlspecialchars(taller_valor($valores, 'nino_documento'), ENT_QUOTES, 'UTF-8') ?>">
                            <?php if (!empty($errores['nino_documento'])): ?><div class="err"><?= htmlspecialchars($errores['nino_documento'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                            <div class="field">
                                <label for="nino_fecha_nacimiento">Fecha de nacimiento <span class="req">*</span></label>
                                <input class="form-control" type="date" id="nino_fecha_nacimiento" name="nino_fecha_nacimiento" required
                                       value="<?= htmlspecialchars(taller_valor($valores, 'nino_fecha_nacimiento'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($errores['nino_fecha_nacimiento'])): ?><div class="err"><?= htmlspecialchars($errores['nino_fecha_nacimiento'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                            <div class="field">
                                <label for="nino_edad">Edad</label>
                                <input class="form-control" type="number" id="nino_edad" name="nino_edad" min="0" max="18" readonly
                                       value="<?= htmlspecialchars(taller_valor($valores, 'nino_edad'), ENT_QUOTES, 'UTF-8') ?>">
                                <?php if (!empty($errores['nino_edad'])): ?><div class="err"><?= htmlspecialchars($errores['nino_edad'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </div>
                        </div>
                        <input type="hidden" name="nino_encontrado_bd" id="nino_encontrado_bd" value="<?= htmlspecialchars(taller_valor($valores, 'nino_encontrado_bd', '0'), ENT_QUOTES, 'UTF-8') ?>">

                    <?php elseif ($tipoBloque === 'documentos'): ?>

                        <?php include VIEWS . '/talleres_publico/_bloque_documentos_presentacion.php'; ?>

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

            <?php if ($esPresentacionNinos): ?>
            <div id="panel-docs-ya-inscrito" class="persona-aviso" style="display:none;background:#fef3c7;border-color:#fcd34d;color:#92400e;margin-top:12px;">
                Este niño(a) <strong>ya está inscrito</strong>. Puede adjuntar documentos en la sección «Documentos» y pulsar <strong>Subir documentos</strong>.
            </div>
            <?php endif; ?>

            <?php if ($esTourLevantate): ?>
            <div id="panel-ya-inscrito-tour" class="persona-aviso" style="display:none;background:#fef3c7;border-color:#fcd34d;color:#92400e;margin-top:12px;"></div>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary w-100" id="btn-enviar-formulario-taller" style="background:#325fa9;border-color:#325fa9;padding:12px;margin-top:12px;">

                Enviar formulario

            </button>

        </form>

    <?php endif; ?>

</div>



<?php if (empty($enviado_ok)): ?>

<script>

(function() {

    const urlBuscar = <?= json_encode($urlBuscarPersona) ?>;

    const slugFormularioTour = <?= $esTourLevantate ? json_encode($slug) : 'null' ?>;

    const btnBuscar = document.getElementById('btn-buscar-persona');

    const inpBuscarDoc = document.getElementById('buscar_documento');

    const inpDoc = document.getElementById('persona_documento');

    const inpTel = document.getElementById('persona_telefono');

    const msg = document.getElementById('buscar-persona-msg');

    const MIN_DOC_BUSQUEDA = 5;

    const MIN_TEL_BUSQUEDA = 9;

    let buscarPersonaSeq = 0;

    let buscarPersonaTimer = null;



    function soloDigitos(valor) {

        return String(valor || '').replace(/\D+/g, '');

    }



    function sincronizarDocumento(origen) {

        if (!inpDoc || !inpBuscarDoc) return;

        const doc = soloDigitos(origen === 'buscar' ? inpBuscarDoc.value : inpDoc.value);

        inpDoc.value = doc;

        if (inpBuscarDoc) inpBuscarDoc.value = doc;

    }



    function setPersona(p) {

        const map = {

            persona_nombre: p.nombre_pila || p.nombre || '',

            persona_apellido: p.apellido || '',

            persona_documento: p.documento || '',

            persona_telefono: p.telefono || '',

            persona_email: p.email || '',

            persona_direccion: p.direccion || '',

            persona_fecha_nacimiento: p.fecha_nacimiento || '',

            persona_edad: p.edad || '',

            persona_lider: p.lider || '',

            persona_ministerio: p.ministerio || ''

        };

        Object.keys(map).forEach(function(id) {

            const el = document.getElementById(id);

            if (el) el.value = map[id];

        });

        if (inpBuscarDoc && map.persona_documento) inpBuscarDoc.value = soloDigitos(map.persona_documento);

        if (inpTel && map.persona_telefono) inpTel.value = soloDigitos(map.persona_telefono);

        ['persona_lider', 'persona_ministerio'].forEach(function(id) {

            const el = document.getElementById(id);

            if (!el) return;

            const bloquear = String(map[id] || '').trim() !== '';

            el.readOnly = bloquear;

            el.classList.toggle('campo-bloqueado', bloquear);

        });

    }



    function ejecutarBusquedaPersona(origen) {

        sincronizarDocumento(origen === 'buscar' ? 'buscar' : 'form');

        const doc = soloDigitos(inpDoc ? inpDoc.value : '');

        const tel = soloDigitos(inpTel ? inpTel.value : '');

        const buscarPorDoc = origen !== 'telefono' && doc.length >= MIN_DOC_BUSQUEDA;

        const buscarPorTel = !buscarPorDoc && tel.length >= MIN_TEL_BUSQUEDA;



        if (!buscarPorDoc && !buscarPorTel) {

            if (msg) msg.textContent = '';

            return;

        }



        const params = new URLSearchParams();

        if (buscarPorDoc) {

            params.set('documento', doc);

        } else {

            params.set('telefono', tel);

        }

        if (slugFormularioTour) {

            params.set('slug', slugFormularioTour);

        }



        const seq = ++buscarPersonaSeq;

        if (msg) msg.textContent = 'Buscando…';



        fetch(urlBuscar + '&' + params.toString())

            .then(function(r) { return r.json(); })

            .then(function(data) {

                if (seq !== buscarPersonaSeq) return;



                if (data.ya_inscrito) {

                    if (typeof marcarYaInscritoTour === 'function') {

                        marcarYaInscritoTour(data.mensaje || 'Esta cédula ya está inscrita.');

                    }

                    if (msg) msg.textContent = data.mensaje || 'Ya inscrita.';

                    if (data.ok && data.persona) {

                        setPersona(data.persona);

                    }

                    return;

                }

                if (typeof marcarYaInscritoTour === 'function') {

                    marcarYaInscritoTour('');

                }



                if (data.ok && data.persona) {

                    setPersona(data.persona);

                    if (msg) msg.textContent = data.persona && data.persona.origen_externo === 'nehemias'
                        ? 'Encontrada en histórico de la iglesia. Datos cargados.'
                        : 'Persona encontrada. Datos cargados.';

                } else {

                    if (msg) msg.textContent = 'No encontrada. Complete como persona nueva.';

                    if (buscarPorDoc && inpDoc) inpDoc.value = doc;

                    ['persona_lider', 'persona_ministerio'].forEach(function(id) {

                        const el = document.getElementById(id);

                        if (!el) return;

                        el.readOnly = false;

                        el.classList.remove('campo-bloqueado');

                    });

                }

            })

            .catch(function() {

                if (seq !== buscarPersonaSeq) return;

                if (msg) msg.textContent = 'Error al buscar. Intente de nuevo.';

            });

    }



    function programarBusquedaPersona(origen) {

        if (buscarPersonaTimer) clearTimeout(buscarPersonaTimer);

        buscarPersonaTimer = setTimeout(function() {

            ejecutarBusquedaPersona(origen);

        }, 450);

    }



    if (btnBuscar) {

        btnBuscar.addEventListener('click', function() {

            ejecutarBusquedaPersona('buscar');

        });

    }



    if (inpBuscarDoc) {

        inpBuscarDoc.addEventListener('input', function() {

            inpBuscarDoc.value = soloDigitos(inpBuscarDoc.value);

            sincronizarDocumento('buscar');

            programarBusquedaPersona('buscar');

        });

        inpBuscarDoc.addEventListener('blur', function() {

            ejecutarBusquedaPersona('buscar');

        });

    }



    if (inpDoc) {

        inpDoc.addEventListener('input', function() {

            inpDoc.value = soloDigitos(inpDoc.value);

            sincronizarDocumento('form');

            programarBusquedaPersona('documento');

        });

        inpDoc.addEventListener('blur', function() {

            ejecutarBusquedaPersona('documento');

        });

    }



    if (inpTel) {

        inpTel.addEventListener('input', function() {

            inpTel.value = soloDigitos(inpTel.value);

            programarBusquedaPersona('telefono');

        });

        inpTel.addEventListener('blur', function() {

            ejecutarBusquedaPersona('telefono');

        });

    }



    function serializarTabla(wrapper) {

        const columnas = JSON.parse(wrapper.getAttribute('data-columnas') || '[]');

        const filas = [];

        wrapper.querySelectorAll('.tabla-fila').forEach(function(fila) {

            const obj = {};

            let tieneDato = false;

            fila.querySelectorAll('.tabla-celda').forEach(function(inp) {

                const col = inp.getAttribute('data-col') || '';

                const val = inp.value.trim();

                if (col) obj[col] = val;

                if (val) tieneDato = true;

            });

            if (tieneDato) filas.push(obj);

        });

        const hidden = wrapper.querySelector('.tabla-json-input');

        if (hidden) hidden.value = JSON.stringify(filas);

    }



    function crearFilaTabla(wrapper) {

        const columnas = JSON.parse(wrapper.getAttribute('data-columnas') || '[]');

        const div = document.createElement('div');

        div.className = 'tabla-fila';

        div.style.cssText = 'display:grid;grid-template-columns:repeat(' + Math.max(1, columnas.length) + ',1fr) auto;gap:8px;margin-bottom:8px;';

        columnas.forEach(function(col) {

            const inp = document.createElement('input');

            inp.type = 'text';

            inp.className = 'form-control form-control-sm tabla-celda';

            inp.setAttribute('data-col', col);

            inp.placeholder = col;

            div.appendChild(inp);

        });

        const btn = document.createElement('button');

        btn.type = 'button';

        btn.className = 'btn btn-outline-danger btn-sm btn-quitar-fila';

        btn.textContent = '×';

        btn.addEventListener('click', function() {

            div.remove();

            serializarTabla(wrapper);

        });

        div.appendChild(btn);

        return div;

    }



    document.querySelectorAll('.tabla-campo').forEach(function(wrapper) {

        const body = wrapper.querySelector('.tabla-campo-body');

        const btnAdd = wrapper.querySelector('.btn-agregar-fila');

        if (btnAdd && body) {

            btnAdd.addEventListener('click', function() {

                body.appendChild(crearFilaTabla(wrapper));

            });

        }

        wrapper.querySelectorAll('.btn-quitar-fila').forEach(function(btn) {

            btn.addEventListener('click', function() {

                const fila = btn.closest('.tabla-fila');

                if (fila) fila.remove();

                serializarTabla(wrapper);

            });

        });

        wrapper.addEventListener('input', function() { serializarTabla(wrapper); });

        serializarTabla(wrapper);

    });



    const form = document.getElementById('form-taller-publico');

    if (form) {

        form.addEventListener('submit', function() {

            document.querySelectorAll('.tabla-campo').forEach(serializarTabla);

            if (typeof guardarFirmaEnHidden === 'function') {

                guardarFirmaEnHidden();

            }

        });

    }



    // Canvas de firma

    const canvas = document.getElementById('firma-canvas');

    const inputFirma = document.getElementById('autorizacion_firma');

    const btnLimpiarFirma = document.getElementById('btn-limpiar-firma');

    let dibujando = false;

    let huboTrazo = false;



    function guardarFirmaEnHidden() {

        if (!canvas || !inputFirma) return;

        if (huboTrazo) {

            inputFirma.value = canvas.toDataURL('image/png');

        }

    }



    if (canvas && inputFirma) {

        const ctx = canvas.getContext('2d');

        function resizeCanvas() {

            const ratio = window.devicePixelRatio || 1;

            const rect = canvas.getBoundingClientRect();

            canvas.width = rect.width * ratio;

            canvas.height = rect.height * ratio;

            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

            ctx.strokeStyle = '#1e293b';

            ctx.lineWidth = 2;

            ctx.lineCap = 'round';

            ctx.lineJoin = 'round';

            if (inputFirma.value && inputFirma.value.indexOf('data:image') === 0) {

                const img = new Image();

                img.onload = function() {

                    ctx.drawImage(img, 0, 0, rect.width, rect.height);

                    huboTrazo = true;

                };

                img.src = inputFirma.value;

            }

        }

        resizeCanvas();

        window.addEventListener('resize', resizeCanvas);



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

    }

})();

</script>

<?php if ($esTourLevantate): ?>
<script>
(function() {
    const VALOR_SIN_LIBRO = 'No, aún no tengo el libro';
    const wrap = document.getElementById('wrap-comprar-libro-tour');
    const radiosLibro = document.querySelectorAll('input[name="ya_tiene_el_libro"]');
    const radiosComprar = document.querySelectorAll('input[name="desea_comprar_libro"]');
    const form = document.getElementById('form-taller-publico');
    const panelYaInscrito = document.getElementById('panel-ya-inscrito-tour');
    const btnEnviar = document.getElementById('btn-enviar-formulario-taller');

    window.marcarYaInscritoTour = function(mensaje) {
        const bloqueado = String(mensaje || '').trim() !== '';
        if (panelYaInscrito) {
            panelYaInscrito.style.display = bloqueado ? '' : 'none';
            panelYaInscrito.textContent = bloqueado ? mensaje : '';
        }
        if (btnEnviar) {
            btnEnviar.disabled = bloqueado;
            btnEnviar.style.opacity = bloqueado ? '0.65' : '';
        }
    };

    function sinLibroSeleccionado() {
        let sinLibro = false;
        radiosLibro.forEach(function(r) {
            if (r.checked && r.value === VALOR_SIN_LIBRO) sinLibro = true;
        });
        return sinLibro;
    }

    function actualizarComprarLibro() {
        const sinLibro = sinLibroSeleccionado();
        if (wrap) wrap.style.display = sinLibro ? '' : 'none';
        radiosComprar.forEach(function(r) {
            r.required = sinLibro;
            if (!sinLibro) r.checked = false;
        });
    }

    radiosLibro.forEach(function(r) { r.addEventListener('change', actualizarComprarLibro); });
    actualizarComprarLibro();

    if (form) {
        form.addEventListener('submit', function(e) {
            if (!sinLibroSeleccionado()) return;
            let tieneRespuesta = false;
            radiosComprar.forEach(function(r) {
                if (r.checked) tieneRespuesta = true;
            });
            if (!tieneRespuesta) {
                e.preventDefault();
                alert('Indique si desea comprar el libro.');
                if (wrap) wrap.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }
})();
</script>
<?php endif; ?>

<?php if ($esPresentacionNinos): ?>
<script>
(function() {
    const urlBuscar = <?= json_encode($urlBuscarPersona) ?>;
    const slugFormulario = <?= json_encode($slug) ?>;
    const MIN_DOC = 5;

    const IDS_PADRES = ['padres_nombre', 'padres_documento', 'padres_telefono'];
    const IDS_NINO_EDITABLES = ['nino_nombre', 'nino_documento', 'nino_fecha_nacimiento'];
    const IDS_NINO_TODOS = IDS_NINO_EDITABLES.concat(['nino_edad']);

    let padresEncontrado = false;
    let ninoEncontrado = false;
    let ninoYaInscrito = false;
    let idRespuestaInscripcion = 0;
    let seqPadres = 0;
    let seqNino = 0;
    let timerPadres = null;
    let timerNino = null;

    const inpBuscarPadres = document.getElementById('buscar_padres_documento');
    const inpBuscarNino = document.getElementById('buscar_nino_documento');
    const hiddenPadresBd = document.getElementById('padres_encontrado_bd');
    const hiddenNinoBd = document.getElementById('nino_encontrado_bd');
    const msgPadres = document.getElementById('buscar-padres-msg');
    const msgNino = document.getElementById('buscar-nino-msg');
    const fechaNac = document.getElementById('nino_fecha_nacimiento');
    const edadNino = document.getElementById('nino_edad');
    const hiddenSoloDocs = document.getElementById('solo_subir_documentos');
    const hiddenIdRespuesta = document.getElementById('id_respuesta_existente');
    const panelDocsInscrito = document.getElementById('panel-docs-ya-inscrito');
    const btnEnviarForm = document.getElementById('btn-enviar-formulario-taller');

    function resetModoSoloDocumentos() {
        idRespuestaInscripcion = 0;
        ninoYaInscrito = false;
        if (hiddenSoloDocs) hiddenSoloDocs.value = '0';
        if (hiddenIdRespuesta) hiddenIdRespuesta.value = '';
        if (panelDocsInscrito) panelDocsInscrito.style.display = 'none';
        if (btnEnviarForm) btnEnviarForm.textContent = 'Enviar formulario';
    }

    function activarModoSoloDocumentos(idRespuesta) {
        idRespuestaInscripcion = parseInt(idRespuesta, 10) || 0;
        ninoYaInscrito = idRespuestaInscripcion > 0;
        if (hiddenSoloDocs) hiddenSoloDocs.value = idRespuestaInscripcion > 0 ? '1' : '0';
        if (hiddenIdRespuesta) hiddenIdRespuesta.value = idRespuestaInscripcion > 0 ? String(idRespuestaInscripcion) : '';
        if (panelDocsInscrito) panelDocsInscrito.style.display = idRespuestaInscripcion > 0 ? '' : 'none';
        if (btnEnviarForm) btnEnviarForm.textContent = idRespuestaInscripcion > 0 ? 'Subir documentos' : 'Enviar formulario';
        const sectionDocs = document.getElementById('section-documentos-presentacion');
        if (sectionDocs && idRespuestaInscripcion > 0) {
            sectionDocs.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function soloDigitos(v) { return String(v || '').replace(/\D+/g, ''); }

    function calcularEdad(fecha) {
        if (!fecha) return '';
        const nac = new Date(fecha + 'T12:00:00');
        if (isNaN(nac.getTime())) return '';
        const hoy = new Date();
        let edad = hoy.getFullYear() - nac.getFullYear();
        const m = hoy.getMonth() - nac.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < nac.getDate())) edad--;
        return edad >= 0 ? String(edad) : '';
    }

    function marcarCampo(id, bloqueado) {
        const el = document.getElementById(id);
        if (!el) return;
        el.readOnly = bloqueado;
        el.classList.toggle('campo-bloqueado', bloqueado);
        el.classList.toggle('campo-editable', !bloqueado);
    }

    function bloquearLista(ids, bloqueado) {
        ids.forEach(function(id) { marcarCampo(id, bloqueado); });
    }

    function llenarPersona(p, mapa) {
        Object.keys(mapa).forEach(function(campoId) {
            const el = document.getElementById(campoId);
            if (!el) return;
            const key = mapa[campoId];
            let val = p[key] || '';
            if (campoId.indexOf('telefono') !== -1 || campoId.indexOf('documento') !== -1) {
                val = soloDigitos(val);
            }
            el.value = val;
        });
    }

    function limpiarPadres(mantenerDocumento) {
        padresEncontrado = false;
        if (hiddenPadresBd) hiddenPadresBd.value = '0';
        if (msgPadres) msgPadres.style.color = '';
        const doc = mantenerDocumento && inpBuscarPadres ? soloDigitos(inpBuscarPadres.value) : '';
        IDS_PADRES.forEach(function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            if (id === 'padres_documento' && doc) {
                el.value = doc;
            } else if (id !== 'padres_documento' || !mantenerDocumento) {
                el.value = '';
            }
        });
        desbloquearPadresManual(doc);
    }

    function desbloquearPadresManual(doc) {
        padresEncontrado = false;
        if (hiddenPadresBd) hiddenPadresBd.value = '0';
        if (msgPadres) msgPadres.style.color = '';
        bloquearLista(IDS_PADRES, false);
        if (doc) {
            const docEl = document.getElementById('padres_documento');
            if (docEl) docEl.value = doc;
            if (inpBuscarPadres) inpBuscarPadres.value = doc;
        }
    }

    function aplicarPadresEncontrado(p) {
        llenarPersona(p, {
            padres_nombre: 'nombre',
            padres_documento: 'documento',
            padres_telefono: 'telefono'
        });
        if (inpBuscarPadres && p.documento) inpBuscarPadres.value = soloDigitos(p.documento);

        const incompleto = !(p.nombre || '').trim() || soloDigitos(p.telefono || '').length < 7;
        if (incompleto) {
            padresEncontrado = false;
            if (hiddenPadresBd) hiddenPadresBd.value = '0';
            bloquearLista(IDS_PADRES, false);
            if (p.documento) marcarCampo('padres_documento', true);
            if (msgPadres) {
                msgPadres.style.color = '';
                msgPadres.textContent = 'Encontrado con datos incompletos. Complete nombre y teléfono.';
            }
            return;
        }

        padresEncontrado = true;
        if (hiddenPadresBd) hiddenPadresBd.value = '1';
        bloquearLista(IDS_PADRES, true);
        if (msgPadres) {
            msgPadres.style.color = '';
            msgPadres.textContent = 'Acudiente encontrado. Los datos no se pueden modificar.';
        }
    }

    function limpiarNino(mantenerDocumento) {
        resetModoSoloDocumentos();
        ninoEncontrado = false;
        if (hiddenNinoBd) hiddenNinoBd.value = '0';
        if (msgNino) msgNino.style.color = '';
        const doc = mantenerDocumento && inpBuscarNino ? soloDigitos(inpBuscarNino.value) : '';
        IDS_NINO_TODOS.forEach(function(id) {
            const el = document.getElementById(id);
            if (!el) return;
            if (id === 'nino_documento' && doc) {
                el.value = doc;
            } else if (id !== 'nino_documento' || !mantenerDocumento) {
                el.value = '';
            }
        });
        desbloquearNinoManual(doc);
    }

    function desbloquearNinoManual(doc) {
        resetModoSoloDocumentos();
        ninoEncontrado = false;
        if (hiddenNinoBd) hiddenNinoBd.value = '0';
        if (msgNino) msgNino.style.color = '';
        bloquearLista(IDS_NINO_EDITABLES, false);
        marcarCampo('nino_edad', true);
        if (doc) {
            const docEl = document.getElementById('nino_documento');
            if (docEl) docEl.value = doc;
            if (inpBuscarNino) inpBuscarNino.value = doc;
        }
    }

    function aplicarNinoEncontrado(p) {
        resetModoSoloDocumentos();
        ninoYaInscrito = false;
        llenarPersona(p, {
            nino_nombre: 'nombre',
            nino_documento: 'documento',
            nino_fecha_nacimiento: 'fecha_nacimiento',
            nino_edad: 'edad'
        });
        if (fechaNac && p.fecha_nacimiento) {
            fechaNac.value = p.fecha_nacimiento;
            if (edadNino) {
                edadNino.value = calcularEdad(p.fecha_nacimiento) || p.edad || '';
            }
        }
        if (inpBuscarNino && p.documento) inpBuscarNino.value = soloDigitos(p.documento);

        const incompleto = !(p.nombre || '').trim() || !(p.fecha_nacimiento || '').trim();
        if (incompleto) {
            ninoEncontrado = false;
            if (hiddenNinoBd) hiddenNinoBd.value = '0';
            bloquearLista(IDS_NINO_EDITABLES, false);
            marcarCampo('nino_edad', true);
            if (p.documento) marcarCampo('nino_documento', true);
            if (msgNino) {
                msgNino.style.color = '';
                msgNino.textContent = 'Encontrado con datos incompletos. Complete nombre y fecha de nacimiento.';
            }
            return;
        }

        ninoEncontrado = true;
        if (hiddenNinoBd) hiddenNinoBd.value = '1';
        bloquearLista(IDS_NINO_TODOS, true);
        if (msgNino) msgNino.textContent = 'Persona encontrada. Los datos no se pueden modificar.';
    }

    function marcarNinoYaInscrito(data, doc) {
        if (data.persona) {
            ninoEncontrado = true;
            if (hiddenNinoBd) hiddenNinoBd.value = '1';
            llenarPersona(data.persona, {
                nino_nombre: 'nombre',
                nino_documento: 'documento',
                nino_fecha_nacimiento: 'fecha_nacimiento',
                nino_edad: 'edad'
            });
            if (fechaNac && data.persona.fecha_nacimiento) {
                fechaNac.value = data.persona.fecha_nacimiento;
                if (edadNino) {
                    edadNino.value = calcularEdad(data.persona.fecha_nacimiento) || data.persona.edad || '';
                }
            }
            bloquearLista(IDS_NINO_TODOS, true);
            if (inpBuscarNino && data.persona.documento) {
                inpBuscarNino.value = soloDigitos(data.persona.documento);
            }
        } else {
            desbloquearNinoManual(doc);
        }
        ninoYaInscrito = true;
        const idResp = data.inscripcion && data.inscripcion.id_respuesta ? data.inscripcion.id_respuesta : 0;
        activarModoSoloDocumentos(idResp);
        if (msgNino) {
            msgNino.textContent = (data.mensaje || 'Este niño(a) ya está inscrito en este formulario.')
                + ' Seleccione archivos y pulse «Subir documentos».';
            msgNino.style.color = '#b45309';
        }
    }

    function buscarPadres() {
        const doc = inpBuscarPadres ? soloDigitos(inpBuscarPadres.value) : '';
        if (inpBuscarPadres) inpBuscarPadres.value = doc;
        if (doc.length < MIN_DOC) {
            if (msgPadres) msgPadres.textContent = '';
            return;
        }
        const actual = ++seqPadres;
        if (msgPadres) msgPadres.textContent = 'Buscando…';
        fetch(urlBuscar + '&' + new URLSearchParams({ documento: doc, modo: 'padres' }))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (actual !== seqPadres) return;
                if (data.ok && data.persona) {
                    aplicarPadresEncontrado(data.persona);
                } else {
                    desbloquearPadresManual(doc);
                    if (msgPadres) msgPadres.textContent = 'No está en Personas. Complete nombre y teléfono.';
                }
            })
            .catch(function() {
                if (actual !== seqPadres) return;
                if (msgPadres) msgPadres.textContent = 'Error al buscar. Intente de nuevo.';
            });
    }

    function buscarNino() {
        const doc = inpBuscarNino ? soloDigitos(inpBuscarNino.value) : '';
        if (inpBuscarNino) inpBuscarNino.value = doc;
        const docEl = document.getElementById('nino_documento');
        if (docEl) docEl.value = doc;
        if (doc.length < MIN_DOC) {
            if (msgNino) msgNino.textContent = '';
            return;
        }
        const actual = ++seqNino;
        if (msgNino) msgNino.textContent = 'Buscando…';
        fetch(urlBuscar + '&' + new URLSearchParams({ documento: doc, modo: 'nino', slug: slugFormulario }))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (actual !== seqNino) return;
                if (data.ya_inscrito) {
                    marcarNinoYaInscrito(data, doc);
                    return;
                }
                if (data.ok && data.persona) {
                    aplicarNinoEncontrado(data.persona);
                } else {
                    desbloquearNinoManual(doc);
                    if (msgNino) msgNino.textContent = 'No está en Personas. Complete nombre y fecha de nacimiento.';
                }
            })
            .catch(function() {
                if (actual !== seqNino) return;
                if (msgNino) msgNino.textContent = 'Error al buscar. Intente de nuevo.';
            });
    }

    if (inpBuscarPadres) {
        inpBuscarPadres.addEventListener('input', function() {
            inpBuscarPadres.value = soloDigitos(inpBuscarPadres.value);
            if (padresEncontrado) limpiarPadres(true);
            if (timerPadres) clearTimeout(timerPadres);
            timerPadres = setTimeout(buscarPadres, 450);
        });
        inpBuscarPadres.addEventListener('blur', buscarPadres);
    }
    const btnPadres = document.getElementById('btn-buscar-padres');
    if (btnPadres) btnPadres.addEventListener('click', buscarPadres);

    const docPadres = document.getElementById('padres_documento');
    if (docPadres) {
        docPadres.addEventListener('input', function() {
            if (padresEncontrado) return;
            docPadres.value = soloDigitos(docPadres.value);
            if (inpBuscarPadres) inpBuscarPadres.value = docPadres.value;
        });
        docPadres.addEventListener('blur', buscarPadres);
    }

    const telPadres = document.getElementById('padres_telefono');
    if (telPadres) {
        telPadres.addEventListener('input', function() {
            if (padresEncontrado) return;
            telPadres.value = soloDigitos(telPadres.value);
        });
    }

    if (inpBuscarNino) {
        inpBuscarNino.addEventListener('input', function() {
            inpBuscarNino.value = soloDigitos(inpBuscarNino.value);
            if (ninoEncontrado) limpiarNino(true);
            if (timerNino) clearTimeout(timerNino);
            timerNino = setTimeout(buscarNino, 450);
        });
        inpBuscarNino.addEventListener('blur', buscarNino);
    }
    const btnNino = document.getElementById('btn-buscar-nino');
    if (btnNino) btnNino.addEventListener('click', buscarNino);

    const docNino = document.getElementById('nino_documento');
    if (docNino) {
        docNino.addEventListener('input', function() {
            if (ninoEncontrado) return;
            ninoYaInscrito = false;
            if (msgNino) msgNino.style.color = '';
            docNino.value = soloDigitos(docNino.value);
            if (inpBuscarNino) inpBuscarNino.value = docNino.value;
        });
        docNino.addEventListener('blur', buscarNino);
    }

    if (fechaNac && edadNino) {
        fechaNac.addEventListener('change', function() {
            if (ninoEncontrado) return;
            edadNino.value = calcularEdad(fechaNac.value);
        });
    }

    marcarCampo('nino_edad', true);
    if (hiddenPadresBd && hiddenPadresBd.value === '1') {
        padresEncontrado = true;
        bloquearLista(IDS_PADRES, true);
    } else {
        const nombrePadres = document.getElementById('padres_nombre');
        const telPadresInit = document.getElementById('padres_telefono');
        const tieneDatosPadres = (nombrePadres && nombrePadres.value.trim() !== '')
            || (telPadresInit && telPadresInit.value.trim() !== '');
        desbloquearPadresManual(inpBuscarPadres ? soloDigitos(inpBuscarPadres.value) : '');
        if (tieneDatosPadres && msgPadres) {
            msgPadres.textContent = 'Complete o corrija los datos del acudiente.';
        }
    }
    if (hiddenNinoBd && hiddenNinoBd.value === '1') {
        ninoEncontrado = true;
        bloquearLista(IDS_NINO_TODOS, true);
    } else {
        const nombreNino = document.getElementById('nino_nombre');
        const tieneDatosManuales = (nombreNino && nombreNino.value.trim() !== '')
            || (fechaNac && fechaNac.value.trim() !== '');
        desbloquearNinoManual(inpBuscarNino ? soloDigitos(inpBuscarNino.value) : '');
        if (tieneDatosManuales && msgNino) {
            msgNino.textContent = 'Complete o corrija los datos del niño(a).';
        }
    }

    const form = document.getElementById('form-taller-publico');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (ninoYaInscrito && idRespuestaInscripcion > 0) {
                if (hiddenSoloDocs) hiddenSoloDocs.value = '1';
                if (hiddenIdRespuesta) hiddenIdRespuesta.value = String(idRespuestaInscripcion);
                const tieneArchivos = window.tallerDocsPresentacion
                    ? window.tallerDocsPresentacion.tieneArchivos()
                    : (function () {
                        const inputDocs = document.getElementById('documentos_presentacion_ninos');
                        return inputDocs && inputDocs.files && inputDocs.files.length > 0;
                    })();
                if (!tieneArchivos) {
                    e.preventDefault();
                    alert('Seleccione al menos un archivo en la sección Documentos.');
                    const sectionDocs = document.getElementById('section-documentos-presentacion');
                    if (sectionDocs) sectionDocs.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }
                form.setAttribute('novalidate', 'novalidate');
                return;
            }
            form.removeAttribute('novalidate');
            if (padresEncontrado) {
                bloquearLista(IDS_PADRES, true);
            }
            if (ninoEncontrado) {
                bloquearLista(IDS_NINO_TODOS, true);
            }
        });
    }
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

<?php endif; ?>

</body>

</html>

