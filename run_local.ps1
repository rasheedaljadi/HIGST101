# Launch Local Laravel Dev Server using local PHP 8.3
$Php83 = "C:\Users\RASHEED\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"

Write-Host "====================================================" -ForegroundColor Cyan
Write-Host " Starting Bagisto Local Server (PHP 8.3) " -ForegroundColor Cyan
Write-Host " Local URL: http://127.0.0.1:8000 " -ForegroundColor Cyan
Write-Host "====================================================" -ForegroundColor Cyan

& $Php83 -S 127.0.0.1:8000 -t public server.php
