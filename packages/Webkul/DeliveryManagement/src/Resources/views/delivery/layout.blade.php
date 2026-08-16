<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'بوابة التوصيل | Hayest Delivery')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #16a34a;
            --warning: #d97706;
            --danger: #dc2626;
            --bg-main: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', system-ui, -apple-system, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #ffffff;
            padding: 1rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.15rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .badge-agent {
            background: rgba(37, 99, 235, 0.2);
            color: #93c5fd;
            padding: 0.25rem 0.6rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .container {
            flex: 1;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            padding: 1rem;
        }

        .card {
            background: var(--bg-card);
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .card:active {
            transform: scale(0.99);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.65rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .badge-ready { background: #e0f2fe; color: #0369a1; }
        .badge-assigned { background: #fef3c7; color: #92400e; }
        .badge-picked { background: #e0e7ff; color: #3730a3; }
        .badge-out { background: #fef08a; color: #854d0e; }
        .badge-delivered { background: #dcfce7; color: #166534; }
        .badge-failed { background: #fee2e2; color: #991b1b; }
        .badge-retry { background: #ffedd5; color: #9a3412; }
        .badge-returned { background: #f1f5f9; color: #475569; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.95rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s ease, transform 0.1s ease;
            gap: 0.5rem;
        }

        .btn-primary { background: var(--primary); color: #ffffff; }
        .btn-primary:active { background: var(--primary-dark); }
        .btn-success { background: var(--success); color: #ffffff; }
        .btn-danger { background: var(--danger); color: #ffffff; }
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); }

        .nav-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }

        .nav-tab {
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-muted);
            background: #ffffff;
            border: 1px solid var(--border-color);
            white-space: nowrap;
        }

        .nav-tab.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }
    </style>
    @yield('styles')
</head>
<body>
    <header class="header">
        <h1>
            <span>🚀</span>
            <span>بوابة التوصيل</span>
        </h1>
        <div class="badge-agent">
            {{ $user->name ?? 'مندوب هايست' }}
        </div>
    </header>

    <main class="container">
        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
