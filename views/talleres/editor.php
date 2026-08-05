<?php include VIEWS . '/layout/header.php'; ?>



<?php

require_once APP . '/Models/TallerFormulario.php';

$flashError = $_SESSION['talleres_flash_error'] ?? '';

unset($_SESSION['talleres_flash_error']);

$id = (int)($formulario['Id_Formulario'] ?? 0);

$slug = (string)($formulario['Slug'] ?? '');

$urlPublica = $slug !== '' ? (PUBLIC_URL . '?url=talleres_publico&slug=' . urlencode($slug)) : '';
$urlQrPublica = $slug !== '' ? (PUBLIC_URL . '?url=talleres_publico/qr&slug=' . urlencode($slug)) : '';

$tiposCampo = ['text', 'textarea', 'email', 'tel', 'number', 'date', 'select', 'radio', 'checkbox', 'tabla'];

$labelsTipo = [

    'text' => 'Respuesta corta',

    'textarea' => 'Respuesta larga',

    'email' => 'Correo electrónico',

    'tel' => 'Teléfono',

    'number' => 'Número',

    'date' => 'Fecha',

    'select' => 'Lista desplegable',

    'radio' => 'Opción única (círculos)',

    'checkbox' => 'Varias opciones (casillas)',

    'tabla' => 'Tabla (varias filas)',

];

$plantillaPadres = TallerFormulario::getPlantillaTallerPadres();
$configPlantillaPadres = TallerFormulario::getConfigPlantillaTallerPadres();

?>



<div class="page-header">

    <h2><?= ($modo ?? '') === 'crear' ? 'Nuevo formulario' : 'Editar formulario' ?></h2>

    <a href="<?= PUBLIC_URL ?>?url=talleres" class="btn btn-secondary btn-sm">← Volver al listado</a>

</div>



<?php if ($flashError !== ''): ?>

<div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>

<?php endif; ?>



