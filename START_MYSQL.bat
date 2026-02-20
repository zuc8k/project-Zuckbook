@echo off
REM Start MySQL in XAMPP

echo.
echo ========================================
echo   Starting MySQL...
echo ========================================
echo.

REM Check if XAMPP is installed
if not exist "C:\xampp\mysql\bin\mysqld.exe" (
    echo ERROR: XAMPP MySQL not found
    echo Please install XAMPP first
    pause
    exit /b 1
)

REM Start MySQL
cd C:\xampp\mysql\bin
mysqld.exe --port=3306

pause
