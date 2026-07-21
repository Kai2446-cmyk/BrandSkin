@extends('layouts.admin')
@section('title','Website Settings — GlowSkin')
@section('content')
@php
  $webName = $settings->has('web_name') ? trim((string) $settings['web_name']) : 'GlowSkin';
  $tagline = $settings->has('brand_tagline') ? trim((string) $settings['brand_tagline']) : 'Beauty Brand';
  $hasIdentityText = filled($webName) || filled($tagline);
  $logo = $settings['logo'] ?? 'assets/images/app_logo.png';
  $img = fn($path) => \Illuminate\Support\Str::startsWith($path ?? '', ['http://','https://']) ? $path : asset($path ?: 'assets/images/no_image.png');
@endphp

<section class="admin-page-head">
  <div>
    <p>WEBSITE SETTINGS</p>
    <h1>Website Settings</h1>
    <span>Kelola logo, nama website, social media, dan hero section landing page.</span>
  </div>
  <a href="{{ route('home') }}" class="green-gradient-btn admin-head-btn">View Site</a>
</section>

@if($errors->any())
  <div class="admin-error mb-5">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="admin-panel admin-settings-panel">
  @csrf
  <div class="admin-panel-head">
    <div><h2>Website Identity</h2><p>Logo, nama website, tagline, dan link social media footer/landing page.</p></div>
    <button type="submit" class="green-gradient-btn admin-save-btn">Save Settings</button>
  </div>

  <div class="admin-logo-preview {{ $hasIdentityText ? '' : 'is-logo-only' }}">
    <img src="{{ $img($logo) }}" alt="Logo Preview">
    @if($hasIdentityText)
      <div class="admin-logo-preview__text">
        @if(filled($webName))<strong>{{ $webName }}</strong>@endif
        @if(filled($tagline))<span>{{ $tagline }}</span>@endif
      </div>
    @endif
  </div>

  <div class="grid md:grid-cols-3 gap-4 mt-5">
    <label class="admin-field"><span>Nama Website</span><input name="web_name" value="{{ old('web_name', $webName) }}" placeholder="Opsional jika logo sudah memuat nama brand"></label>
    <label class="admin-field"><span>Tagline Brand</span><input name="brand_tagline" value="{{ old('brand_tagline', $tagline) }}" placeholder="Opsional jika logo sudah memuat tagline"></label>
    <label class="admin-field"><span>Upload Logo Website</span><input type="file" name="logo" accept="image/*"></label>
    <label class="admin-field"><span>Atau URL Logo</span><input name="logo_url" value="" placeholder="https://... atau assets/images/logo.png"></label>

    <label class="admin-field"><span>Instagram Link</span><input name="instagram" value="{{ old('instagram', $settings['instagram'] ?? '') }}" placeholder="Kosongkan jika tidak ingin tampil"></label>
    <label class="admin-field"><span>TikTok Link</span><input name="tiktok" value="{{ old('tiktok', $settings['tiktok'] ?? '') }}" placeholder="Kosongkan jika tidak ingin tampil"></label>
    <label class="admin-field"><span>Facebook Link</span><input name="facebook" value="{{ old('facebook', $settings['facebook'] ?? '') }}" placeholder="Kosongkan jika tidak ingin tampil"></label>
    <label class="admin-field"><span>Twitter / X Link</span><input name="twitter" value="{{ old('twitter', $settings['twitter'] ?? '') }}" placeholder="Kosongkan jika tidak ingin tampil"></label>
    <label class="admin-field md:col-span-2"><span>YouTube Link</span><input name="youtube" value="{{ old('youtube', $settings['youtube'] ?? '') }}" placeholder="Kosongkan jika tidak ingin tampil"></label>
  </div>

  <p class="admin-settings-note">Catatan: kalau link social media dikosongkan, ikon dan link social media otomatis tidak muncul di website.</p>
</form>

