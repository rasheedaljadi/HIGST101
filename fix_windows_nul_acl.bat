@echo off
echo Fixing Windows NUL Device ACL Permissions...
sc.exe sdset null D:(A;;GA;;;WD)(A;;GA;;;SY)(A;;GA;;;BA)(A;;GA;;;BU)
if %ERRORLEVEL% EQU 0 (
    echo [SUCCESS] Windows NUL Device permissions restored successfully!
) else (
    echo [ERROR] Failed to set permissions. Please run this script AS ADMINISTRATOR.
)
pause
