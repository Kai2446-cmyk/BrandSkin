@extends('layouts.admin')

@section('title', 'Voucher — Admin GlowSkin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-voucher-list-clean-only.css') }}?v=20260710">
<section class="admin-page-head">
  <div>
    <p>VOUCHER MANAGEMENT</p>
    <h1>VOUCHER</h1>
    <span>Buat voucher yang otomatis tampil di Profile user bagian My Voucher dan bisa dipakai saat checkout.</span>
  </div>
  <a href="{{ \Illuminate\Support\Facades\Route::has('profile.vouchers') ? route('profile.vouchers') : route('profile.index') }}" class="admin-head-btn bg-white/10">Lihat My Voucher</a>
</section>

<div class="promo-admin-grid">
  <form method="POST" action="{{ route('admin.promos.store') }}" class="admin-panel promo-form" data-voucher-form>
    @csrf

    <h2>Tambah Voucher</h2>

    <label class="admin-field">
      <span>Kode Voucher</span>
      <input name="code" placeholder="Contoh: GLOW10" required>
    </label>

    <label class="admin-field">
      <span>Deskripsi Voucher</span>
      <input name="description" placeholder="Contoh: Diskon khusus member GlowSkin">
    </label>

    <div class="grid md:grid-cols-2 gap-4">
      <label class="admin-field">
        <span>Tipe Diskon</span>
        <select name="discount_type" required data-voucher-type>
          <option value="percent">Persen (%)</option>
          <option value="fixed">Nominal Rupiah</option>
          <option value="free_shipping">Gratis Ongkir</option>
        </select>
      </label>

      <label class="admin-field" data-voucher-value-wrap>
        <span>Nilai Voucher</span>
        <input type="number" name="discount_value" min="1" max="100" list="percentOptions" placeholder="10 / 50000" required data-voucher-value>
        <datalist id="percentOptions">
          @for($i = 1; $i <= 100; $i++)
            <option value="{{ $i }}">{{ $i }}%</option>
          @endfor
        </datalist>
      </label>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
      <label class="admin-field">
        <span>Minimum Belanja</span>
        <input type="number" name="minimum_purchase" min="0" value="0">
      </label>

      <label class="admin-field">
        <span>Batas Pemakaian</span>
        <input type="number" name="usage_limit" min="1" placeholder="Kosongkan jika bebas">
      </label>
    </div>

    <label class="admin-field">
      <span>Berlaku Sampai</span>
      <input type="date" name="expires_at">
    </label>

    <div class="admin-checkbox-row">
      <label class="admin-check">
        <input type="checkbox" name="is_active" value="1" checked>
        Voucher Aktif dan tampil di My Voucher user
      </label>
    </div>

    <button class="green-gradient-btn admin-head-btn" type="submit">Tambah Voucher</button>
  </form>

  <div class="admin-panel promo-list voucher-list-clean">
    <div class="voucher-list-clean__head">
      <div>
        <p>VOUCHER AKTIF</p>
        <h2>Daftar Voucher</h2>
      </div>
      <span>{{ $promos->count() }} voucher</span>
    </div>

    <div class="voucher-list-clean__items">
      @forelse($promos as $promo)
        @php
          $discountLabel = $promo->discount_type === 'free_shipping'
              ? 'GRATIS ONGKIR'
              : ($promo->discount_type === 'percent'
                  ? $promo->discount_value.'% OFF'
                  : 'Rp'.number_format($promo->discount_value,0,',','.').' OFF');
        @endphp
        <article class="promo-card voucher-card-clean">
          <div class="voucher-card-clean__content">
            <div class="voucher-card-clean__top">
              <strong>{{ $promo->code }}</strong>
              <span class="voucher-card-clean__status">{{ $promo->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            </div>

            <p>{{ $promo->description ?: 'Tidak ada deskripsi voucher.' }}</p>

            <div class="voucher-card-clean__meta">
              <span>{{ $discountLabel }}</span>
              <span>Min. Rp{{ number_format($promo->minimum_purchase ?? 0,0,',','.') }}</span>
              <span>Dipakai {{ $promo->used_count }}/{{ $promo->usage_limit ?: '∞' }}</span>
            </div>
          </div>

          <form method="POST" action="{{ route('admin.promos.destroy', $promo) }}" data-voucher-delete-form data-voucher-code="{{ $promo->code }}">
            @csrf
            @method('DELETE')
            <button type="submit">Hapus</button>
          </form>
        </article>
      @empty
        <div class="promo-empty">Belum ada voucher.</div>
      @endforelse
    </div>
  </div>
</div>

<div class="admin-voucher-confirm" data-admin-voucher-confirm aria-hidden="true">
  <div class="admin-voucher-confirm-backdrop" data-voucher-cancel></div>
  <div class="admin-voucher-confirm-panel">
    <span>Hapus Voucher</span>
    <h3>Yakin hapus voucher ini?</h3>
    <p data-voucher-confirm-text>Voucher akan dihapus dari daftar admin dan My Voucher user.</p>
    <div>
      <button type="button" data-voucher-cancel>Cancel</button>
      <button type="button" data-voucher-ok>Hapus</button>
    </div>
  </div>
</div>

<script>
(function(){
  const voucherType = document.querySelector('[data-voucher-type]');
  const voucherValue = document.querySelector('[data-voucher-value]');
  const valueWrap = document.querySelector('[data-voucher-value-wrap]');

  function syncVoucherValueField(){
    if(!voucherType || !voucherValue || !valueWrap) return;

    if(voucherType.value === 'free_shipping'){
      voucherValue.value = 0;
      voucherValue.min = 0;
      voucherValue.required = false;
      voucherValue.disabled = true;
      valueWrap.classList.add('is-disabled');
      valueWrap.querySelector('span').textContent = 'Nilai Voucher';
      voucherValue.placeholder = 'Otomatis gratis ongkir';
      return;
    }

    voucherValue.disabled = false;
    voucherValue.required = true;
    valueWrap.classList.remove('is-disabled');

    if(voucherType.value === 'percent'){
      voucherValue.min = 1;
      voucherValue.max = 100;
      voucherValue.setAttribute('list', 'percentOptions');
      voucherValue.placeholder = 'Pilih / ketik 1 - 100';
      valueWrap.querySelector('span').textContent = 'Nilai Diskon (%)';
    } else {
      voucherValue.min = 1;
      voucherValue.removeAttribute('max');
      voucherValue.removeAttribute('list');
      voucherValue.placeholder = 'Contoh: 50000';
      valueWrap.querySelector('span').textContent = 'Nilai Diskon (Rp)';
    }
  }

  voucherType?.addEventListener('change', syncVoucherValueField);
  syncVoucherValueField();

  const modal = document.querySelector('[data-admin-voucher-confirm]');
  const text = document.querySelector('[data-voucher-confirm-text]');
  const okButton = document.querySelector('[data-voucher-ok]');
  let pendingForm = null;

  function openConfirm(form){
    pendingForm = form;
    const code = form?.dataset.voucherCode || 'ini';
    if(text) text.innerHTML = 'Voucher <b>' + code + '</b> akan dihapus dari daftar admin dan My Voucher user.';
    modal?.classList.add('is-open');
    modal?.setAttribute('aria-hidden', 'false');
  }

  function closeConfirm(){
    pendingForm = null;
    modal?.classList.remove('is-open');
    modal?.setAttribute('aria-hidden', 'true');
  }

  document.querySelectorAll('[data-voucher-delete-form]').forEach(function(form){
    form.addEventListener('submit', function(event){
      event.preventDefault();
      openConfirm(form);
    });
  });

  document.querySelectorAll('[data-voucher-cancel]').forEach(function(button){
    button.addEventListener('click', closeConfirm);
  });

  okButton?.addEventListener('click', function(){
    const form = pendingForm;
    closeConfirm();
    form?.submit();
  });

  document.addEventListener('keydown', function(event){
    if(event.key === 'Escape') closeConfirm();
  });
})();
</script>
@endsection