<form method="POST" action="{{ route('admin.settings.update') }}" class="admin-panel mt-6 admin-settings-panel admin-sale-timer-panel">
  @csrf
  <input type="hidden" name="setting_section" value="sale_timer">

  <div class="admin-panel-head">
    <div>
      <h2>Pengaturan Sale Landing Page</h2>
      <p>Atur persentase promo dan batas waktu yang tampil pada section Sale di landing page.</p>
    </div>
    <button type="submit" class="green-gradient-btn admin-save-btn">Simpan Pengaturan Sale</button>
  </div>

  <div class="grid md:grid-cols-2 gap-4 mt-5">
    <label class="admin-field">
      <span>Persentase Banner Sale</span>
      <div class="admin-sale-percent-input">
        <input
          type="number"
          name="sale_banner_percentage"
          min="1"
          max="100"
          value="{{ old('sale_banner_percentage', $settings['sale_banner_percentage'] ?? 50) }}"
          required
        >
        <strong>% OFF</strong>
      </div>
      <small>Contoh: isi 35 untuk menampilkan 35% OFF pada landing page.</small>
    </label>

    <label class="admin-field">
      <span>Waktu Sale Berakhir</span>
      <input
        type="datetime-local"
        name="sale_ends_at"
        value="{{ old('sale_ends_at', !empty($settings['sale_ends_at'] ?? null) ? \Carbon\Carbon::parse($settings['sale_ends_at'])->format('Y-m-d\TH:i') : '') }}"
      >
    </label>
    <div class="admin-sale-timer-help">
      <strong>Catatan</strong>
      <span>Kosongkan field lalu simpan jika timer tidak ingin ditampilkan.</span>
    </div>
  </div>
</form>

