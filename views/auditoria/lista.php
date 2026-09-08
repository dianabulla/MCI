<?php include VIEWS . '/layout/header.php'; ?>
<?php $admin_nav_active = 'auditoria'; include VIEWS . '/partials/admin_nav.php'; ?>

<?php
$eventos = is_array($eventos ?? null) ? $eventos : [];
$filtros = is_array($filtros ?? null) ? $filtros : [];
?>

<div class="card">
    <div class="card-body">
        <p class="text-muted" style="margin-top:0;">Quién creó, editó o eliminó fichas, y los intentos que fallaron (login u operación). No guarda contraseñas.</p>
        <form method="GET" action="<?= PUBLIC_URL ?>index.php" class="personas-mobile-stack" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:16px;">
            <input type="hidden" name="url" value="auditoria">
            <div>
                <label for="filtro_modulo">Módulo</label>
                <select id="filtro_modulo" name="modulo" class="form-control">
                    <option value="">Todos</option>
                    <option value="personas" <?= (($filtros['modulo'] ?? '') === 'personas') ? 'selected' : '' ?>>Personas</option>
                    <option value="auth" <?= (($filtros['modulo'] ?? '') === 'auth') ? 'selected' : '' ?>>Acceso</option>
                </select>
            </div>
            <div>
                <label for="filtro_accion">Acción</label>
                <select id="filtro_accion" name="accion" class="form-control">
                    <option value="">Todas</option>
                    <option value="crear" <?= (($filtros['accion'] ?? '') === 'crear') ? 'selected' : '' ?>>Crear</option>
                    <option value="editar" <?= (($filtros['accion'] ?? '') === 'editar') ? 'selected' : '' ?>>Editar</option>
                    <option value="eliminar" <?= (($filtros['accion'] ?? '') === 'eliminar') ? 'selected' : '' ?>>Eliminar</option>
                    <option value="login" <?= (($filtros['accion'] ?? '') === 'login') ? 'selected' : '' ?>>Login</option>
                </select>
            </div>
            <div>
                <label for="filtro_exito">Resultado</label>
                <select id="filtro_exito" name="exito" class="form-control">
                    <option value="todos" <?= (($filtros['exito'] ?? '') === '') ? 'selected' : '' ?>>Todos</option>
                    <option value="1" <?= (string)($filtros['exito'] ?? '') === '1' ? 'selected' : '' ?>>Correcto</option>
                    <option value="0" <?= (string)($filtros['exito'] ?? '') === '0' ? 'selected' : '' ?>>Fallido</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Quién</th>
                        <th>Acción</th>
                        <th>Módulo</th>
                        <th>Registro</th>
                        <th>Resultado</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($eventos)): ?>
                    <tr><td colspan="7">Aún no hay movimientos registrados.</td></tr>
                    <?php else: ?>
                    <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= htmlspecialchars((string)($evento['Fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($evento['Usuario_Actor'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($evento['Accion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($evento['Modulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($evento['Entidad']) || !empty($evento['Id_Registro'])): ?>
                                <?= htmlspecialchars(trim((string)($evento['Entidad'] ?? '') . ' #' . (string)($evento['Id_Registro'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= !empty($evento['Exito']) ? 'Correcto' : 'Fallido' ?></td>
                        <td><?= htmlspecialchars((string)($evento['Mensaje'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
