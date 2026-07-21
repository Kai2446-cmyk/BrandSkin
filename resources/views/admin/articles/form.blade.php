@extends('layouts.admin')
@section('title', ($mode === 'edit' ? 'Edit Artikel' : 'Tambah Artikel').' — Admin GlowSkin')
@section('content')
<link rel="stylesheet" href="{{ asset('css/admin-form-select-fix.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin-article-block-editor.css') }}">
@php
  $isEdit = $mode === 'edit';
  $img = fn($path) => \Illuminate\Support\Str::startsWith($path ?? '', ['http://','https://']) ? $path : asset($path ?: 'assets/images/no_image.png');

  /*
   * PATCH:
   * Kategori dan Tag artikel dibuat dropdown.
   * Opsi utama tetap mengikuti data yang sudah ada di database.
   */
  $dbCategories = \App\Models\Article::query()
      ->whereNotNull('category')
      ->where('category', '!=', '')
      ->select('category')
      ->distinct()
      ->orderBy('category')
      ->pluck('category')
      ->toArray();

  $dbTags = \App\Models\Article::query()
      ->whereNotNull('tag')
      ->where('tag', '!=', '')
      ->select('tag')
      ->distinct()
      ->orderBy('tag')
      ->pluck('tag')
      ->toArray();

  $defaultCategories = [
      'Tutorial',
      'How To',
      'Tips',
      'Skincare',
      'Makeup',
      'Beauty Tips',
      'Product Review',
      'Ingredient Guide',
      'Routine',
  ];

  $defaultTags = [
      'Tutorial',
      'Makeup',
      'Skincare',
      'Glow',
      'Serum',
      'Foundation',
      'Lip',
      'Eyes',
      'Beauty Tips',
      'Routine',
  ];

  $categoryOptions = collect(array_merge($dbCategories, $defaultCategories))->filter()->unique()->values();
  $tagOptions = collect(array_merge($dbTags, $defaultTags))->filter()->unique()->values();

  $selectedCategory = old('category', $article->category);
  $selectedTag = old('tag', $article->tag);
@endphp

<section class="admin-page-head">
  <div>
    <p>ARTICLE FORM</p>
    <h1>{{ $isEdit ? 'Edit Artikel' : 'Tambah Artikel' }}</h1>
    <span>{{ $isEdit ? 'Ubah artikel yang sudah tampil.' : 'Tambahkan artikel baru ke halaman Articles.' }}</span>
  </div>
  <a href="{{ route('admin.articles.index') }}" class="admin-head-btn bg-white/10">Back</a>
</section>

@if($errors->any())
  <div class="admin-error mb-5">
    @foreach($errors->all() as $error)
      <div>{{ $error }}</div>
    @endforeach
  </div>
@endif

