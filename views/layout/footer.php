        </main>
    
        <footer class="app-footer">
            <p>&copy; <?= date('Y') ?> Iglesia MCI Madrid - Colombia. Todos los derechos reservados.</p>
        </footer>
    </div>
</div>

    <script src="<?= htmlspecialchars(function_exists('asset_url') ? asset_url('js/main.js') : (ASSETS_URL . '/js/main.js?v=' . date('Ymd')), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php
    $productTourRoleFooter = '';
    if (class_exists('AuthController') && AuthController::estaAutenticado()) {
        $esMenuMaestroFooter = AuthController::esContextoMaestro() && !AuthController::esAdministrador();
        $esMenuDiscipuloFooter = AuthController::esVistaDiscipuloSimplificada();
        if ($esMenuMaestroFooter && AuthController::puedeVerMaterialCapacitacionDestino()) {
            $productTourRoleFooter = 'maestro';
        } elseif ($esMenuDiscipuloFooter) {
            $productTourRoleFooter = 'discipulo';
        }
    }
    ?>
    <?php if ($productTourRoleFooter !== ''): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(function_exists('asset_url') ? asset_url('css/product-tour.css') : (ASSETS_URL . '/css/product-tour.css?v=' . date('Ymd')), ENT_QUOTES, 'UTF-8') ?>">
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
    <script src="<?= htmlspecialchars(function_exists('asset_url') ? asset_url('js/product-tour.js') : (ASSETS_URL . '/js/product-tour.js?v=' . date('Ymd')), ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
    <?php
    if (class_exists('AuthController') && AuthController::estaAutenticado()) {
        include VIEWS . '/partials/chatbot_widget.php';
    }
    ?>
</body>
</html>
