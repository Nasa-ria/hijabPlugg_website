@extends('layouts.admin')

@section('page_title', 'Add Category')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8">
    <h1 class="text-3xl font-bold mb-6">Add Category</h1>

    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div>
            <label class="block font-bold mb-2">Category Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-bold mb-2">Description</label>
            <textarea name="description" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg">{{ old('description') }}</textarea>
            @error('description')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block font-bold mb-2">Category Image</label>
            <input type="file" name="image" class="w-full">
            @error('image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 transition">Create Category</button>
    </form>
</div>
@endsection