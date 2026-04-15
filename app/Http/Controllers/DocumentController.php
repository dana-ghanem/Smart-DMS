<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    private string $pythonApiUrl;

    public function __construct()
    {
        $this->pythonApiUrl = env('PYTHON_API_URL', 'http://localhost:8000');
    }

    // =========================================================================
    //  PRIVATE — Python API Communication
    // =========================================================================

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
    //  PRIVATE — Index Helpers  (NEW)
    // =========================================================================

    /**
     * Tell Python to index (or re-index) a document.
     * Called after store() and update().
     * Failures are logged but never shown to the user — upload still succeeds.
     */
    private function indexDocumentInPython(Document $document): void
    {
        $category = $document->category?->name ?? '';
        $searchableText = $this->buildSearchableText($document);

        $result = $this->callPythonApi('/index-document', [
            'document_id' => $document->document_id,
            'title'       => $document->title       ?? '',
            'author'      => $document->author_name ?? '',
            'description' => $document->description ?? '',
            'category'    => $category,
            'text'        => $searchableText,
        ], timeout: 10);

        if (!($result['success'] ?? false)) {
            Log::warning("Python indexing failed for document #{$document->document_id}: " . ($result['error'] ?? 'unknown'));
        } else {
            Log::info("Python indexed document #{$document->document_id} ({$document->title})");
        }
    }

    /**
     * Tell Python to remove a document from the index.
     * Called after destroy().
     */
    private function removeDocumentFromPython(int $documentId): void
    {
        try {
            Http::timeout(10)->delete("{$this->pythonApiUrl}/remove-document/{$documentId}");
        } catch (\Exception $e) {
            Log::warning("Python remove-document failed for #{$documentId}: " . $e->getMessage());
        }
    }

    private function buildSearchableText(Document $document): string
    {
        $parts = array_filter([
            $document->title,
            $document->author_name,
            $document->description,
            $document->extracted_text,
        ], fn ($value) => filled($value));

        return trim(implode("\n\n", $parts));
    }

    private function extractDocumentText(string $filePath): array
    {
        return $this->callPythonApi('/extract-document', [
            'file_path' => Storage::disk('public')->path($filePath),
        ], timeout: 30);
    }

    private function duplicateDocumentForUser(int $userId, string $fileHash, ?int $ignoreDocumentId = null): ?Document
    {
        return Document::query()
            ->where('user_id', $userId)
            ->where('file_hash', $fileHash)
            ->when($ignoreDocumentId, fn ($query) => $query->where('document_id', '!=', $ignoreDocumentId))
            ->first();
    }

    private function syncDocumentContentAndIndex(Document $document): void
    {
        if ($document->file_path) {
            $extraction = $this->extractDocumentText($document->file_path);

            if ($extraction['success'] ?? false) {
                $document->forceFill([
                    'extracted_text' => $extraction['raw_text'] ?? null,
                    'file_hash'      => $extraction['metadata']['sha256'] ?? $document->file_hash,
                ])->save();
            } else {
                Log::warning("Document extraction failed for #{$document->document_id}: " . ($extraction['error'] ?? 'unknown'));
            }
        }

        $this->indexDocumentInPython($document->fresh()->load('category'));
    }

    private function hashUploadedFile(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    private function syncMissingDocumentContentForUser(int $userId): void
    {
        $documents = Document::query()
            ->where('user_id', $userId)
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->where(function ($query) {
                $query->whereNull('extracted_text')
                    ->orWhere('extracted_text', '')
                    ->orWhereNull('file_hash')
                    ->orWhere('file_hash', '');
            })
            ->with('category')
            ->get();

        foreach ($documents as $document) {
            $this->syncDocumentContentAndIndex($document);
        }
    }

    // =========================================================================
    //  REST API ENDPOINTS  (called by frontend JS / external clients)
    // =========================================================================

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

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function preprocessDocument(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['success' => false, 'error' => 'Document not found.'], 404);
        }

        $text = $document->description ?? '';

        if (empty(trim($text))) {
            return response()->json([
                'success'        => false,
                'error'          => 'Document "' . $document->title . '" has no description to preprocess.',
                'document_title' => $document->title,
                'document_id'    => $document->document_id,
            ], 422);
        }

        $result = $this->callPythonApi('/preprocess', [
            'text'             => $text,
            'remove_stopwords' => $request->input('remove_stopwords', true),
            'lemmatize'        => $request->input('lemmatize', true),
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * POST /api/search
     * FIXED: enrichSearchResults now matches by document_id (not filename).
     */
    public function searchDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query'     => 'required|string|max:500',
            'top_k'     => 'integer|min:1|max:50',
            'min_score' => 'numeric|min:0|max:1',
        ]);

        $this->syncMissingDocumentContentForUser(Auth::id());

        $result = $this->callPythonApi('/search', [
            'query'     => $validated['query'],
            'top_k'     => $validated['top_k']     ?? 10,
            'min_score' => $validated['min_score'] ?? 0.0,
        ]);

        // Enrich results with full DB record
        if ($result['success'] && !empty($result['results'])) {
            $result['results'] = $this->enrichSearchResults($result['results']);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

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

        if ($result['success']) {
            $document->update([
                'processed_tokens' => json_encode($result['tokens']),
                'token_count'      => $result['token_count'],
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function analyzeQuery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $result = $this->callPythonApi('/analyze-query', ['query' => $validated['query']]);
        return response()->json($result, $result['success'] ? 200 : 500);
    }

    public function checkAiHealth()
    {
        $pythonUrl = env('PYTHON_API_URL', 'http://localhost:8000');
        try {
            $response = \Illuminate\Support\Facades\Http::get($pythonUrl . '/health');
            return response()->json(['laravel' => 'ok', 'python' => $response->json()]);
        } catch (\Exception $e) {
            return response()->json(['laravel' => 'ok', 'python' => 'unreachable', 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //  API ENDPOINTS - DOCUMENT CRUD
    // =========================================================================

    public function apiIndex(): JsonResponse
    {
        $documents = Document::select('document_id', 'title', 'author_name', 'description', 'category_id', 'created_at')
            ->with('category:category_id,name')
            ->latest()
            ->get();

        return response()->json(['success' => true, 'count' => $documents->count(), 'documents' => $documents]);
    }

    public function apiShow(int $id): JsonResponse
    {
        $document = Document::with('category')
            ->select('document_id', 'title', 'author_name', 'description', 'category_id', 'file_path', 'created_at')
            ->find($id);

        if (!$document) {
            return response()->json(['success' => false, 'error' => 'Document not found'], 404);
        }

        return response()->json(['success' => true, 'document' => $document]);
    }

    public function apiStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,category_id',
            'user_id'     => 'nullable|integer|exists:users,user_id',
        ]);

        $userId = $validated['user_id'] ?? User::query()->value('user_id');

        try {
            $document = Document::create([
                'title'       => $validated['title'],
                'author_name' => $validated['author_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'user_id'     => $userId,
                'file_path'   => null,
            ]);

            // Index in Python (best-effort)
            $this->indexDocumentInPython($document->load('category'));

            return response()->json([
                'success'     => true,
                'message'     => 'Document created successfully',
                'document_id' => $document->document_id,
                'document'    => $document,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file'        => 'required|file|max:40960',
            'document_id' => 'nullable|integer|exists:documents,document_id',
            'title'       => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'nullable|integer|exists:categories,category_id',
            'author_name' => 'nullable|string|max:255',
            'user_id'     => 'nullable|integer|exists:users,user_id',
        ]);

        try {
            $file     = $request->file('file');
            $fileHash = $this->hashUploadedFile($file);
            $userId   = $validated['user_id'] ?? User::query()->value('user_id');

            $filePath = $file->store('documents', 'public');

            if ($validated['document_id'] ?? null) {
                $document = Document::find($validated['document_id']);
                if (!$document) {
                    return response()->json(['success' => false, 'error' => 'Document not found'], 404);
                }
                if ($this->duplicateDocumentForUser($document->user_id, $fileHash, $document->document_id)) {
                    Storage::disk('public')->delete($filePath);

                    return response()->json([
                        'success' => false,
                        'error'   => 'This document already exists and was not uploaded again.',
                    ], 409);
                }

                $document->update([
                    'file_path' => $filePath,
                    'file_hash' => $fileHash,
                ]);
                $this->syncDocumentContentAndIndex($document->load('category'));

                return response()->json([
                    'success'     => true,
                    'message'     => 'File uploaded and attached to document',
                    'document_id' => $document->document_id,
                    'file_path'   => $filePath,
                ]);
            }

            if ($this->duplicateDocumentForUser($userId, $fileHash)) {
                Storage::disk('public')->delete($filePath);

                return response()->json([
                    'success' => false,
                    'error'   => 'This document already exists and was not uploaded again.',
                ], 409);
            }

            $title    = $validated['title']   ?? $file->getClientOriginalName();
            $document = Document::create([
                'title'       => $title,
                'author_name' => $validated['author_name'] ?? null,
                'description' => $validated['description'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
                'file_path'   => $filePath,
                'file_hash'   => $fileHash,
                'user_id'     => $userId,
            ]);

            $this->syncDocumentContentAndIndex($document->load('category'));

            return response()->json([
                'success'     => true,
                'message'     => 'File uploaded and document created',
                'document_id' => $document->document_id,
                'file_path'   => $filePath,
                'document'    => $document,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    //  FIXED: enrichSearchResults — matches by document_id, not filename
    // =========================================================================

    /**
     * Python now returns document_id in every result, so we look up by PK.
     * We still restrict results to the current user's documents.
     */
    private function enrichSearchResults(array $results): array
    {
        return array_map(function (array $result) {
            $documentId = $result['document_id'] ?? null;

            if ($documentId) {
                $document = Document::where('document_id', $documentId)
                    ->where('user_id', Auth::id())
                    ->with('category')
                    ->first();

                if ($document) {
                    $result['document_id'] = $document->document_id;
                    $result['title']       = $document->title;
                    $result['author']      = $document->author_name;
                    $result['description'] = $document->description;
                    $result['category']    = $document->category?->name;
                    $result['file_path']   = $document->file_path;
                    $result['content']     = $document->extracted_text ?: ($result['content'] ?? null);
                }
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
    //  DOCUMENT CRUD (web, session-auth)
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

    /**
     * UPDATED: calls indexDocumentInPython() after saving.
     */
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
        $file     = $request->file('file');
        $fileHash = $this->hashUploadedFile($file);

        if ($this->duplicateDocumentForUser(Auth::id(), $fileHash)) {
            return back()->withErrors([
                'file' => 'This document already exists and was not uploaded again.',
            ])->withInput();
        }

        $path = $file->store('documents', 'public');

        $document = Auth::user()->documents()->create([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $category->category_id,
            'file_path'   => $path,
            'file_hash'   => $fileHash,
        ]);

        $this->syncDocumentContentAndIndex($document->load('category'));

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

    /**
     * UPDATED: re-indexes after metadata/file change.
     */
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
            $newFile = $request->file('file');
            $fileHash = $this->hashUploadedFile($newFile);

            if ($this->duplicateDocumentForUser(Auth::id(), $fileHash, $document->document_id)) {
                return back()->withErrors([
                    'file' => 'This document already exists and was not uploaded again.',
                ])->withInput();
            }

            Storage::disk('public')->delete($document->file_path);
            $filePath = $newFile->store('documents', 'public');
        } else {
            $fileHash = $document->file_hash;
        }

        $document->update([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $category->category_id,
            'file_path'   => $filePath,
            'file_hash'   => $fileHash,
        ]);

        $this->syncDocumentContentAndIndex($document->fresh()->load('category'));

        return redirect()->route('documents.index')
                         ->with('success', 'Document updated successfully.');
    }

    /**
     * UPDATED: removes from Python index on delete.
     */
    public function destroy(Document $document): \Illuminate\Http\RedirectResponse
    {
        if ($document->user_id !== Auth::id()) {
            abort(403);
        }

        $documentId = $document->document_id;

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        // ← NEW: remove from Python index
        $this->removeDocumentFromPython($documentId);

        return redirect()->route('documents.index')
                         ->with('success', 'Document deleted successfully.');
    }
}
