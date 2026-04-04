<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — SMART-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
        .divider { height: 1px; background: var(--border); margin: 1.5rem 0; }
        .password-hint { font-size: 0.75rem; color: var(--muted); margin-top: 5px; }
    </style>
</head>
<body class="auth-body">

    <div class="auth-card">
        <a href="{{ url('/') }}" class="auth-logo">smart<span>DMS</span></a>

        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">Start managing your documents for free.</p>

        @if ($errors->any())
            <div class="alert alert-danger" style="margin-bottom: 1.25rem;">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <ul style="margin: 4px 0 0 16px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="form-group">
                <label>Full name</label>
                <input
                    type="text"
                    name="name"
                    class="form-control"
                    value="{{ old('name') }}"
                    placeholder="e.g. Jamal Hassan"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label>Email address</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="{{ old('email') }}"
                    placeholder="you@example.com"
                    required
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
                <p class="password-hint">Minimum 8 characters.</p>
            </div>

            <div class="form-group">
                <label>Confirm password</label>
                <input
                    type="password"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="••••••••"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-full">
                <i class="fas fa-user-plus" style="font-size:13px;"></i>
                Create account
            </button>
        </form>

        <div class="divider"></div>

        <div class="auth-footer">
            Already have an account?
            <a href="{{ route('login') }}">Log in</a>
        </div>
    </div>

</body>
</html>
