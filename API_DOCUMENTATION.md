# Smart DMS AI API - FastAPI Backend Documentation

**Student B - Python API Development**

## Overview

This is a REST API built with **FastAPI** that provides AI-powered text preprocessing, document search, and query analysis capabilities. It integrates with the existing Python AI module and is designed to work seamlessly with Student A's Laravel REST API.

## Quick Start

### 1. Install Dependencies

```bash
pip install -r requirements.txt
```

### 2. Run the API Server

**Windows (PowerShell):**
```powershell
.\start_api.ps1
```

**Linux/Mac:**
```bash
bash start_api.sh
```

**Manual Start:**
```bash
cd python
uvicorn main:app --host 0.0.0.0 --port 8000 --reload
```

### 3. Access API Documentation

- **Interactive Swagger UI**: http://localhost:8000/docs
- **ReDoc Documentation**: http://localhost:8000/redoc
- **OpenAPI Schema**: http://localhost:8000/openapi.json

## API Endpoints

### Health & Status

#### `GET /health`
Health check endpoint - verify API and module status

**Response:**
```json
{
  "status": "healthy",
  "version": "1.0.0",
  "ai_module_loaded": {
    "text_preprocessor": true,
    "ai_search": true,
    "query_processor": true
  }
}
```

#### `GET /status`
Get detailed API and AI module status

#### `GET /info`
Get API information and available endpoints

---

### Text Preprocessing

#### `POST /preprocess`
Preprocess a single text: tokenize, remove stopwords, lemmatize

**Request:**
```json
{
  "text": "The quick brown fox jumps over the lazy dog",
  "remove_stopwords": true,
  "lemmatize": true
}
```

**Response:**
```json
{
  "success": true,
  "tokens": ["quick", "brown", "fox", "jump", "lazy", "dog"],
  "token_count": 6,
  "cleaned_text": "quick brown fox jump lazy dog",
  "text_length": 43
}
```

#### `POST /preprocess/batch`
Batch preprocess multiple texts (max 100 per request)

**Request:**
```json
[
  "First document text here",
  "Second document text here",
  "Third document text here"
]
```

**Response:**
```json
{
  "success": true,
  "total_processed": 3,
  "results": [
    {
      "original_text": "First document text here",
      "tokens": ["first", "document", "text"],
      "token_count": 3,
      "cleaned_text": "first document text"
    },
    ...
  ]
}
```

---

### Document Search

#### `POST /search`
Search documents using AI-powered semantic search

**Request:**
```json
{
  "query": "machine learning",
  "top_k": 10,
  "min_score": 0.1
}
```

**Response:**
```json
{
  "success": true,
  "query": "machine learning",
  "results": [
    {
      "document": "document_name.txt",
      "score": 0.95,
      "content": "Document preview content..."
    },
    {
      "document": "another_doc.txt",
      "score": 0.87,
      "content": "Document preview content..."
    }
  ],
  "total_results": 2,
  "execution_time": 0.234
}
```

#### `GET /search`
Query parameter version of search

**Query Parameters:**
- `query` (string, required): Search query
- `top_k` (integer, optional, default=10): Number of results (1-50)
- `min_score` (float, optional, default=0.0): Minimum score threshold (0.0-1.0)

**Example:**
```
GET /search?query=machine%20learning&top_k=10&min_score=0.1
```

---

### Query Analysis

#### `POST /analyze-query`
Analyze query: tokenization, expansion, and processing

**Request:**
```json
{
  "query": "document retrieval",
  "enable_fuzzy": true,
  "enable_expansion": true
}
```

**Response:**
```json
{
  "success": true,
  "query": "document retrieval",
  "processing": {
    "tokens": ["document", "retrieval"],
    "token_count": 2,
    ...
  },
  "expansion": {
    "suggested_terms": ["document management", "information retrieval"],
    ...
  }
}
```

#### `GET /query-suggestions`
Get query expansion suggestions

**Query Parameters:**
- `query` (string, required): Query to get suggestions for

**Example:**
```
GET /query-suggestions?query=neural%20networks
```

---

### Combined Operations

#### `POST /search-and-preprocess`
Search documents AND preprocess the query in one call

**Request:**
```json
{
  "query": "machine learning algorithms",
  "top_k": 5,
  "min_score": 0.1
}
```

**Response:**
```json
{
  "success": true,
  "original_query": "machine learning algorithms",
  "preprocessed_query_tokens": ["machine", "learn", "algorithm"],
  "search_results": [
    {
      "document": "ml_guide.txt",
      "score": 0.92
    }
  ],
  "total_results": 1
}
```

