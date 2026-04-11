"""
Integrated AI Search Engine
===========================
Combines document vectorization, query processing, and result ranking.

Pipeline:
    1. Student A (DocumentReader) → Extract and vectorize documents
    2. Student B (QueryProcessor) → Process user queries
    3. Student C (Ranking) → Rank results by relevance
"""

import logging
import json
from pathlib import Path
from typing import Any, Dict, List, Optional, Tuple
import numpy as np

try:
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity
    _SKLEARN = True
except ImportError:
    _SKLEARN = False

try:
    from document_reader import read_document
    _DOC_READER = True
except ImportError:
    _DOC_READER = False

try:
    from query_processing import QueryProcessor
    _QUERY_PROCESSOR = True
except ImportError:
    _QUERY_PROCESSOR = False

# ── Logging ─────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s  [%(levelname)s]  %(name)s — %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("ai_search_engine")


# =============================================================================
# AI SEARCH ENGINE
# =============================================================================

class AISearchEngine:
    """
    Complete AI-powered search engine combining vectorization, query processing, and ranking.
    """

    def __init__(self, document_folder: str = "documents"):
        """
        Initialize search engine.

        Args:
            document_folder: Path to folder containing documents
        """
        self.document_folder = Path(document_folder)
        self.documents = []
        self.file_names = []
        self.vectorizer = None
        self.tfidf_matrix = None
        self.query_processor = None
        self.is_initialized = False

        logger.info("AISearchEngine initialized with folder: %s", document_folder)

    def build_index(self) -> Dict[str, Any]:
        """
        Build search index from documents.

        Returns:
            dict: Build result metadata
        """
        if not _DOC_READER:
            return {
                'success': False,
                'error': 'DocumentReader not available'
            }

        if not self.document_folder.exists():
            return {
                'success': False,
                'error': f"Document folder not found: {self.document_folder}"
            }

        logger.info("Building search index from: %s", self.document_folder)

        self.documents = []
        self.file_names = []
        errors = []

        # Read all supported documents
        for file_path in self.document_folder.iterdir():
            if file_path.suffix.lower() in {".txt", ".pdf", ".docx"}:
                logger.info("Processing document: %s", file_path.name)
                result = read_document(str(file_path))

                if result['success']:
                    # Use cleaned text if available, else raw text
                    text = result.get('cleaned_text') or result.get('raw_text')
                    self.documents.append(text)
                    self.file_names.append(result['file_name'])
                else:
                    error_msg = f"{file_path.name}: {result.get('error')}"
                    logger.warning("Failed to process: %s", error_msg)
                    errors.append(error_msg)

        if not self.documents:
            return {
                'success': False,
                'error': 'No valid documents found',
                'errors': errors
            }

        # Create TF-IDF vectorizer
        try:
            self.vectorizer = TfidfVectorizer(
                lowercase=True,
                stop_words='english',
                max_features=1000,
                min_df=1,
                max_df=0.9,
            )
            self.tfidf_matrix = self.vectorizer.fit_transform(self.documents)
            self.query_processor = QueryProcessor()
            self.query_processor.set_vectorizer(self.vectorizer)
            self.is_initialized = True

            logger.info("Index built: %d documents, TF-IDF matrix shape=%s",
                       len(self.documents), self.tfidf_matrix.shape)

            return {
                'success': True,
                'documents_indexed': len(self.documents),
                'tfidf_shape': self.tfidf_matrix.shape,
                'vocabulary_size': len(self.vectorizer.vocabulary_),
                'skipped': len(errors),
                'errors': errors
            }

        except Exception as e:
            logger.exception("Failed to build TF-IDF matrix")
            return {
                'success': False,
                'error': str(e)
            }

    def search(self, query: str, top_k: int = 10, min_score: float = 0.0) -> Dict[str, Any]:
        """
        Search for documents matching query.

        Args:
            query: Search query
            top_k: Number of top results to return
            min_score: Minimum relevance score threshold

        Returns:
            dict: Search results with metadata
        """
        if not self.is_initialized:
            return {
                'success': False,
                'error': 'Search engine not initialized. Call build_index() first.',
                'query': query
            }

        logger.info("Searching: '%s' (top_k=%d)", query, top_k)

        # Step 1: Process query
        query_result = self.query_processor.process_query(query, vectorize=True)

        if not query_result['success']:
            return {
                'success': False,
                'error': query_result.get('error', 'Query processing failed'),
                'query': query,
                'metadata': query_result.get('metadata')
            }

        query_vector = np.array(query_result['vector']).reshape(1, -1)

        # Step 2: Compute similarity scores
        try:
            similarity_scores = cosine_similarity(query_vector, self.tfidf_matrix).flatten()
        except Exception as e:
            logger.exception("Similarity computation failed")
            return {
                'success': False,
                'error': str(e),
                'query': query
            }

        # Step 3: Rank results
        ranked_indices = np.argsort(-similarity_scores)
        results = []

        for rank, idx in enumerate(ranked_indices[:top_k], 1):
            score = float(similarity_scores[idx])

            if score < min_score:
                break

            results.append({
                'rank': rank,
                'document': self.file_names[idx],
                'score': round(score, 4),
                'preview': self.documents[idx][:200] + '...' if len(self.documents[idx]) > 200 else self.documents[idx]
            })

        logger.info("Search complete: found %d results", len(results))

        return {
            'success': True,
            'query': query,
            'query_tokens': query_result.get('tokens'),
            'results': results,
            'result_count': len(results),
            'metadata': {
                'query_metadata': query_result.get('metadata'),
                'top_score': results[0]['score'] if results else 0.0,
            }
        }

    def get_advanced_search_info(self, query: str) -> Dict[str, Any]:
        """
        Get detailed information about query processing.

        Args:
            query: Search query

        Returns:
            dict: Detailed processing information
        """
        if not self.query_processor:
            return {'error': 'QueryProcessor not initialized'}

        return {
            'query': query,
            'preprocessing': self.query_processor.preprocess_query(query),
            'expansion': self.query_processor.expand_query(query),
        }


# =============================================================================
# CONVENIENCE FUNCTIONS
# =============================================================================

def search_documents(query: str, document_folder: str = "documents", top_k: int = 10) -> Dict[str, Any]:
    """
    One-shot search function.

    Args:
        query: Search query
        document_folder: Path to documents
        top_k: Number of results

    Returns:
        dict: Search results
    """
    engine = AISearchEngine(document_folder)
    index_result = engine.build_index()

    if not index_result['success']:
        return {
            'success': False,
            'error': index_result.get('error'),
            'query': query
        }

    return engine.search(query, top_k=top_k)


# =============================================================================
# CLI ENTRY POINT
# =============================================================================

if __name__ == "__main__":
    import sys

    if len(sys.argv) > 1:
        query = sys.argv[1]
        result = search_documents(query)
        print(json.dumps(result, indent=2))
    else:
        engine = AISearchEngine("documents")

        print("Building search index...")
        index_result = engine.build_index()
        print(json.dumps(index_result, indent=2))

        if not index_result['success']:
            exit(1)

        print("\n" + "="*60)
        print("Interactive Search Mode")
        print("="*60)

        while True:
            try:
                query = input("\nEnter search query (or 'exit'): ").strip()
                if query.lower() == 'exit':
                    break
                if not query:
                    continue

                result = engine.search(query, top_k=5)
                print("\n" + json.dumps(result, indent=2))
            except KeyboardInterrupt:
                break
