@extends('layouts.admin')

@section('title', 'Pengaturan Artikel — GlowSkin')

@section('content')
@php
    /*
      PATCH RESTORE:
      - Mengembalikan isi dashboard admin artikel.
      - Fix blank page karena section sebelumnya tidak kebaca layout admin.
      - Fix undefined variable $keyword dengan fallback request('q').
    */
    $keyword = $keyword ?? request('q', '');
    $articles = $articles ?? collect();

    $imgUrl = function ($raw) {
        $raw = $raw ?: 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=85';
        return \Illuminate\Support\Str::startsWith($raw, ['http://','https://']) ? $raw : asset($raw);
    };
@endphp

<link rel="stylesheet" href="{{ asset('css/admin-articles-lavender-refresh.css') }}?v=20260713">

<main class="admin-articles-restore">
    <section class="admin-articles-head">
        <div>
            <span class="admin-articles-eyebrow">ARTICLE MANAGEMENT</span>
            <h1>PENAMBAHAN ARTIKEL</h1>
            <p>Data artikel yang sudah tampil dari database.</p>
        </div>

        <div class="admin-articles-actions">
            <a href="{{ route('admin.articles.create') }}" class="admin-articles-btn">Tambah Artikel</a>
            <a href="{{ route('admin.dashboard') }}" class="admin-articles-btn dark">Back</a>
        </div>
    </section>

    <form method="GET" action="{{ route('admin.articles.index') }}" class="admin-articles-search">
        <input name="q"
               value="{{ $keyword }}"
               placeholder="Search artikel berdasarkan judul, kategori, atau tag...">

        <button type="submit">Search</button>

        @if($keyword)
            <a href="{{ route('admin.articles.index') }}">Reset</a>
        @endif
    </form>

    <section class="admin-articles-card">
        <div class="admin-articles-table-wrap">
            <table class="admin-articles-table">
                <thead>
                    <tr>
                        <th>Artikel</th>
                        <th>Kategori</th>
                        <th>Tag</th>
                        <th>Dibaca</th>
                        <th>Tanggal</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($articles as $article)
                        <tr>
                            <td>
                                <div class="admin-articles-list">
                                    <img src="{{ $imgUrl($article->image ?? null) }}" alt="{{ $article->title ?? 'Article' }}">
                                    <div>
                                        <strong>{{ $article->title ?? '-' }}</strong>
                                        <span>{{ \Illuminate\Support\Str::limit($article->excerpt ?? $article->short_description ?? $article->description ?? 'Beauty article content.', 78) }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $article->category ?? '-' }}</td>
                            <td>{{ $article->tag ?? '-' }}</td>
                            <td>{{ number_format((int)($article->views ?? $article->read_count ?? 0), 0, ',', '.') }}</td>
                            <td>
                                @if(!empty($article->created_at))
                                    {{ \Illuminate\Support\Carbon::parse($article->created_at)->format('d M Y') }}
                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-right">
                                <a href="{{ route('admin.articles.edit', $article->id) }}" class="admin-articles-mini-btn">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="admin-articles-empty">
                                    <h3>Belum ada artikel</h3>
                                    <p>Tambahkan artikel baru untuk ditampilkan di website.</p>
                                    <a href="{{ route('admin.articles.create') }}" class="admin-articles-btn">Tambah Artikel</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if(method_exists($articles, 'links'))
        <div class="admin-articles-pagination">
            {{ $articles->links() }}
        </div>
    @endif
</main>
@endsection