---

## Configuration

### Environment Variables

Create a `.env` file in the project root:

```env
API_HOST=0.0.0.0
API_PORT=8000
API_RELOAD=true

DOCUMENTS_FOLDER=ai_module/documents
DEFAULT_TOP_K=10
MAX_BATCH_SIZE=100

DEFAULT_REMOVE_STOPWORDS=true
DEFAULT_LEMMATIZE=true
DEFAULT_ENABLE_FUZZY=true
DEFAULT_ENABLE_EXPANSION=true

LOG_LEVEL=INFO
```

### Modify Configuration in Code

Edit `python/config.py` to change default settings.

---

## Error Handling

All endpoints return structured error responses:

```json
{
  "success": false,
  "error": "Error type description",
  "detail": "Detailed error message"
}
```

**Common HTTP Status Codes:**
- `200 OK` - Request successful
- `400 Bad Request` - Invalid parameters
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

---

## Integration with Other Components

### For Student A (Laravel):
The API can be called from Laravel using the `curl` PHP function or HTTP client:

```php
// Example Laravel integration
$response = Http::post('http://localhost:8000/search', [
    'query' => 'search term',
    'top_k' => 10,
    'min_score' => 0.1
]);
```

### For Student C (System Integration):
The API provides all necessary endpoints for connecting search results with document management, user interface, and other components.

---

## Testing

### Test with cURL

**Test health endpoint:**
```bash
curl http://localhost:8000/health
```

**Test text preprocessing:**
```bash
curl -X POST http://localhost:8000/preprocess \
  -H "Content-Type: application/json" \
  -d '{"text": "Hello world", "remove_stopwords": true}'
```

**Test search:**
```bash
curl -X POST http://localhost:8000/search \
  -H "Content-Type: application/json" \
  -d '{"query": "machine learning", "top_k": 5}'
```

### Using Swagger UI

Navigate to http://localhost:8000/docs to:
- View all available endpoints
- Try endpoints directly in the browser
- See request/response examples
- Download OpenAPI specification

---

## Performance Considerations

- **Batch Processing**: Use `/preprocess/batch` for processing multiple texts
- **Search Filtering**: Use `min_score` to filter low-quality results
- **Rate Limiting**: Implement in production using middleware
- **Caching**: Consider caching frequently searched queries
- **Document Indexing**: Pre-index documents for faster searches

---

## Dependencies

Key dependencies installed via `requirements.txt`:

- **FastAPI** - Modern web framework
- **Uvicorn** - ASGI server
- **Pydantic** - Data validation
- **CORS Middleware** - Cross-origin requests
- **Spacy, NLTK** - NLP processing
- **scikit-learn** - Machine learning utilities
- **numpy** - Numerical computing

---

## Project Structure

```
smart_dms/
├── python/
│   ├── main.py          # FastAPI application
│   ├── config.py        # Configuration settings
│   ├── api.py           # Preprocessing utilities
│   ├── query_api.py     # Query analysis
│   ├── search_api.py    # Search utilities
│   └── __pycache__/
├── ai_module/
│   ├── ai_search.py
│   ├── query_processing.py
│   ├── text_preprocessing.py
│   └── documents/       # Document storage
├── start_api.ps1        # Windows startup script
├── start_api.sh         # Linux/Mac startup script
├── requirements.txt     # Python dependencies
└── README.md
```

---

## Troubleshooting

### Module Import Errors
Ensure you're in the project root directory and have activated the virtual environment.

### Port Already in Use
Change the port:
```bash
uvicorn main:app --port 8001
```

### Missing Documents Folder
Create the folder:
```bash
mkdir -p ai_module/documents
```

### Slow Startup
The first startup loads NLP models. Subsequent starts are faster.

---

## Future Enhancements

- [ ] Authentication & authorization
- [ ] Request rate limiting
- [ ] API versioning
- [ ] Caching layer (Redis)
- [ ] Async document processing
- [ ] WebSocket support for real-time search
- [ ] Database persistence
- [ ] Advanced filtering options

---

## Support & Questions

For issues with Student B's API development, check:
1. API logs for detailed error messages
2. Swagger documentation at `/docs`
3. Status endpoint at `/status`
4. Test endpoints in interactive docs

---

**Version:** 1.0.0  
**Last Updated:** April 2026  
**Student:** B - Backend API Development
