# 🚀 GUÍA RÁPIDA DE INSTALACIÓN - SISTEMA DE TRANSMISIONES

## ⚡ Paso 1: Importar la Base de Datos

1. Abre **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Selecciona la base de datos **mci**
3. Ve a la pestaña **SQL**
4. Copia el contenido de `agregar_transmisiones.sql`
5. Pega y ejecuta

**O por terminal (si tienes MySQL instalado)**:
```bash
mysql -u root -p mci < agregar_transmisiones.sql
```

## ✅ Paso 2: Verificar que la Tabla Existe

En phpMyAdmin:
- Actualiza la base de datos `mci`
- Busca la tabla `TRANSMISIONES_YOUTUBE`
- Verifica que tenga 10 columnas

## 🎯 Paso 3: Acceder a la Funcionalidad

### Administrador (CON LOGIN)
1. Inicia sesión en el sistema
2. Ve al menú y haz clic en "📡 Transmisiones"
3. O accede directamente: `?url=transmisiones`

**Acciones disponibles**:
- ➕ Nueva Transmisión
- ✏️ Editar
- 🗑️ Eliminar
- 📺 Ver en YouTube

### Público (SIN LOGIN)
1. Abre directamente: `?url=transmisiones-publico`
2. Ver transmisión en vivo si está disponible
3. Ver próximas transmisiones
4. Ver historial de transmisiones

## 📹 Prueba Rápida

### Crear una transmisión de prueba:

1. Inicia sesión como admin
2. Ve a **Transmisiones** → **Nueva Transmisión**
3. Llena el formulario:
   ```
   Nombre: Servicio de Prueba 18 Enero 2026
   URL: https://www.youtube.com/watch?v=dQw4w9WgXcQ
   Fecha: 18/01/2026
   Hora: 10:00
   Estado: En Vivo
   Descripción: Transmisión de prueba del sistema
   ```
4. Haz clic en "Crear Transmisión"

### Ver en público:
1. Ve a `?url=transmisiones-publico`
2. Deberías ver la transmisión en vivo destacada
3. Con el video incrustado de YouTube

## 🎨 Estructura de Carpetas Creada

```
views/transmisiones/
├── listar.php      (Panel admin)
├── crear.php       (Formulario crear)
├── editar.php      (Formulario editar)
└── publico.php     (Vista pública)

app/Models/
└── Transmision.php (Modelo con 14 métodos)

app/Controllers/
└── TransmisionController.php (Controlador con 11 métodos)
```

## 🔗 URLs Disponibles

```
Admin Panel:
- http://localhost/public_html/?url=transmisiones
- http://localhost/public_html/?url=transmisiones/crear
- http://localhost/public_html/?url=transmisiones/editar&id=1

Público:
- http://localhost/public_html/?url=transmisiones-publico
```

## ✨ Características Destacadas

✅ **En Vivo**: Indicador pulsante rojo con animación  
✅ **Responsivo**: Funciona en mobile, tablet y desktop  
✅ **YouTube Integrado**: Embed automático de video  
✅ **Seguridad**: Admin requiere login, público sin restricciones  
✅ **Base de Datos**: Auditoría automática de cambios  
✅ **Búsqueda**: API de búsqueda integrada  
✅ **Estadísticas**: Contador en vivo, próximas, finalizadas  

## 🐛 Solución de Problemas

### La tabla no se crea
- Verifica que tengas permisos en la BD
- Comprueba que la BD `mci` exista
- Ejecuta el SQL línea por línea en phpMyAdmin

### El enlace no aparece en el menú
- Asegúrate de estar logueado
- Recarga la página
- Borra cookies del navegador

### Los videos no se reproducen
- Verifica que la URL sea válida de YouTube
- Usa `youtube.com/watch?v=ID` o `youtu.be/ID`
- Comprueba que el video sea público

### Errores de permisos
- Verifica que tu usuario tenga rol admin
- Comprueba permisos en tabla PERMISOS
- Contacta al administrador

## 📞 Soporte

Para más información, consulta:
- `TRANSMISIONES_README.md` (Documentación completa)
- `IMPLEMENTACION_TRANSMISIONES.md` (Detalles técnicos)

---

**Instalación completada en**: 18 de Enero de 2026
