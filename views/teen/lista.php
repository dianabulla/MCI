<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Material Teens</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>index.php?url=teen/registro-menores" class="btn btn-sm btn-secondary">
            Registrar menor
        </a>
        <?php if (AuthController::tienePermiso('teen', 'crear')): ?>
            <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('formSubidaTeen').scrollIntoView({ behavior: 'smooth' });">
                Subir PDF
            </button>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($mensaje)): ?>
    <div class="alert alert-<?= ($tipo ?? '') === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 15px;">
        <?= htmlspecialchars($mensaje) ?>
    </div>
<?php endif; ?>

<?php if (AuthController::tienePermiso('teen', 'crear')): ?>
<div class="card" id="formSubidaTeen" style="margin-bottom: 20px;">
    <div class="card-body">
        <h3 style="margin-top: 0;">Subir material PDF</h3>

        <form action="<?= PUBLIC_URL ?>index.php?url=teen" method="POST" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom: 15px;">
                <label for="titulo">Título</label>
                <input
                    type="text"
                    id="titulo"
                    name="titulo"
                    class="form-control"
                    required
                    maxlength="255"
                    placeholder="Ej: Guía Semana 1"
                >
                <small style="display:block; margin-top:4px; color:#666;">Se aplicará a todos los archivos subidos</small>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="descripcion">Descripción</label>
                <textarea
                    id="descripcion"
                    name="descripcion"
                    class="form-control"
                    rows="3"
                    placeholder="Descripción opcional del material"
                ></textarea>
                <small style="display:block; margin-top:4px; color:#666;">Se aplicará a todos los archivos subidos</small>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label for="archivo_pdf">Archivos PDF (varios permitidos)</label>
                <input
                    type="file"
                    id="archivo_pdf"
                    name="archivo_pdf[]"
                    class="form-control"
                    accept="application/pdf"
                    multiple
                    required
                >
                <small style="display:block; margin-top:6px; color:#666;">
                    Solo se permiten archivos PDF. Tamaño máximo: 20MB.
                </small>
            </div>

            <button type="submit" class="btn btn-success">
                Subir material
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h3 style="margin-top: 0;">Materiales cargados</h3>

        <?php if (!empty($materiales)): ?>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Descripción</th>
                            <th>Archivos</th>
                            <th>Peso Total</th>
                            <th>Vistas</th>
                            <th>Fecha</th>
                            <th style="min-width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($materiales as $material): ?>
                            <tr>
                                <td><?= htmlspecialchars($material['titulo'] ?? '') ?></td>
                                <td><?= htmlspecialchars($material['descripcion'] ?? 'Sin descripción') ?></td>
                                <td>
                                    <?php $archivos = $material['archivos'] ?? []; ?>
                                    <strong><?= count($archivos) ?></strong> archivo(s)
                                    <?php if (count($archivos) > 0): ?>
                                        <ul style="margin: 5px 0 0 0; padding-left: 20px; font-size: 12px;">
                                            <?php foreach ($archivos as $archivo): ?>
                                                <li><?= htmlspecialchars($archivo['nombre']) ?> (<?= number_format((float)$archivo['peso_kb'], 2) ?> KB)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </td>
                                <td><?= number_format((float)($material['peso_total_kb'] ?? 0), 2) ?> KB</td>
                                <td>
                                    <span class="badge bg-info">
                                        <?= (int)($material['vistas_totales'] ?? 0) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                        $fecha = $material['created_at'] ?? '';
                                        echo $fecha ? htmlspecialchars($fecha) : 'N/A';
                                    ?>
                                </td>
                                <td>
                                    <?php $archivos = $material['archivos'] ?? []; ?>
                                    <?php if (count($archivos) > 0): ?>
                                        <div style="display: flex; gap: 5px; flex-wrap: wrap; align-items: flex-start;">
                                            <!-- Botones de descarga para CADA archivo -->
                                            <div style="display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px;">
                                                <?php foreach ($archivos as $archivo): ?>
                                                    <a
                                                        href="<?= PUBLIC_URL ?>index.php?url=teen/verPdf&archivo=<?= rawurlencode($archivo['nombre']) ?>"
                                                        target="_blank"
                                                        class="btn btn-sm btn-primary"
                                                        title="Descargar: <?= htmlspecialchars($archivo['nombre']) ?>"
                                                        style="text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                                    >
                                                        📥 <?= htmlspecialchars(basename($archivo['nombre'], '.pdf'), ENT_QUOTES, 'UTF-8') ?>
                                                    </a>
                                                <?php endforeach; ?>
                                            </div>

                                            <!-- Botones de acciones comunes -->
                                            <div style="display: flex; flex-direction: column; gap: 5px;">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-info"
                                                    onclick="verDetalleVistas('<?= htmlspecialchars($archivos[0]['nombre'], ENT_QUOTES, 'UTF-8') ?>')"
                                                    title="Ver quién vio"
                                                >
                                                    Vistas
                                                </button>

                                                <?php if (AuthController::tienePermiso('teen', 'editar')): ?>
                                                    <a
                                                        href="<?= PUBLIC_URL ?>index.php?url=teen/editar&id=<?= (int)($material['id'] ?? 0) ?>"
                                                        class="btn btn-sm btn-warning"
                                                        title="Editar módulo"
                                                    >
                                                        Editar
                                                    </a>
                                                <?php endif; ?>

                                                <?php if (AuthController::tienePermiso('teen', 'eliminar')): ?>
                                                    <a
                                                        href="<?= PUBLIC_URL ?>index.php?url=teen/eliminar&id=<?= (int)($material['id'] ?? 0) ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('¿Seguro que deseas eliminar este módulo y todos sus archivos?');"
                                                    >
                                                        Eliminar
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-center" style="padding: 20px; color: #666;">
                No hay materiales cargados todavía.
            </p>
        <?php endif; ?>
    </div>
