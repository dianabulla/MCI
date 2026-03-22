$ErrorActionPreference = 'Stop'

$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $scriptDir

$logDir = Join-Path $scriptDir 'logs'
if (!(Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

$bootstrapLog = Join-Path $logDir 'autostart.log'
$workerLog = Join-Path $logDir 'worker.log'

function Write-BootstrapLog {
    param([string]$Message)

    $timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    Add-Content -Path $bootstrapLog -Value "[$timestamp] $Message"
}

function Resolve-NodeExe {
    $candidates = @(
        (Get-Command node.exe -ErrorAction SilentlyContinue | Select-Object -ExpandProperty Source -ErrorAction SilentlyContinue),
        'C:\Program Files\nodejs\node.exe',
        'C:\Program Files (x86)\nodejs\node.exe',
        (Join-Path $env:ProgramFiles 'nodejs\node.exe'),
        (Join-Path ${env:ProgramFiles(x86)} 'nodejs\node.exe')
    ) | Where-Object { $_ -and (Test-Path $_) } | Select-Object -Unique

    $resolved = @($candidates)
    if ($resolved.Count -gt 0) {
        return [string]$resolved[0]
    }

    throw 'No se encontró node.exe. Instala Node.js o ajusta la ruta en start_worker_windows.ps1.'
}

[string]$nodeExe = Resolve-NodeExe
$workerScript = Join-Path $scriptDir 'worker.js'

if (!(Test-Path $workerScript)) {
    throw "No se encontró worker.js en $scriptDir"
}

Write-BootstrapLog ('Bootstrap iniciado. Node={0}' -f $nodeExe)

while ($true) {
    Write-BootstrapLog 'Lanzando worker.js'
    Add-Content -Path $workerLog -Value ('[{0}] Iniciando worker con {1}' -f (Get-Date -Format 'yyyy-MM-dd HH:mm:ss'), $nodeExe)

    & $nodeExe $workerScript >> $workerLog 2>&1
    $exitCode = $LASTEXITCODE

    Write-BootstrapLog "worker.js finalizó con código $exitCode"

    if ($exitCode -eq 0) {
        Write-BootstrapLog 'Salida limpia detectada. Reinicio en 10 segundos para mantener worker activo.'
    } else {
        Write-BootstrapLog 'Salida con error. Reintento en 15 segundos.'
    }

    Start-Sleep -Seconds ($(if ($exitCode -eq 0) { 10 } else { 15 }))
}