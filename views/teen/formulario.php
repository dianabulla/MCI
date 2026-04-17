<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Registro de Menores - Teens</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-secondary">Volver a Material Teens</a>
    </div>
</div>

<?php if (!empty($mensaje ?? '')): ?>
    <div class="alert alert-<?= (($tipo ?? '') === 'success') ? 'success' : 'danger' ?>" style="margin-bottom:16px;">
        <?= htmlspecialchars((string)$mensaje) ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body">
        <h3 style="margin-top:0;">Formulario de registro</h3>

        <?php if (AuthController::tienePermiso('teen', 'crear')): ?>
            <form method="POST" action="<?= PUBLIC_URL ?>index.php?url=teen/guardar-menor" id="formRegistroMenor">
                <div class="teen-grid">
                    <div class="form-group">
                        <label for="nombre_menor">Nombre y apellido</label>
                        <input type="text" id="nombre_menor" name="nombre_menor" class="form-control js-upper" required value="<?= htmlspecialchars((string)($old['nombre_menor'] ?? '')) ?>" placeholder="Nombre completo del menor">
                    </div>

                    <div class="form-group autocomplete-wrapper" style="position:relative;">
                        <label for="acudiente_busqueda">Nombre de acudiente</label>
                        <input type="text" id="acudiente_busqueda" name="acudiente_busqueda" class="form-control js-upper" required value="<?= htmlspecialchars((string)($old['acudiente_busqueda'] ?? '')) ?>" placeholder="Escribe y selecciona de la base de personas" autocomplete="off">
                        <input type="hidden" id="id_acudiente" name="id_acudiente" value="<?= htmlspecialchars((string)($old['id_acudiente'] ?? '')) ?>">
                        <div id="acudiente_autocomplete" class="autocomplete-items"></div>
                        <small class="form-text text-muted">Al seleccionar el acudiente se cargará su número de contacto.</small>
                        <small id="acudiente_error" class="form-text text-danger" style="display:none;">Debes seleccionar un acudiente válido de la lista.</small>
                    </div>

                    <div class="form-group">
                        <label for="telefono_contacto">N° de contacto</label>
                        <input type="text" id="telefono_contacto" name="telefono_contacto" class="form-control" required value="<?= htmlspecialchars((string)($old['telefono_contacto'] ?? '')) ?>" placeholder="Se completa automáticamente con el acudiente">
                    </div>

                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de nacimiento</label>
                        <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" required value="<?= htmlspecialchars((string)($old['fecha_nacimiento'] ?? '')) ?>">
                    </div>

                    <div class="form-group">
                        <label for="edad">Edad</label>
                        <input type="number" id="edad" name="edad" class="form-control" min="0" max="17" required value="<?= htmlspecialchars((string)($old['edad'] ?? '')) ?>" placeholder="Edad del menor" readonly>
                    </div>

                    <div class="form-group">
                        <label for="id_ministerio">Ministerio</label>
                        <select id="id_ministerio" name="id_ministerio" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <?php foreach (($ministerios ?? []) as $ministerio): ?>
                                <option value="<?= (int)$ministerio['Id_Ministerio'] ?>" <?= (string)($old['id_ministerio'] ?? '') === (string)$ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)$ministerio['Nombre_Ministerio']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="asiste_celula">Asiste a célula</label>
                        <select id="asiste_celula" name="asiste_celula" class="form-control" required>
                            <option value="">Seleccione...</option>
                            <option value="SI" <?= strtoupper((string)($old['asiste_celula'] ?? '')) === 'SI' ? 'selected' : '' ?>>Sí</option>
                            <option value="NO" <?= strtoupper((string)($old['asiste_celula'] ?? '')) === 'NO' ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>

                    <div class="form-group teen-grid-full">
                        <label for="barrio">Barrio</label>
                        <input type="text" id="barrio" name="barrio" class="form-control js-upper" value="<?= htmlspecialchars((string)($old['barrio'] ?? '')) ?>" placeholder="Barrio del menor">
                    </div>
                </div>

                <div style="margin-top: 16px; display:flex; gap:8px; flex-wrap:wrap;">
                    <button type="submit" class="btn btn-primary">Guardar menor</button>
                    <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <p style="margin:0; color:#666;">No tienes permiso para registrar menores, pero sí puedes consultar los registros existentes.</p>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 style="margin-top:0;">Menores registrados</h3>

        <?php if (!empty($registros ?? [])): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Menor</th>
                            <th>Acudiente</th>
                            <th>Contacto</th>
                            <th>Fecha nacimiento</th>
                            <th>Edad</th>
                            <th>Ministerio</th>
                            <th>Asiste a célula</th>
                            <th>Barrio</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($registros ?? []) as $registro): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars((string)($registro['codigo_registro'] ?? '')) ?></strong></td>
                                <td><?= htmlspecialchars((string)($registro['nombre_menor'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($registro['Nombre_Acudiente_Base'] ?: ($registro['nombre_acudiente'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars((string)($registro['Telefono_Acudiente_Actual'] ?? ($registro['telefono_contacto'] ?? ''))) ?></td>
                                <td><?= !empty($registro['fecha_nacimiento']) ? htmlspecialchars((string)$registro['fecha_nacimiento']) : '—' ?></td>
                                <td><?= (int)($registro['edad'] ?? 0) ?></td>
                                <td><?= htmlspecialchars((string)($registro['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= !empty($registro['asiste_celula']) ? 'Sí' : 'No' ?></td>
                                <td><?= htmlspecialchars((string)($registro['barrio'] ?? '')) ?></td>
                                <td><?= !empty($registro['created_at']) ? htmlspecialchars((string)$registro['created_at']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="margin:0; color:#666;">Aún no hay menores registrados.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.teen-grid {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
}

.teen-grid-full {
    grid-column: 1 / -1;
}

.autocomplete-items {
    position:absolute;
    top:100%;
    left:0;
    right:0;
    background:#fff;
    border:1px solid #d7e2f3;
    border-top:none;
    z-index:50;
    max-height:240px;
    overflow-y:auto;
    box-shadow:0 6px 16px rgba(0,0,0,.08);
}

.autocomplete-item {
    padding:10px 12px;
    cursor:pointer;
    border-bottom:1px solid #eef3fb;
}

.autocomplete-item:hover {
    background:#f5f8ff;
}

.input-invalid {
    border-color:#dc3545 !important;
    box-shadow:0 0 0 0.2rem rgba(220,53,69,.15) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formRegistroMenor');
    const acudienteInput = document.getElementById('acudiente_busqueda');
    const acudienteHidden = document.getElementById('id_acudiente');
    const telefonoInput = document.getElementById('telefono_contacto');
    const fechaNacimientoInput = document.getElementById('fecha_nacimiento');
    const edadInput = document.getElementById('edad');
    const errorEl = document.getElementById('acudiente_error');
    const autocompleteEl = document.getElementById('acudiente_autocomplete');
    let debounceTimer = null;

    document.querySelectorAll('.js-upper').forEach(function(campo) {
        campo.style.textTransform = 'uppercase';
        const transformar = function() {
            campo.value = String(campo.value || '').toUpperCase();
        };
        campo.addEventListener('input', transformar);
        campo.addEventListener('change', transformar);
        transformar();
    });

    function calcularEdad(fechaTexto) {
        if (!fechaTexto) {
            return '';
        }

        const fecha = new Date(fechaTexto + 'T00:00:00');
        if (Number.isNaN(fecha.getTime())) {
            return '';
        }

        const hoy = new Date();
        let edad = hoy.getFullYear() - fecha.getFullYear();
        const mes = hoy.getMonth() - fecha.getMonth();
        if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
            edad--;
        }

        return edad >= 0 ? edad : '';
    }

    function actualizarEdadAutomatica() {
        if (!fechaNacimientoInput || !edadInput) {
            return;
        }
        edadInput.value = calcularEdad(fechaNacimientoInput.value);
    }

    function limpiarError() {
        if (acudienteInput) {
            acudienteInput.classList.remove('input-invalid');
        }
        if (errorEl) {
            errorEl.style.display = 'none';
        }
    }

    function marcarError() {
        if (acudienteInput) {
            acudienteInput.classList.add('input-invalid');
        }
        if (errorEl) {
            errorEl.style.display = 'block';
        }
    }

    function cerrarAutocomplete() {
        if (autocompleteEl) {
            autocompleteEl.innerHTML = '';
        }
    }

    function renderItem(persona) {
        const div = document.createElement('div');
        div.className = 'autocomplete-item';
        const nombre = [persona.Nombre || '', persona.Apellido || ''].join(' ').trim();
        const telefono = persona.Telefono || 'Sin teléfono';
        const ministerio = persona.Nombre_Ministerio || 'Sin ministerio';
        div.innerHTML = '<strong>' + nombre + '</strong><br><small>' + telefono + ' · ' + ministerio + '</small>';
        div.addEventListener('click', function() {
            acudienteInput.value = nombre.toUpperCase();
            acudienteHidden.value = persona.Id_Persona || '';
            telefonoInput.value = persona.Telefono || telefonoInput.value || '';
            limpiarError();
            cerrarAutocomplete();
        });
        autocompleteEl.appendChild(div);
    }

    if (acudienteInput) {
        acudienteInput.addEventListener('input', function() {
            const term = String(this.value || '').trim();
            limpiarError();

            if (!term) {
                acudienteHidden.value = '';
                cerrarAutocomplete();
                return;
            }

            acudienteHidden.value = '';
            cerrarAutocomplete();

            if (term.length < 2) {
                return;
            }

            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function() {
                fetch('<?= PUBLIC_URL ?>index.php?url=teen/buscarAcudientes&term=' + encodeURIComponent(term))
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        cerrarAutocomplete();
                        if (!data || !data.success || !Array.isArray(data.data)) {
                            return;
                        }
                        data.data.forEach(renderItem);
                    })
                    .catch(function() {
                        cerrarAutocomplete();
                    });
            }, 200);
        });

        acudienteInput.addEventListener('blur', function() {
            setTimeout(function() {
                if (String(acudienteInput.value || '').trim() !== '' && String(acudienteHidden.value || '').trim() === '') {
                    marcarError();
                }
                cerrarAutocomplete();
            }, 180);
        });
    }

    document.addEventListener('click', function(e) {
        if (!autocompleteEl || !acudienteInput) {
            return;
        }
        if (e.target !== acudienteInput && !autocompleteEl.contains(e.target)) {
            cerrarAutocomplete();
        }
    });

    if (fechaNacimientoInput) {
        fechaNacimientoInput.addEventListener('change', actualizarEdadAutomatica);
        fechaNacimientoInput.addEventListener('input', actualizarEdadAutomatica);
        actualizarEdadAutomatica();
    }

    if (form) {
        form.addEventListener('submit', function(e) {
            if (acudienteInput && String(acudienteInput.value || '').trim() !== '' && String(acudienteHidden.value || '').trim() === '') {
                e.preventDefault();
                marcarError();
                acudienteInput.focus();
            }
        });
    }
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>