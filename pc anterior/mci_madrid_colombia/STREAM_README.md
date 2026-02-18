# 📹 Sistema de Transmisión ESP32-CAM

Sistema completo de transmisión en vivo y captura de fotos desde ESP32-CAM.

## 🌐 URLs de Acceso

### 📹 Transmisión en Vivo
```
http://localhost/mci_madrid_colombia/public/index.php?route=stream/live
```
o también:
```
http://localhost/mci_madrid_colombia/public/index.php?url=stream/live
```

### 🖼️ Galería de Fotos
```
http://localhost/mci_madrid_colombia/public/index.php?route=stream/gallery
```

### 🔧 API Endpoint (Para ESP32-CAM)
```
http://localhost/mci_madrid_colombia/api/stream.php
```

### 🧪 Página de Pruebas
```
http://localhost/mci_madrid_colombia/test_esp32cam.html
```

---

## 📁 Archivos Creados

### Backend
- **`/api/stream.php`** - Endpoint API que recibe fotos desde ESP32-CAM
- **`/app/Controllers/StreamController.php`** - Controlador para las vistas

### Frontend
- **`/views/stream/live.php`** - Vista de transmisión en vivo
- **`/views/stream/gallery.php`** - Vista de galería de fotos
- **`/test_esp32cam.html`** - Página de pruebas para subir imágenes

### Almacenamiento
- **`/public/assets/stream/`** - Carpeta donde se guardan las fotos
- **`/public/assets/stream/latest.jpg`** - Última foto capturada (actualizada automáticamente)
- **`/public/assets/stream/stream_*.jpg`** - Fotos históricas (últimas 100)

### Documentación
- **`/ESP32_CAM_SETUP.md`** - Guía completa de configuración para ESP32-CAM con código Arduino

---

## 🚀 Cómo Usar

### Opción 1: Con ESP32-CAM (Producción)

1. **Configura tu ESP32-CAM:**
   - Lee el archivo [ESP32_CAM_SETUP.md](ESP32_CAM_SETUP.md)
   - Sigue las instrucciones paso a paso
   - Carga el código Arduino proporcionado

2. **Accede a la transmisión:**
   - Abre la URL de transmisión en vivo en tu navegador
   - Las fotos se actualizarán automáticamente cada segundo

### Opción 2: Sin ESP32-CAM (Pruebas)

1. **Usa la página de pruebas:**
   - Abre `http://localhost/mci_madrid_colombia/test_esp32cam.html`
   - Selecciona una imagen desde tu computadora
   - Haz clic en "Subir Imagen"

2. **Verifica el resultado:**
   - Ve a la transmisión en vivo para ver tu foto
   - Ve a la galería para ver todas las fotos subidas

---

## 🔧 API Endpoints

### POST `/api/stream.php`
Recibe una foto desde la ESP32-CAM.

**Request:**
```
Content-Type: image/jpeg
Body: [Datos binarios de la imagen JPEG]
```

**Response:**
```json
{
  "success": true,
  "message": "Imagen recibida correctamente",
  "filename": "stream_20260101123045_abc123.jpg",
  "timestamp": "2026-01-01 12:30:45"
}
```

### GET `/api/stream.php?action=latest`
Obtiene información de la última imagen capturada.

**Response:**
```json
{
  "success": true,
  "url": "/public/assets/stream/latest.jpg",
  "timestamp": 1735740645
}
```

### GET `/api/stream.php?action=list`
Lista todas las imágenes almacenadas.

**Response:**
```json
{
  "success": true,
  "count": 25,
  "images": [
    {
      "filename": "stream_20260101123045_abc123.jpg",
      "url": "/public/assets/stream/stream_20260101123045_abc123.jpg",
      "timestamp": "2026-01-01 12:30:45",
      "size": 45678
    },
    ...
  ]
}
```

---

## ⚙️ Características

### Transmisión en Vivo
- ✅ Actualización automática cada segundo
- ✅ Indicador de estado (en vivo / sin señal)
- ✅ Contador de FPS en tiempo real
- ✅ Información de última actualización
- ✅ Contador total de fotos
- ✅ Captura de pantalla con un clic
- ✅ Acceso directo a galería

### Galería de Fotos
- ✅ Vista en cuadrícula responsive
- ✅ Miniaturas de todas las fotos
- ✅ Timestamp de cada foto
- ✅ Tamaño de archivo
- ✅ Descarga individual
- ✅ Vista ampliada (modal)
- ✅ Diseño moderno y atractivo

