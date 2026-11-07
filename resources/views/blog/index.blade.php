@extends('layouts.app')

@section('title', 'Blog — Nasaari Store')

@section('content')
{{-- Header --}}
<section class="relative overflow-hidden bg-cream-200">
    <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(224,112,138,0.18),_transparent_40%)]"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20 text-center">
        <span class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-white/80 px-4 py-2 text-sm font-semibold text-brand-700">
            <i class="fas fa-feather-pointed text-accent-500"></i>
            The Nasaari Store Journal
        </span>
        <h1 class="mt-5 text-4xl sm:text-5xl font-semibold tracking-tight text-gray-900">Styling tips, videos &amp; stories</h1>
        <p class="mt-4 max-w-2xl mx-auto text-base sm:text-lg text-gray-600">Tutorials, styling inspiration and modest fashion write-ups from our team.</p>

        <form action="{{ route('blog.index') }}" method="GET" class="mt-8 max-w-xl mx-auto flex gap-2">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search the blog..." class="input-field">
            <button type="submit" class="btn-brand px-5" aria-label="Search"><i class="fas fa-search"></i></button>
        </form>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    {{-- Type filter --}}
    <div class="flex flex-wrap items-center justify-center gap-2 mb-10">
        @php
            $filters = ['' => ['All', 'fa-border-all'], 'article' => ['Write-ups', 'fa-pen-nib'], 'video' => ['Videos', 'fa-play'], 'tip' => ['Tips', 'fa-lightbulb']];
        @endphp
        @foreach($filters as $value => [$label, $icon])
            @php $active = request('type', '') === $value; @endphp
            <a href="{{ route('blog.index', array_filter(['type' => $value, 'search' => request('search')])) }}"
               class="bg-accent-500 inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold transition-all duration-200 {{ $active ? 'bg-gradient-to-r text-white shadow-lg shadow-accent-500/25' : 'bg-white text-gray-600 border border-gray-200 hover:text-accent-600 hover:border-accent-200' }}">
                <i class="fas {{ $icon }} text-xs"></i>{{ $label }}
            </a>
        @endforeach
    </div>

    @if($posts->isEmpty())
        <div class="card-panel p-12 text-center">
            <span class="mx-auto w-16 h-16 rounded-2xl bg-accent-50 flex items-center justify-center mb-4">
                <i class="fas fa-feather-pointed text-accent-500 text-2xl"></i>
            </span>
            <h2 class="text-xl font-semibold text-gray-900">No posts yet</h2>
            <p class="text-gray-500 mt-2">Check back soon for styling tips, videos and stories.</p>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($posts as $post)
                @include('blog._card', ['post' => $post])
            @endforeach
        </div>

        <div class="mt-12">
            {{ $posts->links() }}
        </div>
    @endif
</div>
@endsection
