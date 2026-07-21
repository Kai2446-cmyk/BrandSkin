@extends('layouts.app')

@section('title', 'Checkout — GlowSkin')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="{{ asset('css/checkout-glowskin.css') }}">
<link rel="stylesheet" href="{{ asset('css/checkout-lavender-only.css') }}?v=20260710">
@endpush

@section('content')
@php
    $items = collect($items ?? []);
    $addresses = collect($addresses ?? []);
    $subtotal = (int) ($subtotal ?? 0);
    $discount = (int) ($discount ?? 0);
    $total = (int) ($total ?? max(0, $subtotal - $discount));
    $shippingWeight = (int) ($shippingWeight ?? 1000);
    $destinationQuery = trim(collect([$defaultAddress->district ?? null, $defaultAddress->city ?? null, $defaultAddress->province ?? null, $defaultAddress->postal_code ?? null])->filter()->implode(' '));
    $authUser = $authUser ?? session('glowskin_user');
    $voucherGroups = collect($voucherGroups ?? []);

    $formatPrice = fn($price) => 'Rp' . number_format((int) $price, 0, ',', '.');
    $imgUrl = function ($raw) {
        $raw = $raw ?: 'assets/images/no_image.png';
        return \Illuminate\Support\Str::startsWith($raw, ['http://','https://']) ? $raw : asset($raw);
    };
@endphp

<main class="checkout-page">
    <section class="checkout-hero">
        <div class="checkout-shell">
            <div>
                <p>Secure Checkout</p>
                <h1>Proceed To Checkout</h1>
            </div>
            <a href="{{ route('cart.index') }}">Back to Shopping Bag</a>
        </div>
    </section>


    @if($errors->any())
        <div class="checkout-alert checkout-shell">
            {{ $errors->first() }}
        </div>
    @endif

    <section class="checkout-shell checkout-grid">
        <div class="checkout-main">
            <section class="checkout-card checkout-products-card">
                <div class="checkout-section-title">
                    <h2>ORDER ITEMS</h2>
                    <span>{{ $items->sum('quantity') }} item</span>
                </div>

                <div class="checkout-product-list">
                    @foreach($items as $item)
                        @php $product = $item->product; @endphp
                        @if($product)
                            <article class="checkout-product">
                                <img src="{{ $imgUrl($product->image ?? null) }}" alt="{{ $product->name }}">
                                <div>
                                    <h3>{{ $product->name }}</h3>
                                    <p>Qty {{ $item->quantity }}</p>
                                </div>
                                <strong>{{ $formatPrice(($product->price ?? 0) * $item->quantity) }}</strong>
                            </article>
                        @endif
                    @endforeach
                </div>
            </section>

            <section class="checkout-card checkout-address-card">
                <div class="checkout-section-title">
                    <h2>DELIVERY ADDRESS</h2>
                    <button type="button" data-address-modal-open>{{ $defaultAddress ? 'EDIT' : 'ADD ADDRESS' }}</button>
                </div>

                @if($defaultAddress)
                    <div class="checkout-address-preview">
                        <div class="checkout-address-pin">⌖</div>
                        <div>
                            <h3>{{ $defaultAddress->label ?: 'Home' }}</h3>
                            <strong>{{ $defaultAddress->recipient_name }}</strong>
                            <p>{{ $defaultAddress->phone }}</p>
                            <p>
                                {{ $defaultAddress->address_line }}
                                @if($defaultAddress->district), {{ $defaultAddress->district }}@endif
                                @if($defaultAddress->city), {{ $defaultAddress->city }}@endif
                                @if($defaultAddress->province), {{ $defaultAddress->province }}@endif
                                @if($defaultAddress->country), {{ $defaultAddress->country }}@endif
                                @if($defaultAddress->postal_code), {{ $defaultAddress->postal_code }}@endif
                            </p>
                            @if($defaultAddress->map_link)
                                <a href="{{ $defaultAddress->map_link }}" target="_blank" rel="noopener">Open sharelock maps →</a>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="checkout-address-empty">
                        <h3>Alamat pengiriman belum diisi</h3>
                        <p>Tambahkan nama penerima, nomor HP, dan alamat lengkap sebelum melanjutkan payment.</p>
                        <button type="button" data-address-modal-open>ADD NEW ADDRESS</button>
                    </div>
                @endif
            </section>

            <section class="checkout-card checkout-method-card"
                     data-shipping-box
                     data-rates-url="{{ route('checkout.shipping.rates') }}"
                     data-destination-query="{{ $destinationQuery }}"
                     data-district="{{ $defaultAddress->district ?? '' }}"
                     data-city="{{ $defaultAddress->city ?? '' }}"
                     data-province="{{ $defaultAddress->province ?? '' }}"
                     data-postal-code="{{ $defaultAddress->postal_code ?? '' }}"
                     data-address-line="{{ $defaultAddress->address_line ?? '' }}"
                     data-weight="{{ $shippingWeight }}"
                     data-free-shipping="{{ (($promo['type'] ?? null) === 'free_shipping') ? 1 : 0 }}">
                <div class="checkout-section-title">
                    <h2>DELIVERY METHOD</h2>
                    <span>PILIH PENGIRIMAN</span>
                </div>

                <div class="checkout-shipping-loader" data-shipping-loader>
                    <strong>Memuat ongkir real...</strong>
                    <p>Opsi pengiriman akan tampil otomatis sesuai alamat yang dipilih.</p>
                </div>

                <div class="checkout-shipping-options" data-shipping-options></div>

                <div class="checkout-shipping-empty" data-shipping-empty hidden>
                    <strong>Ongkir belum tersedia</strong>
                    <p>Lengkapi alamat pengiriman atau coba beberapa saat lagi.</p>
                </div>
            </section>
        </div>

        <aside class="checkout-summary">
            <h2>ORDER<br>SUMMARY</h2>

            @include('partials.voucher-groups', [
                'voucherGroups' => $voucherGroups,
                'promo' => $promo ?? null,
                'subtotal' => $subtotal,
                'formatPrice' => $formatPrice,
            ])

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
                <strong data-shipping-summary>Rp0</strong>
            </div>

            <div class="checkout-summary-divider"></div>

            <div class="checkout-summary-line total">
                <span>Total</span>
                <strong data-checkout-total data-base-total="{{ $total }}">{{ $formatPrice($total) }}</strong>
            </div>

            <form method="POST" action="{{ route('checkout.confirm') }}" class="checkout-confirm-form" data-checkout-confirm-form>
                @csrf
                <input type="hidden" name="shipping_cost" value="0" data-selected-shipping-cost>
                <input type="hidden" name="shipping_courier" value="" data-selected-shipping-courier>
                <input type="hidden" name="shipping_service" value="" data-selected-shipping-service>
                <input type="hidden" name="shipping_etd" value="" data-selected-shipping-etd>
                <input type="hidden" name="shipping_description" value="" data-selected-shipping-description>
                <button type="submit" class="checkout-pay-button">
                    CONTINUE TO PAYMENT
                </button>
            </form>

            <div class="checkout-payments">
                <span>VISA</span><span>BCA</span><span>BNI</span><span>BRI</span><span>MANDIRI</span><span>OVO</span>
            </div>
        </aside>
    </section>
