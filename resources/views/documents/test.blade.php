<!DOCTYPE html>
<html>
<head>
    <title>Test Documents</title>
</head>
<body>
    <h1>Test Page</h1>
    
    @if(isset($documents) && $documents->count() > 0)
        @foreach($documents as $doc)
            <div>
                <strong>{{ $doc->title }}</strong><br>
                <a href="{{ route('documents.show', $doc->id) }}">View Document</a>
                <hr>
            </div>
        @endforeach
    @else
        <p>No documents found</p>
    @endif
</body>
</html>