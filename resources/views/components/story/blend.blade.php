{{--
    Curved edge between two chapters: the "to" colour rises into the
    "from" colour in a soft arch. Solid colours only, no gradient mixing.
    Tones: ink, plum, cream, sand.
    Usage: <x-story.blend from="ink" to="cream" />  (flip="true" arches the other way)
--}}
@props(['from', 'to', 'flip' => false])

<div class="story-blend" aria-hidden="true" style="background: var(--tone-{{ $from }});">
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none" focusable="false">
        @if($flip)
            <path d="M0,0 C480,120 960,120 1440,0 L1440,120 L0,120 Z" fill="var(--tone-{{ $to }})" />
        @else
            <path d="M0,120 C360,10 1080,10 1440,120 Z" fill="var(--tone-{{ $to }})" />
        @endif
    </svg>
</div>
