@extends('layouts.app')

@section('title', ($term !== '' ? 'Search: ' . $term . ' – ' : ($activeCategory ? $activeCategory->name . ' – ' : '')) . 'Nasaari Store – Hijabs | Modesty & More')

@section('content')
@php
    // Same query parameters as before: search, category, sort
    $action = route('products.index');
    $activeFilters = ($activeCategory ? 1 : 0) + ($currentSort !== 'newest' ? 1 : 0);
    $query = fn (array $changes) => $action . '?' . http_build_query(array_filter(array_merge(request()->only('search', 'category', 'sort'), $changes), fn ($v) => $v !== null && $v !== ''));
    $count = $products->total();
@endphp

<h1 class="sr-only">Shop Nasaari: hijabs, modesty &amp; more</h1>

{{-- ════════════════════════════════════════════
     HERO – first page of the shop only
════════════════════════════════════════════ --}}
@if($showHero)
    <section class="shop-hero" aria-labelledby="shop-hero-title">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 lg:py-16 grid gap-10 lg:grid-cols-[1.1fr_1fr] items-center">
            <div class="shop-hero__copy">
                <p class="font-script text-3xl sm:text-4xl text-brand-900 leading-none">Nasaari</p>
                <h2 id="shop-hero-title" class="mt-3 font-serif text-4xl sm:text-5xl lg:text-6xl leading-[1.05] text-brand-900">
                    Modesty, <em class="italic text-brand-700">beautifully</em> made.
                </h2>
                <p class="mt-5 max-w-lg text-base sm:text-lg text-brand-900/85 leading-relaxed">
                    Soft, breathable hijabs and modest pieces in colours chosen to be worn every day and treasured for years.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $query(['sort' => 'newest']) }}#shop-products" class="btn-brand !bg-brand-900 hover:!bg-brand-800 !shadow-brand-900/20">
                        <i class="fas fa-star text-xs" aria-hidden="true"></i> Shop new arrivals
                    </a>
                    <a href="{{ $query(['sort' => 'popular']) }}#shop-products" class="btn-secondary !bg-white/80 !border-white !text-brand-900 hover:!bg-white">
                        Best sellers
                    </a>
                </div>
                <ul class="mt-9 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm text-brand-900" role="list">
                    <li class="shop-hero__perk"><i class="fas fa-truck text-brand-700" aria-hidden="true"></i> Pay delivery on arrival in Ghana</li>
                    <li class="shop-hero__perk"><i class="fas fa-plane-departure text-brand-700" aria-hidden="true"></i> DHL Express worldwide</li>
                    <li class="shop-hero__perk"><i class="fas fa-lock text-brand-700" aria-hidden="true"></i> Secure Paystack checkout</li>
                </ul>
            </div>

            @if($heroProducts->isNotEmpty())
                <div class="shop-hero__collage" aria-hidden="true">
                    @foreach($heroProducts as $i => $hero)
                        @php $img = $hero->variants->first()?->image ?: $hero->main_image; @endphp
                        <a href="{{ route('products.show', $hero) }}" tabindex="-1" class="shop-hero__tile shop-hero__tile--{{ $i + 1 }}">
                            <img src="{{ asset('storage/' . $img) }}" alt="" width="600" height="750" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" decoding="async">
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif

