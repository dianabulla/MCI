<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teens</title>
    <style>
        :root {
            --bg: #f5f8ff;
            --panel: #ffffff;
            --brand: #0f4c81;
            --brand-2: #1389d3;
            --text: #1d2b3a;
            --muted: #5e6f83;
            --ok-bg: #e8f8ef;
            --ok: #1a7f46;
            --err-bg: #fdeeee;
            --err: #a32525;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 10% -10%, rgba(19,137,211,.15), transparent 45%),
                radial-gradient(circle at 100% 0%, rgba(15,76,129,.12), transparent 38%),
                var(--bg);
            min-height: 100vh;
            padding: 24px 14px;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
            background: var(--panel);
            border-radius: 16px;
            box-shadow: 0 18px 42px rgba(15, 51, 85, .16);
            overflow: hidden;
        }

        .hero {
            background: linear-gradient(120deg, var(--brand), var(--brand-2));
            color: #fff;
            padding: 24px;
        }

        .hero h1 {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
        }

        .hero p {
            margin: 10px 0 0;
            opacity: .95;
        }

        .content {
            padding: 22px;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 14px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: var(--ok-bg);
            color: var(--ok);
            border-color: #b8ebcd;
        }

        .alert-error {
            background: var(--err-bg);
            color: var(--err);
            border-color: #f1c0c0;
        }

        .alert-info {
            background: #e9f4ff;
            color: #14507a;
            border-color: #bfdef7;
        }

        .codigo-box {
            margin: 14px 0 20px;
            background: #fffbe8;
            border: 1px solid #f4de92;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }

        .codigo-box strong {
            display: block;
            font-size: 30px;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
        }

        .full {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        input,
        select {
            width: 100%;
            border: 1px solid #ced7e2;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 15px;
            color: var(--text);
            background: #fff;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--brand-2);
            box-shadow: 0 0 0 3px rgba(19,137,211,.18);
        }

        .actions {
            margin-top: 16px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(120deg, var(--brand), var(--brand-2));
            color: #fff;
            font-weight: 600;
        }

        .btn-secondary {
            background: #e9eef5;
            color: #2d435a;
        }

        .hint {
            margin-top: 16px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }

        .input-with-btn {
            display: flex;
            gap: 8px;
        }

        .input-with-btn input { flex: 1; }

        .btn-buscar {
            width: auto;
            white-space: nowrap;
            background: #e9eef5;
            color: #2d435a;
            font-weight: 600;
        }

        .panel-nino-nuevo {
            grid-column: 1 / -1;
            background: #fff8e6;
            border: 1px solid #f4de92;
            border-radius: 12px;
            padding: 14px;
        }

        .panel-nino-nuevo h3 {
            margin: 0 0 10px;
            font-size: 15px;
            color: #92400e;
        }

        .badge-nuevo {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .req { color: var(--err); }

        .campo-registrado[readonly],
        .campo-registrado:disabled {
            background: #f1f5f9;
            color: #334155;
            cursor: not-allowed;
        }

        .panel-ya-registrado {
            grid-column: 1 / -1;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 12px;
            padding: 14px;
            color: #065f46;
            font-size: 14px;
        }

        .hint-inline {
            display: block;
            margin-top: 6px;
            color: #5e6f83;
            font-size: 12px;
        }

        @media (max-width: 600px) {
            .hero h1 { font-size: 23px; }
            .content { padding: 16px; }
            form { grid-template-columns: 1fr; }
            .codigo-box strong { font-size: 25px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>Registro de ninos - Teens / Kids</h1>
            <p>Registro de asistencia cada semana. Busca al niño por documento para cargar sus datos o registrar uno nuevo.</p>
        </div>

        <div class="content">
            <?php if (!empty($mensaje ?? '')): ?>
                <div class="alert <?= (($tipo ?? '') === 'success') ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars((string)$mensaje, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($codigo ?? '')): ?>
                <div class="codigo-box">
                    Codigo semanal
                    <strong><?= htmlspecialchars((string)$codigo, ENT_QUOTES, 'UTF-8') ?></strong>
                    <div>Guardalo. Cada vez que te registras se genera un código nuevo para esta semana.</div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= PUBLIC_URL ?>index.php?url=teen/guardar-menor-publico" id="formRegistroPublicoTeen">
                <input type="hidden" id="id_menor_existente" name="id_menor_existente" value="<?= htmlspecialchars((string)($old['id_menor_existente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="es_nuevo" name="es_nuevo" value="<?= htmlspecialchars((string)($old['es_nuevo'] ?? '1'), ENT_QUOTES, 'UTF-8') ?>">

                <div class="full">
                    <label for="documento">Documento del niño <span class="req">*</span></label>
                    <div class="input-with-btn">
                        <input type="text" id="documento" name="documento" required maxlength="40" inputmode="numeric"
                               placeholder="Número de cédula o documento"
                               value="<?= htmlspecialchars((string)($old['documento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="btn btn-buscar" id="btn_buscar_documento">Buscar</button>
                    </div>
                    <small id="documento_lookup_info" style="display:block; margin-top:6px; color:#5e6f83;">Busca por documento para cargar datos de semanas anteriores.</small>
                </div>

                <div>
                    <label for="nombre_menor">Nombre y apellido del nino <span class="req">*</span></label>
                    <input type="text" id="nombre_menor" name="nombre_menor" required value="<?= htmlspecialchars((string)($old['nombre_menor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="js-upper">
                </div>

                <div>
                    <label for="nombre_acudiente">Nombre del acudiente (este domingo) <span class="req">*</span></label>
                    <input type="text" id="nombre_acudiente" name="nombre_acudiente" required value="<?= htmlspecialchars((string)($old['nombre_acudiente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="js-upper">
                    <small class="hint-inline">Editable: puede ser mamá, papá u otro acudiente según quien registre.</small>
                </div>

                <div>
                    <label for="telefono_contacto">Telefono del acudiente <span class="req">*</span></label>
                    <input type="tel" id="telefono_contacto" name="telefono_contacto" required maxlength="15"
                           inputmode="numeric" pattern="[0-9\s\-()]{10,15}"
                           placeholder="10 dígitos"
                           value="<?= htmlspecialchars((string)($old['telefono_contacto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <small id="telefono_lookup_info" style="display:block; margin-top:6px; color:#5e6f83;">Teléfono de quien registra hoy (10 dígitos).</small>
                </div>

                <div>
                    <label for="fecha_nacimiento">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required value="<?= htmlspecialchars((string)($old['fecha_nacimiento'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div>
                    <label for="edad">Edad</label>
                    <input type="number" id="edad" name="edad" min="0" max="17" readonly required value="<?= htmlspecialchars((string)($old['edad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div>
                    <label for="id_ministerio">Ministerio al que pertenece <span class="req">*</span></label>
                    <select id="id_ministerio" name="id_ministerio" required>
                        <option value="">Selecciona...</option>
                        <?php foreach (($ministerios ?? []) as $ministerio): ?>
                            <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= (string)($old['id_ministerio'] ?? '') === (string)$ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$ministerio['Nombre_Ministerio'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div id="panel_ya_registrado" class="panel-ya-registrado" style="display:none;">
                    <strong>✓ Niño ya registrado</strong>
                    <div id="texto_ya_registrado" style="margin-top:6px;">
                        Datos del niño cargados. Puedes cambiar <strong>acudiente y teléfono</strong> de este domingo.
                        Al guardar solo se renueva el <strong>código semanal</strong> (no se duplica por documento).
                    </div>
                </div>

                <div id="panel_nino_nuevo" class="panel-nino-nuevo" style="display:<?= (($old['es_nuevo'] ?? '1') === '0') ? 'none' : '' ?>;">
                    <span class="badge-nuevo">Niño nuevo</span>
                    <h3>Datos adicionales para primer registro</h3>
                    <div>
                        <label for="invitado_por">¿Quién lo invitó? <span class="req">*</span></label>
                        <input type="text" id="invitado_por" name="invitado_por" maxlength="180"
                               placeholder="Nombre de quien invitó al niño"
                               value="<?= htmlspecialchars((string)($old['invitado_por'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="js-upper">
                    </div>
                    <small style="display:block;margin-top:8px;color:#5e6f83;">Indica también el ministerio al que pertenece en el campo de arriba.</small>
                </div>

                <div>
                    <label for="asiste_celula">Asiste a celula</label>
                    <?php $valorAsiste = strtoupper((string)($old['asiste_celula'] ?? '')); ?>
                    <select id="asiste_celula" name="asiste_celula" required>
                        <option value="">Selecciona...</option>
                        <option value="SI" <?= $valorAsiste === 'SI' ? 'selected' : '' ?>>Si</option>
                        <option value="NO" <?= $valorAsiste === 'NO' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>

                <div class="full">
                    <label for="barrio">Barrio</label>
                    <input type="text" id="barrio" name="barrio" value="<?= htmlspecialchars((string)($old['barrio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="js-upper">
                </div>

                <div class="full actions">
                    <button type="submit" class="btn btn-primary">Guardar registro</button>
                    <a href="<?= PUBLIC_URL ?>index.php?url=teen/consulta-codigo" class="btn btn-secondary">Consultar codigo</a>
                </div>

                <div id="alerta_registro_existente" class="full alert alert-info" style="display:none; margin-top:0;">
                    Buscando registro...
                </div>
            </form>

            <p class="hint">
                Nota: el registro se renueva cada semana (~cada 8 días). Si el niño ya existe, busca por documento y solo se actualiza la asistencia con un código nuevo.
            </p>
        </div>
    </div>

    <script>
        (function () {
            function calcularEdad(fechaTexto) {
                if (!fechaTexto) {
                    return '';
                }

                var fecha = new Date(fechaTexto + 'T00:00:00');
                if (Number.isNaN(fecha.getTime())) {
                    return '';
                }

                var hoy = new Date();
                var edad = hoy.getFullYear() - fecha.getFullYear();
                var mes = hoy.getMonth() - fecha.getMonth();

                if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
                    edad--;
                }

                return edad >= 0 ? edad : '';
            }

            var documentoInput = document.getElementById('documento');
            var btnBuscarDocumento = document.getElementById('btn_buscar_documento');
            var documentoLookupInfo = document.getElementById('documento_lookup_info');
            var fechaNacimientoInput = document.getElementById('fecha_nacimiento');
            var edadInput = document.getElementById('edad');
            var telefonoInput = document.getElementById('telefono_contacto');
            var nombreMenorInput = document.getElementById('nombre_menor');
            var nombreAcudienteInput = document.getElementById('nombre_acudiente');
            var ministerioInput = document.getElementById('id_ministerio');
            var asisteCelulaInput = document.getElementById('asiste_celula');
            var barrioInput = document.getElementById('barrio');
            var invitadoPorInput = document.getElementById('invitado_por');
            var idMenorExistenteInput = document.getElementById('id_menor_existente');
            var esNuevoInput = document.getElementById('es_nuevo');
            var panelNinoNuevo = document.getElementById('panel_nino_nuevo');
            var panelYaRegistrado = document.getElementById('panel_ya_registrado');
            var textoYaRegistrado = document.getElementById('texto_ya_registrado');
            var alertaRegistroExistente = document.getElementById('alerta_registro_existente');
            var form = document.getElementById('formRegistroPublicoTeen');
            var ultimoDocumentoConsultado = '';
            var URL_BUSCAR_DOCUMENTO = <?= json_encode(public_app_url('teen/buscar-menor-publico-documento'), JSON_UNESCAPED_UNICODE) ?>;

            var camposSoloLecturaNino = [
                'nombre_menor', 'fecha_nacimiento', 'edad', 'barrio'
            ];

            var camposRequeridosSoloNuevo = [
                'nombre_menor', 'fecha_nacimiento', 'id_ministerio', 'asiste_celula'
            ];

            function normalizarTelefono(valor) {
                return String(valor || '').replace(/\D+/g, '');
            }

            function normalizarDocumento(valor) {
                return String(valor || '').replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
            }

            function bloquearCamposRegistrado(bloquear) {
                camposSoloLecturaNino.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    if (bloquear) {
                        el.classList.add('campo-registrado');
                        el.readOnly = el.tagName === 'INPUT';
                        el.disabled = el.tagName === 'SELECT';
                    } else {
                        el.classList.remove('campo-registrado');
                        el.readOnly = id === 'edad';
                        el.disabled = false;
                    }
                });

                camposRequeridosSoloNuevo.forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    if (bloquear) {
                        el.removeAttribute('required');
                    } else {
                        el.setAttribute('required', 'required');
                    }
                });

                if (invitadoPorInput) {
                    invitadoPorInput.required = !bloquear;
                }
            }

            function setEsNuevo(esNuevo) {
                if (esNuevoInput) {
                    esNuevoInput.value = esNuevo ? '1' : '0';
                }
                if (panelNinoNuevo) {
                    panelNinoNuevo.style.display = esNuevo ? '' : 'none';
                }
                if (panelYaRegistrado) {
                    panelYaRegistrado.style.display = esNuevo ? 'none' : '';
                }
                if (invitadoPorInput) {
                    invitadoPorInput.required = esNuevo;
                }
                bloquearCamposRegistrado(!esNuevo);
            }

            function ocultarAlertaExistente(limpiarId) {
                if (alertaRegistroExistente) {
                    alertaRegistroExistente.style.display = 'none';
                }
                if (limpiarId && idMenorExistenteInput) {
                    idMenorExistenteInput.value = '';
                }
            }

            function mostrarAlertaExistente(texto) {
                if (alertaRegistroExistente) {
                    alertaRegistroExistente.style.display = 'block';
                    alertaRegistroExistente.textContent = texto;
                }
            }

            function llenarDatosMenor(data, esNuevo) {
                if (!data) {
                    return;
                }

                if (documentoInput && data.documento) {
                    documentoInput.value = String(data.documento);
                }
                if (nombreMenorInput && data.nombre_menor) {
                    nombreMenorInput.value = String(data.nombre_menor).toUpperCase();
                }
                if (nombreAcudienteInput && data.nombre_acudiente) {
                    nombreAcudienteInput.value = String(data.nombre_acudiente).toUpperCase();
                }
                if (telefonoInput && data.telefono_contacto) {
                    telefonoInput.value = String(data.telefono_contacto);
                }
                if (fechaNacimientoInput && data.fecha_nacimiento) {
                    fechaNacimientoInput.value = String(data.fecha_nacimiento);
                }
                if (edadInput && typeof data.edad !== 'undefined') {
                    edadInput.value = String(data.edad || '');
                }
                if (ministerioInput && data.id_ministerio) {
                    ministerioInput.value = String(data.id_ministerio);
                }
                if (asisteCelulaInput && data.asiste_celula) {
                    asisteCelulaInput.value = String(data.asiste_celula).toUpperCase();
                }
                if (barrioInput && data.barrio) {
                    barrioInput.value = String(data.barrio).toUpperCase();
                }
                if (invitadoPorInput && data.invitado_por) {
                    invitadoPorInput.value = String(data.invitado_por).toUpperCase();
                }
                if (idMenorExistenteInput && data.id) {
                    idMenorExistenteInput.value = String(data.id);
                } else if (idMenorExistenteInput && !esNuevo) {
                    idMenorExistenteInput.value = '';
                }

                setEsNuevo(esNuevo);
                actualizarEdad();
            }

            function consultarDocumentoExistente(forzar) {
                if (!documentoInput) {
                    return;
                }

                var docNorm = normalizarDocumento(documentoInput.value);
                if (docNorm.length < 3) {
                    ocultarAlertaExistente(false);
                    setEsNuevo(true);
                    ultimoDocumentoConsultado = '';
                    if (documentoLookupInfo) {
                        documentoLookupInfo.textContent = 'Escribe al menos 3 caracteres del documento.';
                    }
                    return;
                }

                if (!forzar && docNorm === ultimoDocumentoConsultado) {
                    return;
                }
                ultimoDocumentoConsultado = docNorm;

                if (documentoLookupInfo) {
                    documentoLookupInfo.textContent = 'Buscando…';
                }
                if (btnBuscarDocumento) {
                    btnBuscarDocumento.disabled = true;
                }

                var url = URL_BUSCAR_DOCUMENTO
                    + (URL_BUSCAR_DOCUMENTO.indexOf('?') >= 0 ? '&' : '?')
                    + 'documento=' + encodeURIComponent(docNorm);

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then(function (res) {
                        if (!res.ok) {
                            throw new Error('HTTP ' + res.status);
                        }
                        var ct = res.headers.get('content-type') || '';
                        if (ct.indexOf('application/json') === -1) {
                            throw new Error('Respuesta no JSON');
                        }
                        return res.json();
                    })
                    .then(function (res) {
                        if (btnBuscarDocumento) {
                            btnBuscarDocumento.disabled = false;
                        }
                        if (!res || !res.success) {
                            ocultarAlertaExistente(true);
                            setEsNuevo(true);
                            if (documentoLookupInfo) {
                                documentoLookupInfo.textContent = (res && res.message) ? res.message : 'No se pudo buscar.';
                            }
                            return;
                        }

                        if (!res.found) {
                            ocultarAlertaExistente(true);
                            setEsNuevo(true);
                            if (documentoLookupInfo) {
                                documentoLookupInfo.textContent = res.mensaje || 'Documento no encontrado. Completa los datos del niño nuevo.';
                            }
                            mostrarAlertaExistente(res.mensaje || 'Niño nuevo: indica quién lo invitó y el ministerio.');
                            return;
                        }

                        llenarDatosMenor(res.data || {}, res.es_nuevo !== false);

                        var texto = res.mensaje || 'Datos cargados.';
                        if (!res.es_nuevo && res.data) {
                            if (res.data.codigo_semana) {
                                texto += ' Código semanal vigente: ' + String(res.data.codigo_semana) + '.';
                            }
                            if (res.data.nombre_ministerio) {
                                texto += ' Ministerio: ' + String(res.data.nombre_ministerio) + '.';
                            }
                            if (textoYaRegistrado) {
                                textoYaRegistrado.textContent = texto;
                            }
                        }
                        mostrarAlertaExistente(texto);
                        if (documentoLookupInfo) {
                            documentoLookupInfo.textContent = res.es_nuevo
                                ? 'Encontrado en directorio. Completa los datos faltantes.'
                                : 'Registro existente cargado. Solo se renovará el código semanal al guardar.';
                        }
                    })
                    .catch(function () {
                        if (btnBuscarDocumento) {
                            btnBuscarDocumento.disabled = false;
                        }
                        ultimoDocumentoConsultado = '';
                        ocultarAlertaExistente(false);
                        setEsNuevo(true);
                        if (documentoLookupInfo) {
                            documentoLookupInfo.textContent = 'Error al buscar. Verifica la conexión e intenta de nuevo.';
                        }
                    });
            }

            function actualizarEdad() {
                if (!fechaNacimientoInput || !edadInput) {
                    return;
                }
                edadInput.value = calcularEdad(fechaNacimientoInput.value);
            }

            if (fechaNacimientoInput) {
                fechaNacimientoInput.addEventListener('change', actualizarEdad);
                fechaNacimientoInput.addEventListener('input', actualizarEdad);
            }
            actualizarEdad();

            if (btnBuscarDocumento) {
                btnBuscarDocumento.addEventListener('click', function () {
                    consultarDocumentoExistente(true);
                });
            }
            if (documentoInput) {
                documentoInput.addEventListener('blur', function () {
                    consultarDocumentoExistente(false);
                });
                documentoInput.addEventListener('input', function () {
                    ultimoDocumentoConsultado = '';
                });
                documentoInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        consultarDocumentoExistente(true);
                    }
                });
            }

            if (telefonoInput) {
                telefonoInput.addEventListener('input', function () {
                    var digits = normalizarTelefono(telefonoInput.value);
                    if (digits.length > 10) {
                        telefonoInput.value = digits.slice(0, 10);
                    }
                });
            }

            if (form) {
                form.addEventListener('submit', function (e) {
                    var esRegistrado = esNuevoInput && esNuevoInput.value === '0';
                    var idMenor = idMenorExistenteInput ? parseInt(idMenorExistenteInput.value || '0', 10) : 0;
                    var doc = normalizarDocumento(documentoInput ? documentoInput.value : '');
                    var tel = normalizarTelefono(telefonoInput ? telefonoInput.value : '');
                    var nombre = String(nombreMenorInput ? nombreMenorInput.value : '').trim();

                    if (doc.length < 5) {
                        e.preventDefault();
                        alert('El documento del niño es obligatorio (mínimo 5 caracteres).');
                        return;
                    }
                    if (esRegistrado && idMenor <= 0) {
                        e.preventDefault();
                        alert('Debes buscar al niño por documento antes de renovar el código semanal.');
                        return;
                    }
                    if (!esRegistrado && tel.length !== 10) {
                        e.preventDefault();
                        alert('El teléfono debe tener exactamente 10 dígitos.');
                        return;
                    }
                    if (esRegistrado && tel.length !== 10) {
                        e.preventDefault();
                        alert('El teléfono del acudiente debe tener 10 dígitos.');
                        return;
                    }
                    if (!esRegistrado && nombre === '') {
                        e.preventDefault();
                        alert('El nombre del niño es obligatorio.');
                        return;
                    }
                    var acudiente = String(nombreAcudienteInput ? nombreAcudienteInput.value : '').trim();
                    if (acudiente === '') {
                        e.preventDefault();
                        alert('El nombre del acudiente es obligatorio.');
                        return;
                    }
                    if (esNuevoInput && esNuevoInput.value === '1') {
                        var invitador = String(invitadoPorInput ? invitadoPorInput.value : '').trim();
                        if (invitador === '') {
                            e.preventDefault();
                            alert('Indica quién invitó al niño.');
                            return;
                        }
                    }
                    if (telefonoInput && tel !== '') {
                        telefonoInput.value = tel;
                    }

                    camposSoloLecturaNino.forEach(function (id) {
                        var el = document.getElementById(id);
                        if (el) {
                            el.readOnly = false;
                            el.disabled = false;
                        }
                    });
                });
            }

            setEsNuevo((esNuevoInput && esNuevoInput.value !== '0'));

            var camposUpper = document.querySelectorAll('.js-upper');
            camposUpper.forEach(function (campo) {
                campo.style.textTransform = 'uppercase';
                var transformar = function () {
                    campo.value = String(campo.value || '').toUpperCase();
                };
                campo.addEventListener('input', transformar);
                campo.addEventListener('change', transformar);
                transformar();
            });

            if (documentoInput && documentoInput.value.trim() !== '') {
                consultarDocumentoExistente();
            }
        })();
    </script>
</body>
</html>
