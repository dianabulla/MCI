<!--
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Obsequio Navideño - MCI Madrid Colombia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .registro-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 600px;
            width: 100%;
        }
        .registro-header {
            background: linear-gradient(135deg, #c31432 0%, #240b36 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .registro-header i {
            font-size: 80px;
            margin-bottom: 15px;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .registro-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 28px;
        }
        .registro-header p {
            margin: 10px 0 0;
            opacity: 0.95;
            font-size: 16px;
        }
        .registro-body {
            padding: 40px 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-label .required {
            color: #c31432;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #c31432;
            box-shadow: 0 0 0 0.2rem rgba(195, 20, 50, 0.15);
        }
        .form-select {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 15px;
        }
        .btn-registrar {
            background: linear-gradient(135deg, #c31432 0%, #240b36 100%);
            border: none;
            border-radius: 10px;
            padding: 15px;
            font-weight: 600;
            font-size: 16px;
            color: white;
            width: 100%;
            transition: transform 0.2s;
            margin-top: 20px;
        }
        .btn-registrar:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(195, 20, 50, 0.3);
            color: white;
        }
        .btn-registrar:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #fee;
            color: #c33;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .edad-warning {
            display: none;
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            color: #856404;
            font-weight: 600;
        }
        .edad-warning i {
            font-size: 20px;
            margin-right: 10px;
        }
        .snowflake {
            position: fixed;
            top: -10px;
            z-index: 9999;
            color: white;
            font-size: 1em;
            opacity: 0.8;
            animation: fall linear infinite;
        }
        @keyframes fall {
            to {
                transform: translateY(100vh);
            }
        }
    </style>
</head>
<body>
    
    <script>
        function createSnowflake() {
            const snowflake = document.createElement('div');
            snowflake.classList.add('snowflake');
            snowflake.innerHTML = '❄';
            snowflake.style.left = Math.random() * window.innerWidth + 'px';
            snowflake.style.animationDuration = Math.random() * 3 + 2 + 's';
            snowflake.style.opacity = Math.random();
            snowflake.style.fontSize = Math.random() * 10 + 10 + 'px';
            document.body.appendChild(snowflake);
            setTimeout(() => {
                snowflake.remove();
            }, 5000);
        }
        setInterval(createSnowflake, 300);
    </script>

    <div class="registro-container">
        <div class="registro-header">
            <i class="bi bi-gift-fill"></i>
            <h2>Registro Obsequio Navideño</h2>
            <p>MCI Madrid Colombia - Navidad 2024</p>
        </div>
        <div class="registro-body">
            <?php if (isset($mensaje) && !empty($mensaje)): ?>
                <div class="alert alert-<?= $tipo_mensaje === 'success' ? 'success' : 'danger' ?>">
                    <i class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($registro_exitoso) || !$registro_exitoso): ?>
            <form method="POST" action="?url=registro_obsequio/guardar" id="formRegistro">
                <div class="form-group">
                    <label class="form-label">
                        Nombre y Apellidos del Niño(a) <span class="required">*</span>
                    </label>
                    <input type="text" name="nombre_apellidos" class="form-control" 
                           placeholder="Ej: Juan Pérez García" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Fecha de Nacimiento <span class="required">*</span>
                    </label>
                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" 
                           class="form-control" required max="<?= date('Y-m-d') ?>">
                    <div class="edad-warning" id="edadWarning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Lo sentimos, el obsequio solo aplica para niños menores de 11 años
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Nombre del Acudiente <span class="required">*</span>
                    </label>
                    <input type="text" name="nombre_acudiente" class="form-control" 
                           placeholder="Ej: María García" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Teléfono del Acudiente <span class="required">*</span>
                    </label>
                    <input type="tel" name="telefono_acudiente" class="form-control" 
                           placeholder="Ej: 3001234567" required pattern="[0-9]{10}">
                    <small class="text-muted">10 dígitos</small>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Barrio <span class="required">*</span>
                    </label>
                    <input type="text" name="barrio" class="form-control" 
                           placeholder="Ej: Centro" required>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        Ministerio <span class="required">*</span>
                    </label>
                    <select name="id_ministerio" class="form-select" required>
                        <option value="">Seleccione un ministerio...</option>
                        <?php if (!empty($ministerios)): ?>
                            <?php foreach ($ministerios as $ministerio): ?>
                                <option value="<?= $ministerio['Id_Ministerio'] ?>">
                                    <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-registrar" id="btnRegistrar">
                    <i class="bi bi-gift"></i> Registrar para Obsequio
                </button>
            </form>
            <?php else: ?>
                <div class="text-center mt-4">
                    <i class="bi bi-check-circle-fill" style="font-size: 60px; color: #28a745;"></i>
                    <p class="mt-3">
                        <a href="<?= PUBLIC_URL ?>?url=registro_obsequio" class="btn btn-registrar">
                            <i class="bi bi-plus-circle"></i> Registrar Otro Niño
                        </a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Validación de edad en tiempo real
        const fechaNacimiento = document.getElementById('fecha_nacimiento');
        const btnRegistrar = document.getElementById('btnRegistrar');
        const edadWarning = document.getElementById('edadWarning');

        fechaNacimiento.addEventListener('change', function() {
            const fecha = new Date(this.value);
            const hoy = new Date();
            let edad = hoy.getFullYear() - fecha.getFullYear();
            const mes = hoy.getMonth() - fecha.getMonth();
            
            if (mes < 0 || (mes === 0 && hoy.getDate() < fecha.getDate())) {
                edad--;
            }

            if (edad >= 11) {
                btnRegistrar.disabled = true;
                edadWarning.style.display = 'block';
            } else {
                btnRegistrar.disabled = false;
                edadWarning.style.display = 'none';
            }
        });
    </script>
</body>
</html>
