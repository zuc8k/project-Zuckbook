#!/bin/bash

# ZuckBook Startup Script for Linux/Mac

echo ""
echo "========================================"
echo "  ZuckBook - Complete Startup"
echo "========================================"
echo ""

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "ERROR: Docker is not installed"
    echo "Please install Docker from https://www.docker.com/products/docker-desktop"
    exit 1
fi

echo "[1/4] Stopping any existing containers..."
docker-compose down 2>/dev/null

echo "[2/4] Building and starting services..."
docker-compose up -d

echo ""
echo "[3/4] Waiting for services to be ready..."
sleep 5

echo "[4/4] Checking service status..."
docker-compose ps

echo ""
echo "========================================"
echo "  ZuckBook is Ready!"
echo "========================================"
echo ""
echo "Access the application at:"
echo "  http://localhost:8080"
echo ""
echo "Services running:"
echo "  - Apache (PHP): http://localhost:8080"
echo "  - MySQL: localhost:3307"
echo "  - Socket Server: http://localhost:3000"
echo ""
echo "To view logs:"
echo "  docker-compose logs -f"
echo ""
echo "To stop the project:"
echo "  docker-compose down"
echo ""
