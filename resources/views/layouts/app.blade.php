<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', \App\Support\Brand::name()) - Dynamic ELO Rankings</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: 'var(--color-primary)',
                        secondary: 'var(--color-secondary)',
                        ink: 'var(--color-ink)',
                        surface: 'var(--color-surface)',
                    }
                }
            }
        }
    </script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            --color-bg: #ffffff;
            --color-ink: #0f172a;
            --color-muted: #64748b;
            --color-surface: #f8fafc;
            --color-border: #e2e8f0;
            --color-primary: {{ $theme->primary_color ?? '#DC143C' }};
            --color-secondary: {{ $theme->secondary_color ?? '#003893' }};
        }

        body {
            font-family: 'Inter', sans-serif;
            -webkit-tap-highlight-color: transparent;
            background: var(--color-bg);
            color: var(--color-ink);
        }
        h1, h2, h3, .font-heading {
            font-family: 'Outfit', sans-serif;
        }

        .surface-card {
            background: var(--color-bg);
            border: 1px solid var(--color-border);
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        }
        .dock-bar {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            border-top: 1px solid var(--color-border);
        }

        .bg-primary { background-color: var(--color-primary); }
        .bg-secondary { background-color: var(--color-secondary); }
        .bg-primary-soft { background-color: color-mix(in srgb, var(--color-primary) 12%, white); }
        .bg-secondary-soft { background-color: color-mix(in srgb, var(--color-secondary) 12%, white); }
        .text-primary { color: var(--color-primary); }
        .text-secondary { color: var(--color-secondary); }
        .text-muted { color: var(--color-muted); }
        .border-primary { border-color: var(--color-primary); }
        .border-secondary { border-color: var(--color-secondary); }
        .border-theme { border-color: var(--color-border); }
        .ring-primary-soft { box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 35%, transparent); }
        .ring-secondary-soft { box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-secondary) 35%, transparent); }
        .shadow-primary { box-shadow: 0 8px 20px -8px color-mix(in srgb, var(--color-primary) 45%, transparent); }
        .shadow-secondary { box-shadow: 0 8px 20px -8px color-mix(in srgb, var(--color-secondary) 45%, transparent); }

        .pill-active {
            background-color: var(--color-primary);
            color: #fff;
            border-color: var(--color-primary);
            box-shadow: 0 8px 20px -8px color-mix(in srgb, var(--color-primary) 45%, transparent);
        }
        .pill-idle {
            background-color: var(--color-surface);
            color: var(--color-ink);
            border-color: var(--color-border);
        }
        .pill-idle:hover {
            border-color: color-mix(in srgb, var(--color-secondary) 40%, var(--color-border));
            color: var(--color-secondary);
        }

        .nav-link-active {
            background-color: color-mix(in srgb, var(--color-secondary) 10%, white);
            color: var(--color-secondary);
        }
        .nav-link-idle {
            color: var(--color-muted);
        }
        .nav-link-idle:hover {
            color: var(--color-ink);
            background-color: var(--color-surface);
        }

        .app-shell-bg {
            background:
                radial-gradient(ellipse 70% 40% at 50% -10%, color-mix(in srgb, var(--color-primary) 8%, white), transparent),
                radial-gradient(ellipse 50% 30% at 100% 0%, color-mix(in srgb, var(--color-secondary) 6%, white), transparent),
                var(--color-bg);
        }
    </style>
