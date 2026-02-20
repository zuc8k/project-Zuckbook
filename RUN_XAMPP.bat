@echo off
REM ZuckBook XAMPP Startup

echo.
echo ========================================
echo   ZuckBook - XAMPP Startup
echo ========================================
echo.

REM Check if XAMPP is installed
if not exist "C:\xampp\apache\bin\httpd.exe" (
    echo ERROR: XAMPP not found at C:\xampp
    echo Please install XAMPP first
    pause
    exit /b 1
)

echo [1/3] Starting MySQL...
cd C:\xampp
mysql_start.bat

echo [2/3] Starting Apache...
apache_start.bat

echo [3/3] Starting Socket Server...
cd %~dp0socket-server
if not exist node_modules (
    echo Installing dependencies...
    call npm install socket.io
)
start cmd /k npm start

echo.
echo ========================================
echo   ZuckBook is Ready!
echo ========================================
echo.
echo Access at: http://localhost:8080
echo.
echo Services:
echo   - Apache: http://localhost:8080
echo   - MySQL: localhost:3306
echo   - Socket: http://localhost:3000
echo.
pause