{{-- ════════════════════════════════════════════
     CATEGORY NAVIGATION
════════════════════════════════════════════ --}}
@if($categories->isNotEmpty())
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 {{ $showHero ? 'pt-2' : 'pt-6' }}" aria-label="Categories">
        <ul class="shop-categories" role="list">
            <li>
                <a href="{{ $query(['category' => null]) }}" class="shop-category {{ $activeCategory ? '' : 'is-active' }}" @unless($activeCategory) aria-current="page" @endunless>
                    All pieces
                </a>
            </li>
            @foreach($categories as $cat)
                <li>
                    <a href="{{ $query(['category' => $cat->slug]) }}" class="shop-category {{ $activeCategory?->id === $cat->id ? 'is-active' : '' }}" @if($activeCategory?->id === $cat->id) aria-current="page" @endif>
                        {{ $cat->name }} <span class="shop-category__count">{{ $cat->products_count }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>
@endif

{{-- ════════════════════════════════════════════
     TOOLBAR – slim, sticky search / category / sort
════════════════════════════════════════════ --}}
<div class="shop-toolbar mt-4" data-shop-toolbar>
    <div class="max-w-7xl mx-auto h-full px-4 sm:px-6 lg:px-8">
        {{-- Desktop: one row --}}
        <form method="GET" action="{{ $action }}" class="hidden md:flex h-full items-center gap-4" data-autosubmit role="search">
            <label class="shop-toolbar__search">
                <span class="sr-only">Search products</span>
                <i class="fas fa-search text-xs text-gray-400" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ $term }}" placeholder="Search hijabs, fabrics, colours…" id="search-input" autocomplete="off" maxlength="100">
                @if($term !== '')
                    <a href="{{ $query(['search' => null]) }}" class="shop-toolbar__clear" aria-label="Clear search"><i class="fas fa-times" aria-hidden="true"></i></a>
                @endif
            </label>
            <p class="text-xs text-gray-500 whitespace-nowrap">{{ $count }} {{ Str::plural('piece', $count) }}</p>

            <div class="ml-auto flex items-center gap-2">
                <label class="sr-only" for="toolbar-category">Category</label>
                <select id="toolbar-category" name="category" class="shop-toolbar__select">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->slug }}" @selected($activeCategory?->id === $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <label class="sr-only" for="toolbar-sort">Sort by</label>
                <select id="toolbar-sort" name="sort" class="shop-toolbar__select">
                    @foreach($sorts as $key => $label)
                        <option value="{{ $key }}" @selected($currentSort === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <noscript><button type="submit" class="btn-brand btn-sm">Apply</button></noscript>
            </div>
        </form>

        {{-- Phones: search icon that expands + one Filter button --}}
        <div class="md:hidden flex h-full items-center gap-2">
            <form method="GET" action="{{ $action }}" class="shop-toolbar__mobile-search {{ $term !== '' ? 'is-open' : '' }}" data-mobile-search role="search">
                @if($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory->slug }}">@endif
                @if(request('sort'))<input type="hidden" name="sort" value="{{ $currentSort }}">@endif
                <button type="button" class="shop-toolbar__icon" data-search-toggle aria-label="Search" aria-expanded="{{ $term !== '' ? 'true' : 'false' }}">
                    <i class="fas fa-search" aria-hidden="true"></i>
                </button>
                <label class="sr-only" for="mobile-search">Search products</label>
                <input type="search" id="mobile-search" name="search" value="{{ $term }}" placeholder="Search hijabs, colours…" enterkeyhint="search" maxlength="100">
            </form>
            <p class="text-xs text-gray-500 whitespace-nowrap" data-mobile-count>{{ $count }} {{ Str::plural('piece', $count) }}</p>
            <button type="button" class="shop-toolbar__filter ml-auto" data-filter-open aria-haspopup="dialog">
                <i class="fas fa-sliders text-xs" aria-hidden="true"></i> Filter
                @if($activeFilters)<span class="ml-1 rounded-full bg-accent-500 px-1.5 text-[10px] font-bold text-white">{{ $activeFilters }}</span>@endif
            </button>
        </div>
    </div>
</div>

{{-- Phones: filter bottom sheet --}}
<dialog id="filterSheet" class="shop-modal shop-filter-sheet" aria-labelledby="filterSheetTitle">
    <form method="GET" action="{{ $action }}" class="p-6 space-y-6">
        @if($term !== '')<input type="hidden" name="search" value="{{ $term }}">@endif
        <div class="flex items-center justify-between">
            <h2 id="filterSheetTitle" class="font-serif text-2xl text-brand-900">Filter</h2>
            <button type="button" class="shop-toolbar__icon" data-filter-close aria-label="Close"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <fieldset>
            <legend class="admin-label">Category</legend>
            <div class="flex flex-wrap gap-2">
                <label class="shop-chip"><input type="radio" name="category" value="" class="sr-only" @checked(! $activeCategory)><span>All</span></label>
                @foreach($categories as $cat)
                    <label class="shop-chip"><input type="radio" name="category" value="{{ $cat->slug }}" class="sr-only" @checked($activeCategory?->id === $cat->id)><span>{{ $cat->name }}</span></label>
                @endforeach
            </div>
        </fieldset>
        <fieldset>
            <legend class="admin-label">Sort by</legend>
            <div class="flex flex-wrap gap-2">
                @foreach($sorts as $key => $label)
                    <label class="shop-chip"><input type="radio" name="sort" value="{{ $key }}" class="sr-only" @checked($currentSort === $key)><span>{{ $label }}</span></label>
                @endforeach
            </div>
        </fieldset>
        <div class="flex gap-3 pt-2">
            <a href="{{ $action }}{{ $term !== '' ? '?search=' . urlencode($term) : '' }}" class="btn-secondary flex-1">Clear</a>
            <button type="submit" class="btn-brand flex-1">Show results</button>
        </div>
    </form>
