<?php
/**
 * Archivo de conexión a la base de datos
 * Base de datos: mci
 */

// Configurar zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// La configuración la carga public/index.php antes de este archivo.
// No volver a cargar config aquí: require_once impediría aplicar actualizaciones nuevas.

$host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'localhost');
$port = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306');
$dbname = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'mcimadrid');
$username = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'root');
$password = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: '');

try {
    // Intentar conexión
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Configurar zona horaria de MySQL a Colombia
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    $isLocal = in_array($host, ['localhost', '127.0.0.1'], true);

    // Mostrar mensaje de error detallado
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; margin: 20px; border-radius: 5px;'>";
    echo "<h2>❌ Error de Conexión a la Base de Datos</h2>";
    echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<hr>";
    echo "<h3>🔧 Soluciones:</h3>";
    echo "<ol>";
    if ($isLocal) {
        echo "<li><strong>Verificar que MySQL esté corriendo en XAMPP</strong><br>";
        echo "    - Abre el Panel de Control de XAMPP<br>";
        echo "    - Verifica que MySQL tenga luz verde (Running)</li>";
        echo "<li><strong>Verificar que la base de datos exista</strong><br>";
        echo "    - Ve a: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>";
        echo "    - Verifica que exista la base de datos configurada<br>";
        echo "    - Si no existe, impórtala desde tu script SQL</li>";
    } else {
        echo "<li><strong>Verificar credenciales de hosting</strong><br>";
        echo "    - En producción normalmente NO se usa root<br>";
        echo "    - Usa el usuario, contraseña y base de datos reales del hosting</li>";
        echo "<li><strong>Verificar permisos del usuario MySQL</strong><br>";
        echo "    - El usuario debe tener acceso a la base de datos configurada</li>";
    }
    echo "<li><strong>Verificar usuario y contraseña</strong><br>";
    echo "    - Usuario configurado: <code>$username</code><br>";
    echo "    - Contraseña: " . (empty($password) ? "<code>(vacía)</code>" : "<code>(configurada)</code>") . "<br>";
    echo "    - Si phpMyAdmin te pide contraseña, actualiza el archivo conexion.php</li>";
    echo "<li><strong>Verificar puerto MySQL</strong><br>";
    echo "    - Puerto configurado: <code>$port</code><br>";
    echo "    - Verifica en XAMPP que MySQL esté usando el puerto 3306</li>";
    echo "</ol>";
    echo "<hr>";
    echo "<p><strong>Archivo de configuración:</strong> <code>" . htmlspecialchars(__FILE__) . "</code></p>";
    echo "</div>";
    die();
}
