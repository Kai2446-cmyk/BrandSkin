@extends('layouts.admin')
@section('title','Admin Dashboard — GlowSkin')
@section('content')
@php
  $img = fn($path) => \Illuminate\Support\Str::startsWith($path ?? '', ['http://','https://']) ? $path : asset($path ?: 'assets/images/no_image.png');
  $report = $dashboardReport ?? [];
  $rupiah = fn($value) => 'Rp'.number_format((int) $value, 0, ',', '.');
  $chart = collect($report['chart'] ?? []);
  $maxChartValue = max(1, (int) ($report['max_chart_value'] ?? 1));
  $chartWidth = 600;
  $chartHeight = 260;
  $points = $chart->values()->map(function ($item, $index) use ($chart, $maxChartValue, $chartWidth, $chartHeight) {
      $count = max(1, $chart->count() - 1);
      $x = 34 + (($chartWidth - 68) / $count) * $index;
      $y = 28 + (($chartHeight - 70) * (1 - ((int) ($item['value'] ?? 0) / $maxChartValue)));
      return ['x' => round($x, 1), 'y' => round($y, 1), 'item' => $item];
  });
  $polyline = $points->map(fn($point) => $point['x'].','.$point['y'])->implode(' ');
  $areaLine = $points->isNotEmpty() ? '34,'.($chartHeight - 28).' '.$polyline.' '.($chartWidth - 34).','.($chartHeight - 28) : '';
@endphp
<section class="admin-page-head report-head">
  <div>
    <p>CMS REPORT</p>
    <h1>Admin Dashboard</h1>
    <span>Laporan ringkas website: payment, pemasaran, produk, artikel, user, promo, dan update landing page.</span>
  </div>
  <a href="{{ route('home') }}" class="green-gradient-btn admin-head-btn">View Site</a>
</section>

<div class="admin-report-stats">
  <div class="admin-report-stat">
    <span>Total Revenue</span>
    <strong>{{ $rupiah($report['total_revenue'] ?? 0) }}</strong>
    <small>Real dari transaksi payment paid/success saja</small>
  </div>
  <div class="admin-report-stat">
    <span>Payment Orders</span>
    <strong>{{ number_format((int) ($report['total_orders'] ?? 0), 0, ',', '.') }}</strong>
    <small>{{ (int) ($report['paid_orders'] ?? 0) }} paid • {{ (int) ($report['pending_orders'] ?? 0) }} pending</small>
  </div>
  <div class="admin-report-stat">
    <span>Total Users</span>
    <strong>{{ number_format((int) ($report['users_count'] ?? 0), 0, ',', '.') }}</strong>
    <small>Wishlist {{ (int) ($report['wishlist_count'] ?? 0) }} • Cart item {{ (int) ($report['cart_items_count'] ?? 0) }}</small>
  </div>
  <div class="admin-report-stat">
    <span>Content Update</span>
    <strong>{{ number_format(count($products) + count($articles), 0, ',', '.') }}</strong>
    <small>{{ count($products) }} produk • {{ count($articles) }} artikel</small>
  </div>
</div>

<div class="admin-report-grid mt-6">
  <section class="admin-panel admin-report-panel admin-report-chart-panel">
    <div class="admin-panel-head">
      <div>
        <h2>Grafik Pemasaran</h2>
        <p>Grafik ini membaca data payment real saja. Kalau belum ada transaksi payment paid/success, nilainya tetap 0 dulu.</p>
      </div>
      <div class="report-pill">REAL PAYMENT DATA</div>
    </div>

    <div class="marketing-visual-chart" aria-label="Grafik pemasaran real payment">
      <div class="marketing-visual-top">
        <div>
          <span>Total Payment Revenue</span>
          <strong>{{ $rupiah($report['total_revenue'] ?? 0) }}</strong>
        </div>
        <small>{{ (int) ($report['paid_orders'] ?? 0) }} paid order</small>
      </div>

      <svg class="marketing-line-svg" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="Line chart payment revenue">
        <defs>
          <linearGradient id="paymentChartArea" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="#65A240" stop-opacity="0.28" />
            <stop offset="100%" stop-color="#65A240" stop-opacity="0" />
          </linearGradient>
        </defs>
        <line x1="34" y1="42" x2="566" y2="42" class="chart-grid-line" />
        <line x1="34" y1="104" x2="566" y2="104" class="chart-grid-line" />
        <line x1="34" y1="166" x2="566" y2="166" class="chart-grid-line" />
        <line x1="34" y1="232" x2="566" y2="232" class="chart-axis-line" />

        @if($points->isNotEmpty())
          <polygon points="{{ $areaLine }}" class="chart-area" />
          <polyline points="{{ $polyline }}" class="chart-line" />
          @foreach($points as $point)
            <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="6" class="chart-dot" />
            <text x="{{ $point['x'] }}" y="{{ $chartHeight - 8 }}" text-anchor="middle" class="chart-label">{{ $point['item']['label'] ?? '-' }}</text>
            <text x="{{ $point['x'] }}" y="{{ max(18, $point['y'] - 14) }}" text-anchor="middle" class="chart-value">{{ $rupiah($point['item']['value'] ?? 0) }}</text>
          @endforeach
        @endif
      </svg>
    </div>
  </section>

  <section class="admin-panel admin-report-panel">
    <div class="admin-panel-head"><div><h2>Status Website</h2><p>Update fitur dan kondisi data website saat ini.</p></div></div>
    <div class="website-update-list">
      <div><span>✓</span><p><strong>Website Settings</strong><small>Sudah dipindahkan ke menu kiri admin.</small></p></div>
      <div><span>✓</span><p><strong>Hero Section Landing Page</strong><small>Bisa tambah, edit, aktif/nonaktif, dan hapus foto hero.</small></p></div>
      <div><span>✓</span><p><strong>Social Media Footer</strong><small>Link sosmed kosong otomatis tidak muncul di website.</small></p></div>
      <div><span>✓</span><p><strong>Promo Aktif</strong><small>{{ (int) ($report['active_promos_count'] ?? 0) }} promo sedang aktif.</small></p></div>
      <div><span>✓</span><p><strong>Hero Aktif</strong><small>{{ (int) ($report['active_slides_count'] ?? 0) }} slide tampil di landing page.</small></p></div>
    </div>
  </section>
