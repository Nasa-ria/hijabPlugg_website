 
@extends('layouts.app')

@section('title', 'Hijabis Plugg — Elegant Modest Fashion')

@section('content')
<div class="relative overflow-hidden bg-brand-50">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(139,92,246,0.15),_transparent_35%)]"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-20 lg:pt-20 lg:pb-24">
        <div class="grid grid-cols-1 xl:grid-cols-[1.05fr_0.95fr] gap-10 lg:gap-14 items-center">
            <div class="space-y-6 lg:space-y-8">
                <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-4 py-2 text-sm font-semibold text-brand-700 shadow-sm shadow-brand-500/10">
                    <i class="fas fa-gem text-brand-500"></i>
                    Modern luxury modest fashion
                </span>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-gray-900">Timeless elegance for the modern hijab wardrobe.</h1>
                <p class="max-w-2xl text-base sm:text-lg text-gray-600">Discover premium hijabs, elevated modest staples, and refined essentials designed to feel luxurious, comfortable, and beautifully expressive.</p>

                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <a href="{{ route('products.index') }}" class="btn-brand">Shop the Collection</a>
                    <a href="#story" class="btn-secondary">Our Story</a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="rounded-[1.75rem] border border-white bg-white/90 p-5 shadow-sm shadow-brand-500/5">
                        <p class="text-sm text-gray-500">Luxury fabrics</p>
                        <p class="mt-3 text-lg font-semibold text-gray-900">Soft, breathable, luxe materials.</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-white bg-white/90 p-5 shadow-sm shadow-brand-500/5">
                        <p class="text-sm text-gray-500">Effortless style</p>
                        <p class="mt-3 text-lg font-semibold text-gray-900">Minimal silhouettes with polished details.</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-white bg-white/90 p-5 shadow-sm shadow-brand-500/5">
                        <p class="text-sm text-gray-500">Trusted service</p>
                        <p class="mt-3 text-lg font-semibold text-gray-900">Fast delivery and premium care.</p>
                    </div>
                </div>
            </div>

            <div class="relative rounded-[2rem] overflow-hidden shadow-2xl shadow-brand-500/10">
                <div class="hero-carousel relative h-[520px] sm:h-[560px] lg:h-[640px] bg-gray-100 overflow-hidden">
                    <div class="hero-slide active absolute inset-0 transition-all duration-700">
                        <img src="https://images.unsplash.com/photo-1503342452485-86b7d0d5d5b5?auto=format&fit=crop&w=1200&q=80" alt="Elegant hijab fashion" class="h-full w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent"></div>
                        <div class="absolute bottom-8 left-8 right-8 text-white">
                            <p class="text-sm uppercase tracking-[0.3em] text-white/70">Featured Collection</p>
                            <h2 class="mt-3 text-3xl font-semibold leading-tight">Satin Luxe Hijabs</h2>
                        </div>
                    </div>
                    <div class="hero-slide absolute inset-0 opacity-0 translate-x-4 transition-all duration-700">
                        <img src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1200&q=80" alt="Modest fashion story" class="h-full w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent"></div>
                        <div class="absolute bottom-8 left-8 right-8 text-white">
                            <p class="text-sm uppercase tracking-[0.3em] text-white/70">New Arrival</p>
                            <h2 class="mt-3 text-3xl font-semibold leading-tight">Soft Neutrals Edit</h2>
                        </div>
                    </div>
                    <div class="hero-slide absolute inset-0 opacity-0 translate-x-4 transition-all duration-700">
                        <img src="https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=1200&q=80" alt="Hijab lifestyle" class="h-full w-full object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-black/10 to-transparent"></div>
                        <div class="absolute bottom-8 left-8 right-8 text-white">
                            <p class="text-sm uppercase tracking-[0.3em] text-white/70">Best Seller</p>
                            <h2 class="mt-3 text-3xl font-semibold leading-tight">Tailored Harmony Scarf</h2>
                        </div>
                    </div>
                </div>
                <div class="absolute bottom-6 left-6 right-6 flex items-center justify-between gap-4">
                    <div class="rounded-full bg-white/90 px-4 py-2 text-sm text-gray-700 shadow-sm shadow-black/5">Curated premium pieces that inspire confidence.</div>
                    <div class="inline-flex items-center gap-2">
                        <button type="button" class="carousel-control rounded-full w-10 h-10 bg-white/90 text-gray-700 shadow-sm shadow-black/10 hover:bg-white transition" data-direction="prev" aria-label="Previous slide">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" class="carousel-control rounded-full w-10 h-10 bg-white/90 text-gray-700 shadow-sm shadow-black/10 hover:bg-white transition" data-direction="next" aria-label="Next slide">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section id="story" class="bg-white py-20 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-[0.95fr_1.05fr] gap-12 items-center">
            <div class="space-y-6">
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-brand-500">Our philosophy</p>
                <h2 class="text-3xl sm:text-4xl font-semibold text-gray-900">Modesty, confidence and modern elegance in every design.</h2>
                <p class="text-gray-600 text-base leading-8">At Hijabis Plugg, we design pieces that honor modest dressing while celebrating your personal style. Each collection pairs subtle luxury with everyday comfort, allowing women to feel graceful, polished and empowered.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="rounded-[1.75rem] border border-gray-100 bg-brand-50 p-6 shadow-sm shadow-brand-500/5">
                        <p class="text-sm text-gray-500">Boutique craftsmanship</p>
                        <p class="mt-3 text-lg font-semibold text-gray-900">Thoughtful tailoring and premium finishes.</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-gray-100 bg-brand-50 p-6 shadow-sm shadow-brand-500/5">
                        <p class="text-sm text-gray-500">Timeless design</p>
                        <p class="mt-3 text-lg font-semibold text-gray-900">Versatile silhouettes for every occasion.</p>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-5">
                <div class="group relative overflow-hidden rounded-[2rem] bg-gray-100 shadow-lg shadow-brand-500/10">
                    <img src="https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=900&q=80" alt="Modest lifestyle" class="h-[340px] w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-6 left-6 text-white">
                        <p class="text-sm uppercase tracking-[0.28em]">Modest Living</p>
                        <h3 class="mt-2 text-2xl font-semibold">Styled for everyday grace</h3>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-5">
                    <div class="rounded-[1.75rem] overflow-hidden bg-gray-100 shadow-lg shadow-brand-500/10">
                        <img src="https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=600&q=80" alt="Hijab detail" class="h-[160px] w-full object-cover" />
                    </div>
                    <div class="rounded-[1.75rem] overflow-hidden bg-gray-100 shadow-lg shadow-brand-500/10">
                        <img src="https://images.unsplash.com/photo-1512428559087-560fa5ceab42?auto=format&fit=crop&w=600&q=80" alt="Luxury fabric" class="h-[160px] w-full object-cover" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-brand-50 py-20 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between mb-10">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-brand-500">Collections</p>
                <h2 class="text-3xl sm:text-4xl font-semibold text-gray-900 mt-3">Featured collections</h2>
            </div>
            <a href="{{ route('products.index') }}" class="btn-secondary">View all products</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="{{ route('products.index', ['category' => 'hijabs']) }}" class="collection-card group rounded-[2rem] overflow-hidden border border-white bg-white shadow-xl shadow-brand-500/5 transition hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative h-72 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=900&q=80" alt="Hijabs collection" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                </div>
                <div class="p-6">
                    <p class="text-sm uppercase tracking-[0.3em] text-brand-500">Hooded Silk</p>
                    <h3 class="mt-3 text-2xl font-semibold text-gray-900">Satin Luxe Scarves</h3>
                    <p class="mt-4 text-sm text-gray-500">Soft textures and rich neutral tones for elevated layering.</p>
                </div>
            </a>
            <a href="{{ route('products.index', ['category' => 'tops']) }}" class="collection-card group rounded-[2rem] overflow-hidden border border-white bg-white shadow-xl shadow-brand-500/5 transition hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative h-72 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1495121605193-b116b5b9c5c4?auto=format&fit=crop&w=900&q=80" alt="Modest tops" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                </div>
                <div class="p-6">
                    <p class="text-sm uppercase tracking-[0.3em] text-brand-500">Refined silhouettes</p>
                    <h3 class="mt-3 text-2xl font-semibold text-gray-900">Classic Tunics</h3>
                    <p class="mt-4 text-sm text-gray-500">Lightweight layers with a tailored finish for effortless elegance.</p>
                </div>
            </a>
            <a href="{{ route('products.index', ['category' => 'dresses']) }}" class="collection-card group rounded-[2rem] overflow-hidden border border-white bg-white shadow-xl shadow-brand-500/5 transition hover:-translate-y-1 hover:shadow-2xl">
                <div class="relative h-72 overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=900&q=80" alt="Modest dresses" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/10 to-transparent"></div>
                </div>
                <div class="p-6">
                    <p class="text-sm uppercase tracking-[0.3em] text-brand-500">Bold essentials</p>
                    <h3 class="mt-3 text-2xl font-semibold text-gray-900">Evening layers</h3>
                    <p class="mt-4 text-sm text-gray-500">Statement pieces designed with modesty and movement in mind.</p>
                </div>
            </a>
        </div>
    </div>
