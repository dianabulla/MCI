<?php include VIEWS . '/layout/header.php'; ?>

<?php
$persona = is_array($persona ?? null) ? $persona : null;
$cuentaAcceso = is_array($cuenta_acceso ?? null) ? $cuenta_acceso : null;
$roles = is_array($roles ?? null) ? $roles : [];
$postData = is_array($post_data ?? null) ? $post_data : [];
$tablaDisponible = !empty($tabla_usuario_acceso_disponible);
$modoEdicion = !empty($modo_edicion);
$tipoCuenta = (string)($tipo_cuenta ?? 'persona');
$cuentaId = (int)($cuenta_id ?? 0);
$tipoCreacion = (string)($tipo_creacion ?? 'ministerial');

$nombrePersona = $persona
    ? trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''))
    : trim((string)($cuentaAcceso['Nombre_Mostrar'] ?? ''));

$usuarioActual = (string)($postData['usuario'] ?? ($cuentaAcceso['Usuario'] ?? ($persona['Usuario'] ?? '')));
$rolActual = (string)($postData['id_rol'] ?? ($cuentaAcceso['Id_Rol'] ?? ($persona['Id_Rol'] ?? '')));
$estadoActual = (string)($postData['estado_cuenta'] ?? ($cuentaAcceso['Estado_Cuenta'] ?? ($persona['Estado_Cuenta'] ?? 'Activo')));
?>

<div class="page-header">
    <h2><?= $modoEdicion ? 'Editar Cuenta' : ($tipoCreacion === 'administrativo' ? 'Crear Usuario Administrativo' : 'Crear Cuenta Ministerial') ?></h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=cuentas" class="btn btn-secondary">← Volver a Cuentas</a>
    </div>
</div>

<?php if (!empty($error ?? '')): ?>
<div class="alert alert-danger" style="margin-bottom:16px;">
    <?= htmlspecialchars((string)$error) ?>
</div>
<?php endif; ?>

<?php if (!empty($success ?? '')): ?>
<div class="alert alert-success" style="margin-bottom:16px;">
    <?= htmlspecialchars((string)$success) ?>
</div>
<?php endif; ?>

<?php if (!$tablaDisponible && !$modoEdicion): ?>
<div class="alert alert-warning" style="margin-bottom:16px;">
    La tabla usuario_acceso aún no existe. Ejecuta primero la migración SQL para poder crear cuentas nuevas.
</div>
<?php endif; ?>

<?php if (!$modoEdicion): ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
        Tipo de usuario
    </div>
    <div style="padding:14px; display:flex; gap:10px; flex-wrap:wrap;">
        <a href="<?= PUBLIC_URL ?>?url=cuentas/crear&tipo=ministerial" class="btn <?= $tipoCreacion === 'ministerial' ? 'btn-primary' : 'btn-secondary' ?>">Cuenta ministerial</a>
        <a href="<?= PUBLIC_URL ?>?url=cuentas/crear&tipo=administrativo" class="btn <?= $tipoCreacion === 'administrativo' ? 'btn-primary' : 'btn-secondary' ?>">Usuario administrativo</a>
    </div>
</div>

