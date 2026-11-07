@extends('layouts.admin')

@section('page_title', 'Order ' . $order->order_number)

@php
    $money = fn ($v) => '₵' . number_format($v, 2);
    $next = $order->nextStatus();
    $canReturn = $order->payment_status === 'paid' && auth()->user()->canAccessAdmin('inventory');
@endphp

@section('content')
<div class="space-y-6">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-accent-700 hover:text-accent-800">
        <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i> All orders
    </a>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.6fr_1fr]">
        <div class="space-y-6">
            {{-- Status --}}
            <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6" aria-labelledby="status-heading">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="status-heading" class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Order status</h2>
                        <div class="mt-2 flex flex-wrap items-center gap-3">
                            <x-admin.status-badge :status="$order->status" class="!text-sm !px-4 !py-1.5" />
                            <x-admin.payment-badge :status="$order->payment_status" />
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Placed {{ $order->created_at->format('j M Y, g:i a') }}</p>
                </div>

                {{-- Progress through the stages --}}
                @if($order->status !== 'cancelled')
                    @php $step = array_search($order->status, \App\Models\Order::FLOW, true); @endphp
                    <ol class="mt-6 grid grid-cols-4 gap-2" aria-label="Progress">
                        @foreach(\App\Models\Order::FLOW as $i => $stage)
                            <li>
                                <div class="h-1.5 rounded-full {{ $i <= $step ? 'bg-accent-500' : 'bg-cream-300' }}"></div>
                                <p class="mt-2 text-xs {{ $i <= $step ? 'font-semibold text-brand-900' : 'text-gray-400' }}">{{ \App\Models\Order::STATUSES[$stage]['label'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                @endif

                {{-- Change status --}}
                <div class="mt-6 flex flex-col gap-3 border-t border-cream-200 pt-6 sm:flex-row sm:items-center">
                    @if($next)
                        <button type="button" class="btn-brand" data-status-change="{{ $next }}">
                            Move to {{ \App\Models\Order::STATUSES[$next]['label'] }} <i class="fas fa-arrow-right text-xs" aria-hidden="true"></i>
                        </button>
                    @endif
                    <div class="flex flex-1 gap-2">
                        <label for="status-select" class="sr-only">Change status to</label>
                        <select id="status-select" class="input-field !py-2.5">
                            <option value="">Change status to…</option>
                            @foreach(\App\Models\Order::STATUSES as $value => $config)
                                @continue($value === $order->status)
                                <option value="{{ $value }}">{{ $config['label'] }}</option>
                            @endforeach
                        </select>
                        <button type="button" class="btn-secondary !py-2.5" id="status-select-apply">Update</button>
                    </div>
                </div>
            </section>

            {{-- Items --}}
            <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
                <h2 class="font-serif text-xl text-brand-900">Items</h2>
                <ul class="mt-4 divide-y divide-cream-200">
                    @foreach($order->items as $item)
                        @php $path = $item->variant?->imagePath() ?? $item->product?->main_image; @endphp
                        <li class="flex items-center gap-4 py-4">
                            <div class="h-20 w-16 flex-shrink-0 overflow-hidden rounded-lg bg-cream-200">
                                @if($path)<img src="{{ asset('storage/' . $path) }}" alt="" class="h-full w-full object-cover">@endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-brand-900">{{ $item->product?->name ?? 'Deleted product' }}</p>
                                <p class="text-sm text-gray-500">
                                    @if($item->color_name)Colour: {{ $item->color_name }} · @endif
                                    Qty {{ $item->quantity }} × {{ $money($item->price) }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-brand-900">{{ $money($item->subtotal) }}</p>
                                @if($item->unit_cost !== null)
                                    <p class="text-xs text-gray-500">Cost {{ $money($item->unit_cost * $item->quantity) }}</p>
                                @endif
                                @if(($returned = $item->returns->sum('quantity')) > 0)
                                    <p class="text-xs font-semibold text-accent-700">{{ $returned }} returned</p>
                                @endif
                            </div>
                        </li>
                        @if($canReturn && $item->quantity > $item->returns->sum('quantity'))
                            <li class="pb-4">
                                <details class="rounded-xl border border-cream-300 bg-cream-50 p-4">
                                    <summary class="cursor-pointer text-sm font-semibold text-accent-700">Record a return</summary>
                                    <form method="POST" action="{{ route('admin.inventory.returns.store') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                        @csrf
                                        <input type="hidden" name="order_item_id" value="{{ $item->id }}">
                                        <div>
                                            <label class="admin-label" for="ret-qty-{{ $item->id }}">Units returned</label>
                                            <input id="ret-qty-{{ $item->id }}" type="number" name="quantity" min="1" max="{{ $item->quantity - $item->returns->sum('quantity') }}" value="1" required class="input-field !py-2">
                                        </div>
                                        <div>
                                            <label class="admin-label" for="ret-refund-{{ $item->id }}">Refund (₵)</label>
                                            <input id="ret-refund-{{ $item->id }}" type="number" name="refund_amount" step="0.01" min="0" placeholder="Full price paid" class="input-field !py-2">
                                        </div>
                                        <fieldset class="sm:col-span-2">
                                            <legend class="admin-label">Condition</legend>
                                            <div class="flex flex-wrap gap-4">
                                                @foreach(\App\Models\ProductReturn::CONDITIONS as $value => $label)
                                                    <label class="inline-flex items-center gap-2"><input type="radio" name="condition" value="{{ $value }}" @checked($value === 'resellable') required class="text-accent-600"> {{ $label }}</label>
                                                @endforeach
                                            </div>
                                        </fieldset>
                                        <div class="sm:col-span-2">
                                            <label class="admin-label" for="ret-reason-{{ $item->id }}">Reason</label>
                                            <input id="ret-reason-{{ $item->id }}" type="text" name="reason" maxlength="255" placeholder="e.g. Wrong colour, torn seam" class="input-field !py-2">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <button type="submit" class="btn-brand btn-sm">Save return</button>
                                        </div>
                                    </form>
                                </details>
                            </li>
                        @endif
                    @endforeach
                </ul>
                <dl class="mt-4 space-y-1.5 border-t border-cream-300 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd>{{ $money($order->subtotal) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">{{ $order->isPayOnDelivery() ? 'Delivery (customer pays on delivery)' : ($order->isDhl() ? 'DHL shipping charged' : 'Delivery') }}</dt><dd>{{ $money($order->shipping_cost) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Tax</dt><dd>{{ $money($order->tax) }}</dd></div>
                    <div class="flex justify-between pt-2 text-base font-semibold text-brand-900"><dt>Total</dt><dd>{{ $money($order->total_amount) }}</dd></div>
                    @if($order->display_currency && $order->display_currency !== $order->currency)
                        <div class="flex justify-between text-xs text-gray-500"><dt>Customer saw prices in {{ $order->display_currency }}</dt><dd>{{ $order->displayTotal() }}</dd></div>
                    @endif
                    @if((float) $order->payment_fees > 0)
                        <div class="flex justify-between text-xs text-gray-500"><dt>Paystack fees</dt><dd>{{ $money($order->payment_fees) }}</dd></div>
                    @endif
                    @if(($refunded = $order->returns->sum('refund_amount')) > 0)
                        <div class="flex justify-between text-xs font-semibold text-accent-700"><dt>Refunded for returns</dt><dd>−{{ $money($refunded) }}</dd></div>
                    @endif
                </dl>
            </section>
        </div>

        <div class="space-y-6">
            {{-- Customer --}}
            <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
                <h2 class="font-serif text-xl text-brand-900">Customer</h2>
                <dl class="mt-4 space-y-3 text-sm">
                    <div><dt class="text-gray-500">Name</dt><dd class="font-medium text-brand-900">{{ $order->customer_name ?? $order->user?->name }}</dd></div>
                    <div><dt class="text-gray-500">Email</dt><dd><a href="mailto:{{ $order->contactEmail() }}" class="font-medium text-accent-700 hover:underline">{{ $order->contactEmail() }}</a></dd></div>
                    <div><dt class="text-gray-500">Phone</dt><dd>
                        @if($order->customer_phone)
                            <a href="tel:{{ $order->customer_phone }}" class="font-medium text-accent-700 hover:underline">{{ $order->customer_phone }}</a>
                        @else
                            <span class="text-gray-400">Not recorded</span>
                        @endif
                    </dd></div>
                    <div><dt class="text-gray-500">Delivery address</dt><dd class="font-medium text-brand-900">{{ $order->shipping_address }}</dd></div>
                </dl>
            </section>

            {{-- Shipping --}}
            <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
                <h2 class="font-serif text-xl text-brand-900">Shipping</h2>
                @if($order->isPayOnDelivery())
                    <p class="mt-2 text-sm text-gray-600"><i class="fas fa-truck text-accent-500 mr-1" aria-hidden="true"></i>Ghana delivery. The customer pays the courier on delivery; nothing was charged for shipping.</p>
                @elseif($order->isDhl())
                    @php $quote = $order->shipping_quote ?? []; @endphp
                    <dl class="mt-3 space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Service</dt><dd class="font-medium text-brand-900">{{ $quote['service'] ?? 'DHL Express' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Estimate (charged)</dt><dd class="font-medium text-brand-900">{{ $money($order->shipping_estimate ?? $order->shipping_cost) }}</dd></div>
                        @if(! empty($quote['provider_currency']) && $quote['provider_currency'] !== 'GHS')
                            <div class="flex justify-between text-xs text-gray-500"><dt>DHL quoted</dt><dd>{{ $quote['provider_currency'] }} {{ number_format($quote['provider_amount'], 2) }}</dd></div>
                        @endif
                        @if(! empty($quote['chargeable_weight']))
                            <div class="flex justify-between text-xs text-gray-500"><dt>Weight</dt><dd>{{ $quote['actual_weight'] }} kg actual · {{ $quote['chargeable_weight'] }} kg charged · {{ count($quote['packages'] ?? []) }} {{ Str::plural('box', count($quote['packages'] ?? [])) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-gray-500">Final DHL charge</dt><dd class="font-medium {{ $order->shipping_final_cost !== null ? 'text-brand-900' : 'text-amber-700' }}">{{ $order->shipping_final_cost !== null ? $money($order->shipping_final_cost) : 'Not confirmed yet' }}</dd></div>
                        @if($order->shipping_final_cost !== null)
                            @php $diff = (float) $order->shipping_cost - (float) $order->shipping_final_cost; @endphp
                            <div class="flex justify-between text-xs"><dt class="text-gray-500">Difference</dt><dd class="{{ $diff >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $diff >= 0 ? '+' : '−' }}{{ $money(abs($diff)) }}</dd></div>
                        @endif
                    </dl>
                    <form method="POST" action="{{ route('admin.orders.shipping', $order) }}" class="mt-4 flex gap-2">
                        @csrf
                        <label for="final-shipping" class="sr-only">Final DHL charge (₵)</label>
                        <input id="final-shipping" type="number" name="shipping_final_cost" step="0.01" min="0" value="{{ $order->shipping_final_cost }}" placeholder="Final DHL charge (₵)" class="input-field !py-2.5">
                        <button type="submit" class="btn-secondary !py-2.5">Save</button>
                    </form>
                    <p class="mt-2 text-xs text-gray-500">Enter what DHL actually billed once shipped. The customer is not charged again.</p>
                @else
                    <p class="mt-2 text-sm text-gray-500">Placed before shipping methods were recorded.</p>
                @endif
            </section>

            {{-- Payment --}}
            <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
                <h2 class="font-serif text-xl text-brand-900">Payment</h2>
                <p class="mt-2 text-sm text-gray-500">
                    @if($order->paid_at)Paid {{ $order->paid_at->format('j M Y, g:i a') }}@else Not paid yet @endif
                    @if($order->transaction_id)<br>Ref: {{ $order->transaction_id }}@endif
                </p>
                <form action="{{ route('admin.orders.update-payment-status', $order) }}" method="POST" class="mt-4 flex gap-2">
                    @csrf
                    <label for="payment-status" class="sr-only">Payment status</label>
                    <select id="payment-status" name="payment_status" class="input-field !py-2.5">
                        @foreach(['unpaid' => 'Unpaid', 'paid' => 'Paid', 'failed' => 'Failed'] as $value => $label)
                            <option value="{{ $value }}" @selected($order->payment_status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn-secondary !py-2.5">Save</button>
                </form>
            </section>

            {{-- History --}}
            <section class="rounded-2xl border border-cream-300 bg-white p-5 sm:p-6">
                <h2 class="font-serif text-xl text-brand-900">Status history</h2>
                <x-admin.status-timeline :order="$order" class="mt-5 ml-2" />
            </section>
        </div>
    </div>
</div>

{{-- Confirm status change --}}
<dialog id="statusConfirm" class="w-[min(28rem,calc(100vw-2rem))] rounded-2xl p-0 backdrop:bg-brand-dark/40 backdrop:backdrop-blur-sm" aria-labelledby="statusConfirmTitle">
    <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="p-6">
        @csrf
        <input type="hidden" name="status" value="">
        <h2 id="statusConfirmTitle" class="font-serif text-2xl text-brand-900">Change status to <span data-confirm-label></span>?</h2>

        <div class="mt-4 rounded-xl bg-cream-100 p-4 text-sm" data-email-on>
            <p class="flex items-start gap-2 text-brand-900"><i class="fas fa-envelope mt-0.5 text-accent-600" aria-hidden="true"></i>
                <span>This will email the customer at <strong>{{ $order->contactEmail() }}</strong>.</span></p>
            <label class="mt-3 flex items-center gap-2 text-gray-600">
                <input type="checkbox" class="rounded border-gray-300" data-skip-email> Don't send an email this time
            </label>
        </div>
        <p class="mt-4 rounded-xl bg-cream-100 p-4 text-sm text-gray-600 hidden" data-email-already>
            <i class="fas fa-circle-info mr-1 text-accent-600" aria-hidden="true"></i>
            The customer was already emailed about this status, so no email will be sent.
        </p>
        <input type="hidden" name="notify" value="1">

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" class="btn-secondary" data-confirm-cancel>Cancel</button>
            <button type="submit" class="btn-brand">Change status</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
    (function () {
        const dialog = document.getElementById('statusConfirm');
        const form = dialog.querySelector('form');
        const labels = @json(collect(\App\Models\Order::STATUSES)->map->label);
        const emailed = @json($emailedStatuses);

        function confirmChange(status) {
            if (!status) return;
            const already = emailed.includes(status);
            form.elements.status.value = status;
            dialog.querySelector('[data-confirm-label]').textContent = labels[status];
            dialog.querySelector('[data-email-on]').classList.toggle('hidden', already);
            dialog.querySelector('[data-email-already]').classList.toggle('hidden', !already);
            dialog.querySelector('[data-skip-email]').checked = false;
            form.elements.notify.value = already ? '0' : '1';
            dialog.showModal();
        }

        dialog.querySelector('[data-skip-email]').addEventListener('change', (e) => {
            form.elements.notify.value = e.target.checked ? '0' : '1';
        });
        dialog.querySelector('[data-confirm-cancel]').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', (e) => { if (e.target === dialog) dialog.close(); });

        document.querySelectorAll('[data-status-change]').forEach(btn =>
            btn.addEventListener('click', () => confirmChange(btn.dataset.statusChange)));
        document.getElementById('status-select-apply').addEventListener('click', () =>
            confirmChange(document.getElementById('status-select').value));
    })();
</script>
@endpush
