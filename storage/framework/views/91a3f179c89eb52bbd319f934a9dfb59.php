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
<?php if($documents->count() > 0): ?>
    <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr>
            <td><?php echo e($document->title); ?></td>
            <td><?php echo e($document->author); ?></td>
            <td><span class="badge bg-info"><?php echo e($document->category->name ?? 'N/A'); ?></span></td>
            <td><?php echo e($document->created_at ? $document->created_at->format('M d, Y') : 'N/A'); ?></td>
            <td>
                <!-- Laravel route model binding works with model instances -->
                <a href="<?php echo e(route('documents.show', $document)); ?>" class="btn btn-sm btn-info">View</a>
                <a href="<?php echo e(route('documents.edit', $document)); ?>" class="btn btn-sm btn-warning">Edit</a>
                <form action="<?php echo e(route('documents.destroy', $document)); ?>" method="POST" style="display:inline-block;">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php else: ?>
    <tr>
        <td colspan="5" class="text-center">No documents found.</td>
    </tr>
<?php endif; ?>
</tbody>
    </table>
</div><?php /**PATH C:\xampp\htdocs\Smart-DMS\resources\views/documents/index.blade.php ENDPATH**/ ?>