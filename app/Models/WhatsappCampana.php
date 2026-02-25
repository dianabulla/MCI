<?php
/**
 * Modelo WhatsApp Campañas
 */

require_once APP . '/Models/BaseModel.php';

class WhatsappCampana extends BaseModel {
    protected $table = 'whatsapp_campanas';
    protected $primaryKey = 'id';

    public function getAllWithStats() {
        $sql = "SELECT c.*, p.nombre AS plantilla_nombre, p.tipo AS plantilla_tipo,
                       SUM(CASE WHEN q.estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                       SUM(CASE WHEN q.estado IN ('enviado','entregado','leido') THEN 1 ELSE 0 END) AS enviados,
                       SUM(CASE WHEN q.estado = 'fallido' THEN 1 ELSE 0 END) AS fallidos,
                       COUNT(q.id) AS total_cola
                FROM whatsapp_campanas c
                INNER JOIN whatsapp_plantillas p ON p.id = c.id_plantilla
                LEFT JOIN whatsapp_cola_envio q ON q.id_campana = c.id
                GROUP BY c.id, p.nombre, p.tipo
                ORDER BY c.creado_en DESC, c.id DESC";

        return $this->query($sql);
    }

    public function crearCampanaConPlantilla(array $campana, array $plantilla, array $filtro) {
        try {
            $this->db->beginTransaction();

            $sqlPlantilla = "INSERT INTO whatsapp_plantillas (nombre, cuerpo, tipo, media_url, activa, creado_por)
                             VALUES (?, ?, ?, ?, 1, ?)";
            $stmtPlantilla = $this->db->prepare($sqlPlantilla);
            $stmtPlantilla->execute([
                $plantilla['nombre'],
                $plantilla['cuerpo'],
                $plantilla['tipo'],
                $plantilla['media_url'],
                $plantilla['creado_por']
            ]);

            $idPlantilla = (int)$this->db->lastInsertId();

            $sqlCampana = "INSERT INTO whatsapp_campanas
                (nombre, objetivo, id_plantilla, fecha_programada, estado, limite_lote, pausa_segundos, creado_por)
                VALUES (?, ?, ?, ?, 'programada', ?, ?, ?)";
            $stmtCampana = $this->db->prepare($sqlCampana);
            $stmtCampana->execute([
                $campana['nombre'],
                $campana['objetivo'],
                $idPlantilla,
                $campana['fecha_programada'],
                $campana['limite_lote'],
                $campana['pausa_segundos'],
                $campana['creado_por']
            ]);

            $idCampana = (int)$this->db->lastInsertId();

            $sqlFiltro = "INSERT INTO whatsapp_campana_filtros (id_campana, filtro_json) VALUES (?, ?)";
            $stmtFiltro = $this->db->prepare($sqlFiltro);
            $stmtFiltro->execute([
                $idCampana,
                json_encode($filtro, JSON_UNESCAPED_UNICODE)
            ]);

            $this->db->commit();
            return $idCampana;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getCampanaConFiltro($idCampana) {
        $sql = "SELECT c.*, f.filtro_json
                FROM whatsapp_campanas c
                LEFT JOIN whatsapp_campana_filtros f ON f.id_campana = c.id
                WHERE c.id = ?
                LIMIT 1";
        $result = $this->query($sql, [$idCampana]);
        if (empty($result)) {
            return null;
        }

        $campana = $result[0];
        $campana['filtro'] = [];
        if (!empty($campana['filtro_json'])) {
            $decoded = json_decode($campana['filtro_json'], true);
            if (is_array($decoded)) {
                $campana['filtro'] = $decoded;
            }
        }

        return $campana;
    }

    public function generarColaDesdeFiltro($idCampana) {
        $campana = $this->getCampanaConFiltro($idCampana);
        if (empty($campana)) {
            throw new Exception('Campaña no encontrada');
        }

        $filtro = $campana['filtro'] ?? [];
        $lider = trim((string)($filtro['lider'] ?? ''));
        $liderNehemias = trim((string)($filtro['lider_nehemias'] ?? ''));
        $requiereConsentimientoWhatsapp = !empty($filtro['consentimiento_whatsapp']) ? 1 : 0;

        $sql = "INSERT INTO whatsapp_cola_envio
                    (id_campana, id_nehemias, telefono, payload_json, estado, intentos, programado_en)
                SELECT
                    ?,
                    n.Id_Nehemias,
                    n.Telefono_Normalizado,
                    JSON_OBJECT(
                        'nombres', n.Nombres,
                        'apellidos', n.Apellidos,
                        'cedula', n.Numero_Cedula,
                        'lider', n.Lider,
                        'lider_nehemias', n.Lider_Nehemias
                    ),
                    'pendiente',
                    0,
                    ?
                FROM nehemias n
                LEFT JOIN whatsapp_optout o
                    ON o.telefono = n.Telefono_Normalizado
                   AND o.activo = 1
                WHERE n.Acepta = 1
                  AND n.Telefono_Normalizado IS NOT NULL
                  AND n.Telefono_Normalizado <> ''
                  AND o.id IS NULL";

        $params = [(int)$idCampana, $campana['fecha_programada']];

        if ($requiereConsentimientoWhatsapp) {
            $sql .= " AND n.Consentimiento_Whatsapp = 1";
        }

        if ($lider !== '') {
            $sql .= " AND n.Lider = ?";
            $params[] = $lider;
        }

        if ($liderNehemias !== '') {
            $sql .= " AND n.Lider_Nehemias LIKE ?";
            $params[] = '%' . $liderNehemias . '%';
        }

        $sql .= " AND NOT EXISTS (
                        SELECT 1
                        FROM whatsapp_cola_envio q
                        WHERE q.id_campana = ?
                          AND q.id_nehemias = n.Id_Nehemias
                    )";
        $params[] = (int)$idCampana;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    public function obtenerConfigActiva() {
        $sql = "SELECT * FROM whatsapp_config WHERE activo = 1 ORDER BY id DESC LIMIT 1";
        $rows = $this->query($sql);
        return $rows[0] ?? null;
    }

    public function procesarLotePendiente($limite = 50, $dryRun = false) {
        $limite = max(1, (int)$limite);
        $pendientes = $this->obtenerPendientes($limite);
        $resultado = [
            'total' => count($pendientes),
            'enviados' => 0,
            'fallidos' => 0,
            'detalles' => []
        ];

        if (empty($pendientes)) {
            return $resultado;
        }

        $config = $this->obtenerConfigActiva();
        if (!$dryRun && empty($config)) {
            throw new Exception('No hay configuración activa en whatsapp_config');
        }

        foreach ($pendientes as $item) {
            $idCola = (int)$item['id'];
            $idCampana = (int)$item['id_campana'];
            $this->marcarEstadoCola($idCola, 'procesando');
            $this->actualizarEstadoCampana($idCampana);

            $payload = json_decode((string)($item['payload_json'] ?? '{}'), true);
            if (!is_array($payload)) {
                $payload = [];
            }

            $mensaje = $this->aplicarVariables((string)$item['plantilla_cuerpo'], $payload);
            $telefono = (string)$item['telefono'];

            if ($dryRun) {
                $messageId = 'dryrun-' . $idCola . '-' . time();
                $this->marcarResultadoCola($idCola, 'enviado', $messageId, null);
                $this->registrarEvento($idCola, $messageId, 'sent', ['dry_run' => true]);
                $resultado['enviados']++;
                $resultado['detalles'][] = [
                    'id_cola' => $idCola,
                    'telefono' => $telefono,
                    'estado' => 'enviado',
                    'dry_run' => true
                ];
                $this->actualizarEstadoCampana($idCampana);
                continue;
            }

            $envio = $this->enviarProveedor(
                $config,
                $telefono,
                $mensaje,
                (string)$item['plantilla_tipo'],
                (string)($item['plantilla_media_url'] ?? ''),
                $payload
            );

            if (!empty($envio['ok'])) {
                $messageId = (string)($envio['message_id'] ?? '');
                $this->marcarResultadoCola($idCola, 'enviado', $messageId, null);
                $this->registrarEvento($idCola, $messageId, 'sent', ['provider_response' => $envio['raw'] ?? null]);
                $resultado['enviados']++;
                $resultado['detalles'][] = [
                    'id_cola' => $idCola,
                    'telefono' => $telefono,
                    'estado' => 'enviado'
                ];
            } else {
                $error = (string)($envio['error'] ?? 'Error desconocido al enviar');
                $this->marcarResultadoCola($idCola, 'fallido', null, $error);
                $this->registrarEvento($idCola, null, 'failed', ['error' => $error]);
                $resultado['fallidos']++;
                $resultado['detalles'][] = [
                    'id_cola' => $idCola,
                    'telefono' => $telefono,
                    'estado' => 'fallido',
                    'error' => $error
                ];
            }

            $this->actualizarEstadoCampana($idCampana);
        }

        return $resultado;
    }

    public function procesarWebhookEstado(array $payload) {
        $data = $this->extraerEstadoWebhook($payload);
        if (empty($data['provider_message_id']) || empty($data['evento'])) {
            return [
                'actualizado' => false,
                'motivo' => 'Payload sin provider_message_id/evento reconocible'
            ];
        }

        $providerMessageId = (string)$data['provider_message_id'];
        $evento = (string)$data['evento'];
        $estadoCola = $this->mapearEstadoColaDesdeEvento($evento);
        $error = (string)($data['error'] ?? '');

        $sqlBuscar = "SELECT id, id_campana FROM whatsapp_cola_envio WHERE proveedor_message_id = ? ORDER BY id DESC LIMIT 1";
        $rows = $this->query($sqlBuscar, [$providerMessageId]);
        $idCola = !empty($rows) ? (int)$rows[0]['id'] : null;
        $idCampana = !empty($rows) ? (int)$rows[0]['id_campana'] : null;

        if ($idCola !== null) {
            $this->marcarResultadoCola($idCola, $estadoCola, $providerMessageId, $error !== '' ? $error : null, false);
        }

        $this->registrarEvento($idCola, $providerMessageId, $evento, $payload);

        if ($idCampana !== null) {
            $this->actualizarEstadoCampana($idCampana);
        }

        return [
            'actualizado' => true,
            'id_cola' => $idCola,
            'evento' => $evento,
            'estado_cola' => $estadoCola
        ];
    }

    private function obtenerPendientes($limite) {
        $sql = "SELECT q.*, c.id AS campana_id, c.estado AS campana_estado,
                       p.cuerpo AS plantilla_cuerpo, p.tipo AS plantilla_tipo, p.media_url AS plantilla_media_url
                FROM whatsapp_cola_envio q
                INNER JOIN whatsapp_campanas c ON c.id = q.id_campana
                INNER JOIN whatsapp_plantillas p ON p.id = c.id_plantilla
                WHERE q.estado = 'pendiente'
                  AND q.programado_en <= NOW()
                  AND c.fecha_programada <= NOW()
                  AND c.estado IN ('programada','enviando')
                ORDER BY q.programado_en ASC, q.id ASC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function aplicarVariables($texto, array $payload) {
        $reemplazos = [
            '{{nombres}}' => (string)($payload['nombres'] ?? ''),
            '{{apellidos}}' => (string)($payload['apellidos'] ?? ''),
            '{{cedula}}' => (string)($payload['cedula'] ?? ''),
            '{{lider}}' => (string)($payload['lider'] ?? ''),
            '{{lider_nehemias}}' => (string)($payload['lider_nehemias'] ?? '')
        ];
        return strtr((string)$texto, $reemplazos);
    }

    private function enviarProveedor(array $config, $telefono, $mensaje, $tipo, $mediaUrl, array $payload = []) {
        $proveedor = strtolower(trim((string)($config['proveedor'] ?? '')));
        $endpoint = trim((string)($config['endpoint_base'] ?? ''));
        $apiKey = (string)($config['api_key_encriptada'] ?? '');

        if ($endpoint === '') {
            return ['ok' => false, 'error' => 'Endpoint de proveedor no configurado'];
        }

        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'Extensión cURL no disponible'];
        }

        if ($proveedor === 'meta_cloud') {
            $body = [
                'messaging_product' => 'whatsapp',
                'to' => ltrim((string)$telefono, '+')
            ];

            if ($tipo === 'texto' || $mediaUrl === '') {
                $body['type'] = 'text';
                $body['text'] = ['body' => (string)$mensaje];
            } else {
                $body['type'] = $tipo;
                $body[$tipo] = ['link' => (string)$mediaUrl, 'caption' => (string)$mensaje];
            }
        } else {
            $body = [
                'to' => (string)$telefono,
                'message' => (string)$mensaje,
                'type' => (string)$tipo,
                'media_url' => $mediaUrl !== '' ? (string)$mediaUrl : null,
                'meta' => $payload
            ];
        }

        $ch = curl_init($endpoint);
        $headers = ['Content-Type: application/json'];
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'error' => $curlErr !== '' ? $curlErr : 'Sin respuesta del proveedor'];
        }

        $decoded = json_decode($response, true);
        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMsg = is_array($decoded) ? (string)($decoded['error']['message'] ?? $decoded['message'] ?? 'Error HTTP ' . $httpCode) : ('Error HTTP ' . $httpCode);
            return ['ok' => false, 'error' => $errorMsg, 'raw' => $decoded ?: $response];
        }

