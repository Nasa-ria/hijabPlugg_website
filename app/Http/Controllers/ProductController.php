<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductInsights;
use App\Services\ProductSearch;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public const SORTS = [
        'newest' => 'Newest arrivals',
        'popular' => 'Most popular',
        'price_low' => 'Price: low to high',
        'price_high' => 'Price: high to low',
        'rating' => 'Top rated',
    ];

    public function __construct(private ProductSearch $search, private ProductInsights $insights)
    {
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|string|max:120',
            'sort' => 'nullable|string|max:20',
        ]);

        $term = trim((string) $request->query('search', ''));
        $sort = $request->query('sort', 'newest');
        $sort = $sort === 'latest' ? 'newest' : $sort; // older links
        $sort = array_key_exists($sort, self::SORTS) ? $sort : 'newest';

        $categories = Category::where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_available', true)])
            ->orderBy('name')->get();
        $activeCategory = $request->filled('category') ? $categories->firstWhere('slug', $request->query('category')) : null;

        $query = Product::query()->with(['variants', 'images', 'category'])->where('is_available', true)
            ->when($activeCategory, fn ($q) => $q->where('category_id', $activeCategory->id));

        if ($term !== '') {
            $this->search->apply($query, $term);
        }

        // Items in stock first, then the chosen order
        $query->orderByRaw('CASE WHEN stock_quantity > 0 THEN 0 ELSE 1 END');
        match ($sort) {
            'popular' => ProductInsights::withUnitsSold($query)->orderByDesc('units_sold')->orderByDesc('average_rating'),
            'price_low' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price_high' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'rating' => $query->orderByDesc('average_rating')->orderByDesc('total_reviews'),
            default => $query->latest(),
        };
        $query->orderByDesc('id');

        $products = $query->paginate(12)->withQueryString();

        // Nothing found: suggest a corrected spelling and recommend products instead of an empty page
        $suggestion = null;
        $recommendations = null;
        if ($term !== '' && $products->total() === 0) {
            $suggestion = $this->search->suggestion($term);
            $similar = $this->search->similar($term);
            $shown = $similar->pluck('id')->all();

            $categoryIds = $similar->pluck('category_id')->unique()->values()->all();
            if ($activeCategory) {
                $categoryIds[] = $activeCategory->id;
            }
            $sameCategory = $this->insights->fromCategories(array_unique($categoryIds), 8, $shown);
            $shown = array_merge($shown, $sameCategory->pluck('id')->all());
            $bestSellers = $this->insights->bestSellers(8, $shown);
            $shown = array_merge($shown, $bestSellers->pluck('id')->all());

            $recommendations = [
                'similar' => $similar,
                'category' => $sameCategory,
                'best' => $bestSellers,
                'new' => $this->insights->newArrivals(8, $shown),
            ];
        }

        $showHero = $term === '' && ! $activeCategory && $products->currentPage() === 1;
        // The photo collage is decorative: skipped in data-saver mode
        $heroProducts = $showHero && ! \App\Support\DataSaver::on() ? $this->heroProducts() : collect();

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'activeCategory' => $activeCategory,
            'sorts' => self::SORTS,
            'currentSort' => $sort,
            'term' => $term,
            'suggestion' => $suggestion,
            'recommendations' => $recommendations,
            'showHero' => $showHero,
            'heroProducts' => $heroProducts,
        ]);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        if (!$product->is_available) {
            abort(404);
        }

        $product->load(['images', 'category', 'variants', 'approvedReviews.user']);
        \App\Support\Analytics::record(request(), 'product_view', ['product_id' => $product->id, 'source' => 'page', 'path' => '/' . request()->path()]);

        $relatedProducts = $this->insights->fromCategories([$product->category_id], 4, [$product->id]);
        $recommendedProducts = $this->insights->recommendedFor($product, $relatedProducts->pluck('id')->all(), 4);

        $userReview = null;
        if (auth()->check()) {
            $userReview = $product->reviews()
                ->where('user_id', auth()->id())
                ->first();
        }

        return view('products.show', compact('product', 'relatedProducts', 'recommendedProducts', 'userReview'));
    }

    /**
     * Three photos for the shop banner: best sellers first, then the newest pieces.
     */
    private function heroProducts()
    {
        $picks = $this->insights->bestSellers(3)->filter(fn ($p) => $p->main_image || $p->variants->first()?->image);
        if ($picks->count() < 3) {
            $picks = $picks->merge($this->insights->newArrivals(6, $picks->pluck('id')->all())
                ->filter(fn ($p) => $p->main_image || $p->variants->first()?->image))->take(3);
        }

        return $picks->values();
    }
}
