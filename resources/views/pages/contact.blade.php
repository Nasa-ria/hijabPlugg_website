@extends('layouts.app')

@section('title', 'Contact us – Nasaari Store')

@php
    $contact = config('nasaari.contact');
    $whatsapp = config('nasaari.social.whatsapp');
    $topic = old('topic', request('topic', 'question'));
    $topics = [
        'question' => ['Question', 'fa-circle-question', 'Ask about a product, sizing or delivery'],
        'feedback' => ['Feedback', 'fa-star', 'Tell us what you love or what we can do better'],
        'order' => ['Order help', 'fa-bag-shopping', 'Something not right with an order'],
    ];
@endphp

@section('content')
<div class="bg-cream-100">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">
        <header class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-accent-600">We'd love to hear from you</p>
            <h1 class="mt-3 font-serif text-4xl sm:text-5xl text-brand-900">Contact us</h1>
            <p class="mt-4 text-gray-600 leading-7">Questions, feedback or help with an order, send us a note and we'll reply within one working day.</p>
        </header>

        <div class="mt-12 grid grid-cols-1 gap-8 lg:grid-cols-[0.8fr_1.2fr]">
            {{-- Ways to reach us --}}
            <aside class="space-y-6">
                <div class="rounded-[1.75rem] border border-cream-300 bg-white p-6 sm:p-8 space-y-5">
                    <h2 class="font-serif text-2xl text-brand-900">Reach us</h2>
                    <dl class="space-y-4 text-sm">
                        @if($contact['email'])
                            <div class="flex gap-3">
                                <dt><i class="fas fa-envelope mt-0.5 w-4 text-accent-500" aria-hidden="true"></i><span class="sr-only">Email</span></dt>
                                <dd><a href="mailto:{{ $contact['email'] }}" class="font-medium text-brand-900 hover:text-accent-700">{{ $contact['email'] }}</a></dd>
                            </div>
                        @endif
                        @if($contact['phone'])
                            <div class="flex gap-3">
                                <dt><i class="fas fa-phone mt-0.5 w-4 text-accent-500" aria-hidden="true"></i><span class="sr-only">Phone</span></dt>
                                <dd><a href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}" class="font-medium text-brand-900 hover:text-accent-700">{{ $contact['phone'] }}</a></dd>
                            </div>
                        @endif
                        @if($whatsapp)
                            <div class="flex gap-3">
                                <dt><i class="fab fa-whatsapp mt-0.5 w-4 text-accent-500" aria-hidden="true"></i><span class="sr-only">WhatsApp</span></dt>
                                <dd><a href="https://wa.me/{{ preg_replace('/\D+/', '', $whatsapp) }}" target="_blank" rel="noopener" class="font-medium text-brand-900 hover:text-accent-700">Chat on WhatsApp</a></dd>
                            </div>
                        @endif
                        @if($contact['hours'])
                            <div class="flex gap-3">
                                <dt><i class="fas fa-clock mt-0.5 w-4 text-accent-500" aria-hidden="true"></i><span class="sr-only">Hours</span></dt>
                                <dd class="text-gray-600">{{ $contact['hours'] }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if(collect(config('nasaari.social'))->filter()->isNotEmpty())
                        <div class="border-t border-cream-200 pt-5">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.2em] text-gray-500">Follow along{{ config('nasaari.handle') ? ' · ' . config('nasaari.handle') : '' }}</p>
                            <x-social-links tone="dark" />
                        </div>
                    @endif
                </div>

                <a href="{{ route('orders.track') }}" class="flex items-center gap-4 rounded-[1.75rem] border border-cream-300 bg-white p-6 hover:border-accent-300 transition-colors">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full bg-accent-50 text-accent-600"><i class="fas fa-truck-fast" aria-hidden="true"></i></span>
                    <span>
                        <span class="block font-semibold text-brand-900">Where's my order?</span>
                        <span class="block text-sm text-gray-500">Track it with your order number and email</span>
                    </span>
                    <i class="fas fa-arrow-right ml-auto text-xs text-gray-400" aria-hidden="true"></i>
                </a>
            </aside>

            {{-- Message / feedback form --}}
            <section class="rounded-[1.75rem] border border-cream-300 bg-white p-6 sm:p-8" aria-labelledby="contact-form-title">
                <h2 id="contact-form-title" class="font-serif text-2xl text-brand-900">Send us a message</h2>

                <form action="{{ route('pages.contact.store') }}" method="POST" class="mt-6 space-y-5" id="contactForm" novalidate>
                    @csrf
                    {{-- Honeypot for spam bots (hidden from people) --}}
                    <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

                    <fieldset>
                        <legend class="mb-3 text-sm font-semibold text-brand-900">What's this about?</legend>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            @foreach($topics as $value => [$label, $icon, $hint])
                                <label class="contact-topic relative flex cursor-pointer flex-col gap-1 rounded-2xl border border-cream-300 bg-cream-50 p-4 transition-colors has-[:checked]:border-accent-500 has-[:checked]:bg-accent-50 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent-400">
                                    <input type="radio" name="topic" value="{{ $value }}" class="sr-only" @checked($topic === $value)>
                                    <span class="flex items-center gap-2 font-semibold text-brand-900"><i class="fas {{ $icon }} text-accent-500" aria-hidden="true"></i>{{ $label }}</span>
                                    <span class="text-xs text-gray-500">{{ $hint }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('topic')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </fieldset>

                    {{-- Star rating (feedback only) --}}
                    <fieldset data-topic-field="feedback" @class(['hidden' => $topic !== 'feedback'])>
                        <legend class="mb-2 text-sm font-semibold text-brand-900">How was your experience?</legend>
                        <div class="flex flex-row-reverse justify-end gap-1" role="radiogroup">
                            @for($i = 5; $i >= 1; $i--)
                                <input type="radio" name="rating" id="rating-{{ $i }}" value="{{ $i }}" class="peer sr-only" @checked((int) old('rating') === $i)>
                                <label for="rating-{{ $i }}" class="cursor-pointer text-3xl text-cream-400 transition-colors hover:text-accent-500 peer-hover:text-accent-500 peer-checked:text-accent-500" title="{{ $i }} {{ Str::plural('star', $i) }}">
                                    <i class="fas fa-star" aria-hidden="true"></i><span class="sr-only">{{ $i }} {{ Str::plural('star', $i) }}</span>
                                </label>
                            @endfor
                        </div>
                        @error('rating')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
                    </fieldset>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="contact-name" class="admin-label">Your name</label>
                            <input id="contact-name" type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required maxlength="100" autocomplete="name" class="input-field">
                            @error('name')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="contact-email" class="admin-label">Email</label>
                            <input id="contact-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required maxlength="150" autocomplete="email" class="input-field">
                            @error('email')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="contact-phone" class="admin-label">Phone <span class="font-normal text-gray-400">(optional)</span></label>
                            <input id="contact-phone" type="tel" name="phone" value="{{ old('phone') }}" maxlength="40" autocomplete="tel" class="input-field">
                        </div>
                        <div data-topic-field="order" @class(['hidden' => $topic !== 'order'])>
                            <label for="contact-order" class="admin-label">Order number</label>
                            <input id="contact-order" type="text" name="order_number" value="{{ old('order_number') }}" maxlength="60" placeholder="ORD-…" class="input-field">
                        </div>
                    </div>

                    <div>
                        <label for="contact-message" class="admin-label" data-message-label>Message</label>
                        <textarea id="contact-message" name="message" rows="6" required minlength="5" maxlength="3000" class="input-field resize-y">{{ old('message') }}</textarea>
                        @error('message')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                    </div>

                    <button type="submit" class="btn-brand w-full sm:w-auto px-10">
                        <i class="fas fa-paper-plane text-xs" aria-hidden="true"></i> <span data-submit-label>Send message</span>
                    </button>
                </form>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Show the star rating for feedback and the order number for order help
    (function () {
        const form = document.getElementById('contactForm');
        const update = () => {
            const topic = form.querySelector('input[name="topic"]:checked')?.value;
            form.querySelectorAll('[data-topic-field]').forEach(el => el.classList.toggle('hidden', el.dataset.topicField !== topic));
            form.querySelector('[data-message-label]').textContent = topic === 'feedback' ? 'Your feedback' : 'Message';
            form.querySelector('[data-submit-label]').textContent = topic === 'feedback' ? 'Send feedback' : 'Send message';
        };
        form.addEventListener('change', (e) => { if (e.target.name === 'topic') update(); });
        update();
    })();
</script>
@endpush
