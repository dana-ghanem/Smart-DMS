#!/usr/bin/env python3
"""
backfill_index.py
=================
One-time script: index all existing documents by calling Laravel's own API.

Run ONCE after deploying the fix so that documents uploaded BEFORE the fix
are also searchable.

Usage:
    python backfill_index.py

Requirements:
    - Laravel is running (default: http://localhost:8001)
    - Python FastAPI is running (default: http://localhost:8000)
    - pip install requests
    - .env contains LARAVEL_API_EMAIL and LARAVEL_API_PASSWORD

The script reads all documents from Laravel's /api/documents endpoint
and POSTs each one to the Python /index-document endpoint.
"""

import os
import sys
import requests
from python.laravel_api_client import LaravelApiClient

LARAVEL_URL = os.getenv("LARAVEL_URL") or os.getenv("APP_URL") or "http://localhost:8001"
PYTHON_URL = os.getenv("PYTHON_API_URL") or "http://localhost:8000"


def main():
    laravel = LaravelApiClient(base_url=LARAVEL_URL)

    # 1. Fetch all documents from Laravel
    print(f"Fetching documents from {LARAVEL_URL}/api/documents ...")
    try:
        documents = laravel.list_documents()
    except Exception as e:
        print(f"ERROR: Could not reach Laravel: {e}")
        sys.exit(1)

    print(f"Found {len(documents)} documents.\n")

    if not documents:
        print("Nothing to index.")
        return

    ok = 0
    fail = 0

    for doc in documents:
        doc_id    = doc.get("document_id")
        title     = doc.get("title", "")
        author    = doc.get("author_name", "") or ""
        desc      = doc.get("description", "") or ""
        extracted = doc.get("extracted_text", "") or ""
        category_data = doc.get("category")
        category = category_data.get("name", "") if isinstance(category_data, dict) else (category_data or "")
        searchable_text = extracted.strip() or desc.strip()

        if not searchable_text:
            print(f"  SKIP  #{doc_id} '{title}' — no searchable text to index")
            continue

        payload = {
            "document_id": doc_id,
            "title":       title,
            "author":      author,
            "description": desc,
            "category":    category,
            "text":        searchable_text,
        }

        try:
            r = requests.post(f"{PYTHON_URL}/index-document", json=payload, timeout=10)
            if r.ok and r.json().get("success"):
                print(f"  OK    #{doc_id} '{title}'")
                ok += 1
            else:
                print(f"  FAIL  #{doc_id} '{title}' — {r.text}")
                fail += 1
        except Exception as e:
            print(f"  ERROR #{doc_id} '{title}' — {e}")
            fail += 1

    print(f"\nDone. Indexed: {ok}  |  Failed/Skipped: {fail}")
    print(f"Check Python index at: {PYTHON_URL}/index-status")


if __name__ == "__main__":
    main()
