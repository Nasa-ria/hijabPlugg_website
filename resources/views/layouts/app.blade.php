<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Hijabis Plugg – Your trusted online destination for premium modest fashion, hijabs, and accessories.">
    <title>@yield('title', 'Hijabis Plugg – Premium Modest Fashion')</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-brand-light text-gray-800 min-h-screen flex flex-col">

    {{-- ═══════════════════════════════════════════════════════
         NAVIGATION – Glassmorphism sticky header
    ═══════════════════════════════════════════════════════ --}}
    <nav id="mainNav" class="sticky top-0 z-50 transition-all duration-500 bg-white/80 backdrop-blur-xl border-b border-gray-100 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-[72px]">

                {{-- Logo --}}
                <a href="/" class="flex items-center gap-2.5 group" id="nav-logo">
                    <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-lg shadow-brand-500/25 group-hover:shadow-brand-500/40 transition-shadow duration-300">
                        <i class="fas fa-leaf text-white text-sm"></i>
                    </span>
                    <span class="text-xl font-bold bg-gradient-to-r from-brand-700 to-brand-500 bg-clip-text text-transparent">
                        Hijabis Plugg
                    </span>
                </a>

                {{-- Desktop Navigation Links --}}
                <div class="hidden lg:flex items-center gap-1">
                    <a href="/" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-home">
                        Home
                    </a>
                    <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-shop">
                        Shop
                    </a>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-brand-600 bg-brand-50 hover:bg-brand-100 transition-all duration-200" id="nav-admin">
                                <i class="fas fa-shield-halved mr-1.5 text-xs"></i>Admin
                            </a>
                        @endif
                    @endauth
                </div>

                {{-- Right side actions --}}
                <div class="flex items-center gap-2 sm:gap-3">

                    {{-- Cart Button --}}
                    <a href="{{ route('cart.index') }}" class="relative p-2.5 rounded-xl text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-cart">
                        <i class="fas fa-shopping-bag text-lg"></i>
                        @if(session('cart') && count(session('cart')) > 0)
                            <span class="absolute -top-0.5 -right-0.5 min-w-[20px] h-5 px-1 bg-gradient-to-r from-brand-500 to-brand-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-lg shadow-brand-500/30 animate-pulse">
                                {{ count(session('cart')) }}
                            </span>
                        @endif
                    </a>

                    {{-- User Menu --}}
                    @auth
                        <div class="relative" x-data="{ open: false }">
                            <button onclick="toggleUserMenu()" class="flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-user-menu">
                                <span class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-xs font-bold shadow-md">
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
                        <a href="{{ route('login') }}" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 hover:text-brand-600 hover:bg-brand-50 transition-all duration-200" id="nav-login">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 transition-all duration-300" id="nav-register">
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
                <a href="/" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:text-brand-600 hover:bg-brand-50 transition-all font-medium">
                    <i class="fas fa-home w-5 text-center text-gray-400"></i>Home
                </a>
                <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-700 hover:text-brand-600 hover:bg-brand-50 transition-all font-medium">
                    <i class="fas fa-th-large w-5 text-center text-gray-400"></i>Shop
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
         FOOTER – Elegant dark footer
    ═══════════════════════════════════════════════════════ --}}
    <footer class="bg-brand-dark text-gray-300 mt-auto">
        {{-- Top wave decoration --}}
        <div class="h-px bg-gradient-to-r from-transparent via-brand-500/40 to-transparent"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                {{-- Brand Column --}}
                <div class="lg:col-span-1">
                    <a href="/" class="flex items-center gap-2.5 mb-5 group">
                        <span class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 flex items-center justify-center shadow-lg shadow-brand-500/25">
                            <i class="fas fa-leaf text-white text-sm"></i>
                        </span>
                        <span class="text-lg font-bold text-white">Hijabis Plugg</span>
                    </a>
                    <p class="text-sm text-gray-400 leading-relaxed mb-6">
                        Your trusted destination for premium modest fashion. Curated collections that blend elegance with modesty.
                    </p>
                    <div class="flex gap-3">
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-brand-500 flex items-center justify-center text-gray-400 hover:text-white transition-all duration-300" aria-label="Facebook">
                            <i class="fab fa-facebook-f text-sm"></i>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-brand-500 flex items-center justify-center text-gray-400 hover:text-white transition-all duration-300" aria-label="Instagram">
                            <i class="fab fa-instagram text-sm"></i>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-brand-500 flex items-center justify-center text-gray-400 hover:text-white transition-all duration-300" aria-label="Twitter">
                            <i class="fab fa-twitter text-sm"></i>
                        </a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-white/5 hover:bg-brand-500 flex items-center justify-center text-gray-400 hover:text-white transition-all duration-300" aria-label="TikTok">
                            <i class="fab fa-tiktok text-sm"></i>
                        </a>
                    </div>
                </div>

                {{-- Shop Links --}}
                <div>
                    <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-5">Shop</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('products.index') }}" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">All Products</a></li>
                        <li><a href="{{ route('products.index') }}?sort=price_low" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Best Deals</a></li>
                        <li><a href="{{ route('products.index') }}?sort=rating" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Top Rated</a></li>
                        <li><a href="{{ route('products.index') }}?sort=latest" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">New Arrivals</a></li>
                    </ul>
                </div>

                {{-- Customer Links --}}
                <div>
                    <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-5">Customer</h4>
                    <ul class="space-y-3">
                        <li><a href="#" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Track Order</a></li>
                        <li><a href="{{ route('pages.returns-exchanges') }}" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Returns & Exchanges</a></li>
                        <li><a href="{{ route('pages.size-guide') }}" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Size Guide</a></li>
                        <li><a href="{{ route('pages.contact') }}" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Contact Support</a></li>
                    </ul>
                </div>

                {{-- Legal Links --}}
                <div>
                    <h4 class="text-sm font-semibold text-white uppercase tracking-wider mb-5">Legal</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('pages.privacy-policy') }}" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Privacy Policy</a></li>
                        <li><a href="#" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Terms of Service</a></li>
                        <li><a href="{{ route('pages.shipping-policy') }}" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Shipping Policy</a></li>
                        <li><a href="{{ route('pages.refund-policy') }}" class="text-sm text-gray-400 hover:text-brand-400 transition-colors duration-200">Refund Policy</a></li>
                    </ul>
                </div>
            </div>

            {{-- Trust badges --}}
            <div class="flex flex-wrap items-center justify-center gap-6 py-8 border-t border-white/5 mb-8">
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i class="fas fa-lock text-brand-400"></i>
                    <span>Secure Payments</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i class="fas fa-truck text-brand-400"></i>
                    <span>Fast Delivery</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i class="fas fa-rotate-left text-brand-400"></i>
                    <span>Easy Returns</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-500">
                    <i class="fas fa-headset text-brand-400"></i>
                    <span>24/7 Support</span>
                </div>
            </div>

            {{-- Copyright --}}
            <div class="text-center">
                <p class="text-xs text-gray-500">&copy; {{ date('Y') }} Hijabis Plugg. All rights reserved. Crafted with <i class="fas fa-heart text-brand-500 text-[10px]"></i> for modest fashion.</p>
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
