@extends('layouts.app')

@section('title', $product->name . ' – Hijabis Plugg')

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
         PRODUCT HERO – Image gallery + details
    ════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 mb-16">

        {{-- Image Gallery --}}
        <div class="animate-slide-up">
            <div class="relative rounded-2xl overflow-hidden bg-gray-100 aspect-square mb-4 group">
                @if($product->main_image)
                    <img id="mainImage" src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}"
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                @else
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-brand-50 to-gray-100">
                        <i class="fas fa-image text-6xl text-gray-300"></i>
                    </div>
                @endif

                {{-- Sale badge --}}
                @if($product->sale_price)
                    <div class="absolute top-4 left-4">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-500 text-white text-sm font-bold shadow-lg shadow-red-500/30">
                            <i class="fas fa-bolt text-xs"></i>
                            {{ round((($product->price - $product->sale_price) / $product->price) * 100) }}% OFF
                        </span>
                    </div>
                @endif
            </div>

            {{-- Thumbnail gallery --}}
            @if($product->images->count() > 0)
                <div class="flex gap-3 overflow-x-auto pb-2">
                    @if($product->main_image)
                        <button onclick="document.getElementById('mainImage').src = '{{ asset('storage/' . $product->main_image) }}'"
                                class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-brand-400 ring-2 ring-brand-200/50 transition-all duration-200 hover:opacity-80" id="thumb-main">
                            <img src="{{ asset('storage/' . $product->main_image) }}" alt="Main" class="w-full h-full object-cover">
                        </button>
                    @endif
                    @foreach($product->images as $image)
                        <button onclick="changeImage(this, '{{ asset('storage/' . $image->image_path) }}')"
                                class="flex-shrink-0 w-20 h-20 rounded-xl overflow-hidden border-2 border-gray-200 hover:border-brand-400 transition-all duration-200" id="thumb-{{ $image->id }}">
                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="Product" class="w-full h-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Product Details --}}
        <div class="animate-slide-up" style="animation-delay: 0.1s">

            {{-- Category --}}
            <a href="{{ route('products.index', ['category' => $product->category->slug]) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-brand-50 text-brand-600 text-xs font-semibold hover:bg-brand-100 transition-colors mb-4" id="product-category">
                <i class="fas fa-tag text-[10px]"></i>{{ $product->category->name }}
            </a>

            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4 leading-tight">{{ $product->name }}</h1>

            {{-- Rating --}}
            <div class="flex items-center gap-4 mb-6">
                @if($product->average_rating > 0)
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-0.5">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= round($product->average_rating))
                                    <i class="fas fa-star text-amber-400 text-sm"></i>
                                @else
                                    <i class="far fa-star text-gray-300 text-sm"></i>
                                @endif
                            @endfor
                        </div>
                        <span class="text-sm font-semibold text-gray-700">{{ number_format($product->average_rating, 1) }}</span>
                        <span class="text-sm text-gray-400">({{ $product->total_reviews }} review{{ $product->total_reviews !== 1 ? 's' : '' }})</span>
                    </div>
                @else
                    <span class="text-sm text-gray-400 flex items-center gap-1.5">
                        <i class="far fa-star"></i>No reviews yet
                    </span>
                @endif
            </div>

            {{-- Pricing --}}
            <div class="flex items-baseline gap-3 mb-6 pb-6 border-b border-gray-100">
                @if($product->sale_price)
                    <span class="text-3xl font-bold text-brand-600">₵{{ number_format($product->sale_price, 2) }}</span>
                    <span class="text-xl text-gray-400 line-through">₵{{ number_format($product->price, 2) }}</span>
                    <span class="badge-sale">
                        <i class="fas fa-arrow-down mr-1 text-[10px]"></i>Save ₵{{ number_format($product->price - $product->sale_price, 2) }}
                    </span>
                @else
                    <span class="text-3xl font-bold text-brand-600">₵{{ number_format($product->price, 2) }}</span>
                @endif
            </div>

            {{-- Description --}}
            <div class="mb-6">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Description</h3>
                <p class="text-gray-600 leading-relaxed text-[15px]">{{ $product->description }}</p>
            </div>

            {{-- Stock Status --}}
            <div class="mb-8">
                @if($product->is_available && $product->stock_quantity > 0)
                    <div class="flex items-center gap-2">
                        <span class="badge-stock">
                            <i class="fas fa-check-circle mr-1 text-[10px]"></i>In Stock
                        </span>
                        <span class="text-sm text-gray-400">{{ $product->stock_quantity }} available</span>
                    </div>
                @else
                    <span class="badge-out">
                        <i class="fas fa-times-circle mr-1 text-[10px]"></i>Out of Stock
                    </span>
                @endif
            </div>

            {{-- Add to Cart --}}
            @if($product->is_available && $product->stock_quantity > 0)
                <form action="{{ route('cart.add') }}" method="POST" class="mb-8" id="add-to-cart-form">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="flex items-center gap-4">
                        {{-- Quantity selector --}}
                        <div class="flex items-center rounded-xl border border-gray-200 bg-gray-50 overflow-hidden">
                            <button type="button" onclick="decreaseQty()" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition-all" id="qty-decrease">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="{{ $product->stock_quantity }}"
                                   class="w-12 h-11 text-center text-sm font-semibold bg-transparent border-x border-gray-200 focus:outline-none" readonly>
                            <button type="button" onclick="increaseQty({{ $product->stock_quantity }})" class="w-11 h-11 flex items-center justify-center text-gray-500 hover:text-brand-600 hover:bg-brand-50 transition-all" id="qty-increase">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn-brand flex-1" id="add-to-cart-btn">
                            <i class="fas fa-shopping-bag text-sm"></i>
                            Add to Cart
                        </button>
                    </div>
                </form>
            @else
                <button disabled class="w-full px-8 py-3.5 rounded-xl bg-gray-100 text-gray-400 font-semibold cursor-not-allowed mb-8 text-sm" id="out-of-stock-btn">
                    <i class="fas fa-ban mr-2 text-xs"></i>Out of Stock
                </button>
            @endif

            {{-- Trust signals --}}
            <div class="grid grid-cols-3 gap-3">
                <div class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl bg-gray-50 border border-gray-100">
                    <i class="fas fa-truck text-brand-400 text-sm"></i>
                    <span class="text-[11px] font-medium text-gray-500 text-center">Fast Delivery</span>
                </div>
                <div class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl bg-gray-50 border border-gray-100">
                    <i class="fas fa-shield-halved text-brand-400 text-sm"></i>
                    <span class="text-[11px] font-medium text-gray-500 text-center">Secure Payment</span>
                </div>
                <div class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl bg-gray-50 border border-gray-100">
                    <i class="fas fa-rotate-left text-brand-400 text-sm"></i>
                    <span class="text-[11px] font-medium text-gray-500 text-center">Easy Returns</span>
                </div>
            </div>

            {{-- Share --}}
            <div class="mt-6 pt-6 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Share</p>
                <div class="flex gap-2">
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-blue-50 border border-gray-100 hover:border-blue-200 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-all duration-200" aria-label="Share on Facebook">
                        <i class="fab fa-facebook-f text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-sky-50 border border-gray-100 hover:border-sky-200 flex items-center justify-center text-gray-400 hover:text-sky-500 transition-all duration-200" aria-label="Share on Twitter">
                        <i class="fab fa-twitter text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-pink-50 border border-gray-100 hover:border-pink-200 flex items-center justify-center text-gray-400 hover:text-pink-500 transition-all duration-200" aria-label="Share on Instagram">
                        <i class="fab fa-instagram text-sm"></i>
                    </a>
                    <a href="#" class="w-9 h-9 rounded-lg bg-gray-50 hover:bg-green-50 border border-gray-100 hover:border-green-200 flex items-center justify-center text-gray-400 hover:text-green-600 transition-all duration-200" aria-label="Share on WhatsApp">
                        <i class="fab fa-whatsapp text-sm"></i>
                    </a>
                </div>
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
            <div class="flex items-center gap-4 px-6 py-5 rounded-2xl bg-brand-50 border border-brand-100 mb-8 animate-fade-in">
                <span class="w-10 h-10 rounded-xl bg-brand-100 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user-lock text-brand-500 text-sm"></i>
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
                                <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center text-white text-sm font-bold shadow-md">
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
    @if($relatedProducts->count() > 0)
        <section class="mb-8">
            <h2 class="section-heading mb-6">You May Also Like</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-5">
                @foreach($relatedProducts as $related)
                    <a href="{{ route('products.show', $related) }}" class="stagger-item group" id="related-{{ $related->id }}">
                        <div class="product-card h-full flex flex-col">
                            <div class="aspect-[4/5] img-zoom bg-gray-100">
                                @if($related->main_image)
                                    <img src="{{ asset('storage/' . $related->main_image) }}" alt="{{ $related->name }}"
                                         class="w-full h-full object-cover" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-brand-50 to-gray-100">
                                        <i class="fas fa-image text-2xl text-gray-300"></i>
                                    </div>
                                @endif
                            </div>
                            <div class="p-3.5 flex-1 flex flex-col">
                                <h3 class="font-semibold text-sm text-gray-800 group-hover:text-brand-600 transition-colors mb-1 line-clamp-2">{{ Str::limit($related->name, 35) }}</h3>
                                <div class="mt-auto flex items-center justify-between">
                                    <span class="font-bold text-brand-600 text-sm">₵{{ number_format($related->sale_price ?? $related->price, 2) }}</span>
                                    @if($related->average_rating > 0)
                                        <div class="flex items-center gap-0.5 text-[11px]">
                                            <i class="fas fa-star text-amber-400"></i>
                                            <span class="font-medium text-gray-500">{{ number_format($related->average_rating, 1) }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>

@push('scripts')
<script>
    // Quantity controls
    function increaseQty(max) {
        const qty = document.getElementById('quantity');
        if (parseInt(qty.value) < max) qty.value = parseInt(qty.value) + 1;
    }
    function decreaseQty() {
        const qty = document.getElementById('quantity');
        if (parseInt(qty.value) > 1) qty.value = parseInt(qty.value) - 1;
    }

    // Image gallery
    function changeImage(btn, src) {
        document.getElementById('mainImage').src = src;
        // Update border styles
        document.querySelectorAll('[id^="thumb-"]').forEach(t => {
            t.classList.remove('border-brand-400', 'ring-2', 'ring-brand-200/50');
            t.classList.add('border-gray-200');
        });
        btn.classList.add('border-brand-400', 'ring-2', 'ring-brand-200/50');
        btn.classList.remove('border-gray-200');
    }

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
