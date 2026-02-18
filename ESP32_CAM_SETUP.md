# 📹 Configuración ESP32-CAM para Transmisión en Vivo

## 🎯 Descripción
Este documento contiene las instrucciones completas para configurar tu ESP32-CAM y transmitir video (fotos cada segundo) al servidor.

## 📋 Requisitos

### Hardware
- **ESP32-CAM** (AI-Thinker o similar)
- **Programador FTDI** o **ESP32-CAM-MB** (módulo con USB integrado)
- **Cable USB**
- **Conexión WiFi estable**

### Software
- **Arduino IDE** (versión 1.8.x o 2.x)
- **Librería ESP32** para Arduino
- **Biblioteca HTTPClient** (incluida en ESP32)

---

## 🔧 Configuración del Arduino IDE

### 1. Instalar soporte para ESP32

1. Abrir Arduino IDE
2. Ir a `Archivo > Preferencias`
3. En "Gestor de URLs Adicionales de Tarjetas", agregar:
   ```
   https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json
   ```
4. Ir a `Herramientas > Placa > Gestor de tarjetas`
5. Buscar "ESP32" e instalar "esp32 by Espressif Systems"

### 2. Configurar la placa

1. Ir a `Herramientas > Placa > ESP32 Arduino`
2. Seleccionar: **AI Thinker ESP32-CAM**
3. Configurar:
   - **Upload Speed:** 115200
   - **Flash Frequency:** 80MHz
   - **Flash Mode:** QIO
   - **Partition Scheme:** Huge APP (3MB No OTA)
   - **Core Debug Level:** None

---

## 💻 Código para ESP32-CAM

Copia y pega este código en Arduino IDE:

