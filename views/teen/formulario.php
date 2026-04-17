<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Registro teens-kids</h2>
    <div class="page-actions personas-mobile-stack" style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=teen" class="btn btn-nav-pill">Material Teens</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-nav-pill active">Registro teens-kids</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/codigos" class="btn btn-nav-pill">Códigos</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-publico" target="_blank" class="btn btn-primary">Nuevo registro</a>
    </div>
</div>

<?php if (!empty($mensaje ?? '')): ?>
    <div class="alert alert-<?= (($tipo ?? '') === 'success') ? 'success' : 'danger' ?>" style="margin-bottom:16px;">
        <?= htmlspecialchars((string)$mensaje) ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
        <span style="color:#4d5f7b; font-weight:600;">Filtrar grupo:</span>
        <button type="button" class="btn btn-sm btn-secondary js-filtro-grupo active" data-grupo="todos">Todos</button>
        <button type="button" class="btn btn-sm btn-secondary js-filtro-grupo" data-grupo="teen">Teens</button>
        <button type="button" class="btn btn-sm btn-secondary js-filtro-grupo" data-grupo="kids">Kids</button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h3 style="margin-top:0;">Menores registrados</h3>

        <?php if (!empty($registros ?? [])): ?>
            <div class="table-container">
                <table class="data-table teen-registros-table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Código semanal</th>
                            <th>Menor</th>
                            <th>Acudiente</th>
                            <th>Contacto</th>
                            <th>Edad</th>
                            <th>Nacimiento</th>
                            <th>Ministerio</th>
                            <th>Asiste célula</th>
                            <th>Total asistencias</th>
                            <th>Último domingo</th>
                            <th>Barrio</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (($registros ?? []) as $registro): ?>
                            <?php
                                $edadRegistro = (int)($registro['edad'] ?? 0);
                                if ($edadRegistro >= 4 && $edadRegistro <= 9) {
                                    $grupoRegistro = 'kids';
                                } elseif ($edadRegistro >= 10 && $edadRegistro <= 13) {
                                    $grupoRegistro = 'teen';
                                } else {
                                    $grupoRegistro = 'otros';
                                }
                            ?>
                            <tr data-grupo="<?= $grupoRegistro ?>">
                                <td><strong><?= htmlspecialchars((string)($registro['codigo_registro'] ?? '')) ?></strong></td>
                                <td><strong><?= htmlspecialchars((string)($registro['codigo_semana_actual'] ?? '—')) ?></strong></td>
                                <td class="teen-nowrap teen-strong"><?= htmlspecialchars((string)($registro['nombre_menor'] ?? '')) ?></td>
                                <td class="teen-nowrap"><?= htmlspecialchars((string)($registro['Nombre_Acudiente_Base'] ?: ($registro['nombre_acudiente'] ?? ''))) ?></td>
                                <td class="teen-nowrap teen-strong"><?= htmlspecialchars((string)($registro['Telefono_Acudiente_Actual'] ?? ($registro['telefono_contacto'] ?? ''))) ?></td>
                                <td><?= (int)($registro['edad'] ?? 0) ?></td>
                                <td class="teen-nowrap"><?= !empty($registro['fecha_nacimiento']) ? htmlspecialchars((string)$registro['fecha_nacimiento']) : '—' ?></td>
                                <td class="teen-nowrap"><?= htmlspecialchars((string)($registro['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= !empty($registro['asiste_celula']) ? 'Sí' : 'No' ?></td>
                                <td><strong><?= (int)($registro['total_asistencias'] ?? 0) ?></strong></td>
                                <td class="teen-nowrap"><?= !empty($registro['ultima_fecha_asistencia']) ? htmlspecialchars((string)$registro['ultima_fecha_asistencia']) : '—' ?></td>
                                <td class="teen-nowrap"><?= htmlspecialchars((string)($registro['barrio'] ?? '')) ?></td>
                                <td class="teen-nowrap"><?= !empty($registro['created_at']) ? htmlspecialchars((string)$registro['created_at']) : '—' ?></td>
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

.js-filtro-grupo.active {
    background: #2f65b5;
    color: #fff;
    border-color: #2f65b5;
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

.teen-codigo-grid {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap:16px;
}

.teen-registros-table th,
.teen-registros-table td {
    vertical-align: top;
    white-space: normal;
    overflow-wrap: normal;
    word-break: normal;
    padding: 10px 12px !important;
    overflow: hidden;
}

.teen-registros-table {
    min-width: 1660px;
    width: max-content;
    table-layout: auto !important;
}

.teen-registros-table th:first-child,
.teen-registros-table td:first-child {
    white-space: nowrap;
    min-width: 120px;
}

.teen-registros-table th:nth-child(2),
.teen-registros-table td:nth-child(2) {
    min-width: 140px;
}

.teen-registros-table th:nth-child(3),
.teen-registros-table td:nth-child(3) {
    min-width: 230px;
}

.teen-registros-table th:nth-child(4),
.teen-registros-table td:nth-child(4) {
    min-width: 190px;
}

.teen-registros-table th:nth-child(5),
.teen-registros-table td:nth-child(5) {
    min-width: 145px;
}

.teen-registros-table th:nth-child(6),
.teen-registros-table td:nth-child(6) {
    min-width: 75px;
}

.teen-registros-table th:nth-child(7),
.teen-registros-table td:nth-child(7) {
    min-width: 130px;
}

.teen-registros-table th:nth-child(8),
.teen-registros-table td:nth-child(8) {
    min-width: 185px;
}

.teen-registros-table th:nth-child(9),
.teen-registros-table td:nth-child(9) {
    min-width: 120px;
}

.teen-registros-table th:nth-child(10),
.teen-registros-table td:nth-child(10) {
    min-width: 125px;
}

.teen-registros-table th:nth-child(11),
.teen-registros-table td:nth-child(11) {
    min-width: 135px;
}

.teen-registros-table th:nth-child(12),
.teen-registros-table td:nth-child(12) {
    min-width: 150px;
}

.teen-registros-table th:nth-child(13),
.teen-registros-table td:nth-child(13) {
    min-width: 175px;
}

.teen-nowrap {
    white-space: nowrap;
    text-overflow: ellipsis;
}

.teen-strong {
    font-weight: 700;
    color: #1f365f;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const botonesGrupo = Array.from(document.querySelectorAll('.js-filtro-grupo'));
    const filasRegistros = Array.from(document.querySelectorAll('.teen-registros-table tbody tr[data-grupo]'));

    function aplicarFiltroGrupo(grupo) {
        filasRegistros.forEach(function(fila) {
            const grupoFila = String(fila.getAttribute('data-grupo') || '');
            const visible = (grupo === 'todos') || (grupoFila === grupo);
            fila.style.display = visible ? '' : 'none';
        });

        botonesGrupo.forEach(function(btn) {
            btn.classList.toggle('active', btn.getAttribute('data-grupo') === grupo);
        });
    }

    botonesGrupo.forEach(function(btn) {
        btn.addEventListener('click', function() {
            aplicarFiltroGrupo(String(btn.getAttribute('data-grupo') || 'todos'));
        });
    });

    if (botonesGrupo.length > 0) {
        aplicarFiltroGrupo('todos');
    }

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