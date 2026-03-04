<?php include VIEWS . '/layout/header.php'; ?>
<?php
    $permisosUi = is_array($permisosUi ?? null) ? $permisosUi : [];
    $puedeVerCedula = !empty($permisosUi['ver_cedula']);
    $puedeVerTelefono = !empty($permisosUi['ver_telefono']);
    $puedeVerSubidoLink = !empty($permisosUi['ver_subido_link']);
    $puedeVerBogotaSubio = !empty($permisosUi['ver_bogota_subio']);
    $puedeVerPuesto = !empty($permisosUi['ver_puesto']);
    $puedeVerMesa = !empty($permisosUi['ver_mesa']);
    $puedeVerAcepta = !empty($permisosUi['ver_acepta']);
    $mostrarBotonEditar = !empty($permisosUi['mostrar_boton_editar']);
    $mostrarBotonEliminar = !empty($permisosUi['mostrar_boton_eliminar']);
    $mostrarAcciones = $mostrarBotonEditar || $mostrarBotonEliminar;
    $totalColumnasTabla = 5
        + ($puedeVerCedula ? 1 : 0)
        + ($puedeVerTelefono ? 1 : 0)
        + ($puedeVerSubidoLink ? 1 : 0)
        + ($puedeVerBogotaSubio ? 1 : 0)
        + ($puedeVerPuesto ? 1 : 0)
        + ($puedeVerMesa ? 1 : 0)
        + ($puedeVerAcepta ? 1 : 0)
        + 1
        + 1
        + 1
        + ($mostrarAcciones ? 1 : 0);
