@extends('layouts.admin')
@section('title', ($mode === 'edit' ? 'Edit Produk' : 'Tambah Produk').' — Admin GlowSkin')
@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-color-picker-swatch.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-product-gallery-4.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-product-tone-images.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-product-live-preview.css') }}?v=20260713">
@php
  $isEdit = $mode === 'edit';
  $img = function ($path) {
      $path = trim((string) $path);
      if ($path === '') return asset('assets/images/no_image.png');
      if (\Illuminate\Support\Str::startsWith($path, ['http://','https://'])) return $path;
      if (\Illuminate\Support\Str::startsWith($path, 'storage/')) return route('media.storage', ['path' => substr($path, 8)]);
      return asset(ltrim($path, '/'));
  };
  $colorsValue = is_array($product->colors ?? null) ? implode(', ', $product->colors) : ($product->colors ?? '');
  $colorImagesValue = is_array($product->color_images ?? null) ? $product->color_images : [];
  $galleryImages = is_array($product->product_images ?? null) ? $product->product_images : [];
  if (empty($galleryImages) && filled($product->image)) $galleryImages = [$product->image];

  $dbCategories = \App\Models\Product::query()
      ->whereNotNull('category')
      ->where('category', '!=', '')
      ->select('category')
      ->distinct()
      ->orderBy('category')
      ->pluck('category')
      ->toArray();

  $defaultCategories = [
      'Makeup', 'Face', 'Foundation', 'Powder', 'Cushion', 'Lip', 'Eyes',
      'Skincare', 'Serum', 'Moisturizer', 'Cleanser', 'Toner', 'Sunscreen',
  ];

  $categoryOptions = collect(array_merge($dbCategories, $defaultCategories))->filter()->unique()->values();

  $badgeOptions = [
      '' => 'Tidak ada badge',
      'NEW' => 'NEW',
      'BEST SELLER' => 'BEST SELLER',
      'SALE' => 'SALE',
      'LIMITED' => 'LIMITED',
  ];

  $presetColors = [
      '#F5D5B0', '#E8B887', '#C9936A', '#A67550', '#7A4F2E', '#5C3520',
      '#E1B07A', '#D7A06E', '#C48755', '#8A5737', '#6E3F28', '#4C2B1E',
      '#E8A58E', '#D9877C', '#C36D67', '#A95A58', '#8F4548', '#6D3238',
      '#F6B7C4', '#EF8FA9', '#E66A8C', '#C94F73', '#A63A5D', '#7D2947',
      '#F1D6B8', '#C79B7C', '#9A6B55', '#6C4A3A', '#3D302D', '#1F1F1F',
      '#FFFFFF', '#F7F3EA', '#E8DED2', '#D2C1B3', '#A98C78', '#6F5A4B',
  ];

  $selectedCategory = old('category', $product->category);
  $selectedDiscount = (int) old('discount_percentage', $product->discount_percentage ?? 0);
  $selectedColor = old('selected_color', $product->selected_color);
@endphp

<section class="admin-page-head">
  <div>
    <p>PRODUCT FORM</p>
    <h1>{{ $isEdit ? 'Edit Produk' : 'Tambah Produk' }}</h1>
    <span>Admin bisa menambahkan gambar produk sesuai kebutuhan. Kalau hanya 1 gambar, thumbnail yang muncul hanya 1.</span>
  </div>
  <a href="{{ route('admin.products.index') }}" class="admin-head-btn bg-white/10">Back</a>
</section>

