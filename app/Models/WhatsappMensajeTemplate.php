<?php
/**
 * Plantillas de mensajes de WhatsApp para automatizaciones de Personas.
 */

require_once APP . '/Models/BaseModel.php';

class WhatsappMensajeTemplate extends BaseModel {
    protected $table = 'whatsapp_mensaje_template';
    protected $primaryKey = 'clave';

    private $defaults = [
        'bienvenida_persona' => 'Hola {persona_nombre}, bienvenido(a) a MCI Madrid Colombia. Estamos felices de recibirte. Muy pronto te compartiremos información de crecimiento y eventos.',
        'asignacion_lider' => 'Hola {lider_nombre}, tienes una persona nueva asignada: {persona_nombre}.',
        'asignacion_ministerio' => 'Hola {destinatario_nombre}, se asignó una persona nueva al ministerio: {persona_nombre}.'
    ];

    public function __construct() {
        parent::__construct();
        $this->asegurarTabla();
        $this->asegurarDefaults();
    }

    private function asegurarTabla() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    clave VARCHAR(80) PRIMARY KEY,
                    plantilla TEXT NOT NULL,
                    media_url VARCHAR(500) NULL,
                    media_tipo VARCHAR(20) NULL,
                    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->execute($sql);
        $this->asegurarColumna('media_url', "ALTER TABLE {$this->table} ADD COLUMN media_url VARCHAR(500) NULL AFTER plantilla");
        $this->asegurarColumna('media_tipo', "ALTER TABLE {$this->table} ADD COLUMN media_tipo VARCHAR(20) NULL AFTER media_url");
    }

    private function asegurarColumna($columna, $sqlAlter) {
        $rows = $this->query(
            "SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?",
            [$this->table, $columna]
        );

        if (empty($rows)) {
            $this->execute($sqlAlter);
        }
    }

    private function asegurarDefaults() {
        foreach ($this->defaults as $clave => $plantilla) {
            $this->execute(
                "INSERT IGNORE INTO {$this->table} (clave, plantilla) VALUES (?, ?)",
                [$clave, $plantilla]
            );
        }
    }

    public function getPlantillas() {
        $rows = $this->query("SELECT clave, plantilla, media_url, media_tipo FROM {$this->table}");
        $resultado = [];

        foreach ($this->defaults as $clave => $plantillaDefault) {
            $resultado[$clave] = [
                'plantilla' => $plantillaDefault,
                'media_url' => null,
                'media_tipo' => null
            ];
        }

        foreach ($rows as $row) {
            $clave = (string)($row['clave'] ?? '');
            if ($clave !== '') {
                if (!isset($resultado[$clave])) {
                    $resultado[$clave] = [
                        'plantilla' => '',
                        'media_url' => null,
                        'media_tipo' => null
                    ];
                }

                $resultado[$clave]['plantilla'] = (string)($row['plantilla'] ?? '');
                $resultado[$clave]['media_url'] = !empty($row['media_url']) ? (string)$row['media_url'] : null;
                $resultado[$clave]['media_tipo'] = !empty($row['media_tipo']) ? (string)$row['media_tipo'] : null;
            }
        }

        return $resultado;
    }

    public function actualizarPlantilla($clave, $plantilla) {
        if (!isset($this->defaults[$clave])) {
            return false;
        }

        $plantilla = trim((string)$plantilla);
        if ($plantilla === '') {
            $plantilla = $this->defaults[$clave];
        }

        return $this->execute(
            "UPDATE {$this->table} SET plantilla = ? WHERE clave = ?",
            [$plantilla, $clave]
        );
    }

    public function actualizarMedia($clave, $mediaUrl = null, $mediaTipo = null) {
        if (!isset($this->defaults[$clave])) {
            return false;
        }

        $mediaUrl = $mediaUrl !== null ? trim((string)$mediaUrl) : null;
        $mediaTipo = $mediaTipo !== null ? trim((string)$mediaTipo) : null;

        if ($mediaTipo !== null && !in_array($mediaTipo, ['image', 'video'], true)) {
            $mediaTipo = null;
        }

        if ($mediaUrl === '') {
            $mediaUrl = null;
            $mediaTipo = null;
        }

        return $this->execute(
            "UPDATE {$this->table} SET media_url = ?, media_tipo = ? WHERE clave = ?",
            [$mediaUrl, $mediaTipo, $clave]
        );
    }

    public function render($clave, array $vars = []) {
        $plantillas = $this->getPlantillas();
        $template = (string)($plantillas[$clave]['plantilla'] ?? ($this->defaults[$clave] ?? ''));

        if ($template === '') {
            return '';
        }

        $replace = [];
        foreach ($vars as $k => $v) {
            $replace['{' . $k . '}'] = trim((string)$v);
        }

        $rendered = strtr($template, $replace);
        return preg_replace('/\s+/', ' ', trim($rendered));
    }

    public function getTemplatePayload($clave, array $vars = []) {
        $plantillas = $this->getPlantillas();
        $plantilla = $plantillas[$clave] ?? ['plantilla' => ($this->defaults[$clave] ?? ''), 'media_url' => null, 'media_tipo' => null];

        return [
            'mensaje' => $this->render($clave, $vars),
            'media_url' => $plantilla['media_url'] ?? null,
            'media_tipo' => $plantilla['media_tipo'] ?? null
        ];
    }

    public function getVariablesDisponibles() {
        return [
            '{persona_nombre}',
            '{persona_telefono}',
            '{persona_id}',
            '{lider_nombre}',
            '{destinatario_nombre}',
            '{ministerio_nombre}'
        ];
    }
}
