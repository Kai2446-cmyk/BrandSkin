@extends('layouts.app')

@section('title', 'My Voucher — GlowSkin')

@section('content')
@php
    $formatPrice = fn($price) => 'Rp' . number_format((int) $price, 0, ',', '.');
    $voucherValue = function ($voucher) use ($formatPrice) {
        return match ($voucher->discount_type ?? 'percent') {
            'free_shipping' => 'Gratis Ongkir',
            'fixed' => $formatPrice($voucher->discount_value) . ' OFF',
            default => (int) $voucher->discount_value . '% OFF',
        };
    };
@endphp

<link rel="stylesheet" href="{{ asset('css/profile-page.css') }}?v=20260710-profile-base">
<link rel="stylesheet" href="{{ asset('css/profile-pages-lavender-navbar-order-fix.css') }}?v=20260710">
<link rel="stylesheet" href="{{ asset('css/profile-vouchers.css') }}?v=20260713">

<main class="profile-page profile-voucher-page">
    <section class="profile-shell">
        <aside class="profile-sidebar">
            <a href="{{ route('profile.index') }}">My Profile <span>♥</span></a>
            <a href="{{ route('profile.orders') }}">My Order</a>
            <a href="{{ route('wishlist.index') }}">My Wishlist</a>
            <a href="{{ route('profile.vouchers') }}" class="active">My Voucher</a>
            <a href="{{ route('profile.skin-diary') }}">Skin Diary</a>
        </aside>

        <section class="profile-card profile-voucher-card">
            <header class="voucher-page-head">
                <div>
                    <span>Voucher Center</span>
                    <h1>My Voucher</h1>
                    <p>Semua voucher GlowSkin dikelompokkan berdasarkan jenisnya. Voucher dengan minimum belanja tetap terlihat agar kamu tahu syarat pemakaiannya.</p>
                </div>
                <div class="voucher-head-icon" aria-hidden="true">%</div>
            </header>

            <div class="voucher-overview">
                <article>
                    <span>Subtotal Keranjang</span>
                    <strong>{{ $formatPrice($cartSubtotal) }}</strong>
                    <small>Dasar pengecekan minimum belanja</small>
                </article>
                <article>
                    <span>Bisa Dipakai</span>
                    <strong>{{ $usableCount }}</strong>
                    <small>Voucher siap digunakan sekarang</small>
                </article>
                <article>
                    <span>Belum Memenuhi Minimum</span>
                    <strong>{{ $lockedCount }}</strong>
                    <small>Tetap muncul sampai syarat terpenuhi</small>
                </article>
            </div>

            @forelse($voucherGroups as $type => $group)
                <section class="voucher-type-group">
                    <div class="voucher-group-heading">
                        <div class="voucher-group-icon">{{ $group['icon'] }}</div>
                        <div>
                            <h2>{{ $group['title'] }}</h2>
                            <p>{{ $group['subtitle'] }}</p>
                        </div>
                        <span>{{ $group['items']->count() }} voucher</span>
                    </div>

                    <div class="voucher-grid">
                        @foreach($group['items'] as $voucher)
                            @php
                                $eligible = (bool) $voucher->profile_is_eligible;
                                $available = (bool) $voucher->profile_schedule_available;
                            @endphp
                            <article class="voucher-ticket {{ $eligible ? 'is-usable' : 'is-locked' }} {{ !$available ? 'is-unavailable' : '' }}">
                                <div class="voucher-ticket-main">
                                    <div class="voucher-ticket-topline">
                                        <span>GlowSkin Voucher</span>
                                        @if($eligible)
                                            <em class="voucher-status usable">Bisa Dipakai</em>
                                        @elseif($available)
                                            <em class="voucher-status locked">Belum Memenuhi Minimum</em>
                                        @else
                                            <em class="voucher-status unavailable">Tidak Tersedia</em>
                                        @endif
                                    </div>

                                    <h3>{{ $voucherValue($voucher) }}</h3>
                                    <p>{{ $voucher->description ?: 'Voucher spesial untuk belanja produk GlowSkin.' }}</p>

                                    <div class="voucher-requirements">
                                        <span>Min. belanja <strong>{{ $formatPrice($voucher->minimum_purchase ?? 0) }}</strong></span>
                                        <span>
                                            @if($voucher->expires_at)
                                                Berlaku sampai <strong>{{ $voucher->expires_at->format('d M Y') }}</strong>
                                            @else
                                                <strong>Tanpa batas waktu</strong>
                                            @endif
                                        </span>
                                    </div>

                                    @if($available && !$eligible)
                                        <div class="voucher-progress-note">
                                            Tambah belanja <strong>{{ $formatPrice($voucher->profile_missing_amount) }}</strong> lagi agar voucher bisa dipakai.
                                        </div>
                                    @elseif(!$available)
                                        <div class="voucher-progress-note unavailable-note">
                                            Voucher sedang nonaktif, belum dimulai, sudah berakhir, atau kuotanya telah habis.
                                        </div>
                                    @endif
                                </div>

                                <div class="voucher-code-panel">
                                    <span>Kode Voucher</span>
                                    <strong>{{ $voucher->code }}</strong>
                                    <button type="button" data-copy-voucher="{{ $voucher->code }}" {{ !$available ? 'disabled' : '' }}>
                                        {{ $available ? 'Salin Kode' : 'Tidak Tersedia' }}
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="voucher-empty-state">
                    <div>%</div>
                    <h2>Belum ada voucher</h2>
                    <p>Voucher yang dibuat dan diaktifkan oleh admin akan otomatis muncul di halaman ini.</p>
                </div>
            @endforelse
        </section>
    </section>
</main>

<script>
document.addEventListener('click', function (event) {
    const button = event.target.closest('[data-copy-voucher]');
    if (!button || button.disabled) return;

    const code = button.getAttribute('data-copy-voucher');
    navigator.clipboard?.writeText(code).then(function () {
        const oldText = button.textContent;
        button.textContent = 'Tersalin';
        button.classList.add('copied');
        setTimeout(function () {
            button.textContent = oldText;
            button.classList.remove('copied');
        }, 1300);
    }).catch(function () {
        button.textContent = code;
    });
});
</script>
@endsection
