@extends('layouts.app')

@section('title', 'Shipping Policy - Hijabis Plugg')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-100 py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
            <h1 class="section-heading">Shipping Policy</h1>
            <p class="text-gray-600 text-sm">Effective Date: May 24, 2026</p>
        </div>

        <div class="card-panel p-8 space-y-8">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Shipping Methods</h2>
                <p class="text-gray-600 text-sm mb-4 leading-relaxed">We offer several shipping options for your convenience:</p>
                <div class="space-y-3">
                    <div class="border-l-4 border-brand-600 pl-4">
                        <p class="font-semibold text-gray-900 text-sm">Standard Shipping (5-7 Business Days)</p>
                        <p class="text-gray-600 text-sm">$5.99 - Reliable and affordable</p>
                    </div>
                    <div class="border-l-4 border-brand-600 pl-4">
                        <p class="font-semibold text-gray-900 text-sm">Express Shipping (2-3 Business Days)</p>
                        <p class="text-gray-600 text-sm">$12.99 - Faster delivery</p>
                    </div>
                    <div class="border-l-4 border-brand-600 pl-4">
                        <p class="font-semibold text-gray-900 text-sm">Overnight Shipping (Next Business Day)</p>
                        <p class="text-gray-600 text-sm">$24.99 - Premium and fastest option</p>
                    </div>
                </div>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Order Processing</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Orders are processed within 1-2 business days. You will receive a confirmation email with tracking information once your order ships. Shipping times do not include processing time.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Free Shipping</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Free standard shipping is available on orders over $50 within the continental United States.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">4. International Shipping</h2>
                <p class="text-gray-600 text-sm mb-3 leading-relaxed">We currently ship to select countries. International orders may be subject to customs duties and taxes. Shipping times vary based on the destination country.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Tracking Your Order</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Once your order ships, you will receive a tracking number via email. You can track your package status in real-time using our website or the carrier's tracking system.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Lost or Damaged Shipments</h2>
                <p class="text-gray-600 text-sm leading-relaxed">If your package arrives damaged or goes missing, please contact us immediately with photos and your order number. We will investigate and provide a replacement or refund.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Address Changes</h2>
                <p class="text-gray-600 text-sm leading-relaxed">If you need to change your shipping address, please contact us within 24 hours of placing your order, before it ships.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Us</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Questions about shipping? Email us at <a href="mailto:support@hijabisplug.com" class="text-brand-600 hover:text-brand-700 font-medium">support@hijabisplug.com</a></p>
            </section>
        </div>
    </div>
</div>
@endsection