<?php if ($tipoCreacion === 'ministerial'): ?>
<div class="card" style="margin-bottom:16px;">
    <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
        Buscar Persona por Cédula
    </div>
    <div style="padding:14px;">
        <form method="POST" action="<?= PUBLIC_URL ?>?url=cuentas/crear" class="filters-inline">
            <input type="hidden" name="accion" value="buscar">
            <div class="form-group" style="min-width:260px;">
                <label for="numero_documento">Número de cédula</label>
                <input type="text" id="numero_documento" name="numero_documento" class="form-control" value="<?= htmlspecialchars((string)($postData['numero_documento'] ?? '')) ?>" required>
            </div>
            <div class="filters-actions">
                <button type="submit" class="btn btn-primary">Buscar</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<div class="card">
    <div class="card-header" style="padding:12px 14px; border-bottom:1px solid #dde6f4; font-weight:700; color:#1f365f;">
        <?= !$modoEdicion && $tipoCreacion === 'administrativo' ? 'Datos del Usuario Administrativo' : 'Datos de la Cuenta' ?>
    </div>
    <div style="padding:14px;">
        <?php if ($tipoCreacion === 'administrativo' && !$modoEdicion): ?>
            <form method="POST" action="<?= PUBLIC_URL ?>?url=cuentas/crear">
                <input type="hidden" name="accion" value="crear">
                <input type="hidden" name="tipo_creacion" value="administrativo">

                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_mostrar">Nombre para mostrar</label>
                        <input type="text" id="nombre_mostrar" name="nombre_mostrar" class="form-control" value="<?= htmlspecialchars((string)($postData['nombre_mostrar'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" class="form-control" value="<?= htmlspecialchars($usuarioActual) ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contrasena">Contraseña</label>
                        <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="id_rol">Rol de acceso</label>
                        <select id="id_rol" name="id_rol" class="form-control" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= (int)$rol['Id_Rol'] ?>" <?= $rolActual === (string)($rol['Id_Rol'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)($rol['Nombre_Rol'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="estado_cuenta">Estado</label>
                        <select id="estado_cuenta" name="estado_cuenta" class="form-control">
                            <option value="Activo" <?= $estadoActual === 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= $estadoActual === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            <option value="Bloqueado" <?= $estadoActual === 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" <?= !$tablaDisponible ? 'disabled' : '' ?>>Guardar usuario</button>
                    <a href="<?= PUBLIC_URL ?>?url=cuentas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        <?php elseif ($persona || $cuentaAcceso): ?>
            <div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px; margin-bottom:16px;">
                <div class="dashboard-card" style="border-left-color:#0f5fca;">
                    <h3><?= $modoEdicion && !$persona ? 'Usuario' : 'Persona' ?></h3>
                    <div style="font-weight:700;"><?= htmlspecialchars($nombrePersona !== '' ? $nombrePersona : 'Sin vínculo') ?></div>
                    <small style="color:#637087;">Cédula: <?= htmlspecialchars((string)($persona['Numero_Documento'] ?? 'Sin cédula')) ?></small>
                </div>
                <div class="dashboard-card" style="border-left-color:#1f7a45;">
                    <h3>Ministerio</h3>
                    <div style="font-weight:700;"><?= htmlspecialchars((string)($persona['Nombre_Ministerio'] ?? ($cuentaAcceso['Nombre_Ministerio'] ?? 'Sin ministerio'))) ?></div>
                    <small style="color:#637087;">Rol actual: <?= htmlspecialchars((string)($persona['Nombre_Rol'] ?? ($cuentaAcceso['Nombre_Rol'] ?? 'Sin rol'))) ?></small>
                </div>
            </div>

            <form method="POST" action="<?= PUBLIC_URL ?>?url=cuentas/<?= $modoEdicion ? 'editar' : 'crear' ?>">
                <?php if ($modoEdicion): ?>
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipoCuenta) ?>">
                <input type="hidden" name="id" value="<?= $cuentaId ?>">
                <?php else: ?>
                <input type="hidden" name="accion" value="crear">
                <input type="hidden" name="tipo_creacion" value="ministerial">
                <input type="hidden" name="numero_documento" value="<?= htmlspecialchars((string)($persona['Numero_Documento'] ?? '')) ?>">
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="usuario">Usuario</label>
                        <input type="text" id="usuario" name="usuario" class="form-control" value="<?= htmlspecialchars($usuarioActual) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contrasena">Contraseña <?= $modoEdicion ? '(opcional)' : '' ?></label>
                        <input type="password" id="contrasena" name="contrasena" class="form-control" <?= $modoEdicion ? '' : 'required' ?>>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="id_rol">Rol de acceso</label>
                        <select id="id_rol" name="id_rol" class="form-control" required>
                            <option value="">Seleccione</option>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= (int)$rol['Id_Rol'] ?>" <?= $rolActual === (string)($rol['Id_Rol'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string)($rol['Nombre_Rol'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="estado_cuenta">Estado</label>
                        <select id="estado_cuenta" name="estado_cuenta" class="form-control">
                            <option value="Activo" <?= $estadoActual === 'Activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="Inactivo" <?= $estadoActual === 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            <option value="Bloqueado" <?= $estadoActual === 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" <?= (!$modoEdicion && !$tablaDisponible) ? 'disabled' : '' ?>><?= $modoEdicion ? 'Guardar cambios' : 'Guardar cuenta' ?></button>
                    <a href="<?= PUBLIC_URL ?>?url=cuentas" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <p style="margin:0; color:#637087;">Busca primero una persona por cédula para asignarle su cuenta ministerial.</p>
        <?php endif; ?>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
