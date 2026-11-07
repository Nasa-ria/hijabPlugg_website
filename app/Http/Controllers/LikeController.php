<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Like or unlike a post.
     */
    public function togglePost(Request $request, Post $post)
    {
        abort_unless($post->isPublished(), 404);

        return $this->toggle($request, $post);
    }

    /**
     * Like or unlike a comment.
     */
    public function toggleComment(Request $request, PostComment $comment)
    {
        return $this->toggle($request, $comment);
    }

    private function toggle(Request $request, Model $likeable)
    {
        $existing = $likeable->likes()->where('user_id', auth()->id())->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            $likeable->likes()->firstOrCreate(['user_id' => auth()->id()]);
            $liked = true;
        }

        if ($request->expectsJson()) {
            return response()->json([
                'liked' => $liked,
                'count' => $likeable->likes()->count(),
            ]);
        }

        return back();
    }
}
