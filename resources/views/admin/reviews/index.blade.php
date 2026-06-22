@extends('layouts.admin')

@section('page_title', 'Reviews')

@section('content')
<div class="mb-6">
    <form method="GET" action="{{ route('admin.reviews.index') }}" class="flex gap-4 mb-4">
        <select name="status" class="px-4 py-2 border border-gray-300 rounded">
            <option value="">All Statuses</option>
            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
            Filter
        </button>
    </form>
</div>

@if($reviews->count() > 0)
    <div class="space-y-6">
        @foreach($reviews as $review)
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold text-lg">{{ $review->product->name }}</h3>
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
                        <span class="px-3 py-1 rounded-full text-sm @if($review->status === 'approved') bg-green-100 text-green-800 @elseif($review->status === 'rejected') bg-red-100 text-red-800 @else bg-yellow-100 text-yellow-800 @endif">
                            {{ ucfirst($review->status) }}
                        </span>
                    </div>
                </div>

                @if($review->title)
                    <h4 class="font-bold mb-2">{{ $review->title }}</h4>
                @endif

                <p class="text-gray-700 mb-4">{{ $review->comment }}</p>

                @if($review->admin_reply)
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                        <p class="font-bold text-blue-900 mb-1">Your Reply:</p>
                        <p class="text-blue-800">{{ $review->admin_reply }}</p>
                    </div>
                @endif

                @if($review->status === 'pending')
                    <div class="flex gap-4">
                        <form method="POST" action="{{ route('admin.reviews.approve', $review) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                                Approve
                            </button>
                        </form>

                        <form method="POST" action="{{ route('admin.reviews.reject', $review) }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                Reject
                            </button>
                        </form>
                    </div>
                @else
                    <div class="flex gap-4">
                        <form method="POST" action="{{ route('admin.reviews.reply', $review) }}" class="inline">
                            @csrf
                            <div class="flex gap-2">
                                <input type="text" name="admin_reply" placeholder="Add a reply..." class="px-3 py-2 border border-gray-300 rounded" required>
                                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                                    Reply
                                </button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Are you sure?')" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
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
    <div class="bg-white rounded-lg shadow-md p-12 text-center">
        <i class="fas fa-star text-6xl text-gray-300 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-600 mb-2">No reviews yet</h2>
        <p class="text-gray-500">Customer reviews will appear here once they start leaving feedback</p>
    </div>
@endif
@endsection
