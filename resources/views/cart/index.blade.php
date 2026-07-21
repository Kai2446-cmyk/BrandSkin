@extends('layouts.app')

@section('title', 'Shopping Bag — GlowSkin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart-wishlist-navbar-lavender.css') }}?v=20260710">
@endpush

@section('content')
@php
    $items = collect($items ?? []);
    $wishlistProducts = collect($wishlistProducts ?? []);
    $subtotal = (int) ($subtotal ?? 0);
    $discount = (int) ($discount ?? 0);
    $total = (int) ($total ?? max(0, $subtotal - $discount));
    $promo = $promo ?? null;
    $voucherGroups = collect($voucherGroups ?? []);

    $formatPrice = function ($price) {
        return 'Rp' . number_format((int) $price, 0, ',', '.');
    };

    $imgUrl = function ($raw) {
        $raw = $raw ?: 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?auto=format&fit=crop&w=900&q=85';
        return \Illuminate\Support\Str::startsWith($raw, ['http://','https://']) ? $raw : asset($raw);
    };
@endphp

<link rel="stylesheet" href="{{ asset('css/cart-restore-old-design.css') }}">

<main class="cart-old-page">
    <section class="cart-old-wrap">
        <div class="cart-old-left">
            <div class="cart-old-breadcrumb">
                <a href="{{ route('home') }}">HOME</a>
                <span>/</span>
                <span>SHOPPING BAG</span>
            </div>

            <h1>SHOPPING BAG</h1>

            <div class="cart-old-list" data-cart-list>
                @forelse($items as $item)
                    @php
                        $product = $item->product;
                        if (!$product) continue;
                        $price = (int) ($product->price ?? 0);
                        $lineTotal = $price * (int) $item->quantity;
                    @endphp

                    <article class="cart-old-item" data-cart-item="{{ $item->id }}">
                        <a href="{{ route('products.show', $product->slug) }}" class="cart-old-img">
                            <img src="{{ $imgUrl($product->image ?? null) }}" alt="{{ $product->name }}">
                        </a>

                        <div class="cart-old-info">
                            <a href="{{ route('products.show', $product->slug) }}" class="cart-old-name">
                                {{ $product->name }}
                            </a>

                            <div class="cart-old-qty">
                                <button type="button" data-cart-minus data-item-id="{{ $item->id }}">−</button>
                                <input type="text" value="{{ $item->quantity }}" readonly data-cart-qty>
                                <button type="button" data-cart-plus data-item-id="{{ $item->id }}">+</button>
                            </div>

                            <div class="cart-old-actions">
                                <button type="button"
                                        data-wishlist-from-cart
                                        data-product-id="{{ $product->id }}">
                                    ADD TO WISHLIST
                                </button>

                                <button type="button"
                                        data-cart-remove
                                        data-item-id="{{ $item->id }}">
                                    REMOVE
                                </button>
                            </div>
                        </div>

                        <div class="cart-old-price">
                            {{ $formatPrice($lineTotal) }}
                        </div>
                    </article>
                @empty
                    <div class="cart-old-empty">
                        <h2>Shopping Bag masih kosong</h2>
                        <p>Produk yang kamu tambahkan ke bag akan muncul di halaman ini.</p>
                        <a href="{{ route('catalogue.makeup') }}">SHOP NOW</a>
                    </div>
                @endforelse
            </div>

            <section class="cart-old-wishlist">
                <h2>WISHLIST</h2>

                @if($wishlistProducts->isEmpty())
                    <div class="cart-old-wishlist-empty">
                        Wishlist kamu masih kosong.
                    </div>
                @else
                    <div class="cart-old-wishlist-grid">
                        @foreach($wishlistProducts->take(8) as $product)
                            <article class="cart-old-wishlist-card" data-product-id="{{ $product->id }}">
                                <div class="cart-old-wishlist-image">
                                    <a href="{{ route('products.show', $product->slug) }}">
                                        <img src="{{ $imgUrl($product->image ?? null) }}" alt="{{ $product->name }}">
                                    </a>

                                    <button type="button"
                                            class="cart-old-heart active"
                                            data-wishlist-toggle
                                            data-product-id="{{ $product->id }}">
                                        ♥
                                    </button>
                                </div>

                                <a href="{{ route('products.show', $product->slug) }}" class="cart-old-wishlist-name">
                                    {{ $product->name }}
                                </a>

                                <strong>{{ $formatPrice($product->price ?? 0) }}</strong>

                                <button type="button"
                                        class="cart-old-add"
                                        data-cart-add
                                        data-product-id="{{ $product->id }}">
                                    ADD TO BAG
                                </button>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <aside class="cart-old-summary">
            <h2>ORDER<br>SUMMARY</h2>

            @include('partials.voucher-groups', [
                'voucherGroups' => $voucherGroups,
                'promo' => $promo,
                'subtotal' => $subtotal,
                'formatPrice' => $formatPrice,
            ])

            @if($errors->any())
                <div class="cart-old-message error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if(session('success'))
                <div class="cart-old-message success">{{ session('success') }}</div>
            @endif

            <div class="cart-old-summary-line">
                <span>Subtotal</span>
                <strong>{{ $formatPrice($subtotal) }}</strong>
            </div>

            @if($discount > 0)
                <div class="cart-old-summary-line discount">
                    <span>Discount {{ $promo['code'] ?? '' }}</span>
                    <strong>-{{ $formatPrice($discount) }}</strong>
                </div>
            @endif

            <div class="cart-old-divider"></div>

            <div class="cart-old-summary-line total">
                <span>Total</span>
                <strong>{{ $formatPrice($total) }}</strong>
            </div>

            <a href="{{ route('checkout.index') }}" class="cart-old-checkout cart-old-checkout-link" data-checkout-button>
                PROCEED TO CHECKOUT
            </a>

            <div class="cart-old-payment">
                <span>VISA</span>
                <span>BCA</span>
                <span>BNI</span>
                <span>BRI</span>
                <span>MANDIRI</span>
                <span>OVO</span>
            </div>
        </aside>
    </section>
</main>

<script>
    window.GLOWSKIN_CART_ENDPOINTS = {
        count: "{{ route('cart.count') }}",
        update: "{{ url('/cart') }}",
        remove: "{{ url('/cart') }}"
    };
</script>
<script src="{{ asset('js/cart-restore-old-design.js') }}"></script>
@endsection
