<?php
/**
 * API de peticiones descontinuada. La tabla no se borra.
 */
require_once 'config.php';
jsonResponse(false, null, 'El módulo de peticiones ya no está disponible.', 410);
exit;
