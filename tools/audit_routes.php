<?php
/**
 * One-off: lista rutas ?url= referenciadas en views/Controllers sin entrada en routes.php
 * Ejecutar: php tools/audit_routes.php
 */
$root = dirname(__DIR__);
$routes = require $root . '/app/Config/routes.php';
$registered = array_fill_keys(array_keys($routes), true);

$dirs = [$root . '/views', $root . '/app/Controllers'];
$found = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $f) {
        /** @var SplFileInfo $f */
        $ext = strtolower($f->getExtension());
        if (!in_array($ext, ['php', 'js'], true)) {
            continue;
        }
        $pathname = $f->getPathname();
        $rel = str_replace('\\', '/', substr($pathname, strlen($root) + 1));
        if (strpos($rel, '_archive') !== false) {
            continue;
        }
        $c = file_get_contents($pathname);
        if ($c === false) {
            continue;
        }
        if (!preg_match_all('/url=([^&\s"\']+)/', $c, $m)) {
            continue;
        }
        foreach ($m[1] as $u) {
            $u = trim($u, '/');
            $base = preg_replace('/[<$\{].*/', '', $u);
            $base = rtrim($base, '/');
            if ($base === '' || strpos($base, '$') !== false || strpos($base, '{') !== false) {
                continue;
            }
            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_\\-\\/]*$/', $base)) {
                continue;
            }
            $found[$base][$rel] = true;
        }
    }
}

$missing = [];
foreach (array_keys($found) as $path) {
    if (!isset($registered[$path])) {
        $missing[$path] = array_keys($found[$path]);
    }
}
ksort($missing);

foreach ($missing as $p => $files) {
    echo $p . PHP_EOL;
    foreach (array_slice($files, 0, 5) as $ff) {
        echo '  ' . $ff . PHP_EOL;
    }
    if (count($files) > 5) {
        echo '  ... +' . (count($files) - 5) . PHP_EOL;
    }
}
echo 'TOTAL missing: ' . count($missing) . PHP_EOL;
