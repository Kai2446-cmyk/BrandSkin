@extends('layouts.app')

@section('title', 'GlowSkin Skin Analyzer - Start')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/skin-analyzer/skin-analyzer.css') }}">
@endpush

@section('content')
<main class="page"><section class="sa-frame start-frame">
<div class="top-actions"><button class="round-nav" type="button" data-action="back" aria-label="Back"><svg><use href="{{ asset('assets/skin-analyzer/assets/icons.svg') }}#back"></use></svg></button><a class="round-nav" href="{{ route('skin-analyzer.index') }}" aria-label="Home"><svg><use href="{{ asset('assets/skin-analyzer/assets/icons.svg') }}#home"></use></svg></a></div>
<section class="card selfie-card"><h1>How to take a perfect selfie</h1>
@foreach([['👩🏻‍🦰','Remove your glasses and makeup.'],['👩🏻','Stand in a well lit area and ensure there are no shadows on your face.'],['🧕🏻',"If you're wearing headscarf, adjust it so it does not cover or create a shadow on your face."],['🙂','Use a neutral expression, no smiling, and align your face in the oval.']] as $i => [$emoji,$text])
<div class="selfie-step"><div class="avatar {{ $i < 2 ? 'warn' : '' }}"><span>{{ $emoji }}</span></div><div><h2>Step {{ $i+1 }}</h2><p>{{ $text }}</p></div></div>
@endforeach
<div class="policy-box"><hr><p>By using “GlowSkin AI Skin Analysis”, you agree to our Privacy Policy and Terms of Use.</p><a class="disabled-cta" style="display:flex;align-items:center;justify-content:center;text-decoration:none;opacity:1;pointer-events:auto" href="{{ route('skin-analyzer.report') }}">Ok, I’m Ready!</a></div>
</section></section></main>
@endsection

@push('scripts')
<script src="{{ asset('assets/skin-analyzer/skin-analyzer.js') }}"></script>
@endpush
