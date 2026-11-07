{{--
    Status history of an order, newest first, with date and time of each change.
    show-emails: also show whether the customer was emailed (admin only), with a Resend button on failures.
--}}
@props(['order', 'showEmails' => true])

@php $history = $order->statusHistory->sortByDesc(fn ($h) => [$h->created_at, $h->id])->values(); @endphp

<ol {{ $attributes->merge(['class' => 'relative space-y-6 border-l border-cream-300 pl-6']) }}>
    @forelse($history as $entry)
        <li class="relative">
            <span class="absolute -left-[31px] top-1 h-3.5 w-3.5 rounded-full ring-4 ring-cream-50 {{ $loop->first ? 'bg-accent-500' : 'bg-cream-400' }}" aria-hidden="true"></span>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <p class="font-semibold text-brand-900">{{ $entry->status_label }}</p>
                <time class="text-sm text-gray-500" datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('j M Y, g:i a') }}</time>
            </div>

            @if($showEmails)
                <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                    @if($entry->changedBy)
                        <span class="text-gray-500">by {{ $entry->changedBy->name }}</span>
                    @endif
                    @switch($entry->email_status)
                        @case('sent')
                            <span class="text-emerald-700"><i class="fas fa-envelope-circle-check mr-1" aria-hidden="true"></i>Customer emailed {{ $entry->emailed_at?->format('g:i a') }}</span>
                            @break
                        @case('failed')
                            <span class="text-red-700"><i class="fas fa-triangle-exclamation mr-1" aria-hidden="true"></i>Email failed: {{ $entry->email_error }}</span>
                            <form action="{{ route('admin.orders.resend-email', [$order, $entry]) }}" method="POST">
                                @csrf
                                <button type="submit" class="font-semibold text-accent-700 underline underline-offset-2 hover:text-accent-800">Resend email</button>
                            </form>
                            @break
                        @default
                            <span class="text-gray-400">No email sent{{ $entry->email_error ? ' (' . $entry->email_error . ')' : '' }}</span>
                    @endswitch
                </div>
            @endif
        </li>
    @empty
        <li class="text-sm text-gray-500">No status changes yet.</li>
    @endforelse
</ol>
