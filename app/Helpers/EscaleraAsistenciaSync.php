<?php
/**
 * Sincroniza la escalera del éxito con asistencias de escuelas de formación.
 */
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/EscuelaFormacionAsistenciaClase.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';

class EscaleraAsistenciaSync {
    private const TOTAL_CLASES_CAP = 10;

    private Persona $personaModel;
    private EscuelaFormacionAsistenciaClase $asistenciaModel;
    private EscuelaFormacionInscripcion $inscripcionModel;
    private bool $soportaChecklist;
    private bool $soportaProceso;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->asistenciaModel = new EscuelaFormacionAsistenciaClase();
        $this->inscripcionModel = new EscuelaFormacionInscripcion();
        $this->personaModel->ensureEscaleraChecklistColumnExists();
        $this->personaModel->ensureProcesoColumnExists();
        $this->soportaChecklist = $this->personaModel->tieneColumna('Escalera_Checklist');
        $this->soportaProceso = $this->personaModel->tieneColumna('Proceso');
    }

    /**
     * Tras guardar una asistencia (UV, bautismo o Cap).
     */
    public function afterGuardarAsistencia(int $idPersona, string $modulo, string $programa): void {
        $idPersona = (int)$idPersona;
        $modulo = trim($modulo);
        $programa = trim($programa);

        if ($idPersona <= 0 || !$this->soportaChecklist) {
            return;
        }

        if ($modulo === 'consolidar' && $programa === 'universidad_vida') {
            $this->syncConsolidarUniversidadVida($idPersona);
            return;
        }

        if ($modulo === 'consolidar' && $programa === 'bautismo') {
            $this->syncConsolidarBautismoDesdeAsistencia($idPersona);
            return;
        }

        if ($programa === 'capacitacion_destino' && preg_match('/^modulo_(\d+)$/', $modulo, $m)) {
            $this->syncDiscipularCapDestino($idPersona, (int)$m[1]);
        }
    }

    /**
     * Valor para la columna Bautismo en el listado UV (asistencia o escalera).
     */
    public function resolverBautismoParaListado(
        int $idPersona,
        ?array $asistenciasBautismoMap = null,
        ?array $escaleraBautismoMap = null
    ): bool {
        $idPersona = (int)$idPersona;
        if ($idPersona <= 0) {
            return false;
        }

        if (is_array($asistenciasBautismoMap) && !empty($asistenciasBautismoMap[$idPersona][1])) {
            return true;
        }

        if (is_array($escaleraBautismoMap)) {
            return !empty($escaleraBautismoMap[$idPersona]);
        }

        $map = $this->asistenciaModel->getAsistenciasPorPrograma([$idPersona], 'consolidar', 'bautismo');
        if (!empty($map[$idPersona][1])) {
            return true;
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return false;
        }

        $checklist = $this->normalizarChecklistEscalera(
            $this->decodificarChecklist((string)($persona['Escalera_Checklist'] ?? ''))
        );

        return !empty($checklist['Consolidar'][2]);
    }

    /**
     * @return array<int, bool> Id_Persona => bautismo marcado en escalera
     */
    public function mapBautismoDesdeEscalera(array $idsPersona): array {
        $idsPersona = array_values(array_filter(array_map('intval', $idsPersona), static fn($id) => $id > 0));
        if (empty($idsPersona) || !$this->soportaChecklist) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($idsPersona), '?'));
        $rows = $this->personaModel->query(
            "SELECT Id_Persona, Escalera_Checklist FROM persona WHERE Id_Persona IN ({$placeholders})",
            $idsPersona
        );

        $map = [];
        foreach ((array)$rows as $row) {
            $id = (int)($row['Id_Persona'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $checklist = $this->normalizarChecklistEscalera(
                $this->decodificarChecklist((string)($row['Escalera_Checklist'] ?? ''))
            );
            $map[$id] = !empty($checklist['Consolidar'][2]);
        }

        return $map;
    }

    private function syncConsolidarUniversidadVida(int $idPersona): void {
        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return;
        }

        $map = $this->asistenciaModel->getAsistenciasPorPrograma([$idPersona], 'consolidar', 'universidad_vida');
        $clases = $map[$idPersona] ?? [];

        $tieneUv = false;
        $tieneEncuentro = false;
        foreach ($clases as $num => $asistio) {
            if (!$asistio) {
                continue;
            }
            $n = (int)$num;
            if (in_array($n, [1, 2, 3, 4, 7, 8, 9, 10], true)) {
                $tieneUv = true;
            }
            if (in_array($n, [5, 6], true)) {
                $tieneEncuentro = true;
            }
        }

        $checklist = $this->normalizarChecklistEscalera(
            $this->decodificarChecklist((string)($persona['Escalera_Checklist'] ?? ''))
        );
        $this->aplicarReglasAutomaticasGanar($checklist, $persona);
        $checklist['Consolidar'][0] = $tieneUv;
        $checklist['Consolidar'][1] = $tieneEncuentro;

        $this->persistir($idPersona, $checklist, $persona);
    }

    private function syncConsolidarBautismoDesdeAsistencia(int $idPersona): void {
        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return;
        }

        $map = $this->asistenciaModel->getAsistenciasPorPrograma([$idPersona], 'consolidar', 'bautismo');
        $marcado = !empty($map[$idPersona][1]);

        $checklist = $this->normalizarChecklistEscalera(
            $this->decodificarChecklist((string)($persona['Escalera_Checklist'] ?? ''))
        );
        $this->aplicarReglasAutomaticasGanar($checklist, $persona);
        $checklist['Consolidar'][2] = $marcado;

        $this->persistir($idPersona, $checklist, $persona);
    }

    private function syncDiscipularCapDestino(int $idPersona, int $nivel): void {
        if ($nivel < 1 || $nivel > 3) {
            return;
        }

        $programasNivel = $this->programasInscripcionPorNivel($nivel);
        $inscritos = $this->inscripcionModel->getProgramasInscritosPersona($idPersona);
        $estaInscrito = false;
        foreach ($programasNivel as $prog) {
            if (in_array($prog, $inscritos, true)) {
                $estaInscrito = true;
                break;
            }
        }

        $persona = $this->personaModel->getById($idPersona);
        if (empty($persona)) {
            return;
        }

        $map = $this->asistenciaModel->getAsistenciasPorPrograma(
            [$idPersona],
            'modulo_' . $nivel,
            'capacitacion_destino'
        );
        $clases = $map[$idPersona] ?? [];
        $asistidas = 0;
        for ($c = 1; $c <= self::TOTAL_CLASES_CAP; $c++) {
            if (!empty($clases[$c])) {
                $asistidas++;
            }
        }

        $mayoria = $asistidas > (int)floor(self::TOTAL_CLASES_CAP / 2);
        $cumple = $estaInscrito && $mayoria;

        $checklist = $this->normalizarChecklistEscalera(
            $this->decodificarChecklist((string)($persona['Escalera_Checklist'] ?? ''))
        );
        $this->aplicarReglasAutomaticasGanar($checklist, $persona);
        $checklist['Discipular'][$nivel - 1] = $cumple;

        $this->persistir($idPersona, $checklist, $persona);
    }

    /**
     * Sincroniza todos los niveles Cap inscritos para una persona.
     */
    public function syncTodosNivelesCapInscritos(int $idPersona): void {
        for ($nivel = 1; $nivel <= 3; $nivel++) {
            $programasNivel = $this->programasInscripcionPorNivel($nivel);
            $inscritos = $this->inscripcionModel->getProgramasInscritosPersona($idPersona);
            foreach ($programasNivel as $prog) {
                if (in_array($prog, $inscritos, true)) {
                    $this->syncDiscipularCapDestino($idPersona, $nivel);
                    break;
                }
            }
        }
    }

    private function programasInscripcionPorNivel(int $nivel): array {
        if ($nivel === 1) {
            return ['capacitacion_destino_nivel_1', 'capacitacion_destino'];
        }
        if ($nivel === 2) {
            return ['capacitacion_destino_nivel_2'];
        }
        if ($nivel === 3) {
            return ['capacitacion_destino_nivel_3'];
        }
        return [];
    }

    private function persistir(int $idPersona, array $checklist, array $persona): void {
        $json = json_encode($checklist, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }

        $proceso = $this->soportaProceso ? $this->calcularProcesoPorChecklist($checklist) : null;
        $this->personaModel->updateEscaleraChecklistYProceso($idPersona, $json, $proceso);
    }

    private function aplicarReglasAutomaticasGanar(array &$checklist, array $persona): void {
        $checklist['Ganar'][1] = !empty($persona['Id_Lider']) && !empty($persona['Id_Ministerio']);
        $checklist['Ganar'][4] = !empty($persona['Id_Celula']);
    }

    private function decodificarChecklist(string $raw): array {
        if ($raw === '') {
            return [];
        }
        $tmp = json_decode($raw, true);
        return is_array($tmp) ? $tmp : [];
    }

    private function normalizarChecklistEscalera($checklist): array {
        $estructuraEtapas = [
            'Ganar' => 6,
            'Consolidar' => 3,
            'Discipular' => 3,
            'Enviar' => 3,
        ];
        $normalizado = [];

        foreach ($estructuraEtapas as $etapa => $totalSubprocesos) {
            $normalizado[$etapa] = array_fill(0, $totalSubprocesos, false);
        }

        $normalizado['_meta'] = [
            'no_disponible_observacion' => '',
            'convenciones' => [],
            'reasignado_automatico' => false,
            'reasignado_automatico_at' => '',
            'reasignado_automatico_motivo' => '',
            'reasignado_manual' => false,
            'reasignado_manual_at' => '',
            'reasignado_manual_motivo' => '',
        ];

        if (!is_array($checklist)) {
            return $normalizado;
        }

        foreach ($estructuraEtapas as $etapa => $totalSubprocesos) {
            $valoresEtapa = $checklist[$etapa] ?? [];
            if (!is_array($valoresEtapa)) {
                continue;
            }
            for ($i = 0; $i < $totalSubprocesos; $i++) {
                $normalizado[$etapa][$i] = !empty($valoresEtapa[$i]);
            }
        }

        if (isset($checklist['_meta']) && is_array($checklist['_meta'])) {
            $normalizado['_meta']['no_disponible_observacion'] = trim((string)($checklist['_meta']['no_disponible_observacion'] ?? ''));
            $normalizado['_meta']['convenciones'] = array_values(array_filter(
                (array)($checklist['_meta']['convenciones'] ?? []),
                static fn($item) => trim((string)$item) !== ''
            ));
            $normalizado['_meta']['reasignado_automatico'] = !empty($checklist['_meta']['reasignado_automatico']);
            $normalizado['_meta']['reasignado_automatico_at'] = trim((string)($checklist['_meta']['reasignado_automatico_at'] ?? ''));
            $normalizado['_meta']['reasignado_automatico_motivo'] = trim((string)($checklist['_meta']['reasignado_automatico_motivo'] ?? ''));
            $normalizado['_meta']['reasignado_manual'] = !empty($checklist['_meta']['reasignado_manual']);
            $normalizado['_meta']['reasignado_manual_at'] = trim((string)($checklist['_meta']['reasignado_manual_at'] ?? ''));
            $normalizado['_meta']['reasignado_manual_motivo'] = trim((string)($checklist['_meta']['reasignado_manual_motivo'] ?? ''));
        }

        return $normalizado;
    }

    private function calcularProcesoPorChecklist(array $checklistNormalizado): string {
        if (!empty($checklistNormalizado['Ganar'][5])) {
            return 'Ganar';
        }

        $etapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $completadasSeguidas = 0;

        foreach ($etapas as $etapa) {
            $valores = $checklistNormalizado[$etapa] ?? [false, false, false];
            if ($etapa === 'Ganar') {
                $completa = !empty($valores[0]) && !empty($valores[1]) && !empty($valores[2])
                    && !empty($valores[3]) && !empty($valores[4]);
            } else {
                $completa = !empty($valores[0]) && !empty($valores[1]) && !empty($valores[2]);
            }

            if (!$completa) {
                break;
            }
            $completadasSeguidas++;
        }

        if ($completadasSeguidas === 0) {
            return 'Ganar';
        }
        if ($completadasSeguidas >= count($etapas)) {
            return 'Enviar';
        }

        return $etapas[$completadasSeguidas];
    }
}
