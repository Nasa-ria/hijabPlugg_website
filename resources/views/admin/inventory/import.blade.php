@extends('layouts.admin')

@section('page_title', 'Excel import & export')

@section('content')
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    {{-- Export --}}
    <section class="admin-card space-y-5" aria-labelledby="export-title">
        <div>
            <h2 id="export-title" class="font-serif text-2xl text-brand-900"><i class="fas fa-file-arrow-down text-accent-500 text-xl mr-2" aria-hidden="true"></i>Export inventory &amp; sales</h2>
            <p class="mt-1 text-sm text-gray-600">An Excel workbook with stock, sales, cost, profit and loss per product and colour, a money summary, and the full stock history for the period. All amounts are GHS.</p>
        </div>
        <form method="GET" action="{{ route('admin.inventory.export') }}" class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="admin-label" for="exp-from">From</label>
                    <input id="exp-from" type="date" name="from" value="{{ now()->subDays(29)->toDateString() }}" required class="input-field">
                </div>
                <div>
                    <label class="admin-label" for="exp-to">To</label>
                    <input id="exp-to" type="date" name="to" value="{{ now()->toDateString() }}" required class="input-field">
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="admin-label" for="exp-category">Category</label>
                    <select id="exp-category" name="category_id" class="input-field">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="admin-label" for="exp-product">Product</label>
                    <select id="exp-product" name="product_id" class="input-field">
                        <option value="">All products</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                @foreach(['This month' => [now()->startOfMonth(), now()], 'Last month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()], 'This year' => [now()->startOfYear(), now()]] as $label => [$from, $to])
                    <button type="button" class="rounded-full border border-cream-300 px-3 py-1 hover:bg-cream-100" data-range-from="{{ $from->toDateString() }}" data-range-to="{{ $to->toDateString() }}">{{ $label }}</button>
                @endforeach
            </div>
            <button type="submit" class="btn-brand w-full"><i class="fas fa-file-excel text-xs" aria-hidden="true"></i> Download .xlsx</button>
        </form>
    </section>

    {{-- Import --}}
    <section class="admin-card space-y-5" aria-labelledby="import-title">
        <div>
            <h2 id="import-title" class="font-serif text-2xl text-brand-900"><i class="fas fa-file-arrow-up text-accent-500 text-xl mr-2" aria-hidden="true"></i>Bulk stock update</h2>
            <p class="mt-1 text-sm text-gray-600">Upload an Excel file to add deliveries or set counted stock for many items at once. The whole file is checked first: if any row has a problem, nothing changes and every problem is listed.</p>
        </div>

        <ol class="list-decimal space-y-1.5 pl-5 text-sm text-gray-600">
            <li><a href="{{ route('admin.inventory.import.template') }}" class="font-semibold text-accent-600 hover:underline">Download the template</a>, which lists every SKU with its current stock.</li>
            <li>For each row, set <strong>Action</strong> to <code>add</code> (a delivery) or <code>set</code> (a stock count), and enter the <strong>Quantity</strong>.</li>
            <li>Optionally enter a new <strong>Purchase cost</strong> and a <strong>Note</strong>. Delete rows you don't want to change.</li>
        </ol>

        <form method="POST" action="{{ route('admin.inventory.import.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <div>
                <label class="admin-label" for="import-file">Excel file (.xlsx)</label>
                <input id="import-file" type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required class="block w-full text-sm">
                @error('file')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="btn-brand w-full"><i class="fas fa-upload text-xs" aria-hidden="true"></i> Check &amp; import</button>
        </form>

        @if(session('import_errors'))
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4" role="alert">
                <p class="font-semibold text-red-800">Problems found ({{ count(session('import_errors')) }})</p>
                <ul class="mt-2 max-h-72 space-y-1 overflow-y-auto text-sm text-red-700">
                    @foreach(session('import_errors') as $problem)
                        <li>{{ $problem }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-range-from]').forEach((b) => b.addEventListener('click', () => {
        document.getElementById('exp-from').value = b.dataset.rangeFrom;
        document.getElementById('exp-to').value = b.dataset.rangeTo;
    }));
</script>
@endpush
