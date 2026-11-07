<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    /**
     * Store a comment or a reply.
     */
    public function store(Request $request, Post $post)
    {
        abort_unless($post->isPublished(), 404);

        $request->validate([
            'body' => 'required|string|min:2|max:2000',
            'parent_id' => 'nullable|integer|exists:post_comments,id',
        ]);

        $parentId = null;

        if ($request->filled('parent_id')) {
            $parent = PostComment::where('post_id', $post->id)->findOrFail($request->parent_id);
            // Keep threads one level deep: replies to replies attach to the original comment
            $parentId = $parent->parent_id ?? $parent->id;
        }

        $comment = PostComment::create([
            'post_id' => $post->id,
            'user_id' => auth()->id(),
            'parent_id' => $parentId,
            'body' => $request->body,
        ]);

        $message = $parentId ? 'Reply posted!' : 'Comment posted!';

        return redirect()->to(route('blog.show', $post) . '#comment-' . $comment->id)->with('success', $message);
    }

    /**
     * Delete a comment (owner or admin).
     */
    public function destroy(PostComment $comment)
    {
        if (! $comment->canBeDeletedBy(auth()->user())) {
            abort(403);
        }

        $post = $comment->post;
        $comment->delete();

        return redirect()->to(route('blog.show', $post) . '#comments')->with('success', 'Comment deleted.');
    }
}
