"""
CHANGES vs original main.py (Student B)
========================================
1. Added POST /index-document  — Laravel calls this on every upload/update
2. Added DELETE /remove-document/{id} — Laravel calls this on delete
3. Added GET /index-status     — useful for debugging
4. Replaced /search logic      — now uses the persistent index (document_index.py)
   instead of rebuilding from the local `ai_module/documents/` folder every call.

Everything else (preprocess, analyze-query, health, etc.) is UNCHANGED.
"""

from fastapi import FastAPI, HTTPException, Query
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
import sys
import logging
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent.parent / 'ai_module'))

logger = logging.getLogger(__name__)
logging.basicConfig(level=logging.INFO)

module_status = {
    "text_preprocessor": False,
    "ai_search":         False,
    "query_processor":   False,
    "document_index":    False,
}

try:
    from text_preprocessing import TextPreprocessor
    module_status["text_preprocessor"] = True
    logger.info("✓ TextPreprocessor loaded")
except ImportError as e:
    logger.warning("✗ TextPreprocessor: %s", e)

try:
    from ai_search import search_documents as _legacy_search
    module_status["ai_search"] = True
    logger.info("✓ ai_search (legacy) loaded")
except ImportError as e:
    logger.warning("✗ ai_search: %s", e)

try:
    from query_processing import QueryProcessor
    module_status["query_processor"] = True
    logger.info("✓ QueryProcessor loaded")
except ImportError as e:
    logger.warning("✗ QueryProcessor: %s", e)

# NEW: persistent index module
try:
    from document_index import index_document, remove_document, search_index, get_index_status
    module_status["document_index"] = True
    logger.info("✓ document_index loaded")
except ImportError as e:
    logger.warning("✗ document_index: %s", e)

try:
    from document_reader import read_document
    module_status["document_reader"] = True
    logger.info("✓ document_reader loaded")
except ImportError as e:
    module_status["document_reader"] = False
    logger.warning("✗ document_reader: %s", e)

