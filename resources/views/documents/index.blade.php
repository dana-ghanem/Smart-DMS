<<<<<<< HEAD
<!DOCTYPE html>
<html>
<head>
    <title>My Documents</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>My Documents</h1>
            <div>
                <a href="{{ route('documents.create') }}" class="btn btn-primary">Upload New</a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if($documents->isEmpty())
            <p>No documents uploaded yet.</p>
        @else
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Uploaded</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($documents as $doc)
                        <tr>
                            <td>{{ $doc->title }}</td>
                            <td>{{ $doc->author_name ?? '-' }}</td>
                            <td>{{ $doc->category->name ?? '-' }}</td>
                            <td>{{ Str::limit($doc->description, 50) }}</td>
                            <td>{{ $doc->created_at->format('Y-m-d') }}</td>
                            <td><a href="{{ Storage::url($doc->file_path) }}" target="_blank">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</body>
</html>
=======
