<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><?= isset($celula) ? 'Editar' : 'Nueva' ?> Célula</h2>
    <a href="<?= PUBLIC_URL ?>index.php?url=celulas" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-group">
            <label for="nombre_celula">Nombre de la Célula</label>
            <input type="text" id="nombre_celula" name="nombre_celula" class="form-control" 
                   value="<?= htmlspecialchars($celula['Nombre_Celula'] ?? '') ?>" required readonly
                   placeholder="Se llena automáticamente">
            <small class="form-text text-muted">Este campo se llena automáticamente con el líder y el anfitrión.</small>
        </div>

        <div class="form-group">
            <label for="direccion_celula">Dirección</label>
            <input type="text" id="direccion_celula" name="direccion_celula" class="form-control" 
                   value="<?= htmlspecialchars($celula['Direccion_Celula'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="dia_reunion">Día de Reunión</label>
            <select id="dia_reunion" name="dia_reunion" class="form-control" required>
                <option value="">Seleccione...</option>
                <option value="Lunes" <?= isset($celula) && $celula['Dia_Reunion'] == 'Lunes' ? 'selected' : '' ?>>Lunes</option>
                <option value="Martes" <?= isset($celula) && $celula['Dia_Reunion'] == 'Martes' ? 'selected' : '' ?>>Martes</option>
                <option value="Miércoles" <?= isset($celula) && $celula['Dia_Reunion'] == 'Miércoles' ? 'selected' : '' ?>>Miércoles</option>
                <option value="Jueves" <?= isset($celula) && $celula['Dia_Reunion'] == 'Jueves' ? 'selected' : '' ?>>Jueves</option>
                <option value="Viernes" <?= isset($celula) && $celula['Dia_Reunion'] == 'Viernes' ? 'selected' : '' ?>>Viernes</option>
                <option value="Sábado" <?= isset($celula) && $celula['Dia_Reunion'] == 'Sábado' ? 'selected' : '' ?>>Sábado</option>
                <option value="Domingo" <?= isset($celula) && $celula['Dia_Reunion'] == 'Domingo' ? 'selected' : '' ?>>Domingo</option>
            </select>
        </div>

        <div class="form-group">
            <label for="hora_reunion">Hora de Reunión</label>
            <input type="time" id="hora_reunion" name="hora_reunion" class="form-control" 
                   value="<?= htmlspecialchars($celula['Hora_Reunion'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="lider_search">Líder de Célula</label>
            <div class="autocomplete-container">
                <input type="text" 
                       id="lider_search" 
                       class="form-control autocomplete-input" 
                       placeholder="Buscar líder por nombre..."
                       autocomplete="off"
                       value="<?= isset($celula) && !empty($celula['Nombre_Lider']) ? htmlspecialchars($celula['Nombre_Lider']) : '' ?>">
                <input type="hidden" 
                       id="id_lider" 
                       name="id_lider" 
                       value="<?= isset($celula) ? $celula['Id_Lider'] : '' ?>">
                <div id="lider_autocomplete_results" class="autocomplete-results"></div>
                <small class="form-text text-muted">
                    <?php if (isset($celula) && !empty($celula['Nombre_Lider'])): ?>
                        Líder actual: <strong><?= htmlspecialchars($celula['Nombre_Lider']) ?></strong>
                    <?php else: ?>
                        Escriba para buscar un líder o deje en blanco para sin líder
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="pastor_principal">Pastor Principal</label>
            <div class="autocomplete-container">
                <input type="text" 
                       id="pastor_principal_search" 
                       name="pastor_principal"
                       class="form-control autocomplete-input" 
                       placeholder="Buscar pastor por nombre..."
                       autocomplete="off"
                       value="<?= htmlspecialchars($celula['Pastor_Principal'] ?? '') ?>">
                <div id="pastor_principal_autocomplete_results" class="autocomplete-results"></div>
                <small class="form-text text-muted">
                    Escriba para buscar un pastor
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="lider_inmediato_search">Líder Inmediato (Líder de 12)</label>
            <div class="autocomplete-container">
                <input type="text" 
                       id="lider_inmediato_search" 
                       class="form-control autocomplete-input" 
                       placeholder="Buscar líder de 12..."
                       autocomplete="off"
                       value="<?= isset($celula) && !empty($celula['Nombre_Lider_Inmediato']) ? htmlspecialchars($celula['Nombre_Lider_Inmediato']) : '' ?>">
                <input type="hidden" 
                       id="id_lider_inmediato" 
                       name="id_lider_inmediato" 
                       value="<?= isset($celula) ? $celula['Id_Lider_Inmediato'] : '' ?>">
                <div id="lider_inmediato_autocomplete_results" class="autocomplete-results"></div>
                <small class="form-text text-muted">
                    <?php if (isset($celula) && !empty($celula['Nombre_Lider_Inmediato'])): ?>
                        Líder inmediato: <strong><?= htmlspecialchars($celula['Nombre_Lider_Inmediato']) ?></strong>
                    <?php else: ?>
                        Escriba para buscar líder de 12
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="barrio">Barrio</label>
            <input type="text" id="barrio" name="barrio" class="form-control" 
                   value="<?= htmlspecialchars($celula['Barrio'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="red">Red</label>
            <select id="red" name="red" class="form-control">
                <option value="">Seleccione...</option>
                <option value="Hombre" <?= isset($celula) && $celula['Red'] == 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                <option value="Mujer" <?= isset($celula) && $celula['Red'] == 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                <option value="Mixta" <?= isset($celula) && $celula['Red'] == 'Mixta' ? 'selected' : '' ?>>Mixta</option>
                <option value="Jóvenes" <?= isset($celula) && $celula['Red'] == 'Jóvenes' ? 'selected' : '' ?>>Jóvenes</option>
                <option value="Rocas" <?= isset($celula) && $celula['Red'] == 'Rocas' ? 'selected' : '' ?>>Rocas</option>
                <option value="Teens" <?= isset($celula) && $celula['Red'] == 'Teens' ? 'selected' : '' ?>>Teens</option>
                <option value="Kids" <?= isset($celula) && $celula['Red'] == 'Kids' ? 'selected' : '' ?>>Kids</option>
            </select>
        </div>

        <div class="form-group">
            <label for="anfitrion_search">Anfitrión</label>
            <div class="autocomplete-container">
                <input type="text" 
                       id="anfitrion_search" 
                       class="form-control autocomplete-input" 
                       placeholder="Buscar anfitrión..."
                       autocomplete="off"
                       value="<?= isset($celula) && !empty($celula['Nombre_Anfitrion']) ? htmlspecialchars($celula['Nombre_Anfitrion']) : '' ?>">
                <input type="hidden" 
                       id="id_anfitrion" 
                       name="id_anfitrion" 
                       value="<?= isset($celula) ? $celula['Id_Anfitrion'] : '' ?>">
                <div id="anfitrion_autocomplete_results" class="autocomplete-results"></div>
                <small class="form-text text-muted">
                    <?php if (isset($celula) && !empty($celula['Nombre_Anfitrion'])): ?>
                        Anfitrión actual: <strong><?= htmlspecialchars($celula['Nombre_Anfitrion']) ?></strong>
                    <?php else: ?>
                        Escriba para buscar anfitrión
                    <?php endif; ?>
                </small>
            </div>
        </div>

        <div class="form-group">
            <label for="telefono_anfitrion">Teléfono Anfitrión</label>
            <input type="text" id="telefono_anfitrion" name="telefono_anfitrion" class="form-control" 
                   value="<?= htmlspecialchars($celula['Telefono_Anfitrion'] ?? '') ?>" readonly>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>index.php?url=celulas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<style>
