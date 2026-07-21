@extends('layouts.admin')

@section('title', 'Review Produk — Admin GlowSkin')

@section('content')
<section class="admin-page-head">
  <div>
    <p>PRODUCT REVIEWS</p>
    <h1>Review Produk</h1>
    <span>Review hanya berasal dari user yang sudah membeli produk.</span>
  </div>
  <a href="{{ route('admin.products.index') }}" class="admin-head-btn bg-white/10">Produk</a>
</section>

<div class="admin-panel">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Produk</th>
          <th>User</th>
          <th>Rating</th>
          <th>Review</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($reviews as $review)
          <tr>
            <td>
              <strong>{{ $review->product->name ?? 'Produk dihapus' }}</strong>
              <span>Verified purchase</span>
            </td>
            <td>
              <div class="admin-review-user">
                @php
                  $adminReviewProfile = $review->user?->profile_image;
                  $adminReviewProfileUrl = $adminReviewProfile
                    ? (\Illuminate\Support\Str::startsWith($adminReviewProfile, ['http://', 'https://'])
                      ? $adminReviewProfile
                      : asset($adminReviewProfile))
                    : null;
                @endphp
                <div class="admin-review-avatar">
                  @if($adminReviewProfileUrl)
                    <img src="{{ $adminReviewProfileUrl }}" alt="Foto profil {{ $review->user->name ?? 'User GlowSkin' }}">
                  @else
                    <span>{{ strtoupper(substr($review->user->name ?? 'U', 0, 1)) }}</span>
                  @endif
                </div>
                <div>
                  <strong>{{ $review->user->name ?? 'User GlowSkin' }}</strong>
                  <span>{{ $review->user->email ?? 'User #'.$review->user_id }}</span>
                </div>
              </div>
            </td>
            <td>{{ $review->rating }} / 5</td>
            <td>
              {{ \Illuminate\Support\Str::limit($review->review, 80) }}
              @if($review->is_edited)
                <span class="admin-review-edited">Edited</span>
              @endif
            </td>
            <td>{{ $review->is_active ? 'Tampil' : 'Disembunyikan' }}</td>
            <td class="admin-actions">
              <form method="POST" action="{{ route('admin.reviews.toggle', $review) }}">
                @csrf
                @method('PUT')
                <button type="submit">{{ $review->is_active ? 'Hide' : 'Show' }}</button>
              </form>

              <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}">
                @csrf
                @method('DELETE')
                <button type="submit">Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">Belum ada review produk.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
<style>
.admin-review-user{display:flex;align-items:center;gap:10px;min-width:190px}.admin-review-user>div:last-child{display:flex;flex-direction:column;gap:2px}.admin-review-user strong{color:inherit}.admin-review-user span{font-size:11px;opacity:.65}.admin-review-avatar{width:38px;height:38px;min-width:38px;border-radius:50%;overflow:hidden;background:#4A7A3A;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:900}.admin-review-avatar img{width:100%;height:100%;object-fit:cover;display:block}.admin-review-edited{display:inline-flex;margin-left:8px;padding:3px 7px;border:1px solid #ead8aa;background:#fff8df;color:#8a6717;font-size:9px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;vertical-align:middle}
</style>
@endsection
