@extends('layouts.app')

@section('title', 'Size Guide - Hijabis Plugg')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-100 py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-12">
            <h1 class="section-heading">Size Guide</h1>
            <p class="section-subheading max-w-2xl mx-auto">Find your perfect fit! Our size guide helps you choose the right size for every item.</p>
        </div>

        <div class="space-y-8">
            <!-- Hijabs Size Guide -->
            <div class="card-panel p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Hijab Sizes</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-brand-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">Size</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">Length</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">Width</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">Best For</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">One Size</td>
                                <td class="px-4 py-3 text-gray-600">180 cm</td>
                                <td class="px-4 py-3 text-gray-600">75 cm</td>
                                <td class="px-4 py-3 text-gray-600">Universal fit</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">Petite</td>
                                <td class="px-4 py-3 text-gray-600">160 cm</td>
                                <td class="px-4 py-3 text-gray-600">70 cm</td>
                                <td class="px-4 py-3 text-gray-600">Shorter individuals</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">Premium</td>
                                <td class="px-4 py-3 text-gray-600">200 cm</td>
                                <td class="px-4 py-3 text-gray-600">80 cm</td>
                                <td class="px-4 py-3 text-gray-600">Extra coverage</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Clothing Size Guide -->
            <div class="card-panel p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">Clothing Sizes</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-brand-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">Size</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">XS</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">S</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">M</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">L</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">XL</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-900">2XL</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">Bust</td>
                                <td class="px-4 py-3 text-gray-600">81 cm</td>
                                <td class="px-4 py-3 text-gray-600">86 cm</td>
                                <td class="px-4 py-3 text-gray-600">91 cm</td>
                                <td class="px-4 py-3 text-gray-600">97 cm</td>
                                <td class="px-4 py-3 text-gray-600">102 cm</td>
                                <td class="px-4 py-3 text-gray-600">109 cm</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-gray-900">Length</td>
                                <td class="px-4 py-3 text-gray-600">64 cm</td>
                                <td class="px-4 py-3 text-gray-600">66 cm</td>
                                <td class="px-4 py-3 text-gray-600">68 cm</td>
                                <td class="px-4 py-3 text-gray-600">71 cm</td>
                                <td class="px-4 py-3 text-gray-600">74 cm</td>
                                <td class="px-4 py-3 text-gray-600">76 cm</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- How to Measure -->
            <div class="card-panel p-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-6">How to Measure</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Bust</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Measure around the fullest part of your chest with the tape measure parallel to the ground. Keep the tape measure snug but not tight.</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Length</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Measure from the shoulder down along the side seam to your desired hem length. Keep your arms relaxed at your sides.</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Waist</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Measure around your natural waistline without pulling too tight. This is typically at the narrowest part of your torso.</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 mb-3">Hips</h3>
                        <p class="text-gray-600 text-sm leading-relaxed">Measure around the fullest part of your hips, approximately 20 cm below your waist. Keep the tape measure level all the way around.</p>
                    </div>
                </div>
            </div>

            <!-- Fit Tips -->
            <div class="card-panel p-8 border-l-4 border-brand-600">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Fit Tips</h2>
                <ul class="space-y-3 text-gray-600 text-sm">
                    <li class="flex gap-3">
                        <span class="text-brand-600 font-bold">•</span>
                        <span>All measurements should be taken while wearing light, close-fitting clothing</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-brand-600 font-bold">•</span>
                        <span>Always refer to the specific product's size chart for the most accurate fit</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-brand-600 font-bold">•</span>
                        <span>Different styles may fit differently - check individual product descriptions</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-brand-600 font-bold">•</span>
                        <span>When in doubt between two sizes, we recommend choosing the larger size for comfort</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="text-brand-600 font-bold">•</span>
                        <span>Have a friend help you measure for the most accurate results</span>
                    </li>
                </ul>
            </div>

            <!-- Still Unsure -->
            <div class="card-panel p-8 bg-brand-50">
                <h2 class="text-xl font-semibold text-gray-900 mb-3">Still Unsure About Your Size?</h2>
                <p class="text-gray-600 text-sm mb-4 leading-relaxed">Our customer service team is happy to help! Reach out with your measurements and we'll recommend the perfect size for you.</p>
                <a href="{{ route('pages.contact') }}" class="btn-brand inline-block">Contact Us</a>
            </div>
        </div>
    </div>
</div>
@endsection
