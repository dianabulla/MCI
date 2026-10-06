<?php
require_once APP . '/Helpers/SegmentoRedUv.php';

$programa = 'universidad_vida';
$redAsistencia = SegmentoRedUv::normalizarRed((string)($red_asistencia ?? ($_GET['red'] ?? '')));
$etiquetaRed = SegmentoRedUv::etiqueta($redAsistencia);
$titulo = $etiquetaRed !== ''
    ? ('Asistencias Universidad de la Vida · ' . $etiquetaRed)
    : 'Asistencias Universidad de la Vida';
$buscarAsistencias = preg_replace('/\s+/u', ' ', trim((string)($buscar_asistencias ?? ($_GET['buscar'] ?? ''))));
$public_url = PUBLIC_URL;

$paramsRetorno = $_GET;
if (!isset($paramsRetorno['url']) || trim((string)$paramsRetorno['url']) === '') {
    $paramsRetorno['url'] = 'programas/asistencias';
}
if ($redAsistencia !== '') {
    $paramsRetorno['red'] = $redAsistencia;
}
$returnUrlInscritos = '?' . http_build_query($paramsRetorno);

$urlHubRedes = (string)($url_hub_redes ?? '');
if ($urlHubRedes === '') {
    $paramsHub = $paramsRetorno;
    unset($paramsHub['red'], $paramsHub['buscar']);
    $urlHubRedes = rtrim((string)PUBLIC_URL, '/') . '/?' . http_build_query($paramsHub);
}

$esAdmin = class_exists('AuthController') && AuthController::esAdministrador();
$puedeAcceso = $esAdmin
    || (class_exists('AuthController') && AuthController::puedeVerListadoAsistenciasUniversidadVida());

$puede_editar = $esAdmin || $puedeAcceso;
$puede_eliminar = $esAdmin || $puedeAcceso;
$puede_editar_persona = $esAdmin
    || (class_exists('AuthController') && AuthController::puedeEditarPersonasConsulta());

include VIEWS . '/escuelas_formacion/listado_inscritos.php';
