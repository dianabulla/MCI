<?php
/**
 * Vista: Detalle de Persona
 */
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user"></i> Información Personal</h5>
            </div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">Nombre Completo</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($persona['nombre'] . ' ' . $persona['apellido']); ?></dd>

                    <dt class="col-sm-3">Email</dt>
                    <dd class="col-sm-9">
                        <a href="mailto:<?php echo htmlspecialchars($persona['email']); ?>">
                            <?php echo htmlspecialchars($persona['email'] ?? 'N/A'); ?>
                        </a>
                    </dd>

                    <dt class="col-sm-3">Teléfono</dt>
                    <dd class="col-sm-9">
                        <a href="tel:<?php echo htmlspecialchars($persona['telefono']); ?>">
                            <?php echo htmlspecialchars($persona['telefono'] ?? 'N/A'); ?>
                        </a>
                    </dd>

                    <dt class="col-sm-3">Fecha Nacimiento</dt>
                    <dd class="col-sm-9">
                        <?php 
                        if ($persona['fecha_nacimiento']) {
                            echo date('d/m/Y', strtotime($persona['fecha_nacimiento']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </dd>

                    <dt class="col-sm-3">Registro</dt>
                    <dd class="col-sm-9"><?php echo date('d/m/Y H:i', strtotime($persona['fecha_creacion'])); ?></dd>
                </dl>

                <div class="d-flex gap-2">
                    <a href="<?php echo APP_URL; ?>/personas/editar?id=<?php echo $persona['id_persona']; ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="<?php echo APP_URL; ?>/personas/eliminar?id=<?php echo $persona['id_persona']; ?>" 
                       class="btn btn-danger" onclick="return confirm('¿Estás seguro?')">
                        <i class="fas fa-trash"></i> Eliminar
                    </a>
                    <a href="<?php echo APP_URL; ?>/personas" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Atrás
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <?php if (!empty($roles)): ?>
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-shield"></i> Roles</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <?php foreach ($roles as $rol): ?>
                            <li class="mb-2">
                                <span class="badge bg-info"><?php echo htmlspecialchars($rol['nombre_rol']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($ministerios)): ?>
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0"><i class="fas fa-hands-praying"></i> Ministerios</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <?php foreach ($ministerios as $ministerio): ?>
                            <li class="mb-2">
                                <a href="<?php echo APP_URL; ?>/ministerios/ver?id=<?php echo $ministerio['id_ministerio']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($ministerio['nombre_ministerio']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($lider): ?>
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-user-tie"></i> Líder/Mentor</h6>
                </div>
                <div class="card-body">
                    <a href="<?php echo APP_URL; ?>/personas/ver?id=<?php echo $lider['id_persona']; ?>" 
                       class="text-decoration-none">
                        <?php echo htmlspecialchars($lider['nombre'] . ' ' . $lider['apellido']); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($discipulos)): ?>
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-sitemap"></i> Discípulos (<?php echo count($discipulos); ?>)</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        <?php foreach ($discipulos as $discipulo): ?>
                            <li class="mb-2">
                                <a href="<?php echo APP_URL; ?>/personas/ver?id=<?php echo $discipulo['id_persona']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo htmlspecialchars($discipulo['nombre'] . ' ' . $discipulo['apellido']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
