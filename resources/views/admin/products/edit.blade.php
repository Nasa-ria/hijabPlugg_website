@extends('layouts.admin')

@section('page_title', 'Edit Product')

@section('content')
<div class="admin-card">
    <h1 class="font-serif text-2xl text-brand-900 mb-6">Edit Product</h1>

    <form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="admin-label">Product Name</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="input-field">
                @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Category</label>
                <select name="category_id" required class="input-field">
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="admin-label">Short Description</label>
            <input type="text" name="short_description" value="{{ old('short_description', $product->short_description) }}" class="input-field">
            @error('short_description')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="admin-label">Description</label>
            <textarea name="description" rows="6" required class="input-field">{{ old('description', $product->description) }}</textarea>
            @error('description')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="admin-label">Price</label>
                <input type="number" name="price" step="0.01" min="0" value="{{ old('price', $product->price) }}" required class="input-field">
                @error('price')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Sale Price</label>
                <input type="number" name="sale_price" step="0.01" min="0" value="{{ old('sale_price', $product->sale_price) }}" class="input-field">
                @error('sale_price')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', $product->stock_quantity) }}" required class="input-field">
                @error('stock_quantity')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="admin-label">Main Image</label>
                <input type="file" name="main_image" class="w-full">
                @if($product->main_image)
                    <p class="text-gray-500 mt-2">Current: {{ $product->main_image }}</p>
                @endif
                @error('main_image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-4">
                <label class="inline-flex items-center gap-2 mt-1">
                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }} class="rounded border-gray-300 text-accent-600 focus:ring-accent-500">
                    <span>Available</span>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="admin-label">Fabric / Material</label>
                <input type="text" name="material" value="{{ old('material', $product->material) }}" placeholder="e.g. Chiffon" class="input-field">
                @error('material')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Dimensions <span class="font-normal text-gray-500">(veils, scarves)</span></label>
                <input type="text" name="size" value="{{ old('size', $product->size) }}" placeholder="e.g. 180 × 70 cm" class="input-field">
                @error('size')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="admin-label">Size chart <span class="font-normal text-gray-500">(optional, for dresses and abayas)</span></label>
            <p class="text-sm text-gray-500 mb-2">One line per row, values separated by commas. The first line is the heading. Example:<br>
                <code>Size, Bust, Waist, Length</code><br><code>S, 86 cm, 70 cm, 140 cm</code><br><code>M, 92 cm, 76 cm, 142 cm</code></p>
            <textarea name="size_chart" rows="5" class="input-field font-mono text-sm" placeholder="Size, Bust, Waist, Length">{{ old('size_chart', $product->size_chart) }}</textarea>
            @error('size_chart')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        @include('admin.products._inventory')

        @include('admin.products._variants')

        @include('admin.products._gallery')

        <button type="submit" class="btn-brand">Update Product</button>
    </form>
</div>
@endsection
