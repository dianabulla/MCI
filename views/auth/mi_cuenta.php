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
                    <div class="password-toggle-wrap">
                        <input
                            type="password"
                            class="form-control"
                            id="contrasena_actual"
                            name="contrasena_actual"
                            required
                            placeholder="Obligatoria para confirmar cambios"
                        >
                        <button type="button" class="password-toggle-btn" data-target="contrasena_actual" aria-label="Mostrar contraseña actual" title="Mostrar/Ocultar">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <hr>
                <p class="text-muted mb-3">Si no quieres cambiar la clave, deja los siguientes campos vacíos.</p>

                <div class="mb-3">
                    <label for="contrasena_nueva" class="form-label">Nueva contraseña</label>
                    <div class="password-toggle-wrap">
                        <input
                            type="password"
                            class="form-control"
                            id="contrasena_nueva"
                            name="contrasena_nueva"
                            minlength="6"
                            placeholder="Mínimo 6 caracteres"
                        >
                        <button type="button" class="password-toggle-btn" data-target="contrasena_nueva" aria-label="Mostrar nueva contraseña" title="Mostrar/Ocultar">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="contrasena_nueva_confirmacion" class="form-label">Confirmar nueva contraseña</label>
                    <div class="password-toggle-wrap">
                        <input
                            type="password"
                            class="form-control"
                            id="contrasena_nueva_confirmacion"
                            name="contrasena_nueva_confirmacion"
                            minlength="6"
                            placeholder="Repite la nueva contraseña"
                        >
                        <button type="button" class="password-toggle-btn" data-target="contrasena_nueva_confirmacion" aria-label="Mostrar confirmación de contraseña" title="Mostrar/Ocultar">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
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

<style>
.password-toggle-wrap {
    position: relative;
}

.password-toggle-wrap .form-control {
    padding-right: 44px;
}

.password-toggle-btn {
    position: absolute;
    top: 50%;
    right: 10px;
    transform: translateY(-50%);
    border: 0;
    background: transparent;
    color: #5f6f85;
    padding: 4px;
    line-height: 1;
}

.password-toggle-btn:hover {
    color: #325fa9;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggles = document.querySelectorAll('.password-toggle-btn');

    toggles.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            if (!input || !icon) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('bi-eye', !isPassword);
            icon.classList.toggle('bi-eye-slash', isPassword);
        });
    });
});
</script>

<?php include VIEWS . '/layout/footer.php'; ?>
