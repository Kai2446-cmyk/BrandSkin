@extends('layouts.app')

@section('title', 'My Order — GlowSkin')

@section('content')
@php
    $formatPrice = fn($price) => 'Rp' . number_format((int) $price, 0, ',', '.');
    $imgUrl = function ($raw) {
        $raw = $raw ?: 'assets/images/no_image.png';
        return \Illuminate\Support\Str::startsWith($raw, ['http://','https://']) ? $raw : asset($raw);
    };
@endphp


<link rel="stylesheet" href="{{ asset('css/profile-page.css') }}?v=20260710-profile-base">
<link rel="stylesheet" href="{{ asset('css/profile-pages-lavender-navbar-order-fix.css') }}?v=20260710">

<main class="profile-page profile-orders-page">
    <section class="profile-shell">
        <aside class="profile-sidebar">
            <a href="{{ route('profile.index') }}">My Profile <span>♥</span></a>
            <a href="{{ route('profile.orders') }}" class="active">My Order</a>
            <a href="{{ route('wishlist.index') }}">My Wishlist</a>
            <a href="{{ route('profile.vouchers') }}">My Voucher</a>
            <a href="{{ route('profile.skin-diary') }}">Skin Diary</a>
        </aside>

        <section class="profile-card profile-order-card">
            <div class="profile-head">
                <div>
                    <span>Order History</span>
                    <h1>My Order</h1>
                    <p>Riwayat pemesanan user ini saja, lengkap dengan status pending, success, dan failed.</p>
                </div>
                <div class="profile-avatar-big">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</div>
            </div>

            @if(session('success'))
                <div class="profile-alert success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="profile-alert error">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="profile-alert error">{{ $errors->first() }}</div>
            @endif

            <div class="profile-orders-list">
                @forelse($orders as $order)
                    @php
                        $status = strtolower((string) $order->payment_status);
                        $isPaid = in_array($status, ['paid', 'success', 'settlement', 'capture'], true);
                        $isFailed = in_array($status, ['failed', 'failure', 'deny', 'denied', 'cancel', 'cancelled', 'expire', 'expired'], true);
                        $statusLabel = $isPaid ? 'SUCCESS' : ($isFailed ? 'FAILED' : 'PENDING');
                        $statusClass = $isPaid ? 'paid' : ($isFailed ? 'failed' : 'pending');
                    @endphp
                    <article class="profile-order-box">
                        <div class="profile-order-top">
                            <div>
                                <strong>{{ $order->order_code }}</strong>
                                <p>{{ optional($order->created_at)->format('d M Y, H:i') }} · {{ $order->shipping_courier }} {{ $order->shipping_service }}</p>
                            </div>
                            <span class="profile-order-status {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="profile-order-items">
                            @foreach($order->items as $item)
                                @php
                                    $product = $item->product;
                                    $existingReview = $product
                                        ? \App\Models\ProductReview::where('user_id', $user->id)->where('product_id', $product->id)->first()
                                        : null;
                                @endphp
                                <div class="profile-order-item">
                                    <img src="{{ $imgUrl($item->product_image) }}" alt="{{ $item->product_name }}">
                                    <div class="profile-order-item-main">
                                        <h3>{{ $item->product_name }}</h3>
                                        <p>Qty {{ $item->quantity }} × {{ $formatPrice($item->price) }}</p>

                                        @if($isPaid && $product)
                                            <details class="profile-review-details" @if($errors->any() && old('product_id') == $product->id) open @endif>
                                                <summary>{{ $existingReview ? 'Edit Ulasan Produk' : 'Isi Ulasan Produk' }}</summary>
                                                <form method="POST" action="{{ route('products.reviews.store', $product) }}" class="profile-review-form">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <label>
                                                        <span>Rating</span>
                                                        <select name="rating" required>
                                                            @for($i = 5; $i >= 1; $i--)
                                                                <option value="{{ $i }}" @selected((int) old('rating', $existingReview->rating ?? 5) === $i)>{{ $i }} Bintang</option>
                                                            @endfor
                                                        </select>
                                                    </label>
                                                    <label>
                                                        <span>Ulasan</span>
                                                        <textarea name="review" rows="3" required placeholder="Tulis pengalaman kamu pakai produk ini...">{{ old('review', $existingReview->review ?? '') }}</textarea>
                                                    </label>
                                                    <button type="submit">Simpan Ulasan</button>
                                                </form>
                                            </details>
                                        @elseif($isFailed)
                                            <small class="profile-review-locked failed">Pesanan gagal, produk tidak bisa diulas.</small>
                                        @else
                                            <small class="profile-review-locked">Ulasan bisa diisi setelah pembayaran success.</small>
                                        @endif
                                    </div>
                                    <strong>{{ $formatPrice($item->price * $item->quantity) }}</strong>
                                </div>
                            @endforeach
                        </div>

                        <div class="profile-order-bottom">
                            <div>
                                <span>Shipping</span>
                                <strong>{{ $formatPrice($order->shipping_cost) }}</strong>
                            </div>
                            <div>
                                <span>Total Bayar</span>
                                <strong>{{ $formatPrice($order->grand_total) }}</strong>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="profile-order-empty">
                        <strong>Belum ada pemesanan.</strong>
                        <p>Pesanan yang sudah masuk Midtrans akan tampil di sini.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </section>
</main>

<script src="{{ asset('js/profile-page.js') }}"></script>
@endsection
