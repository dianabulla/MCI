<?php require_once VIEWS . '/layout/header.php'; ?>

<style>
/* Pagina de permisos */
.perm-page { max-width: 960px; margin: 0 auto; }

/* Pestanas de roles */
.perm-tabs {
    display: flex;
    flex-wrap: nowrap;
    gap: 6px;
    margin-bottom: 0;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 2px;
    scrollbar-width: thin;
}
.perm-tab-btn {
    padding: 8px 18px;
    border: 2px solid #d1d5db;
    border-bottom: none;
    border-radius: 8px 8px 0 0;
    background: #f3f4f6;
    color: #374151;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    transition: background .15s, color .15s;
    position: relative;
    bottom: -2px;
}
.perm-tab-btn:hover { background: #e0e7ff; color: #1d4ed8; border-color: #a5b4fc; }
.perm-tab-btn.active {
    background: #fff;
    color: #1d4ed8;
    border-color: #2563eb;
    border-bottom-color: #fff;
    z-index: 1;
}
.perm-tab-btn .perm-tab-badge {
    display: inline-block;
    font-size: 10px;
    padding: 1px 5px;
    border-radius: 10px;
    margin-left: 4px;
    background: #dbeafe;
    color: #1e40af;
    font-weight: 700;
}
.perm-tab-btn.active .perm-tab-badge { background: #2563eb; color: #fff; }

/* Panel por rol */
.perm-panel {
    display: none;
    border: 2px solid #2563eb;
    border-radius: 0 8px 8px 8px;
    background: #fff;
    padding: 20px 20px 12px;
}
.perm-panel.active { display: block; }

/* Barra superior del panel */
.perm-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}
.perm-panel-title { font-size: 17px; font-weight: 700; color: #1e3a8a; margin: 0; }
.perm-panel-actions { display: flex; gap: 8px; flex-wrap: wrap; }

.btn-perm-all {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 14px; border-radius: 6px; border: none;
    font-size: 12px; font-weight: 600; cursor: pointer; transition: opacity .15s;
}
.btn-perm-all:hover { opacity: .85; }
.btn-perm-activar  { background: #2563eb; color: #fff; }
.btn-perm-quitar   { background: #dc2626; color: #fff; }
.btn-perm-solo-ver { background: #059669; color: #fff; }

/* Acceso total badge */
.perm-acceso-total-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px;
    background: #dcfce7; color: #15803d;
    font-size: 12px; font-weight: 700;
    border: 1px solid #86efac;
}

/* Tabla de modulos */
.perm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}
.perm-table thead th {
    background: #1e3a8a;
    color: #fff;
    text-align: center;
    padding: 8px 10px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .3px;
}
.perm-table thead th:first-child { text-align: left; width: 44%; }
.perm-table tbody tr { border-bottom: 1px solid #e5e7eb; }
.perm-table tbody tr:last-child { border-bottom: none; }
.perm-table tbody tr:hover { background: #f0f4ff; }
.perm-table tbody tr.perm-group-header td {
    background: #f1f5f9;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 5px 10px;
    border-top: 2px solid #e2e8f0;
}
.perm-table td { padding: 9px 10px; vertical-align: middle; }
.perm-table td.perm-check-cell { text-align: center; width: 14%; }
.perm-table td.perm-name { font-size: 13px; color: #1e293b; }
.perm-table td.perm-name small { display: block; color: #94a3b8; font-size: 11px; margin-top: 1px; }

.perm-search-wrap {
    margin: 10px 0 14px;
}

.perm-search-input {
    width: 100%;
    max-width: 460px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 10px;
    font-size: 13px;
}

/* Checkbox estilo toggle */
.perm-cb {
    width: 18px; height: 18px;
    cursor: pointer;
    accent-color: #2563eb;
}
.perm-cb:disabled { opacity: .4; cursor: not-allowed; }

/* Toast */
#perm-toast {
    position: fixed;
    bottom: 24px; right: 24px;
    background: #15803d;
    color: #fff;
    padding: 10px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 14px rgba(0,0,0,.18);
    display: none;
    z-index: 9999;
    transition: opacity .3s;
}
#perm-toast.error { background: #dc2626; }
</style>

<div class="page-header">
    <h2><i class="bi bi-shield-check"></i> Administracion de Permisos</h2>
</div>

<div class="perm-page">

    <!-- Aviso roles con acceso total -->
    <div class="alert alert-warning" style="margin-bottom:18px; font-size:13px;">
        <i class="bi bi-shield-fill-exclamation"></i>
        El rol <strong>Administrador</strong> se mantiene protegido por el sistema.<br>
        En los demas roles, los checks de esta pantalla definen la visibilidad real de cada modulo.
    </div>

    <!-- Pestanas de roles -->
    <div class="perm-tabs">
        <?php foreach ($roles as $i => $rol):
            $idRol = $rol['Id_Rol'];
            $nombreRol = htmlspecialchars($rol['Nombre_Rol']);
            // Contar permisos activos (Ver=1) para el badge
            $activos = 0;
            foreach ($modulos as $mk => $mn) {
                if (!empty($permisos[$idRol][$mk]['Puede_Ver'])) $activos++;
            }
        ?>
        <button type="button"
            class="perm-tab-btn <?= $i === 0 ? 'active' : '' ?>"
            data-tab="rol-<?= $idRol ?>">
            <?= $nombreRol ?>
            <span class="perm-tab-badge"><?= $activos ?>/<?= count($modulos) ?></span>
        </button>
        <?php endforeach; ?>
    </div>

    <div class="perm-search-wrap">
        <input type="text" id="perm-search" class="perm-search-input" placeholder="Buscar módulo o descripción...">
    </div>

    <!-- Paneles por rol -->
    <?php
    $rolesProtegidos = [];
    foreach ($roles as $r) {
        $rn = strtolower(trim(strtr($r['Nombre_Rol'], ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'])));
        if ((int)$r['Id_Rol'] === 6 || strpos($rn, 'admin') !== false) {
            $rolesProtegidos[] = (int)$r['Id_Rol'];
        }
    }

    // Grupos visuales de modulos
    $gruposModulos = [
        'Principal' => [
            'personas'         => ['Personas',         'Permisos generales del modulo (listado y CRUD principal).'],
            'personas_formulario_publico' => ['Personas: Formulario publico', 'Controla la visibilidad del boton Formulario publico en Personas.'],
            'personas_plantillas_whatsapp' => ['Personas: Plantillas WhatsApp', 'Controla acceso a Plantillas WhatsApp de Personas.'],
            'personas_ganar_asignados' => ['Pendiente: Atajo Asignados', 'Controla la visibilidad del atajo Asignados en Pendiente por consolidar.'],
            'personas_ganar_reasignados' => ['Pendiente: Atajo Reasignados', 'Controla la visibilidad del atajo Reasignados en Pendiente por consolidar.'],
            'celulas'          => ['Celulas',           'Gestion de celulas y miembros'],
            'materiales_celulas'=> ['Materiales Celulas','Archivos PDF para celulas'],
            'ministerios'      => ['Ministerios',       'Ver y gestionar ministerios'],
            'asistencias'      => ['Asistencias',       'Registro de asistencias a celulas'],
            'eventos'          => ['Eventos',           'Eventos y actividades generales'],
            'peticiones'       => ['Peticiones',        'Peticiones de oracion'],
            'reportes'         => ['Reportes',          'Reportes y estadisticas'],
            'transmisiones'    => ['Transmisiones',     'Transmisiones en vivo'],
            'escuelas_formacion' => ['Escuelas de Formacion', 'Registro y asistencias de escuelas de formacion'],
            'escuelas_formacion_marcar_asistencia' => ['Escuelas: Marcar asistencia', 'Permite marcar/desmarcar asistencias en la matriz de Escuelas'],
            'escuelas_formacion_editar_fechas' => ['Escuelas: Editar fechas de clases', 'Permite editar fechas de clases en la matriz de Escuelas'],
            'teen'             => ['Material Teens',     'Material educativo para adolescentes'],
        ],
        'Obsequios' => [
            'entrega_obsequio'   => ['Entrega de Obsequios',  'Registrar entrega de obsequios'],
            'registro_obsequio'  => ['Registro de Obsequios', 'Consultar registros de obsequios'],
        ],
        'Nehemias' => [
            'nehemias'                => ['Nehemias (general)',             'Acceso al modulo Nehemias'],
            'nehemias_cols_cedula'    => ['Ver: Cedula',                   'Columna cedula en Nehemias'],
            'nehemias_cols_telefono'  => ['Ver: Telefono',                 'Columna telefono en Nehemias'],
            'nehemias_cols_subido_link'=> ['Ver: Link subido',             'Columna link subido en Nehemias'],
            'nehemias_cols_bogota_subio'=> ['Ver: En Bogota se le subio',  'Columna especifica del reporte'],
            'nehemias_cols_puesto'    => ['Ver: Puesto',                   'Columna puesto en Nehemias'],
            'nehemias_cols_mesa'      => ['Ver: Mesa',                     'Columna mesa en Nehemias'],
            'nehemias_cols_acepta'    => ['Ver: Acepta',                   'Columna acepta en Nehemias'],
            'nehemias_acciones_editar'=> ['Boton Editar',                  'Permite editar registros Nehemias'],
            'nehemias_acciones_eliminar'=> ['Boton Eliminar',              'Permite eliminar registros Nehemias'],
        ],
        'Sistema' => [
            'roles'    => ['Roles',    'Gestion de roles de usuario'],
            'permisos' => ['Permisos', 'Administracion de permisos'],
        ],
    ];
    ?>

    <?php foreach ($roles as $i => $rol):
        $idRol = $rol['Id_Rol'];
        $esRolProtegido = in_array($idRol, $rolesProtegidos, true);
    ?>
    <div class="perm-panel <?= $i === 0 ? 'active' : '' ?>" id="rol-<?= $idRol ?>">

        <!-- Cabecera del panel -->
        <div class="perm-panel-header">
            <h3 class="perm-panel-title">
                <i class="bi bi-person-badge"></i>
                <?= htmlspecialchars($rol['Nombre_Rol']) ?>
            </h3>
            <div class="perm-panel-actions">
                <?php if ($esRolProtegido): ?>
                <span class="perm-acceso-total-badge">
                    <i class="bi bi-shield-fill-check"></i> Rol protegido por sistema
                </span>
                <?php else: ?>
                <button type="button" class="btn-perm-all btn-perm-solo-ver"
                    data-rol="<?= $idRol ?>" data-accion="solo_ver">
                    <i class="bi bi-eye"></i> Solo leer todo
                </button>
                <button type="button" class="btn-perm-all btn-perm-activar"
                    data-rol="<?= $idRol ?>" data-accion="activar">
                    <i class="bi bi-check-all"></i> Activar Todo
                </button>
                <button type="button" class="btn-perm-all btn-perm-quitar"
                    data-rol="<?= $idRol ?>" data-accion="desactivar">
                    <i class="bi bi-slash-circle"></i> Quitar Todo
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de modulos -->
        <table class="perm-table">
            <thead>
                <tr>
                    <th>Modulo</th>
                    <th>Ver</th>
                    <th>Crear</th>
                    <th>Editar</th>
                    <th>Eliminar</th>
                </tr>
            </thead>
            <tbody>
                <?php $modulosMostrados = []; ?>
                <?php foreach ($gruposModulos as $grupoNombre => $grupoItems):
                    // Filtrar solo los modulos que existen en $modulos
                    $itemsVis = array_filter($grupoItems, fn($k) => isset($modulos[$k]), ARRAY_FILTER_USE_KEY);
                    if (empty($itemsVis)) continue;
                ?>
                <tr class="perm-group-header">
                    <td colspan="5"><?= $grupoNombre ?></td>
                </tr>
                <?php foreach ($itemsVis as $mk => [$mnombre, $mdesc]):
                    $modulosMostrados[$mk] = true;
                    $permiso = $permisos[$idRol][$mk] ?? null;
                    $pVer  = $permiso ? (int)$permiso['Puede_Ver']    : 0;
                    $pCre  = $permiso ? (int)$permiso['Puede_Crear']  : 0;
                    $pEdi  = $permiso ? (int)$permiso['Puede_Editar'] : 0;
                    $pEli  = $permiso ? (int)$permiso['Puede_Eliminar']:0;
                ?>
                <tr>
                    <td class="perm-name">
                        <?= htmlspecialchars($mnombre) ?>
                        <small><?= htmlspecialchars($mdesc) ?></small>
                    </td>
                    <?php foreach ([
                        ['puede_ver',      $pVer, 'Ver'],
                        ['puede_crear',    $pCre, 'Crear'],
                        ['puede_editar',   $pEdi, 'Editar'],
                        ['puede_eliminar', $pEli, 'Eliminar'],
                    ] as [$campo, $val, $label]): ?>
                    <td class="perm-check-cell">
                        <input type="checkbox"
                            class="perm-cb permiso-check"
                            data-rol="<?= $idRol ?>"
                            data-modulo="<?= $mk ?>"
                            data-campo="<?= $campo ?>"
                            title="<?= $label ?> - <?= htmlspecialchars($mnombre) ?> (<?= htmlspecialchars($rol['Nombre_Rol']) ?>)"
                            <?= $val ? 'checked' : '' ?>
                            <?= $esRolProtegido ? 'disabled' : '' ?>>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endforeach; ?>

                <?php
                    $modulosRestantes = array_diff_key($modulos, $modulosMostrados);
                ?>
                <?php if (!empty($modulosRestantes)): ?>
                <tr class="perm-group-header">
                    <td colspan="5">Otros modulos detectados</td>
                </tr>
                <?php foreach ($modulosRestantes as $mk => $mnombre):
                    $permiso = $permisos[$idRol][$mk] ?? null;
                    $pVer  = $permiso ? (int)$permiso['Puede_Ver']    : 0;
                    $pCre  = $permiso ? (int)$permiso['Puede_Crear']  : 0;
                    $pEdi  = $permiso ? (int)$permiso['Puede_Editar'] : 0;
                    $pEli  = $permiso ? (int)$permiso['Puede_Eliminar']:0;
                ?>
                <tr>
                    <td class="perm-name">
                        <?= htmlspecialchars((string)$mnombre) ?>
                        <small>Modulo detectado automaticamente en BD o codigo.</small>
                    </td>
                    <?php foreach ([
                        ['puede_ver',      $pVer, 'Ver'],
                        ['puede_crear',    $pCre, 'Crear'],
                        ['puede_editar',   $pEdi, 'Editar'],
                        ['puede_eliminar', $pEli, 'Eliminar'],
                    ] as [$campo, $val, $label]): ?>
                    <td class="perm-check-cell">
                        <input type="checkbox"
                            class="perm-cb permiso-check"
                            data-rol="<?= $idRol ?>"
                            data-modulo="<?= htmlspecialchars((string)$mk, ENT_QUOTES, 'UTF-8') ?>"
                            data-campo="<?= $campo ?>"
                            title="<?= $label ?> - <?= htmlspecialchars((string)$mnombre) ?> (<?= htmlspecialchars($rol['Nombre_Rol']) ?>)"
                            <?= $val ? 'checked' : '' ?>
                            <?= $esRolProtegido ? 'disabled' : '' ?>>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>

</div><!-- .perm-page -->

<!-- Toast de confirmacion -->
<div id="perm-toast"><i class="bi bi-check-circle"></i> <span id="perm-toast-msg">Permiso actualizado</span></div>

<script>
(function () {
    const ENDPOINT = <?= json_encode(
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
        . strtok($_SERVER['REQUEST_URI'] ?? '/public/', '?')
        . '?url=permisos/actualizar'
    ) ?>;

    /* Toast */
    let toastTimer = null;
    function showToast(msg, tipo) {
        const el = document.getElementById('perm-toast');
        const msgEl = document.getElementById('perm-toast-msg');
        msgEl.textContent = msg;
        el.classList.toggle('error', tipo === 'error');
        el.style.display = 'block';
        el.style.opacity = '1';
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { el.style.opacity = '0'; setTimeout(() => { el.style.display = 'none'; el.style.opacity = '1'; }, 300); }, 2500);
    }

    /* Actualizar badge de pestana */
    function actualizarBadge(idRol) {
        const panel = document.getElementById('rol-' + idRol);
        if (!panel) return;
        const total   = panel.querySelectorAll('.permiso-check[data-campo="puede_ver"]').length;
        const activos = panel.querySelectorAll('.permiso-check[data-campo="puede_ver"]:checked').length;
        const tab = document.querySelector(`.perm-tab-btn[data-tab="rol-${idRol}"] .perm-tab-badge`);
        if (tab) tab.textContent = activos + '/' + total;
    }

    /* Guardar un permiso */
    function guardarPermiso(cb, onError) {
        const { rol, modulo, campo } = cb.dataset;
        const valor = cb.checked ? 1 : 0;

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: `id_rol=${encodeURIComponent(rol)}&modulo=${encodeURIComponent(modulo)}&campo=${encodeURIComponent(campo)}&valor=${encodeURIComponent(valor)}`
        })
        .then(r => r.text()).then(txt => {
            const data = JSON.parse(txt);
            if (!data.success) throw new Error(data.error || 'Error desconocido');
            actualizarBadge(rol);
            showToast('Permiso actualizado correctamente');
        })
        .catch(err => {
            cb.checked = !cb.checked;
            showToast('Error: ' + err.message, 'error');
            if (onError) onError();
        });
    }

    /* Checkbox individual */
    document.querySelectorAll('.permiso-check').forEach(cb => {
        cb.addEventListener('change', function () { guardarPermiso(this); });
    });

    /* Botones masivos */
    document.querySelectorAll('.btn-perm-all').forEach(btn => {
        btn.addEventListener('click', function () {
            const idRol = this.dataset.rol;
            const accion = this.dataset.accion;

            const labels = { activar: 'Activar TODO', desactivar: 'Quitar TODO', solo_ver: 'Dejar solo lectura' };
            if (!confirm('¿' + (labels[accion] || accion) + ' para este rol?')) return;

            const panel = document.getElementById('rol-' + idRol);
            const cbs   = panel.querySelectorAll('.permiso-check');

            let lista = [];

            if (accion === 'activar') {
                cbs.forEach(cb => { cb.checked = true; lista.push(cb); });
            } else if (accion === 'desactivar') {
                cbs.forEach(cb => { cb.checked = false; lista.push(cb); });
            } else if (accion === 'solo_ver') {
                cbs.forEach(cb => {
                    cb.checked = (cb.dataset.campo === 'puede_ver');
                    lista.push(cb);
                });
            }

            cbs.forEach(cb => { cb.disabled = true; });

            const promesas = lista.map(cb => {
                const { rol, modulo, campo } = cb.dataset;
                const valor = cb.checked ? 1 : 0;
                return fetch(ENDPOINT, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                    body: `id_rol=${encodeURIComponent(rol)}&modulo=${encodeURIComponent(modulo)}&campo=${encodeURIComponent(campo)}&valor=${encodeURIComponent(valor)}`
                })
                .then(r => r.text())
                .then(txt => {
                    let data;
                    try {
                        data = JSON.parse(txt);
                    } catch (e) {
                        throw new Error('Respuesta inválida del servidor');
                    }

                    if (!data || !data.success) {
                        throw new Error(data?.error || 'Error al guardar permisos');
                    }

                    return true;
                });
            });

            Promise.all(promesas)
                .then(() => {
                    cbs.forEach(cb => { cb.disabled = false; });
                    actualizarBadge(idRol);
                    showToast('Permisos actualizados correctamente');
                })
                .catch(err => {
                    cbs.forEach(cb => { cb.disabled = false; });
                    showToast('Error al actualizar: ' + err.message, 'error');
                });
        });
    });

    /* Pestanas */
    document.querySelectorAll('.perm-tab-btn').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.perm-tab-btn').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.perm-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const panel = document.getElementById(this.dataset.tab);
            if (panel) panel.classList.add('active');
        });
    });

    const searchInput = document.getElementById('perm-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const term = String(this.value || '').toLowerCase().trim();
            document.querySelectorAll('.perm-panel').forEach((panel) => {
                const rows = panel.querySelectorAll('tbody tr:not(.perm-group-header)');
                rows.forEach((row) => {
                    const texto = String(row.querySelector('.perm-name')?.innerText || '').toLowerCase();
                    row.style.display = (term === '' || texto.includes(term)) ? '' : 'none';
                });

                panel.querySelectorAll('tbody tr.perm-group-header').forEach((headerRow) => {
                    let next = headerRow.nextElementSibling;
                    let hayVisible = false;
                    while (next && !next.classList.contains('perm-group-header')) {
                        if (next.style.display !== 'none') {
                            hayVisible = true;
                            break;
                        }
                        next = next.nextElementSibling;
                    }
                    headerRow.style.display = hayVisible ? '' : 'none';
                });
            });
        });
    }

})();
</script>

<?php require_once VIEWS . '/layout/footer.php'; ?>
