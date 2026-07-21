@extends('layouts.app')

@section('title', $product->name . ' — GlowSkin')

@section('content')
@php
    $galleryImages = $galleryImages ?? $product->gallery_images;
    $galleryImages = collect($galleryImages)->filter()->values()->take(20)->toArray();
    if (empty($galleryImages) && filled($product->image)) $galleryImages = [$product->image];

    $productMediaUrl = function ($path) {
        $path = trim((string) $path);
        if ($path === '') return asset('assets/images/no_image.png');
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
        if (str_starts_with($path, 'storage/')) return route('media.storage', ['path' => substr($path, 8)]);
        return asset(ltrim($path, '/'));
    };

    $mainImageRaw = $galleryImages[0] ?? $product->image ?? 'assets/images/no_image.png';
    $mainImage = $productMediaUrl($mainImageRaw);
    $colors = is_array($product->colors) && count($product->colors) ? $product->colors : [];
    $colorImages = is_array($product->color_images ?? null) ? $product->color_images : [];
    $stock = (int) ($product->stock ?? 0);
    $isSoldOut = $stock <= 0;
@endphp

<link rel="stylesheet" href="{{ asset('css/product-detail-review.css') }}">
<link rel="stylesheet" href="{{ asset('css/product-detail-lavender-only.css') }}?v=20260710b">
<link rel="stylesheet" href="{{ asset('css/product-detail-navbar-lavender-only.css') }}?v=20260710b">

