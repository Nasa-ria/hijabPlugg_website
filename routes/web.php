<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PostCommentController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home page
// The shop is the home page; the scroll story lives at /our-story
Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/our-story', [HomeController::class, 'index'])->name('story');

// ==========================================
// PUBLIC STOREFRONT ROUTES
// ==========================================

// Products
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');

// Display currency (GH₵ / US$). Prices are stored and charged in GHS; this only changes what is shown.
Route::post('/preferences/currency', [\App\Http\Controllers\CurrencyController::class, 'update'])->name('currency.update')->middleware('throttle:30,1');

// Shopping Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Reviews
Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');
Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update')->middleware('auth');
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy')->middleware('auth');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{post}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{post}/share', [BlogController::class, 'share'])->name('blog.share')->middleware('throttle:30,1');
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::post('/blog/{post}/like', [LikeController::class, 'togglePost'])->name('blog.like');
    Route::post('/blog/{post}/comments', [PostCommentController::class, 'store'])->name('blog.comments.store');
    Route::delete('/blog/comments/{comment}', [PostCommentController::class, 'destroy'])->name('blog.comments.destroy');
    Route::post('/blog/comments/{comment}/like', [LikeController::class, 'toggleComment'])->name('blog.comments.like');
});

// Anonymous storefront tracking (product clicks / popup views)
Route::post('/track', [\App\Http\Controllers\TrackController::class, 'store'])->name('track')->middleware('throttle:120,1');

// Order tracking for customers (order number + email, no account needed)
Route::get('/track-order', [\App\Http\Controllers\OrderTrackingController::class, 'show'])->name('orders.track');
Route::post('/track-order', [\App\Http\Controllers\OrderTrackingController::class, 'lookup'])->name('orders.track.lookup')->middleware('throttle:10,1');

// Customer Orders and Checkout
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('/checkout/shipping-quote', [CheckoutController::class, 'shippingQuote'])->name('checkout.shipping-quote')->middleware('throttle:20,1');
    Route::get('/checkout/confirmation/{order}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

// Newsletter subscription
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

// Pages
Route::get('/contact', [\App\Http\Controllers\ContactController::class, 'show'])->name('pages.contact');
Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'store'])->name('pages.contact.store')->middleware('throttle:5,1');
Route::get('/privacy-policy', [PagesController::class, 'privacyPolicy'])->name('pages.privacy-policy');
Route::get('/shipping-policy', [PagesController::class, 'shippingPolicy'])->name('pages.shipping-policy');
Route::get('/refund-policy', [PagesController::class, 'refundPolicy'])->name('pages.refund-policy');
Route::get('/returns-exchanges', [PagesController::class, 'returnsExchanges'])->name('pages.returns-exchanges');
Route::get('/size-guide', [PagesController::class, 'sizeGuide'])->name('pages.size-guide');

// Payment
// Paystack: return address and webhook must come before /payment/{order}
Route::get('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('payment.show')->middleware('auth');
Route::post('/payment/{order}/initiate', [PaymentController::class, 'initiate'])->name('payment.initiate')->middleware('auth');
Route::get('/payment/{order}/failed', [PaymentController::class, 'failed'])->name('payment.failed')->middleware('auth');

// ==========================================
// ADMIN PANEL ROUTES
// ==========================================

Route::middleware(['auth', 'admin', 'admin.access'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Products Management
    Route::resource('products', AdminProductController::class);

    // Categories Management
    Route::resource('categories', CategoryController::class);

    // Team: super admins add admins and choose their pages (super admin only, see AdminAccess)
    Route::get('/team', [\App\Http\Controllers\Admin\TeamController::class, 'index'])->name('team.index');
    Route::get('/team/create', [\App\Http\Controllers\Admin\TeamController::class, 'create'])->name('team.create');
    Route::post('/team', [\App\Http\Controllers\Admin\TeamController::class, 'store'])->name('team.store');
    Route::get('/team/{member}/edit', [\App\Http\Controllers\Admin\TeamController::class, 'edit'])->name('team.edit');
    Route::put('/team/{member}', [\App\Http\Controllers\Admin\TeamController::class, 'update'])->name('team.update');
    Route::delete('/team/{member}', [\App\Http\Controllers\Admin\TeamController::class, 'destroy'])->name('team.destroy');
    Route::post('/team/{member}/resend', [\App\Http\Controllers\Admin\TeamController::class, 'resendInvite'])->name('team.resend');

    // My password (every admin; new admins must change their temporary password here first)
    Route::get('/password', [\App\Http\Controllers\Admin\PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [\App\Http\Controllers\Admin\PasswordController::class, 'update'])->name('password.update');

    // Contact messages and feedback
    Route::get('/messages', [\App\Http\Controllers\Admin\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{message}', [\App\Http\Controllers\Admin\MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{message}/unread', [\App\Http\Controllers\Admin\MessageController::class, 'markUnread'])->name('messages.unread');
    Route::delete('/messages/{message}', [\App\Http\Controllers\Admin\MessageController::class, 'destroy'])->name('messages.destroy');

    // Blog Management
    Route::resource('posts', AdminPostController::class)->except(['show']);

    // Orders Management
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])->name('orders.update-payment-status');
    Route::post('/orders/{order}/history/{history}/resend', [AdminOrderController::class, 'resendEmail'])->name('orders.resend-email');
    Route::post('/orders/{order}/shipping', [AdminOrderController::class, 'updateShipping'])->name('orders.shipping');

    // Inventory: stock levels, history, adjustments, returns and Excel import/export
    Route::prefix('inventory')->name('inventory.')->controller(\App\Http\Controllers\Admin\InventoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/movements', 'movements')->name('movements');
        Route::post('/adjust', 'adjust')->name('adjust');
        Route::get('/export', 'export')->name('export');
        Route::get('/import', 'importForm')->name('import');
        Route::post('/import', 'import')->name('import.store');
        Route::get('/import/template', 'template')->name('import.template');
    });
    Route::get('/inventory/returns', [\App\Http\Controllers\Admin\ReturnController::class, 'index'])->name('inventory.returns.index');
    Route::post('/inventory/returns', [\App\Http\Controllers\Admin\ReturnController::class, 'store'])->name('inventory.returns.store');

    // Shipping: DHL connection status and a test quote (credentials live in .env)
    Route::get('/shipping', [\App\Http\Controllers\Admin\ShippingController::class, 'index'])->name('shipping.index');
    Route::post('/shipping/test', [\App\Http\Controllers\Admin\ShippingController::class, 'test'])->name('shipping.test')->middleware('throttle:10,1');

    // Reviews Management
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');
    Route::post('/reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // Newsletter Management
    Route::get('/newsletters/subscribers', [AdminNewsletterController::class, 'subscribers'])->name('newsletters.subscribers');
    Route::resource('newsletters', AdminNewsletterController::class)->except(['show']);
    Route::get('/newsletters/{newsletter}/preview', [AdminNewsletterController::class, 'preview'])->name('newsletters.preview');
    Route::post('/newsletters/{newsletter}/send', [AdminNewsletterController::class, 'send'])->name('newsletters.send');
    Route::post('/newsletters/{newsletter}/send-test', [AdminNewsletterController::class, 'sendTest'])->name('newsletters.send-test');
});

require __DIR__.'/auth.php';
