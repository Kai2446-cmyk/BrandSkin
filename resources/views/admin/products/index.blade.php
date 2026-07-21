@extends('layouts.admin')
@section('title','Pengaturan Produk — Admin GlowSkin')
@section('content')
@php $img = fn($path) => \Illuminate\Support\Str::startsWith($path ?? '', ['http://','https://']) ? $path : asset($path ?: 'assets/images/no_image.png'); @endphp
<section class="admin-page-head">
  <div>
    <p>PRODUCT MANAGEMENT</p>
    <h1>Pengaturan Produk</h1>
    <span>Search, tambah produk, dan edit data produk yang tampil di website.</span>
  </div>
  <div class="admin-head-actions">
    <a href="{{ route('admin.products.create') }}" class="green-gradient-btn admin-head-btn">+ Tambah Produk</a>
    <a href="{{ route('admin.dashboard') }}" class="admin-head-btn bg-white/10">Back</a>
  </div>
</section>

<form method="GET" action="{{ route('admin.products.index') }}" class="admin-search-bar">
  <input name="q" value="{{ $keyword }}" placeholder="Search produk berdasarkan nama, kategori, atau subtitle...">
  <button class="green-gradient-btn" type="submit">Search</button>
  @if($keyword)<a href="{{ route('admin.products.index') }}">Reset</a>@endif
</form>

<div class="admin-panel">
  <div class="overflow-x-auto">
    <table class="admin-table">
      <thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Status</th><th>Stok</th><th>Aksi</th></tr></thead>
      <tbody>
        @forelse($products as $product)
          <tr>
            <td><div class="flex items-center gap-3"><img src="{{ $img($product->image) }}" class="w-14 h-14 object-cover rounded-lg"><div><strong>{{ $product->name }}</strong><small>{{ $product->subtitle }}</small></div></div></td>
            <td>{{ $product->category }}</td>
            <td>Rp{{ number_format($product->price,0,',','.') }}</td>
            <td>@if($product->is_new_arrival)<span class="admin-badge">New</span>@endif @if($product->is_best_seller)<span class="admin-badge">Best</span>@endif @if($product->is_on_sale)<span class="admin-badge sale">Sale</span>@endif</td>
            <td>{{ $product->stock }}</td>
            <td><a href="{{ route('admin.products.edit', $product) }}" class="admin-action-btn">Edit Produk</a></td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center py-8 text-white/50">Produk tidak ditemukan.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
