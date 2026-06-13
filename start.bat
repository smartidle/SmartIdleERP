@echo off
title SmartIdle ERP System
chcp 65001 >nul
echo ============================================
echo   SmartIdle ERP System
echo ============================================
echo.

REM Set project directory and PHP path
set "PROJECT_DIR=%~dp0"
set "PHP_EXE=C:\xampp\php\php.exe"

REM Remove trailing backslash
if "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

echo [INFO] Project: %PROJECT_DIR%
echo [INFO] PHP: %PHP_EXE%
echo.

REM Check PHP
"%PHP_EXE%" -v >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Cannot run PHP at: %PHP_EXE%
    echo.
    echo Please ensure XAMPP with PHP 8.2+ is installed.
    echo Download: https://www.apachefriends.org/
    echo.
    pause
    exit /b 1
)
echo [OK] PHP found.

REM Check if .env exists, create from example if not
if not exist "%PROJECT_DIR%\.env" (
    echo.
    echo [1/4] Creating environment file...
    copy "%PROJECT_DIR%\.env.example" "%PROJECT_DIR%\.env" >nul
    if errorlevel 1 (
        echo [ERROR] Failed to create .env file!
        pause
        exit /b 1
    )
    echo       .env created from .env.example
)

REM Generate application key if not set
"%PHP_EXE%" "%PROJECT_DIR%\artisan" key:generate --force >nul 2>&1

REM Check if database file exists, create if not
if not exist "%PROJECT_DIR%\database\database.sqlite" (
    echo.
    echo [2/4] Initializing database...
    echo. > "%PROJECT_DIR%\database\database.sqlite"
    echo       Database file created.
) else (
    echo [2/4] Database ready.
)

REM Run migrations
echo.
echo [3/4] Running migrations...
"%PHP_EXE%" "%PROJECT_DIR%\artisan" migrate --force >nul 2>&1
if errorlevel 1 (
    echo [WARNING] Migration issue detected, running fresh migration...
    "%PHP_EXE%" "%PROJECT_DIR%\artisan" migrate:fresh --force
    if errorlevel 1 (
        echo [ERROR] Database migration failed!
        pause
        exit /b 1
    )
)
echo       Migrations complete.

REM Check if seed data exists
for /f %%i in ('"%PHP_EXE%" "%PROJECT_DIR%\artisan" tinker --execute="echo \App\Models\Product::count();" 2^>nul') do set PRODUCT_COUNT=%%i
if "%PRODUCT_COUNT%"=="0" (
    echo.
    echo [4/4] Seeding demo data...
    "%PHP_EXE%" "%PROJECT_DIR%\artisan" db:seed --force
    echo       Demo data seeded.
) else (
    echo [4/4] Demo data already exists (%PRODUCT_COUNT% products).
)

echo.
echo ============================================
echo   Starting SmartIdle ERP Server...
echo ============================================
echo.
echo   URL:       http://localhost:8000
echo   Login:     admin@erp.com / admin123
echo.
echo   Press Ctrl+C to stop.
echo ============================================
echo.

start http://localhost:8000

"%PHP_EXE%" "%PROJECT_DIR%\artisan" serve --host=localhost --port=8000

echo.
echo [Server stopped]
pause
