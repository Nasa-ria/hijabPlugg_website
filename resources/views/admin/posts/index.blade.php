@extends('layouts.admin')

@section('page_title', 'Blog Posts')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl text-brand-900">Blog Posts</h1>
            <p class="text-gray-500 mt-2">Share videos, tips and write-ups with your customers.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
            <form action="{{ route('admin.posts.index') }}" method="GET" class="flex w-full sm:w-auto gap-2">
                <select name="type" class="input-field rounded-l-lg">
                    <option value="">All types</option>
                    @foreach(\App\Models\Post::TYPES as $value => $label)
                        <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search posts..." class="input-field">
                <button type="submit" class="btn-brand rounded-r-lg">Search</button>
            </form>
            <a href="{{ route('admin.posts.create') }}" class="btn-brand">
                <i class="fas fa-plus"></i> New Post
            </a>
        </div>
    </div>

    <div class="admin-card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left border-collapse">
                <thead>
                    <tr class="bg-cream-50 text-xs uppercase tracking-[0.15em] text-gray-500 border-b border-cream-300">
                        <th class="px-4 py-4">Post</th>
                        <th class="px-4 py-4">Type</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Engagement</th>
                        <th class="px-4 py-4">Published</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-cream-200">
                    @forelse($posts as $post)
                        <tr class="hover:bg-cream-50 transition-colors">
                            <td class="px-4 py-5 align-top">
                                <div class="flex items-center gap-4">
                                    <div class="w-20 h-14 rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center flex-shrink-0">
                                        @if($post->cover_image)
                                            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="{{ $post->title }}" class="object-cover w-full h-full">
                                        @else
                                            <i class="fas fa-feather-pointed text-gray-400"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $post->title }}</div>
                                        <div class="text-sm text-gray-500">{{ Str::limit($post->excerpt, 70) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-5 align-top text-gray-700">{{ $post->type_label }}</td>
                            <td class="px-4 py-5 align-top">
                                @if($post->isPublished())
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-800 ring-emerald-200">Published</span>
                                @elseif($post->status === 'published')
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset bg-amber-50 text-amber-800 ring-amber-200">Scheduled</span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">Draft</span>
                                @endif
                            </td>
                            <td class="px-4 py-5 align-top text-sm text-gray-600 space-x-3 whitespace-nowrap">
                                <span title="Views"><i class="fas fa-eye mr-1 text-gray-400"></i>{{ $post->views_count }}</span>
                                <span title="Likes"><i class="fas fa-heart mr-1 text-gray-400"></i>{{ $post->likes_count }}</span>
                                <span title="Comments"><i class="fas fa-comment mr-1 text-gray-400"></i>{{ $post->comments_count }}</span>
                                <span title="Shares"><i class="fas fa-share-nodes mr-1 text-gray-400"></i>{{ $post->shares_count }}</span>
                            </td>
                            <td class="px-4 py-5 align-top text-gray-500 text-sm">{{ $post->published_at?->format('M d, Y') ?? '—' }}</td>
                            <td class="px-4 py-5 align-top text-sm space-y-2">
                                <a href="{{ route('blog.show', $post) }}" target="_blank" class="btn-secondary btn-sm"><i class="fas fa-eye"></i> View</a>
                                <a href="{{ route('admin.posts.edit', $post) }}" class="btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <form action="{{ route('admin.posts.destroy', $post) }}" method="POST" onsubmit="return confirm('Delete this post and all its comments?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-500">No posts yet. Create your first video, tip or write-up.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $posts->links() }}
    </div>
</div>
@endsection