</main>

<div class="checkout-modal" data-address-modal aria-hidden="true">
    <div class="checkout-modal-backdrop" data-address-modal-close></div>

    <div class="checkout-modal-panel" role="dialog" aria-modal="true">
        <button type="button" class="checkout-modal-close" data-address-modal-close>×</button>
        <h2>DELIVERY ADDRESS</h2>
        <p class="checkout-modal-subtitle">Pilih alamat tersimpan dulu. Kalau ingin ubah/tambah alamat, lanjutkan dari form maps di bawah.</p>

        <form method="POST" action="{{ route('checkout.address.save') }}" class="checkout-address-form">
            @csrf
            <input type="hidden" name="address_id" value="{{ $defaultAddress->id ?? '' }}" data-address-id>
            <input type="hidden" name="latitude" value="{{ $defaultAddress->latitude ?? '' }}" data-address-lat>
            <input type="hidden" name="longitude" value="{{ $defaultAddress->longitude ?? '' }}" data-address-lng>

            <div class="checkout-address-start">
                <div class="checkout-address-start-head">
                    <div>
                        <strong>ALAMAT TERSIMPAN</strong>
                        <p>Pilih alamat yang sudah pernah disimpan. Alamat tiap user otomatis berbeda sesuai akun login.</p>
                    </div>
                    <button type="button" data-new-address>+ Add New</button>
                </div>

                @if($addresses->isNotEmpty())
                    <div class="checkout-saved-addresses">
                        @foreach($addresses as $address)
                            <div class="checkout-saved-address-card {{ $defaultAddress && $defaultAddress->id === $address->id ? 'active' : '' }}">
                                <button type="button"
                                        class="checkout-saved-address"
                                        data-fill-address
                                        data-address='@json($address)'>
                                    <strong>{{ $address->label ?: 'Home' }}</strong>
                                    <span>{{ $address->recipient_name }} · {{ $address->phone }}</span>
                                    <small>{{ \Illuminate\Support\Str::limit($address->address_line, 86) }}</small>
                                </button>
                                <button type="button"
                                        class="checkout-address-delete"
                                        title="Hapus alamat"
                                        aria-label="Hapus alamat"
                                        data-delete-address
                                        data-delete-url="{{ route('checkout.address.delete', $address) }}"
                                        data-delete-label="{{ $address->label ?: 'Home' }}">×</button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="checkout-address-no-save">
                        <strong>Belum ada alamat tersimpan</strong>
                        <span>Tambahkan alamat baru lewat maps dan form di bawah.</span>
                    </div>
                @endif
            </div>

            <div class="checkout-address-edit-block" data-address-edit-block>
                <div class="checkout-address-edit-title">
                    <strong>EDIT / TAMBAH ALAMAT</strong>
                    <span>Pilih titik maps, lalu lengkapi data penerima.</span>
                </div>

                <div class="checkout-map-picker">
                    <div class="checkout-map-head">
                        <div>
                            <strong>PILIH TITIK LOKASI DI MAPS</strong>
                            <p>Klik area maps atau geser pin. Alamat, kecamatan, kota, provinsi, kode pos, dan sharelock akan terisi otomatis.</p>
                        </div>
                        <button type="button" data-use-current-location>Use My Location</button>
                    </div>
                    <div id="checkoutAddressMap" class="checkout-address-map" data-default-lat="{{ $defaultAddress->latitude ?? '-6.93552104' }}" data-default-lng="{{ $defaultAddress->longitude ?? '107.53465931' }}"></div>
                    <div class="checkout-map-helper" data-map-status>Pilih titik alamat pengiriman di maps.</div>
                </div>

            <div class="checkout-form-grid">
                <label>
                    <span>Label Alamat</span>
                    <input name="label" value="{{ old('label', $defaultAddress->label ?? 'Home') }}" placeholder="Home / Kantor">
                </label>

                <label>
                    <span>Nama Penerima</span>
                    <input name="recipient_name" value="{{ old('recipient_name', $defaultAddress->recipient_name ?? ($authUser['name'] ?? '')) }}" required>
                </label>

                <label>
                    <span>No. HP</span>
                    <input name="phone" value="{{ old('phone', $defaultAddress->phone ?? '') }}" required>
                </label>

                <label>
                    <span>Kode Pos</span>
                    <input name="postal_code" value="{{ old('postal_code', $defaultAddress->postal_code ?? '') }}">
                </label>
            </div>

            <label>
                <span>Alamat Lengkap</span>
                <textarea name="address_line" rows="3" required>{{ old('address_line', $defaultAddress->address_line ?? '') }}</textarea>
            </label>

            <div class="checkout-form-grid three">
                <label>
                    <span>Kecamatan</span>
                    <input name="district" value="{{ old('district', $defaultAddress->district ?? '') }}">
                </label>
                <label>
                    <span>Kota/Kabupaten</span>
                    <input name="city" value="{{ old('city', $defaultAddress->city ?? '') }}">
                </label>
                <label>
                    <span>Provinsi</span>
                    <input name="province" value="{{ old('province', $defaultAddress->province ?? '') }}">
                </label>
            </div>

            <input type="hidden" name="map_link" value="{{ old('map_link', $defaultAddress->map_link ?? '') }}">

            <label>
                <span>Catatan Kurir</span>
                <input name="courier_note" value="{{ old('courier_note', $defaultAddress->courier_note ?? '') }}" placeholder="Contoh: pagar hijau, titip satpam, dll">
            </label>

            <input type="hidden" name="country" value="Indonesia">

            </div>

            <div class="checkout-modal-actions">
                <button type="button" data-address-modal-close>Cancel</button>
                <button type="submit">Use This Location</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/checkout-glowskin.js') }}"></script>
@endpush
