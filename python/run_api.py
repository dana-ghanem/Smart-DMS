#!/usr/bin/env python
"""
Smart DMS FastAPI Server Starter
=================================
Student B - Python API Implementation

Run this script to start the FastAPI server.
The API will be available at: http://localhost:8000
Interactive docs at: http://localhost:8000/docs
"""

import sys
import os
from pathlib import Path

# Ensure we're in the right directory
os.chdir(Path(__file__).parent.parent)

# Add paths
sys.path.insert(0, str(Path(__file__).parent.parent / 'ai_module'))
sys.path.insert(0, str(Path(__file__).parent))

if __name__ == "__main__":
    import uvicorn
    from main import app

    print("\n" + "="*60)
    print("Smart DMS AI FastAPI Server")
    print("="*60)
    print("\n📍 Starting server on: http://0.0.0.0:8000")
    print("📚 Interactive docs: http://localhost:8000/docs")
    print("🔄 ReLoader enabled: Changes will auto-reload\n")

    try:
        uvicorn.run(
            "main:app",
            host="0.0.0.0",
            port=8000,
            reload=True,
            log_level="info",
            access_log=True,
        )
    except KeyboardInterrupt:
        print("\n✋ Server stopped.")
        sys.exit(0)
    except Exception as e:
        print(f"\n❌ Error: {e}")
        sys.exit(1)
