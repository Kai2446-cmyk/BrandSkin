@extends('layouts.app')

@section('title', 'Skin Diary — GlowSkin')

@push('styles')
    
<link rel="stylesheet" href="{{ asset('css/profile-page.css') }}?v=20260710-profile-base">
<link rel="stylesheet" href="{{ asset('css/profile-pages-lavender-navbar-order-fix.css') }}?v=20260710">
    <link rel="stylesheet" href="{{ asset('css/skin-diary.css') }}">
@endpush

@section('content')
@php
    /*
     | Data siap menerima hasil AI asli dari controller nanti.
     | Controller cukup mengirim $skinReports berupa Collection/array.
     | Saat belum ada hasil asli, halaman menampilkan satu contoh dummy.
     */
    $reports = collect($skinReports ?? [])->map(function ($report) {
        return is_array($report) ? (object) $report : $report;
    });

    $usingExample = $reports->isEmpty();

    if ($usingExample) {
        $reports = collect([(object) [
            'id' => null,
            'analyzed_at' => '2026-06-30 10:54:00',
            'photo_url' => asset('skin-report/assets/face.png'),
            'skin_health' => 6,
            'brightness' => 7,
            'acne' => 4,
            'texture' => 6,
            'pore_size' => 5,
            'dark_spots' => 3,
            'report_url' => Route::has('skin-analyzer.report') ? route('skin-analyzer.report') : '#',
        ]]);
    }

    $latestReport = $reports->first();

    $reportDate = function ($report, $format = 'd M Y · H:i') {
        try {
            return \Carbon\Carbon::parse($report->analyzed_at ?? $report->created_at ?? now())->format($format);
        } catch (\Throwable $e) {
            return '-';
        }
    };

    $reportPhoto = function ($report) {
        return $report->photo_url
            ?? $report->image_url
            ?? (!empty($report->photo_path) ? asset('storage/'.$report->photo_path) : null)
            ?? asset('skin-report/assets/face.png');
    };

    $reportLink = function ($report) {
        if (!empty($report->report_url)) return $report->report_url;
        if (!empty($report->id) && Route::has('skin-analyzer.report.show')) {
            return route('skin-analyzer.report.show', $report->id);
        }
        return Route::has('skin-analyzer.report') ? route('skin-analyzer.report') : '#';
    };
@endphp

<main class="profile-page skin-diary-page">
    <section class="profile-shell">
        <aside class="profile-sidebar">
            <a href="{{ Route::has('profile.index') ? route('profile.index') : url('/profile') }}">My Profile <span>♥</span></a>
            <a href="{{ Route::has('profile.orders') ? route('profile.orders') : url('/profile/orders') }}">My Order</a>
            <a href="{{ Route::has('wishlist.index') ? route('wishlist.index') : url('/wishlist') }}">My Wishlist</a>
            <a href="{{ Route::has('profile.vouchers') ? route('profile.vouchers') : url('/profile/vouchers') }}">My Voucher</a>
            <a href="{{ Route::has('profile.skin-diary') ? route('profile.skin-diary') : url('/profile/skin-diary') }}" class="active">Skin Diary</a>
        </aside>

        <section class="profile-card skin-diary-card">
            <div class="profile-head">
                <div>
                    <span>Skin Journey</span>
                    <h1>My Skin Diary</h1>
                    <p>Riwayat hasil Skin Analyzer dan perkembangan kondisi kulit kamu.</p>
                </div>
                <a class="skin-diary-analyze" href="{{ Route::has('skin-analyzer.index') ? route('skin-analyzer.index') : url('/skin-analyzer') }}">
                    Analyze Again
                </a>
            </div>

            @if($usingExample)
                <div class="skin-diary-example-note">
                    <strong>Contoh tampilan hasil analisis</strong>
                    <span>Data asli dari AI Skin Analyzer nanti otomatis menggantikan contoh ini dengan desain yang sama.</span>
                </div>
            @endif

            <div class="skin-diary-grid">
                <article class="diary-feature">
                    <img src="{{ $reportPhoto($latestReport) }}" alt="Latest skin analysis">
                    <div>
                        <span>{{ $usingExample ? 'Example Report' : 'Latest Report' }}</span>
                        <h2>{{ $reportDate($latestReport) }}</h2>
                        <p>
                            Skin Health {{ (int)($latestReport->skin_health ?? 0) }}/10
                            · Brightness {{ (int)($latestReport->brightness ?? 0) }}/10
                        </p>
                        <a href="{{ $reportLink($latestReport) }}">View Skin Report →</a>
                    </div>
                </article>

                <article class="diary-summary">
                    <h2>Photo History</h2>
                    <img src="{{ $reportPhoto($latestReport) }}" alt="Photo history">
                    <p>Bandingkan hasil analisis dari waktu ke waktu.</p>
                </article>
            </div>

            <section class="diary-history" id="all-history">
                <div class="diary-history-head">
                    <div>
                        <span>Report Archive</span>
                        <h2>Skin Report History</h2>
                    </div>
                    <input type="search" data-diary-search placeholder="Search by date...">
                </div>

                <div class="diary-history-list" data-diary-list>
                    @foreach($reports as $report)
                        @php $searchValue = strtolower($reportDate($report, 'd M Y H:i')); @endphp
                        <article data-diary-item="{{ $searchValue }}">
                            <div class="diary-report-icon">✦</div>
                            <div>
                                <strong>{{ $reportDate($report, 'd M Y') }}</strong>
                                <span>{{ $reportDate($report, 'H:i') }}</span>
                            </div>
                            <a href="{{ $reportLink($report) }}">Open Report</a>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="diary-insights">
                <span>Personalised Insights</span>
                <h2>Recommended for your skin</h2>
                <div>
                    <article>
                        <img src="{{ asset('skin-report/assets/tips-element.png') }}" alt="Skin tips">
                        <h3>Routine Tepat untuk Kondisi Kulit Kamu</h3>
                    </article>
                    <article>
                        <img src="{{ asset('skin-report/assets/tips.png') }}" alt="Glow tips">
                        <h3>Tips Menjaga Pori dan Tekstur Kulit</h3>
                    </article>
                </div>
            </section>
        </section>
    </section>
</main>
@endsection

@push('scripts')
    <script src="{{ asset('js/skin-diary.js') }}"></script>
@endpush
