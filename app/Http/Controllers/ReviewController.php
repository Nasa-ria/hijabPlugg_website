<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a review.
     */
    public function store(Request $request, Product $product)
    {
        $this->middleware('auth');

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:100',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        // Check if user already reviewed this product
        $existingReview = Review::where('product_id', $product->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existingReview) {
            return back()->with('error', 'You have already reviewed this product.');
        }

        Review::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Review submitted! It will be displayed after admin approval.');
    }

    /**
     * Update a review (customer).
     */
    public function update(Request $request, Review $review)
    {
        $this->middleware('auth');

        if (auth()->id() !== $review->user_id) {
            abort(403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:100',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        $review->update([
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Review updated and sent for approval!');
    }

    /**
     * Delete a review.
     */
    public function destroy(Review $review)
    {
        $this->middleware('auth');

        if (auth()->id() !== $review->user_id && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $review->delete();

        return back()->with('success', 'Review deleted!');
    }
}
