<?php
// Receptor ultra simple - sin validaciones
file_put_contents('test_received.txt', date('Y-m-d H:i:s') . " - Recibido\n", FILE_APPEND);

$data = file_get_contents('php://input');
file_put_contents('received.jpg', $data);

echo '{"ok":true}';
