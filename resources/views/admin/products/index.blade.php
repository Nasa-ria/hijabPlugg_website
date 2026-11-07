@extends('layouts.admin')

@section('page_title', 'Products')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
        <div>
            <h1 class="font-serif text-2xl text-brand-900">Products</h1>
            <p class="text-gray-500 mt-2">Manage your catalog and keep product details up to date.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
            <form action="{{ route('admin.products.index') }}" method="GET" class="flex w-full sm:w-auto gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..." class="input-field rounded-l-lg">
                <button type="submit" class="btn-brand rounded-r-lg">Search</button>
            </form>
            <a href="{{ route('admin.products.create') }}" class="btn-brand">
                <i class="fas fa-plus"></i> Add Product
            </a>
        </div>
    </div>

    <div class="admin-card !p-0 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] text-left border-collapse">
                <thead>
                    <tr class="bg-cream-50 text-xs uppercase tracking-[0.15em] text-gray-500 border-b border-cream-300">
                        <th class="px-4 py-4">Product</th>
                        <th class="px-4 py-4">Category</th>
                        <th class="px-4 py-4">Price</th>
                        <th class="px-4 py-4">Stock</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-4 py-4">Created</th>
                        <th class="px-4 py-4">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-cream-200">
                    @forelse($products as $product)
                        <tr class="hover:bg-cream-50 transition-colors">
                            <td class="px-4 py-5 align-top">
                                <div class="flex items-center gap-4">
                                    <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 flex items-center justify-center">
                                        @if($product->main_image)
                                            <img src="{{ asset('storage/' . $product->main_image) }}" alt="{{ $product->name }}" class="object-cover w-full h-full">
                                        @else
                                            <i class="fas fa-box text-gray-400"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ $product->name }}</div>
                                        <div class="text-sm text-gray-500">{{ Str::limit($product->short_description, 70) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-5 align-top text-gray-700">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                            <td class="px-4 py-5 align-top text-gray-700">₵{{ number_format($product->price, 2) }}</td>
                            <td class="px-4 py-5 align-top text-gray-700">{{ $product->stock_quantity }}</td>
                            <td class="px-4 py-5 align-top">
                                @if($product->is_available)
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-800 ring-emerald-200">Available</span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1 ring-inset bg-red-50 text-red-800 ring-red-200">Hidden</span>
                                @endif
                            </td>
                            <td class="px-4 py-5 align-top text-gray-500 text-sm">{{ $product->created_at->format('M d, Y') }}</td>
                            <td class="px-4 py-5 align-top text-sm space-y-2">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn-secondary btn-sm"><i class="fas fa-edit"></i> Edit</a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500">No products found. Add your first product to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $products->links() }}
    </div>
</div>
@endsection
