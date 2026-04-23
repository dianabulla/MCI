<?php include VIEWS . '/layout/header.php'; ?>

<?php
$clasificarGrupoRegistro = static function(array $registro) {
    $edadRegistro = (int)($registro['edad'] ?? 0);
    return ($edadRegistro <= 9) ? 'kids' : 'teen';
};

$registrosTeens = [];
$registrosKids = [];
foreach (($registros ?? []) as $registroTmp) {
    if ($clasificarGrupoRegistro((array)$registroTmp) === 'kids') {
        $registrosKids[] = $registroTmp;
    } else {
        $registrosTeens[] = $registroTmp;
    }
}
?>

<div class="page-header">
    <h2>Registro teens-kids</h2>
</div>

<div class="card teen-topbar-card" style="margin-bottom:20px;">
    <div class="card-body">
        <div class="page-actions personas-mobile-stack teen-topbar-actions">
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-nav-pill active">Registro teens-kids</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/codigos" class="btn btn-nav-pill">Códigos</a>
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-publico" target="_blank" class="btn btn-primary">Nuevo registro</a>
        </div>
    </div>
</div>

<?php if (!empty($mensaje ?? '')): ?>
    <div class="alert alert-<?= (($tipo ?? '') === 'success') ? 'success' : 'danger' ?>" style="margin-bottom:16px;">
        <?= htmlspecialchars((string)$mensaje) ?>
    </div>
<?php endif; ?>

<div class="teen-resumen-grid" style="margin-bottom:20px;">
    <button type="button" class="dashboard-card teen-resumen-card teen-resumen-teen teen-toggle-card" data-target="teen-panel-teens" aria-expanded="false">
        <h3>Teens</h3>
        <div class="value"><?= count($registrosTeens) ?></div>
        <small>Haz clic para ver la información</small>
    </button>
    <button type="button" class="dashboard-card teen-resumen-card teen-resumen-kids teen-toggle-card" data-target="teen-panel-kids" aria-expanded="false">
        <h3>Kids</h3>
        <div class="value"><?= count($registrosKids) ?></div>
        <small>Haz clic para ver la información</small>
    </button>
</div>