### API
- ✅ Recepción de imágenes binarias
- ✅ Soporte para formularios multipart
- ✅ Mantenimiento automático (solo últimas 100 fotos)
- ✅ Actualización de "latest.jpg" automática
- ✅ CORS habilitado
- ✅ Respuestas JSON

---

## 🔒 Seguridad

### URLs Ocultas
- Las URLs **NO** aparecen en ningún menú del sistema
- Solo accesibles para quien conozca la URL exacta
- No requieren autenticación (son públicas)

### Recomendaciones para Producción
Si deseas agregar seguridad adicional:

1. **Token de acceso:**
   - Modifica `api/stream.php` para verificar un token en el header
   - Configura el mismo token en tu ESP32-CAM

2. **Autenticación:**
   - Quita las rutas de `$rutasPublicas` en `/public/index.php`
   - Requiere login para acceder a las vistas

3. **IP Whitelist:**
   - Agrega verificación de IP en `api/stream.php`
   - Solo permite acceso desde IPs específicas

---

## 📊 Configuración Avanzada

### Cambiar cantidad de fotos almacenadas

En `/api/stream.php`, línea 53:
```php
cleanOldImages($uploadDir, 100);  // Cambiar 100 por el número deseado
```

### Cambiar intervalo de actualización

En `/views/stream/live.php`, línea 237:
```javascript
updateInterval = setInterval(updateImage, 1000);  // 1000ms = 1 segundo
```

### Configurar ESP32-CAM

Ver archivo completo: [ESP32_CAM_SETUP.md](ESP32_CAM_SETUP.md)

En el código Arduino:
```cpp
const unsigned long captureInterval = 1000; // 1 foto por segundo
config.jpeg_quality = 10;  // Calidad JPEG (0-63)
config.frame_size = FRAMESIZE_UXGA;  // Resolución
```

---

## 🐛 Solución de Problemas

### No se ve la transmisión
1. Verifica que Apache esté corriendo
2. Verifica que la carpeta `/public/assets/stream/` exista
3. Verifica permisos de escritura en la carpeta
4. Intenta subir una foto con la página de pruebas

### ESP32-CAM no envía fotos
1. Verifica la conexión WiFi de la ESP32-CAM
2. Verifica que la URL del servidor sea correcta
3. Revisa el Monitor Serie de Arduino IDE (115200 baud)
4. Verifica que el firewall no bloquee las conexiones

### Fotos no se guardan
1. Verifica permisos de escritura: `chmod 777 public/assets/stream/`
2. Verifica espacio en disco
3. Revisa los logs de PHP

### Las fotos no se actualizan
1. Presiona Ctrl+F5 para forzar recarga
2. Verifica que JavaScript esté habilitado
3. Abre la consola del navegador (F12) para ver errores

---

## 📱 Compatibilidad

### Navegadores
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

### Dispositivos
- ✅ Desktop (Windows, Mac, Linux)
- ✅ Tablets
- ✅ Smartphones

### Hardware ESP32
- ✅ ESP32-CAM AI-Thinker
- ✅ ESP32-CAM MB (con USB integrado)
- ✅ Otros modelos compatibles con ESP32

---

## 🎯 Casos de Uso

- 🏠 Cámara de seguridad casera
- 👶 Monitor de bebés
- 🐕 Cámara para mascotas
- 📦 Monitoreo de procesos
- 🌡️ Vigilancia de equipos
- 🚪 Cámara de entrada
- 🔬 Documentación de experimentos
- 📹 Streaming de eventos en vivo

---

## 🔄 Actualizaciones Futuras

Posibles mejoras:
- [ ] Grabación de video real (no solo fotos)
- [ ] Detección de movimiento
- [ ] Notificaciones push
- [ ] Múltiples cámaras simultáneas
- [ ] Control PTZ (Pan-Tilt-Zoom)
- [ ] Visión nocturna mejorada
- [ ] Almacenamiento en la nube
- [ ] Reproducción de timeline

---

## 📞 Soporte

Para más información sobre la configuración de ESP32-CAM, consulta:
- [ESP32_CAM_SETUP.md](ESP32_CAM_SETUP.md) - Guía completa de configuración
- Arduino IDE - Monitor Serie (115200 baud)
- Logs de Apache - `/xampp/apache/logs/error.log`

---

## 📄 Licencia

Este módulo es parte del sistema MCI Madrid Colombia.

---

**✨ ¡Tu sistema de transmisión ESP32-CAM está listo para usar! ✨**
