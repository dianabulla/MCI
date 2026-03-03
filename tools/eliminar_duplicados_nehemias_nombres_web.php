<?php
declare(strict_types=1);

require_once __DIR__ . '/../conexion.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo 'No se pudo inicializar la conexión a base de datos.';
    exit;
}

function normalizePersonPart(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    $value = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ', 'á', 'é', 'í', 'ó', 'ú', 'ñ'], ['A', 'E', 'I', 'O', 'U', 'N', 'A', 'E', 'I', 'O', 'U', 'N'], $value);
    $value = preg_replace('/[^A-Za-z0-9 ]+/u', ' ', $value) ?? $value;
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    $value = trim($value);

    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($value, 'UTF-8');
    }

    return strtoupper($value);
}

function loadNehemiasRows(PDO $pdo): array
{
    $sql = "SELECT Id_Nehemias, Nombres, Apellidos, Numero_Cedula, Telefono, Lider, Lider_Nehemias, Fecha_Registro
            FROM nehemias
            WHERE TRIM(COALESCE(Nombres, '')) <> ''
              AND TRIM(COALESCE(Apellidos, '')) <> ''
            ORDER BY Id_Nehemias ASC";

    $stmt = $pdo->query($sql);
    if (!$stmt) {
        return [];
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildDuplicateGroups(array $rows): array
{
    $grouped = [];

    foreach ($rows as $row) {
        $nombres = (string)($row['Nombres'] ?? '');
        $apellidos = (string)($row['Apellidos'] ?? '');

        $normNombres = normalizePersonPart($nombres);
        $normApellidos = normalizePersonPart($apellidos);

        if ($normNombres === '' || $normApellidos === '') {
            continue;
        }

        $groupKey = $normNombres . '|' . $normApellidos;
        if (!isset($grouped[$groupKey])) {
            $grouped[$groupKey] = [
                'key' => $groupKey,
                'norm_nombres' => $normNombres,
                'norm_apellidos' => $normApellidos,
                'display_nombres' => trim((string)$nombres),
                'display_apellidos' => trim((string)$apellidos),
                'rows' => [],
            ];
        }

        $grouped[$groupKey]['rows'][] = $row;
    }

    $duplicates = [];
    foreach ($grouped as $group) {
        if (count($group['rows']) <= 1) {
            continue;
        }

        usort($group['rows'], static function (array $left, array $right): int {
            return ((int)$left['Id_Nehemias']) <=> ((int)$right['Id_Nehemias']);
        });

        $duplicates[] = $group;
    }

    usort($duplicates, static function (array $left, array $right): int {
        $sizeDiff = count($right['rows']) <=> count($left['rows']);
        if ($sizeDiff !== 0) {
            return $sizeDiff;
        }

        return strnatcasecmp(
            (string)$left['display_apellidos'] . ' ' . (string)$left['display_nombres'],
            (string)$right['display_apellidos'] . ' ' . (string)$right['display_nombres']
        );
    });

    return $duplicates;
}

function prepareGroupState(array $groups): array
{
    $idGroupMap = [];

    foreach ($groups as &$group) {
        $groupId = sha1((string)$group['key']);
        $group['group_id'] = $groupId;

        foreach (($group['rows'] ?? []) as $row) {
            $rowId = (int)($row['Id_Nehemias'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            $idGroupMap[$rowId] = $groupId;
        }
    }
    unset($group);

    return [
        'groups' => $groups,
        'id_group_map' => $idGroupMap,
    ];
}

function parseSelectedIds(array $input): array
{
    $ids = [];
    foreach ($input as $value) {
        $id = (int)$value;
        if ($id > 0) {
            $ids[$id] = true;
        }
    }

    return array_keys($ids);
}

function filterSelectedIds(array $selectedIds, array $idGroupMap): array
{
    $filtered = [];
    foreach ($selectedIds as $selectedId) {
        if (isset($idGroupMap[$selectedId])) {
            $filtered[$selectedId] = true;
        }
    }

    return array_keys($filtered);
}

$message = ['type' => 'info', 'text' => 'Modo revisión: puedes editar registros y/o seleccionar los que quieras eliminar.'];

$groups = buildDuplicateGroups(loadNehemiasRows($pdo));
$state = prepareGroupState($groups);
$groups = $state['groups'];
$idGroupMap = $state['id_group_map'];

$applyRequested = ($_SERVER['REQUEST_METHOD'] === 'POST');
$action = trim((string)($_POST['action'] ?? ''));
$confirmarRaw = trim((string)($_POST['confirmar'] ?? ''));
$confirmarNormalizado = strtoupper(str_replace(['Í', 'í'], 'I', $confirmarRaw));

$selectedIdsInput = $_POST['selected_ids'] ?? [];
if (!is_array($selectedIdsInput)) {
    $selectedIdsInput = [];
}

$selectedIdsPosted = parseSelectedIds($selectedIdsInput);
$selectedIds = filterSelectedIds($selectedIdsPosted, $idGroupMap);

$selectedIdMap = [];
foreach ($selectedIds as $selectedId) {
    $selectedIdMap[(int)$selectedId] = true;
}

$rowsDeleted = 0;
$rowsUpdated = 0;

if ($applyRequested) {
    if ($action === 'save') {
        $rowsInput = $_POST['rows'] ?? [];
        if (!is_array($rowsInput) || empty($rowsInput)) {
            $message = ['type' => 'warn', 'text' => 'No hay filas para actualizar.'];
        } else {
            $pdo->beginTransaction();
            try {
                $stmtUpdate = $pdo->prepare(
                    "UPDATE nehemias
                     SET Nombres = ?, Apellidos = ?, Numero_Cedula = ?, Telefono = ?, Lider = ?, Lider_Nehemias = ?, Fecha_Registro = ?
                     WHERE Id_Nehemias = ?"
                );

                foreach ($rowsInput as $rowIdRaw => $rowData) {
                    $rowId = (int)$rowIdRaw;
                    if ($rowId <= 0 || !isset($idGroupMap[$rowId]) || !is_array($rowData)) {
                        continue;
                    }

                    $nombres = trim((string)($rowData['Nombres'] ?? ''));
                    $apellidos = trim((string)($rowData['Apellidos'] ?? ''));
                    $numeroCedula = trim((string)($rowData['Numero_Cedula'] ?? ''));
                    $telefono = trim((string)($rowData['Telefono'] ?? ''));
                    $lider = trim((string)($rowData['Lider'] ?? ''));
                    $liderNehemias = trim((string)($rowData['Lider_Nehemias'] ?? ''));
                    $fechaRegistro = trim((string)($rowData['Fecha_Registro'] ?? ''));

                    $stmtUpdate->execute([
                        $nombres,
                        $apellidos,
                        $numeroCedula,
                        $telefono,
                        $lider,
                        $liderNehemias,
                        $fechaRegistro,
                        $rowId,
                    ]);

                    $rowsUpdated += (int)$stmtUpdate->rowCount();
                }

                $pdo->commit();
                $message = ['type' => 'success', 'text' => 'Cambios guardados. Filas actualizadas: ' . $rowsUpdated];
            } catch (Throwable $e) {
                $pdo->rollBack();
                $message = ['type' => 'error', 'text' => 'Error guardando cambios: ' . $e->getMessage()];
            }
        }
    } elseif ($action === 'delete') {
        if ($confirmarNormalizado !== 'SI') {
            $message = ['type' => 'warn', 'text' => 'Confirmación inválida. Escribe SI para eliminar.'];
        } elseif (empty($selectedIds)) {
            $message = ['type' => 'warn', 'text' => 'No seleccionaste registros válidos para eliminar.'];
        } else {
            $pdo->beginTransaction();
            try {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $stmtDelete = $pdo->prepare("DELETE FROM nehemias WHERE Id_Nehemias IN ($placeholders)");
                $stmtDelete->execute($selectedIds);
                $rowsDeleted = (int)$stmtDelete->rowCount();
                $pdo->commit();

                $message = ['type' => 'success', 'text' => 'Eliminación completada. Registros eliminados: ' . $rowsDeleted];
            } catch (Throwable $e) {
                $pdo->rollBack();
                $message = ['type' => 'error', 'text' => 'Error eliminando registros: ' . $e->getMessage()];
            }
        }
    } else {
        $message = ['type' => 'warn', 'text' => 'Acción no válida.'];
    }

    $groups = buildDuplicateGroups(loadNehemiasRows($pdo));
    $state = prepareGroupState($groups);
    $groups = $state['groups'];
    $idGroupMap = $state['id_group_map'];
    $selectedIds = filterSelectedIds($selectedIdsPosted, $idGroupMap);
    $selectedIdMap = [];
    foreach ($selectedIds as $selectedId) {
        $selectedIdMap[(int)$selectedId] = true;
    }
}

$totalGroups = count($groups);
$totalCandidates = count($idGroupMap);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Registros Duplicados en nehemias</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f7fb; }
        .wrap { max-width: 1200px; margin: 0 auto; background: #fff; border-radius: 10px; padding: 20px; border: 1px solid #e5e7eb; }
        .msg { padding: 10px 12px; border-radius: 8px; margin: 10px 0; }
        .info { background: #eef2ff; color: #3730a3; }
        .success { background: #ecfdf5; color: #065f46; }
        .warn { background: #fffbeb; color: #92400e; }
        .error { background: #fef2f2; color: #991b1b; }
        .toolbar { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin: 10px 0 16px; }
        .btn { border: 0; background: #2563eb; color: #fff; padding: 9px 14px; border-radius: 7px; cursor: pointer; }
        .btn-secondary { background: #6b7280; }
        .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; margin-top: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; font-size: 14px; }
        th { background: #f9fafb; }
        .tag-keep { color: #065f46; font-weight: bold; }
        .muted { color: #6b7280; }
        .cell-input { width: 100%; box-sizing: border-box; font-size: 13px; }
        .cell-input.modified { background: #fffde7; border: 1px solid #f4e58a; }
        .dirty-indicator { display: none; padding: 8px 10px; border-radius: 8px; margin: 8px 0; background: #fffbeb; color: #92400e; }
        .dirty-indicator.show { display: block; }
        .column-controls { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 8px; }
        .column-controls label { font-size: 13px; color: #374151; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Gestionar registros duplicados en tabla nehemias</h1>
    <p class="muted">Este proceso solo usa <strong>Nombres</strong> y <strong>Apellidos</strong> para detectar duplicados. No usa cédula ni teléfono para comparar.</p>
    <p class="muted"><strong>No elimina la tabla nehemias.</strong> Solo edita o elimina <strong>filas seleccionadas</strong> de esa tabla.</p>
    <p class="muted">Puedes editar cualquier campo de cada fila, guardar y luego marcar libremente los registros a eliminar (1 o varios).</p>

    <div class="msg <?= htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($message['text'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <div id="dirty-indicator" class="dirty-indicator">Tienes cambios sin guardar en algunos campos.</div>

    <div class="card">
        <h3 style="margin:0 0 8px;">Columnas visibles</h3>
        <div class="column-controls">
            <label><input type="checkbox" class="col-toggle" data-col="id" checked> ID</label>
            <label><input type="checkbox" class="col-toggle" data-col="nombres" checked> Nombres</label>
            <label><input type="checkbox" class="col-toggle" data-col="apellidos" checked> Apellidos</label>
            <label><input type="checkbox" class="col-toggle" data-col="cedula" checked> Cédula</label>
            <label><input type="checkbox" class="col-toggle" data-col="telefono" checked> Teléfono</label>
            <label><input type="checkbox" class="col-toggle" data-col="lider" checked> Líder</label>
            <label><input type="checkbox" class="col-toggle" data-col="lider-nehemias" checked> Líder Nehemías</label>
            <label><input type="checkbox" class="col-toggle" data-col="fecha" checked> Fecha</label>
        </div>
    </div>

    <p>
        <strong>Grupos duplicados:</strong> <?= $totalGroups ?> |
        <strong>Registros candidatos a borrar:</strong> <?= $totalCandidates ?>
    </p>

    <form method="POST">
        <div class="toolbar">
            <button type="button" class="btn btn-secondary" onclick="toggleAll(true)">Seleccionar todo</button>
            <button type="button" class="btn btn-secondary" onclick="toggleAll(false)">Deseleccionar todo</button>
        </div>

        <?php if (empty($groups)): ?>
            <div class="card">
                <p>No hay duplicados por nombres+apellidos en este momento.</p>
            </div>
        <?php else: ?>
            <?php foreach ($groups as $group): ?>
                <div class="card">
                    <h3 style="margin:0 0 8px;">
                        <?= htmlspecialchars((string)$group['display_nombres'], ENT_QUOTES, 'UTF-8') ?>
                        <?= htmlspecialchars((string)$group['display_apellidos'], ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <p class="muted">Coincidencia normalizada: <?= htmlspecialchars((string)$group['norm_nombres'] . ' ' . (string)$group['norm_apellidos'], ENT_QUOTES, 'UTF-8') ?> | Registros: <?= count($group['rows']) ?></p>
                    <?php $groupIdForActions = (string)($group['group_id'] ?? ''); ?>
                    <div class="toolbar" style="margin:8px 0 0;">
                        <button type="button" class="btn btn-secondary" onclick="setGroupDelete('<?= htmlspecialchars($groupIdForActions, ENT_QUOTES, 'UTF-8') ?>', false)">Conservar todos este grupo</button>
                        <button type="button" class="btn btn-secondary" onclick="setGroupDelete('<?= htmlspecialchars($groupIdForActions, ENT_QUOTES, 'UTF-8') ?>', true)">Marcar todo este grupo</button>
                    </div>

                    <table>
                        <thead>
                        <tr>
                            <th>Eliminar</th>
                            <th class="col-id">ID</th>
                            <th class="col-nombres">Nombres</th>
                            <th class="col-apellidos">Apellidos</th>
                            <th class="col-cedula">Cédula</th>
                            <th class="col-telefono">Teléfono</th>
                            <th class="col-lider">Líder</th>
                            <th class="col-lider-nehemias">Líder Nehemías</th>
                            <th class="col-fecha">Fecha</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($group['rows'] as $row): ?>
                            <?php $rowId = (int)$row['Id_Nehemias']; ?>
                            <?php $groupId = (string)($group['group_id'] ?? ''); ?>
                            <?php $isChecked = isset($selectedIdMap[$rowId]); ?>
                            <tr>
                                <td>
                                    <input type="checkbox"
                                           class="dup-checkbox"
                                           name="selected_ids[]"
                                           data-group="<?= htmlspecialchars($groupId, ENT_QUOTES, 'UTF-8') ?>"
                                           data-id="<?= $rowId ?>"
                                           value="<?= $rowId ?>"
                                           <?= $isChecked ? 'checked' : '' ?>>
                                </td>
                                <td class="col-id"><?= $rowId ?></td>
                                <td class="col-nombres"><input class="cell-input" type="text" name="rows[<?= $rowId ?>][Nombres]" value="<?= htmlspecialchars((string)$row['Nombres'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="col-apellidos"><input class="cell-input" type="text" name="rows[<?= $rowId ?>][Apellidos]" value="<?= htmlspecialchars((string)$row['Apellidos'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="col-cedula"><input class="cell-input" type="text" name="rows[<?= $rowId ?>][Numero_Cedula]" value="<?= htmlspecialchars((string)$row['Numero_Cedula'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="col-telefono"><input class="cell-input" type="text" name="rows[<?= $rowId ?>][Telefono]" value="<?= htmlspecialchars((string)$row['Telefono'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="col-lider"><input class="cell-input" type="text" name="rows[<?= $rowId ?>][Lider]" value="<?= htmlspecialchars((string)$row['Lider'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="col-lider-nehemias"><input class="cell-input" type="text" name="rows[<?= $rowId ?>][Lider_Nehemias]" value="<?= htmlspecialchars((string)$row['Lider_Nehemias'], ENT_QUOTES, 'UTF-8') ?>"></td>
                                <td class="col-fecha"><input class="cell-input" type="text" name="rows[<?= $rowId ?>][Fecha_Registro]" value="<?= htmlspecialchars((string)$row['Fecha_Registro'], ENT_QUOTES, 'UTF-8') ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>

            <div class="card">
                <h3 style="margin:0 0 8px;">Acciones sobre filas</h3>
                <p>1) Si editaste datos, pulsa <strong>Guardar cambios</strong>. 2) Para borrar filas, marca registros, escribe <strong>SI</strong> y pulsa <strong>Eliminar seleccionados</strong>.</p>
                <div class="toolbar">
                    <button type="submit" class="btn btn-secondary" name="action" value="save">Guardar cambios en filas</button>
                    <input type="text" name="confirmar" placeholder="Escribe SI para borrar filas">
                    <button type="submit" class="btn" name="action" value="delete">Eliminar filas seleccionadas</button>
                </div>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
var hasDirtyInputs = false;

function toggleAll(checked) {
    var items = document.querySelectorAll('.dup-checkbox');
    items.forEach(function (item) {
        item.checked = checked;
    });
}

function setGroupDelete(groupId, shouldDelete) {
    var items = document.querySelectorAll('.dup-checkbox[data-group="' + groupId + '"]');
    items.forEach(function (item) {
        item.checked = shouldDelete;
    });
}

function applyColumnVisibility(columnKey, isVisible) {
    var cells = document.querySelectorAll('.col-' + columnKey);
    cells.forEach(function (cell) {
        cell.style.display = isVisible ? '' : 'none';
    });
}

document.querySelectorAll('.col-toggle').forEach(function (toggle) {
    var columnKey = toggle.getAttribute('data-col') || '';
    var storageKey = 'dup_cols_' + columnKey;
    var saved = localStorage.getItem(storageKey);
    if (saved === '0') {
        toggle.checked = false;
    }

    applyColumnVisibility(columnKey, toggle.checked);

    toggle.addEventListener('change', function () {
        applyColumnVisibility(columnKey, toggle.checked);
        localStorage.setItem(storageKey, toggle.checked ? '1' : '0');
    });
});

function updateDirtyIndicator() {
    var indicator = document.getElementById('dirty-indicator');
    if (!indicator) {
        return;
    }
    if (hasDirtyInputs) {
        indicator.classList.add('show');
    } else {
        indicator.classList.remove('show');
    }
}

document.querySelectorAll('.cell-input').forEach(function (input) {
    input.setAttribute('data-original', input.value);

    input.addEventListener('input', function () {
        var original = input.getAttribute('data-original') || '';
        var current = input.value;
        if (current !== original) {
            input.classList.add('modified');
        } else {
            input.classList.remove('modified');
        }

        hasDirtyInputs = document.querySelectorAll('.cell-input.modified').length > 0;
        updateDirtyIndicator();
    });
});

document.querySelectorAll('button[name="action"][value="save"]').forEach(function (button) {
    button.addEventListener('click', function () {
        hasDirtyInputs = false;
        updateDirtyIndicator();
    });
});
</script>
</body>
</html>
