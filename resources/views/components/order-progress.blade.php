{{--
    Visual order progress for customers: Processing → Packaged → Out for delivery → Delivered.
    Completed stages show the date they were reached; the current stage is highlighted.
    Cancelled orders show the stages reached before cancelling, then "Cancelled".
--}}
@props(['order'])

@php
    $flow = \App\Models\Order::FLOW;
    $history = $order->statusHistory;
    // First time each stage was reached
    $reached = $history->groupBy('status')->map(fn ($rows) => $rows->min('created_at'));
    $cancelled = $order->status === 'cancelled';
    $currentIndex = $cancelled
        ? collect($flow)->filter(fn ($s) => $reached->has($s))->keys()->max() ?? 0
        : (array_search($order->status, $flow, true) ?: 0);
@endphp

<ol {{ $attributes->merge(['class' => 'order-progress']) }} aria-label="Order progress">
    @foreach($flow as $i => $stage)
        @php
            $done = $i < $currentIndex || (! $cancelled && $i === $currentIndex);
            $current = ! $cancelled && $i === $currentIndex;
            $date = $reached[$stage] ?? ($i === 0 ? $order->created_at : null);
        @endphp
        <li class="order-progress__step {{ $done ? 'is-done' : '' }} {{ $current ? 'is-current' : '' }}" @if($current) aria-current="step" @endif>
            <span class="order-progress__dot" aria-hidden="true">
                @if($done && ! $current)<i class="fas fa-check text-[10px]"></i>@endif
            </span>
            <span class="order-progress__label">{{ \App\Models\Order::STATUSES[$stage]['label'] }}</span>
            <span class="order-progress__date">
                @if($done && $date){{ $date->format('j M, g:i a') }}@elseif($current)Now@else&nbsp;@endif
            </span>
        </li>
    @endforeach
    @if($cancelled)
        <li class="order-progress__step is-cancelled" aria-current="step">
            <span class="order-progress__dot" aria-hidden="true"><i class="fas fa-times text-[10px]"></i></span>
            <span class="order-progress__label">Cancelled</span>
            <span class="order-progress__date">{{ ($reached['cancelled'] ?? $order->updated_at)->format('j M, g:i a') }}</span>
        </li>
    @endif
</ol>
