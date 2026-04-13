"""
Python FastAPI REST API for Smart DMS AI Module
================================================

This API provides endpoints for:
- Text Preprocessing
- Document Search
- Query Processing and Analysis

Integration with Laravel backend for document management system.
Student B - FastAPI Backend Development
"""

from fastapi import FastAPI, HTTPException, Query, UploadFile, File
from fastapi.responses import JSONResponse
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field
from typing import List, Optional, Dict, Any
import sys
import json
import logging
from pathlib import Path

# Add ai_module to path
sys.path.insert(0, str(Path(__file__).parent.parent / 'ai_module'))

# Import AI module components
try:
    from text_preprocessing import TextPreprocessor
    logger = logging.getLogger(__name__)
    logger.info("TextPreprocessor loaded successfully")
except ImportError as e:
    logger = logging.getLogger(__name__)
    logger.warning(f"Could not import TextPreprocessor: {e}")

try:
    from ai_search import search_documents
    logger.info("AI Search module loaded successfully")
except ImportError as e:
    logger.warning(f"Could not import ai_search: {e}")

try:
    from query_processing import QueryProcessor
    logger.info("QueryProcessor loaded successfully")
except ImportError as e:
    logger.warning(f"Could not import QueryProcessor: {e}")

# ────────────────────────────────────────────────────────────────────
# FastAPI App Initialization
# ────────────────────────────────────────────────────────────────────

app = FastAPI(
    title="Smart DMS AI API",
    description="REST API for AI-powered document search and text processing",
    version="1.0.0",
)

# ────────────────────────────────────────────────────────────────────
# CORS Configuration
# ────────────────────────────────────────────────────────────────────

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:8000", "http://localhost:3000", "*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ────────────────────────────────────────────────────────────────────
# Pydantic Models (Request/Response Schemas)
# ────────────────────────────────────────────────────────────────────


class TextPreprocessingRequest(BaseModel):
    """Request model for text preprocessing endpoint"""
    text: str = Field(..., description="Text to preprocess", min_length=1)
    remove_stopwords: bool = Field(True, description="Remove stopwords")
    lemmatize: bool = Field(True, description="Lemmatize tokens")

    class Config:
        json_schema_extra = {
            "example": {
                "text": "The quick brown fox jumps over the lazy dog",
                "remove_stopwords": True,
                "lemmatize": True
            }
        }


class TextPreprocessingResponse(BaseModel):
    """Response model for text preprocessing"""
    success: bool
    tokens: List[str]
    token_count: int
    cleaned_text: str
    text_length: int


class SearchRequest(BaseModel):
    """Request model for document search"""
    query: str = Field(..., description="Search query", min_length=1)
    top_k: int = Field(10, description="Number of results to return", ge=1, le=50)
    min_score: float = Field(0.0, description="Minimum relevance score", ge=0.0, le=1.0)

    class Config:
        json_schema_extra = {
            "example": {
                "query": "machine learning",
                "top_k": 10,
                "min_score": 0.1
            }
        }


class SearchResult(BaseModel):
    """Individual search result"""
    document: str
    score: float
    content: Optional[str] = None


class SearchResponse(BaseModel):
    """Response model for document search"""
    success: bool
    query: str
    results: List[SearchResult]
    total_results: int
    execution_time: Optional[float] = None


class QueryAnalysisRequest(BaseModel):
    """Request model for query analysis"""
    query: str = Field(..., description="Query to analyze", min_length=1)
    enable_fuzzy: bool = Field(True, description="Enable fuzzy matching")
    enable_expansion: bool = Field(True, description="Enable query expansion")

    class Config:
        json_schema_extra = {
            "example": {
                "query": "document retrieval",
                "enable_fuzzy": True,
                "enable_expansion": True
            }
        }


class QueryAnalysisResponse(BaseModel):
    """Response model for query analysis"""
    success: bool
    query: str
    processing: Optional[Dict[str, Any]] = None
    expansion: Optional[Dict[str, Any]] = None


class HealthResponse(BaseModel):
    """Response model for health check"""
    status: str
    version: str
    ai_module_loaded: Dict[str, bool]


# ────────────────────────────────────────────────────────────────────
# Helper Functions
# ────────────────────────────────────────────────────────────────────


def check_module_status() -> Dict[str, bool]:
    """Check which AI modules are loaded"""
    return {
        "text_preprocessor": "TextPreprocessor" in dir(),
        "ai_search": "search_documents" in dir(),
        "query_processor": "QueryProcessor" in dir(),
    }


# ────────────────────────────────────────────────────────────────────
# API Endpoints
# ────────────────────────────────────────────────────────────────────


