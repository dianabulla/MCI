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
            color: #DE6D6D;
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
            <form method="POST" action="<?= PUBLIC_URL ?>?url=registro_personas/guardar">
                <div class="grid">
                    <div class="field">
                        <label for="nombre">Nombre <span class="req">*</span></label>
                        <input type="text" id="nombre" name="nombre" required value="<?= htmlspecialchars((string)($old['nombre'] ?? '')) ?>">
                    </div>

                    <div class="field">
                        <label for="apellido">Apellidos <span class="req">*</span></label>
                        <input type="text" id="apellido" name="apellido" required value="<?= htmlspecialchars((string)($old['apellido'] ?? '')) ?>">
                    </div>

                    <div class="field">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="<?= htmlspecialchars((string)($old['telefono'] ?? '')) ?>" placeholder="Ej: 3001234567">
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
                        <label for="cedula">Cédula</label>
                        <input type="text" id="cedula" name="cedula" value="<?= htmlspecialchars((string)($old['cedula'] ?? '')) ?>" placeholder="Número de cédula (opcional)">
                    </div>

                    <div class="field">
                        <label for="fecha_nacimiento">Fecha de nacimiento</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= htmlspecialchars((string)($old['fecha_nacimiento'] ?? '')) ?>">
                    </div>

                    <div class="field">
                        <label for="barrio">Barrio</label>
                        <input type="text" id="barrio" name="barrio" value="<?= htmlspecialchars((string)($old['barrio'] ?? '')) ?>" placeholder="Barrio (opcional)">
                    </div>

                    <div class="field full">
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
                        <label for="ganado_en_otro_observacion">Observaciones</label>
                        <textarea id="ganado_en_otro_observacion" name="ganado_en_otro_observacion" rows="3" placeholder="Describe dónde fue ganado o la observación necesaria..."><?= htmlspecialchars((string)($old['ganado_en_otro_observacion'] ?? '')) ?></textarea>
                    </div>

                    <div class="field full">
                        <label for="peticion">Petición</label>
                        <input type="text" id="peticion" name="peticion" value="<?= htmlspecialchars((string)($old['peticion'] ?? '')) ?>" placeholder="Petición de oración (opcional)">
                    </div>
                </div>

                <p class="hint">Obligatorio: nombre, apellidos y ganado en. Si eliges Otros, escribe también la observación.</p>

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
})();
</script>
</body>
</html>
