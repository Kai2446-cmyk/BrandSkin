@extends('layouts.app')

@section('title', 'Pembayaran Berhasil — GlowSkin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/checkout-glowskin.css') }}">
@endpush

@section('content')
@php
    $formatPrice = fn($price) => 'Rp' . number_format((int) $price, 0, ',', '.');
    $imgUrl = function ($raw) {
        $raw = $raw ?: 'assets/images/no_image.png';
        return \Illuminate\Support\Str::startsWith($raw, ['http://','https://']) ? $raw : asset($raw);
    };
    $isPaid = ($order->payment_status ?? 'pending') === 'paid';
@endphp

<main class="checkout-page checkout-success-page">
    <section class="checkout-success-hero">
        <div class="checkout-success-card">
            <div class="checkout-success-badge">{{ $isPaid ? '✓' : '!' }}</div>
            <p class="checkout-success-eyebrow">{{ $isPaid ? 'Payment Success' : 'Payment Pending' }}</p>
            <h1>{{ $isPaid ? 'Pembayaran Berhasil' : 'Menunggu Pembayaran' }}</h1>
            <p class="checkout-success-copy">
                {{ $isPaid
                    ? 'Terima kasih. Pesanan kamu sudah berhasil dibuat dan masuk ke history pemesanan profile.'
                    : 'Pesanan kamu sudah masuk ke history. Selesaikan pembayaran sesuai instruksi Midtrans.' }}
            </p>

            <div class="checkout-success-summary">
                <span>Order ID</span>
                <strong>{{ $order->order_code }}</strong>
            </div>
            <div class="checkout-success-summary">
                <span>Total Bayar</span>
                <strong>{{ $formatPrice($order->grand_total) }}</strong>
            </div>
            <div class="checkout-success-summary">
                <span>Status</span>
                <strong class="{{ $isPaid ? 'paid' : 'pending' }}">{{ $isPaid ? 'SUCCESS' : 'PENDING' }}</strong>
            </div>

            <div class="checkout-success-actions">
                <a href="{{ route('profile.orders') }}">LIHAT HISTORY PEMESANAN</a>
                <a href="{{ route('home') }}" class="ghost">KEMBALI KE HOME</a>
            </div>
        </div>
    </section>

    <section class="checkout-shell checkout-success-detail">
        <div class="checkout-card checkout-review-card">
            <div class="checkout-section-title">
                <h2>Barang yang Dibeli</h2>
                <span>{{ $order->items->sum('quantity') }} item</span>
            </div>

            <div class="checkout-review-items">
                @foreach($order->items as $item)
                    <article class="checkout-review-item">
                        <img src="{{ $imgUrl($item->product_image) }}" alt="{{ $item->product_name }}">
                        <div>
                            <h3>{{ $item->product_name }}</h3>
                            <p>Qty {{ $item->quantity }} × {{ $formatPrice($item->price) }}</p>
                        </div>
                        <strong>{{ $formatPrice($item->price * $item->quantity) }}</strong>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="checkout-card checkout-review-card">
            <div class="checkout-section-title">
                <h2>Pengiriman</h2>
                <span>{{ $formatPrice($order->shipping_cost) }}</span>
            </div>

            <div class="checkout-review-info">
                <strong>{{ $order->shipping_courier }} {{ $order->shipping_service }}</strong>
                <p>{{ $order->shipping_etd ?: '-' }}</p>
                <p>{{ $order->recipient_name }} · {{ $order->phone }}</p>
                <p>{{ $order->address_line }}, {{ $order->district }}, {{ $order->city }}, {{ $order->province }} {{ $order->postal_code }}</p>
            </div>
        </div>
    </section>
</main>
@endsection
