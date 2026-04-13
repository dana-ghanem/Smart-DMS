# API Usage Examples - cURL Commands

## Quick API Usage Examples

These examples show how to use the FastAPI endpoints with curl commands.
Replace `localhost:8000` with your actual API server address if different.

---

## Health & Status Checks

### Check API Health
```bash
curl http://localhost:8000/health
```

### Get API Status
```bash
curl http://localhost:8000/status
```

### Get API Information
```bash
curl http://localhost:8000/info
```

---

## Text Preprocessing

### Preprocess Single Text
```bash
curl -X POST http://localhost:8000/preprocess \
  -H "Content-Type: application/json" \
  -d '{
    "text": "The quick brown fox jumps over the lazy dog",
    "remove_stopwords": true,
    "lemmatize": true
  }'
```

### Preprocess Without Stopword Removal
```bash
curl -X POST http://localhost:8000/preprocess \
  -H "Content-Type: application/json" \
  -d '{
    "text": "The quick brown fox",
    "remove_stopwords": false,
    "lemmatize": true
  }'
```

### Batch Preprocess Multiple Texts
```bash
curl -X POST http://localhost:8000/preprocess/batch \
  -H "Content-Type: application/json" \
  -d '[
    "First document about machine learning",
    "Second document about neural networks",
    "Third document about artificial intelligence"
  ]'
```

---

## Document Search

### Search Documents (POST)
```bash
curl -X POST http://localhost:8000/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "machine learning",
    "top_k": 10,
    "min_score": 0.1
  }'
```

### Search Documents (GET with Query Parameters)
```bash
curl "http://localhost:8000/search?query=machine%20learning&top_k=10&min_score=0.1"
```

### Search with Minimum Score Filter
```bash
curl -X POST http://localhost:8000/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "deep learning",
    "top_k": 5,
    "min_score": 0.5
  }'
```

---

## Query Analysis

### Analyze Query
```bash
curl -X POST http://localhost:8000/analyze-query \
  -H "Content-Type: application/json" \
  -d '{
    "query": "information retrieval system",
    "enable_fuzzy": true,
    "enable_expansion": true
  }'
```

### Get Query Suggestions
```bash
curl "http://localhost:8000/query-suggestions?query=neural%20networks"
```

### Query Analysis Without Expansion
```bash
curl -X POST http://localhost:8000/analyze-query \
  -H "Content-Type: application/json" \
  -d '{
    "query": "document management",
    "enable_fuzzy": true,
    "enable_expansion": false
  }'
```

---

## Combined Operations

### Search and Preprocess in One Call
```bash
curl -X POST http://localhost:8000/search-and-preprocess \
  -H "Content-Type: application/json" \
  -d '{
    "query": "machine learning algorithms",
    "top_k": 5,
    "min_score": 0.1
  }'
```

---

## Using PowerShell (Windows)

### Basic Search Request
```powershell
$body = @{
    query = "machine learning"
    top_k = 10
    min_score = 0.1
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/search" `
  -Method Post `
  -ContentType "application/json" `
  -Body $body
```

### Health Check
```powershell
Invoke-WebRequest -Uri "http://localhost:8000/health" `
  -Method Get
```

---

## Using Python Requests

### Search
```python
import requests

response = requests.post(
    'http://localhost:8000/search',
    json={
        'query': 'machine learning',
        'top_k': 10,
        'min_score': 0.1
    }
)

print(response.json())
```

### Batch Preprocess
```python
import requests

texts = [
    "First document",
    "Second document",
    "Third document"
]

response = requests.post(
    'http://localhost:8000/preprocess/batch',
    json=texts
)

print(response.json())
```

---

## Using JavaScript/Fetch API

### Search with Fetch
```javascript
const response = await fetch('http://localhost:8000/search', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    query: 'machine learning',
    top_k: 10,
    min_score: 0.1
  })
});

const data = await response.json();
console.log(data);
```

### Search with Axios
```javascript
const axios = require('axios');

axios.post('http://localhost:8000/search', {
  query: 'machine learning',
  top_k: 10,
  min_score: 0.1
})
.then(response => console.log(response.data))
.catch(error => console.error(error));
```

---

## Using PHP (Laravel)

### Using Laravel HTTP Client
```php
use Illuminate\Support\Facades\Http;

$response = Http::post('http://localhost:8000/search', [
    'query' => 'machine learning',
    'top_k' => 10,
    'min_score' => 0.1
]);

$data = $response->json();
```

### Using cURL in PHP
```php
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => 'http://localhost:8000/search',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'query' => 'machine learning',
        'top_k' => 10,
        'min_score' => 0.1
    ])
]);

$response = curl_exec($ch);
$data = json_decode($response, true);
curl_close($ch);
```

---

## Response Format

### Success Response
```json
{
  "success": true,
  "query": "machine learning",
  "results": [
    {
      "document": "document_name.txt",
      "score": 0.95,
      "content": "Document preview..."
    }
  ],
  "total_results": 1,
  "execution_time": 0.234
}
```

### Error Response
```json
{
  "success": false,
  "error": "Error type",
  "detail": "Detailed error message"
}
```

---

## Tips & Tricks

1. **URL Encoding**: Remember to URL encode special characters
   ```bash
   # Spaces become %20
   curl "http://localhost:8000/search?query=machine%20learning"
   ```

2. **Pretty Print JSON**: Use `jq` to format responses
   ```bash
   curl http://localhost:8000/health | jq .
   ```

3. **Save Response to File**:
   ```bash
   curl -o response.json http://localhost:8000/health
   ```

4. **Include Response Headers**:
   ```bash
   curl -i http://localhost:8000/health
   ```

5. **Debug Request**:
   ```bash
   curl -v http://localhost:8000/health
   ```

---

## Common Curl Flags

| Flag | Purpose |
|------|---------|
| `-X POST` | Specify HTTP method |
| `-H "Content-Type: application/json"` | Set header |
| `-d` | Send data |
| `-i` | Include response headers |
| `-v` | Verbose (debug) mode |
| `-o filename` | Save to file |
| `-w "\n"` | Add newline to output |

---

## Testing with Swagger UI

Instead of using curl, you can test all endpoints interactively:

1. Start the API: `python python/main.py`
2. Open browser: http://localhost:8000/docs
3. Click on any endpoint
4. Click "Try it out"
5. Fill in parameters
6. Click "Execute"
7. See the response

---

**For more detailed information, see:**
- `API_DOCUMENTATION.md` - Full documentation
- `STUDENT_B_SUMMARY.md` - Task completion summary
- `python/test_api.py` - Automated test suite
