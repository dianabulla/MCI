<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testigos Electorales - Nehemias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --te-blue: #0b4aa2;
            --te-orange: #f37021;
            --te-orange-dark: #d85f12;
        }
        body {
            background: var(--te-blue);
            min-height: 100vh;
            padding: 30px 15px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #1a1a1a;
        }
        .te-container {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
            overflow: hidden;
        }
        .te-header {
            background: var(--te-orange);
            color: #fff;
            padding: 26px 30px;
        }
        .te-header h1 {
            margin: 0 0 8px 0;
            font-size: 28px;
            font-weight: 700;
        }
        .te-header p {
            margin: 0;
            font-size: 15px;
            opacity: 0.95;
        }
        .te-body {
            padding: 30px;
        }
        .required {
            color: #c1121f;
        }
        .form-label {
            font-weight: 600;
        }
        .btn-primary {
            background: var(--te-orange);
            border-color: var(--te-orange);
            font-weight: 600;
        }
        .btn-primary:hover {
            background: var(--te-orange-dark);
            border-color: var(--te-orange-dark);
        }
        @media (max-width: 576px) {
            body { padding: 15px 10px; }
            .te-container { border-radius: 12px; }
            .te-header { padding: 20px; }
            .te-header h1 { font-size: 24px; }
            .te-body { padding: 20px; }
            .form-control, .form-select { font-size: 16px; padding: 12px; }
            .btn-primary { font-size: 17px; padding: 14px; }
        }
    </style>
