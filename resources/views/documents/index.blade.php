<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents — SMART-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/documents.js'])
</head>
<body>

<nav class="nav">
    <a href="{{ route('documents.index') }}" class="nav-brand">smart<span>DMS</span></a>
    <div class="nav-right">
        <div class="user-badge" id="userBadge">
            <div class="user-avatar">
                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
            </div>
            <span class="user-name">{{ Auth::user()->name }}</span>
            <div class="user-tooltip">
                <i class="fas fa-envelope" style="font-size:11px;"></i>
                {{ Auth::user()->email }}
            </div>
        </div>
        <a href="{{ route('documents.create') }}" class="btn btn-primary">
            <i class="fas fa-plus" style="font-size:12px;"></i> Upload New
        </a>
        <a href="{{ route('preprocess.tool') }}" class="btn btn-ghost">
            <i class="fas fa-robot" style="font-size:12px;"></i> AI Preprocess
        </a>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-ghost">
                <i class="fas fa-sign-out-alt" style="font-size:12px;"></i> Logout
            </button>
        </form>
    </div>
</nav>

<div class="main">

    <div class="page-header">
        <h1>My Documents</h1>
        <p>Manage, search, and organise all your uploaded files.</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($documents->isEmpty())

        <div class="empty">
            <div class="empty-icon"><i class="fas fa-folder-open"></i></div>
            <h3>No documents yet</h3>
            <p>Upload your first document to get started.</p>
            <a href="{{ route('documents.create') }}" class="btn btn-primary">
                <i class="fas fa-plus" style="font-size:12px;"></i> Upload Document
            </a>
        </div>

    @else

        @php
            $total      = $documents->count();
            $categories = $documents->pluck('category.name')->filter()->unique()->count();
            $latest     = $documents->first()?->created_at?->format('M d');
        @endphp

        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label">Total Documents</div>
                <div class="stat-value">{{ $total }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Categories</div>
                <div class="stat-value">{{ $categories }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Last Uploaded</div>
                <div class="stat-value" style="font-size:1.35rem;">{{ $latest ?? '—' }}</div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-wrap">
                <i class="fas fa-search"></i>
                <input type="text" class="search-input" id="searchInput"
                    placeholder="Search by title, author, description…" autocomplete="off">
                <div class="autocomplete-dropdown" id="acDropdown"></div>
            </div>

            <div class="toolbar-divider"></div>

            <select class="filter-select" id="categoryFilter">
                <option value="">All Categories</option>
                @foreach($documents->pluck('category.name')->filter()->unique()->sort() as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>

            <select class="filter-select" id="authorFilter">
                <option value="">All Authors</option>
                @foreach($documents->pluck('author_name')->filter()->unique()->sort() as $auth)
                    <option value="{{ $auth }}">{{ $auth }}</option>
                @endforeach
            </select>

            <button class="btn btn-ghost btn-sm" id="clearBtn" style="display:none;" onclick="clearFilters()">
                <i class="fas fa-times" style="font-size:11px;"></i> Clear
            </button>
        </div>

        <div class="result-meta">
            <span id="resultCount"></span>
        </div>

        <div class="table-wrap">
            <table id="docsTable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Uploaded</th>
                        <th>File</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @foreach($documents as $doc)
                        <tr
                            data-title="{{ strtolower($doc->title) }}"
                            data-author="{{ strtolower($doc->author_name ?? '') }}"
                            data-description="{{ strtolower($doc->description ?? '') }}"
                            data-category="{{ $doc->category->name ?? '' }}"
                        >
                            <td class="td-title">{{ $doc->title }}</td>
                            <td class="td-muted">{{ $doc->author_name ?? '—' }}</td>
                            <td>
                                @if($doc->category)
                                    <span class="badge badge-blue">{{ $doc->category->name }}</span>
                                @else
                                    <span class="badge badge-gray">—</span>
                                @endif
                            </td>
                            <td class="td-muted">{{ Str::limit($doc->description, 55) ?: '—' }}</td>
                            <td class="td-muted">{{ $doc->created_at->format('M d, Y') }}</td>
                            <td>
                                <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="file-link">
                                    <i class="fas fa-file-alt" style="font-size:11px;"></i> View
                                </a>
                            </td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('documents.show', $doc->document_id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye" style="font-size:11px;"></i>
                                    </a>
                                    <a href="{{ route('documents.edit', $doc->document_id) }}" class="btn btn-sm btn-warning-outline">
                                        <i class="fas fa-pen" style="font-size:11px;"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-danger-outline"
                                        onclick="openDeleteModal('{{ route('documents.destroy', $doc->document_id) }}', '{{ addslashes($doc->title) }}')"
                                    >
                                        <i class="fas fa-trash" style="font-size:11px;"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr id="noResultsRow" style="display:none;" class="no-results">
                        <td colspan="7">
                            <i class="fas fa-search" style="font-size:18px; display:block; margin-bottom:8px; color:#ccc;"></i>
                            No documents match your search.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    @endif
</div>

{{-- Delete Modal --}}
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-icon"><i class="fas fa-trash"></i></div>
        <h3>Delete this document?</h3>
        <p id="modalText">This document will be permanently deleted. This action cannot be undone.</p>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="closeDeleteModal()">Cancel</button>
            <form method="POST" id="deleteForm" style="display:inline;">
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
