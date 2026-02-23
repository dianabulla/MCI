# Sistema de Autenticación - Guía de Instalación

## 📋 Pasos para Activar el Sistema de Autenticación

### 1. Ejecutar el Script SQL

**IMPORTANTE:** Ejecute el script `sistema_autenticacion.sql` en su base de datos MySQL.

#### En Desarrollo (Local):
```sql
-- Abra phpMyAdmin en http://localhost/phpmyadmin
-- Seleccione la base de datos 'mci'
-- Vaya a la pestaña SQL
-- Copie y pegue todo el contenido de sistema_autenticacion.sql
-- Haga clic en "Continuar"
```

#### En Producción:
```sql
-- Acceda a phpMyAdmin de su hosting
-- Seleccione la base de datos 'u694856656_mci'
-- Vaya a la pestaña SQL
-- Copie y pegue todo el contenido de sistema_autenticacion.sql
-- Haga clic en "Continuar"
```

### 2. Usuarios de Prueba Creados

El script crea automáticamente 3 usuarios de prueba:

| Usuario | Contraseña | Rol | Permisos |
|---------|-----------|-----|----------|
| admin | admin123 | Administrador | Acceso total a todos los módulos |
| pastor | pastor123 | Pastor | Acceso limitado (sin permisos ni roles) |
| lider | lider123 | Líder | Acceso restringido (solo personas, células, asistencias) |

### 3. Primer Acceso

1. Navegue a: https://www.mcimadridcolombia.com/
2. Será redirigido automáticamente al login
3. Ingrese con: **admin** / **admin123**
4. ¡Ya está dentro del sistema!

### 4. Asignar Usuarios a Personas Existentes

Para que las personas existentes puedan ingresar:

1. Inicie sesión como **admin**
2. Vaya a **Personas**
3. Edite cada persona que necesite acceso
4. En el formulario, agregue los campos:
   - **Usuario:** nombre de usuario único
   - **Contraseña:** contraseña segura
   - **Estado:** Activo

O ejecute SQL directo:
```sql
-- Ejemplo: Dar acceso a una persona específica
UPDATE persona 
SET 
    Usuario = 'juanperez',
    Contrasena = '$2y$10$YourBcryptHashHere',
    Estado_Cuenta = 'Activo'
WHERE Id_Persona = 5;
```

**IMPORTANTE:** Las contraseñas deben estar encriptadas con bcrypt. Use la función PHP:
```php
password_hash('micontraseña', PASSWORD_BCRYPT)
```

### 5. Administrar Permisos

1. Inicie sesión como **admin**
2. Vaya al menú **Permisos** (solo visible para administradores)
3. Marque/desmarque las casillas para otorgar/revocar permisos
4. Los cambios se guardan automáticamente

**Tipos de permisos por módulo:**
- ✅ **Ver:** Ver listados y detalles
- ➕ **Crear:** Agregar nuevos registros
- ✏️ **Editar:** Modificar registros existentes
- ❌ **Eliminar:** Borrar registros

### 6. Cambios Aplicados al Sistema

#### Archivos Nuevos:
- ✅ `sistema_autenticacion.sql` - Script de instalación
- ✅ `app/Controllers/AuthController.php` - Controlador de autenticación
- ✅ `app/Controllers/PermisosController.php` - Gestión de permisos
- ✅ `views/auth/login.php` - Página de login
- ✅ `views/auth/acceso_denegado.php` - Página de error 403
- ✅ `views/permisos/index.php` - Administración de permisos

#### Archivos Modificados:
- ✅ `public/index.php` - Middleware de autenticación
- ✅ `app/Config/routes.php` - Rutas de auth y permisos
- ✅ `app/Models/Persona.php` - Métodos de autenticación
- ✅ `views/layout/header.php` - Menú dinámico + info de usuario

#### Base de Datos:
- ✅ Tabla `persona`: +4 campos (Usuario, Contrasena, Estado_Cuenta, Ultimo_Acceso)
- ✅ Tabla `permisos`: Nueva tabla de control de acceso
- ✅ Rol "Pastor" renombrado a "Administrador"
- ✅ 3 usuarios de prueba creados
- ✅ Permisos por defecto asignados

### 7. Seguridad

✅ **Contraseñas encriptadas** con bcrypt
✅ **Sesiones seguras** con verificación en cada request
✅ **Control de permisos** granular por módulo y acción
✅ **Prevención de acceso directo** a rutas protegidas
✅ **Logout seguro** con destrucción de sesión
✅ **Menú dinámico** que solo muestra opciones permitidas

### 8. Recuperación de Contraseña

Si olvida la contraseña del admin, ejecute:
```sql
-- Restablecer contraseña de admin a "admin123"
UPDATE persona 
SET Contrasena = '$2y$10$vHZ2bvQqEKGJ8jX5K9WPReF8dKx4LwpEf0TBJjKvhN3AzJdGNGXZC'
WHERE Usuario = 'admin';
```

### 9. Bloquear/Desbloquear Usuarios

```sql
-- Bloquear usuario
UPDATE persona SET Estado_Cuenta = 'Bloqueado' WHERE Usuario = 'usuario';

-- Activar usuario
UPDATE persona SET Estado_Cuenta = 'Activo' WHERE Usuario = 'usuario';

-- Suspender usuario
UPDATE persona SET Estado_Cuenta = 'Suspendido' WHERE Usuario = 'usuario';
```

### 10. Verificación del Sistema

✅ Al acceder sin login → Redirige a /auth/login
✅ Credenciales incorrectas → Muestra error
✅ Cuenta bloqueada → Muestra error
✅ Login exitoso → Redirige a dashboard
✅ Menú muestra solo módulos permitidos
✅ Acceso a ruta sin permisos → Error 403
✅ Botón "Salir" cierra sesión correctamente

---

## 🎉 ¡Sistema Listo!

El sistema de autenticación está completamente funcional. Todos los usuarios ahora deben iniciar sesión para acceder al sistema, y los permisos se controlan automáticamente según su rol.

**Contacto:** Para soporte, contacte al administrador del sistema.
