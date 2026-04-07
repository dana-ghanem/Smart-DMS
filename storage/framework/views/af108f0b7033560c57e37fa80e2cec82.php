<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document — SMART-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>

<nav class="nav">
    <a href="<?php echo e(route('documents.index')); ?>" class="nav-brand">smart<span>DMS</span></a>
    <div class="nav-right">
        <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-ghost">
            <i class="fas fa-arrow-left" style="font-size:12px;"></i> Back
        </a>
    </div>
</nav>

<div class="main-narrow">

    <div class="page-header">
        <h1>Upload Document</h1>
        <p>Add a new file to your document library.</p>
    </div>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                Please fix the following errors:
                <ul>
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 2rem;">
        <form action="<?php echo e(route('documents.store')); ?>" method="POST" enctype="multipart/form-data" id="uploadForm">
            <?php echo csrf_field(); ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Title <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?php echo e(old('title')); ?>" placeholder="e.g. Network Report" required>
                </div>
                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author_name" class="form-control" value="<?php echo e(old('author_name')); ?>" placeholder="e.g. Jamal">
                </div>
            </div>

            <div class="form-group">
                <label>Category <span style="color:var(--danger)">*</span></label>
                <input type="text" name="category" class="form-control" value="<?php echo e(old('category')); ?>" placeholder="e.g. network, reports, general…" required>
                <p class="form-hint">A new category will be created automatically if it doesn't exist.</p>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" placeholder="Brief description of this document…"><?php echo e(old('description')); ?></textarea>
            </div>

            <div class="form-group">
                <label>File <span style="color:var(--danger)">*</span></label>
                <div class="file-drop" id="fileDrop">
                    <input type="file" name="file" id="fileInput" accept=".pdf,.doc,.docx,.txt" required>
                    <div class="file-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <p><span>Click to browse</span> or drag & drop</p>
                    <p style="font-size:0.75rem; margin-top:4px;">PDF, DOC, DOCX, TXT — max 2MB</p>
                    <p class="file-name" id="fileName"></p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload" style="font-size:12px;"></i> Upload Document
                </button>
                <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    const fileInput = document.getElementById('fileInput');
    const fileName  = document.getElementById('fileName');
    const fileDrop  = document.getElementById('fileDrop');

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) {
            fileName.textContent = '✓ ' + fileInput.files[0].name;
            fileName.style.display = 'block';
        }
    });
    fileDrop.addEventListener('dragover',  e => { e.preventDefault(); fileDrop.classList.add('dragover'); });
    fileDrop.addEventListener('dragleave', ()  => fileDrop.classList.remove('dragover'));
    fileDrop.addEventListener('drop', e => {
        e.preventDefault(); fileDrop.classList.remove('dragover');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            fileName.textContent = '✓ ' + e.dataTransfer.files[0].name;
            fileName.style.display = 'block';
        }
    });
</script>

</body>
</html>
<?php /**PATH C:\Smart-DMS\resources\views/documents/upload.blade.php ENDPATH**/ ?>