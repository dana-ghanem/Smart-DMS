"""
Query Processing Module
======================
Smart Document Management System — Week 7 | Student B
-------------------------------------------------------
Responsibilities:
    - Parse and normalize user search queries
    - Apply text preprocessing to queries (same pipeline as documents)
    - Convert queries to vector representation using TF-IDF
    - Handle query expansion and refinement
    - Validate and sanitize user input
    - Return processed query ready for ranking

Integration points:
    - Receives preprocessed documents from document_reader.py
    - Uses TextPreprocessor from text_preprocessing.py
    - Outputs vectorized queries for ranking (Student C)
    - Called from Laravel API via DocumentController

Supported query modes:
    - Single keyword search
    - Multi-word phrase search
    - Boolean queries (AND, OR operators)
    - Fuzzy matching for typos
"""

import logging
import re
from typing import Any, Dict, List, Optional, Tuple
from pathlib import Path
import numpy as np

try:
    from sklearn.feature_extraction.text import TfidfVectorizer
    _SKLEARN = True
except ImportError:
    _SKLEARN = False

try:
    from text_preprocessing import TextPreprocessor
    _PREPROCESSOR = True
except ImportError:
    _PREPROCESSOR = False

try:
    from difflib import SequenceMatcher
    _DIFFLIB = True
except ImportError:
    _DIFFLIB = False

# ── Logging ─────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s  [%(levelname)s]  %(name)s — %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("query_processing")

# ── Constants ───────────────────────────────────────────────────────
MIN_QUERY_LENGTH = 1
MAX_QUERY_LENGTH = 500
FUZZY_MATCH_THRESHOLD = 0.8


# =============================================================================
# HELPERS
# =============================================================================

def _normalize_query(query: str) -> str:
    """Normalize query string: strip, lowercase, remove extra spaces."""
    query = query.strip()
    query = re.sub(r'\s+', ' ', query)
    return query.lower()


def _extract_operators(query: str) -> Tuple[str, Dict[str, Any]]:
    """
    Extract boolean operators from query.

    Returns:
        tuple: (cleaned_query, operators_dict)
    """
    operators = {
        'require_all': False,
        'exclude_terms': [],
        'exact_phrase': False,
    }

    # Check for exact phrase (quoted text)
    if '"' in query:
        operators['exact_phrase'] = True

    # Extract exclude terms (words starting with -)
    excluded = re.findall(r'-(\w+)', query)
    if excluded:
        operators['exclude_terms'] = excluded
        query = re.sub(r'-\w+', '', query)

    return _normalize_query(query), operators


def _is_valid_query(query: str) -> Tuple[bool, Optional[str]]:
    """
    Validate query string.

    Returns:
        tuple: (is_valid, error_message)
    """
    if not query or not isinstance(query, str):
        return False, "Query must be a non-empty string"

    query = query.strip()

    if len(query) < MIN_QUERY_LENGTH:
        return False, f"Query too short (minimum {MIN_QUERY_LENGTH} character)"

    if len(query) > MAX_QUERY_LENGTH:
        return False, f"Query too long (maximum {MAX_QUERY_LENGTH} characters)"

    # Check if query contains only whitespace or special chars
    if not re.search(r'[a-zA-Z0-9]', query):
        return False, "Query must contain at least one alphanumeric character"

    return True, None


def _fuzzy_match(term: str, vocabulary: Dict[str, int], threshold: float = FUZZY_MATCH_THRESHOLD) -> Optional[str]:
    """
    Find similar term in vocabulary using fuzzy matching.

    Args:
        term: Query term to match
        vocabulary: TF-IDF vocabulary dict
        threshold: Similarity threshold (0-1)

    Returns:
        Best matching term or None
    """
    if not _DIFFLIB:
        return None

    best_match = None
    best_ratio = threshold

    for vocab_term in vocabulary.keys():
        ratio = SequenceMatcher(None, term, vocab_term).ratio()
        if ratio > best_ratio:
            best_ratio = ratio
            best_match = vocab_term

    return best_match


# =============================================================================
# QUERY PROCESSOR
# =============================================================================

