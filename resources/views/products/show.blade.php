@extends('layouts.app')

@section('title', $product->name . ' – Nasaari Store')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 lg:py-12">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-8 animate-fade-in" aria-label="Breadcrumb">
        <a href="{{ route('products.index') }}" class="hover:text-brand-500 transition-colors">Shop</a>
        <i class="fas fa-chevron-right text-[8px]"></i>
        <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-brand-500 transition-colors">{{ $product->category->name }}</a>
        <i class="fas fa-chevron-right text-[8px]"></i>
        <span class="text-gray-600 font-medium truncate max-w-[200px]">{{ $product->name }}</span>
    </nav>

    {{-- ════════════════════════════════════════════
         PRODUCT – gallery, colours, price, stock, add to cart
         (colour/quantity/cart behaviour shared with the shop popup: resources/js/shop.js)
    ════════════════════════════════════════════ --}}
    @php
        $variants = $product->variants;
        $hasVariants = $variants->isNotEmpty();
        // Gallery: one photo per colour first, then the extra gallery photos
        $colourPhotos = $hasVariants
            ? $variants->map(fn ($v) => ['key' => (string) $v->id, 'path' => $v->imagePath(), 'alt' => $product->name . ' in ' . $v->color_name, 'label' => $v->color_name])
            : collect();
        $extraPhotos = collect([$product->main_image])->merge($product->images->pluck('image_path'))
            ->filter()->unique()
            ->reject(fn ($path) => $colourPhotos->pluck('path')->contains($path))
            ->values()
            ->map(fn ($path, $i) => ['key' => 'img-' . $i, 'path' => $path, 'alt' => $product->name . ', photo ' . ($i + 1), 'label' => 'photo ' . ($i + 1)]);
        $stage = $colourPhotos->concat($extraPhotos)->values();
        $inStock = $product->is_available && ($hasVariants ? $variants->contains->inStock() : $product->stock_quantity > 0);
        $lowestPrice = $hasVariants ? $variants->map->effectivePrice()->min() : $product->currentPrice();
        $chart = $product->sizeChartTable();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-[1.1fr_1fr] gap-10 lg:gap-16 mb-20" data-product-detail aria-labelledby="product-title">
        {{-- Gallery --}}
        <div class="lg:sticky lg:top-24 self-start">
            <div class="shop-modal__stage !max-h-none rounded-2xl">
                @forelse($stage as $i => $img)
                    <img src="{{ asset('storage/' . $img['path']) }}" alt="{{ $img['alt'] }}" width="900" height="1125"
                         decoding="async" {{ $i === 0 ? 'fetchpriority=high' : 'loading=lazy' }}
                         data-stage-image="{{ $img['key'] }}" class="{{ $i === 0 ? 'is-active' : '' }}">
                @empty
                    <span class="flex h-full w-full items-center justify-center text-accent-300" aria-hidden="true"><i class="fas fa-image text-5xl"></i></span>
                @endforelse
                <x-shop.badges :product="$product" :limit="3" class="shop-badges--overlay shop-badges--lg" />
            </div>

            @if($stage->count() > 1)
                <div class="mt-4 flex flex-wrap gap-3" aria-label="Photos">
                    @foreach($stage as $i => $img)
                        <button type="button" class="shop-thumb !w-20 {{ $i === 0 ? 'is-active' : '' }}" data-thumb="{{ $img['key'] }}" aria-label="Show {{ $img['label'] }}">
                            <img src="{{ asset('storage/' . $img['path']) }}" alt="" width="80" height="100" loading="lazy">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div>
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="text-xs font-semibold uppercase tracking-[0.25em] text-accent-600 hover:text-accent-700">
                {{ $product->category->name }}
            </a>
            <h1 id="product-title" class="mt-3 font-serif text-4xl leading-tight text-brand-900">
                {{ $product->name }}<span class="block mt-1 text-lg font-sans text-gray-500" data-color-title></span>
            </h1>

            {{-- Rating --}}
            <div class="mt-3 flex items-center gap-2 text-sm">
                @if($product->average_rating > 0)
                    <span class="flex items-center gap-0.5" aria-hidden="true">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="{{ $i <= round($product->average_rating) ? 'fas text-amber-400' : 'far text-gray-300' }} fa-star text-xs"></i>
                        @endfor
                    </span>
                    <a href="#reviews-section" class="text-gray-500 hover:text-brand-700">{{ number_format($product->average_rating, 1) }} · {{ $product->total_reviews }} review{{ $product->total_reviews !== 1 ? 's' : '' }}</a>
                @else
                    <a href="#reviews-section" class="text-gray-400 hover:text-brand-700">No reviews yet</a>
                @endif
            </div>

            {{-- Price --}}
            <p class="mt-6 flex items-baseline gap-3">
                <span class="text-3xl font-semibold text-brand-900" data-price>@money($lowestPrice)</span>
                <s class="text-lg text-gray-400" data-compare>{{ $product->isOnSale() ? \App\Support\Money::show($product->price) : '' }}</s>
                @if($product->isOnSale() && ! $hasVariants)
                    <span class="rounded-full bg-accent-100 px-2.5 py-0.5 text-xs font-bold text-accent-700">Save {{ round((1 - $product->sale_price / $product->price) * 100) }}%</span>
                @endif
            </p>
            @if($displayCurrency->isConverted())
                <p class="mt-1 text-xs text-gray-500">Approximate price in {{ config('currency.supported.' . $displayCurrency->current() . '.name') }}s. You'll be charged @ghs($lowestPrice) in Ghana Cedis.</p>
            @endif

            {{-- How many left --}}
            <p class="mt-3 text-sm" data-stock-left aria-live="polite">
                @if(! $inStock)
                    <span class="text-red-700">Out of stock</span>
                @elseif($hasVariants)
                    <span class="text-gray-600">{{ $variants->sum('stock_quantity') }} in stock across {{ $variants->filter->inStock()->count() }} colours. Choose a colour to see how many are left.</span>
                @else
                    <span class="{{ $product->isLowStock() ? 'text-accent-700 font-semibold' : 'text-emerald-700' }}">
                        <i class="fas {{ $product->isLowStock() ? 'fa-hourglass-half' : 'fa-check' }} text-xs mr-1" aria-hidden="true"></i>{{ $product->isLowStock() ? 'Only ' . $product->stock_quantity . ' left, order soon' : 'In stock, ready to ship' }}
                    </span>
                @endif
            </p>

            @if($product->short_description)
                <p class="mt-5 leading-7 text-gray-600">{{ $product->short_description }}</p>
            @endif

            {{-- Colour, quantity, add to cart --}}
            <form action="{{ route('cart.add') }}" method="POST" class="mt-8 pt-8 border-t border-cream-300" data-add-form data-stock="{{ $product->stock_quantity }}" data-has-variants="{{ $hasVariants ? 1 : 0 }}">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" value="">
                <input type="hidden" name="source" value="page">

                @if($hasVariants)
                    <p id="swatch-label-{{ $product->id }}" class="text-sm text-gray-600">
                        Colour: <span class="font-semibold text-brand-900" data-selected-color>Choose a colour</span>
                    </p>
                    <x-shop.color-swatches :product="$product" mode="select" class="mt-3" />
                @endif

                <div class="mt-6 flex items-center gap-4">
                    <div class="shop-qty" role="group" aria-label="Quantity">
                        <button type="button" data-qty-step="-1" aria-label="Decrease quantity"><i class="fas fa-minus text-[10px]" aria-hidden="true"></i></button>
                        <input type="number" name="quantity" value="1" min="1" max="100" inputmode="numeric" aria-label="Quantity" data-qty>
                        <button type="button" data-qty-step="1" aria-label="Increase quantity"><i class="fas fa-plus text-[10px]" aria-hidden="true"></i></button>
                    </div>
                    <button type="submit" class="btn-brand flex-1 !py-3.5" data-add-button @disabled($hasVariants || ! $inStock)>
                        <span data-add-label>{{ $inStock ? 'Add to cart' : 'Out of stock' }}</span>
                    </button>
                </div>

                <p class="mt-3 min-h-[1.25rem] text-sm" data-message role="status" aria-live="polite">
                    @if($hasVariants && $inStock)<span class="text-gray-500">Choose a colour to add it to your cart.</span>@endif
                </p>

                <div class="mt-2 hidden gap-3" data-after-add>
                    <a href="{{ route('products.index') }}" class="btn-secondary flex-1 !py-2.5">Continue shopping</a>
                    <a href="{{ route('cart.index') }}" class="btn-brand flex-1 !py-2.5">View cart</a>
                </div>
            </form>

            {{-- Description, details, size chart --}}
            <div class="mt-10 divide-y divide-cream-300 border-y border-cream-300">
                <details class="group py-5" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-brand-900">
                        Description <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                    </summary>
                    <div class="mt-3 leading-7 text-gray-600 whitespace-pre-line">{{ $product->description }}</div>
                </details>

                @if($product->material || $product->size)
                    <details class="group py-5" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-brand-900">
                            Details &amp; dimensions <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                        </summary>
                        <dl class="mt-3 grid grid-cols-[8rem_1fr] gap-y-2 text-sm">
                            @if($product->material)
                                <dt class="text-gray-500">Fabric</dt><dd class="text-brand-900">{{ $product->material }}</dd>
                            @endif
                            @if($product->size)
                                <dt class="text-gray-500">Dimensions</dt><dd class="text-brand-900">{{ $product->size }}</dd>
                            @endif
                        </dl>
                    </details>
                @endif

                @if($chart)
                    <details class="group py-5" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between font-semibold text-brand-900">
                            Size chart <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                        </summary>
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full min-w-[20rem] text-sm">
                                <thead>
                                    <tr class="border-b border-cream-300 text-left">
                                        @foreach($chart['headers'] as $heading)
                                            <th scope="col" class="py-2 pr-4 font-semibold text-brand-900">{{ $heading }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($chart['rows'] as $row)
                                        <tr class="border-b border-cream-200 last:border-0">
                                            @foreach($row as $i => $cell)
                                                @if($i === 0)
                                                    <th scope="row" class="py-2 pr-4 font-medium text-brand-900 text-left">{{ $cell }}</th>
                                                @else
                                                    <td class="py-2 pr-4 text-gray-600">{{ $cell }}</td>
                                                @endif
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </details>
                @endif
            </div>

            {{-- Delivery for this visitor --}}
            <div class="mt-8">
                <x-shop.shipping-notice :type="$displayCurrency->isHomeCountry() ? 'pod' : 'dhl'" />
            </div>

            @if($product->sku)
                <p class="mt-4 text-xs text-gray-400">SKU {{ $product->sku }}</p>
            @endif

            {{-- Trust signals --}}
            <div class="mt-6 grid grid-cols-3 gap-3 text-center text-[11px] font-medium text-gray-500">
                <span class="flex flex-col items-center gap-1.5"><i class="fas fa-truck text-accent-500" aria-hidden="true"></i>Fast delivery</span>
                <span class="flex flex-col items-center gap-1.5"><i class="fas fa-shield-halved text-accent-500" aria-hidden="true"></i>Secure payment</span>
                <span class="flex flex-col items-center gap-1.5"><i class="fas fa-rotate-left text-accent-500" aria-hidden="true"></i>Easy returns</span>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════════
         REVIEWS SECTION
    ════════════════════════════════════════════ --}}
    <section class="mb-16" id="reviews-section">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="section-heading">Customer Reviews</h2>
                @if($product->total_reviews > 0)
                    <p class="section-subheading">{{ $product->total_reviews }} review{{ $product->total_reviews !== 1 ? 's' : '' }} from verified buyers</p>
                @endif
            </div>
        </div>

        @auth
            {{-- Review Form --}}
            <div class="bg-white rounded-2xl border border-gray-100 p-6 lg:p-8 mb-8 animate-fade-in">
                <h3 class="text-lg font-bold text-gray-800 mb-1">{{ $userReview ? 'Update Your Review' : 'Write a Review' }}</h3>
                <p class="text-sm text-gray-400 mb-6">Share your experience with this product</p>

                @if($userReview)
                    <form action="{{ route('reviews.update', $userReview) }}" method="POST" id="review-form">
                        @method('PUT')
                @else
                    <form action="{{ route('reviews.store', $product) }}" method="POST" id="review-form">
                @endif
                    @csrf

                    {{-- Star Rating --}}
                    <div class="mb-6">
                        <label class="text-sm font-semibold text-gray-700 mb-3 block">Your Rating</label>
                        <div class="flex gap-1.5" id="star-rating-container">
                            @for($i = 1; $i <= 5; $i++)
                                <input type="radio" name="rating" value="{{ $i }}" id="star{{ $i }}" class="hidden peer"
                                    {{ $userReview && $userReview->rating == $i ? 'checked' : '' }}>
                                <label for="star{{ $i }}" class="cursor-pointer w-10 h-10 rounded-xl flex items-center justify-center border-2 border-gray-200 text-gray-300 hover:border-amber-300 hover:text-amber-400 transition-all duration-200 star-label" data-value="{{ $i }}">
                                    <i class="fas fa-star text-lg"></i>
                                </label>
                            @endfor
                        </div>
                        @error('rating')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    {{-- Title --}}
                    <div class="mb-5">
                        <label class="text-sm font-semibold text-gray-700 mb-2 block">Title <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" name="title" placeholder="Summarise your experience" value="{{ $userReview?->title }}"
                               class="input-field" id="review-title">
                    </div>

                    {{-- Comment --}}
                    <div class="mb-6">
                        <label class="text-sm font-semibold text-gray-700 mb-2 block">Your Review</label>
                        <textarea name="comment" placeholder="Tell others what you think about this product…" required rows="4"
                                  class="input-field resize-none" id="review-comment">{{ $userReview?->comment }}</textarea>
                        @error('comment')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <button type="submit" class="btn-brand" id="review-submit">
                        <i class="fas fa-paper-plane text-xs"></i>
                        {{ $userReview ? 'Update Review' : 'Submit Review' }}
                    </button>
                </form>
            </div>
        @else
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl bg-cream-200 border border-cream-300 mb-8 animate-fade-in">
                <span class="w-10 h-10 rounded-xl bg-accent-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user-lock text-accent-500 text-sm"></i>
                </span>
                <p class="text-sm text-brand-700">
                    <a href="{{ route('login') }}" class="font-semibold hover:underline">Sign in</a> to leave a review and share your experience.
                </p>
            </div>
        @endauth

        {{-- Reviews List --}}
        @if($product->approvedReviews->count() > 0)
            <div class="space-y-4">
                @foreach($product->approvedReviews as $review)
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 transition-all duration-200 hover:border-gray-200" id="review-{{ $review->id }}">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <span class="bg-accent-500 w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold shadow-md">
                                    {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-800 text-sm">{{ $review->user->name }}</p>
                                    <div class="flex items-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="{{ $i <= $review->rating ? 'fas' : 'far' }} fa-star text-[11px] {{ $i <= $review->rating ? 'text-amber-400' : 'text-gray-300' }}"></i>
                                        @endfor
                                    </div>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400 flex-shrink-0">{{ $review->created_at->diffForHumans() }}</span>
                        </div>

                        @if($review->title)
                            <h4 class="font-semibold text-gray-800 mb-1.5 text-[15px]">{{ $review->title }}</h4>
                        @endif
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $review->comment }}</p>

                        @if($review->admin_reply)
                            <div class="mt-4 ml-4 pl-4 border-l-2 border-brand-200 py-2">
                                <p class="text-xs font-semibold text-brand-600 mb-1 flex items-center gap-1.5">
                                    <i class="fas fa-shield-halved text-[10px]"></i>Store Reply
                                </p>
                                <p class="text-sm text-gray-600">{{ $review->admin_reply }}</p>
                            </div>
                        @endif

                        @if(auth()->check() && auth()->id() === $review->user_id)
                            <div class="mt-4 pt-3 border-t border-gray-100">
                                <form action="{{ route('reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Delete this review?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-400 hover:text-red-600 transition-colors flex items-center gap-1" id="delete-review-{{ $review->id }}">
                                        <i class="fas fa-trash text-[10px]"></i>Delete
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12 animate-fade-in">
                <div class="w-16 h-16 rounded-2xl bg-gray-50 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-comment-dots text-2xl text-gray-300"></i>
                </div>
                <p class="text-gray-500 text-sm">No reviews yet. Be the first to share your thoughts!</p>
            </div>
        @endif
    </section>

    {{-- ════════════════════════════════════════════
         RELATED PRODUCTS
    ════════════════════════════════════════════ --}}
    <div class="space-y-14 mb-8">
        <x-shop.product-row title="More from {{ $product->category->name }}" :products="$relatedProducts"
            :link="route('products.index', ['category' => $product->category->slug])" link-label="Shop the collection" />
        <x-shop.product-row title="You may also like" subtitle="Best sellers and new pieces picked for you" :products="$recommendedProducts" />
    </div>
