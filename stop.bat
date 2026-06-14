@echo off
title Stop SmartIdle ERP

echo ============================================
echo   Stopping SmartIdle ERP Server
echo ============================================
echo.

REM Kill processes using port 8000
for /f "tokens=5" %%a in ('netstat -ano ^| findstr :8000 ^| findstr LISTENING') do (
    echo [INFO] Stopping process on PID: %%a
    taskkill /F /PID %%a >nul 2>&1
)

echo.
echo [OK] Server stopped.
echo.
pause
