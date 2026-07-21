<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\CatalogueController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SkinAnalyzerController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
Route::post('/profile/address', [ProfileController::class, 'saveAddress'])->name('profile.address.save');
Route::put('/profile/address/{address}/default', [ProfileController::class, 'defaultAddress'])->name('profile.address.default');
Route::delete('/profile/address/{address}', [ProfileController::class, 'deleteAddress'])->name('profile.address.delete');
Route::get('/profile/orders', [ProfileController::class, 'orders'])->name('profile.orders');
Route::get('/profile/vouchers', [ProfileController::class, 'vouchers'])->name('profile.vouchers');


// Skin Analyzer & Skin Diary
Route::get('/skin-analyzer', [SkinAnalyzerController::class, 'index'])->name('skin-analyzer.index');
Route::get('/skin-analyzer/start', [SkinAnalyzerController::class, 'start'])->name('skin-analyzer.start');
Route::get('/skin-analyzer/report', [SkinAnalyzerController::class, 'report'])->name('skin-analyzer.report');
Route::get('/skin-analyzer/recommendations', [SkinAnalyzerController::class, 'recommendations'])->name('skin-analyzer.recommendations');
Route::get('/profile/skin-diary', [SkinAnalyzerController::class, 'diary'])->name('profile.skin-diary');

Route::get('/product-detail/{slug?}', [ProductController::class, 'show'])->name('products.show');
Route::post('/products/{product}/reviews', [ProductReviewController::class, 'store'])->name('products.reviews.store');

Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])->name('articles.show');

Route::get('/search/live', [SearchController::class, 'live'])->name('search.live');

Route::get('/makeup', [CatalogueController::class, 'makeup'])->name('catalogue.makeup');
Route::get('/skincare', [CatalogueController::class, 'skincare'])->name('catalogue.skincare');
Route::get('/sale', [CatalogueController::class, 'sale'])->name('catalogue.sale');

Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
Route::get('/wishlist/ids', [WishlistController::class, 'ids'])->name('wishlist.ids');
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/promo', [CartController::class, 'applyPromo'])->name('cart.promo.apply');
Route::delete('/cart/promo', [CartController::class, 'removePromo'])->name('cart.promo.remove');
Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout.index');
Route::post('/checkout/confirm', [CartController::class, 'confirmCheckout'])->name('checkout.confirm');
Route::get('/checkout/review', [CartController::class, 'reviewCheckout'])->name('checkout.review');
Route::post('/checkout/payment', [CartController::class, 'startMidtransPayment'])->name('checkout.payment');
Route::post('/checkout/payment/complete', [CartController::class, 'completeMidtransPayment'])->name('checkout.payment.complete');
Route::get('/checkout/payment/success/{order}', [CartController::class, 'paymentSuccess'])->name('checkout.success');
Route::post('/checkout/address', [CartController::class, 'saveCheckoutAddress'])->name('checkout.address.save');
Route::delete('/checkout/address/{address}', [CartController::class, 'deleteCheckoutAddress'])->name('checkout.address.delete');
Route::get('/checkout/shipping/destinations', [CartController::class, 'searchShippingDestination'])->name('checkout.shipping.destinations');
Route::post('/checkout/shipping/rates', [CartController::class, 'shippingRates'])->name('checkout.shipping.rates');

Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register/send-code', [AuthController::class, 'sendRegisterCode'])->name('register.send-code');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::get('/forgot-password', [AuthController::class, 'forgotPasswordForm'])->name('password.forgot');
Route::post('/forgot-password/send-code', [AuthController::class, 'sendResetCode'])->name('password.send-code');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('admin-dashboard')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('dashboard');
    Route::get('/settings', [AdminController::class, 'settingsPage'])->name('settings.index');
    Route::post('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
    Route::put('/settings', [AdminController::class, 'updateSettings'])->name('settings.update.put');
    Route::post('/hero-slides', [AdminController::class, 'storeHeroSlide'])->name('hero-slides.store');
    Route::put('/hero-slides/{slide}', [AdminController::class, 'updateHeroSlide'])->name('hero-slides.update');
    Route::delete('/hero-slides/{slide}', [AdminController::class, 'destroyHeroSlide'])->name('hero-slides.destroy');

    Route::get('/products', [AdminController::class, 'products'])->name('products.index');
    Route::get('/products/create', [AdminController::class, 'productsCreate'])->name('products.create');
    Route::post('/products', [AdminController::class, 'productsStore'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminController::class, 'productsEdit'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'productsUpdate'])->name('products.update');

    Route::get('/articles', [AdminController::class, 'articles'])->name('articles.index');
    Route::get('/articles/create', [AdminController::class, 'articlesCreate'])->name('articles.create');
    Route::post('/articles', [AdminController::class, 'articlesStore'])->name('articles.store');
    Route::get('/articles/{article}/edit', [AdminController::class, 'articlesEdit'])->name('articles.edit');
    Route::put('/articles/{article}', [AdminController::class, 'articlesUpdate'])->name('articles.update');

    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::put('/reviews/{review}/toggle', [AdminReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    Route::get('/promos', [PromoController::class, 'index'])->name('promos.index');
    Route::post('/promos', [PromoController::class, 'store'])->name('promos.store');
    Route::put('/promos/{promo}', [PromoController::class, 'update'])->name('promos.update');
    Route::delete('/promos/{promo}', [PromoController::class, 'destroy'])->name('promos.destroy');
});


