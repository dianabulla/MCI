<?php

/**
 * Documentos adjuntos — remisión Servicio Social.
 */
class ServicioSocialDocumentos {
    public const EXTENSIONES = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx'];
    public const TAMANO_MAX = 8 * 1024 * 1024;
    public const INPUT_NAME = 'documentos_remision';

    /**
     * @param mixed $filesInput
     * @return array<int, string>
     */
    public static function validarUpload($filesInput): array {
        $archivos = self::normalizarArchivosMultiples(is_array($filesInput) ? $filesInput : []);
        if ($archivos === []) {
            return [];
        }

        $errores = [];
        foreach ($archivos as $archivo) {
            $nombre = trim((string)($archivo['name'] ?? ''));
            $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
            $size = (int)($archivo['size'] ?? 0);

            if ($error === UPLOAD_ERR_NO_FILE || $nombre === '') {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $errores[] = 'Error al subir «' . $nombre . '».';
                continue;
            }
            if ($size <= 0 || $size > self::TAMANO_MAX) {
                $errores[] = '«' . $nombre . '» supera el máximo de 8 MB.';
                continue;
            }
            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
            if (!in_array($ext, self::EXTENSIONES, true)) {
                $errores[] = 'Tipo no permitido: «' . $nombre . '». Use PDF, JPG, PNG, WEBP, DOC o DOCX.';
            }
        }

        return $errores;
    }

    /**
     * @param mixed $filesInput
     * @return array{guardados:int, errores:array<int,string>, documentos:array<int,array{nombre:string,archivo:string,url:string,fecha:string}>}
     */
    public function adjuntarDesdeUpload(int $idCita, $filesInput, array $existentes = []): array {
        $idCita = (int)$idCita;
        if ($idCita <= 0) {
            return ['guardados' => 0, 'errores' => [], 'documentos' => $existentes];
        }

        $archivos = self::normalizarArchivosMultiples(is_array($filesInput) ? $filesInput : []);
        if ($archivos === []) {
            return ['guardados' => 0, 'errores' => [], 'documentos' => $existentes];
        }

        $dir = self::directorioCita($idCita);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception('No se pudo crear la carpeta de documentos.');
        }

        $guardados = 0;
        $errores = [];
        $lista = $existentes;

        foreach ($archivos as $archivo) {
            $nombreOriginal = trim((string)($archivo['name'] ?? ''));
            $tmp = (string)($archivo['tmp_name'] ?? '');
            $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
            $size = (int)($archivo['size'] ?? 0);

            if ($error === UPLOAD_ERR_NO_FILE || $nombreOriginal === '' || $tmp === '') {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                $errores[] = 'Error al subir «' . $nombreOriginal . '».';
                continue;
            }
            if ($size <= 0 || $size > self::TAMANO_MAX) {
                $errores[] = '«' . $nombreOriginal . '» supera el máximo de 8 MB.';
                continue;
            }

            $ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
            if (!in_array($ext, self::EXTENSIONES, true)) {
                $errores[] = 'Tipo no permitido: «' . $nombreOriginal . '».';
                continue;
            }

            $slug = preg_replace('/[^a-zA-Z0-9._-]+/', '_', pathinfo($nombreOriginal, PATHINFO_FILENAME)) ?? 'documento';
            $slug = trim(substr((string)$slug, 0, 60), '_');
            if ($slug === '') {
                $slug = 'documento';
            }

            $nombreFinal = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '_' . $slug . '.' . $ext;
            $destino = $dir . DIRECTORY_SEPARATOR . $nombreFinal;

            if (!is_uploaded_file($tmp) || !@move_uploaded_file($tmp, $destino)) {
                $errores[] = 'No se pudo guardar «' . $nombreOriginal . '».';
                continue;
            }

            $lista[] = [
                'nombre' => $nombreOriginal,
                'archivo' => $nombreFinal,
                'url' => self::construirUrlArchivo($idCita, $nombreFinal, ''),
                'fecha' => date('Y-m-d H:i'),
            ];
            $guardados++;
        }