</section>

<section class="py-20 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-brand-500">New arrivals</p>
            <h2 class="text-3xl sm:text-4xl font-semibold text-gray-900 mt-4">Discover best sellers & fresh essentials</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="product-highlight rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm shadow-brand-500/5 hover:-translate-y-1 hover:shadow-lg transition">
                <div class="mb-5 h-80 overflow-hidden rounded-[1.75rem]"><img src="https://images.unsplash.com/photo-1520975648227-3597347f8c31?auto=format&fit=crop&w=900&q=80" class="h-full w-full object-cover" alt="Best seller" /></div>
                <p class="text-sm uppercase tracking-[0.3em] text-brand-500">Top seller</p>
                <h3 class="mt-3 text-2xl font-semibold text-gray-900">Silk Drip Hijab</h3>
                <p class="mt-3 text-gray-500">A refined everyday essential with luminous drape and soft hold.</p>
                <div class="mt-5 flex items-center justify-between text-gray-900 font-semibold">
                    <span>From ₵120</span>
                    <a href="{{ route('products.index') }}" class="text-brand-600 hover:text-brand-700">Shop now →</a>
                </div>
            </div>
            <div class="product-highlight rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm shadow-brand-500/5 hover:-translate-y-1 hover:shadow-lg transition">
                <div class="mb-5 h-80 overflow-hidden rounded-[1.75rem]"><img src="https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&fit=crop&w=900&q=80" class="h-full w-full object-cover" alt="New arrival" /></div>
                <p class="text-sm uppercase tracking-[0.3em] text-brand-500">New edit</p>
                <h3 class="mt-3 text-2xl font-semibold text-gray-900">Soft Neutral Set</h3>
                <p class="mt-3 text-gray-500">Lightweight, refined layering that feels polished from day to night.</p>
                <div class="mt-5 flex items-center justify-between text-gray-900 font-semibold">
                    <span>From ₵180</span>
                    <a href="{{ route('products.index') }}" class="text-brand-600 hover:text-brand-700">Explore →</a>
                </div>
            </div>
            <div class="product-highlight rounded-[2rem] border border-gray-100 bg-white p-6 shadow-sm shadow-brand-500/5 hover:-translate-y-1 hover:shadow-lg transition">
                <div class="mb-5 h-80 overflow-hidden rounded-[1.75rem]"><img src="https://images.unsplash.com/photo-1512436991641-6745cdb1723f?auto=format&fit=crop&w=900&q=80" class="h-full w-full object-cover" alt="Luxury modest" /></div>
                <p class="text-sm uppercase tracking-[0.3em] text-brand-500">Editorial</p>
                <h3 class="mt-3 text-2xl font-semibold text-gray-900">Iconic luxe pieces</h3>
                <p class="mt-3 text-gray-500">Curated looks for evenings, events and thoughtful gift giving.</p>
                <div class="mt-5 flex items-center justify-between text-gray-900 font-semibold">
                    <span>From ₵220</span>
                    <a href="{{ route('products.index') }}" class="text-brand-600 hover:text-brand-700">View collection →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-brand-900 text-white py-20 lg:py-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_0.8fr] gap-10 items-center">
            <div>
                <p class="text-sm uppercase tracking-[0.28em] text-brand-300">Testimonials</p>
                <h2 class="mt-4 text-3xl sm:text-4xl font-semibold">Loved by customers for the quality and styling.</h2>
                <p class="mt-4 max-w-2xl text-base text-brand-200">Our community loves the soft drape, comfortable fit and elevated aesthetic that feels both modest and luxurious.</p>
            </div>
            <div class="space-y-5">
                <div class="rounded-[2rem] bg-white/10 p-6 shadow-2xl shadow-black/10 backdrop-blur-sm">
                    <p class="text-sm uppercase tracking-[0.28em] text-brand-300">Sarah R.</p>
                    <p class="mt-4 text-lg font-semibold">“Beautiful quality and perfect fit. The hijab colors feel rich and effortless.”</p>
                    <p class="mt-4 text-sm text-brand-200">“I love how comfortable it feels all day. The customer service was thoughtful and fast.”</p>
                </div>
                <div class="rounded-[2rem] bg-white/10 p-6 shadow-2xl shadow-black/10 backdrop-blur-sm">
                    <p class="text-sm uppercase tracking-[0.28em] text-brand-300">Mariam A.</p>
                    <p class="mt-4 text-lg font-semibold">“A refined modest wardrobe with a polished look. Truly feels premium and special.”</p>
                    <p class="mt-4 text-sm text-brand-200">“The packaging was elegant and the delivery arrived sooner than expected.”</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-20 lg:py-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="rounded-[2rem] border border-gray-100 bg-white p-10 shadow-xl shadow-brand-500/10">
            <p class="text-sm uppercase tracking-[0.28em] text-brand-500">Stay inspired</p>
            <h2 class="mt-4 text-3xl sm:text-4xl font-semibold text-gray-900">Join our newsletter for style edits and exclusive launches.</h2>
            <p class="mt-4 text-gray-600">Receive early access to new collections, styling guides, and curated modest fashion previews.</p>
            @if(session('success'))
                <div class="rounded-3xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-5 py-4 mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="rounded-3xl border border-red-200 bg-red-50 text-red-700 px-5 py-4 mb-4">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="rounded-3xl border border-rose-200 bg-rose-50 text-rose-700 px-5 py-4 mb-4">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form action="{{ route('newsletter.subscribe') ?? url('#') }}" method="POST" class="mt-8 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-4 items-center">
                @csrf
                <input type="email" name="email" required placeholder="Enter your email" class="input-field min-w-0 px-5 py-4" />
                <button type="submit" class="btn-brand px-8">Subscribe</button>
            </form>
            <p class="mt-4 text-sm text-gray-500">No spam, just gentle styling inspiration and product launches.</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    const slides = document.querySelectorAll('.hero-slide');
    const prevButton = document.querySelector('[data-direction="prev"]');
    const nextButton = document.querySelector('[data-direction="next"]');
    let currentIndex = 0;

    function showSlide(index) {
        slides.forEach((slide, idx) => {
            const active = idx === index;
            slide.classList.toggle('active', active);
            slide.style.opacity = active ? '1' : '0';
            slide.style.transform = active ? 'translateX(0)' : 'translateX(30px)';
            slide.style.zIndex = active ? '10' : '0';
        });
    }

    function goNext() {
        currentIndex = (currentIndex + 1) % slides.length;
        showSlide(currentIndex);
    }

    function goPrev() {
        currentIndex = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(currentIndex);
    }

    nextButton?.addEventListener('click', goNext);
    prevButton?.addEventListener('click', goPrev);
    showSlide(currentIndex);
    setInterval(goNext, 6500);
</script>
@endpush
