@php
    $typeStyles = [
        'article' => ['fa-pen-nib', 'bg-cream-100 text-brand-700 border-cream-300'],
        'video' => ['fa-play', 'bg-accent-50 text-accent-700 border-accent-100'],
        'tip' => ['fa-lightbulb', 'bg-amber-50 text-amber-700 border-amber-100'],
    ];
    [$typeIcon, $typeClass] = $typeStyles[$post->type] ?? $typeStyles['article'];
@endphp
<article class="product-card stagger-item flex flex-col">
    <a href="{{ route('blog.show', $post) }}" class="img-zoom relative block aspect-[16/10] bg-cream-200">
        @if($post->cover_image)
            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
        @elseif($post->video_embed_url && str_contains($post->video_embed_url, 'youtube.com'))
            <img src="https://img.youtube.com/vi/{{ basename($post->video_embed_url) }}/hqdefault.jpg" alt="{{ $post->title }}" class="w-full h-full object-cover">
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-cream-200 to-brand-100">
                <i class="fas {{ $typeIcon }} text-4xl text-accent-400"></i>
            </div>
        @endif

        @if($post->type === 'video')
            <span class="absolute inset-0 flex items-center justify-center">
                <span class="w-14 h-14 rounded-full bg-white/90 flex items-center justify-center shadow-lg">
                    <i class="fas fa-play text-accent-600 ml-1"></i>
                </span>
            </span>
        @endif

        <span class="absolute top-4 left-4 badge border {{ $typeClass }}">
            <i class="fas {{ $typeIcon }} mr-1.5 text-[10px]"></i>{{ $post->type_label }}
        </span>
    </a>

    <div class="p-6 flex flex-col flex-1">
        <p class="text-xs text-gray-400">{{ $post->published_at?->format('M d, Y') }} · {{ $post->reading_time }} min read</p>
        <h2 class="mt-2 text-lg font-semibold text-gray-900 leading-snug">
            <a href="{{ route('blog.show', $post) }}" class="hover:text-brand-700 transition-colors">{{ $post->title }}</a>
        </h2>
        @if($post->excerpt)
            <p class="mt-2 text-sm text-gray-500 line-clamp-2">{{ $post->excerpt }}</p>
        @endif

        <div class="mt-auto pt-5 flex items-center gap-4 text-xs text-gray-400">
            <span><i class="fas fa-heart mr-1"></i>{{ $post->likes_count ?? 0 }}</span>
            <span><i class="fas fa-comment mr-1"></i>{{ $post->comments_count ?? 0 }}</span>
            <span><i class="fas fa-share-nodes mr-1"></i>{{ $post->shares_count }}</span>
            <a href="{{ route('blog.show', $post) }}" class="ml-auto font-semibold text-accent-600 hover:text-accent-700">Read <i class="fas fa-arrow-right ml-1"></i></a>
        </div>
    </div>
</article>