</div>

<!-- Modal detalle de vistas -->
<div id="modalVistasTeen" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 1000px; width: 95%;">
        <div class="modal-header">
            <h3 id="tituloModalVistasTeen">Detalle de visualizaciones</h3>
            <span class="close" onclick="cerrarModalVistasTeen()">&times;</span>
        </div>
        <div class="modal-body">
            <div id="contenidoModalVistasTeen">
                <p>Cargando...</p>
            </div>
        </div>
    </div>
</div>

<style>
    .badge.bg-info {
        background: #17a2b8;
        color: #fff;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
    }

    .modal {
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background: rgba(0,0,0,.5);
    }

    .modal-content {
        background: #fff;
        margin: 4% auto;
        border-radius: 8px;
        box-shadow: 0 8px 24px rgba(0,0,0,.2);
        overflow: hidden;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 20px;
        border-bottom: 1px solid #eee;
    }

    .modal-body {
        padding: 20px;
        max-height: 70vh;
        overflow-y: auto;
    }

    .close {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .table-modal {
        width: 100%;
        border-collapse: collapse;
    }

    .table-modal th,
    .table-modal td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        font-size: 14px;
    }

    .table-modal th {
        background: #f8f9fa;
    }
</style>

<script>
function verDetalleVistas(archivo) {
    const modal = document.getElementById('modalVistasTeen');
    const contenido = document.getElementById('contenidoModalVistasTeen');
    const titulo = document.getElementById('tituloModalVistasTeen');

    modal.style.display = 'block';
    titulo.textContent = 'Detalle de visualizaciones';
    contenido.innerHTML = '<p>Cargando...</p>';

    fetch('<?= PUBLIC_URL ?>index.php?url=teen/detalleVistas&archivo=' + encodeURIComponent(archivo))
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                contenido.innerHTML = '<p style="color:red;">' + (data.message || 'Error al consultar la información') + '</p>';
                return;
            }

            let html = '';
            html += '<p><strong>Archivo:</strong> ' + escaparHtml(data.archivo) + '</p>';
            html += '<p><strong>Total de personas:</strong> ' + (data.total_personas || 0) + '</p>';

            if (!data.vistas || data.vistas.length === 0) {
                html += '<p style="padding: 15px; color: #666;">Aún no hay visualizaciones registradas para este PDF.</p>';
            } else {
                html += `
                    <div class="table-container">
                        <table class="table-modal">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Teléfono</th>
                                    <th>Ministerio</th>
                                    <th>Total vistas</th>
                                    <th>Primera vista</th>
                                    <th>Última vista</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                data.vistas.forEach(item => {
                    const nombre = ((item.Nombre || '') + ' ' + (item.Apellido || '')).trim() || 'Sin nombre';
                    html += `
                        <tr>
                            <td>${escaparHtml(nombre)}</td>
                            <td>${escaparHtml(item.Telefono || '')}</td>
                            <td>${escaparHtml(item.Nombre_Ministerio || '')}</td>
                            <td>${item.total_vistas || 0}</td>
                            <td>${escaparHtml(item.fecha_primera_vista || '')}</td>
                            <td>${escaparHtml(item.fecha_ultima_vista || '')}</td>
                        </tr>
                    `;
                });

                html += `
                            </tbody>
                        </table>
                    </div>
                `;
            }

            contenido.innerHTML = html;
        })
        .catch(() => {
            contenido.innerHTML = '<p style="color:red;">No se pudo consultar la información.</p>';
        });
}

function cerrarModalVistasTeen() {
    document.getElementById('modalVistasTeen').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('modalVistasTeen');
    if (event.target === modal) {
        cerrarModalVistasTeen();
    }
};

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto === null || texto === undefined ? '' : texto;
    return div.innerHTML;
}
</script>

<?php include VIEWS . '/layout/footer.php'; ?>