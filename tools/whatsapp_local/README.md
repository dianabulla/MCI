# WhatsApp local worker (sin Meta)

Este worker procesa la tabla whatsapp_local_queue y envía mensajes con whatsapp-web.js.

## Arquitectura para tu caso

- La web corre en Hostinger.
- Tu PC Windows corre este worker 24/7.
- La web escribe en cola y el worker envía automáticamente.

## 1) Configurar entorno

1. Copia .env.example a .env.
2. Configura DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME con la base de datos remota de Hostinger.
3. Define WA_CLIENT_ID para separar sesión por proyecto.
4. Si tu plan exige SSL en MySQL remoto, cambia DB_SSL_MODE a required.

Ejemplo rápido en PowerShell:

```powershell
Copy-Item .env.example .env
```

Opción guiada (recomendada):

```powershell
powershell -ExecutionPolicy Bypass -File .\configure_env.ps1
```

Esto te pide host, usuario, clave y base, y genera el archivo .env automáticamente.

## 1.1) Probar conexión a Hostinger antes de iniciar WhatsApp

```powershell
npm run test-db
```

Si ves Conexion OK, ya puedes iniciar el worker.

## 2) Instalar dependencias

```powershell
npm install
```

## 3) Primer arranque y vinculación WhatsApp

```powershell
npm start
```

Al primer arranque se imprime un QR en consola. Escanéalo con el WhatsApp emisor.

## 4) Autoarranque en Windows (sin intervención humana)

Instala una tarea programada para arrancar el worker al iniciar sesión:

```powershell
powershell -ExecutionPolicy Bypass -File .\install_autostart_task.ps1
```

Esto crea la tarea MCIMadrid-WhatsappLocalWorker.

Logs del worker:

- logs/worker.log
- logs/autostart.log

Si Windows bloquea la creación de la tarea por permisos, el instalador intenta un fallback con `schtasks` para el usuario actual.
Si también falla, configura autoarranque por usuario con clave `HKCU\...\Run` y acceso directo en la carpeta Inicio.

Verificación rápida después de instalar:

```powershell
schtasks /Query /TN "MCIMadrid-WhatsappLocalWorker" /V /FO LIST
Get-Content .\logs\autostart.log -Tail 50
```

Para quitar la tarea:

```powershell
powershell -ExecutionPolicy Bypass -File .\uninstall_autostart_task.ps1
```

## 5) Estructura de estados de cola

- pendiente: listo para enviar.
- procesando: tomado por el worker.
- enviado: entrega confirmada por cliente WhatsApp.
- fallido: envío falló, revisar ultimo_error.

## 6) Origen de mensajes automáticos

Los mensajes se encolan desde PersonaController en estos eventos:

- Creación de persona: bienvenida + notificaciones de asignación.
- Edición de persona: notificaciones cuando cambia líder y/o ministerio.

Además, el worker encola automáticamente felicitaciones de cumpleaños cada día:

- Revisa personas con fecha de nacimiento del día.
- Usa la plantilla felicitacion_cumpleanos (editable en Personas > Plantillas mensaje what).
- Evita duplicados por persona y fecha.

## 7) Notas operativas

- Este flujo no usa API de Meta, Twilio ni 360dialog.
- Si tu PC está apagado, los mensajes quedan en cola y salen al volver a encender.
- Asegura en Hostinger que la base acepte conexiones remotas desde la IP de tu PC.

## 8) Control de ritmo de envíos

Para evitar que salgan todos los mensajes de una vez, el worker aplica una pausa aleatoria entre mensajes.

- Por defecto: entre 1 y 3 minutos.
- Variables en `.env`:
	- `WA_DELAY_MIN_MS=60000`
	- `WA_DELAY_MAX_MS=180000`
- Si prefieres tiempo fijo, puedes usar `WA_DELAY_MS` (compatibilidad), por ejemplo `WA_DELAY_MS=120000`.
