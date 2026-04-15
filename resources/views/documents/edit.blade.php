<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Document — SMART-DMS</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>

<nav class="nav">
    <a href="{{ route('documents.index') }}" class="nav-brand">smart<span>DMS</span></a>
    <div class="nav-right">
        <a href="{{ route('documents.index') }}" class="btn btn-ghost">
            <i class="fas fa-arrow-left" style="font-size:12px;"></i> Back
        </a>
    </div>
</nav>

<div class="main-narrow">

    <div class="page-header">
        <h1>Edit Document</h1>
        <p>Updating <strong>{{ $document->title }}</strong></p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                Please fix the following errors:
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="card" style="padding: 2rem;">
        <form action="{{ route('documents.update', $document->document_id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-row">
                <div class="form-group">
                    <label>Title <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $document->title) }}" required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author_name" class="form-control" value="{{ old('author_name', $document->author_name) }}">
                </div>
            </div>

            <div class="form-group">
                <label>Category <span style="color:var(--danger)">*</span></label>
                <input type="text" name="category" class="form-control" value="{{ old('category', $document->category->name ?? '') }}" required>
                <p class="form-hint">A new category will be created automatically if it doesn't exist.</p>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control">{{ old('description', $document->description) }}</textarea>
            </div>

            <div class="form-group">
                <label>Replace File <span style="color:var(--muted); font-weight:400; text-transform:none; letter-spacing:0; font-size:0.8rem;">(optional)</span></label>
                <div class="current-file">
                    <i class="fas fa-check-circle"></i>
                    Current file is kept unless you choose a new one.
                </div>
                <div class="file-drop" id="fileDrop">
                    <input type="file" name="file" id="fileInput" accept=".pdf,.doc,.docx,.txt">
                    <div class="file-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <p><span>Click to browse</span> or drag & drop to replace</p>
                    <p style="font-size:0.75rem; margin-top:4px;">PDF, DOC, DOCX, TXT — max 40MB</p>
                    <p class="file-name" id="fileName"></p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save" style="font-size:12px;"></i> Save Changes
                </button>
                <a href="{{ route('documents.index') }}" class="btn btn-ghost">Cancel</a>
                <div class="form-actions-right">
                    <button type="button" class="btn btn-danger-solid" onclick="openDeleteModalStatic()">
                        <i class="fas fa-trash" style="font-size:12px;"></i> Delete
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Delete Modal --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-icon"><i class="fas fa-trash"></i></div>
        <h3>Delete this document?</h3>
        <p id="modalText">
            <strong>{{ $document->title }}</strong> will be permanently deleted along with its file.
            This action cannot be undone.
        </p>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" action="{{ route('documents.destroy', $document->document_id) }}" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger-solid">
                    <i class="fas fa-trash" style="font-size:12px;"></i> Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    const fileName  = document.getElementById('fileName');
    const fileDrop  = document.getElementById('fileDrop');

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            fileName.textContent = '✓ ' + fileInput.files[0].name;
            fileName.style.display = 'block';
        }
    });
    fileDrop.addEventListener('dragover',  e => { e.preventDefault(); fileDrop.classList.add('dragover'); });
    fileDrop.addEventListener('dragleave', ()  => fileDrop.classList.remove('dragover'));
    fileDrop.addEventListener('drop', e => {
        e.preventDefault(); fileDrop.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileName.textContent = '✓ ' + e.dataTransfer.files[0].name;
            fileName.style.display = 'block';
        }
    });
</script>

</body>
</html>
