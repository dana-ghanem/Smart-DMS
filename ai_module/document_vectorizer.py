"""
document_vectorizer.py
======================
Smart Document Management System — Week 7 | Student A
------------------------------------------------------
Responsibility:
    Convert extracted document text into TF-IDF vectors that the
    AI search engine can use for similarity computation and ranking.

Pipeline position:
    document_reader.py  →  document_vectorizer.py  →  ai_search.py
    (Week 6, Student A)     (Week 7, Student A)        (Student C)

How it connects:
    - Receives cleaned text produced by document_reader.read_document()
    - Builds and stores a fitted TfidfVectorizer + matrix
    - Exposes transform() so ai_search.py can vectorize new queries
      against the same vocabulary
    - Saves/loads the index to disk so the engine does not re-vectorize
      on every request (performance requirement for production systems)

Required packages : scikit-learn numpy
                    (pip install scikit-learn numpy)
"""

from __future__ import annotations

import json
import logging
import os
import pickle
from datetime import datetime
from pathlib import Path
from typing import Any

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

# ── Logging ───────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s  [%(levelname)s]  %(name)s — %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("document_vectorizer")

# ── Constants ─────────────────────────────────────────────────────────────────
SUPPORTED_EXTENSIONS = {".pdf", ".docx", ".txt"}
DEFAULT_INDEX_PATH   = "vectorizer_index.pkl"


# =============================================================================
#  DOCUMENT VECTORIZER
# =============================================================================

