@extends('layouts.admin')

@section('page_title', 'Edit Newsletter')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold">Edit Newsletter Campaign</h1>
            <p class="text-gray-500 mt-2">Update your campaign and preview the final email design.</p>
        </div>
        <a href="{{ route('admin.newsletters.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition"><i class="fas fa-arrow-left"></i> Back</a>
    </div>

    <form action="{{ route('admin.newsletters.update', $newsletter) }}" method="POST" enctype="multipart/form-data" class="space-y-8">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Campaign Title</label>
                <input type="text" name="title" value="{{ old('title', $newsletter->title) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('title')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email Subject</label>
                <input type="text" name="subject" value="{{ old('subject', $newsletter->subject) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('subject')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Preheader Text</label>
                <input type="text" name="preheader" value="{{ old('preheader', $newsletter->preheader) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('preheader')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Hero Image</label>
                <input type="file" name="hero_image" class="w-full">
                @if($newsletter->hero_image_path)
                    <img src="{{ asset('storage/' . $newsletter->hero_image_path) }}" alt="Hero" class="mt-3 rounded-xl border border-gray-200 w-full max-h-52 object-cover">
                @endif
                @error('hero_image')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Hero Text</label>
                <textarea name="hero_text" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('hero_text', $newsletter->hero_text) }}</textarea>
                @error('hero_text')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Primary CTA</label>
                <input type="text" name="cta_text" value="{{ old('cta_text', $newsletter->cta_text) }}" placeholder="Shop Now" class="w-full px-4 py-3 border border-gray-300 rounded-lg mb-4">
                <input type="url" name="cta_url" value="{{ old('cta_url', $newsletter->cta_url) }}" placeholder="https://example.com" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('cta_text')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                @error('cta_url')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold">Newsletter Sections</h2>
                    <p class="text-gray-500">Add promotional sections, product highlights, and image cards.</p>
                </div>
                <button type="button" onclick="addSection()" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition"><i class="fas fa-plus"></i> Add Section</button>
            </div>

            <div id="section-list" class="space-y-6">
                @foreach(old('section_title', $newsletter->content ? array_column($newsletter->content, 'title') : []) as $index => $title)
                    <div class="bg-slate-50 border border-gray-200 rounded-2xl p-6 relative section-item">
                        <button type="button" onclick="removeSection(this)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Section Title</label>
                                <input type="text" name="section_title[]" value="{{ $title }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Section Image</label>
                                <input type="file" name="section_image[]" class="w-full">
                                @if(!empty(old('existing_section_image.' . $index)))
                                    <img src="{{ asset('storage/' . old('existing_section_image.' . $index)) }}" alt="Section" class="mt-3 rounded-xl border border-gray-200 w-full max-h-52 object-cover">
                                @elseif(!empty($newsletter->content[$index]['image_path']))
                                    <img src="{{ asset('storage/' . $newsletter->content[$index]['image_path']) }}" alt="Section" class="mt-3 rounded-xl border border-gray-200 w-full max-h-52 object-cover">
                                @endif
                                <input type="hidden" name="existing_section_image[]" value="{{ old('existing_section_image.' . $index, $newsletter->content[$index]['image_path'] ?? '') }}">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Section Body</label>
                                <textarea name="section_body[]" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('section_body.' . $index, $newsletter->content[$index]['body'] ?? '') }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Button URL</label>
                                <input type="url" name="section_button_url[]" value="{{ old('section_button_url.' . $index, $newsletter->content[$index]['button_url'] ?? '') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="https://example.com">
                                <label class="block text-sm font-semibold text-gray-700 mb-2 mt-4">Button Text</label>
                                <input type="text" name="section_button_text[]" value="{{ old('section_button_text.' . $index, $newsletter->content[$index]['button_text'] ?? '') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="View Collection">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <template id="section-template">
                <div class="bg-slate-50 border border-gray-200 rounded-2xl p-6 relative section-item">
                    <button type="button" onclick="removeSection(this)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500"><i class="fas fa-times"></i></button>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Section Title</label>
                            <input type="text" name="section_title[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Section Image</label>
                            <input type="file" name="section_image[]" class="w-full">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Section Body</label>
                            <textarea name="section_body[]" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Button URL</label>
                            <input type="url" name="section_button_url[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="https://example.com">
                            <label class="block text-sm font-semibold text-gray-700 mb-2 mt-4">Button Text</label>
                            <input type="text" name="section_button_text[]" class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="View Collection">
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1fr] gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Schedule Send</label>
                <input type="datetime-local" name="schedule_at" value="{{ old('schedule_at', optional($newsletter->scheduled_at)->format('Y-m-d\TH:i')) }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                <p class="text-sm text-gray-500 mt-2">Leave blank to keep the campaign in draft.</p>
            </div>
            <div class="flex flex-col justify-end gap-3">
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition">Save Changes</button>
                    @if(!$newsletter->isSent())
                        <form action="{{ route('admin.newsletters.send', $newsletter) }}" method="POST" class="inline-flex">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-green-600 text-white hover:bg-green-700 transition">Send Campaign</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function addSection() {
        const template = document.getElementById('section-template');
        const clone = template.content.cloneNode(true);
        document.getElementById('section-list').appendChild(clone);
    }

    function removeSection(button) {
        button.closest('.section-item').remove();
    }
</script>
@endpush
@endsection
