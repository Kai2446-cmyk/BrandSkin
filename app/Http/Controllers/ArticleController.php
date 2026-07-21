<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Support\DatabaseColumn;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        /*
         * Semua artikel tetap diambil dari database yang sudah ada.
         * Pengecekan kolom memakai helper aman agar tidak memanggil
         * generation_expression di information_schema pada MySQL/MariaDB lama.
         */
        $query = Article::query();

        if (DatabaseColumn::has('articles', 'view_count')) {
            $query->orderByDesc('view_count');
        }

        $articles = $query->latest()->get();
        $featured = $articles->first();

        $categories = $articles
            ->pluck('category')
            ->filter()
            ->unique()
            ->values();

        if ($categories->isEmpty()) {
            $categories = collect(['Tutorial', 'How To', 'Tips', 'Skincare', 'Makeup']);
        }

        return view('articles.index', [
            'articles' => $articles,
            'featured' => $featured,
            'categories' => $categories,
            'activeCategory' => 'all',
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function show(string $slug)
    {
        $article = Article::where('slug', $slug)->firstOrFail();

        /*
         * Hitung satu pembukaan per artikel dalam satu sesi browser.
         * Refresh berulang tidak menambah angka secara palsu, tetapi user/browser
         * lain tetap tercatat sebagai pembaca baru.
         */
        $sessionKey = 'glowskin_article_viewed_'.$article->id;
        if (! session()->has($sessionKey)) {
            if (DatabaseColumn::has('articles', 'read_count')) {
                $article->increment('read_count');
                $article->refresh();
            } elseif (DatabaseColumn::has('articles', 'view_count')) {
                $article->increment('view_count');
                $article->refresh();
            }

            session()->put($sessionKey, now()->timestamp);
        }

        $relatedArticles = Article::where('id', '!=', $article->id)
            ->when($article->category, function ($query) use ($article) {
                $query->where('category', $article->category);
            })
            ->latest()
            ->take(3)
            ->get();

        if ($relatedArticles->count() < 3) {
            $extraArticles = Article::where('id', '!=', $article->id)
                ->whereNotIn('id', $relatedArticles->pluck('id'))
                ->latest()
                ->take(3 - $relatedArticles->count())
                ->get();

            $relatedArticles = $relatedArticles->merge($extraArticles);
        }

        $relatedProducts = Product::query()
            ->latest()
            ->take(1)
            ->get();

        return view('articles.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'relatedProducts' => $relatedProducts,
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }
}
