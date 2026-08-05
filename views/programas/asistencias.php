<?php
$programa = 'universidad_vida';
$titulo = 'Asistencias Universidad de la Vida';
$public_url = PUBLIC_URL;
$returnUrlInscritos = '?url=programas/asistencias';

$esAdmin = class_exists('AuthController') && AuthController::esAdministrador();
$puedeAcceso = $esAdmin
    || (class_exists('AuthController') && (
        AuthController::puede('asistencias:ver')
        || AuthController::tieneCoordinacionTotalProgramas()
    ));

$puede_editar = $esAdmin || $puedeAcceso;
$puede_eliminar = $esAdmin || $puedeAcceso;
$puede_editar_persona = $esAdmin
    || (class_exists('AuthController') && AuthController::puedeEditarPersonasConsulta());

include VIEWS . '/escuelas_formacion/listado_inscritos.php';
?>
