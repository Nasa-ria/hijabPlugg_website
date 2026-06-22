@extends('layouts.app')

@section('title', 'Shop Products – Hijabis Plugg')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">

    {{-- ════════════════════════════════════════════
         PAGE HEADER
    ════════════════════════════════════════════ --}}
    <div class="mb-10 lg:mb-12">
        <h1 class="section-heading">Our Collection</h1>
        <p class="section-subheading">Discover curated modest fashion pieces crafted for elegance, comfort, and confidence.</p>
    </div>

    {{-- ════════════════════════════════════════════
         FILTERS SECTION – Above the grid
    ════════════════════════════════════════════ --}}
    <div class="mb-10 space-y-6">
        {{-- Search Bar --}}
        <div class="card-panel p-6">
            <form method="GET" action="{{ route('products.index') }}" class="relative">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                <div class="flex items-center">
                    <i class="fas fa-search absolute left-6 text-gray-400"></i>
                    <input type="text" name="search" placeholder="Search products by name, style, color…" value="{{ request('search') }}"
                           class="w-full pl-12 pr-6 py-3.5 rounded-2xl border border-gray-200 bg-gray-50 text-gray-800 placeholder-gray-500 focus:bg-white focus:border-brand-400 focus:ring-2 focus:ring-brand-100 transition-all duration-200" id="search-input">
                </div>
            </form>
        </div>

        {{-- Categories & Sort Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_280px] gap-6">
            {{-- Categories Filter --}}
            <div class="card-panel p-6">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Categories</p>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('products.index', request()->except('category')) }}"
                       class="px-4 py-2.5 rounded-full text-sm font-medium transition-all duration-200
                              {{ !request('category') ? 'bg-brand-600 text-white shadow-md shadow-brand-500/25' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" id="cat-all">
                        <i class="fas fa-th-large text-[10px] mr-1.5"></i>All Items
                    </a>
                    @foreach($categories as $cat)
                        <a href="{{ route('products.index', array_merge(request()->except('category'), ['category' => $cat->slug])) }}"
                           class="px-4 py-2.5 rounded-full text-sm font-medium transition-all duration-200
                                  {{ request('category') === $cat->slug ? 'bg-brand-600 text-white shadow-md shadow-brand-500/25' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}" id="cat-{{ $cat->slug }}">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Sort Dropdown --}}
            <div class="card-panel p-6">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 mb-4">Sort By</p>
                <div class="relative">
                    @php
                        $sorts = [
                            'latest'     => 'Latest',
                            'price_low'  => 'Price: Low to High',
                            'price_high' => 'Price: High to Low',
                            'rating'     => 'Top Rated',
                        ];
                        $currentSort = request('sort', 'latest');
                        $currentLabel = $sorts[$currentSort] ?? 'Latest';
                    @endphp
                    <div class="relative group">
                        <button type="button" class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-left text-sm font-medium text-gray-700 hover:border-brand-300 focus:ring-2 focus:ring-brand-100 transition-all flex items-center justify-between cursor-pointer" onclick="document.getElementById('sortDropdown').classList.toggle('hidden')">
                            <span><i class="fas fa-arrow-up-down text-gray-400 mr-2"></i>{{ $currentLabel }}</span>
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </button>
                        <div id="sortDropdown" class="hidden absolute top-full left-0 right-0 mt-2 bg-white border border-gray-200 rounded-xl shadow-lg z-10">
                            @foreach($sorts as $key => $label)
                                <a href="{{ route('products.index', array_merge(request()->query(), ['sort' => $key])) }}"
                                   class="block px-4 py-3 text-sm font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 first:rounded-t-xl last:rounded-b-xl transition-colors">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         PRODUCTS GRID
    ════════════════════════════════════════════ --}}
    <section class="min-w-0">
            @if($products->count() > 0)
                {{-- Results Info --}}
                <div class="mb-6 flex items-center justify-between">
                    <p class="text-sm text-gray-600">
                        Showing <span class="font-semibold text-gray-800">{{ $products->count() }}</span> of <span class="font-semibold text-gray-800">{{ $products->total() }}</span> product{{ $products->total() !== 1 ? 's' : '' }}
                    </p>
                </div>

                {{-- Products Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-10">
                    @foreach($products as $product)
                        <a href="{{ route('products.show', $product) }}" class="stagger-item group" id="product-{{ $product->id }}">
                            <div class="product-card h-full flex flex-col">
                                {{-- Image --}}
                                <div class="relative aspect-[4/5] img-zoom bg-gray-100 rounded-2xl overflow-hidden">
                                    @if($product->main_image)
                                        <img src="{{ asset('storage/' . $product->main_image) }}"
                                             alt="{{ $product->name }}"
                                             class="w-full h-full object-cover"
                                             loading="lazy">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-brand-50 to-gray-100">
                                            <i class="fas fa-image text-4xl text-gray-300"></i>
                                        </div>
                                    @endif

                                    {{-- Sale badge --}}
                                    @if($product->sale_price)
                                        <div class="absolute top-4 left-4 z-10">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-red-500 text-white text-xs font-bold shadow-lg shadow-red-500/30">
                                                <i class="fas fa-lightning-bolt text-[9px]"></i>
                                                SAVE {{ round((($product->price - $product->sale_price) / $product->price) * 100) }}%
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Sold out overlay --}}
                                    @if(!$product->is_available)
                                        <div class="absolute inset-0 bg-white/70 backdrop-blur-sm flex items-center justify-center z-5">
                                            <span class="badge-out text-sm"><i class="fas fa-lock mr-1.5"></i>Sold Out</span>
                                        </div>
                                    @endif

                                    {{-- Quick view hover overlay --}}
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-5 z-5">
                                        <span class="px-5 py-2.5 rounded-full bg-white text-sm font-semibold text-gray-900 shadow-xl transform translate-y-3 group-hover:translate-y-0 transition-transform duration-300">
                                            View Details <i class="fas fa-arrow-right ml-2 text-brand-500"></i>
                                        </span>
                                    </div>
                                </div>

                                {{-- Details --}}
                                <div class="p-5 flex-1 flex flex-col">
                                    <h3 class="font-semibold text-gray-900 group-hover:text-brand-600 transition-colors duration-200 mb-2 line-clamp-2 text-base">
                                        {{ $product->name }}
                                    </h3>
                                    <p class="text-xs text-gray-500 mb-4 line-clamp-2 leading-relaxed">{{ Str::limit($product->short_description, 70) }}</p>

                                    <div class="mt-auto space-y-4">
                                        {{-- Pricing --}}
                                        <div class="flex items-end justify-between">
                                            <div class="space-y-0.5">
                                                @if($product->sale_price)
                                                    <p class="text-xs text-gray-400 line-through">₵{{ number_format($product->price, 2) }}</p>
                                                    <p class="text-xl font-bold text-brand-600">₵{{ number_format($product->sale_price, 2) }}</p>
                                                @else
                                                    <p class="text-xl font-bold text-brand-600">₵{{ number_format($product->price, 2) }}</p>
                                                @endif
                                            </div>
                                            {{-- Rating --}}
                                            @if($product->average_rating > 0)
                                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-50 border border-amber-100">
                                                    <i class="fas fa-star text-amber-400 text-xs"></i>
                                                    <span class="text-xs font-bold text-amber-700">{{ number_format($product->average_rating, 1) }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Add to Cart CTA --}}
                                        @if($product->is_available && $product->stock_quantity > 0)
                                            <form action="{{ route('cart.add') }}" method="POST" class="w-full" onsubmit="event.preventDefault(); addToCart(this);">
                                                @csrf
                                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="w-full py-2.5 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 font-semibold hover:bg-brand-100 transition-all duration-200 text-sm">
                                                    <i class="fas fa-shopping-bag text-xs mr-1.5"></i>Add to Cart
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="flex justify-center pt-8">
                    {{ $products->links() }}
                </div>

            @else
                {{-- Empty State --}}
                <div class="flex flex-col items-center justify-center py-24 px-6 text-center animate-fade-in">
                    <div class="w-28 h-28 rounded-full bg-brand-50 flex items-center justify-center mb-8 animate-float">
                        <i class="fas fa-search text-5xl text-brand-300"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-3">No Products Found</h2>
                    <p class="text-gray-600 mb-8 max-w-xs leading-relaxed">We couldn't find any products matching your search. Try adjusting your filters or browse our full collection.</p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="{{ route('products.index') }}" class="btn-brand">
                            <i class="fas fa-home text-xs"></i> Browse All
                        </a>
                        <a href="/" class="btn-secondary">
                            <i class="fas fa-arrow-left text-xs"></i> Go Home
                        </a>
                    </div>
                </div>
            @endif
    </section>
</div>

@push('scripts')
<script>
    function addToCart(form) {
        const formData = new FormData(form);
        fetch('{{ route("cart.add") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success toast
                const toast = document.createElement('div');
                toast.className = 'toast-item pointer-events-auto fixed bottom-6 right-6 flex items-start gap-3 px-5 py-4 rounded-2xl bg-white border border-emerald-100 shadow-2xl shadow-emerald-500/10 z-50 animate-slide-in';
                toast.innerHTML = `
                    <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                        <i class="fas fa-check-circle text-emerald-500 text-sm"></i>
                    </span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-emerald-800">Added to cart</p>
                        <p class="text-xs text-emerald-600 mt-0.5">Item successfully added</p>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xs"></i>
                    </button>
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
@endpush
@endsection

