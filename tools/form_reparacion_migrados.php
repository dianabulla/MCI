<?php
/**
 * Formulario de reparacion: asignar automaticamente celula, lider y ministerio,
 * y dejar Ganado en = Migrados.
 *
 * Uso web:
 *   /tools/form_reparacion_migrados.php
 */

date_default_timezone_set('America/Bogota');
require_once __DIR__ . '/../conexion.php';

header('Content-Type: text/html; charset=utf-8');

function normalizeDigits($value) {
    return preg_replace('/\D+/', '', (string)$value) ?: '';
}

function normalizeChecklist($rawChecklist) {
    $decoded = [];
    if (is_string($rawChecklist) && trim($rawChecklist) !== '') {
        $tmp = json_decode($rawChecklist, true);
        if (is_array($tmp)) {
            $decoded = $tmp;
        }
    }

    if (!isset($decoded['Ganar']) || !is_array($decoded['Ganar'])) {
        $decoded['Ganar'] = [false, false, false, false];
    }

    for ($i = 0; $i < 4; $i++) {
        if (!array_key_exists($i, $decoded['Ganar'])) {
            $decoded['Ganar'][$i] = false;
        }
        $decoded['Ganar'][$i] = !empty($decoded['Ganar'][$i]);
    }

    if (!isset($decoded['_meta']) || !is_array($decoded['_meta'])) {
        $decoded['_meta'] = [];
    }

    unset($decoded['_meta']['reasignado_automatico']);
    unset($decoded['_meta']['reasignado_automatico_at']);
    unset($decoded['_meta']['reasignado_automatico_motivo']);
    if (empty($decoded['_meta'])) {
        unset($decoded['_meta']);
    }

    $decoded['Ganar'][0] = true;

    return $decoded;
}

