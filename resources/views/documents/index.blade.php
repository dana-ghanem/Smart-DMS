<div class="container">
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
@if($documents->count() > 0)
    @foreach($documents as $document)
        <tr>
            <td>{{ $document->title }}</td>
            <td>{{ $document->author }}</td>
            <td><span class="badge bg-info">{{ $document->category->name ?? 'N/A' }}</span></td>
            <td>{{ $document->created_at ? $document->created_at->format('M d, Y') : 'N/A' }}</td>
            <td>
                <!-- Laravel route model binding works with model instances -->
                <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-info">View</a>
                <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-warning">Edit</a>
                <form action="{{ route('documents.destroy', $document) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="5" class="text-center">No documents found.</td>
    </tr>
@endif
</tbody>
    </table>
</div>