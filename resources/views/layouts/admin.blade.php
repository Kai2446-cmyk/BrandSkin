@php
  $authUser = session('glowskin_user');
  $settings = $siteSettings ?? collect();
  $webName = trim((string) ($settings['web_name'] ?? ''));
  $tagline = trim((string) ($settings['brand_tagline'] ?? ''));
  $logo = $settings['logo'] ?? 'assets/images/app_logo.png';
@endphp
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Admin Dashboard — '.$webName)</title>
  @php
    $adminFavicon = \Illuminate\Support\Str::startsWith($logo, ['http://', 'https://']) ? $logo : asset($logo);
  @endphp
  <link rel="icon" href="{{ $adminFavicon }}?v={{ md5($logo) }}">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="{{ asset('css/glowskin.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-promo.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-settings-fix.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-dashboard-report.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-lavender-accent-only.css') }}">
  <link rel="stylesheet" href="{{ asset('css/admin-white-lavender-theme-only.css') }}?v=20260710-final">
</head>
<body class="admin-body">
  <aside class="admin-sidebar">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
      <img src="{{ asset($logo) }}" alt="{{ $webName }} Logo">
      <div>
        <strong>{{ $webName }}</strong>
        <span>{{ $tagline }}</span>
      </div>
    </a>

    <nav class="admin-nav">
      <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">🏠 Dashboard</a>
      <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') || request()->routeIs('admin.hero-slides.*') ? 'active' : '' }}">⚙️ Website Settings</a>
      <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">🧴 Pengaturan Produk</a>
      <a href="{{ route('admin.articles.index') }}" class="{{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">📝 Pengaturan Artikel</a>
      <a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">⭐ Review Produk</a>
      <a href="{{ route('admin.promos.index') }}" class="{{ request()->routeIs('admin.promos.*') ? 'active' : '' }}">🎟️ Voucher</a>
      <a href="{{ route('home') }}">👁️ Lihat Website</a>
    </nav>

    <div class="admin-sidebar-user">
      <div class="profile-avatar">{{ strtoupper(substr($authUser['name'] ?? 'A', 0, 1)) }}</div>
      <div class="min-w-0">
        <p>{{ $authUser['name'] ?? 'Admin' }}</p>
        <small>{{ $authUser['email'] ?? 'admin@gmail.com' }}</small>
      </div>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
      @csrf
      <button type="submit" class="admin-logout">Logout</button>
    </form>
  </aside>

  <main class="admin-main">
    @if(session('success'))
      <div class="admin-alert">{{ session('success') }}</div>
    @endif
    @yield('content')
  </main>

  <script src="{{ asset('js/glowskin.js') }}"></script>
</body>
</html>