@app.get("/", tags=["Root"])
async def root():
    """Welcome message and API information"""
    return {
        "message": "Smart DMS AI API",
        "version": "1.0.0",
        "documentation": "/docs",
        "description": "REST API for AI-powered document search and text preprocessing"
    }


@app.get("/health", response_model=HealthResponse, tags=["Health"])
async def health_check():
    """Health check endpoint - verify API and module status"""
    return {
        "status": "healthy",
        "version": "1.0.0",
        "ai_module_loaded": check_module_status()
    }


# ────────────────────────────────────────────────────────────────────
# Text Preprocessing Endpoints
# ────────────────────────────────────────────────────────────────────


@app.post("/preprocess", response_model=TextPreprocessingResponse, tags=["Text Processing"])
async def preprocess_text(request: TextPreprocessingRequest):
    """
    Preprocess text: tokenize, remove stopwords, lemmatize
    
    Returns:
    - tokens: List of processed tokens
    - token_count: Number of tokens
    - cleaned_text: Complete cleaned text
    - text_length: Original text length
    """
    try:
        preprocessor = TextPreprocessor()

        # Get tokens
        tokens = preprocessor.preprocess(
            request.text,
            remove_stopwords=request.remove_stopwords,
            lemmatize=request.lemmatize
        )

        # Get cleaned text
        cleaned_text = preprocessor.preprocess(
            request.text,
            remove_stopwords=request.remove_stopwords,
            lemmatize=request.lemmatize,
            return_string=True
        )

        return {
            "success": True,
            "tokens": tokens,
            "token_count": len(tokens),
            "cleaned_text": cleaned_text,
            "text_length": len(request.text)
        }

    except Exception as e:
        logger.error(f"Text preprocessing error: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Text preprocessing failed: {str(e)}"
        )


@app.post("/preprocess/batch", tags=["Text Processing"])
async def batch_preprocess(texts: List[str]):
    """
    Batch preprocess multiple texts
    
    Request body: List of strings
    
    Returns: List of preprocessing results
    """
    if not texts:
        raise HTTPException(status_code=400, detail="Empty text list")

    if len(texts) > 100:
        raise HTTPException(status_code=400, detail="Maximum 100 texts per request")

    try:
        preprocessor = TextPreprocessor()
        results = []

        for text in texts:
            tokens = preprocessor.preprocess(text, remove_stopwords=True, lemmatize=True)
            cleaned = preprocessor.preprocess(text, remove_stopwords=True, lemmatize=True, return_string=True)

            results.append({
                "original_text": text,
                "tokens": tokens,
                "token_count": len(tokens),
                "cleaned_text": cleaned
            })

        return {
            "success": True,
            "total_processed": len(results),
            "results": results
        }

    except Exception as e:
        logger.error(f"Batch preprocessing error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Batch preprocessing failed: {str(e)}")


# ────────────────────────────────────────────────────────────────────
# Document Search Endpoints
# ────────────────────────────────────────────────────────────────────


