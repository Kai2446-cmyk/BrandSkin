@extends('layouts.app')

@section('title', 'Wishlist — GlowSkin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart-wishlist-navbar-lavender.css') }}?v=20260710">
@endpush

@section('content')
<main class="wishlist-page">
    <section class="wishlist-hero">
        <div class="wishlist-hero-bg"></div>
        <div class="wishlist-hero-overlay"></div>
        <div class="wishlist-container wishlist-hero-content">
            <div class="wishlist-breadcrumb">
                <a href="{{ route('home') }}">HOME</a>
                <span>/</span>
                <span>WISHLIST</span>
            </div>
            <h1>WISHLIST</h1>
            <p>Produk favorit yang tersimpan khusus untuk akun kamu.</p>
        </div>
    </section>

    <section class="wishlist-container wishlist-content">
        <div class="wishlist-head">
            <div>
                <span class="wishlist-eyebrow">SAVED ITEMS</span>
                <h2>My Beauty Wishlist</h2>
            </div>
            <a href="{{ route('catalogue.makeup') }}" class="wishlist-shop-link">Continue Shopping</a>
        </div>

        @if($products->isEmpty())
            <div class="wishlist-empty show">
                <div class="wishlist-empty-icon">♡</div>
                <h3>Wishlist kamu masih kosong</h3>
                <p>Klik tombol love di produk Makeup, Skincare, atau Sale untuk menyimpan produk favorit ke akun kamu.</p>
                <a href="{{ route('catalogue.makeup') }}">Lihat Produk</a>
            </div>
        @else
            <div class="wishlist-grid">
                @foreach($products as $product)
                    @php
                        $img = $product->image ?: 'assets/images/no_image.png';
                        $colors = is_array($product->colors) && count($product->colors) ? $product->colors : ['#D9B391','#F1C9A4','#E5B78F','#C99372','#E7C6AC'];
                    @endphp

                    <article class="wishlist-card" data-product-id="{{ $product->id }}">
                        <a href="{{ route('products.show', $product->slug) }}" class="wishlist-image">
                            <button type="button" class="wishlist-love is-wished" data-wishlist-toggle data-product-id="{{ $product->id }}" aria-label="Remove wishlist">♥</button>
                            <img src="{{ str_starts_with($img, 'http') ? $img : asset($img) }}" alt="{{ $product->alt ?: $product->name }}">
                        </a>

                        <div class="wishlist-swatches">
                            @foreach(array_slice($colors, 0, 6) as $i => $color)
                                <span class="{{ $i === 0 ? 'active' : '' }}" style="background:{{ $color }}"></span>
                            @endforeach
                        </div>

                        <a href="{{ route('products.show', $product->slug) }}" class="wishlist-name">{{ $product->name }}</a>
                        <div class="wishlist-price">Rp{{ number_format($product->price, 0, ',', '.') }}</div>

                        <div class="wishlist-actions">
                            <a href="{{ route('products.show', $product->slug) }}">TRY ON</a>
                            <a href="{{ route('products.show', $product->slug) }}">ADD TO BAG</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</main>
@endsection
