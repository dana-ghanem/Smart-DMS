<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $document->title }} — SMART-DMS</title>
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

    <div class="doc-header">
        <div class="doc-icon"><i class="fas fa-file-alt"></i></div>
        <div>
            <h1>{{ $document->title }}</h1>
            <p>Uploaded {{ $document->created_at->format('M d, Y \a\t H:i') }}</p>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1rem; overflow:hidden;">
        <div class="detail-row">
            <span class="detail-label">Author</span>
            <span class="detail-value">{{ $document->author_name ?? '—' }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Category</span>
            <span class="detail-value">
                @if($document->category)
                    <span class="badge badge-blue">{{ $document->category->name }}</span>
                @else
                    <span class="detail-value muted">Uncategorised</span>
                @endif
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Description</span>
            <span class="detail-value {{ !$document->description ? 'muted' : '' }}">
                {{ $document->description ?? 'No description provided.' }}
            </span>
        </div>
        <div class="detail-row">
            <span class="detail-label">File</span>
            <span class="detail-value">
                <a href="{{ Storage::url($document->file_path) }}" target="_blank" class="file-btn">
                    <i class="fas fa-download" style="font-size:12px;"></i> View / Download
                </a>
            </span>
        </div>
    </div>

    <div class="actions-bar">
        <a href="{{ route('documents.edit', $document->document_id) }}" class="btn btn-warning-outline">
            <i class="fas fa-pen" style="font-size:12px;"></i> Edit
        </a>
        <div class="actions-bar-right">
            <button class="btn btn-danger-solid" onclick="openDeleteModalStatic()">
                <i class="fas fa-trash" style="font-size:12px;"></i> Delete
            </button>
        </div>
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

</body>
</html>
