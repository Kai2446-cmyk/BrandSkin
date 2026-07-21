<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPurchase;
use App\Models\ProductReview;
use App\Models\CheckoutOrderItem;
use App\Support\DatabaseColumn;

class ProductController extends Controller
{
    public function show(?string $slug = null)
    {
        $product = $slug ? Product::where('slug', $slug)->first() : Product::first();

        abort_if(!$product, 404);

        if (DatabaseColumn::has('products', 'view_count')) {
            $product->increment('view_count');
        }

        $related = Product::where('id', '!=', $product->id)
            ->where('category', $product->category)
            ->orderByDesc(DatabaseColumn::has('products', 'sold_count') ? 'sold_count' : 'id')
            ->take(4)
            ->get();

        if ($related->isEmpty()) {
            $related = Product::where('id', '!=', $product->id)
                ->latest()
                ->take(4)
                ->get();
        }

        $reviews = ProductReview::with('user')->where('product_id', $product->id)
            ->where('is_active', true)
            ->latest()
            ->get();

        $averageRating = round((float) $reviews->avg('rating'), 1);
        $reviewCount = $reviews->count();

        $authUser = session('glowskin_user');
        $hasPurchased = false;
        $userReview = null;

        if ($authUser) {
            $hasPurchased = ProductPurchase::where('user_id', $authUser['id'])
                ->where('product_id', $product->id)
                ->whereIn('status', ['paid', 'completed', 'delivered'])
                ->exists();

            if (!$hasPurchased) {
                $hasPurchased = CheckoutOrderItem::where('product_id', $product->id)
                    ->whereHas('order', function ($query) use ($authUser) {
                        $query->where('user_id', $authUser['id'])
                            ->whereIn('payment_status', ['paid', 'success', 'settlement', 'capture']);
                    })
                    ->exists();
            }

            $userReview = ProductReview::where('user_id', $authUser['id'])
                ->where('product_id', $product->id)
                ->first();
        }

        $galleryImages = $product->gallery_images;

        return view('products.show', compact(
            'product',
            'related',
            'reviews',
            'averageRating',
            'reviewCount',
            'hasPurchased',
            'userReview',
            'galleryImages'
        ));
    }
}
