@extends('layouts.app')

@section('title', 'GlowSkin Recommendations')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('skin-report/skin-report.css') }}">
@endpush

@section('content')
<div class="skin-analyzer-page-data"
     data-skin-diary-url="{{ route('profile.skin-diary') }}"
     data-skin-report-url="{{ route('skin-analyzer.report') }}"
     data-skin-recommendation-url="{{ route('skin-analyzer.recommendations') }}">
<main class="page">
    <section class="sa-frame reco-frame">
      
<button class="nav-back" type="button" data-report aria-label="Back"><svg><use href="{{ asset('skin-report/assets/icons.svg') }}#back"></use></svg></button>

<section class="reco-header">
  <h1>Your Personalised<br>Product Recommendations</h1>
  <div class="concerns">
    <div><img src="{{ asset('skin-report/assets/acne.png') }}" alt="Acne"><p>Acne & Pimples</p></div>
    <div><img src="{{ asset('skin-report/assets/pore-size.png') }}" alt="Pore"><p>Enlarged Pores</p></div>
    <div><img src="{{ asset('skin-report/assets/skin-texture.png') }}" alt="Oily Skin"><p>Oily Skin</p></div>
    <div><img src="{{ asset('skin-report/assets/dark-spots.jpg') }}" alt="Dark spots"><p>Dark Spots & Pigmentation</p></div>
  </div>

  <div class="product-mode-carousel">
    <button class="mode-tab active" data-mode="morning"><span class="mode-icon">☁️</span><p>Morning<br>Routine</p></button>
    <button class="mode-tab" data-mode="night"><span class="mode-icon">☁️</span><p>Night<br>Routine</p></button>
    <button class="mode-tab" data-mode="other"><span class="mode-icon">☁️</span><p>Other<br>Products</p></button>
  </div>
</section>

<section id="productGrid" class="product-grid"></section>

<section class="card dos-card">
  <h2>Do's and Don'ts</h2>
  <div class="do-row">
    <span class="do-icon good-bg"><svg><use href="{{ asset('skin-report/assets/icons.svg') }}#check"></use></svg></span>
    <p>Do Test Products: Introduce new skincare products one at a time to identify any potential triggers.</p>
  </div>
  <div class="do-row">
    <span class="do-icon bad-bg"><svg><use href="{{ asset('skin-report/assets/icons.svg') }}#x"></use></svg></span>
    <p>Don't Neglect Diet: An unhealthy diet can lead to skin issues. Consume foods rich in vitamins and minerals.</p>
  </div>
</section>

<button class="solid-btn floating-bottom" data-report>Back to Skin Report</button>

    </section>
  </main>
  

</div>
@endsection

@push('scripts')
<script>
  document.body.dataset.skinDiaryUrl = @json(route('profile.skin-diary'));
  document.body.dataset.skinReportUrl = @json(route('skin-analyzer.report'));
  document.body.dataset.skinRecommendationUrl = @json(route('skin-analyzer.recommendations'));
</script>
<script src="{{ asset('skin-report/skin-report.js') }}"></script>
@endpush
