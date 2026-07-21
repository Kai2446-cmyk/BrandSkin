@extends('layouts.app')

@section('title', 'GlowSkin - Skin Report Detail')

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
    <section class="sa-frame detail-frame">
      
<button class="nav-back" type="button" data-history-list aria-label="Back to Skin Report History"><svg><use href="{{ asset('skin-report/assets/icons.svg') }}#back"></use></svg></button>

<section class="hero-scan">
  <img src="{{ asset('skin-report/assets/face.png') }}" alt="User skin scan result">
</section>

<section class="report-strip">
  <h2>Your Skin Report</h2>
  <div id="scoreCarousel" class="score-carousel">
    <button class="score-card active" type="button" data-score-key="health"><div class="score-ring medium">6/10</div><p>Skin<br>Health</p></button>
<button class="score-card" type="button" data-score-key="acne"><div class="score-ring medium">6/10</div><p>Acne</p></button>
<button class="score-card" type="button" data-score-key="texture"><div class="score-ring medium">6/10</div><p>Texture</p></button>
<button class="score-card" type="button" data-score-key="pore"><div class="score-ring bad">4/10</div><p>Pore Size</p></button>
<button class="score-card" type="button" data-score-key="spots"><div class="score-ring medium">6/10</div><p>Dark<br>Spots</p></button>
<button class="score-card" type="button" data-score-key="brightness"><div class="score-ring good">7/10</div><p>Brightness</p></button>
  </div>
  <div class="score-note">The higher the score, the better</div>
</section>

<section class="card info-card center analysis-detail-card">
  <h2 id="analysisTitle">Skin Health</h2>
  <div class="score-text">Your score: <span id="analysisScore" class="medium">6/10</span></div>
  <div id="analysisTip" class="tip">Moderate. No worries, read on for more tips!</div>
  <p id="analysisDescription">You're rocking it! Keep in mind, dedication pays off on your journey to flawless skin. Embrace your uniqueness and keep pushing forward with style!</p>
</section>

<section class="card dynamic-detail-card" id="causesCard">
  <h2 id="causesTitle">What causes dull skin?</h2>
  <div class="reason-list" id="causesList"></div>
</section>

<section class="card dynamic-detail-card" id="tipsCard">
  <h2 id="tipsTitle">How to get glowing skin?</h2>
  <img class="tips-illustration" src="{{ asset('skin-report/assets/tips.png') }}" alt="GlowSkin care tips">
  <div class="reason-list" id="tipsList"></div>
</section>


<section class="card info-card" id="skinSummarySection">
  <h2>Your Skin Summary</h2>
  <div class="summary-item">
  <span class="number-badge">1</span>
  <img src="{{ asset('skin-report/assets/pore-size.png') }}" alt="Pore Size">
  <div><h3>Pore Size</h3><p>Genetics and excess sebum affect large pore size. Aging, reduced elasticity, sun damage, and inflammation also contribute to pore enlargement.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">2</span>
  <img src="{{ asset('skin-report/assets/dark-spots.jpg') }}" alt="Dark Spots">
  <div><h3>Dark Spots</h3><p>Dark spots form from excess melanin due to sun exposure, hormones, injuries, or inflammation. Acne, aging, and skin conditions can also cause hyperpigmentation.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">3</span>
  <img src="{{ asset('skin-report/assets/skin-texture.png') }}" alt="Texture">
  <div><h3>Texture</h3><p>Rough skin results from dead cells, poor exfoliation, and reduced collagen. Environmental factors like sun exposure and pollution worsen elasticity and texture.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">4</span>
  <img src="{{ asset('skin-report/assets/acne.png') }}" alt="Acne">
  <div><h3>Acne</h3><p>Excess sebum leads to clogged follicles, fostering acne bacteria growth, causing inflammation and forming pimples, blackheads, and whiteheads.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">5</span>
  <img src="{{ asset('skin-report/assets/brightness.png') }}" alt="Brightness">
  <div><h3>Brightness</h3><p>Dullness arises from insufficient exfoliation, dead skin buildup, dehydration, and low collagen. Sun damage, pollution, stress, and lifestyle add to diminished radiance.</p></div>
</div>
</section>

<section class="card info-card" id="maintainAspectSection">
  <h2>Maintain This Aspect</h2>
  <div class="summary-item">
  <span class="number-badge">1</span>
  <img src="{{ asset('skin-report/assets/pore-size.png') }}" alt="Pore Size">
  <div><h3>Pore Size</h3><p>Keep using suitable products consistently to maintain progress and support healthier-looking skin.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">2</span>
  <img src="{{ asset('skin-report/assets/dark-spots.jpg') }}" alt="Dark Spots">
  <div><h3>Dark Spots</h3><p>Your care is improving this aspect. Stay consistent and protect your skin daily for better long-term results.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">3</span>
  <img src="{{ asset('skin-report/assets/skin-texture.png') }}" alt="Texture">
  <div><h3>Texture</h3><p>Your care is improving this aspect. Stay consistent and protect your skin daily for better long-term results.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">4</span>
  <img src="{{ asset('skin-report/assets/acne.png') }}" alt="Acne">
  <div><h3>Acne</h3><p>Your care is improving this aspect. Stay consistent and protect your skin daily for better long-term results.</p></div>
</div>
<div class="summary-item">
  <span class="number-badge">5</span>
  <img src="{{ asset('skin-report/assets/brightness.png') }}" alt="Brightness">
  <div><h3>Brightness</h3><p>Your care is improving this aspect. Stay consistent and protect your skin daily for better long-term results.</p></div>
</div>
</section>

<button class="outline-btn bottom-link" data-history-list>View All Skin Reports <svg style="width:18px;height:18px;margin-left:8px"><use href="{{ asset('skin-report/assets/icons.svg') }}#chevron"></use></svg></button>
<button class="solid-btn floating-bottom" data-recommendation>View Product Recommendations</button>

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
