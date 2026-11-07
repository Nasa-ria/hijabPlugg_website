@extends('layouts.app')

@section('title', 'Nasaari Store — Woven with intention, worn with grace')

@php
    // Placeholder photography (Unsplash). Swap for Nasaari's own shots when ready.
    $img = fn (string $id, int $w = 900) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$w}&q=75";
    $heroImage = $img('1693985007521-e08e64770947', 1100);

    // Collection tiles fill the product row while the catalogue grows
    $collections = [
        ['title' => 'Everyday Chiffon', 'subtitle' => 'Light & breathable', 'image' => $img('1585728748176-455ac5eed962', 700)],
        ['title' => 'Satin Occasion', 'subtitle' => 'Quiet sheen', 'image' => $img('1542380841-5eef57349ca1', 700)],
        ['title' => 'Jersey Essentials', 'subtitle' => 'Soft, no-slip', 'image' => $img('1613447895817-e617a4093f50', 700)],
        ['title' => 'Modest Wear', 'subtitle' => 'Tailored ease', 'image' => $img('1640154852340-9de73a0643a8', 700)],
    ];
    $tilesNeeded = max(0, 4 - $products->count());
@endphp

@push('meta')
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500;600&family=Playfair+Display:ital,wght@0,500;1,500&display=swap" rel="stylesheet">
    <link rel="preload" as="image" href="{{ $heroImage }}" imagesrcset="{{ $img('1693985007521-e08e64770947', 600) }} 600w, {{ $heroImage }} 1100w" imagesizes="(min-width: 1024px) 45vw, 92vw" fetchpriority="high">
    {{-- Hide reveal targets only once JS is coming; fall back to visible after 2.5s if it never runs --}}
    <script>
        document.documentElement.classList.add('story-js');
        setTimeout(function () { if (!window.__storyReady) document.documentElement.classList.remove('story-js'); }, 2500);
    </script>
@endpush