.autocomplete-container {
    position: relative;
}

.autocomplete-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 250px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.autocomplete-item {
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.2s;
}

.autocomplete-item:hover,
.autocomplete-item.active {
    background: #f8f9fa;
}

.autocomplete-item strong {
    color: #007bff;
}

.autocomplete-item small {
    display: block;
    color: #6c757d;
    margin-top: 3px;
}

.autocomplete-loading {
    padding: 10px 15px;
    text-align: center;
    color: #6c757d;
}

.autocomplete-no-results {
    padding: 10px 15px;
    text-align: center;
    color: #6c757d;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nombreCelulaInput = document.getElementById('nombre_celula');
    const liderInput = document.getElementById('lider_search');
    const anfitrionInput = document.getElementById('anfitrion_search');
    const telefonoAnfitrionInput = document.getElementById('telefono_anfitrion');

    function actualizarNombreCelula() {
        if (!nombreCelulaInput) return;
        const lider = (liderInput ? liderInput.value.trim() : '');
        const anfitrion = (anfitrionInput ? anfitrionInput.value.trim() : '');

        if (lider && anfitrion) {
            nombreCelulaInput.value = `${lider} - ${anfitrion}`;
        } else if (lider) {
            nombreCelulaInput.value = lider;
        } else if (anfitrion) {
            nombreCelulaInput.value = anfitrion;
        }
    }

    function closeAllResults() {
        document.querySelectorAll('.autocomplete-results').forEach(el => {
            el.style.display = 'none';
        });
    }

    function initAutocomplete(options) {
        const input = document.getElementById(options.inputId);
        const hidden = document.getElementById(options.hiddenId);
        const results = document.getElementById(options.resultsId);

        if (!input || !results) return;

        let debounceTimer;
        let currentFocus = -1;

        input.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            currentFocus = -1;

            if (searchTerm === '') {
                if (hidden) hidden.value = '';
                if (typeof options.onClear === 'function') {
                    options.onClear();
                }
                results.style.display = 'none';
                return;
            }

            if (hidden && hidden.value) {
                hidden.value = '';
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                buscar(searchTerm);
            }, options.delay || 300);
        });

        input.addEventListener('keydown', function(e) {
            const items = results.getElementsByClassName('autocomplete-item');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentFocus++;
                addActive(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentFocus--;
                addActive(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentFocus > -1 && items[currentFocus]) {
                    items[currentFocus].click();
                }
            } else if (e.key === 'Escape') {
                results.style.display = 'none';
            }
        });

        function addActive(items) {
            if (!items) return false;
            removeActive(items);
            if (currentFocus >= items.length) currentFocus = 0;
            if (currentFocus < 0) currentFocus = items.length - 1;
            items[currentFocus].classList.add('active');
        }

        function removeActive(items) {
            for (let i = 0; i < items.length; i++) {
                items[i].classList.remove('active');
            }
        }

        function buscar(searchTerm) {
            if (searchTerm.length < (options.minLength || 2)) {
                results.style.display = 'none';
                return;
            }

            results.innerHTML = '<div class="autocomplete-loading">Buscando...</div>';
            results.style.display = 'block';

            fetch(options.searchUrl + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.length > 0) {
                        renderResults(data.data);
                    } else {
                        results.innerHTML = '<div class="autocomplete-no-results">No se encontraron resultados</div>';
                    }
                })
                .catch(() => {
                    results.innerHTML = '<div class="autocomplete-no-results">Error al buscar</div>';
                });
        }

        function renderResults(items) {
            results.innerHTML = '';

            items.forEach(itemData => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';

                const details = [];
                if (itemData.Rol) details.push('Rol: ' + itemData.Rol);
                if (itemData.Telefono) details.push('Tel: ' + itemData.Telefono);

                item.innerHTML = `
                    <strong>${itemData.Nombre} ${itemData.Apellido}</strong>
                    ${details.length ? `<small>${details.join(' | ')}</small>` : ''}
                `;

                item.addEventListener('click', function() {
                    input.value = itemData.Nombre + ' ' + itemData.Apellido;
                    if (hidden) hidden.value = itemData.Id_Persona;
                    if (typeof options.onSelect === 'function') {
                        options.onSelect(itemData);
                    }
                    results.style.display = 'none';
                });

                results.appendChild(item);
            });

            results.style.display = 'block';
        }
    }

    initAutocomplete({
        inputId: 'lider_search',
        hiddenId: 'id_lider',
        resultsId: 'lider_autocomplete_results',
        searchUrl: '<?= PUBLIC_URL ?>index.php?url=celulas/buscarLideres&term=',
        onSelect: () => {
            actualizarNombreCelula();
        },
        onClear: () => {
            actualizarNombreCelula();
        }
    });

    initAutocomplete({
        inputId: 'lider_inmediato_search',
        hiddenId: 'id_lider_inmediato',
        resultsId: 'lider_inmediato_autocomplete_results',
        searchUrl: '<?= PUBLIC_URL ?>index.php?url=celulas/buscarLideres12&term='
    });

    initAutocomplete({
        inputId: 'pastor_principal_search',
        resultsId: 'pastor_principal_autocomplete_results',
        searchUrl: '<?= PUBLIC_URL ?>index.php?url=celulas/buscarPastores&term='
    });

    initAutocomplete({
        inputId: 'anfitrion_search',
        hiddenId: 'id_anfitrion',
        resultsId: 'anfitrion_autocomplete_results',
        searchUrl: '<?= PUBLIC_URL ?>index.php?url=celulas/buscarAnfitriones&term=',
        onSelect: (item) => {
            if (telefonoAnfitrionInput) {
                telefonoAnfitrionInput.value = item.Telefono || '';
            }
            actualizarNombreCelula();
        },
        onClear: () => {
            if (telefonoAnfitrionInput) {
                telefonoAnfitrionInput.value = '';
            }
            actualizarNombreCelula();
        }
    });

    actualizarNombreCelula();

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.autocomplete-container')) {
            closeAllResults();
        }
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
