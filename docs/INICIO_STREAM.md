# 🎥 ESP32-CAM - Guía Rápida de Inicio

## ✅ Sistema Instalado Correctamente

Todo está listo para usar tu ESP32-CAM. Aquí tienes las URLs principales:

---

## 🌐 URLs Principales

### 🚀 Página de Acceso Rápido (Inicio)
```
http://localhost/mci_madrid_colombia/stream_access.html
```
**Portal central con acceso a todas las funciones**

### 📹 Transmisión en Vivo
```
http://localhost/mci_madrid_colombia/public/index.php?route=stream/live
```
**Ver el video en vivo (fotos cada segundo) desde tu ESP32-CAM**

### 🖼️ Galería de Fotos
```
http://localhost/mci_madrid_colombia/public/index.php?route=stream/gallery
```
**Ver todas las fotos capturadas (últimas 100)**

### 🧪 Página de Pruebas
```
http://localhost/mci_madrid_colombia/test_esp32cam.html
```
**Probar el sistema sin ESP32-CAM (subir fotos manualmente)**

### 🔍 Verificación del Sistema
```
http://localhost/mci_madrid_colombia/verificar_stream.php
```
**Verificar que todo esté instalado correctamente**

---

## 🔧 Para tu ESP32-CAM

### URL del API (Configúrala en tu ESP32-CAM)
```
http://TU_IP_LOCAL/mci_madrid_colombia/api/stream.php
```

**Ejemplo:**
```cpp
const char* serverUrl = "http://192.168.1.100/mci_madrid_colombia/api/stream.php";
```

### 📖 Guía de Configuración
Lee el archivo: **ESP32_CAM_SETUP.md** para instrucciones completas del código Arduino.

---

## 🎯 Pasos Rápidos

### Sin ESP32-CAM (Probar el sistema)
1. Abre: `http://localhost/mci_madrid_colombia/test_esp32cam.html`
2. Selecciona una foto de tu computadora
3. Haz clic en "Subir Imagen"
4. Ve a la transmisión en vivo para ver tu foto

### Con ESP32-CAM
1. Configura tu ESP32-CAM siguiendo **ESP32_CAM_SETUP.md**
2. Modifica el WiFi y la URL del servidor en el código Arduino
3. Sube el código a tu ESP32-CAM
4. Abre la transmisión en vivo
5. ¡Disfruta tu stream!

---

## 📱 Acceso desde Otros Dispositivos

Para acceder desde tu teléfono u otra computadora en la misma red:

1. Averigua tu IP local:
   - Windows: `ipconfig` en CMD (busca "IPv4")
   - Ejemplo: `192.168.1.100`

2. Cambia `localhost` por tu IP:
   ```
   http://192.168.1.100/mci_madrid_colombia/public/index.php?route=stream/live
   ```

---

## ⚠️ Importante

- ✅ Estas URLs **NO** aparecen en los menús del sistema
- ✅ Son **públicas** (no requieren login)
- ✅ Solo accesibles para quien conozca la URL
- ✅ El sistema guarda las últimas **100 fotos** automáticamente
- ✅ Las fotos más antiguas se eliminan automáticamente

---

## 🆘 Solución Rápida de Problemas

### No puedo acceder a las URLs
```
✓ Verifica que XAMPP Apache esté corriendo
✓ Abre: http://localhost/mci_madrid_colombia/verificar_stream.php
```

### ESP32-CAM no envía fotos
```
✓ Revisa el Monitor Serie de Arduino (115200 baud)
✓ Verifica la URL del servidor
✓ Asegúrate de estar en la misma red WiFi
```

### Las fotos no se guardan
```
✓ Verifica permisos de la carpeta: /public/assets/stream/
✓ Revisa que haya espacio en disco
```

---

## 📚 Documentación Completa

- **ESP32_CAM_SETUP.md** - Configuración completa con código Arduino
- **STREAM_README.md** - Documentación técnica detallada
- **verificar_stream.php** - Diagnóstico del sistema

---

## 🎉 ¡Listo!

Tu sistema de transmisión ESP32-CAM está completamente instalado y configurado.

### Enlaces Rápidos:
- 🚀 [Acceso Rápido](http://localhost/mci_madrid_colombia/stream_access.html)
- 📹 [Ver en Vivo](http://localhost/mci_madrid_colombia/public/index.php?route=stream/live)
- 🧪 [Probar Sistema](http://localhost/mci_madrid_colombia/test_esp32cam.html)
- 🔍 [Verificar](http://localhost/mci_madrid_colombia/verificar_stream.php)

---

**Creado para MCI Madrid Colombia**
