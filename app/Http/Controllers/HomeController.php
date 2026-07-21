<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\SkinType;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::pluck('value', 'key');
        $saleEndsAt = filled($settings['sale_ends_at'] ?? null)
            ? Carbon::parse($settings['sale_ends_at'])
            : null;

        /*
         * Ketika timer sale habis, harga produk dikembalikan ke harga normal.
         * Hanya produk yang memang ditandai sale yang disentuh.
         */
        if ($saleEndsAt && now()->greaterThanOrEqualTo($saleEndsAt)) {
            Product::where(function ($query) {
                $query->where('is_on_sale', true)
                    ->orWhere('discount_percentage', '>', 0);
            })->whereNotNull('original_price')->get()->each(function (Product $product) {
                $product->forceFill([
                    'price' => $product->original_price,
                    'original_price' => null,
                    'discount_percentage' => 0,
                    'is_on_sale' => false,
                ])->save();
            });

            SiteSetting::updateOrCreate(['key' => 'sale_ends_at'], ['value' => '']);
            $settings['sale_ends_at'] = '';
            $saleEndsAt = null;
        }
        $defaultSkinTypes = [
            ['code' => 'dry', 'label' => 'Dry Skin', 'hint' => 'Produk untuk kulit kering', 'tone' => 'gold', 'color' => '#D89B1D', 'description' => 'Kulit terasa kering dan membutuhkan kelembapan ekstra.', 'tags' => ['Hydration', 'Barrier Care'], 'icon' => ''],
            ['code' => 'normal', 'label' => 'Normal Skin', 'hint' => 'Produk untuk kulit normal', 'tone' => 'green', 'color' => '#4F8B3A', 'description' => 'Kulit seimbang yang membutuhkan perawatan harian.', 'tags' => ['Daily Care', 'Protection'], 'icon' => ''],
            ['code' => 'oily', 'label' => 'Oily Skin', 'hint' => 'Produk untuk kulit berminyak', 'tone' => 'olive', 'color' => '#77A45A', 'description' => 'Kulit berminyak yang membutuhkan kontrol sebum.', 'tags' => ['Oil Control', 'Lightweight'], 'icon' => ''],
        ];

        $skinTypeDefinitions = json_decode((string) ($settings['skin_type_definitions'] ?? ''), true);
        if (! is_array($skinTypeDefinitions) || empty($skinTypeDefinitions)) {
            $skinTypeDefinitions = $defaultSkinTypes;
        }

        $skinTypes = collect($skinTypeDefinitions)
            ->filter(fn ($type) => ! empty($type['code']) && ! empty($type['label']))
            ->map(function (array $type) {
                $type['tags'] = is_array($type['tags'] ?? null) ? $type['tags'] : [];
                return (object) $type;
            })
            ->values();

        $skinTypeProducts = $skinTypes->mapWithKeys(function ($type) use ($settings) {
            $code = $type->code;
            $ids = collect(json_decode((string) ($settings['skin_type_'.$code.'_product_ids'] ?? '[]'), true))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values();

            $products = $ids->isNotEmpty()
                ? Product::whereIn('id', $ids)
                    ->get()
                    ->sortBy(fn (Product $product) => $ids->search($product->id))
                    ->values()
                : collect();

            return [$code => $products];
        });

        $skinTypeProductImages = $skinTypes->mapWithKeys(function ($type) use ($settings) {
            $map = json_decode((string) ($settings['skin_type_'.$type->code.'_product_images'] ?? '{}'), true) ?: [];
            return [$type->code => $map];
        });

        /*
         * Best Seller dibuat otomatis dari jumlah produk terjual.
         * Maksimal 4 produk dengan sold_count tertinggi dan tidak bergantung
         * pada nomor ranking manual, sehingga urutannya selalu #1 sampai #4.
         */
        $bestSellers = Product::where('sold_count', '>', 0)
            ->orderByDesc('sold_count')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->orderBy('id')
            ->take(4)
            ->get()
            ->values();

        $bestSellerIds = $bestSellers->pluck('id');

        /*
         * Produk yang sudah masuk daftar Best Seller tidak ditampilkan kembali
         * pada New Arrival agar satu produk tidak muncul ganda di dua tab.
         */
        $newArrivals = Product::where('is_new_arrival', true)
            ->when($bestSellerIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $bestSellerIds))
            ->latest()
            ->take(4)
            ->get()
            ->values();

        $bestSellers->each(function (Product $product, int $index) {
            $product->best_seller_rank = $index + 1;
        });

        return view('home', [
            'slides' => HeroSlide::where('is_active', true)->orderBy('sort_order')->get(),

            'newArrivals' => $newArrivals,

            'bestSellers' => $bestSellers,

            'saleProducts' => Product::where(function ($q) {
                    $q->where('is_on_sale', true)
                      ->orWhereNotNull('original_price')
                      ->orWhere('discount_percentage', '>', 0);
                })
                ->orderByDesc('sold_count')
                ->orderByDesc('discount_percentage')
                ->take(5)
                ->get(),

            'articles' => Article::where('is_active', true)
                ->orderByDesc('read_count')
                ->latest('published_at')
                ->take(3)
                ->get(),

            'skinTypes' => $skinTypes,
            'skinTypeProducts' => $skinTypeProducts,
            'skinTypeProductImages' => $skinTypeProductImages,
            'settings' => $settings,
            'saleEndsAt' => $saleEndsAt?->toIso8601String(),
        ]);
    }
}
