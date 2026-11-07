<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Storefront search.
 *  - Every word must appear in the name, descriptions, material, category or a colour name.
 *  - Common related words are searched too (e.g. "scarf" also finds "hijab").
 *  - Small spelling mistakes are corrected against words that exist in the catalogue.
 */
class ProductSearch
{
    /** Words customers use interchangeably */
    private const SYNONYMS = [
        'hijab' => ['scarf', 'headscarf', 'shawl', 'wrap'],
        'scarf' => ['hijab', 'shawl', 'wrap'],
        'shawl' => ['hijab', 'scarf', 'wrap'],
        'wrap' => ['hijab', 'scarf', 'turban'],
        'veil' => ['hijab', 'niqab', 'khimar'],
        'khimar' => ['hijab', 'veil'],
        'abaya' => ['dress', 'jilbab', 'kaftan'],
        'dress' => ['abaya', 'kaftan', 'gown'],
        'jilbab' => ['abaya'],
        'undercap' => ['cap', 'bonnet', 'tube'],
        'cap' => ['undercap', 'bonnet'],
        'pin' => ['pins', 'brooch'],
        'grey' => ['gray'],
        'gray' => ['grey'],
        'colour' => ['color'],
        'color' => ['colour'],
    ];

    private const STOP_WORDS = ['a', 'an', 'and', 'the', 'for', 'of', 'in', 'with', 'to', 'on', 'my'];

    /**
     * Words of the search phrase (lower case, no stop words, at most 6).
     */
    public function terms(string $phrase): array
    {
        return array_slice($this->words($phrase), 0, 6);
    }

    private function words(string $text): array
    {
        $words = preg_split('/[^\pL\pN]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn ($w) => mb_strlen($w) > 1 && ! in_array($w, self::STOP_WORDS, true));

        return array_values(array_unique($words));
    }

    /**
     * Limit a product query to matches for the phrase. Each word (or one of its related
     * words) must match somewhere.
     */
    public function apply(Builder $query, string $phrase): Builder
    {
        $terms = $this->terms($phrase);
        if (empty($terms)) {
            return $query;
        }

        $like = DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';

        foreach ($terms as $term) {
            $variants = array_unique(array_merge([$term, Str::singular($term)], self::SYNONYMS[$term] ?? []));

            $query->where(function (Builder $q) use ($variants, $like) {
                foreach ($variants as $word) {
                    $pattern = '%' . addcslashes($word, '%_\\') . '%';
                    $q->orWhere('products.name', $like, $pattern)
                        ->orWhere('products.short_description', $like, $pattern)
                        ->orWhere('products.description', $like, $pattern)
                        ->orWhere('products.material', $like, $pattern)
                        ->orWhere('products.sku', $like, $pattern)
                        ->orWhereHas('category', fn ($c) => $c->where('name', $like, $pattern))
                        ->orWhereHas('variants', fn ($v) => $v->where('color_name', $like, $pattern));
                }
            });
        }

        return $query;
    }

    /**
     * The phrase with misspelt words replaced by the closest catalogue word,
     * or null when nothing needed correcting.
     */
    public function suggestion(string $phrase): ?string
    {
        $terms = $this->terms($phrase);
        if (empty($terms)) {
            return null;
        }

        $vocabulary = $this->vocabulary();
        $changed = false;

        $corrected = array_map(function ($term) use ($vocabulary, &$changed) {
            if (in_array($term, $vocabulary, true) || isset(self::SYNONYMS[$term])) {
                return $term;
            }

            $best = null;
            $bestDistance = PHP_INT_MAX;
            // One mistake allowed per 4 letters (at least one)
            $allowed = max(1, intdiv(mb_strlen($term), 4));

            foreach ($vocabulary as $word) {
                if (abs(mb_strlen($word) - mb_strlen($term)) > $allowed) {
                    continue;
                }
                $distance = levenshtein($term, $word);
                if ($distance < $bestDistance) {
                    [$best, $bestDistance] = [$word, $distance];
                }
            }

            if ($best !== null && $bestDistance <= $allowed) {
                $changed = true;

                return $best;
            }

            return $term;
        }, $terms);

        return $changed ? implode(' ', $corrected) : null;
    }

    /**
     * Catalogue words: product names, materials, category and colour names (cached 10 minutes).
     */
    public function vocabulary(): array
    {
        return Cache::remember('shop:search-vocabulary', now()->addMinutes(10), function () {
            $text = collect()
                ->merge(Product::where('is_available', true)->pluck('name'))
                ->merge(Product::where('is_available', true)->whereNotNull('material')->pluck('material'))
                ->merge(Category::where('is_active', true)->pluck('name'))
                ->merge(ProductVariant::query()->distinct()->pluck('color_name'))
                ->merge(array_keys(self::SYNONYMS))
                ->implode(' ');

            return $this->words($text);
        });
    }

    /**
     * Products that loosely match: any single word (or its correction) matching counts.
     * Used for "similar products" when the full phrase finds nothing.
     */
    public function similar(string $phrase, int $limit = 8): Collection
    {
        $words = array_unique(array_merge($this->terms($phrase), $this->terms((string) $this->suggestion($phrase))));
        if (empty($words)) {
            return collect();
        }

        $insights = app(ProductInsights::class);
        $matches = collect();

        foreach ($words as $word) {
            $matches = $matches->merge($this->apply($insights->base(), $word)->limit($limit)->get());
        }

        // Products matching more of the words first
        return $matches->groupBy('id')->sortByDesc(fn ($group) => $group->count())
            ->map->first()->take($limit)->values();
    }
}
