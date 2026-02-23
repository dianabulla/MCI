# 📋 INSTRUCCIONES DE INSTALACIÓN Y USO
## Sistema de Gestión - Iglesia MCI Madrid Colombia

---

## ⚠️ ANTES DE COMENZAR

Asegúrate de tener XAMPP instalado y funcionando.

---

## 🚀 PASO 1: Iniciar Servicios XAMPP

1. Abre el Panel de Control de XAMPP
2. Inicia **Apache**
3. Inicia **MySQL**
4. Verifica que ambos tengan luz verde

---

## 💾 PASO 2: Crear la Base de Datos

### Opción A: Importar archivo SQL (RECOMENDADO)

1. Abre tu navegador
2. Ve a: `http://localhost/phpmyadmin`
3. Haz clic en "Nueva" (New) en la barra lateral izquierda
4. Nombre de la base de datos: `mci`
5. Cotejamiento: `utf8mb4_general_ci`
6. Haz clic en "Crear"
7. Selecciona la base de datos `mci`
8. Haz clic en "Importar"
9. Haz clic en "Seleccionar archivo" (Choose File)
10. Busca y selecciona el archivo: `C:\xampp\htdocs\mci_madrid_colombia\mci.sql`
11. Haz clic en "Continuar" (Go)
12. Verifica que aparezca el mensaje "Importación finalizada con éxito"

### Opción B: Crear manualmente

Si prefieres crear la base de datos desde cero, ejecuta el contenido del archivo `mci.sql` en la pestaña SQL de phpMyAdmin.

---

## ✅ PASO 3: Verificar la Instalación

### 3.1 Verificar que existan las tablas

En phpMyAdmin:
1. Selecciona la base de datos `mci`
2. Deberías ver estas tablas:
   - ASISTENCIA_CELULA
   - CELULA
   - EVENTO
   - MINISTERIO
   - PERSONA
   - PETICION
   - ROL

### 3.2 Verificar conexión

1. Abre el archivo `conexion.php` ubicado en:
   ```
   C:\xampp\htdocs\mci_madrid_colombia\conexion.php
   ```

2. Verifica que tenga estos datos:
   ```php
   $host = 'localhost';
   $dbname = 'mci';
   $username = 'root';
   $password = '';
   ```

3. Si tu configuración de MySQL es diferente, ajusta estos valores.

---

## 🌐 PASO 4: Acceder al Sistema

### Menú Principal

Abre tu navegador y ve a:
```
http://localhost/mci_madrid_colombia/
```

Deberías ver el menú principal con 8 módulos:
- 🏠 Dashboard
- 👥 Personas
- ⛪ Células
- 🎵 Ministerios
- 👤 Roles
- 📅 Eventos
- 🙏 Peticiones
- ✅ Asistencias

### Acceso Directo a la Aplicación

```
http://localhost/mci_madrid_colombia/public/index.php?url=home
```

---

## 📱 PASO 5: Comenzar a Usar el Sistema

### 5.1 Agregar Ministerios

1. Desde el menú principal, haz clic en **Ministerios**
2. Haz clic en **+ Nuevo Ministerio**
3. Completa el formulario
4. Haz clic en **Guardar**

**Ejemplos de Ministerios:**
- Alabanza
- Intercesión
- Multimedia
- Ujieres
- Protocolo

### 5.2 Agregar Roles

1. Desde el menú principal, haz clic en **Roles**
2. Haz clic en **+ Nuevo Rol**
3. Completa el formulario
4. Haz clic en **Guardar**

**Ejemplos de Roles:**
- Pastor
- Líder
- Miembro
- Visitante
- Colaborador

### 5.3 Agregar Células

1. Desde el menú principal, haz clic en **Células**
2. Haz clic en **+ Nueva Célula**
3. Completa el formulario:
   - Nombre de la Célula
   - Dirección
   - Día de Reunión
   - Hora
   - Líder (opcional, se puede asignar después)
4. Haz clic en **Guardar**

### 5.4 Agregar Personas

1. Desde el menú principal, haz clic en **Personas**
2. Haz clic en **+ Nueva Persona**
3. Completa el formulario:
   - Nombre
   - Apellido
   - Fecha de Nacimiento
   - Teléfono
   - Email
   - Dirección
   - Célula (opcional)
   - Rol (opcional)
   - Ministerio (opcional)
4. Haz clic en **Guardar**

### 5.5 Registrar Eventos

1. Desde el menú principal, haz clic en **Eventos**
2. Haz clic en **+ Nuevo Evento**
3. Completa el formulario
4. Haz clic en **Guardar**

### 5.6 Registrar Peticiones

1. Desde el menú principal, haz clic en **Peticiones**
2. Haz clic en **+ Nueva Petición**
3. Selecciona la persona
4. Escribe la petición
5. Haz clic en **Guardar**

