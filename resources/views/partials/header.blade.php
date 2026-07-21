@php
  $settings = $siteSettings ?? $settings ?? collect();
  $webName = trim((string) ($settings['web_name'] ?? ''));
  $tagline = trim((string) ($settings['brand_tagline'] ?? ''));
  $hasBrandText = $webName !== '' || $tagline !== '';
  $logo = $settings['logo'] ?? 'assets/images/app_logo.png';
  $shopNavSkincareImage = $settings['shop_nav_skincare_image'] ?? 'assets/article-detail/fallback-product.jpg';
  $shopNavSaleImage = $settings['shop_nav_sale_image'] ?? 'assets/article-detail/fallback-hero.jpg';
  $mediaUrl = function ($path, $fallback = 'assets/images/no_image.png') {
      $path = $path ?: $fallback;
      return \Illuminate\Support\Str::startsWith($path, ['http://','https://']) ? $path : asset($path);
  };
  $authUser = session('glowskin_user');
  $isAdmin = ($authUser['role'] ?? null) === 'admin';
  $authProfileImage = $authUser['profile_image'] ?? null;

  if ($authUser && empty($authProfileImage)) {
      try {
          $authProfileImage = \Illuminate\Support\Facades\DB::table('users')->where('id', $authUser['id'])->value('profile_image');
      } catch (\Throwable $e) {
          $authProfileImage = null;
      }
  }

  $authProfileImageUrl = $authProfileImage ? (\Illuminate\Support\Str::startsWith($authProfileImage, ['http://','https://']) ? $authProfileImage : asset($authProfileImage)) : null;

  $wishlistCount = 0;
  $cartCount = 0;

  if ($authUser) {
      try {
          $wishlistCount = \App\Models\Wishlist::where('user_id', $authUser['id'])->count();
      } catch (\Throwable $e) {
          $wishlistCount = 0;
      }

      try {
          $cartCount = \App\Models\CartItem::where('user_id', $authUser['id'])->sum('quantity');
      } catch (\Throwable $e) {
          $cartCount = 0;
      }
  }
@endphp

