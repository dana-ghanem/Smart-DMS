<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * ApiController
 * =============
 * Stateless REST API for external clients (Postman, mobile, frontend JS).
 * Uses token-based authentication via Laravel Sanctum.
 *
 * All routes are registered in routes/api.php under /api prefix.
 */
class ApiController extends Controller
{
    private string $pythonApiUrl;

    public function __construct()
    {
        $this->pythonApiUrl = env('PYTHON_API_URL', 'http://localhost:8000');
    }

    private function extractDocumentText(string $filePath): array
    {
        return $this->callPython('/extract-document', [
            'file_path' => Storage::disk('public')->path($filePath),
        ], 30);
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

    private function hashUploadedFile(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    private function duplicateDocumentForUser(int $userId, string $fileHash, ?int $ignoreDocumentId = null): ?Document
    {
        return Document::query()
            ->where('user_id', $userId)
            ->where('file_hash', $fileHash)
            ->when($ignoreDocumentId, fn ($query) => $query->where('document_id', '!=', $ignoreDocumentId))
            ->first();
    }

    private function indexDocumentInPython(Document $document): void
    {
        $result = $this->callPython('/index-document', [
            'document_id' => $document->document_id,
            'title'       => $document->title ?? '',
            'author'      => $document->author_name ?? '',
            'description' => $document->description ?? '',
            'category'    => $document->category?->name ?? '',
            'text'        => $this->buildSearchableText($document),
        ], 15);

        if (! ($result['success'] ?? false)) {
            Log::warning("Python indexing failed for document #{$document->document_id}: " . ($result['error'] ?? 'unknown'));
        }
    }

    private function removeDocumentFromPython(int $documentId): void
    {
        try {
            Http::timeout(10)->delete("{$this->pythonApiUrl}/remove-document/{$documentId}");
        } catch (\Exception $e) {
            Log::warning("Python remove-document failed for #{$documentId}: " . $e->getMessage());
        }
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

        $this->indexDocumentInPython($document->fresh('category'));
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
    //  AUTH — Login & Register (no token required)
    // =========================================================================

    /**
     * POST /api/auth/register
     * Body: { name, email, password, password_confirmation }
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user  = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => bcrypt($validated['password']),
        ]);
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => ['id' => $user->getKey(), 'name' => $user->name, 'email' => $user->email],
        ], 201);
    }

    /**
     * POST /api/auth/login
     * Body: { email, password }
     */
   public function login(Request $request): JsonResponse
{
    $validated = $request->validate([
        'email'    => 'required|email',
        'password' => 'required|string',
    ]);

    if (!Auth::attempt($validated)) {
        return response()->json([
            'success' => false,
            'error'   => 'Invalid credentials.'
        ], 401);
    }

    $user = User::where('email', $validated['email'])->first();

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'token'   => $token,
        'user'    => [
            'id' => $user->getKey(),
            'name'  => $user->name,
            'email' => $user->email,
        ],
    ]);
}

    /**
     * POST /api/auth/logout
     * Header: Authorization: Bearer <token>
     */
    public function logout(Request $request): JsonResponse
{
    $request->user()->tokens()->delete();

    return response()->json([
        'success' => true,
        'message' => 'Logged out.'
    ]);
}

    // =========================================================================
    //  DOCUMENTS — CRUD
    // =========================================================================

    /**
     * GET /api/documents
     * Returns all documents belonging to the authenticated user.
     */
    public function listDocuments(Request $request): JsonResponse
    {
        $documents = $request->user()
            ->documents()
            ->with('category')
            ->latest()
            ->get()
            ->map(fn($d) => $this->formatDocument($d));

        return response()->json([
            'success' => true,
            'count'   => $documents->count(),
            'data'    => $documents,
        ]);
    }

    /**
     * GET /api/documents/{id}
     */
    public function getDocument(Request $request, string $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json(['success' => false, 'error' => 'Document not found.'], 404);
        }

        if ($document->user_id !== $request->user()->getKey()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'data' => $this->formatDocument($document)]);
    }

    /**
     * POST /api/documents
     * Create document with metadata only (no file).
     * Body (JSON): { title, author_name, description, category_id, category }
     */
    public function createDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:255|required_without:category_id',
            'category_id' => 'nullable|integer|exists:categories,category_id|required_without:category',
        ]);

        $categoryId = $this->resolveCategoryId($validated);

        $document = $request->user()->documents()->create([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $categoryId,
            'file_path'   => '',
        ]);

        $this->indexDocumentInPython($document->load('category'));

        return response()->json([
            'success' => true,
            'message' => 'Document created.',
            'data'    => $this->formatDocument($document->load('category')),
        ], 201);
    }

    /**
     * POST /api/upload
     * Upload a file with metadata.
     * Body (multipart/form-data): file, title, author_name, description, category
     */
    public function uploadDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'file'        => 'required|file|mimes:pdf,doc,docx,txt|max:40960',
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:categories,category_id',
        ]);

        $categoryId = $this->resolveCategoryId($validated, createDefault: true);
        $file = $request->file('file');
        $fileHash = $this->hashUploadedFile($file);
        $userId = (int) $request->user()->getKey();

        if ($this->duplicateDocumentForUser($userId, $fileHash)) {
            return response()->json([
                'success' => false,
                'error'   => 'This document already exists and was not uploaded again.',
            ], 409);
        }

        $path = $file->store('documents', 'public');

        $document = $request->user()->documents()->create([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $categoryId,
            'file_path'   => $path,
            'file_hash'   => $fileHash,
        ]);

        $this->syncDocumentContentAndIndex($document->load('category'));

        return response()->json([
            'success'  => true,
            'message'  => 'File uploaded successfully.',
            'data'     => $this->formatDocument($document->load('category')),
        ], 201);
    }

    /**
     * PUT /api/documents/{id}
     * Body (JSON): { title, author_name, description, category }
     */
    public function updateDocument(Request $request, string $id): JsonResponse
    {
        $document = $this->findOwnedDocument($request, $id);

        if (!$document) {
            return response()->json(['success' => false, 'error' => 'Document not found.'], 404);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'author_name' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'category'    => 'sometimes|nullable|string|max:255',
            'category_id' => 'sometimes|nullable|integer|exists:categories,category_id',
        ]);

        if ($validated === []) {
            throw ValidationException::withMessages([
                'body' => ['Provide at least one updatable field: title, author_name, description, category, or category_id.'],
            ]);
        }

        if (array_key_exists('category', $validated) || array_key_exists('category_id', $validated)) {
            $validated['category_id'] = $this->resolveCategoryId($validated);
            unset($validated['category']);
        }

        $document->update($validated);
        $this->indexDocumentInPython($document->fresh('category'));

        return response()->json([
            'success' => true,
            'message' => 'Document updated.',
            'data'    => $this->formatDocument($document->fresh('category')),
        ]);
    }

    /**
     * DELETE /api/documents/{id}
     */
    public function deleteDocument(Request $request, string $id): JsonResponse
    {
        $document = $this->findOwnedDocument($request, $id);

        if (!$document) {
            return response()->json(['success' => false, 'error' => 'Document not found.'], 404);
        }

        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $this->removeDocumentFromPython($document->document_id);
        $document->delete();

        return response()->json(['success' => true, 'message' => 'Document deleted.']);
    }

    // =========================================================================
    //  AI ENDPOINTS
    // =========================================================================

    /**
     * POST /api/preprocess
     * Body: { text, remove_stopwords, lemmatize }
     */
    public function preprocessText(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text'             => 'required|string|max:10000',
            'remove_stopwords' => 'boolean',
            'lemmatize'        => 'boolean',
        ]);

        $result = $this->callPython('/preprocess', [
            'text'             => $validated['text'],
            'remove_stopwords' => $validated['remove_stopwords'] ?? true,
            'lemmatize'        => $validated['lemmatize']        ?? true,
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * POST /api/preprocess/document/{id}
     * Preprocess a specific document by ID.
     * Body: { remove_stopwords, lemmatize }
     */
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
            'document_id'    => $document->document_id,
            'document_title' => $document->title,
            'edit_url'       => url("/documents/{$id}/edit"),
        ], 422);
    }

    $result = $this->callPython('/preprocess', [
        'text'             => $text,
        'remove_stopwords' => $request->input('remove_stopwords', true),
        'lemmatize'        => $request->input('lemmatize', true),
    ]);

    if ($result['success']) {
        $document->update([
            'processed_tokens' => json_encode($result['tokens'] ?? []),
            'token_count'      => $result['token_count'] ?? 0,
        ]);
    }

    return response()->json($result, $result['success'] ? 200 : 500);
}

    /**
     * POST /api/search
     * Body: { query, top_k, min_score }
     */
    public function searchDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query'     => 'required|string|max:500',
            'top_k'     => 'integer|min:1|max:50',
            'min_score' => 'numeric|min:0|max:1',
        ]);

        $this->syncMissingDocumentContentForUser((int) $request->user()->getKey());

        $result = $this->callPython('/search', [
            'query'     => $validated['query'],
            'top_k'     => $validated['top_k']     ?? 10,
            'min_score' => $validated['min_score'] ?? 0.0,
        ]);

        if ($result['success'] && !empty($result['results'])) {
            $result['results'] = $this->enrichResults($result['results'], $request->user());
        }

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * POST /api/analyze-document
     * Body: { document_id }
     */
    public function analyzeDocument(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_id' => 'required|integer|exists:documents,document_id',
        ]);

        $document = Document::find($validated['document_id']);

        if ($document->user_id !== $request->user()->getKey()) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 403);
        }

        $text = $document->description ?? '';
        if (empty(trim($text))) {
            return response()->json([
                'success' => false,
                'error'   => 'Document has no description to analyze.',
            ], 422);
        }

        $result = $this->callPython('/preprocess', [
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

    /**
     * POST /api/analyze-query
     * Body: { query }
     */
    public function analyzeQuery(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $result = $this->callPython('/analyze-query', [
            'query' => $validated['query'],
        ]);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * GET /api/ai-health
     * Check if Python FastAPI is reachable.
     */
    public function aiHealth(): JsonResponse
    {
        try {
            $response = Http::timeout(5)->get("{$this->pythonApiUrl}/health");
            return response()->json([
                'success'   => true,
                'ai_online' => $response->successful(),
                'status'    => $response->successful() ? 'online' : 'degraded',
                'ai_url'    => $this->pythonApiUrl,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'ai_online' => false,
                'status'    => 'offline',
                'error'     => 'AI service unreachable.',
            ]);
        }
    }

    // =========================================================================
    //  PRIVATE HELPERS
    // =========================================================================

    private function callPython(string $endpoint, array $payload, int $timeout = 30): array
    {
        try {
            $response = Http::timeout($timeout)
                ->post("{$this->pythonApiUrl}{$endpoint}", $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("Python API [{$endpoint}] HTTP {$response->status()}", ['body' => $response->body()]);
            return ['success' => false, 'error' => "AI service error: HTTP {$response->status()}"];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Python API unreachable [{$endpoint}]: " . $e->getMessage());
            return ['success' => false, 'error' => 'AI service is currently unavailable.'];
        } catch (\Exception $e) {
            Log::error("Python API unexpected [{$endpoint}]: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function enrichResults(array $results, $user): array
    {
        return array_map(function ($result) use ($user) {
            $documentId = $result['document_id'] ?? null;

            $document = $documentId
                ? Document::where('document_id', $documentId)->where('user_id', $user->getKey())->first()
                : Document::where('file_path', 'like', '%' . $result['document'] . '%')
                    ->where('user_id', $user->getKey())
                    ->first();

            if ($document) {
                $result['document_id'] = $document->document_id;
                $result['title']       = $document->title;
                $result['author']      = $document->author_name;
                $result['description'] = $document->description;
                $result['category']    = $document->category?->name;
                $result['content']     = $document->extracted_text ?: ($result['content'] ?? null);
            }

            return $result;
        }, $results);
    }

    private function formatDocument(Document $document): array
    {
        return [
            'document_id'  => $document->document_id,
            'title'        => $document->title,
            'author_name'  => $document->author_name,
            'description'  => $document->description,
            'extracted_text' => $document->extracted_text,
            'category'     => $document->category?->name,
            'file_path'    => filled($document->file_path)
                ? asset('storage/' . $document->file_path)
                : null,
            'token_count'  => $document->token_count,
            'created_at'   => $document->created_at?->toISOString(),
            'updated_at'   => $document->updated_at?->toISOString(),
        ];
    }

    private function findOwnedDocument(Request $request, string $id): ?Document
    {
        return $request->user()
            ->documents()
            ->with('category')
            ->find((int) $id);
    }

    private function resolveCategoryId(array $validated, bool $createDefault = false): ?int
    {
        if (! empty($validated['category_id'])) {
            return (int) $validated['category_id'];
        }

        if (array_key_exists('category', $validated)) {
            $categoryName = trim((string) ($validated['category'] ?? ''));

            if ($categoryName === '') {
                return null;
            }

            return (int) Category::firstOrCreate(['name' => $categoryName])->category_id;
        }

        if (! $createDefault) {
            return null;
        }

        return (int) Category::firstOrCreate(['name' => 'General'])->category_id;
    }
}
