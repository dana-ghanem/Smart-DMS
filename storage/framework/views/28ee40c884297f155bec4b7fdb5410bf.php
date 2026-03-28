<!DOCTYPE html>
<html>
<head>
    <title><?php echo e($document->title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1><?php echo e($document->title); ?></h1>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Details</h5>
                <p><strong>Author:</strong> <?php echo e($document->author_name ?? 'N/A'); ?></p>
                <p><strong>Category:</strong> <?php echo e($document->category->name ?? 'N/A'); ?></p>
                <p><strong>Description:</strong> <?php echo e($document->description ?? 'No description'); ?></p>
                <p><strong>Uploaded:</strong> <?php echo e($document->created_at->format('Y-m-d H:i')); ?></p>
                <p><strong>File:</strong> <a href="<?php echo e(Storage::url($document->file_path)); ?>" target="_blank" class="btn btn-primary">View/Download</a></p>
            </div>
        </div>

        <div class="mt-3">
            <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-secondary">Back to Documents</a>
            <a href="<?php echo e(route('documents.edit', $document->document_id)); ?>" class="btn btn-warning">Edit</a>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\smart-dms\resources\views/documents/show.blade.php ENDPATH**/ ?>