<?php include VIEWS . '/layout/header.php'; ?>

<div class="container mt-4" style="max-width: 720px;">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="bi bi-person-gear"></i> Mi cuenta</h4>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars((string)$error) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars((string)$success) ?>
                </div>
            <?php endif; ?>

            <p class="text-muted mb-4">
                Aquí puedes cambiar <strong>tu propio usuario</strong> y <strong>tu contraseña</strong>.
            </p>

            <form method="POST" action="<?= PUBLIC_URL ?>?url=auth/mi-cuenta" autocomplete="off">
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input
                        type="text"
                        class="form-control"
                        id="usuario"
                        name="usuario"
                        required
                        minlength="3"
                        value="<?= htmlspecialchars((string)($persona['Usuario'] ?? '')) ?>"
                        placeholder="Tu nombre de usuario"
                    >
                </div>

                <div class="mb-3">
                    <label for="contrasena_actual" class="form-label">Contraseña actual</label>
                    <input
                        type="password"
                        class="form-control"
                        id="contrasena_actual"
                        name="contrasena_actual"
                        required
                        placeholder="Obligatoria para confirmar cambios"
                    >
                </div>

                <hr>
                <p class="text-muted mb-3">Si no quieres cambiar la clave, deja los siguientes campos vacíos.</p>

                <div class="mb-3">
                    <label for="contrasena_nueva" class="form-label">Nueva contraseña</label>
                    <input
                        type="password"
                        class="form-control"
                        id="contrasena_nueva"
                        name="contrasena_nueva"
                        minlength="6"
                        placeholder="Mínimo 6 caracteres"
                    >
                </div>

                <div class="mb-4">
                    <label for="contrasena_nueva_confirmacion" class="form-label">Confirmar nueva contraseña</label>
                    <input
                        type="password"
                        class="form-control"
                        id="contrasena_nueva_confirmacion"
                        name="contrasena_nueva_confirmacion"
                        minlength="6"
                        placeholder="Repite la nueva contraseña"
                    >
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Guardar cambios
                    </button>
                    <a href="<?= PUBLIC_URL ?>?url=home" class="btn btn-secondary">Volver</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