@section('content')
<div class="story" data-story>

    {{-- Signature moment: the thread drawn through every chapter --}}
    <x-story.thread />

    {{-- ═══════════ CHAPTER 1 · OPENING ═══════════ --}}
    <x-story.chapter tone="ink" class="overflow-hidden">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-14 pb-20 lg:pt-20 lg:pb-28 min-h-[calc(100svh-72px)] grid grid-cols-1 lg:grid-cols-[1.1fr_0.9fr] gap-12 lg:gap-16 items-center">
            <div>
                <p class="story-onload text-xs font-semibold uppercase tracking-[0.3em] text-brand-ivory/70" style="--i: 0">
                    Nasaari Store · Hijabs | modesty &amp; more
                </p>

                <h1 class="story-lines mt-6 font-serif text-5xl sm:text-6xl lg:text-7xl font-medium leading-[1.02] text-brand-ivory">
                    <span class="line"><span style="--i: 0">Woven with intention,</span></span>
                    <span class="line"><span style="--i: 1" class="italic text-cream-200/90">worn with grace.</span></span>
                </h1>

                <p class="story-onload mt-6 max-w-xl text-base sm:text-lg leading-8 text-cream-200/80" style="--i: 2">
                    Every Nasaari piece begins as a single thread and becomes something you'll reach for for years.
                </p>

                {{-- Shopping first: these buttons are never animated or delayed --}}
                <div class="mt-9 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                    <a href="{{ route('products.index') }}" class="btn-brand px-8">Shop the Collection</a>
                    <a href="#origin" class="story-link text-sm font-semibold text-brand-ivory">
                        Read our story <i class="fas fa-arrow-down text-xs" aria-hidden="true"></i>
                    </a>
                </div>

                <span class="mt-16 hidden md:block h-0" data-thread-anchor="0.07" aria-hidden="true"></span>
            </div>

            <div class="story-curtain-load relative rounded-[2rem] overflow-hidden aspect-[4/5] max-h-[78vh] w-full max-w-md lg:max-w-none mx-auto bg-brand-900">
                <img src="{{ $heroImage }}" srcset="{{ $img('1693985007521-e08e64770947', 600) }} 600w, {{ $heroImage }} 1100w" sizes="(min-width: 1024px) 45vw, 92vw" alt="A woman in a plum hijab among blossoms" width="1100" height="1375"
                     fetchpriority="high" decoding="async" class="h-full w-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-brand-dark/50 via-transparent to-transparent" aria-hidden="true"></div>
            </div>
        </div>
    </x-story.chapter>

    <x-story.blend from="ink" to="cream" />

    {{-- ═══════════ CHAPTER 2 · ORIGIN ═══════════ --}}
    <x-story.chapter tone="cream" id="origin" class="pt-16 pb-12 lg:pt-24 lg:pb-16 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-2 gap-14 lg:gap-24 items-center">
            <div class="max-w-xl">
                <x-story.eyebrow number="01" data-reveal>Origin</x-story.eyebrow>
                <h2 class="mt-6 font-serif text-4xl sm:text-5xl font-medium leading-tight text-brand-dark" data-reveal="soft" style="--i: 1">
                    It begins with a single thread.
                </h2>
                <p class="mt-6 text-base sm:text-lg leading-8 text-gray-600" data-reveal style="--i: 2">
                    Before anything is cut, we choose the fabric by hand: airy chiffon, soft modal jersey, satin with a quiet sheen.
                    If it doesn't breathe, drape and feel right against the skin, it doesn't become a Nasaari piece.
                </p>
                <p class="mt-4 text-base leading-8 text-gray-500" data-reveal style="--i: 3">
                    Fewer pieces, chosen slowly, so the ones you keep earn their place.
                </p>
            </div>

            <div class="relative grid grid-cols-2 gap-5 items-start">
                <span class="absolute -left-10 top-1/2 hidden lg:block h-0" data-thread-anchor="0.5" aria-hidden="true"></span>
                <div class="rounded-[1.75rem] overflow-hidden aspect-[3/4] bg-cream-200" data-parallax="0.06">
                    <img src="{{ $img('1613611927458-3ddd4b0afdb9', 700) }}" alt="Soft white hijab fabric" width="700" height="933" loading="lazy" decoding="async" class="h-full w-full object-cover">
                </div>
                <div class="mt-16 rounded-[1.75rem] overflow-hidden aspect-[3/4] bg-cream-200" data-parallax="0.14">
                    <img src="{{ $img('1662806407800-56793fa8e924', 700) }}" alt="Hijab and robe in rich tones by the water" width="700" height="933" loading="lazy" decoding="async" class="h-full w-full object-cover">
                </div>
            </div>
        </div>
        <span class="mt-8 hidden md:block h-0" data-thread-anchor="0.9" aria-hidden="true"></span>
    </x-story.chapter>

    {{-- ═══════════ CHAPTER 3 · CRAFT ═══════════ --}}
    <x-story.chapter tone="cream" class="font-craft pt-12 pb-24 lg:pt-20 lg:pb-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="max-w-2xl">
                <x-story.eyebrow number="02" data-reveal>Craft</x-story.eyebrow>
                <h2 class="mt-6 font-craft-display text-4xl sm:text-5xl font-medium leading-tight text-brand-dark" data-reveal="soft" style="--i: 1">
                    Shaped slowly, finished by hand.
                </h2>
                <p class="mt-6 text-base sm:text-lg leading-8 text-gray-600" data-reveal style="--i: 2">
                    Three quiet steps take raw fabric to a piece you'll wear for years.
                </p>
            </div>


            @php
                $steps = [
                    ['Chosen', 'Selected for drape and breathability, then washed and tested for colour before it ever reaches you.'],
                    ['Cut', 'Cut generously so it falls softly around the face and shoulders, never clinging, never slipping.'],
                    ['Finished', 'Edges finished by hand and every piece checked, folded and wrapped with care.'],
                ];
            @endphp
            {{-- Staircase: each block sits one step lower (and, on phones, further right) and arrives in turn --}}
            @php
                $stairs = ['', 'ml-10 md:ml-0 md:mt-32', 'ml-20 md:ml-0 md:mt-64'];
            @endphp
            <ol class="relative mt-16 grid grid-cols-1 md:grid-cols-3 gap-14 md:gap-16 items-start">
                {{-- Like Origin: the thread sweeps through open space, down the gap between steps 2 and 3, never over text --}}
                <span class="absolute hidden md:block w-0 h-0 top-20" style="left: calc((100% - 8rem) * 2 / 3 + 6rem)" data-thread-anchor="self" aria-hidden="true"></span>
                <span class="absolute hidden md:block w-0 h-0 bottom-6" style="left: calc((100% - 8rem) * 2 / 3 + 6rem)" data-thread-anchor="self" aria-hidden="true"></span>
                @foreach($steps as $i => [$title, $text])
                    <li class="{{ $stairs[$i] }}"
                        data-reveal style="--i: {{ $i * 3 }}">
                        <p class="flex items-center gap-4" aria-hidden="true">
                            <span class="font-craft-display italic font-medium text-4xl md:text-5xl leading-none text-accent-500">{{ $i + 1 }}</span>
                            <span class="h-px w-14 bg-accent-300"></span>
                        </p>
                        <h3 class="mt-4 text-2xl font-semibold text-brand-dark">{{ $title }}</h3>
                        <p class="mt-2 leading-7 text-gray-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </x-story.chapter>

    {{-- ═══════════ CHAPTER 4 · RESULT ═══════════ --}}
    <x-story.chapter tone="sand" class="py-24 lg:py-32">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                <div class="max-w-2xl">
                    <x-story.eyebrow number="03" data-reveal>The collection</x-story.eyebrow>
                    <h2 class="mt-6 font-serif text-4xl sm:text-5xl font-medium leading-tight text-brand-dark" data-reveal="soft" style="--i: 1">
                        Made to be kept.
                    </h2>
                </div>
                <a href="{{ route('products.index') }}" class="story-link text-sm font-semibold text-brand-700">
                    View all pieces <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                </a>
            </div>

            <span class="mt-6 hidden md:block h-0" data-thread-anchor="0.6" aria-hidden="true"></span>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($products as $product)
                    <x-story.product-card :product="$product" />
                @endforeach
                @foreach(array_slice($collections, 0, $tilesNeeded) as $i => $collection)
                    <x-story.collection-tile :title="$collection['title']" :subtitle="$collection['subtitle']"
                        :image="$collection['image']" :href="route('products.index')" :index="$i" />
                @endforeach
            </div>

            {{-- Testimonials as quiet proof --}}
            <span class="mt-16 md:mt-24 hidden md:block h-0" data-thread-anchor="0.3" aria-hidden="true"></span>

            <div class="relative mt-16 md:mt-20 grid grid-cols-1 md:grid-cols-2 gap-6">
                <span class="absolute hidden md:block w-0 h-0 left-1/2 top-1/2" data-thread-anchor="self" aria-hidden="true"></span>
                @php
                    $quotes = [
                        ['Beautiful quality and perfect fit. The colours feel rich and effortless, and it stays put all day.', 'Sarah R.'],
                        ['A refined modest wardrobe with a polished look. The packaging was elegant and it arrived sooner than expected.', 'Mariam A.'],
                    ];
                @endphp
                @foreach($quotes as $i => [$quote, $name])
                    <figure class="rounded-[1.75rem] border border-cream-300 bg-cream-50/70 p-8" data-reveal style="--i: {{ $i }}">
                        <span class="block h-12 text-[6.5rem] leading-[0.85] text-accent-500 select-none" style="font-family: Georgia, 'Times New Roman', serif;" aria-hidden="true">&ldquo;</span>
                        <blockquote class="mt-3 font-serif text-xl leading-relaxed text-brand-dark">{{ $quote }}</blockquote>
                        <figcaption class="mt-5 text-xs font-semibold uppercase tracking-[0.28em] text-brand-600">{{ $name }}</figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
        <span class="mt-16 hidden md:block h-0" data-thread-anchor="0.5" aria-hidden="true"></span>
    </x-story.chapter>

    <x-story.blend from="sand" to="ink" flip />

    {{-- ═══════════ CHAPTER 5 · INVITATION ═══════════ --}}
    <x-story.chapter tone="ink" class="py-24 lg:py-36">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <span class="hidden md:block h-0" data-thread-anchor="0.5" aria-hidden="true"></span>
            <x-story.signature class="mx-auto max-w-sm md:max-w-md" />

            <h2 class="mt-4 font-serif text-4xl sm:text-5xl font-medium text-brand-ivory" data-reveal="soft">Yours for years.</h2>
            <p class="mt-5 text-base sm:text-lg leading-8 text-cream-200/80" data-reveal style="--i: 1">
                Hijabs, accessories and modest pieces chosen with care. Find the one you'll keep reaching for.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('products.index') }}" class="btn-brand px-10 py-4 text-base">Shop the Collection</a>
                <a href="{{ route('blog.index') }}" class="story-btn-outline inline-flex items-center gap-2 px-6 py-4 rounded-xl text-sm font-semibold text-brand-ivory border border-brand-ivory/40">
                    <i class="fas fa-feather-pointed text-accent-300" aria-hidden="true"></i> Styling tips &amp; videos
                </a>
            </div>

            {{-- Social media (set in .env, see config/nasaari.php) --}}
            @if(collect(config('nasaari.social'))->filter()->isNotEmpty())
                <div class="mt-12 flex flex-col items-center gap-4" data-reveal>
                    <p class="text-xs font-semibold uppercase tracking-[0.3em] text-brand-ivory/70">
                        Follow along{{ config('nasaari.handle') ? ' · ' . config('nasaari.handle') : '' }}
                    </p>
                    <x-social-links size="lg" class="justify-center" />
                </div>
            @endif

            @if($latestPosts->isNotEmpty())
                <div class="mt-14 grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                    @foreach($latestPosts as $i => $post)
                        <a href="{{ route('blog.show', $post) }}" class="story-card block rounded-2xl border border-white/10 bg-white/5 p-5" data-reveal style="--i: {{ $i }}">
                            <span class="text-xs uppercase tracking-[0.25em] text-accent-300">{{ $post->type_label }}</span>
                            <span class="mt-2 block font-semibold text-cream-100">{{ $post->title }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            {{-- Newsletter --}}
            <div class="mt-16 rounded-[2rem] border border-white/10 bg-white/5 p-8 sm:p-10" data-reveal>
                <h3 class="font-serif text-2xl text-brand-ivory">Letters from Nasaari</h3>
                <p class="mt-2 text-sm text-cream-200/70">New pieces, styling notes and early access. Never noisy.</p>
                @if(session('success'))
                    <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 text-emerald-700 px-5 py-3 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mt-4 rounded-2xl border border-red-200 bg-red-50 text-red-700 px-5 py-3 text-sm">{{ session('error') }}</div>
                @endif
                <form action="{{ route('newsletter.subscribe') }}" method="POST" class="mt-6 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3">
                    @csrf
                    <label for="newsletter-email" class="sr-only">Email address</label>
                    <input id="newsletter-email" type="email" name="email" required placeholder="Your email address" class="input-field min-w-0 !py-3.5">
                    <button type="submit" class="btn-brand px-8">Subscribe</button>
                </form>
            </div>
        </div>
    </x-story.chapter>
</div>
@endsection
