<?php
/**
 * Controlador Evento
 */

require_once APP . '/Models/Evento.php';
require_once APP . '/Models/EventoModulo.php';
require_once APP . '/Helpers/DataIsolation.php';

class EventoController extends BaseController {
    private $eventoModel;
    private $eventoModuloModel;
    private $uploadDir;
    private $uploadUrlBase;
    private const MAX_IMAGE_UPLOAD_BYTES = 50 * 1024 * 1024; // 50MB
    private const MAX_VIDEO_UPLOAD_BYTES = 500 * 1024 * 1024; // 500MB

    private function puedeGestionarPanelEventos(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('eventos:editar')
            || AuthController::puede('eventos:crear')
            || AuthController::puede('eventos:gestionar_contenido_publico');
    }

    private function puedeGestionarContenidoPublicoEventos(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('eventos:gestionar_contenido_publico')
            || AuthController::puede('eventos:editar');
    }

    /** @deprecated Use puedeGestionarPanelEventos() — compatibilidad con vistas */
    private function esAdminEventos() {
        return $this->puedeGestionarPanelEventos();
    }

    private function ordenarItemsPublicosRecientes($items) {
        $items = is_array($items) ? $items : [];

        usort($items, static function($a, $b) {
            $ordenA = (int)($a['Orden'] ?? 0);
            $ordenB = (int)($b['Orden'] ?? 0);
            if ($ordenA !== $ordenB) {
                return $ordenA <=> $ordenB;
            }

            $creacionA = strtotime((string)($a['Fecha_Creacion'] ?? '')) ?: PHP_INT_MAX;
            $creacionB = strtotime((string)($b['Fecha_Creacion'] ?? '')) ?: PHP_INT_MAX;
            if ($creacionA !== $creacionB) {
                return $creacionA <=> $creacionB;
            }

            return ((int)($a['Id_Contenido'] ?? 0)) <=> ((int)($b['Id_Contenido'] ?? 0));
        });

        return $items;
    }

    private function eventoSigueVigenteParaPublico(array $evento) {
        $fecha = trim((string)($evento['Fecha_Evento'] ?? ''));
        if ($fecha === '') {
            return false;
        }

        $hora = trim((string)($evento['Hora_Evento'] ?? ''));
        $timestampEvento = strtotime($fecha . ' ' . ($hora !== '' ? $hora : '23:59:59'));
        if ($timestampEvento === false) {
            return false;
        }

        return $timestampEvento >= time();
    }

    public function __construct() {
        $this->eventoModel = new Evento();
        $this->eventoModuloModel = new EventoModulo();
        $this->uploadDir = ROOT . '/public/uploads/eventos';
        $this->uploadUrlBase = rtrim(PUBLIC_URL, '/') . '/uploads/eventos';
    }

    private function getModuloConfig($tipo) {
        $tipo = strtolower(trim((string)$tipo));
        $map = [
            'universidad_vida' => [
                'tipo' => 'universidad_vida',
                'titulo' => 'Universidad de la vida',
                'route_privada' => 'eventos/universidad-vida',
                'route_publica' => 'eventos/universidad-vida/publico'
            ],
            'capacitacion_destino' => [
                'tipo' => 'capacitacion_destino',
                'titulo' => 'Capacitación destino',
                'route_privada' => 'eventos/capacitacion-destino',
                'route_publica' => 'eventos/capacitacion-destino/publico'
            ],
            'otros' => [
                'tipo' => 'otros',
                'titulo' => 'Otros',
                'route_privada' => 'eventos/otros',
                'route_publica' => 'eventos/otros/publico'
            ]
        ];

        return $map[$tipo] ?? null;
    }