</div>

@push('scripts')
<script>
    // Star rating interactive
    const starLabels = document.querySelectorAll('.star-label');
    function updateStars(value) {
        starLabels.forEach(label => {
            const v = parseInt(label.dataset.value);
            if (v <= value) {
                label.classList.add('border-amber-400', 'bg-amber-50', 'text-amber-400');
                label.classList.remove('border-gray-200', 'text-gray-300');
            } else {
                label.classList.remove('border-amber-400', 'bg-amber-50', 'text-amber-400');
                label.classList.add('border-gray-200', 'text-gray-300');
            }
        });
    }

    starLabels.forEach(label => {
        label.addEventListener('click', () => updateStars(parseInt(label.dataset.value)));
        label.addEventListener('mouseenter', () => updateStars(parseInt(label.dataset.value)));
    });

    const starContainer = document.getElementById('star-rating-container');
    if (starContainer) {
        starContainer.addEventListener('mouseleave', () => {
            const checked = document.querySelector('input[name="rating"]:checked');
            updateStars(checked ? parseInt(checked.value) : 0);
        });
    }

    // Initialize stars if editing
    const checkedRating = document.querySelector('input[name="rating"]:checked');
    if (checkedRating) updateStars(parseInt(checkedRating.value));
</script>
@endpush
@endsection
