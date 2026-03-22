@echo off
setlocal

cd /d "%~dp0"

if not exist logs mkdir logs

echo [%date% %time%] Iniciando bootstrap...>> logs\autostart.log
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0start_worker_windows.ps1" >> logs\autostart.log 2>&1
echo [%date% %time%] Bootstrap finalizado.>> logs\autostart.log
