<?php include VIEWS . '/layout/header.php'; ?>
<?php
$programa   = (string)($programa   ?? 'universidad_vida');
$titulo     = (string)($titulo     ?? 'Inscritos Universidad de la Vida');
$publicUrl  = rtrim((string)($public_url ?? PUBLIC_URL), '/');
$urlPagosEscuelaUv = $publicUrl . '/?url=escuelas_formacion/pagos/consolidar';
$returnUrlInscritos = '?url=escuelas_formacion/inscritos';

// Permisos: si la vista se incluye desde programas/asistencias puede no venir del controlador.
$esAdminSesion = class_exists('AuthController') && AuthController::esAdministrador();
$puedeAccesoListado = $esAdminSesion
    || (class_exists('AuthController') && (
        AuthController::puede('asistencias:ver')
        || AuthController::tieneCoordinacionTotalProgramas()
    ));

if (!isset($puede_editar)) {
    $puede_editar = $puedeAccesoListado;
}
if (!isset($puede_eliminar)) {
    $puede_eliminar = $puedeAccesoListado;
}
if (!isset($puede_editar_persona)) {
    $puede_editar_persona = $esAdminSesion
        || (class_exists('AuthController') && AuthController::puede('personas:editar'));
}

// Administrador: siempre puede editar y eliminar en esta tabla.
if ($esAdminSesion) {
    $puede_editar = true;
    $puede_eliminar = true;
    $puede_editar_persona = true;
}

$puedeEditar        = !empty($puede_editar);
$puedeEditarPersona = !empty($puede_editar_persona);
$puedeEliminar      = !empty($puede_eliminar);
?>

