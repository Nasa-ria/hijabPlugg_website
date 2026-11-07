@extends('layouts.admin')

@section('page_title', 'Categories')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="font-serif text-2xl text-brand-900">Categories</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn-brand btn-sm">
        <i class="fas fa-plus mr-2"></i>Add Category
    </a>
</div>

@if($categories->count() > 0)
    <div class="admin-card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-cream-50 border-b border-cream-300">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-[0.15em]">Name</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-[0.15em]">Products</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-[0.15em]">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-[0.15em]">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-cream-200">
                    @foreach($categories as $category)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $category->name }}</div>
                                <div class="text-sm text-gray-500">{{ Str::limit($category->description, 60) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $category->products()->count() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($category->is_active)
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-800 ring-emerald-200">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset bg-red-50 text-red-800 ring-red-200">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn-secondary btn-sm mr-2"><i class="fas fa-edit" aria-hidden="true"></i> Edit</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete category?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash" aria-hidden="true"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $categories->links() }}
    </div>
@else
    <div class="admin-card !p-12 text-center">
        <i class="fas fa-list text-6xl text-gray-300 mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-600 mb-2">No categories yet</h2>
        <p class="text-gray-500">Create a new category to group your products.</p>
    </div>
@endif
@endsection