@if($errors->any())
  <div class="admin-error mb-5">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<form method="POST" action="{{ $isEdit ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="admin-panel admin-form-wide">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <section class="admin-product-live-preview" data-product-live-preview>
    <div class="admin-product-live-preview__top">
      <strong>Preview Tampilan Produk</strong>
      <span>Preview berubah otomatis saat data produk diisi.</span>
    </div>
    <div class="admin-product-preview-card">
      <div class="admin-product-preview-media">
        <span class="admin-product-preview-badge" data-preview-badge></span>
        <img data-preview-image data-fallback="{{ asset('assets/images/no_image.png') }}" src="{{ $img($galleryImages[0] ?? old('image_url', $product->image)) }}" alt="Preview produk">
      </div>
      <div class="admin-product-preview-copy">
        <p class="admin-product-preview-category" data-preview-category>{{ $selectedCategory ?: 'Kategori Produk' }}</p>
        <h2 class="admin-product-preview-name" data-preview-name>{{ old('name', $product->name) ?: 'Nama Produk' }}</h2>
        <p class="admin-product-preview-subtitle" data-preview-subtitle>{{ old('subtitle', $product->subtitle) ?: 'Subtitle produk akan tampil di sini.' }}</p>
        <div class="admin-product-preview-prices">
          <strong class="admin-product-preview-price" data-preview-price>Rp {{ number_format((int) old('price', $product->price ?? 0), 0, ',', '.') }}</strong>
          <span class="admin-product-preview-original" data-preview-original>{{ old('original_price', $product->original_price) ? 'Rp '.number_format((int) old('original_price', $product->original_price), 0, ',', '.') : '' }}</span>
        </div>
        <div class="admin-product-preview-tones" data-preview-tones></div>
      </div>
    </div>
  </section>

  <div class="admin-form-preview">
    <img src="{{ $img($galleryImages[0] ?? old('image_url', $product->image)) }}" alt="Preview">
    <div>
      <strong>{{ $product->name ?: 'Preview Produk' }}</strong>
      <span>Gambar utama otomatis memakai gambar pertama dari galeri produk.</span>
    </div>
  </div>

  <div class="grid md:grid-cols-2 gap-4">
    <label class="admin-field">
      <span>Nama Produk</span>
      <input name="name" value="{{ old('name', $product->name) }}" required>
    </label>

    <label class="admin-field">
      <span>Subtitle</span>
      <input name="subtitle" value="{{ old('subtitle', $product->subtitle) }}" placeholder="Contoh: Cover melt and blur powder">
    </label>

    <label class="admin-field">
      <span>Kategori</span>
      <select name="category" required>
        <option value="">Pilih kategori produk</option>
        @foreach($categoryOptions as $category)
          <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Badge</span>
      <select name="badge">
        @foreach($badgeOptions as $value => $label)
          <option value="{{ $value }}" @selected(old('badge', $product->badge) === $value)>{{ $label }}</option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Harga</span>
      <input type="number" name="price" value="{{ old('price', $product->price) }}" required>
    </label>

    <label class="admin-field">
      <span>Harga Coret</span>
      <input type="number" name="original_price" value="{{ old('original_price', $product->original_price) }}" placeholder="Isi jika produk sale">
    </label>

    <label class="admin-field">
      <span>Stok</span>
      <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 100) }}">
    </label>

    <label class="admin-field">
      <span>Diskon %</span>
      <select name="discount_percentage">
        @for($discount = 0; $discount <= 90; $discount += 5)
          <option value="{{ $discount }}" @selected($selectedDiscount === $discount)>{{ $discount }}%</option>
        @endfor
        <option value="100" @selected($selectedDiscount === 100)>100%</option>
      </select>
    </label>

    <div class="admin-field md:col-span-2" data-admin-product-gallery data-existing-gallery='@json($galleryImages)'>
      <div class="admin-gallery-title-row">
        <span>Galeri Gambar Produk</span>
        <button type="button" class="admin-gallery-add" data-add-product-image aria-label="Tambah gambar produk">
          <b>+</b> Tambah Gambar
        </button>
      </div>

      <div class="admin-product-gallery-4" data-product-gallery-list></div>

      <p class="admin-gallery-note">
        Klik <strong>+ Tambah Gambar</strong> untuk menambah slot. Gambar pertama otomatis menjadi gambar utama, dan semua gambar dapat dipilih untuk setiap tone warna.
      </p>

      <input type="hidden" name="image_url" value="{{ $galleryImages[0] ?? $product->image }}">
    </div>

    <div class="admin-field md:col-span-2">
      <span>Pilihan Warna Produk</span>

      <input type="hidden" name="colors" data-color-values value="{{ old('colors', $colorsValue) }}">
      <input type="hidden" name="selected_color" data-selected-color value="{{ $selectedColor }}">

      <div class="admin-color-toolbar">
        <button type="button" class="admin-color-empty" data-clear-colors>
          Tanpa warna / tidak ada tone
        </button>

        <div class="admin-custom-color">
          <input type="text" data-hex-input placeholder="#F5D5B0" maxlength="7">
          <button type="button" data-add-hex>Tambah warna</button>
        </div>
      </div>

      <div class="admin-color-swatch-grid" data-swatch-grid>
        @foreach($presetColors as $color)
          <button
            type="button"
            class="admin-color-swatch"
            data-color="{{ $color }}"
            style="--swatch: {{ $color }}"
            aria-label="Pilih warna {{ $color }}">
          </button>
        @endforeach
      </div>

      <div class="admin-selected-colors" data-selected-colors>
        <p class="admin-color-help">Belum ada warna dipilih. Produk akan tampil tanpa pilihan tone warna.</p>
      </div>
    </div>

    <div class="admin-field md:col-span-2" data-admin-tone-images data-existing-tone-images='@json($colorImagesValue)'>
      <span>Gambar Berdasarkan Tone Warna</span>
      <p class="admin-tone-note">
        Isi gambar khusus untuk tiap tone. Kalau user klik tone warna di detail produk, gambar utama otomatis berubah ke gambar tone tersebut.
      </p>
      <div class="admin-tone-image-list" data-tone-image-list>
        <div class="admin-tone-empty">Pilih warna produk dulu untuk menambahkan gambar per tone.</div>
      </div>
    </div>

    <label class="admin-field md:col-span-2">
      <span>Deskripsi</span>
      <textarea name="description" rows="5">{{ old('description', $product->description) }}</textarea>
    </label>
  </div>

  <div class="admin-checkbox-row">
    <label class="admin-check">
      <input type="checkbox" name="is_new_arrival" value="1" @checked(old('is_new_arrival', $product->is_new_arrival))>
      New Arrival
    </label>

    <label class="admin-check">
      <input type="checkbox" name="is_best_seller" value="1" @checked(old('is_best_seller', $product->is_best_seller))>
      Best Seller
    </label>

    <label class="admin-check">
      <input type="checkbox" name="is_on_sale" value="1" @checked(old('is_on_sale', $product->is_on_sale))>
      Sale
    </label>
  </div>

  <div class="admin-form-actions">
    <button class="green-gradient-btn admin-head-btn" type="submit">{{ $isEdit ? 'Save Produk' : 'Tambah Produk' }}</button>
    <a href="{{ route('admin.products.index') }}" class="admin-head-btn bg-white/10">Cancel</a>
  </div>
</form>

<script src="{{ asset('js/admin-product-gallery-dynamic.js') }}"></script>
<script src="{{ asset('js/admin-color-picker-swatch.js') }}"></script>
<script src="{{ asset('js/admin-product-tone-images.js') }}"></script>
<script src="{{ asset('js/admin-product-live-preview.js') }}?v=20260713"></script>
@endsection
