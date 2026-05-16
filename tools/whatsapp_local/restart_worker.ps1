# Reinicia el worker de WhatsApp local (MCIMadrid).
# Uso (PowerShell): cd tools\whatsapp_local ; .\restart_worker.ps1
# Opcional: .\restart_worker.ps1 -TailLog 50

param(
    [string]$TaskName = 'MCIMadrid-WhatsappLocalWorker',
    [int]$TailLog = 40
)

$ErrorActionPreference = 'Continue'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptDir

$workerLog = Join-Path $scriptDir 'logs\worker.log'
$autoLog = Join-Path $scriptDir 'logs\autostart.log'

function Read-LogTail {
    param([string]$Path, [int]$Tail)
    if (!(Test-Path $Path)) { return }
    try {
        Get-Content -LiteralPath $Path -Tail $Tail -Encoding Unicode -ErrorAction Stop
        return
    } catch {}
    try {
        Get-Content -LiteralPath $Path -Tail $Tail -Encoding Utf8 -ErrorAction Stop
        return
    } catch {}
    Get-Content -LiteralPath $Path -Tail $Tail -ErrorAction SilentlyContinue
}

function Stop-NodeWorkerProcesses {
    # CommandLine en WMI suele mezclar barras; filtramos por worker.js y carpeta whatsapp_local.
    Get-CimInstance Win32_Process -Filter "Name='node.exe'" -ErrorAction SilentlyContinue |
        Where-Object { $_.CommandLine -and ($_.CommandLine -match 'worker\.js') -and ($_.CommandLine -match 'whatsapp_local') } |
        ForEach-Object {
            Write-Host "Deteniendo PID $($_.ProcessId) (node worker.js)"
            Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue
        }
}

$task = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
if ($task) {
    Write-Host "Reiniciando tarea programada: $TaskName"
    Stop-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    Start-Sleep -Seconds 3
    Stop-NodeWorkerProcesses
    Start-Sleep -Seconds 2
    Start-ScheduledTask -TaskName $TaskName
    Write-Host "Tarea lanzada. Espera unos segundos y revisa los logs."
} else {
    Write-Host "No existe la tarea '$TaskName'. Deteniendo procesos Node que ejecuten worker.js en esta carpeta..."
    Stop-NodeWorkerProcesses
    Write-Host ""
    Write-Host "Inicia el worker manualmente, por ejemplo:"
    Write-Host "  Doble clic en: $scriptDir\01_INICIAR_WHATSAPP.cmd"
    Write-Host "  o en PowerShell: cd `"$scriptDir`" ; npm start"
}

Write-Host ""
Write-Host "===== Ultimas lineas: logs\worker.log ====="
if (Test-Path $workerLog) {
    Read-LogTail -Path $workerLog -Tail $TailLog
} else {
    Write-Host "(archivo no existe todavia)"
}

Write-Host ""
Write-Host "===== Ultimas lineas: logs\autostart.log ====="
if (Test-Path $autoLog) {
    Read-LogTail -Path $autoLog -Tail ([Math]::Min(20, $TailLog))
} else {
    Write-Host "(archivo no existe todavia)"
}
