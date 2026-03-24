<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
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
        return view('documents.upload');
    }

    // Store uploaded document
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|integer',
            'file'        => 'required|file|mimes:pdf,doc,docx,txt|max:2048',
        ]);

        // Store the file
        $path = $request->file('file')->store('documents', 'public');

        // Create the document record
        /** @var User $user */
        $user = Auth::user();
        $user->documents()->create([
            'title'       => $validated['title'],
            'author_name' => $validated['author_name'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'file_path'   => $path,
        ]);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }
}