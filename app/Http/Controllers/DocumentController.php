<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DocumentController extends Controller
{
    /**
     * Call Python text preprocessing script
     */
    private function callPythonPreprocessing($text, $removeStopwords = true, $lemmatize = true)
    {
        try {
            // Build Python command
            $pythonPath = base_path('python/api.py');
            $command = [
                'python',
                $pythonPath,
                $text,
                $removeStopwords ? 'true' : 'false',
                $lemmatize ? 'true' : 'false'
            ];
            
            $process = new Process($command);
            $process->run();
            
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
            
            $output = $process->getOutput();
            return json_decode($output, true);
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Preprocess text via API
     */
    public function preprocessText(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'remove_stopwords' => 'boolean',
            'lemmatize' => 'boolean',
        ]);

        $text = $validated['text'];
        $removeStopwords = $validated['remove_stopwords'] ?? true;
        $lemmatize = $validated['lemmatize'] ?? true;

        $result = $this->callPythonPreprocessing($text, $removeStopwords, $lemmatize);

        return response()->json($result);
    }

    /**
     * Preprocess and analyze a document
     */
    public function analyzeDocument(Request $request)
    {
        $validated = $request->validate([
            'document_id' => 'required|integer|exists:documents,document_id',
        ]);

        $document = Document::find($validated['document_id']);
        $user = Auth::user();

        // Check authorization
        if ($document->user_id !== $user->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $text = $document->description ?? '';

        if (empty($text)) {
            return response()->json([
                'success' => false,
                'error' => 'Document has no description to analyze'
            ]);
        }

        $result = $this->callPythonPreprocessing($text);

        if ($result['success']) {
            // Save processed tokens to document
            $document->update([
                'processed_tokens' => json_encode($result['tokens']),
                'token_count' => $result['token_count']
            ]);
        }

        return response()->json($result);
    }

    /**
     * Search documents using AI search engine
     */
    public function searchDocuments(Request $request)
    {
        $validated = $request->validate([
            'query'  => 'required|string|max:500',
            'top_k'  => 'integer|min:1|max:20',
            'min_score' => 'numeric|min:0|max:1',
        ]);

        $query = $validated['query'];
        $topK = $validated['top_k'] ?? 10;
        $minScore = $validated['min_score'] ?? 0.0;

        try {
            $pythonPath = base_path('python/search_api.py');
            $command = [
                'python',
                $pythonPath,
                $query,
                (string)$topK,
                (string)$minScore
            ];

            $process = new Process($command);
            $process->setTimeout(30);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();
            $result = json_decode($output, true);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'query' => $query
            ], 500);
        }
    }

    /**
     * Get query processing details (for debugging/analysis)
     */
    public function analyzeQuery(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
        ]);

        $query = $validated['query'];

        try {
            $pythonPath = base_path('python/query_api.py');
            $command = [
                'python',
                $pythonPath,
                $query
            ];

            $process = new Process($command);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = $process->getOutput();
            $result = json_decode($output, true);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'query' => $query
            ], 500);
        }
    }

    /**
     * Show text preprocessing tool
     */
    public function showPreprocessTool()
    {
        $user = Auth::user();
        $documents = $user->documents()->latest()->get();

        return view('documents.preprocess', compact('documents'));
    }

    // Show list of user's documents
    public function index()
    {
        $user = Auth::user();
        $documents = $user->documents()->latest()->get();

        return view('documents.index', compact('documents'));
    }

    // Show upload form
    public function create()
    {
        return view('documents.upload');
    }

    // Show document details
    public function show(Document $document)
    {
        $user = Auth::user();

        if ($document->user_id !== $user->user_id) {
            abort(403);
        }

        return view('documents.show', compact('document'));
    }

    // Store uploaded document
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:255',
            'file'        => 'required|file|mimes:pdf,doc,docx,txt|max:2048',
        ]);

        $category = Category::firstOrCreate([
            'name' => $validated['category']
        ]);

        $path = $request->file('file')->store('documents', 'public');

        $user = Auth::user();
        $user->documents()->create([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $category->category_id,
            'file_path'   => $path,
        ]);

        return redirect()->route('documents.index')
                         ->with('success', 'Document uploaded successfully.');
    }

    // Show edit form
    public function edit(Document $document)
    {
        $user = Auth::user();

        if ($document->user_id !== $user->user_id) {
            abort(403);
        }

        $categories = Category::all();

        return view('documents.edit', compact('document', 'categories'));
    }

    // Update document
    public function update(Request $request, Document $document)
    {
        $user = Auth::user();

        if ($document->user_id !== $user->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:255',
            'file'        => 'nullable|file|mimes:pdf,doc,docx,txt|max:2048',
        ]);

        $category = Category::firstOrCreate([
            'name' => $validated['category']
        ]);

        if ($request->hasFile('file')) {
            Storage::disk('public')->delete($document->file_path);

            $validated['file_path'] = $request->file('file')->store('documents', 'public');
        }

        $document->update([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $category->category_id,
            'file_path'   => $validated['file_path'] ?? $document->file_path,
        ]);

        return redirect()->route('documents.index')
                         ->with('success', 'Document updated successfully.');
    }

    // Delete document
    public function destroy(Document $document)
    {
        $user = Auth::user();

        if ($document->user_id !== $user->user_id) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);

        $document->delete();

        return redirect()->route('documents.index')
                         ->with('success', 'Document deleted successfully.');
    }
}
