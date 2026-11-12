@extends('layouts.app')

@section('title', $post->title . ' — Nasaari Store')

@push('meta')
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $post->title }}">
    <meta property="og:description" content="{{ $post->excerpt ?? Str::limit(strip_tags($post->body_html), 160) }}">
    <meta property="og:url" content="{{ route('blog.show', $post) }}">
    @if($post->cover_image)
        <meta property="og:image" content="{{ asset('storage/' . $post->cover_image) }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
@endpush

@section('content')
@php
    $shareUrl = urlencode(route('blog.show', $post));
    $shareText = urlencode($post->title);
@endphp

<article class="bg-cream-100">
    {{-- Header --}}
    <header class="max-w-3xl mx-auto px-4 sm:px-6 pt-12 lg:pt-16 text-center">
        <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-brand-600 hover:text-brand-700">
            <i class="fas fa-arrow-left text-xs"></i> Back to blog
        </a>

        @unless($post->isPublished())
            <p class="mt-4 inline-block badge bg-amber-50 text-amber-700 border border-amber-100">Draft — only admins can see this</p>
        @endunless

        <p class="mt-6 text-xs font-semibold uppercase tracking-[0.25em] text-brand-500">{{ $post->type_label }}</p>
        <h1 class="mt-3 text-3xl sm:text-4xl lg:text-5xl font-semibold tracking-tight text-gray-900">{{ $post->title }}</h1>
        @if($post->excerpt)
            <p class="mt-4 text-lg text-gray-600">{{ $post->excerpt }}</p>
        @endif
        <p class="mt-5 text-sm text-gray-400">
            By {{ $post->author->name ?? 'Nasaari Store' }} · {{ $post->published_at?->format('F d, Y') }} · {{ $post->reading_time }} min read · {{ number_format($post->views_count) }} views
        </p>
    </header>

    {{-- Media --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 mt-10">
        @if($post->video_path)
            <video controls preload="metadata" class="w-full rounded-[2rem] shadow-xl shadow-brand-500/10 bg-black"
                   @if($post->cover_image) poster="{{ \App\Support\Img::url($post->cover_image, 960) }}" @endif>
                <source src="{{ asset('storage/' . $post->video_path) }}">
                Your browser does not support video playback.
            </video>
        @elseif($post->video_embed_url)
            <div class="relative aspect-video rounded-[2rem] overflow-hidden shadow-xl shadow-brand-500/10 bg-black">
                <iframe src="{{ $post->video_embed_url }}" title="{{ $post->title }}" class="absolute inset-0 w-full h-full"
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        @elseif($post->cover_image)
            <x-responsive-img :path="$post->cover_image" :alt="$post->title" sizes="(min-width: 1024px) 896px, 92vw" :widths="[480, 720, 960, 1280]" :width="960" :eager="true" class="w-full rounded-[2rem] shadow-xl shadow-brand-500/10 object-cover max-h-[560px]" />
        @endif
    </div>

    {{-- Body --}}
    @if($post->body)
        <div class="max-w-3xl mx-auto px-4 sm:px-6 mt-10">
            <div class="blog-prose {{ $post->type === 'tip' ? 'rounded-[2rem] border border-cream-300 bg-white p-8' : '' }}">
                @if($post->type === 'tip')
                    <p class="flex items-center gap-2 text-sm font-semibold text-brand-700 mb-4"><i class="fas fa-lightbulb text-amber-500"></i> Quick tip</p>
                @endif
                {!! $post->body_html !!}
            </div>
        </div>
    @endif

    {{-- Like & Share bar --}}
    <div class="max-w-3xl mx-auto px-4 sm:px-6 mt-10">
        <div class="flex flex-wrap items-center gap-3 py-5 border-y border-cream-300">
            <button type="button"
                    class="like-btn inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold border transition-all {{ $likedPost ? 'bg-accent-600 text-white border-accent-600' : 'bg-white text-gray-600 border-gray-200 hover:text-accent-600 hover:border-accent-200' }}"
                    data-url="{{ route('blog.like', $post) }}"
                    data-liked="{{ $likedPost ? '1' : '0' }}"
                    data-style="pill">
                <i class="{{ $likedPost ? 'fas' : 'far' }} fa-heart"></i>
                <span class="like-count">{{ $post->likes_count }}</span>
                <span class="hidden sm:inline">{{ Str::plural('Like', $post->likes_count) }}</span>
            </button>

            <a href="#comments" class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold bg-white text-gray-600 border border-gray-200 hover:text-accent-600 hover:border-accent-200 transition-all">
                <i class="far fa-comment"></i> {{ $post->comments_count }}
            </a>

            <div class="flex items-center gap-2 sm:ml-auto">
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 mr-1">Share</span>
                <a href="https://wa.me/?text={{ $shareText }}%20{{ $shareUrl }}" target="_blank" rel="noopener" class="share-link w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-white hover:bg-[#25D366] hover:border-[#25D366] transition-all" aria-label="Share on WhatsApp"><i class="fab fa-whatsapp"></i></a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" class="share-link w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-white hover:bg-[#1877F2] hover:border-[#1877F2] transition-all" aria-label="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://twitter.com/intent/tweet?text={{ $shareText }}&url={{ $shareUrl }}" target="_blank" rel="noopener" class="share-link w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-white hover:bg-black hover:border-black transition-all" aria-label="Share on X"><i class="fab fa-x-twitter"></i></a>
                <a href="https://pinterest.com/pin/create/button/?url={{ $shareUrl }}&description={{ $shareText }}" target="_blank" rel="noopener" class="share-link w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-white hover:bg-[#E60023] hover:border-[#E60023] transition-all" aria-label="Share on Pinterest"><i class="fab fa-pinterest-p"></i></a>
                <button type="button" id="copyLinkBtn" class="w-9 h-9 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-500 hover:text-accent-600 hover:border-accent-200 transition-all" aria-label="Copy link"><i class="fas fa-link"></i></button>
                <button type="button" id="nativeShareBtn" class="hidden w-9 h-9 rounded-full bg-white border border-gray-200 items-center justify-center text-gray-500 hover:text-accent-600 hover:border-accent-200 transition-all" aria-label="More sharing options"><i class="fas fa-share-nodes"></i></button>
            </div>
        </div>
    </div>

    {{-- Comments --}}
    <section id="comments" class="max-w-3xl mx-auto px-4 sm:px-6 py-12">
        <h2 class="text-2xl font-semibold text-gray-900">Comments <span class="text-gray-400 font-normal">({{ $post->comments_count }})</span></h2>

        @auth
            <form action="{{ route('blog.comments.store', $post) }}" method="POST" class="mt-6 flex gap-3">
                @csrf
                <span class="bg-accent-500 flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </span>
                <div class="flex-1">
                    <textarea name="body" rows="3" required maxlength="2000" placeholder="Share your thoughts..." class="input-field resize-none">{{ old('parent_id') ? '' : old('body') }}</textarea>
                    <div class="mt-2 flex justify-end">
                        <button type="submit" class="btn-brand !py-2.5">Post comment</button>
                    </div>
                </div>
            </form>
        @else
            <div class="mt-6 rounded-2xl border border-cream-300 bg-white px-6 py-5 text-sm text-gray-600">
                <a href="{{ route('login') }}" class="font-semibold text-brand-600 hover:text-brand-700">Sign in</a>
                or <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:text-brand-700">create an account</a>
                to like, comment and reply.
            </div>
        @endauth

        <div class="mt-8 space-y-6">
            @forelse($comments as $comment)
                @include('blog._comment', ['comment' => $comment])
            @empty
                <p class="text-sm text-gray-500">No comments yet. Be the first to share your thoughts!</p>
            @endforelse
        </div>
    </section>
</article>

{{-- Related posts --}}
@if($relatedPosts->isNotEmpty())
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <h2 class="section-heading mb-8">More {{ Str::plural(strtolower($post->type_label)) }}</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($relatedPosts as $related)
                @include('blog._card', ['post' => $related])
            @endforeach
        </div>
    </section>
@endif
@endsection

@push('scripts')
<script>
    (function () {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const loginUrl = @json(route('login'));
        const shareUrl = @json(route('blog.share', $post));
        const pageUrl = @json(route('blog.show', $post));
        const pageTitle = @json($post->title);

        const post = (url) => fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        });

        // Likes (post and comments)
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const res = await post(btn.dataset.url);
                if (res.status === 401) { window.location = loginUrl; return; }
                if (!res.ok) return;

                const { liked, count } = await res.json();
                const icon = btn.querySelector('i');
                btn.dataset.liked = liked ? '1' : '0';
                btn.querySelector('.like-count').textContent = count;
                icon.classList.toggle('fas', liked);
                icon.classList.toggle('far', !liked);

                if (btn.dataset.style === 'pill') {
                    btn.classList.toggle('bg-accent-600', liked);
                    btn.classList.toggle('text-white', liked);
                    btn.classList.toggle('border-accent-600', liked);
                    btn.classList.toggle('bg-white', !liked);
                    btn.classList.toggle('text-gray-600', !liked);
                    btn.classList.toggle('border-gray-200', !liked);
                } else {
                    btn.classList.toggle('text-accent-600', liked);
                    btn.classList.toggle('text-gray-400', !liked);
                }
            });
        });

        // Reply forms
        document.querySelectorAll('.reply-toggle').forEach(btn => {
            btn.addEventListener('click', () => {
                const form = document.getElementById(btn.dataset.target);
                if (!form) return;
                form.classList.remove('hidden');
                const textarea = form.querySelector('textarea');
                if (btn.dataset.mention && !textarea.value) textarea.value = btn.dataset.mention;
                textarea.focus();
            });
        });

        // Sharing
        const recordShare = () => post(shareUrl).catch(() => {});
        document.querySelectorAll('.share-link').forEach(a => a.addEventListener('click', recordShare));

        const copyBtn = document.getElementById('copyLinkBtn');
        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(pageUrl);
                copyBtn.innerHTML = '<i class="fas fa-check text-emerald-500"></i>';
                setTimeout(() => copyBtn.innerHTML = '<i class="fas fa-link"></i>', 2000);
                recordShare();
            } catch (e) {
                window.prompt('Copy this link:', pageUrl);
            }
        });

        const nativeBtn = document.getElementById('nativeShareBtn');
        if (navigator.share) {
            nativeBtn.classList.remove('hidden');
            nativeBtn.classList.add('flex');
            nativeBtn.addEventListener('click', async () => {
                try {
                    await navigator.share({ title: pageTitle, url: pageUrl });
                    recordShare();
                } catch (e) { /* user cancelled */ }
            });
        }
    })();
</script>
@endpush
