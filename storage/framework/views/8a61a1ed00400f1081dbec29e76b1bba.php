<!DOCTYPE html>
<html>
<head>
    <title>Edit Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Edit Document</h1>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('documents.update', $document->document_id)); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PUT'); ?>
            <div class="mb-3">
                <label>Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo e(old('title', $document->title)); ?>" required>
            </div>
            <div class="mb-3">
                <label>Author</label>
                <input type="text" name="author_name" class="form-control" value="<?php echo e(old('author_name', $document->author_name)); ?>">
            </div>
            <div class="mb-3">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="3"><?php echo e(old('description', $document->description)); ?></textarea>
            </div>
            <div class="mb-3">
                <label>Category</label>
                <input type="text" name="category" class="form-control" value="<?php echo e(old('category', $document->category->name ?? '')); ?>" required>
                <small class="form-text text-muted">Type a category name (e.g. "network"). It will be created if missing.</small>
            </div>
            <div class="mb-3">
                <label>Replace File (optional)</label>
                <input type="file" name="file" class="form-control">
                <small class="form-text text-muted">Leave empty to keep current file.</small>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
            <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html>
<?php /**PATH C:\smart-dms\resources\views/documents/edit.blade.php ENDPATH**/ ?>