        return ['guardados' => $guardados, 'errores' => $errores, 'documentos' => $lista];
    }

    /**
     * @param mixed $raw
     * @return array<int, array{nombre:string,archivo:string,url:string,fecha:string}>
     */
    public static function decodificar($raw, int $idCita = 0): array {
        if (is_array($raw)) {
            $decoded = $raw;
        } else {
            $json = trim((string)$raw);
            if ($json === '') {
                return [];
            }
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                return [];
            }
        }

        $out = [];
        foreach ($decoded as $item) {
            if (!is_array($item)) {
                continue;
            }
            $archivo = trim((string)($item['archivo'] ?? ''));
            $nombre = trim((string)($item['nombre'] ?? $archivo));
            if ($archivo === '') {
                continue;
            }
            $out[] = [
                'nombre' => $nombre !== '' ? $nombre : $archivo,
                'archivo' => $archivo,
                'url' => self::construirUrlArchivo($idCita, $archivo, trim((string)($item['url'] ?? ''))),
                'fecha' => trim((string)($item['fecha'] ?? '')),
            ];
        }

        return $out;
    }

    /**
     * @param mixed $raw
     * @return array<int, array{nombre:string,archivo:string,url:string,fecha:string}>
     */
    public static function obtenerParaCita(int $idCita, $raw): array {
        $idCita = (int)$idCita;
        if ($idCita <= 0) {
            return [];
        }

        $porArchivo = [];
        foreach (self::decodificar($raw, $idCita) as $doc) {
            $clave = (string)($doc['archivo'] ?? '');
            if ($clave !== '') {
                $porArchivo[$clave] = $doc;
            }
        }
        foreach (self::listarDesdeDirectorio($idCita) as $doc) {
            $clave = (string)($doc['archivo'] ?? '');
            if ($clave === '') {
                continue;
            }
            if (!isset($porArchivo[$clave])) {
                $porArchivo[$clave] = $doc;
            } elseif (trim((string)($porArchivo[$clave]['url'] ?? '')) === '' && trim((string)($doc['url'] ?? '')) !== '') {
                $porArchivo[$clave]['url'] = $doc['url'];
            }
        }

        return array_values($porArchivo);
    }

    /**
     * @return array<int, array{nombre:string,archivo:string,url:string,fecha:string}>
     */
    public static function listarDesdeDirectorio(int $idCita): array {
        $idCita = (int)$idCita;
        if ($idCita <= 0) {
            return [];
        }

        $dir = self::directorioCita($idCita);
        if (!is_dir($dir)) {
            return [];
        }

        $archivos = scandir($dir);
        if (!is_array($archivos)) {
            return [];
        }

        $out = [];
        foreach ($archivos as $nombreArchivo) {
            if ($nombreArchivo === '.' || $nombreArchivo === '..' || $nombreArchivo === '.gitkeep') {
                continue;
            }
            $ruta = $dir . DIRECTORY_SEPARATOR . $nombreArchivo;
            if (!is_file($ruta)) {
                continue;
            }
            $mtime = @filemtime($ruta);
            $out[] = [
                'nombre' => $nombreArchivo,
                'archivo' => $nombreArchivo,
                'url' => self::construirUrlArchivo($idCita, $nombreArchivo, ''),
                'fecha' => $mtime ? date('Y-m-d H:i', (int)$mtime) : '',
            ];
        }

        usort($out, static function (array $a, array $b): int {
            return strcmp((string)($a['archivo'] ?? ''), (string)($b['archivo'] ?? ''));
        });

        return $out;
    }

    public static function construirUrlArchivo(int $idCita, string $archivo, string $urlExistente = ''): string {
        $urlExistente = trim($urlExistente);
        if ($urlExistente !== '') {
            return $urlExistente;
        }
        $archivo = trim($archivo);
        $idCita = (int)$idCita;
        if ($archivo === '' || $idCita <= 0) {
            return '';
        }
        $basePublic = rtrim((string)(defined('PUBLIC_URL') ? PUBLIC_URL : ''), '/');
        return $basePublic . '/uploads/servicio_social/' . $idCita . '/' . rawurlencode($archivo);
    }

    /**
     * @param mixed $filesInput
     */
    public static function tieneArchivosEnUpload($filesInput): bool {
        return self::normalizarArchivosMultiples(is_array($filesInput) ? $filesInput : []) !== [];
    }

    public static function directorioCita(int $idCita): string {
        $root = defined('ROOT') ? ROOT : dirname(__DIR__, 2);
        return $root . '/public/uploads/servicio_social/' . (int)$idCita;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<int, array<string, mixed>>
     */
    private static function normalizarArchivosMultiples(array $input): array {
        if ($input === [] || !isset($input['name'])) {
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
}