# ────────────────────────────────────────────────────────────────────
app = FastAPI(
    title="Smart DMS AI API",
    description="REST API for AI-powered document search and text processing",
    version="2.0.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000", "http://localhost:3000", "*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ────────────────────────────────────────────────────────────────────
# Pydantic models
# ────────────────────────────────────────────────────────────────────

class TextPreprocessingRequest(BaseModel):
    text: str = Field(..., min_length=1)
    remove_stopwords: bool = True
    lemmatize: bool = True

class TextPreprocessingResponse(BaseModel):
    success: bool
    tokens: List[str]
    token_count: int
    cleaned_text: str
    text_length: int

class SearchRequest(BaseModel):
    query: str = Field(..., min_length=1)
    top_k: int = Field(10, ge=1, le=50)
    min_score: float = Field(0.0, ge=0.0, le=1.0)

class SearchResult(BaseModel):
    document_id: Optional[int] = None
    document: str
    score: float
    title: Optional[str] = None
    author: Optional[str] = None
    category: Optional[str] = None
    content: Optional[str] = None

class SearchResponse(BaseModel):
    success: bool
    query: str
    results: List[SearchResult]
    total_results: int
    execution_time: Optional[float] = None

# NEW: model for indexing a document
class IndexDocumentRequest(BaseModel):
    document_id: int = Field(..., description="Laravel DB document_id")
    title: str       = Field("", description="Document title")
    author: str      = Field("", description="Author name")
    description: str = Field("", description="Document description (main searchable text)")
    category: str    = Field("", description="Category name")
    text: str        = Field("", description="Full extracted text (optional, falls back to description)")

    class Config:
        json_schema_extra = {
            "example": {
                "document_id": 42,
                "title": "Machine Learning Overview",
                "author": "John Doe",
                "description": "An introduction to supervised and unsupervised learning.",
                "category": "AI",
                "text": ""
            }
        }

class DocumentAnalysisRequest(BaseModel):
    text: str = Field(..., min_length=1)
    remove_stopwords: bool = True
    lemmatize: bool = True

class ExtractDocumentRequest(BaseModel):
    file_path: str = Field(..., min_length=1)

class DocumentAnalysisResponse(BaseModel):
    success: bool
    tokens: List[str]
    token_count: int
    cleaned_text: str
    text_length: int

class QueryAnalysisRequest(BaseModel):
    query: str = Field(..., min_length=1)
    enable_fuzzy: bool = True
    enable_expansion: bool = True

class QueryAnalysisResponse(BaseModel):
    success: bool
    query: str
    processing: Optional[Dict[str, Any]] = None
    expansion: Optional[Dict[str, Any]] = None

class HealthResponse(BaseModel):
    status: str
    version: str
    ai_module_loaded: Dict[str, bool]


# ────────────────────────────────────────────────────────────────────
# Existing endpoints (UNCHANGED)
# ────────────────────────────────────────────────────────────────────

@app.get("/", tags=["Root"])
async def root():
    return {"message": "Smart DMS AI API", "version": "2.0.0", "documentation": "/docs"}

@app.get("/health", response_model=HealthResponse, tags=["Health"])
async def health_check():
    return {"status": "healthy", "version": "2.0.0", "ai_module_loaded": module_status}

@app.get("/status", tags=["Status"])
async def status():
    idx_status = get_index_status() if module_status["document_index"] else {"error": "not loaded"}
    return {
        "api_status":   "running",
        "version":      "2.0.0",
        "ai_modules":   module_status,
        "index_status": idx_status,
    }

@app.get("/info", tags=["Status"])
async def info():
    return {
        "name":      "Smart DMS AI API",
        "version":   "2.0.0",
        "endpoints": {
            "indexing":          ["/index-document", "/remove-document/{id}", "/index-status"],
            "text_preprocessing": ["/preprocess", "/preprocess/batch"],
            "document_search":   ["/search"],
            "query_analysis":    ["/analyze-query", "/query-suggestions"],
            "status":            ["/health", "/status", "/info"],
        },
        "documentation": "/docs",
    }


# ────────────────────────────────────────────────────────────────────
# NEW: Indexing endpoints
# ────────────────────────────────────────────────────────────────────

@app.post("/index-document", tags=["Indexing"])
async def api_index_document(request: IndexDocumentRequest):
    """
    Add or update a document in the search index.
    Laravel calls this endpoint after every upload or edit.

    The searchable content is `text` if provided, otherwise falls back to `description`.
    """
    if not module_status["document_index"]:
        raise HTTPException(status_code=503, detail="document_index module not loaded")

    # Use `text` if provided and non-empty, else fall back to description
    searchable_text = request.text.strip() if request.text.strip() else request.description.strip()

    if not searchable_text:
        raise HTTPException(
            status_code=422,
            detail="Provide at least a non-empty 'description' or 'text' field to index."
        )

    result = index_document(
        document_id=request.document_id,
        title=request.title,
        author=request.author,
        description=request.description,
        category=request.category,
        text=searchable_text,
    )

    if not result["success"]:
        raise HTTPException(status_code=500, detail=result.get("error", "Indexing failed"))

    return result


@app.delete("/remove-document/{document_id}", tags=["Indexing"])
async def api_remove_document(document_id: int):
    """
    Remove a document from the search index.
    Laravel calls this when a document is deleted.
    """
    if not module_status["document_index"]:
        raise HTTPException(status_code=503, detail="document_index module not loaded")

    result = remove_document(document_id)
    return result


@app.get("/index-status", tags=["Indexing"])
async def api_index_status():
    """Show what's currently in the search index. Useful for debugging."""
    if not module_status["document_index"]:
        raise HTTPException(status_code=503, detail="document_index module not loaded")

    return get_index_status()


@app.post("/extract-document", tags=["Indexing"])
async def api_extract_document(request: ExtractDocumentRequest):
    """Extract raw text and metadata from a local document file."""
    if not module_status.get("document_reader"):
        raise HTTPException(status_code=503, detail="document_reader module not loaded")

    result = read_document(request.file_path)
    if not result.get("success"):
        raise HTTPException(status_code=422, detail=result.get("error", "Document extraction failed"))

    return result


# ────────────────────────────────────────────────────────────────────
# FIXED: Search endpoint (uses persistent index, not local files)
# ────────────────────────────────────────────────────────────────────

@app.post("/search", response_model=SearchResponse, tags=["Document Search"])
async def search(request: SearchRequest):
    """
    AI-powered document search over the persistent index.

    Results include `document_id` so Laravel can reliably enrich them
    from the database without guessing filenames.
    """
    if not module_status["document_index"]:
        raise HTTPException(status_code=503, detail="document_index module not loaded")

    result = search_index(request.query, top_k=request.top_k, min_score=request.min_score)

    if not result["success"]:
        raise HTTPException(status_code=500, detail=result.get("error", "Search failed"))

    return result


@app.get("/search", response_model=SearchResponse, tags=["Document Search"])
async def search_get(
    query: str = Query(..., min_length=1),
    top_k: int = Query(10, ge=1, le=50),
    min_score: float = Query(0.0, ge=0.0, le=1.0),
):
    """GET version of search (query string parameters)."""
    return await search(SearchRequest(query=query, top_k=top_k, min_score=min_score))


# ────────────────────────────────────────────────────────────────────
# Text preprocessing (UNCHANGED)
# ────────────────────────────────────────────────────────────────────

@app.post("/preprocess", response_model=TextPreprocessingResponse, tags=["Text Processing"])
async def preprocess_text(request: TextPreprocessingRequest):
    if not module_status["text_preprocessor"]:
        raise HTTPException(status_code=503, detail="TextPreprocessor not loaded")
    try:
        preprocessor = TextPreprocessor()
        tokens = preprocessor.preprocess(request.text, remove_stopwords=request.remove_stopwords, lemmatize=request.lemmatize)
        cleaned_text = preprocessor.preprocess(request.text, remove_stopwords=request.remove_stopwords, lemmatize=request.lemmatize, return_string=True)
        return {"success": True, "tokens": tokens, "token_count": len(tokens), "cleaned_text": cleaned_text, "text_length": len(request.text)}
    except Exception as e:
        logger.error("preprocess error: %s", e)
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/preprocess/batch", tags=["Text Processing"])
async def batch_preprocess(texts: List[str]):
    if not texts:
        raise HTTPException(status_code=400, detail="Empty text list")
    if len(texts) > 100:
        raise HTTPException(status_code=400, detail="Maximum 100 texts per batch")
    if not module_status["text_preprocessor"]:
        raise HTTPException(status_code=503, detail="TextPreprocessor not loaded")
    try:
        preprocessor = TextPreprocessor()
        results = []
        for text in texts:
            tokens  = preprocessor.preprocess(text, remove_stopwords=True, lemmatize=True)
            cleaned = preprocessor.preprocess(text, remove_stopwords=True, lemmatize=True, return_string=True)
            results.append({"original_text": text, "tokens": tokens, "token_count": len(tokens), "cleaned_text": cleaned})
        return {"success": True, "total_processed": len(results), "results": results}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/analyze-document", response_model=DocumentAnalysisResponse, tags=["Document Analysis"])
async def analyze_document(request: DocumentAnalysisRequest):
    if not module_status["text_preprocessor"]:
        raise HTTPException(status_code=503, detail="TextPreprocessor not loaded")
    try:
        preprocessor = TextPreprocessor()
        tokens = preprocessor.preprocess(request.text, remove_stopwords=request.remove_stopwords, lemmatize=request.lemmatize)
        cleaned_text = preprocessor.preprocess(request.text, remove_stopwords=request.remove_stopwords, lemmatize=request.lemmatize, return_string=True)
        return {"success": True, "tokens": tokens, "token_count": len(tokens), "cleaned_text": cleaned_text, "text_length": len(request.text)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


# ────────────────────────────────────────────────────────────────────
# Query analysis (UNCHANGED)
# ────────────────────────────────────────────────────────────────────

@app.post("/analyze-query", response_model=QueryAnalysisResponse, tags=["Query Analysis"])
async def analyze_query(request: QueryAnalysisRequest):
    if not module_status["query_processor"]:
        raise HTTPException(status_code=503, detail="QueryProcessor not loaded")
    try:
        processor = QueryProcessor(enable_fuzzy_matching=request.enable_fuzzy, enable_expansion=request.enable_expansion)
        result = {"success": True, "query": request.query, "processing": processor.process_query(request.query, vectorize=False)}
        if request.enable_expansion:
            result["expansion"] = processor.expand_query(request.query)
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/query-suggestions", tags=["Query Analysis"])
async def query_suggestions(query: str = Query(...)):
    if not module_status["query_processor"]:
        raise HTTPException(status_code=503, detail="QueryProcessor not loaded")
    try:
        processor = QueryProcessor(enable_expansion=True)
        return {"success": True, "original_query": query, "suggestions": processor.expand_query(query)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))


# ────────────────────────────────────────────────────────────────────
# Error handler
# ────────────────────────────────────────────────────────────────────

@app.exception_handler(Exception)
async def general_exception_handler(request, exc):
    logger.error("Unhandled exception: %s", exc)
    return JSONResponse(status_code=500, content={"success": False, "error": str(exc)})


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
