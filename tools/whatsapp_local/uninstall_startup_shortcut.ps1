$shortcutPath = Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs\Startup\MCIMadrid WhatsApp Worker.lnk'

if (Test-Path $shortcutPath) {
    Remove-Item $shortcutPath -Force
    Write-Host "Acceso directo eliminado: $shortcutPath"
} else {
    Write-Host 'No existe acceso directo de autoarranque en Inicio.'
}
