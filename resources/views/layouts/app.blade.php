@php
  $layoutSettings = $siteSettings ?? $settings ?? collect();
  $layoutLogo = trim((string) ($layoutSettings['logo'] ?? ''));
  $dynamicFavicon = $layoutLogo !== ''
      ? (\Illuminate\Support\Str::startsWith($layoutLogo, ['http://', 'https://']) ? $layoutLogo : asset($layoutLogo))
      : asset('favicon.ico');
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'GlowSkin — Premium Skincare & Makeup Collection')</title>
  <meta name="description" content="GlowSkin premium cosmetics and skincare. Shop new arrivals, bestsellers, and exclusive sale products.">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="wishlist-toggle-url" content="{{ route('wishlist.toggle') }}">
  <meta name="wishlist-ids-url" content="{{ route('wishlist.ids') }}">
  <meta name="cart-add-url" content="{{ route('cart.add') }}">
  <meta name="cart-count-url" content="{{ route('cart.count') }}">
  <meta name="login-url" content="{{ route('login') }}">
  <link rel="icon" href="{{ $dynamicFavicon }}?v={{ md5($layoutLogo ?: 'default-favicon') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="{{ asset('css/glowskin.css') }}">
  <link rel="stylesheet" href="{{ asset('css/landing-navbar-restore.css') }}">
  <link rel="stylesheet" href="{{ asset('css/catalogue.css') }}">
  <link rel="stylesheet" href="{{ asset('css/wishlist.css') }}">
  <link rel="stylesheet" href="{{ asset('css/cart.css') }}">
  <link rel="stylesheet" href="{{ asset('css/page-loader-filter-cart.css') }}">
  <link rel="stylesheet" href="{{ asset('css/landing-sale-position-fix.css') }}">
  <link rel="stylesheet" href="{{ asset('css/glowskin-purple-white-theme.css') }}">
  <link rel="stylesheet" href="{{ asset('css/glowskin-lavender-complete-override.css') }}?v=20260709-restore">
  <link rel="stylesheet" href="{{ asset('css/article-skin-navbar-lavender-only.css') }}">
  <link rel="stylesheet" href="{{ asset('css/register-button-lavender-only.css') }}?v=20260710">
  <link rel="stylesheet" href="{{ asset('css/logo-image-only-size-fix.css') }}?v=20260710-1">
  <link rel="stylesheet" href="{{ asset('css/product-card-rounded-consistency.css') }}?v=20260715">
  <link rel="stylesheet" href="{{ asset('css/product-add-to-bag-visibility.css') }}?v=20260715">
  @stack('styles')
</head>
<body>
  <div class="glow-page-loader" data-page-loader aria-hidden="true">
    <div class="glow-loader-card">
      <div class="glow-loader-mark">
        <span></span><span></span><span></span><span></span>
      </div>
      <strong>GlowSkin</strong>
      <small>Loading beauty page...</small>
    </div>
  </div>

  @include('partials.header')
  @yield('content')
  @include('partials.footer')
  <script src="{{ asset('js/glowskin.js') }}"></script>
  <script src="{{ asset('js/wishlist.js') }}"></script>
  <script src="{{ asset('js/cart.js') }}"></script>
  <script src="{{ asset('js/product-add-to-bag-feedback.js') }}?v=20260715"></script>
  <script src="{{ asset('js/page-loader-filter-cart.js') }}"></script>
  <script src="{{ asset('js/profile-dropdown-link-fix.js') }}"></script>
  <script src="{{ asset('js/landing-transparent-navbar.js') }}"></script>
  @stack('scripts')
</body>
</html>
