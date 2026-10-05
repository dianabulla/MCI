<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Nuevos - MCI Madrid</title>
    <style>
        :root {
            --primary: #3562ad;
            --primary-dark: #2b4f8f;
            --primary-soft: #f3f7ff;
            --text-main: #2e3550;
            --text-title: #2f446e;
            --border: #dce5f5;
            --danger-bg: #FFF2F2;
            --danger-text: #A84747;
            --success-bg: #e3f4eb;
            --success-text: #1e7a51;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(180deg, #f5f8ff 0%, #eef3fb 100%);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 12px;
        }

        .container {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(32, 68, 126, 0.12);
        }

        .header {
            padding: 28px 24px;
            background: linear-gradient(135deg, var(--primary-soft) 0%, #ffffff 100%);
            border-bottom: 1px solid var(--border);
        }

        .eyebrow {
            margin: 0 0 8px;
            color: var(--primary);
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 15px;
        }

        h1 {
            margin: 0;
            color: var(--text-title);
            font-size: 30px;
            line-height: 1.2;
        }

        .sub {
            margin: 10px 0 0;
            color: var(--text-main);
            font-size: 15px;
        }

        .body {
            padding: 24px;
        }

        .alert {
            border-radius: 10px;
            padding: 12px 14px;
            margin: 0 0 16px;
            border: 1px solid transparent;
            font-size: 14px;
        }

        .alert.error {
            background: var(--danger-bg);
            color: var(--danger-text);
            border-color: #F7D7D7;
        }

        .alert.success {
            background: var(--success-bg);
            color: var(--success-text);
            border-color: #CFEFDF;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            color: var(--text-title);
            font-weight: 600;
            font-size: 14px;
        }

        .req {
            color: #c62828;
            font-weight: 700;
        }

        .form-req-note {
            margin: 0 0 16px;
            font-size: 14px;
            color: #475569;
        }

        .field-hint {
            display: block;
            margin-top: 6px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.35;
        }

        .field-error {
            display: block;
            margin-top: 6px;
            font-size: 13px;
            color: #b91c1c;
            font-weight: 600;
        }

        input.input-invalid,
        select.input-invalid {
            border-color: #dc2626;
            background: #fff7f7;
        }

        .field-pair {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            align-items: start;
        }

        input,
        select,
        textarea {
            border: 1px solid #dbe7f7;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 15px;
            color: #475063;
            background: #fff;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        textarea {
            min-height: 92px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(53, 98, 173, 0.15);
        }

        .hint {
            margin-top: 10px;
            font-size: 13px;
            color: #8B92A1;
        }

        .tipo-persona-options {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .tipo-persona-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: 600;
            color: #2e2a24;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            justify-content: flex-end;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            color: #fff;
            background: linear-gradient(135deg, var(--primary) 0%, #4b73bb 100%);
            box-shadow: 0 10px 20px rgba(53, 98, 173, 0.2);
        }

        .btn:hover {
            filter: brightness(0.98);
        }

        .btn:active {
            transform: translateY(1px);
        }

        .success-box {
            border: 1px solid #b8e2c9;
            background: #f3fbf6;
            border-radius: 12px;
            padding: 18px;
        }

        .success-actions {
            margin-top: 14px;
        }

        @media (max-width: 720px) {
            h1 {
                font-size: 24px;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .field-pair {
                grid-template-columns: 1fr;
            }

            .actions {
                justify-content: stretch;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <p class="eyebrow">Registro Público</p>
        <h1>Nuevas Personas</h1>
        <p class="sub">Formulario para el registro de nuevos los domingos. Quedan en pendiente por consolidar.</p>
    </div>

    <div class="body">
        <?php if (!empty($mensaje)): ?>
            <div class="alert <?= ($tipo_mensaje ?? '') === 'success' ? 'success' : 'error' ?>">
                <?= htmlspecialchars((string)$mensaje) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($registro_exitoso)): ?>
            <div class="success-box">
                <strong>Registro completado.</strong>
                <div class="success-actions">
                    <a class="btn" href="<?= PUBLIC_URL ?>?url=registro_personas" style="display:inline-block; text-decoration:none;">Registrar otra persona</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST" action="<?= PUBLIC_URL ?>?url=registro_personas/guardar" id="registro_persona_form">
                <p class="form-req-note">Los campos marcados con <span class="req">*</span> son obligatorios. En <strong>Nombre</strong> escriba solo el o los nombres; en <strong>Apellidos</strong> el primer y segundo apellido, sin usar X ni valores de relleno.</p>
                <div class="grid">
                    <div class="field">
                        <label for="nombre">Nombre <span class="req">*</span></label>
                        <input type="text" id="nombre" name="nombre" required autocomplete="given-name" maxlength="60"
                               placeholder="Ej: JUAN CARLOS"
                               value="<?= htmlspecialchars((string)($old['nombre'] ?? '')) ?>">
                        <small class="field-hint">Solo nombres. No incluya apellidos.</small>
                        <small class="field-error" id="nombre_error" hidden></small>
                    </div>

                    <div class="field">
                        <label for="apellido">Apellidos <span class="req">*</span></label>
                        <input type="text" id="apellido" name="apellido" required autocomplete="family-name" maxlength="80"
                               placeholder="Ej: PÉREZ GÓMEZ"
                               value="<?= htmlspecialchars((string)($old['apellido'] ?? '')) ?>">
                        <small class="field-hint">Primer y segundo apellido. No use X, N/A ni un punto.</small>
                        <small class="field-error" id="apellido_error" hidden></small>
                    </div>

                    <div class="field-pair">
                        <div class="field">
                            <label for="tipo_documento">Tipo de documento <span class="req">*</span></label>
                            <?php $tipoDocumento = (string)($old['tipo_documento'] ?? ''); ?>
                            <select id="tipo_documento" name="tipo_documento" required>
                                <option value="">Seleccione...</option>
                                <option value="Cedula de Ciudadania" <?= $tipoDocumento === 'Cedula de Ciudadania' ? 'selected' : '' ?>>Cédula de Ciudadanía</option>
                                <option value="Cedula Extranjera" <?= $tipoDocumento === 'Cedula Extranjera' ? 'selected' : '' ?>>Cédula Extranjera</option>
                                <option value="Tarjeta de Identidad" <?= $tipoDocumento === 'Tarjeta de Identidad' ? 'selected' : '' ?>>Tarjeta de Identidad</option>
                                <option value="Registro Civil" <?= $tipoDocumento === 'Registro Civil' ? 'selected' : '' ?>>Registro Civil</option>
                            </select>
                        </div>
                        <div class="field">
                            <label for="cedula">Número de documento <span class="req">*</span></label>
                            <input type="text" id="cedula" name="cedula" required value="<?= htmlspecialchars((string)($old['cedula'] ?? '')) ?>" placeholder="Número de documento">
                        </div>
                    </div>

                    <div class="field">
                        <label for="telefono">Teléfono <span class="req">*</span></label>
                        <input type="tel" id="telefono" name="telefono" required value="<?= htmlspecialchars((string)($old['telefono'] ?? '')) ?>" placeholder="Ej: 3001234567">
                    </div>

                    <div class="field">
                        <label for="fecha_nacimiento">Fecha de nacimiento <span class="req">*</span></label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" required value="<?= htmlspecialchars((string)($old['fecha_nacimiento'] ?? '')) ?>">
                    </div>

                    <div class="field">
                        <label for="genero">Género <span class="req">*</span></label>
                        <?php $generoOld = (string)($old['genero'] ?? ''); ?>
                        <select id="genero" name="genero" required>
                            <option value="">Seleccione...</option>
                            <option value="Hombre" <?= $generoOld === 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                            <option value="Mujer" <?= $generoOld === 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="id_ministerio">Ministerio</label>
                        <select id="id_ministerio" name="id_ministerio">
                            <option value="">Seleccione...</option>
                            <?php foreach (($ministerios ?? []) as $ministerio): ?>
                                <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= (string)($old['id_ministerio'] ?? '') === (string)$ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$ministerio['Nombre_Ministerio']) ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="otro" <?= (string)($old['id_ministerio'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="barrio">Barrio</label>
                        <input type="text" id="barrio" name="barrio" value="<?= htmlspecialchars((string)($old['barrio'] ?? '')) ?>" placeholder="Barrio (opcional)">
                    </div>

                    <div class="field">
                        <label for="invitado_por">Invitado por</label>
                        <input type="text" id="invitado_por" name="invitado_por" value="<?= htmlspecialchars((string)($old['invitado_por'] ?? '')) ?>" placeholder="Nombre de quien invitó (opcional)">
                    </div>

                    <div class="field full">
                        <label for="ganado_en">Ganado en <span class="req">*</span></label>
                        <select id="ganado_en" name="ganado_en" required>
                            <option value="">Seleccione...</option>
                            <option value="domingo" <?= (string)($old['ganado_en'] ?? '') === 'domingo' ? 'selected' : '' ?>>Domingo</option>
                            <option value="somos_uno" <?= (string)($old['ganado_en'] ?? '') === 'somos_uno' ? 'selected' : '' ?>>Somos Uno</option>
                            <option value="celula" <?= (string)($old['ganado_en'] ?? '') === 'celula' ? 'selected' : '' ?>>Célula</option>
                            <option value="otro" <?= (string)($old['ganado_en'] ?? '') === 'otro' ? 'selected' : '' ?>>Otros</option>
                        </select>
                    </div>

                    <input type="hidden" name="tipo_persona" value="nueva">

                    <div class="field full" id="ganado_en_otro_wrap" style="display:none;">
                        <label for="ganado_en_otro_observacion">Observaciones <span class="req">*</span></label>
                        <textarea id="ganado_en_otro_observacion" name="ganado_en_otro_observacion" rows="3" placeholder="Describe dónde fue ganado o la observación necesaria..."><?= htmlspecialchars((string)($old['ganado_en_otro_observacion'] ?? '')) ?></textarea>
                    </div>

                    <?php if (!empty($soportaTratamientoDatos)): ?>
                    <?php $tratamientoOld = strtolower(trim((string)($old['tratamiento_datos'] ?? ''))); ?>
                    <div class="field full">
                        <label>Tratamiento de datos <span class="req">*</span></label>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:6px;">
                            <label style="font-weight:500;display:flex;align-items:center;gap:8px;">
                                <input type="radio" name="tratamiento_datos" value="acepta" <?= $tratamientoOld === 'acepta' ? 'checked' : '' ?> required>
                                Acepto el tratamiento de mis datos personales
                            </label>
                            <label style="font-weight:500;display:flex;align-items:center;gap:8px;">
                                <input type="radio" name="tratamiento_datos" value="no acepta" <?= $tratamientoOld === 'no acepta' ? 'checked' : '' ?> required>
                                No acepto el tratamiento de mis datos personales
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <button type="submit" class="btn">Guardar registro</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<script>
(function() {
    const ganadoEn = document.getElementById('ganado_en');
    const observacionWrap = document.getElementById('ganado_en_otro_wrap');
    const observacion = document.getElementById('ganado_en_otro_observacion');

    function actualizarObservacionOtros() {
        if (!ganadoEn || !observacionWrap || !observacion) {
            return;
        }

        const esOtros = String(ganadoEn.value || '') === 'otro';
        observacionWrap.style.display = esOtros ? '' : 'none';
        observacion.required = esOtros;
    }

    function aplicarMayusculasAutomaticas() {
        const campos = document.querySelectorAll('input[type="text"], textarea');
        campos.forEach(function(campo) {
            if (!campo) {
                return;
            }

            campo.style.textTransform = 'uppercase';

            const transformar = function() {
                if (typeof campo.value === 'string') {
                    campo.value = campo.value.toUpperCase();
                }
            };

            campo.addEventListener('input', transformar);
            campo.addEventListener('change', transformar);
            transformar();
        });
    }

    aplicarMayusculasAutomaticas();

    if (ganadoEn) {
        ganadoEn.addEventListener('change', actualizarObservacionOtros);
        actualizarObservacionOtros();
    }

    function normalizarClaveNombrePersona(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z]/g, '');
    }

    function esParticulaNombrePersona(palabra) {
        return ['de', 'del', 'la', 'las', 'los', 'y', 'da', 'do', 'dos', 'das', 'van', 'von', 'san', 'santa', 'di', 'el', 'e']
            .indexOf(normalizarClaveNombrePersona(palabra)) !== -1;
    }

    function contarPalabrasSignificativasNombre(texto) {
        const palabras = String(texto || '').trim().split(/\s+/).filter(Boolean);
        let total = 0;
        palabras.forEach(function(palabra) {
            if (esParticulaNombrePersona(palabra)) {
                return;
            }
            const letras = palabra.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g, '');
            if (letras.length >= 2) {
                total += 1;
            }
        });
        return total;
    }

    function esMarcadorFalsoNombre(texto) {
        const clave = normalizarClaveNombrePersona(texto);
        const prohibidos = {
            x: 1, xx: 1, xxx: 1, xxxx: 1, xxxxx: 1,
            n: 1, na: 1, nd: 1, nn: 1, sn: 1, nna: 1, nde: 1,
            no: 1, si: 1, ok: 1, asd: 1, qwe: 1, qwer: 1, abc: 1, xyz: 1,
            aaa: 1, bbb: 1, ccc: 1, nnn: 1,
            test: 1, prueba: 1, temp: 1, fake: 1, none: 1, null: 1,
            nombre: 1, nombres: 1, apellido: 1, apellidos: 1,
            noaplica: 1, sinapellido: 1, desconocido: 1, ninguno: 1, ninguna: 1,
            pendiente: 1, completar: 1, tbd: 1, naa: 1
        };
        if (!clave || prohibidos[clave] || /^(x+|n+|a+|z+)$/.test(clave)) {
            return true;
        }
        const letras = String(texto || '').replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/g, '');
        return letras.length < 2;
    }

    function mostrarErrorCampoNombre(idCampo, idError, mensaje) {
        const campo = document.getElementById(idCampo);
        const errorEl = document.getElementById(idError);
        if (campo) {
            if (mensaje) {
                campo.classList.add('input-invalid');
            } else {
                campo.classList.remove('input-invalid');
            }
        }
        if (errorEl) {
            if (mensaje) {
                errorEl.textContent = mensaje;
                errorEl.hidden = false;
            } else {
                errorEl.textContent = '';
                errorEl.hidden = true;
            }
        }
    }

    const registroForm = document.getElementById('registro_persona_form');
    if (registroForm) {
        registroForm.addEventListener('submit', function(e) {
            const nombreInput = document.getElementById('nombre');
            const apellidoInput = document.getElementById('apellido');
            const nombre = nombreInput ? String(nombreInput.value || '').trim().replace(/\s+/g, ' ') : '';
            const apellido = apellidoInput ? String(apellidoInput.value || '').trim().replace(/\s+/g, ' ') : '';
            const regexLetras = /^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ'’\-\s]+$/;
            let ok = true;

            mostrarErrorCampoNombre('nombre', 'nombre_error', '');
            mostrarErrorCampoNombre('apellido', 'apellido_error', '');

            if (!nombre) {
                mostrarErrorCampoNombre('nombre', 'nombre_error', 'El nombre es requerido.');
                ok = false;
            } else if (!regexLetras.test(nombre)) {
                mostrarErrorCampoNombre('nombre', 'nombre_error', 'El nombre solo puede tener letras.');
                ok = false;
            } else if (esMarcadorFalsoNombre(nombre)) {
                mostrarErrorCampoNombre('nombre', 'nombre_error', 'Escriba un nombre real. No use X, N/A o prueba.');
                ok = false;
            } else if (contarPalabrasSignificativasNombre(nombre) > 3) {
                mostrarErrorCampoNombre('nombre', 'nombre_error', 'En Nombre escriba solo el o los nombres, no los apellidos.');
                ok = false;
            }

            if (!apellido) {
                mostrarErrorCampoNombre('apellido', 'apellido_error', 'Los apellidos son requeridos.');
                ok = false;
            } else if (!regexLetras.test(apellido)) {
                mostrarErrorCampoNombre('apellido', 'apellido_error', 'Los apellidos solo pueden tener letras.');
                ok = false;
            } else if (esMarcadorFalsoNombre(apellido)) {
                mostrarErrorCampoNombre('apellido', 'apellido_error', 'Escriba los apellidos reales. No use X, N/A ni un punto.');
                ok = false;
            } else if (contarPalabrasSignificativasNombre(apellido) < 1) {
                mostrarErrorCampoNombre('apellido', 'apellido_error', 'Escriba al menos un apellido real (mínimo 2 letras).');
                ok = false;
            }

            if (ok && normalizarClaveNombrePersona(nombre) === normalizarClaveNombrePersona(apellido)) {
                mostrarErrorCampoNombre('apellido', 'apellido_error', 'Nombre y apellidos no pueden ser iguales.');
                ok = false;
            }

            if (!ok) {
                e.preventDefault();
                const primerInvalido = registroForm.querySelector('.input-invalid');
                if (primerInvalido) {
                    primerInvalido.focus();
                }
            }
        });
    }
})();
</script>
</body>
</html>
