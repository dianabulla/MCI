<?php

/**
 * Códigos jerárquicos G12: 01 pastor, 02 pastora, 0101 primer cupo bajo pastor, 010101 bajo ese líder, etc.
 */
class CodigoJerarquiaHelper {
    public static function codigoRaizPastor(bool $esMujer): string {
        return $esMujer ? '02' : '01';
    }

    public static function segmentoCupo(int $numero): string {
        $n = max(1, min(12, (int)$numero));
        return str_pad((string)$n, 2, '0', STR_PAD_LEFT);
    }

    public static function appendCupo(string $codigoPadre, int $numeroCupo): string {
        $padre = trim($codigoPadre);
        if ($padre === '') {
            return self::segmentoCupo($numeroCupo);
        }
        return $padre . self::segmentoCupo($numeroCupo);
    }

    /**
     * @param array<int, array<int, array<string, mixed>|null>> $equipoDirectoPorLider slots 0-11
     * @return array<int, string> id_persona => codigo
     */
    public static function construirMapaCodigos(
        int $idPastorHombre,
        int $idPastoraMujer,
        array $equipoDirectoPorLider
    ): array {
        $codigos = [];
        $visitados = [];

        if ($idPastorHombre > 0) {
            $codigos[$idPastorHombre] = self::codigoRaizPastor(false);
            self::propagarCodigosHijos($idPastorHombre, $codigos[$idPastorHombre], $equipoDirectoPorLider, $codigos, $visitados, 1);
        }

        if ($idPastoraMujer > 0) {
            $codigos[$idPastoraMujer] = self::codigoRaizPastor(true);
            self::propagarCodigosHijos($idPastoraMujer, $codigos[$idPastoraMujer], $equipoDirectoPorLider, $codigos, $visitados, 1);
        }

        return $codigos;
    }

    /**
     * @param array<int, array<int, array<string, mixed>|null>> $equipoDirectoPorLider
     * @param array<int, string> $codigos
     */
    private static function propagarCodigosHijos(
        int $idLider,
        string $codigoLider,
        array $equipoDirectoPorLider,
        array &$codigos,
        array &$visitados,
        int $nivel
    ): void {
        // Protección ante ciclos de asignación (A->B->A) o grafos corruptos.
        if (isset($visitados[$idLider])) {
            return;
        }
        $visitados[$idLider] = true;

        // Salvaguarda de profundidad extrema para evitar agotar memoria.
        if ($nivel > 60) {
            unset($visitados[$idLider]);
            return;
        }

        $equipo = $equipoDirectoPorLider[$idLider] ?? null;
        if (!is_array($equipo)) {
            unset($visitados[$idLider]);
            return;
        }

        for ($i = 0; $i < 12; $i++) {
            $persona = $equipo[$i] ?? null;
            if (!is_array($persona) || empty($persona['id_persona'])) {
                continue;
            }

            $idPersona = (int)$persona['id_persona'];
            $slot = (int)($persona['slot_numero'] ?? ($i + 1));
            $codigo = self::appendCupo($codigoLider, $slot);
            $codigos[$idPersona] = $codigo;

            if (isset($equipoDirectoPorLider[$idPersona])) {
                self::propagarCodigosHijos($idPersona, $codigo, $equipoDirectoPorLider, $codigos, $visitados, $nivel + 1);
            }
        }

        // Backtracking: permite que otra rama válida lo procese sin bloquear toda la ejecución.
        unset($visitados[$idLider]);
    }
}
