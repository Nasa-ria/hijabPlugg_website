@extends('layouts.app')

@section('title', 'Returns & Exchanges - Nasaari Store')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-cream-100 via-cream-50 to-cream-200 py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
            <h1 class="section-heading">Returns & Exchanges</h1>
            <p class="text-gray-600 text-sm">Effective Date: May 24, 2026</p>
        </div>

        <div class="card-panel p-8 space-y-8">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">1. Return Window</h2>
                <p class="text-gray-600 text-sm leading-relaxed">All returns and exchanges must be initiated within 30 days of delivery. Items received after this window cannot be accepted for return or exchange.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Exchange Policy</h2>
                <p class="text-gray-600 text-sm mb-3 leading-relaxed">We're happy to exchange items for:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-600 text-sm">
                    <li>Different sizes (if available in stock)</li>
                    <li>Different colors or styles from the same item</li>
                    <li>Defective or damaged items</li>
                </ul>
                <p class="text-gray-600 text-sm mt-3 leading-relaxed">Exchanges are processed free of charge. If the replacement item is more expensive, you'll be invoiced for the difference. If it's less expensive, we'll refund the difference.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Exchange Process</h2>
                <ol class="list-decimal list-inside space-y-2 text-gray-600 text-sm">
                    <li>Contact us at support@nasaari.store with your order number</li>
                    <li>Specify the size, color, or style you'd like instead</li>
                    <li>Receive a prepaid return shipping label</li>
                    <li>Ship the original item back in original packaging</li>
                    <li>Your replacement item will ship within 2-3 business days after we receive the return</li>
                </ol>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">4. Condition Requirements</h2>
                <p class="text-gray-600 text-sm mb-3 leading-relaxed">For successful exchanges and returns, items must be:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-600 text-sm">
                    <li>Unworn and unwashed (except for defective items)</li>
                    <li>In original packaging with all tags attached</li>
                    <li>Not damaged or altered</li>
                    <li>Accompanied by proof of purchase</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Shipping Costs</h2>
                <p class="text-gray-600 text-sm leading-relaxed">We provide prepaid return shipping labels for all valid returns and exchanges. Return shipping is completely free for eligible items.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Item Not Available</h2>
                <p class="text-gray-600 text-sm leading-relaxed">If your desired size or style is out of stock, we'll either place it on backorder for you or process a refund, your choice. If placed on backorder, we'll notify you when it becomes available.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Defective Items</h2>
                <p class="text-gray-600 text-sm leading-relaxed">If you receive an item with defects or damage due to shipping, contact us immediately with photos. We'll replace the item or issue a refund at no cost to you, including return shipping.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">8. Tracking Your Return</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Use the prepaid return label to track your package. Once we receive your return, we'll send you a confirmation email and process your exchange or refund within 5-7 business days.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Questions?</h2>
                <p class="text-gray-600 text-sm leading-relaxed">For any questions about returns or exchanges, contact our customer service team at <a href="mailto:support@nasaari.store" class="text-brand-600 hover:text-brand-700 font-medium">support@nasaari.store</a> or call <a href="tel:+1234567890" class="text-brand-600 hover:text-brand-700 font-medium">+1 (234) 567-8900</a></p>
            </section>
        </div>
    </div>
</div>
@endsection
