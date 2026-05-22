<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acuerdo de confidencialidad - MCI Madrid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #f3f7ff 0%, #e8eef9 100%);
            min-height: 100vh;
            padding: 24px 12px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .acuerdo-card {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #d7e3f6;
            border-radius: 16px;
            box-shadow: 0 14px 36px rgba(36, 70, 126, 0.12);
            overflow: hidden;
        }
        .acuerdo-header {
            background: linear-gradient(135deg, #355fa8 0%, #5b7fc3 100%);
            color: #fff;
            padding: 24px 28px;
        }
        .acuerdo-body {
            padding: 28px;
        }
        .acuerdo-texto {
            max-height: 340px;
            overflow-y: auto;
            padding: 16px 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            line-height: 1.55;
            color: #334155;
        }
        .acuerdo-texto h2 {
            font-size: 1.1rem;
            margin: 0 0 12px;
            color: #1e3a5f;
        }
        .acuerdo-texto ul {
            padding-left: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="acuerdo-card">
        <div class="acuerdo-header">
            <h1 class="h4 mb-1">Acuerdo de confidencialidad</h1>
            <p class="mb-0 opacity-90">Hola, <?= htmlspecialchars($usuario_nombre ?? 'Usuario') ?>. Antes de continuar, confirma que comprendes tu responsabilidad con los datos de la plataforma.</p>
        </div>
        <div class="acuerdo-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="acuerdo-texto mb-4">
                <h2>Compromiso de confidencialidad y uso responsable de datos</h2>
                <p>Como líder o administrador con acceso a MCI Madrid, manejas información personal sensible (nombres, documentos, teléfonos, direcciones, peticiones, asistencias, procesos de discipulado y datos de ministerio).</p>
                <p><strong>Te comprometes a:</strong></p>
                <ul>
                    <li>Usar la información únicamente para fines pastorales, administrativos y de seguimiento autorizados por la iglesia.</li>
                    <li>No compartir, copiar ni divulgar datos fuera de la plataforma sin autorización expresa.</li>
                    <li>Proteger tus credenciales de acceso y no permitir que otras personas usen tu cuenta.</li>
                    <li>Reportar de inmediato cualquier incidente, fuga o acceso indebido al equipo administrativo.</li>
                    <li>Respetar el consentimiento de tratamiento de datos registrado para cada persona.</li>
                </ul>
                <p>El incumplimiento de este acuerdo puede implicar la revocación del acceso y las medidas que correspondan según la normativa de protección de datos y las políticas internas de MCI Madrid.</p>
                <p class="mb-0 text-muted"><small>Versión del acuerdo: <?= htmlspecialchars($version ?? '') ?></small></p>
            </div>

            <form method="post" action="<?= PUBLIC_URL ?>?url=auth/aceptar-acuerdo-confidencialidad">
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="acepta_acuerdo" value="1" id="acepta_acuerdo" required>
                    <label class="form-check-label" for="acepta_acuerdo">
                        He leído y acepto el acuerdo de confidencialidad. Me comprometo a custodiar los datos a los que tengo acceso.
                    </label>
                </div>
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                    <button type="submit" class="btn btn-primary px-4">Aceptar y continuar</button>
                    <a href="<?= PUBLIC_URL ?>?url=auth/logout" class="btn btn-outline-secondary">Cerrar sesión</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
