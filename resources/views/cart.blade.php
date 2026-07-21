@extends('layouts.app')

@section('title', 'Shopping Bag — GlowSkin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/cart-wishlist-navbar-lavender.css') }}?v=20260710">
@endpush

@section('content')
<style>
.cart-refresh-enter{animation:cartRefreshEnter .28s ease both}
@keyframes cartRefreshEnter{from{opacity:.55;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
</style>
<main class="cart-page">
  <section class="cart-wrap" data-cart-page-wrap>
    <div class="cart-main">
      <div class="cart-breadcrumb">
        <a href="{{ route('home') }}">HOME</a>
        <span>/</span>
        <span>SHOPPING BAG</span>
      </div>

      <h1>SHOPPING BAG</h1>

      @if($items->isEmpty())
        <div class="cart-empty">
          <div>🛍️</div>
          <h2>Shopping bag masih kosong</h2>
          <p>Tambahkan produk dari Makeup, Skincare, atau Sale.</p>
          <a href="{{ route('catalogue.makeup') }}">Belanja Produk</a>
        </div>
      @else
        <div class="cart-items">
          @foreach($items as $item)
            @php
              $product = $item->product;
              $img = $product?->image ?: 'assets/images/no_image.png';
            @endphp

            @if($product)
              <article class="cart-item" data-cart-item-id="{{ $item->id }}" data-product-id="{{ $product->id }}">
                <a href="{{ route('products.show', $product->slug) }}" class="cart-item-img">
                  <img src="{{ str_starts_with($img, 'http') ? $img : asset($img) }}" alt="{{ $product->alt ?: $product->name }}">
                </a>

                <div class="cart-item-info">
                  <a href="{{ route('products.show', $product->slug) }}" class="cart-item-name">{{ $product->name }}</a>

                  <form method="POST" action="{{ route('cart.update', $item) }}" class="cart-qty" data-cart-qty-form>
                    @csrf
                    @method('PUT')

                    @if($item->quantity <= 1)
                      <button type="button" data-cart-min-alert>−</button>
                    @else
                      <button type="submit" name="quantity" value="{{ $item->quantity - 1 }}">−</button>
                    @endif

                    <span>{{ $item->quantity }}</span>
                    <button type="submit" name="quantity" value="{{ $item->quantity + 1 }}">+</button>
                  </form>

                  <div class="cart-item-links">
                    <button type="button" data-wishlist-toggle data-product-id="{{ $product->id }}">ADD TO WISHLIST</button>

                    <form method="POST" action="{{ route('cart.remove', $item) }}" data-cart-remove-form data-product-name="{{ $product->name }}">
                      @csrf
                      @method('DELETE')
                      <button type="submit">REMOVE</button>
                    </form>
                  </div>
                </div>

                <div class="cart-item-price">Rp{{ number_format($product->price * $item->quantity, 0, ',', '.') }}</div>
              </article>
            @endif
          @endforeach
        </div>
      @endif

      <section class="cart-wishlist">
        <h2>WISHLIST</h2>

        @if($wishlistProducts->isEmpty())
          <p class="cart-small-empty">Belum ada produk wishlist untuk akun ini.</p>
        @else
          <div class="cart-wishlist-grid">
            @foreach($wishlistProducts as $product)
              @php
                $img = $product->image ?: 'assets/images/no_image.png';
              @endphp

              <article class="cart-wish-card" data-product-id="{{ $product->id }}">
                <a href="{{ route('products.show', $product->slug) }}" class="cart-wish-img">
                  <button type="button" class="cart-wish-love is-wished" data-wishlist-toggle data-product-id="{{ $product->id }}">♥</button>
                  <img src="{{ str_starts_with($img, 'http') ? $img : asset($img) }}" alt="{{ $product->alt ?: $product->name }}">
                </a>

                <a href="{{ route('products.show', $product->slug) }}" class="cart-wish-name">{{ $product->name }}</a>
                <div class="cart-wish-price">Rp{{ number_format($product->price, 0, ',', '.') }}</div>
                <button type="button" class="cart-add-link" data-cart-add data-product-id="{{ $product->id }}">ADD TO BAG</button>
              </article>
            @endforeach
          </div>
        @endif
      </section>
    </div>

    <aside class="cart-summary">
      <h2>ORDER SUMMARY</h2>

      <form method="POST" action="{{ route('cart.promo.apply') }}" class="cart-promo">
        @csrf
        <input name="promo_code" placeholder="ENTER PROMO CODE" value="{{ $promo?->code }}">
        <button type="submit">APPLY</button>
      </form>

      @if($promo)
        <div class="cart-promo-active">
          <span>Promo {{ $promo->code }} aktif</span>
          <form method="POST" action="{{ route('cart.promo.remove') }}">
            @csrf
            @method('DELETE')
            <button type="submit">hapus</button>
          </form>
        </div>
      @endif

      @if($promoError)
        <div class="cart-promo-error">{{ $promoError }}</div>
      @endif

      <div class="cart-summary-line">
        <span>Subtotal</span>
        <strong>Rp{{ number_format($subtotal, 0, ',', '.') }}</strong>
      </div>

      @if($discount > 0)
        <div class="cart-summary-line discount">
          <span>Discount</span>
          <strong>-Rp{{ number_format($discount, 0, ',', '.') }}</strong>
        </div>
      @endif

      <div class="cart-summary-line total">
        <span>Total</span>
        <strong>Rp{{ number_format($total, 0, ',', '.') }}</strong>
      </div>

      <button type="button" class="cart-checkout">PROCEED TO CHECKOUT</button>

      <div class="cart-payments">
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
@endsection
