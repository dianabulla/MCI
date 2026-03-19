<?php
/**
 * Formulario de reparacion: asignar automaticamente celula, lider y ministerio,
 * y dejar Ganado en = Migrados.
 *
 * Flujo: Ministerio → Célula (filtrada) → Líder (auto)
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
        if (!array_key_exists($i, $decoded['Ganar'])) $decoded['Ganar'][$i] = false;
        $decoded['Ganar'][$i] = !empty($decoded['Ganar'][$i]);
    }
    if (!isset($decoded['_meta']) || !is_array($decoded['_meta'])) $decoded['_meta'] = [];
    unset($decoded['_meta']['reasignado_automatico'], $decoded['_meta']['reasignado_automatico_at'], $decoded['_meta']['reasignado_automatico_motivo']);
    if (empty($decoded['_meta'])) unset($decoded['_meta']);
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
    if ($idCelula <= 0) return null;
    $stmt = $pdo->prepare("SELECT c.Id_Celula, c.Nombre_Celula, c.Id_Lider,
            l.Nombre AS Lider_Nombre, l.Apellido AS Lider_Apellido,
            l.Id_Ministerio, m.Nombre_Ministerio
        FROM celula c
        LEFT JOIN persona l ON l.Id_Persona = c.Id_Lider
        LEFT JOIN ministerio m ON m.Id_Ministerio = l.Id_Ministerio
        WHERE c.Id_Celula = ? LIMIT 1");
    $stmt->execute([$idCelula]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function findPersona(PDO $pdo, $documento, $telefono) {
    $documento = trim((string)$documento);
    $tel = normalizeDigits($telefono);
    if ($documento !== '') {
        $stmt = $pdo->prepare("SELECT * FROM persona WHERE Numero_Documento = ? LIMIT 1");
        $stmt->execute([$documento]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    }
    if ($tel !== '') {
        $stmt = $pdo->prepare("SELECT * FROM persona WHERE REPLACE(REPLACE(REPLACE(Telefono,' ',''),'-',''),'+','') = ? LIMIT 1");
        $stmt->execute([$tel]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    }
    return null;
}

$action = $_GET['action'] ?? '';

if ($action === 'buscar_persona') {
    try {
        $persona = findPersona($pdo, $_GET['documento'] ?? '', $_GET['telefono'] ?? '');
        if (!$persona) jsonResponse(['ok' => true, 'found' => false]);
        jsonResponse(['ok' => true, 'found' => true, 'persona' => [
            'id_persona'       => (int)($persona['Id_Persona'] ?? 0),
            'nombre'           => (string)($persona['Nombre'] ?? ''),
            'apellido'         => (string)($persona['Apellido'] ?? ''),
            'tipo_documento'   => (string)($persona['Tipo_Documento'] ?? ''),
            'numero_documento' => (string)($persona['Numero_Documento'] ?? ''),
            'telefono'         => (string)($persona['Telefono'] ?? ''),
            'id_celula'        => (int)($persona['Id_Celula'] ?? 0),
            'id_ministerio'    => (int)($persona['Id_Ministerio'] ?? 0),
        ]]);
    } catch (Throwable $e) { jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500); }
}

if ($action === 'buscar_ministerios') {
    try {
        $q = trim((string)($_GET['q'] ?? ''));
        $sql = "SELECT Id_Ministerio, Nombre_Ministerio FROM ministerio";
        $params = [];
        if ($q !== '') { $sql .= " WHERE Nombre_Ministerio LIKE ?"; $params = ['%'.$q.'%']; }
        $sql .= " ORDER BY Nombre_Ministerio LIMIT 30";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = array_map(fn($r) => ['id_ministerio' => (int)$r['Id_Ministerio'], 'nombre_ministerio' => (string)($r['Nombre_Ministerio'] ?? '')], $rows);
        jsonResponse(['ok' => true, 'ministerios' => $result]);
    } catch (Throwable $e) { jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500); }
}

if ($action === 'buscar_celulas') {
    try {
        $q = trim((string)($_GET['q'] ?? ''));
        $idMin = (int)($_GET['id_ministerio'] ?? 0);
        $sql = "SELECT c.Id_Celula, c.Nombre_Celula, c.Id_Lider,
                    l.Nombre AS Lider_Nombre, l.Apellido AS Lider_Apellido,
                    l.Id_Ministerio, m.Nombre_Ministerio
                FROM celula c
                LEFT JOIN persona l ON l.Id_Persona = c.Id_Lider
                LEFT JOIN ministerio m ON m.Id_Ministerio = l.Id_Ministerio
                WHERE (c.Estado_Celula = 'Activa' OR c.Estado_Celula IS NULL)";
        $params = [];
        if ($idMin > 0) { $sql .= " AND l.Id_Ministerio = ?"; $params[] = $idMin; }
        if ($q !== '') { $sql .= " AND (c.Nombre_Celula LIKE ? OR l.Nombre LIKE ? OR l.Apellido LIKE ?)"; $like='%'.$q.'%'; $params[]=$like;$params[]=$like;$params[]=$like; }
        $sql .= " ORDER BY c.Nombre_Celula LIMIT 60";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $r) {
            $idL = (int)($r['Id_Lider'] ?? 0);
            $idM = (int)($r['Id_Ministerio'] ?? 0);
            $ln  = trim(($r['Lider_Nombre'] ?? '').' '.($r['Lider_Apellido'] ?? ''));
            $mn  = (string)($r['Nombre_Ministerio'] ?? '');
            $result[] = ['id_celula' => (int)$r['Id_Celula'], 'nombre_celula' => (string)($r['Nombre_Celula'] ?? ''), 'id_lider' => $idL, 'lider_nombre' => $ln, 'id_ministerio' => $idM, 'ministerio_nombre' => $mn, 'puede_asignar' => $idL > 0 && $idM > 0];
        }
        jsonResponse(['ok' => true, 'celulas' => $result]);
    } catch (Throwable $e) { jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500); }
}

if ($action === 'datos_celula') {
    try {
        $idCelula = (int)($_GET['id_celula'] ?? 0);
        if ($idCelula <= 0) jsonResponse(['ok' => false, 'error' => 'ID invalido.'], 400);
        $c = getCelulaData($pdo, $idCelula);
        if (!$c) jsonResponse(['ok' => false, 'error' => 'No existe.'], 404);
        $idL = (int)($c['Id_Lider'] ?? 0); $idM = (int)($c['Id_Ministerio'] ?? 0);
        $ln  = trim(($c['Lider_Nombre'] ?? '').' '.($c['Lider_Apellido'] ?? ''));
        jsonResponse(['ok' => true, 'celula' => ['id_celula' => (int)$c['Id_Celula'], 'nombre_celula' => (string)($c['Nombre_Celula'] ?? ''), 'id_lider' => $idL, 'lider_nombre' => $ln, 'id_ministerio' => $idM, 'ministerio_nombre' => (string)($c['Nombre_Ministerio'] ?? ''), 'puede_asignar' => $idL > 0 && $idM > 0]]);
    } catch (Throwable $e) { jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500); }
}

$mensaje = ''; $tipoMensaje = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre    = trim((string)($_POST['nombre'] ?? ''));
        $apellido  = trim((string)($_POST['apellido'] ?? ''));
        $tipoDoc   = trim((string)($_POST['tipo_documento'] ?? 'Cedula de Ciudadania'));
        $numDoc    = trim((string)($_POST['numero_documento'] ?? ''));
        $tel       = trim((string)($_POST['telefono'] ?? ''));
        $idCelula  = (int)($_POST['id_celula'] ?? 0);
        if ($nombre === '' || $apellido === '') throw new RuntimeException('Nombre y apellido son obligatorios.');
        if ($numDoc === '' && normalizeDigits($tel) === '') throw new RuntimeException('Debe indicar documento o teléfono.');
        if ($idCelula <= 0) throw new RuntimeException('Debe seleccionar una célula válida.');
        $celula = getCelulaData($pdo, $idCelula);
        if (!$celula) throw new RuntimeException('La célula seleccionada no existe.');
        $idLider = (int)($celula['Id_Lider'] ?? 0);
        $idMin   = (int)($celula['Id_Ministerio'] ?? 0);
        if ($idLider <= 0 || $idMin <= 0) throw new RuntimeException('Esa célula no tiene líder o ministerio configurado.');
        $existing = findPersona($pdo, $numDoc, $tel);
        $pdo->beginTransaction();
        if ($existing) {
            $idPer = (int)$existing['Id_Persona'];
            $cl    = normalizeChecklist((string)($existing['Escalera_Checklist'] ?? ''));
            $clJ   = json_encode($cl, JSON_UNESCAPED_UNICODE);
            $stmt  = $pdo->prepare("UPDATE persona SET Nombre=?,Apellido=?,Tipo_Documento=?,Numero_Documento=?,Telefono=?,Id_Celula=?,Id_Lider=?,Id_Ministerio=?,Tipo_Reunion='Migrados',Proceso='Ganar',Estado_Cuenta='Activo',Fecha_Asignacion_Lider=NOW(),Escalera_Checklist=? WHERE Id_Persona=?");
            $stmt->execute([$nombre,$apellido,$tipoDoc,$numDoc!==''?$numDoc:null,$tel!==''?$tel:null,$idCelula,$idLider,$idMin,$clJ,$idPer]);
            $pdo->commit();
            $mensaje = 'Persona actualizada con célula, líder, ministerio y Ganado en = Migrados.';
        } else {
            $cl  = normalizeChecklist('');
            $clJ = json_encode($cl, JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("INSERT INTO persona (Nombre,Apellido,Tipo_Documento,Numero_Documento,Telefono,Estado_Cuenta,Tipo_Reunion,Proceso,Escalera_Checklist,Id_Celula,Id_Lider,Id_Ministerio,Fecha_Asignacion_Lider,Fecha_Registro,Fecha_Registro_Unix) VALUES (?,?,?,?,?,'Activo','Migrados','Ganar',?,?,?,?,NOW(),NOW(),UNIX_TIMESTAMP(NOW()))");
            $stmt->execute([$nombre,$apellido,$tipoDoc,$numDoc!==''?$numDoc:null,$tel!==''?$tel:null,$clJ,$idCelula,$idLider,$idMin]);
            $pdo->commit();
            $mensaje = 'Persona creada con célula, líder, ministerio y Ganado en = Migrados.';
        }
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
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
    <title>Reparación Rápida – Migrados</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; max-width: 1000px; }
        h2 { margin-top: 0; font-size: 18px; }
        .sec { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #888; margin: 18px 0 8px; border-bottom: 1px solid #eee; padding-bottom: 4px; }
        .grid3 { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
        .field { display: flex; flex-direction: column; }
        label { font-size: 12px; margin-bottom: 3px; color: #444; font-weight: 600; }
        input, select { padding: 8px 10px; border: 1px solid #bbb; border-radius: 4px; font-size: 13px; width: 100%; }
        input:focus, select:focus { outline: none; border-color: #1f6feb; box-shadow: 0 0 0 2px rgba(31,111,235,.15); }
        .readonly { background: #f2f2f2; color: #555; }
        .btn-row { margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        button { border: 0; padding: 9px 16px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .btn-primary   { background: #1f6feb; color: #fff; }
        .btn-secondary { background: #6e7681; color: #fff; }
        .btn-search    { background: #0d6e70; color: #fff; }
        button:disabled { opacity: .45; cursor: default; }
        .msg { margin: 12px 0; padding: 10px 14px; border-radius: 4px; font-size: 13px; }
        .msg.ok  { background: #e6f4ea; color: #1a5e2a; border: 1px solid #b7dfbe; }
        .msg.err { background: #fdecea; color: #9e2a1c; border: 1px solid #f5bcbc; }
        .hint { font-size: 11px; color: #555; margin-top: 4px; }
        .hint.warn { color: #b45309; }
        .ac-wrap { position: relative; }
        .ac-drop { display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #bbb; border-top:none; border-radius:0 0 5px 5px; max-height:220px; overflow-y:auto; z-index:9999; box-shadow:0 6px 14px rgba(0,0,0,.14); }
        .ac-item { padding:9px 12px; cursor:pointer; font-size:13px; border-bottom:1px solid #f0f0f0; }
        .ac-item:hover { background:#eef4ff; }
        .ac-empty { padding:10px 12px; color:#aaa; font-size:13px; }
        .badge { display:inline-block; border-radius:12px; padding:4px 12px; font-size:12px; font-weight:700; }
        .badge-m { background:#1f6feb; color:#fff; }
        .badge-g { background:#2ea043; color:#fff; }
        @media(max-width:700px){ .grid3{grid-template-columns:1fr;} }
    </style>
</head>
<body>
<div class="card">
<h2>Reparación Rápida de Personas — Migrados</h2>

<?php if ($mensaje !== ''): ?>
<div class="msg <?= htmlspecialchars($tipoMensaje,ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars($mensaje,ENT_QUOTES,'UTF-8') ?></div>
<?php endif; ?>

<div class="sec">1. Buscar persona existente (opcional)</div>
<div class="grid3">
    <div class="field">
        <label for="bd">Documento</label>
        <input type="text" id="bd" placeholder="Ej: 1073155101">
    </div>
    <div class="field">
        <label for="bt">Teléfono</label>
        <input type="text" id="bt" placeholder="Ej: 3140000000">
    </div>
    <div class="field" style="justify-content:flex-end">
        <label>&nbsp;</label>
        <button type="button" class="btn-search" id="btn_buscar">Buscar persona</button>
    </div>
</div>

<form method="post" id="frm" autocomplete="off">

<div class="sec">2. Datos personales</div>
<div class="grid3">
    <div class="field">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required>
    </div>
    <div class="field">
        <label for="apellido">Apellido *</label>
        <input type="text" id="apellido" name="apellido" required>
    </div>
    <div class="field">
        <label for="tipo_documento">Tipo documento</label>
        <select id="tipo_documento" name="tipo_documento">
            <option value="Cedula de Ciudadania">Cédula de Ciudadanía</option>
            <option value="Cedula Extranjera">Cédula Extranjera</option>
            <option value="Registro Civil">Registro Civil</option>
        </select>
    </div>
    <div class="field">
        <label for="numero_documento">Número documento</label>
        <input type="text" id="numero_documento" name="numero_documento">
    </div>
    <div class="field">
        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono">
    </div>
</div>

<div class="sec">3. Asignación</div>
<div class="grid3">

    <div class="field">
        <label>Ministerio *</label>
        <div class="ac-wrap">
            <input type="text" id="min_search" placeholder="Escribe para buscar ministerio..." autocomplete="off">
            <input type="hidden" id="id_ministerio" name="id_ministerio">
            <div id="min_drop" class="ac-drop"></div>
        </div>
        <div class="hint" id="min_hint"></div>
    </div>

    <div class="field">
        <label>Célula *</label>
        <div class="ac-wrap">
            <input type="text" id="cel_search" placeholder="Primero selecciona el ministerio..." autocomplete="off">
            <input type="hidden" id="id_celula" name="id_celula">
            <div id="cel_drop" class="ac-drop"></div>
        </div>
        <div class="hint" id="cel_hint"></div>
    </div>

    <div class="field">
        <label>Líder (automático)</label>
        <input type="text" id="lider_view" class="readonly" readonly placeholder="Se llena al elegir la célula">
    </div>

</div>

<div class="sec">4. Fijo</div>
<div class="grid3">
    <div class="field">
        <label>Ganado en</label>
        <div style="margin-top:6px"><span class="badge badge-m">Migrados</span></div>
    </div>
    <div class="field">
        <label>Proceso</label>
        <div style="margin-top:6px"><span class="badge badge-g">Ganar</span></div>
    </div>
</div>

<div class="btn-row">
    <button type="submit" class="btn-primary" id="btn_guardar" disabled>Guardar reparación</button>
    <button type="button" class="btn-secondary" id="btn_limpiar">Limpiar</button>
</div>

</form>
</div>

<script>
(function(){
'use strict';

var minSearchEl = document.getElementById('min_search');
var minHiddenEl = document.getElementById('id_ministerio');
var minDropEl   = document.getElementById('min_drop');
var minHintEl   = document.getElementById('min_hint');

var celSearchEl = document.getElementById('cel_search');
var celHiddenEl = document.getElementById('id_celula');
var celDropEl   = document.getElementById('cel_drop');
var celHintEl   = document.getElementById('cel_hint');

var liderViewEl = document.getElementById('lider_view');
var btnGuardar  = document.getElementById('btn_guardar');

var bdEl = document.getElementById('bd');
var btEl = document.getElementById('bt');
var btnBuscar  = document.getElementById('btn_buscar');
var btnLimpiar = document.getElementById('btn_limpiar');

var nombreEl   = document.getElementById('nombre');
var apellidoEl = document.getElementById('apellido');
var tipoDocEl  = document.getElementById('tipo_documento');
var numDocEl   = document.getElementById('numero_documento');
var telEl      = document.getElementById('telefono');

var selMin = null;
var selCel = null;
var tMin = null, tCel = null;

function esc(s){ var d=document.createElement('span'); d.textContent=s||''; return d.innerHTML; }

function updateBtn(){
    btnGuardar.disabled = !(selCel && selCel.puede_asignar);
}

// ── Dropdown helper ──
function buildDropdown(dropEl, items, onPickFn) {
    dropEl.innerHTML = '';
    if (!items.length) {
        dropEl.innerHTML = '<div class="ac-empty">Sin resultados</div>';
    } else {
        items.forEach(function(item){
            var d = document.createElement('div');
            d.className = 'ac-item';
            d.innerHTML = item._html;
            d.addEventListener('mousedown', function(e){
                e.preventDefault();
                onPickFn(item);
                dropEl.style.display = 'none';
            });
            dropEl.appendChild(d);
        });
    }
    dropEl.style.display = 'block';
}

function hideAll(){ minDropEl.style.display='none'; celDropEl.style.display='none'; }
document.addEventListener('click', function(e){
    if(!minSearchEl.contains(e.target) && !minDropEl.contains(e.target)) minDropEl.style.display='none';
    if(!celSearchEl.contains(e.target) && !celDropEl.contains(e.target)) celDropEl.style.display='none';
});

// ── Ministerio ──
function clearMin(){
    selMin = null; minHiddenEl.value=''; minHintEl.textContent='';
    clearCel();
    celSearchEl.placeholder = 'Primero selecciona el ministerio...';
    celSearchEl.disabled = true;
}

function pickMin(item){
    selMin = item;
    minSearchEl.value  = item.nombre_ministerio;
    minHiddenEl.value  = item.id_ministerio;
    minHintEl.textContent = '✔ Ministerio seleccionado';
    minHintEl.className = 'hint';
    clearCel();
    celSearchEl.disabled = false;
    celSearchEl.placeholder = 'Escribe para buscar célula...';
    fetchCelulas('', item.id_ministerio);
}

async function fetchMins(q){
    try{
        var url='?action=buscar_ministerios'+(q?'&q='+encodeURIComponent(q):'');
        var data=await(await fetch(url)).json();
        if(!data.ok) return;
        var items=(data.ministerios||[]).map(function(m){
            return Object.assign({},m,{_html:'<strong>'+esc(m.nombre_ministerio)+'</strong>'});
        });
        buildDropdown(minDropEl, items, pickMin);
    }catch(e){ minDropEl.style.display='none'; }
}

minSearchEl.addEventListener('focus', function(){ fetchMins(minSearchEl.value.trim()); });
minSearchEl.addEventListener('input', function(){
    clearMin();
    clearTimeout(tMin);
    var q = minSearchEl.value.trim();
    tMin = setTimeout(function(){ fetchMins(q); }, 220);
});
minSearchEl.addEventListener('blur', function(){ setTimeout(function(){ minDropEl.style.display='none'; },160); });

// ── Célula ──
function clearCel(){
    selCel = null; celHiddenEl.value=''; celHintEl.textContent='';
    liderViewEl.value=''; updateBtn();
}

function pickCel(item){
    selCel = item;
    celHiddenEl.value = item.id_celula;
    celSearchEl.value = item.nombre_celula;
    liderViewEl.value = item.lider_nombre || '';
    if(item.puede_asignar){
        celHintEl.textContent = '✔ Líder: '+(item.lider_nombre||'?');
        celHintEl.className = 'hint';
    } else {
        celHintEl.textContent = '⚠ Esta célula no tiene líder configurado.';
        celHintEl.className = 'hint warn';
    }
    updateBtn();
}

async function fetchCelulas(q, idMin){
    try{
        var url='?action=buscar_celulas';
        if(idMin) url+='&id_ministerio='+encodeURIComponent(idMin);
        if(q)     url+='&q='+encodeURIComponent(q);
        var data=await(await fetch(url)).json();
        if(!data.ok) return;
        var items=(data.celulas||[]).map(function(c){
            return Object.assign({},c,{
                _html:'<strong>'+esc(c.nombre_celula)+'</strong>'
                    +' <span style="color:#555">— '+esc(c.lider_nombre||'Sin líder')+'</span>'
            });
        });
        buildDropdown(celDropEl, items, pickCel);
    }catch(e){ celDropEl.style.display='none'; }
}

celSearchEl.disabled = true;
celSearchEl.addEventListener('focus', function(){
    if(!selMin) return;
    fetchCelulas(celSearchEl.value.trim(), selMin.id_ministerio);
});
celSearchEl.addEventListener('input', function(){
    clearCel();
    if(!selMin) return;
    clearTimeout(tCel);
    var q=celSearchEl.value.trim();
    tCel=setTimeout(function(){ fetchCelulas(q, selMin.id_ministerio); },220);
});
celSearchEl.addEventListener('blur', function(){ setTimeout(function(){ celDropEl.style.display='none'; },160); });

// ── Buscar persona ──
async function preloadCelula(idCelula){
    if(!idCelula) return;
    try{
        var data=await(await fetch('?action=datos_celula&id_celula='+encodeURIComponent(idCelula))).json();
        if(!data.ok||!data.celula) return;
        var c=data.celula;
        // Set ministerio
        selMin={ id_ministerio:c.id_ministerio, nombre_ministerio:c.ministerio_nombre };
        minSearchEl.value = c.ministerio_nombre||'';
        minHiddenEl.value = c.id_ministerio||'';
        minHintEl.textContent = c.ministerio_nombre ? '✔ '+c.ministerio_nombre : '';
        celSearchEl.disabled = false;
        celSearchEl.placeholder = 'Escribe para buscar célula...';
        // Set celula
        pickCel(Object.assign({},c,{puede_asignar: c.id_lider>0 && c.id_ministerio>0}));
    }catch(e){}
}

btnBuscar.addEventListener('click', async function(){
    var doc=bdEl.value.trim(), tel=btEl.value.trim();
    if(!doc&&!tel){ alert('Ingresa documento o teléfono.'); return; }
    try{
        var url='?action=buscar_persona&documento='+encodeURIComponent(doc)+'&telefono='+encodeURIComponent(tel);
        var data=await(await fetch(url)).json();
        if(!data.ok){ alert(data.error||'Error al buscar.'); return; }
        if(!data.found){ alert('No encontrada en BD. Completa y guarda para crearla.'); numDocEl.value=doc; telEl.value=tel; return; }
        var p=data.persona;
        nombreEl.value=p.nombre||''; apellidoEl.value=p.apellido||'';
        tipoDocEl.value=p.tipo_documento||'Cedula de Ciudadania';
        numDocEl.value=p.numero_documento||doc; telEl.value=p.telefono||tel;
        if(p.id_celula) await preloadCelula(p.id_celula);
        alert('Persona encontrada. Revisa y guarda para reparar.');
    }catch(e){ alert('Error de red.'); }
});

btnLimpiar.addEventListener('click', function(){
    document.getElementById('frm').reset();
    bdEl.value=''; btEl.value='';
    minSearchEl.value=''; celSearchEl.value='';
    liderViewEl.value='';
    clearMin(); updateBtn();
});

})();
</script>
</body>
</html>