<form method="POST" action="{{ $isEdit ? route('admin.articles.update', $article) : route('admin.articles.store') }}" enctype="multipart/form-data" class="admin-panel admin-form-wide">
  @csrf
  @if($isEdit) @method('PUT') @endif

  <div class="admin-form-preview">
    <img src="{{ $img(old('image_url', $article->image)) }}" alt="Preview">
    <div>
      <strong>{{ $article->title ?: 'Preview Artikel' }}</strong>
      <span>Upload gambar baru atau isi link gambar.</span>
    </div>
  </div>

  <div class="grid md:grid-cols-2 gap-4">
    <label class="admin-field md:col-span-2">
      <span>Judul Artikel</span>
      <input name="title" value="{{ old('title', $article->title) }}" required>
    </label>

    <label class="admin-field">
      <span>Kategori</span>
      <select name="category" required>
        <option value="">Pilih kategori artikel</option>
        @foreach($categoryOptions as $category)
          <option value="{{ $category }}" @selected($selectedCategory === $category)>{{ $category }}</option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Tag</span>
      <select name="tag">
        <option value="">Pilih tag artikel</option>
        @foreach($tagOptions as $tag)
          <option value="{{ $tag }}" @selected($selectedTag === $tag)>{{ $tag }}</option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Upload Gambar Artikel</span>
      <input type="file" name="image" accept="image/*">
    </label>

    <label class="admin-field">
      <span>Atau Link Gambar</span>
      <input name="image_url" value="{{ old('image_url', $article->image) }}" placeholder="https://...">
    </label>

    <label class="admin-field md:col-span-2">
      <span>Caption Hero Gambar</span>
      <input name="hero_caption" value="{{ old('hero_caption', $article->hero_caption) }}" maxlength="500" placeholder="Contoh: Rangkaian skincare untuk menjaga skin barrier tetap sehat.">
      <small class="article-field-help">Caption akan tampil tepat di bawah hero gambar artikel.</small>
    </label>

    <label class="admin-field md:col-span-2">
      <span>Deskripsi Singkat</span>
      <textarea name="excerpt" rows="3">{{ old('excerpt', $article->excerpt) }}</textarea>
    </label>

    <div class="admin-field md:col-span-2 article-editor-field">
      <span>Isi Artikel</span>
      <div class="article-editor-shell" data-article-editor>
        <div class="article-editor-toolbar" role="toolbar" aria-label="Pengaturan tulisan artikel">
          <button type="button" data-command="bold" title="Tebal"><strong>B</strong></button>
          <button type="button" data-command="italic" title="Miring"><em>I</em></button>
          <span class="article-toolbar-divider"></span>
          <button type="button" data-align="left" title="Rata kiri">⇤</button>
          <button type="button" data-align="center" title="Rata tengah">↔</button>
          <button type="button" data-align="right" title="Rata kanan">⇥</button>
          <span class="article-toolbar-note">Klik elemen lalu atur posisinya</span>
        </div>

        <div id="articleBlockCanvas" class="article-block-canvas" contenteditable="true" data-placeholder="Mulai tulis artikel di sini..."></div>
        <textarea id="articleContentInput" name="content" hidden required>{{ old('content', $article->content) }}</textarea>

        <div class="article-add-wrap">
          <button class="article-add-button" type="button" id="articleAddButton" aria-expanded="false" aria-controls="articleAddMenu" title="Tambah blok artikel">+</button>
          <div class="article-add-menu" id="articleAddMenu" hidden>
            <button type="button" data-insert="paragraph"><span>¶</span><div><strong>Paragraf</strong><small>Teks isi artikel</small></div></button>
            <button type="button" data-insert="heading"><span>H2</span><div><strong>Subjudul</strong><small>Pembatas bagian artikel</small></div></button>
            <button type="button" data-insert="readmore"><span>↗</span><div><strong>Baca Juga</strong><small>Pilih artikel dari website</small></div></button>
          </div>
        </div>
      </div>
      <small class="article-field-help">Gunakan tombol + di kanan bawah untuk menambah paragraf, subjudul, atau tautan “Baca Juga”.</small>
    </div>
  </div>


  <section class="article-live-preview" id="articleLivePreview">
    <div class="article-live-preview-head">
      <div>
        <small>LIVE PREVIEW</small>
        <h2>Preview Halaman Detail Artikel</h2>
        <p>Preview ini mengikuti isian form dan tidak menyimpan data sebelum tombol artikel ditekan.</p>
      </div>
      <button type="button" id="articlePreviewToggle" class="article-preview-toggle" aria-expanded="true">Tutup Preview</button>
    </div>
    <div id="articlePreviewBody" class="article-preview-body">
      <div class="article-preview-browser">
        <div class="article-preview-browser-bar"><i></i><i></i><i></i><span>Preview artikel GlowSkin</span></div>
        <div class="article-preview-page">
          <figure class="article-preview-hero">
            <img id="articlePreviewImage" src="{{ $img(old('image_url', $article->image)) }}" alt="Preview hero artikel">
            <figcaption id="articlePreviewCaption">{{ old('hero_caption', $article->hero_caption) ?: 'Caption hero gambar akan tampil di sini.' }}</figcaption>
          </figure>
          <div class="article-preview-content-wrap">
            <nav class="article-preview-breadcrumb">Home <span>/</span> Articles <span>/</span> <b id="articlePreviewBreadcrumb">{{ old('title', $article->title) ?: 'Judul Artikel' }}</b></nav>
            <p class="article-preview-category" id="articlePreviewCategory">{{ old('category', $article->category) ?: 'BEAUTY HIGHLIGHTS' }}</p>
            <h1 id="articlePreviewTitle">{{ old('title', $article->title) ?: 'Judul Artikel Akan Tampil di Sini' }}</h1>
            <div class="article-preview-meta"><span>{{ now()->format('F d, Y') }}</span><span>Share to ● ● ● ●</span></div>
            <div class="article-preview-toc"><strong>Isi dalam artikel ini</strong><ol><li id="articlePreviewTocTitle">{{ old('title', $article->title) ?: 'Judul Artikel' }}</li></ol></div>
            <div class="article-preview-excerpt" id="articlePreviewExcerpt">{{ old('excerpt', $article->excerpt) ?: 'Deskripsi singkat artikel akan tampil di bagian pembuka.' }}</div>
            <div class="article-preview-content" id="articlePreviewContent"><p>Isi artikel akan tampil di sini saat admin mulai menulis.</p></div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="admin-checkbox-row">
    <label class="admin-check">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $article->is_active ?? true))>
      Artikel Aktif
    </label>
  </div>

  <div class="admin-form-actions">
    <button class="green-gradient-btn admin-head-btn" type="submit">{{ $isEdit ? 'Save Artikel' : 'Tambah Artikel' }}</button>
    <a href="{{ route('admin.articles.index') }}" class="admin-head-btn bg-white/10">Cancel</a>
  </div>

  <div class="article-related-modal" id="articleRelatedModal" hidden aria-hidden="true">
    <div class="article-related-dialog" role="dialog" aria-modal="true" aria-labelledby="articleRelatedTitle">
      <div class="article-related-head">
        <div>
          <small>BACA JUGA</small>
          <h3 id="articleRelatedTitle">Pilih Artikel dari Website</h3>
          <p>Judul dan link diambil langsung dari artikel GlowSkin yang sudah tersedia.</p>
        </div>
        <button type="button" class="article-related-close" data-related-close aria-label="Tutup">×</button>
      </div>
      <label class="article-related-search">
        <span>⌕</span>
        <input type="search" id="articleRelatedSearch" placeholder="Cari judul atau kategori artikel..." autocomplete="off">
      </label>
      <div class="article-related-list" id="articleRelatedList"></div>
      <div class="article-related-empty" id="articleRelatedEmpty" hidden>Tidak ada artikel yang cocok.</div>
      <div class="article-related-actions">
        <button type="button" class="article-related-cancel" data-related-close>Batal</button>
      </div>
    </div>
  </div>

</form>
@php
    $availableArticlesPayload = ($availableArticles ?? collect())
        ->map(function ($item) use ($img) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'category' => $item->category,
                'image' => $img($item->image),
                'url' => route('articles.show', $item->slug),
            ];
        })
        ->values()
        ->all();
@endphp
<script>
window.GlowSkinArticleEditorInitialContent = @json(old('content', $article->content));
window.GlowSkinAvailableArticles = @json($availableArticlesPayload);
</script>
<script src="{{ asset('js/admin-article-block-editor.js') }}?v={{ file_exists(public_path('js/admin-article-block-editor.js')) ? filemtime(public_path('js/admin-article-block-editor.js')) : time() }}"></script>
@endsection
