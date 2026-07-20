@echo off
REM ============================================================
REM  AliExpress local environment launcher
REM  Starts: MySQL (XAMPP) + Laravel server + static ngrok tunnel
REM ============================================================

setlocal

set "PHP_BIN=C:\Users\RASHEED\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.3_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe"
set "NGROK_BIN=%LOCALAPPDATA%\ngrok\ngrok.exe"
set "NGROK_DOMAIN=zoologist-decathlon-eclair.ngrok-free.dev"
set "APP_PORT=8000"

echo ============================================================
echo  Starting AliExpress local environment
echo ============================================================

REM --- 1. MySQL (XAMPP) -------------------------------------------------
echo.
echo [1/3] Checking MySQL on port 3306 ...
powershell -NoProfile -Command "if((Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue).TcpTestSucceeded){ exit 0 } else { exit 1 }"
if %errorlevel%==0 (
    echo       MySQL already running.
) else (
    echo       MySQL not running. Starting XAMPP MySQL ...
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "XAMPP MySQL" /D "C:\xampp\mysql\bin" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini --standalone
        echo       Waiting for MySQL to accept connections ...
        powershell -NoProfile -Command "$ok=$false; for($i=0;$i -lt 30;$i++){ if((Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -WarningAction SilentlyContinue).TcpTestSucceeded){ $ok=$true; break }; Start-Sleep -Seconds 1 }; if($ok){ exit 0 } else { exit 1 }"
        if %errorlevel%==0 ( echo       MySQL is up. ) else ( echo       WARNING: MySQL did not come up. Start it manually from XAMPP. )
    ) else (
        echo       XAMPP not found at C:\xampp. Start MySQL manually ^(XAMPP/Laragon^).
    )
)

REM --- 2. Laravel server ------------------------------------------------
echo.
echo [2/3] Starting Laravel server on http://127.0.0.1:%APP_PORT% ...
start "Laravel Server" cmd /k ""%PHP_BIN%" artisan serve --host=127.0.0.1 --port=%APP_PORT%"

REM --- 3. ngrok static tunnel ------------------------------------------
echo.
echo [3/3] Starting ngrok static tunnel ...
start "ngrok Tunnel" cmd /k ""%NGROK_BIN%" http %APP_PORT% --domain=%NGROK_DOMAIN%"

echo.
echo ============================================================
echo  Done. Two new windows opened (Laravel + ngrok).
echo.
echo  Connect : https://%NGROK_DOMAIN%/aliexpress/connect
echo  Callback: https://%NGROK_DOMAIN%/aliexpress/callback
echo ============================================================
echo.
echo  Close those windows to stop the services.
pause

endlocal
