<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function live(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 1) {
            return response()->json(['products' => []]);
        }

        /*
         * Rating dibaca langsung dari review aktif agar hasil pencarian selalu
         * mengikuti rating user terbaru, tanpa bergantung pada cache rating_avg.
         */
        $ratingStats = ProductReview::query()
            ->where('is_active', true)
            ->selectRaw('product_id, AVG(rating) as live_rating, COUNT(*) as live_rating_count')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $products = Product::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('subtitle', 'like', "%{$q}%")
                    ->orWhere('category', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            })
            ->get();

        // Total penjualan per kategori untuk memprioritaskan kategori terlaris.
        $categorySales = Product::query()
            ->selectRaw("LOWER(COALESCE(category, '')) as category_key, SUM(COALESCE(sold_count, 0)) as total_sold")
            ->groupBy('category_key')
            ->pluck('total_sold', 'category_key');

        $needle = mb_strtolower($q);

        $products = $products
            ->map(function ($product) use ($ratingStats, $categorySales, $needle) {
                $stats = $ratingStats->get($product->id);
                $liveRating = $stats ? round((float) $stats->live_rating, 1) : (float) ($product->rating_avg ?? 0);
                $ratingCount = $stats ? (int) $stats->live_rating_count : (int) ($product->rating_count ?? 0);
                $name = mb_strtolower((string) $product->name);
                $categoryKey = mb_strtolower((string) ($product->category ?? ''));

                $product->search_relevance = $name === $needle ? 3 : (Str::startsWith($name, $needle) ? 2 : 1);
                $product->live_rating = $liveRating;
                $product->live_rating_count = $ratingCount;
                $product->category_sold = (int) ($categorySales[$categoryKey] ?? 0);

                return $product;
            })
            ->sort(function ($a, $b) {
                return [$b->search_relevance, $b->category_sold, (int) ($b->sold_count ?? 0), $b->live_rating, $b->live_rating_count, $b->id]
                    <=> [$a->search_relevance, $a->category_sold, (int) ($a->sold_count ?? 0), $a->live_rating, $a->live_rating_count, $a->id];
            })
            ->take(8)
            ->values()
            ->map(function ($product) {
                $ratingText = $product->live_rating_count > 0
                    ? number_format($product->live_rating, 1) . ' ★ (' . $product->live_rating_count . ')'
                    : 'Belum ada rating';

                return [
                    'type' => 'Produk',
                    'title' => $product->name,
                    'category' => $product->category ?: 'Product',
                    'price' => 'Rp' . number_format((int) $product->price, 0, ',', '.'),
                    'rating' => $ratingText,
                    'sold' => (int) ($product->sold_count ?? 0),
                    'image' => $product->image,
                    'url' => route('products.show', $product->slug),
                ];
            });

        return response()->json(['products' => $products]);
    }
}