class DocumentVectorizer:
    """
    Builds and manages TF-IDF vector representations of documents.

    Usage (in ai_search.py):
        from document_vectorizer import DocumentVectorizer

        vectorizer = DocumentVectorizer()
        result     = vectorizer.build_index("documents/")

        query_vec  = vectorizer.transform_query("machine learning")
        scores     = vectorizer.similarity_scores(query_vec)
    """

    def __init__(
        self,
        max_features: int = 5000,
        min_df: int = 1,
        max_df: float = 0.95,
        ngram_range: tuple = (1, 2),
    ):
        """
        Parameters
        ----------
        max_features : int
            Maximum number of terms kept in the vocabulary.
            5000 gives broad coverage without memory overhead.
        min_df : int
            Terms that appear in fewer than min_df documents are ignored.
            Filters typos and one-off tokens.
        max_df : float
            Terms that appear in more than max_df fraction of documents
            are ignored (acts as a domain-specific stopword filter).
        ngram_range : tuple
            (1, 2) captures single words AND two-word phrases, improving
            recall for compound terms like "machine learning".
        """
        if not _SKLEARN:
            raise EnvironmentError(
                "scikit-learn not installed. Run: pip install scikit-learn"
            )

        self.max_features = max_features
        self.min_df       = min_df
        self.max_df       = max_df
        self.ngram_range  = ngram_range

        # State — populated by build_index()
        self._vectorizer:   TfidfVectorizer | None = None
        self._tfidf_matrix: Any                    = None   # sparse matrix
        self._documents:    list[str]              = []     # cleaned texts
        self._file_names:   list[str]              = []     # original filenames
        self._metadata:     list[dict]             = []     # per-doc metadata
        self._built_at:     str | None             = None
        self.is_built:      bool                   = False

    # ── Build ─────────────────────────────────────────────────────────────────

    def build_index(self, document_folder: str) -> dict[str, Any]:
        """
        Read every supported document in document_folder, extract text via
        document_reader, and fit the TF-IDF model.

        Parameters
        ----------
        document_folder : str
            Path to the folder containing .pdf / .docx / .txt files.

        Returns
        -------
        dict
            success            bool
            documents_indexed  int
            vocabulary_size    int
            matrix_shape       tuple
            skipped            list   files that could not be processed
            built_at           str    ISO-8601 timestamp
            error              str | None
        """
        if not _DOC_READER:
            return {
                "success": False,
                "error": "document_reader.py not found. Week 6 file is required.",
            }

        folder = Path(document_folder)
        if not folder.exists():
            return {
                "success": False,
                "error": f"Document folder not found: {folder}",
            }

        logger.info("Building TF-IDF index from: %s", folder)

        texts:      list[str]  = []
        file_names: list[str]  = []
        metadata:   list[dict] = []
        skipped:    list[str]  = []

        for path in sorted(folder.iterdir()):
            if path.suffix.lower() not in SUPPORTED_EXTENSIONS:
                continue

            logger.info("Vectorizing: %s", path.name)
            result = read_document(str(path))

            if not result["success"]:
                reason = result.get("error", "unknown error")
                logger.warning("Skipped %s — %s", path.name, reason)
                skipped.append(f"{path.name}: {reason}")
                continue

            # Prefer preprocessed text; fall back to raw text
            text = result.get("cleaned_text") or result.get("raw_text", "")
            if not text.strip():
                skipped.append(f"{path.name}: empty text after extraction")
                continue

            texts.append(text)
            file_names.append(result["file_name"])
            metadata.append({
                "file_name":   result["file_name"],
                "file_type":   result["file_type"],
                "raw_length":  result["raw_length"],
                "token_count": result["token_count"],
                "doc_metadata": result.get("metadata", {}),
            })

        if not texts:
            return {
                "success": False,
                "error":   "No readable documents found in the folder.",
                "skipped": skipped,
            }

        # Fit TF-IDF
        try:
            self._vectorizer = TfidfVectorizer(
                lowercase    = True,
                stop_words   = "english",
                max_features = self.max_features,
                min_df       = self.min_df,
                max_df       = self.max_df,
                ngram_range  = self.ngram_range,
                sublinear_tf = True,   # apply log(tf) — reduces impact of very
                                       # frequent terms, improves ranking quality
            )
            self._tfidf_matrix = self._vectorizer.fit_transform(texts)
            self._documents    = texts
            self._file_names   = file_names
            self._metadata     = metadata
            self._built_at     = datetime.utcnow().isoformat() + "Z"
            self.is_built      = True

            shape = self._tfidf_matrix.shape
            vocab = len(self._vectorizer.vocabulary_)
            logger.info(
                "Index built — %d documents | vocab %d terms | matrix %s",
                shape[0], vocab, shape,
            )

            return {
                "success":           True,
                "documents_indexed": shape[0],
                "vocabulary_size":   vocab,
                "matrix_shape":      shape,
                "ngram_range":       self.ngram_range,
                "skipped":           skipped,
                "built_at":          self._built_at,
                "error":             None,
            }

        except Exception as exc:
            logger.exception("TF-IDF fitting failed.")
            return {"success": False, "error": str(exc)}

    # ── Query vectorization ───────────────────────────────────────────────────

    def transform_query(self, query: str) -> np.ndarray | None:
        """
        Transform a search query into the same TF-IDF vector space
        as the indexed documents.

        Parameters
        ----------
        query : str
            Raw user search query.

        Returns
        -------
        np.ndarray  shape (1, vocab_size)  or  None on failure
        """
        if not self.is_built or self._vectorizer is None:
            logger.error("Index not built. Call build_index() first.")
            return None
        try:
            vec = self._vectorizer.transform([query])
            return vec
        except Exception as exc:
            logger.error("Query transform failed: %s", exc)
            return None

    # ── Similarity ────────────────────────────────────────────────────────────

    def similarity_scores(self, query_vector: np.ndarray) -> np.ndarray | None:
        """
        Compute cosine similarity between a query vector and all
        indexed document vectors.

        Parameters
        ----------
        query_vector : np.ndarray
            Output of transform_query().

        Returns
        -------
        np.ndarray  shape (n_documents,)  float scores in [0, 1]
        """
        if not self.is_built or self._tfidf_matrix is None:
            logger.error("Index not built.")
            return None
        try:
            scores = cosine_similarity(query_vector, self._tfidf_matrix).flatten()
            return scores
        except Exception as exc:
            logger.error("Similarity computation failed: %s", exc)
            return None

    # ── Ranked results ────────────────────────────────────────────────────────

    def ranked_results(
        self,
        query: str,
        top_k: int = 10,
        min_score: float = 0.01,
    ) -> dict[str, Any]:
        """
        Full vectorization + similarity + ranking in one call.
        Used directly by ai_search.py.

        Parameters
        ----------
        query     : str    raw search query
        top_k     : int    maximum results to return
        min_score : float  discard results below this cosine score

        Returns
        -------
        dict
            success       bool
            query         str
            results       list of dicts  {rank, file_name, score, preview, metadata}
            result_count  int
            error         str | None
        """
        if not self.is_built:
            return {
                "success": False,
                "error":   "Index not built. Call build_index() first.",
                "query":   query,
            }

        query_vec = self.transform_query(query)
        if query_vec is None:
            return {"success": False, "error": "Query vectorization failed.", "query": query}

        scores = self.similarity_scores(query_vec)
        if scores is None:
            return {"success": False, "error": "Similarity computation failed.", "query": query}

        ranked_indices = np.argsort(-scores)
        results = []

        for rank, idx in enumerate(ranked_indices[:top_k], start=1):
            score = float(scores[idx])
            if score < min_score:
                break

            doc_text = self._documents[idx]
            results.append({
                "rank":      rank,
                "file_name": self._file_names[idx],
                "score":     round(score, 6),
                "preview":   doc_text[:300] + "…" if len(doc_text) > 300 else doc_text,
                "metadata":  self._metadata[idx],
            })

        return {
            "success":      True,
            "query":        query,
            "results":      results,
            "result_count": len(results),
            "error":        None,
        }

    # ── Persistence ───────────────────────────────────────────────────────────

    def save_index(self, path: str = DEFAULT_INDEX_PATH) -> bool:
        """
        Persist the fitted vectorizer and matrix to disk.
        Call this after build_index() so the server does not re-vectorize
        every request — critical for production performance.
        """
        if not self.is_built:
            logger.error("Nothing to save — index not built.")
            return False
        try:
            payload = {
                "vectorizer":    self._vectorizer,
                "tfidf_matrix":  self._tfidf_matrix,
                "documents":     self._documents,
                "file_names":    self._file_names,
                "metadata":      self._metadata,
                "built_at":      self._built_at,
            }
            with open(path, "wb") as fh:
                pickle.dump(payload, fh)
            logger.info("Index saved to: %s", path)
            return True
        except Exception as exc:
            logger.error("Save failed: %s", exc)
            return False

    def load_index(self, path: str = DEFAULT_INDEX_PATH) -> bool:
        """
        Load a previously saved index from disk.
        Call this at server startup instead of rebuilding every time.
        """
        if not os.path.exists(path):
            logger.warning("Index file not found: %s", path)
            return False
        try:
            with open(path, "rb") as fh:
                payload = pickle.load(fh)
            self._vectorizer   = payload["vectorizer"]
            self._tfidf_matrix = payload["tfidf_matrix"]
            self._documents    = payload["documents"]
            self._file_names   = payload["file_names"]
            self._metadata     = payload["metadata"]
            self._built_at     = payload.get("built_at")
            self.is_built      = True
            logger.info(
                "Index loaded from %s — %d documents, built at %s",
                path, len(self._file_names), self._built_at,
            )
            return True
        except Exception as exc:
            logger.error("Load failed: %s", exc)
            return False

    # ── Inspection ────────────────────────────────────────────────────────────

    def get_vocabulary(self, top_n: int = 50) -> list[str]:
        """Return the top_n most common terms in the vocabulary."""
        if not self.is_built or self._vectorizer is None:
            return []
        vocab = self._vectorizer.vocabulary_
        # Sort by index (frequency order from TfidfVectorizer)
        return sorted(vocab, key=lambda w: vocab[w])[:top_n]

    def get_document_vector(self, file_name: str) -> np.ndarray | None:
        """Return the TF-IDF vector for a specific document by filename."""
        if not self.is_built:
            return None
        try:
            idx = self._file_names.index(file_name)
            return self._tfidf_matrix[idx].toarray().flatten()
        except ValueError:
            logger.warning("Document not found in index: %s", file_name)
            return None

    def status(self) -> dict[str, Any]:
        """Return current state of the vectorizer — useful for health checks."""
        if not self.is_built:
            return {"is_built": False}
        return {
            "is_built":          True,
            "documents_indexed": len(self._file_names),
            "vocabulary_size":   len(self._vectorizer.vocabulary_),
            "matrix_shape":      self._tfidf_matrix.shape,
            "ngram_range":       self._vectorizer.ngram_range,
            "built_at":          self._built_at,
            "file_names":        self._file_names,
        }


