<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Nasaari Store – Your trusted online destination for premium modest fashion, hijabs, and accessories.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nasaari Store – Hijabs | Modesty & More')</title>
    @if(file_exists(public_path('images/logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    @endif
    @stack('meta')

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400;0,6..96,500;0,6..96,600;1,6..96,400;1,6..96,500&family=Parisienne&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Display currency for prices formatted in JavaScript (GHS stays the source of truth) --}}
    <script>window.NasaariCurrency = @json(app(\App\Services\CurrencyService::class)->jsConfig());</script>
</head>
<body class="font-sans antialiased bg-brand-light text-gray-800 min-h-screen flex flex-col">

    {{-- ═══════════════════════════════════════════════════════
         NAVIGATION – Glassmorphism sticky header
    ═══════════════════════════════════════════════════════ --}}
    <nav id="mainNav" class="sticky top-0 z-50 transition-all duration-500 bg-white/80 backdrop-blur-xl border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-[72px]">

                {{-- Logo --}}
                <a href="/" class="flex items-end gap-1.5 group" id="nav-logo" aria-label="Nasaari Store home">
                    <span class="font-script text-[2.1rem] leading-none text-brand-dark group-hover:text-brand-700 transition-colors duration-300">Nasaari</span>
                    <span class="mb-0.5 text-[11px] font-medium tracking-[0.2em] uppercase text-brand-600">Store</span>
                </a>

                {{-- Desktop Navigation Links --}}
                <div class="hidden lg:flex items-center gap-1">
                    <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('home', 'products.*') ? 'text-brand-600 bg-brand-50' : 'text-gray-600' }} hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-shop">
                        Shop
                    </a>
                    <a href="{{ route('story') }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('story') ? 'text-brand-600 bg-brand-50' : 'text-gray-600' }} hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-story">
                        Our story
                    </a>
                    <a href="{{ route('blog.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('blog.*') ? 'text-brand-600 bg-brand-50' : 'text-gray-600' }} hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-blog">
                        Blog
                    </a>
                    <a href="{{ route('pages.contact') }}" class="px-4 py-2 rounded-lg text-sm font-medium {{ request()->routeIs('pages.contact') ? 'text-brand-600 bg-brand-50' : 'text-gray-600' }} hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-contact">
                        Contact
                    </a>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-accent-600 bg-accent-50 hover:bg-accent-100 transition-all duration-200" id="nav-admin">
                                <i class="fas fa-shield-halved mr-1.5 text-xs"></i>Admin
                            </a>
                        @endif
                    @endauth
                </div>

                {{-- Right side actions --}}
                <div class="flex items-center gap-2 sm:gap-3">

                    {{-- Display currency --}}
                    <x-shop.currency-switcher id="nav-currency" class="hidden sm:flex" />

                    {{-- Cart Button --}}
                    <a href="{{ route('cart.index') }}" class="relative p-2.5 rounded-xl text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-cart">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        @php $cartCount = count(session('cart', [])); @endphp
                        <span id="nav-cart-count" class="bg-accent-500 absolute -top-0.5 -right-0.5 min-w-[20px] h-5 px-1 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-lg shadow-accent-500/30 {{ $cartCount ? '' : 'hidden' }}" aria-live="polite">{{ $cartCount ?: '' }}</span>
                    </a>

                    {{-- User Menu --}}
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button onclick="toggleUserMenu()" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-user-menu">
                                <span class="bg-accent-500 w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold shadow-md">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden sm:inline">{{ auth()->user()->name }}</span>
                                <i class="fas fa-chevron-down text-[10px] text-gray-400"></i>
                            </button>
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-2xl shadow-gray-200/50 border border-gray-100 py-2 z-50 animate-fade-in">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                @if(!auth()->user()->isAdmin())
                                    <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-colors" id="nav-my-orders">
                                        <i class="fas fa-box w-4 text-center text-gray-400"></i>My Orders
                                    </a>
                                @endif
                                <div class="border-t border-gray-100 mt-1 pt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-500 hover:text-red-600 hover:bg-red-50 transition-colors" id="nav-logout">
                                            <i class="fas fa-arrow-right-from-bracket w-4 text-center"></i>Sign Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline-flex px-4 py-2 rounded-xl text-sm font-medium whitespace-nowrap text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-login">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="bg-accent-500 hover:bg-accent-600 hidden sm:inline-flex px-5 py-2.5 rounded-xl text-sm font-semibold whitespace-nowrap text-white shadow-lg shadow-accent-500/25 hover:shadow-accent-500/40 transition-all duration-300" id="nav-register">
                            Get Started
                        </a>
                    @endauth

                    {{-- Mobile Menu Toggle --}}
                    <button onclick="toggleMobileMenu()" class="lg:hidden p-2.5 rounded-xl text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-mobile-toggle">
                        <i class="fas fa-bars text-lg" id="mobileMenuIcon"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Menu --}}
        <div id="mobileMenu" class="lg:hidden hidden border-t border-gray-100 bg-white/95 backdrop-blur-xl">
            <div class="max-w-7xl mx-auto px-4 py-4 space-y-1">
                <div class="flex items-center justify-between px-4 py-2 sm:hidden">
                    <span class="text-sm font-medium text-gray-500">Show prices in</span>
                    <x-shop.currency-switcher id="mobile-currency" />
                </div>
                <a href="{{ route('story') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:text-brand-600 hover:bg-brand-50 transition-all font-medium">
                    <i class="fas fa-feather w-5 text-center text-gray-400"></i>Our story
                </a>
                <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:text-brand-600 hover:bg-brand-50 transition-all font-medium">
                    <i class="fas fa-th-large w-5 text-center text-gray-400"></i>Shop
                </a>
                <a href="{{ route('blog.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:text-brand-600 hover:bg-brand-50 transition-all font-medium">
                    <i class="fas fa-feather-pointed w-5 text-center text-gray-400"></i>Blog
                </a>
                <a href="{{ route('pages.contact') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:text-brand-600 hover:bg-brand-50 transition-all font-medium">
                    <i class="fas fa-envelope w-5 text-center text-gray-400"></i>Contact
                </a>
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-brand-600 bg-brand-50 font-medium">
                            <i class="fas fa-shield-halved w-5 text-center"></i>Admin Panel
                        </a>
                    @endif
                    @if(!auth()->user()->isAdmin())
                        <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:text-brand-600 hover:bg-brand-50 transition-all font-medium">
                            <i class="fas fa-box w-5 text-center text-gray-400"></i>My Orders
                        </a>
                    @endif
                @else
                    {{-- On phones the sign-in links live here to keep the top bar uncluttered --}}
                    <div class="sm:hidden grid grid-cols-2 gap-3 pt-3 mt-2 border-t border-gray-100">
                        <a href="{{ route('login') }}" class="btn-secondary !py-2.5">Sign In</a>
                        <a href="{{ route('register') }}" class="btn-brand !py-2.5">Get Started</a>
                    </div>
                @endauth
            </div>
        </div>
    </nav>

    {{-- ═══════════════════════════════════════════════════════
         TOAST NOTIFICATIONS
    ═══════════════════════════════════════════════════════ --}}
    <div id="toastContainer" class="fixed top-20 right-4 z-[60] space-y-3 max-w-sm w-full pointer-events-none">
        @if($errors->any())
            <div class="toast-item pointer-events-auto flex items-start gap-3 px-5 py-4 rounded-2xl bg-white border border-red-100 shadow-2xl shadow-red-500/10 animate-slide-in">
                <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <i class="fas fa-exclamation-circle text-red-500 text-sm"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-red-800">Something went wrong</p>
                    @foreach($errors->all() as $error)
                        <p class="text-xs text-red-600 mt-0.5">{{ $error }}</p>
                    @endforeach
                </div>
                <button onclick="this.closest('.toast-item').remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        @endif

        @if(session('success'))
            <div class="toast-item pointer-events-auto flex items-start gap-3 px-5 py-4 rounded-2xl bg-white border border-emerald-100 shadow-2xl shadow-emerald-500/10 animate-slide-in">
                <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                    <i class="fas fa-check-circle text-emerald-500 text-sm"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-emerald-800">Success</p>
                    <p class="text-xs text-emerald-600 mt-0.5">{{ session('success') }}</p>
                </div>
                <button onclick="this.closest('.toast-item').remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="toast-item pointer-events-auto flex items-start gap-3 px-5 py-4 rounded-2xl bg-white border border-red-100 shadow-2xl shadow-red-500/10 animate-slide-in">
                <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-red-500 text-sm"></i>
                </span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-red-800">Error</p>
                    <p class="text-xs text-red-600 mt-0.5">{{ session('error') }}</p>
                </div>
                <button onclick="this.closest('.toast-item').remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════
         MAIN CONTENT
    ═══════════════════════════════════════════════════════ --}}
    <main class="flex-1">
        @yield('content')
    </main>

    {{-- ═══════════════════════════════════════════════════════
         FOOTER – Full on every page; the home page keeps only
         the trust badges and copyright (it ends with its own story)
    ═══════════════════════════════════════════════════════ --}}
    @php $fullFooter = ! request()->routeIs('story'); @endphp
    <footer class="bg-brand-dark text-cream-200 mt-auto">
        {{-- Top wave decoration --}}
        <div class="h-px bg-gradient-to-r from-transparent via-accent-500/40 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 {{ $fullFooter ? 'pt-16' : 'pt-6' }} pb-8">
            @if($fullFooter)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                    {{-- Brand Column --}}
                    <div class="lg:col-span-1">
                        <a href="/" class="inline-block mb-5" aria-label="Nasaari Store home">
                            <span class="flex items-end gap-1.5">
                                <span class="font-script text-5xl leading-none text-brand-ivory">Nasaari</span>
                                <span class="mb-1 text-xs tracking-[0.2em] uppercase text-brand-ivory/80">Store</span>
                            </span>
                            <span class="block mt-2 font-serif text-sm text-brand-ivory/70">Hijabs | modesty &amp; more</span>
                        </a>
                        <p class="text-sm text-cream-200/70 leading-relaxed mb-6">
                            Your trusted destination for premium modest fashion. Curated collections that blend elegance with modesty.
                        </p>
                        <x-social-links />
                    </div>

                    @php
                        $footerLinks = [
                            'Shop' => [
                                ['All Products', route('products.index')],
                                ['Best Deals', route('products.index') . '?sort=price_low'],
                                ['Top Rated', route('products.index') . '?sort=rating'],
                                ['New Arrivals', route('products.index') . '?sort=latest'],
                                ['Blog & Tips', route('blog.index')],
                            ],
                            'Customer' => [
                                ['Track Order', route('orders.track')],
                                ['Returns & Exchanges', route('pages.returns-exchanges')],
                                ['Size Guide', route('pages.size-guide')],
                                ['Contact Support', route('pages.contact')],
                            ],
                            'Legal' => [
                                ['Privacy Policy', route('pages.privacy-policy')],
                                ['Terms of Service', '#'],
                                ['Shipping Policy', route('pages.shipping-policy')],
                                ['Refund Policy', route('pages.refund-policy')],
                            ],
                        ];
                    @endphp
                    @foreach($footerLinks as $heading => $links)
                        <div>
                            <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-5">{{ $heading }}</h4>
                            <ul class="space-y-3">
                                @foreach($links as [$label, $href])
                                    <li><a href="{{ $href }}" class="text-sm text-cream-200/70 hover:text-accent-300 transition-colors duration-200">{{ $label }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Trust badges --}}
            <div class="flex flex-wrap items-center justify-center gap-6 py-8 mb-4 {{ $fullFooter ? 'border-t border-white/10' : '' }}">
                <div class="flex items-center gap-2 text-xs text-cream-200/80">
                    <i class="fas fa-lock text-accent-400"></i>
                    <span>Secure Payments</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-cream-200/80">
                    <i class="fas fa-truck text-accent-400"></i>
                    <span>Fast Delivery</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-cream-200/80">
                    <i class="fas fa-rotate-left text-accent-400"></i>
                    <span>Easy Returns</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-cream-200/80">
                    <i class="fas fa-headset text-accent-400"></i>
                    <span>24/7 Support</span>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="text-center">
                <p class="text-xs text-cream-200/80">&copy; {{ date('Y') }} Nasaari Store. All rights reserved. Crafted with <i class="fas fa-heart text-accent-500 text-[10px]"></i> for modest fashion.</p>
            </div>
        </div>
    </footer>

    {{-- ═══════════════════════════════════════════════════════
         SCRIPTS
    ═══════════════════════════════════════════════════════ --}}
    <script>
        // User dropdown toggle
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Mobile menu toggle
        function toggleMobileMenu() {
            const menu = document.getElementById('mobileMenu');
            const icon = document.getElementById('mobileMenuIcon');
            menu.classList.toggle('hidden');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        }

        // Close dropdown on outside click
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('userDropdown');
            const btn = document.getElementById('nav-user-menu');
            if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Auto-dismiss toasts
        document.querySelectorAll('.toast-item').forEach(toast => {
            setTimeout(() => {
                toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 400);
            }, 5000);
        });

        // Navbar scroll effect
        let lastScroll = 0;
        window.addEventListener('scroll', () => {
            const nav = document.getElementById('mainNav');
            const scrollY = window.scrollY;
            if (scrollY > 100) {
                nav.classList.add('shadow-md');
                nav.classList.remove('shadow-sm');
            } else {
                nav.classList.remove('shadow-md');
                nav.classList.add('shadow-sm');
            }
            lastScroll = scrollY;
        });
    </script>

    @stack('scripts')
</body>
</html>
