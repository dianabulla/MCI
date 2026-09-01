<?php

require_once APP . '/Controllers/AuthController.php';
require_once APP . '/Models/EscuelaFormacionInscripcion.php';
require_once APP . '/Models/TallerFormulario.php';
require_once APP . '/Models/WhatsappLocalQueue.php';

class ComunidadPagosController extends BaseController
{
    private const PROGRAMAS_ESCUELA = [
        'universidad_vida' => 'Universidad de la Vida',
        'capacitacion_destino' => 'Capacitación Destino',
        'capacitacion_destino_nivel_1' => 'Capacitación Destino — Nivel 1',
        'capacitacion_destino_nivel_2' => 'Capacitación Destino — Nivel 2',
        'capacitacion_destino_nivel_3' => 'Capacitación Destino — Nivel 3',
    ];

    private EscuelaFormacionInscripcion $escuelaModel;
    private TallerFormulario $tallerModel;
    private WhatsappLocalQueue $whatsappQueue;

    public function __construct()
    {
        $this->escuelaModel = new EscuelaFormacionInscripcion();
        $this->tallerModel = new TallerFormulario();
        $this->whatsappQueue = new WhatsappLocalQueue();
    }

    public function index(): void
    {
        $this->requiereAcceso();
        $cedula = $this->normalizarDocumento($_GET['cedula'] ?? '');
        $opciones = $cedula !== '' ? $this->buscarInscripciones($cedula) : [];
        $ticket = is_array($_SESSION['comunidad_ticket'] ?? null) ? $_SESSION['comunidad_ticket'] : null;
        if (isset($_GET['limpiar_ticket'])) {
            unset($_SESSION['comunidad_ticket']);
            $ticket = null;
        }

        $this->view('comunidad/pagos', [
            'cedula' => $cedula,
            'opciones' => $opciones,
            'mensaje' => trim((string)($_SESSION['comunidad_pagos_ok'] ?? $_GET['mensaje'] ?? '')),
            'tipo_mensaje' => trim((string)($_SESSION['comunidad_pagos_tipo'] ?? $_GET['tipo'] ?? '')),
            'error' => trim((string)($_SESSION['comunidad_pagos_error'] ?? '')),
            'ticket' => $ticket,
            'responsable_default' => trim((string)($_SESSION['usuario_nombre'] ?? '')),
        ]);
        unset($_SESSION['comunidad_pagos_ok'], $_SESSION['comunidad_pagos_tipo'], $_SESSION['comunidad_pagos_error']);
    }

    public function buscar(): void
    {
        $this->requiereAcceso();
        $cedula = $this->normalizarDocumento($_GET['cedula'] ?? $_POST['cedula'] ?? '');
        if ($cedula === '') {
            $this->json(['ok' => false, 'mensaje' => 'Escriba el número de cédula.', 'opciones' => []]);
        }
        $opciones = $this->buscarInscripciones($cedula);
        $this->json([
            'ok' => true,
            'cedula' => $cedula,
            'opciones' => $opciones,
            'mensaje' => $opciones === [] ? 'No hay inscripciones con esa cédula en UV, Cap. Destino ni talleres.' : '',
        ]);
    }

    public function guardar(): void
    {
        $this->requiereAcceso();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            header('Location: ' . PUBLIC_URL . '?url=comunidad/pagos');
            exit;
        }

        $cedula = $this->normalizarDocumento($_POST['cedula'] ?? '');
        $clave = trim((string)($_POST['inscripcion'] ?? ''));
        $metodo = $this->normalizarMetodo($_POST['metodo_pago'] ?? '');
        $tipoPago = strtolower(trim((string)($_POST['tipo_pago'] ?? ''))) === 'completo' ? 'completo' : 'abono';
        $valor = $this->normalizarValor($_POST['valor_pago'] ?? '');
        $entregoMaterial = in_array(strtolower(trim((string)($_POST['entrego_material'] ?? ''))), ['1', 'si', 'sí'], true) ? 1 : 0;
        $recibidoPor = trim((string)($_POST['recibido_por'] ?? ''));
        if ($recibidoPor === '') {
            $recibidoPor = trim((string)($_SESSION['usuario_nombre'] ?? ''));
        }