### 5.7 Registrar Asistencias

1. Desde el menú principal, haz clic en **Asistencias**
2. Haz clic en **+ Registrar Asistencia**
3. Selecciona la célula
4. Selecciona la fecha
5. Marca las asistencias
6. Haz clic en **Guardar Asistencias**

---

## 🔧 SOLUCIÓN DE PROBLEMAS COMUNES

### ❌ Error: "Base de datos no encontrada"

**Solución:**
1. Verifica que MySQL esté corriendo en XAMPP
2. Verifica que la base de datos `mci` exista en phpMyAdmin
3. Verifica la configuración en `conexion.php`

### ❌ Error: "Página en blanco"

**Solución:**
1. Revisa los errores en:
   ```
   C:\xampp\apache\logs\error.log
   ```
2. Verifica que PHP esté habilitado en Apache
3. Verifica que los archivos estén en:
   ```
   C:\xampp\htdocs\mci_madrid_colombia\
   ```

### ❌ Error: "No se puede conectar a la base de datos"

**Solución:**
1. Verifica que MySQL esté corriendo
2. Verifica usuario y contraseña en `conexion.php`
3. Por defecto XAMPP usa:
   - Usuario: `root`
   - Contraseña: (vacía)

### ❌ Error: "No se encuentra el archivo"

**Solución:**
1. Verifica que la URL sea correcta:
   ```
   http://localhost/mci_madrid_colombia/
   ```
2. Verifica que los archivos estén en la carpeta correcta de XAMPP

### ❌ Las rutas no funcionan

**Solución:**
1. Verifica que el archivo `.htaccess` exista en:
   ```
   C:\xampp\htdocs\mci_madrid_colombia\public\.htaccess
   ```
2. Habilita `mod_rewrite` en Apache:
   - Abre: `C:\xampp\apache\conf\httpd.conf`
   - Busca: `#LoadModule rewrite_module modules/mod_rewrite.so`
   - Quita el `#` al inicio
   - Guarda y reinicia Apache

---

## 📊 NAVEGACIÓN DEL SISTEMA

### URLs Importantes

**Menú Principal:**
```
http://localhost/mci_madrid_colombia/
```

**Dashboard:**
```
http://localhost/mci_madrid_colombia/public/index.php?url=home
```

**Personas:**
```
http://localhost/mci_madrid_colombia/public/index.php?url=personas
```

**Células:**
```
http://localhost/mci_madrid_colombia/public/index.php?url=celulas
```

### Patrón de URLs

Todas las URLs siguen este patrón:
```
http://localhost/mci_madrid_colombia/public/index.php?url=modulo/accion
```

**Ejemplos:**
- Crear persona: `?url=personas/crear`
- Editar persona: `?url=personas/editar&id=1`
- Eliminar persona: `?url=personas/eliminar&id=1`

---

## 🎨 PERSONALIZACIÓN

### Cambiar Colores

Edita el archivo:
```
C:\xampp\htdocs\mci_madrid_colombia\public\assets\css\styles.css
```

Busca y cambia:
```css
/* Colores principales */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
```

### Cambiar Logo

1. Coloca tu logo en:
   ```
   C:\xampp\htdocs\mci_madrid_colombia\public\assets\img\
   ```

2. Edita `index.html` para agregar el logo

---

## 🔐 SEGURIDAD

### Cambiar Contraseña de MySQL

1. Abre phpMyAdmin
2. Ve a la pestaña "Cuentas de usuario"
3. Edita el usuario `root`
4. Establece una contraseña
5. Actualiza `conexion.php` con la nueva contraseña

### Backup de la Base de Datos

1. Abre phpMyAdmin
2. Selecciona la base de datos `mci`
3. Haz clic en "Exportar"
4. Selecciona "Método rápido"
5. Formato: SQL
6. Haz clic en "Continuar"
7. Guarda el archivo en un lugar seguro

**Recomendación:** Hacer backup cada semana

---

## 📞 SOPORTE

Para dudas o problemas:
1. Revisa este archivo primero
2. Revisa el archivo `README.md`
3. Revisa los logs de error de Apache

---

## ✨ CARACTERÍSTICAS DEL SISTEMA

✅ Gestión completa de personas  
✅ Gestión de células/grupos familiares  
✅ Gestión de ministerios  
✅ Gestión de roles  
✅ Calendario de eventos  
✅ Peticiones de oración  
✅ Control de asistencias  
✅ Diseño responsive (móvil, tablet, escritorio)  
✅ Interfaz intuitiva  
✅ Seguridad con PDO  

---

## 📝 VERSIÓN

**Sistema**: MCI Madrid Colombia  
**Versión**: 1.0  
**Fecha**: Diciembre 2025  
**Base de datos**: mci  

---

**¡Listo! El sistema está completamente instalado y funcionando. 🎉**
