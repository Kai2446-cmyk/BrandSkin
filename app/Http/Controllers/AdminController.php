<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    private function ensureAdmin()
    {
        if ((session('glowskin_user.role') ?? null) !== 'admin') {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login sebagai admin terlebih dahulu.']);
        }

        return null;
    }

    private function settings()
    {
        return SiteSetting::pluck('value', 'key');
    }

    public function index()
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $products = Product::latest()->get();
        $articles = Article::latest()->get();
        $slides = HeroSlide::orderBy('sort_order')->get();

        return view('admin.dashboard', [
            'products' => $products,
            'articles' => $articles,
            'slides' => $slides,
            'settings' => $this->settings(),
            'dashboardReport' => $this->dashboardReportData($products, $articles, $slides),
        ]);
    }

    private function dashboardReportData($products, $articles, $slides): array
    {
        $hasPurchases = Schema::hasTable('product_purchases');
        $hasUsers = Schema::hasTable('users');
        $hasReviews = Schema::hasTable('product_reviews');
        $hasWishlists = Schema::hasTable('wishlists');
        $hasCart = Schema::hasTable('cart_items');
        $hasPromos = Schema::hasTable('promo_codes');

        $purchaseRows = collect();

        if ($hasPurchases) {
            $purchaseRows = DB::table('product_purchases')
                ->leftJoin('products', 'product_purchases.product_id', '=', 'products.id')
                ->select(
                    'product_purchases.id',
                    'product_purchases.quantity',
                    'product_purchases.status',
                    'product_purchases.purchased_at',
                    'product_purchases.created_at',
                    'products.name as product_name',
                    'products.category',
                    'products.price'
                )
                ->latest('product_purchases.id')
                ->get();
        }

        $paidStatuses = ['paid', 'success', 'settlement', 'completed', 'done'];
        $failedStatuses = ['failed', 'cancelled', 'canceled', 'expired', 'expire'];

        $paidRows = $purchaseRows->filter(function ($row) use ($paidStatuses) {
            return in_array(strtolower((string) $row->status), $paidStatuses, true);
        });

        $pendingRows = $purchaseRows->filter(function ($row) use ($paidStatuses, $failedStatuses) {
            $status = strtolower((string) $row->status);
            return ! in_array($status, $paidStatuses, true) && ! in_array($status, $failedStatuses, true);
        });

        $revenue = (int) $paidRows->sum(function ($row) {
            return ((int) ($row->price ?? 0)) * max(1, (int) ($row->quantity ?? 1));
        });

        /*
         * FIX: Grafik pemasaran harus memakai data payment real saja.
         * Kalau payment belum ada / belum paid, jangan pakai estimasi sold_count produk.
         * Nilai grafik dibuat 0 dulu sampai nanti modul payment mengisi transaksi asli.
         */
        $chart = collect(range(5, 0))->map(function ($back) use ($paidRows) {
            $date = now()->subMonths($back);
            $key = $date->format('Y-m');
            $monthRows = $paidRows->filter(function ($row) use ($key) {
                $dateValue = $row->purchased_at ?: $row->created_at;
                return $dateValue && substr((string) $dateValue, 0, 7) === $key;
            });

            return [
                'label' => $date->format('M'),
                'value' => (int) $monthRows->sum(fn ($row) => ((int) ($row->price ?? 0)) * max(1, (int) ($row->quantity ?? 1))),
                'orders' => $monthRows->count(),
            ];
        })->values();

        $maxChartValue = max(1, (int) $chart->max('value'));

        $statusList = $purchaseRows
            ->groupBy(fn ($row) => strtolower((string) ($row->status ?: 'unknown')))
            ->map(fn ($rows, $status) => ['label' => strtoupper($status), 'total' => $rows->count()])
            ->values();

        $topProducts = $products
            ->sortByDesc(fn ($product) => (int) ($product->sold_count ?? 0))
            ->take(5)
            ->values();

        $categoryPerformance = $paidRows
            ->groupBy(fn ($row) => $row->category ?: 'Uncategorized')
            ->map(function ($items, $category) {
                return [
                    'label' => $category,
                    'sold' => (int) $items->sum(fn ($item) => max(1, (int) ($item->quantity ?? 1))),
                    'revenue' => (int) $items->sum(fn ($item) => ((int) ($item->price ?? 0)) * max(1, (int) ($item->quantity ?? 1))),
                ];
            })
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        return [
            'total_revenue' => $revenue,
            'total_orders' => $purchaseRows->count(),
            'paid_orders' => $paidRows->count(),
            'pending_orders' => $pendingRows->count(),
            'chart' => $chart,
            'max_chart_value' => $maxChartValue,
            'status_list' => $statusList,
            'top_products' => $topProducts,
            'category_performance' => $categoryPerformance,
            'users_count' => $hasUsers ? DB::table('users')->count() : 0,
            'reviews_count' => $hasReviews ? DB::table('product_reviews')->count() : 0,
            'wishlist_count' => $hasWishlists ? DB::table('wishlists')->count() : 0,
            'cart_items_count' => $hasCart ? DB::table('cart_items')->sum('quantity') : 0,
            'active_promos_count' => $hasPromos ? DB::table('promo_codes')->where('is_active', 1)->count() : 0,
            'active_slides_count' => $slides->where('is_active', true)->count(),
        ];
    }

    public function settingsPage()
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $settings = $this->settings();
        $defaultSkinTypes = [
            ['code' => 'dry', 'label' => 'Dry Skin', 'hint' => 'Produk untuk kulit kering', 'tone' => 'gold', 'color' => '#D89B1D', 'description' => 'Kulit terasa kering dan membutuhkan kelembapan ekstra.', 'tags' => ['Hydration', 'Barrier Care'], 'icon' => ''],
            ['code' => 'normal', 'label' => 'Normal Skin', 'hint' => 'Produk untuk kulit normal', 'tone' => 'green', 'color' => '#4F8B3A', 'description' => 'Kulit seimbang yang membutuhkan perawatan harian.', 'tags' => ['Daily Care', 'Protection'], 'icon' => ''],
            ['code' => 'oily', 'label' => 'Oily Skin', 'hint' => 'Produk untuk kulit berminyak', 'tone' => 'olive', 'color' => '#77A45A', 'description' => 'Kulit berminyak yang membutuhkan kontrol sebum.', 'tags' => ['Oil Control', 'Lightweight'], 'icon' => ''],
        ];

        $skinTypeDefinitions = json_decode((string) ($settings['skin_type_definitions'] ?? ''), true);
        if (! is_array($skinTypeDefinitions) || empty($skinTypeDefinitions)) {
            $skinTypeDefinitions = $defaultSkinTypes;
        }

        return view('admin.settings.index', [
            'slides' => HeroSlide::orderBy('sort_order')->get(),
            'settings' => $settings,
            'products' => Product::orderBy('name')->get(),
            'skinTypeDefinitions' => $skinTypeDefinitions,
        ]);
    }

    public function updateSettings(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        // Form timer sale memakai route POST yang sama dengan Website Settings.
        // Dipisahkan lewat setting_section agar field website lain tidak ikut tertimpa.
        if ($request->input('setting_section') === 'sale_timer') {
            $data = $request->validate([
                'sale_ends_at' => ['nullable', 'date'],
            ]);

            SiteSetting::updateOrCreate(
                ['key' => 'sale_ends_at'],
                ['value' => $data['sale_ends_at'] ?? '']
            );

            return back()->with('success', 'Timer sale berhasil disimpan.');
        }

        // FIX: Simpan pengaturan Shop by Skin Type yang bisa ditambah dinamis dari admin.
        // Tipe kulit baru dibuat dari input form, lalu disimpan ke site_settings tanpa menambah tabel database.
        if ($request->input('setting_section') === 'skin_type_products') {
            $skinTypesInput = collect($request->input('skin_types', []));
            $currentSettings = $this->settings();
            $usedCodes = [];
            $definitions = [];

            $skinTypesInput->each(function ($type, $index) use ($request, $currentSettings, &$usedCodes, &$definitions) {
                $label = trim((string) ($type['label'] ?? ''));

                if ($label === '') {
                    return;
                }

                $rawCode = trim((string) ($type['code'] ?? ''));
                $code = $rawCode !== '' ? Str::slug($rawCode, '_') : Str::slug($label, '_');
                $code = $code !== '' ? $code : 'skin_type_'.($index + 1);

                $baseCode = $code;
                $counter = 2;
                while (in_array($code, $usedCodes, true)) {
                    $code = $baseCode.'_'.$counter;
                    $counter++;
                }
                $usedCodes[] = $code;

                $productIds = collect($type['product_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                $tags = collect(explode(',', (string) ($type['tags'] ?? '')))
                    ->map(fn ($tag) => trim($tag))
                    ->filter()
                    ->values()
                    ->toArray();

                $iconPath = trim((string) ($type['existing_icon'] ?? ''));
                if ($request->hasFile('skin_types.'.$index.'.icon')) {
                    $iconFile = $request->file('skin_types.'.$index.'.icon');
                    if ($iconFile && $iconFile->isValid()) {
                        $storedIcon = $iconFile->store('skin-types/icons', 'public');
                        $iconPath = 'storage/'.$storedIcon;
                    }
                }

                $definitions[] = [
                    'code' => $code,
                    'label' => $label,
                    'hint' => trim((string) ($type['hint'] ?? '')),
                    'tone' => trim((string) ($type['tone'] ?? 'green')),
                    'color' => trim((string) ($type['color'] ?? '#4F8B3A')),
                    'description' => trim((string) ($type['description'] ?? '')),
                    'tags' => $tags,
                    'icon' => $iconPath,
                ];

                SiteSetting::updateOrCreate(
                    ['key' => 'skin_type_'.$code.'_product_ids'],
                    ['value' => json_encode($productIds->all())]
                );

                $imageMap = json_decode((string) ($currentSettings['skin_type_'.$code.'_product_images'] ?? '{}'), true);
                $imageMap = is_array($imageMap) ? $imageMap : [];

                $uploadedImages = $request->file('skin_types.'.$index.'.images', []);
                if (! is_array($uploadedImages)) {
                    $uploadedImages = [$uploadedImages];
                }

                foreach ($uploadedImages as $imageIndex => $image) {
                    if (! $image || ! method_exists($image, 'isValid') || ! $image->isValid()) {
                        continue;
                    }

                    $productId = $productIds->get($imageIndex);
                    if (! $productId) {
                        continue;
                    }

                    $path = $image->store('skin-types', 'public');
                    $imageMap[(string) $productId] = 'storage/'.$path;
                }

                $allowedProductKeys = $productIds->map(fn ($id) => (string) $id)->flip()->all();
                $imageMap = array_intersect_key($imageMap, $allowedProductKeys);

                SiteSetting::updateOrCreate(
                    ['key' => 'skin_type_'.$code.'_product_images'],
                    ['value' => json_encode($imageMap)]
                );
            });

            SiteSetting::updateOrCreate(
                ['key' => 'skin_type_definitions'],
                ['value' => json_encode($definitions)]
            );

            $activeCodes = collect($definitions)->pluck('code');
            SiteSetting::where(function ($query) {
                $query->where('key', 'like', 'skin_type_%_product_ids')
                    ->orWhere('key', 'like', 'skin_type_%_product_images');
            })->get()->each(function (SiteSetting $setting) use ($activeCodes) {
                $key = (string) $setting->key;
                $code = Str::between($key, 'skin_type_', str_ends_with($key, '_product_ids') ? '_product_ids' : '_product_images');

                if (! $activeCodes->contains($code)) {
                    $setting->delete();
                }
            });

            return back()->with('success', 'Pengaturan Shop by Skin Type berhasil disimpan.');
        }

        $data = $request->validate([
            'web_name' => ['nullable', 'string', 'max:100'],
            'brand_tagline' => ['nullable', 'string', 'max:150'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'tiktok' => ['nullable', 'string', 'max:255'],
            'twitter' => ['nullable', 'string', 'max:255'],
            'youtube' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:4096'],
            'logo_url' => ['nullable', 'string', 'max:500'],
        ]);

        foreach (['web_name','brand_tagline','instagram','facebook','tiktok','twitter','youtube'] as $key) {
            SiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => trim((string) ($data[$key] ?? ''))]
            );
        }

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $uploadDirectory = public_path('uploads/site');

            if (! is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0755, true);
            }

            $extension = strtolower($logoFile->getClientOriginalExtension() ?: $logoFile->extension() ?: 'png');
            $filename = 'logo-'.now()->format('YmdHis').'-'.bin2hex(random_bytes(4)).'.'.$extension;
            $logoFile->move($uploadDirectory, $filename);

            SiteSetting::updateOrCreate(
                ['key' => 'logo'],
                ['value' => 'uploads/site/'.$filename]
            );
        } elseif ($request->filled('logo_url')) {
            SiteSetting::updateOrCreate(['key' => 'logo'], ['value' => trim((string) $data['logo_url'])]);
        }

        return back()->with('success', 'Website settings berhasil diperbarui.');
    }

    public function storeHeroSlide(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:150'],
            'image' => ['nullable', 'image', 'max:8192'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('hero-slides', 'public');
            $data['image'] = 'storage/'.$path;
        } elseif ($request->filled('image_url')) {
            $data['image'] = trim((string) $request->image_url);
        } else {
            return back()->withErrors(['image' => 'Upload gambar atau isi URL gambar hero terlebih dahulu.'])->withInput();
        }

        $data['label'] = filled($data['label'] ?? null) ? $data['label'] : 'New Collection';
        $data['subtitle'] = $data['subtitle'] ?? '';
        $data['alt'] = $data['title'];
        $data['sort_order'] = $data['sort_order'] ?? ((int) HeroSlide::max('sort_order') + 1);
        $data['is_active'] = $request->boolean('is_active', true);

        unset($data['image_url']);

        HeroSlide::create($data);

        return back()->with('success', 'Hero slide baru berhasil ditambahkan.');
    }

    public function updateHeroSlide(Request $request, HeroSlide $slide)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:150'],
            'image' => ['nullable', 'image', 'max:8192'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('hero-slides', 'public');
            $data['image'] = 'storage/'.$path;
        } elseif ($request->filled('image_url')) {
            $data['image'] = trim((string) $request->image_url);
        } else {
            unset($data['image']);
        }

        $data['label'] = filled($data['label'] ?? null) ? $data['label'] : $slide->label;
        $data['subtitle'] = $data['subtitle'] ?? '';
        $data['alt'] = $data['title'];
        $data['sort_order'] = $data['sort_order'] ?? $slide->sort_order;
        $data['is_active'] = $request->boolean('is_active');

        unset($data['image_url']);

        $slide->update($data);

        return back()->with('success', 'Hero slide berhasil diperbarui.');
    }

    public function destroyHeroSlide(HeroSlide $slide)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $slide->delete();

        return back()->with('success', 'Hero slide berhasil dihapus dari landing page.');
    }

    public function products(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        /*
         * FIX:
         * View admin.products.index memakai variable $keyword.
         * Sebelumnya controller tidak mengirim $keyword, jadi muncul:
         * Undefined variable $keyword
         */
        $keyword = trim((string) $request->query('q', ''));

        $query = Product::query();

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%'.$keyword.'%')
                  ->orWhere('category', 'like', '%'.$keyword.'%')
                  ->orWhere('subtitle', 'like', '%'.$keyword.'%');
            });
        }

        return view('admin.products.index', [
            'products' => $query->latest()->get(),
            'settings' => $this->settings(),
            'keyword' => $keyword,
        ]);
    }

    public function productsCreate()
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        return view('admin.products.form', [
            'mode' => 'create',
            'product' => new Product([
                'stock' => 100,
                'discount_percentage' => 0,
                'colors' => [],
                'product_images' => [],
            ]),
            'settings' => $this->settings(),
        ]);
    }

    public function productsEdit(Product $product)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        return view('admin.products.form', [
            'mode' => 'edit',
            'product' => $product,
            'settings' => $this->settings(),
        ]);
    }

    public function productsStore(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $this->validateProduct($request);
        $data = $this->prepareProductPayload($request, $data);

        Product::create($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function productsUpdate(Request $request, Product $product)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $this->validateProduct($request);
        $data = $this->prepareProductPayload($request, $data, $product);

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:100'],
            'badge' => ['nullable', 'string', 'max:50'],
            'price' => ['required', 'integer', 'min:0'],
            'original_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'discount_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'selected_color' => ['nullable', 'string', 'max:20'],
            'colors' => ['nullable', 'string', 'max:1000'],
            'color_image_colors' => ['nullable', 'array', 'max:20'],
            'color_image_colors.*' => ['nullable', 'string', 'max:20'],
            'color_image_uploads' => ['nullable', 'array', 'max:20'],
            'color_image_uploads.*' => ['nullable', 'image', 'max:8192'],
            'color_image_urls' => ['nullable', 'array', 'max:20'],
            'color_image_urls.*' => ['nullable', 'string', 'max:1000'],
            'color_image_gallery_indexes' => ['nullable', 'array', 'max:20'],
            'color_image_gallery_indexes.*' => ['nullable', 'integer', 'min:0', 'max:99'],
            'existing_color_images' => ['nullable', 'array', 'max:20'],
            'existing_color_images.*' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'max:8192'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'product_images' => ['nullable', 'array', 'max:20'],
            'product_images.*' => ['nullable', 'image', 'max:8192'],
            'product_image_urls' => ['nullable', 'array', 'max:20'],
            'product_image_urls.*' => ['nullable', 'string', 'max:1000'],
            'existing_product_images' => ['nullable', 'array', 'max:20'],
            'existing_product_images.*' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'is_new_arrival' => ['nullable'],
            'is_best_seller' => ['nullable'],
            'is_on_sale' => ['nullable'],
        ]);
    }

    private function prepareProductPayload(Request $request, array $data, ?Product $product = null): array
    {
        $data['slug'] = Str::slug($data['name']);
        $data['alt'] = $data['name'];
        $data['stock'] = $data['stock'] ?? 0;
        $data['discount_percentage'] = $data['discount_percentage'] ?? 0;
        $data['is_new_arrival'] = $request->boolean('is_new_arrival');
        $data['is_best_seller'] = $request->boolean('is_best_seller');
        $data['is_on_sale'] = $request->boolean('is_on_sale') || (($data['discount_percentage'] ?? 0) > 0) || filled($data['original_price'] ?? null);

        $data['colors'] = collect(explode(',', $request->input('colors', '')))
            ->map(fn ($item) => strtoupper(trim($item)))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $colorImageColors = $request->input('color_image_colors', []) ?? [];
        $existingColorImages = $request->input('existing_color_images', []) ?? [];
        $colorImageUrls = $request->input('color_image_urls', []) ?? [];
        $colorImageGalleryIndexes = $request->input('color_image_gallery_indexes', []) ?? [];

        $gallery = [];
        $existingGallery = $request->input('existing_product_images', []) ?? [];
        $galleryUrls = $request->input('product_image_urls', []) ?? [];
        $galleryFiles = $request->file('product_images', []) ?? [];
        $galleryIndexes = array_unique(array_merge(
            array_keys($existingGallery),
            array_keys($galleryUrls),
            array_keys($galleryFiles)
        ));
        sort($galleryIndexes, SORT_NUMERIC);

        foreach ($galleryIndexes as $i) {
            $image = trim((string) ($existingGallery[$i] ?? ''));

            if ($request->hasFile("product_images.$i")) {
                $image = $this->storeProductPublicImage($request->file("product_images.$i"), 'gallery');
            }

            if (filled($galleryUrls[$i] ?? null)) {
                $image = trim((string) $galleryUrls[$i]);
            }

            if (filled($image)) {
                $gallery[] = $image;
            }
        }

        $gallery = collect($gallery)
            ->filter(fn ($image) => filled($image))
            ->values()
            ->take(20)
            ->toArray();

        if ($request->hasFile('image')) {
            $data['image'] = $this->storeProductPublicImage($request->file('image'), 'gallery');
        } elseif (!empty($gallery)) {
            $data['image'] = $gallery[0];
        } elseif ($request->filled('image_url')) {
            $data['image'] = $request->image_url;
        } elseif ($product) {
            $data['image'] = $product->image;
        }

        if (empty($gallery) && filled($data['image'] ?? null)) {
            $gallery = [$data['image']];
        }

        $data['product_images'] = $gallery;

        $colorImages = [];
        foreach ($colorImageColors as $index => $toneColor) {
            $toneColor = strtoupper(trim((string) $toneColor));
            if (! in_array($toneColor, $data['colors'], true)) {
                continue;
            }

            $toneImage = trim((string) ($existingColorImages[$index] ?? ''));
            $galleryIndex = $colorImageGalleryIndexes[$index] ?? null;

            if ($galleryIndex !== null && $galleryIndex !== '' && isset($gallery[(int) $galleryIndex])) {
                $toneImage = $gallery[(int) $galleryIndex];
            }

            if ($request->hasFile("color_image_uploads.$index")) {
                $toneImage = $this->storeProductPublicImage($request->file("color_image_uploads.$index"), 'tones');
            }

            if (filled($colorImageUrls[$index] ?? null)) {
                $toneImage = trim((string) $colorImageUrls[$index]);
            }

            if (filled($toneImage)) {
                $colorImages[$toneColor] = $toneImage;
            }
        }
        $data['color_images'] = $colorImages;

        unset(
            $data['image_url'],
            $data['product_image_urls'],
            $data['existing_product_images'],
            $data['color_image_colors'],
            $data['color_image_uploads'],
            $data['color_image_urls'],
            $data['color_image_gallery_indexes'],
            $data['existing_color_images']
        );

        return $data;
    }


    /**
     * Simpan gambar produk langsung ke public/uploads agar tetap tampil tanpa
     * bergantung pada symbolic link public/storage di hosting atau Windows.
     */
    private function storeProductPublicImage($file, string $folder = 'gallery'): string
    {
        $folder = trim($folder, '/');
        $targetDirectory = public_path('uploads/products/'.$folder);

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $safeExtension = preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'jpg';
        $filename = now()->format('YmdHis').'-'.bin2hex(random_bytes(6)).'.'.$safeExtension;
        $file->move($targetDirectory, $filename);

        return 'uploads/products/'.$folder.'/'.$filename;
    }

    public function articles()
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        return view('admin.articles.index', [
            'articles' => Article::latest()->get(),
            'settings' => $this->settings(),
        ]);
    }

    public function articlesCreate()
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        return view('admin.articles.form', [
            'mode' => 'create',
            'article' => new Article(['is_active' => true]),
            'availableArticles' => Article::query()
                ->where('is_active', true)
                ->latest('published_at')
                ->latest('id')
                ->get(['id', 'title', 'slug', 'category', 'image']),
            'settings' => $this->settings(),
        ]);
    }

    public function articlesEdit(Article $article)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        return view('admin.articles.form', [
            'mode' => 'edit',
            'article' => $article,
            'availableArticles' => Article::query()
                ->where('is_active', true)
                ->whereKeyNot($article->getKey())
                ->latest('published_at')
                ->latest('id')
                ->get(['id', 'title', 'slug', 'category', 'image']),
            'settings' => $this->settings(),
        ]);
    }

    public function articlesStore(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $this->validateArticle($request);
        $data = $this->prepareArticlePayload($request, $data);

        Article::create($data);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil ditambahkan.');
    }

    public function articlesUpdate(Request $request, Article $article)
    {
        if ($redirect = $this->ensureAdmin()) return $redirect;

        $data = $this->validateArticle($request);
        $data = $this->prepareArticlePayload($request, $data, $article);

        $article->update($data);

        return redirect()->route('admin.articles.index')->with('success', 'Artikel berhasil diperbarui.');
    }

    private function validateArticle(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:220'],
            'category' => ['nullable', 'string', 'max:100'],
            'tag' => ['nullable', 'string', 'max:100'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:8192'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable'],
        ]);
    }

    private function prepareArticlePayload(Request $request, array $data, ?Article $article = null): array
    {
        $data['slug'] = Str::slug($data['title']);
        $data['is_active'] = $request->boolean('is_active');
        $data['published_at'] = now();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
            $data['image'] = 'storage/'.$path;
        } elseif ($request->filled('image_url')) {
            $data['image'] = $request->image_url;
        } elseif ($article) {
            $data['image'] = $article->image;
        }

        unset($data['image_url']);

        return $data;
    }
}
