param(
    [string]$TaskName = "MCIMadrid-WhatsappLocalWorker"
)

$ErrorActionPreference = "Stop"
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$cmdPath = Join-Path $scriptDir "start_worker_windows.cmd"
$psPath = Join-Path $scriptDir "start_worker_windows.ps1"
$runValueName = 'MCIMadridWhatsappLocalWorker'

if (!(Test-Path $cmdPath)) {
    throw "No se encontró start_worker_windows.cmd en $scriptDir"
}

if (!(Test-Path $psPath)) {
    throw "No se encontró start_worker_windows.ps1 en $scriptDir"
}

function Install-StartupShortcutFallback {
    param([string]$ScriptDir, [string]$BootstrapPath)

    $startupDir = Join-Path $env:APPDATA 'Microsoft\Windows\Start Menu\Programs\Startup'
    if (!(Test-Path $startupDir)) {
        New-Item -ItemType Directory -Path $startupDir -Force | Out-Null
    }

    $shortcutPath = Join-Path $startupDir 'MCIMadrid WhatsApp Worker.lnk'
    $wsh = New-Object -ComObject WScript.Shell
    $shortcut = $wsh.CreateShortcut($shortcutPath)
    $shortcut.TargetPath = 'powershell.exe'
    $shortcut.Arguments = ('-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "{0}"' -f $BootstrapPath)
    $shortcut.WorkingDirectory = $ScriptDir
    $shortcut.WindowStyle = 7
    $shortcut.Description = 'Inicia el worker local de WhatsApp para MCIMadrid'
    $shortcut.Save()

    return $shortcutPath
}

function Install-RunKeyFallback {
    param([string]$BootstrapPath)

    $runKeyPath = 'HKCU:\Software\Microsoft\Windows\CurrentVersion\Run'
    if (!(Test-Path $runKeyPath)) {
        New-Item -Path $runKeyPath -Force | Out-Null
    }

    $runValue = ('powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "{0}"' -f $BootstrapPath)
    Set-ItemProperty -Path $runKeyPath -Name $runValueName -Value $runValue
    return $runValue
}

$taskCreated = $false
$fallbackInstalled = $false

try {
    $psArgs = ('-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "{0}"' -f $psPath)
    $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument $psArgs
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

    $taskCreated = $true
    Write-Host "Tarea instalada con Register-ScheduledTask: $TaskName"
} catch {
    Write-Warning "Register-ScheduledTask falló: $($_.Exception.Message)"
    Write-Host "Intentando crear tarea con schtasks para el usuario actual..."

    $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
    $taskCommand = ('powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "{0}"' -f $psPath)
    $taskArgs = @(
        '/Create',
        '/F',
        '/SC', 'ONLOGON',
        '/TN', $TaskName,
        '/TR', $taskCommand,
        '/RL', 'LIMITED',
        '/RU', $currentUser
    )

    & schtasks.exe $taskArgs | Out-Null
    if ($LASTEXITCODE -eq 0) {
        $taskCreated = $true
        Write-Host "Tarea instalada con schtasks: $TaskName"
    } else {
        Write-Warning 'No se pudo crear la tarea programada automáticamente con schtasks.'
    }
}

if ($taskCreated) {
    Write-Host "Se ejecutará al iniciar sesión en Windows."
} else {
    $shortcutPath = Install-StartupShortcutFallback -ScriptDir $scriptDir -BootstrapPath $psPath
    $runValue = Install-RunKeyFallback -BootstrapPath $psPath
    $fallbackInstalled = $true
    Write-Warning 'La tarea programada no pudo instalarse por permisos. Se configuró autoarranque por usuario con HKCU Run y carpeta Inicio.'
    Write-Host "Shortcut Inicio: $shortcutPath"
    Write-Host "Run key: $runValueName => $runValue"
}

if ($fallbackInstalled) {
    Write-Host 'El worker se lanzará al iniciar sesión del usuario actual.'
}
