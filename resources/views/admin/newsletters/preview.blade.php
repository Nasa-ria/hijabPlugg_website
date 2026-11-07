@extends('layouts.admin')

@section('page_title', 'Preview Newsletter')

@section('content')
<div class="admin-card">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="font-serif text-2xl text-brand-900">Preview Campaign</h1>
            <p class="text-gray-500 mt-2">Review the email before sending it to your subscribers.</p>
        </div>
        <a href="{{ route('admin.newsletters.index') }}" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <div class="overflow-hidden rounded-3xl border border-gray-200 shadow-xl bg-gray-50">
        <div class="bg-white p-8 text-center border-b border-gray-200">
            <p class="text-xs uppercase tracking-[0.2em] text-gray-400">{{ $newsletter->preheader }}</p>
            <h2 class="text-4xl font-bold text-gray-900 mt-4">{{ $newsletter->title }}</h2>
            <p class="text-gray-600 mt-3">{{ $newsletter->hero_text }}</p>
            @if($newsletter->hero_image_path)
                <img src="{{ asset('storage/' . $newsletter->hero_image_path) }}" alt="Hero" class="mx-auto mt-8 rounded-3xl max-w-full object-cover">
            @endif
            @if($newsletter->cta_text && $newsletter->cta_url)
                <a href="{{ $newsletter->cta_url }}" class="btn-brand mt-8">{{ $newsletter->cta_text }}</a>
            @endif
        </div>

        <div class="p-8 space-y-8">
            @foreach($newsletter->content ?? [] as $section)
                <div class="grid grid-cols-1 lg:grid-cols-[1fr_1fr] gap-6 items-center">
                    <div>
                        <h3 class="text-2xl font-semibold text-gray-900">{{ $section['title'] }}</h3>
                        <p class="mt-3 text-gray-600">{{ $section['body'] }}</p>
                        @if($section['button_text'] && $section['button_url'])
                            <a href="{{ $section['button_url'] }}" class="btn-brand mt-6">{{ $section['button_text'] }}</a>
                        @endif
                    </div>
                    @if(!empty($section['image_path']))
                        <div>
                            <img src="{{ asset('storage/' . $section['image_path']) }}" alt="Section image" class="rounded-3xl w-full object-cover">
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="mt-8 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="text-xl font-semibold text-gray-900">Send a test email</h3>
        <p class="text-gray-500 mt-2">Send a preview copy to your inbox before distributing it to subscribers.</p>

        <form action="{{ route('admin.newsletters.send-test', $newsletter) }}" method="POST" class="mt-6 grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-4 items-center">
            @csrf
            <input type="email" name="test_email" required placeholder="Enter test email" class="input-field" />
            <button type="submit" class="btn-brand">Send Test</button>
        </form>
    </div>
</div>
@endsection
