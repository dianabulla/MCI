        </main>
    
        <footer class="app-footer">
            <p>&copy; <?= date('Y') ?> Iglesia MCI Madrid - Colombia. Todos los derechos reservados.</p>
        </footer>
    </div>
</div>

    <script src="<?= ASSETS_URL ?>/js/main.js?v=20260519-chrome-mobile-1"></script>
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
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/product-tour.css?v=20260519-cap-tour-1">
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.3.1/dist/driver.js.iife.js"></script>
    <script src="<?= ASSETS_URL ?>/js/product-tour.js?v=20260519-cap-tour-maestro-v2"></script>
    <?php endif; ?>
</body>
</html>