```cpp
#include <WiFi.h>
#include <HTTPClient.h>
#include "esp_camera.h"

// ========================================
// 🔐 CONFIGURACIÓN - MODIFICA ESTOS VALORES
// ========================================
const char* ssid = "TU_WIFI";           // Nombre de tu red WiFi
const char* password = "TU_PASSWORD";    // Contraseña de tu WiFi
const char* serverUrl = "http://TU_IP/mci_madrid_colombia/api/stream.php";  // URL del servidor

// Intervalo de captura (milisegundos)
const unsigned long captureInterval = 1000; // 1 segundo = 1 foto por segundo

// ========================================
// 📷 CONFIGURACIÓN DE PINES CÁMARA (AI-Thinker ESP32-CAM)
// ========================================
#define PWDN_GPIO_NUM     32
#define RESET_GPIO_NUM    -1
#define XCLK_GPIO_NUM      0
#define SIOD_GPIO_NUM     26
#define SIOC_GPIO_NUM     27
#define Y9_GPIO_NUM       35
#define Y8_GPIO_NUM       34
#define Y7_GPIO_NUM       39
#define Y6_GPIO_NUM       36
#define Y5_GPIO_NUM       21
#define Y4_GPIO_NUM       19
#define Y3_GPIO_NUM       18
#define Y2_GPIO_NUM        5
#define VSYNC_GPIO_NUM    25
#define HREF_GPIO_NUM     23
#define PCLK_GPIO_NUM     22

// LED Flash (GPIO 4)
#define LED_FLASH         4

// Variables globales
unsigned long lastCaptureTime = 0;
bool wifiConnected = false;

void setup() {
  Serial.begin(115200);
  Serial.println("\n\n========================================");
  Serial.println("ESP32-CAM Streaming System");
  Serial.println("========================================\n");
  
  // Configurar LED flash
  pinMode(LED_FLASH, OUTPUT);
  digitalWrite(LED_FLASH, LOW);
  
  // Inicializar cámara
  if (!initCamera()) {
    Serial.println("❌ Error al inicializar la cámara");
    Serial.println("Reiniciando...");
    delay(3000);
    ESP.restart();
  }
  
  // Conectar a WiFi
  connectWiFi();
  
  Serial.println("\n✅ Sistema iniciado correctamente");
  Serial.println("📹 Iniciando transmisión...\n");
}

void loop() {
  // Verificar conexión WiFi
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️ WiFi desconectado. Reconectando...");
    connectWiFi();
  }
  
  // Capturar y enviar foto según el intervalo
  unsigned long currentTime = millis();
  if (currentTime - lastCaptureTime >= captureInterval) {
    captureAndSend();
    lastCaptureTime = currentTime;
  }
  
  delay(10); // Pequeño delay para no saturar el CPU
}

/**
 * Inicializar la cámara con configuración óptima
 */
bool initCamera() {
  Serial.println("📷 Inicializando cámara...");
  
  camera_config_t config;
  config.ledc_channel = LEDC_CHANNEL_0;
  config.ledc_timer = LEDC_TIMER_0;
  config.pin_d0 = Y2_GPIO_NUM;
  config.pin_d1 = Y3_GPIO_NUM;
  config.pin_d2 = Y4_GPIO_NUM;
  config.pin_d3 = Y5_GPIO_NUM;
  config.pin_d4 = Y6_GPIO_NUM;
  config.pin_d5 = Y7_GPIO_NUM;
  config.pin_d6 = Y8_GPIO_NUM;
  config.pin_d7 = Y9_GPIO_NUM;
  config.pin_xclk = XCLK_GPIO_NUM;
  config.pin_pclk = PCLK_GPIO_NUM;
  config.pin_vsync = VSYNC_GPIO_NUM;
  config.pin_href = HREF_GPIO_NUM;
  config.pin_sscb_sda = SIOD_GPIO_NUM;
  config.pin_sscb_scl = SIOC_GPIO_NUM;
  config.pin_pwdn = PWDN_GPIO_NUM;
  config.pin_reset = RESET_GPIO_NUM;
  config.xclk_freq_hz = 20000000;
  config.pixel_format = PIXFORMAT_JPEG;
  
  // Configuración de calidad según PSRAM
  if(psramFound()){
    Serial.println("✓ PSRAM encontrada");
    config.frame_size = FRAMESIZE_UXGA;  // 1600x1200
    config.jpeg_quality = 10;  // 0-63, menor = mejor calidad
    config.fb_count = 2;
  } else {
    Serial.println("⚠️ PSRAM no encontrada, usando configuración básica");
    config.frame_size = FRAMESIZE_SVGA;  // 800x600
    config.jpeg_quality = 12;
    config.fb_count = 1;
  }
  
  // Inicializar cámara
  esp_err_t err = esp_camera_init(&config);
  if (err != ESP_OK) {
    Serial.printf("❌ Error 0x%x al inicializar cámara\n", err);
    return false;
  }
  
  // Configuraciones adicionales del sensor
  sensor_t * s = esp_camera_sensor_get();
  if (s != NULL) {
    // Ajustes opcionales de imagen
    s->set_brightness(s, 0);     // -2 a 2
    s->set_contrast(s, 0);       // -2 a 2
    s->set_saturation(s, 0);     // -2 a 2
    s->set_special_effect(s, 0); // 0 = sin efecto
    s->set_whitebal(s, 1);       // 0 = desactivar, 1 = activar
    s->set_awb_gain(s, 1);       // 0 = desactivar, 1 = activar
    s->set_wb_mode(s, 0);        // 0 a 4
    s->set_exposure_ctrl(s, 1);  // 0 = desactivar, 1 = activar
    s->set_aec2(s, 0);           // 0 = desactivar, 1 = activar
    s->set_ae_level(s, 0);       // -2 a 2
    s->set_aec_value(s, 300);    // 0 a 1200
    s->set_gain_ctrl(s, 1);      // 0 = desactivar, 1 = activar
    s->set_agc_gain(s, 0);       // 0 a 30
    s->set_gainceiling(s, (gainceiling_t)0);  // 0 a 6
    s->set_bpc(s, 0);            // 0 = desactivar, 1 = activar
    s->set_wpc(s, 1);            // 0 = desactivar, 1 = activar
    s->set_raw_gma(s, 1);        // 0 = desactivar, 1 = activar
    s->set_lenc(s, 1);           // 0 = desactivar, 1 = activar
    s->set_hmirror(s, 0);        // 0 = desactivar, 1 = activar
    s->set_vflip(s, 0);          // 0 = desactivar, 1 = activar
    s->set_dcw(s, 1);            // 0 = desactivar, 1 = activar
    s->set_colorbar(s, 0);       // 0 = desactivar, 1 = activar
  }
  
  Serial.println("✅ Cámara inicializada correctamente");
  return true;
}

/**
 * Conectar a la red WiFi
 */
void connectWiFi() {
  Serial.print("📡 Conectando a WiFi: ");
  Serial.println(ssid);
  
  WiFi.begin(ssid, password);
  WiFi.setSleep(false); // Desactivar modo sleep para mejor rendimiento
  
  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(".");
    attempts++;
  }
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println("\n✅ WiFi conectado");
    Serial.print("📍 IP Local: ");
    Serial.println(WiFi.localIP());
    Serial.print("📶 Señal: ");
    Serial.print(WiFi.RSSI());
    Serial.println(" dBm");
    wifiConnected = true;
  } else {
    Serial.println("\n❌ No se pudo conectar a WiFi");
    Serial.println("Reiniciando en 5 segundos...");
    delay(5000);
    ESP.restart();
  }
}

/**
 * Capturar foto y enviar al servidor
 */
void captureAndSend() {
  // Capturar foto
  camera_fb_t * fb = esp_camera_fb_get();
  if (!fb) {
    Serial.println("❌ Error al capturar foto");
    return;
  }
  
  Serial.printf("📸 Foto capturada: %d bytes\n", fb->len);
  
  // Enviar al servidor
  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(serverUrl);
    http.addHeader("Content-Type", "image/jpeg");
    
    // Enviar datos
    int httpResponseCode = http.POST(fb->buf, fb->len);
    
    if (httpResponseCode > 0) {
      Serial.printf("✅ Enviado exitosamente (código: %d)\n", httpResponseCode);
      
      // Opcional: parpadear LED para indicar envío exitoso
      digitalWrite(LED_FLASH, HIGH);
      delay(50);
      digitalWrite(LED_FLASH, LOW);
    } else {
      Serial.printf("❌ Error al enviar (código: %d)\n", httpResponseCode);
      Serial.printf("   Error: %s\n", http.errorToString(httpResponseCode).c_str());
    }
    
    http.end();
  } else {
    Serial.println("⚠️ WiFi desconectado, no se puede enviar");
  }
  
  // Liberar memoria del frame buffer
  esp_camera_fb_return(fb);
  
  Serial.println("---");
}
```

