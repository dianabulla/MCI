param(
    [string]$DbHost,
    [int]$DbPort = 3306,
    [string]$DbName,
    [string]$DbUser,
    [string]$DbPass,
    [ValidateSet('disabled','required')]
    [string]$DbSslMode = 'disabled',
    [ValidateSet('true','false')]
    [string]$DbSslRejectUnauthorized = 'false',
    [string]$WaClientId = 'mcimadrid_hostinger',
    [ValidateSet('true','false')]
    [string]$WaHeadless = 'true',
    [int]$WaBatchLimit = 20,
    [int]$WaDelayMs = 3500,
    [int]$WaPollMs = 5000
)

$ErrorActionPreference = 'Stop'
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$envPath = Join-Path $scriptDir '.env'

if ([string]::IsNullOrWhiteSpace($DbHost)) {
    $DbHost = Read-Host 'DB_HOST (ej: srvXXXX.hstgr.io)'
}
if ([string]::IsNullOrWhiteSpace($DbName)) {
    $DbName = Read-Host 'DB_NAME'
}
if ([string]::IsNullOrWhiteSpace($DbUser)) {
    $DbUser = Read-Host 'DB_USER'
}
if ([string]::IsNullOrWhiteSpace($DbPass)) {
    $secure = Read-Host 'DB_PASS' -AsSecureString
    $ptr = [System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        $DbPass = [System.Runtime.InteropServices.Marshal]::PtrToStringBSTR($ptr)
    } finally {
        [System.Runtime.InteropServices.Marshal]::ZeroFreeBSTR($ptr)
    }
}

$envContent = @(
    "DB_HOST=$DbHost"
    "DB_PORT=$DbPort"
    "DB_USER=$DbUser"
    "DB_PASS=$DbPass"
    "DB_NAME=$DbName"
    ""
    "DB_CONNECT_TIMEOUT_MS=15000"
    "DB_SSL_MODE=$DbSslMode"
    "DB_SSL_REJECT_UNAUTHORIZED=$DbSslRejectUnauthorized"
    ""
    "WA_CLIENT_ID=$WaClientId"
    "WA_HEADLESS=$WaHeadless"
    "WA_BATCH_LIMIT=$WaBatchLimit"
    "WA_DELAY_MS=$WaDelayMs"
    "WA_POLL_MS=$WaPollMs"
) -join [Environment]::NewLine

Set-Content -Path $envPath -Value $envContent -Encoding UTF8
Write-Host ".env generado en: $envPath"
Write-Host "Siguiente paso: npm run test-db"