        $redir = PUBLIC_URL . '?url=comunidad/pagos&cedula=' . rawurlencode($cedula);

        if ($cedula === '' || $clave === '') {
            $_SESSION['comunidad_pagos_error'] = 'Busque la cédula y seleccione a qué inscripción corresponde el pago.';
            header('Location: ' . $redir);
            exit;
        }
        if ($metodo === '') {
            $_SESSION['comunidad_pagos_error'] = 'Elija el medio de pago.';
            header('Location: ' . $redir);
            exit;
        }
        if ($valor <= 0) {
            $_SESSION['comunidad_pagos_error'] = 'Indique un valor de pago válido.';
            header('Location: ' . $redir);
            exit;
        }
        if ($recibidoPor === '') {
            $_SESSION['comunidad_pagos_error'] = 'Escriba el nombre del responsable que recibe el pago.';
            header('Location: ' . $redir);
            exit;
        }

        $partes = explode(':', $clave, 2);
        $origen = $partes[0] ?? '';
        $id = (int)($partes[1] ?? 0);
        if ($id <= 0 || !in_array($origen, ['escuela', 'taller'], true)) {
            $_SESSION['comunidad_pagos_error'] = 'La inscripción seleccionada no es válida.';
            header('Location: ' . $redir);
            exit;
        }

        $referencia = $this->generarReferencia($origen === 'taller' ? 'T' : 'R');
        $tipoEtiqueta = $tipoPago === 'abono' ? 'Abono' : 'Pago total';

        if ($origen === 'escuela') {
            $inscripcion = $this->escuelaModel->getByIdInscripcion($id);
            if (!is_array($inscripcion) || !$this->programaEscuelaPermitido((string)($inscripcion['Programa'] ?? ''))) {
                $_SESSION['comunidad_pagos_error'] = 'No se encontró esa inscripción de programa.';
                header('Location: ' . $redir);
                exit;
            }
            $metodoEscuela = strtolower($metodo);
            $this->escuelaModel->actualizarPagoInscripcion($id, $metodoEscuela, $tipoPago, $valor, $referencia, $recibidoPor, $entregoMaterial);
            $this->escuelaModel->registrarMovimientoPago($inscripcion, $metodoEscuela, $tipoPago, $valor, $referencia, $recibidoPor, $entregoMaterial);
            $titulo = $this->etiquetaPrograma((string)($inscripcion['Programa'] ?? ''));
            $nombre = trim((string)($inscripcion['Nombre'] ?? ''));
            $telefono = trim((string)($inscripcion['Telefono'] ?? ''));
            $doc = $this->normalizarDocumento($inscripcion['Cedula'] ?? $cedula);
        } else {
            $respuesta = $this->tallerModel->getRespuestaPorId($id);
            if (!is_array($respuesta)) {
                $_SESSION['comunidad_pagos_error'] = 'No se encontró esa inscripción de taller.';
                header('Location: ' . $redir);
                exit;
            }
            $idFormulario = (int)($respuesta['Id_Formulario'] ?? 0);
            $formulario = $this->tallerModel->getById($idFormulario);
            if (!$formulario) {
                $_SESSION['comunidad_pagos_error'] = 'El taller de esa inscripción ya no existe.';
                header('Location: ' . $redir);
                exit;
            }
            $this->tallerModel->registrarPago($id, $idFormulario, [
                'metodo_pago' => $metodo,
                'tipo_pago' => $tipoPago,
                'valor_pago' => $valor,
                'referencia_pago' => $referencia,
                'recibido_por' => $recibidoPor,
                'entrego_material' => $entregoMaterial,
            ]);
            $json = json_decode((string)($respuesta['Datos_JSON'] ?? '{}'), true);
            if (!is_array($json)) {
                $json = [];
            }
            $titulo = trim((string)($formulario['Titulo'] ?? 'Taller'));
            $nombre = trim((string)($json['nino_nombre'] ?? $json['persona_nombre'] ?? ''));
            $telefono = trim((string)($json['persona_telefono'] ?? $json['padres_telefono'] ?? ''));
            $doc = $this->normalizarDocumento($json['nino_documento'] ?? $json['persona_documento'] ?? $cedula);
        }

