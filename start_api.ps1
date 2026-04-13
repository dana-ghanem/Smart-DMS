# Startup script for FastAPI Server (Windows)
# Student B - Python API Backend

Write-Host "==================================" -ForegroundColor Green
Write-Host "Smart DMS AI API - Startup Script" -ForegroundColor Green
Write-Host "Student B - FastAPI Backend" -ForegroundColor Green
Write-Host "==================================" -ForegroundColor Green
Write-Host ""

# Check if virtual environment exists
if (-not (Test-Path "venv")) {
    Write-Host "Virtual environment not found. Creating..." -ForegroundColor Yellow
    python -m venv venv
}

# Activate virtual environment
$activateScript = ".\venv\Scripts\Activate.ps1"
if (Test-Path $activateScript) {
    & $activateScript
    Write-Host "Virtual environment activated" -ForegroundColor Green
} else {
    Write-Host "ERROR: Could not find activation script" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Installing/updating dependencies..." -ForegroundColor Yellow
pip install -r requirements.txt --quiet

Write-Host ""
Write-Host "Starting FastAPI Server..." -ForegroundColor Green
Write-Host "API will be available at: http://localhost:8000" -ForegroundColor Cyan
Write-Host "API Documentation: http://localhost:8000/docs" -ForegroundColor Cyan
Write-Host "Alternative Docs: http://localhost:8000/redoc" -ForegroundColor Cyan
Write-Host ""

# Change to python directory and run API
Set-Location python
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
