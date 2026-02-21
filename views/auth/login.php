<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - MCI Madrid Colombia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            background:
                radial-gradient(circle at 10% 10%, rgba(121, 175, 234, 0.25) 0, transparent 30%),
                radial-gradient(circle at 90% 20%, rgba(95, 96, 216, 0.20) 0, transparent 35%),
                #edf3fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .login-container {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #d7e3f6;
            box-shadow: 0 20px 50px rgba(36, 70, 126, 0.16);
            overflow: hidden;
            max-width: 430px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #355fa8 0%, #5b7fc3 100%);
            padding: 34px 30px;
            text-align: center;
            color: white;
            position: relative;
        }
        .login-header::after {
            content: '';
            position: absolute;
            inset: auto 0 -22px 0;
            height: 44px;
            background: #fff;
            border-top-left-radius: 26px;
            border-top-right-radius: 26px;
        }
        .login-header i {
            font-size: 54px;
            margin-bottom: 12px;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 23px;
            letter-spacing: 0.2px;
        }
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.95;
            font-size: 14px;
        }
        .login-body {
            padding: 34px 30px 30px;
            position: relative;
            z-index: 1;
        }
        .form-group {
            margin-bottom: 22px;
        }
        .form-label {
            font-weight: 700;
            color: #4d5f86;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-control {
            border-radius: 12px;
            border: 1px solid #cfdbef;
            background: #fbfdff;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #80a9e8;
            box-shadow: 0 0 0 0.2rem rgba(105, 144, 211, 0.18);
        }
        .input-group-text {
            background: #fbfdff;
            border: 1px solid #cfdbef;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #3f69ad;
        }
        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }
        .input-group:focus-within .input-group-text {
            border-color: #80a9e8;
        }
        .password-toggle {
            position: relative;
        }
        .password-toggle .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7a88a8;
            z-index: 10;
            background: #fbfdff;
            padding: 0 5px;
        }
        .password-toggle .toggle-password:hover {
            color: #3f69ad;
        }
        .btn-login {
            background: linear-gradient(135deg, #5f60d8 0%, #7a7ee9 100%);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 700;
            font-size: 16px;
            color: white;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 22px rgba(95, 96, 216, 0.34);
            color: white;
        }
        .alert {
            border-radius: 12px;
            border: 1px solid transparent;
            padding: 12px 15px;
        }
        .alert-danger {
            background-color: #ffedf1;
            border-color: #ffd6dd;
            color: #9d3c4a;
        }
        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }
        .forgot-password a {
            color: #3f69ad;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="bi bi-shield-lock"></i>
            <h2>MCI Madrid Colombia</h2>
            <p>Misión Carismática Internacional</p>
        </div>
        <div class="login-body">
            <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <?php
            // Debug: mostrar información de depuración
            if (isset($debug)) {
                echo '<div class="alert alert-info" style="font-size: 12px;">';
                echo '<strong>Debug Info:</strong><br>';
                echo 'Usuario existe en BD: ' . ($debug['usuario_existe'] ? 'SÍ' : 'NO') . '<br>';
                echo 'Hash en BD: ' . htmlspecialchars($debug['hash_bd']) . '<br>';
                echo 'Estado cuenta: ' . htmlspecialchars($debug['estado_cuenta']);
                echo '</div>';
            }
            ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="usuario" class="form-control" placeholder="Ingrese su usuario" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <div class="input-group password-toggle">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="contrasena" class="form-control" placeholder="Ingrese su contraseña" required>
                        <i class="bi bi-eye toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar contraseña
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            // Cambiar el tipo de input
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Cambiar el ícono
            this.classList.toggle('bi-eye');
            this.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>
