"""
Query Analysis API Endpoint
=============================
Provides detailed query processing information for debugging and analysis
"""

import json
import sys
from pathlib import Path

# Add ai_module to path
sys.path.insert(0, str(Path(__file__).parent.parent / 'ai_module'))

from query_processing import QueryProcessor


def main():
    """Main entry point for CLI usage."""
    if len(sys.argv) < 2:
        print(json.dumps({
            'success': False,
            'error': 'No query provided'
        }))
        return

    query = sys.argv[1]

    try:
        processor = QueryProcessor(enable_fuzzy_matching=True, enable_expansion=True)

        result = {
            'success': True,
            'query': query,
            'processing': processor.process_query(query, vectorize=False),
            'expansion': processor.expand_query(query),
        }

        print(json.dumps(result, indent=2))
    except Exception as e:
        print(json.dumps({
            'success': False,
            'error': str(e),
            'query': query
        }))


if __name__ == "__main__":
    main()
