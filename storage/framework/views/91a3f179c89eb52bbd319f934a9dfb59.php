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
                <a href="<?php echo e(route('documents.create')); ?>" class="btn btn-primary">Upload New</a>
                <form method="POST" action="<?php echo e(route('logout')); ?>" class="d-inline">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-danger">Logout</button>
                </form>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($documents->isEmpty()): ?>
            <p>No documents uploaded yet.</p>
        <?php else: ?>
            <table class="table table-bordered">
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
                <tbody>
                    <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($doc->title); ?></td>
                            <td><?php echo e($doc->author_name ?? '-'); ?></td>
                            <td><?php echo e($doc->category->name ?? '-'); ?></td>
                            <td><?php echo e(Str::limit($doc->description, 50)); ?></td>
                            <td><?php echo e($doc->created_at->format('Y-m-d')); ?></td>
                            <td><a href="<?php echo e(Storage::url($doc->file_path)); ?>" target="_blank">View</a></td>
                            <td>
                                <a href="<?php echo e(route('documents.show', $doc->document_id)); ?>" class="btn btn-sm btn-info">View</a>
                                <a href="<?php echo e(route('documents.edit', $doc->document_id)); ?>" class="btn btn-sm btn-warning">Edit</a>
                                <form method="POST" action="<?php echo e(route('documents.destroy', $doc->document_id)); ?>" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\Smart-DMS\resources\views/documents/index.blade.php ENDPATH**/ ?>