---

## 🚀 Pasos para Programar la ESP32-CAM

### 1. Modificar el código
En las líneas del inicio del código, modifica:
```cpp
const char* ssid = "TU_WIFI";           // Tu red WiFi
const char* password = "TU_PASSWORD";    // Tu contraseña WiFi
const char* serverUrl = "http://TU_IP/mci_madrid_colombia/api/stream.php";
```

**Ejemplo:**
```cpp
const char* ssid = "MiCasaWiFi";
const char* password = "password123";
const char* serverUrl = "http://192.168.1.100/mci_madrid_colombia/api/stream.php";
```

### 2. Conectar la ESP32-CAM

**Si usas programador FTDI:**
- ESP32-CAM → FTDI
- 5V → 5V
- GND → GND
- U0R → TX
- U0T → RX
- IO0 → GND (para modo programación)

**Si usas ESP32-CAM-MB:**
- Solo conecta el cable USB

### 3. Subir el código

1. Abrir Arduino IDE
2. Seleccionar placa: **AI Thinker ESP32-CAM**
3. Seleccionar puerto COM correcto
4. Click en **Subir** (⬆️)
5. Esperar a que compile y suba (puede tomar 1-2 minutos)

### 4. Desconectar IO0 de GND

Si usaste programador FTDI, desconecta el cable de IO0 a GND después de programar.

### 5. Resetear la ESP32-CAM

Presiona el botón RST o desconecta y vuelve a conectar la alimentación.

---

## 🔍 Monitoreo y Depuración

### Ver mensajes del Serial

1. Abrir `Herramientas > Monitor Serie`
2. Configurar velocidad: **115200 baud**
3. Observar los mensajes:

