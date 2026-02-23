<?php
/**
 * Vista Dashboard/Home
 */
?>

<div class="row">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Personas</h6>
                        <h2><?php 
                            $persona = new \App\Models\Persona();
                            echo $persona->count();
                        ?></h2>
                    </div>
                    <i class="fas fa-users fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Células</h6>
                        <h2><?php 
                            $celula = new \App\Models\Celula();
                            echo $celula->count();
                        ?></h2>
                    </div>
                    <i class="fas fa-circle-nodes fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Ministerios</h6>
                        <h2><?php 
                            $ministerio = new \App\Models\Ministerio();
                            echo $ministerio->count();
                        ?></h2>
                    </div>
                    <i class="fas fa-hands-praying fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title">Eventos</h6>
                        <h2><?php 
                            $evento = new \App\Models\Evento();
                            echo $evento->count();
                        ?></h2>
                    </div>
                    <i class="fas fa-calendar-days fa-3x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calendar"></i> Próximos Eventos</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php 
                    $evento = new \App\Models\Evento();
                    $proximos = $evento->getProximos(7);
                    
                    if (empty($proximos)): 
                    ?>
                        <p class="text-muted">No hay eventos próximos</p>
                    <?php 
                    else:
                        foreach ($proximos as $ev):
                    ?>
                        <a href="<?php echo APP_URL; ?>/eventos/ver?id=<?php echo $ev['id_evento']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?php echo htmlspecialchars($ev['nombre_evento']); ?></h6>
                                <small><?php echo date('d/m/Y', strtotime($ev['fecha'])); ?></small>
                            </div>
                            <p class="mb-1"><?php echo htmlspecialchars($ev['lugar'] ?? 'Sin lugar'); ?></p>
                        </a>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-hands-praying"></i> Peticiones Pendientes</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php 
                    $peticion = new \App\Models\Peticion();
                    $pendientes = $peticion->getPendientes();
                    
                    if (empty($pendientes)): 
                    ?>
                        <p class="text-muted">No hay peticiones pendientes</p>
                    <?php 
                    else:
                        foreach (array_slice($pendientes, 0, 5) as $pet):
                    ?>
                        <a href="<?php echo APP_URL; ?>/peticiones/ver?id=<?php echo $pet['id_peticion']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">Petición #<?php echo $pet['id_peticion']; ?></h6>
                                <small><?php echo date('d/m/Y', strtotime($pet['fecha_creacion'])); ?></small>
                            </div>
                            <p class="mb-1 text-truncate"><?php echo htmlspecialchars(substr($pet['descripcion_peticion'], 0, 80)); ?>...</p>
                        </a>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border: none;
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
        margin-bottom: 1.5rem;
    }

    .card-header {
        border-bottom: none;
    }
</style>
