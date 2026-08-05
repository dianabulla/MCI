<?php
$roles = $roles ?? [];
$id_rol = (int)($id_rol ?? 0);
$nombre_rol = $nombre_rol ?? '';
$evaluacion = $evaluacion ?? null;
$filas_modulos = $filas_modulos ?? [];
$form_action = $form_action ?? (rtrim(PUBLIC_URL, '/') . '/index.php');
$route_diagnostico = $route_diagnostico ?? 'herramientas/diagnostico-permisos-persona';
$parche_desplegado = !empty($parche_desplegado);
$error = $error ?? null;
$sesion_rol_id = (int)($sesion_rol_id ?? 0);
$sesion_rol_nombre = $sesion_rol_nombre ?? '';
$host = $host ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico — Permisos editar persona</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 960px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        .meta { color: #64748b; font-size: 0.9rem; margin-bottom: 20px; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; background: #fff; padding: 14px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; min-width: 280px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary { background: #1d4ed8; color: #fff; }
        .panel { background: #fff; border-radius: 8px; padding: 16px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .ok { color: #15803d; font-weight: 700; }
        .no { color: #b91c1c; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; }
        .info { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; border-radius: 4px; }
        .warn { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; border-radius: 4px; }
        .err { background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; }
        ul { margin: 8px 0 0; padding-left: 20px; }
        code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Diagnóstico: editar personas (por rol)</h1>
    <p class="meta">
        Servidor: <strong><?= htmlspecialchars($host, ENT_QUOTES, 'UTF-8') ?></strong>
        · BD: <strong><?= htmlspecialchars(defined('DB_NAME') ? DB_NAME : '', ENT_QUOTES, 'UTF-8') ?></strong>
        · Solo administradores
    </p>

    <div class="info">
        Use esta pantalla en <strong>producción</strong> tras subir el parche de permisos.
        Elija el rol del líder de células y compruebe si la ruta <code>personas/editar</code> quedaría permitida.
    </div>

    <?php if (!$parche_desplegado): ?>
    <div class="warn">
        <strong>Parche no detectado en este servidor.</strong>
        Falta el método <code>puedeEditarPersonaDesdeDiscipular</code> en AuthController.
        Suba los archivos del fix antes de probar con usuarios reales.
    </div>
    <?php else: ?>
    <div class="info">
        <strong>Parche detectado</strong> en este servidor (<code>puedeEditarPersonaDesdeDiscipular</code>).
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form class="toolbar" method="get" action="<?= htmlspecialchars($form_action, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="url" value="<?= htmlspecialchars($route_diagnostico, ENT_QUOTES, 'UTF-8') ?>">
        <label for="id_rol">Rol:</label>
        <select name="id_rol" id="id_rol">
            <?php foreach ($roles as $rol): ?>
                <?php $rid = (int)($rol['Id_Rol'] ?? 0); ?>
                <option value="<?= $rid ?>" <?= $rid === $id_rol ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)($rol['Nombre_Rol'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (ID <?= $rid ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Analizar</button>
    </form>

    <?php if ($evaluacion && $id_rol > 0): ?>
    <div class="panel">
        <h2 style="margin:0 0 12px;font-size:1.1rem;">Rol: <?= htmlspecialchars($nombre_rol, ENT_QUOTES, 'UTF-8') ?> (ID <?= $id_rol ?>)</h2>
        <p>
            Acceso a <code>personas/editar</code>:
            <?php if (!empty($evaluacion['acceso_ruta_personas_editar'])): ?>
                <span class="ok">PERMITIDO</span>
            <?php else: ?>
                <span class="no">DENEGADO</span>
            <?php endif; ?>
        </p>
        <p>Botón Editar en Discipular (equipo principal):
            <?php if (!empty($evaluacion['boton_discipular_visible'])): ?>
                <span class="ok">visible</span>
            <?php else: ?>
                <span class="no">oculto</span>
            <?php endif; ?>
        </p>
        <?php if (!empty($evaluacion['motivos'])): ?>
        <ul>
            <?php foreach ($evaluacion['motivos'] as $motivo): ?>
            <li><?= htmlspecialchars((string)$motivo, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2 style="margin:0 0 12px;font-size:1.1rem;">Comprobaciones lógicas</h2>
        <table>
            <thead><tr><th>Regla</th><th>Resultado</th></tr></thead>
            <tbody>
                <?php
                $checks = [
                    'personas:editar (Almas ganadas → Editar fichas)' => 'personas_editar',
                    'personas_consulta:editar (Discípulos → Editar fichas)' => 'personas_consulta_editar',
                    'ministerios:editar (Discipular → Editar equipos)' => 'ministerios_editar',
                    'celulas:editar (solo células, no personas)' => 'celulas_editar',
                    'puedeEditarPersonasConsulta (OR personas + consulta)' => 'puede_editar_personas_consulta',
                    'puedeEditarPersonaDesdeDiscipular (+ ministerios)' => 'puede_editar_desde_discipular',
                ];
                foreach ($checks as $label => $key):
                    $ok = !empty($evaluacion[$key]);
                ?>
                <tr>
                    <td><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="<?= $ok ? 'ok' : 'no' ?>"><?= $ok ? 'Sí' : 'No' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="panel">
        <h2 style="margin:0 0 12px;font-size:1.1rem;">Módulos en base de datos (tabla permisos)</h2>
        <table>
            <thead>
                <tr>
                    <th>Módulo</th>
                    <th>En pantalla Permisos</th>
                    <th>Ver</th>
                    <th>Crear</th>
                    <th>Editar</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filas_modulos as $fila): ?>
                <tr>
                    <td><code><?= htmlspecialchars((string)$fila['modulo'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td><?= htmlspecialchars((string)$fila['etiqueta'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= !empty($fila['configurado']) ? (!empty($fila['ver']) ? 'Sí' : 'No') : '—' ?></td>
                    <td><?= !empty($fila['configurado']) ? (!empty($fila['crear']) ? 'Sí' : 'No') : '—' ?></td>
                    <td><?= !empty($fila['configurado']) ? (!empty($fila['editar']) ? 'Sí' : 'No') : '—' ?></td>
                    <td><?= !empty($fila['configurado']) ? (!empty($fila['eliminar']) ? 'Sí' : 'No') : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($sesion_rol_id > 0): ?>
    <p class="meta">Su sesión actual: rol ID <?= $sesion_rol_id ?> — <?= htmlspecialchars($sesion_rol_nombre, ENT_QUOTES, 'UTF-8') ?> (debe ser administrador para ver esta página).</p>
    <?php endif; ?>

    <p class="meta">
        Tras cambiar permisos en Roles, el usuario afectado debe <strong>cerrar sesión y volver a entrar</strong>.
    </p>
</div>
</body>
</html>