</head>
<body>
    <div class="te-container">
        <div class="te-header">
            <h1>Registro de Testigos Electorales</h1>
            <p>Completa la información para reportar los votos contados por puesto de votación.</p>
        </div>

        <div class="te-body">
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?= ($tipo_mensaje ?? '') === 'success' ? 'success' : 'danger' ?>">
                    <?= htmlspecialchars((string)$mensaje) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="?url=nehemias/testigos-electorales/guardar" enctype="multipart/form-data" id="formTestigosElectorales">
                <div class="mb-3">
                    <label class="form-label">Testigo <span class="required">*</span></label>
                    <input type="text" name="testigo_nombre" class="form-control" required maxlength="180" placeholder="Nombre completo del testigo">
                </div>

                <div class="mb-3">
                    <label class="form-label">Puesto de votación <span class="required">*</span></label>
                    <select name="puesto_votacion" id="puesto_votacion" class="form-select" required>
                        <option value="">Seleccione un puesto</option>
                        <?php foreach (($puestosVotacion ?? []) as $puesto): ?>
                            <option value="<?= htmlspecialchars((string)$puesto) ?>"><?= htmlspecialchars((string)$puesto) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3" id="observaciones_wrap" style="display:none;">
                    <label class="form-label">Observaciones <span class="required">*</span></label>
                    <textarea name="observaciones" id="observaciones" class="form-control" rows="3" maxlength="500" placeholder="Escribe el puesto de votación u observación adicional"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mesa de votación <span class="required">*</span></label>
                    <input type="text" name="mesa_votacion" class="form-control" required maxlength="100" placeholder="Ej: 12">
                </div>

                <div class="mb-3">
                    <label class="form-label">Votos contados para CAMARA - Yancly Escobar <span class="required">*</span></label>
                    <input type="number" name="votos_camara" class="form-control" required min="0" step="1" placeholder="0">
                </div>

                <div class="mb-4">
                    <label class="form-label">Foto de CAMARA - Yancly Escobar</label>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" id="btn_tomar_foto_camara" class="btn btn-outline-secondary btn-sm">Tomar foto</button>
                        <button type="button" id="btn_galeria_camara" class="btn btn-outline-secondary btn-sm">Elegir de galería</button>
                    </div>
                    <input type="file" name="foto_camara" id="foto_camara" class="form-control" accept="image/*">
                    <small id="foto_nombre_camara" class="text-muted d-block mt-1">Sin archivo seleccionado</small>
                    <div id="foto_alerta_camara" class="alert alert-danger mt-2" style="display:none; margin-bottom:0;">Debes adjuntar una foto válida de CAMARA</div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Votos contados para SENADO - Sara Castellanos <span class="required">*</span></label>
                    <input type="number" name="votos_senado" class="form-control" required min="0" step="1" placeholder="0">
                </div>

                <div class="mb-4">
                    <label class="form-label">Foto de SENADO - Sara Castellanos</label>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" id="btn_tomar_foto_senado" class="btn btn-outline-secondary btn-sm">Tomar foto</button>
                        <button type="button" id="btn_galeria_senado" class="btn btn-outline-secondary btn-sm">Elegir de galería</button>
                    </div>
                    <input type="file" name="foto_senado" id="foto_senado" class="form-control" accept="image/*">
                    <small id="foto_nombre_senado" class="text-muted d-block mt-1">Sin archivo seleccionado</small>
                    <div id="foto_alerta_senado" class="alert alert-danger mt-2" style="display:none; margin-bottom:0;">Debes adjuntar una foto válida de SENADO</div>
                </div>

                <button type="submit" id="btn_enviar_reporte" class="btn btn-primary w-100" disabled>Enviar reporte</button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var puesto = document.getElementById('puesto_votacion');
            var observacionesWrap = document.getElementById('observaciones_wrap');
            var observaciones = document.getElementById('observaciones');
            var fotoCamaraInput = document.getElementById('foto_camara');
            var fotoCamaraAlerta = document.getElementById('foto_alerta_camara');
            var fotoNombreCamara = document.getElementById('foto_nombre_camara');
            var btnTomarFotoCamara = document.getElementById('btn_tomar_foto_camara');
            var btnGaleriaCamara = document.getElementById('btn_galeria_camara');
            var fotoSenadoInput = document.getElementById('foto_senado');
            var fotoSenadoAlerta = document.getElementById('foto_alerta_senado');
            var fotoNombreSenado = document.getElementById('foto_nombre_senado');
            var btnTomarFotoSenado = document.getElementById('btn_tomar_foto_senado');
            var btnGaleriaSenado = document.getElementById('btn_galeria_senado');
            var form = document.getElementById('formTestigosElectorales');
            var btnEnviar = document.getElementById('btn_enviar_reporte');
            var fotoCamaraValida = true;
            var fotoSenadoValida = true;
            var enviandoValidado = false;

            function actualizarNombreArchivo(input, label) {
                if (!label) {
                    return;
                }
                if (!input || !input.files || input.files.length === 0) {
                    label.textContent = 'Sin archivo seleccionado';
                    return;
                }
                label.textContent = input.files[0].name || 'Archivo seleccionado';
            }

            function abrirSelectorFoto(input, usarCamara) {
                if (!input) {
                    return;
                }
                if (usarCamara) {
                    input.setAttribute('capture', 'environment');
                } else {
                    input.removeAttribute('capture');
                }
                input.click();
            }

            function mostrarAlertaFoto(alerta, mostrar) {
                if (!alerta) {
                    return;
                }
                alerta.style.display = mostrar ? 'block' : 'none';
            }

            function setMensajeAlertaFoto(alerta, texto) {
                if (!alerta) {
                    return;
                }
                alerta.textContent = texto;
            }

            function actualizarEstadoBoton() {
                if (!btnEnviar) {
                    return;
                }
                btnEnviar.disabled = !(fotoCamaraValida && fotoSenadoValida);
            }

            async function validarFotoSeleccionada(input, alerta, mensajeBase) {
                if (!input || !input.files || input.files.length === 0) {
                    setMensajeAlertaFoto(alerta, mensajeBase);
                    mostrarAlertaFoto(alerta, false);
                    actualizarEstadoBoton();
                    return true;
                }

                var file = input.files[0];
                if (!file.type || file.type.indexOf('image/') !== 0) {
                    setMensajeAlertaFoto(alerta, 'Debes seleccionar un archivo de imagen válido.');
                    mostrarAlertaFoto(alerta, true);
                    actualizarEstadoBoton();
                    return false;
                }

                var valida = true;

                if (!valida) {
                    if (!alerta.textContent || alerta.textContent === mensajeBase) {
                        setMensajeAlertaFoto(alerta, mensajeBase);
                    }
                    mostrarAlertaFoto(alerta, true);
                    actualizarEstadoBoton();
                    return false;
                }

                setMensajeAlertaFoto(alerta, mensajeBase);
                mostrarAlertaFoto(alerta, false);
                actualizarEstadoBoton();
                return true;
            }

            function toggleObservaciones() {
                var esOtros = puesto && puesto.value === 'OTROS';
                if (observacionesWrap) {
                    observacionesWrap.style.display = esOtros ? 'block' : 'none';
                }
                if (observaciones) {
                    observaciones.required = esOtros;
                    if (!esOtros) {
                        observaciones.value = '';
                    }
                }
            }

            if (puesto) {
                puesto.addEventListener('change', toggleObservaciones);
                toggleObservaciones();
            }

            if (fotoCamaraInput) {
                fotoCamaraInput.addEventListener('change', async function () {
                    actualizarNombreArchivo(fotoCamaraInput, fotoNombreCamara);
                    fotoCamaraValida = await validarFotoSeleccionada(fotoCamaraInput, fotoCamaraAlerta, 'Debes adjuntar una foto válida de CAMARA');
                    actualizarEstadoBoton();
                });
            }

            if (btnTomarFotoCamara) {
                btnTomarFotoCamara.addEventListener('click', function () {
                    abrirSelectorFoto(fotoCamaraInput, true);
                });
            }

            if (btnGaleriaCamara) {
                btnGaleriaCamara.addEventListener('click', function () {
                    abrirSelectorFoto(fotoCamaraInput, false);
                });
            }

            if (fotoSenadoInput) {
                fotoSenadoInput.addEventListener('change', async function () {
                    actualizarNombreArchivo(fotoSenadoInput, fotoNombreSenado);
                    fotoSenadoValida = await validarFotoSeleccionada(fotoSenadoInput, fotoSenadoAlerta, 'Debes adjuntar una foto válida de SENADO');
                    actualizarEstadoBoton();
                });
            }

            if (btnTomarFotoSenado) {
                btnTomarFotoSenado.addEventListener('click', function () {
                    abrirSelectorFoto(fotoSenadoInput, true);
                });
            }

            if (btnGaleriaSenado) {
                btnGaleriaSenado.addEventListener('click', function () {
                    abrirSelectorFoto(fotoSenadoInput, false);
                });
            }

            actualizarNombreArchivo(fotoCamaraInput, fotoNombreCamara);
            actualizarNombreArchivo(fotoSenadoInput, fotoNombreSenado);
            actualizarEstadoBoton();

            if (form) {
                form.addEventListener('submit', async function (e) {
                    if (enviandoValidado) {
                        return;
                    }

                    e.preventDefault();
                    fotoCamaraValida = await validarFotoSeleccionada(fotoCamaraInput, fotoCamaraAlerta, 'Debes adjuntar una foto válida de CAMARA');
                    fotoSenadoValida = await validarFotoSeleccionada(fotoSenadoInput, fotoSenadoAlerta, 'Debes adjuntar una foto válida de SENADO');

                    if (!fotoCamaraValida || !fotoSenadoValida) {
                        actualizarEstadoBoton();
                        return;
                    }

                    enviandoValidado = true;
                    form.submit();
                });
            }
        })();
    </script>
</body>
</html>
