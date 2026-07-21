@extends('layouts.app')

@section('title', 'My Profile — GlowSkin')

@section('content')
@php
    $birthDate = null;
    if (!empty($user->birth_date)) {
        try { $birthDate = \Carbon\Carbon::parse($user->birth_date); } catch (\Throwable $e) { $birthDate = null; }
    }

    $day = old('birth_day', $birthDate?->day);
    $month = old('birth_month', $birthDate?->month);
    $year = old('birth_year', $birthDate?->year);

    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
    ];
@endphp


<link rel="stylesheet" href="{{ asset('css/profile-page.css') }}?v=20260710-profile-base">
<link rel="stylesheet" href="{{ asset('css/profile-pages-lavender-navbar-order-fix.css') }}?v=20260710">
<link rel="stylesheet" href="{{ asset('css/profile-address-photo-fix.css') }}?v=20260714-map">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<main class="profile-page">
    <section class="profile-shell">
        <aside class="profile-sidebar">
            <a href="{{ route('profile.index') }}" class="active">My Profile <span>♥</span></a>
            <a href="{{ route('profile.orders') }}">My Order</a>
            <a href="{{ route('wishlist.index') }}">My Wishlist</a>
            <a href="{{ route('profile.vouchers') }}">My Voucher</a>
            <a href="{{ route('profile.skin-diary') }}">Skin Diary</a>
        </aside>

        <section class="profile-card">
            <div class="profile-head">
                <div>
                    <span>Account Center</span>
                    <h1>My Profile</h1>
                    <p>Kelola data akun GlowSkin kamu dengan tampilan yang rapi dan aman.</p>
                </div>
                <label class="profile-avatar-upload" title="Klik untuk mengganti foto profil">
                    @if(!empty($user->profile_image))
                        <img src="{{ asset($user->profile_image) }}" alt="Foto profil">
                    @else
                        <span>{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                    @endif
                    <b>Ubah Foto</b>
                    <input type="file" name="profile_image" accept="image/jpeg,image/png,image/webp" form="profile-main-form">
                </label>
            </div>

            @if(session('success'))
                <div class="profile-alert success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="profile-alert error">
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form id="profile-main-form" method="POST" action="{{ route('profile.update') }}" class="profile-form" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <label class="profile-field full">
                    <span>Full Name</span>
                    <input name="name" value="{{ old('name', $user->name) }}" required>
                </label>

                <label class="profile-field full">
                    <span>Email</span>
                    <input value="{{ $user->email }}" readonly>
                </label>

                <label class="profile-field full">
                    <span>Mobile number optional</span>
                    <input name="phone" value="{{ old('phone', $user->phone ?? '') }}" placeholder="08xxxxxxxxxx">
                </label>

                <div class="profile-date-block full">
                    <span>Date of Birth</span>
                    <div class="profile-date-grid">
                        <label>
                            <small>Date</small>
                            <select name="birth_day">
                                <option value="">Date</option>
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }}" @selected((string)$day === (string)$i)>{{ $i }}</option>
                                @endfor
                            </select>
                        </label>

                        <label>
                            <small>Month</small>
                            <select name="birth_month">
                                <option value="">Month</option>
                                @foreach($months as $value => $label)
                                    <option value="{{ $value }}" @selected((string)$month === (string)$value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label>
                            <small>Year</small>
                            <select name="birth_year">
                                <option value="">Year</option>
                                @for($i = now()->year; $i >= 1900; $i--)
                                    <option value="{{ $i }}" @selected((string)$year === (string)$i)>{{ $i }}</option>
                                @endfor
                            </select>
                        </label>
                    </div>
                </div>

                <div class="profile-password-preview">
                    <label class="profile-field">
                        <span>Password</span>
                        <input value="••••••••" readonly>
                    </label>
                    <button type="button" data-profile-password-open>Edit Password</button>
                </div>

                <label class="profile-check full">
                    <input type="checkbox" name="marketing_consent" value="1" @checked(old('marketing_consent', $user->marketing_consent ?? false))>
                    <span>Send me latest info & promotions on GlowSkin products</span>
                </label>

                <button type="submit" class="profile-save">Save Changes</button>
            </form>


            <section class="profile-address-section">
                <div class="profile-address-title"><div><span>Saved Address</span><h2>Alamat Tersimpan</h2><p>Alamat ini sama dengan alamat checkout dan berbeda untuk setiap akun.</p></div><button type="button" data-address-toggle>+ Tambah Alamat</button></div>
                <div class="profile-address-grid">
                    @forelse($addresses as $address)
                    <article class="profile-address-card {{ $address->is_default ? 'is-default' : '' }}">
                        <div class="profile-address-card-head"><strong>{{ $address->label ?: 'Alamat' }}</strong>@if($address->is_default)<em>Utama</em>@endif</div>
                        <h3>{{ $address->recipient_name }}</h3><p>{{ $address->phone }}</p><p>{{ $address->address_line }}</p>
                        <small>{{ collect([$address->district,$address->city,$address->province,$address->postal_code])->filter()->join(', ') }}</small>
                        <div class="profile-address-actions">
                            @unless($address->is_default)<form method="POST" action="{{ route('profile.address.default',$address) }}">@csrf @method('PUT')<button>Jadikan Utama</button></form>@endunless
                            <form method="POST" action="{{ route('profile.address.delete',$address) }}" onsubmit="return confirm('Hapus alamat ini?')">@csrf @method('DELETE')<button class="danger">Hapus</button></form>
                        </div>
                    </article>
                    @empty <div class="profile-address-empty">Belum ada alamat tersimpan.</div> @endforelse
                </div>
                <form method="POST" action="{{ route('profile.address.save') }}" class="profile-address-form" data-address-form>@csrf
                    <div class="profile-map-picker">
                        <div class="profile-map-head">
                            <div>
                                <strong>PILIH TITIK LOKASI DI MAPS</strong>
                                <p>Klik maps atau geser pin. Alamat, kecamatan, kota, provinsi, kode pos, dan tautan Google Maps akan terisi otomatis.</p>
                            </div>
                            <button type="button" data-profile-use-location>Gunakan Lokasi Saya</button>
                        </div>
                        <div id="profileAddressMap" class="profile-address-map" data-default-lat="{{ $addresses->firstWhere('is_default', true)->latitude ?? '-6.93552104' }}" data-default-lng="{{ $addresses->firstWhere('is_default', true)->longitude ?? '107.53465931' }}"></div>
                        <div class="profile-map-status" data-profile-map-status>Pilih titik alamat di maps.</div>
                    </div>
                    <input type="hidden" name="latitude" data-profile-lat>
                    <input type="hidden" name="longitude" data-profile-lng>
                    <input type="hidden" name="map_link" data-profile-map-link>
                    <div><label>Label<input name="label" placeholder="Rumah / Kantor"></label><label>Nama Penerima<input name="recipient_name" value="{{ old('recipient_name',$user->name) }}" required></label></div>
                    <div><label>Nomor HP<input name="phone" value="{{ old('phone',$user->phone ?? '') }}" required></label><label>Kode Pos<input name="postal_code"></label></div>
                    <label>Alamat Lengkap<textarea name="address_line" required></textarea></label>
                    <div><label>Kecamatan<input name="district"></label><label>Kota/Kabupaten<input name="city"></label></div>
                    <div><label>Provinsi<input name="province"></label><label>Negara<input name="country" value="Indonesia"></label></div>
                    <label>Catatan Kurir<textarea name="courier_note"></textarea></label>
                    <label class="profile-address-default"><input type="checkbox" name="is_default" value="1"> Jadikan alamat utama</label>
                    <button type="submit">Simpan Alamat</button>
                </form>
            </section>

            <form method="POST" action="{{ route('profile.password') }}" class="profile-password-form" data-profile-password-form>
                @csrf
                @method('PUT')

                <h2>Change Password</h2>

                <div class="profile-password-grid">
                    <label class="profile-field">
                        <span>Old Password</span>
                        <input type="password" name="old_password" placeholder="Old Password">
                    </label>

                    <label class="profile-field">
                        <span>New Password</span>
                        <input type="password" name="new_password" placeholder="New Password">
                    </label>
                </div>

                <button type="submit">Save Changes</button>
            </form>
        </section>
    </section>
</main>

<script src="{{ asset('js/profile-page.js') }}"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="{{ asset('js/profile-address-map.js') }}?v=20260714"></script>
<script>document.addEventListener('DOMContentLoaded',()=>{const t=document.querySelector('[data-address-toggle]'),f=document.querySelector('[data-address-form]');if(t&&f)t.addEventListener('click',()=>f.classList.toggle('is-open'));const i=document.querySelector('.profile-avatar-upload input');if(i)i.addEventListener('change',()=>i.form?.submit());});</script>
@endsection
