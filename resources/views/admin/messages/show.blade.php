@extends('layouts.admin')

@section('page_title', 'Message from ' . $message->name)

@section('content')
<div class="max-w-3xl space-y-6">
    <a href="{{ route('admin.messages.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-accent-700 hover:text-accent-800">
        <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i> All messages
    </a>

    <article class="admin-card space-y-6">
        <header class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full bg-cream-200 px-2.5 py-1 font-semibold text-brand-800">{{ $message->topic_label }}</span>
                    @if($message->rating)
                        <span class="text-base text-accent-500" aria-label="{{ $message->rating }} out of 5 stars">
                            @for($i = 1; $i <= 5; $i++)<i class="{{ $i <= $message->rating ? 'fas' : 'far' }} fa-star"></i>@endfor
                        </span>
                    @endif
                </p>
                <h2 class="mt-3 font-serif text-2xl text-brand-900">{{ $message->name }}</h2>
                <p class="text-sm text-gray-500">{{ $message->created_at->format('j M Y, g:i a') }}</p>
            </div>
        </header>

        <dl class="grid grid-cols-1 gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-gray-500">Email</dt><dd><a href="mailto:{{ $message->email }}" class="font-medium text-accent-700 hover:underline">{{ $message->email }}</a></dd></div>
            <div><dt class="text-gray-500">Phone</dt><dd>
                @if($message->phone)<a href="tel:{{ $message->phone }}" class="font-medium text-accent-700 hover:underline">{{ $message->phone }}</a>@else<span class="text-gray-400">Not given</span>@endif
            </dd></div>
            @if($message->order_number)
                <div><dt class="text-gray-500">Order</dt><dd>
                    @php $order = \App\Models\Order::where('order_number', $message->order_number)->first(); @endphp
                    @if($order)
                        <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-accent-700 hover:underline">{{ $message->order_number }}</a>
                    @else
                        <span class="font-medium text-brand-900">{{ $message->order_number }}</span> <span class="text-xs text-gray-400">(not found)</span>
                    @endif
                </dd></div>
            @endif
        </dl>

        <div class="rounded-2xl bg-cream-50 p-5 leading-7 text-gray-700 whitespace-pre-line">{{ $message->message }}</div>

        <div class="flex flex-wrap gap-3 border-t border-cream-200 pt-5">
            <a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: your message to Nasaari') }}" class="btn-brand"><i class="fas fa-reply" aria-hidden="true"></i> Reply by email</a>
            @if($message->phone)
                <a href="https://wa.me/{{ preg_replace('/\D+/', '', $message->phone) }}" target="_blank" rel="noopener" class="btn-secondary"><i class="fab fa-whatsapp" aria-hidden="true"></i> WhatsApp</a>
            @endif
            <form action="{{ route('admin.messages.unread', $message) }}" method="POST">
                @csrf
                <button type="submit" class="btn-secondary">Mark as unread</button>
            </form>
            <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" onsubmit="return confirm('Delete this message?');" class="sm:ml-auto">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger"><i class="fas fa-trash" aria-hidden="true"></i> Delete</button>
            </form>
        </div>
    </article>
</div>
@endsection
