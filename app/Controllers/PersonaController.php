<?php
/**
 * Controlador Persona
 */

require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Celula.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Models/Rol.php';
require_once APP . '/Models/Peticion.php';
require_once APP . '/Models/WhatsappLocalQueue.php';
require_once APP . '/Models/WhatsappMensajeTemplate.php';
require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Helpers/DataIsolation.php';

class PersonaController extends BaseController {
    private $personaModel;
    private $celulaModel;
    private $ministerioModel;
    private $rolModel;
    private $peticionModel;
    private $whatsappLocalQueueModel;
    private $whatsappMensajeTemplateModel;
    private $soportaProceso = false;
    private $soportaChecklistEscalera = false;
    private $soportaConvencion = false;
    private $soportaFechaAsignacionLider = false;

    public function __construct() {
        $this->personaModel = new Persona();
        $this->celulaModel = new Celula();
        $this->ministerioModel = new Ministerio();
        $this->rolModel = new Rol();
        $this->peticionModel = new Peticion();
        $this->whatsappLocalQueueModel = new WhatsappLocalQueue();
        $this->whatsappMensajeTemplateModel = new WhatsappMensajeTemplate();

        $this->personaModel->ensureProcesoColumnExists();
        $this->personaModel->ensureConvencionColumnExists();
        $this->personaModel->ensureEscaleraChecklistColumnExists();
        $this->personaModel->ensureFechaAsignacionLiderColumnExists();
        $this->soportaProceso = $this->personaModel->tieneColumna('Proceso');
        $this->soportaChecklistEscalera = $this->personaModel->tieneColumna('Escalera_Checklist');
        $this->soportaConvencion = $this->personaModel->tieneColumna('Convencion');
        $this->soportaFechaAsignacionLider = $this->personaModel->tieneColumna('Fecha_Asignacion_Lider');
    }

