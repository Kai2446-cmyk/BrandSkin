@extends('layouts.app')

@section('title', $title . ' — GlowSkin Catalogue')

@section('content')
@php
    $pageTitle = strtoupper($title);
    $crumb = strtoupper($type);
    $count = $products->count();
    $topSellerIds = $topSellerIds ?? [];
    $topRatingIds = $topRatingIds ?? [];
@endphp

<link rel="stylesheet" href="{{ asset('css/catalogue_auto_rank_patch.css') }}">
<link rel="stylesheet" href="{{ asset('css/sale-page-show-animation.css') }}">
<link rel="stylesheet" href="{{ asset('css/catalogue-live-search.css') }}">
<link rel="stylesheet" href="{{ asset('css/catalogue-navbar-lavender.css') }}?v=1.0">

<main class="catalogue-page catalogue-page-{{ $type }}" data-page-ready="false">
    <section class="catalogue-hero">
        <div class="catalogue-hero-bg" style="background-image:url('{{ str_starts_with($heroImage, 'http') ? $heroImage : asset($heroImage) }}')"></div>
        <div class="catalogue-hero-overlay"></div>
        <div class="catalogue-container catalogue-hero-content">
            <div class="catalogue-breadcrumb"><a href="{{ route('home') }}">HOME</a><span>/</span><span>{{ $crumb }}</span></div>
            <h1>{{ $pageTitle }}</h1>
        </div>
    </section>

    <section class="catalogue-container catalogue-layout" data-catalogue-layout>
        <aside class="catalogue-filter">
            <form method="GET" id="catalogueFilterForm" data-catalogue-filter-form>
                <label>Sort by</label>
                <select name="sort">
                    <option value="default" @selected($keywordSort === 'default')>Best Seller</option>
                    <option value="new" @selected($keywordSort === 'new')>New Arrival</option>
                    <option value="best" @selected($keywordSort === 'best')>Best Seller</option>
                    <option value="price_low" @selected($keywordSort === 'price_low')>Price: Low to High</option>
                    <option value="price_high" @selected($keywordSort === 'price_high')>Price: High to Low</option>
                </select>

                <label>Category</label>
                <select name="category">
                    <option value="all" @selected($keywordCategory === 'all')>All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}" @selected($keywordCategory === $category)>{{ $category }}</option>
                    @endforeach
                </select>

                <label>Price range</label>
                <select name="price">
                    <option value="all" @selected($keywordPrice === 'all')>All price range</option>
                    <option value="under150" @selected($keywordPrice === 'under150')>Under Rp150.000</option>
                    <option value="150to200" @selected($keywordPrice === '150to200')>Rp150.000 - Rp200.000</option>
                    <option value="above200" @selected($keywordPrice === 'above200')>Above Rp200.000</option>
                </select>
            </form>
        </aside>

        <div class="catalogue-products-wrap" data-catalogue-products-wrap>
            <div class="catalogue-search" data-catalogue-search-wrap>
                <span class="catalogue-search-icon" aria-hidden="true">⌕</span>
                <input
                    type="search"
                    id="catalogueLiveSearch"
                    class="catalogue-search-input"
                    placeholder="Search {{ strtolower($title) }} products..."
                    autocomplete="off"
                    aria-label="Search {{ strtolower($title) }} products"
                    data-catalogue-search
                >
                <button type="button" class="catalogue-search-clear" aria-label="Clear search" data-catalogue-search-clear>×</button>
            </div>
            <div class="catalogue-count"><strong data-catalogue-count>{{ $count }}</strong> products available</div>

            @if($count < 1)
                <div class="catalogue-sale-empty">
                    <div class="catalogue-sale-empty-icon">%</div>
                    <h2>Belum ada produk sale</h2>
                    <p>Produk diskon akan muncul otomatis di halaman ini setelah admin menandai produk sebagai Sale atau mengisi harga coret/diskon.</p>
                    <a href="{{ route('catalogue.makeup') }}">Lihat Makeup</a>
                </div>
            @else
                <div class="catalogue-grid" id="catalogueGrid">
                    @foreach($products as $product)
                        @php
                            $img = $product->image ?: 'assets/images/no_image.png';
                            $colors = is_array($product->colors) && count($product->colors) ? $product->colors : [];
                            $categoryName = strtolower((string) ($product->category ?? ''));
                            $isSkincareProduct = preg_match('/skincare|skin care|serum|moisturizer|toner|cleanser|sunscreen|mask|facial|essence|ampoule/', $categoryName);
                            $showColorTone = $type === 'makeup' || ($type === 'sale' && ! $isSkincareProduct);
                            $hasColors = $showColorTone && count($colors) > 0;
                            $hasSale = $product->is_on_sale || $product->original_price || $product->discount_percentage > 0;
                            $sellerIndex = array_search($product->id, $topSellerIds);
                            $ratingIndex = array_search($product->id, $topRatingIds);
                            $sellerRank = $sellerIndex !== false ? $sellerIndex + 1 : null;
                            $ratingRank = $ratingIndex !== false ? $ratingIndex + 1 : null;
                        @endphp

                        <article
                            class="catalogue-card reveal-on-scroll"
                            data-product-id="{{ $product->id }}"
                            data-search-text="{{ strtolower(trim($product->name . ' ' . ($product->subtitle ?? '') . ' ' . ($product->category ?? ''))) }}"
                            data-sold-count="{{ (int) ($product->sold_count ?? 0) }}"
                            data-rating="{{ (float) ($product->rating_avg ?? 0) }}"
                            @if($hasSale) data-sale-card="true" @endif
                        >
                            <a href="{{ route('products.show', $product->slug) }}" class="catalogue-image-wrap">
                                @if($hasSale)
                                    <span class="catalogue-badge badge-sale">SALE</span>
                                    @if($product->discount_percentage > 0)
                                        <span class="catalogue-sale-discount">-{{ $product->discount_percentage }}%</span>
                                    @endif
                                @elseif($sellerRank && $sellerRank <= 10)
                                    <span class="catalogue-badge badge-best">BEST SELLER #{{ $sellerRank }}</span>
                                @elseif($ratingRank && $ratingRank <= 10)
                                    <span class="catalogue-badge badge-rating">RATING TERBAIK #{{ $ratingRank }}</span>
                                @elseif($product->is_new_arrival)
                                    <span class="catalogue-badge badge-new">NEW</span>
                                @endif

                                <button type="button" class="catalogue-wish" data-wishlist-toggle data-product-id="{{ $product->id }}" aria-label="Wishlist">♡</button>
                                <img src="{{ str_starts_with($img, 'http') ? $img : asset($img) }}" alt="{{ $product->alt ?: $product->name }}" loading="lazy">
                            </a>

                            {{-- Pilihan tone sengaja hanya ditampilkan di halaman detail produk. --}}

                            <a href="{{ route('products.show', $product->slug) }}" class="catalogue-name">{{ $product->name }}</a>

                            @if($hasSale && $product->original_price)
                                <div class="catalogue-sale-price">
                                    <span class="old">Rp{{ number_format($product->original_price, 0, ',', '.') }}</span>
                                    <span class="new">Rp{{ number_format($product->price, 0, ',', '.') }}</span>
                                </div>
                            @else
                                <div class="catalogue-price">Rp{{ number_format($product->price, 0, ',', '.') }}</div>
                            @endif

                            <div class="catalogue-actions catalogue-actions--cart-only">
                                <button type="button" class="product-add-to-bag" data-cart-add data-product-id="{{ $product->id }}">ADD TO BAG</button>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="catalogue-search-empty" data-catalogue-search-empty hidden>
                    <strong>No products found</strong>
                    <span>Try another product name or keyword.</span>
                </div>
            @endif

            @if($count > 0)
                <div class="catalogue-load">
                    <p>You've viewed <strong>{{ $count }}</strong> of <strong>{{ $count }}</strong> products</p>
                    <button type="button">LOAD MORE</button>
                </div>
            @endif
        </div>
    </section>
</main>

<script src="{{ asset('js/sale-page-show-animation.js') }}"></script>
<script src="{{ asset('js/catalogue-live-search.js') }}"></script>
@endsection