        $messageId = '';
        if (is_array($decoded)) {
            $messageId = (string)($decoded['messages'][0]['id'] ?? $decoded['message_id'] ?? $decoded['id'] ?? '');
        }

        return [
            'ok' => true,
            'message_id' => $messageId,
            'raw' => $decoded ?: $response
        ];
    }

    private function marcarEstadoCola($idCola, $estado) {
        $sql = "UPDATE whatsapp_cola_envio SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$estado, (int)$idCola]);
    }

    private function marcarResultadoCola($idCola, $estado, $providerMessageId = null, $error = null, $incrementarIntento = true) {
        $sql = "UPDATE whatsapp_cola_envio
                SET estado = ?,
                    proveedor_message_id = COALESCE(?, proveedor_message_id),
                    ultimo_error = ?,
                    procesado_en = NOW()";

        if ($incrementarIntento) {
            $sql .= ", intentos = intentos + 1";
        }

        $sql .= " WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $estado,
            $providerMessageId,
            $error,
            (int)$idCola
        ]);
    }

    private function registrarEvento($idCola, $providerMessageId, $evento, $detalle) {
        $sql = "INSERT INTO whatsapp_eventos (id_cola, proveedor_message_id, evento, detalle_json)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $idCola,
            $providerMessageId,
            $evento,
            json_encode($detalle, JSON_UNESCAPED_UNICODE)
        ]);
    }

    private function actualizarEstadoCampana($idCampana) {
        $sql = "SELECT
                    SUM(CASE WHEN estado IN ('pendiente','procesando') THEN 1 ELSE 0 END) AS pendientes,
                    COUNT(*) AS total
                FROM whatsapp_cola_envio
                WHERE id_campana = ?";
        $rows = $this->query($sql, [(int)$idCampana]);
        if (empty($rows)) {
            return;
        }

        $pendientes = (int)($rows[0]['pendientes'] ?? 0);
        $total = (int)($rows[0]['total'] ?? 0);

        if ($total === 0) {
            return;
        }

        $estado = $pendientes > 0 ? 'enviando' : 'finalizada';
        $sqlUpdate = "UPDATE whatsapp_campanas SET estado = ? WHERE id = ?";
        $stmt = $this->db->prepare($sqlUpdate);
        $stmt->execute([$estado, (int)$idCampana]);
    }

    private function extraerEstadoWebhook(array $payload) {
        if (!empty($payload['message_id']) && !empty($payload['status'])) {
            return [
                'provider_message_id' => $payload['message_id'],
                'evento' => $payload['status'],
                'error' => $payload['error'] ?? null
            ];
        }

        $status = $payload['entry'][0]['changes'][0]['value']['statuses'][0] ?? null;
        if (is_array($status)) {
            return [
                'provider_message_id' => $status['id'] ?? null,
                'evento' => $status['status'] ?? null,
                'error' => $status['errors'][0]['title'] ?? null
            ];
        }

        return [];
    }

    private function mapearEstadoColaDesdeEvento($evento) {
        $evento = strtolower(trim((string)$evento));
        if ($evento === 'delivered') {
            return 'entregado';
        }
        if ($evento === 'read') {
            return 'leido';
        }
        if ($evento === 'failed' || $evento === 'spam_report' || $evento === 'blocked') {
            return 'fallido';
        }
        return 'enviado';
    }
}
