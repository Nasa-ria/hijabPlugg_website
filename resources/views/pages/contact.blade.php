@extends('layouts.app')

@section('title', 'Contact Us - Hijabis Plugg')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-brand-100 py-16">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h1 class="section-heading">Get in Touch</h1>
            <p class="section-subheading max-w-2xl mx-auto">Have questions? We're here to help. Reach out to us using the form below or through our contact information.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <div class="card-panel p-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Contact Information</h3>
                <div class="space-y-6">
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-1">Email</p>
                        <a href="mailto:support@hijabisplug.com" class="text-brand-600 hover:text-brand-700 font-medium">support@hijabisplug.com</a>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-1">Phone</p>
                        <a href="tel:+1234567890" class="text-brand-600 hover:text-brand-700 font-medium">+1 (234) 567-8900</a>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-1">Hours</p>
                        <p class="text-gray-600 text-sm">Monday - Friday: 9:00 AM - 6:00 PM (EST)</p>
                        <p class="text-gray-600 text-sm">Saturday & Sunday: 10:00 AM - 4:00 PM (EST)</p>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-1">Follow Us</p>
                        <div class="flex gap-4">
                            <a href="#" class="text-gray-500 hover:text-brand-600"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="text-gray-500 hover:text-brand-600"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="text-gray-500 hover:text-brand-600"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-panel p-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Send us a Message</h3>
                <form class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Name</label>
                        <input id="name" type="text" placeholder="Your name" class="input-field">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <input id="email" type="email" placeholder="you@example.com" class="input-field">
                    </div>
                    <div>
                        <label for="message" class="block text-sm font-semibold text-gray-700 mb-2">Message</label>
                        <textarea id="message" rows="4" placeholder="Your message..." class="input-field resize-none"></textarea>
                    </div>
                    <button type="submit" class="btn-brand w-full">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
