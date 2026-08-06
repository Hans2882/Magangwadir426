<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'SIMKERMA - Data Pelaporan Case Study' }}</title>
        
        <!-- Google Fonts: Outfit -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <!-- Icons -->
        <script src="https://unpkg.com/lucide@latest"></script>

        @livewireStyles
        
        <style>
            :root {
                --primary: #2563eb;
                --primary-hover: #1d4ed8;
                --bg-gradient-start: #f8fafc;
                --bg-gradient-end: #e2e8f0;
                --text-main: #0f172a;
                --text-muted: #64748b;
                --card-bg: rgba(255, 255, 255, 0.85);
                --card-border: rgba(255, 255, 255, 0.5);
            }
            
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                font-family: 'Outfit', sans-serif;
            }
            
            body {
                background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
                color: var(--text-main);
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            
            header {
                background: var(--card-bg);
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border-bottom: 1px solid var(--card-border);
                padding: 1rem 2rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                position: sticky;
                top: 0;
                z-index: 50;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
            
            header h1 {
                font-size: 1.5rem;
                font-weight: 700;
                color: black;
                letter-spacing: -0.5px;
            }
            
            .login-btn {
                text-decoration: none;
                color: var(--primary);
                font-weight: 500;
                padding: 0.5rem 1rem;
                border-radius: 8px;
                border: 1px solid var(--primary);
                transition: all 0.2s ease;
            }
            
            .login-btn:hover {
                background: var(--primary);
                color: white;
            }
            
            main {
                flex: 1;
                width: 100%;
                max-width: 1200px;
                margin: 0 auto;
                padding: 3rem 1.5rem;
            }
            
            footer {
                text-align: center;
                padding: 2rem;
                color: var(--text-muted);
                font-size: 0.875rem;
            }
        </style>
    </head>
    <body>
        <header>
            <h1>SIMKERMA</h1>
            @if(auth()->check())
                <a href="{{ auth()->user()?->canAccessPanel(Filament\Facades\Filament::getPanel('admin')) ? url('/admin') : url('/user') }}" class="login-btn">Dashboard</a>
            @else
                <a href="{{ url('/user/login') }}" class="login-btn">Login</a>
            @endif
        </header>

        <main>
            {{ $slot }}
        </main>

        {{-- <footer>
            &copy; {{ date('Y') }} SIMKERMA. Hak Cipta Dilindungi.
        </footer> --}}

        @livewireScripts
        <script>
            lucide.createIcons();
        </script>
    </body>
</html>
