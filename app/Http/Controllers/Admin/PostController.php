<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /**
     * Display a listing of blog posts.
     */
    public function index(Request $request)
    {
        $query = Post::query()->withCount(['likes', 'comments']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $posts = $query->latest()->paginate(15)->withQueryString();

        return view('admin.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new post.
     */
    public function create()
    {
        return view('admin.posts.create', ['post' => new Post(['type' => 'article', 'status' => 'published'])]);
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['user_id'] = auth()->id();
        $data['slug'] = Post::uniqueSlug($data['title']);

        $post = new Post($data);
        $this->handleUploads($request, $post);
        $post->save();

        return redirect()->route('admin.posts.index')->with('success', 'Post created successfully!');
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Post $post)
    {
        return view('admin.posts.edit', compact('post'));
    }

    /**
     * Update the specified post.
     */
    public function update(Request $request, Post $post)
    {
        $data = $this->validated($request, $post);

        if ($data['title'] !== $post->title) {
            $data['slug'] = Post::uniqueSlug($data['title'], $post->id);
        }

        $post->fill($data);
        $this->handleUploads($request, $post);
        $post->save();

        return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully!');
    }

    /**
     * Remove the specified post.
     */
    public function destroy(Post $post)
    {
        foreach (['cover_image', 'video_path'] as $field) {
            if ($post->$field) {
                Storage::disk('public')->delete($post->$field);
            }
        }

        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully!');
    }

    private function validated(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(Post::TYPES))],
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string|required_unless:type,video',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'video_url' => 'nullable|url|max:255',
            'video_file' => 'nullable|file|mimetypes:video/mp4,video/webm,video/quicktime|max:102400',
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => 'nullable|date',
        ], [
            'body.required_unless' => 'Please write some content for this post.',
            'video_file.max' => 'The video may not be larger than 100MB.',
        ]);

        $keepsExistingVideo = $post?->video_path && ! $request->boolean('remove_video');

        if ($data['type'] === 'video'
            && empty($data['video_url'])
            && ! $request->hasFile('video_file')
            && ! $keepsExistingVideo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'video_url' => 'Add a YouTube/Vimeo link or upload a video file.',
            ]);
        }

        if (! empty($data['video_url']) && ! (new Post(['video_url' => $data['video_url']]))->video_embed_url) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'video_url' => 'Only YouTube and Vimeo links are supported.',
            ]);
        }

        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = $post?->published_at ?? now();
        }

        unset($data['cover_image'], $data['video_file']);

        return $data;
    }

    private function handleUploads(Request $request, Post $post): void
    {
        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $post->cover_image = $request->file('cover_image')->store('blog/covers', 'public');
        }

        if ($request->hasFile('video_file')) {
            if ($post->video_path) {
                Storage::disk('public')->delete($post->video_path);
            }
            $post->video_path = $request->file('video_file')->store('blog/videos', 'public');
        } elseif ($request->boolean('remove_video') && $post->video_path) {
            Storage::disk('public')->delete($post->video_path);
            $post->video_path = null;
        }
    }
}