    /**
     * Construir URL pública absoluta (compatible con proxy)
     */
    private function buildAbsolutePublicUrl($route) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(PUBLIC_URL, '/');
        return $scheme . '://' . $host . $base . '/index.php?url=' . urlencode($route);
    }

    private function tieneModuloPermisoExplicito($modulo) {
        return isset($_SESSION['permisos'][$modulo]) && is_array($_SESSION['permisos'][$modulo]);
    }

    private function puedeVerPlantillasWhatsapp() {
        if (AuthController::esAdministrador()) {
            return true;
        }

        if ($this->tieneModuloPermisoExplicito('personas_plantillas_whatsapp')) {
            return AuthController::tienePermiso('personas_plantillas_whatsapp', 'ver');
        }

        return AuthController::tienePermiso('personas', 'editar');
    }

    private function puedeVerAtajoAsignados() {
        if (AuthController::esAdministrador()) {
            return true;
        }

        if ($this->tieneModuloPermisoExplicito('personas_ganar_asignados')) {
            return AuthController::tienePermiso('personas_ganar_asignados', 'ver');
        }

        return AuthController::tienePermiso('personas', 'editar');
    }

    private function puedeVerAtajoReasignados() {
        if (AuthController::esAdministrador()) {
            return true;
        }

        if ($this->tieneModuloPermisoExplicito('personas_ganar_reasignados')) {
            return AuthController::tienePermiso('personas_ganar_reasignados', 'ver');
        }

        return AuthController::tienePermiso('personas', 'editar');
    }

    private function obtenerDestinatariosMinisterio($idMinisterio, $idPersonaExcluir = 0) {
        $idMinisterio = (int)$idMinisterio;
        $idPersonaExcluir = (int)$idPersonaExcluir;
        if ($idMinisterio <= 0) {
            return [];
        }

        $sql = "SELECT DISTINCT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono, r.Nombre_Rol
                FROM persona p
                LEFT JOIN rol r ON r.Id_Rol = p.Id_Rol
                WHERE p.Id_Ministerio = ?
                  AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                  AND p.Telefono IS NOT NULL
                  AND TRIM(p.Telefono) <> ''
                  AND (
                        p.Id_Rol = 8
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%pastor%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider de 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lider 12%'
                        OR LOWER(COALESCE(r.Nombre_Rol, '')) LIKE '%lideres de 12%'
                  )";

        $params = [$idMinisterio];
        if ($idPersonaExcluir > 0) {
            $sql .= " AND p.Id_Persona <> ?";
            $params[] = $idPersonaExcluir;
        }

        return $this->personaModel->query($sql, $params);
    }

    private function encolarMensajeBienvenidaYAsignacion(array $personaNueva) {
        if (!$this->whatsappLocalQueueModel) {
            return;
        }

        $idPersona = (int)($personaNueva['Id_Persona'] ?? 0);
        $nombrePersona = trim((string)($personaNueva['Nombre'] ?? '') . ' ' . (string)($personaNueva['Apellido'] ?? ''));
        $telefonoPersona = (string)($personaNueva['Telefono'] ?? '');
        $nombreMinisterio = '';
        if (!empty($personaNueva['Nombre_Ministerio'])) {
            $nombreMinisterio = (string)$personaNueva['Nombre_Ministerio'];
        } elseif (!empty($personaNueva['Id_Ministerio'])) {
            $ministerio = $this->ministerioModel->getById((int)$personaNueva['Id_Ministerio']);
            $nombreMinisterio = (string)($ministerio['Nombre_Ministerio'] ?? '');
        }

        $urlPeticiones = $this->buildAbsolutePublicUrl('peticiones/crear');

        $varsBase = [
            'persona_nombre' => $nombrePersona,
            'persona_telefono' => $telefonoPersona,
            'persona_id' => $idPersona,
            'ministerio_nombre' => $nombreMinisterio,
            'url_peticiones' => $urlPeticiones
        ];

        if ($telefonoPersona !== '' && $idPersona > 0) {
            $payloadBienvenida = $this->whatsappMensajeTemplateModel->getTemplatePayload('bienvenida_persona', $varsBase);
            $this->whatsappLocalQueueModel->encolar(
                $telefonoPersona,
                (string)($payloadBienvenida['mensaje'] ?? ''),
                'bienvenida_persona',
                'persona:' . $idPersona,
                $payloadBienvenida['media_url'] ?? null,
                $payloadBienvenida['media_tipo'] ?? null
            );
        }

        $idLider = (int)($personaNueva['Id_Lider'] ?? 0);
        if ($idLider > 0) {
            $lider = $this->personaModel->getById($idLider);
            if (!empty($lider) && !empty($lider['Telefono'])) {
                $nombreLider = trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''));
                $payloadLider = $this->whatsappMensajeTemplateModel->getTemplatePayload('asignacion_lider', array_merge($varsBase, [
                    'lider_nombre' => $nombreLider,
                    'destinatario_nombre' => $nombreLider
                ]));
                $this->whatsappLocalQueueModel->encolar(
                    (string)$lider['Telefono'],
                    (string)($payloadLider['mensaje'] ?? ''),
                    'asignacion_lider',
                    'persona:' . $idPersona . ':lider:' . $idLider . ':alta',
                    $payloadLider['media_url'] ?? null,
                    $payloadLider['media_tipo'] ?? null
                );
            }
        }

        $idMinisterio = (int)($personaNueva['Id_Ministerio'] ?? 0);
        if ($idMinisterio > 0) {
            $destinatariosMinisterio = $this->obtenerDestinatariosMinisterio($idMinisterio, $idLider);
            foreach ($destinatariosMinisterio as $destinatario) {
                $idDestino = (int)($destinatario['Id_Persona'] ?? 0);
                $telefonoDestino = (string)($destinatario['Telefono'] ?? '');
                if ($telefonoDestino === '' || $idDestino <= 0) {
                    continue;
                }

                $nombreDestino = trim((string)($destinatario['Nombre'] ?? '') . ' ' . (string)($destinatario['Apellido'] ?? ''));
                $payloadMinisterio = $this->whatsappMensajeTemplateModel->getTemplatePayload('asignacion_ministerio', array_merge($varsBase, [
                    'destinatario_nombre' => $nombreDestino
                ]));

                $this->whatsappLocalQueueModel->encolar(
                    $telefonoDestino,
                    (string)($payloadMinisterio['mensaje'] ?? ''),
                    'asignacion_ministerio',
                    'persona:' . $idPersona . ':ministerio:' . $idMinisterio . ':alta:' . $idDestino,
                    $payloadMinisterio['media_url'] ?? null,
                    $payloadMinisterio['media_tipo'] ?? null
                );
            }
        }
    }

    private function encolarNotificacionCambiosAsignacion(array $personaAntes, array $personaDespues) {
        if (!$this->whatsappLocalQueueModel) {
            return;
        }

        $idPersona = (int)($personaDespues['Id_Persona'] ?? 0);
        if ($idPersona <= 0) {
            return;
        }

        $nombrePersona = trim((string)($personaDespues['Nombre'] ?? '') . ' ' . (string)($personaDespues['Apellido'] ?? ''));
        $telefonoPersona = (string)($personaDespues['Telefono'] ?? '');

        $nombreMinisterio = '';
        if (!empty($personaDespues['Nombre_Ministerio'])) {
            $nombreMinisterio = (string)$personaDespues['Nombre_Ministerio'];
        } elseif (!empty($personaDespues['Id_Ministerio'])) {
            $ministerio = $this->ministerioModel->getById((int)$personaDespues['Id_Ministerio']);
            $nombreMinisterio = (string)($ministerio['Nombre_Ministerio'] ?? '');
        }

        $varsBase = [
            'persona_nombre' => $nombrePersona,
            'persona_telefono' => $telefonoPersona,
            'persona_id' => $idPersona,
            'ministerio_nombre' => $nombreMinisterio
        ];

        $liderAntes = (int)($personaAntes['Id_Lider'] ?? 0);
        $liderDespues = (int)($personaDespues['Id_Lider'] ?? 0);
        if ($liderDespues > 0 && $liderDespues !== $liderAntes) {
            $lider = $this->personaModel->getById($liderDespues);
            if (!empty($lider) && !empty($lider['Telefono'])) {
                $nombreLider = trim((string)($lider['Nombre'] ?? '') . ' ' . (string)($lider['Apellido'] ?? ''));
                $payloadLider = $this->whatsappMensajeTemplateModel->getTemplatePayload('asignacion_lider', array_merge($varsBase, [
                    'lider_nombre' => $nombreLider,
                    'destinatario_nombre' => $nombreLider
                ]));
                $this->whatsappLocalQueueModel->encolar(
                    (string)$lider['Telefono'],
                    (string)($payloadLider['mensaje'] ?? ''),
                    'asignacion_lider',
                    'persona:' . $idPersona . ':lider:' . $liderDespues . ':reasignacion',
                    $payloadLider['media_url'] ?? null,
                    $payloadLider['media_tipo'] ?? null
                );
            }
        }

        $ministerioAntes = (int)($personaAntes['Id_Ministerio'] ?? 0);
        $ministerioDespues = (int)($personaDespues['Id_Ministerio'] ?? 0);
        if ($ministerioDespues > 0 && $ministerioDespues !== $ministerioAntes) {
            $destinatariosMinisterio = $this->obtenerDestinatariosMinisterio($ministerioDespues, $liderDespues);
            foreach ($destinatariosMinisterio as $destinatario) {
                $idDestino = (int)($destinatario['Id_Persona'] ?? 0);
                $telefonoDestino = (string)($destinatario['Telefono'] ?? '');
                if ($telefonoDestino === '' || $idDestino <= 0) {
                    continue;
                }

                $nombreDestino = trim((string)($destinatario['Nombre'] ?? '') . ' ' . (string)($destinatario['Apellido'] ?? ''));
                $payloadMinisterio = $this->whatsappMensajeTemplateModel->getTemplatePayload('asignacion_ministerio', array_merge($varsBase, [
                    'destinatario_nombre' => $nombreDestino
                ]));

                $this->whatsappLocalQueueModel->encolar(
                    $telefonoDestino,
                    (string)($payloadMinisterio['mensaje'] ?? ''),
                    'asignacion_ministerio',
                    'persona:' . $idPersona . ':ministerio:' . $ministerioDespues . ':reasignacion:' . $idDestino,
                    $payloadMinisterio['media_url'] ?? null,
                    $payloadMinisterio['media_tipo'] ?? null
                );
            }
        }

        // Detectar cambio de célula y enviar mensaje con Universidad de la Vida
        $celulaAntes = (int)($personaAntes['Id_Celula'] ?? 0);
        $celulaDespues = (int)($personaDespues['Id_Celula'] ?? 0);
        if ($celulaDespues > 0 && $celulaDespues !== $celulaAntes) {
            $this->encolarMensajeAsignacionCelulaConUniversidad($personaDespues);
        }
    }

    /**
     * Encolar mensaje de asignación a célula con información de Universidad de la Vida
     */
    private function encolarMensajeAsignacionCelulaConUniversidad(array $persona) {
        if (!$this->whatsappLocalQueueModel) {
            return;
        }

        $idPersona = (int)($persona['Id_Persona'] ?? 0);
        $telefonoPersona = (string)($persona['Telefono'] ?? '');
        $idCelula = (int)($persona['Id_Celula'] ?? 0);

        if ($idPersona <= 0 || $telefonoPersona === '' || $idCelula <= 0) {
            return;
        }

        // Obtener próximo contenido de Universidad de la Vida
        require_once APP . '/Models/EventoModulo.php';
        $eventoModuloModel = new EventoModulo();
        $itemsUniversidad = $eventoModuloModel->getByModuloPublico('universidad_vida', date('Y-m-d'));

        $nombrePersona = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
        $nombreCelula = '';
        if (!empty($persona['Nombre_Celula'])) {
            $nombreCelula = (string)$persona['Nombre_Celula'];
        } elseif ($idCelula > 0) {
            $celula = $this->celulaModel->getById($idCelula);
            $nombreCelula = (string)($celula['Nombre_Celula'] ?? '');
        }

        // URL pública de Universidad de la Vida
        $urlUniversidad = $this->buildAbsolutePublicUrl('eventos/universidad-vida/publico');

        $varsBase = [
            'persona_nombre' => $nombrePersona,
            'celula_nombre' => $nombreCelula,
            'persona_id' => $idPersona,
            'celula_id' => $idCelula,
            'url_universidad_vida' => $urlUniversidad
        ];

        // Obtener información de Universidad de la Vida para incluir en el mensaje
        $infoUniversidad = '';
        if (!empty($itemsUniversidad)) {
            $primerItem = $itemsUniversidad[0];
            $infoUniversidad = "📚 Universidad de la Vida: " . (string)($primerItem['Titulo'] ?? '');
            if (!empty($primerItem['Parrafo'])) {
                $parrafo = trim((string)$primerItem['Parrafo']);
                if (strlen($parrafo) > 200) {
                    $parrafo = substr($parrafo, 0, 197) . '...';
                }
                $infoUniversidad .= "\n\n" . $parrafo;
            }
        }

        $varsBase['universidad_vida_info'] = $infoUniversidad;

        // Obtener plantilla y encolar
        $payload = $this->whatsappMensajeTemplateModel->getTemplatePayload('asignacion_celula_universidad', $varsBase);
        if (!empty($payload['mensaje'])) {
            $this->whatsappLocalQueueModel->encolar(
                $telefonoPersona,
                (string)($payload['mensaje'] ?? ''),
                'asignacion_celula_universidad',
                'persona:' . $idPersona . ':celula:' . $idCelula . ':asignacion',
                $payload['media_url'] ?? null,
                $payload['media_tipo'] ?? null
            );
        }
    }

    private function normalizarProceso($proceso) {
        $proceso = trim((string)$proceso);
        $procesosPermitidos = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        return in_array($proceso, $procesosPermitidos, true) ? $proceso : null;
    }

    private function normalizarConvencion($convencion) {
        $convencion = trim((string)$convencion);
        $permitidas = [
            'Convencion Enero',
            'Convencion Mujeres',
            'Convencion Jovenes',
            'Convencion Hombres'
        ];

        if (in_array($convencion, $permitidas, true)) {
            return $convencion;
        }

        return null;
    }

    private function normalizarEtapaFiltro($etapa) {
        return $this->normalizarProceso($etapa);
    }

    private function normalizarOrigenFiltro($origen) {
        $origen = strtolower(trim((string)$origen));
        return in_array($origen, ['domingo', 'celula', 'asignados', 'reasignados', 'no_disponible'], true) ? $origen : '';
    }

    private function personaTieneAsignacionCompleta(array $persona) {
        return !empty($persona['Id_Lider']) && !empty($persona['Id_Ministerio']) && !empty($persona['Id_Celula']);
    }

    private function resolverFechaAsignacionLider(array $data, $personaAntes = null) {
        $idLiderNuevo = (int)($data['Id_Lider'] ?? 0);
        $idMinisterioNuevo = (int)($data['Id_Ministerio'] ?? 0);

        // El temporizador de primer contacto corre cuando ya existe asignación
        // completa de ministerio + líder.
        if ($idLiderNuevo <= 0 || $idMinisterioNuevo <= 0) {
            return null;
        }

        if (!is_array($personaAntes) || empty($personaAntes)) {
            return date('Y-m-d H:i:s');
        }

        $idLiderAnterior = (int)($personaAntes['Id_Lider'] ?? 0);
        $idMinisterioAnterior = (int)($personaAntes['Id_Ministerio'] ?? 0);
        $fechaAnterior = trim((string)($personaAntes['Fecha_Asignacion_Lider'] ?? ''));

        // Si no estaba asignado antes, inicia el conteo ahora.
        if ($idLiderAnterior <= 0 || $idMinisterioAnterior <= 0) {
            return date('Y-m-d H:i:s');
        }

        // Si cambia líder o ministerio, reinicia ventana de 48 horas.
        if ($idLiderAnterior !== $idLiderNuevo || $idMinisterioAnterior !== $idMinisterioNuevo) {
            return date('Y-m-d H:i:s');
        }

        // Si no cambió asignación, conserva la fecha previa.
        if ($fechaAnterior !== '') {
            return $fechaAnterior;
        }

        return date('Y-m-d H:i:s');
    }

    private function enriquecerChecklistPersona(array $persona) {
        $raw = (string)($persona['Escalera_Checklist'] ?? '');
        $decoded = [];

        if ($raw !== '') {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) {
                $decoded = $tmp;
            }
        }

        $checklist = $this->normalizarChecklistEscalera($decoded);
        // Regla de negocio: "Asignado a líder" se calcula automáticamente.
        $checklist['Ganar'][0] = $this->personaTieneAsignacionCompleta($persona);

        $persona['Escalera_Checklist'] = json_encode($checklist, JSON_UNESCAPED_UNICODE);
        $persona['Seguimiento_No_Disponible'] = !empty($checklist['Ganar'][3]);
        $persona['Seguimiento_Observacion'] = (string)($checklist['_meta']['no_disponible_observacion'] ?? '');
        $persona['Seguimiento_Reasignado'] = !empty($checklist['_meta']['reasignado_automatico']);
        $persona['Seguimiento_Reasignado_At'] = (string)($checklist['_meta']['reasignado_automatico_at'] ?? '');

        return $persona;
    }

    private function aplicarReglaReasignacionPrimerContacto($horasLimite = 48) {
        if (!$this->soportaChecklistEscalera) {
            return;
        }

        $candidatos = $this->personaModel->getCandidatosReasignacionPrimerContacto($horasLimite);
        if (empty($candidatos)) {
            return;
        }

        foreach ($candidatos as $persona) {
            $idPersona = (int)($persona['Id_Persona'] ?? 0);
            if ($idPersona <= 0) {
                continue;
            }

            $checklistRaw = (string)($persona['Escalera_Checklist'] ?? '');
            $checklistActual = [];
            if ($checklistRaw !== '') {
                $tmp = json_decode($checklistRaw, true);
                if (is_array($tmp)) {
                    $checklistActual = $tmp;
                }
            }

            $checklist = $this->normalizarChecklistEscalera($checklistActual);

            // Si ya hubo primer contacto, no se reasigna.
            if (!empty($checklist['Ganar'][1])) {
                continue;
            }

            // No mover casos cerrados como "No se dispone".
            if (!empty($checklist['Ganar'][3])) {
                continue;
            }

            $checklist['Ganar'][0] = false;
            $checklist['_meta']['reasignado_automatico'] = true;
            $checklist['_meta']['reasignado_automatico_at'] = date('Y-m-d H:i:s');
            $checklist['_meta']['reasignado_automatico_motivo'] = 'Sin primer contacto en 48 horas';

            $checklistJson = json_encode($checklist, JSON_UNESCAPED_UNICODE);
            if ($checklistJson === false) {
                continue;
            }

            $this->personaModel->aplicarReasignacionAutomatica($idPersona, $checklistJson, 'Ganar');
        }
    }

    private function enriquecerChecklistPersonas(array $personas) {
        return array_map(function($persona) {
            return $this->enriquecerChecklistPersona($persona);
        }, $personas);
    }

    private function normalizarPerfilListado($perfil) {
        $perfil = strtolower(trim((string)$perfil));
        return in_array($perfil, ['', 'lideres_12', 'lideres_celula', 'asistentes', 'otros', 'pastores', 'sin_rol'], true) ? $perfil : '';
    }

    private function textoSinAcentos($texto) {
        $texto = strtolower(trim((string)$texto));
        return strtr($texto, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);
    }

    private function esLider12(array $persona) {
        if ((int)($persona['Id_Rol'] ?? 0) === 8) {
            return true;
        }

        $rol = $this->textoSinAcentos((string)($persona['Nombre_Rol'] ?? ''));
        return strpos($rol, 'lider de 12') !== false
            || strpos($rol, 'lider 12') !== false
            || strpos($rol, 'lideres de 12') !== false;
    }

    private function filtrarPersonasPorPerfilListado(array $personas, $perfil) {
        $perfil = $this->normalizarPerfilListado($perfil);
        if ($perfil === '') {
            return $personas;
        }

        return array_values(array_filter($personas, function($persona) use ($perfil) {
            $rol = $this->textoSinAcentos((string)($persona['Nombre_Rol'] ?? ''));

            if ($perfil === 'asistentes') {
                return strpos($rol, 'asistente') !== false;
            }

            if ($perfil === 'lideres_celula') {
                return strpos($rol, 'lider de celula') !== false;
            }

            if ($perfil === 'lideres_12') {
                return $this->esLider12($persona);
            }

            if ($perfil === 'pastores') {
                return strpos($rol, 'pastor') !== false;
            }

            if ($perfil === 'sin_rol') {
                return $rol === '' || $rol === 'sin rol';
            }

            if ($perfil === 'otros') {
                return !$this->esLider12($persona)
                    && strpos($rol, 'lider de celula') === false
                    && strpos($rol, 'asistente') === false;
            }

            return true;
        }));
    }

    private function filtrarPersonasPorNombreListado(array $personas, $termino) {
        $termino = trim((string)$termino);
        if ($termino === '') {
            return $personas;
        }

        $terminoNormalizado = $this->textoSinAcentos($termino);

        return array_values(array_filter($personas, function($persona) use ($terminoNormalizado) {
            $nombre = trim((string)($persona['Nombre'] ?? ''));
            $apellido = trim((string)($persona['Apellido'] ?? ''));
            $nombreCompleto = trim($nombre . ' ' . $apellido);

            $nombreNormalizado = $this->textoSinAcentos($nombre);
            $apellidoNormalizado = $this->textoSinAcentos($apellido);
            $nombreCompletoNormalizado = $this->textoSinAcentos($nombreCompleto);

            return $nombreNormalizado === $terminoNormalizado
                || $apellidoNormalizado === $terminoNormalizado
                || $nombreCompletoNormalizado === $terminoNormalizado;
        }));
    }

    private function filtrarPersonasPorMinisterioListado(array $personas, $idMinisterio) {
        $idMinisterio = trim((string)$idMinisterio);
        if ($idMinisterio === '') {
            return $personas;
        }

        if ($idMinisterio === '0') {
            return array_values(array_filter($personas, static function($persona) {
                return empty($persona['Id_Ministerio']);
            }));
        }

        if (!ctype_digit($idMinisterio)) {
            return $personas;
        }

        $idMinisterioInt = (int)$idMinisterio;

        return array_values(array_filter($personas, static function($persona) use ($idMinisterioInt) {
            return (int)($persona['Id_Ministerio'] ?? 0) === $idMinisterioInt;
        }));
    }

    private function obtenerSugerenciasNombreListado(array $personas, $limite = 200) {
        $sugerencias = [];
        $vistos = [];

        foreach ($personas as $persona) {
            $nombreCompleto = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
            if ($nombreCompleto === '') {
                continue;
            }

            $clave = $this->textoSinAcentos($nombreCompleto);
            if ($clave === '' || isset($vistos[$clave])) {
                continue;
            }

            $vistos[$clave] = true;
            $sugerencias[] = $nombreCompleto;
        }

        natcasesort($sugerencias);
        if ($limite > 0 && count($sugerencias) > $limite) {
            $sugerencias = array_slice(array_values($sugerencias), 0, $limite);
        } else {
            $sugerencias = array_values($sugerencias);
        }

        return $sugerencias;
    }

    private function contarPerfilesListado(array $personas) {
        $totales = [
            'lideres_12' => 0,
            'lideres_celula' => 0,
            'asistentes' => 0,
            'otros' => 0
        ];

        foreach ($personas as $persona) {
            $rol = $this->textoSinAcentos((string)($persona['Nombre_Rol'] ?? ''));

            if ($this->esLider12($persona)) {
                $totales['lideres_12']++;
                continue;
            }

            if (strpos($rol, 'lider de celula') !== false) {
                $totales['lideres_celula']++;
                continue;
            }

            if (strpos($rol, 'asistente') !== false) {
                $totales['asistentes']++;
                continue;
            }

            $totales['otros']++;
        }

        return $totales;
    }

    private function obtenerCategoriaOrigenPendiente(array $persona) {
        if (!empty($persona['Seguimiento_Reasignado'])) {
            return 'reasignados';
        }

        if (!empty($persona['Seguimiento_No_Disponible'])) {
            return 'no_disponible';
        }

        $tipoReunion = $this->textoSinAcentos((string)($persona['Tipo_Reunion'] ?? ''));
        $invitadoPor = trim((string)($persona['Invitado_Por'] ?? ''));

        if (strpos($tipoReunion, 'celula') !== false) {
            return 'celula';
        }

        if (strpos($tipoReunion, 'domingo') !== false) {
            return $invitadoPor !== '' ? 'domingo' : 'asignados';
        }

        return 'otros';
    }

    private function contarOrigenesPendientes(array $personas) {
        $totales = [
            'celula' => 0,
            'domingo' => 0,
            'asignados' => 0,
            'reasignados' => 0,
            'no_disponible' => 0,
            'otros' => 0
        ];

        foreach ($personas as $persona) {
            $categoria = $this->obtenerCategoriaOrigenPendiente($persona);
            if (!array_key_exists($categoria, $totales)) {
                $categoria = 'otros';
            }

            $totales[$categoria]++;
        }

        return $totales;
    }

    private function contarEtapasEscalera(array $personas) {
        $totales = [
            'Ganar' => 0,
            'Consolidar' => 0,
            'Discipular' => 0,
            'Enviar' => 0,
            'sin_etapa' => 0
        ];

        foreach ($personas as $persona) {
            $etapa = trim((string)($persona['Proceso'] ?? ''));
            if ($etapa === '' || !array_key_exists($etapa, $totales)) {
                $totales['sin_etapa']++;
                continue;
            }

            $totales[$etapa]++;
        }

        return $totales;
    }

    private function obtenerEtiquetaOrigenGanar(array $persona) {
        $tipoReunionRaw = trim((string)($persona['Tipo_Reunion'] ?? ''));
        $tipoReunion = strtolower($tipoReunionRaw);
        $tipoReunion = strtr($tipoReunion, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        if (strpos($tipoReunion, 'celula') !== false) {
            return 'Célula';
        }

        if ($tipoReunion === '' || strpos($tipoReunion, 'domingo') !== false) {
            return 'Domingo';
        }

        return $tipoReunionRaw;
    }

    private function getContextoFiltrosVisibles() {
        if (AuthController::esAdministrador() || DataIsolation::tieneAccesoTotal()) {
            return [
                'restringido' => false,
                'ministerios' => $this->ministerioModel->getAll(),
                'lideres' => $this->personaModel->getLideresYPastores(),
                'ministerioIdsPermitidos' => null,
                'liderIdsPermitidos' => null
            ];
        }

        $ministerios = [];
        $lideres = [];
        $ministerioIdsPermitidos = [];
        $liderIdsPermitidos = [];

        $idMinisterio = DataIsolation::getUsuarioMinisterioId();
        if ($idMinisterio) {
            $ministerio = $this->ministerioModel->getById($idMinisterio);
            if (!empty($ministerio)) {
                $ministerios[] = $ministerio;
                $ministerioIdsPermitidos[] = (int)$ministerio['Id_Ministerio'];
            }

            $lideres = $this->personaModel->getLideresByMinisterio($idMinisterio);
            foreach ($lideres as $lider) {
                $liderIdsPermitidos[] = (int)$lider['Id_Persona'];
            }
        }

        if (DataIsolation::esLiderCelula()) {
            $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
            if ($usuarioId > 0) {
                $yaExiste = false;
                foreach ($lideres as $liderExistente) {
                    if ((int)$liderExistente['Id_Persona'] === $usuarioId) {
                        $yaExiste = true;
                        break;
                    }
                }

                if (!$yaExiste) {
                    $personaUsuario = $this->personaModel->getById($usuarioId);
                    if (!empty($personaUsuario)) {
                        $lideres[] = $personaUsuario;
                    }
                }

                if (!in_array($usuarioId, $liderIdsPermitidos, true)) {
                    $liderIdsPermitidos[] = $usuarioId;
                }
            }
        }

        return [
            'restringido' => true,
            'ministerios' => $ministerios,
            'lideres' => $lideres,
            'ministerioIdsPermitidos' => $ministerioIdsPermitidos,
            'liderIdsPermitidos' => $liderIdsPermitidos
        ];
    }

    private function limpiarFiltrosNoPermitidos($filtroMinisterio, $filtroLider, array $contexto) {
        $filtroMinisterio = $filtroMinisterio !== null ? (string)$filtroMinisterio : '';
        $filtroLider = $filtroLider !== null ? (string)$filtroLider : '';

        if (!$contexto['restringido']) {
            return [$filtroMinisterio, $filtroLider];
        }

        if ($filtroMinisterio === '0') {
            $filtroMinisterio = '';
        }
        if ($filtroLider === '0') {
            $filtroLider = '';
        }

        $ministerioIdsPermitidos = $contexto['ministerioIdsPermitidos'] ?? [];
        $liderIdsPermitidos = $contexto['liderIdsPermitidos'] ?? [];

        if ($filtroMinisterio !== '' && !in_array((int)$filtroMinisterio, $ministerioIdsPermitidos, true)) {
            $filtroMinisterio = '';
        }

        if ($filtroLider !== '' && !in_array((int)$filtroLider, $liderIdsPermitidos, true)) {
            $filtroLider = '';
        }

        return [$filtroMinisterio, $filtroLider];
    }

    private function esRolAsistente($idRol) {
        $idRol = (int)$idRol;
        if ($idRol <= 0) {
            return false;
        }

        $rol = $this->rolModel->getById($idRol);
        if (empty($rol['Nombre_Rol'])) {
            return false;
        }

        $nombreRol = strtolower(trim((string)$rol['Nombre_Rol']));
        $nombreRol = strtr($nombreRol, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n'
        ]);

        return strpos($nombreRol, 'asistente') !== false;
    }

    /**
     * Resolver anclaje automático cuando la creación viene desde asistencias.
     */
    private function resolverAnclajeDesdeAsistencia($celulaRetorno, array $data) {
        $idCelulaOrigen = (int)($celulaRetorno ?? 0);
        if ($idCelulaOrigen <= 0) {
            return $data;
        }

        $celula = $this->celulaModel->getById($idCelulaOrigen);
        if (empty($celula)) {
            return $data;
        }

        $idLider = !empty($celula['Id_Lider']) ? (int)$celula['Id_Lider'] : null;
        $idMinisterio = null;

        if (!empty($idLider)) {
            $lider = $this->personaModel->getById($idLider);
            if (!empty($lider['Id_Ministerio'])) {
                $idMinisterio = (int)$lider['Id_Ministerio'];
            }
        }

        $data['Id_Celula'] = $idCelulaOrigen;
        $data['Id_Lider'] = $idLider;
        $data['Id_Ministerio'] = $idMinisterio;
        // Si viene desde asistencias, se considera ganado en célula.
        if (empty($data['Tipo_Reunion'])) {
            $data['Tipo_Reunion'] = 'Celula';
        }

        return $data;
    }

    private function normalizarIdMinisterioPost($idMinisterioRaw) {
        $idMinisterioRaw = trim((string)$idMinisterioRaw);
        if ($idMinisterioRaw === '' || strtolower($idMinisterioRaw) === 'otro') {
            return null;
        }

        return ctype_digit($idMinisterioRaw) ? (int)$idMinisterioRaw : null;
    }

    private function registrarPeticionSiAplica($idPersona, $peticionTexto) {
        $idPersona = (int)$idPersona;
        $peticionTexto = trim((string)$peticionTexto);

        if ($idPersona <= 0 || $peticionTexto === '') {
            return;
        }

        $fechaHoy = date('Y-m-d');
        $existe = $this->peticionModel->query(
            "SELECT Id_Peticion FROM peticion WHERE Id_Persona = ? AND Descripcion_Peticion = ? AND Fecha_Peticion = ? LIMIT 1",
            [$idPersona, $peticionTexto, $fechaHoy]
        );

        if (!empty($existe)) {
            return;
        }

        $this->peticionModel->create([
            'Id_Persona' => $idPersona,
            'Descripcion_Peticion' => $peticionTexto,
            'Fecha_Peticion' => $fechaHoy,
            'Estado_Peticion' => 'Pendiente'
        ]);
    }

    public function index() {
        if (!AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $filtroPerfil = $this->normalizarPerfilListado($_GET['perfil'] ?? '');
        $filtroMinisterio = isset($_GET['ministerio']) ? trim((string)$_GET['ministerio']) : '';
        $filtroNombre = trim((string)($_GET['buscar'] ?? ''));
        if ($filtroMinisterio !== '' && $filtroMinisterio !== '0' && !ctype_digit($filtroMinisterio)) {
            $filtroMinisterio = '';
        }

        // Aplicar aislamiento por rol del usuario conectado.
        $filtroRol = DataIsolation::generarFiltroPersonas();
        $contextoFiltros = $this->getContextoFiltrosVisibles();
        $ministerios = $contextoFiltros['ministerios'] ?? [];

        if ($contextoFiltros['restringido']) {
            $ministerioIdsPermitidos = $contextoFiltros['ministerioIdsPermitidos'] ?? [];
            if ($filtroMinisterio !== '' && $filtroMinisterio !== '0' && !in_array((int)$filtroMinisterio, $ministerioIdsPermitidos, true)) {
                $filtroMinisterio = '';
            }
        }

        // Base general: todas las personas visibles (incluye asignadas y pendientes).
        $personasBase = $this->personaModel->getAllWithRole($filtroRol, null);
        $personasBaseFiltradasPorMinisterio = $this->filtrarPersonasPorMinisterioListado($personasBase, $filtroMinisterio);
        // El resumen por rol respeta la búsqueda por nombre actual.
        $personasBaseFiltradasPorNombre = $this->filtrarPersonasPorNombreListado($personasBaseFiltradasPorMinisterio, $filtroNombre);
        $totalesPerfil = $this->contarPerfilesListado($personasBaseFiltradasPorNombre);
        $personas = $this->filtrarPersonasPorPerfilListado($personasBaseFiltradasPorNombre, $filtroPerfil);
        $sugerenciasNombre = $this->obtenerSugerenciasNombreListado($personasBaseFiltradasPorMinisterio);

        $this->view('personas/lista', [
            'personas' => $personas,
            'ministerios' => $ministerios,
            'filtroPerfilActual' => $filtroPerfil,
            'filtroMinisterioActual' => $filtroMinisterio,
            'filtroNombreActual' => $filtroNombre,
            'totalesPerfil' => $totalesPerfil,
            'sugerenciasNombre' => $sugerenciasNombre
        ]);
    }

    public function plantillasWhatsapp() {
        if (!$this->puedeVerPlantillasWhatsapp()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->view('personas/plantillas_whatsapp', [
            'plantillasWhatsapp' => $this->whatsappMensajeTemplateModel->getPlantillas(),
            'variablesPlantillasWhatsapp' => $this->whatsappMensajeTemplateModel->getVariablesDisponibles(),
            'plantillasGuardadas' => (($_GET['tpl_msg'] ?? '') === 'ok')
        ]);
    }

    private function subirMediaPlantilla($claveTemplate, $campoArchivo, $mediaActualUrl = null) {
        if (empty($_FILES[$campoArchivo]) || ($_FILES[$campoArchivo]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            if (!empty($_POST['quitar_' . $campoArchivo])) {
                return ['media_url' => null, 'media_tipo' => null];
            }

            return ['media_url' => $mediaActualUrl, 'media_tipo' => null];
        }

        $error = (int)($_FILES[$campoArchivo]['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return ['media_url' => $mediaActualUrl, 'media_tipo' => null];
        }

        $tmp = (string)$_FILES[$campoArchivo]['tmp_name'];
        $nombreOriginal = (string)$_FILES[$campoArchivo]['name'];
        $mime = '';
        if (function_exists('mime_content_type')) {
            $mime = (string)@mime_content_type($tmp);
        }

        $tipo = null;
        $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extPermitidasImg = ['jpg', 'jpeg', 'png', 'webp'];
        $extPermitidasVid = ['mp4', 'webm', 'mov', 'm4v'];

        if (strpos($mime, 'image/') === 0 || in_array($ext, $extPermitidasImg, true)) {
            $tipo = 'image';
            if (!in_array($ext, $extPermitidasImg, true)) {
                $ext = 'jpg';
            }
        } elseif (strpos($mime, 'video/') === 0 || in_array($ext, $extPermitidasVid, true)) {
            $tipo = 'video';
            if (!in_array($ext, $extPermitidasVid, true)) {
                $ext = 'mp4';
            }
        } else {
            return ['media_url' => $mediaActualUrl, 'media_tipo' => null];
        }

        $destDir = ROOT . '/public/uploads/whatsapp_templates';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $fileName = $claveTemplate . '_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
        $destPath = $destDir . '/' . $fileName;

        if (!@move_uploaded_file($tmp, $destPath)) {
            return ['media_url' => $mediaActualUrl, 'media_tipo' => null];
        }

        $basePublic = rtrim(PUBLIC_URL, '/');
        if (strpos($basePublic, 'http') !== 0) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $basePublic = $scheme . '://' . $host . $basePublic;
        }

        $mediaUrl = $basePublic . '/uploads/whatsapp_templates/' . rawurlencode($fileName);
        return ['media_url' => $mediaUrl, 'media_tipo' => $tipo];
    }

    public function guardarPlantillasWhatsapp() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('personas/plantillas-whatsapp');
        }

        if (!$this->puedeVerPlantillasWhatsapp()) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $this->whatsappMensajeTemplateModel->actualizarPlantilla('bienvenida_persona', $_POST['tpl_bienvenida_persona'] ?? '');
        $this->whatsappMensajeTemplateModel->actualizarPlantilla('asignacion_lider', $_POST['tpl_asignacion_lider'] ?? '');
        $this->whatsappMensajeTemplateModel->actualizarPlantilla('asignacion_ministerio', $_POST['tpl_asignacion_ministerio'] ?? '');
        $this->whatsappMensajeTemplateModel->actualizarPlantilla('felicitacion_cumpleanos', $_POST['tpl_felicitacion_cumpleanos'] ?? '');
        $this->whatsappMensajeTemplateModel->actualizarPlantilla('asignacion_celula_universidad', $_POST['tpl_asignacion_celula_universidad'] ?? '');

        $plantillas = $this->whatsappMensajeTemplateModel->getPlantillas();
        $bienvenidaMedia = $this->subirMediaPlantilla('bienvenida_persona', 'media_bienvenida_persona', $plantillas['bienvenida_persona']['media_url'] ?? null);
        $liderMedia = $this->subirMediaPlantilla('asignacion_lider', 'media_asignacion_lider', $plantillas['asignacion_lider']['media_url'] ?? null);
        $ministerioMedia = $this->subirMediaPlantilla('asignacion_ministerio', 'media_asignacion_ministerio', $plantillas['asignacion_ministerio']['media_url'] ?? null);
        $cumpleanosMedia = $this->subirMediaPlantilla('felicitacion_cumpleanos', 'media_felicitacion_cumpleanos', $plantillas['felicitacion_cumpleanos']['media_url'] ?? null);
        $celulaUniversidadMedia = $this->subirMediaPlantilla('asignacion_celula_universidad', 'media_asignacion_celula_universidad', $plantillas['asignacion_celula_universidad']['media_url'] ?? null);

        $this->whatsappMensajeTemplateModel->actualizarMedia('bienvenida_persona', $bienvenidaMedia['media_url'], $bienvenidaMedia['media_tipo']);
        $this->whatsappMensajeTemplateModel->actualizarMedia('asignacion_lider', $liderMedia['media_url'], $liderMedia['media_tipo']);
        $this->whatsappMensajeTemplateModel->actualizarMedia('asignacion_ministerio', $ministerioMedia['media_url'], $ministerioMedia['media_tipo']);
        $this->whatsappMensajeTemplateModel->actualizarMedia('felicitacion_cumpleanos', $cumpleanosMedia['media_url'], $cumpleanosMedia['media_tipo']);
        $this->whatsappMensajeTemplateModel->actualizarMedia('asignacion_celula_universidad', $celulaUniversidadMedia['media_url'], $celulaUniversidadMedia['media_tipo']);

        $this->redirect('personas/plantillas-whatsapp&tpl_msg=ok');
    }

    public function ganar() {
        if (!AuthController::tienePermiso('personas', 'ver')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $soloGanar = true;
        $filtroMinisterio = $_GET['ministerio'] ?? null;
        $filtroLider = $_GET['lider'] ?? null;
        $filtroEstado = $_GET['estado'] ?? null;
        $filtroCelula = $_GET['celula'] ?? null;
        $filtroProceso = (string)($_GET['proceso'] ?? '');
        $filtroSinLider = (string)($_GET['sin_lider'] ?? '') === '1';
        $filtroSinCelula = (string)($_GET['sin_celula'] ?? '') === '1';
        $filtroEtapa = $this->normalizarEtapaFiltro($_GET['etapa'] ?? null);
        $filtroOrigen = $this->normalizarOrigenFiltro($_GET['origen'] ?? null);
        $puedeVerAtajoAsignados = $this->puedeVerAtajoAsignados();
        $puedeVerAtajoReasignados = $this->puedeVerAtajoReasignados();

        if ($filtroOrigen === 'asignados' && !$puedeVerAtajoAsignados) {
            $filtroOrigen = '';
        }
        if ($filtroOrigen === 'reasignados' && !$puedeVerAtajoReasignados) {
            $filtroOrigen = '';
        }

        // Regla de negocio: si en 48 horas no hay primer contacto, liberar líder/ministerio.
        // Seguridad: solo aplicar cuando existe soporte de fecha de asignación.
        if ($this->soportaFechaAsignacionLider) {
            $this->aplicarReglaReasignacionPrimerContacto(48);
        }

        // Pendientes por consolidar: mostrar siempre personas nuevas en etapa Ganar.
        if ($filtroEtapa === null || $filtroEtapa === '') {
            $filtroEtapa = 'Ganar';
        }

        if (!in_array($filtroProceso, ['', 'sin_ministerio', 'sin_lider', 'sin_celula'], true)) {
            $filtroProceso = '';
        }

        if ($filtroProceso === 'sin_ministerio') {
            $filtroMinisterio = '0';
        } elseif ($filtroProceso === 'sin_lider') {
            $filtroSinLider = true;
        } elseif ($filtroProceso === 'sin_celula') {
            $filtroSinCelula = true;
        }

        if ($filtroSinLider) {
            $filtroLider = '0';
        }

        if ($filtroSinCelula) {
            $filtroCelula = '0';
        }

        $contextoFiltros = $this->getContextoFiltrosVisibles();
        [$filtroMinisterio, $filtroLider] = $this->limpiarFiltrosNoPermitidos($filtroMinisterio, $filtroLider, $contextoFiltros);

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $celulasDisponiblesRaw = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $celulas = array_map(static function($celula) {
            return [
                'Id_Celula' => (int)($celula['Id_Celula'] ?? 0),
                'Nombre_Celula' => (string)($celula['Nombre_Celula'] ?? ''),
                'Id_Lider' => (int)($celula['Id_Lider'] ?? 0),
                'Id_Ministerio' => (int)($celula['Id_Ministerio_Lider'] ?? 0)
            ];
        }, $celulasDisponiblesRaw);

        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulas);

        $filtroCelula = $filtroCelula !== null ? (string)$filtroCelula : '';
        if ($filtroCelula !== '' && $filtroCelula !== '0' && !in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) {
            $filtroCelula = '';
        }

        $filtroRol = $soloGanar
            ? DataIsolation::generarFiltroPersonasPendienteConsolidar()
            : DataIsolation::generarFiltroPersonas();

        $hayFiltrosSinOrigen = ($filtroMinisterio !== null && $filtroMinisterio !== '')
            || ($filtroLider !== null && $filtroLider !== '')
            || ($filtroEstado !== null && $filtroEstado !== '')
            || ($filtroCelula !== null && $filtroCelula !== '')
            || ($filtroEtapa !== null && $filtroEtapa !== '');

        // Totales rápidos por origen sin limitar por el botón seleccionado.
        if ($hayFiltrosSinOrigen) {
            $personasBaseConteo = $this->personaModel->getWithFiltersAndRole($filtroRol, $filtroMinisterio, $filtroLider, null, $filtroEstado, $filtroCelula, $filtroEtapa, '');
        } else {
            $personasBaseConteo = $this->personaModel->getAllWithRole($filtroRol, null, $filtroEstado, $filtroCelula, $filtroEtapa, '');
        }
        $personasBaseConteo = $this->enriquecerChecklistPersonas($personasBaseConteo);
        $totalesOrigenPendiente = $this->contarOrigenesPendientes($personasBaseConteo);

        if ($hayFiltrosSinOrigen || ($filtroOrigen !== '')) {
            $personas = $this->personaModel->getWithFiltersAndRole($filtroRol, $filtroMinisterio, $filtroLider, null, $filtroEstado, $filtroCelula, $filtroEtapa, $filtroOrigen);
        } else {
            $personas = $this->personaModel->getAllWithRole($filtroRol, null, $filtroEstado, $filtroCelula, $filtroEtapa, $filtroOrigen);
        }

        $personas = $this->enriquecerChecklistPersonas($personas);

        if ($filtroOrigen === 'no_disponible') {
            $personas = array_values(array_filter($personas, static function($persona) {
                return !empty($persona['Seguimiento_No_Disponible']);
            }));
        } elseif ($filtroOrigen === 'reasignados') {
            $personas = array_values(array_filter($personas, static function($persona) {
                return empty($persona['Seguimiento_No_Disponible']) && !empty($persona['Seguimiento_Reasignado']);
            }));
        } else {
            $personas = array_values(array_filter($personas, static function($persona) {
                return empty($persona['Seguimiento_No_Disponible']);
            }));

            if ($filtroOrigen !== '') {
                $personas = array_values(array_filter($personas, static function($persona) {
                    return empty($persona['Seguimiento_Reasignado']);
                }));
            }
        }

        $ministerios = $contextoFiltros['ministerios'];
        $lideres = $contextoFiltros['lideres'];

        $this->view('personas/ganar', [
            'personas' => $personas,
            'ministerios' => $ministerios,
            'lideres' => $lideres,
            'celulas' => $celulas,
            'filtroRestringido' => $contextoFiltros['restringido'],
            'filtroMinisterioActual' => (string)$filtroMinisterio,
            'filtroLiderActual' => (string)$filtroLider,
            'filtroEstadoActual' => (string)$filtroEstado,
            'filtroCelulaActual' => (string)$filtroCelula,
            'filtroProcesoActual' => $filtroProceso,
            'filtroSinLiderActual' => $filtroSinLider,
            'filtroSinCelulaActual' => $filtroSinCelula,
            'filtroEtapaActual' => (string)($filtroEtapa ?? ''),
            'filtroOrigenActual' => (string)$filtroOrigen,
            'totalesOrigenPendiente' => $totalesOrigenPendiente
        ]);
    }

    public function escalera() {
        // Flujo unificado: el seguimiento de escalera ahora se gestiona por persona
        // desde los listados de Personas y Pendiente por consolidar.
        $this->redirect('personas');
    }

    private function normalizarChecklistEscalera($checklist) {
        $estructuraEtapas = [
            'Ganar' => 4,
            'Consolidar' => 3,
            'Discipular' => 3,
            'Enviar' => 3
        ];
        $normalizado = [];

        foreach ($estructuraEtapas as $etapa => $totalSubprocesos) {
            $normalizado[$etapa] = array_fill(0, $totalSubprocesos, false);
        }

        $normalizado['_meta'] = [
            'no_disponible_observacion' => '',
            'reasignado_automatico' => false,
            'reasignado_automatico_at' => '',
            'reasignado_automatico_motivo' => ''
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
            $normalizado['_meta']['reasignado_automatico'] = !empty($checklist['_meta']['reasignado_automatico']);
            $normalizado['_meta']['reasignado_automatico_at'] = trim((string)($checklist['_meta']['reasignado_automatico_at'] ?? ''));
            $normalizado['_meta']['reasignado_automatico_motivo'] = trim((string)($checklist['_meta']['reasignado_automatico_motivo'] ?? ''));
        }

        return $normalizado;
    }

    private function calcularProcesoPorChecklist(array $checklistNormalizado) {
        if (!empty($checklistNormalizado['Ganar'][3])) {
            return 'Ganar';
        }

        $etapas = ['Ganar', 'Consolidar', 'Discipular', 'Enviar'];
        $completadasSeguidas = 0;

        foreach ($etapas as $etapa) {
            $valores = $checklistNormalizado[$etapa] ?? [false, false, false];
            if ($etapa === 'Ganar') {
                $completa = !empty($valores[0]) && !empty($valores[1]) && !empty($valores[2]);
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

    public function actualizarChecklistEscalera() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Metodo no permitido'], 405);
        }

        if (!$this->soportaChecklistEscalera) {
            $this->json(['success' => false, 'message' => 'Checklist de escalera no disponible en esta base de datos'], 400);
        }

        if (!AuthController::tienePermiso('personas', 'ver')) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $payload = $_POST;
        if (empty($payload)) {
            $raw = file_get_contents('php://input');
            if (is_string($raw) && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }

        $idPersona = isset($payload['id_persona']) ? (int)$payload['id_persona'] : 0;
        $etapa = trim((string)($payload['etapa'] ?? ''));
        $indice = isset($payload['indice']) ? (int)$payload['indice'] : -1;
        $marcado = !empty($payload['marcado']);
        $observacionNoDisponible = trim((string)($payload['observacion_no_disponible'] ?? ''));

        $estructuraEtapas = [
            'Ganar' => 4,
            'Consolidar' => 3,
            'Discipular' => 3,
            'Enviar' => 3
        ];

        if ($idPersona <= 0 || !isset($estructuraEtapas[$etapa]) || $indice < 0 || $indice >= $estructuraEtapas[$etapa]) {
            $this->json(['success' => false, 'message' => 'Datos invalidos'], 422);
        }

        $filtroRol = DataIsolation::generarFiltroPersonas();
        if (!$this->personaModel->puedeEditarEscaleraPorRol($idPersona, $filtroRol)) {
            $this->json(['success' => false, 'message' => 'No tienes acceso a esta persona'], 403);
        }

        $persona = $this->personaModel->getById($idPersona);
        if (!$persona) {
            $this->json(['success' => false, 'message' => 'Persona no encontrada'], 404);
        }

        $checklistActual = [];
        $checklistRaw = (string)($persona['Escalera_Checklist'] ?? '');
        if ($checklistRaw !== '') {
            $decoded = json_decode($checklistRaw, true);
            if (is_array($decoded)) {
                $checklistActual = $decoded;
            }
        }

        $checklistNormalizado = $this->normalizarChecklistEscalera($checklistActual);

        // Regla de negocio: "Asignado a líder" se controla automáticamente.
        $checklistNormalizado['Ganar'][0] = $this->personaTieneAsignacionCompleta($persona);

        if ($etapa === 'Ganar' && $indice === 0) {
            $this->json([
                'success' => true,
                'message' => 'Asignado a líder se actualiza automáticamente',
                'proceso' => $this->soportaProceso ? $this->calcularProcesoPorChecklist($checklistNormalizado) : null,
                'checklist' => $checklistNormalizado
            ]);
        }

        $checklistNormalizado[$etapa][$indice] = $marcado;

        if ($etapa === 'Ganar' && $indice === 3) {
            if ($marcado && $observacionNoDisponible === '') {
                $this->json(['success' => false, 'message' => 'Debes registrar una observación para No se dispone'], 422);
            }

            $checklistNormalizado['_meta']['no_disponible_observacion'] = $marcado ? $observacionNoDisponible : '';
            // Personas no concretadas pasan a no activas; al reactivar, vuelven a Activo.
            $this->personaModel->cambiarEstado($idPersona, $marcado ? 'Inactivo' : 'Activo');
        }

        $nuevoProceso = $this->soportaProceso
            ? $this->calcularProcesoPorChecklist($checklistNormalizado)
            : null;

        $checklistJson = json_encode($checklistNormalizado, JSON_UNESCAPED_UNICODE);
        if ($checklistJson === false) {
            $this->json(['success' => false, 'message' => 'No se pudo serializar el checklist'], 500);
        }

        $ok = $this->personaModel->updateEscaleraChecklistYProceso($idPersona, $checklistJson, $nuevoProceso);
        if (!$ok) {
            $this->json(['success' => false, 'message' => 'No se pudo guardar el checklist'], 500);
        }

        $this->json([
            'success' => true,
            'message' => 'Checklist actualizado',
            'proceso' => $nuevoProceso,
            'checklist' => $checklistNormalizado
        ]);
    }

    public function exportarExcel() {
        if (!AuthController::esAdministrador() && !AuthController::tienePermiso('personas', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $esModoGanar = (string)($_GET['modo'] ?? '') === 'ganar';
        $soloGanar = $esModoGanar ? true : false;
        $filtroMinisterio = $_GET['ministerio'] ?? null;
        $filtroLider = $_GET['lider'] ?? null;
        $filtroEstado = $_GET['estado'] ?? null;
        $filtroCelula = $_GET['celula'] ?? null;
        $filtroProceso = (string)($_GET['proceso'] ?? '');
        $filtroSinLider = (string)($_GET['sin_lider'] ?? '') === '1';
        $filtroSinCelula = (string)($_GET['sin_celula'] ?? '') === '1';
        $filtroEtapa = $this->normalizarEtapaFiltro($_GET['etapa'] ?? null);
        $filtroOrigen = $this->normalizarOrigenFiltro($_GET['origen'] ?? null);
        $puedeVerAtajoAsignados = $this->puedeVerAtajoAsignados();
        $puedeVerAtajoReasignados = $this->puedeVerAtajoReasignados();

        if ($filtroOrigen === 'asignados' && !$puedeVerAtajoAsignados) {
            $filtroOrigen = '';
        }
        if ($filtroOrigen === 'reasignados' && !$puedeVerAtajoReasignados) {
            $filtroOrigen = '';
        }

        if ($esModoGanar && ($filtroEtapa === null || $filtroEtapa === '')) {
            $filtroEtapa = 'Ganar';
        }
        $filtroPerfil = $this->normalizarPerfilListado($_GET['perfil'] ?? '');

        if (!in_array($filtroProceso, ['', 'sin_ministerio', 'sin_lider', 'sin_celula'], true)) {
            $filtroProceso = '';
        }

        if ($filtroProceso === 'sin_ministerio') {
            $filtroMinisterio = '0';
        } elseif ($filtroProceso === 'sin_lider') {
            $filtroSinLider = true;
        } elseif ($filtroProceso === 'sin_celula') {
            $filtroSinCelula = true;
        }

        if ($filtroSinLider) {
            $filtroLider = '0';
        }

        if ($filtroSinCelula) {
            $filtroCelula = '0';
        }

        $contextoFiltros = $this->getContextoFiltrosVisibles();
        [$filtroMinisterio, $filtroLider] = $this->limpiarFiltrosNoPermitidos($filtroMinisterio, $filtroLider, $contextoFiltros);

        $filtroCelulas = DataIsolation::generarFiltroCelulas();
        $celulasDisponiblesRaw = $this->celulaModel->getAllWithMemberCountAndRole($filtroCelulas);
        $celulaIdsPermitidas = array_map(static function($celula) {
            return (int)($celula['Id_Celula'] ?? 0);
        }, $celulasDisponiblesRaw);

        $filtroCelula = $filtroCelula !== null ? (string)$filtroCelula : '';
        if ($filtroCelula !== '' && $filtroCelula !== '0' && !in_array((int)$filtroCelula, $celulaIdsPermitidas, true)) {
            $filtroCelula = '';
        }

        $filtroRol = DataIsolation::generarFiltroPersonas();

        if (($filtroMinisterio !== null && $filtroMinisterio !== '') || ($filtroLider !== null && $filtroLider !== '') || ($filtroEstado !== null && $filtroEstado !== '') || ($filtroCelula !== null && $filtroCelula !== '') || ($filtroEtapa !== null && $filtroEtapa !== '') || ($filtroOrigen !== '')) {
            $personas = $this->personaModel->getWithFiltersAndRole($filtroRol, $filtroMinisterio, $filtroLider, $soloGanar, $filtroEstado, $filtroCelula, $filtroEtapa, $filtroOrigen);
        } else {
            $personas = $this->personaModel->getAllWithRole($filtroRol, $soloGanar, $filtroEstado, $filtroCelula, $filtroEtapa, $filtroOrigen);
        }

        if ($filtroPerfil !== '') {
            $personas = $this->filtrarPersonasPorPerfilListado($personas, $filtroPerfil);
        }

        $rows = [];
        foreach ($personas as $persona) {
            $rows[] = [
                (string)($persona['Nombre'] ?? ''),
                (string)($persona['Apellido'] ?? ''),
                (string)($persona['Numero_Documento'] ?? ''),
                (string)($persona['Telefono'] ?? ''),
                (string)($persona['Email'] ?? ''),
                (string)($persona['Nombre_Celula'] ?? ''),
                (string)($persona['Nombre_Lider'] ?? ''),
                (string)($persona['Nombre_Ministerio'] ?? ''),
                (string)($persona['Proceso'] ?? ''),
                (string)($persona['Nombre_Rol'] ?? ''),
                (string)($persona['Estado_Cuenta'] ?? ''),
                (string)($persona['Fecha_Registro'] ?? '')
            ];
        }

        $this->exportCsv(
            $esModoGanar ? 'personas_pendiente_consolidar_' . date('Ymd_His') : 'personas_' . date('Ymd_His'),
            ['Nombre', 'Apellido', 'Documento', 'Telefono', 'Email', 'Celula', 'Lider', 'Ministerio', 'Proceso', 'Rol', 'Estado', 'Fecha Registro'],
            $rows
        );
    }

    public function crear() {
        // Verificar permiso de crear
        if (!AuthController::tienePermiso('personas', 'crear')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $returnTo = $_POST['return_to'] ?? ($_GET['return_to'] ?? null);
        $celulaRetorno = $_POST['celula_retorno'] ?? ($_GET['celula'] ?? null);
        $celulaRetorno = ($celulaRetorno !== null && $celulaRetorno !== '') ? (int) $celulaRetorno : null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idRolSeleccionado = $_POST['id_rol'] ?: null;
            $rolEsAsistente = $this->esRolAsistente($idRolSeleccionado);
            $idMinisterioNormalizado = $this->normalizarIdMinisterioPost($_POST['id_ministerio'] ?? null);

            $data = [
                'Nombre' => $_POST['nombre'],
                'Apellido' => $_POST['apellido'],
                'Tipo_Documento' => $_POST['tipo_documento'] ?: null,
                'Numero_Documento' => $_POST['numero_documento'] ?: null,
                'Fecha_Nacimiento' => $_POST['fecha_nacimiento'] ?: null,
                'Edad' => $_POST['edad'] ?: null,
                'Genero' => $_POST['genero'] ?: null,
                'Telefono' => $_POST['telefono'] ?: null,
                'Email' => $_POST['email'] ?: null,
                'Direccion' => $_POST['direccion'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Peticion' => $_POST['peticion'] ?: null,
                'Invitado_Por' => $_POST['invitado_por'] ?: null,
                'Tipo_Reunion' => $_POST['tipo_reunion'] ?: null,
                'Id_Lider' => $_POST['id_lider'] ?: null,
                'Id_Celula' => $_POST['id_celula'] ?: null,
                'Id_Rol' => $_POST['id_rol'] ?: null,
                'Id_Ministerio' => $idMinisterioNormalizado,
                'Fecha_Registro' => date('Y-m-d H:i:s'),
                'Fecha_Registro_Unix' => time()
            ];

            if ($this->soportaConvencion) {
                $data['Convencion'] = $this->normalizarConvencion($_POST['convencion'] ?? null);
            }

            if ($this->soportaProceso) {
                    // Regla ministerial: toda persona nueva inicia en la etapa Ganar.
                    $data['Proceso'] = 'Ganar';
            }

            // Regla de negocio: si se crea desde Asistencias, se ancla automáticamente
            // a la célula/líder/ministerio de origen de ese registro.
            if ($returnTo === 'asistencia') {
                $data = $this->resolverAnclajeDesdeAsistencia($celulaRetorno, $data);
            }

            if ($this->soportaFechaAsignacionLider) {
                $data['Fecha_Asignacion_Lider'] = $this->resolverFechaAsignacionLider($data, null);
            }
            
            // Agregar campos de acceso al sistema si se proporcionan (solo admin)
            if (AuthController::esAdministrador() && !$rolEsAsistente) {
                if (!empty($_POST['usuario'])) {
                    $data['Usuario'] = $_POST['usuario'];
                }

                if (isset($_POST['estado_cuenta'])) {
                    $data['Estado_Cuenta'] = $_POST['estado_cuenta'];
                }
                
                // Si se proporciona contraseña, hashearla
                if (!empty($_POST['contrasena'])) {
                    $data['Contrasena'] = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
                    // Activar cuenta por defecto si se crea con contraseña y no se definió estado
                    if (!isset($data['Estado_Cuenta'])) {
                        $data['Estado_Cuenta'] = 'Activo';
                    }
                }
            }
            
            try {
                $idPersonaNueva = (int)$this->personaModel->create($data);

                if ($idPersonaNueva > 0) {
                    $personaCreada = $this->personaModel->getById($idPersonaNueva);
                    if (!empty($personaCreada)) {
                        $this->encolarMensajeBienvenidaYAsignacion($personaCreada);
                    }

                    $this->registrarPeticionSiAplica($idPersonaNueva, $data['Peticion'] ?? '');
                }

                if ($returnTo === 'asistencia') {
                    $urlRetorno = 'asistencias/registrar';
                    if ($celulaRetorno) {
                        $urlRetorno .= '&celula=' . $celulaRetorno;
                    }
                    $this->redirect($urlRetorno);
                }

                $this->redirect('personas/ganar');
            } catch (PDOException $e) {
                // Detectar error de duplicado
                if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
                    $error = 'La cédula ' . htmlspecialchars($data['Numero_Documento']) . ' ya está registrada en el sistema.';
                } else {
                    $error = 'Error al guardar la persona: ' . $e->getMessage();
                }
                
                $viewData = [
                    'celulas' => $this->celulaModel->getAll(),
                    'ministerios' => $this->ministerioModel->getAll(),
                    'roles' => $this->rolModel->getAll(),
                    'personas_invitadores' => $this->personaModel->getAll(),
                    'personas_lideres' => $this->personaModel->getLideresYPastores(),
                    'error' => $error,
                    'post_data' => $_POST,
                    'soportaConvencion' => $this->soportaConvencion,
                    'soportaProceso' => $this->soportaProceso,
                    'return_to' => $returnTo,
                    'celula_retorno' => $celulaRetorno
                ];
                $this->view('personas/formulario', $viewData);
            }
        } else {
            $data = [
                'celulas' => $this->celulaModel->getAll(),
                'ministerios' => $this->ministerioModel->getAll(),
                'roles' => $this->rolModel->getAll(),
                'personas_invitadores' => $this->personaModel->getAll(),
                'personas_lideres' => $this->personaModel->getLideresYPastores(),
                'soportaConvencion' => $this->soportaConvencion,
                'soportaProceso' => $this->soportaProceso,
                'return_to' => $returnTo,
                'celula_retorno' => $celulaRetorno
            ];
            $this->view('personas/formulario', $data);
        }
    }

    public function editar() {
        // Verificar permiso de editar
        if (!AuthController::tienePermiso('personas', 'editar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }

        $returnTo = $_POST['return_to'] ?? ($_GET['return_to'] ?? null);
        $celulaRetorno = $_POST['celula_retorno'] ?? ($_GET['celula'] ?? null);
        $celulaRetorno = ($celulaRetorno !== null && $celulaRetorno !== '') ? (int) $celulaRetorno : null;
        
        $id = $_GET['id'] ?? ($_POST['id'] ?? null);
        
        if (!$id) {
            $this->redirect('personas');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $personaAntes = $this->personaModel->getById($id);
            $idRolSeleccionado = $_POST['id_rol'] ?: null;
            $rolEsAsistente = $this->esRolAsistente($idRolSeleccionado);
            $idMinisterioNormalizado = $this->normalizarIdMinisterioPost($_POST['id_ministerio'] ?? null);

            $data = [
                'Nombre' => $_POST['nombre'],
                'Apellido' => $_POST['apellido'],
                'Tipo_Documento' => $_POST['tipo_documento'] ?: null,
                'Numero_Documento' => $_POST['numero_documento'] ?: null,
                'Fecha_Nacimiento' => $_POST['fecha_nacimiento'] ?: null,
                'Edad' => $_POST['edad'] ?: null,
                'Genero' => $_POST['genero'] ?: null,
                'Telefono' => $_POST['telefono'] ?: null,
                'Email' => $_POST['email'] ?: null,
                'Direccion' => $_POST['direccion'] ?: null,
                'Barrio' => $_POST['barrio'] ?: null,
                'Peticion' => $_POST['peticion'] ?: null,
                'Invitado_Por' => $_POST['invitado_por'] ?: null,
                'Tipo_Reunion' => $_POST['tipo_reunion'] ?: null,
                'Id_Lider' => $_POST['id_lider'] ?: null,
                'Id_Celula' => $_POST['id_celula'] ?: null,
                'Id_Rol' => $_POST['id_rol'] ?: null,
                'Id_Ministerio' => $idMinisterioNormalizado
            ];

            if ($this->soportaConvencion) {
                $data['Convencion'] = $this->normalizarConvencion($_POST['convencion'] ?? null);
            }

            if ($this->soportaProceso) {
                $data['Proceso'] = $this->normalizarProceso($_POST['proceso'] ?? null);
            }

            if ($this->soportaFechaAsignacionLider) {
                $data['Fecha_Asignacion_Lider'] = $this->resolverFechaAsignacionLider($data, $personaAntes);
            }
            
            // Agregar campos de acceso al sistema si se proporcionan (solo admin)
            if (AuthController::esAdministrador() && !$rolEsAsistente) {
                if (!empty($_POST['usuario'])) {
                    $data['Usuario'] = $_POST['usuario'];
                }
                
                // Si se proporciona contraseña, hashearla
                if (!empty($_POST['contrasena'])) {
                    $data['Contrasena'] = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
                }
                
                // Estado de cuenta (solo al editar)
                if (isset($_POST['estado_cuenta'])) {
                    $data['Estado_Cuenta'] = $_POST['estado_cuenta'];
                }
            }

            if (AuthController::esAdministrador() && isset($_POST['estado_cuenta'])) {
                $data['Estado_Cuenta'] = $_POST['estado_cuenta'];
            }
            
            try {
                $this->personaModel->update($id, $data);

                $peticionAntes = trim((string)($personaAntes['Peticion'] ?? ''));
                $peticionDespues = trim((string)($data['Peticion'] ?? ''));
                if ($peticionDespues !== '' && $peticionDespues !== $peticionAntes) {
                    $this->registrarPeticionSiAplica($id, $peticionDespues);
                }

                $personaDespues = $this->personaModel->getById($id);
                if (!empty($personaAntes) && !empty($personaDespues)) {
                    $this->encolarNotificacionCambiosAsignacion($personaAntes, $personaDespues);
                }

                if ($returnTo === 'asistencia') {
                    $urlRetorno = 'asistencias/registrar';
                    if ($celulaRetorno) {
                        $urlRetorno .= '&celula=' . $celulaRetorno;
                    }
                    $this->redirect($urlRetorno);
                }

                if ($returnTo === 'celulas') {
                    $this->redirect('celulas');
                }

                $this->redirect('personas');
            } catch (PDOException $e) {
                // Detectar error de duplicado
                if ($e->getCode() == 23000 || strpos($e->getMessage(), '1062') !== false) {
                    $error = 'La cédula ' . htmlspecialchars($data['Numero_Documento']) . ' ya está registrada en el sistema.';
                } else {
                    $error = 'Error al actualizar la persona: ' . $e->getMessage();
                }
                
                $persona = $this->personaModel->getById($id);
                $viewData = [
                    'persona' => $persona,
                    'celulas' => $this->celulaModel->getAll(),
                    'ministerios' => $this->ministerioModel->getAll(),
                    'roles' => $this->rolModel->getAll(),
                    'personas_invitadores' => $this->personaModel->getAll(),
                    'personas_lideres' => $this->personaModel->getLideresYPastores(),
                    'error' => $error,
                    'post_data' => $_POST,
                    'soportaConvencion' => $this->soportaConvencion,
                    'soportaProceso' => $this->soportaProceso,
                    'return_to' => $returnTo,
                    'celula_retorno' => $celulaRetorno
                ];
                $this->view('personas/formulario', $viewData);
            }
        } else {
            $persona = $this->personaModel->getById($id);
            $data = [
                'persona' => $persona,
                'celulas' => $this->celulaModel->getAll(),
                'ministerios' => $this->ministerioModel->getAll(),
                'roles' => $this->rolModel->getAll(),
                'personas_invitadores' => $this->personaModel->getAll(),
                'personas_lideres' => $this->personaModel->getLideresYPastores(),
                'soportaConvencion' => $this->soportaConvencion,
                'soportaProceso' => $this->soportaProceso,
                'return_to' => $returnTo,
                'celula_retorno' => $celulaRetorno
            ];
            $this->view('personas/formulario', $data);
        }
    }

    public function detalle() {
        $id = $_GET['id'] ?? null;
        $returnTo = $_GET['return_to'] ?? null;
        
        if (!$id) {
            $this->redirect('personas');
        }

        $persona = $this->personaModel->getById($id);
        $this->view('personas/detalle', [
            'persona' => $persona,
            'return_to' => $returnTo
        ]);
    }

    public function eliminar() {
        // Verificar permiso de eliminar
        if (!AuthController::tienePermiso('personas', 'eliminar')) {
            header('Location: ' . BASE_URL . '/public/?url=auth/acceso-denegado');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $this->personaModel->delete($id);
        }
        
        $this->redirect('personas');
    }
}
