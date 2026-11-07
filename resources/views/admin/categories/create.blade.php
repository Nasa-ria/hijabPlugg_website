@extends('layouts.admin')

@section('page_title', 'Add Category')

@section('content')
<div class="admin-card">
    <h1 class="font-serif text-2xl text-brand-900 mb-6">Add Category</h1>

    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div>
            <label class="admin-label">Category Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="input-field">
            @error('name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="admin-label">Description</label>
            <textarea name="description" rows="4" class="input-field">{{ old('description') }}</textarea>
            @error('description')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="admin-label">Category Image</label>
            <input type="file" name="image" class="w-full">
            @error('image')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="btn-brand">Create Category</button>
    </form>
</div>
@endsection