<style>
/* ── Contenedor principal ───────────────────────────────────────── */
.li-shell { display:flex; flex-direction:column; gap:16px; padding:0 4px; }
.li-head  { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.li-head h2 { margin:0; font-size:1.15rem; color:#1e3a5f; font-weight:800; }

/* ── Barra de herramientas ──────────────────────────────────────── */
.li-toolbar { display:flex; gap:8px; flex-wrap:wrap; align-items:flex-end; }
.li-toolbar label { font-size:0.76rem; color:#475569; font-weight:600; text-transform:uppercase; letter-spacing:.04em; display:block; margin-bottom:4px; }
.li-toolbar input[type=search],
.li-toolbar select {
  padding:7px 10px; border:1px solid #c5d5e8; border-radius:8px;
  font-size:0.83rem; color:#1e293b; background:#fff;
  outline:none; transition:border-color .2s;
}
.li-toolbar input[type=search]:focus,
.li-toolbar select:focus { border-color:#3b82f6; }
.li-toolbar input[type=search] { min-width:220px; }

/* ── Resumen ────────────────────────────────────────────────────── */
.li-summary { display:flex; gap:10px; flex-wrap:wrap; }
.li-stat { background:#f1f7ff; border:1px solid #cfe0f5; border-radius:10px;
  padding:10px 16px; min-width:130px; flex:1 1 130px; }
.li-stat strong { display:block; font-size:1.3rem; font-weight:800; color:#1e3a5f; }
.li-stat span   { font-size:0.73rem; color:#4b6482; text-transform:uppercase; letter-spacing:.04em; }
.li-stat-sub    { display:block; margin-top:2px; font-size:0.68rem; color:#64748b; font-weight:600; text-transform:none; letter-spacing:0; }
.li-stat--filtro.is-active { background:#eef6ff; border-color:#93c5fd; }
.li-stat--filtro.is-active strong { color:#1d4ed8; }
.li-stat--ok  { background:#ecfdf5; border-color:#a7f3d0; }
.li-stat--ok strong { color:#166534; }
.li-stat--enc { background:#ecfdf5; border-color:#86efac; }
.li-stat--enc .li-encuentro-titulo {
  display:block; font-size:0.73rem; color:#166534; font-weight:700;
  text-transform:uppercase; letter-spacing:.04em; margin-bottom:8px;
}
.li-stat--enc .li-encuentro-asist {
  display:flex; flex-wrap:wrap; gap:8px 12px;
}
.li-stat--enc .li-enc-badge {
  display:inline-flex; align-items:center; gap:5px;
  padding:6px 12px; border-radius:8px; font-size:0.85rem; font-weight:800;
  background:#dcfce7; color:#166534; border:1px solid #86efac;
}
.li-stat--enc .li-enc-badge--d2 { background:#d1fae5; color:#047857; border-color:#6ee7b7; }
.li-stat--enc .li-enc-badge small { font-weight:700; opacity:.9; font-size:0.72rem; text-transform:uppercase; }
.li-resumen-filtro {
  margin:-6px 0 0; padding:8px 12px; font-size:0.78rem; color:#1e40af;
  background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px;
}

/* ── Tabla ──────────────────────────────────────────────────────── */
.li-card { background:#fff; border:1px solid #dbe7f3; border-radius:12px;
  box-shadow:0 1px 4px rgba(15,23,42,.08); overflow:hidden; }
.li-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
.li-table { width:100%; border-collapse:collapse; white-space:nowrap; }
.li-table th {
  background:#f8fafc; color:#475569; font-size:0.68rem;
  text-transform:uppercase; letter-spacing:.04em; padding:9px 8px;
  border-bottom:2px solid #dbe7f3; text-align:center; position:sticky; top:0; z-index:2;
}
.li-table th.t-left { text-align:left; }
.li-table td {
  padding:7px 8px; font-size:0.80rem; color:#1e293b;
  border-bottom:1px solid #eef2f7; text-align:center; vertical-align:middle;
}
.li-table td.t-left { text-align:left; }
.li-table tbody tr:hover { background:#f7faff; }

/* Vista compacta al ocultar columnas: menos scroll, celdas más estrechas */
.li-table-wrap--compact { overflow-x: visible; }
.li-table.li-table--compact {
  white-space: normal;
  width: 100%;
  max-width: 100%;
  table-layout: fixed;
}
.li-table.li-table--compact th,
.li-table.li-table--compact td {
  padding: 5px 6px;
  font-size: 0.74rem;
  line-height: 1.25;
}
.li-table.li-table--compact th.col-nombre,
.li-table.li-table--compact td.col-nombre {
  min-width: 0 !important;
  width: 18%;
  white-space: normal;
  word-break: break-word;
}
.li-table.li-table--compact th.col-lider,
.li-table.li-table--compact td.col-lider {
  min-width: 0 !important;
  width: 14%;
  white-space: normal;
  word-break: break-word;
}
.li-table.li-table--compact th.col-cedula,
.li-table.li-table--compact td.col-cedula { width: 9%; }
.li-table.li-table--compact th.col-telefono,
.li-table.li-table--compact td.col-telefono { width: 8%; }
.li-table.li-table--compact th.col-genero,
.li-table.li-table--compact td.col-genero,
.li-table.li-table--compact th.col-edad,
.li-table.li-table--compact td.col-edad { width: 4%; }
.li-table.li-table--compact th.col-pago,
.li-table.li-table--compact td.col-pago { width: 10%; }
.li-table.li-table--compact th.col-acciones,
.li-table.li-table--compact td.col-acciones { width: 56px; }
.li-table.li-table--compact th[class*="col-pre-"],
.li-table.li-table--compact th[class*="col-enc-"],
.li-table.li-table--compact th[class*="col-post-"],
.li-table.li-table--compact td[class*="col-pre-"],
.li-table.li-table--compact td[class*="col-enc-"],
.li-table.li-table--compact td[class*="col-post-"] {
  width: 36px;
  min-width: 36px;
  max-width: 40px;
  padding: 4px 2px;
}
.li-table.li-table--compact .check-asist {
  width: 17px;
  height: 17px;
}
.li-table.li-table--compact .btn-pago {
  padding: 3px 8px;
  font-size: 0.68rem;
}
.li-table.li-table--compact .pago-status { font-size: 0.62rem; padding: 1px 5px; }
.li-table.li-table--compact .li-acc-btn { width: 22px; height: 22px; }
.li-table.li-table--compact .li-acc-btn i { font-size: 11px; }
/* Pocas columnas visibles: aún más densidad */
.li-table.li-table--compact[data-visibles="10"] th,
.li-table.li-table--compact[data-visibles="10"] td,
.li-table.li-table--compact[data-visibles="9"] th,
.li-table.li-table--compact[data-visibles="9"] td,
.li-table.li-table--compact[data-visibles="8"] th,
.li-table.li-table--compact[data-visibles="8"] td {
  padding: 4px 5px;
  font-size: 0.72rem;
}
.li-table.li-table--compact[data-visibles="7"] th,
.li-table.li-table--compact[data-visibles="7"] td,
.li-table.li-table--compact[data-visibles="6"] th,
.li-table.li-table--compact[data-visibles="6"] td,
.li-table.li-table--compact[data-visibles="5"] th,
.li-table.li-table--compact[data-visibles="5"] td,
.li-table.li-table--compact[data-visibles="4"] th,
.li-table.li-table--compact[data-visibles="4"] td {
  padding: 3px 4px;
  font-size: 0.70rem;
}
.li-table.li-table--compact[data-visibles="7"] th.col-nombre,
.li-table.li-table--compact[data-visibles="7"] td.col-nombre,
.li-table.li-table--compact[data-visibles="6"] th.col-nombre,
.li-table.li-table--compact[data-visibles="6"] td.col-nombre {
  width: 22%;
}

/* ── Grupos de asistencia (encabezados de sección) ──────────────── */
.th-group { background:#eef4fc !important; color:#1e40af !important; font-size:0.70rem !important; border-bottom:1px solid #bfdbfe !important; }
.th-pre   { background:#fef3c7 !important; color:#92400e !important; }
.th-enc   { background:#dcfce7 !important; color:#166534 !important; }
.th-post  { background:#f3e8ff !important; color:#7e22ce !important; }

/* ── Checkboxes de asistencia ───────────────────────────────────── */
.check-asist {
  width:20px; height:20px; cursor:pointer; accent-color:#3b82f6;
  border-radius:4px; transition:opacity .15s;
}
.check-asist:disabled { opacity:.4; cursor:not-allowed; }

/* ── Botón pago ─────────────────────────────────────────────────── */
.btn-pago {
  display:inline-flex; align-items:center; gap:5px;
  padding:5px 11px; border-radius:7px; font-size:0.76rem; font-weight:700;
  background:#2563eb; color:#fff; border:none; cursor:pointer;
  text-decoration:none; white-space:nowrap; transition:background .18s;
}
.btn-pago:hover { background:#1d4ed8; color:#fff; }

.pago-cell {
  display:flex;
  align-items:center;
  justify-content:center;
  gap:6px;
}

.btn-pago-icon {
  width:24px;
  height:24px;
  border-radius:999px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  background:#2563eb;
  color:#fff;
  text-decoration:none;
  border:1px solid #1d4ed8;
  font-size:12px;
  line-height:1;
  font-weight:700;
  box-shadow:0 1px 2px rgba(15,23,42,.12);
}

.btn-pago-icon:hover {
  background:#1d4ed8;
  color:#fff;
}

.pago-status {
  display:inline-flex;
  align-items:center;
  border-radius:999px;
  padding:2px 7px;
  font-size:0.68rem;
  font-weight:700;
  border:1px solid #dbe7f3;
  color:#1e3a5f;
  background:#f8fbff;
}

.pago-status.ok {
  background:#e8f7ee;
  border-color:#bfe7cc;
  color:#166534;
}

/* ── Acciones (editar / eliminar) ───────────────────────────────── */
.li-acciones {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 4px;
  white-space: nowrap;
}
.li-acc-btn {
  width: 26px;
  height: 26px;
  padding: 0;
  border-radius: 6px;
  border: 1px solid #c5d5e8;
  background: #fff;
  color: #475569;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  text-decoration: none;
  line-height: 1;
  transition: background .15s, border-color .15s, color .15s;
}
.li-acc-btn i { font-size: 13px; line-height: 1; }
.li-acc-btn:hover { background: #f1f7ff; border-color: #93c5fd; color: #1e40af; }
.li-acc-btn--edit:hover { color: #1d4ed8; }
.li-acc-btn--del { border-color: #fecaca; color: #b91c1c; }
.li-acc-btn--del:hover { background: #fef2f2; border-color: #f87171; color: #991b1b; }
.li-acc-btn:disabled { opacity: .45; cursor: not-allowed; pointer-events: none; }

/* ── Estado de carga ────────────────────────────────────────────── */
.li-loading { padding:40px; text-align:center; color:#64748b; font-size:0.88rem; }
.li-empty   { padding:32px; text-align:center; color:#94a3b8; font-size:0.85rem; }

/* ── Indicador de guardado ──────────────────────────────────────── */
.li-save-indicator {
  position:fixed; bottom:20px; right:20px; z-index:9999;
  padding:8px 14px; border-radius:9px; font-size:0.80rem; font-weight:700;
  background:#166534; color:#fff; box-shadow:0 4px 16px rgba(0,0,0,.18);
  opacity:0; transition:opacity .25s; pointer-events:none;
}
.li-save-indicator.show { opacity:1; }
.li-save-indicator.error { background:#991b1b; }

/* Columnas ocultas (data-oculta = ids separados por espacio) */
.li-table[data-oculta~="nombre"] .col-nombre,
.li-table[data-oculta~="genero"] .col-genero,
.li-table[data-oculta~="edad"] .col-edad,
.li-table[data-oculta~="cedula"] .col-cedula,
.li-table[data-oculta~="telefono"] .col-telefono,
.li-table[data-oculta~="lider"] .col-lider,
.li-table[data-oculta~="pago"] .col-pago,
.li-table[data-oculta~="acciones"] .col-acciones,
.li-table[data-oculta~="pre-1"] .col-pre-1,
.li-table[data-oculta~="pre-2"] .col-pre-2,
.li-table[data-oculta~="pre-3"] .col-pre-3,
.li-table[data-oculta~="pre-4"] .col-pre-4,
.li-table[data-oculta~="enc-1"] .col-enc-1,
.li-table[data-oculta~="enc-2"] .col-enc-2,
.li-table[data-oculta~="post-1"] .col-post-1,
.li-table[data-oculta~="post-2"] .col-post-2,
.li-table[data-oculta~="post-3"] .col-post-3,
.li-table[data-oculta~="post-4"] .col-post-4 { display: none !important; }

.li-col-picker { position: relative; align-self: flex-end; }
.li-col-picker__btn { min-width: 140px; }
.li-col-picker__panel {
  display: none; position: absolute; right: 0; top: calc(100% + 6px); z-index: 50;
  min-width: 280px; max-width: 320px; max-height: 70vh; overflow: auto;
  background: #fff; border: 1px solid #c5d5e8; border-radius: 10px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14); padding: 10px 12px;
}
.li-col-picker.is-open .li-col-picker__panel { display: block; }
.li-col-picker__head {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0;
}
.li-col-picker__head strong { font-size: 0.82rem; color: #1e3a5f; }
.li-col-picker__actions { display: flex; gap: 6px; flex-wrap: wrap; }
.li-col-picker__actions button {
  border: none; background: #eff6ff; color: #1d4ed8; font-size: 0.72rem;
  font-weight: 700; padding: 4px 8px; border-radius: 6px; cursor: pointer;
}
.li-col-picker__actions button:hover { background: #dbeafe; }
.li-col-picker__grupo {
  font-size: 0.68rem; font-weight: 800; color: #64748b; text-transform: uppercase;
  letter-spacing: 0.05em; margin: 10px 0 6px;
}
.li-col-picker__grupo:first-of-type { margin-top: 0; }
.li-col-picker__item {
  display: flex; align-items: center; gap: 8px; padding: 5px 4px;
  font-size: 0.82rem; color: #1e293b; cursor: pointer; border-radius: 6px;
}
.li-col-picker__item:hover { background: #f8fafc; }
.li-col-picker__item input { width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; }
.li-toolbar #li-lider { min-width: 200px; max-width: 280px; }
</style>

<div class="li-shell">

  <!-- Encabezado -->
  <div class="li-head">
    <h2>📋 <?= htmlspecialchars($titulo) ?></h2>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <?php if ($programa === 'universidad_vida'): ?>
        <a href="<?= htmlspecialchars($urlPagosEscuelaUv, ENT_QUOTES, 'UTF-8') ?>" class="btn-pago" style="text-decoration:none;background:#0f766e;">Ir a pagos</a>
      <?php endif; ?>
      <div id="li-estado" style="font-size:0.78rem;color:#64748b;">Cargando…</div>
    </div>
  </div>

  <!-- Barra de búsqueda y filtros -->
  <div class="li-toolbar">
    <div>
      <label for="li-buscar">Búsqueda universal</label>
      <input type="search" id="li-buscar" placeholder="Nombre, cédula o teléfono…" autocomplete="off">
    </div>
    <div>
      <label for="li-genero">Segmento / Género</label>
      <select id="li-genero">
        <option value="todos">Todos</option>
        <option value="hombre">Hombres</option>
        <option value="mujer">Mujeres</option>
        <option value="joven">Jóvenes</option>
      </select>
    </div>
    <div>
      <label for="li-pago">Pago / Abono</label>
      <select id="li-pago">
        <option value="todos">Todos</option>
        <option value="pagados">Pagados</option>
        <option value="sin_pago">Sin pago</option>
      </select>
    </div>
    <div>
      <label for="li-lider">Líder</label>
      <select id="li-lider">
        <option value="">Todos los líderes</option>
      </select>
    </div>
    <div style="align-self:flex-end;">
      <button type="button" class="btn-pago" style="background:#475569;" onclick="cargarDatos()">↻ Actualizar</button>
    </div>
    <div class="li-col-picker" id="li-col-picker">
      <label class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;" for="li-col-picker-btn">Columnas visibles</label>
      <button type="button" class="btn-pago li-col-picker__btn" id="li-col-picker-btn" style="background:#7c3aed;" aria-expanded="false" aria-haspopup="true">
        ⊞ Columnas
      </button>
      <div class="li-col-picker__panel" id="li-col-picker-panel" role="dialog" aria-label="Mostrar u ocultar columnas">
        <div class="li-col-picker__head">
          <strong>Columnas visibles</strong>
          <div class="li-col-picker__actions">
            <button type="button" id="li-col-ver-todas">Ver todas</button>
            <button type="button" id="li-col-solo-encuentro">Solo encuentro</button>
          </div>
        </div>
        <div id="li-col-checkboxes"></div>
      </div>
    </div>
  </div>

  <!-- Resumen numérico -->
  <div class="li-summary">
    <div class="li-stat">
      <strong id="li-total-personas">–</strong>
      <span>Total inscritos</span>
    </div>
    <div class="li-stat li-stat--filtro" id="li-stat-visible">
      <strong id="li-total-visibles">–</strong>
      <span id="li-label-visibles">Personas mostradas</span>
      <small id="li-sub-visibles" class="li-stat-sub" hidden></small>
    </div>
    <div class="li-stat li-stat--ok">
      <strong id="li-total-pagados">–</strong>
      <span id="li-label-pagados">Con pago</span>
      <small id="li-sub-pagados" class="li-stat-sub" hidden></small>
    </div>
    <div class="li-stat li-stat--enc" style="min-width:180px;">
      <div class="li-encuentro-asist" id="li-encuentro-asist">
        <span class="li-enc-badge" title="Asistieron al día 1 del encuentro">
          <small>Día 1</small> <span id="li-enc-d1">–</span>
        </span>
        <span class="li-enc-badge li-enc-badge--d2" title="Asistieron al día 2 del encuentro">
          <small>Día 2</small> <span id="li-enc-d2">–</span>
        </span>
      </div>
    </div>
  </div>
  <p id="li-resumen-filtro" class="li-resumen-filtro" hidden></p>

  <!-- Tabla -->
  <div class="li-card">
    <div class="li-table-wrap">
      <table class="li-table" id="li-table">
        <thead>
          <tr>
            <!-- Datos básicos -->
            <th class="t-left col-nombre" rowspan="2" style="min-width:160px;">Nombre</th>
            <th class="col-genero" rowspan="2">Género</th>
            <th class="col-edad" rowspan="2">Edad</th>
            <th class="col-cedula" rowspan="2">Cédula</th>
            <th class="col-telefono" rowspan="2">Teléfono</th>
            <th class="t-left col-lider" rowspan="2" style="min-width:120px;">Líder</th>
            <th class="col-pago" rowspan="2">Pago / Abono</th>
            <th class="col-acciones" rowspan="2" style="min-width:64px;">Acciones</th>
            <th colspan="4" class="th-pre col-pre-group col-pre-1 col-pre-2 col-pre-3 col-pre-4" data-col-group="pre">Clases Pre-Encuentro</th>
            <th colspan="2" class="th-enc col-enc-group col-enc-1 col-enc-2" data-col-group="enc">Encuentro</th>
            <th colspan="4" class="th-post col-post-group col-post-1 col-post-2 col-post-3 col-post-4" data-col-group="post">Clases Post-Encuentro</th>
          </tr>
          <tr>
            <th class="th-pre col-pre-1">C1</th>
            <th class="th-pre col-pre-2">C2</th>
            <th class="th-pre col-pre-3">C3</th>
            <th class="th-pre col-pre-4">C4</th>
            <th class="th-enc col-enc-1">Día 1</th>
            <th class="th-enc col-enc-2">Día 2</th>
            <th class="th-post col-post-1">C1</th>
            <th class="th-post col-post-2">C2</th>
            <th class="th-post col-post-3">C3</th>
            <th class="th-post col-post-4">C4</th>
          </tr>
        </thead>
        <tbody id="li-tbody">
          <tr><td colspan="18" class="li-loading">Cargando datos…</td></tr>
        </tbody>
        <tfoot id="li-tfoot"></tfoot>
      </table>
    </div>
  </div>

</div><!-- /.li-shell -->

<!-- Indicador de guardado -->
<div class="li-save-indicator" id="li-save-indicator">✓ Guardado</div>

<script>
(function () {
  'use strict';

  const PROGRAMA   = <?= json_encode($programa) ?>;
  const BASE_URL   = <?= json_encode($publicUrl . '/index.php?url=') ?>;
  const ABONO_URL    = BASE_URL + 'escuelas_formacion/inscritos/abono-admin';
  const ASIST_URL    = BASE_URL + 'escuelas_formacion/inscritos/guardar-asistencia';
  const ELIMINAR_URL = BASE_URL + 'escuelas_formacion/inscritos/eliminar';
  const DATOS_URL    = BASE_URL + 'escuelas_formacion/inscritos&ajax=1';
  const EDITAR_URL   = BASE_URL + 'personas/editar';
  const REGISTRO_UV_URL = BASE_URL + 'escuelas_formacion/registro-publico/universidad-vida';
  const RETURN_URL   = <?= json_encode($returnUrlInscritos) ?>;
  const PERMISOS     = {
    puedeEditar:        <?= $puedeEditar ? 'true' : 'false' ?>,
    puedeEditarPersona: <?= $puedeEditarPersona ? 'true' : 'false' ?>,
    puedeEliminar:      <?= $puedeEliminar ? 'true' : 'false' ?>
  };

  let todosLosDatos  = [];
  let guardandoCheck = false;
  let saveTimer      = null;

  // ── Carga de datos ──────────────────────────────────────────────
  async function cargarDatos() {
    setEstado('Cargando…');
    const buscar = document.getElementById('li-buscar').value.trim();
    const qs = buscar ? '&buscar=' + encodeURIComponent(buscar) : '';
    try {
      const resp = await fetch(DATOS_URL + qs + '&programa=' + encodeURIComponent(PROGRAMA), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await resp.json();
      if (!json.success) throw new Error('Respuesta errónea del servidor');
      todosLosDatos = json.datos || [];
      poblarFiltroLideres();
      renderTabla();
      if (json.limite && todosLosDatos.length >= json.limite) {
        setEstado(document.getElementById('li-estado').textContent + ' (límite ' + json.limite + ')');
      }
    } catch (e) {
      setEstado('Error al cargar datos');
      document.getElementById('li-tbody').innerHTML =
        '<tr><td colspan="18" class="li-empty">No se pudieron cargar los datos. Intenta de nuevo.</td></tr>';
    }
  }

  function tienePagoRegistrado(p) {
    const totalPagado = Number(p.total_pagado || 0);
    const registrosPago = Number(p.registros_pago || 0);
    return !!p.tiene_pago_registrado || totalPagado > 0 || registrosPago > 0;
  }

  function clasificarGeneroBase(g) {
    const genero = normalizar(g || '');
    if (!genero) return 'otro';
    const esMujer = genero.includes('mujer') || genero.includes('femen')
      || /(^|[^a-z])(f|fem|female)([^a-z]|$)/.test(genero);
    const esHombre = genero.includes('hombre') || genero.includes('mascul')
      || /(^|[^a-z])(m|masc|male|h)([^a-z]|$)/.test(genero);
    if (esHombre && !esMujer) return 'hombre';
    if (esMujer && !esHombre) return 'mujer';
    return 'otro';
  }

  function resolverSegmento(p) {
    const segPref = normalizar(p.Segmento_Preferido || '');
    if (['jovenes', 'teens', 'hombres_adultos', 'mujeres_adultas'].includes(segPref)) {
      return segPref;
    }

    const edad = Number(p.Edad || 0);
    const gc = clasificarGeneroBase(p.Genero);
    if (edad >= 14 && edad <= 28) return 'jovenes';
    if (edad >= 9 && edad <= 13) return 'teens';
    if ((edad >= 29 || edad <= 0) && gc === 'hombre') return 'hombres_adultos';
    if ((edad >= 29 || edad <= 0) && gc === 'mujer') return 'mujeres_adultas';

    const g = normalizar(p.Genero || '');
    if (g.includes('joven')) return 'jovenes';
    return 'otros';
  }

  function coincideFiltroSegmento(p, filtroGenero) {
    if (filtroGenero === 'todos' || filtroGenero === '') return true;
    if (filtroGenero === 'hombre') return clasificarGeneroBase(p.Genero) === 'hombre';
    if (filtroGenero === 'mujer') return clasificarGeneroBase(p.Genero) === 'mujer';
    if (filtroGenero === 'joven') {
      const edad = Number(p.Edad || 0);
      if (edad >= 9 && edad <= 13) return true;
      if (edad >= 14 && edad <= 28) return true;
      const segPref = normalizar(p.Segmento_Preferido || '');
      return segPref === 'jovenes' || segPref === 'teens';
    }
    return true;
  }

  // ── Filtrado en frontend ────────────────────────────────────────
  function filasPorFiltro() {
    const buscar = document.getElementById('li-buscar').value.trim().toLowerCase();
    const genero = document.getElementById('li-genero').value;
    const filtroPago = document.getElementById('li-pago').value;
    const filtroLider = (document.getElementById('li-lider')?.value || '').trim();

    const terminoBuscar = normalizar(buscar);

    return todosLosDatos.filter(p => {
      const pagado = tienePagoRegistrado(p);
      if (filtroPago === 'pagados' && !pagado) return false;
      if (filtroPago === 'sin_pago' && pagado) return false;

      if (filtroLider !== '') {
        const liderFila = (p.Lider || '').trim();
        if (liderFila !== filtroLider) return false;
      }

      if (terminoBuscar !== '') {
        const coincideBuscar =
          normalizar(p.Nombre).includes(terminoBuscar)
          || normalizar(p.Cedula).includes(terminoBuscar)
          || normalizar(p.Telefono).includes(terminoBuscar);
        if (!coincideBuscar) return false;
      }

      return coincideFiltroSegmento(p, genero);
    });
  }

  function normalizar(s) {
    return (s || '').toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  function hayFiltrosActivos() {
    const buscar = document.getElementById('li-buscar').value.trim();
    const genero = document.getElementById('li-genero').value;
    const pago = document.getElementById('li-pago').value;
    const lider = (document.getElementById('li-lider')?.value || '').trim();
    return buscar !== '' || genero !== 'todos' || pago !== 'todos' || lider !== '';
  }

  function etiquetaFiltroGenero(valor) {
    if (valor === 'hombre') return 'Hombres (todas las edades)';
    if (valor === 'mujer') return 'Mujeres (todas las edades)';
    if (valor === 'joven') return 'Jóvenes y teens (por edad)';
    return '';
  }

  function etiquetaFiltroPago(valor) {
    if (valor === 'pagados') return 'Con pago';
    if (valor === 'sin_pago') return 'Sin pago';
    return '';
  }

  function describirFiltrosActivos() {
    const partes = [];
    const buscar = document.getElementById('li-buscar').value.trim();
    const genero = document.getElementById('li-genero').value;
    const pago = document.getElementById('li-pago').value;
    const lider = (document.getElementById('li-lider')?.value || '').trim();
    if (buscar !== '') partes.push('Búsqueda: «' + buscar + '»');
    const eg = etiquetaFiltroGenero(genero);
    if (eg) partes.push(eg);
    const ep = etiquetaFiltroPago(pago);
    if (ep) partes.push(ep);
    if (lider !== '') partes.push('Líder: «' + lider + '»');
    return partes.join(' · ');
  }

  function poblarFiltroLideres() {
    const select = document.getElementById('li-lider');
    if (!select) return;
    const valorActual = select.value;
    const lideres = new Set();
    todosLosDatos.forEach(function (p) {
      const nombre = (p.Lider || '').trim();
      if (nombre !== '' && nombre !== '–') {
        lideres.add(nombre);
      }
    });
    const ordenados = Array.from(lideres).sort(function (a, b) {
      return normalizar(a).localeCompare(normalizar(b), 'es');
    });
    select.innerHTML = '<option value="">Todos los líderes</option>';
    ordenados.forEach(function (nombre) {
      const opt = document.createElement('option');
      opt.value = nombre;
      opt.textContent = nombre;
      select.appendChild(opt);
    });
    if (valorActual && ordenados.includes(valorActual)) {
      select.value = valorActual;
    }
  }

  function contarConPago(lista) {
    return (lista || []).filter((p) => tienePagoRegistrado(p)).length;
  }

  function contarSinPago(lista) {
    return (lista || []).filter((p) => !tienePagoRegistrado(p)).length;
  }

  /** Encuentro: clase 5 = día 1, clase 6 = día 2 */
  const CLASE_ENC_D1 = 5;
  const CLASE_ENC_D2 = 6;

  function contarAsistenciaClase(lista, numeroClase) {
    const key = 'clase_' + numeroClase;
    return (lista || []).filter((p) => !!p[key]).length;
  }

  function actualizarEncuentroCard(filas) {
    const elD1 = document.getElementById('li-enc-d1');
    const elD2 = document.getElementById('li-enc-d2');
    if (!elD1 || !elD2) return;

    const total = (filas || []).length;
    const asistD1 = contarAsistenciaClase(filas, CLASE_ENC_D1);
    const asistD2 = contarAsistenciaClase(filas, CLASE_ENC_D2);

    elD1.textContent = asistD1 + ' / ' + total;
    elD2.textContent = asistD2 + ' / ' + total;
  }

  function actualizarEstadoCarga(filas) {
    const total = todosLosDatos.length;
    const visibles = filas.length;
    const pagados = contarConPago(filas);
    const sinPago = contarSinPago(filas);
    let estado = total + ' registros cargados';
    if (hayFiltrosActivos()) {
      estado = visibles + ' mostradas · ' + pagados + ' con pago · ' + sinPago + ' sin pago';
      if (visibles !== total) {
        estado += ' (de ' + total + ')';
      }
    } else {
      estado += ' · ' + pagados + ' con pago · ' + sinPago + ' sin pago';
    }
    setEstado(estado);
  }

  function actualizarResumenConteos(filas) {
    const total = todosLosDatos.length;
    const visibles = filas.length;
    const filtrosActivos = hayFiltrosActivos();

    document.getElementById('li-total-personas').textContent = String(total);
    document.getElementById('li-total-visibles').textContent = String(visibles);

    const statVisible = document.getElementById('li-stat-visible');
    const labelVisible = document.getElementById('li-label-visibles');
    const subVisible = document.getElementById('li-sub-visibles');

    if (filtrosActivos) {
      statVisible.classList.add('is-active');
      labelVisible.textContent = 'Personas con filtro';
      subVisible.textContent = visibles === total
        ? 'Coincide con el total'
        : ('de ' + total + ' inscritos');
      subVisible.hidden = false;
    } else {
      statVisible.classList.remove('is-active');
      labelVisible.textContent = 'Personas mostradas';
      subVisible.textContent = 'Sin filtros activos';
      subVisible.hidden = false;
    }

    const pagadosGlobal = contarConPago(todosLosDatos);
    const pagadosVista = contarConPago(filas);
    const sinPagoGlobal = contarSinPago(todosLosDatos);
    const sinPagoVista = contarSinPago(filas);

    const elPagados = document.getElementById('li-total-pagados');
    const labelPagados = document.getElementById('li-label-pagados');
    const subPagados = document.getElementById('li-sub-pagados');

    if (filtrosActivos) {
      elPagados.textContent = String(pagadosVista);
      labelPagados.textContent = 'Con pago (filtro)';
      subPagados.textContent = pagadosVista === pagadosGlobal
        ? 'en esta vista'
        : ('de ' + pagadosGlobal + ' en total');
      subPagados.hidden = false;
    } else {
      elPagados.textContent = String(pagadosGlobal);
      labelPagados.textContent = 'Con pago';
      subPagados.hidden = true;
    }

    actualizarEncuentroCard(filas);

    if (pagadosVista + sinPagoVista !== visibles) {
      console.warn('Conteo de pagos no coincide con personas visibles', {
        visibles,
        pagadosVista,
        sinPagoVista
      });
    }

    const resumenFiltro = document.getElementById('li-resumen-filtro');
    if (filtrosActivos) {
      const detalle = describirFiltrosActivos();
      resumenFiltro.textContent = 'Filtro activo: ' + detalle
        + ' — Mostrando ' + visibles + ' persona' + (visibles === 1 ? '' : 's')
        + (visibles !== total ? (' de ' + total) : '')
        + ' · ' + pagadosVista + ' con pago, ' + sinPagoVista + ' sin pago';
      resumenFiltro.hidden = false;
    } else {
      resumenFiltro.hidden = true;
      resumenFiltro.textContent = '';
    }
  }

  // ── Renderizado ─────────────────────────────────────────────────
  function renderTabla() {
    const filas = filasPorFiltro();
    const tbody = document.getElementById('li-tbody');

    actualizarResumenConteos(filas);
    actualizarEstadoCarga(filas);

    if (filas.length === 0) {
      tbody.innerHTML = '<tr><td colspan="18" class="li-empty">Sin resultados para este filtro.</td></tr>';
      return;
    }

    const fragment = document.createDocumentFragment();
    filas.forEach(p => {
      const tr = document.createElement('tr');
      tr.dataset.idPersona = p.Id_Persona;

      // Datos básicos
      tr.innerHTML = `
        <td class="t-left col-nombre"><strong>${esc(p.Nombre)}</strong></td>
        <td class="col-genero">${badgeGenero(p.Genero)}</td>
        <td class="col-edad">${esc(p.Edad || '–')}</td>
        <td class="col-cedula"><span style="font-family:monospace;font-size:0.78rem;">${esc(p.Cedula || '–')}</span></td>
        <td class="col-telefono">${esc(p.Telefono || '–')}</td>
        <td class="t-left col-lider" style="color:#475569;">${esc(p.Lider || '–')}</td>
        <td class="col-pago">${btnPago(p)}</td>
        <td class="col-acciones">${btnAcciones(p)}</td>
      `;

      const clasesAsist = ['col-pre-1', 'col-pre-2', 'col-pre-3', 'col-pre-4', 'col-enc-1', 'col-enc-2', 'col-post-1', 'col-post-2', 'col-post-3', 'col-post-4'];
      for (let c = 1; c <= 10; c++) {
        const td = document.createElement('td');
        td.className = clasesAsist[c - 1];
        const cb = document.createElement('input');
        cb.type = 'checkbox';
        cb.className = 'check-asist';
        cb.checked = !!p['clase_' + c];
        cb.dataset.idPersona = p.Id_Persona;
        cb.dataset.clase = c;
        cb.addEventListener('change', onCheckChange);
        td.appendChild(cb);
        tr.appendChild(td);
      }

      fragment.appendChild(tr);
    });

    tbody.innerHTML = '';
    tbody.appendChild(fragment);
  }

  // ── Badge género ────────────────────────────────────────────────
  function badgeGenero(g) {
    if (!g) return '<span style="color:#94a3b8">–</span>';
    const n = normalizar(g);
    if (n.includes('joven') && (n.includes('hombre') || n.includes('mascul')))
      return '<span style="background:#dbeafe;color:#1e40af;padding:2px 7px;border-radius:99px;font-size:0.70rem;font-weight:700;">♂ Joven</span>';
    if (n.includes('joven') && (n.includes('mujer') || n.includes('femen')))
      return '<span style="background:#fce7f3;color:#9d174d;padding:2px 7px;border-radius:99px;font-size:0.70rem;font-weight:700;">♀ Joven</span>';
    if (n.includes('hombre') || n.includes('mascul') || n === 'm')
      return '<span style="background:#dbeafe;color:#1e40af;padding:2px 7px;border-radius:99px;font-size:0.70rem;font-weight:700;">♂ H</span>';
    if (n.includes('mujer') || n.includes('femen') || n === 'f')
      return '<span style="background:#fce7f3;color:#9d174d;padding:2px 7px;border-radius:99px;font-size:0.70rem;font-weight:700;">♀ M</span>';
    return '<span style="color:#64748b;font-size:0.75rem;">' + esc(g) + '</span>';
  }

  // ── Acciones editar / eliminar ──────────────────────────────────
  function btnAcciones(p) {
    if (!PERMISOS.puedeEditar && !PERMISOS.puedeEliminar) {
      return '<span style="color:#94a3b8;font-size:0.75rem;">–</span>';
    }

    const idPersona = Number(p.Id_Persona || 0);
    const idInscripcion = Number(p.Id_Inscripcion || 0);
    const nombre = esc(p.Nombre || 'esta persona');
    let html = '<div class="li-acciones" role="group" aria-label="Acciones">';

    if (PERMISOS.puedeEditar) {
      if (idPersona > 0 && PERMISOS.puedeEditarPersona) {
        const editHref = EDITAR_URL
          + '&id=' + encodeURIComponent(idPersona)
          + '&return_to=formacion'
          + '&return_url=' + encodeURIComponent(RETURN_URL);
        html += '<a href="' + editHref + '" class="li-acc-btn li-acc-btn--edit" title="Editar persona" aria-label="Editar">'
          + '<i class="bi bi-pencil-square" aria-hidden="true"></i></a>';
      } else if (idInscripcion > 0) {
        const regHref = REGISTRO_UV_URL + '&id_inscripcion=' + encodeURIComponent(idInscripcion);
        html += '<a href="' + regHref + '" class="li-acc-btn li-acc-btn--edit" title="Editar inscripción" aria-label="Editar">'
          + '<i class="bi bi-pencil-square" aria-hidden="true"></i></a>';
      } else if (idPersona > 0) {
        const regHref = REGISTRO_UV_URL
          + '&id_persona=' + encodeURIComponent(idPersona)
          + '&id_inscripcion=' + encodeURIComponent(idInscripcion);
        html += '<a href="' + regHref + '" class="li-acc-btn li-acc-btn--edit" title="Editar inscripción" aria-label="Editar">'
          + '<i class="bi bi-pencil-square" aria-hidden="true"></i></a>';
      } else {
        html += '<button type="button" class="li-acc-btn li-acc-btn--edit" disabled title="Sin registro para editar" aria-label="Editar">'
          + '<i class="bi bi-pencil-square" aria-hidden="true"></i></button>';
      }
    }

    if (PERMISOS.puedeEliminar) {
      if (idInscripcion > 0) {
        html += '<button type="button" class="li-acc-btn li-acc-btn--del js-li-eliminar"'
          + ' data-id-inscripcion="' + idInscripcion + '"'
          + ' data-nombre="' + nombre + '"'
          + ' title="Eliminar inscripción" aria-label="Eliminar">'
          + '<i class="bi bi-trash" aria-hidden="true"></i></button>';
      } else {
        html += '<button type="button" class="li-acc-btn li-acc-btn--del" disabled title="Inscripción inválida" aria-label="Eliminar">'
          + '<i class="bi bi-trash" aria-hidden="true"></i></button>';
      }
    }

    html += '</div>';
    return html;
  }

  async function eliminarInscripcion(idInscripcion, nombre) {
    if (!confirm('¿Eliminar la inscripción de ' + nombre + '?')) {
      return;
    }

    const fd = new FormData();
    fd.append('id_inscripcion', String(idInscripcion));

    try {
      const resp = await fetch(ELIMINAR_URL, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await resp.json();
      if (!json.success) {
        throw new Error(json.mensaje || 'No se pudo eliminar');
      }
      mostrarIndicador('Inscripción eliminada', false);
      await cargarDatos();
    } catch (e) {
      mostrarIndicador(e.message || 'Error al eliminar', true);
    }
  }

  // ── Botón pago ──────────────────────────────────────────────────
  function btnPago(p) {
    const qs = new URLSearchParams({
      id_persona: p.Id_Persona || '',
      id_inscripcion: p.Id_Inscripcion || '',
      cedula:   p.Cedula   || '',
      nombre:   p.Nombre   || '',
      telefono: p.Telefono || '',
      genero: p.Genero || '',
      edad: p.Edad || '',
      lider: p.Lider || '',
      id_ministerio: p.Id_Ministerio || '',
      programa: PROGRAMA,
    }).toString();
    const href = ABONO_URL + '&' + qs;
    const totalPagado = Number(p.total_pagado || 0);
    const tienePago = tienePagoRegistrado(p);
    const estado = tienePago
      ? `<span class="pago-status ok" title="Ya tiene pago registrado">$${formatNumber(totalPagado)}</span>`
      : `<span class="pago-status" title="Sin pago registrado">Sin pago</span>`;

    return `<div class="pago-cell">`
      + `<a class="btn-pago-icon" href="${href}" target="_blank" title="Registrar pago/abono">💳</a>`
      + estado
      + `</div>`;
  }

  function formatNumber(n) {
    return Number(n || 0).toLocaleString('es-CO', { maximumFractionDigits: 0 });
  }

  // ── Guardar asistencia ──────────────────────────────────────────
  async function onCheckChange(e) {
    const cb = e.target;
    if (guardandoCheck) {
      // Revertir y encolar para guardar
    }
    cb.disabled = true;
    const idPersona = parseInt(cb.dataset.idPersona, 10);
    const clase     = parseInt(cb.dataset.clase, 10);
    const asistio   = cb.checked ? 1 : 0;

    // Actualizar en memoria
    const p = todosLosDatos.find(x => x.Id_Persona == idPersona);
    if (p) p['clase_' + clase] = !!cb.checked;

    try {
      const body = new FormData();
      body.append('id_persona',   idPersona);
      body.append('modulo',       'consolidar');
      body.append('programa',     PROGRAMA);
      body.append('numero_clase', clase);
      body.append('asistio',      asistio);

      const resp = await fetch(ASIST_URL, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: body,
      });
      const json = await resp.json();
      if (!json.success) throw new Error('Error al guardar');
      mostrarIndicador('✓ Guardado', false);
      renderTabla();
    } catch (err) {
      cb.checked = !cb.checked;  // revertir
      if (p) p['clase_' + clase] = !!cb.checked;
      mostrarIndicador('✗ Error al guardar', true);
    } finally {
      cb.disabled = false;
    }
  }

  // ── Indicador de guardado ───────────────────────────────────────
  function mostrarIndicador(msg, esError) {
    const el = document.getElementById('li-save-indicator');
    el.textContent = msg;
    el.classList.toggle('error', esError);
    el.classList.add('show');
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => el.classList.remove('show'), 2200);
  }

  // ── Estado header ───────────────────────────────────────────────
  function setEstado(msg) {
    document.getElementById('li-estado').textContent = msg;
  }

  // ── Escape HTML ─────────────────────────────────────────────────
  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // ── Escuchar eventos de filtro ──────────────────────────────────
  let searchTimer = null;
  document.getElementById('li-buscar').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(renderTabla, 280);
  });
  document.getElementById('li-genero').addEventListener('change', renderTabla);
  document.getElementById('li-pago').addEventListener('change', renderTabla);
  const selectLider = document.getElementById('li-lider');
  if (selectLider) {
    selectLider.addEventListener('change', renderTabla);
  }

  const STORAGE_COLUMNAS = 'mcimadrid_uv_asist_columnas_visibles';
  const STORAGE_OCULTAR_PRE_POST_LEGACY = 'mcimadrid_uv_asist_ocultar_pre_post';
  const tablaAsistencias = document.getElementById('li-table');
  const colPicker = document.getElementById('li-col-picker');
  const colPickerBtn = document.getElementById('li-col-picker-btn');
  const colPickerPanel = document.getElementById('li-col-picker-panel');
  const colCheckboxesHost = document.getElementById('li-col-checkboxes');

  const COLUMNAS_DEF = [
    { id: 'nombre', label: 'Nombre', grupo: 'Datos personales' },
    { id: 'genero', label: 'Género', grupo: 'Datos personales' },
    { id: 'edad', label: 'Edad', grupo: 'Datos personales' },
    { id: 'cedula', label: 'Cédula', grupo: 'Datos personales' },
    { id: 'telefono', label: 'Teléfono', grupo: 'Datos personales' },
    { id: 'lider', label: 'Líder', grupo: 'Datos personales' },
    { id: 'pago', label: 'Pago / Abono', grupo: 'Gestión' },
    { id: 'acciones', label: 'Acciones', grupo: 'Gestión' },
    { id: 'pre-1', label: 'Pre-encuentro — C1', grupo: 'Clases pre-encuentro' },
    { id: 'pre-2', label: 'Pre-encuentro — C2', grupo: 'Clases pre-encuentro' },
    { id: 'pre-3', label: 'Pre-encuentro — C3', grupo: 'Clases pre-encuentro' },
    { id: 'pre-4', label: 'Pre-encuentro — C4', grupo: 'Clases pre-encuentro' },
    { id: 'enc-1', label: 'Encuentro — Día 1', grupo: 'Encuentro' },
    { id: 'enc-2', label: 'Encuentro — Día 2', grupo: 'Encuentro' },
    { id: 'post-1', label: 'Post-encuentro — C1', grupo: 'Clases post-encuentro' },
    { id: 'post-2', label: 'Post-encuentro — C2', grupo: 'Clases post-encuentro' },
    { id: 'post-3', label: 'Post-encuentro — C3', grupo: 'Clases post-encuentro' },
    { id: 'post-4', label: 'Post-encuentro — C4', grupo: 'Clases post-encuentro' }
  ];

  const columnasVisibles = {};
  COLUMNAS_DEF.forEach(function (col) {
    columnasVisibles[col.id] = true;
  });

  function idsOcultosDesdeEstado() {
    return COLUMNAS_DEF.filter(function (col) {
      return !columnasVisibles[col.id];
    }).map(function (col) {
      return col.id;
    });
  }

  function actualizarEncabezadosGrupo() {
    if (!tablaAsistencias) return;
    const ocultas = new Set(idsOcultosDesdeEstado());
    const grupos = {
      pre: ['pre-1', 'pre-2', 'pre-3', 'pre-4'],
      enc: ['enc-1', 'enc-2'],
      post: ['post-1', 'post-2', 'post-3', 'post-4']
    };
    Object.keys(grupos).forEach(function (nombre) {
      const ids = grupos[nombre];
      const todasOcultas = ids.every(function (id) { return ocultas.has(id); });
      const visibles = ids.filter(function (id) { return !ocultas.has(id); }).length;
      tablaAsistencias.querySelectorAll('[data-col-group="' + nombre + '"]').forEach(function (th) {
        th.style.display = todasOcultas ? 'none' : '';
        if (!todasOcultas) {
          th.colSpan = visibles;
        }
      });
    });
  }

  function aplicarColumnasVisibles() {
    if (!tablaAsistencias) return;
    const ocultas = idsOcultosDesdeEstado();
    const nVisibles = COLUMNAS_DEF.length - ocultas.length;
    const modoCompacto = ocultas.length > 0;

    tablaAsistencias.setAttribute('data-oculta', ocultas.join(' '));
    tablaAsistencias.setAttribute('data-visibles', String(nVisibles));
    tablaAsistencias.classList.toggle('li-table--compact', modoCompacto);

    const wrap = tablaAsistencias.closest('.li-table-wrap');
    if (wrap) {
      wrap.classList.toggle('li-table-wrap--compact', modoCompacto);
    }

    actualizarEncabezadosGrupo();
    try {
      localStorage.setItem(STORAGE_COLUMNAS, JSON.stringify(columnasVisibles));
    } catch (e) {}
  }

  function cargarColumnasGuardadas() {
    try {
      const legacy = localStorage.getItem(STORAGE_OCULTAR_PRE_POST_LEGACY);
      const raw = localStorage.getItem(STORAGE_COLUMNAS);
      if (raw) {
        const parsed = JSON.parse(raw);
        if (parsed && typeof parsed === 'object') {
          COLUMNAS_DEF.forEach(function (col) {
            if (typeof parsed[col.id] === 'boolean') {
              columnasVisibles[col.id] = parsed[col.id];
            }
          });
          return;
        }
      }
      if (legacy === '1') {
        ['pre-1', 'pre-2', 'pre-3', 'pre-4', 'post-1', 'post-2', 'post-3', 'post-4'].forEach(function (id) {
          columnasVisibles[id] = false;
        });
      }
    } catch (e) {}
  }

  function renderColumnasCheckboxes() {
    if (!colCheckboxesHost) return;
    colCheckboxesHost.innerHTML = '';
    let grupoActual = '';
    COLUMNAS_DEF.forEach(function (col) {
      if (col.grupo !== grupoActual) {
        grupoActual = col.grupo;
        const titulo = document.createElement('div');
        titulo.className = 'li-col-picker__grupo';
        titulo.textContent = grupoActual;
        colCheckboxesHost.appendChild(titulo);
      }
      const label = document.createElement('label');
      label.className = 'li-col-picker__item';
      const input = document.createElement('input');
      input.type = 'checkbox';
      input.checked = !!columnasVisibles[col.id];
      input.dataset.colId = col.id;
      input.addEventListener('change', function () {
        columnasVisibles[col.id] = input.checked;
        aplicarColumnasVisibles();
      });
      label.appendChild(input);
      label.appendChild(document.createTextNode(col.label));
      colCheckboxesHost.appendChild(label);
    });
  }

  function setTodasColumnas(visible) {
    COLUMNAS_DEF.forEach(function (col) {
      columnasVisibles[col.id] = !!visible;
    });
    if (colCheckboxesHost) {
      colCheckboxesHost.querySelectorAll('input[type=checkbox]').forEach(function (input) {
        input.checked = !!visible;
      });
    }
    aplicarColumnasVisibles();
  }

  function setSoloEncuentro() {
    COLUMNAS_DEF.forEach(function (col) {
      const esPrePost = col.id.indexOf('pre-') === 0 || col.id.indexOf('post-') === 0;
      columnasVisibles[col.id] = !esPrePost;
    });
    if (colCheckboxesHost) {
      colCheckboxesHost.querySelectorAll('input[type=checkbox]').forEach(function (input) {
        const id = input.dataset.colId || '';
        input.checked = !!(columnasVisibles[id]);
      });
    }
    aplicarColumnasVisibles();
  }

  if (colPicker && colPickerBtn && colPickerPanel && tablaAsistencias) {
    cargarColumnasGuardadas();
    renderColumnasCheckboxes();
    aplicarColumnasVisibles();

    colPickerBtn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      const abierto = colPicker.classList.toggle('is-open');
      colPickerBtn.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });

    document.getElementById('li-col-ver-todas')?.addEventListener('click', function () {
      setTodasColumnas(true);
    });
    document.getElementById('li-col-solo-encuentro')?.addEventListener('click', function () {
      setSoloEncuentro();
    });

    document.addEventListener('click', function (ev) {
      if (!colPicker.contains(ev.target)) {
        colPicker.classList.remove('is-open');
        colPickerBtn.setAttribute('aria-expanded', 'false');
      }
    });
    colPickerPanel.addEventListener('click', function (ev) {
      ev.stopPropagation();
    });
  }

  document.getElementById('li-tbody').addEventListener('click', (ev) => {
    const btn = ev.target.closest('.js-li-eliminar');
    if (!btn) return;
    const idInscripcion = Number(btn.dataset.idInscripcion || 0);
    const nombre = btn.dataset.nombre || 'esta persona';
    if (idInscripcion > 0) {
      eliminarInscripcion(idInscripcion, nombre);
    }
  });

  // ── Carga inicial ───────────────────────────────────────────────
  cargarDatos();

})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
