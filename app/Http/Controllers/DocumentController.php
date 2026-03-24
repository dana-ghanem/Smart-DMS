<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // Show list of user's documents
    public function index()
    {
        $documents = Auth::user()->documents()->latest()->get();
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
            'author'      => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|string|max:100',
            'file'        => 'required|file|mimes:pdf,doc,docx,txt|max:2048', // adjust as needed
        ]);

        // Store the file
        $path = $request->file('file')->store('documents', 'public');

        // Create the document record
        Auth::user()->documents()->create([
            'title'       => $validated['title'],
            'author'      => $validated['author'],
            'description' => $validated['description'],
            'category'    => $validated['category'],
            'file_path'   => $path,
        ]);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }
}