<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'MCI Madrid Colombia' ?></title>
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-left: auto;
            padding: 10px 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 25px;
        }
        .user-info i {
            font-size: 20px;
        }
        .user-name {
            font-weight: 600;
        }
        .user-role {
            font-size: 12px;
            opacity: 0.8;
        }
        .btn-logout {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 8px 15px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        .app-header .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-secondary {
            background: #e2e3e5;
            color: #6c757d;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <header class="app-header">
        <div class="container">
            <div>
                <h1>Iglesia MCI Madrid - Colombia</h1>
                <nav class="main-nav">
                    <a href="<?= PUBLIC_URL ?>?url=home">Inicio</a>
                    <?php if (AuthController::tienePermiso('personas')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=personas/ganar">Personas/Ganar</a>
                    <?php endif; ?>
                    <?php if (AuthController::tienePermiso('personas')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=personas">Personas</a>
                    <?php endif; ?>
                    <?php if (AuthController::tienePermiso('celulas')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=celulas">Células</a>
                    <?php endif; ?>
                    <?php if (AuthController::tienePermiso('ministerios')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=ministerios">Ministerios</a>
                    <?php endif; ?>
                    <?php if (AuthController::tienePermiso('roles')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=roles">Roles</a>
                    <?php endif; ?>
                    <?php if (AuthController::tienePermiso('eventos')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=eventos">Eventos</a>
                    <?php endif; ?>
                    <?php if (AuthController::tienePermiso('peticiones')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=peticiones">Peticiones</a>
                    <?php endif; ?>
                    <?php if (AuthController::tienePermiso('asistencias')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=asistencias">Asistencias</a>
                    <?php endif; ?>
                    <a href="<?= PUBLIC_URL ?>?url=transmisiones">
                        <i class="bi bi-broadcast"></i> Transmisiones
                    </a>
                    <?php if (AuthController::esAdministrador()): ?>
                    <a href="<?= PUBLIC_URL ?>?url=entrega_obsequio">
                        <i class="bi bi-gift-fill"></i> Obsequios Navideños
                    </a>
                    <?php endif; ?>
                    <?php if (AuthController::esAdministrador()): ?>
                    <a href="<?= PUBLIC_URL ?>?url=nehemias/lista">
                        <i class="bi bi-clipboard-data"></i> Nehemias
                    </a>
                    <a href="<?= PUBLIC_URL ?>?url=nehemias/reportes">
                        <i class="bi bi-graph-up"></i> Nehemias Reportes
                    </a>
                    <?php endif; ?>
                    <?php if (AuthController::esAdministrador() || AuthController::tienePermiso('reportes')): ?>
                    <a href="<?= PUBLIC_URL ?>?url=reportes">Reportes</a>
                    <?php endif; ?>
                    <?php if (AuthController::esAdministrador()): ?>
                    <a href="<?= PUBLIC_URL ?>?url=permisos">Permisos</a>
                    <?php endif; ?>
                </nav>
            </div>
            
            <div class="user-info">
                <i class="bi bi-person-circle"></i>
                <div>
                    <div class="user-name"><?= $_SESSION['usuario_nombre'] ?? 'Usuario' ?></div>
                    <div class="user-role"><?= $_SESSION['usuario_rol_nombre'] ?? 'Sin Rol' ?></div>
                </div>
                <a href="<?= PUBLIC_URL ?>?url=auth/logout" class="btn-logout">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </header>
    
    <main class="container main-content">
