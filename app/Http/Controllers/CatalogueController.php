<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class CatalogueController extends Controller
{
    public function makeup(Request $request)
    {
        return $this->catalogue($request, 'makeup');
    }

    public function skincare(Request $request)
    {
        return $this->catalogue($request, 'skincare');
    }

    public function sale(Request $request)
    {
        return $this->catalogue($request, 'sale');
    }

    private function catalogue(Request $request, string $type)
    {
        $query = Product::query();
        $this->applyPageTypeFilter($query, $type);

        if ($request->filled('category') && $request->category !== 'all') {
            $this->applyCategoryFilter($query, $request->category);
        }

        if ($request->filled('price') && $request->price !== 'all') {
            match ($request->price) {
                'under150' => $query->where('price', '<', 150000),
                '150to200' => $query->whereBetween('price', [150000, 200000]),
                'above200' => $query->where('price', '>', 200000),
                default => null,
            };
        }

        match ($request->get('sort', 'default')) {
            'price_low' => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'new' => $query->orderByDesc('is_new_arrival')->orderByDesc('sold_count')->latest(),
            'best' => $query->orderByDesc('sold_count')->orderByDesc('is_best_seller')->orderByDesc('rating_avg'),
            default => $query->orderByDesc('sold_count')->orderByDesc('is_best_seller')->orderByDesc('rating_avg')->latest(),
        };

        $products = $query->get();
        $categories = $this->getPageCategories($type);

        /*
         * Rank otomatis:
         * - Best Seller #1 s/d #10 berdasarkan sold_count terbanyak.
         * - Rating Terbaik #1 s/d #10 berdasarkan rating_avg tertinggi.
         */
        $topSellerIds = Product::query()
            ->orderByDesc('sold_count')
            ->orderByDesc('rating_avg')
            ->take(10)
            ->pluck('id')
            ->values()
            ->all();

        $topRatingIds = Product::query()
            ->where('rating_avg', '>', 0)
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->take(10)
            ->pluck('id')
            ->values()
            ->all();

        $heroImage = match ($type) {
            'skincare' => 'https://images.unsplash.com/photo-1571781926291-c477ebfd024b?auto=format&fit=crop&w=1800&q=85',
            'sale' => 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1800&q=85',
            default => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=1800&q=85',
        };

        return view('catalogue', [
            'settings' => SiteSetting::pluck('value', 'key'),
            'products' => $products,
            'categories' => $categories,
            'type' => $type,
            'title' => $type === 'sale' ? 'Sale' : ucfirst($type),
            'heroImage' => $heroImage,
            'keywordSort' => $request->get('sort', 'default'),
            'keywordCategory' => $request->get('category', 'all'),
            'keywordPrice' => $request->get('price', 'all'),
            'topSellerIds' => $topSellerIds,
            'topRatingIds' => $topRatingIds,
        ]);
    }

    private function applyPageTypeFilter($query, string $type): void
    {
        if ($type === 'skincare') {
            $query->where(function ($q) {
                $q->where('category', 'like', '%skincare%')
                  ->orWhere('category', 'like', '%skin%')
                  ->orWhere('name', 'like', '%serum%')
                  ->orWhere('name', 'like', '%moisturizer%')
                  ->orWhere('name', 'like', '%cream%')
                  ->orWhere('name', 'like', '%toner%')
                  ->orWhere('name', 'like', '%cleanser%')
                  ->orWhere('name', 'like', '%sunscreen%')
                  ->orWhere('name', 'like', '%spf%');
            });
            return;
        }

        if ($type === 'sale') {
            $query->where(function ($q) {
                $q->where('is_on_sale', true)
                  ->orWhereNotNull('original_price')
                  ->orWhere('discount_percentage', '>', 0);
            });
            return;
        }

        $query->where(function ($q) {
            $q->where('category', 'like', '%makeup%')
              ->orWhere('category', 'like', '%face%')
              ->orWhere('category', 'like', '%foundation%')
              ->orWhere('category', 'like', '%lip%')
              ->orWhere('category', 'like', '%powder%')
              ->orWhere('category', 'like', '%cushion%')
              ->orWhere('category', 'like', '%concealer%')
              ->orWhere('category', 'like', '%eyes%')
              ->orWhere('category', 'like', '%eye%')
              ->orWhere('category', 'like', '%brow%')
              ->orWhere('category', 'like', '%blush%')
              ->orWhere('name', 'like', '%foundation%')
              ->orWhere('name', 'like', '%powder%')
              ->orWhere('name', 'like', '%cushion%')
              ->orWhere('name', 'like', '%lip%')
              ->orWhere('name', 'like', '%blush%')
              ->orWhere('name', 'like', '%palette%');
        })->where(function ($q) {
            $q->whereNull('category')
              ->orWhere('category', 'not like', '%skincare%')
              ->where('category', 'not like', '%skin%');
        });
    }

    private function applyCategoryFilter($query, string $category): void
    {
        $map = [
            'Face' => ['face', 'foundation', 'powder', 'cushion', 'concealer', 'tinted', 'cover'],
            'Foundation' => ['foundation', 'cover', 'liquid', 'matte'],
            'Powder' => ['powder', 'translucent', 'cake'],
            'Cushion' => ['cushion'],
            'Lip' => ['lip', 'cream', 'matte'],
            'Eyes' => ['eye', 'eyes', 'brow', 'mascara', 'liner', 'palette'],
            'Serum' => ['serum'],
            'Moisturizer' => ['moisturizer', 'cream'],
            'Cleanser' => ['cleanser', 'wash'],
            'Toner' => ['toner'],
            'Sunscreen' => ['sunscreen', 'spf'],
            'Skincare' => ['skincare', 'skin', 'serum', 'moisturizer', 'cream', 'toner', 'cleanser', 'sunscreen'],
        ];

        if (isset($map[$category])) {
            $words = $map[$category];

            $query->where(function ($q) use ($words) {
                foreach ($words as $word) {
                    $q->orWhere('category', 'like', "%{$word}%")
                      ->orWhere('name', 'like', "%{$word}%")
                      ->orWhere('subtitle', 'like', "%{$word}%");
                }
            });

            return;
        }

        $query->where('category', $category);
    }

    private function getPageCategories(string $type)
    {
        if ($type === 'skincare') {
            return collect(['Skincare', 'Serum', 'Moisturizer', 'Cleanser', 'Toner', 'Sunscreen']);
        }

        if ($type === 'sale') {
            return collect(['Face', 'Foundation', 'Powder', 'Cushion', 'Lip', 'Skincare']);
        }

        return collect(['Face', 'Foundation', 'Powder', 'Cushion', 'Lip', 'Eyes']);
    }
}
