@extends('layouts.app')

@section('title', 'Register — GlowSkin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth-reference.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth-navbar-lavender-only.css') }}?v=20260710">
<link rel="stylesheet" href="{{ asset('css/auth-full-height-fix.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth-smooth-switch.css') }}">
<link rel="stylesheet" href="{{ asset('css/register-email-verification.css') }}">
<link rel="stylesheet" href="{{ asset('css/reset-password-email-verification.css') }}">

<main class="auth-ref-page auth-register-page" data-auth-page="register">
    <section class="auth-ref-split">
        <div class="auth-ref-left auth-register-left">
            <div class="auth-ref-left-copy">
                <span>WELCOME TO</span>
                <h1>GLOWSKIN<br>BEAUTY BEYOND RULES</h1>
            </div>
        </div>

        <div class="auth-ref-right">
            <div class="auth-card auth-register-card" data-auth-card>
                <div class="auth-tabs">
                    <a href="{{ route('login') }}" data-auth-switch data-no-page-loader>LOGIN</a>
                    <a href="{{ route('register') }}" class="active" data-no-page-loader>REGISTER</a>
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

                <div class="register-code-alert" data-register-code-alert hidden></div>

                <form method="POST" action="{{ route('register.post') }}" class="auth-ref-form register-verif-form">
                    @csrf

                    <label>
                        <span>Email</span>
                        <div class="register-email-row">
                            <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required data-register-email>
                            <button type="button" data-send-register-code>VERIF EMAIL</button>
                        </div>
                    </label>

                    <label class="register-code-field">
                        <span>Kode Verifikasi Email</span>
                        <input type="text" name="verification_code" value="{{ old('verification_code') }}" maxlength="6" inputmode="numeric" placeholder="Masukkan 6 digit kode dari Gmail" required>
                    </label>

                    <label>
                        <span>Full name</span>
                        <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
                    </label>

                    <label>
                        <span>Mobile number</span>
                        <input type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                    </label>

                    <label>
                        <span>Password</span>
                        <div class="auth-password-line">
                            <input type="password" name="password" autocomplete="new-password" required data-auth-password>
                            <button type="button" data-auth-show>SHOW</button>
                        </div>
                        <small>Minimum 8 characters, must have at least 1 number</small>
                    </label>

                    <label>
                        <span>Confirm password</span>
                        <div class="auth-password-line">
                            <input type="password" name="password_confirmation" autocomplete="new-password" required data-auth-password>
                            <button type="button" data-auth-show>SHOW</button>
                        </div>
                    </label>

                    <label class="auth-agree">
                        <input type="checkbox" name="terms" value="1" required>
                        <span>
                            I have read and agree to GlowSkin's <b>Terms of Use</b> and consent to the processing of my personal data in accordance with GlowSkin's <b>Privacy Policy</b>.
                        </span>
                    </label>

                    <button type="submit" class="auth-main-btn auth-register-btn">REGISTER</button>
                </form>

                <p class="auth-small-note">
                    Sudah punya akun?
                    <a href="{{ route('login') }}" data-auth-switch data-no-page-loader>Login</a>
                    ·
                    <a href="{{ route('password.forgot') }}" data-auth-switch data-no-page-loader>Lupa Password?</a>
                </p>
            </div>
        </div>
    </section>
</main>

<script>
    window.GLOWSKIN_REGISTER_CODE_URL = "{{ route('register.send-code') }}";
</script>
<script src="{{ asset('js/auth-reference.js') }}"></script>
<script src="{{ asset('js/auth-smooth-switch.js') }}"></script>
<script src="{{ asset('js/register-email-verification.js') }}"></script>
@endsection
