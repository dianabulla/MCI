<?php include VIEWS . '/layout/header.php'; ?>
<?php
$programa   = (string)($programa   ?? 'universidad_vida');
$titulo     = (string)($titulo     ?? 'Inscritos Universidad de la Vida');
$publicUrl  = rtrim((string)($public_url ?? PUBLIC_URL), '/');
$urlPagosEscuelaUv = $publicUrl . '/?url=escuelas_formacion/pagos/consolidar';
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
    <div style="align-self:flex-end;">
      <button class="btn-pago" style="background:#475569;" onclick="cargarDatos()">↻ Actualizar</button>
    </div>
  </div>

  <!-- Resumen numérico -->
  <div class="li-summary">
    <div class="li-stat">
      <strong id="li-total-personas">–</strong>
      <span>Total inscritos</span>
    </div>
    <div class="li-stat">
      <strong id="li-total-asistencias">–</strong>
      <span>Asistencias marcadas</span>
    </div>
    <div class="li-stat" id="li-stat-visible" style="display:none;">
      <strong id="li-total-visibles">–</strong>
      <span>Visible / filtro</span>
    </div>
  </div>

  <!-- Tabla -->
  <div class="li-card">
    <div class="li-table-wrap">
      <table class="li-table" id="li-table">
        <thead>
          <tr>
            <!-- Datos básicos -->
            <th class="t-left" rowspan="2" style="min-width:160px;">Nombre</th>
            <th rowspan="2">Género</th>
            <th rowspan="2">Edad</th>
            <th rowspan="2">Cédula</th>
            <th rowspan="2">Teléfono</th>
            <th class="t-left" rowspan="2" style="min-width:120px;">Líder</th>
            <!-- Pago -->
            <th rowspan="2">Pago / Abono</th>
            <!-- Grupos asistencia -->
            <th colspan="4" class="th-pre">Clases Pre-Encuentro</th>
            <th colspan="2" class="th-enc">Encuentro</th>
            <th colspan="4" class="th-post">Clases Post-Encuentro</th>
          </tr>
          <tr>
            <!-- Pre-encuentro clases 1-4 -->
            <th class="th-pre">C1</th>
            <th class="th-pre">C2</th>
            <th class="th-pre">C3</th>
            <th class="th-pre">C4</th>
            <!-- Encuentro días 1-2 -->
            <th class="th-enc">Día 1</th>
            <th class="th-enc">Día 2</th>
            <!-- Post-encuentro clases 1-4 (7-10) -->
            <th class="th-post">C1</th>
            <th class="th-post">C2</th>
            <th class="th-post">C3</th>
            <th class="th-post">C4</th>
          </tr>
        </thead>
        <tbody id="li-tbody">
          <tr><td colspan="17" class="li-loading">Cargando datos…</td></tr>
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
  const ABONO_URL  = BASE_URL + 'escuelas_formacion/inscritos/abono-admin';
  const ASIST_URL  = BASE_URL + 'escuelas_formacion/inscritos/guardar-asistencia';
  const DATOS_URL  = BASE_URL + 'escuelas_formacion/inscritos&ajax=1';

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
      renderTabla();
      setEstado(todosLosDatos.length + ' registros cargados');
    } catch (e) {
      setEstado('Error al cargar datos');
      document.getElementById('li-tbody').innerHTML =
        '<tr><td colspan="17" class="li-empty">No se pudieron cargar los datos. Intenta de nuevo.</td></tr>';
    }
  }

  function tienePagoRegistrado(p) {
    const totalPagado = Number(p.total_pagado || 0);
    return !!p.tiene_pago_registrado || totalPagado > 0;
  }

  // ── Filtrado en frontend ────────────────────────────────────────
  function filasPorFiltro() {
    const buscar = document.getElementById('li-buscar').value.trim().toLowerCase();
    const genero = document.getElementById('li-genero').value;
    const filtroPago = document.getElementById('li-pago').value;

    return todosLosDatos.filter(p => {
      const pagado = tienePagoRegistrado(p);
      if (filtroPago === 'pagados' && !pagado) return false;
      if (filtroPago === 'sin_pago' && pagado) return false;

      // Búsqueda universal — si hay término, ignora el filtro de segmento
      if (buscar !== '') {
        const hayCoincidencia =
          normalizar(p.Nombre).includes(buscar) ||
          normalizar(p.Cedula).includes(buscar) ||
          normalizar(p.Telefono).includes(buscar);
        return hayCoincidencia;
      }

      // Sin búsqueda → aplica filtro de género/segmento
      if (genero === 'todos') return true;
      const g = normalizar(p.Genero || '');
      if (genero === 'hombre')  return g.includes('hombre') || g.includes('mascul') || g === 'm';
      if (genero === 'mujer')   return g.includes('mujer')  || g.includes('femen')  || g === 'f';
      if (genero === 'joven')   return g.includes('joven');
      return true;
    });
  }

  function normalizar(s) {
    return (s || '').toLowerCase()
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }

  // ── Renderizado ─────────────────────────────────────────────────
  function renderTabla() {
    const filas = filasPorFiltro();
    const tbody = document.getElementById('li-tbody');

    // Estadísticas
    let totalAsist = 0;
    todosLosDatos.forEach(p => {
      for (let c = 1; c <= 10; c++) {
        if (p['clase_' + c]) totalAsist++;
      }
    });
    document.getElementById('li-total-personas').textContent    = todosLosDatos.length;
    document.getElementById('li-total-asistencias').textContent = totalAsist;

    const statV = document.getElementById('li-stat-visible');
    const el = document.getElementById('li-total-visibles');
    if (filas.length < todosLosDatos.length) {
      statV.style.display = '';
      el.textContent = filas.length;
    } else {
      statV.style.display = 'none';
    }

    if (filas.length === 0) {
      tbody.innerHTML = '<tr><td colspan="17" class="li-empty">Sin resultados para este filtro.</td></tr>';
      return;
    }

    const fragment = document.createDocumentFragment();
    filas.forEach(p => {
      const tr = document.createElement('tr');
      tr.dataset.idPersona = p.Id_Persona;

      // Datos básicos
      tr.innerHTML = `
        <td class="t-left"><strong>${esc(p.Nombre)}</strong></td>
        <td>${badgeGenero(p.Genero)}</td>
        <td>${esc(p.Edad || '–')}</td>
        <td><span style="font-family:monospace;font-size:0.78rem;">${esc(p.Cedula || '–')}</span></td>
        <td>${esc(p.Telefono || '–')}</td>
        <td class="t-left" style="color:#475569;">${esc(p.Lider || '–')}</td>
        <td>${btnPago(p)}</td>
      `;

      // Checkboxes de asistencia (10 en total)
      for (let c = 1; c <= 10; c++) {
        const td = document.createElement('td');
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
      // Actualizar resumen total
      actualizarTotalAsistencias();
    } catch (err) {
      cb.checked = !cb.checked;  // revertir
      if (p) p['clase_' + clase] = !!cb.checked;
      mostrarIndicador('✗ Error al guardar', true);
    } finally {
      cb.disabled = false;
    }
  }

  function actualizarTotalAsistencias() {
    let total = 0;
    todosLosDatos.forEach(p => {
      for (let c = 1; c <= 10; c++) {
        if (p['clase_' + c]) total++;
      }
    });
    document.getElementById('li-total-asistencias').textContent = total;
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

  // ── Carga inicial ───────────────────────────────────────────────
  cargarDatos();

})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