<main class="product-detail-page">
    <section class="product-detail-wrap">
        <div class="product-gallery">
            <div class="product-main-image">
                @if($isSoldOut)
                    <span class="product-soldout-ribbon">SOLD OUT</span>
                @endif
                <img src="{{ $mainImage }}" alt="{{ $product->alt ?: $product->name }}" data-product-main-image>
            </div>

            @if(count($galleryImages) > 1)
                <div class="product-thumbs product-thumbs-count-{{ count($galleryImages) }}">
                    @foreach($galleryImages as $i => $image)
                        @php $thumb = $productMediaUrl($image); @endphp
                        <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-product-thumb="{{ $thumb }}">
                            <img src="{{ $thumb }}" alt="{{ $product->name }} thumbnail {{ $i + 1 }}">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="product-info">
            <div class="product-breadcrumb">
                <a href="{{ route('home') }}">HOME</a>
                <span>/</span>
                <a href="{{ url('/'.strtolower($product->category ?: 'makeup')) }}">{{ strtoupper($product->category ?: 'PRODUCT') }}</a>
            </div>

            @if($product->subtitle)
                <div class="product-subtitle">{{ $product->subtitle }}</div>
            @endif

            <h1>{{ $product->name }}</h1>

            <div class="product-rating-summary">
                <div class="stars" aria-label="{{ $averageRating }} dari 5">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="{{ $i <= round($averageRating ?: 0) ? 'filled' : '' }}">★</span>
                    @endfor
                </div>
                <span>{{ $averageRating ?: '0.0' }} / 5</span>
                <em>{{ $reviewCount }} review terverifikasi</em>
            </div>

            <div class="product-price">Rp{{ number_format($product->price, 0, ',', '.') }}</div>

            @if($product->description)
                <p class="product-desc">{{ $product->description }}</p>
            @endif

            @if(count($colors))
                <div class="product-color-block">
                    <span>SHADE / COLOR</span>
                    <div class="product-colors">
                        @foreach($colors as $i => $color)
                            @php
                                $toneKey = strtoupper(trim($color));
                                $toneImageRaw = $colorImages[$toneKey] ?? $colorImages[$color] ?? null;
                                $toneImage = $toneImageRaw ? $productMediaUrl($toneImageRaw) : '';
                            @endphp
                            <button
                                type="button"
                                class="{{ $i === 0 ? 'selected' : '' }}"
                                style="background: {{ $color }}"
                                aria-label="Shade {{ $color }}"
                                data-product-tone-color="{{ $toneKey }}"
                                data-product-tone-image="{{ $toneImage }}">
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="product-action-row">
                <div class="product-qty">
                    <button type="button" data-detail-minus>−</button>
                    <span data-detail-qty>1</span>
                    <button type="button" data-detail-plus>+</button>
                </div>

                @if($isSoldOut)
                    <button type="button" class="product-bag disabled">SOLD OUT</button>
                @else
                    <button type="button" class="product-bag product-detail-add" data-product-detail-add data-product-id="{{ $product->id }}">ADD TO BAG</button>
                @endif

                <button type="button" class="product-love" data-wishlist-toggle data-product-id="{{ $product->id }}" aria-label="Wishlist">♡</button>

                @if(!$isSoldOut)
                    <button type="button" class="product-buy-now" data-product-buy-now data-product-id="{{ $product->id }}" data-checkout-url="{{ url('/checkout') }}">BELI LANGSUNG</button>
                @endif
            </div>

            @if($isSoldOut)
                <div class="product-soldout-note">
                    <strong>Sold Out.</strong>
                    <span>Produk ini sedang habis. Kamu tetap bisa menyimpan ke wishlist.</span>
                </div>
            @endif

            <div class="product-meta">
                <p><strong>Category:</strong> {{ $product->category ?: '-' }}</p>
                <p><strong>Stock:</strong> {{ $stock }}</p>
            </div>
        </div>
    </section>

    <section class="product-tabs">
        <div class="product-tab-head">
            <button type="button" class="active" data-tab-target="desc">Description</button>
            <button type="button" data-tab-target="review">Review</button>
            <button type="button" data-tab-target="tips">Tips & Trick</button>
        </div>

        <div class="product-tab-panel active" data-tab-panel="desc">
            <h2>Product Description</h2>
            <p>{{ $product->description ?: 'Detail produk dapat diubah dari Admin Dashboard tanpa mengubah desain halaman.' }}</p>
        </div>

        <div class="product-tab-panel" data-tab-panel="review">
            <div class="review-section-head">
                <div>
                    <h2>Review Pembeli</h2>
                    <p>Hanya user yang sudah membeli produk ini yang bisa menulis review. User lain tetap bisa membaca review.</p>
                </div>
                <div class="review-score">
                    <strong>{{ $averageRating ?: '0.0' }}</strong>
                    <span>{{ $reviewCount }} review</span>
                </div>
            </div>

            @if(session('success'))
                <div class="review-alert success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="review-alert error">{{ session('error') }}</div>
            @endif

            @if($hasPurchased)
                @if($userReview)
                    <div class="review-edit-toolbar" data-review-edit-toolbar>
                        <div>
                            <strong>Ulasan kamu sudah dipublikasikan.</strong>
                            <span>Kamu tetap dapat memperbarui rating dan komentar kapan saja.</span>
                        </div>
                        <button type="button" class="review-edit-trigger" data-review-edit-trigger>Edit Review</button>
                    </div>
                @endif

                <form method="POST" action="{{ route('products.reviews.store', $product) }}"
                      class="review-form {{ $userReview && !$errors->any() ? 'is-collapsed' : '' }}"
                      data-review-form>
                    @csrf
                    <fieldset class="review-rating-fieldset">
                        <legend>Rating</legend>
                        <div class="review-rating-picker" aria-label="Pilih rating produk">
                            @for($i = 5; $i >= 1; $i--)
                                <input type="radio"
                                       id="review-rating-{{ $i }}"
                                       name="rating"
                                       value="{{ $i }}"
                                       @checked((int) old('rating', $userReview->rating ?? 5) === $i)
                                       required>
                                <label for="review-rating-{{ $i }}" title="{{ $i }} bintang" aria-label="{{ $i }} bintang">★</label>
                            @endfor
                        </div>
                        <span class="review-rating-caption" data-review-rating-caption></span>
                    </fieldset>

                    <label>
                        <span>Ulasan kamu</span>
                        <textarea name="review" rows="4" required placeholder="Tulis pengalaman kamu setelah membeli produk ini...">{{ old('review', $userReview->review ?? '') }}</textarea>
                    </label>

                    <div class="review-form-actions">
                        <button type="submit">{{ $userReview ? 'Update Review' : 'Kirim Review' }}</button>
                        @if($userReview)
                            <button type="button" class="review-cancel-edit" data-review-edit-cancel>Batal</button>
                        @endif
                    </div>
                </form>
            @else
                <div class="review-locked">
                    <strong>Review hanya untuk pembeli.</strong>
                    <span>Kamu bisa melihat review dari user lain, tapi belum bisa menulis review sampai produk ini sudah dibeli.</span>
                </div>
            @endif

            <div class="review-list">
                @forelse($reviews as $review)
                    @php
                        $reviewUser = $review->user;
                        $reviewProfileImage = $reviewUser?->profile_image;
                        $reviewProfileImageUrl = $reviewProfileImage
                            ? (\Illuminate\Support\Str::startsWith($reviewProfileImage, ['http://', 'https://'])
                                ? $reviewProfileImage
                                : asset($reviewProfileImage))
                            : null;
                    @endphp
                    <article class="review-card">
                        <div class="review-avatar">
                            @if($reviewProfileImageUrl)
                                <img src="{{ $reviewProfileImageUrl }}" alt="Foto profil {{ $reviewUser->name ?? 'User GlowSkin' }}">
                            @else
                                <span>{{ strtoupper(substr($reviewUser->name ?? 'U', 0, 1)) }}</span>
                            @endif
                        </div>
                        <div>
                            <div class="review-card-head">
                                <strong>{{ $reviewUser->name ?? 'User GlowSkin' }}</strong>
                                @if($review->is_verified_purchase)
                                    <span class="review-badge verified">Verified Purchase</span>
                                @endif
                                @if($review->is_edited)
                                    <span class="review-badge edited">Edited</span>
                                @endif
                            </div>
                            <div class="review-stars">
                                @for($i = 1; $i <= 5; $i++)
                                    <span class="{{ $i <= $review->rating ? 'filled' : '' }}">★</span>
                                @endfor
                            </div>
                            <p>{{ $review->review }}</p>
                        </div>
                    </article>
                @empty
                    <div class="review-empty">Belum ada review untuk produk ini.</div>
                @endforelse
            </div>
        </div>

        <div class="product-tab-panel" data-tab-panel="tips">
            <h2>Tips & Trick</h2>
            <p>Gunakan produk secara tipis terlebih dahulu, lalu build coverage sesuai kebutuhan agar hasil tetap natural.</p>
        </div>
    </section>

    <section class="related-products-section">
        <h2>RELATED PRODUCTS</h2>

        <div class="related-grid">
            @foreach($related as $item)
                @php
                    $rImg = $item->image ?: 'assets/images/no_image.png';
                    $relatedImage = str_starts_with($rImg, 'http') ? $rImg : asset($rImg);
                @endphp

                <article class="related-card" data-product-id="{{ $item->id }}">
                    <a href="{{ route('products.show', $item->slug) }}">
                        <img src="{{ $relatedImage }}" alt="{{ $item->alt ?: $item->name }}">
                    </a>
                    <a href="{{ route('products.show', $item->slug) }}" class="related-name">{{ $item->name }}</a>
                    <span>Rp{{ number_format($item->price, 0, ',', '.') }}</span>
                    <button type="button" data-cart-add data-product-id="{{ $item->id }}">ADD TO BAG</button>
                </article>
            @endforeach
        </div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const qty = document.querySelector('[data-detail-qty]');
    const minus = document.querySelector('[data-detail-minus]');
    const plus = document.querySelector('[data-detail-plus]');

    if (qty && minus && plus) {
        minus.addEventListener('click', function () {
            const next = Math.max(1, parseInt(qty.textContent || '1') - 1);
            qty.textContent = next;
        });

        plus.addEventListener('click', function () {
            qty.textContent = parseInt(qty.textContent || '1') + 1;
        });
    }



    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const cartAddUrl = document.querySelector('meta[name="cart-add-url"]')?.content || '/cart/add';
    const loginUrl = document.querySelector('meta[name="login-url"]')?.content || '/login';

    function detailToast(message) {
        let box = document.querySelector('[data-product-detail-toast]');
        if (!box) {
            box = document.createElement('div');
            box.setAttribute('data-product-detail-toast', '');
            box.className = 'product-detail-toast';
            document.body.appendChild(box);
        }

        box.textContent = message;
        box.classList.add('show');
        clearTimeout(window.__productDetailToastTimer);
        window.__productDetailToastTimer = setTimeout(() => box.classList.remove('show'), 1700);
    }

    function detailQtyValue() {
        return Math.max(1, parseInt(qty?.textContent || '1', 10) || 1);
    }

    function updateCartBadge(count) {
        document.querySelectorAll('[data-cart-count]').forEach(function (badge) {
            badge.textContent = count || 0;
            badge.hidden = !(Number(count) > 0);
        });
    }

    function setButtonLoading(button, active) {
        if (!button) return;
        button.disabled = active;
        button.classList.toggle('is-loading', active);
    }

    function animateDetailButton(button) {
        if (!button) return;
        button.classList.remove('is-added');
        void button.offsetWidth;
        button.classList.add('is-added');
        setTimeout(() => button.classList.remove('is-added'), 620);
    }

    function animateWishlistButton(button) {
        if (!button) return;
        button.classList.remove('is-love-pop');
        void button.offsetWidth;
        button.classList.add('is-love-pop');
        setTimeout(() => button.classList.remove('is-love-pop'), 620);
    }

    function flyProductImage() {
        const image = document.querySelector('[data-product-main-image]');
        const cartIcon = document.querySelector('[data-cart-count]')?.closest('a, button, .header-icon, .nav-icon') || document.querySelector('[data-cart-count]');
        if (!image || !cartIcon) return;

        const imgRect = image.getBoundingClientRect();
        const targetRect = cartIcon.getBoundingClientRect();
        const clone = image.cloneNode(true);
        clone.className = 'product-fly-image';
        clone.style.left = imgRect.left + 'px';
        clone.style.top = imgRect.top + 'px';
        clone.style.width = Math.min(120, imgRect.width * .28) + 'px';
        clone.style.height = Math.min(120, imgRect.height * .28) + 'px';
        document.body.appendChild(clone);

        requestAnimationFrame(function () {
            clone.style.transform = 'translate(' + (targetRect.left - imgRect.left) + 'px,' + (targetRect.top - imgRect.top) + 'px) scale(.28) rotate(8deg)';
            clone.style.opacity = '0';
        });

        setTimeout(() => clone.remove(), 760);
    }

    async function addDetailToCart(productId, quantity) {
        const response = await fetch(cartAddUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                product_id: productId,
                quantity: quantity || 1,
            }),
        });

        let data = {};
        try { data = await response.json(); } catch (e) {}

        if (response.status === 401) {
            detailToast(data.message || 'Silakan login terlebih dahulu.');
            setTimeout(() => window.location.href = data.login_url || loginUrl, 650);
            return { ok: false, redirected: true };
        }

        if (!response.ok || data.ok === false) {
            detailToast(data.message || 'Produk gagal dimasukkan ke shopping bag.');
            return { ok: false, data };
        }

        updateCartBadge(data.count || 0);
        return { ok: true, data };
    }

    document.querySelectorAll('[data-product-detail-add]').forEach(function (button) {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const productId = button.dataset.productId;
            setButtonLoading(button, true);

            try {
                const result = await addDetailToCart(productId, detailQtyValue());
                if (result.ok) {
                    animateDetailButton(button);
                    flyProductImage();
                    detailToast(result.data.message || 'Produk berhasil ditambahkan ke shopping bag.');
                }
            } finally {
                setButtonLoading(button, false);
            }
        });
    });

    document.querySelectorAll('[data-product-buy-now]').forEach(function (button) {
        button.addEventListener('click', async function (event) {
            event.preventDefault();
            event.stopPropagation();

            const productId = button.dataset.productId;
            const checkoutUrl = button.dataset.checkoutUrl || '/checkout';
            setButtonLoading(button, true);

            try {
                const result = await addDetailToCart(productId, detailQtyValue());
                if (result.ok) {
                    animateDetailButton(button);
                    detailToast('Produk masuk shopping bag. Membuka checkout...');
                    setTimeout(() => window.location.href = checkoutUrl, 520);
                }
            } finally {
                setButtonLoading(button, false);
            }
        });
    });

    document.querySelectorAll('.product-love[data-wishlist-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            animateWishlistButton(button);
            detailToast(button.classList.contains('is-wished') ? 'Wishlist diperbarui.' : 'Wishlist diperbarui.');
        });
    });

    const mainImage = document.querySelector('[data-product-main-image]');
    const defaultMainImage = mainImage ? mainImage.src : '';

    document.querySelectorAll('[data-product-tone-color]').forEach(function (toneButton) {
        toneButton.addEventListener('click', function () {
            document.querySelectorAll('[data-product-tone-color]').forEach(item => item.classList.remove('selected'));
            toneButton.classList.add('selected');

            const toneImage = toneButton.dataset.productToneImage || '';
            if (mainImage && toneImage) {
                mainImage.classList.add('is-tone-changing');
                mainImage.src = toneImage;
                window.setTimeout(() => mainImage.classList.remove('is-tone-changing'), 260);
            } else if (mainImage && defaultMainImage) {
                mainImage.src = defaultMainImage;
            }

            document.querySelectorAll('[data-product-thumb]').forEach(item => item.classList.remove('active'));
        });
    });

    document.querySelectorAll('[data-product-thumb]').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            if (mainImage) mainImage.src = thumb.dataset.productThumb;

            document.querySelectorAll('[data-product-thumb]').forEach(item => item.classList.remove('active'));
            thumb.classList.add('active');
        });
    });

    const reviewForm = document.querySelector('[data-review-form]');
    const reviewToolbar = document.querySelector('[data-review-edit-toolbar]');
    const reviewEditTrigger = document.querySelector('[data-review-edit-trigger]');
    const reviewEditCancel = document.querySelector('[data-review-edit-cancel]');
    const ratingCaption = document.querySelector('[data-review-rating-caption]');
    const ratingLabels = {
        1: 'Kurang memuaskan',
        2: 'Cukup',
        3: 'Bagus',
        4: 'Sangat bagus',
        5: 'Luar biasa'
    };

    function updateRatingCaption() {
        const selected = document.querySelector('input[name="rating"]:checked');
        if (ratingCaption && selected) {
            ratingCaption.textContent = selected.value + ' bintang — ' + ratingLabels[selected.value];
        }
    }

    document.querySelectorAll('input[name="rating"]').forEach(function (input) {
        input.addEventListener('change', updateRatingCaption);
    });
    updateRatingCaption();

    if (reviewEditTrigger && reviewForm) {
        reviewEditTrigger.addEventListener('click', function () {
            reviewForm.classList.remove('is-collapsed');
            if (reviewToolbar) reviewToolbar.hidden = true;
            reviewForm.querySelector('textarea')?.focus();
        });
    }

    if (reviewEditCancel && reviewForm) {
        reviewEditCancel.addEventListener('click', function () {
            reviewForm.classList.add('is-collapsed');
            if (reviewToolbar) reviewToolbar.hidden = false;
        });
    }

    document.querySelectorAll('[data-tab-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = btn.dataset.tabTarget;

            document.querySelectorAll('[data-tab-target]').forEach(item => item.classList.remove('active'));
            document.querySelectorAll('[data-tab-panel]').forEach(item => item.classList.remove('active'));

            btn.classList.add('active');
            document.querySelector('[data-tab-panel="'+target+'"]')?.classList.add('active');
        });
    });
});
</script>
@endsection
