@extends('layouts.app')

@section('title', 'GlowSkin Skin Analyzer')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/skin-analyzer/skin-analyzer.css') }}">
@endpush

@section('content')

  <main class="page" aria-label="GlowSkin Skin Analyzer">
    <section class="sa-frame home-frame">
      <div class="home-title"><span>SKIN</span><strong>ANALYZER</strong><p>Skin Consultant</p></div>
      <section class="card intro-card"><h1>Get to know your Skintuation</h1><p>Say goodbye to acne, dullness, and spots! Analyze your skin and get tailored product recommendations made just for you.</p></section>
      <section class="card steps-card">
        <h2>How it works</h2>
        <div class="step-row"><div class="icon-badge"><svg><use href="{{ asset('assets/skin-analyzer/assets/icons.svg') }}#face"></use></svg></div><div><h3>Step 1</h3><p>Take your Selfie</p></div></div>
        <div class="step-row"><div class="icon-badge"><svg><use href="{{ asset('assets/skin-analyzer/assets/icons.svg') }}#face"></use></svg></div><div><h3>Step 2</h3><p>See skin analysis report</p></div></div>
        <div class="step-row"><div class="icon-badge"><svg><use href="{{ asset('assets/skin-analyzer/assets/icons.svg') }}#bottle"></use></svg></div><div><h3>Step 3</h3><p>See product recommendation</p></div></div>
      </section>
      <section class="card result-card">
        <h2>What can you get from here?</h2>
        <div class="skin-carousel"><div class="carousel-track" data-carousel-track>
          @foreach([['acne.png','Acne'],['skin-texture.png','Skin Texture'],['pore-size.png','Pore Size'],['dark-spots.jpg','Dark Spots'],['brightness.png','Brightness']] as [$image,$label])
            <div class="skin-item"><img src="{{ asset('assets/skin-analyzer/assets/'.$image) }}" alt="{{ $label }}"><p>{{ $label }}</p></div>
          @endforeach
        </div></div>
        <p class="result-copy">Unleash your skin's radiant glow and dive into your unique “Skintuation” with the GlowSkin Skin Analyzer.</p>
      </section>
    </section>
  </main>
  <a class="floating-cta" href="{{ route('skin-analyzer.start') }}">Get to Know Your Skin!</a>


@endsection

@push('scripts')
<script src="{{ asset('assets/skin-analyzer/skin-analyzer.js') }}"></script>
@endpush