</dialog>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-12 lg:pt-8" id="shop-products">

    {{-- Results heading for searches and categories --}}
    @if($term !== '' || $activeCategory)
        <div class="mb-8 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-accent-600">{{ $term !== '' ? 'Search' : 'Category' }}</p>
                <h2 class="mt-1 font-serif text-3xl text-brand-900">
                    @if($term !== '')
                        “{{ $term }}”@if($activeCategory) <span class="text-gray-400 text-xl">in {{ $activeCategory->name }}</span>@endif
                    @else
                        {{ $activeCategory->name }}
                    @endif
                </h2>
                @if($activeCategory?->description && $term === '')
                    <p class="mt-2 max-w-2xl text-sm text-gray-600">{{ $activeCategory->description }}</p>
                @endif
            </div>
            <a href="{{ $action }}" class="text-sm font-semibold text-accent-600 hover:text-accent-700">
                <i class="fas fa-times text-xs" aria-hidden="true"></i> Clear {{ $term !== '' ? 'search' : 'filter' }}
            </a>
        </div>
    @endif

    {{-- ════════════════════════════════════════════
         PRODUCTS GRID
    ════════════════════════════════════════════ --}}
    <section class="min-w-0 shop-results" data-shop-results aria-live="polite">
        <div class="shop-results__loading" aria-hidden="true"><span class="shop-spinner"></span></div>

        @if($products->count() > 0)
            {{-- Products Grid: floating images + product popup --}}
            <x-shop.product-grid :products="$products" />

            {{-- Pagination --}}
            <div class="flex justify-center pt-16">
                {{ $products->links() }}
            </div>

        @elseif($recommendations)
            {{-- Search with no match: friendly message and recommendations instead of an empty page --}}
            <div class="shop-empty">
                <div class="shop-empty__icon" aria-hidden="true"><i class="fas fa-wand-magic-sparkles"></i></div>
                <h2 class="font-serif text-2xl sm:text-3xl text-brand-900">We couldn't find exactly what you're looking for, but you might like these products.</h2>
                @if($suggestion)
                    <p class="mt-3 text-gray-600">
                        Did you mean <a href="{{ $query(['search' => $suggestion]) }}" class="font-semibold text-accent-600 underline decoration-accent-300 underline-offset-4 hover:text-accent-700">{{ $suggestion }}</a>?
                    </p>
                @else
                    <p class="mt-3 text-gray-600">Try a different word, check the spelling, or browse the collection.</p>
                @endif
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a href="{{ $action }}" class="btn-brand" id="clear-search">
                        <i class="fas fa-th-large text-xs" aria-hidden="true"></i> Clear search &amp; browse all
                    </a>
                    @if($activeCategory)
                        <a href="{{ $query(['search' => null]) }}" class="btn-secondary">All {{ $activeCategory->name }}</a>
                    @endif
                </div>
            </div>

            <div class="mt-14 space-y-14">
                <x-shop.product-row title="Similar to your search" icon="fa-magnifying-glass" :products="$recommendations['similar']" />
                <x-shop.product-row title="From the same collection" icon="fa-layer-group" :products="$recommendations['category']" />
                <x-shop.product-row title="Best sellers" icon="fa-fire" subtitle="What our customers love most" :products="$recommendations['best']" :link="$query(['search' => null, 'sort' => 'popular'])" />
                <x-shop.product-row title="New arrivals" icon="fa-star" :products="$recommendations['new']" :link="$query(['search' => null, 'sort' => 'newest'])" />
            </div>

        @else
            {{-- Empty category / filter --}}
            <div class="shop-empty">
                <div class="shop-empty__icon" aria-hidden="true"><i class="fas fa-box-open"></i></div>
                <h2 class="font-serif text-2xl text-brand-900">Nothing here just yet</h2>
                <p class="mt-3 max-w-sm mx-auto text-gray-600 leading-relaxed">New pieces are on their way. In the meantime, have a look around the rest of the collection.</p>
                <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">
                    <a href="{{ $action }}" class="btn-brand"><i class="fas fa-th-large text-xs" aria-hidden="true"></i> Browse all</a>
                    <a href="{{ route('story') }}" class="btn-secondary">Our story</a>
                </div>
            </div>
        @endif
    </section>
</div>

@endsection
