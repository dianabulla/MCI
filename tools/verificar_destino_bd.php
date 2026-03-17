<?php
require_once __DIR__ . '/../conexion.php';

$row = $pdo->query("SELECT DATABASE() AS db, @@hostname AS host, @@port AS port")->fetch(PDO::FETCH_ASSOC);

echo 'db=' . ($row['db'] ?? '') . PHP_EOL;
echo 'host=' . ($row['host'] ?? '') . PHP_EOL;
echo 'port=' . ($row['port'] ?? '') . PHP_EOL;
