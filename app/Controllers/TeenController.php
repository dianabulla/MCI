<?php
/**
 * Controlador Teen
 * Módulo para subir material PDF, listar, ver, eliminar y consultar visualizaciones.
 */

require_once APP . '/Models/Teen.php';
require_once APP . '/Models/Persona.php';
require_once APP . '/Models/Ministerio.php';
require_once APP . '/Controllers/AuthController.php';

class TeenController extends BaseController {
    private $teenModel;
    private $personaModel;
    private $ministerioModel;

    public function __construct() {
        $this->teenModel = new Teen();
        $this->personaModel = new Persona();
        $this->ministerioModel = new Ministerio();
    }

    private function obtenerDirectorioMaterialesTeen(): string {
        $override = trim((string)(getenv('TEENS_UPLOAD_DIR') ?: ''));
        if ($override !== '') {
            $override = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $override), DIRECTORY_SEPARATOR);
            if (is_dir($override)) {
                return $override;
            }
        }

        $directorio = ROOT . '/public/uploads/teens';
        if (!is_dir($directorio) && !@mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear el directorio de material teens.');
        }

        return $directorio;
    }

    private function obtenerDirectorioSemanaTeen(int $anio, int $mes, int $semanaMes): string {
        $base = $this->obtenerDirectorioMaterialesTeen();
        $ruta = $base . DIRECTORY_SEPARATOR . $anio . DIRECTORY_SEPARATOR . sprintf('%02d', $mes)
            . DIRECTORY_SEPARATOR . 'semana-' . $semanaMes;

        if (!is_dir($ruta) && !@mkdir($ruta, 0775, true) && !is_dir($ruta)) {
            throw new RuntimeException('No se pudo crear la carpeta del material teens.');
        }

        return $ruta;
    }

    private function prefijoRelativoSemanaTeen(int $anio, int $mes, int $semanaMes): string {
        return $anio . '/' . sprintf('%02d', $mes) . '/semana-' . $semanaMes;
    }

    private function obtenerDirectorioDecoracionTeen(int $anio, int $mes): string {
        $base = $this->obtenerDirectorioMaterialesTeen();
        $ruta = $base . DIRECTORY_SEPARATOR . $anio . DIRECTORY_SEPARATOR . sprintf('%02d', $mes)
            . DIRECTORY_SEPARATOR . 'decoracion';

        if (!is_dir($ruta) && !@mkdir($ruta, 0775, true) && !is_dir($ruta)) {
            throw new RuntimeException('No se pudo crear la carpeta de decoración teens.');
        }

        return $ruta;
    }

    private function prefijoRelativoDecoracionTeen(int $anio, int $mes): string {
        return $anio . '/' . sprintf('%02d', $mes) . '/decoracion';
    }

    private function tituloAutomaticoDecoracion(int $anio, int $mes): string {
        return Teen::nombreMes($mes) . ' ' . $anio . ' — Decoración';
    }

    private function validarMesSemanaMaterial(int $mes, int $semanaMes): void {
        if ($mes < 1 || $mes > 12) {
            throw new Exception('Selecciona un mes válido (1-12).');
        }
        if (Teen::esSemanaDecoracion($semanaMes)) {
            return;
        }
        if ($semanaMes < 1 || $semanaMes > Teen::semanasPorMes()) {
            throw new Exception('Selecciona una semana válida del mes (1-' . Teen::semanasPorMes() . ').');
        }
    }

    private function tituloAutomaticoSemana(int $anio, int $mes, int $semanaMes): string {
        return Teen::nombreMes($mes) . ' ' . $anio . ' — Semana ' . $semanaMes;
    }

    /**
     * Clasifica un PDF dentro de la carpeta del mes: semana 1-5 o decoración.
     *
     * @return array{tipo:string,semana?:int}|null
     */
    private function clasificarRutaMaterialCarpetaMes(string $ruta): ?array {
        $ruta = str_replace('\\', '/', trim($ruta));
        if ($ruta === '') {
            return null;
        }

        $maxSemana = Teen::semanasPorMes();
        $partes = array_values(array_filter(explode('/', $ruta), static function ($parte) {
            return trim((string)$parte) !== '';
        }));

        foreach ($partes as $parte) {
            if (preg_match('/^decoraci[oó]n$/iu', $parte)) {
                return ['tipo' => 'decoracion'];
            }
        }

        foreach ($partes as $parte) {
            if (preg_match('/^semana[\s\-_]?(\d+)$/iu', $parte, $coincidencia)) {
                $numero = (int)($coincidencia[1] ?? 0);
                if ($numero >= 1 && $numero <= $maxSemana) {
                    return ['tipo' => 'semana', 'semana' => $numero];
                }
            }
            if (preg_match('/^week[\s\-_]?(\d+)$/iu', $parte, $coincidencia)) {
                $numero = (int)($coincidencia[1] ?? 0);
                if ($numero >= 1 && $numero <= $maxSemana) {
                    return ['tipo' => 'semana', 'semana' => $numero];
                }
            }
            if (preg_match('/^s[\s\-_]?(\d+)$/iu', $parte, $coincidencia)) {
                $numero = (int)($coincidencia[1] ?? 0);
                if ($numero >= 1 && $numero <= $maxSemana) {
                    return ['tipo' => 'semana', 'semana' => $numero];
                }
            }
        }

        return null;
    }

    /**
     * Crea o actualiza el registro de material de una semana con los PDF subidos.
     *
     * @param list<string> $archivosSubidos Rutas relativas guardadas en disco
     */
    private function persistirMaterialSemanaTeen(
        int $anio,
        int $mes,
        int $semanaMes,
        array $archivosSubidos,
        string $tituloManual = '',
        string $descripcion = '',
        array $profesor = []
    ): void {
        if ($archivosSubidos === []) {
            return;
        }

        if (Teen::esSemanaDecoracion($semanaMes)) {
            $existente = $this->teenModel->getMaterialDecoracionPorMes($anio, $mes);
            $titulo = $tituloManual !== ''
                ? $tituloManual
                : $this->tituloAutomaticoDecoracion($anio, $mes);
        } else {
            $existente = $this->teenModel->getMaterialPorSemana($anio, $mes, $semanaMes);
            $titulo = $tituloManual !== ''
                ? $tituloManual
                : $this->tituloAutomaticoSemana($anio, $mes, $semanaMes);
        }
        $idProfesor = (int)($profesor['id_profesor'] ?? 0);
        $nombreProfesor = trim((string)($profesor['profesor_nombre'] ?? ''));

        if (!empty($existente)) {
            $archivosPrevios = $this->parsearArchivosPdfRegistro($existente['archivos_pdf'] ?? '');
            $archivosFinales = array_values(array_unique(array_merge($archivosPrevios, $archivosSubidos)));
            $dataUpdate = [
                'titulo' => $titulo,
                'descripcion' => $descripcion !== '' ? $descripcion : ($existente['descripcion'] ?? null),
                'archivos_pdf' => json_encode($archivosFinales, JSON_UNESCAPED_UNICODE),
            ];
            if ($nombreProfesor !== '') {
                $dataUpdate['id_profesor'] = $idProfesor > 0 ? $idProfesor : null;
                $dataUpdate['profesor_nombre'] = $nombreProfesor;
            }
            $this->teenModel->updateTeen((int)$existente['id'], $dataUpdate);
            return;
        }

        $this->teenModel->create([
            'titulo' => $titulo,
            'descripcion' => $descripcion !== '' ? $descripcion : null,
            'archivos_pdf' => json_encode($archivosSubidos, JSON_UNESCAPED_UNICODE),
            'anio' => $anio,
            'mes' => $mes,
            'semana_mes' => $semanaMes,
            'id_profesor' => $idProfesor > 0 ? $idProfesor : null,
            'profesor_nombre' => $nombreProfesor !== '' ? $nombreProfesor : null,
        ]);
    }

    private function normalizarArchivosPdfSubidos(array $archivos): array {
        if (!is_array($archivos['name'] ?? null)) {
            return [
                'name' => [$archivos['name'] ?? ''],
                'tmp_name' => [$archivos['tmp_name'] ?? ''],
                'size' => [$archivos['size'] ?? 0],
                'error' => [$archivos['error'] ?? UPLOAD_ERR_NO_FILE],
            ];
        }

        return $archivos;
    }

    private function normalizarProfesorMaterial(array $post): array {
        $idProfesor = (int)($post['id_profesor'] ?? 0);
        $nombreManual = trim((string)($post['profesor_nombre'] ?? ''));
        $busqueda = trim((string)($post['profesor_busqueda'] ?? ''));

        if ($nombreManual === '' && $busqueda !== '') {
            $nombreManual = $busqueda;
        }

        if ($idProfesor > 0 && $nombreManual === '') {
            $persona = $this->personaModel->getById($idProfesor);
            if (!empty($persona)) {
                $nombreManual = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
            }
        }

        return [
            'id_profesor' => $idProfesor > 0 ? $idProfesor : 0,
            'profesor_nombre' => $this->normalizarTextoMayusculas($nombreManual),
        ];
    }

    /** Rutas antiguas donde pudo quedar material si cambió el despliegue o el hosting. */
    private function obtenerDirectoriosLegacyTeen(): array {
        $docRoot = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, (string)($_SERVER['DOCUMENT_ROOT'] ?? '')), DIRECTORY_SEPARATOR);
        $candidatos = [
            ROOT . '/uploads/teens',
            ROOT . '/public/uploads/material_hub/teens',
            ROOT . '/public/uploads/material_teens',
            dirname(ROOT) . '/public/uploads/teens',
            dirname(ROOT) . '/uploads/teens',
        ];
        if ($docRoot !== '') {
            $candidatos[] = $docRoot . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'teens';
            $candidatos[] = $docRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'teens';
        }

        $principal = $this->obtenerDirectorioMaterialesTeen();
        $unicos = [];
        foreach ($candidatos as $ruta) {
            $ruta = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $ruta), DIRECTORY_SEPARATOR);
            if ($ruta === '' || $ruta === $principal) {
                continue;
            }
            $unicos[$ruta] = true;
        }

        return array_keys($unicos);
    }

    /** Directorios donde buscar PDF (principal + legacy), sin duplicados. */
    private function obtenerDirectoriosBusquedaTeen(): array {
        $principal = $this->obtenerDirectorioMaterialesTeen();
        $dirs = array_merge([$principal], $this->obtenerDirectoriosLegacyTeen());

        $unicos = [];
        foreach ($dirs as $dir) {
            $dir = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $dir), DIRECTORY_SEPARATOR);
            if ($dir !== '' && is_dir($dir)) {
                $unicos[$dir] = true;
            }
        }

        return array_keys($unicos);
    }

    /** Índice nombre => ruta física bajo public/uploads (una vez por petición). */
    private function indicePdfsBajoUploads(): array {
        static $indice = null;
        if ($indice !== null) {
            return $indice;
        }

        $indice = [];
        $base = ROOT . '/public/uploads';
        if (!is_dir($base)) {
            return $indice;
        }

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $archivo) {
                if (!$archivo->isFile()) {
                    continue;
                }

                $nombre = $archivo->getFilename();
                if (strtolower((string)pathinfo($nombre, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }

                $indice[$nombre] = $archivo->getPathname();
            }
        } catch (Throwable $e) {
            error_log('TeenController: no se pudo escanear uploads: ' . $e->getMessage());
        }

        return $indice;
    }

    private function copiarPdfTeenAlPrincipal(string $origen, string $nombreArchivo, string $directorioPrincipal): ?string {
        $destino = rtrim($directorioPrincipal, '/\\') . DIRECTORY_SEPARATOR . $nombreArchivo;
        if (is_file($destino)) {
            return $destino;
        }

        if (!is_file($origen) || !is_readable($origen)) {
            return null;
        }

        if (@copy($origen, $destino) || is_file($destino)) {
            return $destino;
        }

        return is_file($origen) ? $origen : null;
    }

    /**
     * Extrae nombres de archivo PDF desde el campo archivos_pdf (JSON, texto o URLs).
     *
     * @return list<string>
     */
    private function parsearArchivosPdfRegistro($archivosPdfRaw): array {
        $raw = trim((string)$archivosPdfRaw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = json_decode(stripslashes($raw), true);
        }

        if (!is_array($decoded)) {
            $uno = $this->normalizarNombreArchivoDesdeRegistro($raw);
            return $uno !== '' ? [$uno] : [];
        }

        $nombres = [];
        foreach ($decoded as $item) {
            if (is_string($item)) {
                $nombre = $this->normalizarNombreArchivoDesdeRegistro($item);
                if ($nombre !== '') {
                    $nombres[] = $nombre;
                }
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            foreach (['archivo', 'nombre', 'file', 'filename', 'path', 'url'] as $clave) {
                if (empty($item[$clave])) {
                    continue;
                }
                $nombre = $this->normalizarNombreArchivoDesdeRegistro((string)$item[$clave]);
                if ($nombre !== '') {
                    $nombres[] = $nombre;
                    break;
                }
            }
        }

        return array_values(array_unique($nombres));
    }

    private function normalizarNombreArchivoDesdeRegistro(string $valor): string {
        return $this->normalizarRutaArchivoTeen($valor);
    }

    private function normalizarRutaArchivoTeen(string $valor): string {
        $valor = trim(urldecode($valor));
        if ($valor === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $valor)) {
            $path = (string)(parse_url($valor, PHP_URL_PATH) ?? '');
            if ($path !== '' && preg_match('#/uploads/teens/(.+)$#i', $path, $coincidencia)) {
                $valor = (string)$coincidencia[1];
            } elseif ($path !== '') {
                $valor = basename($path);
            }
        }

        $valor = str_replace('\\', '/', $valor);
        $valor = ltrim($valor, '/');

        if ($valor === '' || str_contains($valor, '..')) {
            return '';
        }

        if (!preg_match('/\.pdf$/i', $valor)) {
            return '';
        }

        $partes = [];
        foreach (explode('/', $valor) as $parte) {
            $parte = trim($parte);
            if ($parte === '' || $parte === '.') {
                continue;
            }
            $partes[] = $parte;
        }

        if ($partes === []) {
            return '';
        }

        return implode('/', $partes);
    }

    /** Clave para emparejar aunque cambie el prefijo de fecha/hash al subir por FTP. */
    private function obtenerClaveComparacionPdfTeen(string $nombreArchivo): string {
        $nombre = strtolower(basename($nombreArchivo));
        if (preg_match('/^\d{8}_\d{6}_[a-f0-9]{8}_(.+)$/i', $nombre, $coincidencia)) {
            return (string)$coincidencia[1];
        }
        if (preg_match('/^material_teens_\d{8}_\d{6}_[a-f0-9]+_\d+_(.+)$/i', $nombre, $coincidencia)) {
            return (string)$coincidencia[1];
        }

        return $nombre;
    }

    /**
     * @return array{exact: array<string, array{nombre:string,ruta:string}>, slug: array<string, list<array{nombre:string,ruta:string}>>}
     */
    private function indicePdfsDisponiblesTeen(): array {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $cache = ['exact' => [], 'slug' => []];

        foreach ($this->obtenerDirectoriosBusquedaTeen() as $directorio) {
            $archivos = @scandir($directorio) ?: [];
            foreach ($archivos as $archivo) {
                if ($archivo === '.' || $archivo === '..') {
                    continue;
                }
                if (strtolower((string)pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }

                $ruta = $directorio . DIRECTORY_SEPARATOR . $archivo;
                if (!is_file($ruta)) {
                    continue;
                }

                $entrada = ['nombre' => $archivo, 'ruta' => $ruta];
                $cache['exact'][strtolower($archivo)] = $entrada;

                $slug = $this->obtenerClaveComparacionPdfTeen($archivo);
                if ($slug !== '') {
                    $cache['slug'][$slug][] = $entrada;
                }
            }
        }

        foreach ($this->indicePdfsBajoUploads() as $nombre => $ruta) {
            if (!is_file($ruta)) {
                continue;
            }
            $entrada = ['nombre' => $nombre, 'ruta' => $ruta];
            $cache['exact'][strtolower($nombre)] = $entrada;
            $slug = $this->obtenerClaveComparacionPdfTeen($nombre);
            if ($slug !== '') {
                $cache['slug'][$slug][] = $entrada;
            }
        }

        return $cache;
    }

    private function buscarPdfTeenFlexible(string $nombreEsperado, string $directorioPrincipal): ?string {
        $indice = $this->indicePdfsDisponiblesTeen();
        $lower = strtolower($nombreEsperado);

        if (isset($indice['exact'][$lower])) {
            return $this->copiarPdfTeenAlPrincipal($indice['exact'][$lower]['ruta'], $nombreEsperado, $directorioPrincipal);
        }

        $slug = $this->obtenerClaveComparacionPdfTeen($nombreEsperado);
        if ($slug === '' || empty($indice['slug'][$slug])) {
            return null;
        }

        $candidatos = $indice['slug'][$slug];
        if (count($candidatos) === 1) {
            return $this->copiarPdfTeenAlPrincipal($candidatos[0]['ruta'], $nombreEsperado, $directorioPrincipal);
        }

        $mejor = null;
        $mejorPuntaje = -1;
        foreach ($candidatos as $candidato) {
            $puntaje = 0;
            similar_text(strtolower($nombreEsperado), strtolower((string)$candidato['nombre']), $puntaje);
            if ($puntaje > $mejorPuntaje) {
                $mejorPuntaje = $puntaje;
                $mejor = $candidato;
            }
        }

        if ($mejor === null) {
            return null;
        }

        return $this->copiarPdfTeenAlPrincipal($mejor['ruta'], $nombreEsperado, $directorioPrincipal);
    }

    private function migrarMaterialesTeensLegacy(string $directorioDestino): void {
        foreach ($this->obtenerDirectoriosLegacyTeen() as $directorioLegacy) {
            if (!is_dir($directorioLegacy)) {
                continue;
            }

            $archivos = @scandir($directorioLegacy) ?: [];
            foreach ($archivos as $archivo) {
                if ($archivo === '.' || $archivo === '..') {
                    continue;
                }

                if (strtolower((string)pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
                    continue;
                }

                $origen = $directorioLegacy . '/' . $archivo;
                if (!is_file($origen)) {
                    continue;
                }

                $destino = rtrim($directorioDestino, '/') . '/' . $archivo;
                if (!is_file($destino)) {
                    @copy($origen, $destino);
                }
            }
        }
    }

    private function resolverRutaPdfTeen(string $nombreArchivo): ?string {
        $rutaRelativa = $this->normalizarRutaArchivoTeen($nombreArchivo);
        if ($rutaRelativa === '') {
            return null;
        }

        $directorioPrincipal = $this->obtenerDirectorioMaterialesTeen();
        $rutaPrincipal = $directorioPrincipal . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $rutaRelativa);
        if (is_file($rutaPrincipal)) {
            return $rutaPrincipal;
        }

        $nombreBase = basename($rutaRelativa);
        if ($nombreBase !== $rutaRelativa) {
            $rutaBase = $directorioPrincipal . DIRECTORY_SEPARATOR . $nombreBase;
            if (is_file($rutaBase)) {
                return $rutaBase;
            }
        }

        foreach ($this->obtenerDirectoriosBusquedaTeen() as $directorio) {
            if ($directorio === $directorioPrincipal) {
                continue;
            }

            $ruta = $directorio . DIRECTORY_SEPARATOR . $nombreBase;
            if (!is_file($ruta)) {
                continue;
            }

            $copiada = $this->copiarPdfTeenAlPrincipal($ruta, $nombreBase, $directorioPrincipal);
            if ($copiada !== null) {
                return $copiada;
            }
        }

        $indice = $this->indicePdfsBajoUploads();
        if (isset($indice[$nombreBase])) {
            return $this->copiarPdfTeenAlPrincipal($indice[$nombreBase], $nombreBase, $directorioPrincipal);
        }

        return $this->buscarPdfTeenFlexible($nombreBase, $directorioPrincipal);
    }

    /**
     * Intenta copiar al directorio principal todos los PDF referenciados en BD que existan en otra ruta.
     *
     * @return array{recuperados:int,faltan:int}
     */
    private function repararRegistrosArchivosPdfEnBd(): int {
        $corregidos = 0;

        foreach ((array)$this->teenModel->getAll() as $material) {
            $id = (int)($material['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $raw = (string)($material['archivos_pdf'] ?? '');
            $nombres = $this->parsearArchivosPdfRegistro($raw);
            if (empty($nombres)) {
                continue;
            }

            $jsonNuevo = json_encode($nombres, JSON_UNESCAPED_UNICODE);
            if ($jsonNuevo === false || $jsonNuevo === $raw) {
                continue;
            }

            $this->teenModel->updateTeen($id, ['archivos_pdf' => $jsonNuevo]);
            $corregidos++;
        }

        return $corregidos;
    }

    private function recuperarArchivosFaltantesTeen(): array {
        $directorioPrincipal = $this->obtenerDirectorioMaterialesTeen();
        $this->migrarMaterialesTeensLegacy($directorioPrincipal);
        $jsonReparados = $this->repararRegistrosArchivosPdfEnBd();

        $recuperados = 0;
        $faltan = 0;
        $nombresVistos = [];

        foreach ((array)$this->teenModel->getAll() as $material) {
            $idMaterial = (int)($material['id'] ?? 0);
            $nombres = $this->parsearArchivosPdfRegistro($material['archivos_pdf'] ?? '');
            $nombresActualizados = $nombres;
            $huboCambioNombres = false;

            foreach ($nombres as $idx => $nombre) {
                if ($nombre === '' || isset($nombresVistos[$nombre])) {
                    continue;
                }
                $nombresVistos[$nombre] = true;

                $rutaPrincipalArchivo = $directorioPrincipal . DIRECTORY_SEPARATOR . $nombre;
                $existiaEnPrincipal = is_file($rutaPrincipalArchivo);
                $ruta = $this->resolverRutaPdfTeen($nombre);

                if ($ruta === null || !is_file($ruta)) {
                    $faltan++;
                    continue;
                }

                if (!$existiaEnPrincipal && is_file($rutaPrincipalArchivo)) {
                    $recuperados++;
                } elseif (!$existiaEnPrincipal) {
                    $nombreEnDisco = basename($ruta);
                    if ($nombreEnDisco !== '' && $nombreEnDisco !== $nombre) {
                        $nombresActualizados[$idx] = $nombreEnDisco;
                        $huboCambioNombres = true;
                        $recuperados++;
                    }
                }
            }

            if ($huboCambioNombres && $idMaterial > 0) {
                $jsonNuevo = json_encode(array_values(array_unique($nombresActualizados)), JSON_UNESCAPED_UNICODE);
                if ($jsonNuevo !== false) {
                    $this->teenModel->updateTeen($idMaterial, ['archivos_pdf' => $jsonNuevo]);
                }
            }
        }

        return [
            'recuperados' => $recuperados,
            'faltan' => $faltan,
            'json_reparados' => $jsonReparados,
        ];
    }

    private function contarPdfsEnDirectorio(string $directorio): int {
        if (!is_dir($directorio)) {
            return 0;
        }

        $total = 0;
        foreach (@scandir($directorio) ?: [] as $archivo) {
            if ($archivo === '.' || $archivo === '..') {
                continue;
            }
            if (is_file($directorio . DIRECTORY_SEPARATOR . $archivo)
                && strtolower((string)pathinfo($archivo, PATHINFO_EXTENSION)) === 'pdf') {
                $total++;
            }
        }

        return $total;
    }

    /**
     * Acción admin: buscar PDF en carpetas legacy / uploads y copiarlos a teens.
     */
    public function recuperarArchivos() {
        if (!AuthController::puede('teen:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $resultado = $this->recuperarArchivosFaltantesTeen();
        $recuperados = (int)($resultado['recuperados'] ?? 0);
        $faltan = (int)($resultado['faltan'] ?? 0);
        $jsonReparados = (int)($resultado['json_reparados'] ?? 0);

        if ($faltan === 0) {
            $mensaje = 'Todos los PDF del módulo están disponibles.';
            if ($recuperados > 0) {
                $mensaje = 'Se emparejaron ' . $recuperados . ' archivo(s) con la base de datos. ' . $mensaje;
            }
            if ($jsonReparados > 0) {
                $mensaje .= ' Se corrigieron ' . $jsonReparados . ' registro(s) en BD.';
            }
            $tipo = 'success';
        } elseif ($recuperados > 0 || $jsonReparados > 0) {
            $mensaje = 'Se emparejaron ' . $recuperados . ' archivo(s)';
            if ($jsonReparados > 0) {
                $mensaje .= ' y se corrigieron ' . $jsonReparados . ' registro(s) en BD';
            }
            $mensaje .= '. Aún faltan ' . $faltan . ': el nombre en disco debe coincidir (ej. PRE-HA-Intro-Maestro.pdf) o sube de nuevo desde Material Teens.';
            $tipo = 'success';
        } else {
            $mensaje = 'Hay ' . $faltan . ' PDF en BD sin archivo en el servidor. Verifica que estén en public/uploads/teens con el mismo nombre (o similar, ej. sin prefijo de fecha). Carpeta: ' . $this->obtenerDirectorioMaterialesTeen();
            $tipo = 'error';
        }

        $this->redirect('teen&mensaje=' . urlencode($mensaje) . '&tipo=' . $tipo);
    }

    private function urlVerPdfTeen(string $nombreArchivo, bool $soloEmbed = false): string {
        $ruta = $this->normalizarRutaArchivoTeen($nombreArchivo);
        if ($ruta === '') {
            $ruta = basename($nombreArchivo);
        }

        $url = PUBLIC_URL . 'index.php?url=teen/verPdf&archivo=' . rawurlencode($ruta);
        if ($soloEmbed) {
            $url .= '&embed=1';
        }

        return $url;
    }

    private function urlPublicaPdfTeen(string $nombreArchivo): string {
        $ruta = $this->normalizarRutaArchivoTeen($nombreArchivo);
        if ($ruta === '') {
            $ruta = basename($nombreArchivo);
        }

        $partes = array_map('rawurlencode', explode('/', $ruta));

        return rtrim(PUBLIC_URL, '/') . '/uploads/teens/' . implode('/', $partes);
    }

    private function servirPdfTeen(string $rutaFisica, string $nombreArchivo): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $tamano = (int)@filesize($rutaFisica);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . addslashes($nombreArchivo) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Accept-Ranges: bytes');
        if ($tamano > 0) {
            header('Content-Length: ' . $tamano);
        }

        $fp = fopen($rutaFisica, 'rb');
        if ($fp === false) {
            throw new RuntimeException('No se pudo abrir el PDF.');
        }

        fpassthru($fp);
        fclose($fp);
        exit;
    }

    private function normalizarTextoMayusculas($valor) {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        $valor = preg_replace('/\s+/', ' ', $valor);
        return function_exists('mb_strtoupper') ? mb_strtoupper($valor, 'UTF-8') : strtoupper($valor);
    }

    private function redirigirRegistroMenor($mensaje, $tipo = 'error', array $old = []) {
        $params = array_merge([
            'url' => 'teen/registro-menores',
            'mensaje' => $mensaje,
            'tipo' => $tipo
        ], $old);

        header('Location: ' . PUBLIC_URL . 'index.php?' . http_build_query($params));
        exit;
    }

    private function redirigirRegistroPublico($mensaje, $tipo = 'error', array $old = [], $codigo = '') {
        $params = array_merge([
            'url' => 'teen/registro-publico',
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'codigo' => $codigo
        ], $old);

        header('Location: ' . PUBLIC_URL . 'index.php?' . http_build_query($params));
        exit;
    }

    private function buildAbsolutePublicUrl($route) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $scheme = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]));
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(PUBLIC_URL, '/');

        return $scheme . '://' . $host . $base . '/index.php?url=' . urlencode($route);
    }

    private function normalizarCodigoRegistro($codigo) {
        $codigo = trim((string)$codigo);
        $codigo = strtoupper($codigo);
        $codigo = preg_replace('/[^A-Z0-9\-]/', '', $codigo);
        return $codigo;
    }

    private function getFechaDomingoSemana(?DateTimeInterface $fechaReferencia = null) {
        $base = $fechaReferencia ? DateTimeImmutable::createFromInterface($fechaReferencia) : new DateTimeImmutable('today');
        if ($base === false) {
            $base = new DateTimeImmutable('today');
        }

        $diaSemana = (int)$base->format('w');
        if ($diaSemana > 0) {
            $base = $base->modify('-' . $diaSemana . ' days');
        }

        return $base->format('Y-m-d');
    }

    private function resolverGrupoMenor(array $menor) {
        $edad = (int)($menor['edad'] ?? $menor['Edad'] ?? 0);
        return ($edad <= 9) ? 'kids' : 'teen';
    }

    private function obtenerPrefijoCodigoKids() {
        return 'KS';
    }

    private function generarCodigoRegistroUnico() {
        for ($i = 0; $i < 15; $i++) {
            $numero = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codigo = 'TN-' . date('ymd') . '-' . $numero;
            if (!$this->teenModel->existeCodigoRegistro($codigo)) {
                return $codigo;
            }
        }

        throw new Exception('No fue posible generar un código único. Intenta nuevamente.');
    }

    private function generarCodigoSemanalUnico(array $menor, ?DateTimeInterface $fechaReferencia = null) {
        $fechaDomingo = $this->getFechaDomingoSemana($fechaReferencia);
        $grupo = $this->resolverGrupoMenor($menor);
        $prefijo = $grupo === 'kids'
            ? $this->obtenerPrefijoCodigoKids()
            : 'TNS';

        for ($i = 0; $i < 120; $i++) {
            $numero = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codigo = $prefijo . substr($numero, -2);
            if (!$this->teenModel->existeCodigoSemanal($codigo, $fechaDomingo)) {
                return $codigo;
            }
        }

        throw new Exception('No fue posible generar un código semanal corto único para esta semana.');
    }

    private function obtenerOCrearAsistenciaSemanal($idMenor) {
        $idMenor = (int)$idMenor;
        if ($idMenor <= 0) {
            throw new Exception('ID de menor inválido para registrar asistencia semanal.');
        }

        $menor = $this->teenModel->getMenorRegistradoById($idMenor);
        if (empty($menor)) {
            throw new Exception('No se encontró la información del menor para generar el código semanal.');
        }

        $existente = $this->teenModel->getAsistenciaSemanalActualByMenor($idMenor);
        $codigoSemanal = $this->generarCodigoSemanalUnico($menor);

        if (!empty($existente)) {
            $ok = $this->teenModel->actualizarCodigoAsistenciaSemanal((int)$existente['id'], $codigoSemanal);
            if (!$ok) {
                throw new Exception('No se pudo renovar el código semanal.');
            }
        } else {
            $ok = $this->teenModel->registrarAsistenciaSemanal($idMenor, $codigoSemanal);
            if (!$ok) {
                throw new Exception('No se pudo registrar la asistencia semanal.');
            }
        }

        $asistencia = $this->teenModel->getAsistenciaSemanalActualByMenor($idMenor);
        if (empty($asistencia)) {
            throw new Exception('No se encontró la asistencia semanal del registro.');
        }

        return [
            'asistencia' => $asistencia,
            'fue_nueva' => empty($existente),
            'fue_renovada' => !empty($existente),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $materiales
     * @return array{0: array<int, array<string, mixed>>, 1: int, 2: int, 3: int}
     */
    private function prepararMaterialesParaVista(array $materiales, string $directorioMateriales): array {
        $vistasPorArchivo = [];
        try {
            $vistasPorArchivo = $this->obtenerConteoVistasTeen();
        } catch (Throwable $e) {
            $vistasPorArchivo = [];
        }

        $totalArchivosRegistrados = 0;
        $totalArchivosOk = 0;

        foreach ($materiales as &$material) {
            $archivos = $this->parsearArchivosPdfRegistro($material['archivos_pdf'] ?? '');
            $material['archivos'] = [];
            $pesoTotal = 0;
            $vistasTotales = 0;
            $fechaUltima = 0;

            foreach ($archivos as $nombreArchivo) {
                $nombreArchivo = $this->normalizarRutaArchivoTeen((string)$nombreArchivo);
                if ($nombreArchivo === '') {
                    continue;
                }

                $totalArchivosRegistrados++;
                $ruta = $this->resolverRutaPdfTeen($nombreArchivo);
                $existe = $ruta !== null && is_file($ruta);
                if ($existe) {
                    $totalArchivosOk++;
                }

                $rutaPublicaRelativa = str_replace('\\', '/', $directorioMateriales . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $nombreArchivo));
                $enCarpetaPublica = is_file($rutaPublicaRelativa);

                $infArchivo = [
                    'nombre' => $nombreArchivo,
                    'url' => $this->urlVerPdfTeen($nombreArchivo),
                    'url_preview' => $enCarpetaPublica
                        ? $this->urlPublicaPdfTeen($nombreArchivo)
                        : $this->urlVerPdfTeen($nombreArchivo, true),
                    'url_embed' => $this->urlVerPdfTeen($nombreArchivo, true),
                    'existe' => $existe,
                    'peso_kb' => $existe ? round(((int)@filesize($ruta)) / 1024, 2) : 0,
                    'fecha_mod' => $existe ? (@filemtime($ruta) ?: 0) : 0,
                ];

                $claveVista = basename($nombreArchivo);
                $material['archivos'][] = $infArchivo;
                $pesoTotal += $infArchivo['peso_kb'];
                $vistasTotales += (int)($vistasPorArchivo[$claveVista] ?? $vistasPorArchivo[$nombreArchivo] ?? 0);
                $fechaUltima = max($fechaUltima, $infArchivo['fecha_mod']);
            }

            $material['peso_total_kb'] = $pesoTotal;
            $material['vistas_totales'] = $vistasTotales;
            $material['fecha_ultima'] = $fechaUltima;
            $archivosLista = $material['archivos'] ?? [];
            $material['archivos_ok'] = count(array_filter($archivosLista, static fn($a) => !empty($a['existe'])));
            $material['archivos_total'] = count($archivosLista);
        }
        unset($material);

        return [$materiales, $totalArchivosRegistrados, $totalArchivosOk, count($materiales)];
    }

    /**
     * @param array<int, array<string, mixed>> $materiales
     * @return array{
     *   calendario: array<int, array{nombre: string, semanas: array<int, array<string, mixed>|null>}>,
     *   sin_clasificar: array<int, array<string, mixed>>
     * }
     */
    private function construirCalendarioMateriales(int $anio, array $materiales): array {
        $mapa = [];
        $mapaDecoracion = [];
        $sinClasificar = [];

        foreach ($materiales as $material) {
            $matAnio = (int)($material['anio'] ?? 0);
            $mes = (int)($material['mes'] ?? 0);
            $semana = (int)($material['semana_mes'] ?? 0);

            if ($matAnio === $anio && $mes >= 1 && $mes <= 12 && Teen::esSemanaDecoracion($semana)) {
                if (!isset($mapaDecoracion[$mes])) {
                    $mapaDecoracion[$mes] = $material;
                }
                continue;
            }

            if ($matAnio === $anio && $mes >= 1 && $mes <= 12 && $semana >= 1 && $semana <= Teen::semanasPorMes()) {
                if (!isset($mapa[$mes][$semana])) {
                    $mapa[$mes][$semana] = $material;
                }
                continue;
            }

            if ($mes <= 0 || $semana <= 0 || $matAnio <= 0) {
                $sinClasificar[] = $material;
            }
        }

        $calendario = [];
        foreach (Teen::nombresMeses() as $numMes => $nombreMes) {
            $semanas = [];
            for ($s = 1; $s <= Teen::semanasPorMes(); $s++) {
                $semanas[$s] = $mapa[$numMes][$s] ?? null;
            }
            $calendario[$numMes] = [
                'nombre' => $nombreMes,
                'tema_mes' => '',
                'decoracion' => $mapaDecoracion[$numMes] ?? null,
                'semanas' => $semanas,
            ];
        }

        usort($sinClasificar, static function ($a, $b) {
            return ((int)($b['fecha_ultima'] ?? 0)) <=> ((int)($a['fecha_ultima'] ?? 0));
        });

        return [
            'calendario' => $calendario,
            'sin_clasificar' => $sinClasificar,
        ];
    }

    private function puedeGestionarMaterialTeensCompleto(): bool {
        return AuthController::esAdministrador()
            || AuthController::puede('teen:editar')
            || AuthController::puede('teen:crear');
    }

    /**
     * @param array<int, array{nombre: string, semanas: array<int, array<string, mixed>|null>}> $calendario
     * @return array<int, array{nombre: string, semanas: array<int, array<string, mixed>|null>}>
     */
    private function filtrarCalendarioMaterialPorAcceso(array $calendario, bool $gestionCompleta, int $anio): array {
        if ($gestionCompleta) {
            return $calendario;
        }

        $mesActual = (int)date('n');
        $semanaActual = Teen::semanaActualDelMes();
        if ((int)date('Y') !== $anio || !isset($calendario[$mesActual])) {
            return [];
        }

        $mesData = $calendario[$mesActual];

        return [
            $mesActual => [
                'nombre' => (string)($mesData['nombre'] ?? Teen::nombreMes($mesActual)),
                'tema_mes' => (string)($mesData['tema_mes'] ?? ''),
                'decoracion' => $mesData['decoracion'] ?? null,
                'semanas' => [
                    $semanaActual => $mesData['semanas'][$semanaActual] ?? null,
                ],
            ],
        ];
    }

    private function anexarTemasMesAlCalendario(array $calendario, int $anio): array {
        $temasPorMes = $this->teenModel->getTemasMesPorAnio($anio);
        foreach ($calendario as $numMes => $mesData) {
            $calendario[$numMes]['tema_mes'] = $temasPorMes[(int)$numMes] ?? '';
        }

        return $calendario;
    }

    private function persistirTemaMesDesdePost(int $anio, int $mes): void {
        if (!array_key_exists('tema_mes', $_POST)) {
            return;
        }

        $temaMes = trim((string)($_POST['tema_mes'] ?? ''));
        $this->teenModel->guardarTemaMes($anio, $mes, $temaMes);
    }

    /**
     * Pantalla principal del módulo.
     * GET: lista materiales
     * POST: sube nuevo PDF
     */
    /**
     * Subir carpeta completa de un mes (subcarpetas semana-1 … semana-5 con PDF).
     */
    public function subirMes() {
        if (!AuthController::puede('teen:crear')) {
            $this->redirect('teen&mensaje=' . urlencode('No tienes permiso para subir material.') . '&tipo=error');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('teen');
            return;
        }

        try {
            $anio = (int)($_POST['anio'] ?? date('Y'));
            $mes = (int)($_POST['mes'] ?? 0);
            if ($mes < 1 || $mes > 12) {
                throw new Exception('Selecciona un mes válido (1-12).');
            }
            if ($anio < 2020 || $anio > 2100) {
                throw new Exception('Año inválido.');
            }

            if (!isset($_FILES['archivo_pdf'])) {
                throw new Exception('No se recibieron archivos. Selecciona la carpeta del mes.');
            }

            $archivos = $this->normalizarArchivosPdfSubidos($_FILES['archivo_pdf']);
            $rutasRelativas = $_POST['ruta_relativa'] ?? [];
            if (!is_array($rutasRelativas)) {
                $rutasRelativas = [$rutasRelativas];
            }

            $porDestino = [];
            $erroresSubida = [];
            $omitidos = 0;

            for ($i = 0; $i < count($archivos['name']); $i++) {
                $nombreArchivo = (string)($archivos['name'][$i] ?? '');
                if (($archivos['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    continue;
                }

                $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
                if ($extension !== 'pdf') {
                    $omitidos++;
                    continue;
                }

                $rutaRelativa = trim((string)($rutasRelativas[$i] ?? $nombreArchivo));
                $clasificacion = $this->clasificarRutaMaterialCarpetaMes($rutaRelativa);
                if ($clasificacion === null) {
                    $erroresSubida[] = 'Sin destino (semana o decoración): ' . htmlspecialchars($rutaRelativa);
                    continue;
                }

                $claveDestino = ($clasificacion['tipo'] ?? '') === 'decoracion'
                    ? 'decoracion'
                    : 'semana-' . (int)($clasificacion['semana'] ?? 0);

                $porDestino[$claveDestino][] = [
                    'name' => $nombreArchivo,
                    'tmp_name' => $archivos['tmp_name'][$i],
                    'size' => $archivos['size'][$i],
                    'error' => $archivos['error'][$i],
                ];
            }

            if ($porDestino === []) {
                $detalle = $erroresSubida !== []
                    ? implode('; ', $erroresSubida)
                    : 'Usa subcarpetas semana-1, semana-2, decoracion, etc. con PDF dentro.';
                throw new Exception('No se encontraron PDF válidos en la carpeta. ' . $detalle);
            }

            ksort($porDestino, SORT_STRING);

            $totalArchivos = 0;
            $destinosPublicados = [];

            foreach ($porDestino as $claveDestino => $listaArchivos) {
                $esDecoracion = $claveDestino === 'decoracion';
                $semanaMes = $esDecoracion
                    ? Teen::semanaDecoracion()
                    : (int)substr($claveDestino, strlen('semana-'));

                if (!$esDecoracion) {
                    $this->validarMesSemanaMaterial($mes, $semanaMes);
                    $directorioDestino = $this->obtenerDirectorioSemanaTeen($anio, $mes, $semanaMes);
                    $prefijoRelativo = $this->prefijoRelativoSemanaTeen($anio, $mes, $semanaMes);
                } else {
                    $directorioDestino = $this->obtenerDirectorioDecoracionTeen($anio, $mes);
                    $prefijoRelativo = $this->prefijoRelativoDecoracionTeen($anio, $mes);
                }

                $archivosSubidos = [];

                foreach ($listaArchivos as $archivoTemp) {
                    try {
                        $archivosSubidos[] = $this->subirPdf($archivoTemp, $directorioDestino, $prefijoRelativo);
                    } catch (Exception $e) {
                        $erroresSubida[] = htmlspecialchars((string)($archivoTemp['name'] ?? 'archivo')) . ': ' . $e->getMessage();
                    }
                }

                if ($archivosSubidos === []) {
                    continue;
                }

                $this->persistirMaterialSemanaTeen($anio, $mes, $semanaMes, $archivosSubidos);
                $totalArchivos += count($archivosSubidos);
                $destinosPublicados[] = $esDecoracion ? 'decoración' : ('semana ' . $semanaMes);
            }

            if ($totalArchivos === 0) {
                throw new Exception('No se pudieron publicar archivos. ' . implode('; ', $erroresSubida));
            }

            $mensaje = $totalArchivos . ' PDF publicado(s) en '
                . Teen::nombreMes($mes) . ' ' . $anio . ' ('
                . implode(', ', $destinosPublicados) . ').';
            if ($omitidos > 0) {
                $mensaje .= ' (' . $omitidos . ' archivo(s) omitido(s) por no ser PDF)';
            }
            if ($erroresSubida !== []) {
                $mensaje .= ' Avisos: ' . implode('; ', array_slice($erroresSubida, 0, 5));
                if (count($erroresSubida) > 5) {
                    $mensaje .= '…';
                }
            }

            $this->persistirTemaMesDesdePost($anio, $mes);
            $temaGuardado = trim((string)($_POST['tema_mes'] ?? ''));
            if ($temaGuardado !== '') {
                $mensaje .= ' Tema del mes guardado.';
            }

            $this->redirect('teen&anio=' . $anio . '&mes=' . $mes . '&mensaje=' . urlencode($mensaje) . '&tipo=success');
        } catch (Exception $e) {
            $this->redirect('teen&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
        }
    }

    public function index() {
        if (!AuthController::puedeVerMaterialTeens()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $directorioMateriales = $this->obtenerDirectorioMaterialesTeen();
        $this->migrarMaterialesTeensLegacy($directorioMateriales);

        $mensaje = (string)($_GET['mensaje'] ?? '');
        $tipo = (string)($_GET['tipo'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && AuthController::puede('teen:editar')) {
            $sync = $this->recuperarArchivosFaltantesTeen();
            $rec = (int)($sync['recuperados'] ?? 0);
            $jsonFix = (int)($sync['json_reparados'] ?? 0);
            if ($mensaje === '' && ($rec > 0 || $jsonFix > 0)) {
                $mensaje = 'Material actualizado: ' . $rec . ' archivo(s) vinculado(s) al servidor.';
                if ($jsonFix > 0) {
                    $mensaje .= ' (' . $jsonFix . ' registro(s) corregido(s) en BD)';
                }
                $tipo = 'success';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!AuthController::puede('teen:crear')) {
                $this->redirect('teen&mensaje=' . urlencode('No tienes permiso para subir material.') . '&tipo=error');
                return;
            }

            try {
                $anio = (int)($_POST['anio'] ?? date('Y'));
                $mes = (int)($_POST['mes'] ?? 0);
                $semanaMes = (int)($_POST['semana_mes'] ?? 0);
                $descripcion = trim((string)($_POST['descripcion'] ?? ''));
                $tituloManual = trim((string)($_POST['titulo'] ?? ''));
                $profesor = $this->normalizarProfesorMaterial($_POST);

                $this->validarMesSemanaMaterial($mes, $semanaMes);
                if ($anio < 2020 || $anio > 2100) {
                    throw new Exception('Año inválido.');
                }

                if (!isset($_FILES['archivo_pdf'])) {
                    throw new Exception('No se recibieron archivos PDF.');
                }

                $archivos = $this->normalizarArchivosPdfSubidos($_FILES['archivo_pdf']);

                $directorioSemana = $this->obtenerDirectorioSemanaTeen($anio, $mes, $semanaMes);
                $prefijoRelativo = $this->prefijoRelativoSemanaTeen($anio, $mes, $semanaMes);
                $archivosSubidos = [];
                $erroresSubida = [];

                for ($i = 0; $i < count($archivos['name']); $i++) {
                    try {
                        $archivoTemp = [
                            'name' => $archivos['name'][$i],
                            'tmp_name' => $archivos['tmp_name'][$i],
                            'size' => $archivos['size'][$i],
                            'error' => $archivos['error'][$i],
                        ];
                        $archivosSubidos[] = $this->subirPdf($archivoTemp, $directorioSemana, $prefijoRelativo);
                    } catch (Exception $e) {
                        $erroresSubida[] = htmlspecialchars((string)$archivos['name'][$i]) . ': ' . $e->getMessage();
                    }
                }

                if ($archivosSubidos === []) {
                    throw new Exception('No se pudieron subir los archivos. ' . implode(', ', $erroresSubida));
                }

                $this->persistirMaterialSemanaTeen(
                    $anio,
                    $mes,
                    $semanaMes,
                    $archivosSubidos,
                    $tituloManual,
                    $descripcion,
                    $profesor
                );

                $mensaje = count($archivosSubidos) . ' archivo(s) publicado(s) en '
                    . Teen::nombreMes($mes) . ', semana ' . $semanaMes . '.';
                if ($profesor['profesor_nombre'] !== '') {
                    $mensaje .= ' Maestro: ' . $profesor['profesor_nombre'] . '.';
                }
                if ($erroresSubida !== []) {
                    $mensaje .= ' Errores: ' . implode('; ', $erroresSubida);
                }

                $this->redirect('teen&anio=' . $anio . '&mes=' . $mes . '&semana=' . $semanaMes . '&mensaje=' . urlencode($mensaje) . '&tipo=success');
            } catch (Exception $e) {
                $this->redirect('teen&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
            }
            return;
        }

        $gestionCompleta = $this->puedeGestionarMaterialTeensCompleto();

        $anioSeleccionado = (int)($_GET['anio'] ?? date('Y'));
        if (!$gestionCompleta) {
            $anioSeleccionado = (int)date('Y');
        } elseif ($anioSeleccionado < 2020 || $anioSeleccionado > 2100) {
            $anioSeleccionado = (int)date('Y');
        }

        $mesAbierto = (int)($_GET['mes'] ?? 0);
        $semanaAbierta = (int)($_GET['semana'] ?? 0);
        if (!$gestionCompleta) {
            $mesAbierto = (int)date('n');
            $semanaAbierta = Teen::semanaActualDelMes();
        } else {
            if ($mesAbierto < 1 || $mesAbierto > 12) {
                $mesAbierto = 0;
            }
            if ($semanaAbierta < 1 || $semanaAbierta > Teen::semanasPorMes()) {
                $semanaAbierta = 0;
            }
        }

        $materialesRaw = $this->teenModel->getMaterialesPorAnio($anioSeleccionado);
        $materialesLegacy = array_values(array_filter(
            (array)$this->teenModel->getAll(),
            static function ($row) use ($anioSeleccionado) {
                $anio = (int)($row['anio'] ?? 0);
                $mes = (int)($row['mes'] ?? 0);
                return $anio !== $anioSeleccionado || $mes <= 0;
            }
        ));
        $materialesTodos = array_merge($materialesRaw, $materialesLegacy);

        [$materiales, $totalArchivosRegistrados, $totalArchivosOk, $totalModulos] = $this->prepararMaterialesParaVista(
            $materialesTodos,
            $directorioMateriales
        );

        $estructura = $this->construirCalendarioMateriales($anioSeleccionado, $materiales);
        $calendario = $this->filtrarCalendarioMaterialPorAcceso(
            $estructura['calendario'],
            $gestionCompleta,
            $anioSeleccionado
        );
        $calendario = $this->anexarTemasMesAlCalendario($calendario, $anioSeleccionado);
        $materialesSinClasificar = $gestionCompleta ? $estructura['sin_clasificar'] : [];

        $this->view('teen/lista', [
            'materiales' => $materiales,
            'calendario' => $calendario,
            'materiales_sin_clasificar' => $materialesSinClasificar,
            'anio_seleccionado' => $anioSeleccionado,
            'mes_abierto' => $mesAbierto,
            'semana_abierta' => $semanaAbierta,
            'gestion_completa' => $gestionCompleta,
            'semana_actual' => Teen::semanaActualDelMes(),
            'mes_actual' => (int)date('n'),
            'nombres_meses' => Teen::nombresMeses(),
            'semanas_por_mes' => Teen::semanasPorMes(),
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'total_modulos' => $totalModulos,
            'total_archivos_faltantes' => max(0, $totalArchivosRegistrados - $totalArchivosOk),
            'total_archivos_registrados' => $totalArchivosRegistrados,
            'total_archivos_ok' => $totalArchivosOk,
            'pdfs_en_carpeta' => $this->contarPdfsRecursivo($directorioMateriales),
            'puede_subir' => AuthController::puede('teen:crear'),
            'puede_editar' => AuthController::puede('teen:editar'),
            'puede_eliminar' => AuthController::puede('teen:eliminar'),
            'solo_ver_material' => AuthController::puedeVerMaterialExistente() && !AuthController::puede('teen:ver'),
            'url_buscar_profesor' => public_app_url('teen/buscarAcudientes'),
        ]);
    }

    private function contarPdfsRecursivo(string $directorio): int {
        if (!is_dir($directorio)) {
            return 0;
        }

        $total = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $archivo) {
            if ($archivo->isFile() && strtolower((string)pathinfo($archivo->getFilename(), PATHINFO_EXTENSION)) === 'pdf') {
                $total++;
            }
        }

        return $total;
    }

    public function guardarTemaMes() {
        if (!AuthController::puede('teen:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('teen');
            return;
        }

        try {
            $anio = (int)($_POST['anio'] ?? 0);
            $mes = (int)($_POST['mes'] ?? 0);
            $temaMes = trim((string)($_POST['tema_mes'] ?? ''));

            if ($anio < 2020 || $anio > 2100) {
                throw new Exception('Año inválido.');
            }
            if ($mes < 1 || $mes > 12) {
                throw new Exception('Mes inválido.');
            }

            $this->teenModel->guardarTemaMes($anio, $mes, $temaMes);

            $mensaje = $temaMes !== ''
                ? 'Tema de ' . Teen::nombreMes($mes) . ' guardado: ' . $temaMes . '.'
                : 'Tema de ' . Teen::nombreMes($mes) . ' eliminado.';

            $this->redirect('teen&anio=' . $anio . '&mes=' . $mes . '&mensaje=' . urlencode($mensaje) . '&tipo=success');
        } catch (Exception $e) {
            $this->redirect('teen&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
        }
    }

    public function asignarProfesorSemana() {
        if (!AuthController::puede('teen:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('teen&mensaje=' . urlencode('Método no permitido') . '&tipo=error');
            return;
        }

        try {
            $anio = (int)($_POST['anio'] ?? 0);
            $mes = (int)($_POST['mes'] ?? 0);
            $semanaMes = (int)($_POST['semana_mes'] ?? 0);
            $idMaterial = (int)($_POST['id_material'] ?? 0);
            $profesor = $this->normalizarProfesorMaterial($_POST);

            if ($profesor['profesor_nombre'] === '') {
                throw new Exception('Indica el nombre del maestro.');
            }

            $material = null;
            if ($idMaterial > 0) {
                $material = $this->teenModel->getById($idMaterial);
            } elseif ($anio > 0 && $mes > 0 && $semanaMes > 0) {
                $this->validarMesSemanaMaterial($mes, $semanaMes);
                $material = $this->teenModel->getMaterialPorSemana($anio, $mes, $semanaMes);
            }

            if (empty($material)) {
                if ($anio <= 0 || $mes <= 0 || $semanaMes <= 0) {
                    throw new Exception('Indica año, mes y semana.');
                }
                $this->validarMesSemanaMaterial($mes, $semanaMes);
                $idNuevo = (int)$this->teenModel->create([
                    'titulo' => $this->tituloAutomaticoSemana($anio, $mes, $semanaMes),
                    'descripcion' => null,
                    'archivos_pdf' => json_encode([]),
                    'anio' => $anio,
                    'mes' => $mes,
                    'semana_mes' => $semanaMes,
                    'id_profesor' => $profesor['id_profesor'] > 0 ? $profesor['id_profesor'] : null,
                    'profesor_nombre' => $profesor['profesor_nombre'],
                ]);
                if ($idNuevo <= 0) {
                    throw new Exception('No se pudo crear el registro de la semana.');
                }
            } else {
                $anio = (int)($material['anio'] ?? $anio);
                $mes = (int)($material['mes'] ?? $mes);
                $semanaMes = (int)($material['semana_mes'] ?? $semanaMes);
                $this->teenModel->actualizarProfesorMaterial(
                    (int)$material['id'],
                    $profesor['id_profesor'],
                    $profesor['profesor_nombre']
                );
            }

            $mensaje = 'Maestro asignado: ' . $profesor['profesor_nombre'] . '.';
            $this->redirect('teen&anio=' . $anio . '&mes=' . $mes . '&semana=' . $semanaMes . '&mensaje=' . urlencode($mensaje) . '&tipo=success');
        } catch (Exception $e) {
            $this->redirect('teen&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
        }
    }

    public function registroMenores() {
        if (!AuthController::puede('teen:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $urlRegistro = $this->buildAbsolutePublicUrl('teen/registro-publico');
        $urlConsulta = $this->buildAbsolutePublicUrl('teen/consulta-codigo');

        $this->view('teen/formulario', [
            'ministerios' => $this->ministerioModel->getAll(),
            'registros' => $this->teenModel->getMenoresRegistrados(),
            'mensaje' => $_GET['mensaje'] ?? '',
            'tipo' => $_GET['tipo'] ?? '',
            'url_registro' => $urlRegistro,
            'url_consulta' => $urlConsulta,
            'old' => [
                'nombre_menor' => (string)($_GET['nombre_menor'] ?? ''),
                'acudiente_busqueda' => (string)($_GET['acudiente_busqueda'] ?? ''),
                'id_acudiente' => (string)($_GET['id_acudiente'] ?? ''),
                'telefono_contacto' => (string)($_GET['telefono_contacto'] ?? ''),
                'fecha_nacimiento' => (string)($_GET['fecha_nacimiento'] ?? ''),
                'edad' => (string)($_GET['edad'] ?? ''),
                'id_ministerio' => (string)($_GET['id_ministerio'] ?? ''),
                'asiste_celula' => (string)($_GET['asiste_celula'] ?? ''),
                'barrio' => (string)($_GET['barrio'] ?? '')
            ]
        ]);
    }

    public function guardarMenor() {
        if (!AuthController::puede('teen:crear')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('teen/registro-menores');
            return;
        }

        $nombreMenor = $this->normalizarTextoMayusculas($_POST['nombre_menor'] ?? '');
        $acudienteBusqueda = $this->normalizarTextoMayusculas($_POST['acudiente_busqueda'] ?? '');
        $idAcudiente = (int)($_POST['id_acudiente'] ?? 0);
        $telefonoContacto = trim((string)($_POST['telefono_contacto'] ?? ''));
        $telefonoContacto = preg_replace('/[^0-9+\s\-\(\)]/', '', $telefonoContacto);
        $fechaNacimiento = trim((string)($_POST['fecha_nacimiento'] ?? ''));
        $edadRaw = trim((string)($_POST['edad'] ?? ''));
        $edad = ctype_digit($edadRaw) ? (int)$edadRaw : -1;
        if ($fechaNacimiento !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimiento)) {
            try {
                $fechaNac = new DateTime($fechaNacimiento);
                $hoy = new DateTime('today');
                $edadCalculada = $fechaNac->diff($hoy)->y;
                if ($edadCalculada >= 0) {
                    $edad = (int)$edadCalculada;
                    $edadRaw = (string)$edad;
                }
            } catch (Throwable $e) {
                // Se valida más abajo.
            }
        }
        $idMinisterioRaw = trim((string)($_POST['id_ministerio'] ?? ''));
        $idMinisterio = ctype_digit($idMinisterioRaw) ? (int)$idMinisterioRaw : 0;
        $idMenorExistenteRaw = trim((string)($_POST['id_menor_existente'] ?? ''));
        $idMenorExistente = ctype_digit($idMenorExistenteRaw) ? (int)$idMenorExistenteRaw : 0;
        $asisteCelulaRaw = strtoupper(trim((string)($_POST['asiste_celula'] ?? '')));
        $barrio = $this->normalizarTextoMayusculas($_POST['barrio'] ?? '');

        $old = [
            'nombre_menor' => $nombreMenor,
            'acudiente_busqueda' => $acudienteBusqueda,
            'id_acudiente' => (string)$idAcudiente,
            'telefono_contacto' => $telefonoContacto,
            'fecha_nacimiento' => $fechaNacimiento,
            'edad' => $edadRaw,
            'id_ministerio' => $idMinisterioRaw,
            'id_menor_existente' => (string)$idMenorExistente,
            'asiste_celula' => $asisteCelulaRaw,
            'barrio' => $barrio
        ];

        $errores = [];

        if ($nombreMenor === '') {
            $errores[] = 'El nombre y apellido del menor es obligatorio.';
        }

        if ($idAcudiente <= 0) {
            $errores[] = 'Debes seleccionar un acudiente válido de la lista.';
        }

        $acudiente = $idAcudiente > 0 ? $this->personaModel->getById($idAcudiente) : null;
        if ($idAcudiente > 0 && empty($acudiente)) {
            $errores[] = 'El acudiente seleccionado no existe en la base de personas.';
        }

        $nombreAcudiente = '';
        if (!empty($acudiente)) {
            $nombreAcudiente = $this->normalizarTextoMayusculas(trim((string)($acudiente['Nombre'] ?? '') . ' ' . (string)($acudiente['Apellido'] ?? '')));
            $acudienteBusqueda = $nombreAcudiente !== '' ? $nombreAcudiente : $acudienteBusqueda;
            $old['acudiente_busqueda'] = $acudienteBusqueda;

            $telefonoBase = trim((string)($acudiente['Telefono'] ?? ''));
            if ($telefonoBase !== '') {
                $telefonoContacto = $telefonoBase;
                $old['telefono_contacto'] = $telefonoContacto;
            }
        }

        if ($telefonoContacto === '') {
            $errores[] = 'El número de contacto es obligatorio.';
        }

        if ($fechaNacimiento === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimiento)) {
            $errores[] = 'La fecha de nacimiento es obligatoria.';
        }

        if ($edad < 0 || $edad > 17) {
            $errores[] = 'La edad debe estar entre 0 y 17 años.';
        }

        if ($idMinisterio <= 0) {
            $errores[] = 'Debes seleccionar un ministerio.';
        }

        if (!in_array($asisteCelulaRaw, ['SI', 'SÍ', 'NO'], true)) {
            $errores[] = 'Debes indicar si asiste a célula.';
        }

        if (!empty($errores)) {
            $this->redirigirRegistroMenor(implode(' ', $errores), 'error', $old);
        }

        $data = [
            'codigo_registro' => $this->generarCodigoRegistroUnico(),
            'nombre_menor' => $nombreMenor,
            'id_acudiente' => $idAcudiente,
            'nombre_acudiente' => $nombreAcudiente !== '' ? $nombreAcudiente : $acudienteBusqueda,
            'telefono_contacto' => $telefonoContacto,
            'fecha_nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
            'edad' => $edad,
            'id_ministerio' => $idMinisterio,
            'asiste_celula' => in_array($asisteCelulaRaw, ['SI', 'SÍ'], true) ? 1 : 0,
            'barrio' => $barrio !== '' ? $barrio : null
        ];

        try {
            $idMenor = (int)$this->teenModel->createMenor($data);
            if ($idMenor <= 0) {
                throw new Exception('No se pudo guardar el registro del menor.');
            }

            $this->redirigirRegistroMenor('Menor registrado correctamente. Código asignado: ' . $data['codigo_registro'], 'success');
        } catch (Throwable $e) {
            $this->redirigirRegistroMenor('Error al guardar el menor: ' . $e->getMessage(), 'error', $old);
        }
    }

    public function registroPublico() {
        $this->view('teen/registro_publico', [
            'ministerios' => $this->ministerioModel->getAll(),
            'mensaje' => (string)($_GET['mensaje'] ?? ''),
            'tipo' => (string)($_GET['tipo'] ?? ''),
            'codigo' => (string)($_GET['codigo'] ?? ''),
            'old' => [
                'documento' => (string)($_GET['documento'] ?? ''),
                'nombre_menor' => (string)($_GET['nombre_menor'] ?? ''),
                'nombre_acudiente' => (string)($_GET['nombre_acudiente'] ?? ''),
                'telefono_contacto' => (string)($_GET['telefono_contacto'] ?? ''),
                'fecha_nacimiento' => (string)($_GET['fecha_nacimiento'] ?? ''),
                'edad' => (string)($_GET['edad'] ?? ''),
                'id_ministerio' => (string)($_GET['id_ministerio'] ?? ''),
                'id_menor_existente' => (string)($_GET['id_menor_existente'] ?? ''),
                'es_nuevo' => (string)($_GET['es_nuevo'] ?? '1'),
                'invitado_por' => (string)($_GET['invitado_por'] ?? ''),
                'asiste_celula' => (string)($_GET['asiste_celula'] ?? ''),
                'barrio' => (string)($_GET['barrio'] ?? '')
            ]
        ]);
    }

    public function guardarMenorPublico() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . PUBLIC_URL . 'index.php?url=teen/registro-publico');
            exit;
        }

        $nombreMenor = $this->normalizarTextoMayusculas($_POST['nombre_menor'] ?? '');
        $documento = trim((string)($_POST['documento'] ?? ''));
        $documentoNorm = Teen::normalizarDocumentoMenor($documento);
        $idMenorExistente = (int)($_POST['id_menor_existente'] ?? 0);
        $esNuevoRaw = trim((string)($_POST['es_nuevo'] ?? '1'));
        $esNuevo = !in_array(strtolower($esNuevoRaw), ['0', 'false', 'no'], true);
        $invitadoPor = $this->normalizarTextoMayusculas($_POST['invitado_por'] ?? '');
        $nombreAcudiente = $this->normalizarTextoMayusculas($_POST['nombre_acudiente'] ?? '');
        $telefonoContacto = trim((string)($_POST['telefono_contacto'] ?? ''));
        $telefonoNormalizado = preg_replace('/\D+/', '', $telefonoContacto);
        $telefonoContacto = preg_replace('/[^0-9+\s\-\(\)]/', '', $telefonoContacto);
        $fechaNacimiento = trim((string)($_POST['fecha_nacimiento'] ?? ''));
        $edadRaw = trim((string)($_POST['edad'] ?? ''));
        $edad = ctype_digit($edadRaw) ? (int)$edadRaw : -1;
        $idMinisterioRaw = trim((string)($_POST['id_ministerio'] ?? ''));
        $idMinisterio = ctype_digit($idMinisterioRaw) ? (int)$idMinisterioRaw : 0;
        $asisteCelulaRaw = strtoupper(trim((string)($_POST['asiste_celula'] ?? '')));
        $barrio = $this->normalizarTextoMayusculas($_POST['barrio'] ?? '');

        $registroPrevio = $this->teenModel->resolverMenorRegistrado(
            $idMenorExistente,
            $documentoNorm,
            $nombreMenor,
            $fechaNacimiento,
            $telefonoNormalizado,
            $nombreAcudiente
        );
        $esRegistroExistente = !empty($registroPrevio);

        if ($esRegistroExistente) {
            $esNuevo = false;
            $idMenorExistente = (int)($registroPrevio['id'] ?? $idMenorExistente);

            if ($nombreMenor === '') {
                $nombreMenor = Teen::normalizarNombreMenor((string)($registroPrevio['nombre_menor'] ?? ''));
            }
            if ($fechaNacimiento === '') {
                $fechaNacimiento = trim((string)($registroPrevio['fecha_nacimiento'] ?? ''));
            }
            if ($idMinisterio <= 0) {
                $idMinisterio = (int)($registroPrevio['id_ministerio'] ?? 0);
                $idMinisterioRaw = (string)$idMinisterio;
            }
            if (!in_array($asisteCelulaRaw, ['SI', 'SÍ', 'NO'], true)) {
                $asisteCelulaRaw = !empty($registroPrevio['asiste_celula']) ? 'SI' : 'NO';
            }
            if ($barrio === '') {
                $barrio = Teen::normalizarNombreMenor((string)($registroPrevio['barrio'] ?? ''));
            }
            if ($edad < 0) {
                $edad = (int)($registroPrevio['edad'] ?? -1);
                $edadRaw = $edad >= 0 ? (string)$edad : $edadRaw;
            }
        }

        if ($fechaNacimiento !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimiento)) {
            try {
                $fechaNac = new DateTime($fechaNacimiento);
                $hoy = new DateTime('today');
                $edadCalculada = $fechaNac->diff($hoy)->y;
                if ($edadCalculada >= 0) {
                    $edad = (int)$edadCalculada;
                    $edadRaw = (string)$edad;
                }
            } catch (Throwable $e) {
                // Se valida más abajo.
            }
        }

        $old = [
            'documento' => $documento,
            'nombre_menor' => $nombreMenor,
            'nombre_acudiente' => $nombreAcudiente,
            'telefono_contacto' => $telefonoContacto,
            'fecha_nacimiento' => $fechaNacimiento,
            'edad' => $edadRaw,
            'id_ministerio' => $idMinisterioRaw,
            'id_menor_existente' => (string)$idMenorExistente,
            'es_nuevo' => $esNuevo ? '1' : '0',
            'invitado_por' => $invitadoPor,
            'asiste_celula' => $asisteCelulaRaw,
            'barrio' => $barrio
        ];

        $errores = [];
        if ($documentoNorm === '' || strlen($documentoNorm) < 5) {
            $errores[] = 'El documento del niño es obligatorio (mínimo 5 caracteres).';
        }

        if ($esRegistroExistente) {
            if ($nombreAcudiente === '') {
                $errores[] = 'El nombre del acudiente de este domingo es obligatorio.';
            }
            if ($telefonoNormalizado === '' || strlen($telefonoNormalizado) !== 10) {
                $errores[] = 'El teléfono del acudiente es obligatorio y debe tener 10 dígitos.';
            }
        } else {
            if ($nombreMenor === '') {
                $errores[] = 'El nombre y apellido del menor es obligatorio.';
            }
            if ($nombreAcudiente === '') {
                $errores[] = 'El nombre del acudiente es obligatorio.';
            }
            if ($telefonoNormalizado === '' || strlen($telefonoNormalizado) !== 10) {
                $errores[] = 'El teléfono de contacto es obligatorio y debe tener 10 dígitos.';
            }
            if ($fechaNacimiento === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimiento)) {
                $errores[] = 'La fecha de nacimiento es obligatoria.';
            }
            if ($edad < 0 || $edad > 17) {
                $errores[] = 'La edad debe estar entre 0 y 17 años.';
            }
            if ($idMinisterio <= 0) {
                $errores[] = 'Debes seleccionar un ministerio.';
            }
            if (!in_array($asisteCelulaRaw, ['SI', 'SÍ', 'NO'], true)) {
                $errores[] = 'Debes indicar si asiste a célula.';
            }
            if ($esNuevo && $invitadoPor === '') {
                $errores[] = 'Indica quién invitó al niño (obligatorio para niños nuevos).';
            }
        }

        if ($esRegistroExistente) {
            $esNuevo = false;
            $old['es_nuevo'] = '0';
            $old['id_menor_existente'] = (string)$idMenorExistente;
        }

        if (!empty($errores)) {
            $this->redirigirRegistroPublico(implode(' ', $errores), 'error', $old);
        }

        if ($fechaNacimiento !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaNacimiento) && ($edad < 0 || $edad > 17)) {
            try {
                $fechaNac = new DateTime($fechaNacimiento);
                $hoy = new DateTime('today');
                $edad = (int)$fechaNac->diff($hoy)->y;
                $edadRaw = (string)$edad;
            } catch (Throwable $e) {
                // Ignorar.
            }
        }

        try {
            $registroExistente = $registroPrevio ?: null;

            $esExistente = !empty($registroExistente);
            $idMenor = 0;

            if ($esExistente) {
                $idMenor = (int)($registroExistente['id'] ?? 0);
                if ($idMenor <= 0) {
                    throw new Exception('Se encontró un registro existente inválido.');
                }

                if ($documentoNorm !== '') {
                    $this->teenModel->vincularDocumentoMenor($idMenor, $documentoNorm);
                }

                // Actualizar solo acudiente de este domingo (el niño no se duplica: clave = documento).
                $this->teenModel->updateMenorById($idMenor, [
                    'nombre_acudiente' => $nombreAcudiente,
                    'telefono_contacto' => $telefonoNormalizado,
                ]);
            } elseif ($documentoNorm !== '' && $this->teenModel->documentoMenorExiste($documentoNorm)) {
                $this->redirigirRegistroPublico(
                    'Este documento ya está registrado. Busca al niño por documento para renovar su código semanal.',
                    'error',
                    $old
                );
            } else {
                try {
                    $codigoRegistro = $this->generarCodigoRegistroUnico();
                    $data = [
                        'codigo_registro' => $codigoRegistro,
                        'nombre_menor' => $nombreMenor,
                        'documento' => $documentoNorm,
                        'id_acudiente' => 0,
                        'nombre_acudiente' => $nombreAcudiente,
                        'telefono_contacto' => $telefonoNormalizado,
                        'fecha_nacimiento' => $fechaNacimiento !== '' ? $fechaNacimiento : null,
                        'edad' => $edad,
                        'id_ministerio' => $idMinisterio,
                        'asiste_celula' => in_array($asisteCelulaRaw, ['SI', 'SÍ'], true) ? 1 : 0,
                        'barrio' => $barrio !== '' ? $barrio : null,
                        'es_nuevo' => 1,
                        'invitado_por' => $invitadoPor !== '' ? $invitadoPor : null,
                    ];

                    $idMenor = (int)$this->teenModel->createMenor($data);
                    if ($idMenor <= 0) {
                        throw new Exception('No se pudo guardar el registro del menor.');
                    }
                } catch (Throwable $createError) {
                    $registroExistente = $this->teenModel->resolverMenorRegistrado(
                        0,
                        $documentoNorm,
                        $nombreMenor,
                        $fechaNacimiento,
                        $telefonoNormalizado,
                        $nombreAcudiente
                    );
                    if (!empty($registroExistente)) {
                        $idMenor = (int)($registroExistente['id'] ?? 0);
                        $esExistente = $idMenor > 0;
                        if ($esExistente && $documentoNorm !== '') {
                            $this->teenModel->vincularDocumentoMenor($idMenor, $documentoNorm);
                        }
                    }
                    if (!$esExistente) {
                        throw $createError;
                    }
                }
            }

            if ($idMenor <= 0) {
                throw new Exception('No se pudo identificar el registro del menor.');
            }

            $resultadoAsistencia = $this->obtenerOCrearAsistenciaSemanal($idMenor);
            $asistencia = $resultadoAsistencia['asistencia'];
            $codigoSemana = (string)($asistencia['codigo_semana'] ?? '');

            if ($codigoSemana === '') {
                throw new Exception('No se pudo obtener el código semanal.');
            }

            if ($esExistente) {
                $mensaje = 'Registro renovado. Se generó un código semanal nuevo para este domingo (no se duplicó el niño).';
            } else {
                $mensaje = 'Registro completado correctamente. Este es tu código semanal.';
            }

            $this->redirigirRegistroPublico($mensaje, 'success', [], $codigoSemana);
        } catch (Throwable $e) {
            $this->redirigirRegistroPublico('Error al guardar el menor: ' . $e->getMessage(), 'error', $old);
        }
    }

    public function consultarCodigoPublico() {
        $codigo = $this->normalizarCodigoRegistro($_GET['codigo'] ?? '');
        $registro = null;
        $mensaje = '';
        $tipo = '';

        if ($codigo !== '') {
            $registro = $this->teenModel->getMenorByCodigoSemanal($codigo);
            if (empty($registro)) {
                $registro = $this->teenModel->getMenorByCodigoRegistro($codigo);
            }

            if (empty($registro)) {
                $mensaje = 'No encontramos un registro con ese código.';
                $tipo = 'error';
            }
        }

        $this->view('teen/consulta_publica', [
            'codigo' => $codigo,
            'registro' => $registro,
            'mensaje' => $mensaje,
            'tipo' => $tipo
        ]);
    }

    public function qrRegistroPublico() {
        $this->redirect('teen/codigos');
        return;
    }

    public function codigos() {
        if (!AuthController::puede('teen:ver')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $urlRegistro = $this->buildAbsolutePublicUrl('teen/registro-publico');
        $urlConsulta = $this->buildAbsolutePublicUrl('teen/consulta-codigo');

        $this->view('teen/codigos', [
            'url_registro' => $urlRegistro,
            'url_consulta' => $urlConsulta
        ]);
    }

    public function buscarAcudientes() {
        if (!AuthController::puede('teen:ver')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        header('Content-Type: application/json');

        $term = trim((string)($_GET['term'] ?? ''));
        if (mb_strlen($term, 'UTF-8') < 2) {
            echo json_encode(['success' => false, 'message' => 'Mínimo 2 caracteres']);
            exit;
        }

        try {
            $buscar = '%' . $term . '%';
            $sql = "SELECT p.Id_Persona, p.Nombre, p.Apellido, p.Telefono, m.Nombre_Ministerio
                    FROM persona p
                    LEFT JOIN ministerio m ON m.Id_Ministerio = p.Id_Ministerio
                    WHERE (p.Nombre LIKE ? OR p.Apellido LIKE ? OR CONCAT(COALESCE(p.Nombre, ''), ' ', COALESCE(p.Apellido, '')) LIKE ?)
                      AND (p.Estado_Cuenta = 'Activo' OR p.Estado_Cuenta IS NULL)
                    ORDER BY p.Nombre, p.Apellido
                    LIMIT 20";
            $rows = $this->personaModel->query($sql, [$buscar, $buscar, $buscar]);

            echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'No se pudo buscar acudientes']);
        }
        exit;
    }

    public function buscarMenorPublicoPorDocumento() {
        header('Content-Type: application/json; charset=utf-8');

        $documentoRaw = trim((string)($_GET['documento'] ?? ''));
        $documentoNorm = Teen::normalizarDocumentoMenor($documentoRaw);

        if ($documentoNorm === '' || strlen($documentoNorm) < 3) {
            echo json_encode(['success' => false, 'message' => 'Ingresa al menos 3 caracteres del documento'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $registro = $this->teenModel->getMenorByDocumento($documentoNorm);
            if (!empty($registro)) {
                echo json_encode([
                    'success' => true,
                    'found' => true,
                    'fuente' => 'teen',
                    'es_nuevo' => false,
                    'data' => $this->formatearMenorPublicoBusqueda($registro),
                    'mensaje' => 'Encontramos al niño en registros anteriores. Cargamos todos sus datos. Al guardar solo se renovará el código semanal.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $persona = $this->personaModel->buscarParaInscripcionEscuela($documentoRaw, '', '');
            $nombreCompleto = '';
            $telefonoPersona = '';
            if (!empty($persona)) {
                $nombreCompleto = trim((string)($persona['Nombre'] ?? '') . ' ' . (string)($persona['Apellido'] ?? ''));
                $telefonoPersona = preg_replace('/\D+/', '', (string)($persona['Telefono'] ?? ''));
            }

            $registroTeen = $this->teenModel->resolverMenorRegistrado(
                0,
                $documentoNorm,
                $nombreCompleto,
                '',
                $telefonoPersona,
                ''
            );
            if (!empty($registroTeen)) {
                echo json_encode([
                    'success' => true,
                    'found' => true,
                    'fuente' => 'teen',
                    'es_nuevo' => false,
                    'data' => $this->formatearMenorPublicoBusqueda($registroTeen),
                    'mensaje' => 'Encontramos al niño en registros anteriores (sin documento vinculado). Al guardar solo se renovará el código semanal.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if (!empty($persona)) {
                echo json_encode([
                    'success' => true,
                    'found' => true,
                    'fuente' => 'persona',
                    'es_nuevo' => true,
                    'data' => [
                        'documento' => (string)($persona['Numero_Documento'] ?? $documentoRaw),
                        'nombre_menor' => $nombreCompleto,
                        'telefono_contacto' => $telefonoPersona,
                        'id_ministerio' => (int)($persona['Id_Ministerio'] ?? 0),
                        'nombre_ministerio' => (string)($persona['Nombre_Ministerio'] ?? ''),
                    ],
                    'mensaje' => 'Encontramos al niño en el directorio de personas. Completa los datos faltantes e indica quién lo invitó.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            echo json_encode([
                'success' => true,
                'found' => false,
                'es_nuevo' => true,
                'mensaje' => 'No encontramos este documento. Es un niño nuevo: completa los datos e indica quién lo invitó y a qué ministerio pertenece.',
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('buscarMenorPublicoPorDocumento: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'No se pudo buscar el documento. Intenta de nuevo.'], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    /**
     * @param array<string, mixed> $registro
     * @return array<string, mixed>
     */
    private function formatearMenorPublicoBusqueda(array $registro): array {
        return [
            'id' => (int)($registro['id'] ?? 0),
            'documento' => (string)($registro['documento'] ?? ''),
            'nombre_menor' => (string)($registro['nombre_menor'] ?? ''),
            'nombre_acudiente' => (string)($registro['nombre_acudiente'] ?? ''),
            'telefono_contacto' => preg_replace('/\D+/', '', (string)($registro['telefono_contacto'] ?? '')),
            'fecha_nacimiento' => (string)($registro['fecha_nacimiento'] ?? ''),
            'edad' => (int)($registro['edad'] ?? 0),
            'id_ministerio' => (int)($registro['id_ministerio'] ?? 0),
            'nombre_ministerio' => (string)($registro['Nombre_Ministerio'] ?? ''),
            'asiste_celula' => !empty($registro['asiste_celula']) ? 'SI' : 'NO',
            'barrio' => (string)($registro['barrio'] ?? ''),
            'invitado_por' => (string)($registro['invitado_por'] ?? ''),
            'codigo_registro' => (string)($registro['codigo_registro'] ?? ''),
            'codigo_semana' => (string)($registro['codigo_semana_actual'] ?? ($registro['ultimo_codigo_semana'] ?? '')),
            'total_asistencias' => (int)($registro['total_asistencias'] ?? 0),
            'ya_registrado' => true,
        ];
    }

    public function buscarMenorPublicoPorTelefono() {
        header('Content-Type: application/json');

        $telefonoRaw = trim((string)($_GET['telefono'] ?? ''));
        $telefonoNormalizado = preg_replace('/\D+/', '', $telefonoRaw);

        if ($telefonoNormalizado === '' || strlen($telefonoNormalizado) < 7) {
            echo json_encode(['success' => false, 'message' => 'Ingresa al menos 7 dígitos']);
            exit;
        }

        try {
            $registro = $this->teenModel->getMenorByTelefonoContacto($telefonoNormalizado);
            if (empty($registro)) {
                echo json_encode(['success' => true, 'found' => false]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'found' => true,
                'fuente' => 'teen',
                'es_nuevo' => false,
                'data' => $this->formatearMenorPublicoBusqueda($registro),
                'mensaje' => 'Encontramos este número en registros anteriores.',
            ], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'No se pudo consultar el teléfono']);
        }

        exit;
    }

    /**
     * Abrir PDF y registrar visualización.
     */
    public function verPdf() {
        if (!AuthController::puedeVerMaterialTeens()) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $archivo = $this->normalizarRutaArchivoTeen((string)($_GET['archivo'] ?? ''));
        if ($archivo === '') {
            $this->redirect('teen&mensaje=' . urlencode('Archivo inválido') . '&tipo=error');
            return;
        }

        $ruta = $this->resolverRutaPdfTeen($archivo);
        if ($ruta === null || !is_file($ruta)) {
            $this->redirect('teen&mensaje=' . urlencode('El archivo no está en el servidor. Puede que no se haya subido en producción o haya cambiado de carpeta.') . '&tipo=error');
            return;
        }

        $soloEmbed = isset($_GET['embed']) && (string)$_GET['embed'] === '1';
        $idPersona = (int)($_SESSION['usuario_id'] ?? 0);

        if (!$soloEmbed) {
            try {
                $this->registrarVistaTeen(basename($archivo), $idPersona);
            } catch (Throwable $e) {
                // No bloquear apertura del PDF por fallo de tracking.
            }
        }

        try {
            $this->servirPdfTeen($ruta, $archivo);
        } catch (Throwable $e) {
            error_log('Error sirviendo PDF teen: ' . $e->getMessage());
            $this->redirect('teen&mensaje=' . urlencode('No se pudo abrir el PDF.') . '&tipo=error');
        }
    }

    /**
     * Eliminar módulo completamente (todos sus archivos)
     */
    public function eliminar() {
        if (!AuthController::puede('teen:eliminar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);

        if ($id > 0) {
            $material = $this->teenModel->getById($id);

            if ($material) {
                // Eliminar todos los archivos físicos
                foreach ($this->parsearArchivosPdfRegistro($material['archivos_pdf'] ?? '') as $archivo) {
                    if ($archivo !== '') {
                        $ruta = $this->resolverRutaPdfTeen($archivo);
                        if ($ruta !== null && is_file($ruta)) {
                            @unlink($ruta);
                        }
                    }
                }

                $this->teenModel->deleteTeen($id);
            }
        }

        $this->redirect('teen&mensaje=' . urlencode('Material eliminado correctamente') . '&tipo=success');
    }

    /**
     * AJAX: detalle de quiénes vieron un material.
     */
    public function detalleVistas() {
        if (!AuthController::puedeVerMaterialTeens()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
            exit;
        }

        header('Content-Type: application/json');

        $archivo = basename((string)($_GET['archivo'] ?? ''));
        if ($archivo === '' || strtolower(pathinfo($archivo, PATHINFO_EXTENSION)) !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'Archivo inválido']);
            exit;
        }

        try {
            $this->asegurarTablaVistasTeen();

            global $pdo;
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                echo json_encode(['success' => false, 'message' => 'Error de conexión']);
                exit;
            }

            $sql = "SELECT 
                        tv.id_persona,
                        p.Nombre,
                        p.Apellido,
                        p.Telefono,
                        m.Nombre_Ministerio,
                        tv.total_vistas,
                        tv.fecha_primera_vista,
                        tv.fecha_ultima_vista
                    FROM teen_vista tv
                    LEFT JOIN persona p ON tv.id_persona = p.Id_Persona
                    LEFT JOIN ministerio m ON p.Id_Ministerio = m.Id_Ministerio
                    WHERE tv.archivo = ?
                    ORDER BY tv.fecha_ultima_vista DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$archivo]);
            $vistas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'archivo' => htmlspecialchars($archivo),
                'total_personas' => count($vistas),
                'vistas' => $vistas
            ]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener datos']);
        }
        exit;
    }

    /**
     * Subir y validar PDF.
     */
    private function subirPdf($archivo, $directorio, ?string $prefijoRelativo = null) {
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir archivo. Intente nuevamente.');
        }

        $nombreOriginal = trim((string)($archivo['name'] ?? 'material.pdf'));
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new Exception('Solo se permiten archivos PDF.');
        }

        $tamanio = (int)($archivo['size'] ?? 0);
        if ($tamanio <= 0) {
            throw new Exception('El archivo está vacío.');
        }

        $maxBytes = 20 * 1024 * 1024;
        if ($tamanio > $maxBytes) {
            throw new Exception('El PDF supera el tamaño máximo de 20MB.');
        }

        $tmp = (string)($archivo['tmp_name'] ?? '');

        $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? (string)@finfo_file($finfo, $tmp) : '';
        if ($finfo) {
            @finfo_close($finfo);
        }

        if ($mime !== '' && stripos($mime, 'pdf') === false) {
            throw new Exception('El archivo no es un PDF válido.');
        }

        $base = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $base = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $base);
        $base = preg_replace('/\s+/', '_', (string)$base);
        $base = trim((string)$base, '_-');
        if ($base === '') {
            $base = 'material_teen';
        }

        $nombreFinal = date('Ymd_His') . '_' . substr((string)md5(uniqid((string)mt_rand(), true)), 0, 8) . '_' . $base . '.pdf';
        $destino = rtrim($directorio, '/\\') . DIRECTORY_SEPARATOR . $nombreFinal;

        if (!move_uploaded_file($tmp, $destino)) {
            throw new Exception('No se pudo guardar el PDF en el servidor.');
        }

        if ($prefijoRelativo !== null && trim($prefijoRelativo) !== '') {
            return trim(str_replace('\\', '/', $prefijoRelativo), '/') . '/' . $nombreFinal;
        }

        return $nombreFinal;
    }

    /**
     * Crear tabla de tracking de vistas.
     */
    private function asegurarTablaVistasTeen() {
        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS teen_vista (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    archivo VARCHAR(255) NOT NULL,
                    id_persona INT NOT NULL,
                    total_vistas INT NOT NULL DEFAULT 1,
                    fecha_primera_vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    fecha_ultima_vista DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_archivo_persona (archivo, id_persona),
                    KEY idx_archivo (archivo),
                    KEY idx_persona (id_persona)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $pdo->exec($sql);
    }

    /**
     * Mapa archivo => cantidad de personas distintas que lo vieron.
     */
    private function obtenerConteoVistasTeen() {
        $this->asegurarTablaVistasTeen();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return [];
        }

        $rows = $pdo->query("SELECT archivo, COUNT(DISTINCT id_persona) AS personas_vieron FROM teen_vista GROUP BY archivo")
                    ->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $archivo = (string)($row['archivo'] ?? '');
            if ($archivo === '') {
                continue;
            }
            $map[$archivo] = (int)($row['personas_vieron'] ?? 0);
        }

        return $map;
    }

    /**
     * Registrar visualización por usuario.
     */
    private function registrarVistaTeen($archivo, $idPersona) {
        $archivo = basename((string)$archivo);
        $idPersona = (int)$idPersona;

        if ($archivo === '' || $idPersona <= 0) {
            return;
        }

        $this->asegurarTablaVistasTeen();

        global $pdo;
        if (!isset($pdo) || !($pdo instanceof PDO)) {
            return;
        }

        $sql = "INSERT INTO teen_vista (archivo, id_persona, total_vistas)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE total_vistas = total_vistas + 1, fecha_ultima_vista = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$archivo, $idPersona]);
    }

    /**
     * Editar material: actualizar título, descripción y/o agregar archivos
     */
    public function editar() {
        if (!AuthController::puede('teen:editar')) {
            header('Location: ' . public_app_url('auth/acceso-denegado'));
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $this->redirect('teen&mensaje=' . urlencode('ID inválido') . '&tipo=error');
            return;
        }

        $material = $this->teenModel->getById($id);
        if (!$material) {
            $this->redirect('teen&mensaje=' . urlencode('Material no encontrado') . '&tipo=error');
            return;
        }

        $directorioMateriales = $this->obtenerDirectorioMaterialesTeen();
        $this->migrarMaterialesTeensLegacy($directorioMateriales);

        $anioMat = (int)($material['anio'] ?? 0);
        $mesMat = (int)($material['mes'] ?? 0);
        $semanaMat = (int)($material['semana_mes'] ?? 0);
        if ($anioMat > 0 && $mesMat > 0 && Teen::esSemanaDecoracion($semanaMat)) {
            $directorioUpload = $this->obtenerDirectorioDecoracionTeen($anioMat, $mesMat);
            $prefijoRelativo = $this->prefijoRelativoDecoracionTeen($anioMat, $mesMat);
        } elseif ($anioMat > 0 && $mesMat > 0 && $semanaMat > 0) {
            $directorioUpload = $this->obtenerDirectorioSemanaTeen($anioMat, $mesMat, $semanaMat);
            $prefijoRelativo = $this->prefijoRelativoSemanaTeen($anioMat, $mesMat, $semanaMat);
        } else {
            $directorioUpload = $directorioMateriales;
            $prefijoRelativo = null;
        }

        // Preparar archivos actuales
        $archivosActuales = $this->parsearArchivosPdfRegistro($material['archivos_pdf'] ?? '');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $titulo = trim((string)($_POST['titulo'] ?? ''));
                $descripcion = trim((string)($_POST['descripcion'] ?? ''));
                $archivosAEliminar = isset($_POST['eliminar_archivo']) ? (array)$_POST['eliminar_archivo'] : [];
                $profesor = $this->normalizarProfesorMaterial($_POST);

                if ($titulo === '') {
                    throw new Exception('El título es obligatorio.');
                }

                // Procesar archivos a eliminar
                $archivosActualizados = [];
                foreach ($archivosActuales as $archivoNombre) {
                    if (!in_array($archivoNombre, $archivosAEliminar, true)) {
                        $archivosActualizados[] = $archivoNombre;
                    } else {
                        // Eliminar archivo físico
                        $ruta = $this->resolverRutaPdfTeen((string)$archivoNombre);
                        if ($ruta !== null && is_file($ruta)) {
                            @unlink($ruta);
                        }
                    }
                }

                // Procesar nuevos archivos si los hay
                $nuevosArchivos = [];
                if (isset($_FILES['archivo_pdf']) && !empty($_FILES['archivo_pdf']['name'][0])) {
                    $archivos = $_FILES['archivo_pdf'];
                    
                    // Convertir a array si es un solo archivo
                    if (!is_array($archivos['name'])) {
                        $archivos = [
                            'name' => [$archivos['name']],
                            'tmp_name' => [$archivos['tmp_name']],
                            'size' => [$archivos['size']],
                            'error' => [$archivos['error']]
                        ];
                    }

                    $erroresSubida = [];
                    for ($i = 0; $i < count($archivos['name']); $i++) {
                        try {
                            $archivoTemp = [
                                'name' => $archivos['name'][$i],
                                'tmp_name' => $archivos['tmp_name'][$i],
                                'size' => $archivos['size'][$i],
                                'error' => $archivos['error'][$i]
                            ];
                            $archivoFinal = $this->subirPdf($archivoTemp, $directorioUpload, $prefijoRelativo);
                            $nuevosArchivos[] = $archivoFinal;
                        } catch (Exception $e) {
                            $erroresSubida[] = htmlspecialchars($archivos['name'][$i]) . ': ' . $e->getMessage();
                        }
                    }

                    if (!empty($erroresSubida)) {
                        throw new Exception('Errores al subir archivos: ' . implode(', ', $erroresSubida));
                    }
                }

                // Combinar archivos: mantener los existentes + agregar los nuevos
                $archivosFinales = array_merge($archivosActualizados, $nuevosArchivos);

                if (empty($archivosFinales)) {
                    throw new Exception('Debe haber al menos un archivo. Agregue archivos nuevos antes de eliminar todos.');
                }

                // Actualizar en BD
                $dataUpdate = [
                    'titulo' => $titulo,
                    'descripcion' => $descripcion !== '' ? $descripcion : null,
                    'archivos_pdf' => json_encode($archivosFinales),
                ];
                if ($profesor['profesor_nombre'] !== '' || $profesor['id_profesor'] > 0) {
                    $dataUpdate['id_profesor'] = $profesor['id_profesor'] > 0 ? $profesor['id_profesor'] : null;
                    $dataUpdate['profesor_nombre'] = $profesor['profesor_nombre'] !== '' ? $profesor['profesor_nombre'] : null;
                }

                $this->teenModel->updateTeen($id, $dataUpdate);

                $mensaje = 'Material actualizado correctamente.';
                if (!empty($nuevosArchivos)) {
                    $mensaje .= ' Se agregaron ' . count($nuevosArchivos) . ' archivo(s) nuevo(s).';
                }

                $this->redirect('teen&mensaje=' . urlencode($mensaje) . '&tipo=success');
            } catch (Exception $e) {
                $this->redirect('teen&mensaje=' . urlencode($e->getMessage()) . '&tipo=error');
            }
            return;
        }

        // GET: mostrar formulario
        $this->view('teen/editar', [
            'material' => $material,
            'archivosActuales' => $archivosActuales,
            'directorioMateriales' => $directorioMateriales,
            'url_buscar_profesor' => public_app_url('teen/buscarAcudientes'),
        ]);
    }
}