<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function store(Request $request)
    {
        // 1️⃣ Validate input
        $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|integer',
            'file' => 'required|mimes:pdf,doc,docx|max:2048',
        ]);

        // 2️⃣ Store file
        $filePath = $request->file('file')->store('documents');

        // 3️⃣ Save record in database
        Document::create([
            'title' => $request->title,
            'author' => $request->author,
            'description' => $request->description,
            'file_path' => $filePath,
            'user_id' => 1, // For now, use a test user (later use Auth)
            'category_id' => $request->category_id,
        ]);

        return back()->with('success', 'Document uploaded successfully!');
    }
}