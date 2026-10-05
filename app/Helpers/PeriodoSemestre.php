<?php

/**
 * Periodo académico por año y semestre (S1 ene–jun, S2 jul–dic).
 */
class PeriodoSemestre
{
    /**
     * @return array{anio:int,semestre:int,fecha_inicio:string,fecha_fin:string,etiqueta:string}
     */
    public static function resolver($anio = null, $semestre = null): array
    {
        $anio = $anio !== null ? (int)$anio : (int)($_GET['anio'] ?? date('Y'));
        if ($anio < 2020 || $anio > ((int)date('Y') + 2)) {
            $anio = (int)date('Y');
        }

        $semestre = $semestre !== null ? (int)$semestre : (int)($_GET['semestre'] ?? 0);
        if ($semestre !== 1 && $semestre !== 2) {
            $semestre = ((int)date('n')) <= 6 ? 1 : 2;
        }

        if ($semestre === 1) {
            return [
                'anio' => $anio,
                'semestre' => 1,
                'fecha_inicio' => sprintf('%04d-01-01', $anio),
                'fecha_fin' => sprintf('%04d-06-30', $anio),
                'etiqueta' => 'Semestre 1 (Ene – Jun)',
            ];
        }

        return [
            'anio' => $anio,
            'semestre' => 2,
            'fecha_inicio' => sprintf('%04d-07-01', $anio),
            'fecha_fin' => sprintf('%04d-12-31', $anio),
            'etiqueta' => 'Semestre 2 (Jul – Dic)',
        ];
    }

    public static function fechaEnRango(string $fecha, array $rango): bool
    {
        $fecha = substr(trim($fecha), 0, 10);
        $inicio = trim((string)($rango['fecha_inicio'] ?? ''));
        $fin = trim((string)($rango['fecha_fin'] ?? ''));
        if ($fecha === '' || $fecha === '0000-00-00' || $inicio === '' || $fin === '') {
            return false;
        }

        return $fecha >= $inicio && $fecha <= $fin;
    }

    /**
     * Ciclo en curso según la fecha de hoy (no usa ?anio=&semestre= de la URL).
     *
     * @return array{anio:int,semestre:int,fecha_inicio:string,fecha_fin:string,etiqueta:string}
     */
    public static function actual(): array
    {
        $anio = (int)date('Y');
        $semestre = ((int)date('n')) <= 6 ? 1 : 2;
        return self::resolver($anio, $semestre);
    }
}
