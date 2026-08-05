<?php

require_once APP . '/Models/Persona.php';

class TallerTourLevantateCorreccion {

    private Persona $personaModel;

    public function __construct() {
        $this->personaModel = new Persona();
    }

    /**
     * @return array{tiene_es_antiguo:bool,tiene_proceso:bool,tiene_origen:bool,tiene_canal:bool,where:array<int,string>}
     */
    private function criterios(): array {
        $tieneEsAntiguo = $this->personaModel->tieneColumna('Es_Antiguo');
        $tieneProceso = $this->personaModel->tieneColumna('Proceso');
        $tieneOrigen = $this->personaModel->tieneColumna('Origen_Ganar');
        $tieneCanal = $this->personaModel->tieneColumna('Canal_Creacion');

        $where = ['1=1'];
        if ($tieneCanal) {
            $where[] = "TRIM(COALESCE(Canal_Creacion, '')) = 'Tour Levántate y Resplandece'";
        } else {
            $where[] = "TRIM(COALESCE(Invitado_Por, '')) = 'Tour Levántate y Resplandece'";
        }
        if ($tieneEsAntiguo) {
            $where[] = 'COALESCE(Es_Antiguo, 0) = 0';
        }
        if ($tieneProceso) {
            $where[] = "(Proceso = 'Ganar' OR Proceso IS NULL OR Proceso = '')";
        }

        return [
            'tiene_es_antiguo' => $tieneEsAntiguo,
            'tiene_proceso' => $tieneProceso,
            'tiene_origen' => $tieneOrigen,
            'tiene_canal' => $tieneCanal,
            'where' => $where,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPendientes(): array {
        $criterios = $this->criterios();
        $sql = 'SELECT Id_Persona, Nombre, Apellido, Numero_Documento, Telefono, Es_Antiguo, Proceso, Canal_Creacion, Fecha_Registro
                FROM persona
                WHERE ' . implode(' AND ', $criterios['where']) . '
                ORDER BY Id_Persona ASC';

        return $this->personaModel->query($sql);
    }

    /**
     * @return array{ok:bool, total:int, mensaje:string}
     */
    public function aplicar(): array {
        $pendientes = $this->obtenerPendientes();
        $total = count($pendientes);

        if ($total === 0) {
            return [
                'ok' => true,
                'total' => 0,
                'mensaje' => 'No hay personas del Tour pendientes de corregir.',
            ];
        }

        $criterios = $this->criterios();
        $sets = [];
        if ($criterios['tiene_es_antiguo']) {
            $sets[] = 'Es_Antiguo = 1';
        }
        if ($criterios['tiene_proceso']) {
            $sets[] = 'Proceso = NULL';
        }
        if ($criterios['tiene_origen']) {
            $sets[] = 'Origen_Ganar = NULL';
        }
        $sets[] = 'Tipo_Reunion = NULL';

        $updateSql = 'UPDATE persona SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $criterios['where']);
        $this->personaModel->execute($updateSql);

        return [
            'ok' => true,
            'total' => $total,
            'mensaje' => $total . ' persona(s) marcadas como antiguas y fuera de Ganar. Las inscripciones del tour no fueron modificadas.',
        ];
    }
}
