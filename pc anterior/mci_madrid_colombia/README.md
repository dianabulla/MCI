# Sistema de Gestión - Iglesia MCI Madrid Colombia

Sistema completo de gestión eclesiástica desarrollado en PHP con arquitectura MVC.

## 📋 Características

- Gestión de Personas (miembros de la iglesia)
- Gestión de Células (grupos familiares)
- Gestión de Ministerios
- Gestión de Roles
- Gestión de Eventos
- Peticiones de Oración
- Control de Asistencias

## 🛠️ Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- XAMPP (Apache + MySQL)

## 📁 Estructura del Proyecto

```
mci_madrid_colombia/
├── index.html              # Menú principal
├── conexion.php            # Conexión a BD
├── mci.sql                 # Base de datos
├── app/
│   ├── Config/
│   │   ├── config.php      # Configuración general
│   │   ├── Database.php    # Clase de conexión
│   │   └── routes.php      # Rutas de la aplicación
│   ├── Controllers/        # Controladores
│   │   ├── BaseController.php
│   │   ├── HomeController.php
│   │   ├── PersonaController.php
│   │   ├── CelulaController.php
│   │   ├── MinisterioController.php
│   │   ├── RolController.php
│   │   ├── EventoController.php
│   │   ├── PeticionController.php
│   │   └── AsistenciaController.php
│   └── Models/             # Modelos
│       ├── BaseModel.php
│       ├── Persona.php
│       ├── Celula.php
│       ├── Ministerio.php
│       ├── Rol.php
│       ├── Evento.php
│       ├── Peticion.php
│       └── Asistencia.php
├── public/
│   ├── index.php           # Front Controller
│   ├── .htaccess          # Reglas Apache
│   └── assets/
│       ├── css/
│       │   └── styles.css
│       ├── js/
│       │   └── main.js
│       └── img/
└── views/                  # Vistas (HTML)
    ├── layout/
    │   ├── header.php
    │   └── footer.php
    ├── home/
    │   └── dashboard.php
    ├── personas/
    │   ├── lista.php
    │   ├── formulario.php
    │   └── detalle.php
    ├── celulas/
    │   ├── lista.php
    │   └── formulario.php
    ├── ministerios/
    │   ├── lista.php
    │   └── formulario.php
    ├── roles/
    │   ├── lista.php
    │   └── formulario.php
    ├── eventos/
    │   ├── lista.php
    │   └── formulario.php
    ├── peticiones/
    │   ├── lista.php
    │   └── formulario.php
    └── asistencias/
        ├── lista.php
        └── formulario.php
```

## 🚀 Instalación

### 1. Importar Base de Datos

1. Abrir phpMyAdmin: `http://localhost/phpmyadmin`
2. Crear base de datos llamada `mci`
3. Importar el archivo `mci.sql`

### 2. Configurar Conexión

El archivo `conexion.php` ya está configurado con:
- Host: localhost
- Database: mci
- User: root
- Password: (vacío)

Si tu configuración es diferente, edita el archivo `conexion.php` y `app/Config/config.php`.

### 3. Acceder a la Aplicación

- **Menú Principal**: `http://localhost/mci_madrid_colombia/`
- **Dashboard**: `http://localhost/mci_madrid_colombia/public/index.php?url=home`

## 📖 Uso

### Navegación

Desde el menú principal (`index.html`), puedes acceder a todos los módulos:
- Personas
- Células
- Ministerios
- Roles
- Eventos
- Peticiones
- Asistencias

### URLs de la Aplicación

Todas las URLs siguen el patrón:
```
http://localhost/mci_madrid_colombia/public/index.php?url=modulo/accion
```

Ejemplos:
- Listar personas: `?url=personas`
- Crear persona: `?url=personas/crear`
- Editar persona: `?url=personas/editar&id=1`

## 🎨 Personalización

### Colores y Estilos

Edita el archivo `public/assets/css/styles.css` para cambiar:
- Colores del tema
- Fuentes
- Espaciados
- Diseño responsivo

### Logo e Imágenes

Coloca tus imágenes en `public/assets/img/`

## 🔧 Desarrollo

### Agregar un Nuevo Módulo

1. **Crear Modelo** en `app/Models/`
2. **Crear Controlador** en `app/Controllers/`
3. **Agregar Rutas** en `app/Config/routes.php`
4. **Crear Vistas** en `views/nombre_modulo/`

### Estructura de una Ruta

```php
'url' => 'NombreController@metodo'
```

## 📝 Base de Datos

### Tablas Principales

- `PERSONA`: Información de miembros
- `CELULA`: Células/grupos familiares
- `MINISTERIO`: Ministerios de la iglesia
- `ROL`: Roles y cargos
- `EVENTO`: Eventos y actividades
- `PETICION`: Peticiones de oración
- `ASISTENCIA_CELULA`: Control de asistencia

## 🔐 Seguridad

- Todas las consultas usan PDO con prepared statements
- Validación de datos en formularios
- Protección contra SQL injection
- Sanitización de salidas HTML

## 📱 Responsive

El sistema es completamente responsive y se adapta a:
- Escritorio
- Tablets
- Móviles

## 🆘 Solución de Problemas

### Error de Conexión a Base de Datos

Verifica que:
1. XAMPP esté ejecutándose
2. MySQL esté activo
3. La base de datos `mci` exista
4. Las credenciales en `conexion.php` sean correctas

### Página en Blanco

Verifica errores en:
```
C:\xampp\apache\logs\error.log
```

### Rutas No Funcionan

Verifica que `mod_rewrite` esté habilitado en Apache.

## 👥 Soporte

Para soporte o dudas, contacta al administrador del sistema.

## 📄 Licencia

Sistema desarrollado para uso interno de la Iglesia MCI Madrid - Colombia.

---

**Versión**: 1.0  
**Última actualización**: Diciembre 2025
