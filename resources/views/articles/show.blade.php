@extends('layouts.app')

@section('title', ($article->title ?? 'Artikel').' — GlowSkin')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/article-detail-reference.css') }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $fallbackHero = asset('assets/article-detail/fallback-hero.jpg');
    $imageRaw = $article->image ?: $fallbackHero;
    $articleImage = Str::startsWith($imageRaw, ['http://', 'https://']) ? $imageRaw : asset($imageRaw);
    $category = $article->category ?: 'Beauty Highlights';
    $date = optional($article->published_at ?? $article->created_at)->format('F d, Y');
    $currentUrl = urlencode(request()->fullUrl());
    $shareTitle = urlencode($article->title ?? 'GlowSkin Article');
    $content = trim((string) ($article->content ?? ''));
    $hasHtmlContent = $content !== strip_tags($content);

    $paragraphs = collect(preg_split('/\R{2,}/', $content))
        ->map(fn ($item) => trim($item))
        ->filter()
        ->values();

    $relatedArticles = collect($relatedArticles ?? [])->values();
    $relatedProducts = collect($relatedProducts ?? [])->values();

    $imageFor = function ($raw, $fallback) {
        if (blank($raw)) {
            return $fallback;
        }

        return \Illuminate\Support\Str::startsWith($raw, ['http://', 'https://']) ? $raw : asset($raw);
    };
@endphp

