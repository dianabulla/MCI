<?php include VIEWS . '/layout/header.php'; ?>

<?php $volverUrlAsistencias = (string)($return_url ?? (PUBLIC_URL . '?url=asistencias')); ?>
<?php
$flashErrorAsistencia = (string)($_SESSION['asistencia_flash_error'] ?? '');
unset($_SESSION['asistencia_flash_error']);
?>

<div class="page-header">
    <h2>Registrar Asistencia</h2>
    <div class="page-actions">
        <a id="persona_nueva_link" href="<?= PUBLIC_URL ?>index.php?url=personas/crear&return_to=asistencia<?= !empty($celula_preseleccionada) ? '&celula=' . (int)$celula_preseleccionada : '' ?>" class="btn btn-primary">Persona Nueva</a>
        <a href="<?= htmlspecialchars($volverUrlAsistencias) ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>

<?php if ($flashErrorAsistencia !== ''): ?>
<div class="alert alert-danger"><?= htmlspecialchars($flashErrorAsistencia, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="form-container" style="max-width: 800px;">
    <form method="POST" id="formAsistencia">
        <input type="hidden" name="return_url" value="<?= htmlspecialchars($volverUrlAsistencias) ?>">
        <div class="form-group autocomplete-wrapper">
            <label for="celula_search">Célula</label>
            <input type="text" id="celula_search" class="form-control"
                   placeholder="Buscar célula..."
                   autocomplete="off" required>
            <input type="hidden" id="id_celula" name="id_celula" required>
            <div id="celula_autocomplete" class="autocomplete-items"></div>
            <small class="form-text text-muted">Escriba el nombre de la célula para buscarla</small>
        </div>

        <div class="form-group">
            <label for="fecha">Fecha</label>
            <input type="date" id="fecha" name="fecha" class="form-control" value="<?= htmlspecialchars((string)($fecha_preseleccionada ?? date('Y-m-d'))) ?>" required>
            <small class="form-text text-muted">Los indicadores cuentan el reporte según esta fecha (lunes a domingo de esa semana). Si reportas la semana pasada, no uses la fecha de hoy.</small>
        </div>

        <div class="form-group">
            <label for="tema">Tema</label>
            <input type="text" id="tema" name="tema" class="form-control">
        </div>

        <div class="form-group">
            <label class="no-realizada-toggle" for="no_se_realizo">
                <input type="checkbox" id="no_se_realizo" name="no_se_realizo" value="1">
                <span>No se realizó</span>
            </label>
            <small class="form-text text-muted">Márcala cuando la célula no pudo reunirse; las asistencias quedarán en no asistió.</small>

            <div id="motivo_no_realizo_wrap" class="motivo-no-realizo-wrap" hidden>
                <p class="motivo-no-realizo-title">¿Por qué no se realizó?</p>
                <label class="motivo-no-realizo-option">
                    <input type="radio" name="motivo_no_realizo" value="convencion" disabled>
                    <span>Convención</span>
                </label>
                <label class="motivo-no-realizo-option">
                    <input type="radio" name="motivo_no_realizo" value="actividades_iglesia" disabled>
                    <span>Actividades de la iglesia</span>
                </label>
                <label class="motivo-no-realizo-option">
                    <input type="radio" name="motivo_no_realizo" value="otro" disabled>
                    <span>Otro</span>
                </label>
                <div class="form-group" style="margin: 10px 0 0;">
                    <label for="motivo_no_realizo_otro">Especifique</label>
                    <input type="text" id="motivo_no_realizo_otro" name="motivo_no_realizo_otro" class="form-control" placeholder="Escriba el motivo" disabled>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3"></textarea>
        </div>

        <div id="miembros-container" style="margin-top: 30px; display: none;">
            <h3>Marcar Asistencias</h3>
            <div id="lista-miembros" style="margin-top: 20px;">
                <!-- Aquí se cargarán los miembros -->
            </div>
        </div>

        <div class="form-actions" id="botones-accion" style="display: none; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">Guardar Asistencias</button>
            <a href="<?= htmlspecialchars($volverUrlAsistencias) ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<div id="modalMigrarCelula" class="no-disponible-modal" aria-hidden="true">
    <div class="no-disponible-modal__overlay" data-close-migrar-celula="1"></div>
    <div class="no-disponible-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="migrarCelulaTitulo">
        <h3 id="migrarCelulaTitulo" style="margin-top:0;">Migrar a otra célula</h3>
        <p id="migrarCelulaPersona" style="margin:0 0 10px; color:#475569;"></p>
        <label for="migrarCelulaSelect" style="display:block; font-weight:600; margin-bottom:6px;">Célula del mismo ministerio</label>
        <select id="migrarCelulaSelect" class="form-control"></select>
        <p id="migrarCelulaVacio" class="form-text text-muted" hidden>No hay otras células en el mismo ministerio.</p>
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;">
            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarMigrarCelula">Cancelar</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnGuardarMigrarCelula">Migrar</button>
        </div>
    </div>
</div>

<div id="modalNoDisponible" class="no-disponible-modal" aria-hidden="true">
    <div class="no-disponible-modal__overlay" data-close-no-disponible="1"></div>
    <div class="no-disponible-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="noDisponibleTitulo">
        <h3 id="noDisponibleTitulo" style="margin-top:0;">Marcar como No se dispuso</h3>
        <p id="noDisponiblePersona" style="margin:0 0 10px; color:#475569;"></p>
        <label for="noDisponibleObservacion" style="display:block; font-weight:600; margin-bottom:6px;">Observación</label>
        <textarea id="noDisponibleObservacion" class="form-control" rows="4" placeholder="Escribe la razón o contexto..."></textarea>
        <div style="display:flex; gap:8px; justify-content:flex-end; margin-top:12px;">
            <button type="button" class="btn btn-secondary btn-sm" id="btnCancelarNoDisponible">Cancelar</button>
            <button type="button" class="btn btn-danger btn-sm" id="btnGuardarNoDisponible">Guardar</button>
        </div>
    </div>
</div>

<?php include VIEWS . '/personas/partials/modal_seguimiento.php'; ?>

<style>
.autocomplete-wrapper {
    position: relative;
}

.autocomplete-items {
    position: absolute;
    border: 1px solid #d4d4d4;
    border-bottom: none;
    border-top: none;
    z-index: 99;
    top: 100%;
    left: 0;
    right: 0;
    max-height: 250px;
    overflow-y: auto;
    background: white;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.autocomplete-items div {
    padding: 10px;
    cursor: pointer;
    background-color: #fff;
    border-bottom: 1px solid #d4d4d4;
}

.autocomplete-items div:hover {
    background-color: #e9e9e9;
}

.autocomplete-active {
    background-color: #667eea !important;
    color: #ffffff;
}

.no-realizada-toggle {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: #334155;
    cursor: pointer;
    margin-bottom: 4px;
}

.no-realizada-toggle input {
    width: 16px;
    height: 16px;
    accent-color: #dc2626;
}

.motivo-no-realizo-wrap {
    margin-top: 12px;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
}

.motivo-no-realizo-title {
    margin: 0 0 8px;
    font-weight: 700;
    color: #1e3a5f;
}

.motivo-no-realizo-option {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 6px 0;
    font-weight: 500;
    color: #334155;
    cursor: pointer;
}

.no-disponible-modal {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1200;
}

.no-disponible-modal.is-open {
    display: flex;
}

.no-disponible-modal__overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
}

.no-disponible-modal__dialog {
    position: relative;
    width: min(520px, calc(100vw - 24px));
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 24px 54px rgba(15, 23, 42, 0.28);
}

.asist-acciones {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
}
.asist-acc-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    border-radius: 8px;
    border: 1px solid #d6e1f0;
    background: #f8fafc;
    color: #334155;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    line-height: 1;
}
.asist-acc-btn i { font-size: 15px; }
.asist-acc-btn:hover { background: #eef4fb; }
.asist-acc-btn:disabled { opacity: .45; cursor: not-allowed; }
.asist-acc-btn--del { border-color: #fecaca; color: #b91c1c; background: #fff1f2; }
.asist-acc-btn--del:hover { background: #fee2e2; color: #991b1b; }
.asist-acc-btn--nd { border-color: #fde68a; color: #b45309; background: #fffbeb; }
.asist-acc-btn--nd:hover { background: #fef3c7; color: #92400e; }
.asist-acc-btn--mig { border-color: #99f6e4; color: #0f766e; background: #ecfdf5; }
.asist-acc-btn--mig:hover { background: #ccfbf1; color: #115e59; }
</style>

<script>
// Datos de células desde PHP
const celulasDisponibles = <?= json_encode($celulas ?? []) ?>;

const urlMiembrosCelula = '<?= PUBLIC_URL ?>?url=asistencias/miembros-celula';
let miembrosCelulaActual = [];
let conteoAsistenciasPorPersona = {};
let duplicadosOcultosCelula = 0;

// Célula preseleccionada (opcional)
const celulaPreseleccionada = <?= json_encode($celula_preseleccionada ?? null) ?>;

// Autocompletar para célula
const celulaInput = document.getElementById('celula_search');
const celulaHidden = document.getElementById('id_celula');
const celulaAutocomplete = document.getElementById('celula_autocomplete');
const personaNuevaLink = document.getElementById('persona_nueva_link');
const noSeRealizoCheck = document.getElementById('no_se_realizo');
const observacionesInput = document.getElementById('observaciones');
const motivoNoRealizoWrap = document.getElementById('motivo_no_realizo_wrap');
const motivoNoRealizoRadios = Array.from(document.querySelectorAll('input[name="motivo_no_realizo"]'));
const motivoNoRealizoOtro = document.getElementById('motivo_no_realizo_otro');
const urlMarcarNoDisponible = '<?= PUBLIC_URL ?>?url=asistencias/marcarNoDisponible';
const urlQuitarDeCelula = '<?= PUBLIC_URL ?>?url=asistencias/quitarDeCelula';
const urlMigrarCelula = '<?= PUBLIC_URL ?>?url=asistencias/migrarCelula';
const puedeUsarSeguimientoReporte = <?= AuthController::puedeUsarSeguimientoPersonaListado() ? 'true' : 'false' ?>;
const puedeGuardarSeguimientoReporte = <?= AuthController::puedeGuardarSeguimientoPersonaListado() ? 'true' : 'false' ?>;
const modalNoDisponible = document.getElementById('modalNoDisponible');
const modalNoDisponiblePersona = document.getElementById('noDisponiblePersona');
const modalNoDisponibleObservacion = document.getElementById('noDisponibleObservacion');
const btnGuardarNoDisponible = document.getElementById('btnGuardarNoDisponible');
const btnCancelarNoDisponible = document.getElementById('btnCancelarNoDisponible');
const modalMigrarCelula = document.getElementById('modalMigrarCelula');
const modalMigrarCelulaPersona = document.getElementById('migrarCelulaPersona');
const migrarCelulaSelect = document.getElementById('migrarCelulaSelect');
const migrarCelulaVacio = document.getElementById('migrarCelulaVacio');
const btnGuardarMigrarCelula = document.getElementById('btnGuardarMigrarCelula');
const btnCancelarMigrarCelula = document.getElementById('btnCancelarMigrarCelula');
let currentFocus = -1;
let noDisponiblePendiente = null;
let migrarPendiente = null;

function idCelulaActual() {
    return parseInt(celulaHidden && celulaHidden.value ? celulaHidden.value : '0', 10) || 0;
}

function celulasMismoMinisterio(idCelulaActualNum) {
    const actual = celulasDisponibles.find(function (c) {
        return parseInt(c.Id_Celula, 10) === idCelulaActualNum;
    });
    const minActual = actual ? parseInt(actual.Id_Ministerio_Lider, 10) || 0 : 0;
    if (minActual <= 0) {
        return [];
    }
    return celulasDisponibles.filter(function (c) {
        const id = parseInt(c.Id_Celula, 10) || 0;
        const min = parseInt(c.Id_Ministerio_Lider, 10) || 0;
        return id > 0 && id !== idCelulaActualNum && min === minActual;
    });
}

function quitarFilaPersona(boton) {
    const fila = boton ? boton.closest('tr') : null;
    if (!fila) {
        return;
    }
    fila.style.opacity = '0.55';
    setTimeout(function () {
        fila.remove();
    }, 180);
}

function postAccionPersona(url, body) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: body.toString()
    }).then(function (resp) {
        return resp.json().then(function (data) {
            return { resp: resp, data: data };
        });
    });
}

function abrirModalMigrarCelula(persona, boton) {
    const actuales = celulasMismoMinisterio(idCelulaActual());
    migrarPendiente = { persona: persona, boton: boton };
    if (modalMigrarCelulaPersona) {
        modalMigrarCelulaPersona.textContent = 'Persona: ' + persona.nombre;
    }
    if (migrarCelulaSelect) {
        migrarCelulaSelect.innerHTML = '<option value="">Selecciona una célula…</option>';
        actuales.forEach(function (c) {
            const opt = document.createElement('option');
            opt.value = String(c.Id_Celula);
            opt.textContent = String(c.Nombre_Celula || 'Célula');
            migrarCelulaSelect.appendChild(opt);
        });
        migrarCelulaSelect.disabled = actuales.length === 0;
    }
    if (migrarCelulaVacio) {
        migrarCelulaVacio.hidden = actuales.length > 0;
    }
    if (btnGuardarMigrarCelula) {
        btnGuardarMigrarCelula.disabled = actuales.length === 0;
    }
    if (modalMigrarCelula) {
        modalMigrarCelula.classList.add('is-open');
        modalMigrarCelula.setAttribute('aria-hidden', 'false');
    }
}

function cerrarModalMigrarCelula() {
    if (modalMigrarCelula) {
        modalMigrarCelula.classList.remove('is-open');
        modalMigrarCelula.setAttribute('aria-hidden', 'true');
    }
    migrarPendiente = null;
}

function abrirModalNoDisponible(persona, boton) {
    noDisponiblePendiente = { persona, boton };
    if (modalNoDisponiblePersona) {
        modalNoDisponiblePersona.textContent = 'Persona: ' + persona.nombre;
    }
    if (modalNoDisponibleObservacion) {
        modalNoDisponibleObservacion.value = '';
    }
    if (modalNoDisponible) {
        modalNoDisponible.classList.add('is-open');
        modalNoDisponible.setAttribute('aria-hidden', 'false');
    }
    if (modalNoDisponibleObservacion) {
        setTimeout(() => modalNoDisponibleObservacion.focus(), 10);
    }
}

function cerrarModalNoDisponible() {
    noDisponiblePendiente = null;
    if (modalNoDisponible) {
        modalNoDisponible.classList.remove('is-open');
        modalNoDisponible.setAttribute('aria-hidden', 'true');
    }
}

function syncMotivoOtroState() {
    const otroMarcado = motivoNoRealizoRadios.some((radio) => radio.checked && radio.value === 'otro');
    if (motivoNoRealizoOtro) {
        motivoNoRealizoOtro.disabled = !otroMarcado;
        if (!otroMarcado) {
            motivoNoRealizoOtro.value = '';
        }
    }
}

function syncNoSeRealizoState() {
    const checksAsistencia = document.querySelectorAll('#lista-miembros input[type="checkbox"][name^="asistencias["]');
    const bloqueado = !!(noSeRealizoCheck && noSeRealizoCheck.checked);

    checksAsistencia.forEach((check) => {
        if (bloqueado) {
            check.checked = false;
        }
        check.disabled = bloqueado;

        const fila = check.closest('tr');
        if (fila) {
            fila.style.opacity = bloqueado ? '0.6' : '1';
        }
    });

    if (motivoNoRealizoWrap) {
        motivoNoRealizoWrap.hidden = !bloqueado;
    }
    motivoNoRealizoRadios.forEach((radio) => {
        radio.disabled = !bloqueado;
        if (!bloqueado) {
            radio.checked = false;
        }
    });
    syncMotivoOtroState();
}

if (noSeRealizoCheck) {
    noSeRealizoCheck.addEventListener('change', syncNoSeRealizoState);
}
motivoNoRealizoRadios.forEach((radio) => {
    radio.addEventListener('change', syncMotivoOtroState);
});

function actualizarEnlacePersonaNueva(celulaId) {
    if (!personaNuevaLink) return;

    let href = '<?= PUBLIC_URL ?>index.php?url=personas/crear&return_to=asistencia';
    if (celulaId) {
        href += '&celula=' + encodeURIComponent(celulaId);
    }
    personaNuevaLink.setAttribute('href', href);
}

celulaInput.addEventListener('input', function() {
    const value = this.value;
    celulaAutocomplete.innerHTML = '';
    
    if (!value) {
        celulaHidden.value = '';
        actualizarEnlacePersonaNueva('');
        // Limpiar miembros
        document.getElementById('miembros-container').style.display = 'none';
        document.getElementById('botones-accion').style.display = 'none';
        return false;
    }
    
    currentFocus = -1;
    
    const filtrados = celulasDisponibles.filter(celula => 
        celula.Nombre_Celula.toLowerCase().includes(value.toLowerCase())
    );
    
    if (filtrados.length === 0) {
        const div = document.createElement('div');
        div.innerHTML = '<em>No se encontraron células</em>';
        div.style.fontStyle = 'italic';
        div.style.color = '#999';
        celulaAutocomplete.appendChild(div);
        return;
    }
    
    filtrados.forEach(celula => {
        const div = document.createElement('div');
        const nombre = celula.Nombre_Celula;
        const index = nombre.toLowerCase().indexOf(value.toLowerCase());
        
        if (index >= 0) {
            div.innerHTML = nombre.substr(0, index) + 
                          '<strong>' + nombre.substr(index, value.length) + '</strong>' + 
                          nombre.substr(index + value.length);
        } else {
            div.innerHTML = nombre;
        }
        
        div.addEventListener('click', function() {
            celulaInput.value = nombre;
            celulaHidden.value = celula.Id_Celula;
            actualizarEnlacePersonaNueva(celula.Id_Celula);
            celulaAutocomplete.innerHTML = '';
            cargarMiembrosCelula(celula.Id_Celula);
        });
        
        celulaAutocomplete.appendChild(div);
    });
});

celulaInput.addEventListener('keydown', function(e) {
    let items = celulaAutocomplete.getElementsByTagName('div');
    
    if (e.keyCode === 40) { // Down
        currentFocus++;
        addActive(items);
    } else if (e.keyCode === 38) { // Up
        currentFocus--;
        addActive(items);
    } else if (e.keyCode === 13) { // Enter
        e.preventDefault();
        if (currentFocus > -1 && items[currentFocus]) {
            items[currentFocus].click();
        }
    }
});

function addActive(items) {
    if (!items) return false;
    removeActive(items);
    if (currentFocus >= items.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = (items.length - 1);
    items[currentFocus].classList.add('autocomplete-active');
}

function removeActive(items) {
    for (let i = 0; i < items.length; i++) {
        items[i].classList.remove('autocomplete-active');
    }
}

document.addEventListener('click', function(e) {
    if (e.target !== celulaInput) {
        celulaAutocomplete.innerHTML = '';
    }
});

async function cargarMiembrosCelula(celulaId) {
    const miembrosContainer = document.getElementById('miembros-container');
    const listaMiembros = document.getElementById('lista-miembros');
    const botonesAccion = document.getElementById('botones-accion');
    
    if (!celulaId) {
        miembrosContainer.style.display = 'none';
        botonesAccion.style.display = 'none';
        miembrosCelulaActual = [];
        conteoAsistenciasPorPersona = {};
        return;
    }

    const celulaIdNum = parseInt(celulaId, 10);
    listaMiembros.innerHTML = '<p style="color:#64748b;">Cargando miembros de la célula...</p>';
    miembrosContainer.style.display = 'block';
    botonesAccion.style.display = 'none';

    try {
        const resp = await fetch(urlMiembrosCelula + '&id_celula=' + encodeURIComponent(String(celulaIdNum)), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();
        if (!resp.ok || !data.ok) {
            throw new Error((data && data.error) ? data.error : 'No se pudieron cargar los miembros');
        }
        miembrosCelulaActual = Array.isArray(data.miembros) ? data.miembros : [];
        conteoAsistenciasPorPersona = (data.conteo && typeof data.conteo === 'object') ? data.conteo : {};
        duplicadosOcultosCelula = parseInt(data.duplicados_ocultos || 0, 10);
    } catch (err) {
        listaMiembros.innerHTML = '<div class="alert alert-warning">' + escapeHtml(err.message || 'Error al cargar miembros') + '</div>';
        if (!navigator.onLine || /failed to fetch|networkerror|load failed/i.test(String(err.message || ''))) {
            window.alert('No tienes conexión a internet. Sal del sistema e ingresa nuevamente.');
        }
        return;
    }

    const miembrosCelula = miembrosCelulaActual;
    const duplicadosOcultos = duplicadosOcultosCelula;

    if (miembrosCelula.length === 0) {
        listaMiembros.innerHTML = '<div class="alert alert-warning">Esta célula no tiene miembros activos asignados.</div>';
        botonesAccion.style.display = 'none';
        return;
    }

    const miembrosCasiNoVan = [];
    const miembrosFrecuentes = [];

    const personaEnSeguimiento = (miembro) => {
        let checklist = miembro.Escalera_Checklist;
        if (typeof checklist === 'string' && checklist.trim() !== '') {
            try {
                checklist = JSON.parse(checklist);
            } catch (e) {
                checklist = null;
            }
        }
        const meta = checklist && checklist._meta ? checklist._meta : {};
        if (meta.en_seguimiento) {
            return true;
        }
        return Array.isArray(meta.seguimiento_notas) && meta.seguimiento_notas.length > 0;
    };

    miembrosCelula.forEach(miembro => {
        const idPersona = parseInt(miembro.Id_Persona, 10);
        const totalAsistencias = parseInt(conteoAsistenciasPorPersona[idPersona] || 0, 10);
        const fila = { ...miembro, totalAsistencias };
        if (totalAsistencias >= 3) {
            miembrosFrecuentes.push(fila);
        } else {
            miembrosCasiNoVan.push(fila);
        }
    });

    const construirTablaSeccion = (titulo, miembros) => {
        let bloque = '<div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 14px;">';
        bloque += '<h4 style="margin: 0 0 10px; color: #2d405f;">' + escapeHtml(titulo) + ' (' + miembros.length + ')</h4>';

        if (!miembros.length) {
            bloque += '<p style="margin: 0; color: #6c7a90; font-size: 0.92em;">Sin personas en esta lista.</p>';
            bloque += '</div>';
            return bloque;
        }

        bloque += '<table style="width: 100%;">';
        bloque += '<thead><tr><th style="text-align: left; padding: 8px;">Nombre</th><th style="text-align: center; padding: 8px;">Asistió</th><th style="text-align: center; padding: 8px;">Acción</th></tr></thead>';
        bloque += '<tbody>';

        miembros.forEach(miembro => {
            const nombreCompleto = ((miembro.Nombre || '') + ' ' + (miembro.Apellido || '')).trim();
            const telefono = String(miembro.Telefono || '').trim();
            bloque += '<tr style="border-bottom: 1px solid #dee2e6;">';
            bloque += '<td style="padding: 8px;"><strong>' + escapeHtml(nombreCompleto) + '</strong></td>';
            bloque += '<td style="text-align: center; padding: 8px;">';
            bloque += '<input type="hidden" name="asistencias[' + miembro.Id_Persona + ']" value="0">';
            bloque += '<input type="checkbox" name="asistencias[' + miembro.Id_Persona + ']" value="1" style="width: 20px; height: 20px; cursor: pointer;">';
            bloque += '</td>';
            bloque += '<td style="text-align:center;padding:8px;">';
            bloque += '<div class="asist-acciones">';
            if (puedeUsarSeguimientoReporte) {
                bloque += '<button type="button" class="action-seguimiento-btn js-seguimiento-btn' + (personaEnSeguimiento(miembro) ? ' is-active' : '') + '"';
                bloque += ' data-persona-id="' + miembro.Id_Persona + '"';
                bloque += ' data-persona-nombre="' + escapeHtml(nombreCompleto) + '"';
                bloque += ' data-persona-telefono="' + escapeHtml(telefono) + '"';
                bloque += ' data-puede-editar="' + (puedeGuardarSeguimientoReporte ? '1' : '0') + '"';
                bloque += ' title="En seguimiento" aria-label="En seguimiento">';
                bloque += '<i class="bi bi-signpost-2" aria-hidden="true"></i><span>En seguimiento</span></button>';
            }
            bloque += '<button type="button" class="asist-acc-btn asist-acc-btn--del js-quitar-celula" data-id-persona="' + miembro.Id_Persona + '" data-nombre="' + escapeHtml(nombreCompleto) + '" title="Eliminar de esta célula" aria-label="Eliminar de esta célula">';
            bloque += '<i class="bi bi-trash" aria-hidden="true"></i></button>';
            bloque += '<button type="button" class="asist-acc-btn asist-acc-btn--nd js-no-disponible" data-id-persona="' + miembro.Id_Persona + '" data-nombre="' + escapeHtml(nombreCompleto) + '" title="No se dispuso" aria-label="No se dispuso">';
            bloque += '<i class="bi bi-person-x" aria-hidden="true"></i></button>';
            bloque += '<button type="button" class="asist-acc-btn asist-acc-btn--mig js-migrar-celula" data-id-persona="' + miembro.Id_Persona + '" data-nombre="' + escapeHtml(nombreCompleto) + '" title="Migrar a otra célula del mismo ministerio" aria-label="Migrar a otra célula">';
            bloque += '<i class="bi bi-arrow-left-right" aria-hidden="true"></i></button>';
            bloque += '</div>';
            bloque += '</td>';
            bloque += '</tr>';
        });

        bloque += '</tbody></table>';
        bloque += '</div>';
        return bloque;
    };

    let html = '';
    if (duplicadosOcultos > 0) {
        html += '<div class="alert alert-warning" style="margin-bottom:12px;font-size:0.92em;">';
        html += 'Se detectaron <strong>' + duplicadosOcultos + '</strong> registro(s) duplicado(s) en esta célula ';
        html += '(misma cédula o nombre). Se muestra una sola fila por persona y se suman sus asistencias. ';
        html += 'Conviene unificar esos registros en Personas para evitar confusiones.';
        html += '</div>';
    }
    html += construirTablaSeccion('Casi no van', miembrosCasiNoVan);
    html += construirTablaSeccion('Frecuentes', miembrosFrecuentes);
    html += '<p style="margin-top: 6px; color: #666; font-size: 0.9em;">';
    html += '<strong>Personas únicas:</strong> ' + miembrosCelula.length;
    if (duplicadosOcultos > 0) {
        html += ' (' + duplicadosOcultos + ' registro(s) duplicado(s) oculto(s) por misma cédula)';
    }
    html += ' | <strong>Regla:</strong> con 3 o más reuniones asistidas en esta célula queda en Frecuentes. Con menos, en Casi no van.';
    html += '</p>';
    
    listaMiembros.innerHTML = html;
    if (typeof window.inicializarBotonesSeguimiento === 'function') {
        window.inicializarBotonesSeguimiento(listaMiembros);
    }
    miembrosContainer.style.display = 'block';
    botonesAccion.style.display = 'flex';
    syncNoSeRealizoState();
}

document.addEventListener('click', async function(e) {
    if (e.target.closest('[data-close-no-disponible="1"]')) {
        cerrarModalNoDisponible();
        return;
    }
    if (e.target.closest('[data-close-migrar-celula="1"]')) {
        cerrarModalMigrarCelula();
        return;
    }

    const btnNoDisp = e.target.closest('.js-no-disponible');
    const btnQuitar = e.target.closest('.js-quitar-celula');
    const btnMigrar = e.target.closest('.js-migrar-celula');
    if (!btnNoDisp && !btnQuitar && !btnMigrar) {
        return;
    }

    const btn = btnNoDisp || btnQuitar || btnMigrar;
    const idPersona = parseInt(btn.getAttribute('data-id-persona') || '0', 10);
    const nombre = String(btn.getAttribute('data-nombre') || '');
    if (!idPersona) {
        return;
    }

    if (btnNoDisp) {
        abrirModalNoDisponible({ idPersona: idPersona, nombre: nombre }, btnNoDisp);
        return;
    }

    if (btnMigrar) {
        abrirModalMigrarCelula({ idPersona: idPersona, nombre: nombre }, btnMigrar);
        return;
    }

    if (!window.confirm('¿Eliminar a ' + nombre + ' de esta célula?')) {
        return;
    }

    btnQuitar.disabled = true;
    try {
        const body = new URLSearchParams();
        body.append('id_persona', String(idPersona));
        body.append('id_celula', String(idCelulaActual()));
        const resultado = await postAccionPersona(urlQuitarDeCelula, body);
        if (!resultado.resp.ok || !resultado.data || !resultado.data.success) {
            throw new Error((resultado.data && resultado.data.message) ? resultado.data.message : 'No se pudo eliminar de la célula.');
        }
        quitarFilaPersona(btnQuitar);
    } catch (err) {
        btnQuitar.disabled = false;
        window.alert(err.message || 'Error al eliminar de la célula.');
    }
});

if (btnCancelarNoDisponible) {
    btnCancelarNoDisponible.addEventListener('click', cerrarModalNoDisponible);
}

if (btnCancelarMigrarCelula) {
    btnCancelarMigrarCelula.addEventListener('click', cerrarModalMigrarCelula);
}

if (btnGuardarMigrarCelula) {
    btnGuardarMigrarCelula.addEventListener('click', async function () {
        if (!migrarPendiente) {
            return;
        }
        const destino = migrarCelulaSelect ? parseInt(migrarCelulaSelect.value || '0', 10) : 0;
        if (!destino) {
            window.alert('Selecciona la célula de destino.');
            return;
        }
        const { persona, boton } = migrarPendiente;
        boton.disabled = true;
        btnGuardarMigrarCelula.disabled = true;
        try {
            const body = new URLSearchParams();
            body.append('id_persona', String(persona.idPersona));
            body.append('id_celula', String(idCelulaActual()));
            body.append('id_celula_destino', String(destino));
            const resultado = await postAccionPersona(urlMigrarCelula, body);
            if (!resultado.resp.ok || !resultado.data || !resultado.data.success) {
                throw new Error((resultado.data && resultado.data.message) ? resultado.data.message : 'No se pudo migrar.');
            }
            quitarFilaPersona(boton);
            cerrarModalMigrarCelula();
        } catch (err) {
            boton.disabled = false;
            window.alert(err.message || 'Error al migrar a otra célula.');
        } finally {
            btnGuardarMigrarCelula.disabled = false;
        }
    });
}

if (btnGuardarNoDisponible) {
    btnGuardarNoDisponible.addEventListener('click', async function() {
        if (!noDisponiblePendiente) {
            return;
        }

        const { persona, boton } = noDisponiblePendiente;
        const observacionLimpia = String((modalNoDisponibleObservacion && modalNoDisponibleObservacion.value) || '').trim();

        if (!observacionLimpia) {
            window.alert('Debes escribir una observación.');
            if (modalNoDisponibleObservacion) {
                modalNoDisponibleObservacion.focus();
            }
            return;
        }

        boton.disabled = true;
        btnGuardarNoDisponible.disabled = true;

        try {
            const body = new URLSearchParams();
            body.append('id_persona', String(persona.idPersona));
            body.append('observacion', observacionLimpia);

            const resultado = await postAccionPersona(urlMarcarNoDisponible, body);
            if (!resultado.resp.ok || !resultado.data || !resultado.data.success) {
                throw new Error((resultado.data && resultado.data.message) ? resultado.data.message : 'No fue posible marcar No se dispuso.');
            }

            quitarFilaPersona(boton);
            cerrarModalNoDisponible();
        } catch (err) {
            boton.disabled = false;
            if (!navigator.onLine || /failed to fetch|networkerror|load failed/i.test(String(err.message || ''))) {
                window.alert('No tienes conexión a internet. Sal del sistema e ingresa nuevamente.');
            } else {
                window.alert(err.message || 'Error al marcar No se dispuso.');
            }
        } finally {
            btnGuardarNoDisponible.disabled = false;
        }
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') {
        return;
    }
    if (modalNoDisponible && modalNoDisponible.classList.contains('is-open')) {
        cerrarModalNoDisponible();
    }
    if (modalMigrarCelula && modalMigrarCelula.classList.contains('is-open')) {
        cerrarModalMigrarCelula();
    }
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

if (celulaPreseleccionada) {
    const celulaInicial = celulasDisponibles.find(c => parseInt(c.Id_Celula, 10) === parseInt(celulaPreseleccionada, 10));
    if (celulaInicial) {
        celulaInput.value = celulaInicial.Nombre_Celula;
        celulaHidden.value = celulaInicial.Id_Celula;
        actualizarEnlacePersonaNueva(celulaInicial.Id_Celula);
        cargarMiembrosCelula(celulaInicial.Id_Celula);
    }
}

const MSG_SIN_CONEXION = 'No tienes conexión a internet. Sal del sistema e ingresa nuevamente.';
const MSG_ERROR_REPORTE = 'Ocurrió un error y no se pudo reportar la célula. Inténtalo de nuevo.';
const urlVolverAsistencias = <?= json_encode($volverUrlAsistencias, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function hayConexionInternet() {
    return navigator.onLine !== false;
}

function esErrorDeConexion(err) {
    if (!hayConexionInternet()) {
        return true;
    }
    const msg = String((err && err.message) || err || '').toLowerCase();
    return msg.indexOf('failed to fetch') !== -1
        || msg.indexOf('networkerror') !== -1
        || msg.indexOf('load failed') !== -1
        || msg.indexOf('network request failed') !== -1;
}

window.addEventListener('offline', function () {
    window.alert(MSG_SIN_CONEXION);
});

const formAsistencia = document.getElementById('formAsistencia');
if (formAsistencia) {
    formAsistencia.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (!hayConexionInternet()) {
            window.alert(MSG_SIN_CONEXION);
            return;
        }

        const idCelula = String((celulaHidden && celulaHidden.value) || '').trim();
        if (!idCelula) {
            window.alert('Selecciona una célula para reportar.');
            return;
        }

        if (noSeRealizoCheck && noSeRealizoCheck.checked) {
            const motivoElegido = motivoNoRealizoRadios.find((radio) => radio.checked);
            if (!motivoElegido) {
                window.alert('Indica por qué no se realizó la célula.');
                return;
            }
            if (motivoElegido.value === 'otro' && !(motivoNoRealizoOtro && motivoNoRealizoOtro.value.trim())) {
                window.alert('Escribe el motivo en Otro.');
                if (motivoNoRealizoOtro) {
                    motivoNoRealizoOtro.focus();
                }
                return;
            }
        }

        const btn = formAsistencia.querySelector('button[type="submit"]');
        if (btn) {
            btn.disabled = true;
            btn.dataset.textoOriginal = btn.textContent;
            btn.textContent = 'Reportando...';
        }

        try {
            const resp = await fetch(window.location.href, {
                method: 'POST',
                body: new FormData(formAsistencia),
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            let data = null;
            try {
                data = await resp.json();
            } catch (parseErr) {
                throw new Error(MSG_ERROR_REPORTE);
            }

            if (!resp.ok || !data || !data.ok) {
                throw new Error((data && data.message) ? data.message : MSG_ERROR_REPORTE);
            }

            window.alert(data.message || ('Célula reportada con éxito. Fecha y hora: ' + (data.fecha_hora || '')));
            window.location.href = data.redirect || urlVolverAsistencias;
        } catch (err) {
            if (esErrorDeConexion(err)) {
                window.alert(MSG_SIN_CONEXION);
            } else {
                window.alert(err.message || MSG_ERROR_REPORTE);
            }
            if (btn) {
                btn.disabled = false;
                btn.textContent = btn.dataset.textoOriginal || 'Guardar Asistencias';
            }
        }
    });
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