        $tituloMci = $this->tituloTicketMci($origen, $titulo);
        $_SESSION['comunidad_ticket'] = [
            'fecha' => date('Y-m-d H:i'),
            'formulario' => $titulo,
            'titulo_mci' => $tituloMci,
            'nombre' => $nombre,
            'documento' => $doc,
            'telefono' => $telefono,
            'etiqueta_nombre' => 'Inscrito',
            'pie_comprobante' => 'Gracias por tu ofrenda · ' . $tituloMci,
            'metodo_pago' => $metodo,
            'recibido_por' => $recibidoPor,
            'tipo_pago' => $tipoEtiqueta,
            'valor_pago' => number_format($valor, 0, ',', '.'),
            'referencia_pago' => $referencia,
            'entrego_material' => $entregoMaterial,
            'origen' => $origen,
        ];
        $_SESSION['comunidad_pagos_ok'] = 'Pago registrado. Quedó en la tabla de ' . $titulo . '.';
        $_SESSION['comunidad_pagos_tipo'] = 'ok';
        header('Location: ' . $redir);
        exit;
    }

    public function reenviarTicket(): void
    {
        $this->requiereAcceso();
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
            $this->json(['ok' => false, 'mensaje' => 'Método no permitido.'], 405);
        }

        $raw = (string)file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }

        $referencia = trim((string)($payload['referencia'] ?? ''));
        if ($referencia === '') {
            $this->json(['ok' => false, 'mensaje' => 'Falta la referencia del pago.'], 400);
        }

        $ticketSesion = is_array($_SESSION['comunidad_ticket'] ?? null) ? $_SESSION['comunidad_ticket'] : [];
        $telefono = trim((string)($ticketSesion['telefono'] ?? ''));
        $ticketData = $ticketSesion;

        if ($telefono === '' || (($ticketSesion['referencia_pago'] ?? '') !== $referencia)) {
            $mov = $this->escuelaModel->getMovimientoPagoPorReferencia($referencia);
            if (is_array($mov)) {
                $telefono = trim((string)($mov['Telefono'] ?? ''));
                $ticketData = [
                    'fecha' => (string)($mov['Fecha_Registro'] ?? ''),
                    'formulario' => $this->etiquetaPrograma((string)($mov['Programa'] ?? '')),
                    'nombre' => (string)($mov['Nombre'] ?? ''),
                    'documento' => (string)($mov['Cedula'] ?? ''),
                    'metodo_pago' => (string)($mov['Metodo_Pago'] ?? ''),
                    'recibido_por' => (string)($mov['Recibido_Por'] ?? ''),
                    'tipo_pago' => (string)($mov['Tipo_Pago'] ?? ''),
                    'valor_pago' => number_format((float)($mov['Valor_Pago'] ?? 0), 0, ',', '.'),
                    'referencia_pago' => $referencia,
                    'pie_comprobante' => 'Comprobante de pago — MCI Madrid',
                ];
            } else {
                $tallerPago = $this->tallerModel->getPagoPorReferencia($referencia);
                if (!is_array($tallerPago)) {
                    $this->json(['ok' => false, 'mensaje' => 'No se encontró ese ticket de pago.'], 404);
                }
                $json = json_decode((string)($tallerPago['Datos_JSON'] ?? '{}'), true);
                if (!is_array($json)) {
                    $json = [];
                }
                $telefono = trim((string)($json['persona_telefono'] ?? $json['padres_telefono'] ?? ''));
                $ticketData = [
                    'fecha' => (string)($tallerPago['Fecha_Registro'] ?? ''),
                    'formulario' => (string)($tallerPago['Titulo'] ?? 'Taller'),
                    'nombre' => trim((string)($json['nino_nombre'] ?? $json['persona_nombre'] ?? '')),
                    'documento' => trim((string)($json['nino_documento'] ?? $json['persona_documento'] ?? '')),
                    'metodo_pago' => (string)($tallerPago['Metodo_Pago'] ?? ''),
                    'recibido_por' => (string)($tallerPago['Recibido_Por'] ?? ''),
                    'tipo_pago' => (string)($tallerPago['Tipo_Pago'] ?? ''),
                    'valor_pago' => number_format((float)($tallerPago['Valor_Pago'] ?? 0), 0, ',', '.'),
                    'referencia_pago' => $referencia,
                    'pie_comprobante' => 'Comprobante de pago — ' . (string)($tallerPago['Titulo'] ?? 'Taller'),
                ];
            }
        }

        if ($this->whatsappQueue->normalizarTelefono($telefono) === null) {
            $this->json(['ok' => false, 'mensaje' => 'Esta persona no tiene un teléfono válido para WhatsApp.'], 400);
        }

        $imagenData = trim((string)($payload['imagen'] ?? ''));
        if (strpos($imagenData, 'base64,') !== false) {
            $imagenData = substr($imagenData, strpos($imagenData, 'base64,') + 7);
        }
        $binario = base64_decode($imagenData, true);
        if ($binario === false || strlen($binario) < 24 || strncmp($binario, "\x89PNG\r\n\x1a\n", 8) !== 0) {
            $this->json(['ok' => false, 'mensaje' => 'La imagen del ticket no es válida.'], 400);
        }
        if (strlen($binario) > 2 * 1024 * 1024) {
            $this->json(['ok' => false, 'mensaje' => 'La imagen del ticket es demasiado pesada.'], 400);
        }

        $destDir = ROOT . '/public/uploads/tickets';
        if (!is_dir($destDir) && !@mkdir($destDir, 0755, true) && !is_dir($destDir)) {
            $this->json(['ok' => false, 'mensaje' => 'No se pudo guardar el ticket.'], 500);
        }
        $fileName = 'ticket_' . preg_replace('/[^\w-]+/', '_', $referencia) . '_' . date('Ymd_His') . '.png';
        if (@file_put_contents($destDir . '/' . $fileName, $binario) === false) {
            $this->json(['ok' => false, 'mensaje' => 'No se pudo guardar el ticket.'], 500);
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim((string)PUBLIC_URL, '/');
        $mediaUrl = $scheme . '://' . $host . $base . '/uploads/tickets/' . rawurlencode($fileName);

        $mensaje = 'Hola ' . trim((string)($ticketData['nombre'] ?? '')) . ", te compartimos tu ticket de pago MCI Madrid.\n"
            . 'Programa: ' . trim((string)($ticketData['formulario'] ?? '')) . "\n"
            . 'Valor: $' . trim((string)($ticketData['valor_pago'] ?? '0')) . "\n"
            . 'Referencia: ' . $referencia;

        $okCola = $this->whatsappQueue->encolar(
            $telefono,
            $mensaje,
            'ticket_pago_comunidad',
            'ticket:' . $referencia . ':' . uniqid('', true),
            $mediaUrl,
            'image'
        );
        if (!$okCola) {
            $this->json(['ok' => false, 'mensaje' => 'No se pudo encolar el envío por WhatsApp.'], 500);
        }

        $this->json(['ok' => true, 'mensaje' => 'Ticket encolado: se enviará por WhatsApp como imagen.']);
    }

    private function requiereAcceso(): void
    {
        if (!AuthController::estaAutenticado()) {
            header('Location: ' . public_app_url('auth/login'));
            exit;
        }
        if (!AuthController::puedeAccederPagosComunidad()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buscarInscripciones(string $cedula): array
    {
        $opciones = [];
        $escuelas = $this->escuelaModel->buscarInscripcionesPorTelefonoOCedula('', $cedula, 40);
        foreach ($escuelas as $fila) {
            $programa = (string)($fila['Programa'] ?? '');
            if (!$this->programaEscuelaPermitido($programa)) {
                continue;
            }
            $id = (int)($fila['Id_Inscripcion'] ?? 0);
            $opciones[] = [
                'clave' => 'escuela:' . $id,
                'origen' => 'escuela',
                'programa' => $this->etiquetaPrograma($programa),
                'nombre' => trim((string)($fila['Nombre'] ?? '')),
                'fecha' => substr((string)($fila['Fecha_Registro'] ?? ''), 0, 16),
                'total_pagado' => $this->totalPagadoEscuela($fila),
            ];
        }

        $talleres = $this->tallerModel->buscarRespuestasPorDocumento($cedula, 40);
        $ids = array_map(static fn(array $r): int => (int)($r['Id_Respuesta'] ?? 0), $talleres);
        $totales = $this->tallerModel->getTotalesPagosPorRespuestas($ids);
        foreach ($talleres as $fila) {
            $id = (int)($fila['Id_Respuesta'] ?? 0);
            $opciones[] = [
                'clave' => 'taller:' . $id,
                'origen' => 'taller',
                'programa' => 'Taller · ' . trim((string)($fila['Titulo'] ?? 'Taller')),
                'nombre' => trim((string)($fila['nombre'] ?? '')),
                'fecha' => substr((string)($fila['Fecha_Registro'] ?? ''), 0, 16),
                'total_pagado' => (float)($totales[$id] ?? 0),
            ];
        }

        return $opciones;
    }

    /**
     * @param array<string, mixed> $inscripcion
     */
    private function totalPagadoEscuela(array $inscripcion): float
    {
        $cedula = $this->normalizarDocumento($inscripcion['Cedula'] ?? '');
        $idPersona = (int)($inscripcion['Id_Persona'] ?? 0);
        $programa = (string)($inscripcion['Programa'] ?? '');
        try {
            return (float)$this->escuelaModel->getTotalPagadoAcumuladoPorClave($cedula, $idPersona, $programa);
        } catch (Throwable $e) {
            return (float)($inscripcion['Valor_Pago'] ?? 0);
        }
    }

    private function programaEscuelaPermitido(string $programa): bool
    {
        return array_key_exists($programa, self::PROGRAMAS_ESCUELA);
    }

    private function etiquetaPrograma(string $programa): string
    {
        return self::PROGRAMAS_ESCUELA[$programa] ?? $programa;
    }

    private function tituloTicketMci(string $origen, string $titulo): string
    {
        $titulo = trim($titulo);
        if ($origen === 'taller') {
            if ($titulo === '') {
                return 'MCI Taller';
            }
            if (stripos($titulo, 'taller') === 0) {
                return 'MCI ' . $titulo;
            }
            return 'MCI Taller ' . $titulo;
        }
        return $titulo !== '' ? ('MCI ' . $titulo) : 'MCI';
    }

    private function normalizarDocumento($valor): string
    {
        return preg_replace('/\D+/', '', (string)$valor) ?? '';
    }

    private function normalizarMetodo($valor): string
    {
        $clave = strtolower(trim((string)$valor));
        $map = [
            'efectivo' => 'Efectivo',
            'nequi' => 'Nequi',
            'daviplata' => 'Daviplata',
        ];
        return $map[$clave] ?? '';
    }

    private function normalizarValor($valor): float
    {
        $raw = trim((string)$valor);
        if ($raw === '') {
            return 0.0;
        }
        $raw = str_replace([' ', "\u{00A0}"], '', $raw);
        $tieneComa = strpos($raw, ',') !== false;
        $tienePunto = strpos($raw, '.') !== false;
        if ($tieneComa && $tienePunto) {
            if ((int)strrpos($raw, ',') > (int)strrpos($raw, '.')) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($tieneComa) {
            $partes = explode(',', $raw);
            $raw = count($partes) === 2 && strlen(end($partes)) <= 2
                ? implode('.', $partes)
                : str_replace(',', '', $raw);
        }
        return round((float)$raw, 2);
    }

    private function generarReferencia(string $prefijo): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $ref = $prefijo;
        for ($i = 0; $i < 5; $i++) {
            $ref .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $ref;
    }
}
