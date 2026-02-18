<?php
// Script temporal para generar hashes de contraseñas

echo "Hashes generados con password_hash():\n\n";

$passwords = [
    'admin123' => 'admin',
    'pastor123' => 'leonardo',
    'diana123' => 'diana'
];

foreach ($passwords as $password => $usuario) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "Usuario: $usuario\n";
    echo "Contraseña: $password\n";
    echo "Hash: $hash\n";
    echo "Verificación: " . (password_verify($password, $hash) ? 'OK' : 'FAIL') . "\n\n";
}

echo "\n--- SQL UPDATE Statements ---\n\n";

foreach ($passwords as $password => $usuario) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    echo "UPDATE persona SET Contrasena = '$hash' WHERE Usuario = '$usuario';\n";
}