@app.post("/search", response_model=SearchResponse, tags=["Document Search"])
async def search(request: SearchRequest):
    """
    Search documents using AI-powered semantic search
    
    Parameters:
    - query: Search query string
    - top_k: Number of results to return (1-50)
    - min_score: Minimum relevance score threshold (0.0-1.0)
    
    Returns:
    - results: List of relevant documents with similarity scores
    - total_results: Number of results returned
    """
    try:
        doc_folder = Path(__file__).parent.parent / 'ai_module' / 'documents'

        if not doc_folder.exists():
            raise HTTPException(
                status_code=404,
                detail=f"Document folder not found: {doc_folder}"
            )

        result = search_documents(
            request.query,
            str(doc_folder),
            request.top_k
        )

        if not result.get("success"):
            raise HTTPException(status_code=500, detail="Search failed")

        # Format results
        formatted_results = []
        for hit in result.get("results", []):
            if hit.get("score", 0) >= request.min_score:
                formatted_results.append({
                    "document": hit.get("document", ""),
                    "score": float(hit.get("score", 0)),
                    "content": hit.get("content")
                })

        return {
            "success": True,
            "query": request.query,
            "results": formatted_results,
            "total_results": len(formatted_results),
            "execution_time": result.get("execution_time")
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Search error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Search failed: {str(e)}")


@app.get("/search", response_model=SearchResponse, tags=["Document Search"])
async def search_get(
    query: str = Query(..., description="Search query", min_length=1),
    top_k: int = Query(10, description="Number of results", ge=1, le=50),
    min_score: float = Query(0.0, description="Minimum score", ge=0.0, le=1.0)
):
    """
    GET version of document search (query parameters)
    """
    request = SearchRequest(query=query, top_k=top_k, min_score=min_score)
    return await search(request)


# ────────────────────────────────────────────────────────────────────
# Query Analysis Endpoints
# ────────────────────────────────────────────────────────────────────


@app.post("/analyze-query", response_model=QueryAnalysisResponse, tags=["Query Analysis"])
async def analyze_query(request: QueryAnalysisRequest):
    """
    Analyze query: tokenization, expansion, and processing
    
    Parameters:
    - query: Query string to analyze
    - enable_fuzzy: Enable fuzzy matching in analysis
    - enable_expansion: Enable query expansion
    
    Returns:
    - processing: Query processing results
    - expansion: Query expansion suggestions
    """
    try:
        processor = QueryProcessor(
            enable_fuzzy_matching=request.enable_fuzzy,
            enable_expansion=request.enable_expansion
        )

        result = {
            "success": True,
            "query": request.query,
            "processing": processor.process_query(request.query, vectorize=False),
        }

        if request.enable_expansion:
            result["expansion"] = processor.expand_query(request.query)

        return result

    except Exception as e:
        logger.error(f"Query analysis error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Query analysis failed: {str(e)}")


@app.post("/query-suggestions", tags=["Query Analysis"])
async def query_suggestions(query: str = Query(..., description="Query to get suggestions for")):
    """
    Get query expansion suggestions
    
    Returns alternative query formulations and related terms
    """
    try:
        processor = QueryProcessor(enable_expansion=True)
        expansion = processor.expand_query(query)

        return {
            "success": True,
            "original_query": query,
            "suggestions": expansion
        }

    except Exception as e:
        logger.error(f"Query suggestions error: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Failed to get suggestions: {str(e)}"
        )


# ────────────────────────────────────────────────────────────────────
# Combined Endpoints (Multi-step operations)
# ────────────────────────────────────────────────────────────────────


@app.post("/search-and-preprocess", tags=["Combined Operations"])
async def search_and_preprocess(request: SearchRequest):
    """
    Search documents AND preprocess the query
    
    Combines search with query preprocessing for enhanced results
    """
    try:
        # Step 1: Preprocess the query
        preprocessor = TextPreprocessor()
        query_tokens = preprocessor.preprocess(
            request.query,
            remove_stopwords=True,
            lemmatize=True
        )

        # Step 2: Search with original query
        doc_folder = Path(__file__).parent.parent / 'ai_module' / 'documents'
        search_result = search_documents(request.query, str(doc_folder), request.top_k)

        if not search_result.get("success"):
            raise HTTPException(status_code=500, detail="Search failed")

        return {
            "success": True,
            "original_query": request.query,
            "preprocessed_query_tokens": query_tokens,
            "search_results": search_result.get("results", []),
            "total_results": len(search_result.get("results", []))
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"Search and preprocess error: {str(e)}")
        raise HTTPException(status_code=500, detail=f"Operation failed: {str(e)}")


# ────────────────────────────────────────────────────────────────────
# Status and Info Endpoints
# ────────────────────────────────────────────────────────────────────


@app.get("/status", tags=["Status"])
async def status():
    """Get API and AI module status information"""
    return {
        "api_status": "running",
        "version": "1.0.0",
        "ai_modules": check_module_status(),
        "documents_folder": str(Path(__file__).parent.parent / 'ai_module' / 'documents'),
    }


@app.get("/info", tags=["Status"])
async def info():
    """Get API information and available endpoints"""
    return {
        "name": "Smart DMS AI API",
        "version": "1.0.0",
        "student": "Student B",
        "description": "REST API for AI-powered document search and text preprocessing",
        "endpoints": {
            "text_preprocessing": ["/preprocess", "/preprocess/batch"],
            "document_search": ["/search"],
            "query_analysis": ["/analyze-query", "/query-suggestions"],
            "combined_operations": ["/search-and-preprocess"],
            "status": ["/health", "/status", "/info"]
        },
        "documentation": "/docs",
        "openapi_schema": "/openapi.json"
    }


# ────────────────────────────────────────────────────────────────────
# Error Handlers
# ────────────────────────────────────────────────────────────────────


@app.exception_handler(Exception)
async def general_exception_handler(request, exc):
    """Handle any unhandled exceptions"""
    logger.error(f"Unhandled exception: {str(exc)}")
    return JSONResponse(
        status_code=500,
        content={
            "success": False,
            "error": "Internal server error",
            "detail": str(exc)
        }
    )


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
