<?php

/**
 * Clasificación por red de Universidad de la Vida (Hombres, Mujeres, Jóvenes, Teens).
 * Misma regla que asistencias UV: segmento preferido, luego edad, luego género.
 */
class SegmentoRedUv
{
    public const REDES = ['hombres', 'mujeres', 'jovenes', 'teens'];

    public static function normalizarRed(string $red): string
    {
        $red = strtolower(trim($red));
        $alias = [
            'hombre' => 'hombres',
            'hombres_adultos' => 'hombres',
            'mujer' => 'mujeres',
            'mujeres_adultas' => 'mujeres',
            'joven' => 'jovenes',
        ];
        $red = $alias[$red] ?? $red;

        return in_array($red, self::REDES, true) ? $red : '';
    }

    public static function etiqueta(string $red): string
    {
        switch (self::normalizarRed($red)) {
            case 'hombres':
                return 'Hombres';
            case 'mujeres':
                return 'Mujeres';
            case 'jovenes':
                return 'Jóvenes';
            case 'teens':
                return 'Teens';
            default:
                return '';
        }
    }

    public static function etiquetaCompleta(string $red): string
    {
        switch (self::normalizarRed($red)) {
            case 'hombres':
                return 'Hombres (red adulta)';
            case 'mujeres':
                return 'Mujeres (red adulta)';
            case 'jovenes':
                return 'Jóvenes (14–28 años)';
            case 'teens':
                return 'Teens (9–13 años)';
            default:
                return '';
        }
    }

    public static function clasificarGenero(string $genero): string
    {
        $genero = strtolower(trim($genero));
        if ($genero === '') {
            return 'otro';
        }

        $esMujer = strpos($genero, 'mujer') !== false
            || strpos($genero, 'femen') !== false
            || (bool)preg_match('/(^|[^a-z])(f|fem|female)([^a-z]|$)/', $genero);
        $esHombre = strpos($genero, 'hombre') !== false
            || strpos($genero, 'mascul') !== false
            || (bool)preg_match('/(^|[^a-z])(m|masc|male|h)([^a-z]|$)/', $genero);

        if ($esHombre && !$esMujer) {
            return 'hombre';
        }
        if ($esMujer && !$esHombre) {
            return 'mujer';
        }

        return 'otro';
    }

    public static function resolver(string $genero, int $edad, string $segmentoPreferido = ''): string
    {
        $segmentoPreferido = strtolower(trim($segmentoPreferido));
        if (in_array($segmentoPreferido, ['jovenes', 'teens', 'hombres_adultos', 'mujeres_adultas'], true)) {
            return $segmentoPreferido;
        }

        $generoClasificado = self::clasificarGenero($genero);
        if ($edad >= 14 && $edad <= 28) {
            return 'jovenes';
        }
        if ($edad >= 9 && $edad <= 13) {
            return 'teens';
        }
        if (($edad >= 29 || $edad <= 0) && $generoClasificado === 'hombre') {
            return 'hombres_adultos';
        }
        if (($edad >= 29 || $edad <= 0) && $generoClasificado === 'mujer') {
            return 'mujeres_adultas';
        }

        $genero = strtolower(trim($genero));
        if ($genero !== '' && strpos($genero, 'joven') !== false) {
            return 'jovenes';
        }

        return 'otros';
    }

    public static function coincideRed(string $red, string $genero, int $edad, string $segmentoPreferido = ''): bool
    {
        $red = self::normalizarRed($red);
        if ($red === '') {
            return true;
        }

        $mapa = [
            'hombres' => 'hombres_adultos',
            'mujeres' => 'mujeres_adultas',
            'jovenes' => 'jovenes',
            'teens' => 'teens',
        ];

        return self::resolver($genero, $edad, $segmentoPreferido) === ($mapa[$red] ?? '');
    }

    /**
     * @param array<int, array<string, mixed>> $personas
     * @return array{hombres:int,mujeres:int,jovenes:int,teens:int,otros:int,total:int}
     */
    public static function contarPorRed(array $personas): array
    {
        $out = [
            'hombres' => 0,
            'mujeres' => 0,
            'jovenes' => 0,
            'teens' => 0,
            'otros' => 0,
            'total' => count($personas),
        ];

        foreach ($personas as $persona) {
            $segmento = self::resolver(
                (string)($persona['Genero'] ?? ''),
                (int)($persona['Edad'] ?? 0),
                (string)($persona['Segmento_Preferido'] ?? '')
            );
            if ($segmento === 'hombres_adultos') {
                $out['hombres']++;
            } elseif ($segmento === 'mujeres_adultas') {
                $out['mujeres']++;
            } elseif ($segmento === 'jovenes') {
                $out['jovenes']++;
            } elseif ($segmento === 'teens') {
                $out['teens']++;
            } else {
                $out['otros']++;
            }
        }

        return $out;
    }
}
