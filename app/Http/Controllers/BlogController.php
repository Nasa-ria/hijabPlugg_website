<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    /**
     * Display the blog listing.
     */
    public function index(Request $request)
    {
        $query = Post::published()
            ->with('author')
            ->withCount(['likes', 'comments']);

        if ($request->filled('type') && array_key_exists($request->type, Post::TYPES)) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('excerpt', 'like', '%' . $search . '%');
            });
        }

        $posts = $query->latest('published_at')->paginate(9)->withQueryString();

        return view('blog.index', compact('posts'));
    }

    /**
     * Display a single post with its comments.
     */
    public function show(Request $request, Post $post)
    {
        $isAdmin = auth()->check() && auth()->user()->isAdmin();

        if (! $post->isPublished() && ! $isAdmin) {
            abort(404);
        }

        // Count each visitor once per session
        $viewedKey = 'blog_viewed_' . $post->id;
        if (! $request->session()->has($viewedKey)) {
            $post->increment('views_count');
            $request->session()->put($viewedKey, true);
        }

        $post->load('author')->loadCount(['likes', 'comments']);

        $comments = $post->topLevelComments()
            ->with([
                'user',
                'replies' => fn ($q) => $q->with('user')->withCount('likes'),
            ])
            ->withCount('likes')
            ->latest()
            ->get();

        $likedPost = false;
        $likedCommentIds = [];

        if (auth()->check()) {
            $user = auth()->user();
            $likedPost = $post->likes()->where('user_id', $user->id)->exists();
            $likedCommentIds = $user->likes()
                ->where('likeable_type', \App\Models\PostComment::class)
                ->pluck('likeable_id')
                ->all();
        }

        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->where('type', $post->type)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('blog.show', compact('post', 'comments', 'likedPost', 'likedCommentIds', 'relatedPosts'));
    }

    /**
     * Record that a post was shared.
     */
    public function share(Post $post)
    {
        abort_unless($post->isPublished(), 404);

        $post->increment('shares_count');

        return response()->json(['shares' => $post->shares_count]);
    }
}
