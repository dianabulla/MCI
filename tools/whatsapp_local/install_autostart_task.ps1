param(
    [string]$TaskName = "MCIMadrid-WhatsappLocalWorker"
)

$ErrorActionPreference = "Stop"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$cmdPath = Join-Path $scriptDir "start_worker_windows.cmd"

if (!(Test-Path $cmdPath)) {
    throw "No se encontró start_worker_windows.cmd en $scriptDir"
}

$action = New-ScheduledTaskAction -Execute "cmd.exe" -Argument "/c \"$cmdPath\""
$trigger = New-ScheduledTaskTrigger -AtLogOn

$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1)

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $action `
    -Trigger $trigger `
    -Settings $settings `
    -Description "Ejecuta el worker de WhatsApp local para MCIMadrid" `
    -Force | Out-Null

Write-Host "Tarea instalada: $TaskName"
Write-Host "Se ejecutará al iniciar sesión en Windows."
