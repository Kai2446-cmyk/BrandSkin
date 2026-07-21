@extends('layouts.app')

@section('title', 'Reset Password — GlowSkin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth-reference.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth-full-height-fix.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth-smooth-switch.css') }}">
<link rel="stylesheet" href="{{ asset('css/reset-password-email-verification.css') }}">

<main class="auth-ref-page auth-reset-page" data-auth-page="reset">
    <section class="auth-ref-split">
        <div class="auth-ref-left auth-reset-left">
            <div class="auth-ref-left-copy">
                <span>RESET ACCESS</span>
                <h1>NEW PASSWORD<br>FOR YOUR BEAUTY ACCOUNT</h1>
            </div>
        </div>

        <div class="auth-ref-right">
            <div class="auth-card auth-reset-card" data-auth-card>
                <div class="auth-tabs auth-reset-tabs">
                    <a href="{{ route('login') }}" data-auth-switch data-no-page-loader>LOGIN</a>
                    <a href="{{ route('register') }}" data-auth-switch data-no-page-loader>REGISTER</a>
                </div>

                <div class="reset-title">
                    <span>FORGOT PASSWORD</span>
                    <h2>Reset Password</h2>
                    <p>Masukkan email akun kamu, kirim kode verifikasi, lalu buat password baru.</p>
                </div>

                @if($errors->any())
                    <div class="auth-alert error">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if(session('success'))
                    <div class="auth-alert success">{{ session('success') }}</div>
                @endif

                <div class="reset-code-alert" data-reset-code-alert hidden></div>

                <form method="POST" action="{{ route('password.reset.post') }}" class="auth-ref-form reset-verif-form">
                    @csrf

                    <label>
                        <span>Email</span>
                        <div class="reset-email-row">
                            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required data-reset-email>
                            <button type="button" data-send-reset-code>KIRIM KODE</button>
                        </div>
                    </label>

                    <label>
                        <span>Kode Verifikasi Email</span>
                        <input type="text" name="reset_code" value="{{ old('reset_code') }}" maxlength="6" inputmode="numeric" placeholder="Masukkan 6 digit kode dari Gmail" required>
                    </label>

                    <label>
                        <span>Password Baru</span>
                        <div class="auth-password-line">
                            <input type="password" name="password" autocomplete="new-password" required data-auth-password>
                            <button type="button" data-auth-show>SHOW</button>
                        </div>
                        <small>Minimum 8 characters, must have at least 1 number</small>
                    </label>

                    <label>
                        <span>Konfirmasi Password Baru</span>
                        <div class="auth-password-line">
                            <input type="password" name="password_confirmation" autocomplete="new-password" required data-auth-password>
                            <button type="button" data-auth-show>SHOW</button>
                        </div>
                    </label>

                    <button type="submit" class="auth-main-btn reset-main-btn">RESET PASSWORD</button>
                </form>

                <p class="auth-small-note">
                    Sudah ingat password?
                    <a href="{{ route('login') }}" data-auth-switch data-no-page-loader>Login</a>
                </p>
            </div>
        </div>
    </section>
</main>

<script>
    window.GLOWSKIN_RESET_CODE_URL = "{{ route('password.send-code') }}";
</script>
<script src="{{ asset('js/auth-reference.js') }}"></script>
<script src="{{ asset('js/auth-smooth-switch.js') }}"></script>
<script src="{{ asset('js/reset-password-email-verification.js') }}"></script>
@endsection
