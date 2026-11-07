{{--
    Dashboard summary tile with change versus the previous period.
    change: percentage (null when there's nothing to compare); good-when-up: false for metrics where lower is better.
--}}
@props(['label', 'value', 'change' => null, 'hint' => null, 'icon' => null, 'goodWhenUp' => true, 'compare' => true])

@php
    $up = $change !== null && $change > 0;
    $down = $change !== null && $change < 0;
    $positive = ($up && $goodWhenUp) || ($down && ! $goodWhenUp);
@endphp

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-cream-300 bg-white p-5']) }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">{{ $label }}</p>
        @if($icon)<i class="fas {{ $icon }} text-accent-500" aria-hidden="true"></i>@endif
    </div>
    <p class="mt-3 font-serif text-3xl text-brand-900">{{ $value }}</p>
    @if($compare)
    <p class="mt-2 flex flex-wrap items-center gap-x-2 text-xs">
        @if($change === null)
            <span class="text-gray-400">No data for previous period</span>
        @else
            <span class="inline-flex items-center gap-1 font-semibold {{ $change == 0 ? 'text-gray-500' : ($positive ? 'text-emerald-700' : 'text-red-700') }}">
                @if($up)<i class="fas fa-arrow-up text-[10px]" aria-hidden="true"></i>@elseif($down)<i class="fas fa-arrow-down text-[10px]" aria-hidden="true"></i>@endif
                {{ number_format(abs($change), 1) }}%
            </span>
            <span class="text-gray-400">vs previous period</span>
        @endif
    </p>
    @endif
    @if($hint)<p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>@endif
</div>
