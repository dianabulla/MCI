<?php
/**
 * Tablas resumen por ministerio (UV segmentos H/M/J, Cap. Destino por niveles).
 */
class EscuelaFormacionResumenHelper {

    public static function esProgramaCapacitacionDestino(string $programa): bool {
        $programa = strtolower(trim($programa));
        if ($programa === '') {
            return false;
        }

        return $programa === 'capacitacion_destino' || strpos($programa, 'capacitacion_destino_') === 0;
    }

    /**
     * @return string nivel_1|nivel_2|nivel_3|otros
     */
    public static function resolverNivelCapacitacionDestino(array $inscripcion): string {
        $programa = strtolower(trim((string)($inscripcion['Programa'] ?? '')));
        $segmento = strtolower(trim((string)($inscripcion['Segmento_Preferido'] ?? '')));

        if (in_array($segmento, ['nivel_1', 'nivel_2', 'nivel_3'], true)) {
            return $segmento;
        }

        if ($programa === 'capacitacion_destino' || $programa === 'capacitacion_destino_nivel_1') {
            return 'nivel_1';
        }
        if ($programa === 'capacitacion_destino_nivel_2') {
            return 'nivel_2';
        }
        if ($programa === 'capacitacion_destino_nivel_3') {
            return 'nivel_3';
        }

        return 'otros';
    }

    /**
     * @param array<int, array<string, mixed>> $inscripciones
     * @param array<int, bool> $personasConAsistencia
     * @return array<int, array<string, mixed>>
     */
    public static function construirTablaCapDestinoPorMinisterio(array $inscripciones, array $personasConAsistencia): array {
        $map = [];

        foreach ($inscripciones as $inscripcion) {
            if (!self::esProgramaCapacitacionDestino((string)($inscripcion['Programa'] ?? ''))) {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
            if ($ministerioNombre === '') {
                $ministerioNombre = 'Sin ministerio';
            }

            if (!isset($map[$ministerioNombre])) {
                $map[$ministerioNombre] = [
                    'ministerio' => $ministerioNombre,
                    'nivel_1' => 0,
                    'nivel_2' => 0,
                    'nivel_3' => 0,
                    'otros' => 0,
                    'asistencias_reales' => 0,
                    'total' => 0,
                ];
            }

            $nivel = self::resolverNivelCapacitacionDestino($inscripcion);
            if ($nivel === 'nivel_1') {
                $map[$ministerioNombre]['nivel_1']++;
            } elseif ($nivel === 'nivel_2') {
                $map[$ministerioNombre]['nivel_2']++;
            } elseif ($nivel === 'nivel_3') {
                $map[$ministerioNombre]['nivel_3']++;
            } else {
                $map[$ministerioNombre]['otros']++;
            }

            $map[$ministerioNombre]['total']++;

            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            if ($idPersona > 0 && !empty($personasConAsistencia[$idPersona])) {
                $map[$ministerioNombre]['asistencias_reales']++;
            }
        }

        $tabla = array_values($map);
        usort($tabla, static function ($a, $b) {
            $cmp = ((int)($b['total'] ?? 0)) <=> ((int)($a['total'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
        });

        return $tabla;
    }

    /**
     * @param array<int, array<string, mixed>> $inscripciones
     * @param array<int, bool> $personasConAsistencia
     * @return array<int, array<string, mixed>>
     */
    public static function construirTablaUniversidadVidaPorMinisterio(array $inscripciones, array $personasConAsistencia): array {
        $map = [];

        foreach ($inscripciones as $inscripcion) {
            if ((string)($inscripcion['Programa'] ?? '') !== 'universidad_vida') {
                continue;
            }

            $ministerioNombre = trim((string)($inscripcion['Nombre_Ministerio'] ?? ''));
            if ($ministerioNombre === '') {
                $ministerioNombre = 'Sin ministerio';
            }

            if (!isset($map[$ministerioNombre])) {
                $map[$ministerioNombre] = [
                    'ministerio' => $ministerioNombre,
                    'hombres' => 0,
                    'mujeres' => 0,
                    'jovenes' => 0,
                    'asistencias_reales' => 0,
                    'total' => 0,
                ];
            }

            $edad = (int)($inscripcion['Edad'] ?? 0);
            $genero = strtolower(trim((string)($inscripcion['Genero'] ?? '')));
            $esMujer = strpos($genero, 'mujer') !== false
                || strpos($genero, 'femen') !== false
                || in_array($genero, ['f', 'fem', 'female'], true);
            $esHombre = strpos($genero, 'hombre') !== false
                || strpos($genero, 'mascul') !== false
                || in_array($genero, ['m', 'masc', 'male', 'h'], true);
            $esJoven = $edad >= 14 && $edad <= 28;

            if ($esJoven) {
                $map[$ministerioNombre]['jovenes']++;
            } elseif ($esHombre) {
                $map[$ministerioNombre]['hombres']++;
            } elseif ($esMujer) {
                $map[$ministerioNombre]['mujeres']++;
            }

            $map[$ministerioNombre]['total']++;

            $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
            if ($idPersona > 0 && !empty($personasConAsistencia[$idPersona])) {
                $map[$ministerioNombre]['asistencias_reales']++;
            }
        }

        $tabla = array_values($map);
        usort($tabla, static function ($a, $b) {
            $cmp = ((int)($b['total'] ?? 0)) <=> ((int)($a['total'] ?? 0));
            if ($cmp !== 0) {
                return $cmp;
            }

            return strcmp((string)($a['ministerio'] ?? ''), (string)($b['ministerio'] ?? ''));
        });

        return $tabla;
    }
}