<header class="site-header {{ request()->routeIs('home') ? '' : 'site-header--inner-page' }}">
  <div class="site-header-inner">
    <a href="{{ route('home') }}" class="site-logo {{ $hasBrandText ? '' : 'site-logo--image-only' }}">
      <img src="{{ \Illuminate\Support\Str::startsWith($logo, ['http://','https://']) ? $logo : asset($logo) }}" alt="{{ $webName !== '' ? $webName.' Logo' : 'Website Logo' }}">
      @if($hasBrandText)
        <div class="site-logo-text">
          @if($webName !== '')<strong>{{ $webName }}</strong>@endif
          @if($tagline !== '')<span>{{ $tagline }}</span>@endif
        </div>
      @endif
    </a>

    <nav class="site-nav">
      <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">Home</a>

      <div class="site-nav-shop {{ request()->routeIs('catalogue.makeup') || request()->routeIs('catalogue.skincare') || request()->routeIs('catalogue.sale') ? 'active' : '' }}">
        <button type="button" class="site-nav-shop-trigger" aria-haspopup="true" aria-expanded="false">
          Shop
          <span aria-hidden="true">⌄</span>
        </button>

        <div class="site-nav-shop-dropdown" aria-label="Shop menu">
          <div class="shop-mega-panel">
            <div class="shop-mega-column">
              <span class="shop-mega-eyebrow">Category</span>
              <a href="{{ route('catalogue.makeup') }}" class="shop-mega-link {{ request()->routeIs('catalogue.makeup') ? 'active' : '' }}">
                <strong>Makeup</strong>
                <small>Face, lip, brow & complexion</small>
              </a>
              <a href="{{ route('catalogue.skincare') }}" class="shop-mega-link {{ request()->routeIs('catalogue.skincare') ? 'active' : '' }}">
                <strong>Skincare</strong>
                <small>Daily glow skin treatment</small>
              </a>
              <a href="{{ route('catalogue.sale') }}" class="shop-mega-link {{ request()->routeIs('catalogue.sale') ? 'active' : '' }}">
                <strong>Sale</strong>
                <small>Special beauty deals</small>
              </a>
            </div>

            <div class="shop-mega-column">
              <span class="shop-mega-eyebrow">Shop by Look</span>
              <a href="{{ route('catalogue.makeup') }}" class="shop-mega-mini">Complexion Glow</a>
              <a href="{{ route('catalogue.skincare') }}" class="shop-mega-mini">Daily Skin Routine</a>
              <a href="{{ route('catalogue.sale') }}" class="shop-mega-mini">Best Deals</a>
            </div>

            <a href="{{ route('catalogue.skincare') }}" class="shop-mega-card shop-mega-card-main">
              <img src="{{ $mediaUrl($shopNavSkincareImage, 'assets/article-detail/fallback-product.jpg') }}" alt="Skincare routine">
              <span>Skin Treatment</span>
              <strong>Find your daily glow routine</strong>
              <em>Explore Skincare →</em>
            </a>

            <a href="{{ route('catalogue.sale') }}" class="shop-mega-card shop-mega-card-sale">
              <img src="{{ $mediaUrl($shopNavSaleImage, 'assets/article-detail/fallback-hero.jpg') }}" alt="Special beauty deals">
              <span>Special Offer</span>
              <strong>Beauty deals with green tone</strong>
              <em>Shop Sale →</em>
            </a>
          </div>
        </div>
      </div>

      <a href="{{ url('/skin-analyzer') }}" class="{{ request()->is('skin-analyzer') ? 'active' : '' }}">Skin Analyzer</a>
      <a href="{{ route('articles.index') }}" class="{{ request()->routeIs('articles.*') ? 'active' : '' }}">Articles</a>
    </nav>

    <div class="site-actions">
      <a href="{{ route('wishlist.index') }}" class="circle-action" title="Wishlist">
        <span>♡</span>
        <em data-wishlist-count {{ $wishlistCount < 1 ? 'hidden' : '' }}>{{ $wishlistCount }}</em>
      </a>

      <a href="{{ route('cart.index') }}" class="circle-action" title="Shopping Bag">
        <span>🛍️</span>
        <em data-cart-count {{ $cartCount < 1 ? 'hidden' : '' }}>{{ $cartCount }}</em>
      </a>

      <button type="button" class="circle-action search-circle-action" data-search-open title="Search" aria-label="Search">
        <span class="search-icon-wrap" aria-hidden="true">
          <svg class="search-icon-svg" viewBox="0 0 24 24" focusable="false">
            <circle cx="10.7" cy="10.7" r="5.4"></circle>
            <path d="M15.1 15.1L20 20"></path>
          </svg>
        </span>
      </button>

      @if($authUser)
        <div class="profile-menu" data-profile-menu>
          <button type="button" class="profile-trigger" data-profile-trigger>
            <span class="profile-avatar {{ $authProfileImageUrl ? 'has-image' : '' }}">
              @if($authProfileImageUrl)
                <img src="{{ $authProfileImageUrl }}" alt="{{ $authUser['name'] ?? 'Profile' }} profile">
              @else
                {{ strtoupper(substr($authUser['name'] ?? 'U', 0, 1)) }}
              @endif
            </span>
            <strong>{{ \Illuminate\Support\Str::limit($authUser['name'] ?? 'Profile', 13) }}</strong>
            <i>⌄</i>
          </button>

          <div class="profile-dropdown" data-profile-dropdown>
            <a href="{{ route('profile.index') }}">My Profile</a>
            <a href="{{ route('wishlist.index') }}">My Wishlist</a>
            <a href="{{ route('cart.index') }}">Shopping Bag</a>

            @if($isAdmin)
              <a href="{{ route('admin.dashboard') }}">Admin Dashboard</a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit">Logout</button>
            </form>
          </div>
        </div>
      @else
        <div class="auth-actions">
          <a href="{{ route('login') }}">Login</a>
          <a href="{{ route('register') }}">Register</a>
        </div>
      @endif
    </div>
  </div>
</header>
