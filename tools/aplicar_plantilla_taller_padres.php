<?php
/**
 * Aplica la plantilla «Taller de Padres» al formulario existente.
 * Uso: c:\xampp\php\php.exe tools/aplicar_plantilla_taller_padres.php
 */

define('APP', dirname(__DIR__) . '/app');
define('ROOT', dirname(__DIR__));

require_once ROOT . '/app/Config/config.php';
require_once ROOT . '/conexion.php';
require_once APP . '/Models/TallerFormulario.php';

$model = new TallerFormulario();

$rows = $model->query(
    "SELECT Id_Formulario, Titulo, Slug FROM talleres_formulario
     WHERE LOWER(Titulo) LIKE '%taller%padres%' OR LOWER(Slug) LIKE '%taller%padres%'
     ORDER BY Id_Formulario ASC"
);

if (empty($rows)) {
    echo "No se encontró formulario «Taller de Padres». Creando uno nuevo...\n";
    $config = TallerFormulario::getConfigPlantillaTallerPadres();
    $slug = $model->generarSlugUnico($config['titulo']);
    $id = (int)$model->create([
        'Titulo' => $config['titulo'],
        'Slug' => $slug,
        'Descripcion' => $config['descripcion'],
        'Mensaje_Gracias' => $config['mensaje_gracias'],
        'Texto_Autorizacion' => $config['texto_autorizacion'],
        'Activo' => 1,
    ]);
    $model->aplicarPlantillaTallerPadres($id);
    echo "Formulario creado Id={$id}, slug={$slug}\n";
    exit(0);
}

foreach ($rows as $row) {
    $id = (int)$row['Id_Formulario'];
    $model->aplicarPlantillaTallerPadres($id);
    echo "Plantilla aplicada a Id={$id} («{$row['Titulo']}», slug={$row['Slug']})\n";
}

echo "Listo.\n";
