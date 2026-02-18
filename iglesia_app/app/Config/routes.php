<?php
/**
 * Configuración de rutas de la aplicación
 * Define todas las rutas disponibles y sus controladores asociados
 */

return [
    // Ruta por defecto (Dashboard/Home)
    '' => 'HomeController@index',
    'home' => 'HomeController@index',
    'dashboard' => 'HomeController@index',

    // Rutas de Personas
    'personas' => 'PersonaController@index',
    'personas/crear' => 'PersonaController@create',
    'personas/store' => 'PersonaController@store',
    'personas/editar' => 'PersonaController@edit',
    'personas/update' => 'PersonaController@update',
    'personas/ver' => 'PersonaController@show',
    'personas/eliminar' => 'PersonaController@delete',

    // Rutas de Células
    'celulas' => 'CelulaController@index',
    'celulas/crear' => 'CelulaController@create',
    'celulas/store' => 'CelulaController@store',
    'celulas/editar' => 'CelulaController@edit',
    'celulas/update' => 'CelulaController@update',
    'celulas/ver' => 'CelulaController@show',
    'celulas/eliminar' => 'CelulaController@delete',

    // Rutas de Ministerios
    'ministerios' => 'MinisterioController@index',
    'ministerios/crear' => 'MinisterioController@create',
    'ministerios/store' => 'MinisterioController@store',
    'ministerios/editar' => 'MinisterioController@edit',
    'ministerios/update' => 'MinisterioController@update',
    'ministerios/ver' => 'MinisterioController@show',
    'ministerios/eliminar' => 'MinisterioController@delete',

    // Rutas de Roles
    'roles' => 'RolController@index',
    'roles/crear' => 'RolController@create',
    'roles/store' => 'RolController@store',
    'roles/editar' => 'RolController@edit',
    'roles/update' => 'RolController@update',
    'roles/ver' => 'RolController@show',
    'roles/eliminar' => 'RolController@delete',

    // Rutas de Asistencias
    'asistencias' => 'AsistenciaController@index',
    'asistencias/crear' => 'AsistenciaController@create',
    'asistencias/store' => 'AsistenciaController@store',
    'asistencias/editar' => 'AsistenciaController@edit',
    'asistencias/update' => 'AsistenciaController@update',
    'asistencias/eliminar' => 'AsistenciaController@delete',

    // Rutas de Eventos
    'eventos' => 'EventoController@index',
    'eventos/crear' => 'EventoController@create',
    'eventos/store' => 'EventoController@store',
    'eventos/editar' => 'EventoController@edit',
    'eventos/update' => 'EventoController@update',
    'eventos/ver' => 'EventoController@show',
    'eventos/eliminar' => 'EventoController@delete',

    // Rutas de Peticiones
    'peticiones' => 'PeticionController@index',
    'peticiones/crear' => 'PeticionController@create',
    'peticiones/store' => 'PeticionController@store',
    'peticiones/editar' => 'PeticionController@edit',
    'peticiones/update' => 'PeticionController@update',
    'peticiones/ver' => 'PeticionController@show',
    'peticiones/eliminar' => 'PeticionController@delete',
];
