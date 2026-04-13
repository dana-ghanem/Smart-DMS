#!/bin/bash
# Startup script for FastAPI Server (Student B - Python API)

set -e

echo "=================================="
echo "Smart DMS AI API - Startup Script"
echo "Student B - FastAPI Backend"
echo "=================================="

# Check if virtual environment exists
if [ ! -d "venv" ]; then
    echo "Virtual environment not found. Creating..."
    python -m venv venv
fi

# Activate virtual environment
if [ -f "venv/bin/activate" ]; then
    source venv/bin/activate
else
    # Windows path
    source venv/Scripts/activate
fi

echo "Installing/updating dependencies..."
pip install -r requirements.txt

echo ""
echo "Starting FastAPI Server..."
echo "API Documentation: http://localhost:8000/docs"
echo "Alternative Docs: http://localhost:8000/redoc"
echo ""

# Run the API server
cd python
uvicorn main:app --host 0.0.0.0 --port 8000 --reload

