@extends('layouts.admin')

@section('page_title', 'Reviews')

@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex gap-4 mb-4">
        <select name="status" class="input-field !py-2">
            <option value="">All Statuses</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <button type="submit" class="btn-brand btn-sm">
            Filter
        </button>
    </form>
</div>

@if($reviews->count() > 0)
    <div class="space-y-6">
        @foreach($reviews as $review)
            <div class="admin-card">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-semibold text-lg text-brand-900">{{ $review->product->name }}</h3>
                        <p class="text-gray-600">By {{ $review->user->name }} • {{ $review->created_at->diffForHumans() }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-yellow-500 mb-2">
                            @for($i = 0; $i < $review->rating; $i++)
                                <i class="fas fa-star"></i>
                            @endfor
                            @for($i = $review->rating; $i < 5; $i++)
                                <i class="far fa-star"></i>
                            @endfor
                        </div>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset @if($review->status === 'approved') bg-emerald-50 text-emerald-800 ring-emerald-200 @elseif($review->status === 'rejected') bg-gray-100 text-gray-600 ring-gray-300 @else bg-cream-200 text-brand-800 ring-cream-400 @endif">
                            {{ ucfirst($review->status) }}
                        </span>
                    </div>
                </div>

                @if($review->title)
                    <h4 class="mb-2 font-semibold text-brand-900">{{ $review->title }}</h4>
                @endif

                <p class="text-gray-700 mb-4">{{ $review->comment }}</p>

                @if($review->admin_reply)
                    <div class="mb-4 rounded-xl border-l-4 border-accent-500 bg-cream-100 p-4">
                        <p class="mb-1 text-sm font-semibold text-brand-900">Your reply</p>
                        <p class="text-gray-700">{{ $review->admin_reply }}</p>
                    </div>
                @endif

                @if($review->status === 'pending')
                    <div class="flex flex-wrap gap-3">
                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-brand btn-sm">
                                Approve
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="inline">
                            @csrf
                            <button type="submit" class="btn-danger btn-sm">
                                Reject
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex flex-wrap items-center gap-3">
                        <form method="POST" action="{{ route('admin.reviews.reply', $review) }}" class="flex-1 min-w-[16rem]">
                            @csrf
                            <div class="flex gap-2">
                                <input type="text" name="admin_reply" placeholder="Write a reply to the customer…" aria-label="Reply" class="input-field !py-2" required>
                                <button type="submit" class="btn-brand btn-sm">
                                    Reply
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Are you sure?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm">
                                Delete
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $reviews->links() }}
    </div>
@else
    <div class="admin-card !p-12 text-center">
        <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-600 mb-2">No reviews yet</h2>
        <p class="text-gray-500">Customer reviews will appear here once they start leaving feedback</p>
    </div>
@endif
@endsection
