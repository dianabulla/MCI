<?php
/**
 * Vista: Lista de Personas
 */
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div></div>
    <a href="<?php echo APP_URL; ?>/personas/crear" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nueva Persona
    </a>
</div>

<?php if (empty($personas)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> No hay personas registradas. 
        <a href="<?php echo APP_URL; ?>/personas/crear" class="alert-link">Crear una ahora</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-primary">
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Fecha Nacimiento</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($personas as $persona): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($persona['nombre'] . ' ' . $persona['apellido']); ?></strong>
                        </td>
                        <td><?php echo htmlspecialchars($persona['email'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($persona['telefono'] ?? 'N/A'); ?></td>
                        <td>
                            <?php 
                            if ($persona['fecha_nacimiento']) {
                                echo date('d/m/Y', strtotime($persona['fecha_nacimiento']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="<?php echo APP_URL; ?>/personas/ver?id=<?php echo $persona['id_persona']; ?>" 
                               class="btn btn-sm btn-info" title="Ver">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="<?php echo APP_URL; ?>/personas/editar?id=<?php echo $persona['id_persona']; ?>" 
                               class="btn btn-sm btn-warning" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?php echo APP_URL; ?>/personas/eliminar?id=<?php echo $persona['id_persona']; ?>" 
                               class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($pagination['last_page'] > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($pagination['current_page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1">Primera</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $pagination['current_page'] - 1; ?>">Anterior</a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                    <li class="page-item <?php echo $i === $pagination['current_page'] ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagination['current_page'] < $pagination['last_page']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $pagination['current_page'] + 1; ?>">Siguiente</a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $pagination['last_page']; ?>">Última</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
