@php
    $voucherGroups = collect($voucherGroups ?? []);
    $promo = $promo ?? null;
    $currentCode = strtoupper((string) ($promo['code'] ?? ''));
    $subtotal = (int) ($subtotal ?? 0);
    $formatPrice = $formatPrice ?? fn($price) => 'Rp' . number_format((int) $price, 0, ',', '.');
@endphp

<section class="glowskin-voucher-box">
    <div class="glowskin-voucher-head">
        <h2>VOUCHER</h2>
        <span>PILIH VOUCHER</span>
    </div>

    @if($promo)
        <div class="glowskin-voucher-active-wrap">
            <div class="glowskin-voucher-active-label">VOUCHER YANG DIPAKAI</div>
            <div class="glowskin-voucher-active">
                <div>
                    <strong>{{ $promo['code'] ?? '' }}</strong>
                <p>
                    @if(($promo['type'] ?? '') === 'free_shipping')
                        Gratis ongkir sedang dipakai.
                    @elseif(($promo['type'] ?? '') === 'percent')
                        Diskon {{ (int) ($promo['discount'] ?? 0) }}% sedang dipakai.
                    @else
                        Potongan {{ $formatPrice((int) ($promo['discount'] ?? 0)) }} sedang dipakai.
                    @endif
                </p>
                </div>
                <form method="POST" action="{{ route('cart.promo.remove.post') }}" class="glowskin-voucher-remove-form" data-checkout-remove-voucher data-checkout-url="{{ request()->fullUrl() }}">
                    @csrf
                    <button type="submit">REMOVE</button>
                </form>
            </div>
        </div>
        @if($voucherGroups->isNotEmpty())
            <div class="glowskin-voucher-separator"><span>VOUCHER TERSEDIA</span></div>
        @endif
    @endif

    @if($voucherGroups->isEmpty())
        <div class="glowskin-voucher-empty">
            <strong>Tidak ada voucher aktif</strong>
        </div>
    @else
        <div class="glowskin-voucher-groups" data-glowskin-voucher-groups>
            @foreach($voucherGroups as $type => $group)
                @php
                    $items = collect($group['items'] ?? [])->map(function ($voucher) use ($subtotal) {
                        $voucher->checkout_is_eligible = (bool) ($voucher->checkout_is_eligible ?? $voucher->isValidFor($subtotal));
                        $voucher->checkout_missing_amount = (int) ($voucher->checkout_missing_amount ?? max(0, (int) ($voucher->minimum_purchase ?? 0) - $subtotal));
                        return $voucher;
                    });

                    $availableItems = $items->filter(fn($voucher) => (bool) $voucher->checkout_is_eligible)->values();
                    $lockedItems = $items->reject(fn($voucher) => (bool) $voucher->checkout_is_eligible)->values();
                    $sortedItems = $availableItems->concat($lockedItems);
                    $openByDefault = false;
                @endphp

                <div class="glowskin-voucher-group {{ $type === 'free_shipping' ? 'is-free-shipping' : '' }} {{ $openByDefault ? 'is-open' : '' }}" data-glowskin-voucher-group>
                    <button type="button" class="glowskin-voucher-group-title" data-glowskin-voucher-toggle aria-expanded="{{ $openByDefault ? 'true' : 'false' }}">
                        <div>
                            <strong>{{ $group['title'] ?? 'Voucher' }}</strong>
                            <p>{{ $group['description'] ?? 'Pilih voucher yang tersedia.' }}</p>
                        </div>
                        <span>{{ $items->count() }} Voucher</span>
                        <b aria-hidden="true">⌄</b>
                    </button>

                    <div class="glowskin-voucher-list" data-glowskin-voucher-list>
                        @if($availableItems->isNotEmpty())
                            <div class="glowskin-voucher-subtitle">Bisa dipakai</div>
                        @endif

                        @foreach($sortedItems as $voucher)
                            @php
                                $code = strtoupper((string) $voucher->code);
                                $voucherType = $voucher->discount_type ?: 'percent';
                                $isCurrent = $currentCode === $code;
                                $isEligible = (bool) ($voucher->checkout_is_eligible ?? false);
                                $missing = (int) ($voucher->checkout_missing_amount ?? 0);
                                $min = (int) ($voucher->minimum_purchase ?? 0);
                                $label = $voucherType === 'free_shipping'
                                    ? 'Gratis ongkir'
                                    : ($voucherType === 'percent'
                                        ? ((int) $voucher->discount_value . '% OFF')
                                        : ($formatPrice((int) $voucher->discount_value) . ' OFF'));
                                $isFirstLocked = !$isEligible && $lockedItems->first()?->id === $voucher->id;
                            @endphp

                            @if($isFirstLocked)
                                <div class="glowskin-voucher-subtitle is-locked">Belum memenuhi minimum</div>
                            @endif

                            <article class="glowskin-voucher-card {{ !$isEligible ? 'is-disabled' : '' }} {{ $isCurrent ? 'is-current' : '' }}">
                                <div class="glowskin-voucher-info">
                                    <strong>{{ $voucher->code }}</strong>
                                    <p>{{ $voucher->description ?: $label }}</p>
                                    <small>{{ $label }} · Min {{ $formatPrice($min) }}</small>
                                    @if(!$isEligible && $missing > 0)
                                        <em>Anda masih kurang {{ $formatPrice($missing) }} untuk memakai voucher ini.</em>
                                    @elseif(!$isEligible)
                                        <em>Voucher belum bisa dipakai untuk pesanan ini.</em>
                                    @endif
                                </div>

                                @if($isCurrent)
                                    <span class="glowskin-voucher-used">Dipakai</span>
                                @elseif($isEligible)
                                    <form method="POST" action="{{ route('cart.promo.apply') }}">
                                        @csrf
                                        <input type="hidden" name="code" value="{{ $voucher->code }}">
                                        <button type="submit">PAKAI</button>
                                    </form>
                                @else
                                    <button type="button" disabled>Tidak Bisa</button>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
