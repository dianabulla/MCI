<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Teens</title>
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
            <h1>Registro de ninos - Teens</h1>
            <p>Completa este formulario y al final te entregaremos un codigo de consulta.</p>
        </div>

        <div class="content">
            <?php if (!empty($mensaje ?? '')): ?>
                <div class="alert <?= (($tipo ?? '') === 'success') ? 'alert-success' : 'alert-error' ?>">
                    <?= htmlspecialchars((string)$mensaje, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($codigo ?? '')): ?>
                <div class="codigo-box">
                    Codigo de registro
                    <strong><?= htmlspecialchars((string)$codigo, ENT_QUOTES, 'UTF-8') ?></strong>
                    <div>Guardalo. Lo necesitaras para consultar la informacion del nino.</div>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?= PUBLIC_URL ?>index.php?url=teen/guardar-menor-publico" id="formRegistroPublicoTeen">
                <div>
                    <label for="nombre_menor">Nombre y apellido del nino</label>
                    <input type="text" id="nombre_menor" name="nombre_menor" required value="<?= htmlspecialchars((string)($old['nombre_menor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="js-upper">
                </div>

                <div>
                    <label for="nombre_acudiente">Nombre del acudiente</label>
                    <input type="text" id="nombre_acudiente" name="nombre_acudiente" required value="<?= htmlspecialchars((string)($old['nombre_acudiente'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="js-upper">
                </div>

                <div>
                    <label for="telefono_contacto">Telefono de contacto</label>
                    <input type="text" id="telefono_contacto" name="telefono_contacto" required value="<?= htmlspecialchars((string)($old['telefono_contacto'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
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
                    <label for="id_ministerio">Ministerio</label>
                    <select id="id_ministerio" name="id_ministerio" required>
                        <option value="">Selecciona...</option>
                        <?php foreach (($ministerios ?? []) as $ministerio): ?>
                            <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= (string)($old['id_ministerio'] ?? '') === (string)$ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string)$ministerio['Nombre_Ministerio'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
            </form>

            <p class="hint">
                Nota: al completar el registro se mostrara un codigo unico. Ese codigo permite consultar despues a que nino pertenece.
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

            var fechaNacimientoInput = document.getElementById('fecha_nacimiento');
            var edadInput = document.getElementById('edad');

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
        })();
    </script>
</body>
</html>
