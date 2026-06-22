@extends('layouts.admin')

@section('page_title', 'Newsletter Subscribers')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold">Newsletter Subscribers</h1>
            <p class="text-gray-500 mt-2">Manage subscriber segments, search by email, and monitor unsubscribe activity.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.newsletters.subscribers') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition">
                <i class="fas fa-users"></i> Subscribers
            </a>
            <a href="{{ route('admin.newsletters.index') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition">
                <i class="fas fa-arrow-left"></i> Campaigns
            </a>
            <a href="{{ route('admin.newsletters.create') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition">
                <i class="fas fa-plus"></i> New Campaign
            </a>
        </div>
    </div>

    <form method="GET" action="" class="grid grid-cols-1 lg:grid-cols-[1fr_220px_180px] gap-4 mb-6">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by email" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
        <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg">
            <option value="">All statuses</option>
            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="unsubscribed" {{ request('status') === 'unsubscribed' ? 'selected' : '' }}>Unsubscribed</option>
        </select>
        <button type="submit" class="inline-flex items-center justify-center px-5 py-3 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition">Filter</button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-sm uppercase text-gray-500 border-b border-gray-200">
                    <th class="px-4 py-4">Email</th>
                    <th class="px-4 py-4">Subscribed</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Unsubscribed At</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse($subscribers as $subscriber)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-5 text-gray-900">{{ $subscriber->email }}</td>
                        <td class="px-4 py-5 text-gray-500 text-sm">{{ $subscriber->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-5">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $subscriber->isUnsubscribed() ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $subscriber->isUnsubscribed() ? 'Unsubscribed' : 'Active' }}
                            </span>
                        </td>
                        <td class="px-4 py-5 text-gray-500 text-sm">{{ optional($subscriber->unsubscribed_at)->format('M d, Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-8 text-center text-gray-500">No subscribers match your search criteria.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $subscribers->links() }}
    </div>
</div>
@endsection
