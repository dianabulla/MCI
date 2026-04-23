<?php include VIEWS . '/layout/header.php'; ?>

<?php
$cuentasPersona = is_array($cuentas_persona ?? null) ? $cuentas_persona : [];
$cuentasAccesoVinculadas = is_array($cuentas_acceso_vinculadas ?? null) ? $cuentas_acceso_vinculadas : [];
$cuentasAdministrativas = is_array($cuentas_administrativas ?? null) ? $cuentas_administrativas : [];
$tablaUsuarioAccesoDisponible = !empty($tabla_usuario_acceso_disponible);

$cuentasMinisteriales = [];

foreach ($cuentasPersona as $cuenta) {
    $cuentasMinisteriales[] = [
        'nombre' => trim((string)($cuenta['Nombre'] ?? '') . ' ' . (string)($cuenta['Apellido'] ?? '')),
        'usuario' => (string)($cuenta['Usuario'] ?? ''),
        'numero_documento' => (string)($cuenta['Numero_Documento'] ?? ''),
        'estado' => (string)($cuenta['Estado_Cuenta'] ?? 'Activo'),
        'tipo' => 'persona',
        'id' => (int)($cuenta['Id_Persona'] ?? 0),
    ];
}

foreach ($cuentasAccesoVinculadas as $cuenta) {
    $cuentasMinisteriales[] = [
        'nombre' => (string)($cuenta['Nombre_Mostrar'] ?? trim((string)($cuenta['Nombre'] ?? '') . ' ' . (string)($cuenta['Apellido'] ?? ''))),
        'usuario' => (string)($cuenta['Usuario'] ?? ''),
        'numero_documento' => (string)($cuenta['Numero_Documento'] ?? ''),
        'estado' => (string)($cuenta['Estado_Cuenta'] ?? 'Activo'),
        'tipo' => 'acceso',
        'id' => (int)($cuenta['Id_Usuario_Acceso'] ?? 0),
    ];
}

usort($cuentasMinisteriales, static function ($a, $b) {
    $nombreA = strtolower(trim((string)($a['nombre'] ?? '')));
    $nombreB = strtolower(trim((string)($b['nombre'] ?? '')));
    return $nombreA <=> $nombreB;
});
?>

<div class="page-header" style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:center;">
    <h2 style="margin:0;">Cuentas</h2>
    <div class="cuentas-header-actions">
        <div class="cuentas-action-group">
            <a href="<?= PUBLIC_URL ?>?url=cuentas/crear&tipo=ministerial" class="cuentas-action-pill">+ Crear cuenta ministerial</a>
            <a href="<?= PUBLIC_URL ?>?url=cuentas/crear&tipo=administrativo" class="cuentas-action-pill">+ Crear usuario administrativo</a>
            <a href="<?= PUBLIC_URL ?>?url=home" class="cuentas-action-pill">← Volver al inicio</a>
        </div>
    </div>
</div>

<div class="dashboard-grid cuentas-summary-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 320px)); margin:16px 0;">
    <button
        type="button"
        class="dashboard-card cuentas-summary-card is-active"
        data-target-cuentas="ministeriales"
        style="border-left-color:#0f5fca; text-align:left; cursor:pointer;"
    >
        <h3>Cuentas ministeriales</h3>
        <div class="value" style="color:#0f5fca;"><?= count($cuentasMinisteriales) ?></div>
        <small style="color:#637087;">Legacy + nuevo modelo unificados · Clic para ver detalle</small>
    </button>
    <button
        type="button"
        class="dashboard-card cuentas-summary-card"
        data-target-cuentas="administrativas"
        style="border-left-color:#8b5a14; text-align:left; cursor:pointer;"
    >
        <h3>Usuarios administrativos</h3>
        <div class="value" style="color:#8b5a14;"><?= count($cuentasAdministrativas) ?></div>
        <small style="color:#637087;">Sin persona obligatoria · Clic para ver detalle</small>
    </button>
</div>

<div class="form-container" style="margin-bottom: 16px;">
    <div class="filter-grid" style="display:grid; grid-template-columns: minmax(260px, 1fr) auto; gap:12px; align-items:end;">
        <div class="form-group" style="margin-bottom:0;">
            <label for="cuentas_busqueda">Buscar en cuentas</label>
            <input
                type="text"
                id="cuentas_busqueda"
                class="form-control"
                placeholder="Escribe nombre, usuario o número de cédula..."
                autocomplete="off"
            >
            <small style="color:#637087;">Filtra en tiempo real por nombre o cédula.</small>
        </div>
        <div class="form-group" style="margin-bottom:0; display:flex; gap:8px;">
            <button type="button" id="cuentas_btn_limpiar" class="btn btn-secondary">Limpiar</button>
        </div>
    </div>
</div>

