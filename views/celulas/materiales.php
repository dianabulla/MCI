
<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Material para Células (PDF)</h2>
    <a href="<?= PUBLIC_URL ?>?url=celulas" class="btn btn-secondary">Volver a Células</a>
</div>

<?php if (!empty($mensaje ?? '')): ?>
    <?php $esSuccess = ($tipo ?? '') === 'success'; ?>
    <div class="alert alert-<?= $esSuccess ? 'success' : 'danger' ?>" style="margin-bottom: 16px;">
        <?= htmlspecialchars((string)$mensaje) ?>
    </div>
<?php endif; ?>

<div class="form-container" style="margin-bottom: 20px;">
    <h3 style="margin-top:0;">Subir nuevo material (PDF)</h3>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="material_pdf">Archivo PDF</label>
            <input type="file" id="material_pdf" name="material_pdf" class="form-control" accept="application/pdf,.pdf" required>
            <small style="display:block; margin-top:8px; color:#666;">Tamaño máximo: 20MB.</small>
        </div>
        <button type="submit" class="btn btn-primary">Subir PDF</button>
    </form>
</div>

<div class="table-container">
    <h3 style="margin-top:0;">Materiales disponibles</h3>

    <?php if (!empty($materiales ?? [])): ?>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th style="width:140px;">Tamaño (KB)</th>
                    <th style="width:170px;">Personas que lo vieron</th>
                    <th style="width:190px;">Fecha</th>
                    <th style="width:280px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($materiales as $material): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$material['nombre_archivo']) ?></td>
                        <td><?= number_format((float)$material['peso_kb'], 2) ?></td>
                        <td><?= (int)($material['personas_vieron'] ?? 0) ?></td>
                        <td>
                            <?php
                            $ts = (int)($material['fecha_modificacion'] ?? 0);
                            echo $ts > 0 ? date('Y-m-d H:i', $ts) : '—';
                            ?>
                        </td>
                        <td style="display:flex; gap:8px; flex-wrap:wrap;">
                            <a href="<?= htmlspecialchars((string)$material['url']) ?>" target="_blank" class="btn btn-sm btn-success">Ver PDF</a>
                            <button type="button" class="btn btn-sm btn-info js-ver-vistas" data-archivo="<?= htmlspecialchars((string)$material['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?>">Ver quién vio</button>
                            <form method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este PDF?');" style="margin:0;">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="archivo" value="<?= htmlspecialchars((string)$material['nombre_archivo'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="margin:0; color:#666;">Aún no hay materiales PDF cargados.</p>
    <?php endif; ?>
</div>

<!-- Modal para ver quiénes vieron el material -->
<div id="modal-vistas-material" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; overflow:auto;">
    <div style="background:white; margin:40px auto; padding:30px; border-radius:8px; max-width:700px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;">Personas que vieron el material</h3>
            <button type="button" class="btn btn-sm" onclick="document.getElementById('modal-vistas-material').style.display='none';" style="padding:5px 10px;">✕</button>
        </div>
        
        <div id="modal-content-loading" style="text-align:center; padding:20px;">
            <p>Cargando...</p>
        </div>

        <div id="modal-content-vistas" style="display:none;">
            <div style="background:#f8f9fa; padding:12px; border-radius:4px; margin-bottom:16px;">
                <strong>Archivo:</strong> <span id="modal-archivo-nombre"></span>
            </div>
            
            <div style="background:#f8f9fa; padding:12px; border-radius:4px; margin-bottom:16px;">
                <strong>Total de personas:</strong> <span id="modal-total-personas" style="font-size:1.2em; color:#007bff;">0</span>
            </div>

            <table class="table table-hover" style="margin-bottom:0;">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th style="width:150px;">Ministerio</th>
                        <th style="width:120px;">Vistas</th>
                        <th style="width:160px;">Última vista</th>
                    </tr>
                </thead>
                <tbody id="modal-vistas-list">
                    <!-- Se llenará dinamicamente -->
                </tbody>
            </table>
        </div>

        <div id="modal-content-error" style="display:none; background:#f8d7da; padding:12px; border-radius:4px; color:#721c24;">
            <!-- Error message aquí -->
        </div>
    </div>
</div>

<style>
    .js-ver-vistas {
        cursor: pointer;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modalElement = document.getElementById('modal-vistas-material');
        const botones = document.querySelectorAll('.js-ver-vistas');

        botones.forEach(btn => {
            btn.addEventListener('click', function() {
                const archivo = this.getAttribute('data-archivo');
                abrirModalVistas(archivo);
            });
        });

        function abrirModalVistas(archivo) {
            // Mostrar modal y loading
            document.getElementById('modal-content-loading').style.display = 'block';
            document.getElementById('modal-content-vistas').style.display = 'none';
            document.getElementById('modal-content-error').style.display = 'none';
            modalElement.style.display = 'block';

            // Hacer request AJAX
            fetch('<?= PUBLIC_URL ?>?url=celulas/detalleVistasMaterial&archivo=' + encodeURIComponent(archivo))
                .then(res => res.json())
                .then(data => {
                    document.getElementById('modal-content-loading').style.display = 'none';

                    if (data.success) {
                        document.getElementById('modal-archivo-nombre').textContent = data.archivo;
                        document.getElementById('modal-total-personas').textContent = data.total_personas;
                        
                        const tbody = document.getElementById('modal-vistas-list');
                        tbody.innerHTML = '';

                        if (data.vistas && data.vistas.length > 0) {
                            data.vistas.forEach(vista => {
                                const nombre = vista.Nombre ? (vista.Nombre + ' ' + (vista.Apellido || '')).trim() : 'Sin nombre';
                                const ministerio = vista.Nombre_Ministerio || 'Sin ministerio';
                                const vistas = vista.Total_Vistas || 0;
                                const ultVista = vista.Fecha_Ultima_Vista ? new Date(vista.Fecha_Ultima_Vista).toLocaleString('es-ES') : '—';
                                
                                const tr = document.createElement('tr');
                                tr.innerHTML = ` 
                                    <td>${nombre}</td>
                                    <td>${ministerio}</td>
                                    <td>${vistas}</td>
                                    <td>${ultVista}</td>
                                `;
                                tbody.appendChild(tr);
                            });
                        } else {
                            const tr = document.createElement('tr');
                            tr.innerHTML = '<td colspan="4" style="text-align:center; color:#999;">Aún no hay registro de vistas</td>';
                            tbody.appendChild(tr);
                        }

                        document.getElementById('modal-content-vistas').style.display = 'block';
                    } else {
                        document.getElementById('modal-content-error').textContent = data.message || 'Error al cargar los datos';
                        document.getElementById('modal-content-error').style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('modal-content-loading').style.display = 'none';
                    document.getElementById('modal-content-error').textContent = 'Error al cargar los datos';
                    document.getElementById('modal-content-error').style.display = 'block';
                });
        }

        // Cerrar modal al hacer clic fuera de él
        modalElement.addEventListener('click', function(e) {
            if (e.target === modalElement) {
                modalElement.style.display = 'none';
            }
        });
    });
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
