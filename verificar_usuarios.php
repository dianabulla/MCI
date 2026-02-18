<?php
/**
 * Script para verificar y crear usuarios en la base de datos
 */

// Configuración de base de datos
$host = 'localhost';
$dbname = 'mcimadrid';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Conexión exitosa a la base de datos 'mcimadrid'</h2>";
    
    // Verificar si existe la tabla persona
    $stmt = $pdo->query("SHOW TABLES LIKE 'persona'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red;'>❌ La tabla 'persona' no existe. Debes importar el archivo mci.sql primero.</p>";
        exit;
    }
    
    // Verificar si existen las columnas de autenticación
    $stmt = $pdo->query("SHOW COLUMNS FROM persona LIKE 'Usuario'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:orange;'>⚠️ La tabla 'persona' no tiene el campo 'Usuario'.</p>";
        echo "<p>Ejecutando actualización de la tabla...</p>";
        
        // Agregar campos de autenticación
        $pdo->exec("ALTER TABLE persona ADD COLUMN Usuario VARCHAR(50) UNIQUE NULL AFTER Email");
        $pdo->exec("ALTER TABLE persona ADD COLUMN Contrasena VARCHAR(255) NULL AFTER Usuario");
        $pdo->exec("ALTER TABLE persona ADD COLUMN Estado_Cuenta ENUM('Activo', 'Inactivo', 'Bloqueado') DEFAULT 'Activo' AFTER Contrasena");
        $pdo->exec("ALTER TABLE persona ADD COLUMN Ultimo_Acceso DATETIME NULL AFTER Estado_Cuenta");
        
        echo "<p style='color:green;'>✅ Campos de autenticación agregados correctamente.</p>";
    }
    
    // Verificar usuarios existentes
    echo "<h3>Usuarios existentes en la base de datos:</h3>";
    $stmt = $pdo->query("SELECT Id_Persona, Nombre, Apellido, Usuario, Estado_Cuenta FROM persona WHERE Usuario IS NOT NULL AND Usuario != ''");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($usuarios) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Apellido</th><th>Usuario</th><th>Estado</th></tr>";
        foreach ($usuarios as $user) {
            echo "<tr>";
            echo "<td>{$user['Id_Persona']}</td>";
            echo "<td>{$user['Nombre']}</td>";
            echo "<td>{$user['Apellido']}</td>";
            echo "<td><strong>{$user['Usuario']}</strong></td>";
            echo "<td>{$user['Estado_Cuenta']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ No hay usuarios creados en la base de datos.</p>";
    }
    
    // Verificar si existe alguna persona en la tabla
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM persona");
    $total = $stmt->fetch()['total'];
    
    echo "<h3>Total de personas en la base de datos: $total</h3>";
    
    if ($total == 0) {
        echo "<p style='color:red;'>❌ No hay personas en la base de datos. Debes importar los datos primero.</p>";
        echo "<p>Opciones:</p>";
        echo "<ol>";
        echo "<li>Importar el archivo <strong>mci.sql</strong> completo</li>";
        echo "<li>O crear un usuario admin manualmente con el botón de abajo</li>";
        echo "</ol>";
        
        // Formulario para crear usuario admin
        if (isset($_POST['crear_admin'])) {
            try {
                // Primero verificar y crear roles si no existen
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM rol");
                $totalRoles = $stmt->fetch()['total'];
                
                if ($totalRoles == 0) {
                    echo "<p>Creando roles básicos...</p>";
                    $pdo->exec("INSERT INTO rol (Id_Rol, Nombre_Rol, Descripcion) VALUES 
                        (1, 'Pastor', 'Pastor de la iglesia'),
                        (2, 'Líder de Célula', 'Líder de célula'),
                        (3, 'Miembro', 'Miembro de la iglesia'),
                        (4, 'Visitante', 'Visitante'),
                        (5, 'Servidor', 'Servidor en ministerio'),
                        (6, 'Administrador del Sistema', 'Administrador con acceso total')");
                    echo "<p style='color:green;'>✅ Roles creados correctamente.</p>";
                }
                
                // Crear tabla de permisos si no existe
                $pdo->exec("CREATE TABLE IF NOT EXISTS permisos (
                    Id_Permiso INT PRIMARY KEY AUTO_INCREMENT,
                    Id_Rol INT NOT NULL,
                    Modulo VARCHAR(50) NOT NULL,
                    Puede_Ver BOOLEAN DEFAULT FALSE,
                    Puede_Crear BOOLEAN DEFAULT FALSE,
                    Puede_Editar BOOLEAN DEFAULT FALSE,
                    Puede_Eliminar BOOLEAN DEFAULT FALSE,
                    FOREIGN KEY (Id_Rol) REFERENCES rol(Id_Rol) ON DELETE CASCADE,
                    UNIQUE KEY unique_rol_modulo (Id_Rol, Modulo)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                
                // Insertar permisos para administrador (rol 6)
                echo "<p>Creando permisos para administrador...</p>";
                $pdo->exec("INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) VALUES
                    (6, 'personas', 1, 1, 1, 1),
                    (6, 'celulas', 1, 1, 1, 1),
                    (6, 'ministerios', 1, 1, 1, 1),
                    (6, 'roles', 1, 1, 1, 1),
                    (6, 'eventos', 1, 1, 1, 1),
                    (6, 'peticiones', 1, 1, 1, 1),
                    (6, 'asistencias', 1, 1, 1, 1),
                    (6, 'reportes', 1, 1, 1, 1),
                    (6, 'permisos', 1, 1, 1, 1)
                ON DUPLICATE KEY UPDATE 
                    Puede_Ver = VALUES(Puede_Ver),
                    Puede_Crear = VALUES(Puede_Crear),
                    Puede_Editar = VALUES(Puede_Editar),
                    Puede_Eliminar = VALUES(Puede_Eliminar)");
                echo "<p style='color:green;'>✅ Permisos creados correctamente.</p>";
                
                // Crear persona admin
                $hash = password_hash('admin123', PASSWORD_DEFAULT);
                $sql = "INSERT INTO persona (Nombre, Apellido, Email, Usuario, Contrasena, Estado_Cuenta, Id_Rol) 
                        VALUES ('Administrador', 'Sistema', 'admin@mcimadrid.com', 'admin', ?, 'Activo', 6)";
                $stmt = $pdo->prepare($sql);
                if ($stmt->execute([$hash])) {
                    echo "<p style='color:green;'>✅ Usuario admin creado exitosamente!</p>";
                    echo "<p><strong>Usuario:</strong> admin<br><strong>Contraseña:</strong> admin123</p>";
                    echo "<p><a href='verificar_usuarios.php'>Recargar página</a></p>";
                } else {
                    echo "<p style='color:red;'>❌ Error al crear el usuario admin.</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<form method='POST'>";
            echo "<button type='submit' name='crear_admin' style='padding:10px 20px; background:green; color:white; border:none; cursor:pointer;'>Crear Usuario Admin</button>";
            echo "</form>";
        }
    } else {
        // Buscar si existe un usuario llamado 'admin'
        $stmt = $pdo->query("SELECT * FROM persona WHERE Usuario = 'admin'");
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin) {
            echo "<h3 style='color:orange;'>⚠️ No existe un usuario 'admin'</h3>";
            echo "<p>¿Deseas crear uno?</p>";
            
            if (isset($_POST['crear_admin'])) {
                try {
                    // Primero verificar y crear roles si no existen
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rol");
                    $totalRoles = $stmt->fetch()['total'];
                    
                    if ($totalRoles == 0) {
                        echo "<p>Creando roles básicos...</p>";
                        $pdo->exec("INSERT INTO rol (Id_Rol, Nombre_Rol, Descripcion) VALUES 
                            (1, 'Pastor', 'Pastor de la iglesia'),
                            (2, 'Líder de Célula', 'Líder de célula'),
                            (3, 'Miembro', 'Miembro de la iglesia'),
                            (4, 'Visitante', 'Visitante'),
                            (5, 'Servidor', 'Servidor en ministerio'),
                            (6, 'Administrador del Sistema', 'Administrador con acceso total')");
                        echo "<p style='color:green;'>✅ Roles creados correctamente.</p>";
                    }
                    
                    // Crear tabla de permisos si no existe
                    $pdo->exec("CREATE TABLE IF NOT EXISTS permisos (
                        Id_Permiso INT PRIMARY KEY AUTO_INCREMENT,
                        Id_Rol INT NOT NULL,
                        Modulo VARCHAR(50) NOT NULL,
                        Puede_Ver BOOLEAN DEFAULT FALSE,
                        Puede_Crear BOOLEAN DEFAULT FALSE,
                        Puede_Editar BOOLEAN DEFAULT FALSE,
                        Puede_Eliminar BOOLEAN DEFAULT FALSE,
                        FOREIGN KEY (Id_Rol) REFERENCES rol(Id_Rol) ON DELETE CASCADE,
                        UNIQUE KEY unique_rol_modulo (Id_Rol, Modulo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    // Insertar permisos para administrador (rol 6)
                    echo "<p>Creando permisos para administrador...</p>";
                    $pdo->exec("INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) VALUES
                        (6, 'personas', 1, 1, 1, 1),
                        (6, 'celulas', 1, 1, 1, 1),
                        (6, 'ministerios', 1, 1, 1, 1),
                        (6, 'roles', 1, 1, 1, 1),
                        (6, 'eventos', 1, 1, 1, 1),
                        (6, 'peticiones', 1, 1, 1, 1),
                        (6, 'asistencias', 1, 1, 1, 1),
                        (6, 'reportes', 1, 1, 1, 1),
                        (6, 'permisos', 1, 1, 1, 1)
                    ON DUPLICATE KEY UPDATE 
                        Puede_Ver = VALUES(Puede_Ver),
                        Puede_Crear = VALUES(Puede_Crear),
                        Puede_Editar = VALUES(Puede_Editar),
                        Puede_Eliminar = VALUES(Puede_Eliminar)");
                    echo "<p style='color:green;'>✅ Permisos creados correctamente.</p>";
                    
                    // Verificar nuevamente si existe admin (por si acaso)
                    $stmt = $pdo->query("SELECT Id_Persona FROM persona WHERE Usuario = 'admin'");
                    $existeAdmin = $stmt->fetch();
                    
                    if ($existeAdmin) {
                        // Ya existe, solo actualizar el hash
                        echo "<p>El usuario admin ya existe, actualizando contraseña...</p>";
                        $hash = password_hash('admin123', PASSWORD_DEFAULT);
                        $sql = "UPDATE persona SET Contrasena = ?, Estado_Cuenta = 'Activo', Id_Rol = 6 WHERE Usuario = 'admin'";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$hash]);
                        echo "<p style='color:green;'>✅ Usuario admin actualizado!</p>";
                        echo "<p><strong>Usuario:</strong> admin<br><strong>Contraseña:</strong> admin123</p>";
                        echo "<p><a href='verificar_usuarios.php'>Recargar página</a></p>";
                    } else {
                        // Crear nuevo registro
                        $hash = password_hash('admin123', PASSWORD_DEFAULT);
                        $sql = "INSERT INTO persona (Nombre, Apellido, Email, Usuario, Contrasena, Estado_Cuenta, Id_Rol) 
                                VALUES ('Administrador', 'Sistema', 'admin@mcimadrid.com', 'admin', ?, 'Activo', 6)";
                        $stmt = $pdo->prepare($sql);
                        if ($stmt->execute([$hash])) {
                            echo "<p style='color:green;'>✅ Usuario admin creado exitosamente!</p>";
                            echo "<p><strong>Usuario:</strong> admin<br><strong>Contraseña:</strong> admin123</p>";
                            echo "<p><a href='verificar_usuarios.php'>Recargar página</a></p>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<p style='color:red;'>❌ Error: " . $e->getMessage() . "</p>";
                }
            } else {
                echo "<form method='POST'>";
                echo "<button type='submit' name='crear_admin' style='padding:10px 20px; background:green; color:white; border:none; cursor:pointer;'>Crear Usuario Admin</button>";
                echo "</form>";
            }
        } else {
            echo "<h3 style='color:green;'>✅ Usuario 'admin' existe en la base de datos</h3>";
            
            // Verificar el hash de la contraseña
            echo "<h4>Verificación de contraseña:</h4>";
            $testHash = password_verify('admin123', $admin['Contrasena'] ?? '');
            if ($testHash) {
                echo "<p style='color:green;'>✅ El hash de la contraseña es correcto</p>";
                echo "<p><strong>Credenciales de acceso:</strong></p>";
                echo "<div style='background:#f0f0f0; padding:15px; border-left:4px solid green;'>";
                echo "<p><strong>Usuario:</strong> admin</p>";
                echo "<p><strong>Contraseña:</strong> admin123</p>";
                echo "<p><strong>URL:</strong> <a href='http://localhost/mcimadrid'>http://localhost/mcimadrid</a></p>";
                echo "</div>";
            } else {
                echo "<p style='color:red;'>❌ El hash de la contraseña NO coincide o el usuario no tiene nombre/apellido</p>";
                echo "<p>Estado del usuario admin:</p>";
                echo "<ul>";
                echo "<li>Nombre: " . ($admin['Nombre'] ?? 'NULL') . "</li>";
                echo "<li>Apellido: " . ($admin['Apellido'] ?? 'NULL') . "</li>";
                echo "<li>Email: " . ($admin['Email'] ?? 'NULL') . "</li>";
                echo "<li>Estado: " . ($admin['Estado_Cuenta'] ?? 'NULL') . "</li>";
                echo "<li>Rol: " . ($admin['Id_Rol'] ?? 'NULL') . "</li>";
                echo "</ul>";
                
                if (isset($_POST['arreglar_admin'])) {
                    echo "<p>Corrigiendo usuario admin...</p>";
                    
                    // Verificar que exista el rol 6
                    $stmt = $pdo->query("SELECT Id_Rol FROM rol WHERE Id_Rol = 6");
                    if (!$stmt->fetch()) {
                        echo "<p>Creando rol de administrador...</p>";
                        $pdo->exec("INSERT INTO rol (Id_Rol, Nombre_Rol, Descripcion) VALUES 
                            (6, 'Administrador del Sistema', 'Administrador con acceso total')
                            ON DUPLICATE KEY UPDATE Nombre_Rol = 'Administrador del Sistema'");
                    }
                    
                    // Crear tabla de permisos si no existe
                    $pdo->exec("CREATE TABLE IF NOT EXISTS permisos (
                        Id_Permiso INT PRIMARY KEY AUTO_INCREMENT,
                        Id_Rol INT NOT NULL,
                        Modulo VARCHAR(50) NOT NULL,
                        Puede_Ver BOOLEAN DEFAULT FALSE,
                        Puede_Crear BOOLEAN DEFAULT FALSE,
                        Puede_Editar BOOLEAN DEFAULT FALSE,
                        Puede_Eliminar BOOLEAN DEFAULT FALSE,
                        FOREIGN KEY (Id_Rol) REFERENCES rol(Id_Rol) ON DELETE CASCADE,
                        UNIQUE KEY unique_rol_modulo (Id_Rol, Modulo)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    // Insertar permisos para administrador
                    echo "<p>Creando permisos para administrador...</p>";
                    $pdo->exec("INSERT INTO permisos (Id_Rol, Modulo, Puede_Ver, Puede_Crear, Puede_Editar, Puede_Eliminar) VALUES
                        (6, 'personas', 1, 1, 1, 1),
                        (6, 'celulas', 1, 1, 1, 1),
                        (6, 'ministerios', 1, 1, 1, 1),
                        (6, 'roles', 1, 1, 1, 1),
                        (6, 'eventos', 1, 1, 1, 1),
                        (6, 'peticiones', 1, 1, 1, 1),
                        (6, 'asistencias', 1, 1, 1, 1),
                        (6, 'reportes', 1, 1, 1, 1),
                        (6, 'permisos', 1, 1, 1, 1)
                    ON DUPLICATE KEY UPDATE 
                        Puede_Ver = VALUES(Puede_Ver),
                        Puede_Crear = VALUES(Puede_Crear),
                        Puede_Editar = VALUES(Puede_Editar),
                        Puede_Eliminar = VALUES(Puede_Eliminar)");
                    
                    // Actualizar usuario admin
                    $hash = password_hash('admin123', PASSWORD_DEFAULT);
                    $sql = "UPDATE persona SET 
                            Nombre = 'Administrador',
                            Apellido = 'Sistema',
                            Email = 'admin@mcimadrid.com',
                            Contrasena = ?, 
                            Estado_Cuenta = 'Activo', 
                            Id_Rol = 6 
                            WHERE Usuario = 'admin'";
                    $stmt = $pdo->prepare($sql);
                    if ($stmt->execute([$hash])) {
                        echo "<p style='color:green;'>✅ Usuario admin corregido y permisos creados. <a href='verificar_usuarios.php'>Recargar página</a></p>";
                    }
                } else {
                    echo "<form method='POST'>";
                    echo "<button type='submit' name='arreglar_admin' style='padding:10px 20px; background:orange; color:white; border:none; cursor:pointer;'>Arreglar Usuario Admin</button>";
                    echo "</form>";
                }
            }
        }
    }
    
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>❌ Error de conexión</h2>";
    echo "<p>{$e->getMessage()}</p>";
    echo "<h3>Verifica:</h3>";
    echo "<ul>";
    echo "<li>Que MySQL esté corriendo en XAMPP</li>";
    echo "<li>Que la base de datos 'mcimadrid' exista</li>";
    echo "<li>Usuario: root, Contraseña: (vacía)</li>";
    echo "</ul>";
}
?>