<div id="tabla-cuentas-ministeriales" class="card cuentas-detalle-card" style="margin-bottom:16px;">
    <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
        Cuentas ministeriales (unificadas)
    </div>
    <div class="table-container">
        <table class="data-table cuentas-data-table" data-cuentas-tipo="ministeriales">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Cédula</th>
                    <th>Estado</th>
                    <th>Editar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($cuentasMinisteriales)): ?>
                    <?php foreach ($cuentasMinisteriales as $cuenta): ?>
                    <?php
                        $textoBusqueda = trim((string)($cuenta['nombre'] ?? '') . ' ' . (string)($cuenta['usuario'] ?? '') . ' ' . (string)($cuenta['numero_documento'] ?? ''));
                    ?>
                    <tr data-cuentas-row="1" data-search="<?= htmlspecialchars((string)$textoBusqueda, ENT_QUOTES, 'UTF-8') ?>">
                        <td><?= htmlspecialchars((string)($cuenta['nombre'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($cuenta['usuario'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($cuenta['numero_documento'] ?? '')) ?: '—' ?></td>
                        <td><?= htmlspecialchars((string)($cuenta['estado'] ?? 'Activo')) ?></td>
                        <td>
                            <a href="<?= PUBLIC_URL ?>?url=cuentas/editar&tipo=<?= htmlspecialchars((string)($cuenta['tipo'] ?? 'persona')) ?>&id=<?= (int)($cuenta['id'] ?? 0) ?>" class="btn btn-sm cuentas-action-btn cuentas-action-btn--icon cuentas-action-btn--edit" title="Editar" aria-label="Editar">
                                <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr data-cuentas-empty="ministeriales">
                        <td colspan="5" class="text-center">No hay cuentas ministeriales registradas.</td>
                    </tr>
                <?php endif; ?>
                <tr data-cuentas-empty-search="ministeriales" style="display:none;">
                    <td colspan="5" class="text-center">No hay resultados para esa búsqueda.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div id="tabla-cuentas-administrativas" class="card cuentas-detalle-card" hidden style="margin-bottom:16px;">
    <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
        Usuarios administrativos
    </div>
    <div class="table-container">
        <table class="data-table cuentas-data-table" data-cuentas-tipo="administrativas">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Editar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$tablaUsuarioAccesoDisponible): ?>
                    <tr>
                        <td colspan="4" class="text-center">La tabla usuario_acceso aún no existe. Ejecuta la migración SQL para habilitarla.</td>
                    </tr>
                <?php elseif (!empty($cuentasAdministrativas)): ?>
                    <?php foreach ($cuentasAdministrativas as $cuenta): ?>
                    <?php
                        $textoBusquedaAdmin = trim((string)($cuenta['Nombre_Mostrar'] ?? '') . ' ' . (string)($cuenta['Usuario'] ?? ''));
                    ?>
                    <tr data-cuentas-row="1" data-search="<?= htmlspecialchars((string)$textoBusquedaAdmin, ENT_QUOTES, 'UTF-8') ?>">
                        <td><?= htmlspecialchars((string)($cuenta['Nombre_Mostrar'] ?? 'Sin nombre')) ?></td>
                        <td><?= htmlspecialchars((string)($cuenta['Usuario'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($cuenta['Estado_Cuenta'] ?? 'Activo')) ?></td>
                        <td>
                            <a href="<?= PUBLIC_URL ?>?url=cuentas/editar&tipo=acceso&id=<?= (int)($cuenta['Id_Usuario_Acceso'] ?? 0) ?>" class="btn btn-sm cuentas-action-btn cuentas-action-btn--icon cuentas-action-btn--edit" title="Editar" aria-label="Editar">
                                <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr data-cuentas-empty="administrativas">
                        <td colspan="4" class="text-center">No hay usuarios administrativos registrados.</td>
                    </tr>
                <?php endif; ?>
                <tr data-cuentas-empty-search="administrativas" style="display:none;">
                    <td colspan="4" class="text-center">No hay resultados para esa búsqueda.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<style>
.cuentas-header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.cuentas-action-group {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px;
    border: 1px solid #d5e2f3;
    border-radius: 999px;
    background: #f8fbff;
}

.cuentas-action-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 12px;
    border: 1px solid transparent;
    border-radius: 999px;
    color: #2a4a73;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    line-height: 1;
    white-space: nowrap;
    transition: all 0.16s ease;
    background: transparent;
    cursor: pointer;
}

.cuentas-action-pill:hover {
    background: #edf4ff;
    color: #1c4478;
}

.cuentas-action-pill.is-active {
    background: #1f5ea8;
    border-color: #1f5ea8;
    color: #ffffff;
    box-shadow: 0 1px 3px rgba(20, 58, 101, 0.28);
}

.cuentas-summary-grid {
    gap: 14px;
}

.cuentas-summary-card {
    appearance: none;
    border-top: 0;
    border-right: 0;
    border-bottom: 0;
    width: 100%;
    transition: transform 0.18s ease, box-shadow 0.18s ease, outline 0.18s ease;
}

.cuentas-summary-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(15, 35, 61, 0.08);
}

.cuentas-summary-card.is-active {
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12), 0 10px 22px rgba(15, 35, 61, 0.08);
}

.cuentas-summary-card:focus-visible {
    outline: 3px solid rgba(37, 99, 235, 0.22);
    outline-offset: 2px;
}

.cuentas-data-table th,
.cuentas-data-table td {
    padding: 2px 5px;
    font-size: 11px;
    line-height: 1.05;
    vertical-align: middle;
    text-align: center;
}

.cuentas-data-table tbody tr {
    height: 26px;
}

.cuentas-data-table .btn {
    padding: 3px 6px;
    font-size: 11px;
    line-height: 1.1;
}

.cuentas-action-btn {
    border: 1px solid #d6e1f0;
    background: #f6f9ff;
    color: #2f4f7a;
    font-weight: 600;
}

.cuentas-action-btn:hover {
    background: #edf3fc;
    color: #274368;
}

.cuentas-action-btn--icon {
    min-width: 26px;
    width: 26px;
    height: 26px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
}

.cuentas-action-btn--edit {
    background: #fff8e8;
    border-color: #f1ddb1;
    color: #8a6400;
}

.cuentas-action-btn--edit:hover {
    background: #fff1d8;
    color: #7a5600;
}

@media (max-width: 800px) {
    .cuentas-header-actions {
        width: 100%;
        justify-content: flex-start;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputBusqueda = document.getElementById('cuentas_busqueda');
    if (!inputBusqueda) {
        return;
    }

    const normalizar = function(texto) {
        return String(texto || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    };

    const filas = Array.from(document.querySelectorAll('tr[data-cuentas-row="1"]'));
    const cardsVista = Array.from(document.querySelectorAll('.cuentas-summary-card'));
    const panelMinisteriales = document.getElementById('tabla-cuentas-ministeriales');
    const panelAdministrativas = document.getElementById('tabla-cuentas-administrativas');
    const btnLimpiar = document.getElementById('cuentas_btn_limpiar');
    const vacioMinisteriales = document.querySelector('tr[data-cuentas-empty-search="ministeriales"]');
    const vacioAdministrativas = document.querySelector('tr[data-cuentas-empty-search="administrativas"]');

    const activarVistaResumen = function(tipo) {
        cardsVista.forEach(function(card) {
            const activo = String(card.getAttribute('data-target-cuentas') || '') === tipo;
            card.classList.toggle('is-active', activo);
        });

        if (panelMinisteriales) {
            panelMinisteriales.hidden = (tipo !== 'ministeriales');
        }
        if (panelAdministrativas) {
            panelAdministrativas.hidden = (tipo !== 'administrativas');
        }
    };

    cardsVista.forEach(function(card) {
        card.addEventListener('click', function() {
            activarVistaResumen(String(card.getAttribute('data-target-cuentas') || 'ministeriales'));
        });
    });

    const filtrar = function() {
        const termino = normalizar(inputBusqueda.value);
        let visiblesMinisteriales = 0;
        let visiblesAdministrativas = 0;

        filas.forEach(function(fila) {
            const textoFila = normalizar(fila.getAttribute('data-search') || '');
            const visible = termino === '' || textoFila.indexOf(termino) !== -1;
            fila.style.display = visible ? '' : 'none';

            if (!visible) {
                return;
            }

            const tabla = fila.closest('table');
            if (!tabla) {
                return;
            }
            const tipoTabla = String(tabla.getAttribute('data-cuentas-tipo') || '');
            if (tipoTabla === 'ministeriales') {
                visiblesMinisteriales++;
            } else {
                visiblesAdministrativas++;
            }
        });

        if (vacioMinisteriales) {
            vacioMinisteriales.style.display = (termino !== '' && visiblesMinisteriales === 0) ? '' : 'none';
        }
        if (vacioAdministrativas) {
            vacioAdministrativas.style.display = (termino !== '' && visiblesAdministrativas === 0) ? '' : 'none';
        }

        if (termino !== '') {
            cardsVista.forEach(function(card) {
                card.classList.add('is-active');
            });
            if (panelMinisteriales) {
                panelMinisteriales.hidden = false;
            }
            if (panelAdministrativas) {
                panelAdministrativas.hidden = false;
            }
        } else {
            activarVistaResumen('ministeriales');
        }
    };

    inputBusqueda.addEventListener('input', filtrar);
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', function() {
            inputBusqueda.value = '';
            filtrar();
            activarVistaResumen('ministeriales');
        });
    }
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
