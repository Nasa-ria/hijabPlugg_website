<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('page_title', 'Admin') – Nasaari Admin</title>

    {{-- Fonts and icons are self-hosted in app.css (resources/css/fonts.css) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="font-sans antialiased bg-cream-100 text-gray-800">
    @php
        $nav = [
            'Overview' => [
                ['Dashboard', 'admin.dashboard', 'admin.dashboard', 'fa-chart-line'],
                ['Orders', 'admin.orders.index', 'admin.orders.*', 'fa-bag-shopping'],
                ['Messages', 'admin.messages.index', 'admin.messages.*', 'fa-envelope'],
            ],
            'Catalogue' => [
                ['Products', 'admin.products.index', 'admin.products.*', 'fa-box'],
                ['Categories', 'admin.categories.index', 'admin.categories.*', 'fa-list'],
                ['Reviews', 'admin.reviews.index', 'admin.reviews.*', 'fa-star'],
            ],
            'Inventory' => [
                ['Stock levels', 'admin.inventory.index', 'admin.inventory.index', 'fa-boxes-stacked'],
                ['Stock history', 'admin.inventory.movements', 'admin.inventory.movements', 'fa-clock-rotate-left'],
                ['Returns', 'admin.inventory.returns.index', 'admin.inventory.returns.*', 'fa-rotate-left'],
                ['Excel import / export', 'admin.inventory.import', 'admin.inventory.import*', 'fa-file-excel'],
            ],
            'Marketing' => [
                ['Newsletters', 'admin.newsletters.index', 'admin.newsletters.*', 'fa-envelope-open-text'],
                ['Blog posts', 'admin.posts.index', 'admin.posts.*', 'fa-feather-pointed'],
            ],
            'Settings' => [
                ['Shipping', 'admin.shipping.index', 'admin.shipping.*', 'fa-truck-fast'],
                ['Team', 'admin.team.index', 'admin.team.*', 'fa-user-shield'],
                ['My password', 'admin.password.edit', 'admin.password.*', 'fa-key'],
            ],
        ];

        // Only show the pages this admin may use (area = the part after "admin." in the route name)
        $me = auth()->user();
        $nav = collect($nav)->map(fn ($links) => array_values(array_filter($links, function ($link) use ($me) {
            $area = explode('.', $link[1])[1];

            return $area === 'password' || $me->canAccessAdmin($area);
        })))->filter()->all();
        $homeRoute = $me->adminHomeRoute() ?? 'admin.password.edit';
    @endphp

    <div class="min-h-screen lg:flex">
        {{-- Sidebar: fixed on desktop, slides in on phones --}}
        <div id="adminSidebarBackdrop" class="fixed inset-0 z-30 bg-brand-dark/40 backdrop-blur-sm hidden lg:hidden" onclick="toggleAdminNav(false)"></div>
        <aside id="adminSidebar" class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen bg-brand-dark text-cream-100 flex flex-col transition-transform duration-300 ease-out" aria-label="Admin navigation">
            <div class="flex items-center justify-between px-6 py-6">
                <a href="{{ route($homeRoute) }}" class="flex items-end gap-1.5">
                    <span class="font-script text-4xl leading-none text-brand-ivory">Nasaari</span>
                    <span class="mb-0.5 text-[10px] tracking-[0.25em] uppercase text-accent-300">Admin</span>
                </a>
                <button type="button" class="lg:hidden p-2 text-cream-200" onclick="toggleAdminNav(false)" aria-label="Close menu"><i class="fas fa-times"></i></button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 pb-6 space-y-6">
                @foreach($nav as $group => $links)
                    <div>
                        <p class="px-3 mb-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-cream-200/50">{{ $group }}</p>
                        @foreach($links as [$label, $route, $pattern, $icon])
                            @php $active = request()->routeIs($pattern); @endphp
                            <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors {{ $active ? 'bg-white/10 text-white font-semibold' : 'text-cream-200/80 hover:bg-white/5 hover:text-white' }}" @if($active) aria-current="page" @endif>
                                <i class="fas {{ $icon }} w-4 text-center {{ $active ? 'text-accent-300' : 'text-cream-200/50' }}"></i>{{ $label }}
                                @if($route === 'admin.inventory.index' && ($lowStock = \App\Models\Product::where('is_available', true)->where('stock_quantity', '>', 0)->whereRaw('stock_quantity <= COALESCE(low_stock_threshold, ?)', [config('shop.inventory.low_stock_threshold')])->count()))
                                    <span class="ml-auto rounded-full bg-amber-400 px-2 py-0.5 text-[11px] font-bold text-brand-900" title="Products low on stock">{{ $lowStock }}<span class="sr-only"> low on stock</span></span>
                                @endif
                                @if($route === 'admin.messages.index' && ($unread = \App\Models\ContactMessage::whereNull('read_at')->count()))
                                    <span class="ml-auto rounded-full bg-accent-500 px-2 py-0.5 text-[11px] font-bold text-white">{{ $unread }}<span class="sr-only"> unread</span></span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="border-t border-white/10 p-4 space-y-1">
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-cream-200/80 hover:bg-white/5 hover:text-white">
                    <i class="fas fa-arrow-up-right-from-square w-4 text-center text-cream-200/50"></i>View store
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-cream-200/80 hover:bg-white/5 hover:text-white">
                        <i class="fas fa-arrow-right-from-bracket w-4 text-center text-cream-200/50"></i>Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="flex-1 min-w-0">
            <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-cream-300 bg-cream-100/90 backdrop-blur px-4 py-4 sm:px-8">
                <button type="button" class="lg:hidden -ml-1 p-2 text-brand-900" onclick="toggleAdminNav(true)" aria-label="Open menu" aria-controls="adminSidebar"><i class="fas fa-bars text-lg"></i></button>
                <h1 class="font-serif text-2xl sm:text-3xl text-brand-900 truncate">@yield('page_title', 'Dashboard')</h1>
                <span class="ml-auto hidden sm:flex items-center gap-2 text-sm text-gray-500">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-accent-500 text-xs font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    {{ auth()->user()->name }}
                </span>
            </header>

            <main class="px-4 py-6 sm:px-8 sm:py-8">
                @if($errors->any())
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif
                @if(session('success'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-800" role="status">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700" role="alert">{{ session('error') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script>
        function toggleAdminNav(open) {
            document.getElementById('adminSidebar').classList.toggle('-translate-x-full', !open);
            document.getElementById('adminSidebarBackdrop').classList.toggle('hidden', !open);
        }
    </script>
    @stack('scripts')
</body>
</html>
