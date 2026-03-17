@echo off
setlocal

cd /d "%~dp0"

if not exist logs mkdir logs

echo [%date% %time%] Iniciando worker...>> logs\worker.log
npm start >> logs\worker.log 2>&1

echo [%date% %time%] Worker finalizado.>> logs\worker.log
