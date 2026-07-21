@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="{{ asset('css/landing-skin-type-products.css') }}">
<link rel="stylesheet" href="{{ asset('css/landing-sale-skin-type-final-only.css') }}?v=20260710-final">
<link rel="stylesheet" href="{{ asset('css/landing-hero-logo-alignment-only.css') }}?v=20260710">
<main class="min-h-screen bg-[var(--background)]">
<section class="hero-section relative w-full overflow-hidden">
@foreach($slides as $i=>$slide)
  <div class="gs-slide {{ $i===0?'active':'' }} absolute inset-0 transition-opacity duration-700">
    <img class="gs-img" src="{{ $slide->image }}" alt="{{ $slide->alt }}"><div class="absolute inset-0 hero-overlay"></div>
    <div class="hero-content absolute inset-0 left-0 right-0 px-6 md:px-12 lg:px-16 z-20">
      <div class="hero-copy max-w-3xl animate-fade-up"><p class="text-hero-sm font-semibold tracking-[0.3em] uppercase text-white/70 mb-3">{{ $slide->label }}</p>
      <h1 class="text-hero-xl font-extrabold uppercase text-white whitespace-pre-line mb-4 leading-none tracking-tight">{{ $slide->title }}</h1>
      <p class="text-sm md:text-base font-medium tracking-[0.2em] uppercase text-white/60 mb-8">{{ $slide->subtitle }}</p><a href="#products" class="green-gradient-btn text-white font-bold uppercase tracking-[0.2em] px-8 py-4 text-sm inline-block">SHOP NOW</a></div>
      <div class="flex items-center gap-6 mt-10"><span data-slide-count class="text-sm font-bold text-white/50 tracking-widest">1 / {{ count($slides) }}</span><button data-slide-prev class="w-10 h-10 border border-white/30 hover:border-[var(--primary)]">‹</button><button data-slide-next class="w-10 h-10 border border-white/30 hover:border-[var(--primary)]">›</button><div class="flex items-center gap-2 ml-2">@foreach($slides as $j=>$s)<button data-slide-dot style="width:{{ $j===0?'32px':'8px' }};height:4px;background:{{ $j===0?'var(--primary)':'rgba(255,255,255,.3)' }};border-radius:2px"></button>@endforeach</div></div>
    </div>
  </div>
@endforeach
</section>
<section id="products" class="bg-white py-12 md:py-16">
  <div class="flex items-center justify-center gap-8 mb-10"><button data-products-tab="new" class="text-base md:text-lg font-bold uppercase tracking-[.15em] pb-2 tab-underline-active" style="color:#111">New Arrival</button><span style="color:#ccc">/</span><button data-products-tab="best" class="text-base md:text-lg font-bold uppercase tracking-[.15em] pb-2" style="color:#888">Best Seller</button></div>
  <div class="max-w-7xl mx-auto px-4 md:px-8 flex flex-col lg:flex-row gap-6 lg:gap-8">
    <div class="w-full lg:w-[42%] relative overflow-hidden group flex-shrink-0" style="min-height:480px"><img class="gs-img group-hover:scale-105 transition-transform duration-700" src="https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=90"><div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div><div class="absolute bottom-8 left-8"><span class="text-white/60 text-xs font-bold tracking-[.3em] uppercase">GlowSkin</span><div data-featured-label class="text-white text-2xl font-extrabold uppercase tracking-tight leading-none whitespace-pre-line">NEW\nARRIVAL</div></div></div>
    <div class="flex-1">
      @foreach(['new'=>$newArrivals,'best'=>$bestSellers] as $panel=>$products)
      <div data-products-panel="{{ $panel }}" class="tab-panel {{ $panel==='new'?'active':'' }}"><div class="grid grid-cols-2 gap-4 md:gap-5">
        @foreach($products as $product)
        <a href="{{ route('products.show',$product->slug) }}" data-product-id="{{ $product->id }}" class="group block bg-white border border-gray-100 hover:border-gray-200 product-card-hover relative"><button type="button" class="wishlist-love absolute top-3 right-3 z-10 w-8 h-8 text-gray-400" data-wishlist-toggle data-product-id="{{ $product->id }}" aria-label="Tambah {{ $product->name }} ke wishlist" aria-pressed="false">♡</button>@if($panel === 'best')<div class="ranking-badge absolute bottom-[40%] right-3 z-10 min-w-9 min-h-9 rounded-full flex flex-col items-center justify-center px-2 py-1" title="Peringkat #{{ $product->best_seller_rank }} Best Seller"><span class="text-white text-xs font-extrabold leading-none">#{{ $product->best_seller_rank }}</span><small class="text-white text-[7px] font-bold uppercase leading-tight mt-1 whitespace-nowrap">BEST SELLER</small></div>@endif @if($product->badge)<div class="absolute top-3 left-3 z-10 bg-[var(--primary)] text-white text-[10px] font-bold px-2 py-1 tracking-widest uppercase">{{ $product->badge }}</div>@endif <div class="relative overflow-hidden bg-gray-50" style="padding-bottom:100%"><img class="absolute inset-0 gs-img group-hover:scale-105 transition-transform duration-500" src="{{ $product->image }}" alt="{{ $product->alt }}"></div><div class="px-3 pt-2 pb-4"><h3 class="text-xs font-bold uppercase tracking-tight leading-tight mb-2" style="color:#111;font-size:11px">{{ $product->name }}</h3><p class="text-sm font-bold" style="color:#111">Rp{{ number_format($product->price,0,',','.') }}</p><button type="button" data-cart-add data-product-id="{{ $product->id }}" class="product-add-to-bag landing-product-add-to-bag mt-2">ADD TO BAG</button></div></a>
        @endforeach
      </div></div>
      @endforeach
    </div>
  </div>