</div>

<div class="admin-report-grid mt-6">
  <section class="admin-panel admin-report-panel">
    <div class="admin-panel-head"><div><h2>Payment Summary</h2><p>Ringkasan status transaksi untuk nanti saat payment sudah aktif penuh.</p></div></div>
    <div class="payment-summary-grid">
      <div><span>Paid</span><strong>{{ (int) ($report['paid_orders'] ?? 0) }}</strong></div>
      <div><span>Pending</span><strong>{{ (int) ($report['pending_orders'] ?? 0) }}</strong></div>
      <div><span>Review</span><strong>{{ (int) ($report['reviews_count'] ?? 0) }}</strong></div>
    </div>
    <div class="payment-status-list">
      @forelse(collect($report['status_list'] ?? []) as $status)
        <div><span>{{ $status['label'] }}</span><strong>{{ $status['total'] }}</strong></div>
      @empty
        <div><span>Belum ada transaksi payment</span><strong>0</strong></div>
      @endforelse
    </div>
  </section>

  <section class="admin-panel admin-report-panel">
    <div class="admin-panel-head"><div><h2>Category Performance</h2><p>Kategori berdasarkan transaksi payment paid/success real.</p></div></div>
    <div class="category-performance-list">
      @forelse(collect($report['category_performance'] ?? []) as $category)
        @php $maxCategory = max(1, collect($report['category_performance'] ?? [])->max('revenue') ?: 1); @endphp
        <div class="category-performance-item">
          <div><strong>{{ $category['label'] }}</strong><span>{{ number_format((int) $category['sold'], 0, ',', '.') }} sold • {{ $rupiah($category['revenue']) }}</span></div>
          <div class="category-line"><i style="width: {{ max(8, round(((int) $category['revenue'] / $maxCategory) * 100)) }}%"></i></div>
        </div>
      @empty
        <div class="admin-empty-report">Belum ada data kategori.</div>
      @endforelse
    </div>
  </section>
</div>

<div class="grid lg:grid-cols-2 gap-6 mt-6">
  <div class="admin-panel admin-report-panel">
    <div class="admin-panel-head"><div><h2>Top Products</h2><p>Produk teratas berdasarkan sold_count.</p></div></div>
    @foreach(collect($report['top_products'] ?? [])->take(5) as $product)
      <div class="admin-mini-row report-mini-row"><img src="{{ $img($product->image) }}"><div><strong>{{ $product->name }}</strong><span>{{ $product->category }} • {{ number_format((int) ($product->sold_count ?? 0), 0, ',', '.') }} sold • {{ $rupiah($product->price) }}</span></div></div>
    @endforeach
  </div>
  <div class="admin-panel admin-report-panel">
    <div class="admin-panel-head"><div><h2>Latest Website Updates</h2><p>Produk dan artikel terbaru yang masuk ke CMS.</p></div></div>
    @foreach($articles->take(3) as $article)
      <div class="admin-mini-row report-mini-row"><img src="{{ $img($article->image) }}"><div><strong>{{ $article->title }}</strong><span>Artikel • {{ $article->category }} • {{ $article->tag }}</span></div></div>
    @endforeach
    @foreach($products->take(3) as $product)
      <div class="admin-mini-row report-mini-row"><img src="{{ $img($product->image) }}"><div><strong>{{ $product->name }}</strong><span>Produk • {{ $product->category }} • Stock {{ (int) ($product->stock ?? 0) }}</span></div></div>
    @endforeach
  </div>
</div>
@endsection
