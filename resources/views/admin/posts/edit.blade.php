@extends('layouts.admin')

@section('page_title', 'Edit Blog Post')

@section('content')
<div class="admin-card">
    <div class="flex items-center justify-between mb-6">
        <h1 class="font-serif text-2xl text-brand-900">Edit Blog Post</h1>
        <a href="{{ route('blog.show', $post) }}" target="_blank" class="text-brand-600 hover:underline"><i class="fas fa-eye mr-1"></i>View post</a>
    </div>

    <form action="{{ route('admin.posts.update', $post) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @method('PUT')
        @include('admin.posts._form')

        <button type="submit" class="btn-brand">Update Post</button>
    </form>
</div>
@endsection
