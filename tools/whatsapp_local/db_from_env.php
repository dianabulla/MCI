<?php
declare(strict_types=1);

/**
 * Conexión PDO usando tools/whatsapp_local/.env (misma BD que el worker Node).
 */
function wa_load_env_file(string $path): array {
    if (!is_file($path)) {
        throw new RuntimeException('No se encontró el archivo .env en: ' . $path);
    }

    $vars = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        throw new RuntimeException('No se pudo leer: ' . $path);
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));
        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        $vars[$key] = $value;
    }

    return $vars;
}

function wa_bool_env(string $value, bool $default = false): bool {
    if ($value === '') {
        return $default;
    }
    $normalized = strtolower(trim($value));
    return in_array($normalized, ['1', 'true', 'yes', 'si', 'sí'], true);
}

function wa_pdo_from_env(?string $envPath = null): PDO {
    $envPath = $envPath ?? __DIR__ . '/.env';
    $env = wa_load_env_file($envPath);

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $dbname = $env['DB_NAME'] ?? 'mcimadrid';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $sslMode = strtolower(trim((string)($env['DB_SSL_MODE'] ?? 'disabled')));
    if ($sslMode === 'required' || $sslMode === 'true' || $sslMode === '1') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = wa_bool_env(
            (string)($env['DB_SSL_REJECT_UNAUTHORIZED'] ?? 'false'),
            false
        );
    }

    $pdo = new PDO($dsn, $user, $pass, $options);

    $tzSql = trim((string)($env['WA_DB_TIME_ZONE_SQL'] ?? "SET time_zone = '-05:00'"));
    if ($tzSql !== '' && wa_bool_env((string)($env['WA_DB_SET_TIMEZONE'] ?? '1'), true)) {
        $pdo->exec($tzSql);
    }

    return $pdo;
}