# =============================================================================
#  CLI — python document_vectorizer.py
# =============================================================================

def _print_section(title: str) -> None:
    print(f"\n{'═' * 66}")
    print(f"  {title}")
    print(f"{'─' * 66}")


if __name__ == "__main__":
    import sys

    doc_folder = sys.argv[1] if len(sys.argv) > 1 else "documents"

    print("\n" + "═" * 66)
    print("  DOCUMENT VECTORIZER — Week 7 | Student A")
    print("═" * 66)

    vectorizer = DocumentVectorizer(
        max_features=5000,
        ngram_range=(1, 2),
    )

    # Build
    print(f"\nBuilding index from: {doc_folder}")
    result = vectorizer.build_index(doc_folder)

    _print_section("INDEX BUILD RESULT")
    if not result["success"]:
        print(f"  ERROR: {result['error']}")
        sys.exit(1)

    print(f"  {'Documents indexed':<25}: {result['documents_indexed']}")
    print(f"  {'Vocabulary size':<25}: {result['vocabulary_size']:,} terms")
    print(f"  {'Matrix shape':<25}: {result['matrix_shape']}")
    print(f"  {'N-gram range':<25}: {result['ngram_range']}")
    print(f"  {'Built at (UTC)':<25}: {result['built_at']}")
    if result["skipped"]:
        print(f"  {'Skipped':<25}: {len(result['skipped'])} file(s)")
        for s in result["skipped"]:
            print(f"    - {s}")

    # Save index
    saved = vectorizer.save_index()
    print(f"\n  Index saved to disk: {'✓' if saved else '✗'}")

    # Vocabulary sample
    _print_section("VOCABULARY SAMPLE (first 30 terms)")
    print(" ", vectorizer.get_vocabulary(30))

    # Interactive search
    _print_section("SEARCH TEST")
    print("  (tests the full vectorization → similarity pipeline)")
    print("  Type a query and press Enter. Type 'exit' to quit.\n")

    while True:
        try:
            query = input("  Query: ").strip()
        except (EOFError, KeyboardInterrupt):
            break
        if not query or query.lower() == "exit":
            break

        search_result = vectorizer.ranked_results(query, top_k=5)
        if not search_result["success"]:
            print(f"  Error: {search_result['error']}")
            continue

        print(f"\n  Results for '{query}':")
        if not search_result["results"]:
            print("  No matching documents found.")
        for r in search_result["results"]:
            print(f"  [{r['rank']}] {r['file_name']}  score={r['score']:.4f}")
            print(f"       {r['preview'][:120]}…")
        print()
