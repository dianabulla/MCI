$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$psPath = Join-Path $scriptDir 'start_worker_windows.ps1'
if (!(Test-Path $psPath)) {
    throw "No se encontró start_worker_windows.ps1 en $scriptDir"
}

$startupDir = Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs\Startup'
if (!(Test-Path $startupDir)) {
    New-Item -ItemType Directory -Path $startupDir -Force | Out-Null
}

$shortcutPath = Join-Path $startupDir 'MCIMadrid WhatsApp Worker.lnk'
$wsh = New-Object -ComObject WScript.Shell
$shortcut = $wsh.CreateShortcut($shortcutPath)
$shortcut.TargetPath = 'powershell.exe'
$shortcut.Arguments = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$psPath`""
$shortcut.WorkingDirectory = $scriptDir
$shortcut.WindowStyle = 7
$shortcut.Description = 'Inicia el worker local de WhatsApp para MCIMadrid'
$shortcut.Save()

Write-Host "Acceso directo instalado en Inicio: $shortcutPath"
Write-Host 'Se ejecutará automáticamente al iniciar sesión del usuario actual.'
