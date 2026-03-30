<!DOCTYPE html>
<html>
<head>
    <title>Test Documents</title>
</head>
<body>
    <h1>Test Page</h1>
    
    <?php if(isset($documents) && $documents->count() > 0): ?>
        <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div>
                <strong><?php echo e($doc->title); ?></strong><br>
                <a href="<?php echo e(route('documents.show', $doc->id)); ?>">View Document</a>
                <hr>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php else: ?>
        <p>No documents found</p>
    <?php endif; ?>
</body>
</html><?php /**PATH C:\xampp\htdocs\Smart-DMS\resources\views/documents/test.blade.php ENDPATH**/ ?>