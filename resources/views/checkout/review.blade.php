@extends('layouts.app')

@section('title', 'Verifikasi Pesanan — GlowSkin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/checkout-glowskin.css') }}">
<link rel="stylesheet" href="{{ asset('css/checkout-lavender-only.css') }}?v=20260710b">
@endpush

@push('scripts')
@php
    $midtransClientKey = trim((string) env('MIDTRANS_CLIENT_KEY'));
    $midtransIsProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);
    $midtransSnapJs = $midtransIsProduction
        ? 'https://app.midtrans.com/snap/snap.js'
        : 'https://app.sandbox.midtrans.com/snap/snap.js';
@endphp
@if($midtransClientKey !== '')
<script src="{{ $midtransSnapJs }}" data-client-key="{{ $midtransClientKey }}"></script>
@endif
<script>
(function () {
    const form = document.querySelector('[data-midtrans-popup-form]');
    const button = document.querySelector('[data-midtrans-pay-button]');
    const alertBox = document.querySelector('[data-midtrans-alert]');

    if (!form || !button) return;

    const showMessage = (message) => {
        if (!alertBox) return;
        alertBox.textContent = message || 'Pembayaran belum bisa dibuka. Coba beberapa saat lagi.';
        alertBox.hidden = false;
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    form.addEventListener('submit', async function (event) {
        event.preventDefault();

        if (alertBox) {
            alertBox.hidden = true;
            alertBox.textContent = '';
        }

        button.disabled = true;
        button.dataset.originalText = button.dataset.originalText || button.textContent.trim();
        button.textContent = 'MEMBUKA MIDTRANS...';

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Pembayaran belum bisa dibuka.');
            }

            const finishPayment = async (result, fallbackStatus) => {
                const finishResponse = await fetch('{{ route('checkout.payment.complete') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': form.querySelector('input[name="_token"]')?.value || '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        order_id: result?.order_id || data.order_id,
                        transaction_status: result?.transaction_status || fallbackStatus,
                        transaction_id: result?.transaction_id || '',
                        payment_type: result?.payment_type || '',
                    }),
                });

                const finishData = await finishResponse.json().catch(() => ({}));
                if (!finishResponse.ok || !finishData.ok) {
                    throw new Error(finishData.message || 'Status pembayaran belum bisa disimpan.');
                }

                window.location.href = finishData.redirect_url;
            };

            if (data.snap_token && window.snap && typeof window.snap.pay === 'function') {
                window.snap.pay(data.snap_token, {
                    onSuccess: function (result) { finishPayment(result, 'settlement').catch((err) => showMessage(err.message)); },
                    onPending: function (result) { finishPayment(result, 'pending').catch((err) => showMessage(err.message)); },
                    onError: function () { showMessage('Pembayaran gagal dari Midtrans. Silakan coba lagi.'); },
                    onClose: function () {
                        button.disabled = false;
                        button.textContent = button.dataset.originalText || 'BAYAR SEKARANG';
                    },
                });
                return;
            }

            throw new Error('Popup Midtrans belum siap. Refresh halaman lalu coba lagi.');
        } catch (error) {
            showMessage(error.message);
        } finally {
            button.disabled = false;
            button.textContent = button.dataset.originalText || 'BAYAR SEKARANG';
        }
    });
})();
</script>
@endpush

@section('content')
@php
    $items = collect($items ?? []);
    $subtotal = (int) ($subtotal ?? 0);
    $discount = (int) ($discount ?? 0);
    $shippingCost = (int) ($shippingCost ?? 0);
    $grandTotal = (int) ($grandTotal ?? max(0, $subtotal - $discount + $shippingCost));
    $formatPrice = fn($price) => 'Rp' . number_format((int) $price, 0, ',', '.');
    $imgUrl = function ($raw) {
        $raw = $raw ?: 'assets/images/no_image.png';
        return \Illuminate\Support\Str::startsWith($raw, ['http://','https://']) ? $raw : asset($raw);
    };
@endphp

