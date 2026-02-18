<?php
/**
 * Generador de contraseñas hasheadas para actualizar en BD
 */

// Cambia esta contraseña por la que quieras
$nueva_contrasena = "sefuerteyvaliente2025";

// Generar hash
$hash = password_hash($nueva_contrasena, PASSWORD_BCRYPT);

echo "<h2>Generar Nueva Contraseña</h2>";
echo "<hr>";
echo "<p><strong>Contraseña:</strong> " . htmlspecialchars($nueva_contrasena) . "</p>";
echo "<p><strong>Hash generado:</strong></p>";
echo "<textarea style='width: 100%; height: 80px; font-family: monospace;'>" . $hash . "</textarea>";
echo "<hr>";
echo "<h3>Instrucciones:</h3>";
echo "<ol>";
echo "<li>Copia el hash generado arriba</li>";
echo "<li>Ve a phpMyAdmin: <a href='https://www.mcimadridcolombia.com:2083/cpsess*/phpMyAdmin' target='_blank'>phpMyAdmin</a></li>";
echo "<li>Abre la tabla <strong>persona</strong></li>";
echo "<li>Busca el registro del usuario <strong>admin</strong></li>";
echo "<li>Edita el campo <strong>Password_Hash</strong> y pega el hash</li>";
echo "<li>Guarda los cambios</li>";
echo "<li>Elimina este archivo (generar_password.php) por seguridad</li>";
echo "</ol>";
echo "<hr>";
echo "<h3>SQL Directo (opcional):</h3>";
echo "<p>O ejecuta este SQL en phpMyAdmin:</p>";
echo "<textarea style='width: 100%; height: 100px; font-family: monospace;'>";
echo "UPDATE persona SET Password_Hash = '$hash' WHERE Usuario = 'admin';";
echo "</textarea>";
?>

<style>
    body {
        font-family: Arial, sans-serif;
        padding: 20px;
        max-width: 800px;
        margin: 0 auto;
    }
    h2 { color: #0078D4; }
    ol { line-height: 2; }
    textarea {
        padding: 10px;
        border: 2px solid #0078D4;
        border-radius: 5px;
    }
</style>
