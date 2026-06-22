<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Display pending reviews.
     */
    public function index(Request $request)
    {
        $query = Review::query();

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $reviews = $query->with('product', 'user')->latest()->paginate(15);

        return view('admin.reviews.index', compact('reviews'));
    }

    /**
     * Approve a review.
     */
    public function approve(Review $review)
    {
        $review->update(['status' => 'approved']);

        // Update product average rating and count
        $this->updateProductRating($review->product_id);

        return back()->with('success', 'Review approved!');
    }

    /**
     * Reject a review.
     */
    public function reject(Review $review)
    {
        $review->update(['status' => 'rejected']);

        return back()->with('success', 'Review rejected!');
    }

    /**
     * Reply to a review.
     */
    public function reply(Request $request, Review $review)
    {
        $request->validate([
            'admin_reply' => 'required|string|max:500',
        ]);

        $review->update(['admin_reply' => $request->admin_reply]);

        return back()->with('success', 'Reply added!');
    }

    /**
     * Delete a review.
     */
    public function destroy(Review $review)
    {
        $productId = $review->product_id;
        $review->delete();

        $this->updateProductRating($productId);

        return back()->with('success', 'Review deleted!');
    }

    /**
     * Update product's average rating.
     */
    private function updateProductRating($productId)
    {
        $product = \App\Models\Product::find($productId);

        if (!$product) {
            return;
        }

        $approvedReviews = Review::where('product_id', $productId)
            ->where('status', 'approved')
            ->get();

        $totalReviews = $approvedReviews->count();

        if ($totalReviews > 0) {
            $averageRating = $approvedReviews->avg('rating');
            $product->update([
                'average_rating' => $averageRating,
                'total_reviews' => $totalReviews,
            ]);
        } else {
            $product->update([
                'average_rating' => 0,
                'total_reviews' => 0,
            ]);
        }
    }
}
