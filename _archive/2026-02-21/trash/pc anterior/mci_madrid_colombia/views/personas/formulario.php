<?php include VIEWS . '/layout/header.php'; ?>

<div class="page-header">
    <h2><?= isset($persona) ? 'Editar' : 'Nueva' ?> Persona</h2>
    <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">Volver</a>
</div>

<div class="form-container">
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="nombre">Nombre *</label>
                <input type="text" id="nombre" name="nombre" class="form-control" 
                       value="<?= htmlspecialchars($persona['Nombre'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="apellido">Apellido *</label>
                <input type="text" id="apellido" name="apellido" class="form-control" 
                       value="<?= htmlspecialchars($persona['Apellido'] ?? '') ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="tipo_documento">Tipo de Documento</label>
                <select id="tipo_documento" name="tipo_documento" class="form-control">
                    <option value="">Seleccionar...</option>
                    <option value="Registro Civil" <?= isset($persona) && $persona['Tipo_Documento'] == 'Registro Civil' ? 'selected' : '' ?>>Registro Civil</option>
                    <option value="Cedula de Ciudadania" <?= isset($persona) && $persona['Tipo_Documento'] == 'Cedula de Ciudadania' ? 'selected' : '' ?>>Cédula de Ciudadanía</option>
                    <option value="Cedula Extranjera" <?= isset($persona) && $persona['Tipo_Documento'] == 'Cedula Extranjera' ? 'selected' : '' ?>>Cédula Extranjera</option>
                </select>
            </div>

            <div class="form-group">
                <label for="numero_documento">Número de Documento</label>
                <input type="text" id="numero_documento" name="numero_documento" class="form-control" 
                       value="<?= htmlspecialchars($persona['Numero_Documento'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="form-control" 
                       value="<?= htmlspecialchars($persona['Fecha_Nacimiento'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="edad">Edad</label>
                <input type="number" id="edad" name="edad" class="form-control" min="0" max="120"
                       value="<?= htmlspecialchars($persona['Edad'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="genero">Género</label>
                <select id="genero" name="genero" class="form-control">
                    <option value="">Seleccionar...</option>
                    <option value="Hombre" <?= isset($persona) && $persona['Genero'] == 'Hombre' ? 'selected' : '' ?>>Hombre</option>
                    <option value="Mujer" <?= isset($persona) && $persona['Genero'] == 'Mujer' ? 'selected' : '' ?>>Mujer</option>
                    <option value="Joven Hombre" <?= isset($persona) && $persona['Genero'] == 'Joven Hombre' ? 'selected' : '' ?>>Joven Hombre</option>
                    <option value="Joven Mujer" <?= isset($persona) && $persona['Genero'] == 'Joven Mujer' ? 'selected' : '' ?>>Joven Mujer</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="telefono">Teléfono</label>
                <input type="tel" id="telefono" name="telefono" class="form-control" 
                       value="<?= htmlspecialchars($persona['Telefono'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($persona['Email'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="hora_llamada">Hora de Llamada</label>
                <select id="hora_llamada" name="hora_llamada" class="form-control">
                    <option value="">Seleccionar...</option>
                    <option value="Mañana" <?= isset($persona) && $persona['Hora_Llamada'] == 'Mañana' ? 'selected' : '' ?>>Mañana</option>
                    <option value="Medio Dia" <?= isset($persona) && $persona['Hora_Llamada'] == 'Medio Dia' ? 'selected' : '' ?>>Medio Día</option>
                    <option value="Tarde" <?= isset($persona) && $persona['Hora_Llamada'] == 'Tarde' ? 'selected' : '' ?>>Tarde</option>
                    <option value="Noche" <?= isset($persona) && $persona['Hora_Llamada'] == 'Noche' ? 'selected' : '' ?>>Noche</option>
                    <option value="Cualquier Hora" <?= isset($persona) && $persona['Hora_Llamada'] == 'Cualquier Hora' ? 'selected' : '' ?>>Cualquier Hora</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" class="form-control" 
                       value="<?= htmlspecialchars($persona['Direccion'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="barrio">Barrio</label>
                <input type="text" id="barrio" name="barrio" class="form-control" 
                       value="<?= htmlspecialchars($persona['Barrio'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="peticion">Petición</label>
            <textarea id="peticion" name="peticion" class="form-control" rows="3"><?= htmlspecialchars($persona['Peticion'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="invitado_por">Invitado Por</label>
                <select id="invitado_por" name="invitado_por" class="form-control">
                    <option value="">Nadie / No aplica</option>
                    <?php if (!empty($personas_invitadores)): ?>
                        <?php foreach ($personas_invitadores as $invitador): ?>
                            <option value="<?= $invitador['Id_Persona'] ?>" 
                                    <?= isset($persona) && $persona['Invitado_Por'] == $invitador['Id_Persona'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($invitador['Nombre'] . ' ' . $invitador['Apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_lider">Líder</label>
                <select id="id_lider" name="id_lider" class="form-control">
                    <option value="">Sin líder</option>
                    <?php if (!empty($personas_lideres)): ?>
                        <?php foreach ($personas_lideres as $lider): ?>
                            <option value="<?= $lider['Id_Persona'] ?>" 
                                    <?= isset($persona) && $persona['Id_Lider'] == $lider['Id_Persona'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lider['Nombre'] . ' ' . $lider['Apellido']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="tipo_reunion">Tipo de Reunión (Primera vez)</label>
                <select id="tipo_reunion" name="tipo_reunion" class="form-control">
                    <option value="">Seleccionar...</option>
                    <option value="Domingo" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Domingo' ? 'selected' : '' ?>>Domingo</option>
                    <option value="Celula" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Celula' ? 'selected' : '' ?>>Célula</option>
                    <option value="Reu Jovenes" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Reu Jovenes' ? 'selected' : '' ?>>Reunión Jóvenes</option>
                    <option value="Reu Hombre" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Reu Hombre' ? 'selected' : '' ?>>Reunión Hombre</option>
                    <option value="Reu Mujeres" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Reu Mujeres' ? 'selected' : '' ?>>Reunión Mujeres</option>
                    <option value="Grupo Go" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Grupo Go' ? 'selected' : '' ?>>Grupo Go</option>
                    <option value="Seminario" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Seminario' ? 'selected' : '' ?>>Seminario</option>
                    <option value="Pesca" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Pesca' ? 'selected' : '' ?>>Pesca</option>
                    <option value="Semana Santa" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Semana Santa' ? 'selected' : '' ?>>Semana Santa</option>
                    <option value="Otro" <?= isset($persona) && $persona['Tipo_Reunion'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="id_celula">Célula</label>
                <select id="id_celula" name="id_celula" class="form-control">
                    <option value="">Sin célula</option>
                    <?php if (!empty($celulas)): ?>
                        <?php foreach ($celulas as $celula): ?>
                            <option value="<?= $celula['Id_Celula'] ?>" 
                                    <?= isset($persona) && $persona['Id_Celula'] == $celula['Id_Celula'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($celula['Nombre_Celula']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_rol">Rol</label>
                <select id="id_rol" name="id_rol" class="form-control">
                    <option value="">Sin rol</option>
                    <?php if (!empty($roles)): ?>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['Id_Rol'] ?>" 
                                    <?= isset($persona) && $persona['Id_Rol'] == $rol['Id_Rol'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rol['Nombre_Rol']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_ministerio">Ministerio</label>
                <select id="id_ministerio" name="id_ministerio" class="form-control">
                    <option value="">Sin ministerio</option>
                    <?php if (!empty($ministerios)): ?>
                        <?php foreach ($ministerios as $ministerio): ?>
                            <option value="<?= $ministerio['Id_Ministerio'] ?>" 
                                    <?= isset($persona) && $persona['Id_Ministerio'] == $ministerio['Id_Ministerio'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ministerio['Nombre_Ministerio']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
        </div>

        <!-- Acceso al Sistema - Solo Administradores -->
        <?php if (AuthController::esAdministrador()): ?>
        <div class="form-section" style="margin-top: 30px; padding-top: 30px; border-top: 2px solid #ddd;">
            <h3 style="margin-bottom: 20px; color: #667eea;">
                <i class="bi bi-key"></i> Acceso al Sistema
            </h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="usuario">Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control" 
                           value="<?= htmlspecialchars($persona['Usuario'] ?? '') ?>"
                           placeholder="Dejar vacío si no tendrá acceso al sistema">
                    <small class="form-text text-muted">
                        Si asigna un usuario, la persona podrá iniciar sesión en el sistema
                    </small>
                </div>

                <div class="form-group">
                    <label for="contrasena">
                        Contraseña <?= isset($persona) ? '(Dejar vacío para mantener la actual)' : '' ?>
                    </label>
                    <input type="password" id="contrasena" name="contrasena" class="form-control" 
                           placeholder="<?= isset($persona) ? 'Solo llenar si desea cambiar la contraseña' : 'Contraseña para acceso' ?>">
                    <small class="form-text text-muted">
                        Mínimo 6 caracteres
                    </small>
                </div>

                <?php if (isset($persona)): ?>
                <div class="form-group">
                    <label for="estado_cuenta">Estado de Cuenta</label>
                    <select id="estado_cuenta" name="estado_cuenta" class="form-control">
                        <option value="Activo" <?= isset($persona) && $persona['Estado_Cuenta'] == 'Activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="Inactivo" <?= isset($persona) && $persona['Estado_Cuenta'] == 'Inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="Bloqueado" <?= isset($persona) && $persona['Estado_Cuenta'] == 'Bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                    </select>
                    <small class="form-text text-muted">
                        Solo las cuentas activas pueden iniciar sesión
                    </small>
                </div>
                <?php endif; ?>
            </div>

            <?php if (isset($persona) && !empty($persona['Ultimo_Acceso'])): ?>
            <div class="alert alert-info" style="margin-top: 15px;">
                <i class="bi bi-clock-history"></i> 
                <strong>Último acceso:</strong> 
                <?= date('d/m/Y H:i:s', strtotime($persona['Ultimo_Acceso'])) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Guardar</button>
            <a href="<?= PUBLIC_URL ?>?url=personas" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php include VIEWS . '/layout/footer.php'; ?>
