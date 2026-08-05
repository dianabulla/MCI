<?php
$labelsTipo = [
    'todos' => 'Todas las anomalías (unificadas)',
    'vacio' => 'Documento vacío',
    '5_digitos' => 'Solo 5 dígitos',
    'telefono' => 'Parece teléfono',
];
$labelsResumen = [
    'documento_vacio' => 'Documento vacío',
    'documento_solo_5_digitos' => 'Solo 5 dígitos',
    'documento_parece_telefono' => 'Parece teléfono',
];
$tipo = $tipo ?? 'todos';
$resumen = $resumen ?? [];
$filas = $filas ?? [];
$base_url = $base_url ?? '';
$export_url = $export_url ?? '';
$usa_regexp_replace = !empty($usa_regexp_replace);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico — Número de documento</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; margin: 0; padding: 24px; background: #f1f5f9; color: #0f172a; }
        .wrap { max-width: 1400px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin: 0 0 8px; }
        .meta { color: #64748b; font-size: 0.9rem; margin-bottom: 20px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 8px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .card strong { display: block; font-size: 1.75rem; color: #1d4ed8; }
        .card span { font-size: 0.85rem; color: #475569; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; background: #fff; padding: 14px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .toolbar label { font-weight: 600; margin-right: 6px; }
        select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; }
        .btn { display: inline-block; padding: 8px 16px; border-radius: 6px; font-size: 14px; font-weight: 600; text-decoration: none; }
        .btn-primary { background: #16a34a; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #334155; }
        .table-wrap { overflow-x: auto; background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; white-space: nowrap; }
        tr:hover td { background: #f8fafc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; background: #fef3c7; color: #92400e; }
        .info { background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; border-radius: 4px; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Diagnóstico: número de documento</h1>
    <p class="meta">
        Base de datos: <strong><?= htmlspecialchars(defined('DB_NAME') ? DB_NAME : '', ENT_QUOTES, 'UTF-8') ?></strong>
        · Solo lectura · <?= count($filas) ?> fila(s)
        · <?= $usa_regexp_replace ? 'Normalización SQL avanzada' : 'Normalización compatible' ?>
    </p>
    <div class="info">
        Documentos <strong>vacíos</strong>, con <strong>solo 5 dígitos</strong>, o que <strong>parecen teléfono</strong> (3xx, 57, o igual al Teléfono).
    </div>
    <div class="cards">
        <?php foreach ($labelsResumen as $key => $label): ?>
            <div class="card">
                <strong><?= (int)($resumen[$key] ?? 0) ?></strong>
                <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endforeach; ?>
        <div class="card">
            <strong><?= array_sum($resumen) ?></strong>
            <span>Total por tipo (puede solaparse)</span>
        </div>
    </div>
    <form class="toolbar" method="get" action="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>">
        <label for="tipo">Ver listado:</label>
        <select name="tipo" id="tipo" onchange="this.form.submit()">
            <?php foreach ($labelsTipo as $val => $label): ?>
                <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>" <?= $tipo === $val ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <a class="btn btn-primary" href="<?= htmlspecialchars($export_url, ENT_QUOTES, 'UTF-8') ?>">Exportar a Excel (.csv)</a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8') ?>">Actualizar</a>
        <a class="btn btn-secondary" href="<?= htmlspecialchars(PUBLIC_URL . '?url=personas', ENT_QUOTES, 'UTF-8') ?>">Volver a Personas</a>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <?php if (!empty($filas)): ?>
                    <?php foreach (array_keys($filas[0]) as $col): ?>
                        <th><?= htmlspecialchars($col, ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endforeach; ?>
                <?php else: ?>
                    <th>Sin datos</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($filas)): ?>
                <tr><td colspan="20">No hay registros para este filtro.</td></tr>
            <?php else: ?>
                <?php foreach ($filas as $fila): ?>
                    <tr>
                        <?php foreach ($fila as $key => $celda): ?>
                            <td>
                                <?php if ($key === 'tipos_anomalia' || $key === 'tipo_anomalia'): ?>
                                    <span class="badge"><?= htmlspecialchars((string)$celda, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php else: ?>
                                    <?= htmlspecialchars((string)($celda ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
