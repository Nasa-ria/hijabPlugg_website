{{-- A single comment; top-level comments also render their replies and a reply form --}}
@php $isReply = (bool) $comment->parent_id; @endphp
<div id="comment-{{ $comment->id }}" class="flex gap-3 {{ $isReply ? 'mt-4' : '' }}">
    <span class="bg-accent-500 flex-shrink-0 {{ $isReply ? 'w-8 h-8 text-xs' : 'w-10 h-10 text-sm' }} rounded-xl flex items-center justify-center text-white font-bold">
        {{ strtoupper(substr($comment->user->name ?? '?', 0, 1)) }}
    </span>

    <div class="flex-1 min-w-0">
        <div class="rounded-2xl {{ $isReply ? 'bg-cream-100' : 'bg-white border border-gray-100' }} px-4 py-3">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-sm font-semibold text-gray-900">{{ $comment->user->name ?? 'Deleted user' }}</span>
                @if($comment->user?->isAdmin())
                    <span class="badge bg-cream-200 text-brand-700 border border-cream-300 !py-0.5">Nasaari Store</span>
                @endif
                <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <p class="mt-1 text-sm text-gray-700 whitespace-pre-line break-words">{{ $comment->body }}</p>
        </div>

        <div class="flex items-center gap-4 mt-1.5 ml-2 text-xs font-semibold">
            @php $liked = in_array($comment->id, $likedCommentIds); @endphp
            <button type="button"
                    class="like-btn inline-flex items-center gap-1 transition-colors {{ $liked ? 'text-accent-600' : 'text-gray-400 hover:text-accent-600' }}"
                    data-url="{{ route('blog.comments.like', $comment) }}"
                    data-liked="{{ $liked ? '1' : '0' }}">
                <i class="{{ $liked ? 'fas' : 'far' }} fa-heart"></i>
                <span class="like-count">{{ $comment->likes_count }}</span>
            </button>

            @auth
                <button type="button" class="reply-toggle text-gray-400 hover:text-accent-600 transition-colors"
                        data-target="reply-form-{{ $isReply ? $comment->parent_id : $comment->id }}"
                        data-mention="{{ $isReply ? '@' . $comment->user?->name . ' ' : '' }}">
                    Reply
                </button>
            @else
                <a href="{{ route('login') }}" class="text-gray-400 hover:text-brand-600">Reply</a>
            @endauth

            @if($comment->canBeDeletedBy(auth()->user()))
                <form action="{{ route('blog.comments.destroy', $comment) }}" method="POST" onsubmit="return confirm('Delete this comment?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors">Delete</button>
                </form>
            @endif
        </div>

        @unless($isReply)
            @foreach($comment->replies as $reply)
                @include('blog._comment', ['comment' => $reply])
            @endforeach

            @auth
                <form id="reply-form-{{ $comment->id }}" action="{{ route('blog.comments.store', $post) }}" method="POST" class="hidden mt-3 flex gap-2">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <textarea name="body" rows="1" required maxlength="2000" placeholder="Write a reply..." class="input-field resize-none !py-2"></textarea>
                    <button type="submit" class="btn-brand !px-4 !py-2" aria-label="Send reply"><i class="fas fa-paper-plane"></i></button>
                </form>
            @endauth
        @endunless
    </div>
</div>
