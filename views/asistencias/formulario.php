<?php include VIEWS . '/layout/header.php'; ?>

<?php $volverUrlAsistencias = (string)($return_url ?? (PUBLIC_URL . '?url=asistencias')); ?>

<div class="page-header">
    <h2>Registrar Asistencia</h2>
    <div class="page-actions">
        <a id="persona_nueva_link" href="<?= PUBLIC_URL ?>index.php?url=personas/crear&return_to=asistencia<?= !empty($celula_preseleccionada) ? '&celula=' . (int)$celula_preseleccionada : '' ?>" class="btn btn-primary">Persona Nueva</a>
        <a href="<?= htmlspecialchars($volverUrlAsistencias) ?>" class="btn btn-secondary">Volver</a>
    </div>
</div>

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
</style>

<script>
// Datos de células desde PHP
const celulasDisponibles = <?= json_encode($celulas ?? []) ?>;

// Datos de personas desde PHP
const personas = <?= json_encode($personas ?? []) ?>;

// Conteo de asistencias completas por persona (Asistio = 1)
const conteoAsistenciasPorPersona = <?= json_encode($conteo_asistencias_por_persona ?? []) ?>;

// Célula preseleccionada (opcional)
const celulaPreseleccionada = <?= json_encode($celula_preseleccionada ?? null) ?>;

// Autocompletar para célula
const celulaInput = document.getElementById('celula_search');
const celulaHidden = document.getElementById('id_celula');
const celulaAutocomplete = document.getElementById('celula_autocomplete');
const personaNuevaLink = document.getElementById('persona_nueva_link');
const noSeRealizoCheck = document.getElementById('no_se_realizo');
const observacionesInput = document.getElementById('observaciones');
const urlMarcarNoDisponible = '<?= PUBLIC_URL ?>?url=asistencias/marcarNoDisponible';
const modalNoDisponible = document.getElementById('modalNoDisponible');
const modalNoDisponiblePersona = document.getElementById('noDisponiblePersona');
const modalNoDisponibleObservacion = document.getElementById('noDisponibleObservacion');
const btnGuardarNoDisponible = document.getElementById('btnGuardarNoDisponible');
const btnCancelarNoDisponible = document.getElementById('btnCancelarNoDisponible');
let currentFocus = -1;
let noDisponiblePendiente = null;

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

    if (observacionesInput) {
        const valor = observacionesInput.value.trim();
        if (bloqueado && valor === '') {
            observacionesInput.value = 'No se realizó';
        } else if (!bloqueado && valor === 'No se realizó') {
            observacionesInput.value = '';
        }
    }
}

if (noSeRealizoCheck) {
    noSeRealizoCheck.addEventListener('change', syncNoSeRealizoState);
}

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

