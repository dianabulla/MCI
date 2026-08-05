<?php
/**
 * Crea o actualiza el formulario «Tour Levántate y Resplandece».
 * Uso: c:\xampp\php\php.exe tools/aplicar_plantilla_tour_levantate.php
 */

define('APP', dirname(__DIR__) . '/app');
define('ROOT', dirname(__DIR__));

require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/conexion.php';
require_once APP . '/Models/TallerFormulario.php';

$model = new TallerFormulario();
$config = TallerFormulario::getConfigPlantillaTourLevantate();
$slug = $config['slug'];

$existente = $model->getBySlug($slug);

if ($existente && (int)($existente['Id_Formulario'] ?? 0) > 0) {
    $id = (int)$existente['Id_Formulario'];
    $model->update($id, [
        'Titulo' => $config['titulo'],
        'Descripcion' => $config['descripcion'],
        'Mensaje_Gracias' => $config['mensaje_gracias'],
        'Texto_Autorizacion' => $config['texto_autorizacion'],
        'Tipo_Plantilla' => TallerFormulario::TIPO_PLANTILLA_TOUR_LEVANTATE,
        'Imagen_Header' => $config['imagen_header'],
        'Activo' => 1,
    ]);
    $model->reemplazarBloquesTourLevantate($id);
    echo "Formulario actualizado Id={$id}, slug={$slug}\n";
    echo "URL pública: " . PUBLIC_URL . "?url=talleres_publico&slug=" . urlencode($slug) . "\n";
    exit(0);
}

$id = $model->crearFormularioTourLevantate(0);
$form = $model->getById($id);
$slugFinal = (string)($form['Slug'] ?? $slug);
echo "Formulario creado Id={$id}, slug={$slugFinal}\n";
echo "URL pública: " . PUBLIC_URL . "?url=talleres_publico&slug=" . urlencode($slugFinal) . "\n";