<link rel="stylesheet" href="{{ asset('css/admin-skin-type-settings.css') }}">

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="admin-panel mt-6 admin-settings-panel admin-skin-type-products-panel" id="skin-type-settings-form">
  @csrf
  <input type="hidden" name="setting_section" value="skin_type_products">

  <div class="admin-panel-head admin-skin-type-head">
    <div>
      <p class="admin-skin-type-kicker">LANDING PAGE CONTENT</p>
      <h2>Shop by Skin Type</h2>
      <p>Tambahkan tipe kulit sebanyak yang diperlukan, lalu pilih produk rekomendasinya.</p>
    </div>
    <button type="button" class="admin-skin-add-type" data-add-skin-type>＋ Tambah Tipe Kulit</button>
  </div>

  <div class="admin-skin-type-grid" data-skin-type-list>
    @foreach($skinTypeDefinitions as $typeIndex => $type)
      @php
        $code = $type['code'] ?? 'skin_type_'.($typeIndex + 1);
        $savedIds = json_decode((string) ($settings['skin_type_'.$code.'_product_ids'] ?? '[]'), true) ?: [];
        $selectedIds = collect(old('skin_types.'.$typeIndex.'.product_ids', $savedIds))->map(fn($id) => (string) $id)->all();
        $savedImageMap = json_decode((string) ($settings['skin_type_'.$code.'_product_images'] ?? '{}'), true) ?: [];
      @endphp
      <section class="admin-skin-type-card" data-skin-type-card>
        <header class="admin-skin-type-card__header">
          <div class="admin-skin-type-card__icon" style="background:{{ $type['color'] ?? '#4F8B3A' }}">
            @if(!empty($type['icon']))
              <img src="{{ $img($type['icon']) }}" alt="Icon {{ $type['label'] ?? 'Skin Type' }}">
            @else
              ✦
            @endif
          </div>
          <div class="admin-skin-type-card__title-fields">
            <input type="hidden" name="skin_types[{{ $typeIndex }}][code]" value="{{ $code }}">
            <input class="admin-skin-type-name" name="skin_types[{{ $typeIndex }}][label]" value="{{ old('skin_types.'.$typeIndex.'.label', $type['label'] ?? '') }}" placeholder="Nama tipe kulit" required>
            <input class="admin-skin-type-hint" name="skin_types[{{ $typeIndex }}][hint]" value="{{ old('skin_types.'.$typeIndex.'.hint', $type['hint'] ?? '') }}" placeholder="Keterangan singkat">
          </div>
          <span class="admin-skin-type-count">{{ count($selectedIds) }} dipilih</span>
          <button type="button" class="admin-skin-remove-type" data-remove-skin-type aria-label="Hapus tipe kulit">×</button>
        </header>

        <div class="admin-skin-type-meta-grid">
          <label><span>Warna</span><input type="color" name="skin_types[{{ $typeIndex }}][color]" value="{{ $type['color'] ?? '#4F8B3A' }}"></label>
          <label><span>Tone</span><select name="skin_types[{{ $typeIndex }}][tone]"><option value="green" @selected(($type['tone'] ?? '') === 'green')>Green</option><option value="gold" @selected(($type['tone'] ?? '') === 'gold')>Gold</option><option value="olive" @selected(($type['tone'] ?? '') === 'olive')>Olive</option></select></label>
          <label class="admin-skin-type-meta-wide"><span>Deskripsi</span><input name="skin_types[{{ $typeIndex }}][description]" value="{{ $type['description'] ?? '' }}" placeholder="Deskripsi tipe kulit"></label>
          <label class="admin-skin-type-meta-wide"><span>Tag</span><input name="skin_types[{{ $typeIndex }}][tags]" value="{{ implode(', ', $type['tags'] ?? []) }}" placeholder="Contoh: Hydration, Barrier Care"></label>
          <label class="admin-skin-type-meta-wide admin-skin-icon-field">
            <span>Logo / Icon Tipe Kulit</span>
            <input type="hidden" name="skin_types[{{ $typeIndex }}][existing_icon]" value="{{ $type['icon'] ?? '' }}">
            <input type="file" name="skin_types[{{ $typeIndex }}][icon]" accept="image/png,image/jpeg,image/webp,image/svg+xml">
            <small>Opsional. Jika kosong, ikon bintang tetap digunakan.</small>
          </label>
        </div>

        <div class="admin-skin-type-card__section">
          <div class="admin-skin-type-card__section-head"><strong>Pilih Produk</strong><small>Boleh pilih lebih dari satu</small></div>
          <div class="admin-skin-product-list">
            @foreach($products as $product)
              <label class="admin-skin-product-option">
                <input type="checkbox" name="skin_types[{{ $typeIndex }}][product_ids][]" value="{{ $product->id }}" @checked(in_array((string) $product->id, $selectedIds, true))>
                <span class="admin-skin-product-option__check"></span>
                <img src="{{ $img($product->image) }}" alt="{{ $product->name }}">
                <span class="admin-skin-product-option__info"><strong>{{ $product->name }}</strong><small>{{ $product->category ?: 'Produk' }} · Rp{{ number_format($product->price, 0, ',', '.') }}</small></span>
              </label>
            @endforeach
          </div>
        </div>

        <div class="admin-skin-type-card__section admin-skin-type-upload">
          <div class="admin-skin-type-card__section-head"><strong>Gambar Khusus</strong><small>Opsional</small></div>
          <p>Upload sesuai urutan produk yang dipilih. Jika kosong, gambar produk utama tetap digunakan.</p>
          <label class="admin-skin-upload-box"><input type="file" name="skin_types[{{ $typeIndex }}][images][]" accept="image/*" multiple><span class="admin-skin-upload-box__icon">＋</span><span>Choose images</span><small>PNG, JPG, WEBP</small></label>
          @if(!empty($savedImageMap))
            <div class="admin-skin-type-saved-images">@foreach($savedImageMap as $productId => $imagePath)<figure><img src="{{ $img($imagePath) }}" alt="Saved image"><figcaption>Gambar tersimpan</figcaption></figure>@endforeach</div>
          @endif
        </div>
      </section>
    @endforeach
  </div>

  <div class="admin-skin-type-save-bottom"><button type="submit" class="green-gradient-btn admin-save-btn">Simpan Pengaturan</button></div>
</form>

