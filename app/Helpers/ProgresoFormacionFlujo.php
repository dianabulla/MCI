<?php
/**
 * Fuente única del flujo formativo para reportes y dashboards:
 * Ganar (contacto, visita/fono, célula) → UV (inscripción) → Encuentro (lista asistencias)
 * → Bautismo → Capacitación Destino (niveles) → líder de célula.
 */
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';

class ProgresoFormacionFlujo {
    private EscuelaFormacionInscripcion $inscripcionModel;
    private EscuelaFormacionAsistenciaClase $asistenciaModel;

    public function __construct() {
        $this->inscripcionModel = new EscuelaFormacionInscripcion();
        $this->asistenciaModel = new EscuelaFormacionAsistenciaClase();
    }

    /**
     * @param array<int, int> $idsPersona
     * @return array<int, array<string, mixed>>
     */
    public function hechosPorPersonas(array $idsPersona): array {
        $idsPersona = array_values(array_unique(array_filter(array_map('intval', $idsPersona), static function ($id) {
            return $id > 0;
        })));
        $base = [
            'inscrito_uv' => false,
            'asistio_uv' => false,
            'encuentro' => false,
            'bautismo' => false,
            'cap_n1' => false,
            'cap_n2' => false,
            'cap_n3' => false,
            'cap_destino' => false,
            'lider_celula' => false,
        ];
        if ($idsPersona === []) {
            return [];
        }

        $hechos = [];
        foreach ($idsPersona as $id) {
            $hechos[$id] = $base;
        }

        $placeholders = implode(',', array_fill(0, count($idsPersona), '?'));
        $inscripciones = $this->inscripcionModel->query(
            "SELECT Id_Persona, Programa
             FROM escuela_formacion_inscripcion
             WHERE Id_Persona IN ({$placeholders})",
            $idsPersona
        );
        foreach ((array)$inscripciones as $fila) {
            $id = (int)($fila['Id_Persona'] ?? 0);
            if ($id <= 0 || !isset($hechos[$id])) {
                continue;
            }
            $this->aplicarProgramaAHecho($hechos[$id], (string)($fila['Programa'] ?? ''));
        }

        $flagsPorCedula = $this->inscripcionModel->getFlagsFormacionPorIdsPersonas($idsPersona);
        foreach ($flagsPorCedula as $id => $flags) {
            $id = (int)$id;
            if (!isset($hechos[$id]) || !is_array($flags)) {
                continue;
            }
            if (!empty($flags['universidad_vida'])) {
                $hechos[$id]['inscrito_uv'] = true;
            }
            if (!empty($flags['encuentro'])) {
                $hechos[$id]['encuentro'] = true;
            }
            if (!empty($flags['bautismo'])) {
                $hechos[$id]['bautismo'] = true;
            }
            if (!empty($flags['capacitacion_destino'])) {
                $hechos[$id]['cap_destino'] = true;
            }
        }

        $asistUv = $this->asistenciaModel->getAsistenciasPorPrograma($idsPersona, 'consolidar', 'universidad_vida');
        foreach ((array)$asistUv as $id => $clases) {
            $id = (int)$id;
            if (!isset($hechos[$id])) {
                continue;
            }
            for ($n = 1; $n <= 4; $n++) {
                if (!empty($clases[$n])) {
                    $hechos[$id]['asistio_uv'] = true;
                    break;
                }
            }
            if (!empty($clases[5]) || !empty($clases[6])) {
                $hechos[$id]['encuentro'] = true;
            }
        }

        $asistEnc = $this->asistenciaModel->getAsistenciasPorPrograma($idsPersona, 'consolidar', 'encuentro');
        foreach ((array)$asistEnc as $id => $clases) {
            $id = (int)$id;
            if (!isset($hechos[$id])) {
                continue;
            }
            foreach ((array)$clases as $ok) {
                if (!empty($ok)) {
                    $hechos[$id]['encuentro'] = true;
                    break;
                }
            }
        }

        $asistBaut = $this->asistenciaModel->getAsistenciasPorPrograma($idsPersona, 'consolidar', 'bautismo');
        foreach ((array)$asistBaut as $id => $clases) {
            $id = (int)$id;
            if (!isset($hechos[$id])) {
                continue;
            }
            if (!empty($clases[1])) {
                $hechos[$id]['bautismo'] = true;
            }
        }

        $lideres = $this->inscripcionModel->query(
            "SELECT DISTINCT Id_Lider AS Id_Persona
             FROM celula
             WHERE Id_Lider IS NOT NULL AND Id_Lider > 0
               AND Id_Lider IN ({$placeholders})",
            $idsPersona
        );
        foreach ((array)$lideres as $fila) {
            $id = (int)($fila['Id_Persona'] ?? 0);
            if ($id > 0 && isset($hechos[$id])) {
                $hechos[$id]['lider_celula'] = true;
            }
        }

        $this->aplicarEscaleraRapidaAHechos($hechos, $idsPersona, $placeholders);

        foreach ($hechos as $id => $hecho) {
            if (!empty($hecho['encuentro']) || !empty($hecho['bautismo']) || !empty($hecho['cap_destino'])) {
                $hechos[$id]['inscrito_uv'] = true;
            }
        }

        return $hechos;
    }

