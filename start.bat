@echo off
setlocal EnableExtensions EnableDelayedExpansion
title EduCare CRM Launcher

set "PROJECT_DIR=%~dp0"
set "HOST=127.0.0.1"
set "PORT=8000"
set "APP_URL=http://%HOST%:%PORT%/consultation.html"
set "PHP_EXE="
set "MYSQL_EXE="

rem ====================================================
rem DATABASE CONFIGURATION:
rem Set your MySQL credentials below.
rem If using default XAMPP (no password), set EDUCARE_DB_PASS=
rem ====================================================
if not defined EDUCARE_DB_USER set "EDUCARE_DB_USER=root"
if not defined EDUCARE_DB_PASS set "EDUCARE_DB_PASS=123456"

echo ========================================
echo          EDUCARE CRM LAUNCHER
echo ========================================
echo.

rem Check PHP from PATH first
where php.exe >nul 2>&1
if not errorlevel 1 set "PHP_EXE=php.exe"

rem Check known PHP installations (XAMPP / Laragon)
if not defined PHP_EXE if exist "D:\xampp\php\php.exe" set "PHP_EXE=D:\xampp\php\php.exe"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "C:\laragon\bin\php\php.exe" set "PHP_EXE=C:\laragon\bin\php\php.exe"
if not defined PHP_EXE if exist "D:\laragon\bin\php\php.exe" set "PHP_EXE=D:\laragon\bin\php\php.exe"

if not defined PHP_EXE (
    echo [ERROR] PHP was not found.
    echo Please install XAMPP or PHP and run this file again.
    pause
    exit /b 1
)

echo [OK] PHP: %PHP_EXE%
echo [OK] Project: %PROJECT_DIR%
echo.

rem Check MySQL client (XAMPP or Oracle MySQL)
where mysql.exe >nul 2>&1
if not errorlevel 1 set "MYSQL_EXE=mysql.exe"
if not defined MYSQL_EXE if exist "C:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL_EXE if exist "D:\xampp\mysql\bin\mysql.exe" set "MYSQL_EXE=D:\xampp\mysql\bin\mysql.exe"
if not defined MYSQL_EXE if exist "C:\Program Files\MySQL\MySQL Server 9.4\bin\mysql.exe" set "MYSQL_EXE=C:\Program Files\MySQL\MySQL Server 9.4\bin\mysql.exe"
if not defined MYSQL_EXE if exist "C:\Program Files\MySQL" (
    for /f "delims=" %%M in ('dir /b /s "C:\Program Files\MySQL\MySQL Server *\bin\mysql.exe" 2^>nul') do if not defined MYSQL_EXE set "MYSQL_EXE=%%M"
)

rem Check MySQL port 3306
set "DB_PORT=3306"
netstat -ano | findstr /r /c:":%DB_PORT% .*LISTENING" >nul 2>&1
if not errorlevel 1 (
    echo [OK] MySQL Server is running on port %DB_PORT%.
) else (
    echo [INFO] Starting MySQL service...
    for %%S in (MySQL94 MySQL84 MySQL80 MySQL) do (
        sc query "%%S" >nul 2>&1
        if not errorlevel 1 (
            net start "%%S" >nul 2>&1
        )
    )
    timeout /t 3 /nobreak >nul
    netstat -ano | findstr /r /c:":%DB_PORT% .*LISTENING" >nul 2>&1
    if errorlevel 1 (
        echo [WARNING] MySQL is not running on port %DB_PORT%.
        echo If using XAMPP, please start MySQL in XAMPP Control Panel.
    ) else (
        echo [OK] MySQL Server started.
    )
)

rem Import database schema if not exists
netstat -ano | findstr /r /c:":%DB_PORT% .*LISTENING" >nul 2>&1
if not errorlevel 1 if defined MYSQL_EXE if exist "%PROJECT_DIR%database\educare_crm.sql" (
    "%MYSQL_EXE%" --protocol=TCP --host=127.0.0.1 --port=%DB_PORT% --user=%EDUCARE_DB_USER% --password=%EDUCARE_DB_PASS% --skip-column-names --execute="SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='educare_crm'" 2>nul | findstr /x "educare_crm" >nul
    if errorlevel 1 (
        echo [INFO] Database educare_crm not found. Importing schema...
        "%MYSQL_EXE%" --protocol=TCP --host=127.0.0.1 --port=%DB_PORT% --user=%EDUCARE_DB_USER% --password=%EDUCARE_DB_PASS% < "%PROJECT_DIR%database\educare_crm.sql"
        if errorlevel 1 (
            echo [WARNING] Could not auto-import database\educare_crm.sql.
            echo Please import database\educare_crm.sql manually via phpMyAdmin or MySQL Workbench.
        ) else (
            echo [OK] Database educare_crm imported successfully.
        )
    ) else (
        echo [OK] Database educare_crm is ready.
    )
)

rem Seed demo data if available
if exist "%PROJECT_DIR%database\seed_demo_data.php" (
    "%PHP_EXE%" "%PROJECT_DIR%database\seed_demo_data.php" >nul 2>&1
)

rem Check if PHP server is already running on port 8000
netstat -ano | findstr /r /c:":%PORT% .*LISTENING" >nul 2>&1
if not errorlevel 1 (
    echo [INFO] PHP server is already running on port %PORT%.
    echo Opening browser at %APP_URL%...
    start "" "%APP_URL%"
    timeout /t 2 /nobreak >nul
    exit /b 0
)

echo [INFO] Starting PHP server at %APP_URL%...
pushd "%PROJECT_DIR%"
start "EduCare CRM PHP Server" cmd /k ""%PHP_EXE%" -S %HOST%:%PORT% -t "%PROJECT_DIR%""
popd

timeout /t 2 /nobreak >nul
start "" "%APP_URL%"

echo.
echo [OK] EduCare CRM opened in browser.
echo Keep the "EduCare CRM PHP Server" window open.
echo.
timeout /t 3 /nobreak >nul
endlocal