<div id="teen-panel-teens" class="card teen-section-card teen-collapsible-panel is-collapsed" style="margin-bottom:20px;">
    <div class="card-body">
        <div class="teen-section-head">
            <div>
                <h3 style="margin-top:0;">Teens</h3>
                <p>Solo se muestra el último código semanal vigente o el último registrado.</p>
            </div>
            <span class="teen-chip teen-chip-teen"><?= count($registrosTeens) ?> registros</span>
        </div>

        <?php if (!empty($registrosTeens)): ?>
            <div class="table-container">
                <table class="data-table teen-registros-table">
                    <thead>
                        <tr>
                            <th>Código actual</th>
                            <th>Menor</th>
                            <th>Acudiente</th>
                            <th>Contacto</th>
                            <th>Edad</th>
                            <th>Ministerio</th>
                            <th>Asiste célula</th>
                            <th>Total asistencias</th>
                            <th>Último domingo</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrosTeens as $registro): ?>
                            <?php $codigoVisible = (string)($registro['codigo_semana_actual'] ?? ($registro['ultimo_codigo_semana'] ?? '')); ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($codigoVisible !== '' ? $codigoVisible : '—') ?></strong></td>
                                <td class="teen-nowrap teen-strong"><?= htmlspecialchars((string)($registro['nombre_menor'] ?? '')) ?></td>
                                <td class="teen-nowrap"><?= htmlspecialchars((string)($registro['Nombre_Acudiente_Base'] ?: ($registro['nombre_acudiente'] ?? ''))) ?></td>
                                <td class="teen-nowrap teen-strong"><?= htmlspecialchars((string)($registro['Telefono_Acudiente_Actual'] ?? ($registro['telefono_contacto'] ?? ''))) ?></td>
                                <td><?= (int)($registro['edad'] ?? 0) ?></td>
                                <td class="teen-nowrap"><?= htmlspecialchars((string)($registro['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= !empty($registro['asiste_celula']) ? 'Sí' : 'No' ?></td>
                                <td><strong><?= (int)($registro['total_asistencias'] ?? 0) ?></strong></td>
                                <td class="teen-nowrap"><?= !empty($registro['ultima_fecha_asistencia']) ? htmlspecialchars((string)$registro['ultima_fecha_asistencia']) : '—' ?></td>
                                <td class="teen-nowrap"><?= !empty($registro['created_at']) ? htmlspecialchars((string)$registro['created_at']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="margin:0; color:#666;">Aún no hay teens registrados.</p>
        <?php endif; ?>
    </div>
</div>

<div id="teen-panel-kids" class="card teen-section-card teen-collapsible-panel is-collapsed">
    <div class="card-body">
        <div class="teen-section-head">
            <div>
                <h3 style="margin-top:0;">Kids</h3>
                <p>El código visible usa el prefijo KS y dos dígitos semanales.</p>
            </div>
            <span class="teen-chip teen-chip-kids"><?= count($registrosKids) ?> registros</span>
        </div>

        <?php if (!empty($registrosKids)): ?>
            <div class="table-container">
                <table class="data-table teen-registros-table">
                    <thead>
                        <tr>
                            <th>Código actual</th>
                            <th>Menor</th>
                            <th>Acudiente</th>
                            <th>Contacto</th>
                            <th>Edad</th>
                            <th>Ministerio</th>
                            <th>Asiste célula</th>
                            <th>Total asistencias</th>
                            <th>Último domingo</th>
                            <th>Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrosKids as $registro): ?>
                            <?php $codigoVisible = (string)($registro['codigo_semana_actual'] ?? ($registro['ultimo_codigo_semana'] ?? '')); ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($codigoVisible !== '' ? $codigoVisible : '—') ?></strong></td>
                                <td class="teen-nowrap teen-strong"><?= htmlspecialchars((string)($registro['nombre_menor'] ?? '')) ?></td>
                                <td class="teen-nowrap"><?= htmlspecialchars((string)($registro['Nombre_Acudiente_Base'] ?: ($registro['nombre_acudiente'] ?? ''))) ?></td>
                                <td class="teen-nowrap teen-strong"><?= htmlspecialchars((string)($registro['Telefono_Acudiente_Actual'] ?? ($registro['telefono_contacto'] ?? ''))) ?></td>
                                <td><?= (int)($registro['edad'] ?? 0) ?></td>
                                <td class="teen-nowrap"><?= htmlspecialchars((string)($registro['Nombre_Ministerio'] ?? 'Sin ministerio')) ?></td>
                                <td><?= !empty($registro['asiste_celula']) ? 'Sí' : 'No' ?></td>
                                <td><strong><?= (int)($registro['total_asistencias'] ?? 0) ?></strong></td>
                                <td class="teen-nowrap"><?= !empty($registro['ultima_fecha_asistencia']) ? htmlspecialchars((string)$registro['ultima_fecha_asistencia']) : '—' ?></td>
                                <td class="teen-nowrap"><?= !empty($registro['created_at']) ? htmlspecialchars((string)$registro['created_at']) : '—' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="margin:0; color:#666;">Aún no hay kids registrados.</p>
        <?php endif; ?>
    </div>
</div>

<style>
.teen-resumen-grid {
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap:14px;
}

.teen-resumen-card {
    border-left: 3px solid transparent;
    width:100%;
    text-align:left;
    cursor:pointer;
    border-top:none;
    border-right:none;
    border-bottom:none;
    transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}

.teen-resumen-card .value {
    font-size: 42px;
    font-weight: 800;
    line-height: 1;
    margin: 10px 0 6px;
}

.teen-resumen-teen {
    border-left-color: #1f66d1;
}

.teen-resumen-teen .value {
    color: #1f66d1;
}

.teen-resumen-kids {
    border-left-color: #0aa678;
}

.teen-resumen-kids .value {
    color: #0aa678;
}

.teen-resumen-card small {
    color:#5f728f;
    font-weight:600;
}

.teen-resumen-card:hover {
    transform: translateY(-2px);
}

.teen-resumen-card.is-active {
    box-shadow: 0 18px 34px rgba(31, 102, 209, 0.16);
}

.teen-topbar-card .card-body {
    padding: 16px 18px;
}

.teen-topbar-actions {
    align-items:center;
}

.teen-section-card {
    overflow: hidden;
}

.teen-collapsible-panel.is-collapsed {
    display:none;
}

.teen-section-head {
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom: 14px;
}

.teen-section-head p {
    margin: 4px 0 0;
    color:#5f728f;
    font-size: 13px;
}

.teen-chip {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    padding:8px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
    white-space:nowrap;
}

.teen-chip-teen {
    background:#eaf1ff;
    color:#1f66d1;
}

.teen-chip-kids {
    background:#e8fbf5;
    color:#0aa678;
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
    min-width: 1220px;
    width: max-content;
    table-layout: auto !important;
}

.teen-registros-table th:first-child,
.teen-registros-table td:first-child {
    white-space: nowrap;
    min-width: 140px;
}

.teen-registros-table th:nth-child(2),
.teen-registros-table td:nth-child(2) {
    min-width: 230px;
}

.teen-registros-table th:nth-child(3),
.teen-registros-table td:nth-child(3) {
    min-width: 190px;
}

.teen-registros-table th:nth-child(4),
.teen-registros-table td:nth-child(4) {
    min-width: 145px;
}

.teen-registros-table th:nth-child(5),
.teen-registros-table td:nth-child(5) {
    min-width: 75px;
}

.teen-registros-table th:nth-child(6),
.teen-registros-table td:nth-child(6) {
    min-width: 185px;
}

.teen-registros-table th:nth-child(7),
.teen-registros-table td:nth-child(7) {
    min-width: 120px;
}

.teen-registros-table th:nth-child(8),
.teen-registros-table td:nth-child(8) {
    min-width: 125px;
}

.teen-registros-table th:nth-child(9),
.teen-registros-table td:nth-child(9) {
    min-width: 135px;
}

.teen-registros-table th:nth-child(10),
.teen-registros-table td:nth-child(10) {
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
    const tarjetasToggle = Array.from(document.querySelectorAll('.teen-toggle-card'));
    const panelesToggle = Array.from(document.querySelectorAll('.teen-collapsible-panel'));

    function abrirPanelTeen(targetId) {
        panelesToggle.forEach(function(panel) {
            const mostrar = panel.id === targetId;
            panel.classList.toggle('is-collapsed', !mostrar);
        });

        tarjetasToggle.forEach(function(card) {
            const activa = String(card.getAttribute('data-target') || '') === targetId;
            card.classList.toggle('is-active', activa);
            card.setAttribute('aria-expanded', activa ? 'true' : 'false');
        });
    }

    tarjetasToggle.forEach(function(card) {
        card.addEventListener('click', function() {
            const targetId = String(card.getAttribute('data-target') || '');
            const expanded = card.getAttribute('aria-expanded') === 'true';

            if (expanded) {
                panelesToggle.forEach(function(panel) {
                    panel.classList.add('is-collapsed');
                });
                tarjetasToggle.forEach(function(item) {
                    item.classList.remove('is-active');
                    item.setAttribute('aria-expanded', 'false');
                });
                return;
            }

            abrirPanelTeen(targetId);
        });
    });

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