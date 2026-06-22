@extends('layouts.admin')

@section('page_title', 'Newsletter Campaigns')

@section('content')
<div class="bg-white rounded-lg shadow-md p-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold">Newsletter Campaigns</h1>
            <p class="text-gray-500 mt-2">Create, preview, and send premium email campaigns to your subscribers.</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.newsletters.subscribers') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-brand-600 text-white hover:bg-brand-700 transition">
                <i class="fas fa-users"></i> Subscribers
            </a>
            <a href="{{ route('admin.newsletters.create') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition">
                <i class="fas fa-plus"></i> New Campaign
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="text-sm uppercase text-gray-500 border-b border-gray-200">
                    <th class="px-4 py-4">Title</th>
                    <th class="px-4 py-4">Subject</th>
                    <th class="px-4 py-4">Status</th>
                    <th class="px-4 py-4">Created</th>
                    <th class="px-4 py-4">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white">
                @forelse($newsletters as $newsletter)
                    <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-5 text-gray-900 font-medium">{{ $newsletter->title }}</td>
                        <td class="px-4 py-5 text-gray-600">{{ $newsletter->subject }}</td>
                        <td class="px-4 py-5">
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $newsletter->status === 'sent' ? 'bg-emerald-100 text-emerald-700' : ($newsletter->status === 'scheduled' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                                {{ ucfirst($newsletter->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-5 text-gray-500 text-sm">{{ $newsletter->created_at->format('M d, Y') }}</td>
                        <td class="px-4 py-5 space-x-2 text-sm">
                            <a href="{{ route('admin.newsletters.preview', $newsletter) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 transition"><i class="fas fa-eye"></i> Preview</a>
                            <a href="{{ route('admin.newsletters.edit', $newsletter) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 transition"><i class="fas fa-edit"></i> Edit</a>
                            <form action="{{ route('admin.newsletters.send', $newsletter) }}" method="POST" class="inline-block">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-purple-600 text-white hover:bg-purple-700 transition">
                                    <i class="fas fa-paper-plane"></i>
                                    {{ $newsletter->status === 'sent' ? 'Resend' : 'Send' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500">No newsletter campaigns found. Create your first campaign.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $newsletters->links() }}
    </div>
</div>
@endsection
