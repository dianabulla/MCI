<?php
/**
 * Vista: Formulario de Persona
 */
$persona = $persona ?? null;
$isEdit = $persona !== null;
?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?php echo APP_URL; ?>/personas/<?php echo $isEdit ? 'update' : 'store'; ?>" novalidate>
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?php echo $persona['id_persona']; ?>">
            <?php endif; ?>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['nombre']) ? 'is-invalid' : ''; ?>" 
                           id="nombre" name="nombre" value="<?php echo htmlspecialchars($persona['nombre'] ?? ''); ?>" required>
                    <?php if (isset($errors['nombre'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['nombre']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?php echo isset($errors['apellido']) ? 'is-invalid' : ''; ?>" 
                           id="apellido" name="apellido" value="<?php echo htmlspecialchars($persona['apellido'] ?? ''); ?>" required>
                    <?php if (isset($errors['apellido'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['apellido']; ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                           id="email" name="email" value="<?php echo htmlspecialchars($persona['email'] ?? ''); ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="tel" class="form-control" id="telefono" name="telefono" 
                           value="<?php echo htmlspecialchars($persona['telefono'] ?? ''); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                           value="<?php echo htmlspecialchars($persona['fecha_nacimiento'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label for="id_lider_mentor" class="form-label">Líder/Mentor</label>
                    <select class="form-select" id="id_lider_mentor" name="id_lider_mentor">
                        <option value="">-- Sin líder --</option>
                        <!-- Se llenarían dinámicamente con AJAX -->
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="observacion" class="form-label">Observaciones</label>
                <textarea class="form-control" id="observacion" name="observacion" rows="3"><?php echo htmlspecialchars($persona['observacion'] ?? ''); ?></textarea>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $isEdit ? 'Actualizar' : 'Guardar'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>/personas" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
