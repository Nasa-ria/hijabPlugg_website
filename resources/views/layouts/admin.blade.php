<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - @yield('title', 'Hijabis Plugg')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --primary: #8b5cf6;
            --primary-dark: #7c3aed;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 text-white flex flex-col sticky top-0">
            <div class="p-6 border-b border-gray-700">
                <h2 class="text-2xl font-bold flex items-center gap-2">
                    <i class="fas fa-cogs"></i>
                    Admin Panel
                </h2>
            </div>

            <nav class="flex-1 overflow-y-auto p-4">
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.dashboard') ? 'bg-purple-600' : 'hover:bg-gray-800' }} transition">
                    <i class="fas fa-chart-line mr-3"></i>Dashboard
                </a>

                <div class="mt-6">
                    <p class="text-xs uppercase text-gray-500 font-bold px-4 mb-3">Catalog</p>
                    <a href="{{ route('admin.products.index') }}" class="block px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.products.*') ? 'bg-purple-600' : 'hover:bg-gray-800' }} transition">
                        <i class="fas fa-box mr-3"></i>Products
                    </a>
                    <a href="{{ route('admin.categories.index') }}" class="block px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.categories.*') ? 'bg-purple-600' : 'hover:bg-gray-800' }} transition">
                        <i class="fas fa-list mr-3"></i>Categories
                    </a>
                </div>

                <div class="mt-6">
                    <p class="text-xs uppercase text-gray-500 font-bold px-4 mb-3">Marketing</p>
                    <a href="{{ route('admin.newsletters.index') }}" class="block px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.newsletters.*') ? 'bg-purple-600' : 'hover:bg-gray-800' }} transition">
                        <i class="fas fa-envelope-open-text mr-3"></i>Newsletters
                    </a>
                </div>

                <div class="mt-6">
                    <p class="text-xs uppercase text-gray-500 font-bold px-4 mb-3">Sales</p>
                    <a href="{{ route('admin.orders.index') }}" class="block px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.orders.*') ? 'bg-purple-600' : 'hover:bg-gray-800' }} transition">
                        <i class="fas fa-shopping-cart mr-3"></i>Orders
                    </a>
                    <a href="{{ route('admin.reviews.index') }}" class="block px-4 py-3 rounded-lg mb-2 {{ request()->routeIs('admin.reviews.*') ? 'bg-purple-600' : 'hover:bg-gray-800' }} transition">
                        <i class="fas fa-star mr-3"></i>Reviews
                    </a>
                </div>
            </nav>

            <div class="border-t border-gray-700 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 rounded-lg bg-gray-800 hover:bg-gray-700 transition flex items-center gap-2">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <div class="bg-white border-b border-gray-200 px-8 py-4 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">@yield('page_title', 'Dashboard')</h1>
                <div class="text-gray-600">
                    {{ auth()->user()->name }}
                </div>
            </div>

            <!-- Content Area -->
            <div class="flex-1 overflow-auto p-8">
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg">
                        <strong>Error!</strong>
                        @foreach($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-6 py-4 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-6 py-4 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