function jsonResponse($payload, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function getCelulaData(PDO $pdo, $idCelula) {
    $idCelula = (int)$idCelula;
    if ($idCelula <= 0) {
        return null;
    }

    $sql = "SELECT
                c.Id_Celula,
                c.Nombre_Celula,
                c.Id_Lider,
                l.Nombre AS Lider_Nombre,
                l.Apellido AS Lider_Apellido,
                l.Id_Ministerio,
                m.Nombre_Ministerio
            FROM celula c
            LEFT JOIN persona l ON l.Id_Persona = c.Id_Lider
            LEFT JOIN ministerio m ON m.Id_Ministerio = l.Id_Ministerio
            WHERE c.Id_Celula = ?
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idCelula]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function findPersona(PDO $pdo, $documento, $telefono) {
    $documento = trim((string)$documento);
    $telefonoNorm = normalizeDigits($telefono);

    if ($documento !== '') {
        $stmt = $pdo->prepare("SELECT * FROM persona WHERE Numero_Documento = ? LIMIT 1");
        $stmt->execute([$documento]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    if ($telefonoNorm !== '') {
        $stmt = $pdo->prepare("SELECT *
                               FROM persona
                               WHERE REPLACE(REPLACE(REPLACE(Telefono, ' ', ''), '-', ''), '+', '') = ?
                               LIMIT 1");
        $stmt->execute([$telefonoNorm]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row;
        }
    }

    return null;
}

$action = $_GET['action'] ?? '';

if ($action === 'buscar_persona') {
    try {
        $documento = trim((string)($_GET['documento'] ?? ''));
        $telefono = trim((string)($_GET['telefono'] ?? ''));

        $persona = findPersona($pdo, $documento, $telefono);
        if (!$persona) {
            jsonResponse([
                'ok' => true,
                'found' => false,
                'message' => 'No existe persona con ese documento/telefono.'
            ]);
        }

        jsonResponse([
            'ok' => true,
            'found' => true,
            'persona' => [
                'id_persona' => (int)($persona['Id_Persona'] ?? 0),
                'nombre' => (string)($persona['Nombre'] ?? ''),
                'apellido' => (string)($persona['Apellido'] ?? ''),
                'tipo_documento' => (string)($persona['Tipo_Documento'] ?? ''),
                'numero_documento' => (string)($persona['Numero_Documento'] ?? ''),
                'telefono' => (string)($persona['Telefono'] ?? ''),
                'id_celula' => (int)($persona['Id_Celula'] ?? 0),
                'id_lider' => (int)($persona['Id_Lider'] ?? 0),
                'id_ministerio' => (int)($persona['Id_Ministerio'] ?? 0),
                'tipo_reunion' => (string)($persona['Tipo_Reunion'] ?? ''),
                'proceso' => (string)($persona['Proceso'] ?? ''),
            ]
        ]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($action === 'datos_celula') {
    try {
        $idCelula = (int)($_GET['id_celula'] ?? 0);
        if ($idCelula <= 0) {
            jsonResponse(['ok' => false, 'error' => 'ID de celula invalido.'], 400);
        }

        $celula = getCelulaData($pdo, $idCelula);
        if (!$celula) {
            jsonResponse(['ok' => false, 'error' => 'La celula no existe.'], 404);
        }

        $idLider = (int)($celula['Id_Lider'] ?? 0);
        $idMinisterio = (int)($celula['Id_Ministerio'] ?? 0);

        jsonResponse([
            'ok' => true,
            'celula' => [
                'id_celula' => (int)$celula['Id_Celula'],
                'nombre_celula' => (string)($celula['Nombre_Celula'] ?? ''),
                'id_lider' => $idLider,
                'lider_nombre' => trim((string)($celula['Lider_Nombre'] ?? '') . ' ' . (string)($celula['Lider_Apellido'] ?? '')),
                'id_ministerio' => $idMinisterio,
                'ministerio_nombre' => (string)($celula['Nombre_Ministerio'] ?? ''),
                'puede_asignar' => $idLider > 0 && $idMinisterio > 0,
            ]
        ]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($action === 'buscar_celulas') {
    try {
        $q = trim((string)($_GET['q'] ?? ''));
        $sql = "SELECT
                    c.Id_Celula,
                    c.Nombre_Celula,
                    c.Id_Lider,
                    l.Nombre AS Lider_Nombre,
                    l.Apellido AS Lider_Apellido,
                    l.Id_Ministerio,
                    m.Nombre_Ministerio
                FROM celula c
                LEFT JOIN persona l ON l.Id_Persona = c.Id_Lider
                LEFT JOIN ministerio m ON m.Id_Ministerio = l.Id_Ministerio
                WHERE c.Estado_Celula = 'Activa' OR c.Estado_Celula IS NULL";

        $params = [];
        if ($q !== '') {
            $sql .= " AND (c.Nombre_Celula LIKE ? OR l.Nombre LIKE ? OR l.Apellido LIKE ? OR m.Nombre_Ministerio LIKE ?)";
            $like = '%' . $q . '%';
            $params = [$like, $like, $like, $like];
        }

        $sql .= " ORDER BY c.Nombre_Celula LIMIT 60";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $r) {
            $idLider = (int)($r['Id_Lider'] ?? 0);
            $idMin = (int)($r['Id_Ministerio'] ?? 0);
            $liderNombre = trim((string)($r['Lider_Nombre'] ?? '') . ' ' . (string)($r['Lider_Apellido'] ?? ''));
            $minNombre = (string)($r['Nombre_Ministerio'] ?? '');

            $result[] = [
                'id_celula' => (int)$r['Id_Celula'],
                'nombre_celula' => (string)($r['Nombre_Celula'] ?? ''),
                'id_lider' => $idLider,
                'lider_nombre' => $liderNombre,
                'id_ministerio' => $idMin,
                'ministerio_nombre' => $minNombre,
                'puede_asignar' => $idLider > 0 && $idMin > 0,
                'label' => (string)($r['Nombre_Celula'] ?? '') . ' — ' . ($liderNombre ?: 'Sin lider') . ' — ' . ($minNombre ?: 'Sin ministerio'),
            ];
        }

        jsonResponse(['ok' => true, 'celulas' => $result]);
    } catch (Throwable $e) {
        jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

$mensaje = '';
$tipoMensaje = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $apellido = trim((string)($_POST['apellido'] ?? ''));
        $tipoDocumento = trim((string)($_POST['tipo_documento'] ?? 'Cedula de Ciudadania'));
        $numeroDocumento = trim((string)($_POST['numero_documento'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $idCelula = (int)($_POST['id_celula'] ?? 0);

        if ($nombre === '' || $apellido === '') {
            throw new RuntimeException('Nombre y apellido son obligatorios.');
        }
        if ($numeroDocumento === '' && normalizeDigits($telefono) === '') {
            throw new RuntimeException('Debe indicar documento o telefono para identificar la persona.');
        }
        if ($idCelula <= 0) {
            throw new RuntimeException('Debe indicar un ID de celula valido.');
        }

        $celula = getCelulaData($pdo, $idCelula);
        if (!$celula) {
            throw new RuntimeException('La celula indicada no existe.');
        }

        $idLider = (int)($celula['Id_Lider'] ?? 0);
        $idMinisterio = (int)($celula['Id_Ministerio'] ?? 0);

        if ($idLider <= 0 || $idMinisterio <= 0) {
            throw new RuntimeException('Esa celula no tiene lider o ministerio configurado.');
        }

        $personaExistente = findPersona($pdo, $numeroDocumento, $telefono);

        $pdo->beginTransaction();

        if ($personaExistente) {
            $idPersona = (int)$personaExistente['Id_Persona'];
            $checklist = normalizeChecklist((string)($personaExistente['Escalera_Checklist'] ?? ''));
            $checklistJson = json_encode($checklist, JSON_UNESCAPED_UNICODE);

            $sql = "UPDATE persona
                    SET Nombre = ?,
                        Apellido = ?,
                        Tipo_Documento = ?,
                        Numero_Documento = ?,
                        Telefono = ?,
                        Id_Celula = ?,
                        Id_Lider = ?,
                        Id_Ministerio = ?,
                        Tipo_Reunion = 'Migrados',
                        Proceso = 'Ganar',
                        Estado_Cuenta = 'Activo',
                        Fecha_Asignacion_Lider = NOW(),
                        Escalera_Checklist = ?
                    WHERE Id_Persona = ?";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $apellido,
                $tipoDocumento,
                $numeroDocumento !== '' ? $numeroDocumento : null,
                $telefono !== '' ? $telefono : null,
                $idCelula,
                $idLider,
                $idMinisterio,
                $checklistJson,
                $idPersona,
            ]);

            $pdo->commit();
            $mensaje = 'Persona actualizada: se asigno celula, lider, ministerio y Ganado en = Migrados.';
            $tipoMensaje = 'ok';
        } else {
            $checklist = normalizeChecklist('');
            $checklistJson = json_encode($checklist, JSON_UNESCAPED_UNICODE);

            $sql = "INSERT INTO persona (
                        Nombre,
                        Apellido,
                        Tipo_Documento,
                        Numero_Documento,
                        Telefono,
                        Estado_Cuenta,
                        Tipo_Reunion,
                        Proceso,
                        Escalera_Checklist,
                        Id_Celula,
                        Id_Lider,
                        Id_Ministerio,
                        Fecha_Asignacion_Lider,
                        Fecha_Registro,
                        Fecha_Registro_Unix
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        'Activo',
                        'Migrados',
                        'Ganar',
                        ?,
                        ?, ?, ?,
                        NOW(),
                        NOW(),
                        UNIX_TIMESTAMP(NOW())
                    )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $nombre,
                $apellido,
                $tipoDocumento,
                $numeroDocumento !== '' ? $numeroDocumento : null,
                $telefono !== '' ? $telefono : null,
                $checklistJson,
                $idCelula,
                $idLider,
                $idMinisterio,
            ]);

            $pdo->commit();
            $mensaje = 'Persona creada con celula, lider, ministerio y Ganado en = Migrados.';
            $tipoMensaje = 'ok';
        }
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $mensaje = 'ERROR: ' . $e->getMessage();
        $tipoMensaje = 'err';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reparacion Rapida de Personas Migradas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fafafa; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 16px; max-width: 980px; }
        h2 { margin-top: 0; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
        .field { display: flex; flex-direction: column; }
        label { font-size: 13px; margin-bottom: 4px; color: #333; }
        input, select { padding: 8px; border: 1px solid #bbb; border-radius: 4px; }
        .readonly { background: #f2f2f2; }
        .row { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
        button { border: 0; padding: 10px 14px; border-radius: 4px; cursor: pointer; }
        .btn-primary { background: #1f6feb; color: #fff; }
        .btn-secondary { background: #666; color: #fff; }
        .msg { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .msg.ok { background: #e8f7ed; color: #1d6b34; border: 1px solid #bfe8cc; }
        .msg.err { background: #fdecec; color: #9f2f2f; border: 1px solid #f5bcbc; }
        .help { font-size: 12px; color: #666; margin-top: 6px; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="card">
    <h2>Reparacion Rapida de Personas</h2>
    <div class="help">Flujo: busca por documento/teléfono → selecciona la célula del autocompletado → líder y ministerio se llenan solos → Guardar.</div>

    <?php if ($mensaje !== ''): ?>
        <div class="msg <?= htmlspecialchars($tipoMensaje, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="field">
            <label for="buscar_documento">Buscar por documento</label>
            <input type="text" id="buscar_documento" placeholder="Ej: 1073155101">
        </div>
        <div class="field">
            <label for="buscar_telefono">Buscar por telefono</label>
            <input type="text" id="buscar_telefono" placeholder="Ej: 3140000000">
        </div>
        <div class="field" style="justify-content:end;">
            <label>&nbsp;</label>
            <button type="button" class="btn-secondary" id="btn_buscar">Buscar persona</button>
        </div>
    </div>

    <form method="post" id="form_reparacion" autocomplete="off">
        <div class="grid" style="margin-top:12px;">
            <div class="field">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>
            <div class="field">
                <label for="apellido">Apellido</label>
                <input type="text" id="apellido" name="apellido" required>
            </div>
            <div class="field">
                <label for="tipo_documento">Tipo documento</label>
                <select id="tipo_documento" name="tipo_documento">
                    <option value="Cedula de Ciudadania">Cedula de Ciudadania</option>
                    <option value="Cedula Extranjera">Cedula Extranjera</option>
                    <option value="Registro Civil">Registro Civil</option>
                </select>
            </div>

            <div class="field">
                <label for="numero_documento">Numero documento</label>
                <input type="text" id="numero_documento" name="numero_documento">
            </div>
            <div class="field">
                <label for="telefono">Telefono</label>
                <input type="text" id="telefono" name="telefono">
            </div>
            <div class="field" style="position:relative;">
                <label for="celula_search">Célula</label>
                <input type="text" id="celula_search" placeholder="Escribe nombre de célula, líder o ministerio..." autocomplete="off">
                <input type="hidden" id="id_celula" name="id_celula" required>
                <div id="celula_dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid #bbb;border-radius:0 0 4px 4px;max-height:220px;overflow-y:auto;z-index:9999;box-shadow:0 4px 10px rgba(0,0,0,0.12);"></div>
                <div class="help" id="celula_info"></div>
            </div>

            <div class="field">
                <label>Líder (auto)</label>
                <input type="text" id="lider_nombre_view" class="readonly" readonly>
            </div>
            <div class="field">
                <label>Ministerio (auto)</label>
                <input type="text" id="ministerio_view" class="readonly" readonly>
            </div>

            <div class="field">
                <label for="tipo_reunion_view">Ganado en</label>
                <input type="text" id="tipo_reunion_view" class="readonly" value="Migrados" readonly>
            </div>
            <div class="field">
                <label for="proceso_view">Proceso</label>
                <input type="text" id="proceso_view" class="readonly" value="Ganar" readonly>
            </div>
            <div class="field"></div>
        </div>

        <div class="row">
            <button type="submit" class="btn-primary">Guardar reparacion</button>
            <button type="button" class="btn-secondary" id="btn_limpiar">Limpiar</button>
        </div>
    </form>
</div>

<script>
(function () {
    const btnBuscar = document.getElementById('btn_buscar');
    const btnLimpiar = document.getElementById('btn_limpiar');
    const celulaSearch = document.getElementById('celula_search');
    const idCelulaHidden = document.getElementById('id_celula');
    const celulaDropdown = document.getElementById('celula_dropdown');
    const celulaInfo = document.getElementById('celula_info');

    const buscarDocumento = document.getElementById('buscar_documento');
    const buscarTelefono = document.getElementById('buscar_telefono');

    const nombre = document.getElementById('nombre');
    const apellido = document.getElementById('apellido');
    const tipoDocumento = document.getElementById('tipo_documento');
    const numeroDocumento = document.getElementById('numero_documento');
    const telefono = document.getElementById('telefono');

    const liderNombreView = document.getElementById('lider_nombre_view');
    const ministerioView = document.getElementById('ministerio_view');

    let searchTimeout = null;
    let celulaSeleccionada = null;

    // ── Autocomplete célula ─────────────────────────────────────────────
    function clearCelulaSelection() {
        idCelulaHidden.value = '';
        liderNombreView.value = '';
        ministerioView.value = '';
        celulaInfo.textContent = '';
        celulaSeleccionada = null;
    }

    function setCelulaSelection(c) {
        celulaSeleccionada = c;
        idCelulaHidden.value = c.id_celula;
        celulaSearch.value = c.nombre_celula;
        liderNombreView.value = c.lider_nombre || '';
        ministerioView.value = c.ministerio_nombre || '';
        celulaInfo.textContent = c.puede_asignar
            ? '✔ Líder: ' + (c.lider_nombre || '?') + ' | Ministerio: ' + (c.ministerio_nombre || '?')
            : '⚠ Esta célula no tiene líder o ministerio configurado.';
        hideDropdown();
    }

    function showDropdown(celulas) {
        celulaDropdown.innerHTML = '';
        if (!celulas.length) {
            celulaDropdown.innerHTML = '<div style="padding:10px;color:#888;font-size:13px;">Sin resultados</div>';
        } else {
            celulas.forEach(function (c) {
                var item = document.createElement('div');
                item.style.cssText = 'padding:9px 12px;cursor:pointer;font-size:13px;border-bottom:1px solid #f0f0f0;';
                item.innerHTML = '<strong>' + esc(c.nombre_celula) + '</strong>'
                    + '<span style="color:#555"> — ' + esc(c.lider_nombre || 'Sin líder') + '</span>'
                    + '<span style="color:#999"> — ' + esc(c.ministerio_nombre || 'Sin ministerio') + '</span>';
                item.addEventListener('mousedown', function (e) {
                    e.preventDefault(); // evita blur antes del click
                    setCelulaSelection(c);
                });
                item.addEventListener('mouseenter', function () { item.style.background = '#f0f5ff'; });
                item.addEventListener('mouseleave', function () { item.style.background = ''; });
                celulaDropdown.appendChild(item);
            });
        }
        celulaDropdown.style.display = 'block';
    }

    function hideDropdown() {
        celulaDropdown.style.display = 'none';
    }

    function esc(str) {
        var d = document.createElement('span');
        d.textContent = str;
        return d.innerHTML;
    }

    async function fetchCelulas(q) {
        try {
            const url = '?action=buscar_celulas' + (q ? '&q=' + encodeURIComponent(q) : '');
            const res = await fetch(url);
            const data = await res.json();
            if (data.ok) showDropdown(data.celulas || []);
        } catch (e) {
            hideDropdown();
        }
    }

    celulaSearch.addEventListener('focus', function () {
        // Mostrar lista inicial si está vacía
        if (!celulaSearch.value.trim()) {
            fetchCelulas('');
        } else {
            fetchCelulas(celulaSearch.value.trim());
        }
    });

    celulaSearch.addEventListener('input', function () {
        clearCelulaSelection();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function () {
            fetchCelulas(celulaSearch.value.trim());
        }, 250);
    });

    celulaSearch.addEventListener('blur', function () {
        setTimeout(hideDropdown, 150);
    });

    document.addEventListener('click', function (e) {
        if (!celulaSearch.contains(e.target) && !celulaDropdown.contains(e.target)) {
            hideDropdown();
        }
    });

    // ── Buscar persona ──────────────────────────────────────────────────
    async function cargarCelulaPorId(idCelula) {
        if (!idCelula) return;
        try {
            const res = await fetch('?action=datos_celula&id_celula=' + encodeURIComponent(idCelula));
            const data = await res.json();
            if (data.ok && data.celula) {
                setCelulaSelection(data.celula);
            }
        } catch (e) { /* silencioso */ }
    }

    async function buscarPersona() {
        const doc = (buscarDocumento.value || '').trim();
        const tel = (buscarTelefono.value || '').trim();

        if (!doc && !tel) {
            alert('Ingresa documento o teléfono para buscar.');
            return;
        }

        try {
            const url = '?action=buscar_persona&documento=' + encodeURIComponent(doc) + '&telefono=' + encodeURIComponent(tel);
            const res = await fetch(url);
            const data = await res.json();

            if (!data.ok) {
                alert(data.error || 'No se pudo buscar persona.');
                return;
            }

            if (!data.found) {
                alert('No existe en BD. Puedes diligenciar y guardar para crearla.');
                numeroDocumento.value = doc;
                telefono.value = tel;
                return;
            }

            const p = data.persona || {};
            nombre.value = p.nombre || '';
            apellido.value = p.apellido || '';
            tipoDocumento.value = p.tipo_documento || 'Cedula de Ciudadania';
            numeroDocumento.value = p.numero_documento || doc;
            telefono.value = p.telefono || tel;

            if (p.id_celula) {
                await cargarCelulaPorId(p.id_celula);
            }

            alert('Persona encontrada. Revisa y guarda para reparar.');
        } catch (err) {
            alert('Error de red al buscar persona.');
        }
    }

    btnBuscar.addEventListener('click', buscarPersona);

    btnLimpiar.addEventListener('click', function () {
        document.getElementById('form_reparacion').reset();
        buscarDocumento.value = '';
        buscarTelefono.value = '';
        celulaSearch.value = '';
        clearCelulaSelection();
    });
})();
</script>
</body>
</html>
