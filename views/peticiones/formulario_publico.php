<?php
// Formulario público - Sin autenticación requerida
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petición de Oración - MCI Madrid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2f4f87;
            --secondary-color: #1e7a51;
            --success-color: #1e7a51;
            --danger-color: #a92d48;
            --warning-color: #9a6708;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a3a5a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .container-form {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            color: #333;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }

        .form-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #e3f4eb;
            color: #1e7a51;
        }

        .alert-danger {
            background: #fff0f3;
            color: #a92d48;
        }

        .alert-warning {
            background: #fff4df;
            color: #9a6708;
        }

        .icon-header {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .footer-text {
            text-align: center;
            font-size: 12px;
            color: #666;
            margin-top: 20px;
        }

        .required {
            color: var(--danger-color);
        }

        .success-message {
            display: none;
            text-align: center;
        }

        .success-icon {
            font-size: 64px;
            color: var(--success-color);
            margin-bottom: 15px;
        }

        .success-message h2 {
            color: var(--success-color);
            margin-bottom: 10px;
        }

        .success-message p {
            color: #666;
            margin-bottom: 20px;
        }

    </style>
</head>
<body>
    <div class="container-form">
        <div class="header">
            <div class="icon-header">
                <i class="bi bi-hands-pray"></i>
            </div>
            <h1>Petición de Oración</h1>
            <p>Compartir tu petición para que oremos contigo</p>
        </div>

        <?php if (isset($_GET['exito']) && $_GET['exito'] === '1'): ?>
            <div class="alert alert-success alert-simple">
                <i class="bi bi-check-circle-fill"></i>
                <strong>¡Gracias!</strong> Tu petición ha sido recibida. Oraremos por tu intención.
            </div>
            <script>
                setTimeout(() => {
                    window.location.href = '<?= PUBLIC_URL ?>?url=peticiones_publica';
                }, 3000);
            </script>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'faltan-campos'): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Campos requeridos.</strong> Por favor completa nombre y descripción de la petición.
                </div>
            <?php elseif ($_GET['error'] === 'email-invalido'): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Email inválido.</strong> Por favor ingresa un correo válido (si deseas proporcionar uno).
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Error.</strong> Ha ocurrido un error al guardar tu petición. Intenta nuevamente.
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="POST" action="<?= PUBLIC_URL ?>?url=peticiones_publica/guardar">
            <div class="form-group">
                <label for="nombre">Tu Nombre<span class="required">*</span></label>
                <input 
                    type="text" 
                    id="nombre" 
                    name="nombre" 
                    class="form-control" 
                    placeholder="Ej: Juan Pérez"
                    required
                    maxlength="100">
                <small class="form-text">Esto nos ayudará a identificar tu petición</small>
            </div>

            <div class="form-group">
                <label for="email">Correo Electrónico (Opcional)</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="tu@correo.com"
                    maxlength="150">
                <small class="form-text">Para que podamos contactarte si lo necesitamos</small>
            </div>

            <div class="form-group">
                <label for="telefono">Teléfono (Opcional)</label>
                <input 
                    type="tel" 
                    id="telefono" 
                    name="telefono" 
                    class="form-control" 
                    placeholder="+57 320 1234567"
                    maxlength="20">
                <small class="form-text">Puedes dejar tu número si prefieres una llamada</small>
            </div>

            <div class="form-group">
                <label for="descripcion_peticion">Tu Petición de Oración<span class="required">*</span></label>
                <textarea 
                    id="descripcion_peticion" 
                    name="descripcion_peticion" 
                    class="form-control" 
                    placeholder="Cuéntanos por qué quieres que oremos por ti..."
                    required
                    maxlength="2000"></textarea>
                <small class="form-text">Máximo 2000 caracteres</small>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-check-lg"></i> Enviar Petición
            </button>
        </form>

        <div class="footer-text">
            <p>
                <i class="bi bi-lock"></i> Tu privacidad es importante. 
                Los datos solo serán usados para oración.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación básica del lado cliente
        document.querySelector('form').addEventListener('submit', function(e) {
            const nombre = document.querySelector('#nombre').value.trim();
            const descripcion = document.querySelector('#descripcion_peticion').value.trim();
            const email = document.querySelector('#email').value.trim();

            if (!nombre) {
                e.preventDefault();
                alert('Por favor ingresa tu nombre');
                return;
            }

            if (!descripcion) {
                e.preventDefault();
                alert('Por favor ingresa tu petición');
                return;
            }

            if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                e.preventDefault();
                alert('Por favor ingresa un email válido');
                return;
            }
        });

        // Contador de caracteres
        const textarea = document.querySelector('#descripcion_peticion');
        if (textarea) {
            textarea.addEventListener('input', function() {
                const contador = 2000 - this.value.length;
                if (contador < 100) {
                    this.style.borderColor = '#f59e0b';
                } else {
                    this.style.borderColor = '#e5e7eb';
                }
            });
        }
    </script>
</body>
</html>
