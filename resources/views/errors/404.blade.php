<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&family=Outfit:wght@400;700;900&display=swap" rel="stylesheet">
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/js/app.js'])
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            display: flex; align-items: center; justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        html.dark body { background: #0f172a; color: #e2e8f0; }

        .card {
            background: #fff;
            border-radius: 1.5rem;
            padding: 3rem 3.5rem;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.08);
        }
        html.dark .card { background: #1e293b; box-shadow: 0 20px 60px rgba(0,0,0,.4); }

        .icon-wrap {
            width: 80px; height: 80px;
            border-radius: 1.25rem;
            background: #eff6ff;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.25rem;
        }
        html.dark .icon-wrap { background: rgba(59,130,246,.15); }

        .big-number {
            font-family: 'Outfit', sans-serif;
            font-size: 5rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .code {
            font-family: 'Outfit', sans-serif;
            font-size: 0.7rem;
            font-weight: 900;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: #3b82f6;
            margin-bottom: 0.75rem;
        }
        h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            font-weight: 900;
            margin-bottom: 0.75rem;
            line-height: 1.2;
        }
        p { font-size: 0.9rem; line-height: 1.7; color: #64748b; margin-bottom: 0.5rem; }
        html.dark p { color: #94a3b8; }

        .actions { display: flex; gap: 0.75rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
        .btn {
            display: inline-flex; align-items: center; gap: 0.4rem;
            padding: 0.65rem 1.5rem;
            border-radius: 0.75rem;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all .15s ease;
        }
        .btn-primary { background: #3b82f6; color: #fff; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }
        .btn-ghost {
            background: transparent;
            color: #64748b;
            border: 1.5px solid #e2e8f0;
        }
        html.dark .btn-ghost { border-color: #334155; color: #94a3b8; }
        .btn-ghost:hover { background: #f8fafc; }
        html.dark .btn-ghost:hover { background: #334155; }

        .divider { margin: 1.75rem 0; border: none; border-top: 1px solid #e2e8f0; }
        html.dark .divider { border-color: #334155; }
    </style>
</head>
<body>
    <div class="card">
        <div class="big-number">404</div>
        <div class="code">Page Not Found</div>
        <h1>Oops! Nothing Here</h1>
        <p>The page you are looking for does not exist or may have been moved.</p>
        <hr class="divider">
        <div class="actions">
            <a href="{{ url('/dashboard') }}" class="btn btn-primary">Return to Dashboard</a>
            <a href="{{ url('/contracts') }}" class="btn btn-ghost">Browse Contracts</a>
        </div>
    </div>
</body>
</html>
