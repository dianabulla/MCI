<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Editar Transmisión</h2>
</div>

<div class="main-content" style="max-width: 600px; margin: 0 auto;">
    <?php if ($transmision): ?>
        <form id="formEditarTransmision" class="form-container">
            <input type="hidden" name="id" id="id" value="<?= $transmision['Id_Transmision'] ?>">
            
            <div class="form-group">
                <label for="nombre">Nombre de la Transmisión *</label>
                <input type="text" id="nombre" name="nombre" class="form-control" 
                       value="<?= htmlspecialchars($transmision['Nombre']) ?>" required>
            </div>

            <div class="form-group">
                <label for="url">URL de YouTube *</label>
                <input type="url" id="url" name="url" class="form-control" 
                       value="<?= htmlspecialchars($transmision['URL_YouTube']) ?>" required>
                <small class="form-text">Link completo del video o transmisión en vivo de YouTube</small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="fecha">Fecha de Transmisión *</label>
                    <input type="date" id="fecha" name="fecha" class="form-control" 
                           value="<?= $transmision['Fecha_Transmision'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="hora">Hora</label>
                    <input type="time" id="hora" name="hora" class="form-control" 
                           value="<?= substr($transmision['Hora_Transmision'], 0, 5) ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="estado">Estado *</label>
                <select id="estado" name="estado" class="form-control" required>
                    <option value="proximamente" <?= $transmision['Estado'] === 'proximamente' ? 'selected' : '' ?>>
                        ⏱️ Proximamente
                    </option>
                    <option value="en_vivo" <?= $transmision['Estado'] === 'en_vivo' ? 'selected' : '' ?>>
                        🔴 En Vivo
                    </option>
                    <option value="finalizada" <?= $transmision['Estado'] === 'finalizada' ? 'selected' : '' ?>>
                        ✓ Finalizada
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" class="form-control" 
                          rows="4"><?= htmlspecialchars($transmision['Descripcion'] ?? '') ?></textarea>
            </div>

            <div class="form-info" style="background: #f0f0f0; padding: 10px; border-radius: 6px; margin-bottom: 20px; font-size: 12px;">
                <p><strong>Creada:</strong> <?= date('d/m/Y H:i', strtotime($transmision['Fecha_Creacion'])) ?></p>
                <?php if ($transmision['Fecha_Actualizacion'] !== $transmision['Fecha_Creacion']): ?>
                    <p><strong>Última actualización:</strong> <?= date('d/m/Y H:i', strtotime($transmision['Fecha_Actualizacion'])) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-group" style="margin-top: 30px; display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Actualizar
                </button>
                <a href="<?= PUBLIC_URL ?>?url=transmisiones" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Cancelar
                </a>
            </div>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">
            <p>La transmisión no fue encontrada.</p>
            <a href="<?= PUBLIC_URL ?>?url=transmisiones" class="btn btn-primary">Volver a transmisiones</a>
        </div>
    <?php endif; ?>
</div>

<style>
    .form-container {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        font-family: inherit;
    }
    
    .form-control:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-text {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 12px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .form-info {
        color: #666;
    }
    
    .form-info p {
        margin: 5px 0;
    }
    
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        text-decoration: none;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        background: #e0e0e0;
        color: #333;
    }
    
    .btn-secondary:hover {
        background: #d0d0d0;
    }
    
    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    @media (max-width: 600px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
document.getElementById('formEditarTransmision').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('id').value;
    const nombre = document.getElementById('nombre').value.trim();
    const url = document.getElementById('url').value.trim();
    const fecha = document.getElementById('fecha').value;
    const hora = document.getElementById('hora').value || '00:00';
    const estado = document.getElementById('estado').value;
    const descripcion = document.getElementById('descripcion').value.trim();
    
    // Validaciones
    if (!nombre) {
        alert('El nombre es requerido');
        return;
    }
    
    if (!url) {
        alert('La URL es requerida');
        return;
    }
    
    if (!fecha) {
        alert('La fecha es requerida');
        return;
    }
    
    // Validar que sea una URL de YouTube
    if (!url.includes('youtube.com') && !url.includes('youtu.be')) {
        alert('Por favor ingresa una URL válida de YouTube');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('nombre', nombre);
        formData.append('url', url);
        formData.append('fecha', fecha);
        formData.append('hora', hora);
        formData.append('estado', estado);
        formData.append('descripcion', descripcion);
        
        const response = await fetch('<?= PUBLIC_URL ?>index.php?url=transmisiones/actualizar', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('¡Transmisión actualizada exitosamente!');
            window.location.href = '<?= PUBLIC_URL ?>?url=transmisiones';
        } else {
            alert('Error: ' + (data.error || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al actualizar la transmisión');
    }
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
