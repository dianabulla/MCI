<?php
/**
 * Alias avanzados para normalización Nehemías.
 *
 * Estructura:
 * - global: aplica a todos los ministerios
 * - by_ministerio: aplica solo en ese ministerio (clave normalizada o visible)
 *
 * Claves y valores se normalizan automáticamente.
 */

return [
    'global' => [
        'DIANA CEDEIL' => 'DIANA CEDIEL',
        'JEFER' => 'JEFERSON CASSO',
        'YEFER' => 'JEFERSON CASSO',
        'JEFERSSON CASSO' => 'JEFERSON CASSO',
        'FABIAN YCHAVELA' => 'FABIAN Y ELIZABETH',
        'FABIAN Y CHAVELA' => 'FABIAN Y ELIZABETH',
    ],
    'by_ministerio' => [
        // Ejemplo:
        // 'HUGO Y NANCY' => [
        //     'ADRI GUALTEROS' => 'ADRIANA GUALTEROS',
        //     'NANCY V' => 'NANCY VARGAS',
        // ],
    ],
    'token_aliases' => [
        // Abreviaturas comunes por token
        'M' => 'MARIA',
        'MA' => 'MARIA',
        'M.' => 'MARIA',
        'J' => 'JOSE',
        'J.' => 'JOSE',
        'ALEX' => 'ALEJANDRO',
        'ALEJ' => 'ALEJANDRO',
        'ANG' => 'ANGELICA',
        'ANDR' => 'ANDREA',
    ],
];
