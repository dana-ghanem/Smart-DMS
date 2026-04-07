<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in — SMART-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <style>
        .auth-body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--bg);
            padding: 2rem;
        }
        .auth-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
        }
        .auth-logo {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            letter-spacing: -0.02em;
            color: var(--text);
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1.75rem;
        }
        .auth-logo span { color: var(--accent); }
        .auth-title { font-size: 1.35rem; font-weight: 600; margin-bottom: 4px; letter-spacing: -0.02em; }
        .auth-subtitle { color: var(--muted); font-size: 0.875rem; margin-bottom: 1.75rem; }
        .btn-full { width: 100%; justify-content: center; padding: 11px 18px; font-size: 0.9rem; margin-top: 0.5rem; }
        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.85rem; color: var(--muted); }
        .auth-footer a { color: var(--accent); text-decoration: none; font-weight: 500; }
        .auth-footer a:hover { text-decoration: underline; }
        .remember-row { display: flex; align-items: center; gap: 8px; margin-bottom: 1.25rem; }
        .remember-row input[type=checkbox] { width: 15px; height: 15px; accent-color: var(--accent); cursor: pointer; }
        .remember-row label { font-size: 0.875rem; color: var(--muted); cursor: pointer; }
        .divider { height: 1px; background: var(--border); margin: 1.5rem 0; }
    </style>
</head>
<body class="auth-body">

    <div class="auth-card">
        <a href="<?php echo e(url('/')); ?>" class="auth-logo">smart<span>DMS</span></a>

        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Log in to access your documents.</p>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo e($errors->first()); ?>

            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('login')); ?>">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label>Email address</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo e(old('email')); ?>"
                    placeholder="you@example.com"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label>Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    required
                >
            </div>

            <div class="remember-row">
                <input type="checkbox" name="remember" id="remember">
                <label for="remember">Remember me for 30 days</label>
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-sign-in-alt" style="font-size:13px;"></i>
                Log in
            </button>
        </form>

        <div class="divider"></div>

        <div class="auth-footer">
            Don't have an account?
            <?php if(Route::has('register')): ?>
                <a href="<?php echo e(route('register')); ?>">Create one free</a>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
<?php /**PATH C:\Smart-DMS\resources\views/auth/login.blade.php ENDPATH**/ ?>