</section>
<section class="sale-gradient-bg sale-home-section py-10 md:py-12 relative overflow-hidden">
  <div class="sale-home-shell">
    <div class="sale-home-head">
      <div class="sale-home-title-wrap">
        <div class="sale-home-badge"><span>{{ (int) ($settings['sale_banner_percentage'] ?? 50) }}% OFF</span><em>⚡</em></div>
        @if(!empty($settings['sale_ends_at'] ?? null))
          @php($saleEndIso = \Carbon\Carbon::parse($settings['sale_ends_at'])->toIso8601String())
          <div class="sale-home-countdown" data-sale-end="{{ $saleEndIso }}" aria-label="Waktu sale tersisa">
            <span><strong data-sale-days>00</strong><small>Hari</small></span>
            <b>:</b>
            <span><strong data-sale-hours>00</strong><small>Jam</small></span>
            <b>:</b>
            <span><strong data-sale-minutes>00</strong><small>Menit</small></span>
            <b>:</b>
            <span><strong data-sale-seconds>00</strong><small>Detik</small></span>
          </div>
        @endif
      </div>
      <a href="{{ url('/sale') }}" class="sale-home-see">SEE ALL →</a>
    </div>

    <div class="sale-home-slider">
      <button data-scroll-left="#sale-scroll" class="sale-home-arrow sale-home-arrow-left">‹</button>
      <button data-scroll-right="#sale-scroll" class="sale-home-arrow sale-home-arrow-right">›</button>

      <div id="sale-scroll" class="sale-home-track scrollbar-hide">
        @foreach($saleProducts as $product)
          <a href="{{ route('products.show',$product->slug) }}" class="sale-home-card group">
            <div class="sale-home-card-box">
              <div class="sale-home-discount">-{{ $product->discount_percentage }}%</div>
              <div class="sale-home-image"><img class="gs-img group-hover:scale-105 transition-transform duration-500" src="{{ $product->image }}" alt="{{ $product->name }}"></div>
              <div class="sale-home-info">
                <p>{{ $product->category }}</p>
                <h3>{{ $product->name }}</h3>
                <span class="sale-home-original">Rp{{ number_format($product->original_price,0,',','.') }}</span>
                <strong>Rp{{ number_format($product->price,0,',','.') }}</strong>
                <button type="button" class="product-add-to-bag" data-cart-add data-product-id="{{ $product->id }}">ADD TO BAG</button>
              </div>
            </div>
          </a>
        @endforeach
      </div>
    </div>
  </div>
