@extends('layouts.admin')

@section('page_title', 'Add Product')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8">
    <h1 class="text-3xl font-bold mb-6">Add Product</h1>

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block font-bold mb-2">Product Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-bold mb-2">Category</label>
                <select name="category_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('category_id')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label class="block font-bold mb-2">Short Description</label>
            <input type="text" name="short_description" value="{{ old('short_description') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            @error('short_description')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-bold mb-2">Description</label>
            <textarea name="description" rows="6" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('description') }}</textarea>
            @error('description')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block font-bold mb-2">Price</label>
                <input type="number" name="price" step="0.01" min="0" value="{{ old('price') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('price')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-bold mb-2">Sale Price</label>
                <input type="number" name="sale_price" step="0.01" min="0" value="{{ old('sale_price') }}" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('sale_price')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-bold mb-2">Stock Quantity</label>
                <input type="number" name="stock_quantity" min="0" value="{{ old('stock_quantity', 0) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                @error('stock_quantity')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block font-bold mb-2">Main Image</label>
                <input type="file" name="main_image" class="w-full">
                @error('main_image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block font-bold mb-2">Additional Images</label>
                <input type="file" name="images[]" multiple class="w-full">
                @error('images.*')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
        </div>

        <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 transition">Save Product</button>
    </form>
</div>
