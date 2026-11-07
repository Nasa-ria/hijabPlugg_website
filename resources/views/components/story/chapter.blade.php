{{--
    A story chapter. The tone drives the backdrop colour (see story.css)
    and the default text colour for the chapter.
    Usage: <x-story.chapter tone="plum" id="origin"> ... </x-story.chapter>
--}}
@props(['tone' => 'cream'])

@php
    $text = in_array($tone, ['ink', 'plum']) ? 'text-cream-100' : 'text-gray-900';
@endphp

<section data-chapter="{{ $tone }}" {{ $attributes->merge(['class' => "relative $text"]) }}>
    <div class="story-chapter-content">
        {{ $slot }}
    </div>
</section>
