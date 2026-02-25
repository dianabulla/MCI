<?php include VIEWS . '/layout/header.php'; ?>

<?php
$returnToAsistencia = ($return_to ?? '') === 'asistencia';
$returnToCelulas = ($return_to ?? '') === 'celulas';
$urlVolver = $returnToAsistencia
    ? (PUBLIC_URL . '?url=asistencias/registrar' . (!empty($celula_retorno) ? '&celula=' . (int)$celula_retorno : ''))
    : ($returnToCelulas ? (PUBLIC_URL . '?url=celulas') : (PUBLIC_URL . '?url=personas'));
?>

<div class="page-header">
    <h2><?= isset($persona) ? 'Editar' : 'Nueva' ?> Persona</h2>
    <a href="<?= $urlVolver ?>" class="btn btn-secondary">Volver</a>
</div>

<?php if (isset($error)): ?>
<div class="alert alert-danger" style="margin-bottom: 20px;">
    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
</div>
<?php endif; ?>

<div class="form-container">
    <form method="POST">
        <?php if ($returnToAsistencia): ?>
        <input type="hidden" name="return_to" value="asistencia">
        <input type="hidden" name="celula_retorno" value="<?= (int)($celula_retorno ?? 0) ?>">
        <?php elseif ($returnToCelulas): ?>
        <input type="hidden" name="return_to" value="celulas">
        <?php endif; ?>

        <!-- Sección: Información Personal -->
        <div class="form-section">
            <h3 class="section-title">📋 Información Personal</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="nombre">Nombre *</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" 
                           value="<?= htmlspecialchars($post_data['nombre'] ?? $persona['Nombre'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="apellido">Apellido *</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" 
                           value="<?= htmlspecialchars($post_data['apellido'] ?? $persona['Apellido'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_documento">Tipo de Documento</label>
                    <select id="tipo_documento" name="tipo_documento" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Registro Civil" <?= isset($persona) && $persona['Tipo_Documento'] == 'Registro Civil' ? 'selected' : '' ?>>Registro Civil</option>
                        <option value="Cedula de Ciudadania" <?= isset($persona) && $persona['Tipo_Documento'] == 'Cedula de Ciudadania' ? 'selected' : '' ?>>Cédula de Ciudadanía</option>
                        <option value="Cedula Extranjera" <?= isset($persona) && $persona['Tipo_Documento'] == 'Cedula Extranjera' ? 'selected' : '' ?>>Cédula Extranjera</option>
                        <option value="Tarjeta de Identidad" <?= isset($persona) && $persona['Tipo_Documento'] == 'Tarjeta de Identidad' ? 'selected' : '' ?>>Tarjeta de Identidad</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="numero_documento">Número de Documento</label>
                    <input type="text" id="numero_documento" name="numero_documento" class="form-control" 
                           value="<?= htmlspecialchars($persona['Numero_Documento'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                    <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" 
                           value="<?= htmlspecialchars($persona['Fecha_Nacimiento'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="edad">Edad</label>
                    <input type="number" id="edad" name="edad" class="form-control" min="0" max="120"
                           value="<?= htmlspecialchars($persona['Edad'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="genero">Género</label>
                    <select id="genero" name="genero" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Hombre" <?= isset($persona) && $persona['Genero'] == 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                        <option value="Mujer" <?= isset($persona) && $persona['Genero'] == 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                        <option value="Joven Hombre" <?= isset($persona) && $persona['Genero'] == 'Joven Hombre' ? 'selected' : '' ?>>Joven Hombre</option>
                        <option value="Joven Mujer" <?= isset($persona) && $persona['Genero'] == 'Joven Mujer' ? 'selected' : '' ?>>Joven Mujer</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sección: Información de Contacto -->
        <div class="form-section">
            <h3 class="section-title">📞 Información de Contacto</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="telefono">Teléfono</label>
                    <input type="tel" id="telefono" name="telefono" class="form-control" 
                           value="<?= htmlspecialchars($persona['Telefono'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?= htmlspecialchars($persona['Email'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="direccion">Dirección</label>
                    <input type="text" id="direccion" name="direccion" class="form-control" 
                           value="<?= htmlspecialchars($persona['Direccion'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="barrio">Barrio</label>
                    <input type="text" id="barrio" name="barrio" class="form-control" 
                           value="<?= htmlspecialchars($persona['Barrio'] ?? '') ?>">
                </div>
            </div>
        </div>

        <!-- Sección: Información Ministerial -->
        <div class="form-section">
            <h3 class="section-title">⛪ Información Ministerial</h3>
            
            <div class="form-row">
                <div class="form-group autocomplete-wrapper">
                    <label for="celula_search">Célula</label>
                    <input type="text" id="celula_search" class="form-control"
                           placeholder="Buscar célula..."
                           value="<?= isset($persona) && $persona['Id_Celula'] ? htmlspecialchars($persona['Nombre_Celula'] ?? '') : '' ?>"
                           autocomplete="off">
                    <input type="hidden" id="id_celula" name="id_celula"
                           value="<?= isset($persona) && $persona['Id_Celula'] ? $persona['Id_Celula'] : '' ?>">
                    <div id="celula_autocomplete" class="autocomplete-items"></div>
                    <small class="form-text text-muted">Escriba el nombre de la célula para buscarla</small>
                </div>

                <div class="form-group">
                    <label for="id_rol">Rol</label>
                    <select id="id_rol" name="id_rol" class="form-control">
                        <option value="">Sin rol</option>
                        <?php if (!empty($roles)): ?>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['Id_Rol'] ?>" 
                                        <?= isset($persona) && $persona['Id_Rol'] == $rol['Id_Rol'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($rol['Nombre_Rol']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_ministerio">Ministerio</label>
                    <select id="id_ministerio" name="id_ministerio" class="form-control">
                        <option value="">Sin ministerio</option>
                        <?php if (!empty($ministerios)): ?>
                            <?php foreach ($ministerios as $ministerio): ?>
                                <option value="<?= $ministerio['Id_Ministerio'] ?>" 
                                        <?= isset($persona) && $persona['Id_Ministerio'] == $ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group autocomplete-wrapper">
                    <label for="lider_search">Líder Asignado</label>
                    <input type="text" id="lider_search" class="form-control" 
                           placeholder="Buscar líder..."
                           value="<?= isset($persona) && $persona['Id_Lider'] ? htmlspecialchars($persona['Nombre_Lider'] ?? '') : '' ?>"
                           autocomplete="off">
                    <input type="hidden" id="id_lider" name="id_lider" 
                           value="<?= isset($persona) && $persona['Id_Lider'] ? $persona['Id_Lider'] : '' ?>">
                    <div id="lider_autocomplete" class="autocomplete-items"></div>
                    <small class="form-text text-muted">Escriba el nombre del líder para buscarlo</small>
                </div>

                <div class="form-group">
                    <label for="invitado_por">Invitado Por</label>
                    <input type="text" id="invitado_por" name="invitado_por" class="form-control" 
                           value="<?= htmlspecialchars($persona['Invitado_Por'] ?? '') ?>"
                           placeholder="Nombre de quien lo invitó">
                </div>

                <div class="form-group">
                    <label for="tipo_reunion">Primera Reunión</label>
                    <select id="tipo_reunion" name="tipo_reunion" class="form-control">
                        <option value="">Seleccionar...</option>
                        <option value="Domingo" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Domingo' ? 'selected' : '' ?>>Domingo</option>
                        <option value="Celula" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Celula' ? 'selected' : '' ?>>Célula</option>
                        <option value="Reu Jovenes" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Reu Jovenes' ? 'selected' : '' ?>>Reunión Jóvenes</option>
                        <option value="Reu Hombre" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Reu Hombre' ? 'selected' : '' ?>>Reunión Hombre</option>
                        <option value="Reu Mujeres" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Reu Mujeres' ? 'selected' : '' ?>>Reunión Mujeres</option>
                        <option value="Grupo Go" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Grupo Go' ? 'selected' : '' ?>>Grupo Go</option>
                        <option value="Seminario" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Seminario' ? 'selected' : '' ?>>Seminario</option>
                        <option value="Pesca" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Pesca' ? 'selected' : '' ?>>Pesca</option>
                        <option value="Semana Santa" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Semana Santa' ? 'selected' : '' ?>>Semana Santa</option>
                        <option value="Otro" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                    </select>
                </div>

                <?php if (AuthController::esAdministrador()): ?>
                <div class="form-group">
                    <label for="estado_cuenta">Estado de Cuenta</label>
                    <select id="estado_cuenta" name="estado_cuenta" class="form-control">
                        <?php $estadoCuentaSeleccionado = $post_data['estado_cuenta'] ?? ($persona['Estado_Cuenta'] ?? 'Activo'); ?>
                        <option value="Activo" <?= $estadoCuentaSeleccionado == 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= $estadoCuentaSeleccionado == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="Bloqueado" <?= $estadoCuentaSeleccionado == 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                    </select>
                    <small class="form-text text-muted">
                        Solo las cuentas activas pueden iniciar sesión
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sección: Petición de Oración -->
        <div class="form-section">
            <h3 class="section-title">🙏 Petición de Oración</h3>
            <div class="form-group">
                <label for="peticion">Petición</label>
                <textarea id="peticion" name="peticion" class="form-control" rows="4" placeholder="Escriba aquí la petición de oración..."><?= htmlspecialchars($persona['Peticion'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- Acceso al Sistema - Solo Administradores -->
        <?php if (AuthController::esAdministrador()): ?>
        <div class="form-section" id="acceso_sistema_section">
            <h3 class="section-title">🔐 Acceso al Sistema</h3>
            <div id="acceso_sistema_alerta" class="alert alert-warning" style="display:none; margin-bottom: 15px;">
                El acceso al sistema no está disponible para personas con rol Asistente.
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control" 
                           value="<?= htmlspecialchars($persona['Usuario'] ?? '') ?>"
                           placeholder="Dejar vacío si no tendrá acceso al sistema">
                    <small class="form-text text-muted">
                        Si asigna un usuario, la persona podrá iniciar sesión en el sistema
                    </small>
                </div>

                <div class="form-group">
                    <label for="contrasena">
                        Contraseña <?= isset($persona) ? '(Dejar vacío para mantener la actual)' : '' ?>
                    </label>
                    <input type="password" id="contrasena" name="contrasena" class="form-control" 
                           placeholder="<?= isset($persona) ? 'Solo llenar si desea cambiar la contraseña' : 'Contraseña para acceso' ?>">
                    <small class="form-text text-muted">
                        Mínimo 6 caracteres
                    </small>
                </div>
            </div>

            <?php if (isset($persona) && !empty($persona['Ultimo_Acceso'])): ?>
            <div class="alert alert-info" style="margin-top: 15px;">
                <i class="bi bi-clock-history"></i> 
                <strong>Último acceso:</strong> 
                <?= date('d/m/Y H:i:s', strtotime($persona['Ultimo_Acceso'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= $urlVolver ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.form-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.section-title {
    font-size: 1.3rem;
    font-weight: 600;
    color: #667eea;
    margin: 0 0 20px 0;
    padding-bottom: 12px;
    border-bottom: 2px solid #f0f0f0;
}

.form-section .form-row {
    margin-bottom: 0;
}

.form-section .form-group {
    margin-bottom: 20px;
}

/* Autocompletar */
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
// Lista de células disponibles
const celulasDisponibles = [
    <?php if (!empty($celulas)): ?>
        <?php foreach ($celulas as $index => $celula): ?>
            {
                id: <?= $celula['Id_Celula'] ?>,
                nombre: "<?= htmlspecialchars($celula['Nombre_Celula'], ENT_QUOTES) ?>"
            }<?= $index < count($celulas) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    <?php endif; ?>
];

// Lista de líderes disponibles
const lideresDisponibles = [
    <?php if (!empty($personas_lideres)): ?>
        <?php foreach ($personas_lideres as $index => $lider): ?>
            {
                id: <?= $lider['Id_Persona'] ?>,
                nombre: "<?= htmlspecialchars($lider['Nombre'] . ' ' . $lider['Apellido'], ENT_QUOTES) ?>"
            }<?= $index < count($personas_lideres) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    <?php endif; ?>
];

// Control de acceso al sistema según rol seleccionado
const rolSelect = document.getElementById('id_rol');
const accesoSistemaSection = document.getElementById('acceso_sistema_section');
const accesoSistemaAlerta = document.getElementById('acceso_sistema_alerta');

function normalizarTexto(texto) {
    if (!texto) return '';
    return texto
        .toString()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
}

function rolSeleccionadoEsAsistente() {
    if (!rolSelect || rolSelect.selectedIndex < 0) {
        return false;
    }

    const option = rolSelect.options[rolSelect.selectedIndex];
    const textoRol = normalizarTexto(option ? option.text : '');
    return textoRol.includes('asistente');
}

function actualizarAccesoSistemaPorRol() {
    if (!accesoSistemaSection) {
        return;
    }

    const esAsistente = rolSeleccionadoEsAsistente();
    const rolAsignado = rolSelect && rolSelect.value !== '';
    const camposAcceso = [
        document.getElementById('usuario'),
        document.getElementById('contrasena')
    ].filter(Boolean);

    if (!rolAsignado || esAsistente) {
        accesoSistemaSection.style.display = 'none';
        if (accesoSistemaAlerta) {
            accesoSistemaAlerta.textContent = !rolAsignado
                ? 'Asigne un rol para habilitar el acceso al sistema.'
                : 'El acceso al sistema no está disponible para personas con rol Asistente.';
            accesoSistemaAlerta.style.display = 'block';
        }

        camposAcceso.forEach(campo => {
            campo.disabled = true;
            if (campo.id === 'usuario' || campo.id === 'contrasena') {
                campo.value = '';
            }
        });
        return;
    }

    accesoSistemaSection.style.display = 'block';
    if (accesoSistemaAlerta) {
        accesoSistemaAlerta.style.display = 'none';
    }
    camposAcceso.forEach(campo => {
        campo.disabled = false;
    });
}

if (rolSelect) {
    rolSelect.addEventListener('change', actualizarAccesoSistemaPorRol);
    actualizarAccesoSistemaPorRol();
}

// Autocompletar para célula
const celulaInput = document.getElementById('celula_search');
const celulaHidden = document.getElementById('id_celula');
const celulaAutocomplete = document.getElementById('celula_autocomplete');
let currentFocusCelula = -1;

celulaInput.addEventListener('input', function() {
    const value = this.value;
    closeAllLists();
    
    if (!value) {
        celulaHidden.value = '';
        return false;
    }
    
    currentFocusCelula = -1;
    
    const filtrados = celulasDisponibles.filter(celula => 
        celula.nombre.toLowerCase().includes(value.toLowerCase())
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
        const nombre = celula.nombre;
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
            celulaHidden.value = celula.id;
            closeAllLists();
        });
        
        celulaAutocomplete.appendChild(div);
    });
});

celulaInput.addEventListener('keydown', function(e) {
    let items = celulaAutocomplete.getElementsByTagName('div');
    
    if (e.keyCode === 40) { // Down
        currentFocusCelula++;
        addActive(items, currentFocusCelula);
    } else if (e.keyCode === 38) { // Up
        currentFocusCelula--;
        addActive(items, currentFocusCelula);
    } else if (e.keyCode === 13) { // Enter
        e.preventDefault();
        if (currentFocusCelula > -1) {
            if (items) items[currentFocusCelula].click();
        }
    }
});

celulaInput.addEventListener('blur', function() {
    setTimeout(() => {
        const selectedCelula = celulasDisponibles.find(c => c.nombre === this.value);
        if (!selectedCelula && this.value) {
            this.value = '';
            celulaHidden.value = '';
        }
    }, 200);
});

// Autocompletar para líder
const liderInput = document.getElementById('lider_search');
const liderHidden = document.getElementById('id_lider');
const liderAutocomplete = document.getElementById('lider_autocomplete');
let currentFocus = -1;

liderInput.addEventListener('input', function() {
    const value = this.value;
    closeAllLists();
    
    if (!value) {
        liderHidden.value = '';
        return false;
    }
    
    currentFocus = -1;
    
    const filtrados = lideresDisponibles.filter(lider => 
        lider.nombre.toLowerCase().includes(value.toLowerCase())
    );
    
    if (filtrados.length === 0) {
        const div = document.createElement('div');
        div.innerHTML = '<em>No se encontraron líderes</em>';
        div.style.fontStyle = 'italic';
        div.style.color = '#999';
        liderAutocomplete.appendChild(div);
        return;
    }
    
    filtrados.forEach(lider => {
        const div = document.createElement('div');
        const nombre = lider.nombre;
        const index = nombre.toLowerCase().indexOf(value.toLowerCase());
        
        if (index >= 0) {
            div.innerHTML = nombre.substr(0, index) + 
                          '<strong>' + nombre.substr(index, value.length) + '</strong>' + 
                          nombre.substr(index + value.length);
        } else {
            div.innerHTML = nombre;
        }
        
        div.addEventListener('click', function() {
            liderInput.value = nombre;
            liderHidden.value = lider.id;
            closeAllLists();
        });
        
        liderAutocomplete.appendChild(div);
    });
});

liderInput.addEventListener('keydown', function(e) {
    let items = liderAutocomplete.getElementsByTagName('div');
    
    if (e.keyCode === 40) { // Down
        currentFocus++;
        addActive(items, currentFocus);
    } else if (e.keyCode === 38) { // Up
        currentFocus--;
        addActive(items, currentFocus);
    } else if (e.keyCode === 13) { // Enter
        e.preventDefault();
        if (currentFocus > -1) {
            if (items) items[currentFocus].click();
        }
    }
});

function addActive(items, focusIndex) {
    if (!items) return false;
    removeActive(items);
    if (focusIndex >= items.length) focusIndex = 0;
    if (focusIndex < 0) focusIndex = (items.length - 1);
    items[focusIndex].classList.add('autocomplete-active');
}

function removeActive(items) {
    for (let i = 0; i < items.length; i++) {
        items[i].classList.remove('autocomplete-active');
    }
}

function closeAllLists(elmnt) {
    const items = document.getElementsByClassName('autocomplete-items');
    for (let i = 0; i < items.length; i++) {
        if (elmnt !== items[i] && elmnt !== liderInput && elmnt !== celulaInput) {
            items[i].innerHTML = '';
        }
    }
}

// Cerrar al hacer click fuera
document.addEventListener('click', function(e) {
    if (e.target !== liderInput && e.target !== celulaInput) {
        closeAllLists(e.target);
    }
});

// Limpiar campo hidden si se borra el input
liderInput.addEventListener('blur', function() {
    setTimeout(() => {
        const selectedLider = lideresDisponibles.find(l => l.nombre === this.value);
        if (!selectedLider && this.value) {
            // Si escribió algo pero no seleccionó, limpiar
            this.value = '';
            liderHidden.value = '';
        }
    }, 200);
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>