?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center module-header-card">
        <h2 class="page-title">
            <i class="bi bi-clipboard-data"></i> Nehemias - Registros
            <?php 
            $totalFiltros = 0;
            foreach ($filtros as $clave => $filtro) {
                if ($clave === 'lider_lista') {
                    continue;
                }
                if ($filtro !== '' && $filtro !== null) $totalFiltros++;
            }
            if ($totalFiltros > 0): ?>
                <span class="badge-filter"><?= $totalFiltros ?> filtro<?= $totalFiltros > 1 ? 's' : '' ?> activo<?= $totalFiltros > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h2>
        <div>
            <a href="?url=nehemias/whatsapp-campanas" class="btn btn-success btn-action me-2">
                <i class="bi bi-whatsapp"></i> WhatsApp Campañas
            </a>
            <a href="?url=nehemias/seremos1200" class="btn btn-warning btn-action me-2">
                <i class="bi bi-people"></i> Seremos 1200
            </a>
            <a href="?url=nehemias/exportarExcel" class="btn btn-success btn-action">
                <i class="bi bi-file-earmark-excel-fill"></i> Exportar Excel
            </a>
            <a href="?url=nehemias/reportes" class="btn btn-primary btn-action ms-2">
                <i class="bi bi-graph-up"></i> Reportes
            </a>
        </div>
    </div>

    <?php if (!empty($mensaje)): ?>
        <?php
            $alertClass = 'alert-info';
            if (($tipo ?? '') === 'success') {
                $alertClass = 'alert-success';
            } elseif (($tipo ?? '') === 'error') {
                $alertClass = 'alert-danger';
            } elseif (($tipo ?? '') === 'warning') {
                $alertClass = 'alert-warning';
            }
        ?>
        <div class="alert <?= $alertClass ?> mt-3" role="alert">
            <?= htmlspecialchars((string)$mensaje) ?>
        </div>
    <?php endif; ?>

    <!-- Formulario de Filtros -->
    <div class="filter-card">
        <div class="filter-title">
            <i class="bi bi-funnel-fill"></i> Filtros de Búsqueda
        </div>
        <form method="GET" action="<?= PUBLIC_URL ?>">
            <input type="hidden" name="url" value="nehemias/lista">
            
            <div class="row g-3">
                <!-- Búsqueda general -->
                <div class="col-md-6">
                    <label class="form-label fw-bold">Buscar por Nombre, Apellido o Cédula</label>
                    <input type="text" name="busqueda" class="form-control" 
                           placeholder="Escriba para buscar..." 
                           value="<?= htmlspecialchars($filtros['busqueda']) ?>">
                </div>

                <!-- Líder Nehemías -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Líder Nehemías</label>
                    <input type="text" name="lider_nehemias" class="form-control" 
                           placeholder="Nombre del líder Nehemías" 
                           value="<?= htmlspecialchars($filtros['lider_nehemias']) ?>">
                </div>

                <!-- Líder (Ministerio) -->
                <div class="col-md-3">
                    <label class="form-label fw-bold">Ministerio</label>
                    <select name="lider" class="form-select">
                        <?php if (empty($filtroLiderRestringido)): ?>
                            <option value="">Todos</option>
                        <?php else: ?>
                            <option value="">Seleccione</option>
                        <?php endif; ?>
                        <?php foreach ($ministeriosNehemias as $ministerio): ?>
                            <option value="<?= htmlspecialchars($ministerio) ?>" <?= $filtros['lider'] === $ministerio ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio) ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($filtroLiderRestringido)): ?>
                            <option value="__otros__" <?= $filtros['lider'] === '__otros__' ? 'selected' : '' ?>>Otros</option>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Filtros de checkbox -->
                <div class="col-md-12">
                    <label class="form-label fw-bold">Filtros Rápidos</label>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="puesto_vacio" value="1" 
                                       id="puesto_vacio" <?= !empty($filtros['puesto_vacio']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="puesto_vacio">
                                    Puesto de Votación Vacío
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="puesto_lleno" value="1" 
                                       id="puesto_lleno" <?= !empty($filtros['puesto_lleno']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="puesto_lleno">
                                    Puesto de Votación Completo
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mesa_vacia" value="1" 
                                       id="mesa_vacia" <?= !empty($filtros['mesa_vacia']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mesa_vacia">
                                    Mesa de Votación Vacía
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mesa_llena" value="1" 
                                       id="mesa_llena" <?= !empty($filtros['mesa_llena']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="mesa_llena">
                                    Mesa de Votación Completa
                                </label>
                            </div>
                        </div>
                        <div class="col-md-3 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="cedula_vacia" value="1" 
                                       id="cedula_vacia" <?= !empty($filtros['cedula_vacia']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cedula_vacia">
                                    Cédula Vacía
                                </label>
                            </div>
                        </div>
                        <?php if ($puedeVerSubidoLink): ?>
                            <div class="col-md-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="subido_link_vacio" value="1" 
                                           id="subido_link_vacio" <?= !empty($filtros['subido_link_vacio']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="subido_link_vacio">
                                        Subido link vacío
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="subido_link_lleno" value="1" 
                                           id="subido_link_lleno" <?= !empty($filtros['subido_link_lleno']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="subido_link_lleno">
                                        Subido link completo
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($puedeVerBogotaSubio): ?>
                            <div class="col-md-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="bogota_subio_vacio" value="1" 
                                           id="bogota_subio_vacio" <?= !empty($filtros['bogota_subio_vacio']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="bogota_subio_vacio">
                                        En Bogotá se le subió vacío
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3 mt-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="bogota_subio_lleno" value="1" 
                                           id="bogota_subio_lleno" <?= !empty($filtros['bogota_subio_lleno']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="bogota_subio_lleno">
                                        En Bogotá se le subió completo
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if ($puedeVerAcepta): ?>
                            <div class="col-md-3 mt-2">
                                <label class="form-label mb-1" style="font-size: 12px;">Acepta</label>
                                <select name="acepta" class="form-select form-select-sm">
                                    <option value="">Todos</option>
                                    <option value="1" <?= $filtros['acepta'] === '1' ? 'selected' : '' ?>>Sí</option>
                                    <option value="0" <?= $filtros['acepta'] === '0' ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-3 mt-2">
                            <label class="form-label mb-1" style="font-size: 12px;">Mensaje 1 enviado</label>
                            <select name="mesaje_1enviado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1" <?= ($filtros['mesaje_1enviado'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= ($filtros['mesaje_1enviado'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-2">
                            <label class="form-label mb-1" style="font-size: 12px;">No recibir más</label>
                            <select name="no_recibir_mas" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="1" <?= ($filtros['no_recibir_mas'] ?? '') === '1' ? 'selected' : '' ?>>Sí</option>
                                <option value="0" <?= ($filtros['no_recibir_mas'] ?? '') === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-3 mt-2">
                            <label class="form-label mb-1" style="font-size: 12px;">Fecha envío mensaje 1</label>
                            <select name="mesaje1_fehca_estado" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="con_fecha" <?= ($filtros['mesaje1_fehca_estado'] ?? '') === 'con_fecha' ? 'selected' : '' ?>>Con fecha</option>
                                <option value="sin_fecha" <?= ($filtros['mesaje1_fehca_estado'] ?? '') === 'sin_fecha' ? 'selected' : '' ?>>Sin fecha</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="col-md-12">
                    <button type="submit" class="btn btn-filter">
                        <i class="bi bi-search"></i> Aplicar Filtros
                    </button>
                    <a href="?url=nehemias/lista" class="btn btn-clear">
                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                    </a>
                    <span class="ms-3 text-muted">
                        <strong><?= count($registros) ?></strong> registro<?= count($registros) !== 1 ? 's' : '' ?> encontrado<?= count($registros) !== 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive nehemias-table-wrap">
                <table class="table table-sm table-hover table-no-card nehemias-table nehemias-table-main" id="nehemias-edit-table">
                    <thead>
                        <tr>
                            <th class="col-nombres">Nombres</th>
                            <th class="col-apellidos">Apellidos</th>
                            <?php if ($puedeVerCedula): ?>
                                <th class="col-cedula">Cedula</th>
                            <?php endif; ?>
                            <?php if ($puedeVerTelefono): ?>
                                <th class="col-telefono">Telefono</th>
                            <?php endif; ?>
                            <th class="col-lider">Lider</th>
                                <th class="col-lider-nehemias">Lider Neh.</th>
                            <?php if ($puedeVerSubidoLink): ?>
                                <th class="col-subido-link">Subido link</th>
                            <?php endif; ?>
                            <?php if ($puedeVerBogotaSubio): ?>
                                <th class="col-bogota-subio">En Bogota se le subio</th>
                            <?php endif; ?>
                            <?php if ($puedeVerPuesto): ?>
                                <th class="col-puesto">Puesto</th>
                            <?php endif; ?>
                            <?php if ($puedeVerMesa): ?>
                                <th class="col-mesa">Mesa</th>
                            <?php endif; ?>
                            <?php if ($puedeVerAcepta): ?>
                                <th class="col-acepta">Acepta</th>
                            <?php endif; ?>
                            <th class="col-mesaje-1enviado">Msg1 env.</th>
                            <th class="col-no-recibir-mas">No recibir</th>
                            <th class="col-mesaje1-fehca">Fecha msg1</th>
                            <th class="col-fecha">Fecha reg.</th>
                            <?php if ($mostrarAcciones): ?>
                                <th class="col-acciones">Acciones</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($registros)): ?>
                            <?php foreach ($registros as $registro): ?>
                                <tr>
                                    <td class="col-nombres" data-label="Nombres"><?= htmlspecialchars($registro['Nombres']) ?></td>
                                    <td class="col-apellidos" data-label="Apellidos"><?= htmlspecialchars($registro['Apellidos']) ?></td>
                                    <?php if ($puedeVerCedula): ?>
                                        <td class="col-cedula" data-label="Cedula"><?= htmlspecialchars($registro['Numero_Cedula']) ?></td>
                                    <?php endif; ?>
                                    <?php if ($puedeVerTelefono): ?>
                                        <td class="col-telefono" data-label="Telefono"><?= htmlspecialchars($registro['Telefono']) ?></td>
                                    <?php endif; ?>
                                    <td class="col-lider" data-label="Lider"><?= htmlspecialchars($registro['Lider']) ?></td>
                                    <td class="col-lider-nehemias" data-label="Lider Nehemias"><?= htmlspecialchars($registro['Lider_Nehemias']) ?></td>
                                    <?php if ($puedeVerSubidoLink): ?>
                                        <td class="col-subido-link" data-label="Subido link"><?= htmlspecialchars($registro['Subido_Link'] ?? '') ?></td>
                                    <?php endif; ?>
                                    <?php if ($puedeVerBogotaSubio): ?>
                                        <td class="col-bogota-subio" data-label="En Bogota se le subio"><?= htmlspecialchars($registro['En_Bogota_Subio'] ?? '') ?></td>
                                    <?php endif; ?>
                                    <?php if ($puedeVerPuesto): ?>
                                        <td class="col-puesto" data-label="Puesto de votacion"><?= htmlspecialchars($registro['Puesto_Votacion'] ?? '') ?></td>
                                    <?php endif; ?>
                                    <?php if ($puedeVerMesa): ?>
                                        <td class="col-mesa" data-label="Mesa de votacion"><?= htmlspecialchars($registro['Mesa_Votacion'] ?? '') ?></td>
                                    <?php endif; ?>
                                    <?php if ($puedeVerAcepta): ?>
                                        <td class="col-acepta" data-label="Acepta"><?= ((int)($registro['Acepta'] ?? 0) === 1) ? 'Si' : 'No' ?></td>
                                    <?php endif; ?>
                                    <td class="col-mesaje-1enviado" data-label="Mensaje 1 enviado"><?= ((int)($registro['mesaje_1enviado'] ?? 0) === 1) ? 'Si' : 'No' ?></td>
                                    <td class="col-no-recibir-mas" data-label="No recibir más"><?= ((int)($registro['no_recibir_mas'] ?? 0) === 1) ? 'Si' : 'No' ?></td>
                                    <td class="col-mesaje1-fehca" data-label="Fecha envío mensaje 1">
                                        <?php
                                            $valorFechaEnvioMensaje1 = (string)($registro['mesaje1_fehca'] ?? '');
                                            $fechaEnvioMensaje1 = '';
                                            if ($valorFechaEnvioMensaje1 !== '' && is_numeric($valorFechaEnvioMensaje1)) {
                                                $timestampFechaEnvio = (int)$valorFechaEnvioMensaje1;
                                                if ($timestampFechaEnvio > 9999999999) {
                                                    $timestampFechaEnvio = (int)floor($timestampFechaEnvio / 1000);
                                                }
                                                if ($timestampFechaEnvio > 0) {
                                                    $fechaEnvioMensaje1 = date('Y-m-d H:i:s', $timestampFechaEnvio);
                                                }
                                            }
                                            echo htmlspecialchars($fechaEnvioMensaje1 !== '' ? $fechaEnvioMensaje1 : $valorFechaEnvioMensaje1);
                                        ?>
                                    </td>
                                    <td class="col-fecha" data-label="Fecha Registro"><?= htmlspecialchars($registro['Fecha_Registro']) ?></td>
                                    <?php if ($mostrarAcciones): ?>
                                        <td class="col-acciones" data-label="Acciones">
                                            <div class="acciones-wrap">
                                            <?php
                                                $registroPayload = [
                                                    'id' => (int)$registro['Id_Nehemias'],
                                                    'nombres' => (string)($registro['Nombres'] ?? ''),
                                                    'apellidos' => (string)($registro['Apellidos'] ?? ''),
                                                    'numero_cedula' => (string)($registro['Numero_Cedula'] ?? ''),
                                                    'telefono' => (string)($registro['Telefono'] ?? ''),
                                                    'telefono_normalizado' => (string)($registro['Telefono_Normalizado'] ?? ''),
                                                    'lider' => (string)($registro['Lider'] ?? ''),
                                                    'lider_nehemias' => (string)($registro['Lider_Nehemias'] ?? ''),
                                                    'subido_link' => (string)($registro['Subido_Link'] ?? ''),
                                                    'en_bogota_subio' => (string)($registro['En_Bogota_Subio'] ?? ''),
                                                    'puesto_votacion' => (string)($registro['Puesto_Votacion'] ?? ''),
                                                    'mesa_votacion' => (string)($registro['Mesa_Votacion'] ?? ''),
                                                    'acepta' => (int)($registro['Acepta'] ?? 0),
                                                    'consentimiento_whatsapp' => (int)($registro['Consentimiento_Whatsapp'] ?? 0),
                                                    'fecha_registro' => (string)($registro['Fecha_Registro'] ?? ''),
                                                ];
                                                $registroPayloadJson = htmlspecialchars(json_encode($registroPayload, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                                            ?>
                                            <?php if ($mostrarBotonEditar): ?>
                                                <button type="button" class="btn btn-sm btn-edit btn-open-edit-modal btn-accion-icon" data-record="<?= $registroPayloadJson ?>" title="Editar" aria-label="Editar">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($mostrarBotonEliminar): ?>
                                                <form method="POST" action="?url=nehemias/eliminar" class="d-inline" onsubmit="return confirm('¿Seguro que quieres eliminar este registro?');">
                                                    <input type="hidden" name="id" value="<?= (int)$registro['Id_Nehemias'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger btn-accion-icon" title="Eliminar" aria-label="Eliminar">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="<?= $totalColumnasTabla ?>" class="text-center">No hay registros.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .nehemias-table-wrap {
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: min(68vh, calc(100vh - 260px));
    }

    #nehemias-edit-table {
        table-layout: auto !important;
        width: max-content;
        font-size: 12px;
    }

    #nehemias-edit-table th,
    #nehemias-edit-table td {
        white-space: nowrap;
        word-break: break-word;
        line-height: 1.15;
        vertical-align: top;
        padding: 0.3rem 0.4rem;
    }

    #nehemias-edit-table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #e9edf5;
        box-shadow: inset 0 -1px 0 #cfd6e3;
    }

    #nehemias-edit-table .col-acciones {
        position: sticky;
        right: 0;
        z-index: 6;
        width: 72px;
        min-width: 72px;
        max-width: 72px;
        background: #fff;
        box-shadow: -1px 0 0 #d6dbe5;
        padding-left: 0.25rem;
        padding-right: 0.25rem;
    }

    #nehemias-edit-table thead .col-acciones {
        z-index: 8;
        background: #e9edf5;
    }

    #nehemias-edit-table .acciones-wrap {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: center;
        gap: 3px;
    }

    #nehemias-edit-table .acciones-wrap form {
        margin: 0;
    }

    #nehemias-edit-table .btn-accion-icon {
        width: 26px;
        height: 26px;
        min-width: 26px;
        max-width: 26px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
        font-size: 13px;
        line-height: 1;
    }

    #nehemias-edit-table .col-nombres,
    #nehemias-edit-table .col-apellidos,
    #nehemias-edit-table .col-lider,
    #nehemias-edit-table .col-lider-nehemias {
        min-width: 105px;
    }

    #nehemias-edit-table .col-subido-link,
    #nehemias-edit-table .col-bogota-subio,
    #nehemias-edit-table .col-puesto,
    #nehemias-edit-table .col-mesa {
        min-width: 68px;
        max-width: 110px;
        width: 78px;
    }

    #nehemias-edit-table .col-acepta,
    #nehemias-edit-table .col-mesaje-1enviado,
    #nehemias-edit-table .col-no-recibir-mas {
        min-width: 70px;
        text-align: center;
    }

    #nehemias-edit-table .col-mesaje1-fehca,
    #nehemias-edit-table .col-fecha {
        min-width: 105px;
    }

    #modalEditarNehemias .field-modified {
        background-color: #fffde7;
        border-color: #f4e58a;
    }

    #modalEditarNehemias {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        background: rgba(17, 24, 39, 0.5);
        z-index: 2000;
        padding: 16px;
    }

    #modalEditarNehemias.show {
        display: flex;
    }

    #modalEditarNehemias .custom-modal-dialog {
        width: min(1100px, 100%);
        max-height: calc(100vh - 32px);
        background: #fff;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    #modalEditarNehemias .custom-modal-header,
    #modalEditarNehemias .custom-modal-footer {
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    #modalEditarNehemias .custom-modal-footer {
        border-bottom: 0;
        border-top: 1px solid #e5e7eb;
        justify-content: flex-end;
        gap: 8px;
    }

    #modalEditarNehemias .custom-modal-body {
        padding: 16px;
        overflow-y: auto;
        max-height: calc(100vh - 190px);
    }

    #modalEditarNehemias .custom-modal-close {
        border: 0;
        background: transparent;
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
    }
