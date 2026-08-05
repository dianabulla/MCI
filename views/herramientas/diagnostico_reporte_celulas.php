<?php
$resumen = $resumen ?? [];
$celulasCon = $celulas_con_reporte ?? [];
$celulasSin = $celulas_sin_reporte ?? [];
$registros = $registros ?? [];
$registrosSinCelula = $registros_sin_celula ?? [];
$gruposConfusos = $grupos_celulas_confusas ?? [];
$buscar = trim((string)($buscar ?? ''));
$log = is_array($log ?? null) ? $log : [];
$logLineas = is_array($log['lineas'] ?? null) ? $log['lineas'] : [];
$opcionesSemanas = $opciones_semanas ?? [];
$baseUrl = $base_url ?? '';
$urlAsistencias = $url_asistencias ?? '';
$urlReportar = static function (int $idCelula, string $fechaInicio): string {
    if ($idCelula <= 0) {
        return '';
    }
    return PUBLIC_URL . '?url=asistencias/registrar&celula=' . $idCelula . '&fecha=' . urlencode($fechaInicio);
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico — Reporte de células</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 1400px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        h2 { font-size: 1.1rem; margin: 28px 0 12px; color: #1e40af; }
        .meta { color: #64748b; font-size: 0.9rem; margin-bottom: 20px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .card strong { display: block; font-size: 1.6rem; color: #1d4ed8; }
        .card.warn strong { color: #b45309; }
        .card.ok strong { color: #15803d; }
        .card span { font-size: 0.85rem; color: #475569; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; background: #fff; padding: 14px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        select, .btn { padding: 8px 12px; border-radius: 6px; font-size: 14px; }
        select { border: 1px solid #cbd5e1; }
        .btn { display: inline-block; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .btn-primary { background: #2563eb; color: #fff; }
        .info { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; border-radius: 4px; }
        .warn-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; border-radius: 4px; }
        .table-wrap { overflow-x: auto; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        th { background: #f8fafc; white-space: nowrap; }
        tr:hover td { background: #f8fafc; }
        .badge-si { background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-no { background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-warn { background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-id { background: #e0e7ff; color: #3730a3; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .danger-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; border-radius: 4px; }
        .search-input { min-width: 220px; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
        .link-sm { font-size: 12px; font-weight: 600; color: #2563eb; text-decoration: none; }
        .link-sm:hover { text-decoration: underline; }
        tr.row-alert td { background: #fffbeb; }
        tr.row-danger td { background: #fef2f2; }
        .log-box { background: #0f172a; color: #e2e8f0; padding: 14px; border-radius: 8px; font-family: Consolas, monospace; font-size: 12px; max-height: 360px; overflow: auto; white-space: pre-wrap; word-break: break-word; }
        .log-empty { color: #94a3b8; font-style: italic; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Diagnóstico: reporte de células</h1>
    <p class="meta">
        Semana analizada: <strong><?= htmlspecialchars($fecha_inicio ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
        al <strong><?= htmlspecialchars($fecha_fin ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
        (<?= htmlspecialchars($semana_iso ?? '', ENT_QUOTES, 'UTF-8') ?>)
        <?php if (!empty($es_semana_anterior_defecto)): ?> · por defecto: <em>semana pasada</em><?php endif; ?>
    </p>

    <div class="info">
        Esta herramienta compara qué células <strong>sí tienen registros</strong> en <code>asistencia_celula</code> esa semana
        y cuáles <strong>no reportaron</strong>. Si alguien dice que reportó pero no aparece aquí con registros, el guardado no llegó a la base de datos.
        <a href="<?= htmlspecialchars($urlAsistencias, ENT_QUOTES, 'UTF-8') ?>">Ver la misma semana en Asistencias →</a>
    </div>

    <form class="toolbar" method="get" action="<?= htmlspecialchars(PUBLIC_URL . '?url=herramientas/diagnostico-reporte-celulas', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="url" value="herramientas/diagnostico-reporte-celulas">
        <label for="semana">Semana:</label>
        <select name="semana" id="semana">
            <?php foreach ($opcionesSemanas as $op): ?>
            <option value="<?= htmlspecialchars($op['semana'], ENT_QUOTES, 'UTF-8') ?>"
                <?= ($semana_iso ?? '') === $op['semana'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($op['etiqueta'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($op['semana'], ENT_QUOTES, 'UTF-8') ?>)
            </option>
            <?php endforeach; ?>
        </select>
        <label for="buscar">Buscar líder/célula:</label>
        <input type="search" class="search-input" id="buscar" name="buscar" value="<?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Leidy Yohana, Escobar...">
        <button type="submit" class="btn btn-primary">Aplicar</button>
        <?php if ($buscar !== ''): ?>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>">Quitar filtro</a>
        <?php endif; ?>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($urlAsistencias, ENT_QUOTES, 'UTF-8') ?>">Abrir Asistencias</a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars(PUBLIC_URL . '?url=home', ENT_QUOTES, 'UTF-8') ?>">Inicio</a>
    </form>

    <?php if ($buscar !== ''): ?>
    <div class="info">Filtro activo: <strong><?= htmlspecialchars($buscar, ENT_QUOTES, 'UTF-8') ?></strong> — solo se muestran coincidencias en las tablas de abajo.</div>
    <?php endif; ?>

    <div class="cards">
        <div class="card"><strong><?= (int)($resumen['total_celulas_activas'] ?? 0) ?></strong><span>Células activas</span></div>
        <div class="card ok"><strong><?= (int)($resumen['celulas_reportaron'] ?? 0) ?></strong><span>Reportaron</span></div>
        <div class="card warn"><strong><?= (int)($resumen['celulas_sin_reporte'] ?? 0) ?></strong><span>Sin reporte</span></div>
        <div class="card"><strong><?= (int)($resumen['total_registros_asistencia'] ?? 0) ?></strong><span>Registros guardados</span></div>
        <div class="card <?= (int)($resumen['registros_sin_celula'] ?? 0) > 0 ? 'warn' : '' ?>"><strong><?= (int)($resumen['registros_sin_celula'] ?? 0) ?></strong><span>Sin Id_Celula válido</span></div>
        <div class="card"><strong><?= (int)($resumen['celulas_entregaron_sobre'] ?? 0) ?></strong><span>Marca entregó sobre</span></div>
    </div>

    <?php if ((int)($resumen['registros_sin_celula'] ?? 0) > 0): ?>
    <div class="danger-box">
        Hay <strong><?= (int)$resumen['registros_sin_celula'] ?></strong> registro(s) en la semana con <strong>Id_Celula vacío o 0</strong>.
        Eso suele pasar cuando el líder escribió el nombre de la célula pero <strong>no lo eligió del listado</strong> antes de guardar.
        Esos reportes no aparecen asociados a ninguna célula en las tablas normales.
    </div>
    <?php endif; ?>

    <h2>Células duplicadas o con nombres que se confunden (<?= count($gruposConfusos) ?>)</h2>
    <div class="info">
        Si hay dos registros con el mismo nombre, o el mismo par líder/anfitrión con el nombre al revés,
        el líder puede reportar en una y ustedes revisar la otra. Revise el <strong>Id</strong> y cuántos <strong>miembros</strong> tiene cada una.
    </div>
    <?php if ($gruposConfusos === []): ?>
    <div class="info">No se detectaron grupos de células con nombres duplicados o invertidos<?= $buscar !== '' ? ' para el filtro actual' : '' ?>.</div>
    <?php else: ?>
    <?php foreach ($gruposConfusos as $grupo): ?>
    <div class="warn-box" style="margin-bottom:10px;">
        <strong><?= htmlspecialchars((string)($grupo['etiqueta'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Célula</th>
                    <th>Líder</th>
                    <th>Miembros</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($grupo['celulas'] ?? []) as $c): ?>
                <?php $miembros = (int)($c['Total_Miembros'] ?? 0); ?>
                <tr class="<?= $miembros === 0 ? 'row-danger' : 'row-alert' ?>">
                    <td><span class="badge-id">#<?= (int)($c['Id_Celula'] ?? 0) ?></span></td>
                    <td><?= htmlspecialchars((string)($c['Nombre_Celula'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($c['Nombre_Lider'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $miembros === 0 ? '<span class="badge-no">0 miembros</span>' : (string)$miembros ?></td>
                    <td>
                        <?php $urlRep = $urlReportar((int)($c['Id_Celula'] ?? 0), (string)($fecha_inicio ?? '')); ?>
                        <?php if ($urlRep !== ''): ?>
                        <a class="link-sm" href="<?= htmlspecialchars($urlRep, ENT_QUOTES, 'UTF-8') ?>">Reportar esta célula</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <h2>Células que SÍ reportaron (<?= count($celulasCon) ?>)</h2>
    <?php if ($celulasCon === []): ?>
    <div class="warn-box">Ninguna célula tiene registros de asistencia en esta semana.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Célula</th>
                    <th>Líder</th>
                    <th>Ministerio</th>
                    <th>Miembros</th>
                    <th>Días reportados</th>
                    <th>Registros</th>
                    <th>Asistieron</th>
                    <th>Fechas</th>
                    <th>Sobre</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($celulasCon as $f): ?>
                <tr>
                    <td><span class="badge-id">#<?= (int)($f['Id_Celula'] ?? 0) ?></span></td>
                    <td><?= htmlspecialchars((string)($f['Nombre_Celula'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($f['Nombre_Lider'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($f['Nombre_Ministerio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($f['Total_Miembros'] ?? 0) ?></td>
                    <td><?= (int)($f['Dias_Reportados'] ?? 0) ?></td>
                    <td><?= (int)($f['Total_Registros'] ?? 0) ?></td>
                    <td><?= (int)($f['Total_Asistieron'] ?? 0) ?></td>
                    <td><?= htmlspecialchars(trim((string)($f['Primera_Fecha'] ?? '') . ' – ' . (string)($f['Ultima_Fecha'] ?? ''), ' –'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($f['Entrego_Sobre'] ?? 0) === 1 ? '<span class="badge-si">Sí</span>' : '<span class="badge-no">No</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <h2>Células SIN reporte en la semana (<?= count($celulasSin) ?>)</h2>
    <?php if ($celulasSin === []): ?>
    <div class="info">Todas las células activas tienen al menos un registro en el rango seleccionado.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Célula</th>
                    <th>Líder</th>
                    <th>Ministerio</th>
                    <th>Miembros</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($celulasSin as $f): ?>
                <?php
                    $miembros = (int)($f['Total_Miembros'] ?? 0);
                    $rowClass = $miembros === 0 ? 'row-danger' : '';
                ?>
                <tr class="<?= $rowClass ?>">
                    <td><span class="badge-id">#<?= (int)($f['Id_Celula'] ?? 0) ?></span></td>
                    <td><?= htmlspecialchars((string)($f['Nombre_Celula'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($f['Nombre_Lider'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($f['Nombre_Ministerio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($miembros === 0): ?>
                        <span class="badge-no">0 miembros</span>
                        <?php else: ?>
                        <?= $miembros ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge-no">Sin registros</span></td>
                    <td>
                        <?php $urlRep = $urlReportar((int)($f['Id_Celula'] ?? 0), (string)($fecha_inicio ?? '')); ?>
                        <?php if ($urlRep !== ''): ?>
                        <a class="link-sm" href="<?= htmlspecialchars($urlRep, ENT_QUOTES, 'UTF-8') ?>">Reportar semana</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <h2>Últimos registros guardados en la semana (<?= count($registros) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Célula</th>
                    <th>Persona</th>
                    <th>Asistió</th>
                    <th>Tema / tipo</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($registros === []): ?>
                <tr><td colspan="6" class="log-empty">No hay registros en asistencia_celula para esta semana.</td></tr>
                <?php else: ?>
                <?php foreach ($registros as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($r['Fecha_Asistencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($r['Nombre_Celula'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($r['Nombre_Persona'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($r['Asistio'] ?? 0) === 1 ? 'Sí' : 'No' ?></td>
                    <td><?= htmlspecialchars(trim((string)($r['Tema'] ?? '') . ' ' . (string)($r['Tipo_Celula'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($r['Observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h2>Registros huérfanos — sin célula válida (<?= count($registrosSinCelula) ?>)</h2>
    <?php if ($registrosSinCelula === []): ?>
    <div class="info">No hay registros con Id_Celula vacío o 0 en esta semana. Buena señal: los reportes quedaron ligados a una célula.</div>
    <?php else: ?>
    <div class="danger-box">
        Estos registros existen en la base de datos pero <strong>no están ligados a ninguna célula</strong>.
        Pueden ser el reporte “perdido” que el líder cree haber enviado.
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Id registro</th>
                    <th>Id_Celula</th>
                    <th>Persona</th>
                    <th>Asistió</th>
                    <th>Tema</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registrosSinCelula as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($r['Fecha_Asistencia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($r['Id_Asistencia'] ?? 0) ?></td>
                    <td><span class="badge-no"><?= htmlspecialchars((string)($r['Id_Celula'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><?= htmlspecialchars((string)($r['Nombre_Persona'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)($r['Asistio'] ?? 0) === 1 ? 'Sí' : 'No' ?></td>
                    <td><?= htmlspecialchars((string)($r['Tema'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($r['Observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <h2>Log del servidor (errores relacionados)</h2>
    <?php if (!empty($log['advertencia'])): ?>
    <div class="warn-box"><?= htmlspecialchars((string)$log['advertencia'], ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>
    <?php if (!empty($puede_ver_log) && !empty($log['archivo'])): ?>
    <p class="meta">Archivo: <code><?= htmlspecialchars((string)$log['archivo'], ENT_QUOTES, 'UTF-8') ?></code></p>
    <?php endif; ?>
    <div class="log-box">
<?php if ($logLineas === []): ?>
<span class="log-empty">Sin líneas relevantes en el log para esta semana.</span>
<?php else: ?>
<?= htmlspecialchars(implode("\n", $logLineas), ENT_QUOTES, 'UTF-8') ?>
<?php endif; ?>
    </div>
</div>
</body>
</html>
