<?php
/**
 * Layout base de la aplicación
 * Envuelve todas las vistas con header, nav y footer
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Iglesia App'); ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?php echo APP_URL; ?>">
                <i class="fas fa-church"></i> Iglesia App
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>">
                            <i class="fas fa-home"></i> Inicio
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/personas">
                                <i class="fas fa-users"></i> Personas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/celulas">
                                <i class="fas fa-circle-nodes"></i> Células
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/ministerios">
                                <i class="fas fa-hands-praying"></i> Ministerios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/roles">
                                <i class="fas fa-shield"></i> Roles
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/asistencias">
                                <i class="fas fa-clipboard-check"></i> Asistencias
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/eventos">
                                <i class="fas fa-calendar-days"></i> Eventos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/peticiones">
                                <i class="fas fa-hands-praying"></i> Peticiones
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <!-- Notificaciones -->
                <?php if (isset($_SESSION['notification'])): ?>
                    <?php $notif = $_SESSION['notification']; unset($_SESSION['notification']); ?>
                    <div class="alert alert-<?php 
                        echo match($notif['type']) {
                            'success' => 'success',
                            'error' => 'danger',
                            'warning' => 'warning',
                            default => 'info'
                        };
                    ?> alert-dismissible fade show mt-3" role="alert">
                        <i class="fas fa-<?php 
                            echo match($notif['type']) {
                                'success' => 'check-circle',
                                'error' => 'exclamation-circle',
                                'warning' => 'exclamation-triangle',
                                default => 'info-circle'
                            };
                        ?>"></i>
                        <?php echo htmlspecialchars($notif['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Título de la página -->
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo htmlspecialchars($title ?? 'Página'); ?></h1>
                </div>

                <!-- Contenido -->
                <?php echo $content; ?>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-light text-center py-4 mt-5 border-top">
        <div class="container">
            <p class="text-muted mb-0">
                &copy; 2025 <?php echo APP_NAME; ?> - Todos los derechos reservados
            </p>
        </div>
    </footer>

    <script src="<?php echo ASSETS_URL; ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/main.js"></script>
</body>
</html>
