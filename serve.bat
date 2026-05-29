@echo off
title AnimusFlowStudio — Dev Server :8001
set PHP=C:\Users\samso\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.2_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe

echo.
echo  ==========================================
echo   AnimusFlowStudio  ^|  http://127.0.0.1:8001
echo  ==========================================
echo.

cd /d "%~dp0"
"%PHP%" -S 127.0.0.1:8001 -t public public/server.php
pause