<template id="skin-type-card-template">
  <section class="admin-skin-type-card" data-skin-type-card>
    <header class="admin-skin-type-card__header">
      <div class="admin-skin-type-card__icon">✦</div>
      <div class="admin-skin-type-card__title-fields">
        <input type="hidden" data-field="code">
        <input class="admin-skin-type-name" data-field="label" placeholder="Nama tipe kulit" required>
        <input class="admin-skin-type-hint" data-field="hint" placeholder="Keterangan singkat">
      </div>
      <span class="admin-skin-type-count">0 dipilih</span>
      <button type="button" class="admin-skin-remove-type" data-remove-skin-type>×</button>
    </header>
    <div class="admin-skin-type-meta-grid">
      <label><span>Warna</span><input type="color" data-field="color" value="#4F8B3A"></label>
      <label><span>Tone</span><select data-field="tone"><option value="green">Green</option><option value="gold">Gold</option><option value="olive">Olive</option></select></label>
      <label class="admin-skin-type-meta-wide"><span>Deskripsi</span><input data-field="description" placeholder="Deskripsi tipe kulit"></label>
      <label class="admin-skin-type-meta-wide"><span>Tag</span><input data-field="tags" placeholder="Contoh: Hydration, Barrier Care"></label>
      <label class="admin-skin-type-meta-wide admin-skin-icon-field"><span>Logo / Icon Tipe Kulit</span><input type="hidden" data-field="existing_icon" value=""><input type="file" data-field="icon" accept="image/png,image/jpeg,image/webp,image/svg+xml"><small>Opsional. Jika kosong, ikon bintang tetap digunakan.</small></label>
    </div>
    <div class="admin-skin-type-card__section"><div class="admin-skin-type-card__section-head"><strong>Pilih Produk</strong><small>Boleh pilih lebih dari satu</small></div><div class="admin-skin-product-list">
      @foreach($products as $product)
        <label class="admin-skin-product-option"><input type="checkbox" data-product-id="{{ $product->id }}"><span class="admin-skin-product-option__check"></span><img src="{{ $img($product->image) }}" alt="{{ $product->name }}"><span class="admin-skin-product-option__info"><strong>{{ $product->name }}</strong><small>{{ $product->category ?: 'Produk' }} · Rp{{ number_format($product->price, 0, ',', '.') }}</small></span></label>
      @endforeach
    </div></div>
    <div class="admin-skin-type-card__section admin-skin-type-upload"><div class="admin-skin-type-card__section-head"><strong>Gambar Khusus</strong><small>Opsional</small></div><label class="admin-skin-upload-box"><input type="file" data-field="images" accept="image/*" multiple><span class="admin-skin-upload-box__icon">＋</span><span>Choose images</span><small>PNG, JPG, WEBP</small></label></div>
  </section>
</template>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const list = document.querySelector('[data-skin-type-list]');
  const addButton = document.querySelector('[data-add-skin-type]');
  const template = document.getElementById('skin-type-card-template');

  if (!list || !addButton || !template) {
    return;
  }

  function refreshNames() {
    const cards = Array.from(list.querySelectorAll('[data-skin-type-card]'));

    cards.forEach(function (card, index) {
      card.querySelectorAll('[data-field]').forEach(function (input) {
        const field = input.dataset.field;
        input.name = `skin_types[${index}][${field}]${field === 'images' ? '[]' : ''}`;
      });

      card.querySelectorAll('[data-product-id]').forEach(function (input) {
        input.name = `skin_types[${index}][product_ids][]`;
      });

      const codeInput = card.querySelector('[data-field="code"]');
      if (codeInput && !codeInput.value) {
        codeInput.value = `skin_type_${Date.now()}_${index}`;
      }
    });
  }

  function updateSelectedCount(card) {
    const count = card.querySelectorAll('[data-product-id]:checked').length;
    const badge = card.querySelector('.admin-skin-type-count');
    if (badge) {
      badge.textContent = `${count} dipilih`;
    }
  }

  addButton.addEventListener('click', function (event) {
    event.preventDefault();

    const fragment = template.content.cloneNode(true);
    const newCard = fragment.querySelector('[data-skin-type-card]');
    list.appendChild(fragment);
    refreshNames();

    const appendedCard = list.lastElementChild || newCard;
    if (appendedCard) {
      const nameInput = appendedCard.querySelector('[data-field="label"]');
      appendedCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
      window.setTimeout(function () {
        if (nameInput) nameInput.focus();
      }, 350);
    }
  });

  list.addEventListener('click', function (event) {
    const removeButton = event.target.closest('[data-remove-skin-type]');
    if (!removeButton) return;

    event.preventDefault();

    const cards = list.querySelectorAll('[data-skin-type-card]');
    if (cards.length <= 1) return;

    const card = removeButton.closest('[data-skin-type-card]');
    if (card) card.remove();
    refreshNames();
  });

  list.addEventListener('change', function (event) {
    if (!event.target.matches('[data-product-id]')) return;
    const card = event.target.closest('[data-skin-type-card]');
    if (card) updateSelectedCount(card);
  });

  refreshNames();
});
</script>

