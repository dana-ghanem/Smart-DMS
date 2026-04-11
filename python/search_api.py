"""
Search API Endpoint
===================
Called from Laravel to perform AI-powered document search
"""

import json
import sys
from pathlib import Path

# Add ai_module to path
sys.path.insert(0, str(Path(__file__).parent.parent / 'ai_module'))

from ai_search import search_documents


def main():
    """Main entry point for CLI usage."""
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'error': 'No query provided'
        }))
        return

    query = sys.argv[1]
    top_k = int(sys.argv[2]) if len(sys.argv) > 2 else 10
    min_score = float(sys.argv[3]) if len(sys.argv) > 3 else 0.0

    # Search all documents in ai_module/documents folder
    doc_folder = Path(__file__).parent.parent / 'ai_module' / 'documents'

    if not doc_folder.exists():
        print(json.dumps({
            'success': False,
            'error': f'Document folder not found: {doc_folder}',
            'query': query
        }))
        return

    result = search_documents(query, str(doc_folder), top_k)
    print(json.dumps(result))


if __name__ == "__main__":
    main()
