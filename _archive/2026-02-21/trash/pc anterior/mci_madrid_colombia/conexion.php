<?php
/**
 * Archivo de conexión a la base de datos
 * Base de datos: mci
 */

// Configurar zona horaria de Colombia
date_default_timezone_set('America/Bogota');

$host = 'localhost';
$port = '3306'; // Puerto estándar de MySQL
$dbname = 'u694856656_mci';
$username = 'u694856656_mci';
$password = 'MCImadridTorres2025**';

try {
    // Intentar conexión
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Configurar zona horaria de MySQL a Colombia
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    // Mostrar mensaje de error detallado
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h2>❌ Error de Conexión a la Base de Datos</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr>";
    echo "<h3>🔧 Soluciones:</h3>";
    echo "<ol>";
    echo "<li><strong>Verificar que MySQL esté corriendo en XAMPP</strong><br>";
    echo "    - Abre el Panel de Control de XAMPP<br>";
    echo "    - Verifica que MySQL tenga luz verde (Running)</li>";
    echo "<li><strong>Verificar que la base de datos 'mci' exista</strong><br>";
    echo "    - Ve a: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>";
    echo "    - Verifica que exista la base de datos 'mci'<br>";
    echo "    - Si no existe, impórtala desde el archivo mci.sql</li>";
    echo "<li><strong>Verificar usuario y contraseña</strong><br>";
    echo "    - Usuario configurado: <code>$username</code><br>";
    echo "    - Contraseña: " . (empty($password) ? "<code>(vacía)</code>" : "<code>(configurada)</code>") . "<br>";
    echo "    - Si phpMyAdmin te pide contraseña, actualiza el archivo conexion.php</li>";
    echo "<li><strong>Verificar puerto MySQL</strong><br>";
    echo "    - Puerto configurado: <code>$port</code><br>";
    echo "    - Verifica en XAMPP que MySQL esté usando el puerto 3306</li>";
    echo "</ol>";
    echo "<hr>";
    echo "<p><strong>Archivo de configuración:</strong> <code>c:\\xampp\\htdocs\\mci_madrid_colombia\\conexion.php</code></p>";
    echo "</div>";
    die();
}
