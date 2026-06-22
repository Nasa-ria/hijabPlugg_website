@extends('layouts.admin')

@section('page_title', 'Edit Category')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8">
    <h1 class="text-3xl font-bold mb-6">Edit Category</h1>

    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-bold mb-2">Category Name</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-bold mb-2">Description</label>
            <textarea name="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('description', $category->description) }}</textarea>
            @error('description')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
            <div>
                <label class="block font-bold mb-2">Category Image</label>
                <input type="file" name="image" class="w-full">
                @if($category->image)
                    <p class="text-gray-500 mt-2">Current: {{ $category->image }}</p>
                @endif
                @error('image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <label class="inline-flex items-center gap-2 mt-1">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-purple-600">
                    <span>Active</span>
                </label>
            </div>
        </div>

        <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 transition">Update Category</button>
    </form>
</div>
@endsection