</head>
<body class="h-full antialiased selection:bg-primary selection:text-white app-shell-bg">

    <div class="min-h-screen w-full flex justify-center">
        <div class="w-full max-w-md lg:max-w-6xl min-h-screen bg-white flex flex-col relative pb-24 lg:pb-10 shadow-sm border-x border-theme lg:border-slate-200/80">
            
            <!-- App Top Bar Header -->
            <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md px-5 py-4 lg:px-8 border-b border-theme flex items-center justify-between gap-4">
                <a href="{{ route('leaderboard') }}" class="flex items-center gap-3 min-w-0 hover:opacity-90 transition">
                    @if($logoUrl = \App\Support\Brand::logoUrl())
                        <img src="{{ $logoUrl }}" alt="{{ \App\Support\Brand::name() }}" class="w-10 h-10 shrink-0 rounded-xl object-cover shadow-sm">
                    @else
                        <div class="w-10 h-10 shrink-0 rounded-xl p-0.5 shadow-sm" style="background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));">
                            <div class="w-full h-full bg-white rounded-[10px] flex items-center justify-center font-heading font-black text-xl text-primary">
                                TT
                            </div>
                        </div>
                    @endif
                    <div class="min-w-0">
                        <h1 class="font-heading font-bold text-lg leading-none tracking-tight text-ink truncate">{{ \App\Support\Brand::name() }}</h1>
                        <p class="text-xs text-muted font-medium mt-0.5">Competitive ELO League</p>
                    </div>
                </a>

                {{-- Desktop horizontal nav --}}
                <nav class="hidden lg:flex items-center gap-1 flex-1 justify-center">
                    <a href="{{ route('leaderboard') }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('leaderboard') ? 'nav-link-active' : 'nav-link-idle' }}">
                        Rankings
                    </a>
                    <a href="{{ route('matches') }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('matches') ? 'nav-link-active' : 'nav-link-idle' }}">
                        Matches
                    </a>
                    <a href="{{ route('head-to-head') }}" class="px-4 py-2 rounded-xl text-sm font-medium transition {{ request()->routeIs('head-to-head') ? 'nav-link-active' : 'nav-link-idle' }}">
                        Head-to-Head
                    </a>
                    <a href="/admin/match-records/create" class="px-4 py-2 rounded-xl text-sm font-medium text-primary hover:bg-primary-soft transition">
                        + Score Match
                    </a>
                </nav>
                
                <a href="/admin/login" class="shrink-0 px-3 py-1.5 lg:px-4 lg:py-2 rounded-lg text-xs lg:text-sm font-semibold bg-secondary-soft text-secondary border border-secondary hover:bg-secondary hover:text-white transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Portal
                </a>
            </header>

            <!-- Main Scrollable Content Area -->
            <main class="flex-1 px-4 py-5 lg:px-8 lg:py-8 w-full bg-white">
                @yield('content')
            </main>

            <!-- Mobile App Fixed Bottom Dock -->
            <nav class="fixed bottom-0 z-40 w-full max-w-md dock-bar px-3 py-2 flex items-center justify-around lg:hidden left-1/2 -translate-x-1/2">
                <a href="{{ route('leaderboard') }}" class="flex flex-col items-center gap-1 p-2 rounded-xl text-xs font-medium transition {{ request()->routeIs('leaderboard') ? 'text-secondary font-semibold' : 'text-muted hover:text-ink' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Rankings</span>
                </a>

                <a href="{{ route('matches') }}" class="flex flex-col items-center gap-1 p-2 rounded-xl text-xs font-medium transition {{ request()->routeIs('matches') ? 'text-secondary font-semibold' : 'text-muted hover:text-ink' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Matches</span>
                </a>

                <a href="{{ route('head-to-head') }}" class="flex flex-col items-center gap-1 p-2 rounded-xl text-xs font-medium transition {{ request()->routeIs('head-to-head') ? 'text-secondary font-semibold' : 'text-muted hover:text-ink' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                    <span>H2H</span>
                </a>

                <a href="/admin/match-records/create" class="flex flex-col items-center gap-1 p-2 rounded-xl text-xs font-medium text-primary hover:opacity-80 transition">
                    <div class="w-7 h-7 rounded-lg bg-primary-soft text-primary flex items-center justify-center font-bold">
                        +
                    </div>
                    <span>Score</span>
                </a>
            </nav>

        </div>
    </div>

</body>
</html>
