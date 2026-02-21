<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2>Nueva Transmisión</h2>
</div>

<div class="content-narrow">
    <?php if (!empty($_GET['error'])): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <form id="formCrearTransmision" class="form-container" method="POST" action="<?= PUBLIC_URL ?>index.php?url=transmisiones/guardar">
        <div class="form-group">
            <label for="nombre">Nombre de la Transmisión *</label>
            <input type="text" id="nombre" name="nombre" class="form-control" 
                   placeholder="Ej: Servicio Dominical 18 Enero 2026" required>
            <small class="form-text">El nombre de la transmisión que verá el público</small>
        </div>

        <div class="form-group">
            <label for="url">URL de YouTube *</label>
            <input type="url" id="url" name="url" class="form-control" 
                   placeholder="Ej: https://www.youtube.com/watch?v=... o https://youtu.be/..." required>
            <small class="form-text">Link completo del video o transmisión en vivo de YouTube</small>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="fecha">Fecha de Transmisión *</label>
                <input type="date" id="fecha" name="fecha" class="form-control" 
                       value="<?= $fechaHoy ?>" required>
            </div>

            <div class="form-group">
                <label for="hora">Hora (opcional)</label>
                <input type="time" id="hora" name="hora" class="form-control" 
                       value="<?= $horaActual ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="estado">Estado *</label>
            <select id="estado" name="estado" class="form-control" required>
                <option value="proximamente">⏱️ Proximamente</option>
                <option value="en_vivo">🔴 En Vivo</option>
                <option value="finalizada">✓ Finalizada</option>
            </select>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción (opcional)</label>
            <textarea id="descripcion" name="descripcion" class="form-control" 
                      rows="4" placeholder="Información adicional sobre la transmisión"></textarea>
        </div>

        <div class="form-group page-actions" style="margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Crear Transmisión
            </button>
            <a href="<?= PUBLIC_URL ?>?url=transmisiones" class="btn btn-secondary">
                <i class="bi bi-x-circle"></i> Cancelar
            </a>
        </div>
    </form>
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
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    @media (max-width: 600px) {
        .form-row {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
(function () {
    var form = document.getElementById('formCrearTransmision');
    if (!form) {
        return;
    }

    if (!window.fetch || !window.URLSearchParams) {
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var nombre = document.getElementById('nombre').value.trim();
        var url = document.getElementById('url').value.trim();
        var fecha = document.getElementById('fecha').value;
        var hora = document.getElementById('hora').value || '00:00';
        var estado = document.getElementById('estado').value;
        var descripcion = document.getElementById('descripcion').value.trim();

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

        var urlLower = url.toLowerCase();
        if (urlLower.indexOf('youtube.com') === -1 && urlLower.indexOf('youtu.be') === -1) {
            alert('Por favor ingresa una URL válida de YouTube');
            return;
        }

        var payload = new URLSearchParams();
        payload.append('nombre', nombre);
        payload.append('url', url);
        payload.append('fecha', fecha);
        payload.append('hora', hora);
        payload.append('estado', estado);
        payload.append('descripcion', descripcion);

        fetch('<?= PUBLIC_URL ?>index.php?url=transmisiones/guardar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: payload.toString()
        })
        .then(function(response) {
            return response.text();
        })
        .then(function(text) {
            var data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                throw new Error('Respuesta inválida del servidor');
            }

            if (data.success) {
                alert('¡Transmisión creada exitosamente!');
                window.location.href = '<?= PUBLIC_URL ?>?url=transmisiones';
                return;
            }

            alert('Error: ' + (data.error || 'Error desconocido'));
        })
        .catch(function(error) {
            console.error('Error:', error);
            form.submit();
        });
    });
})();
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
