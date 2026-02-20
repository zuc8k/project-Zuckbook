@echo off
REM ZuckBook Startup Script for Windows

echo.
echo ========================================
echo   ZuckBook - Complete Startup
echo ========================================
echo.

REM Check if Docker is installed
docker --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Docker is not installed or not in PATH
    echo Please install Docker Desktop from https://www.docker.com/products/docker-desktop
    pause
    exit /b 1
)

echo [1/4] Stopping any existing containers...
docker-compose down 2>nul

echo [2/4] Building and starting services...
docker-compose up -d

echo.
echo [3/4] Waiting for services to be ready...
timeout /t 5 /nobreak

echo [4/4] Checking service status...
docker-compose ps

echo.
echo ========================================
echo   ZuckBook is Ready!
echo ========================================
echo.
echo Access the application at:
echo   http://localhost:8080
echo.
echo Services running:
echo   - Apache (PHP): http://localhost:8080
echo   - MySQL: localhost:3307
echo   - Socket Server: http://localhost:3000
echo.
echo To view logs:
echo   docker-compose logs -f
echo.
echo To stop the project:
echo   docker-compose down
echo.
pause