    /**
     * Alinea el reporte con la escalera rápida: peldaños marcados y etapas anteriores
     * a la etapa actual (Proceso) se dan por hechas, igual que en la ficha.
     *
     * @param array<int, array<string, mixed>> $hechos
     * @param array<int, int> $idsPersona
     */
    private function aplicarEscaleraRapidaAHechos(array &$hechos, array $idsPersona, string $placeholders): void {
        $filas = $this->inscripcionModel->query(
            "SELECT Id_Persona, Escalera_Checklist, Proceso
             FROM persona
             WHERE Id_Persona IN ({$placeholders})",
            $idsPersona
        );
        foreach ((array)$filas as $fila) {
            $id = (int)($fila['Id_Persona'] ?? 0);
            if ($id <= 0 || !isset($hechos[$id])) {
                continue;
            }
            $this->mezclarEscaleraRapidaEnHecho($hechos[$id], $fila);
        }
    }

    /**
     * @param array<string, mixed> $hecho
     */
    private function mezclarEscaleraRapidaEnHecho(array &$hecho, array $persona): void {
        $orden = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $proceso = trim((string)($persona['Proceso'] ?? ''));
        $idxProceso = array_search($proceso, $orden, true);

        $checklist = [];
        $raw = trim((string)($persona['Escalera_Checklist'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $checklist = $decoded;
            }
        }

        $marcado = static function (string $etapa, int $indice) use ($checklist, $orden, $idxProceso): bool {
            $checksEtapa = $checklist[$etapa] ?? [];
            if (is_array($checksEtapa) && !empty($checksEtapa[$indice])) {
                return true;
            }
            $idxEtapa = array_search($etapa, $orden, true);
            if ($idxProceso !== false && $idxEtapa !== false && $idxProceso > $idxEtapa) {
                return true;
            }

            return false;
        };

        if ($marcado('Consolidar', 0) || $marcado('Consolidar', 1) || $marcado('Consolidar', 2)) {
            $hecho['inscrito_uv'] = true;
        }
        if ($marcado('Consolidar', 1)) {
            $hecho['encuentro'] = true;
        }
        if ($marcado('Consolidar', 2)) {
            $hecho['bautismo'] = true;
        }
        if ($marcado('Discipular', 0)) {
            $hecho['cap_n1'] = true;
            $hecho['cap_destino'] = true;
            $hecho['inscrito_uv'] = true;
        }
        if ($marcado('Discipular', 1)) {
            $hecho['cap_n2'] = true;
            $hecho['cap_destino'] = true;
            $hecho['inscrito_uv'] = true;
        }
        if ($marcado('Discipular', 2)) {
            $hecho['cap_n3'] = true;
            $hecho['cap_destino'] = true;
            $hecho['inscrito_uv'] = true;
        }
        if ($idxProceso !== false && $idxProceso >= array_search('Discipular', $orden, true)) {
            $hecho['inscrito_uv'] = true;
            $hecho['cap_destino'] = true;
        }
    }

    /**
     * @param array<string, mixed> $hecho
     */
    private function aplicarProgramaAHecho(array &$hecho, string $programa): void {
        $programa = strtolower(trim($programa));
        if ($programa === 'universidad_vida') {
            $hecho['inscrito_uv'] = true;
            return;
        }
        if ($programa === 'encuentro') {
            $hecho['encuentro'] = true;
            return;
        }
        if ($programa === 'bautismo') {
            $hecho['bautismo'] = true;
            return;
        }
        if ($programa === 'capacitacion_destino' || $programa === 'capacitacion_destino_nivel_1') {
            $hecho['cap_n1'] = true;
            $hecho['cap_destino'] = true;
            return;
        }
        if ($programa === 'capacitacion_destino_nivel_2') {
            $hecho['cap_n2'] = true;
            $hecho['cap_destino'] = true;
            return;
        }
        if ($programa === 'capacitacion_destino_nivel_3') {
            $hecho['cap_n3'] = true;
            $hecho['cap_destino'] = true;
        }
    }
}