```
========================================
ESP32-CAM Streaming System
========================================

📷 Inicializando cámara...
✓ PSRAM encontrada
✅ Cámara inicializada correctamente
📡 Conectando a WiFi: MiCasaWiFi
......
✅ WiFi conectado
📍 IP Local: 192.168.1.150
📶 Señal: -45 dBm

✅ Sistema iniciado correctamente
📹 Iniciando transmisión...

📸 Foto capturada: 45678 bytes
✅ Enviado exitosamente (código: 200)
---
```

---

## 🌐 URLs de Acceso

Una vez que la ESP32-CAM esté transmitiendo, accede a:

### 📹 Ver transmisión en vivo:
```
http://TU_IP/mci_madrid_colombia/public/index.php?route=stream/live
```

### 🖼️ Ver galería de fotos:
```
http://TU_IP/mci_madrid_colombia/public/index.php?route=stream/gallery
```

### 🔧 API de stream:
```
http://TU_IP/mci_madrid_colombia/api/stream.php
```

---

## ⚙️ Configuración Avanzada

### Cambiar intervalo de captura

Modifica esta línea para cambiar la frecuencia:
```cpp
const unsigned long captureInterval = 1000; // 1000ms = 1 segundo
```

**Ejemplos:**
- `500` = 2 fotos por segundo
- `2000` = 1 foto cada 2 segundos
- `5000` = 1 foto cada 5 segundos

### Cambiar calidad de imagen

En la función `initCamera()`:
```cpp
config.jpeg_quality = 10;  // 0-63, menor = mejor calidad (más pesado)
```

### Cambiar resolución

```cpp
config.frame_size = FRAMESIZE_UXGA;  // 1600x1200
```

**Opciones disponibles:**
- `FRAMESIZE_QQVGA` = 160x120
- `FRAMESIZE_QVGA` = 320x240
- `FRAMESIZE_VGA` = 640x480
- `FRAMESIZE_SVGA` = 800x600
- `FRAMESIZE_XGA` = 1024x768
- `FRAMESIZE_SXGA` = 1280x1024
- `FRAMESIZE_UXGA` = 1600x1200

---

## ❗ Solución de Problemas

### Error: "Brownout detector was triggered"
- **Causa:** Alimentación insuficiente
- **Solución:** Usar fuente de 5V con al menos 1A

### Error: "Camera init failed with error 0x105"
- **Causa:** Pines mal configurados
- **Solución:** Verificar que el modelo sea "AI Thinker ESP32-CAM"

### No se conecta a WiFi
- Verificar nombre y contraseña WiFi
- Asegurarse de usar red 2.4GHz (no 5GHz)
- Acercarse al router

### Fotos no llegan al servidor
- Verificar que la URL sea correcta
- Verificar que Apache esté corriendo
- Revisar permisos de carpeta `/public/assets/stream/`

### Imágenes muy oscuras
- Activar LED flash: `digitalWrite(LED_FLASH, HIGH);` antes de capturar
- Ajustar exposición en configuración del sensor

---

## 📊 Consumo y Rendimiento

- **Consumo:** ~160mA en operación normal, hasta 300mA al transmitir
- **Velocidad:** 1 foto por segundo (configurable)
- **Almacenamiento:** Últimas 100 fotos en servidor
- **Calidad:** JPEG con compresión configurable

---

## 🎓 Recursos Adicionales

- [Documentación ESP32-CAM](https://github.com/espressif/esp32-camera)
- [Arduino ESP32 Core](https://github.com/espressif/arduino-esp32)
- [Random Nerd Tutorials - ESP32-CAM](https://randomnerdtutorials.com/projects-esp32-cam/)

---

## 📝 Notas Importantes

1. **Seguridad:** Estas URLs no tienen autenticación. Para producción, considera agregar un token de acceso.
2. **Rendimiento:** La cámara puede calentarse con uso prolongado.
3. **Almacenamiento:** El sistema mantiene solo las últimas 100 fotos automáticamente.
4. **Red:** Funciona mejor en red local (misma WiFi).

---

¡Tu sistema de transmisión ESP32-CAM está listo! 🎉
