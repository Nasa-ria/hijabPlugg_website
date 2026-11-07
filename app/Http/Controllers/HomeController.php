<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Product;

class HomeController extends Controller
{
    /**
     * The scroll-driven story home page.
     */
    public function index()
    {
        $products = Product::with(['variants', 'category'])->where('is_available', true)
            ->latest()
            ->take(4)
            ->get();

        $latestPosts = Post::published()
            ->latest('published_at')
            ->take(2)
            ->get();

        return view('welcome', compact('products', 'latestPosts'));
    }
}