<main class="article-detail-page">
    <section class="article-detail-hero" aria-label="Hero Artikel">
        <img src="{{ $articleImage }}" alt="{{ $article->alt ?: $article->title }}">
        @if(filled($article->hero_caption))
            <p class="article-detail-hero-caption">{{ $article->hero_caption }}</p>
        @endif
    </section>

    <article class="article-detail-shell">
        <div class="article-detail-inner">
            <nav class="article-detail-breadcrumbs" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <a href="{{ route('articles.index') }}">Articles</a>
                <span>/</span>
                <span>{{ $article->title }}</span>
            </nav>

            <p class="article-detail-topic">{{ $category }}</p>

            <h1 class="article-detail-title">{{ $article->title }}</h1>

            <div class="article-detail-meta-row">
                <time datetime="{{ optional($article->published_at ?? $article->created_at)->format('Y-m-d') }}">{{ $date }}</time>

                <div class="article-detail-share-box" aria-label="Share artikel">
                    <span>Share to</span>
                    <a class="article-detail-share-link" href="https://www.facebook.com/sharer/sharer.php?u={{ $currentUrl }}" target="_blank" rel="noopener" aria-label="Share Facebook">
                        <img src="{{ asset('assets/article-detail/facebook.png') }}" alt="Facebook">
                    </a>
                    <a class="article-detail-share-link" href="https://twitter.com/intent/tweet?url={{ $currentUrl }}&text={{ $shareTitle }}" target="_blank" rel="noopener" aria-label="Share X">
                        <img src="{{ asset('assets/article-detail/x.png') }}" alt="X">
                    </a>
                    <a class="article-detail-share-link" href="https://pinterest.com/pin/create/button/?url={{ $currentUrl }}&description={{ $shareTitle }}" target="_blank" rel="noopener" aria-label="Share Pinterest">
                        <img src="{{ asset('assets/article-detail/pinterest.png') }}" alt="Pinterest">
                    </a>
                    <a class="article-detail-share-link" href="https://wa.me/?text={{ $shareTitle }}%20{{ $currentUrl }}" target="_blank" rel="noopener" aria-label="Share WhatsApp">
                        <img src="{{ asset('assets/article-detail/whatsapp.png') }}" alt="WhatsApp">
                    </a>
                </div>
            </div>

            <section class="article-detail-toc" aria-label="Isi dalam artikel ini">
                <h2>Isi dalam artikel ini</h2>
                <ol>
                    <li><a href="#article-main-content">{{ $article->title }}</a></li>
                    @if($relatedProducts->isNotEmpty())
                        <li><a href="#related-products">Related Products</a></li>
                    @endif
                    @if($relatedArticles->isNotEmpty())
                        <li><a href="#related-articles">Related Articles</a></li>
                    @endif
                </ol>
            </section>

            <section class="article-detail-content" id="article-main-content">
                @if(filled($article->excerpt))
                    <p>{{ $article->excerpt }}</p>
                @endif

                @if($hasHtmlContent)
                    {!! $content !!}
                @elseif($paragraphs->isNotEmpty())
                    @foreach($paragraphs as $paragraph)
                        <p>{!! nl2br(e($paragraph)) !!}</p>
                    @endforeach
                @else
                    <p>Konten artikel akan tampil di sini setelah admin mengisinya dari dashboard.</p>
                @endif
            </section>
        </div>
    </article>

    @if($relatedProducts->isNotEmpty())
        <section class="article-detail-related article-detail-related-products article-detail-container" id="related-products">
            <div class="article-detail-section-heading">
                <div>
                    <span>Shop the look</span>
                    <h2 class="article-detail-section-title">Related Products</h2>
                </div>
            </div>

            @foreach($relatedProducts->take(1) as $product)
                @php
                    $productImage = $imageFor($product->image ?? null, asset('assets/article-detail/fallback-product.jpg'));
                @endphp
                <div class="article-detail-product-card">
                    <div class="article-detail-product-media">
                        <a class="article-detail-product-image" href="{{ route('products.show', $product->slug) }}" aria-label="Lihat {{ $product->name }}">
                            <img src="{{ $productImage }}" alt="{{ $product->alt ?: $product->name }}">
                        </a>
                        <button class="article-detail-product-wishlist js-wishlist-toggle" type="button" data-product-id="{{ $product->id }}" aria-label="Tambah ke wishlist">♡</button>
                    </div>

                    <div class="article-detail-product-body">
                        <h3>{{ $product->name }}</h3>
                        <p>{{ $product->subtitle ?: Str::limit(strip_tags($product->description ?? ''), 150) ?: 'Produk pilihan GlowSkin yang masih relevan dengan artikel ini.' }}</p>

                        <div class="article-detail-product-footer">
                            <strong>Rp{{ number_format((float) $product->price, 0, ',', '.') }}</strong>
                            <a href="{{ route('products.show', $product->slug) }}">Lihat Produk</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if($relatedArticles->isNotEmpty())
        <section class="article-detail-related article-detail-related-articles article-detail-container" id="related-articles">
            <div class="article-detail-section-heading">
                <div>
                    <span>Explore more</span>
                    <h2 class="article-detail-section-title">Related Articles</h2>
                </div>
            </div>

            <div class="article-detail-editorial-board">
                @php $feature = $relatedArticles->first(); @endphp
                @if($feature)
                    @php $featureImage = $imageFor($feature->image ?? null, asset('assets/article-detail/fallback-article-1.jpg')); @endphp
                    <article class="article-detail-feature-article">
                        <img src="{{ $featureImage }}" alt="{{ $feature->alt ?: $feature->title }}">
                        <div class="article-detail-feature-body">
                            <time>{{ optional($feature->published_at ?? $feature->created_at)->format('F d, Y') }}</time>
                            <h3>{{ $feature->title }}</h3>
                            <a href="{{ route('articles.show', $feature->slug) }}">Baca Artikel</a>
                        </div>
                    </article>
                @endif

                <div class="article-detail-stacked-articles">
                    @foreach($relatedArticles->slice(1, 2)->values() as $index => $related)
                        @php
                            $fallback = $index === 0 ? asset('assets/article-detail/fallback-article-2.jpg') : asset('assets/article-detail/fallback-article-3.jpg');
                            $relatedImage = $imageFor($related->image ?? null, $fallback);
                        @endphp
                        <a href="{{ route('articles.show', $related->slug) }}" class="article-detail-side-article {{ $index === 1 ? 'is-dark' : '' }}">
                            <img src="{{ $relatedImage }}" alt="{{ $related->alt ?: $related->title }}">
                            <div class="article-detail-side-body">
                                <time>{{ optional($related->published_at ?? $related->created_at)->format('F d, Y') }}</time>
                                <h3>{{ $related->title }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</main>
@endsection
