@echo off
title WhatsApp Worker - Vincular y enviar (MCIMadrid)
cd /d "%~dp0"

if not exist logs mkdir logs

echo ==========================================
echo  WhatsApp Worker - MCIMadrid
echo ==========================================
echo.
echo  IMPORTANTE:
echo  - Usa ESTA ventana para escanear el QR (no cierres la ventana negra).
echo  - Si el autostart esta activo, corre en segundo plano SIN QR visible.
echo    Para vincular de nuevo, deten otros workers y usa solo este archivo.
echo.

set WA_HEADLESS=false
set WA_OPEN_QR_BROWSER=1

where node >nul 2>&1
if errorlevel 1 (
    echo ERROR: Node.js no esta en el PATH. Instala Node.js desde https://nodejs.org
    pause
    exit /b 1
)

if not exist node_modules (
    echo Instalando dependencias npm...
    call npm install
    if errorlevel 1 (
        echo ERROR: npm install fallo.
        pause
        exit /b 1
    )
)

echo Iniciando worker (navegador visible + QR en consola y en logs\whatsapp-qr.html)...
echo.
node worker.js
set EXIT_CODE=%ERRORLEVEL%
echo.
if not "%EXIT_CODE%"=="0" (
    echo El worker termino con codigo %EXIT_CODE%.
    echo Revisa el mensaje de arriba. Si pide QR, abre tambien: logs\whatsapp-qr.html
)
pause
exit /b %EXIT_CODE%
