@echo off
title Probar conexion DB - MCIMadrid WhatsApp
cd /d "%~dp0"
echo ==========================================
echo  Probando conexion a base de datos remota
echo ==========================================
echo.
npm run test-db
echo.
echo ==========================================
echo  Si viste "Conexion OK", todo va bien.
echo ==========================================
pause
