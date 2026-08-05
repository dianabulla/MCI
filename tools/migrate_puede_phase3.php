<?php
declare(strict_types=1);

$roots = [
    dirname(__DIR__) . '/app/Controllers',
    dirname(__DIR__) . '/views',
];
$skipFiles = ['AuthController.php'];

$pattern = '/AuthController::tienePermiso\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/u';
$total = 0;

foreach ($roots as $root) {
    if (!is_dir($root)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    foreach ($it as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        if (strpos($path, 'public - copia') !== false) {
            continue;
        }
        if (in_array(basename($path), $skipFiles, true)) {
            continue;
        }
        $content = file_get_contents($path);
        if (!is_string($content)) {
            continue;
        }
        $count = 0;
        $new = preg_replace_callback($pattern, static function (array $m): string {
            return "AuthController::puede('" . $m[1] . ':' . $m[2] . "')";
        }, $content, -1, $count);
        if ($count > 0 && is_string($new)) {
            file_put_contents($path, $new);
            echo str_replace(dirname(__DIR__) . '/', '', $path) . " ({$count})\n";
            $total += $count;
        }
    }
}

echo "Reemplazos: {$total}\n";
