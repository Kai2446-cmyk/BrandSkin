@extends('layouts.app')

@section('title', 'Login — GlowSkin')

@section('content')
<link rel="stylesheet" href="{{ asset('css/auth-reference.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth-navbar-lavender-only.css') }}?v=20260710">
<link rel="stylesheet" href="{{ asset('css/auth-full-height-fix.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth-smooth-switch.css') }}">
<link rel="stylesheet" href="{{ asset('css/reset-password-email-verification.css') }}">

<main class="auth-ref-page auth-login-page" data-auth-page="login">
    <section class="auth-ref-split">
        <div class="auth-ref-left auth-login-left">
            <div class="auth-ref-left-copy">
                <span>WELCOME TO</span>
                <h1>GLOWSKIN<br>BEAUTY BEYOND RULES</h1>
            </div>
        </div>

        <div class="auth-ref-right">
            <div class="auth-card auth-login-card" data-auth-card>
                <div class="auth-tabs">
                    <a href="{{ route('login') }}" class="active" data-no-page-loader>LOGIN</a>
                    <a href="{{ route('register') }}" data-auth-switch data-no-page-loader>REGISTER</a>
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

                <form method="POST" action="{{ route('login.post') }}" class="auth-ref-form">
                    @csrf

                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                    </label>

                    <label>
                        <span>Password</span>
                        <div class="auth-password-line">
                            <input type="password" name="password" autocomplete="current-password" required data-auth-password>
                            <button type="button" data-auth-show>SHOW</button>
                        </div>
                    </label>

                    <div class="auth-ref-options">
                        <label>
                            <input type="checkbox" name="remember" value="1">
                            <span>Remember me</span>
                        </label>
                        <a href="{{ route('password.forgot') }}" data-auth-switch data-no-page-loader>Forgot Password?</a>
                    </div>

                    <button type="submit" class="auth-main-btn auth-login-submit">LOGIN</button>
                </form>

                <p class="auth-small-note">
                    Belum punya akun?
                    <a href="{{ route('register') }}" data-auth-switch data-no-page-loader>Create Account</a>
                </p>
            </div>
        </div>
    </section>
</main>

<script src="{{ asset('js/auth-reference.js') }}"></script>
<script src="{{ asset('js/auth-smooth-switch.js') }}"></script>
@endsection