class QueryProcessor:
    """
    Processes user search queries for document retrieval.

    Handles:
        - Query normalization and validation
        - Text preprocessing (same pipeline as documents)
        - Query expansion and fuzzy matching
        - Vectorization for similarity search
    """

    def __init__(self, enable_fuzzy_matching: bool = True, enable_expansion: bool = True):
        """
        Initialize QueryProcessor.

        Args:
            enable_fuzzy_matching: Enable typo correction via fuzzy matching
            enable_expansion: Enable query expansion
        """
        self.preprocessor = None
        self.vectorizer = None
        self.vocabulary = None
        self.enable_fuzzy_matching = enable_fuzzy_matching
        self.enable_expansion = enable_expansion

        if _PREPROCESSOR:
            self.preprocessor = TextPreprocessor()
            logger.info("TextPreprocessor initialized")
        else:
            logger.warning("TextPreprocessor not available")

    def set_vectorizer(self, vectorizer: Any) -> None:
        """
        Set the TF-IDF vectorizer (from Student C's module).

        Args:
            vectorizer: Fitted sklearn TfidfVectorizer instance
        """
        self.vectorizer = vectorizer
        if hasattr(vectorizer, 'vocabulary_'):
            self.vocabulary = vectorizer.vocabulary_
            logger.info("Vectorizer set with vocabulary size: %d", len(self.vocabulary))

    def preprocess_query(self, query: str) -> Tuple[bool, Optional[List[str]], Dict[str, Any]]:
        """
        Preprocess query string.

        Args:
            query: Raw query string from user

        Returns:
            tuple: (success, tokens, metadata)
        """
        # Validate
        is_valid, error = _is_valid_query(query)
        if not is_valid:
            logger.warning("Invalid query: %s", error)
            return False, None, {'error': error}

        # Normalize and extract operators
        normalized, operators = _extract_operators(query)

        # Preprocess
        metadata = {
            'original_query': query,
            'normalized_query': normalized,
            'operators': operators,
        }

        if not self.preprocessor:
            logger.warning("Preprocessing skipped - TextPreprocessor unavailable")
            tokens = normalized.split()
            metadata['processed_query'] = normalized
            return True, tokens, metadata

        try:
            tokens = self.preprocessor.preprocess(
                normalized,
                remove_stopwords=True,
                lemmatize=True
            )

            if not tokens:
                logger.warning("Query preprocessing returned no tokens")
                return False, None, {**metadata, 'error': 'Query preprocessing returned no tokens'}

            processed_text = ' '.join(tokens)
            metadata['processed_query'] = processed_text
            metadata['token_count'] = len(tokens)

            logger.info("Query preprocessed: %d tokens", len(tokens))
            return True, tokens, metadata

        except Exception as e:
            logger.exception("Query preprocessing failed")
            return False, None, {**metadata, 'error': str(e)}

    def vectorize_query(self, query: str) -> Tuple[bool, Optional[np.ndarray], Dict[str, Any]]:
        """
        Convert query to TF-IDF vector.

        Args:
            query: Raw query string

        Returns:
            tuple: (success, tfidf_vector, metadata)
        """
        if not self.vectorizer:
            return False, None, {'error': 'Vectorizer not set. Call set_vectorizer() first.'}

        # Preprocess
        success, tokens, preprocess_metadata = self.preprocess_query(query)
        if not success:
            return False, None, preprocess_metadata

        processed_text = preprocess_metadata.get('processed_query', '')

        try:
            # Vectorize using fitted vectorizer
            tfidf_vector = self.vectorizer.transform([processed_text])
            vector_array = tfidf_vector.toarray().flatten()

            metadata = {
                **preprocess_metadata,
                'vector_shape': vector_array.shape,
                'non_zero_terms': int(np.count_nonzero(vector_array)),
            }

            logger.info("Query vectorized: shape=%s, non-zero=%d",
                       vector_array.shape, metadata['non_zero_terms'])
            return True, vector_array, metadata

        except Exception as e:
            logger.exception("Query vectorization failed")
            return False, None, {**preprocess_metadata, 'error': str(e)}

    def expand_query(self, query: str, num_expansions: int = 3) -> Dict[str, Any]:
        """
        Expand query with fuzzy-matched alternatives for typo correction.

        Args:
            query: Query string
            num_expansions: Number of alternative terms to find

        Returns:
            dict: Expansion metadata
        """
        if not self.enable_fuzzy_matching or not self.vocabulary:
            return {'expanded_terms': [], 'message': 'Fuzzy matching disabled or vocabulary unavailable'}

        success, tokens, metadata = self.preprocess_query(query)
        if not success or not tokens:
            return {'expanded_terms': [], 'error': metadata.get('error')}

        expanded_terms = {}

        for token in tokens:
            if token in self.vocabulary:
                expanded_terms[token] = {'match': 'exact', 'alternative': None}
            else:
                # Try fuzzy matching
                match = _fuzzy_match(token, self.vocabulary)
                if match:
                    expanded_terms[token] = {'match': 'fuzzy', 'alternative': match}
                    logger.info("Fuzzy match: '%s' -> '%s'", token, match)
                else:
                    expanded_terms[token] = {'match': 'none', 'alternative': None}

        return {
            'original_query': query,
            'tokens': tokens,
            'expanded_terms': expanded_terms,
            'fuzzy_matches_found': sum(1 for v in expanded_terms.values() if v['match'] == 'fuzzy')
        }

    def process_query(self, query: str, vectorize: bool = True) -> Dict[str, Any]:
        """
        Complete query processing pipeline.

        Args:
            query: Raw query string from user
            vectorize: Whether to vectorize the query

        Returns:
            dict: Complete processing result
        """
        logger.info("Processing query: %s", query)

        # Step 1: Validation
        is_valid, error = _is_valid_query(query)
        if not is_valid:
            return {
                'success': False,
                'error': error,
                'query': query,
            }

        # Step 2: Preprocessing
        preprocess_success, tokens, preprocess_meta = self.preprocess_query(query)
        if not preprocess_success:
            return {
                'success': False,
                'error': preprocess_meta.get('error', 'Preprocessing failed'),
                'query': query,
                'metadata': preprocess_meta,
            }

        result = {
            'success': True,
            'query': query,
            'tokens': tokens,
            'metadata': preprocess_meta,
        }

        # Step 3: Vectorization (optional)
        if vectorize:
            vectorize_success, vector, vector_meta = self.vectorize_query(query)
            if vectorize_success:
                result['vector'] = vector.tolist()
                result['metadata'].update(vector_meta)
            else:
                logger.warning("Vectorization failed: %s", vector_meta.get('error'))
                result['vector'] = None

        # Step 4: Query expansion (optional)
        if self.enable_expansion:
            expansion_result = self.expand_query(query)
            result['metadata']['expansion'] = expansion_result

        logger.info("Query processing complete: success=%s, tokens=%d",
                   result['success'], len(tokens))
        return result


# =============================================================================
# STANDALONE FUNCTIONS (for simple use cases)
# =============================================================================

def process_query_simple(query: str, vectorizer: Optional[Any] = None) -> Dict[str, Any]:
    """
    Simple one-off query processing.

    Args:
        query: Query string
        vectorizer: Optional fitted vectorizer

    Returns:
        dict: Processing result
    """
    processor = QueryProcessor()
    if vectorizer:
        processor.set_vectorizer(vectorizer)
    return processor.process_query(query, vectorize=bool(vectorizer))


# =============================================================================
# CLI ENTRY POINT
# =============================================================================

if __name__ == "__main__":
    import sys
    import json

    if len(sys.argv) > 1:
        query = sys.argv[1]
        processor = QueryProcessor()
        result = processor.process_query(query, vectorize=False)
        print(json.dumps(result, indent=2))
    else:
        logger.info("Interactive mode - enter queries, 'exit' to quit")
        processor = QueryProcessor()

        while True:
            try:
                query = input("\nEnter query: ").strip()
                if query.lower() == 'exit':
                    break
                if not query:
                    continue

                result = processor.process_query(query, vectorize=False)
                print("\n" + json.dumps(result, indent=2))
            except KeyboardInterrupt:
                break