</section>
<section class="bg-black py-12 md:py-16 overflow-hidden"><div class="max-w-7xl mx-auto px-4 md:px-8"><div class="flex flex-col lg:flex-row gap-8 lg:gap-12 items-start"><div class="lg:w-[280px] flex-shrink-0 flex flex-col justify-center" style="min-height:320px"><div class="mb-8"><h2 class="text-4xl md:text-5xl font-extrabold uppercase leading-none tracking-tight text-white">BEAUTY</h2><div class="relative"><h2 class="text-4xl md:text-5xl font-extrabold uppercase leading-none tracking-tight text-white">HIGHLIGHT</h2><div class="absolute -bottom-1 left-0 h-1.5" style="width:100%;background:var(--primary)"></div></div></div><a href="{{ route('articles.index') }}" class="green-gradient-btn inline-flex items-center justify-center text-white font-bold uppercase tracking-[.2em] text-sm py-4 px-8 mt-6" style="max-width:220px">DISCOVER ALL</a></div><div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-5">@foreach($articles as $article)<a href="{{ route('articles.show',$article->slug) }}" class="group block bg-white overflow-hidden hover:shadow-2xl transition-all duration-300"><div class="relative overflow-hidden" style="padding-bottom:75%"><img class="absolute inset-0 gs-img group-hover:scale-105 transition-transform duration-500" src="{{ $article->image }}"><div class="absolute top-3 left-3 px-2 py-1 text-[9px] font-bold uppercase tracking-widest" style="background:var(--primary);color:#fff">{{ $article->category }}</div></div><div class="p-4"><h3 class="text-xs font-bold uppercase leading-tight mb-4 line-clamp-3" style="color:#111;min-height:48px">{{ $article->title }}</h3><div class="flex items-center gap-2"><span class="text-xs font-bold uppercase tracking-[.15em]" style="color:#111">READ ARTICLE</span><span class="text-[var(--primary)]">→</span></div></div></a>@endforeach</div></div></div></section>
<section class="skin-type-showcase" aria-labelledby="skin-type-heading">
  <div class="skin-type-showcase__shell">
    <div class="skin-type-showcase__heading">
      <p>FIND YOUR MATCH</p>
      <h2 id="skin-type-heading">SHOP BY SKIN TYPE</h2>
    </div>

    <div class="skin-type-showcase__layout">
      <div class="skin-type-showcase__tabs" role="tablist" aria-label="Tipe kulit">
        <?php foreach ($skinTypes as $i => $type): ?>
          <?php
            $typeCode = $type->code;
            $typeProducts = $skinTypeProducts[$typeCode] ?? collect();
            $isActive = $i === 1;
            $iconPath = $type->icon ?? null;
          ?>
          <button
            type="button"
            data-skin="{{ $typeCode }}"
            class="skin-type-tab {{ $isActive ? 'active' : '' }}"
            role="tab"
            aria-selected="{{ $isActive ? 'true' : 'false' }}"
          >
            <span class="skin-type-tab__icon" style="--skin-color:{{ $type->color }}">
              <?php if (!empty($iconPath)): ?>
                <img
                  src="{{ \Illuminate\Support\Str::startsWith($iconPath, ['http://', 'https://']) ? $iconPath : asset($iconPath) }}"
                  alt="{{ $type->label }}"
                >
              <?php else: ?>
                ✦
              <?php endif; ?>
            </span>
            <span>
              <strong>{{ $type->label }}</strong>
              <small>{{ $typeProducts->count() }} products</small>
            </span>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="skin-type-showcase__panels">
        <?php foreach ($skinTypes as $i => $type): ?>
          <?php
            $typeCode = $type->code;
            $recommendedProducts = $skinTypeProducts[$typeCode] ?? collect();
            $customImageMap = $skinTypeProductImages[$typeCode] ?? [];
            $isActive = $i === 1;
          ?>

          <div
            data-skin-panel="{{ $typeCode }}"
            class="tab-panel skin-type-panel {{ $isActive ? 'active' : '' }}"
            role="tabpanel"
          >
            <div class="skin-type-products-grid">
              <?php if ($recommendedProducts->isNotEmpty()): ?>
                <?php foreach ($recommendedProducts as $recommendedProduct): ?>
                  <?php
                    $displayImage = $customImageMap[(string) $recommendedProduct->id]
                      ?? $recommendedProduct->image;
                    $imageUrl = \Illuminate\Support\Str::startsWith(
                      (string) $displayImage,
                      ['http://', 'https://']
                    ) ? $displayImage : asset($displayImage);
                  ?>

                  <a
                    href="{{ route('products.show', $recommendedProduct->slug) }}"
                    class="skin-type-product-card"
                    aria-label="Lihat {{ $recommendedProduct->name }}"
                  >
                    <div class="skin-type-product-card__image">
                      <img
                        src="{{ $imageUrl }}"
                        alt="{{ $recommendedProduct->alt ?: $recommendedProduct->name }}"
                      >
                      <span>BEST MATCH</span>
                    </div>

                    <div class="skin-type-product-card__body">
                      <small>{{ strtoupper($recommendedProduct->category ?: 'SKINCARE') }}</small>
                      <h4>{{ $recommendedProduct->name }}</h4>
                      <strong>Rp{{ number_format($recommendedProduct->price, 0, ',', '.') }}</strong>
                    </div>
                  </a>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="skin-type-product-card skin-type-product-card--empty">
                  <div class="skin-type-product-card__body">
                    <small>PRODUCT RECOMMENDATION</small>
                    <h4>Belum ada produk yang dipilih</h4>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <div class="skin-type-panel__copy">
              <p class="skin-type-panel__eyebrow">RECOMMENDED FOR</p>
              <h3>{{ $type->label }}</h3>
              <p class="skin-type-panel__description">{{ $type->description }}</p>

              <div class="skin-type-panel__tags">
                <?php foreach (($type->tags ?? []) as $tag): ?>
                  <span>{{ $tag }}</span>
                <?php endforeach; ?>
              </div>

              <?php if ($recommendedProducts->isNotEmpty()): ?>
                <a
                  href="{{ route('products.show', $recommendedProducts->first()->slug) }}"
                  class="skin-type-panel__button"
                >
                  VIEW FIRST PRODUCT →
                </a>
              <?php else: ?>
                <a href="{{ route('catalogue.skincare') }}" class="skin-type-panel__button">
                  EXPLORE {{ $type->label }} PRODUCTS →
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<section class="relative py-20 overflow-hidden"><img class="absolute inset-0 gs-img" src="https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1600&q=90"><div class="absolute inset-0 connect-overlay"></div><div class="relative max-w-7xl mx-auto px-4 md:px-8 text-center"><p class="text-xs font-bold uppercase tracking-[.35em] mb-3" style="color:var(--primary)">CONNECT WITH US</p><h2 class="text-4xl md:text-6xl font-extrabold uppercase tracking-tight text-white mb-6">JOIN THE GLOW</h2><p class="text-white/60 max-w-xl mx-auto mb-8">Follow GlowSkin and discover new products, tutorials, and beauty inspiration.</p><a href="https://www.instagram.com" class="green-gradient-btn inline-flex text-white font-bold uppercase tracking-[.2em] py-4 px-8">FOLLOW INSTAGRAM</a></div></section>
</main>
@endsection


