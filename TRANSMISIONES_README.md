# 📺 Sistema de Transmisiones en Vivo - YouTube

## Descripción

Sistema integrado para gestionar transmisiones en vivo de YouTube de la iglesia MCI Madrid - Colombia.

Permite:
- **Administradores**: Crear, editar y eliminar transmisiones
- **Público**: Ver transmisiones en vivo, próximas y finalizadas sin necesidad de iniciar sesión

## 📊 Estructura del Sistema

### Base de Datos
**Tabla**: `TRANSMISIONES_YOUTUBE`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| Id_Transmision | INT | Identificador único (PK) |
| Nombre | VARCHAR(150) | Nombre de la transmisión |
| URL_YouTube | VARCHAR(255) | Link del video/transmisión en YouTube |
| Fecha_Transmision | DATE | Fecha de la transmisión |
| Hora_Transmision | TIME | Hora de la transmisión (opcional) |
| Fecha_Creacion | TIMESTAMP | Fecha de registro automática |
| Fecha_Actualizacion | TIMESTAMP | Fecha de última edición |
| Estado | ENUM | 'en_vivo', 'finalizada', 'proximamente' |
| Descripcion | TEXT | Información adicional |
| Id_Usuario_Creador | INT | Referencia al usuario que creó el registro (FK) |

### Controlador
**Archivo**: `app/Controllers/TransmisionController.php`

**Métodos Privados** (Requieren autenticación):
- `listar()` - Panel de gestión con estadísticas
- `crear()` - Formulario para crear nueva transmisión
- `guardar()` - Guardar nueva transmisión (API)
- `editar()` - Formulario para editar
- `actualizar()` - Actualizar transmisión (API)
- `cambiarEstado()` - Cambiar estado sin editar otros campos (API)
- `eliminar()` - Eliminar transmisión (API)
- `buscar()` - Buscar transmisiones (API JSON)

**Métodos Públicos** (Sin autenticación):
- `verPublico()` - Vista pública con transmisiones en vivo, próximas y finalizadas

### Modelo
**Archivo**: `app/Models/Transmision.php`

Métodos disponibles:
- `obtenerTodas()` - Obtener todas las transmisiones
- `obtenerEnVivo()` - Obtener la transmisión en vivo actual
- `obtenerProximas($limite)` - Obtener próximas transmisiones
- `obtenerFinalizadas($limite)` - Obtener transmisiones finalizadas
- `buscar($termino)` - Buscar por nombre o descripción
- `crear()` - Crear nueva transmisión
- `actualizar()` - Actualizar campos
- `cambiarEstado()` - Cambiar solo el estado
- `eliminar()` - Eliminar transmisión
- `contarPorEstado()` - Contar por estado

### Vistas

#### Privadas (Requieren login)
- **listar.php** - Panel de control con tabla de transmisiones, estadísticas y botones de acción
- **crear.php** - Formulario para crear nueva transmisión
- **editar.php** - Formulario para editar transmisión existente

#### Públicas (Sin login)
- **publico.php** - Vista pública elegante mostrando:
  - Transmisión en vivo actual (si hay alguna)
  - Próximas transmisiones
  - Historial de transmisiones finalizadas
  - Integración de video embed de YouTube

## 🚀 Rutas Disponibles

### Rutas Privadas (Autenticación requerida)
```
?url=transmisiones                    // Panel de gestión
?url=transmisiones/crear              // Crear nueva
?url=transmisiones/editar&id=X        // Editar transmisión
?url=transmisiones/guardar            // API guardar (POST)
?url=transmisiones/actualizar         // API actualizar (POST)
?url=transmisiones/cambiarEstado      // API cambiar estado (POST)
?url=transmisiones/eliminar           // API eliminar (POST)
?url=transmisiones/buscar             // API buscar (POST)
?url=transmisiones/obtenerEnVivo      // API obtener en vivo (JSON)
```

### Rutas Públicas (Sin autenticación)
```
?url=transmisiones-publico            // Ver transmisiones en vivo
```

## 🔧 Instalación

### 1. Ejecutar Script SQL
```sql
-- Ejecutar el archivo agregar_transmisiones.sql en phpMyAdmin
```

### 2. Acceso
- **Para admin**: `https://www.mcimadridcolombia.com/?url=transmisiones`
- **Para público**: `https://www.mcimadridcolombia.com/?url=transmisiones-publico`

## 💡 Características

### Panel de Gestión
- ✅ Estadísticas en tiempo real (En vivo, Próximas, Finalizadas)
- ✅ Tabla interactiva con todas las transmisiones
- ✅ Indicadores visuales por estado
- ✅ Botones de acción (editar, eliminar, ver en YouTube)
- ✅ Búsqueda rápida

### Formularios
- ✅ Validación de URL de YouTube (soporta youtube.com, youtu.be)
- ✅ Selección de fecha y hora
- ✅ Estados: En vivo, Próximamente, Finalizada
- ✅ Descripción detallada
- ✅ Información de auditoría (creación, actualización)

### Vista Pública
- ✅ Transmisión en vivo destacada con indicador pulsante
- ✅ Video embed de YouTube automático
- ✅ Próximas transmisiones en tarjetas
- ✅ Historial de transmisiones finalizadas
- ✅ Diseño responsivo y atractivo
- ✅ Sin necesidad de iniciar sesión

## 📝 Ejemplo de Uso

### Crear una transmisión
1. Ir a `?url=transmisiones`
2. Hacer clic en "Nueva Transmisión"
3. Completar formulario:
   - Nombre: "Servicio Dominical 18 Enero 2026"
   - URL: "https://www.youtube.com/watch?v=XXXXX"
   - Fecha: 18/01/2026
   - Hora: 10:30
   - Estado: En Vivo
   - Descripción: Información adicional
4. Hacer clic en "Crear Transmisión"

### Ver públicamente
El público puede acceder a `?url=transmisiones-publico` para:
- Ver la transmisión en vivo en tiempo real
- Ver próximas transmisiones programadas
- Ver el historial de transmisiones pasadas

## 🔐 Seguridad

- ✅ Rutas privadas protegidas por autenticación
- ✅ Ruta pública sin restricciones
- ✅ Validación de URLs en servidor
- ✅ Sanitización de entrada HTML
- ✅ Protección contra inyección SQL (prepared statements)

## 📱 Responsivo

Todas las vistas están optimizadas para:
- Desktop
- Tablet
- Mobile

## 🎨 Estilos

- Usa colores consistentes con el diseño del proyecto
- Gradient principal: `#667eea → #764ba2`
- Indicador en vivo: Rojo con animación pulsante
- Bootstrap Icons para iconografía

## 🐛 Validaciones

- URL de YouTube válida
- Campos obligatorios
- Formato de fecha y hora
- Estados válidos (en_vivo, finalizada, proximamente)

---

**Creado**: 18 de Enero de 2026
**Última actualización**: 18 de Enero de 2026
