<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Base URL of Student B's FastAPI server.
     * Defined in .env as PYTHON_API_URL=http://localhost:8000
     */
    private string $pythonApiUrl;

    public function __construct()
    {
        $this->pythonApiUrl = env('PYTHON_API_URL', 'http://localhost:8000');
    }

    // =========================================================================
    //  PRIVATE — Python API Communication
    // =========================================================================

    /**
     * Send an HTTP POST request to Student B's FastAPI.
     * All Python calls go through this single method.
     *
     * @param  string  $endpoint   e.g. '/preprocess' or '/search'
     * @param  array   $payload    JSON body
     * @param  int     $timeout    seconds before giving up
     * @return array   decoded JSON response
     */
    private function callPythonApi(string $endpoint, array $payload, int $timeout = 30): array
    {
        try {
            $response = Http::timeout($timeout)
                ->post("{$this->pythonApiUrl}{$endpoint}", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Python API error [{$endpoint}]", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return [
                'success' => false,
                'error'   => "Python API returned HTTP {$response->status()}",
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Python API unreachable [{$endpoint}]: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'AI service is currently unavailable. Please try again later.',
            ];
        } catch (\Exception $e) {
            Log::error("Python API unexpected error [{$endpoint}]: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    //  REST API ENDPOINTS  (called by frontend JS / external clients)
    // =========================================================================

    /**
     * POST /api/preprocess
     * Preprocess raw text through the AI pipeline.
     *
     * Request body:
     *   { "text": "...", "remove_stopwords": true, "lemmatize": true }
     *
     * Response:
     *   { "success": true, "tokens": [...], "token_count": 12, "cleaned_text": "..." }
     */
    public function preprocessText(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text'             => 'required|string|max:10000',
            'remove_stopwords' => 'boolean',
            'lemmatize'        => 'boolean',
        ]);

        $result = $this->callPythonApi('/preprocess', [
            'text'             => $validated['text'],
            'remove_stopwords' => $validated['remove_stopwords'] ?? true,
            'lemmatize'        => $validated['lemmatize']        ?? true,
        ]);

        $statusCode = $result['success'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * GET/POST /api/preprocess/document/{id}
     * Preprocess a specific document's text (for frontend compatibility).
     * This route accepts both GET and POST methods.
     *
     * GET: /api/preprocess/document/4
     * POST: /api/preprocess/document/4 (with optional body parameters)
     * Response: { "success": true, "tokens": [...], "token_count": 12, "cleaned_text": "..." }
     */
    public function preprocessDocument(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'error'   => 'Document not found.',
            ], 404);
        }

        // For API routes, we can't check auth easily, so we'll allow it
        // In production, you might want to add API key authentication

        $text = $document->description ?? '';

        if (empty(trim($text))) {
            return response()->json([
                'success' => false,
                'error'   => 'Document "' . $document->title . '" has no description to preprocess. Please edit the document to add a description.',
                'document_title' => $document->title,
                'document_id' => $document->document_id,
            ], 422);
        }

        // Check if POST request has custom options
        $removeStopwords = $request->input('remove_stopwords', true);
        $lemmatize = $request->input('lemmatize', true);

        $result = $this->callPythonApi('/preprocess', [
            'text'             => $text,
            'remove_stopwords' => $removeStopwords,
            'lemmatize'        => $lemmatize,
        ]);

        $statusCode = $result['success'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * POST /api/search
     * AI-powered document search — sends query to Python,
     * maps returned file names back to DB documents.
     *
     * Request body:
     *   { "query": "machine learning", "top_k": 10, "min_score": 0.1 }
     *
     * Response:
     *   { "success": true, "query": "...", "results": [...], "result_count": 5 }
     */
    public function searchDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query'     => 'required|string|max:500',
            'top_k'     => 'integer|min:1|max:50',
            'min_score' => 'numeric|min:0|max:1',
        ]);

        $result = $this->callPythonApi('/search', [
            'query'     => $validated['query'],
            'top_k'     => $validated['top_k']     ?? 10,
            'min_score' => $validated['min_score'] ?? 0.0,
        ]);

        // Enrich results with full document data from DB
        if ($result['success'] && !empty($result['results'])) {
            $result['results'] = $this->enrichSearchResults($result['results']);
        }

        $statusCode = $result['success'] ? 200 : 500;
        return response()->json($result, $statusCode);
    }

    /**
     * POST /api/analyze-document
     * Run AI analysis on a specific document the user owns.
     * Saves the processed tokens back to the DB.
     *
     * Request body:
     *   { "document_id": 3 }
     */
    public function analyzeDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => 'required|integer|exists:documents,document_id',
        ]);

        $document = Document::find($validated['document_id']);

        if ($document->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $text = $document->description ?? '';

        if (empty(trim($text))) {
            return response()->json([
                'success' => false,
                'error'   => 'Document has no description to analyze.',
            ], 422);
        }

        $result = $this->callPythonApi('/preprocess', [
            'text'             => $text,
            'remove_stopwords' => true,
            'lemmatize'        => true,
        ]);

        // Persist tokens to DB if successful
        if ($result['success']) {
            $document->update([
                'processed_tokens' => json_encode($result['tokens']),
                'token_count'      => $result['token_count'],
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * POST /api/analyze-query
     * Get detailed NLP analysis of a search query (expansion, suggestions).
     *
     * Request body:
     *   { "query": "AI document search" }
     */
    public function analyzeQuery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $result = $this->callPythonApi('/analyze-query', [
            'query' => $validated['query'],
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * GET /api/ai-health
     * Check whether the Python FastAPI server is reachable.
     * Used by the frontend to show/hide AI features gracefully.
     */
  public function checkAiHealth()
{
    $pythonUrl = env('PYTHON_API_URL', 'http://localhost:8000');
    try {
        $response = \Illuminate\Support\Facades\Http::get($pythonUrl . '/health');
        return response()->json([
            'laravel' => 'ok',
            'python'  => $response->json()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'laravel' => 'ok',
            'python'  => 'unreachable',
            'error'   => $e->getMessage()
        ], 500);
    }
}

    // =========================================================================
    //  API ENDPOINTS - DOCUMENT CRUD (Public/Stateless)
    // =========================================================================

    /**
     * GET /api/documents
     * List all documents (no authentication for API)
     */
    public function apiIndex(): JsonResponse
    {
        $documents = Document::select('document_id', 'title', 'author_name', 'description', 'category_id', 'created_at')
            ->with('category:category_id,name')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'count' => $documents->count(),
            'documents' => $documents
        ]);
    }

    /**
     * GET /api/documents/{id}
     * Get a specific document
     */
    public function apiShow(int $id): JsonResponse
    {
        $document = Document::with('category')
            ->select('document_id', 'title', 'author_name', 'description', 'category_id', 'file_path', 'created_at')
            ->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'error' => 'Document not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'document' => $document
        ]);
    }

    /**
     * POST /api/documents
     * Create a new document with metadata
     *
     * Request body:
     *   {
     *     "title": "Document Title",
     *     "author_name": "Author Name",
     *     "description": "Document description",
     *     "category_id": 1,
     *     "user_id": 3 (optional - defaults to first user)
     *   }
     */
    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,category_id',
            'user_id' => 'nullable|integer|exists:users,user_id',
        ]);

        // Default to first user if not specified
        $userId = $validated['user_id'] ?? User::query()->value('user_id');

        try {
            $document = Document::create([
                'title' => $validated['title'],
                'author_name' => $validated['author_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'user_id' => $userId,
                'file_path' => null, // Will be set when file is uploaded
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document created successfully',
                'document_id' => $document->document_id,
                'document' => $document
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/upload
     * Upload a file and attach to a document
     *
     * Request (multipart/form-data):
     *   - file: The file to upload
     *   - document_id: ID of document to attach to (optional)
     *   - title: Document title (if creating new)
     *   - description: Document description
     *   - category_id: Category ID
     */
    public function apiUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file' => 'required|file|max:40960', // 40MB max
            'document_id' => 'nullable|integer|exists:documents,document_id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,category_id',
            'author_name' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,user_id',
        ]);

        try {
            $file = $request->file('file');
            $filePath = $file->store('documents', 'public');

            // If document_id provided, update existing
            if ($validated['document_id'] ?? null) {
                $document = Document::find($validated['document_id']);
                if (!$document) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Document not found'
                    ], 404);
                }

                $document->update(['file_path' => $filePath]);

                return response()->json([
                    'success' => true,
                    'message' => 'File uploaded and attached to document',
                    'document_id' => $document->document_id,
                    'file_path' => $filePath
                ], 200);
            }

            // Create new document with file
            $userId = $validated['user_id'] ?? User::query()->value('user_id');
            $title = $validated['title'] ?? $file->getClientOriginalName();

            $document = Document::create([
                'title' => $title,
                'author_name' => $validated['author_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'file_path' => $filePath,
                'user_id' => $userId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and document created',
                'document_id' => $document->document_id,
                'file_path' => $filePath,
                'document' => $document
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    /**
     * Match AI search results (file names) to full Document records in DB.
     * Returns results enriched with title, author, category, description.
     */
    private function enrichSearchResults(array $results): array
    {
        return array_map(function (array $result) {
            // Try to find document in DB by matching file name in file_path
            $document = Document::where('file_path', 'like', '%' . $result['document'] . '%')
                ->where('user_id', Auth::id())
                ->first();

            if ($document) {
                $result['document_id']  = $document->document_id;
                $result['title']        = $document->title;
                $result['author']       = $document->author_name;
                $result['description']  = $document->description;
                $result['category']     = $document->category?->name;
                $result['file_path']    = $document->file_path;
            }

            return $result;
        }, $results);
    }

    // =========================================================================
    //  PAGE VIEWS
    // =========================================================================

    public function showPreprocessTool(): \Illuminate\View\View
    {
        $documents = Auth::user()->documents()->latest()->get();
        return view('documents.preprocess', compact('documents'));
    }

    // =========================================================================
    //  DOCUMENT CRUD
    // =========================================================================

    public function index(): \Illuminate\View\View
    {
        $documents = Auth::user()->documents()->latest()->get();
        return view('documents.index', compact('documents'));
    }

    public function create(): \Illuminate\View\View
    {
        return view('documents.upload');
    }

    public function show(Document $document): \Illuminate\View\View
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }
        return view('documents.show', compact('document'));
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:255',
            'file'        => 'required|file|mimes:pdf,doc,docx,txt|max:40960',
        ]);

        $category = Category::firstOrCreate(['name' => $validated['category']]);
        $path     = $request->file('file')->store('documents', 'public');

        Auth::user()->documents()->create([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $category->category_id,
            'file_path'   => $path,
        ]);

        return redirect()->route('documents.index')
                         ->with('success', 'Document uploaded successfully.');
    }

    public function edit(Document $document): \Illuminate\View\View
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }
        $categories = Category::all();
        return view('documents.edit', compact('document', 'categories'));
    }

    public function update(Request $request, Document $document): \Illuminate\Http\RedirectResponse
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:255',
            'file'        => 'nullable|file|mimes:pdf,doc,docx,txt|max:40960',
        ]);

        $category = Category::firstOrCreate(['name' => $validated['category']]);

        $filePath = $document->file_path;
        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($document->file_path);
            $filePath = $request->file('file')->store('documents', 'public');
        }

        $document->update([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $category->category_id,
            'file_path'   => $filePath,
        ]);

        return redirect()->route('documents.index')
                         ->with('success', 'Document updated successfully.');
    }

    public function destroy(Document $document): \Illuminate\Http\RedirectResponse
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('documents.index')
                         ->with('success', 'Document deleted successfully.');
    }
}