function cargarMiembrosCelula(celulaId) {
    const miembrosContainer = document.getElementById('miembros-container');
    const listaMiembros = document.getElementById('lista-miembros');
    const botonesAccion = document.getElementById('botones-accion');
    
    if (!celulaId) {
        miembrosContainer.style.display = 'none';
        botonesAccion.style.display = 'none';
        return;
    }

    const celulaIdNum = parseInt(celulaId, 10);

    // Filtrar personas que pertenecen a esta célula
    const miembrosCelula = personas.filter(p => parseInt(p.Id_Celula, 10) === celulaIdNum);
    
    if (miembrosCelula.length === 0) {
        listaMiembros.innerHTML = '<div class="alert alert-warning">Esta célula no tiene miembros asignados.</div>';
        miembrosContainer.style.display = 'block';
        botonesAccion.style.display = 'none';
        return;
    }

    const miembrosNuevos = [];
    const miembrosFrecuentes = [];

    miembrosCelula.forEach(miembro => {
        const idPersona = parseInt(miembro.Id_Persona, 10);
        const totalAsistencias = parseInt(conteoAsistenciasPorPersona[idPersona] || 0, 10);
        if (totalAsistencias >= 3) {
            miembrosFrecuentes.push({ ...miembro, totalAsistencias });
        } else {
            miembrosNuevos.push({ ...miembro, totalAsistencias });
        }
    });

    const construirTablaSeccion = (titulo, miembros, tipo) => {
        let bloque = '<div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 14px;">';
        bloque += '<h4 style="margin: 0 0 10px; color: #2d405f;">' + escapeHtml(titulo) + ' (' + miembros.length + ')</h4>';

        if (!miembros.length) {
            bloque += '<p style="margin: 0; color: #6c7a90; font-size: 0.92em;">Sin personas en esta lista.</p>';
            bloque += '</div>';
            return bloque;
        }

        bloque += '<table style="width: 100%;">';
        bloque += '<thead><tr><th style="text-align: left; padding: 8px;">Nombre</th><th style="text-align: left; padding: 8px;">Estado</th><th style="text-align: center; padding: 8px;">Asistió</th><th style="text-align: center; padding: 8px;">Acción</th></tr></thead>';
        bloque += '<tbody>';

        miembros.forEach(miembro => {
            const nombreCompleto = (miembro.Nombre || '') + ' ' + (miembro.Apellido || '');
            const estado = tipo === 'nuevo'
                ? ('Nuevo (' + (miembro.totalAsistencias || 0) + '/3)')
                : ('Frecuente (' + (miembro.totalAsistencias || 0) + ')');

            bloque += '<tr style="border-bottom: 1px solid #dee2e6;">';
            bloque += '<td style="padding: 8px;"><strong>' + escapeHtml(nombreCompleto.trim()) + '</strong></td>';
            bloque += '<td style="padding: 8px; color: #5a6b86;">' + escapeHtml(estado) + '</td>';
            bloque += '<td style="text-align: center; padding: 8px;">';
            bloque += '<input type="hidden" name="asistencias[' + miembro.Id_Persona + ']" value="0">';
            bloque += '<input type="checkbox" name="asistencias[' + miembro.Id_Persona + ']" value="1" style="width: 20px; height: 20px; cursor: pointer;">';
            bloque += '</td>';
            bloque += '<td style="text-align: center; padding: 8px;">';
            bloque += '<button type="button" class="btn btn-sm btn-danger js-no-disponible" data-id-persona="' + miembro.Id_Persona + '" data-nombre="' + escapeHtml(nombreCompleto.trim()) + '">No se dispuso</button>';
            bloque += '</td>';
            bloque += '</tr>';
        });

        bloque += '</tbody></table>';
        bloque += '</div>';
        return bloque;
    };

    let html = '';
    html += construirTablaSeccion('Asistentes nuevos', miembrosNuevos, 'nuevo');
    html += construirTablaSeccion('Asistentes frecuentes', miembrosFrecuentes, 'frecuente');
    html += '<p style="margin-top: 6px; color: #666; font-size: 0.9em;">';
    html += '<strong>Total de miembros:</strong> ' + miembrosCelula.length + ' | '; 
    html += '<strong>Regla:</strong> al completar 3 asistencias, pasa de Nuevo a Frecuente.';
    html += '</p>';
    
    listaMiembros.innerHTML = html;
    miembrosContainer.style.display = 'block';
    botonesAccion.style.display = 'flex';
    syncNoSeRealizoState();
}

document.addEventListener('click', async function(e) {
    const btn = e.target.closest('.js-no-disponible');
    if (!btn) {
        const closeTrigger = e.target.closest('[data-close-no-disponible="1"]');
        if (closeTrigger) {
            cerrarModalNoDisponible();
        }
        return;
    }

    const idPersona = parseInt(btn.getAttribute('data-id-persona') || '0', 10);
    const nombre = String(btn.getAttribute('data-nombre') || '');
    if (!idPersona) {
        return;
    }

    abrirModalNoDisponible({ idPersona, nombre }, btn);
});

if (btnCancelarNoDisponible) {
    btnCancelarNoDisponible.addEventListener('click', cerrarModalNoDisponible);
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
        const textoOriginal = boton.textContent;
        boton.textContent = 'Guardando...';
        btnGuardarNoDisponible.disabled = true;

        try {
            const body = new URLSearchParams();
            body.append('id_persona', String(persona.idPersona));
            body.append('observacion', observacionLimpia);

            const resp = await fetch(urlMarcarNoDisponible, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body.toString()
            });

            const data = await resp.json();
            if (!resp.ok || !data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'No fue posible marcar No se dispuso.');
            }

            const fila = boton.closest('tr');
            if (fila) {
                fila.style.opacity = '0.55';
                fila.style.backgroundColor = '#fff1f2';
                setTimeout(() => {
                    fila.remove();
                }, 180);
            }

            boton.textContent = 'Marcado';
            boton.classList.remove('btn-danger');
            boton.classList.add('btn-secondary');
            cerrarModalNoDisponible();
            window.alert('Persona marcada en No se dispone.');
        } catch (err) {
            boton.disabled = false;
            boton.textContent = textoOriginal;
            window.alert(err.message || 'Error al marcar No se dispuso.');
        } finally {
            btnGuardarNoDisponible.disabled = false;
        }
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && modalNoDisponible && modalNoDisponible.classList.contains('is-open')) {
        cerrarModalNoDisponible();
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
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
