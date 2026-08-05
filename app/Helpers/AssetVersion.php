<?php
/**
 * Versión de assets estáticos (CSS/JS) para evitar caché obsoleto tras despliegues.
 */
if (!function_exists('app_asset_version')) {
    function app_asset_version(): string {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $root = defined('ROOT') ? ROOT : dirname(__DIR__, 2);
        $buildFile = $root . '/public/assets/.build';

        if (is_readable($buildFile)) {
            $fromFile = trim((string)file_get_contents($buildFile));
            if ($fromFile !== '') {
                $cached = preg_replace('/[^a-zA-Z0-9._-]/', '', $fromFile) ?: $fromFile;
                return $cached;
            }
        }

        $env = getenv('APP_ASSET_VERSION');
        if (is_string($env) && trim($env) !== '') {
            $cached = trim($env);
            return $cached;
        }

        $cached = (string)app_asset_version_from_mtime($root);
        return $cached;
    }

    function app_asset_version_from_mtime(string $root): string {
        $dirs = [
            $root . '/public/assets/css',
            $root . '/public/assets/js',
        ];
        $max = 0;

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if (!$file->isFile()) {
                        continue;
                    }
                    $ext = strtolower($file->getExtension());
                    if (!in_array($ext, ['css', 'js'], true)) {
                        continue;
                    }
                    $max = max($max, $file->getMTime());
                }
            } catch (Throwable $e) {
                // Ignorar errores de lectura en hosting restrictivo.
            }
        }

        return $max > 0 ? (string)$max : date('Ymd');
    }

    /**
     * URL de asset con parámetro ?v= para bust de caché del navegador.
     */
    function asset_url(string $relativePath): string {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $base = defined('ASSETS_URL') ? rtrim(ASSETS_URL, '/') : '/assets';
        $v = app_asset_version();

        return $base . '/' . $relativePath . '?v=' . rawurlencode($v);
    }
}
