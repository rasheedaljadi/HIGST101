# Makes PHP 8.3.30 the default `php` by prepending its directory to the Machine PATH.
# Reversible: a backup of the original Machine PATH is written next to this script.
$ErrorActionPreference = 'Stop'
$logPath    = Join-Path $PSScriptRoot 'set-php83-default.log'
$backupPath = Join-Path $PSScriptRoot 'machine-path.backup.txt'
$php83Dir   = 'C:\Users\RASHEED\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe'

try {
    $machine = [Environment]::GetEnvironmentVariable('Path','Machine')

    # Backup original (only once).
    if (-not (Test-Path $backupPath)) {
        Set-Content -Path $backupPath -Value $machine -NoNewline
    }

    # Split, drop any existing copy of the 8.3 dir, then prepend it.
    $parts = $machine -split ';' | Where-Object { $_ -ne '' -and $_.TrimEnd('\') -ne $php83Dir.TrimEnd('\') }
    $newPath = (@($php83Dir) + $parts) -join ';'

    [Environment]::SetEnvironmentVariable('Path', $newPath, 'Machine')

    "OK`r`nNEW_MACHINE_PATH=$newPath" | Set-Content -Path $logPath -NoNewline
} catch {
    "ERROR: $($_.Exception.Message)" | Set-Content -Path $logPath -NoNewline
    exit 1
}