<form method="POST" action="<?= PUBLIC_URL ?>?url=talleres/guardar" id="form-taller-editor">

    <input type="hidden" name="id_formulario" value="<?= $id ?>">



    <div class="form-container" style="margin-bottom:20px;">

        <h4>Información general</h4>

        <div class="form-group">

            <label for="titulo">Nombre del formulario *</label>

            <input type="text" id="titulo" name="titulo" class="form-control" required

                   placeholder="Ej.: Inscripción Taller de Padres"

                   value="<?= htmlspecialchars((string)($formulario['Titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

            <small class="text-muted">Este nombre se usa para crear el enlace que compartirá.</small>

        </div>



        <div class="form-group" id="bloque-enlace-publico" style="<?= $urlPublica !== '' ? '' : 'display:none;' ?>">

            <label>Enlace para compartir</label>

            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">

                <input type="text" id="enlace-publico-vista" class="form-control" readonly

                       value="<?= htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8') ?>"

                       style="flex:1;min-width:220px;background:#f8fafc;">

                <?php if ($urlPublica !== ''): ?>

                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-copiar-enlace">Copiar enlace</button>

                <?php endif; ?>

            </div>

        </div>



        <?php if ($urlQrPublica !== '' && ($id === 0 || !empty($formulario['Activo']))): ?>

        <div class="form-group" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:14px;">

            <label style="margin-bottom:8px;"><i class="bi bi-qr-code"></i> Código QR (público)</label>

            <p class="text-muted" style="font-size:0.88rem;margin:0 0 10px;">

                Imprima o comparta este QR para que las personas escaneen y lleguen directo al formulario, sin iniciar sesión.

            </p>

            <a href="<?= htmlspecialchars($urlQrPublica, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-success btn-sm" target="_blank" rel="noopener">

                Ver / imprimir QR

            </a>

        </div>

        <?php endif; ?>



        <div class="form-group">

            <textarea id="descripcion" name="descripcion" class="form-control" rows="2"

                      placeholder="Ej.: Complete el formulario de inscripción y diagnóstico."><?= htmlspecialchars((string)($formulario['Descripcion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>

        </div>



        <div class="form-group">

            <label for="mensaje_gracias">Mensaje al enviar (opcional)</label>

            <input type="text" id="mensaje_gracias" name="mensaje_gracias" class="form-control"

                   placeholder="Ej.: ¡Gracias! Recibimos su inscripción."

                   value="<?= htmlspecialchars((string)($formulario['Mensaje_Gracias'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

        </div>



        <?php
        require_once APP . '/Helpers/TallerAutorizacionSync.php';
        $textoAuthEditor = trim((string)($formulario['Texto_Autorizacion'] ?? ''));
        if ($textoAuthEditor === '') {
            $textoAuthEditor = TallerAutorizacionSync::textoDefault();
        }
        ?>

        <div class="form-group">

            <label for="texto_autorizacion">Texto de autorización (bloque final fijo)</label>

            <textarea id="texto_autorizacion" name="texto_autorizacion" class="form-control" rows="3"
                      placeholder="Texto que verá la persona antes de firmar."><?= htmlspecialchars($textoAuthEditor, ENT_QUOTES, 'UTF-8') ?></textarea>

            <small class="text-muted">Este bloque siempre aparece al final con casilla de aceptación, espacio para firma y fecha.</small>

        </div>



        <div class="form-group">

            <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer;">

                <input type="checkbox" name="activo" value="1" <?= ($id === 0 || !empty($formulario['Activo'])) ? 'checked' : '' ?>>

                Publicar formulario

            </label>

        </div>

    </div>



    <div class="form-container" style="margin-bottom:20px;background:#f0f9ff;border-color:#bae6fd;">

        <h4 style="margin-bottom:8px;">Bloque 1 — Datos personales (fijo)</h4>

        <p class="text-muted" style="font-size:0.9rem;margin:0;">

            Este bloque siempre está presente y se vincula con la base de datos de personas.

            Si la persona no existe, se crea automáticamente al enviar el formulario.

        </p>

        <ul style="margin:12px 0 0 18px;font-size:0.9rem;color:#475569;">

            <li>Nombre completo, documento, fecha de nacimiento, edad</li>

            <li>Teléfono, correo, dirección, estado civil, ocupación</li>

        </ul>

    </div>



    <div class="form-container">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;flex-wrap:wrap;gap:8px;">

            <h4 style="margin:0;">Bloques de preguntas (2 en adelante)</h4>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">

                <button type="button" class="btn btn-outline-success btn-sm" id="btn-plantilla-padres">

                    Usar plantilla «Taller de Padres»

                </button>

                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-agregar-bloque">

                    <i class="bi bi-plus-lg"></i> Agregar bloque

                </button>

            </div>

        </div>

        <p class="text-muted" style="font-size:0.9rem;">Organice las preguntas por secciones. Cada bloque aparece como un apartado en el formulario público.</p>



        <div id="bloques-container">

            <?php if (!empty($bloques)): ?>

                <?php foreach ($bloques as $bIdx => $bloque): ?>

                <div class="bloque-row card" data-bloque-idx="<?= (int)$bIdx ?>" style="padding:16px;margin-bottom:14px;border:1px solid #cbd5e1;">

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:8px;flex-wrap:wrap;">

                        <strong class="bloque-numero">Bloque <?= (int)$bIdx + 2 ?></strong>

                        <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-bloque">Quitar bloque</button>

                    </div>

                    <div class="form-group">

                        <label>Título del bloque *</label>

                        <input type="text" name="bloque_titulo[<?= (int)$bIdx ?>]" class="form-control bloque-titulo-input" required

                               value="<?= htmlspecialchars((string)($bloque['titulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"

                               placeholder="Ej.: 2. Información familiar">

                    </div>

                    <div class="campos-bloque-container" style="margin-top:12px;">

                        <?php foreach (($bloque['campos'] ?? []) as $cIdx => $campo): ?>

                            <?php

                            $tipoCampo = (string)($campo['Tipo'] ?? 'text');

                            $opcionesTxt = '';

                            $columnasTxt = '';

                            if ($tipoCampo === 'tabla') {

                                $cfg = json_decode((string)($campo['Opciones'] ?? ''), true);

                                if (is_array($cfg['columnas'] ?? null)) {

                                    $columnasTxt = implode("\n", $cfg['columnas']);

                                }

                            } else {

                                $opcionesArr = json_decode((string)($campo['Opciones'] ?? ''), true);

                                if (is_array($opcionesArr) && !isset($opcionesArr['columnas'])) {

                                    $opcionesTxt = implode("\n", $opcionesArr);

                                }

                            }

                            ?>

                            <?php include __DIR__ . '/_campo_editor_row.php'; ?>

                        <?php endforeach; ?>

                    </div>

                    <button type="button" class="btn btn-outline-secondary btn-sm btn-agregar-campo" style="margin-top:10px;">

                        + Agregar pregunta a este bloque

                    </button>

                </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>



    <div class="form-container" style="margin-bottom:20px;background:#fefce8;border-color:#fde047;">

        <h4 style="margin-bottom:8px;">Bloque final — Autorización (fijo)</h4>

        <p class="text-muted" style="font-size:0.9rem;margin:0;">

            Siempre aparece al final del formulario público, después de todas las preguntas.

        </p>

        <ul style="margin:12px 0 0 18px;font-size:0.9rem;color:#475569;">

            <li>Texto de autorización (editable arriba)</li>

            <li>Casilla obligatoria de aceptación</li>

            <li>Espacio para firma digital (canvas)</li>

            <li>Fecha de firma</li>

        </ul>

    </div>



    <div style="margin:20px 0;display:flex;gap:10px;flex-wrap:wrap;">

        <button type="submit" class="btn btn-primary">Guardar formulario</button>

        <?php if ($id > 0): ?>

        <a href="<?= PUBLIC_URL ?>?url=talleres/respuestas&id=<?= $id ?>" class="btn btn-outline-primary">Ver respuestas</a>

        <?php if (!empty($formulario['Activo']) && $slug !== ''): ?>

        <a href="<?= htmlspecialchars($urlPublica, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-info" target="_blank" rel="noopener">Abrir formulario</a>

        <?php endif; ?>

        <?php endif; ?>

    </div>

</form>



<template id="tpl-bloque-row">

    <div class="bloque-row card" style="padding:16px;margin-bottom:14px;border:1px solid #cbd5e1;">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:8px;flex-wrap:wrap;">

            <strong class="bloque-numero">Bloque</strong>

            <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-bloque">Quitar bloque</button>

        </div>

        <div class="form-group">

            <label>Título del bloque *</label>

            <input type="text" class="form-control bloque-titulo-input" required placeholder="Ej.: 3. Diagnóstico familiar">

        </div>

        <div class="campos-bloque-container" style="margin-top:12px;"></div>

        <button type="button" class="btn btn-outline-secondary btn-sm btn-agregar-campo" style="margin-top:10px;">

            + Agregar pregunta a este bloque

        </button>

    </div>

</template>



<template id="tpl-campo-row">

    <div class="campo-row card" style="padding:14px;margin-bottom:10px;border:1px solid #e5e7eb;background:#fafafa;">

        <input type="hidden" class="campo-bloque-idx-input" name="campo_bloque_idx[]" value="0">

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">

            <strong class="campo-numero">Pregunta</strong>

            <button type="button" class="btn btn-outline-danger btn-sm btn-quitar-campo">Quitar</button>

        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">

            <div>

                <label>¿Qué desea preguntar? *</label>

                <input type="text" name="campo_etiqueta[]" class="form-control" required placeholder="Ej.: ¿Cuántos hijos tiene?">

            </div>

            <div>

                <label>Tipo de respuesta</label>

                <select name="campo_tipo[]" class="form-control campo-tipo-select">

                    <?php foreach ($tiposCampo as $tipo): ?>

                    <option value="<?= $tipo ?>"><?= htmlspecialchars($labelsTipo[$tipo] ?? $tipo, ENT_QUOTES, 'UTF-8') ?></option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

        <div class="campo-opciones-wrap" style="margin-top:10px;display:none;">

            <label>Opciones (una por línea)</label>

            <textarea name="campo_opciones[]" class="form-control" rows="3" placeholder="Opción 1&#10;Opción 2"></textarea>

        </div>

        <div class="campo-tabla-wrap" style="margin-top:10px;display:none;">

            <label>Columnas de la tabla (una por línea)</label>

            <textarea name="campo_tabla_columnas[]" class="form-control" rows="2" placeholder="Nombre&#10;Edad&#10;Sexo"></textarea>

        </div>

        <div style="margin-top:10px;">

            <label style="display:flex;align-items:center;gap:8px;font-weight:500;cursor:pointer;">

                <input type="checkbox" class="campo-requerido-check" value="1">

                Obligatoria

            </label>

        </div>

    </div>

</template>



<script>

(function() {

    const bloquesContainer = document.getElementById('bloques-container');

    const tplBloque = document.getElementById('tpl-bloque-row');

    const tplCampo = document.getElementById('tpl-campo-row');

    const btnAgregarBloque = document.getElementById('btn-agregar-bloque');

    const btnPlantilla = document.getElementById('btn-plantilla-padres');

    const tituloInput = document.getElementById('titulo');

    const bloqueEnlace = document.getElementById('bloque-enlace-publico');

    const enlaceVista = document.getElementById('enlace-publico-vista');

    const btnCopiar = document.getElementById('btn-copiar-enlace');

    const basePublicUrl = <?= json_encode(PUBLIC_URL . '?url=talleres_publico&slug=') ?>;

    const plantillaPadres = <?= json_encode($plantillaPadres, JSON_UNESCAPED_UNICODE) ?>;
    const configPlantillaPadres = <?= json_encode($configPlantillaPadres, JSON_UNESCAPED_UNICODE) ?>;

    const tiposConOpciones = ['select', 'radio', 'checkbox'];



    function normalizarSlug(texto) {

        const map = { á:'a', é:'e', í:'i', ó:'o', ú:'u', ü:'u', ñ:'n' };

        let s = String(texto || '').trim().toLowerCase();

        Object.keys(map).forEach(function(k) { s = s.split(k).join(map[k]); });

        s = s.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');

        return s.substring(0, 100);

    }



    function actualizarVistaEnlace() {

        if (!tituloInput || !enlaceVista) return;

        const slug = normalizarSlug(tituloInput.value) || 'formulario';

        enlaceVista.value = basePublicUrl + encodeURIComponent(slug);

        if (bloqueEnlace && tituloInput.value.trim() !== '') {

            bloqueEnlace.style.display = '';

        }

    }



    function renumerarBloques() {

        bloquesContainer.querySelectorAll('.bloque-row').forEach(function(bloque, bIdx) {

            bloque.dataset.bloqueIdx = String(bIdx);

            const tit = bloque.querySelector('.bloque-numero');

            if (tit) tit.textContent = 'Bloque ' + (bIdx + 2);

            const tituloInput = bloque.querySelector('.bloque-titulo-input');

            if (tituloInput) {

                tituloInput.name = 'bloque_titulo[' + bIdx + ']';

            }

            bloque.querySelectorAll('.campo-bloque-idx-input').forEach(function(inp) {

                inp.value = String(bIdx);

            });

            renumerarCamposBloque(bloque);

        });

    }



    function renumerarCamposBloque(bloque) {

        const globalRows = bloquesContainer.querySelectorAll('.campo-row');

        let globalIdx = 0;

        bloquesContainer.querySelectorAll('.bloque-row').forEach(function(b) {

            b.querySelectorAll('.campo-row').forEach(function(row) {

                const tit = row.querySelector('.campo-numero');

                if (tit) tit.textContent = 'Pregunta ' + (globalIdx + 1);

                const chk = row.querySelector('.campo-requerido-check');

                if (chk) chk.name = 'campo_requerido[' + globalIdx + ']';

                globalIdx++;

            });

        });

    }



    function toggleTipoCampo(row) {

        const select = row.querySelector('.campo-tipo-select');

        const wrapOpc = row.querySelector('.campo-opciones-wrap');

        const wrapTabla = row.querySelector('.campo-tabla-wrap');

        if (!select) return;

        const tipo = select.value;

        if (wrapOpc) wrapOpc.style.display = tiposConOpciones.indexOf(tipo) >= 0 ? '' : 'none';

        if (wrapTabla) wrapTabla.style.display = tipo === 'tabla' ? '' : 'none';

    }



    function enlazarCampo(row) {

        const select = row.querySelector('.campo-tipo-select');

        if (select) select.addEventListener('change', function() { toggleTipoCampo(row); });

        const btnQuitar = row.querySelector('.btn-quitar-campo');

        if (btnQuitar) {

            btnQuitar.addEventListener('click', function() {

                const bloque = row.closest('.bloque-row');

                row.remove();

                renumerarBloques();

            });

        }

        toggleTipoCampo(row);

    }



    function agregarCampoABloque(bloque, datos) {

        const container = bloque.querySelector('.campos-bloque-container');

        container.appendChild(tplCampo.content.cloneNode(true));

        const rows = container.querySelectorAll('.campo-row');

        const row = rows[rows.length - 1];

        const bIdx = bloque.dataset.bloqueIdx || '0';

        const idxInput = row.querySelector('.campo-bloque-idx-input');

        if (idxInput) idxInput.value = bIdx;



        if (datos) {

            const etiq = row.querySelector('input[name="campo_etiqueta[]"]');

            const tipo = row.querySelector('select[name="campo_tipo[]"]');

            const opc = row.querySelector('textarea[name="campo_opciones[]"]');

            const col = row.querySelector('textarea[name="campo_tabla_columnas[]"]');

            const req = row.querySelector('.campo-requerido-check');

            if (etiq) etiq.value = datos.etiqueta || '';

            if (tipo) tipo.value = datos.tipo || 'text';

            if (opc && datos.opciones) opc.value = (datos.opciones || []).join('\n');

            if (col && datos.columnas) col.value = (datos.columnas || []).join('\n');

            if (req) req.checked = !!datos.requerido;

        }



        enlazarCampo(row);

        renumerarBloques();

    }



    function enlazarBloque(bloque) {

        const btnQuitar = bloque.querySelector('.btn-quitar-bloque');

        if (btnQuitar) {

            btnQuitar.addEventListener('click', function() {

                if (!confirm('¿Quitar este bloque y todas sus preguntas?')) return;

                bloque.remove();

                renumerarBloques();

            });

        }

        const btnCampo = bloque.querySelector('.btn-agregar-campo');

        if (btnCampo) {

            btnCampo.addEventListener('click', function() {

                agregarCampoABloque(bloque, null);

            });

        }

        bloque.querySelectorAll('.campo-row').forEach(enlazarCampo);

    }



    function agregarBloque(datos) {

        bloquesContainer.appendChild(tplBloque.content.cloneNode(true));

        const bloques = bloquesContainer.querySelectorAll('.bloque-row');

        const bloque = bloques[bloques.length - 1];

        if (datos && datos.titulo) {

            const inp = bloque.querySelector('.bloque-titulo-input');

            if (inp) inp.value = datos.titulo;

        }

        enlazarBloque(bloque);

        renumerarBloques();

        if (datos && Array.isArray(datos.campos)) {

            datos.campos.forEach(function(c) { agregarCampoABloque(bloque, c); });

        }

        return bloque;

    }



    bloquesContainer.querySelectorAll('.bloque-row').forEach(enlazarBloque);

    renumerarBloques();



    btnAgregarBloque.addEventListener('click', function() {

        agregarBloque(null);

        const bloques = bloquesContainer.querySelectorAll('.bloque-row');

        const ultimo = bloques[bloques.length - 1];

        const inp = ultimo.querySelector('.bloque-titulo-input');

        if (inp) inp.focus();

    });



    btnPlantilla.addEventListener('click', function() {

        if (bloquesContainer.querySelectorAll('.bloque-row').length > 0) {

            if (!confirm('Esto reemplazará los bloques actuales por la plantilla «Taller de Padres». ¿Continuar?')) {

                return;

            }

            bloquesContainer.innerHTML = '';

        }

        plantillaPadres.forEach(function(b) { agregarBloque(b); });
        if (configPlantillaPadres) {
            const titulo = document.getElementById('titulo');
            const desc = document.getElementById('descripcion');
            const msg = document.getElementById('mensaje_gracias');
            const auth = document.getElementById('texto_autorizacion');
            if (titulo && configPlantillaPadres.titulo) titulo.value = configPlantillaPadres.titulo;
            if (desc && configPlantillaPadres.descripcion) desc.value = configPlantillaPadres.descripcion;
            if (msg && configPlantillaPadres.mensaje_gracias) msg.value = configPlantillaPadres.mensaje_gracias;
            if (auth && configPlantillaPadres.texto_autorizacion) auth.value = configPlantillaPadres.texto_autorizacion;
            if (titulo) actualizarVistaEnlace();
        }

    });



    if (tituloInput) {

        tituloInput.addEventListener('input', actualizarVistaEnlace);

        actualizarVistaEnlace();

    }



    if (btnCopiar && enlaceVista) {

        btnCopiar.addEventListener('click', function() {

            enlaceVista.select();

            const copiado = function() {

                btnCopiar.textContent = '¡Copiado!';

                setTimeout(function() { btnCopiar.textContent = 'Copiar enlace'; }, 2000);

            };

            if (navigator.clipboard && navigator.clipboard.writeText) {

                navigator.clipboard.writeText(enlaceVista.value).then(copiado);

            } else {

                document.execCommand('copy');

                copiado();

            }

        });

    }

})();

</script>



<?php include VIEWS . '/layout/footer.php'; ?>

