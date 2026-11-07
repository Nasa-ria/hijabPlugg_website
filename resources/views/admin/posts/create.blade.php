@extends('layouts.admin')

@section('page_title', 'New Blog Post')

@section('content')
<div class="admin-card">
    <h1 class="font-serif text-2xl text-brand-900 mb-6">New Blog Post</h1>

    <form action="{{ route('admin.posts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @include('admin.posts._form')

        <button type="submit" class="btn-brand">Save Post</button>
    </form>
</div>
@endsection
