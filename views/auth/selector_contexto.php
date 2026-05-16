<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar modo de entrada</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at 12% 10%, rgba(76, 126, 196, 0.23) 0, transparent 34%),
                radial-gradient(circle at 88% 15%, rgba(33, 150, 119, 0.20) 0, transparent 36%),
                #edf3fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 22px;
        }

        .context-wrap {
            width: 100%;
            max-width: 980px;
            background: #fff;
            border-radius: 22px;
            border: 1px solid #d8e3f3;
            box-shadow: 0 22px 44px rgba(23, 63, 126, 0.15);
            overflow: hidden;
        }

        .context-head {
            padding: 28px 30px;
            background: linear-gradient(135deg, #1f4f93 0%, #2f6abf 100%);
            color: #fff;
        }

        .context-head h1 {
            margin: 0;
            font-size: 27px;
            font-weight: 700;
        }

        .context-head p {
            margin: 8px 0 0;
            opacity: 0.95;
            font-size: 15px;
        }

        .context-body {
            padding: 24px 30px 30px;
        }

        .context-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 14px;
        }

        .context-card {
            border: 1px solid #d9e5f7;
            border-radius: 14px;
            background: #f7fbff;
            padding: 18px 16px;
            text-align: left;
            transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
            cursor: pointer;
            width: 100%;
        }

        .context-card:hover {
            transform: translateY(-2px);
            border-color: #8db1e6;
            box-shadow: 0 9px 20px rgba(40, 92, 166, 0.14);
        }

        .context-card h3 {
            margin: 0 0 8px;
            color: #1d4f93;
            font-size: 20px;
            font-weight: 700;
        }

        .context-card .context-role {
            font-size: 13px;
            color: #5a6f8f;
            margin-bottom: 10px;
        }

        .context-card .context-go {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: #1f5ea8;
        }

        .context-card.context-maestro {
            background: linear-gradient(180deg, #fff9ee 0%, #fffef8 100%);
            border-color: #f2ddb7;
        }

        .context-card.context-maestro h3 {
            color: #8a5b17;
        }

        .context-card.context-discipulo {
            background: linear-gradient(180deg, #edf6ff 0%, #f8fcff 100%);
        }

        .context-card.context-lider {
            background: linear-gradient(180deg, #eef9f3 0%, #f9fffb 100%);
            border-color: #cce8d9;
        }

        .context-foot {
            margin-top: 16px;
            font-size: 12px;
            color: #62748f;
        }
    </style>
</head>
<body>
    <div class="context-wrap">
        <div class="context-head">
            <h1>Modo de entrada</h1>
            <p><?= htmlspecialchars((string)($usuario_nombre ?? 'Usuario')) ?>, selecciona cómo quieres entrar hoy.</p>
        </div>

        <div class="context-body">
            <?php if (!empty($error ?? '')): ?>
                <div class="alert alert-danger" style="margin-bottom:12px;">
                    <?= htmlspecialchars((string)$error) ?>
                </div>
            <?php endif; ?>

            <div class="context-grid">
                <?php foreach ((array)($roles_disponibles ?? []) as $rol): ?>
                    <?php
                        $idRol = (int)($rol['id_rol'] ?? 0);
                        $nombreRol = trim((string)($rol['nombre_rol'] ?? 'Perfil'));
                        $contextKey = trim((string)($rol['context_key'] ?? 'lider'));
                        $contextClass = 'context-lider';
                        $titulo = 'Lider';

                        if ($contextKey === 'maestro') {
                            $contextClass = 'context-maestro';
                            $titulo = 'Maestro';
                        } elseif ($contextKey === 'discipulo') {
                            $contextClass = 'context-discipulo';
                            $titulo = 'Discipulo';
                        }
                    ?>
                    <form method="POST" action="<?= PUBLIC_URL ?>?url=auth/seleccionar-contexto" style="margin:0;">
                        <input type="hidden" name="id_rol" value="<?= $idRol ?>">
                        <button type="submit" class="context-card <?= $contextClass ?>">
                            <h3><?= htmlspecialchars($titulo) ?></h3>
                            <div class="context-role">Rol base: <?= htmlspecialchars($nombreRol) ?></div>
                            <span class="context-go">Entrar ahora <i class="bi bi-arrow-right-circle"></i></span>
                        </button>
                    </form>
                <?php endforeach; ?>
            </div>

            <div class="context-foot">
                Tu selección se guardará en sesión como <strong>active_context</strong> y podrás cambiar de cuenta cuando lo necesites.
            </div>
        </div>
    </div>
</body>
</html>
