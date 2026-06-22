@extends('layouts.app')

@section('title', 'Refund Policy - Hijabis Plugg')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-100 py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
            <h1 class="section-heading">Refund Policy</h1>
            <p class="text-gray-600 text-sm">Effective Date: May 24, 2026</p>
        </div>

        <div class="card-panel p-8 space-y-8">
            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">1. 30-Day Refund Guarantee</h2>
                <p class="text-gray-600 text-sm leading-relaxed">We offer a full 30-day refund guarantee on all items. If you're not satisfied with your purchase, you can return it within 30 days of delivery for a complete refund, no questions asked.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">2. Return Eligibility</h2>
                <p class="text-gray-600 text-sm mb-3 leading-relaxed">Items are eligible for return if they meet the following conditions:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-600 text-sm">
                    <li>Item is unworn, unwashed, and in original condition</li>
                    <li>All tags and packaging are intact</li>
                    <li>Original receipt or proof of purchase</li>
                    <li>Return request submitted within 30 days</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">3. Non-Refundable Items</h2>
                <p class="text-gray-600 text-sm mb-3 leading-relaxed">The following items cannot be refunded:</p>
                <ul class="list-disc list-inside space-y-2 text-gray-600 text-sm">
                    <li>Clearance or final sale items</li>
                    <li>Items on sale or discounted</li>
                    <li>Custom or personalized orders</li>
                    <li>Intimate apparel (for hygiene reasons)</li>
                </ul>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">4. How to Request a Refund</h2>
                <p class="text-gray-600 text-sm mb-3 leading-relaxed">To request a refund:</p>
                <ol class="list-decimal list-inside space-y-2 text-gray-600 text-sm">
                    <li>Contact us at support@hijabisplug.com with your order number</li>
                    <li>Provide photos of the item showing it is unworn</li>
                    <li>Receive a prepaid return shipping label</li>
                    <li>Ship the item back in original packaging</li>
                    <li>Receive your refund within 5-7 business days of return receipt</li>
                </ol>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">5. Shipping Costs</h2>
                <p class="text-gray-600 text-sm leading-relaxed">We provide prepaid return shipping labels for eligible items. If you choose to use your own carrier, you are responsible for return shipping costs.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">6. Defective Items</h2>
                <p class="text-gray-600 text-sm leading-relaxed">If you receive a defective item, please contact us within 7 days with photos and documentation. We will replace the item or issue a full refund, including return shipping costs.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">7. Processing Refunds</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Refunds are processed to your original payment method within 5-7 business days after we receive and verify your return. Depending on your bank or credit card company, it may take an additional 3-5 business days for the credit to appear.</p>
            </section>

            <section>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Contact Us</h2>
                <p class="text-gray-600 text-sm leading-relaxed">Questions about our refund policy? Email us at <a href="mailto:support@hijabisplug.com" class="text-brand-600 hover:text-brand-700 font-medium">support@hijabisplug.com</a></p>
            </section>
        </div>
    </div>
</div>
@endsection
