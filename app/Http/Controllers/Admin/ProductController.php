<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->with('category')->paginate(15);
        $categories = Category::all();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        $categories = Category::all();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sku' => 'nullable|string|max:64|unique:products,sku|unique:product_variants,sku',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'material' => 'nullable|string|max:120',
            'size' => 'nullable|string|max:120',
            'size_chart' => 'nullable|string|max:5000',
            'variants' => 'nullable|array',
            'variants.*.color_name' => 'nullable|string|max:60',
            'new_colour_images' => 'nullable|array|max:20',
            'new_colour_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'new_colour_stock' => 'nullable|integer|min:0',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0|max:1000000',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'length_cm' => 'nullable|numeric|min:0|max:500',
            'width_cm' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:500',
            'variants.*.sku' => 'nullable|string|max:64',
            'variants.*.cost_price' => 'nullable|numeric|min:0|max:1000000',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Create slug
        $slug = \Str::slug($request->name);
        $count = Product::where('slug', $slug)->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        // Handle main image
        $mainImage = null;
        if ($request->hasFile('main_image')) {
            $mainImage = $request->file('main_image')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'material' => $request->material,
            'size' => $request->size,
            'size_chart' => $request->size_chart,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'stock_quantity' => $request->stock_quantity,
            'is_available' => true,
            'main_image' => $mainImage,
        ] + $this->inventoryFields($request));

        $this->syncVariants($request, $product);
        $this->logStockChanges($product, ['product' => 0, 'variants' => []], 'opening', 'Initial stock', $request);

        // Handle additional images
        if ($request->hasFile('images')) {
            $sortOrder = 0;
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        $product->load(['images', 'variants']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'sku' => ['nullable', 'string', 'max:64', \Illuminate\Validation\Rule::unique('products', 'sku')->ignore($product->id), 'unique:product_variants,sku'],
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'material' => 'nullable|string|max:120',
            'size' => 'nullable|string|max:120',
            'size_chart' => 'nullable|string|max:5000',
            'variants' => 'nullable|array',
            'variants.*.color_name' => 'nullable|string|max:60',
            'new_colour_images' => 'nullable|array|max:20',
            'new_colour_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'new_colour_stock' => 'nullable|integer|min:0',
            'images' => 'nullable|array|max:20',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'remove_images' => 'nullable|array',
            'variants.*.stock_quantity' => 'nullable|integer|min:0',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:4096',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0|max:1000000',
            'low_stock_threshold' => 'nullable|integer|min:0|max:100000',
            'weight_kg' => 'nullable|numeric|min:0|max:500',
            'length_cm' => 'nullable|numeric|min:0|max:500',
            'width_cm' => 'nullable|numeric|min:0|max:500',
            'height_cm' => 'nullable|numeric|min:0|max:500',
            'variants.*.sku' => 'nullable|string|max:64',
            'variants.*.cost_price' => 'nullable|numeric|min:0|max:1000000',
            'is_available' => 'boolean',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $this->assertVariantSkusUnique($request, $product);
        $stockBefore = $this->stockSnapshot($product);

        // Handle main image
        if ($request->hasFile('main_image')) {
            $mainImage = $request->file('main_image')->store('products', 'public');
            $product->main_image = $mainImage;
        }

        $product->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'short_description' => $request->short_description,
            'material' => $request->material,
            'size' => $request->size,
            'size_chart' => $request->size_chart,
            'price' => $request->price,
            'sale_price' => $request->sale_price,
            'stock_quantity' => $request->stock_quantity,
            'is_available' => $request->boolean('is_available'),
            'main_image' => $product->main_image,
        ] + $this->inventoryFields($request));

        $this->syncVariants($request, $product);
        $this->syncGallery($request, $product);
        $this->logStockChanges($product, $stockBefore, 'adjustment', 'Edited on the product form', $request);
        \App\Services\ProductInsights::flush();

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully!');
    }

    /**
     * Update the product's colours from the form:
     *  - existing colours can be renamed, restocked, re-photographed or removed
     *  - every newly uploaded photo becomes a new colour, named from its file name
     * When a product has colours, its stock is the total of their stock.
     */
    private function syncVariants(Request $request, Product $product): void
    {
        foreach ($request->input('variants', []) as $index => $row) {
            $variant = ! empty($row['id']) ? $product->variants()->find($row['id']) : null;
            if (! $variant) {
                continue;
            }

            if (! empty($row['remove'])) {
                if ($variant->image) {
                    Storage::disk('public')->delete($variant->image);
                }
                $variant->delete();
                continue;
            }

            $data = [
                'color_name' => trim($row['color_name'] ?? '') ?: $variant->color_name,
                'stock_quantity' => (int) ($row['stock_quantity'] ?? $variant->stock_quantity),
                'price' => ($row['price'] ?? '') === '' ? null : $row['price'],
                'cost_price' => ($row['cost_price'] ?? '') === '' ? null : $row['cost_price'],
            ];
            if (trim((string) ($row['sku'] ?? '')) !== '') {
                $data['sku'] = trim($row['sku']);
            }

            if ($request->hasFile("variants.$index.image")) {
                if ($variant->image) {
                    Storage::disk('public')->delete($variant->image);
                }
                $data['image'] = $request->file("variants.$index.image")->store('products/variants', 'public');
            }

            $variant->update($data);
        }

        // Each new photo becomes its own colour option
        $nextOrder = (int) $product->variants()->max('sort_order') + 1;
        $existingCount = $product->variants()->count();
        foreach ($request->file('new_colour_images', []) as $i => $file) {
            $product->variants()->create([
                'color_name' => $this->colourNameFromFile($file->getClientOriginalName(), $existingCount + $i + 1),
                'image' => $file->store('products/variants', 'public'),
                'stock_quantity' => (int) $request->input('new_colour_stock', 0),
                'sort_order' => $nextOrder + $i,
            ]);
        }

        if ($product->variants()->exists()) {
            $product->update([
                'stock_quantity' => $product->variants()->sum('stock_quantity'),
                // Use the first colour's photo when the product has no main image
                'main_image' => $product->main_image ?: $product->variants()->first()->image,
            ]);
        }
    }

    /**
     * "misty-blue.jpg" -> "Misty Blue"; camera names like "IMG_1234.jpg" -> "Colour 3".
     */
    private function colourNameFromFile(string $fileName, int $position): string
    {
        $base = trim(preg_replace('/[-_]+/', ' ', pathinfo($fileName, PATHINFO_FILENAME)));

        if ($base === '' || preg_match('/^(img|dsc|image|photo|pxl|screenshot)?\s*\d+[\w\s]*$/i', $base)) {
            return 'Colour ' . $position;
        }

        return \Illuminate\Support\Str::title(\Illuminate\Support\Str::limit($base, 60, ''));
    }

    /**
     * Purchase cost, stock alert level, SKU and shipping measurements from the form.
     */
    private function inventoryFields(Request $request): array
    {
        $fields = [
            'cost_price' => $request->filled('cost_price') ? $request->cost_price : 0,
            'low_stock_threshold' => $request->filled('low_stock_threshold') ? (int) $request->low_stock_threshold : null,
        ];
        foreach (['weight_kg', 'length_cm', 'width_cm', 'height_cm'] as $field) {
            $fields[$field] = $request->filled($field) ? $request->input($field) : null;
        }
        if ($request->filled('sku')) {
            $fields['sku'] = trim($request->sku);
        }

        return $fields;
    }

    private function assertVariantSkusUnique(Request $request, Product $product): void
    {
        $skus = collect($request->input('variants', []))->filter(fn ($row) => empty($row['remove']))
            ->map(fn ($row, $i) => ['index' => $i, 'id' => $row['id'] ?? null, 'sku' => trim((string) ($row['sku'] ?? ''))])
            ->filter(fn ($row) => $row['sku'] !== '');

        foreach ($skus as $row) {
            $taken = Product::where('sku', $row['sku'])->exists()
                || \App\Models\ProductVariant::where('sku', $row['sku'])->where('id', '!=', $row['id'])->exists()
                || $skus->where('sku', $row['sku'])->count() > 1;

            if ($taken || $row['sku'] === $request->input('sku')) {
                throw \Illuminate\Validation\ValidationException::withMessages(["variants.{$row['index']}.sku" => "The SKU {$row['sku']} is already used."]);
            }
        }
    }

    /**
     * Stock per product (when it has no colours) and per colour, before an edit.
     */
    private function stockSnapshot(Product $product): array
    {
        $variants = $product->variants()->get();

        return [
            'product' => $variants->isEmpty() ? (int) $product->stock_quantity : 0,
            'variants' => $variants->mapWithKeys(fn ($v) => [$v->id => ['stock' => (int) $v->stock_quantity, 'name' => $v->color_name]])->all(),
        ];
    }

    /**
     * Write the stock history for any stock changed on the product form.
     */
    private function logStockChanges(Product $product, array $before, string $type, string $note, Request $request): void
    {
        $inventory = app(\App\Services\InventoryService::class);
        $product->refresh();
        $after = $this->stockSnapshot($product);
        $by = $request->user();

        $inventory->recordExternalChange($product, null, $before['product'], $after['product'], $type, $note, $by);

        foreach ($before['variants'] + $after['variants'] as $id => $info) {
            $old = $before['variants'][$id]['stock'] ?? 0;
            $new = $after['variants'][$id]['stock'] ?? 0;
            $variant = isset($after['variants'][$id]) ? $product->variants()->find($id) : null;
            $label = $variant ? $note : "Colour {$info['name']} removed";

            $inventory->recordExternalChange($product, $variant, $old, $new, isset($before['variants'][$id]) ? $type : 'opening', $label, $by);
        }
    }

    /**
     * Add newly uploaded gallery photos and remove the ticked ones.
     */
    private function syncGallery(Request $request, Product $product): void
    {
        $product->images()->whereIn('id', $request->input('remove_images', []))->get()
            ->each(function (ProductImage $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            });

        $sortOrder = (int) $product->images()->max('sort_order') + 1;
        foreach ($request->file('images', []) as $file) {
            $product->images()->create([
                'image_path' => $file->store('products', 'public'),
                'sort_order' => $sortOrder++,
            ]);
        }
    }
}
