@echo off
title Iniciar WhatsApp Worker - MCIMadrid
cd /d "%~dp0"
echo ==========================================
echo  Iniciando WhatsApp worker...
echo ==========================================
echo.
echo  1) Espera el QR en pantalla
echo  2) Escanealo con el WhatsApp emisor
echo  3) Debe aparecer: "WhatsApp conectado. Worker activo."
echo.
npm start
echo.
echo Si se cerro por error, revisa el mensaje de arriba.
pause
