@csrf

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <label class="admin-label">Title</label>
        <input type="text" name="title" value="{{ old('title', $post->title) }}" required class="input-field">
        @error('title')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="admin-label">Type</label>
        <select name="type" id="postType" required class="input-field">
            @foreach(\App\Models\Post::TYPES as $value => $label)
                <option value="{{ $value }}" {{ old('type', $post->type) === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        @error('type')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
    </div>
</div>

<div>
    <label class="admin-label">Short summary <span class="font-normal text-gray-500">(shown on the blog listing)</span></label>
    <input type="text" name="excerpt" maxlength="500" value="{{ old('excerpt', $post->excerpt) }}" class="input-field">
    @error('excerpt')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
</div>

{{-- Video source --}}
<div id="videoFields" class="rounded-lg border border-cream-300 bg-cream-100 p-6 space-y-4">
    <p class="font-serif text-lg text-brand-900"><i class="fas fa-video mr-2 text-accent-600"></i>Video</p>
    <div>
        <label class="block text-sm font-semibold mb-2">YouTube or Vimeo link</label>
        <input type="url" name="video_url" value="{{ old('video_url', $post->video_url) }}" placeholder="https://www.youtube.com/watch?v=..." class="input-field">
        @error('video_url')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-semibold mb-2">…or upload a video file <span class="font-normal text-gray-500">(MP4, WebM or MOV, up to 100MB)</span></label>
        <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime" class="w-full">
        @error('video_file')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        @if($post->video_path)
            <div class="mt-3 flex items-center gap-4">
                <video src="{{ asset('storage/' . $post->video_path) }}" controls preload="metadata" class="h-32 rounded-lg bg-black"></video>
                <label class="inline-flex items-center gap-2 text-sm text-red-600">
                    <input type="checkbox" name="remove_video" value="1" class="rounded border-gray-300 text-accent-600 focus:ring-accent-500"> Remove uploaded video
                </label>
            </div>
        @endif
    </div>
</div>

<div>
    <label class="admin-label">Content</label>
    <p class="text-sm text-gray-500 mb-2">
        Supports simple formatting: <code>**bold**</code>, <code>*italic*</code>, <code>## Heading</code>, <code>- list item</code>, <code>> quote</code>, <code>[link text](https://...)</code>. Leave a blank line between paragraphs.
    </p>
    <textarea name="body" rows="14" class="input-field font-mono text-sm">{{ old('body', $post->body) }}</textarea>
    @error('body')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div>
        <label class="admin-label">Cover image</label>
        <input type="file" name="cover_image" accept="image/*" class="w-full">
        @error('cover_image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        @if($post->cover_image)
            <img src="{{ asset('storage/' . $post->cover_image) }}" alt="Current cover" class="mt-3 h-24 rounded-lg object-cover">
        @endif
    </div>
    <div>
        <label class="admin-label">Status</label>
        <select name="status" class="input-field">
            <option value="published" {{ old('status', $post->status) === 'published' ? 'selected' : '' }}>Published</option>
            <option value="draft" {{ old('status', $post->status) === 'draft' ? 'selected' : '' }}>Draft</option>
        </select>
        @error('status')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="admin-label">Publish date <span class="font-normal text-gray-500">(optional)</span></label>
        <input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="input-field">
        <p class="text-xs text-gray-500 mt-1">Leave empty to publish now. A future date schedules the post.</p>
        @error('published_at')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
    </div>
</div>

<script>
    (function () {
        const type = document.getElementById('postType');
        const video = document.getElementById('videoFields');
        const sync = () => video.classList.toggle('hidden', type.value !== 'video');
        type.addEventListener('change', sync);
        sync();
    })();
</script>
