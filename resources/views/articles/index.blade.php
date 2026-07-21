@extends('layouts.app')

@section('title', 'Articles — GlowSkin')

@section('content')
@php
    $articles = collect($articles ?? [])->values();
    $featured = $featured ?: $articles->first();
    $activeCategory = 'all';

    $fallbackHero = 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=1400&q=80';
    $heroImageRaw = $featured?->image ?: $fallbackHero;
    $heroImage = \Illuminate\Support\Str::startsWith($heroImageRaw, ['http://','https://']) ? $heroImageRaw : asset($heroImageRaw);

    $gridArticles = $articles;

    $defaultCategories = collect(['Tutorial', 'How To', 'Tips', 'Skincare', 'Makeup']);
    $categories = collect($categories ?? [])
        ->merge($defaultCategories)
        ->filter()
        ->unique()
        ->values();
@endphp

<link rel="stylesheet" href="{{ asset('css/articles-display-esthetic.css') }}">
<link rel="stylesheet" href="{{ asset('css/article-navbar-gap-fix.css') }}">
<link rel="stylesheet" href="{{ asset('css/article-tabs-no-loading.css') }}">
<link rel="stylesheet" href="{{ asset('css/articles-show-all-fix.css') }}">
<link rel="stylesheet" href="{{ asset('css/article-hero-lasts-position.css') }}">

<main class="articles-page">
    <section class="articles-hero">
        <div class="articles-hero-copy">
            <div class="articles-breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>/</span>
                <span>What's New</span>
            </div>

            <span class="articles-hero-kicker">{{ strtoupper($featured->category ?? 'Beauty Journal') }}</span>

            @if($featured)
                <div class="articles-hero-date">
                    {{ optional($featured->published_at ?? $featured->created_at)->format('F d, Y') }}
                </div>

                <h1>{{ $featured->title }}</h1>

                <div class="articles-hero-actions">
                    <a href="{{ route('articles.show', $featured->slug) }}">Read More</a>
                    <a href="#articleList" data-no-page-loader>View All Articles</a>
                </div>
            @else
                <h1>GlowSkin Beauty Journal</h1>
                <p>Artikel beauty akan tampil di sini setelah admin menambahkan data artikel.</p>
            @endif

            <div class="articles-hero-progress">
                <span></span>
            </div>
        </div>

        <div class="articles-hero-image">
            <img src="{{ $heroImage }}" alt="{{ $featured->title ?? 'GlowSkin Article' }}">
        </div>
    </section>

    <section class="articles-category-strip" data-article-tabs>
        <a href="{{ route('articles.index') }}"
           class="active"
           data-article-filter="all"
           data-no-page-loader>
            All Articles
        </a>

        @foreach($categories->take(5) as $category)
            <a href="{{ route('articles.index', ['category' => $category]) }}"
               data-article-filter="{{ strtolower($category) }}"
               data-no-page-loader>
                {{ $category }}
            </a>
        @endforeach
    </section>

    <section class="articles-grid-section" id="articleList">
        @if($gridArticles->isEmpty())
            <div class="articles-empty">
                <h2>Belum ada artikel</h2>
                <p>Artikel akan muncul otomatis setelah admin menambahkannya di dashboard.</p>
            </div>
        @else
            <div class="articles-grid" data-article-grid>
                @foreach($gridArticles as $article)
                    @php
                        $imgRaw = $article->image ?: $fallbackHero;
                        $img = \Illuminate\Support\Str::startsWith($imgRaw, ['http://','https://']) ? $imgRaw : asset($imgRaw);
                        $categoryKey = strtolower($article->category ?? 'beauty tips');
                        $titleText = $article->title ?: 'Untitled Article';
                    @endphp

                    <article class="article-card reveal-article" data-article-category="{{ $categoryKey }}">
                        <a href="{{ route('articles.show', $article->slug) }}" class="article-card-img">
                            <img src="{{ $img }}" alt="{{ $titleText }}" loading="lazy">
                        </a>

                        <div class="article-card-body">
                            <span>{{ optional($article->published_at ?? $article->created_at)->format('F d, Y') }}</span>
                            <a href="{{ route('articles.show', $article->slug) }}">{{ $titleText }}</a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="articles-load">
                <button type="button" data-article-show-all>Load More</button>
            </div>
        @endif
    </section>
</main>

<script src="{{ asset('js/articles-display-esthetic.js') }}"></script>
@endsection