@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const timer = document.querySelector('.sale-home-countdown');
    if (!timer) return;

    const endAt = Date.parse(timer.dataset.saleEnd || '');
    if (Number.isNaN(endAt)) {
        timer.hidden = true;
        return;
    }

    const fields = {
        days: timer.querySelector('[data-sale-days]'),
        hours: timer.querySelector('[data-sale-hours]'),
        minutes: timer.querySelector('[data-sale-minutes]'),
        seconds: timer.querySelector('[data-sale-seconds]'),
    };
    const pad = value => String(Math.max(0, value)).padStart(2, '0');

    let intervalId = null;
    const render = () => {
        const remaining = endAt - Date.now();
        if (remaining <= 0) {
            fields.days.textContent = '00';
            fields.hours.textContent = '00';
            fields.minutes.textContent = '00';
            fields.seconds.textContent = '00';
            timer.classList.add('is-ended');
            if (intervalId) window.clearInterval(intervalId);
            return;
        }

        fields.days.textContent = pad(Math.floor(remaining / 86400000));
        fields.hours.textContent = pad(Math.floor((remaining % 86400000) / 3600000));
        fields.minutes.textContent = pad(Math.floor((remaining % 3600000) / 60000));
        fields.seconds.textContent = pad(Math.floor((remaining % 60000) / 1000));
    };

    render();
    intervalId = window.setInterval(render, 1000);
});
</script>
@endpush