</style>

<?php if ($mostrarBotonEditar): ?>
<div id="modalEditarNehemias" aria-hidden="true">
    <div class="custom-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modalEditarTitulo">
            <form method="POST" action="?url=nehemias/actualizar" id="formEditarNehemiasModal">
                <div class="custom-modal-header">
                    <h5 class="modal-title" id="modalEditarTitulo">Editar registro Nehemias</h5>
                    <button type="button" class="custom-modal-close" id="btnCloseModalNehemias" aria-label="Cerrar">&times;</button>
                </div>
                <div class="custom-modal-body">
                    <input type="hidden" name="id" id="edit-id">

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">ID</label>
                            <input type="text" class="form-control" id="edit-id-readonly" disabled>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nombres</label>
                            <input type="text" class="form-control modal-editable" name="nombres" id="edit-nombres">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos</label>
                            <input type="text" class="form-control modal-editable" name="apellidos" id="edit-apellidos">
                        </div>

                        <?php if ($puedeVerCedula): ?>
                            <div class="col-md-4">
                                <label class="form-label">Numero de Cedula</label>
                                <input type="text" class="form-control modal-editable" name="numero_cedula" id="edit-numero-cedula">
                            </div>
                        <?php endif; ?>
                        <?php if ($puedeVerTelefono): ?>
                            <div class="col-md-4">
                                <label class="form-label">Telefono</label>
                                <input type="text" class="form-control modal-editable" name="telefono" id="edit-telefono">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telefono normalizado</label>
                                <input type="text" class="form-control" id="edit-telefono-normalizado" disabled>
                            </div>
                        <?php endif; ?>
                        <?php if ($puedeVerAcepta): ?>
                            <div class="col-md-4">
                                <label class="form-label">Acepta</label>
                                <select class="form-select modal-editable" name="acepta" id="edit-acepta">
                                    <option value="1">Si</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <label class="form-label">Consentimiento WhatsApp</label>
                            <select class="form-select modal-editable" name="consentimiento_whatsapp" id="edit-consentimiento-whatsapp">
                                <option value="1">Si</option>
                                <option value="0">No</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Ministerio (Lider)</label>
                            <select class="form-select modal-editable" name="lider" id="edit-lider">
                                <option value="">Seleccione</option>
                                <?php foreach ($ministeriosNehemias as $ministerio): ?>
                                    <option value="<?= htmlspecialchars($ministerio) ?>"><?= htmlspecialchars($ministerio) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lider Nehemias</label>
                            <input type="text" class="form-control modal-editable" name="lider_nehemias" id="edit-lider-nehemias">
                        </div>

                        <?php if ($puedeVerSubidoLink): ?>
                            <div class="col-md-6">
                                <label class="form-label">Subido link</label>
                                <input type="text" class="form-control modal-editable" name="subido_link" id="edit-subido-link">
                            </div>
                        <?php endif; ?>
                        <?php if ($puedeVerBogotaSubio): ?>
                            <div class="col-md-6">
                                <label class="form-label">En Bogota se le subio</label>
                                <input type="text" class="form-control modal-editable" name="en_bogota_subio" id="edit-en-bogota-subio">
                            </div>
                        <?php endif; ?>

                        <?php if ($puedeVerPuesto): ?>
                            <div class="col-md-6">
                                <label class="form-label">Puesto de votacion</label>
                                <input type="text" class="form-control modal-editable" name="puesto_votacion" id="edit-puesto-votacion">
                            </div>
                        <?php endif; ?>
                        <?php if ($puedeVerMesa): ?>
                            <div class="col-md-6">
                                <label class="form-label">Mesa de votacion</label>
                                <input type="text" class="form-control modal-editable" name="mesa_votacion" id="edit-mesa-votacion">
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label">Fecha Registro</label>
                            <input type="text" class="form-control modal-editable" name="fecha_registro" id="edit-fecha-registro">
                        </div>
                    </div>
                </div>
                <div class="custom-modal-footer">
                    <button type="button" class="btn btn-secondary" id="btnCancelarModalNehemias">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </div>
            </form>
    </div>
