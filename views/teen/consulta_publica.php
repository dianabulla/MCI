<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta por codigo - Teens</title>
    <style>
        :root {
            --bg: #f4f7fb;
            --panel: #fff;
            --brand: #0f4c81;
            --brand-2: #1389d3;
            --text: #1c2b3b;
            --muted: #5a6b81;
            --error: #a32525;
            --error-bg: #fdeeee;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 0% 0%, rgba(19,137,211,.14), transparent 44%),
                radial-gradient(circle at 100% 20%, rgba(15,76,129,.10), transparent 36%),
                var(--bg);
            min-height: 100vh;
            padding: 24px 12px;
        }

        .box {
            max-width: 720px;
            margin: 0 auto;
            background: var(--panel);
            border-radius: 16px;
            box-shadow: 0 18px 42px rgba(15, 51, 85, .14);
            overflow: hidden;
        }

        .top {
            background: linear-gradient(120deg, var(--brand), var(--brand-2));
            color: #fff;
            padding: 20px;
        }

        .top h1 {
            margin: 0;
            font-size: 25px;
        }

        .top p {
            margin: 8px 0 0;
            opacity: .96;
        }

        .inner {
            padding: 18px;
        }

        form {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        input[type="text"] {
            flex: 1;
            min-width: 230px;
            border: 1px solid #cbd4de;
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 15px;
            text-transform: uppercase;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: var(--brand-2);
            box-shadow: 0 0 0 3px rgba(19,137,211,.16);
        }

        button,
        .btn-link {
            border: none;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        button {
            background: linear-gradient(120deg, var(--brand), var(--brand-2));
            color: #fff;
            font-weight: 600;
        }

        .btn-link {
            background: #e8edf4;
            color: #284056;
        }

        .alert {
            border-radius: 10px;
            padding: 11px 13px;
            margin-bottom: 12px;
            border: 1px solid #f2c4c4;
            background: var(--error-bg);
            color: var(--error);
        }

        .result {
            background: #f8fbff;
            border: 1px solid #d9e8f7;
            border-radius: 12px;
            padding: 14px;
        }

        .row {
            display: grid;
            grid-template-columns: 180px 1fr;
            gap: 10px;
            padding: 6px 0;
            border-bottom: 1px dashed #dfebf7;
        }

        .row:last-child {
            border-bottom: none;
        }

        .label {
            font-weight: 600;
            color: #355069;
        }

        .value {
            color: #172a3a;
        }

        .empty {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        @media (max-width: 620px) {
            .top h1 { font-size: 22px; }
            .row { grid-template-columns: 1fr; gap: 4px; }
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="top">
            <h1>Consulta por codigo</h1>
            <p>Ingresa el codigo entregado al finalizar el registro para identificar al nino.</p>
        </div>

        <div class="inner">
            <form method="GET" action="<?= PUBLIC_URL ?>index.php">
                <input type="hidden" name="url" value="teen/consulta-codigo">
                <input type="text" name="codigo" value="<?= htmlspecialchars((string)($codigo ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: TN-260416-123456" required>
                <button type="submit">Buscar</button>
                <a class="btn-link" href="<?= PUBLIC_URL ?>index.php?url=teen/registro-publico">Registrar nuevo</a>
            </form>

            <?php if (!empty($mensaje ?? '')): ?>
                <div class="alert">
                    <?= htmlspecialchars((string)$mensaje, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($registro)): ?>
                <div class="result">
                    <div class="row">
                        <div class="label">Codigo</div>
                        <div class="value"><strong><?= htmlspecialchars((string)($registro['codigo_registro'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></div>
                    </div>
                    <div class="row">
                        <div class="label">Nino</div>
                        <div class="value"><?= htmlspecialchars((string)($registro['nombre_menor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="row">
                        <div class="label">Acudiente</div>
                        <div class="value"><?= htmlspecialchars((string)(($registro['Nombre_Acudiente_Base'] ?? '') !== '' ? $registro['Nombre_Acudiente_Base'] : ($registro['nombre_acudiente'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="row">
                        <div class="label">Contacto</div>
                        <div class="value"><?= htmlspecialchars((string)(($registro['Telefono_Acudiente_Actual'] ?? '') !== '' ? $registro['Telefono_Acudiente_Actual'] : ($registro['telefono_contacto'] ?? '')), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="row">
                        <div class="label">Edad</div>
                        <div class="value"><?= (int)($registro['edad'] ?? 0) ?></div>
                    </div>
                    <div class="row">
                        <div class="label">Ministerio</div>
                        <div class="value"><?= htmlspecialchars((string)($registro['Nombre_Ministerio'] ?? 'Sin ministerio'), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="row">
                        <div class="label">Asiste a celula</div>
                        <div class="value"><?= !empty($registro['asiste_celula']) ? 'Si' : 'No' ?></div>
                    </div>
                    <div class="row">
                        <div class="label">Barrio</div>
                        <div class="value"><?= htmlspecialchars((string)($registro['barrio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                    <div class="row">
                        <div class="label">Fecha de registro</div>
                        <div class="value"><?= htmlspecialchars((string)($registro['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                </div>
            <?php elseif (empty($mensaje ?? '') && empty($codigo ?? '')): ?>
                <p class="empty">Todavia no has buscado ningun codigo.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