<main class="checkout-page checkout-review-page">
    <section class="checkout-hero">
        <div class="checkout-shell">
            <div>
                <p>Order Verification</p>
                <h1>Verifikasi Pesanan</h1>
            </div>
            <a href="{{ route('checkout.index') }}">Back to Checkout</a>
        </div>
    </section>

    @if($errors->has('payment'))
        <div class="checkout-alert checkout-shell">
            {{ $errors->first('payment') }}
        </div>
    @elseif($errors->any())
        <div class="checkout-alert checkout-shell">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="checkout-alert checkout-shell" data-midtrans-alert hidden></div>

    <section class="checkout-shell checkout-review-grid">
        <div class="checkout-main">
            <section class="checkout-card checkout-review-card">
                <div class="checkout-section-title">
                    <h2>BARANG YANG DIBELI</h2>
                    <span>{{ $items->sum('quantity') }} item</span>
                </div>

                <div class="checkout-review-items">
                    @foreach($items as $item)
                        @php $product = $item->product; @endphp
                        @if($product)
                            <article class="checkout-review-item">
                                <img src="{{ $imgUrl($product->image ?? null) }}" alt="{{ $product->name }}">
                                <div>
                                    <h3>{{ $product->name }}</h3>
                                    <p>Qty {{ $item->quantity }} × {{ $formatPrice($product->price ?? 0) }}</p>
                                </div>
                                <strong>{{ $formatPrice(($product->price ?? 0) * $item->quantity) }}</strong>
                            </article>
                        @endif
                    @endforeach
                </div>
            </section>

            <section class="checkout-card checkout-review-card">
                <div class="checkout-section-title">
                    <h2>ALAMAT PENGIRIMAN</h2>
                    <a href="{{ route('checkout.index') }}">EDIT</a>
                </div>

                <div class="checkout-review-info">
                    <strong>{{ $address->label ?: 'Home' }}</strong>
                    <p>{{ $address->recipient_name }} · {{ $address->phone }}</p>
                    <p>
                        {{ $address->address_line }}
                        @if($address->district), {{ $address->district }}@endif
                        @if($address->city), {{ $address->city }}@endif
                        @if($address->province), {{ $address->province }}@endif
                        @if($address->postal_code), {{ $address->postal_code }}@endif
                    </p>
                    @if($address->courier_note)
                        <small>Catatan kurir: {{ $address->courier_note }}</small>
                    @endif
                </div>
            </section>

            <section class="checkout-card checkout-review-card">
                <div class="checkout-section-title">
                    <h2>ONGKIR YANG DIPAKAI</h2>
                    <a href="{{ route('checkout.index') }}">GANTI</a>
                </div>

                <div class="checkout-review-shipping">
                    <div>
                        <strong>{{ $shipping['shipping_courier'] ?? 'Belum dipilih' }}</strong>
                        <p>{{ $shipping['shipping_service'] ?? '-' }} · {{ $shipping['shipping_etd'] ?? '-' }}</p>
                        @if(!empty($shipping['shipping_description']))
                            <small>{{ $shipping['shipping_description'] }}</small>
                        @endif
                    </div>
                    <b>{{ $formatPrice($shippingCost) }}</b>
                </div>
            </section>
        </div>

        <aside class="checkout-summary checkout-review-summary">
            <h2>ORDER<br>CONFIRMATION</h2>

            <div class="checkout-summary-line">
                <span>Subtotal</span>
                <strong>{{ $formatPrice($subtotal) }}</strong>
            </div>

            @if($discount > 0)
                <div class="checkout-summary-line discount">
                    <span>Discount</span>
                    <strong>-{{ $formatPrice($discount) }}</strong>
                </div>
            @endif

            <div class="checkout-summary-line">
                <span>Shipping</span>
                <strong>{{ $formatPrice($shippingCost) }}</strong>
            </div>

            <div class="checkout-summary-divider"></div>

            <div class="checkout-summary-line total">
                <span>Total Bayar</span>
                <strong>{{ $formatPrice($grandTotal) }}</strong>
            </div>

            <form method="POST" action="{{ route('checkout.payment') }}" data-midtrans-popup-form>
                @csrf
                <button type="submit" class="checkout-pay-button" data-midtrans-pay-button>BAYAR SEKARANG</button>
            </form>

            <p class="checkout-midtrans-note">Pembayaran akan muncul sebagai popup resmi Midtrans di halaman ini.</p>
            <div class="checkout-payments">
                <span>VISA</span><span>BCA</span><span>BNI</span><span>BRI</span><span>MANDIRI</span><span>OVO</span>
            </div>
        </aside>
    </section>
</main>
@endsection
