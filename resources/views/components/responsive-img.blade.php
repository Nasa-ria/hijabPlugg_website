{{--
    A stored photo at the size it is displayed (compressed WebP copies, see App\Support\Img).
      path   path on the public disk, e.g. products/abc.jpg
      sizes  how wide the image shows, e.g. "(min-width: 1024px) 25vw, 50vw"
      width  fallback size for browsers without srcset
      eager  load straight away (above-the-fold photos); otherwise lazy
--}}
@props(['path', 'alt' => '', 'sizes' => '100vw', 'width' => 480, 'widths' => null, 'eager' => false, 'priority' => false])

@php $srcset = \App\Support\Img::srcset($path, $widths); @endphp

<img src="{{ \App\Support\Img::url($path, $width) }}"
     @if($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
     alt="{{ $alt }}"
     loading="{{ $eager ? 'eager' : 'lazy' }}"
     decoding="async"
     @if($priority) fetchpriority="high" @endif
     {{ $attributes }}>