    private function getResumenModulosEventos() {
        $modulos = [
            array_merge($this->getModuloConfig('universidad_vida'), [
                'descripcion' => 'Contenido y QR público del módulo de Universidad de la Vida.',
                'variant' => 'uv'
            ]),
            array_merge($this->getModuloConfig('capacitacion_destino'), [
                'descripcion' => 'Contenido y QR público del módulo de Capacitación Destino.',
                'variant' => 'destino'
            ]),
            array_merge($this->getModuloConfig('otros'), [
                'descripcion' => 'Contenido y QR público para reuniones o campañas adicionales.',
                'variant' => 'otros'
            ])
        ];

        foreach ($modulos as &$modulo) {
            $urlPublica = $this->buildAbsolutePublicUrl((string)($modulo['route_publica'] ?? 'eventos/proximos'));
            $modulo['url_publica'] = $urlPublica;
            $modulo['qr_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($urlPublica);
            $modulo['visible_publico'] = $this->moduloTieneContenidoPublico((string)($modulo['tipo'] ?? ''));
        }
        unset($modulo);

        return $modulos;
    }

    private function moduloTieneContenidoPublico($tipo) {
        $tipo = strtolower(trim((string)$tipo));

        if ($tipo === 'reuniones') {
            $eventos = (array)$this->eventoModel->getUpcoming();

            foreach ($eventos as $evento) {
                if ((int)($evento['Permitir_Compartir'] ?? 1) === 1 && $this->eventoSigueVigenteParaPublico((array)$evento)) {
                    return true;
                }
            }

            return false;
        }

        $items = (array)$this->eventoModuloModel->getByModulo($tipo);
        foreach ($items as $item) {
            if ((int)($item['Estado_Activo'] ?? 1) === 1) {
                return true;
            }
        }

        return false;
    }

    private function redirigirModulo($tipo) {
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $this->redirect($config['route_privada']);
    }

    private function renderModuloContenido($tipo) {
        if (!AuthController::puede('eventos:ver') && !AuthController::esAdministrador()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        // Cualquier usuario autenticado que no sea admin se envía a la versión pública,
        // incluso si no tiene el permiso específico de "eventos".
        if (!$this->puedeGestionarContenidoPublicoEventos()) {
            $this->redirect($config['route_publica']);
            return;
        }

        $items = $this->eventoModuloModel->getByModulo($config['tipo']);
        $idEditar = (int)($_GET['editar'] ?? 0);
        $itemEditar = null;
        if ($idEditar > 0) {
            $item = $this->eventoModuloModel->getById($idEditar);
            if (!empty($item) && (string)($item['Tipo_Modulo'] ?? '') === $config['tipo']) {
                $itemEditar = $item;
            }
        }

        $urlPublica = $this->buildAbsolutePublicUrl($config['route_publica']);
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($urlPublica);

        $error = $_SESSION['evento_modulo_error'] ?? null;
        unset($_SESSION['evento_modulo_error']);
        $ok = $_SESSION['evento_modulo_ok'] ?? null;
        unset($_SESSION['evento_modulo_ok']);

        $this->view('eventos/modulo_contenido', [
            'modulo' => $config,
            'items' => $items,
            'itemEditar' => $itemEditar,
            'urlPublica' => $urlPublica,
            'qrUrl' => $qrUrl,
            'error' => $error,
            'ok' => $ok,
        ]);
    }

    public function index() {
        if (!AuthController::puede('eventos:ver') && !AuthController::esAdministrador()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $filtroEventos = DataIsolation::generarFiltroEventos();
        $eventos = $this->eventoModel->getAllWithRole($filtroEventos);
        $urlEventosPublicos = $this->buildAbsolutePublicUrl('eventos/proximos');
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . urlencode($urlEventosPublicos);
        $modulosEventos = $this->getResumenModulosEventos();

        if (!$this->esAdminEventos()) {
            $modulosEventos = array_values(array_filter($modulosEventos, static function($modulo) {
                return !empty($modulo['visible_publico']);
            }));
        }

        $this->view('eventos/lista', [
            'eventos' => $eventos,
            'urlEventosPublicos' => $urlEventosPublicos,
            'qrUrl' => $qrUrl,
            'modulosEventos' => $modulosEventos,
            'esAdminEventos' => $this->esAdminEventos()
        ]);
    }

    public function exportarExcel() {
        if (!AuthController::puede('eventos:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $filtroEventos = DataIsolation::generarFiltroEventos();
        $eventos = $this->eventoModel->getAllWithRole($filtroEventos);

        $rows = [];
        foreach ($eventos as $evento) {
            $rows[] = [
                (string)($evento['Nombre_Evento'] ?? ''),
                (string)($evento['Fecha_Evento'] ?? ''),
                (string)($evento['Hora_Evento'] ?? ''),
                (string)($evento['Lugar_Evento'] ?? ''),
                (string)($evento['Descripcion_Evento'] ?? '')
            ];
        }

        $this->exportCsv(
            'eventos_' . date('Ymd_His'),
            ['Evento', 'Fecha', 'Hora', 'Lugar', 'Descripcion'],
            $rows
        );
    }

    public function crear() {
        if (!AuthController::puede('eventos:crear')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $rutaImagen = $this->procesarArchivo('imagen_evento', ['jpg', 'jpeg', 'png', 'webp', 'gif'], self::MAX_IMAGE_UPLOAD_BYTES);
                $rutaVideo = $this->procesarArchivo('video_evento', ['mp4', 'webm', 'mov', 'm4v'], self::MAX_VIDEO_UPLOAD_BYTES);

                $data = [
                    'Nombre_Evento' => trim($_POST['nombre_evento'] ?? ''),
                    'Descripcion_Evento' => trim($_POST['descripcion_evento'] ?? ''),
                    'Fecha_Evento' => $_POST['fecha_evento'] ?? '',
                    'Hora_Evento' => $_POST['hora_evento'] ?? '',
                    'Lugar_Evento' => trim($_POST['lugar_evento'] ?? ''),
                    'Imagen_Evento' => $rutaImagen,
                    'Video_Evento' => $rutaVideo,
                    'Permitir_Compartir' => !empty($_POST['permitir_compartir']) ? 1 : 0
                ];

                $this->eventoModel->create($data);
                $this->redirect('eventos');
            } catch (Exception $e) {
                $_SESSION['evento_error'] = $e->getMessage();
                $this->redirect('eventos/crear');
            }
        } else {
            $error = $_SESSION['evento_error'] ?? null;
            unset($_SESSION['evento_error']);
            $this->view('eventos/formulario', ['error' => $error]);
        }
    }

    public function editar() {
        if (!AuthController::puede('eventos:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            $this->redirect('eventos');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $eventoActual = $this->eventoModel->getById($id);

                $data = [
                    'Nombre_Evento' => trim($_POST['nombre_evento'] ?? ''),
                    'Descripcion_Evento' => trim($_POST['descripcion_evento'] ?? ''),
                    'Fecha_Evento' => $_POST['fecha_evento'] ?? '',
                    'Hora_Evento' => $_POST['hora_evento'] ?? '',
                    'Lugar_Evento' => trim($_POST['lugar_evento'] ?? ''),
                    'Permitir_Compartir' => !empty($_POST['permitir_compartir']) ? 1 : 0
                ];

                $eliminarImagen = !empty($_POST['eliminar_imagen']);
                $eliminarVideo = !empty($_POST['eliminar_video']);

                $nuevaImagen = $this->procesarArchivo('imagen_evento', ['jpg', 'jpeg', 'png', 'webp', 'gif'], self::MAX_IMAGE_UPLOAD_BYTES);
                $nuevoVideo = $this->procesarArchivo('video_evento', ['mp4', 'webm', 'mov', 'm4v'], self::MAX_VIDEO_UPLOAD_BYTES);

                $data['Imagen_Evento'] = $eventoActual['Imagen_Evento'] ?? null;
                $data['Video_Evento'] = $eventoActual['Video_Evento'] ?? null;

                if ($eliminarImagen) {
                    $this->eliminarArchivoFisico($eventoActual['Imagen_Evento'] ?? null);
                    $data['Imagen_Evento'] = null;
                }

                if ($eliminarVideo) {
                    $this->eliminarArchivoFisico($eventoActual['Video_Evento'] ?? null);
                    $data['Video_Evento'] = null;
                }

                if ($nuevaImagen !== null) {
                    $this->eliminarArchivoFisico($eventoActual['Imagen_Evento'] ?? null);
                    $data['Imagen_Evento'] = $nuevaImagen;
                }

                if ($nuevoVideo !== null) {
                    $this->eliminarArchivoFisico($eventoActual['Video_Evento'] ?? null);
                    $data['Video_Evento'] = $nuevoVideo;
                }

                $this->eventoModel->update($id, $data);
                $this->redirect('eventos');
            } catch (Exception $e) {
                $_SESSION['evento_error'] = $e->getMessage();
                $this->redirect('eventos/editar&id=' . $id);
            }
        } else {
            $error = $_SESSION['evento_error'] ?? null;
            unset($_SESSION['evento_error']);

            $data = [
                'evento' => $this->eventoModel->getById($id),
                'error' => $error
            ];
            $this->view('eventos/formulario', $data);
        }
    }

    public function eliminar() {
        if (!AuthController::puede('eventos:eliminar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $evento = $this->eventoModel->getById($id);
            $this->eliminarArchivoFisico($evento['Imagen_Evento'] ?? null);
            $this->eliminarArchivoFisico($evento['Video_Evento'] ?? null);
            $this->eventoModel->delete($id);
        }
        
        $this->redirect('eventos');
    }

    public function proximosPublico() {
        // Reuniones es un módulo público independiente con su propia vista informativa.
        $eventos = (array)$this->eventoModel->getUpcoming();

        // Solo mostrar eventos vigentes y marcados como compartibles públicamente.
        $eventos = array_values(array_filter($eventos, static function($evento) {
            return (int)($evento['Permitir_Compartir'] ?? 1) === 1;
        }));

        $eventos = array_values(array_filter($eventos, function($evento) {
            return $this->eventoSigueVigenteParaPublico((array)$evento);
        }));

        usort($eventos, static function($a, $b) {
            $fechaA = trim((string)($a['Fecha_Evento'] ?? ''));
            $horaA = trim((string)($a['Hora_Evento'] ?? ''));
            $tsA = strtotime($fechaA . ' ' . ($horaA !== '' ? $horaA : '23:59:59')) ?: 0;

            $fechaB = trim((string)($b['Fecha_Evento'] ?? ''));
            $horaB = trim((string)($b['Hora_Evento'] ?? ''));
            $tsB = strtotime($fechaB . ' ' . ($horaB !== '' ? $horaB : '23:59:59')) ?: 0;

            // Orden cronológico: primero el más próximo.
            return $tsA <=> $tsB;
        });

        $this->view('eventos/proximos_publico', ['eventos' => $eventos]);
    }

    public function compartirPublico() {
        $id = (int)($_GET['id'] ?? 0);
        $evento = $this->eventoModel->getByIdPublico($id);

        if (empty($evento)) {
            http_response_code(404);
            echo 'Evento no encontrado';
            return;
        }

        if ((int)($evento['Permitir_Compartir'] ?? 1) !== 1) {
            http_response_code(403);
            echo 'Este evento no está disponible para compartir';
            return;
        }

        $urlCompartir = $this->buildAbsolutePublicUrl('eventos/compartir?id=' . $id);
        $tituloCompartir = trim((string)($evento['Nombre_Evento'] ?? 'Evento'));
        $descripcionCompartir = trim((string)($evento['Descripcion_Evento'] ?? ''));
        $descripcionCompartir = $this->limitarTexto($descripcionCompartir, 180);

        $imagenCompartir = '';
        if (!empty($evento['Imagen_Evento'])) {
            $imagenCompartir = $this->buildAbsoluteAssetUrl(
                'uploads/eventos/' . rawurlencode((string)$evento['Imagen_Evento'])
            );
        }

        $this->view('eventos/evento_compartir_publico', [
            'evento' => $evento,
            'urlCompartir' => $urlCompartir,
            'tituloCompartir' => $tituloCompartir,
            'descripcionCompartir' => $descripcionCompartir,
            'imagenCompartir' => $imagenCompartir
        ]);
    }

    public function universidadVida() {
        $this->renderModuloContenido('universidad_vida');
    }

    public function capacitacionDestino() {
        $this->renderModuloContenido('capacitacion_destino');
    }

    public function otros() {
        $this->renderModuloContenido('otros');
    }

    public function guardarModuloContenido() {
        if (!$this->puedeGestionarContenidoPublicoEventos()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('eventos');
            return;
        }

        $tipo = (string)($_POST['tipo_modulo'] ?? '');
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $idContenido = (int)($_POST['id_contenido'] ?? 0);
        $imagenesSubidas = ['guardados' => [], 'errores' => []];

        try {
            $imagenesSubidas = $this->procesarArchivosMultiples('imagen_modulo', ['jpg', 'jpeg', 'png', 'webp', 'gif'], self::MAX_IMAGE_UPLOAD_BYTES);
            $nuevoVideo = $this->procesarArchivo('video_modulo', ['mp4', 'webm', 'mov', 'm4v'], self::MAX_VIDEO_UPLOAD_BYTES);

            if ($imagenesSubidas['errores'] !== []) {
                throw new Exception(implode(' ', $imagenesSubidas['errores']));
            }

            $data = [
                'Tipo_Modulo' => $config['tipo'],
                'Titulo' => trim((string)($_POST['titulo'] ?? '')),
                'Parrafo' => trim((string)($_POST['parrafo'] ?? '')),
                'Orden' => max(0, (int)($_POST['orden'] ?? 0)),
                'Estado_Activo' => !empty($_POST['estado_activo']) ? 1 : 0,
                'Fecha_Publicacion_Desde' => trim((string)($_POST['fecha_publicacion_desde'] ?? '')),
                'Fecha_Publicacion_Hasta' => trim((string)($_POST['fecha_publicacion_hasta'] ?? ''))
            ];

            $data['Fecha_Publicacion_Desde'] = $data['Fecha_Publicacion_Desde'] !== '' ? $data['Fecha_Publicacion_Desde'] : null;
            $data['Fecha_Publicacion_Hasta'] = $data['Fecha_Publicacion_Hasta'] !== '' ? $data['Fecha_Publicacion_Hasta'] : null;

            if ($data['Fecha_Publicacion_Desde'] && $data['Fecha_Publicacion_Hasta']
                && $data['Fecha_Publicacion_Desde'] > $data['Fecha_Publicacion_Hasta']) {
                throw new Exception('La fecha de publicación desde no puede ser mayor que hasta.');
            }

            if ($data['Titulo'] === '' || $data['Parrafo'] === '') {
                throw new Exception('Título y párrafo son obligatorios.');
            }

            $listaImagenes = $imagenesSubidas['guardados'];
            $nuevaImagen = $listaImagenes[0] ?? null;

            if ($idContenido > 0) {
                if (count($listaImagenes) > 1) {
                    for ($i = 1, $n = count($listaImagenes); $i < $n; $i++) {
                        $this->eliminarArchivoFisico($listaImagenes[$i]);
                    }
                }

                $actual = $this->eventoModuloModel->getById($idContenido);
                if (empty($actual) || (string)($actual['Tipo_Modulo'] ?? '') !== $config['tipo']) {
                    throw new Exception('Contenido no válido para este módulo.');
                }

                $data['Imagen'] = $actual['Imagen'] ?? null;
                $data['Video'] = $actual['Video'] ?? null;

                if (!empty($_POST['eliminar_imagen'])) {
                    $this->eliminarArchivoFisico($actual['Imagen'] ?? null);
                    $data['Imagen'] = null;
                }

                if (!empty($_POST['eliminar_video'])) {
                    $this->eliminarArchivoFisico($actual['Video'] ?? null);
                    $data['Video'] = null;
                }

                if ($nuevaImagen !== null) {
                    $this->eliminarArchivoFisico($actual['Imagen'] ?? null);
                    $data['Imagen'] = $nuevaImagen;
                }

                if ($nuevoVideo !== null) {
                    $this->eliminarArchivoFisico($actual['Video'] ?? null);
                    $data['Video'] = $nuevoVideo;
                }

                $this->eventoModuloModel->update($idContenido, $data);
            } elseif (count($listaImagenes) > 1) {
                $ordenBase = $data['Orden'];
                foreach ($listaImagenes as $idx => $nombreImagen) {
                    $fila = $data;
                    $fila['Imagen'] = $nombreImagen;
                    $fila['Video'] = $idx === 0 ? $nuevoVideo : null;
                    if ($idx > 0) {
                        $fila['Titulo'] = $data['Titulo'] . ' (' . ($idx + 1) . ')';
                        $fila['Orden'] = $ordenBase + $idx;
                    }
                    $this->eventoModuloModel->create($fila);
                }
                $_SESSION['evento_modulo_ok'] = count($listaImagenes) . ' imágenes cargadas correctamente.';
            } else {
                $data['Imagen'] = $nuevaImagen;
                $data['Video'] = $nuevoVideo;
                $this->eventoModuloModel->create($data);
            }
        } catch (Exception $e) {
            foreach ($imagenesSubidas['guardados'] ?? [] as $archivoSubido) {
                $this->eliminarArchivoFisico($archivoSubido);
            }
            $_SESSION['evento_modulo_error'] = $e->getMessage();
        }

        $this->redirigirModulo($config['tipo']);
    }

    public function guardarModuloContenidoMasivo() {
        if (!$this->puedeGestionarContenidoPublicoEventos()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('eventos');
            return;
        }

        $tipo = (string)($_POST['tipo_modulo'] ?? '');
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $imagenesSubidas = ['guardados' => [], 'errores' => []];

        try {
            $parrafo = trim((string)($_POST['parrafo_masivo'] ?? ''));
            $tituloBase = trim((string)($_POST['titulo_masivo'] ?? ''));
            $ordenBase = max(0, (int)($_POST['orden_masivo'] ?? 0));
            $estadoActivo = !empty($_POST['estado_activo_masivo']) ? 1 : 0;
            $fechaDesde = trim((string)($_POST['fecha_publicacion_desde_masivo'] ?? ''));
            $fechaHasta = trim((string)($_POST['fecha_publicacion_hasta_masivo'] ?? ''));

            if ($parrafo === '') {
                throw new Exception('Indica un párrafo base para las imágenes masivas.');
            }

            $imagenesSubidas = $this->procesarArchivosMultiples('imagenes_masivas', ['jpg', 'jpeg', 'png', 'webp', 'gif'], self::MAX_IMAGE_UPLOAD_BYTES);
            if ($imagenesSubidas['errores'] !== []) {
                throw new Exception(implode(' ', $imagenesSubidas['errores']));
            }
            if ($imagenesSubidas['guardados'] === []) {
                throw new Exception('Selecciona al menos una imagen para la carga masiva.');
            }

            if ($fechaDesde !== '' && $fechaHasta !== '' && $fechaDesde > $fechaHasta) {
                throw new Exception('La fecha de publicación desde no puede ser mayor que hasta.');
            }

            $creados = 0;
            foreach ($imagenesSubidas['guardados'] as $idx => $nombreImagen) {
                $titulo = $tituloBase !== ''
                    ? ($tituloBase . (count($imagenesSubidas['guardados']) > 1 ? ' ' . ($idx + 1) : ''))
                    : $this->tituloDesdeNombreArchivo($nombreImagen, $idx);

                $this->eventoModuloModel->create([
                    'Tipo_Modulo' => $config['tipo'],
                    'Titulo' => $titulo,
                    'Parrafo' => $parrafo,
                    'Imagen' => $nombreImagen,
                    'Video' => null,
                    'Orden' => $ordenBase + $idx,
                    'Estado_Activo' => $estadoActivo,
                    'Fecha_Publicacion_Desde' => $fechaDesde !== '' ? $fechaDesde : null,
                    'Fecha_Publicacion_Hasta' => $fechaHasta !== '' ? $fechaHasta : null,
                ]);
                $creados++;
            }

            $_SESSION['evento_modulo_ok'] = $creados . ' imagen(es) subida(s) correctamente en carga masiva.';
        } catch (Exception $e) {
            foreach ($imagenesSubidas['guardados'] as $archivoSubido) {
                $this->eliminarArchivoFisico($archivoSubido);
            }
            $_SESSION['evento_modulo_error'] = $e->getMessage();
        }

        $this->redirigirModulo($config['tipo']);
    }

    private function tituloDesdeNombreArchivo(string $nombreArchivo, int $indice = 0): string {
        $base = pathinfo(basename($nombreArchivo), PATHINFO_FILENAME);
        $base = trim(preg_replace('/[_-]+/', ' ', (string)$base) ?? '');
        if ($base === '') {
            $base = 'Imagen ' . ($indice + 1);
        }
        return mb_substr($base, 0, 180);
    }

    public function duplicarModuloContenido() {
        if (!$this->puedeGestionarContenidoPublicoEventos()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $tipo = (string)($_GET['tipo'] ?? '');
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $idContenido = (int)($_GET['id'] ?? 0);
        if ($idContenido > 0) {
            $item = $this->eventoModuloModel->getById($idContenido);
            if (!empty($item) && (string)($item['Tipo_Modulo'] ?? '') === $config['tipo']) {
                $this->eventoModuloModel->duplicar($idContenido);
            }
        }

        $this->redirigirModulo($config['tipo']);
    }

    public function eliminarModuloContenido() {
        if (!AuthController::puede('eventos:eliminar') && !$this->puedeGestionarContenidoPublicoEventos()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $tipo = (string)($_GET['tipo'] ?? '');
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            $this->redirect('eventos');
            return;
        }

        $idContenido = (int)($_GET['id'] ?? 0);
        if ($idContenido > 0) {
            $actual = $this->eventoModuloModel->getById($idContenido);
            if (!empty($actual) && (string)($actual['Tipo_Modulo'] ?? '') === $config['tipo']) {
                $this->eliminarArchivoFisico($actual['Imagen'] ?? null);
                $this->eliminarArchivoFisico($actual['Video'] ?? null);
                $this->eventoModuloModel->delete($idContenido);
            }
        }

        $this->redirigirModulo($config['tipo']);
    }

    private function renderModuloPublico($tipo) {
        $config = $this->getModuloConfig($tipo);
        if (!$config) {
            http_response_code(404);
            echo 'Módulo no encontrado';
            return;
        }

        // Mostrar contenido publicado (activo) aunque su rango de fechas haya pasado.
        $items = $this->eventoModuloModel->getByModulo($config['tipo']);
        $items = array_values(array_filter($items, static function($item) {
            return (int)($item['Estado_Activo'] ?? 1) === 1;
        }));
        $items = $this->ordenarItemsPublicosRecientes($items);

        $modulosPublicos = [];
        $modulosBase = [
            [
                'tipo' => 'reuniones',
                'titulo' => 'Reuniones',
                'route_publica' => 'eventos/proximos',
            ],
            $this->getModuloConfig('universidad_vida'),
            $this->getModuloConfig('capacitacion_destino'),
            $this->getModuloConfig('otros'),
        ];

        foreach ($modulosBase as $moduloTmp) {
            if (empty($moduloTmp)) {
                continue;
            }

            $modulosPublicos[] = [
                'tipo' => (string)$moduloTmp['tipo'],
                'titulo' => (string)$moduloTmp['titulo'],
                'url_publica' => $this->buildAbsolutePublicUrl((string)$moduloTmp['route_publica']),
            ];
        }

        $this->view('eventos/modulo_publico', [
            'modulo' => $config,
            'items' => $items,
            'modulosPublicos' => $modulosPublicos
        ]);
    }

    public function universidadVidaPublico() {
        $this->renderModuloPublico('universidad_vida');
    }

    public function capacitacionDestinoPublico() {
        $this->renderModuloPublico('capacitacion_destino');
    }

    public function otrosPublico() {
        $this->renderModuloPublico('otros');
    }

    private function procesarArchivo($campo, $extensionesPermitidas, $maxBytes) {
        $resultado = $this->procesarArchivosMultiples($campo, $extensionesPermitidas, $maxBytes);
        if ($resultado['errores'] !== []) {
            throw new Exception($resultado['errores'][0]);
        }

        return $resultado['guardados'][0] ?? null;
    }

    /**
     * @return array{guardados:array<int,string>,errores:array<int,string>}
     */
    private function procesarArchivosMultiples($campo, $extensionesPermitidas, $maxBytes) {
        $archivos = $this->normalizarArchivosMultiples($campo);
        if ($archivos === []) {
            return ['guardados' => [], 'errores' => []];
        }

        $guardados = [];
        $errores = [];

        foreach ($archivos as $archivo) {
            $nombreOriginal = trim((string)($archivo['name'] ?? ''));
            try {
                $nombre = $this->procesarUnArchivoSubido($archivo, $extensionesPermitidas, $maxBytes);
                if ($nombre !== null) {
                    $guardados[] = $nombre;
                }
            } catch (Exception $e) {
                $errores[] = ($nombreOriginal !== '' ? '«' . $nombreOriginal . '»: ' : '') . $e->getMessage();
            }
        }

        return ['guardados' => $guardados, 'errores' => $errores];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizarArchivosMultiples($campo) {
        if (!isset($_FILES[$campo]) || !is_array($_FILES[$campo])) {
            return [];
        }

        $input = $_FILES[$campo];
        if (!isset($input['name'])) {
            return [];
        }

        if (!is_array($input['name'])) {
            if ((int)($input['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                return [];
            }
            return [$input];
        }

        $out = [];
        foreach ($input['name'] as $i => $name) {
            if ((int)($input['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => $name,
                'type' => $input['type'][$i] ?? '',
                'tmp_name' => $input['tmp_name'][$i] ?? '',
                'error' => $input['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $input['size'][$i] ?? 0,
            ];
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $archivo
     */
    private function procesarUnArchivoSubido(array $archivo, $extensionesPermitidas, $maxBytes) {
        $nombreOriginal = trim((string)($archivo['name'] ?? ''));
        $etiqueta = $nombreOriginal !== '' ? $nombreOriginal : 'archivo';

        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE || $nombreOriginal === '') {
            return null;
        }

        $errorUpload = (int)($archivo['error'] ?? UPLOAD_ERR_OK);
        if ($errorUpload !== UPLOAD_ERR_OK) {
            $limiteServidorBytes = $this->getPhpUploadLimitBytes();
            $limiteServidorTexto = $limiteServidorBytes > 0 ? $this->formatBytes($limiteServidorBytes) : null;

            if ($errorUpload === UPLOAD_ERR_INI_SIZE || $errorUpload === UPLOAD_ERR_FORM_SIZE) {
                if ($limiteServidorTexto !== null) {
                    throw new Exception('«' . $etiqueta . '» supera el límite del servidor (' . $limiteServidorTexto . ').');
                }

                throw new Exception('«' . $etiqueta . '» supera el límite del servidor.');
            }

            if ($errorUpload === UPLOAD_ERR_PARTIAL) {
                throw new Exception('La carga de «' . $etiqueta . '» quedó incompleta. Intenta nuevamente.');
            }

            throw new Exception('No se pudo subir «' . $etiqueta . '».');
        }

        $tamano = (int)($archivo['size'] ?? 0);
        if ($tamano <= 0 || $tamano > $maxBytes) {
            throw new Exception('«' . $etiqueta . '» supera el tamaño permitido (' . $this->formatBytes((int)$maxBytes) . ').');
        }

        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if (!in_array($extension, $extensionesPermitidas, true)) {
            throw new Exception('Formato no permitido para «' . $etiqueta . '».');
        }

        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0755, true) && !is_dir($this->uploadDir)) {
            throw new Exception('No se pudo crear la carpeta de uploads de eventos.');
        }

        $nombre = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destino = $this->uploadDir . '/' . $nombre;

        if (!move_uploaded_file((string)($archivo['tmp_name'] ?? ''), $destino)) {
            throw new Exception('No se pudo guardar «' . $etiqueta . '».');
        }

        return $nombre;
    }

    private function getPhpUploadLimitBytes() {
        $uploadMax = $this->parseIniSizeToBytes((string)ini_get('upload_max_filesize'));
        $postMax = $this->parseIniSizeToBytes((string)ini_get('post_max_size'));

        if ($uploadMax <= 0 && $postMax <= 0) {
            return 0;
        }

        if ($uploadMax <= 0) {
            return $postMax;
        }

        if ($postMax <= 0) {
            return $uploadMax;
        }

        return min($uploadMax, $postMax);
    }

    private function parseIniSizeToBytes($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return 0;
        }

        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([KMGTP]?)/i', $valor, $m)) {
            return (int)$valor;
        }

        $numero = (float)$m[1];
        $unidad = strtoupper($m[2] ?? '');
        $factor = 1;
        if ($unidad === 'K') {
            $factor = 1024;
        } elseif ($unidad === 'M') {
            $factor = 1024 * 1024;
        } elseif ($unidad === 'G') {
            $factor = 1024 * 1024 * 1024;
        } elseif ($unidad === 'T') {
            $factor = 1024 * 1024 * 1024 * 1024;
        } elseif ($unidad === 'P') {
            $factor = 1024 * 1024 * 1024 * 1024 * 1024;
        }

        return (int)round($numero * $factor);
    }

    private function formatBytes($bytes) {
        $bytes = max(0, (int)$bytes);
        if ($bytes === 0) {
            return '0 B';
        }

        $unidades = ['B', 'KB', 'MB', 'GB', 'TB'];
        $indice = (int)floor(log($bytes, 1024));
        $indice = min($indice, count($unidades) - 1);
        $valor = $bytes / pow(1024, $indice);

        return number_format($valor, $indice === 0 ? 0 : 1, '.', '') . ' ' . $unidades[$indice];
    }

    private function eliminarArchivoFisico($archivo) {
        if (empty($archivo)) {
            return;
        }

        $ruta = $this->uploadDir . '/' . basename($archivo);
        if (is_file($ruta)) {
            @unlink($ruta);
        }
    }

    private function buildAbsolutePublicUrl($route) {
        if (function_exists('public_app_url')) {
            $route = ltrim((string)$route, '/');
            $query = [];
            if (strpos($route, '?') !== false) {
                [$route, $queryString] = explode('?', $route, 2);
                parse_str($queryString, $query);
            } elseif (strpos($route, '&') !== false) {
                [$route, $queryString] = explode('&', $route, 2);
                parse_str($queryString, $query);
            }

            $relative = public_app_url($route, $query);
        } else {
            $relative = rtrim(PUBLIC_URL, '/') . '/index.php?url=' . ltrim((string)$route, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (preg_match('#^https?://#i', $relative)) {
            return $relative;
        }

        return $scheme . '://' . $host . $relative;
    }

    private function buildAbsoluteAssetUrl($relativePath) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(PUBLIC_URL, '/');

        return $scheme . '://' . $host . $base . '/' . ltrim($relativePath, '/');
    }

    private function limitarTexto($texto, $max = 180) {
        $texto = trim(preg_replace('/\s+/', ' ', (string)$texto));
        if ($texto === '') {
            return '';
        }

        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            if (mb_strlen($texto, 'UTF-8') <= $max) {
                return $texto;
            }

            return rtrim(mb_substr($texto, 0, $max - 3, 'UTF-8')) . '...';
        }

        if (strlen($texto) <= $max) {
            return $texto;
        }

        return rtrim(substr($texto, 0, $max - 3)) . '...';
    }
}