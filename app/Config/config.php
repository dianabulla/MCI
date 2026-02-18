<?php
/**
 * Archivo de configuración de la aplicación
 */

// Zona horaria
date_default_timezone_set('America/Bogota');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'mcimadrid');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// URLs
define('BASE_URL', 'http://localhost/mcimadrid');
define('PUBLIC_URL', BASE_URL . '/public');
define('ASSETS_URL', PUBLIC_URL . '/assets');
