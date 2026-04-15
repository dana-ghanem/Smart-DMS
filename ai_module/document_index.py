"""
document_index.py
=================
Persistent document index for AI-powered search.

Replaces the broken file-based approach with a database-aware index:
  - Index is built from documents sent by Laravel (not from local files)
  - Index is saved to disk so it persists between requests
  - Each document is identified by its DB document_id (no filename guessing)
"""

import json
import logging
import time
from pathlib import Path
from typing import Any, Dict, List

import numpy as np

try:
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.metrics.pairwise import cosine_similarity
    _SKLEARN = True
except ImportError:
    _SKLEARN = False

logger = logging.getLogger(__name__)

# Where the index is persisted on disk (same directory as this file)
INDEX_FILE = Path(__file__).parent / "document_index_data.json"


# ---------------------------------------------------------------------------
# Low-level index helpers
# ---------------------------------------------------------------------------

def _load_index() -> List[Dict[str, Any]]:
    """Load the persisted index from disk. Returns [] if none exists yet."""
    if INDEX_FILE.exists():
        try:
            with open(INDEX_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception as e:
            logger.warning("Could not read index file: %s", e)
    return []


def _save_index(index: List[Dict[str, Any]]) -> None:
    """Persist the index to disk."""
    with open(INDEX_FILE, "w", encoding="utf-8") as f:
        json.dump(index, f, ensure_ascii=False, indent=2)


# ---------------------------------------------------------------------------
# Public API used by main.py
# ---------------------------------------------------------------------------

def index_document(document_id: int, title: str, author: str,
                   description: str, category: str, text: str) -> Dict[str, Any]:
    """
    Add or update a document in the persistent index.

    Called by Laravel every time a document is uploaded or updated.
    The `text` field should contain the actual searchable content.

    Returns a simple success/error dict.
    """
    if not text or not text.strip():
        return {"success": False, "error": "No text content to index"}

    index = _load_index()

    # Remove old entry for this document_id if it exists (update scenario)
    index = [doc for doc in index if doc.get("document_id") != document_id]

    # Append new entry
    index.append({
        "document_id": document_id,
        "title":       title or "",
        "author":      author or "",
        "description": description or "",
        "category":    category or "",
        "text":        text.strip(),
    })

    _save_index(index)
    logger.info("Indexed document_id=%d ('%s'). Total indexed: %d", document_id, title, len(index))

    return {"success": True, "document_id": document_id, "total_indexed": len(index)}


def remove_document(document_id: int) -> Dict[str, Any]:
    """
    Remove a document from the index.
    Call this from Laravel when a document is deleted.
    """
    index = _load_index()
    before = len(index)
    index = [doc for doc in index if doc.get("document_id") != document_id]
    _save_index(index)

    removed = before - len(index)
    logger.info("Removed document_id=%d from index. Total indexed: %d", document_id, len(index))
    return {"success": True, "removed": removed, "total_indexed": len(index)}


def search_index(query: str, top_k: int = 10, min_score: float = 0.0) -> Dict[str, Any]:
    """
    Search the persisted index using TF-IDF cosine similarity.

    Returns results identified by document_id so Laravel can enrich
    them from the database reliably (no filename guessing).
    """
    if not _SKLEARN:
        return {"success": False, "error": "scikit-learn is not installed", "query": query}

    start = time.time()
    index = _load_index()

    if not index:
        return {
            "success": True,
            "query": query,
            "results": [],
            "total_results": 0,
            "message": "Index is empty. Upload some documents first.",
        }

    # Build TF-IDF matrix from all indexed documents
    texts = [doc["text"] for doc in index]

    try:
        vectorizer = TfidfVectorizer(
            lowercase=True,
            stop_words="english",
            max_features=5000,
            min_df=1,
            max_df=0.95,
            ngram_range=(1, 2),   # unigrams + bigrams for better matching
        )
        tfidf_matrix = vectorizer.fit_transform(texts)
        query_vector = vectorizer.transform([query])
    except Exception as e:
        logger.exception("TF-IDF vectorization failed")
        return {"success": False, "error": str(e), "query": query}

    # Cosine similarity
    scores = cosine_similarity(query_vector, tfidf_matrix).flatten()
    ranked = np.argsort(-scores)

    results = []
    for idx in ranked[:top_k]:
        score = float(scores[idx])
        if score < min_score:
            break
        doc = index[idx]
        results.append({
            "document_id": doc["document_id"],
            "document":    str(doc["document_id"]),   # kept for backwards compat
            "score":       round(score, 4),
            "title":       doc["title"],
            "author":      doc["author"],
            "category":    doc["category"],
            "content":     doc["text"][:200] + "..." if len(doc["text"]) > 200 else doc["text"],
        })

    elapsed = round(time.time() - start, 4)
    logger.info("Search '%s': %d results in %.4fs", query, len(results), elapsed)

    return {
        "success":       True,
        "query":         query,
        "results":       results,
        "total_results": len(results),
        "execution_time": elapsed,
    }


def get_index_status() -> Dict[str, Any]:
    """Return how many documents are currently indexed."""
    index = _load_index()
    return {
        "total_indexed": len(index),
        "index_file":    str(INDEX_FILE),
        "documents":     [{"document_id": d["document_id"], "title": d["title"]} for d in index],
    }
