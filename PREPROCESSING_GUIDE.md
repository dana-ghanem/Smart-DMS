# Text Preprocessing Integration Guide

## Setup Instructions

### 1. Run Database Migration
```bash
php artisan migrate
```
This adds `processed_tokens` and `token_count` columns to the documents table.

---

## Usage Methods

### Method 1: PHP/Backend Usage

**In a Controller:**
```php
// Call the preprocessing
$preprocessed = $this->preprocessText("Your text here");

// Access results
if ($preprocessed['success']) {
    $tokens = $preprocessed['tokens'];           // Array of tokens
    $cleaned = $preprocessed['cleaned_text'];   // Cleaned text string
    $count = $preprocessed['token_count'];      // Number of tokens
}
```

### Method 2: API Endpoints

**For Frontend/JavaScript:**

#### Preprocess Text
```bash
POST /api/preprocess
Content-Type: application/json

{
    "text": "Your text to preprocess",
    "remove_stopwords": true,
    "lemmatize": true
}
```

**Response:**
```json
{
    "success": true,
    "tokens": ["word1", "word2", ...],
    "token_count": 42,
    "cleaned_text": "word1 word2 ...",
    "text_length": 500
}
```

#### Analyze Document
```bash
POST /api/analyze-document
Content-Type: application/json
Authorization: Bearer {token}

{
    "document_id": 1
}
```

---

## Frontend Usage (JavaScript)

### Add to your Blade template:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="{{ asset('js/text-preprocessor.js') }}"></script>

<div id="results"></div>

<script>
const preprocessor = new TextPreprocessor();

// Preprocess text
preprocessor.preprocessText("Your text here").then(results => {
    preprocessor.displayResults(results, 'results');
});

// Or analyze a document
preprocessor.analyzeDocument(1).then(results => {
    console.log(results);
});
</script>
```

---

## Example Form Integration

```html
<form id="preprocessForm">
    <textarea id="textInput" placeholder="Enter text to preprocess"></textarea>
    <button type="submit">Analyze</button>
</form>

<div id="results"></div>

<script>
const preprocessor = new TextPreprocessor();

document.getElementById('preprocessForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const text = document.getElementById('textInput').value;
    const results = await preprocessor.preprocessText(text);
    preprocessor.displayResults(results, 'results');
});
</script>
```

---

## Routes Available

| Route | Method | Purpose |
|-------|--------|---------|
| `/api/preprocess` | POST | Preprocess any text |
| `/api/analyze-document` | POST | Analyze document description |
| `/documents` | GET | List documents |
| `/documents` | POST | Upload document |
| `/documents/{id}` | GET | View document |
| `/documents/{id}` | PUT | Update document |
| `/documents/{id}` | DELETE | Delete document |

---

## Features

✅ **Tokenization** - Split text into words
✅ **Stopwords Removal** - Remove common words (the, a, is, etc.)
✅ **Lemmatization** - Convert to base form (running → run)
✅ **Text Cleaning** - Remove URLs, emails, HTML tags
✅ **Database Storage** - Save tokens with documents
✅ **API Authentication** - Protected by Laravel auth middleware

---

## Testing

Test the preprocessing directly:
```bash
python python/api.py "Hello world! This is a test."
```

Output:
```json
{
    "success": true,
    "tokens": ["hello", "world", "test"],
    "token_count": 3,
    "cleaned_text": "hello world test",
    "text_length": 35
}
```

---

## Troubleshooting

**Python not found:** Ensure Python is in system PATH or use full path
**CSRF Token error:** Add `<meta name="csrf-token">` tag to Blade template
**Module import error:** Run `pip install -r requirements.txt`

---

## Next Steps

You can now:
1. Search by preprocessed tokens
2. Recommend similar documents
3. Auto-categorize by keywords
4. Build document analytics
5. Implement full-text search
