<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Registrar Asistencia</h2>
    <div class="page-actions">
        <a id="persona_nueva_link" href="<?= PUBLIC_URL ?>index.php?url=personas/crear&return_to=asistencia<?= !empty($celula_preseleccionada) ? '&celula=' . (int)$celula_preseleccionada : '' ?>" class="btn btn-primary">Persona Nueva</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=asistencias" class="btn btn-secondary">Volver</a>
    </div>
</div>

<div class="form-container" style="max-width: 800px;">
    <form method="POST" id="formAsistencia">
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
            <input type="date" id="fecha" name="fecha" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="form-group">
            <label for="tema">Tema</label>
            <input type="text" id="tema" name="tema" class="form-control">
        </div>

        <div class="form-group">
            <label for="ofrenda">Ofrenda (COP)</label>
            <input type="number" id="ofrenda" name="ofrenda" class="form-control" min="0" step="1" inputmode="numeric">
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
            <a href="<?= PUBLIC_URL ?>index.php?url=asistencias" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
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
</style>

<script>
// Datos de células desde PHP
const celulasDisponibles = <?= json_encode($celulas ?? []) ?>;

// Datos de personas desde PHP
const personas = <?= json_encode($personas ?? []) ?>;

// Célula preseleccionada (opcional)
const celulaPreseleccionada = <?= json_encode($celula_preseleccionada ?? null) ?>;

// Autocompletar para célula
const celulaInput = document.getElementById('celula_search');
const celulaHidden = document.getElementById('id_celula');
const celulaAutocomplete = document.getElementById('celula_autocomplete');
const personaNuevaLink = document.getElementById('persona_nueva_link');
let currentFocus = -1;

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

    // Generar lista de checkboxes para cada miembro
    let html = '<div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">';
    html += '<table style="width: 100%;">';
    html += '<thead><tr><th style="text-align: left; padding: 10px;">Nombre</th><th style="text-align: center; padding: 10px;">Asistió</th></tr></thead>';
    html += '<tbody>';
    
    miembrosCelula.forEach(miembro => {
        html += '<tr style="border-bottom: 1px solid #dee2e6;">';
        html += '<td style="padding: 10px;">';
        html += '<strong>' + escapeHtml(miembro.Nombre + ' ' + miembro.Apellido) + '</strong>';
        html += '</td>';
        html += '<td style="text-align: center; padding: 10px;">';
        // Campo hidden para enviar siempre el ID (valor 0 = no asistió)
        html += '<input type="hidden" name="asistencias[' + miembro.Id_Persona + ']" value="0">';
        // Checkbox que sobrescribe con 1 si está marcado
        html += '<input type="checkbox" name="asistencias[' + miembro.Id_Persona + ']" value="1" ';
        html += 'style="width: 20px; height: 20px; cursor: pointer;">';
        html += '</td>';
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    html += '<p style="margin-top: 15px; color: #666; font-size: 0.9em;">';
    html += '<strong>Total de miembros:</strong> ' + miembrosCelula.length;
    html += '</p></div>';
    
    listaMiembros.innerHTML = html;
    miembrosContainer.style.display = 'block';
    botonesAccion.style.display = 'flex';
}

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
