<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CheckoutOrderItem;
use App\Models\ProductPurchase;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    private function authUser()
    {
        return session('glowskin_user');
    }

    public function store(Request $request, Product $product)
    {
        $user = $this->authUser();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Silakan login dulu untuk memberi review.');
        }

        $hasPurchased = ProductPurchase::where('user_id', $user['id'])
            ->where('product_id', $product->id)
            ->whereIn('status', ['paid', 'completed', 'delivered'])
            ->exists();

        if (!$hasPurchased && class_exists(CheckoutOrderItem::class)) {
            $hasPurchased = CheckoutOrderItem::where('product_id', $product->id)
                ->whereHas('order', function ($query) use ($user) {
                    $query->where('user_id', $user['id'])
                        ->whereIn('payment_status', ['paid', 'success', 'settlement', 'capture']);
                })
                ->exists();
        }

        if (!$hasPurchased) {
            return back()->with('error', 'Review hanya bisa dikirim oleh user yang sudah membeli produk ini.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:5', 'max:1200'],
            'review_images' => ['nullable', 'array', 'max:4'],
            'review_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $existingReview = ProductReview::where('user_id', $user['id'])
            ->where('product_id', $product->id)
            ->first();

        $review = ProductReview::updateOrCreate(
            [
                'user_id' => $user['id'],
                'product_id' => $product->id,
            ],
            [
                'rating' => $data['rating'],
                'review' => $data['review'],
                'is_verified_purchase' => true,
                'is_active' => true,
            ]
        );

        if ($request->hasFile('review_images') && method_exists($review, 'images')) {
            $dir = public_path('uploads/review-images');
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            foreach ($review->images as $oldImage) {
                $oldPath = public_path($oldImage->image_path);
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
                $oldImage->delete();
            }

            foreach (array_slice($request->file('review_images'), 0, 4) as $file) {
                $filename = 'review_'.$review->id.'_'.uniqid().'.'.$file->getClientOriginalExtension();
                $file->move($dir, $filename);
                $review->images()->create(['image_path' => 'uploads/review-images/'.$filename]);
            }
        }

        CheckoutOrderItem::where('product_id', $product->id)
            ->whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user['id'])
                    ->whereIn('payment_status', ['paid', 'success', 'settlement', 'capture']);
            })
            ->update(['reviewed_at' => now()]);

        return back()->with('success', $existingReview ? 'Review produk berhasil diperbarui.' : 'Review produk berhasil disimpan.');
    }
}
