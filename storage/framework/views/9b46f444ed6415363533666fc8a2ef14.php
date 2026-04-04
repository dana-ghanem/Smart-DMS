<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SMART-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        .welcome-body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: var(--bg);
        }
        .welcome-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 3rem;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .welcome-logo {
            font-family: 'Playfair Display', serif;
            font-size: 2.25rem;
            font-weight: 600;
            letter-spacing: -0.03em;
            color: var(--text);
            margin-bottom: 8px;
        }
        .welcome-logo span { color: var(--accent); }
        .welcome-tagline {
            color: var(--muted);
            font-size: 0.95rem;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }
        .welcome-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .welcome-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0.5rem 0;
            color: var(--muted);
            font-size: 0.8rem;
        }
        .welcome-divider::before,
        .welcome-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }
        .btn-full { width: 100%; justify-content: center; padding: 11px 18px; font-size: 0.9rem; }
        .welcome-footer {
            margin-top: 2rem;
            color: var(--muted);
            font-size: 0.8rem;
        }
        .feature-row {
            display: flex;
            gap: 16px;
            margin-bottom: 2rem;
            text-align: left;
        }
        .feature-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            padding: 14px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
        .feature-item i { font-size: 16px; color: var(--accent); }
        .feature-item span { font-size: 0.8rem; color: var(--muted); font-weight: 500; }
    </style>
</head>
<body class="welcome-body">

    <div class="welcome-card">
        <div class="welcome-logo">smart<span>DMS</span></div>
        <p class="welcome-tagline">Your personal document management system.<br>Upload, organise, and find files instantly.</p>

        <div class="feature-row">
            <div class="feature-item">
                <i class="fas fa-upload"></i>
                <span>Upload & store files</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-search"></i>
                <span>Smart search</span>
            </div>
            <div class="feature-item">
                <i class="fas fa-folder-open"></i>
                <span>Organised by category</span>
            </div>
        </div>

        <?php if(auth()->guard()->check()): ?>
            
            <div class="welcome-actions">
                <a href="<?php echo e(route('documents.index')); ?>" class="btn btn-primary btn-full">
                    <i class="fas fa-folder-open" style="font-size:13px;"></i>
                    Go to My Documents
                </a>
            </div>
            <p class="welcome-footer">
                Logged in as <strong><?php echo e(Auth::user()->name); ?></strong>
            </p>
        <?php else: ?>
            
            <div class="welcome-actions">
                <a href="<?php echo e(route('login')); ?>" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt" style="font-size:13px;"></i>
                    Log in
                </a>
                <?php if(Route::has('register')): ?>
                    <div class="welcome-divider">or</div>
                    <a href="<?php echo e(route('register')); ?>" class="btn btn-ghost btn-full">
                        Create an account
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
<?php /**PATH C:\smart-dms\resources\views/welcome.blade.php ENDPATH**/ ?>