</div>
<?php endif; ?>

<script>
    (function () {
        var modal = document.getElementById('modalEditarNehemias');
        var btnCloseModal = document.getElementById('btnCloseModalNehemias');
        var btnCancelarModal = document.getElementById('btnCancelarModalNehemias');

        function openModal() {
            if (!modal) return;
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            if (!modal) return;
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        if (btnCloseModal) {
            btnCloseModal.addEventListener('click', closeModal);
        }
        if (btnCancelarModal) {
            btnCancelarModal.addEventListener('click', closeModal);
        }
        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }

        function setInputValue(id, value) {
            var input = document.getElementById(id);
            if (!input) return;
            input.value = value == null ? '' : value;
            input.setAttribute('data-original', input.value);
            input.classList.remove('field-modified');
        }

        function ensureLiderOption(value) {
            var select = document.getElementById('edit-lider');
            if (!select) return;
            var v = value == null ? '' : String(value);
            var exists = false;
            Array.prototype.forEach.call(select.options, function (opt) {
                if (opt.value === v) {
                    exists = true;
                }
            });
            if (!exists && v !== '') {
                var option = document.createElement('option');
                option.value = v;
                option.textContent = v + ' (actual)';
                option.setAttribute('data-temp', '1');
                select.appendChild(option);
            }
            select.value = v;
            select.setAttribute('data-original', select.value);
            select.classList.remove('field-modified');
        }

        function clearTempLiderOptions() {
            var select = document.getElementById('edit-lider');
            if (!select) return;
            Array.prototype.slice.call(select.querySelectorAll('option[data-temp="1"]')).forEach(function (opt) {
                opt.remove();
            });
        }

        function cargarRegistroEnModal(button) {
            clearTempLiderOptions();
            var raw = button.getAttribute('data-record') || '{}';
            var record = {};
            try {
                record = JSON.parse(raw);
            } catch (e) {
                record = {};
            }

            setInputValue('edit-id', record.id || '');
            setInputValue('edit-id-readonly', record.id || '');
            setInputValue('edit-nombres', record.nombres || '');
            setInputValue('edit-apellidos', record.apellidos || '');
            setInputValue('edit-numero-cedula', record.numero_cedula || '');
            setInputValue('edit-telefono', record.telefono || '');
            setInputValue('edit-telefono-normalizado', record.telefono_normalizado || '');
            ensureLiderOption(record.lider || '');
            setInputValue('edit-lider-nehemias', record.lider_nehemias || '');
            setInputValue('edit-subido-link', record.subido_link || '');
            setInputValue('edit-en-bogota-subio', record.en_bogota_subio || '');
            setInputValue('edit-puesto-votacion', record.puesto_votacion || '');
            setInputValue('edit-mesa-votacion', record.mesa_votacion || '');
            setInputValue('edit-fecha-registro', record.fecha_registro || '');
            setInputValue('edit-acepta', String(record.acepta === 1 ? 1 : 0));
            setInputValue('edit-consentimiento-whatsapp', String(record.consentimiento_whatsapp === 1 ? 1 : 0));
            openModal();
        }

        document.querySelectorAll('.btn-open-edit-modal').forEach(function (button) {
            button.addEventListener('click', function () {
                cargarRegistroEnModal(button);
            });
        });

        document.querySelectorAll('#formEditarNehemiasModal .modal-editable').forEach(function (input) {
            input.addEventListener('input', function () {
                var original = input.getAttribute('data-original') || '';
                if (input.value !== original) {
                    input.classList.add('field-modified');
                } else {
                    input.classList.remove('field-modified');
                }
            });
            input.addEventListener('change', function () {
                var original = input.getAttribute('data-original') || '';
                if (input.value !== original) {
                    input.classList.add('field-modified');
                } else {
                    input.classList.remove('field-modified');
                }
            });
        });
    })();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
