<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Show list of user's documents
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $documents = $user->documents()->latest()->get();
        return view('documents.index', compact('documents'));
    }

    // Show upload form
    public function create()
    {
        $categories = \App\Models\Category::all();
        return view('documents.upload', compact('categories'));
    }

    // Show document details
    public function show(Document $document)
    {
        /** @var User $user */
        $user = Auth::user();

        // Ensure the document belongs to the user
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

        // Create or find category by name and use its category_id
        $category = Category::firstOrCreate(['name' => $validated['category']]);

        // Store the file
        $path = $request->file('file')->store('documents', 'public');

        // Create the document record
        /** @var User $user */
        $user = Auth::user();
        $user->documents()->create([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'category_id' => $category->category_id,
            'file_path'   => $path,
        ]);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }

    // — Show edit form
    public function edit(Document $document)
    {
        /** @var User $user */
        $user = Auth::user();

        // Ensure the document belongs to the user
        if ($document->user_id !== $user->user_id) {
            abort(403);
        }

        $categories = Category::all();

        return view('documents.edit', compact('document', 'categories'));
    }

    // — Save edited document
    public function update(Request $request, Document $document)
    {
        /** @var User $user */
        $user = Auth::user();

        // Ensure the document belongs to the user
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

        $category = Category::firstOrCreate(['name' => $validated['category']]);

        // If a new file is uploaded, replace the old one
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

        return redirect()->route('documents.index')->with('success', 'Document updated successfully.');
    }

    // — Delete document
    public function destroy(Document $document)
    {
        /** @var User $user */
        $user = Auth::user();

        // Ensure the document belongs to the user
        if ($document->user_id !== $user->user_id) {
            abort(403);
        }

        // Delete the file from storage
        Storage::disk('public')->delete($document->file_path);

        // Delete the database record
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