<div class="admin-panel mt-6 admin-settings-panel">
  <div class="admin-panel-head">
    <div><h2>Hero Section Landing Page</h2><p>Tambah foto hero, edit slide yang sudah ada, nonaktifkan, atau hapus slide tanpa mengubah codingan.</p></div>
  </div>

  <form method="POST" action="{{ route('admin.hero-slides.store') }}" enctype="multipart/form-data" class="admin-hero-add-form">
    @csrf
    <div class="admin-hero-add-head">
      <strong>Tambah Foto Hero Baru</strong>
      <span>Upload gambar atau isi URL gambar.</span>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
      <label class="admin-field"><span>Label</span><input name="label" value="{{ old('label') }}" placeholder="NEW ARRIVAL"></label>
      <label class="admin-field"><span>Judul Hero</span><input name="title" value="{{ old('title') }}" placeholder="Glow Starts Here" required></label>
      <label class="admin-field"><span>Subtitle</span><input name="subtitle" value="{{ old('subtitle') }}" placeholder="Skin-first beauty"></label>
      <label class="admin-field"><span>Urutan</span><input type="number" name="sort_order" value="{{ old('sort_order', ($slides->max('sort_order') ?? 0) + 1) }}" min="1"></label>
      <label class="admin-field md:col-span-2"><span>Upload Foto Hero</span><input type="file" name="image" accept="image/*"></label>
      <label class="admin-field md:col-span-2"><span>Atau URL Foto Hero</span><input name="image_url" value="{{ old('image_url') }}" placeholder="https://... atau assets/images/hero.jpg"></label>
    </div>
    <div class="admin-hero-actions">
      <label class="admin-check"><input type="checkbox" name="is_active" value="1" checked> Aktifkan slide</label>
      <button type="submit" class="green-gradient-btn admin-save-btn">Tambah Slide</button>
    </div>
  </form>

  <div class="admin-hero-editor-grid admin-hero-editor-grid-wide mt-6">
    @forelse($slides as $slide)
      <div class="admin-hero-card admin-hero-card-settings">
        <form method="POST" action="{{ route('admin.hero-slides.update', $slide) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <img src="{{ $img($slide->image) }}" alt="{{ $slide->label }}">
          <div class="admin-hero-form">
            <label class="admin-field"><span>Label Slide</span><input name="label" value="{{ old('label', $slide->label) }}"></label>
            <label class="admin-field"><span>Judul Hero</span><input name="title" value="{{ old('title', $slide->title) }}" required></label>
            <label class="admin-field"><span>Subtitle</span><input name="subtitle" value="{{ old('subtitle', $slide->subtitle) }}"></label>
            <label class="admin-field"><span>Urutan</span><input type="number" name="sort_order" value="{{ old('sort_order', $slide->sort_order) }}" min="1"></label>
            <label class="admin-field"><span>Upload Gambar Pengganti</span><input type="file" name="image" accept="image/*"></label>
            <label class="admin-field"><span>Atau URL Gambar</span><input name="image_url" value="" placeholder="Isi hanya kalau ingin ganti gambar"></label>
            <label class="admin-check"><input type="checkbox" name="is_active" value="1" @checked($slide->is_active)> Aktifkan slide</label>
            <button class="green-gradient-btn admin-save-btn" type="submit">Save Slide</button>
          </div>
        </form>
        <form method="POST" action="{{ route('admin.hero-slides.destroy', $slide) }}" class="admin-hero-delete-form" onsubmit="return confirm('Hapus foto hero ini dari landing page?')">
          @csrf
          @method('DELETE')
          <button type="submit">Hapus Foto Hero</button>
        </form>
      </div>
    @empty
      <div class="admin-empty-settings">Belum ada hero slide. Tambahkan foto hero baru dari form di atas.</div>
    @endforelse
  </div>
</div>
@endsection
