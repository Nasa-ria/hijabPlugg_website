@extends('layouts.admin')

@section('page_title', 'Messages')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="inline-flex flex-wrap rounded-xl border border-cream-300 bg-white p-1" role="group" aria-label="Filter messages">
            @php
                $filters = ['' => 'All'] + \App\Models\ContactMessage::TOPICS;
                $current = request('topic', '');
            @endphp
            @foreach($filters as $value => $label)
                <a href="{{ route('admin.messages.index', array_filter(['topic' => $value, 'unread' => request('unread')])) }}"
                   class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $current === $value ? 'bg-brand-900 text-white' : 'text-gray-600 hover:bg-cream-200' }}">{{ $label }}</a>
            @endforeach
        </div>
        <div class="flex items-center gap-4 text-sm">
            @if($averageRating)
                <span class="text-gray-600"><i class="fas fa-star text-accent-500" aria-hidden="true"></i> Average feedback <strong class="text-brand-900">{{ number_format($averageRating, 1) }}</strong> / 5</span>
            @endif
            <a href="{{ route('admin.messages.index', array_filter(['topic' => request('topic'), 'unread' => request()->boolean('unread') ? null : 1])) }}"
               class="btn-secondary btn-sm">{{ request()->boolean('unread') ? 'Show all' : 'Unread only' }}</a>
        </div>
    </div>

    <ul class="admin-card !p-0 divide-y divide-cream-200 overflow-hidden">
        @forelse($messages as $message)
            <li>
                <a href="{{ route('admin.messages.show', $message) }}" class="flex flex-col gap-1 px-5 py-4 hover:bg-cream-50 sm:flex-row sm:items-center sm:gap-6 {{ $message->isUnread() ? 'bg-accent-50/40' : '' }}">
                    <div class="flex items-center gap-3 sm:w-56 sm:flex-shrink-0">
                        <span class="h-2 w-2 flex-shrink-0 rounded-full {{ $message->isUnread() ? 'bg-accent-500' : 'bg-transparent' }}" aria-hidden="true"></span>
                        <span class="truncate {{ $message->isUnread() ? 'font-semibold text-brand-900' : 'text-gray-700' }}">{{ $message->name }}</span>
                        @if($message->isUnread())<span class="sr-only">(unread)</span>@endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="flex flex-wrap items-center gap-2 text-xs">
                            <span class="rounded-full bg-cream-200 px-2 py-0.5 font-semibold text-brand-800">{{ $message->topic_label }}</span>
                            @if($message->rating)
                                <span class="text-accent-500" aria-label="{{ $message->rating }} out of 5 stars">
                                    @for($i = 1; $i <= 5; $i++)<i class="{{ $i <= $message->rating ? 'fas' : 'far' }} fa-star"></i>@endfor
                                </span>
                            @endif
                        </p>
                        <p class="mt-1 truncate text-sm text-gray-600">{{ $message->message }}</p>
                    </div>
                    <time class="text-xs text-gray-400 whitespace-nowrap" datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->diffForHumans() }}</time>
                </a>
            </li>
        @empty
            <li class="px-5 py-12 text-center text-gray-500">No messages yet. They'll appear here when customers use the Contact page.</li>
        @endforelse
    </ul>

    <div>{{ $messages->links() }}</div>
</div>
@endsection
