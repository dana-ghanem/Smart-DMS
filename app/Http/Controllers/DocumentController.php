<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
=======
use Illuminate\Http\Request;
>>>>>>> Lora-Sobh
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
        return view('documents.upload');
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

<<<<<<< HEAD
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
=======
        // 3️⃣ Save record in database
        Document::create([
            'title' => $request->title,
            'author' => $request->author,
            'description' => $request->description,
            'file_path' => $filePath,
            'user_id' => 1,
            'category_id' => $request->category_id,
>>>>>>> Lora-Sobh
        ]);

        return redirect()->route('documents.index')->with('success', 'Document uploaded successfully.');
    }

<<<<<<< HEAD
    // — Show edit form
    public function edit($id)
    {
        /** @var User $user */
        $user = Auth::user();

        $document = $user->documents()->findOrFail($id);
        $categories = Category::all();

        return view('documents.edit', compact('document', 'categories'));
    }

    // — Save edited document
    public function update(Request $request, $id)
    {
        /** @var User $user */
        $user = Auth::user();

        $document = $user->documents()->findOrFail($id);

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
    public function destroy($id)
    {
        /** @var User $user */
        $user = Auth::user();

        $document = $user->documents()->findOrFail($id);

        // Delete the file from storage
        Storage::disk('public')->delete($document->file_path);

        // Delete the database record
        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
    }
}
=======
  public function index() {
    $documents = Document::with('category')->get(); // eager load category
    return view('documents.index', compact('documents'));
}
// DocumentController.php

public function show(Document $document) {
    return view('documents.show', compact('document'));
}

public function edit(Document $document) {
    return view('documents.edit', compact('document'));
}

public function destroy(Document $document) {
    $document->delete();
    return redirect()->route('documents.index')->with('success', 'Document deleted successfully.');
}
}
>>>>>>> Lora-Sobh
